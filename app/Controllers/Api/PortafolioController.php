<?php
namespace App\Controllers\Api;

use Core\Controller;
use App\Models\PortafolioModel;
use App\Models\ConfiguracionModel;
use Core\Security;

class PortafolioController extends Controller
{
    public function index(): void
    {
        $cfg     = new ConfiguracionModel();
        $modoSeg = $cfg->get('modo_seguro') === '1';
        $model   = new PortafolioModel();

        $this->json([
            'success' => true,
            'data'    => $model->getAllVisible($modoSeg),
        ]);
    }

    public function show(string $id): void
    {
        $model   = new PortafolioModel();
        $project = $model->find((int) $id);

        if (!$project || !$project['visible']) {
            $this->json(['success' => false, 'error' => 'No encontrado.'], 404);
        }

        $this->json(['success' => true, 'data' => $project]);
    }
}
