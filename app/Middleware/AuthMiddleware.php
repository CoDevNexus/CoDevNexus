<?php
// ============================================================
// app/Middleware/AuthMiddleware.php — Protege rutas /admin
// ============================================================

namespace App\Middleware;

use Core\Request;
use Core\Response;

class AuthMiddleware
{
    public function __construct(
        private Request  $request,
        private Response $response
    ) {}

    public function handle(): void
    {
        if (empty($_SESSION['admin_logged_in'])) {
            $this->response->redirect(APP_URL . '/admin/login');
        }
    }
}
