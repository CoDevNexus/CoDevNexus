<?php
// ============================================================
// app/Middleware/CsrfMiddleware.php — Verifica token en POST
// ============================================================

namespace App\Middleware;

use Core\Request;
use Core\Response;
use Core\Security;

class CsrfMiddleware
{
    public function __construct(
        private Request  $request,
        private Response $response
    ) {}

    public function handle(): void
    {
        if (!$this->request->isPost()) {
            return;
        }

        $token = $this->request->post('_csrf');

        if (!Security::verifyCsrfToken($token)) {
            if ($this->request->isAjax()) {
                $this->response->json(['error' => 'CSRF token inválido.'], 403);
            } else {
                http_response_code(403);
                echo '<!DOCTYPE html><html><body><h1>403 CSRF Error</h1><p>Token inválido. <a href="javascript:history.back()">Volver</a></p></body></html>';
                exit;
            }
        }
        // Token persiste durante toda la sesión para evitar errores 403
        // cuando hay múltiples formularios o peticiones AJAX en la misma página.
    }
}
