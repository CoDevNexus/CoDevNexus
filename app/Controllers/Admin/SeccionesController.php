<?php
namespace App\Controllers\Admin;

use Core\Controller;
use Core\Security;
use Core\Database;
use App\Models\SeccionModel;
use App\Models\PortafolioModel;
use App\Models\TecnologiaModel;

class SeccionesController extends Controller
{
    public function index(): void
    {
        $secciones = (new SeccionModel())->getAll();

        // Enriquecer con conteo de ítems por sección
        $portafolioCount  = (new PortafolioModel())->count();
        $tecnologiasCount = (new TecnologiaModel())->count();
        $serviciosCount   = $this->countServicios();

        $this->render('admin.secciones.index', [
            'title'            => 'Secciones · Admin',
            'secciones'        => $secciones,
            'portafolioCount'  => $portafolioCount,
            'tecnologiasCount' => $tecnologiasCount,
            'serviciosCount'   => $serviciosCount,
        ], 'admin');
    }

    public function create(): void
    {
        $this->render('admin.secciones.create', ['title' => 'Nueva Sección'], 'admin');
    }

    public function store(): void
    {
        (new SeccionModel())->create([
            'titulo'       => Security::sanitize($this->request->post('titulo', '')),
            'contenido'    => $this->request->post('contenido', ''),
            'tipo_seccion' => $this->request->post('tipo_seccion', 'otro'),
            'orden'        => (int) $this->request->post('orden', 0),
            'visible'      => $this->request->post('visible') ? 1 : 0,
            'modo_seguro'  => $this->request->post('modo_seguro') ? 1 : 0,
        ]);
        $this->redirect(APP_URL . '/admin/secciones');
    }

    public function edit(string $id): void
    {
        $seccion = (new SeccionModel())->find((int) $id);
        if (!$seccion) { $this->redirect(APP_URL . '/admin/secciones'); }

        $data = ['title' => 'Editar: ' . ($seccion['titulo'] ?? ''), 'seccion' => $seccion];

        // Cargar ítems según tipo para el panel inline
        switch ($seccion['tipo_seccion'] ?? '') {
            case 'portafolio':
                $data['items'] = (new PortafolioModel())->findAll('orden ASC');
                break;
            case 'tecnologias':
                $data['items'] = (new TecnologiaModel())->findAll('orden ASC');
                break;
            case 'servicios':
                $data['items'] = $this->decodeServicios($seccion);
                break;
        }

        $this->render('admin.secciones.edit', $data, 'admin');
    }

    public function update(string $id): void
    {
        (new SeccionModel())->update((int) $id, [
            'titulo'       => Security::sanitize($this->request->post('titulo', '')),
            'contenido'    => $this->request->post('contenido', ''),
            'tipo_seccion' => $this->request->post('tipo_seccion', 'otro'),
            'orden'        => (int) $this->request->post('orden', 0),
            'visible'      => $this->request->post('visible') ? 1 : 0,
            'modo_seguro'  => $this->request->post('modo_seguro') ? 1 : 0,
        ]);
        $this->redirect(APP_URL . '/admin/secciones/edit/' . (int) $id);
    }

    public function delete(string $id): void
    {
        (new SeccionModel())->delete((int) $id);
        $this->redirect(APP_URL . '/admin/secciones');
    }

    public function toggleVisible(string $id): void
    {
        $model   = new SeccionModel();
        $seccion = $model->find((int) $id);
        if (!$seccion) {
            $this->json(['success' => false, 'message' => 'No encontrado.'], 404);
            return;
        }
        $newVal = $seccion['visible'] ? 0 : 1;
        $model->update((int) $id, ['visible' => $newVal]);
        $this->json(['success' => true, 'visible' => $newVal]);
    }

    // ── helpers ───────────────────────────────────────────
    private function decodeServicios(array $seccion): array
    {
        $raw = trim($seccion['contenido'] ?? '');
        if (!$raw) return [];
        try { return json_decode($raw, true, 512, JSON_THROW_ON_ERROR) ?? []; }
        catch (\Throwable) { return []; }
    }

    private function countServicios(): int
    {
        $stmt = Database::getInstance()
            ->prepare("SELECT contenido FROM secciones WHERE tipo_seccion = 'servicios' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return 0;
        try {
            $items = json_decode(trim($row['contenido'] ?? ''), true);
            return is_array($items) ? count($items) : 0;
        } catch (\Throwable) { return 0; }
    }
}
