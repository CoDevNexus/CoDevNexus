<?php
namespace App\Controllers\Admin;

use Core\Controller;
use Core\Security;
use Core\ImageUploader;
use App\Models\TecnologiaModel;

class TecnologiasController extends Controller
{
    public function index(): void
    {
        $this->render('admin.tecnologias.index', [
            'title'       => 'Tecnologías · Admin',
            'tecnologias' => (new TecnologiaModel())->getAll(),
        ], 'admin');
    }

    public function create(): void
    {
        $this->render('admin.tecnologias.create', ['title' => 'Nueva Tecnología'], 'admin');
    }

    public function store(): void
    {
        $iconoTipo  = $this->request->post('icono_tipo', 'devicon');
        $iconoValor = $this->resolveIcono($iconoTipo);

        (new TecnologiaModel())->create([
            'nombre'      => Security::sanitize($this->request->post('nombre', '')),
            'nivel'       => min(100, max(0, (int) $this->request->post('nivel', 50))),
            'icono_tipo'  => $iconoTipo,
            'icono_valor' => $iconoValor,
            'categoria'   => $this->request->post('categoria', 'otro'),
            'visible'     => $this->request->post('visible') ? 1 : 0,
            'orden'       => (int) $this->request->post('orden', 0),
        ]);

        $this->redirect(APP_URL . '/admin/tecnologias');
    }

    public function edit(string $id): void
    {
        $model = new TecnologiaModel();
        $tech  = $model->find((int) $id);
        if (!$tech) $this->redirect(APP_URL . '/admin/tecnologias');
        $this->render('admin.tecnologias.edit', [
            'title' => 'Editar Tecnología',
            'tech'  => $tech,
        ], 'admin');
    }

    public function update(string $id): void
    {
        $model     = new TecnologiaModel();
        $iconoTipo = $this->request->post('icono_tipo', 'devicon');
        $iconoValor = $this->resolveIcono($iconoTipo);

        $model->update((int) $id, [
            'nombre'      => Security::sanitize($this->request->post('nombre', '')),
            'nivel'       => min(100, max(0, (int) $this->request->post('nivel', 50))),
            'icono_tipo'  => $iconoTipo,
            'icono_valor' => $iconoValor,
            'categoria'   => $this->request->post('categoria', 'otro'),
            'visible'     => $this->request->post('visible') ? 1 : 0,
            'orden'       => (int) $this->request->post('orden', 0),
        ]);

        $this->redirect(APP_URL . '/admin/tecnologias');
    }

    public function delete(string $id): void
    {
        (new TecnologiaModel())->delete((int) $id);
        $this->redirect(APP_URL . '/admin/tecnologias');
    }

    public function toggleVisible(string $id): void
    {
        $model = new TecnologiaModel();
        $tech  = $model->find((int) $id);
        if (!$tech) {
            $this->json(['success' => false, 'message' => 'No encontrado.'], 404);
            return;
        }
        $newVal = $tech['visible'] ? 0 : 1;
        $model->update((int) $id, ['visible' => $newVal]);
        $this->json(['success' => true, 'visible' => $newVal]);
    }

    private function resolveIcono(string $tipo): string
    {
        if ($tipo === 'svg_custom') {
            $svg = $this->request->post('icono_svg', '');
            return Security::sanitizeSvg($svg);
        }
        // devicons — clase CSS
        return Security::sanitize($this->request->post('icono_devicon', ''));
    }
}
