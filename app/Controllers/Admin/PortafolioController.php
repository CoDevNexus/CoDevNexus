<?php
namespace App\Controllers\Admin;

use Core\Controller;
use Core\Security;
use Core\ImageUploader;
use App\Models\PortafolioModel;

class PortafolioController extends Controller
{
    public function index(): void
    {
        $this->render('admin.portafolio.index', [
            'title'     => 'Portafolio · Admin',
            'proyectos' => (new PortafolioModel())->getAll(),
        ], 'admin');
    }

    public function create(): void
    {
        $this->render('admin.portafolio.create', ['title' => 'Nuevo Proyecto'], 'admin');
    }

    public function store(): void
    {
        $imagenUrl = $this->handleImageUpload('portafolio');

        (new PortafolioModel())->create([
            'titulo'            => Security::sanitize($this->request->post('titulo', '')),
            'descripcion_corta' => Security::sanitize($this->request->post('descripcion_corta', '')),
            'descripcion_larga' => $this->request->post('descripcion_larga', ''),
            'categoria'         => $this->request->post('categoria', 'otro'),
            'imagen_url'        => $imagenUrl ?? '',
            'enlace_demo'       => Security::sanitize($this->request->post('enlace_demo', '')),
            'enlace_repo'       => Security::sanitize($this->request->post('enlace_repo', '')),
            'modo_seguro'       => $this->request->post('modo_seguro') ? 1 : 0,
            'visible'           => $this->request->post('visible') ? 1 : 0,
            'orden'             => (int) $this->request->post('orden', 0),
        ]);

        $this->redirect(APP_URL . '/admin/portafolio');
    }

    public function edit(string $id): void
    {
        $model   = new PortafolioModel();
        $proyecto = $model->find((int) $id);
        if (!$proyecto) $this->redirect(APP_URL . '/admin/portafolio');
        $this->render('admin.portafolio.edit', [
            'title'   => 'Editar Proyecto',
            'proyecto' => $proyecto,
        ], 'admin');
    }

    public function update(string $id): void
    {
        $model    = new PortafolioModel();
        $proyecto = $model->find((int) $id);
        if (!$proyecto) $this->redirect(APP_URL . '/admin/portafolio');

        $imagenUrl = $this->handleImageUpload('portafolio') ?? $proyecto['imagen_url'];

        $model->update((int) $id, [
            'titulo'            => Security::sanitize($this->request->post('titulo', '')),
            'descripcion_corta' => Security::sanitize($this->request->post('descripcion_corta', '')),
            'descripcion_larga' => $this->request->post('descripcion_larga', ''),
            'categoria'         => $this->request->post('categoria', 'otro'),
            'imagen_url'        => $imagenUrl,
            'enlace_demo'       => Security::sanitize($this->request->post('enlace_demo', '')),
            'enlace_repo'       => Security::sanitize($this->request->post('enlace_repo', '')),
            'modo_seguro'       => $this->request->post('modo_seguro') ? 1 : 0,
            'visible'           => $this->request->post('visible') ? 1 : 0,
            'orden'             => (int) $this->request->post('orden', 0),
        ]);

        $this->redirect(APP_URL . '/admin/portafolio');
    }

    public function delete(string $id): void
    {
        (new PortafolioModel())->delete((int) $id);
        $this->redirect(APP_URL . '/admin/portafolio');
    }

    public function toggleVisible(string $id): void
    {
        $model   = new PortafolioModel();
        $proyecto = $model->find((int) $id);
        if (!$proyecto) {
            $this->json(['success' => false, 'message' => 'No encontrado.'], 404);
            return;
        }
        $newVal = $proyecto['visible'] ? 0 : 1;
        $model->update((int) $id, ['visible' => $newVal]);
        $this->json(['success' => true, 'visible' => $newVal]);
    }

    private function handleImageUpload(string $folder): ?string
    {
        $toggle = $this->request->post('imagen_source', 'local');
        $file   = $this->request->file('imagen');

        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            // Accept an external URL regardless of upload driver (covers media library modal)
            $extUrl = trim($this->request->post('imagen_url_externa', ''));
            return $extUrl ?: null;
        }

        return ImageUploader::upload($file, $toggle, $folder);
    }
}
