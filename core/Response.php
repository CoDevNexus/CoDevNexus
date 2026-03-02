<?php
// ============================================================
// core/Response.php — Headers, JSON, redirect
// ============================================================

namespace Core;

class Response
{
    public function json(mixed $data, int $status = 200): void
    {
        if (ob_get_level() > 0) ob_clean(); // discard any accidental output (notices, warnings)
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public function notFound(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html><body><h1>404 Not Found</h1></body></html>';
        exit;
    }

    public function maintenance(string $message = 'Sitio en mantenimiento.'): void
    {
        http_response_code(503);
        header('Retry-After: 3600');
        include APP_ROOT . '/app/Views/errors/503.php';
        exit;
    }

    public function setSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
        if (APP_ENV === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
