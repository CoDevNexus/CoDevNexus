<?php
// ============================================================
// app/Controllers/Api/ContactoController.php
// POST /api/contacto — guarda mensaje + notifica + responde con progreso
// ============================================================
namespace App\Controllers\Api;

use Core\Controller;
use Core\Security;
use Core\Telegram;
use Core\Mailer;
use App\Models\MensajeModel;
use App\Models\ConfiguracionModel;

class ContactoController extends Controller
{
    public function store(): void
    {
        $ip = $this->request->ip();

        // Rate limit: max 3 mensajes por 10 minutos por IP
        if (!Security::rateLimit($ip, 'contacto', 3, 600)) {
            $this->json(['success' => false, 'message' => 'Demasiados intentos. Espera unos minutos.'], 429);
        }

        $nombre   = trim($this->request->post('nombre',   ''));
        $correo   = trim($this->request->post('correo',   '') ?: $this->request->post('email', ''));
        $telefono = trim($this->request->post('telefono', ''));
        $pais     = trim($this->request->post('pais',     ''));
        $asunto   = trim($this->request->post('asunto',   'Sin asunto'));
        $mensaje  = trim($this->request->post('mensaje',  ''));

        if (empty($nombre) || empty($correo) || empty($mensaje)) {
            $this->json(['success' => false, 'message' => 'Nombre, correo y mensaje son requeridos.'], 422);
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Correo electronico invalido.'], 422);
        }

        $nombre   = Security::sanitize($nombre);
        $correo   = Security::sanitize($correo);
        $telefono = Security::sanitize($telefono);
        $pais     = Security::sanitize($pais);
        $asunto   = Security::sanitize($asunto);
        $mensaje  = Security::sanitize($mensaje);

        $steps = [];

        // 1. Guardar en BD
        try {
            $model = new MensajeModel();
            $model->create([
                'nombre'     => $nombre,
                'correo'     => $correo,
                'telefono'   => $telefono ?: null,
                'pais'       => $pais ?: null,
                'asunto'     => $asunto,
                'mensaje'    => $mensaje,
                'ip_origen'  => $ip,
                'user_agent' => $this->request->userAgent(),
                'leido'      => 0,
                'respondido' => 0,
            ]);
            $steps[] = ['key' => 'db', 'label' => 'Mensaje guardado correctamente', 'ok' => true];
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Error al guardar. Intenta de nuevo.'], 500);
            return;
        }

        $cfg      = new ConfiguracionModel();
        $siteName = $cfg->get('site_name', 'CoDevNexus');
        $siteUrl  = defined('APP_URL') ? APP_URL : '';

        // 2. Telegram
        try {
            $tg   = new Telegram();
            if (!$tg->isConfigured()) {
                $steps[] = ['key' => 'telegram', 'label' => 'Telegram (no configurado)', 'ok' => false, 'skipped' => true];
            } else {
                $sent    = $tg->notifyContacto($nombre, $correo, $asunto, $telefono, $pais, $mensaje);
                $steps[] = ['key' => 'telegram', 'label' => 'Notificacion Telegram enviada', 'ok' => $sent];
            }
        } catch (\Throwable) {
            $steps[] = ['key' => 'telegram', 'label' => 'Telegram (error)', 'ok' => false];
        }

        // 3. Email al administrador
        try {
            $adminEmail = $cfg->get('smtp_admin_copy') ?: $cfg->get('smtp_from_email');
            if ($adminEmail) {
                $mailer  = new Mailer();
                $tblRows = self::buildAdminTableRows($nombre, $correo, $telefono, $pais, $asunto);
                $body    = self::adminEmailBody($tblRows, $mensaje, $siteUrl . '/admin/mensajes');
                $html    = Mailer::buildTemplate(
                    "Nuevo mensaje de {$nombre}",
                    $body,
                    "Nuevo contacto en {$siteName}",
                    $siteName,
                    $siteUrl,
                    'Ver en el Admin',
                    $siteUrl . '/admin/mensajes'
                );
                $mailer->send($adminEmail, "Nuevo mensaje de {$nombre} -- {$siteName}", $html);
                $masked  = substr($adminEmail, 0, 3) . '****' . strstr($adminEmail, '@');
                $steps[] = ['key' => 'email_admin', 'label' => 'Notificacion al administrador', 'ok' => true, 'detail' => $masked];
            } else {
                $steps[] = ['key' => 'email_admin', 'label' => 'Email admin (sin destinatario)', 'ok' => false, 'skipped' => true];
            }
        } catch (\Throwable $e) {
            $steps[] = ['key' => 'email_admin', 'label' => 'Email admin: ' . substr($e->getMessage(), 0, 80), 'ok' => false];
        }

        // 4. Copia al remitente
        try {
            $mailer     = new Mailer();
            $clientBody = self::clientEmailBody($nombre, $asunto, $mensaje);
            $html       = Mailer::buildTemplate(
                "Hemos recibido tu mensaje",
                $clientBody,
                "Confirmacion de recepcion",
                $siteName,
                $siteUrl
            );
            $mailer->send($correo, "Hemos recibido tu mensaje -- {$siteName}", $html);
            $maskedC = substr($correo, 0, 3) . '****' . strstr($correo, '@');
            $steps[] = ['key' => 'email_client', 'label' => 'Copia enviada a tu correo', 'ok' => true, 'detail' => $maskedC];
        } catch (\Throwable) {
            $steps[] = ['key' => 'email_client', 'label' => 'Email de confirmacion (error)', 'ok' => false];
        }

        $this->json([
            'success' => true,
            'message' => 'Mensaje enviado! Te hemos enviado una copia a tu correo.',
            'steps'   => $steps,
        ]);
    }

    private static function buildAdminTableRows(string $nombre, string $correo, string $telefono, string $pais, string $asunto): string
    {
        $td1 = 'style="padding:10px 0;border-bottom:1px solid #1e2d40;color:#64748b;font-size:13px;width:110px;vertical-align:top"';
        $td2 = 'style="padding:10px 0;border-bottom:1px solid #1e2d40;color:#e2e8f0;font-size:14px"';
        $h = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES);
        $rows = "<tr><td {$td1}>Nombre</td><td {$td2}>{$h($nombre)}</td></tr>"
              . "<tr><td {$td1}>Correo</td><td {$td2}><a href=\"mailto:{$h($correo)}\" style=\"color:#00d4ff\">{$h($correo)}</a></td></tr>";
        if ($telefono) $rows .= "<tr><td {$td1}>Telefono</td><td {$td2}>{$h($telefono)}</td></tr>";
        if ($pais)     $rows .= "<tr><td {$td1}>Pais</td><td {$td2}>{$h($pais)}</td></tr>";
        $rows .= "<tr><td {$td1}>Asunto</td><td {$td2}><strong>{$h($asunto)}</strong></td></tr>";
        return $rows;
    }

    private static function adminEmailBody(string $tableRows, string $mensaje, string $adminUrl): string
    {
        $h = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES);
        return "<h2 style=\"margin:0 0 6px;font-size:20px;font-weight:700;color:#00d4ff\">Nuevo mensaje de contacto</h2>"
             . "<p style=\"color:#64748b;margin:0 0 24px;font-size:13px\">Recibido el " . date('d/m/Y H:i') . "</p>"
             . "<table style=\"width:100%;border-collapse:collapse\">{$tableRows}</table>"
             . "<div style=\"margin-top:24px;padding:16px;background:#0b0f19;border-radius:8px;border:1px solid #1e2d40\">"
             . "<p style=\"margin:0 0 8px;color:#64748b;font-size:12px;text-transform:uppercase\">Mensaje</p>"
             . "<p style=\"margin:0;color:#e2e8f0;font-size:14px;line-height:1.8;white-space:pre-wrap\">" . $h($mensaje) . "</p></div>";
    }

    private static function clientEmailBody(string $nombre, string $asunto, string $mensaje): string
    {
        $h = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES);
        $preview = mb_substr($mensaje, 0, 280) . (mb_strlen($mensaje) > 280 ? '...' : '');
        return "<h2 style=\"margin:0 0 16px;font-size:20px;font-weight:700;color:#00d4ff\">Hemos recibido tu mensaje!</h2>"
             . "<p style=\"color:#e2e8f0;margin:0 0 20px;font-size:15px;line-height:1.7\">"
             . "Hola <strong style=\"color:#00d4ff\">{$h($nombre)}</strong>, gracias por escribirme. "
             . "Revisare tu mensaje y te respondre a la brevedad posible.</p>"
             . "<div style=\"padding:16px;background:#0b0f19;border-radius:8px;border:1px solid #1e2d40;margin-bottom:20px\">"
             . "<p style=\"margin:0 0 6px;color:#64748b;font-size:12px\">Asunto: <strong style=\"color:#e2e8f0\">{$h($asunto)}</strong></p>"
             . "<p style=\"margin:0;color:#94a3b8;font-size:13px;font-style:italic;line-height:1.6\">&ldquo;{$h($preview)}&rdquo;</p></div>"
             . "<p style=\"color:#64748b;font-size:13px;margin:0\">Este correo es una confirmacion automatica.</p>";
    }
}
