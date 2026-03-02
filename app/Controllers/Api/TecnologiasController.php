<?php
namespace App\Controllers\Api;

use Core\Controller;
use App\Models\TecnologiaModel;

class TecnologiasController extends Controller
{
    public function index(): void
    {
        $model = new TecnologiaModel();
        $rows  = $model->getAllVisible();

        // Normalizar campos de icono para el frontend (icono_tipo/icono_valor → icono_clase/icono_svg)
        $data = array_map(function (array $t): array {
            if ($t['icono_tipo'] !== 'svg_custom') {
                $t['icono_clase'] = $t['icono_valor'];
                $t['icono_svg']   = null;
            } else {
                $t['icono_clase'] = null;
                $t['icono_svg']   = $t['icono_valor'];
            }
            return $t;
        }, $rows);

        $this->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
