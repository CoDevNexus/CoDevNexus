<?php
namespace App\Controllers\Api;

use Core\Controller;
use App\Models\SeccionModel;
use App\Models\ConfiguracionModel;

class SeccionesController extends Controller
{
    public function index(): void
    {
        $cfg      = new ConfiguracionModel();
        $modoSeg  = $cfg->get('modo_seguro') === '1';

        $model    = new SeccionModel();
        $secciones = $model->getAllVisible($modoSeg);

        $this->json([
            'success' => true,
            'data'    => $secciones,
        ]);
    }
}
