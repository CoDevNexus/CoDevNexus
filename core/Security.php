<?php
// ============================================================
// core/Security.php — CSRF, sanitize, sanitizeSvg, rateLimit, AES
// ============================================================

namespace Core;

class Security
{
    // ----------------------------------------------------------
    // CSRF
    // ----------------------------------------------------------
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
    }

    // ----------------------------------------------------------
    // Sanitización
    // ----------------------------------------------------------
    public static function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function sanitizeInt(mixed $value, int $default = 0): int
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : $default;
    }

    /**
     * Sanitiza SVG: elimina <script>, atributos on*, href/xlink:href con javascript:
     */
    public static function sanitizeSvg(string $svg): string
    {
        if (empty(trim($svg))) {
            return '';
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadXML($svg, LIBXML_NONET);

        // Eliminar <script>
        foreach ($dom->getElementsByTagName('script') as $node) {
            $node->parentNode?->removeChild($node);
        }

        // Recorrer todos los elementos
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//*') as $element) {
            /** @var \DOMElement $element */
            $toRemove = [];
            foreach ($element->attributes as $attr) {
                $name  = strtolower($attr->name);
                $value = strtolower(trim($attr->value));
                // Eliminar atributos de eventos on*
                if (str_starts_with($name, 'on')) {
                    $toRemove[] = $attr->name;
                }
                // Eliminar href/src con javascript:
                if (in_array($name, ['href', 'src', 'xlink:href', 'action'], true)
                    && str_starts_with(preg_replace('/\s+/', '', $value), 'javascript:')) {
                    $toRemove[] = $attr->name;
                }
            }
            foreach ($toRemove as $attrName) {
                $element->removeAttribute($attrName);
            }
        }

        libxml_clear_errors();
        $result = $dom->saveXML($dom->documentElement);
        return $result !== false ? $result : '';
    }

    // ----------------------------------------------------------
    // Rate Limiting por IP (usando tabla login_attempts)
    // ----------------------------------------------------------
    public static function rateLimit(string $ip, string $action, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        try {
            $db = Database::getInstance();
            // Contar intentos recientes
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM login_attempts
                 WHERE ip = ? AND intentado_en > DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
            $stmt->execute([$ip, $windowSeconds]);
            $count = (int) $stmt->fetchColumn();

            if ($count >= $maxAttempts) {
                return false; // bloqueado
            }

            // Registrar intento
            $stmt2 = $db->prepare("INSERT INTO login_attempts (ip, username) VALUES (?, ?)");
            $stmt2->execute([$ip, $action]);
            return true;
        } catch (\Throwable) {
            return true; // Si falla la BD, no bloquear
        }
    }

    public static function cleanOldAttempts(string $ip, int $windowSeconds = 300): void
    {
        try {
            $db = Database::getInstance();
            $db->prepare(
                "DELETE FROM login_attempts WHERE ip = ? AND intentado_en <= DATE_SUB(NOW(), INTERVAL ? SECOND)"
            )->execute([$ip, $windowSeconds]);
        } catch (\Throwable) {}
    }

    // ----------------------------------------------------------
    // Cifrado AES-256-CBC para SMTP password
    // ----------------------------------------------------------
    public static function encryptSmtpPassword(string $plain): string
    {
        if (empty($plain)) return '';
        $key    = substr(hash('sha256', APP_KEY, true), 0, 32);
        $iv     = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public static function decryptSmtpPassword(string $encrypted): string
    {
        if (empty($encrypted)) return '';
        try {
            $raw  = base64_decode($encrypted, true);
            if ($raw === false || strlen($raw) < 16) return '';
            $key  = substr(hash('sha256', APP_KEY, true), 0, 32);
            $iv   = substr($raw, 0, 16);
            $data = substr($raw, 16);
            $dec  = openssl_decrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            return $dec !== false ? $dec : '';
        } catch (\Throwable) {
            return '';
        }
    }

    // ----------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
