<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Security;
use App\Models\ConfiguracionModel;

class SistemaController extends Controller
{
    public function status(): void
    {
        $model = new ConfiguracionModel();
        $this->json([
            'ok' => true,
            'data' => [
                'modo_mantenimiento' => $model->get('modo_mantenimiento') === '1',
                'modo_seguro'        => $model->get('modo_seguro') === '1',
                'mantenimiento_msg'  => $model->get('mantenimiento_mensaje'),
            ],
        ]);
    }

    /** GET /api/csrf — SPA contact form token */
    public function csrf(): void
    {
        $token = Security::generateCsrfToken();
        $this->json(['token' => $token]);
    }
}
