<?php
// ============================================================
// core/Mailer.php — SMTP configurable desde BD, fallback mail()
// ============================================================

namespace Core;

class Mailer
{
    private array $config;

    public function __construct()
    {
        $this->config = $this->loadConfig();
    }

    private function loadConfig(): array
    {
        $defaults = [
            'mail_driver'        => 'smtp',
            // Gmail
            'gmail_user'         => '',
            'gmail_app_password' => '',
            'gmail_from_name'    => 'CoDevNexus',
            'gmail_admin_copy'   => '',
            // SMTP personalizado
            'smtp_host'          => '',
            'smtp_port'          => '587',
            'smtp_encryption'    => 'tls',
            'smtp_user'          => '',
            'smtp_password'      => '',
            'smtp_from_email'    => '',
            'smtp_from_name'     => 'CoDevNexus',
            'smtp_admin_copy'    => '',
        ];

        try {
            $db   = Database::getInstance();
            $keys = implode("','", array_keys($defaults));
            $stmt = $db->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('{$keys}')");
            $stmt->execute();
            foreach ($stmt->fetchAll() as $row) {
                $defaults[$row['clave']] = $row['valor'];
            }
            // Descifrar passwords
            $defaults['smtp_password']    = Security::decryptSmtpPassword($defaults['smtp_password']);
            $defaults['gmail_app_password'] = Security::decryptSmtpPassword($defaults['gmail_app_password']);
        } catch (\Throwable) {}

        // Normalizar: si el driver activo es gmail, sobreescribir los campos SMTP
        // efectivos para que sendSmtp() funcione sin cambios adicionales.
        if ($defaults['mail_driver'] === 'gmail' && !empty($defaults['gmail_user'])) {
            $defaults['smtp_host']       = 'smtp.gmail.com';
            $defaults['smtp_port']       = '587';
            $defaults['smtp_encryption'] = 'tls';
            $defaults['smtp_user']       = $defaults['gmail_user'];
            $defaults['smtp_password']   = $defaults['gmail_app_password'];
            $defaults['smtp_from_email'] = $defaults['gmail_user'];
            $defaults['smtp_from_name']  = $defaults['gmail_from_name'] ?: 'CoDevNexus';
            $defaults['smtp_admin_copy'] = $defaults['gmail_admin_copy'];
        }

        return $defaults;
    }

    /**
     * Enviar email
     * @throws \RuntimeException si falla
     */
    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        if (!empty($this->config['smtp_host']) && !empty($this->config['smtp_user'])) {
            return $this->sendSmtp($to, $subject, $htmlBody, $textBody);
        }
        return $this->sendNative($to, $subject, $htmlBody);
    }

    /**
     * SMTP manual via fsockopen (TLS/SSL/plain)
     */
    private function sendSmtp(string $to, string $subject, string $html, string $text): bool
    {
        $host       = $this->config['smtp_host'];
        $port       = (int) $this->config['smtp_port'];
        $enc        = strtolower($this->config['smtp_encryption']);
        $user       = $this->config['smtp_user'];
        $pass       = $this->config['smtp_password'];
        $fromEmail  = $this->config['smtp_from_email'] ?: $user;
        $fromName   = $this->config['smtp_from_name'];
        $adminCopy  = $this->config['smtp_admin_copy'];

        $prefix = ($enc === 'ssl') ? 'ssl://' : '';
        $errno  = $errstr = null;
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 15);

        if (!$socket) {
            throw new \RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }

        $boundary = '----=_Part_' . md5(uniqid('', true));

        $read = fn() => fgets($socket, 512);
        $send = function (string $cmd) use ($socket) {
            fwrite($socket, $cmd . "\r\n");
        };

        $read(); // 220 banner

        $send('EHLO ' . gethostname());
        while (($line = $read()) && substr($line, 3, 1) === '-') {}

        if ($enc === 'tls') {
            $send('STARTTLS');
            $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send('EHLO ' . gethostname());
            while (($line = $read()) && substr($line, 3, 1) === '-') {}
        }

        $send('AUTH LOGIN');
        $read();
        $send(base64_encode($user));
        $read();
        $send(base64_encode($pass));
        $resp = $read();

        if (substr(trim($resp), 0, 3) !== '235') {
            fclose($socket);
            throw new \RuntimeException("SMTP auth failed: {$resp}");
        }

        $send("MAIL FROM:<{$fromEmail}>");
        $read();
        $send("RCPT TO:<{$to}>");
        $read();

        if ($adminCopy && $adminCopy !== $to) {
            $send("RCPT TO:<{$adminCopy}>");
            $read();
        }

        $send('DATA');
        $read();

        // Construir mensaje MIME
        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
        $headers .= "To: {$to}\r\n";
        if ($adminCopy && $adminCopy !== $to) {
            $headers .= "Bcc: {$adminCopy}\r\n";
        }
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= ($text ?: strip_tags($html)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $html . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $send($headers . "\r\n" . $body . "\r\n.");
        $resp = $read();

        $send('QUIT');
        fclose($socket);

        return str_starts_with(trim($resp), '250');
    }

    private function sendNative(string $to, string $subject, string $html): bool
    {
        $fromEmail = $this->config['smtp_from_email'] ?: 'no-reply@codevnexus.tech';
        $fromName  = $this->config['smtp_from_name'] ?: 'CoDevNexus';

        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
    }

    // ----------------------------------------------------------
    // Email HTML template (modern dark theme)
    // ----------------------------------------------------------
    public static function buildTemplate(
        string $title,
        string $bodyHtml,
        string $subtitle    = '',
        string $siteName    = 'CoDevNexus',
        string $siteUrl     = '',
        string $ctaText     = '',
        string $ctaUrl      = ''
    ): string {
        $gradientBar = '<div style="height:3px;background:linear-gradient(90deg,#00d4ff,#7b2d8b,#ff6b35)"></div>';
        $ctaBlock = '';
        if ($ctaText && $ctaUrl) {
            $ctaBlock = '<tr><td align="center" style="padding:24px 0 8px">'
                . '<a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES) . '" '
                . 'style="display:inline-block;padding:12px 28px;background:linear-gradient(135deg,#00d4ff,#7b2d8b);'
                . 'color:#0b0f19;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px">'
                . htmlspecialchars($ctaText, ENT_QUOTES) . '</a></td></tr>';
        }
        $footerUrl  = htmlspecialchars($siteUrl ?: '#', ENT_QUOTES);
        $footerName = htmlspecialchars($siteName, ENT_QUOTES);
        $subtitleHtml = $subtitle
            ? '<div style="margin-top:6px;font-size:13px;color:#64748b">' . htmlspecialchars($subtitle, ENT_QUOTES) . '</div>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#0b0f19;font-family:'Segoe UI',Arial,sans-serif;color:#e2e8f0">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0b1a2e">
  <tr><td align="center" style="padding:30px 20px">
    <div style="font-size:26px;font-weight:800;color:#ffffff;font-family:Arial,sans-serif">
      CoDev<span style="color:#00d4ff">Nexus</span></div>
    {$subtitleHtml}
  </td></tr>
</table>
{$gradientBar}
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#111827">
  <tr><td align="center" style="padding:30px 16px">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%">
      <tr><td style="background:#0f172a;border:1px solid #1e2d40;border-radius:12px;padding:32px">
        {$bodyHtml}
      </td></tr>
      {$ctaBlock}
    </table>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0b0f19">
  <tr><td align="center" style="padding:20px 16px;color:#374151;font-size:12px;font-family:Arial,sans-serif">
    <a href="{$footerUrl}" style="color:#00d4ff;text-decoration:none">{$footerName}</a>
    &nbsp;·&nbsp; Correo generado automáticamente, no respondas directamente.
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    public function testConnection(): array
    {
        try {
            $driver     = $this->config['mail_driver'] ?? 'smtp';
            $driverLabel = $driver === 'gmail' ? 'Gmail' : 'SMTP';
            $adminEmail  = $this->config['smtp_admin_copy']
                        ?: $this->config['smtp_from_email']
                        ?: $this->config['smtp_user'];

            if (empty($adminEmail)) {
                return ['ok' => false, 'error' => 'No hay correo admin configurado.'];
            }

            $this->send(
                $adminEmail,
                'Prueba de email — CoDevNexus',
                "<h2>✅ Email de prueba ({$driverLabel})</h2><p>La configuración de correo funciona correctamente.</p>"
            );
            return ['ok' => true, 'message' => "[{$driverLabel}] Email enviado a {$adminEmail}"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
