<?php
// ============================================================
// core/Telegram.php — sendMessage(), testConnection(), notificaciones
// ============================================================

namespace Core;

class Telegram
{
    private string $token;
    private string $chatId;

    public function __construct(string $token = '', string $chatId = '')
    {
        if ($token && $chatId) {
            $this->token  = $token;
            $this->chatId = $chatId;
        } else {
            // Leer de BD
            try {
                $db    = Database::getInstance();
                $stmt  = $db->prepare(
                    "SELECT clave, valor FROM configuracion WHERE clave IN ('telegram_bot_token','telegram_chat_id')"
                );
                $stmt->execute();
                $rows  = $stmt->fetchAll();
                $conf  = array_column($rows, 'valor', 'clave');
                $this->token  = $conf['telegram_bot_token']  ?? '';
                $this->chatId = $conf['telegram_chat_id']    ?? '';
            } catch (\Throwable) {
                $this->token  = '';
                $this->chatId = '';
            }
        }
    }

    public function sendMessage(string $text): bool
    {
        if (empty($this->token) || empty($this->chatId)) {
            return false;
        }

        $url  = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = ['chat_id' => $this->chatId, 'text' => $text, 'parse_mode' => 'HTML'];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 10,
            CURLOPT_POSTFIELDS      => http_build_query($data),
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($res, true);
        return !empty($decoded['ok']);
    }

    /**
     * Llamada genérica a la API de Telegram; devuelve el JSON decodificado.
     */
    private function apiCall(string $method, array $data = []): array
    {
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $res      = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            return ['ok' => false, '_curl_error' => $curlErr];
        }
        return json_decode($res, true) ?? ['ok' => false, '_parse_error' => true];
    }

    public function testConnection(): array
    {
        if (empty($this->token)) {
            return ['ok' => false, 'error' => 'Bot token no configurado.'];
        }

        // 1) Verificar token con getMe
        $me = $this->apiCall('getMe');
        if (!empty($me['_curl_error'])) {
            return ['ok' => false, 'error' => 'Error de red/SSL: ' . $me['_curl_error']];
        }
        if (empty($me['ok'])) {
            $desc = $me['description'] ?? 'Token inválido o bot inexistente';
            return ['ok' => false, 'error' => "Token incorrecto — Telegram dice: {$desc}"];
        }

        $botName = $me['result']['username'] ?? '?';

        if (empty($this->chatId)) {
            return ['ok' => false, 'error' => "Token OK (@{$botName}) pero Chat ID no configurado."];
        }

        // 2) Enviar mensaje de prueba
        $res = $this->apiCall('sendMessage', [
            'chat_id'    => $this->chatId,
            'text'       => "✅ <b>CoDevNexus</b> — Notificaciones Telegram activas.\nBot: @{$botName}",
            'parse_mode' => 'HTML',
        ]);

        if (!empty($res['_curl_error'])) {
            return ['ok' => false, 'error' => 'Error de red al enviar: ' . $res['_curl_error']];
        }
        if (empty($res['ok'])) {
            $desc = $res['description'] ?? 'Error desconocido';
            return ['ok' => false, 'error' => "Token OK pero no se pudo enviar al chat — Telegram dice: {$desc}"];
        }

        return ['ok' => true, 'message' => "✅ Mensaje enviado desde @{$botName}"];
    }

    // ----------------------------------------------------------
    // Notificaciones tipificadas
    // ----------------------------------------------------------
    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->chatId);
    }

    public function notifyContacto(
        string $nombre,
        string $correo,
        string $asunto,
        string $telefono = '',
        string $pais = '',
        string $mensaje = ''
    ): bool {
        if (!$this->isEnabled('telegram_notify_contacto')) return false;
        $text  = "📩 <b>Nuevo mensaje de contacto</b>\n";
        $text .= "👤 <b>Nombre:</b> {$nombre}\n";
        $text .= "📧 <b>Correo:</b> {$correo}\n";
        if ($telefono) $text .= "📱 <b>Teléfono:</b> {$telefono}\n";
        if ($pais)     $text .= "🌍 <b>País:</b> {$pais}\n";
        $text .= "📌 <b>Asunto:</b> {$asunto}";
        if ($mensaje) {
            $preview = mb_strlen($mensaje) > 300 ? mb_substr($mensaje, 0, 300) . '…' : $mensaje;
            $text .= "\n\n💬 <b>Mensaje:</b>\n{$preview}";
        }
        return $this->sendMessage($text);
    }

    public function notifyLoginFail(string $username, string $ip): void
    {
        if (!$this->isEnabled('telegram_notify_login_fail')) return;
        $text = "⚠️ <b>Login fallido</b>\n"
              . "👤 <b>Usuario:</b> {$username}\n"
              . "🌐 <b>IP:</b> {$ip}";
        $this->sendMessage($text);
    }

    public function notifyNuevoUsuario(string $username): void
    {
        if (!$this->isEnabled('telegram_notify_nuevo_user')) return;
        $text = "🆕 <b>Nuevo usuario registrado:</b> {$username}";
        $this->sendMessage($text);
    }

    public function notifyConfigCambio(string $detalle): void
    {
        if (!$this->isEnabled('telegram_notify_config')) return;
        $text = "⚙️ <b>Cambio de configuración</b>\n{$detalle}";
        $this->sendMessage($text);
    }

    private function isEnabled(string $clave): bool
    {
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = ?");
            $stmt->execute([$clave]);
            return ($stmt->fetchColumn() ?? '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}
