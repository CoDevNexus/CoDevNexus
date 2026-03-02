<?php
namespace App\Controllers\Admin;

use Core\Controller;
use Core\Mailer;
use App\Models\MensajeModel;
use App\Models\ConfiguracionModel;

class MensajesController extends Controller
{
    public function index(): void
    {
        $perPage = 20;
        $page    = max(1, (int)($this->request->get('page', 1)));
        $sort    = $this->request->get('sort', 'fecha');
        $dir     = $this->request->get('dir',  'DESC');

        $model  = new MensajeModel();
        $result = $model->getPaginated($page, $perPage, $sort, $dir);
        $pages  = (int) ceil($result['total'] / $perPage);

        $this->render('admin.mensajes.index', [
            'title'    => 'Mensajes · Admin',
            'mensajes' => $result['rows'],
            'total'    => $result['total'],
            'page'     => $page,
            'pages'    => max(1, $pages),
            'perPage'  => $perPage,
            'sort'     => $sort,
            'dir'      => $dir,
        ], 'admin');
    }

    public function ver(string $id): void
    {
        $model   = new MensajeModel();
        $mensaje = $model->find((int) $id);
        if (!$mensaje) $this->redirect(APP_URL . '/admin/mensajes');

        if (!$mensaje['leido']) {
            $model->markAsRead((int) $id);
        }

        $this->render('admin.mensajes.ver', [
            'title'   => 'Ver Mensaje · Admin',
            'mensaje' => $mensaje,
        ], 'admin');
    }

    public function delete(string $id): void
    {
        (new MensajeModel())->delete((int) $id);
        $this->redirect(APP_URL . '/admin/mensajes');
    }

    public function reply(string $id): void
    {
        $model   = new MensajeModel();
        $mensaje = $model->find((int) $id);
        if (!$mensaje) {
            $this->json(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
            return;
        }

        $replyText = trim($this->request->post('reply_text', ''));
        if (empty($replyText)) {
            $this->json(['success' => false, 'message' => 'El cuerpo de la respuesta no puede estar vacío.'], 422);
            return;
        }

        $cfg      = new ConfiguracionModel();
        $siteName = $cfg->get('site_name', 'CoDevNexus');
        $siteUrl  = defined('APP_URL') ? APP_URL : '';
        $asunto   = $mensaje['asunto'] ?? 'Tu mensaje';

        try {
            $escapedReply = htmlspecialchars($replyText, ENT_QUOTES);
            $escapedName  = htmlspecialchars($mensaje['nombre'], ENT_QUOTES);
            $escapedSubj  = htmlspecialchars($asunto, ENT_QUOTES);

            $body = "<h2 style=\"margin:0 0 4px;font-size:18px;font-weight:700;color:#e2e8f0\">Re: {$escapedSubj}</h2>"
                  . "<p style=\"color:#64748b;margin:0 0 24px;font-size:13px\">Respuesta de {$siteName} · " . date('d/m/Y H:i') . "</p>"
                  . "<div style=\"padding:16px;background:#0b0f19;border-radius:8px;border:1px solid #1e2d40;border-left:3px solid #00d4ff;margin-bottom:20px\">"
                  . "<p style=\"margin:0;color:#e2e8f0;font-size:14px;line-height:1.8;white-space:pre-wrap\">{$escapedReply}</p></div>"
                  . "<p style=\"color:#64748b;font-size:13px;margin:0\">— {$siteName}</p>";

            $html = Mailer::buildTemplate(
                "Re: {$asunto}",
                $body,
                "Respuesta a tu mensaje",
                $siteName,
                $siteUrl
            );

            $mailer = new Mailer();
            $mailer->send($mensaje['correo'], "Re: {$asunto} — {$siteName}", $html);

            $model->markReplied((int) $id);

            $this->json(['success' => true, 'message' => "Respuesta enviada a {$mensaje['correo']}."]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Error al enviar: ' . $e->getMessage()], 500);
        }
    }
}
