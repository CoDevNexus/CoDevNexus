<?php
namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\SeccionModel;
use App\Models\PortafolioModel;
use App\Models\TecnologiaModel;
use App\Models\MensajeModel;
use App\Models\ConfiguracionModel;

class DashboardController extends Controller
{
    public function index(): void
    {
        $cfg = new ConfiguracionModel();

        $data = [
            'title'             => 'Dashboard · Admin',
            'secciones'        => (new SeccionModel())->count(),
            'proyectos'        => (new PortafolioModel())->count(),
            'tecnologias'      => (new TecnologiaModel())->count(),
            'mensajes_nuevos'  => (new MensajeModel())->getUnread(),
            'modo_seguro'      => $cfg->get('modo_seguro') === '1',
            'modo_mant'        => $cfg->get('modo_mantenimiento') === '1',
            'site_name'        => $cfg->get('site_name', 'CoDevNexus'),
        ];

        $this->render('admin.dashboard', $data, 'admin');
    }

    public function toggleModoSeguro(): void
    {
        $cfg     = new ConfiguracionModel();
        $current = $cfg->get('modo_seguro') === '1';
        $cfg->set('modo_seguro', $current ? '0' : '1');
        $this->redirect(APP_URL . '/admin');
    }

    public function toggleMantenimiento(): void
    {
        $cfg     = new ConfiguracionModel();
        $current = $cfg->get('modo_mantenimiento') === '1';
        $cfg->set('modo_mantenimiento', $current ? '0' : '1');
        $this->redirect(APP_URL . '/admin');
    }
}
