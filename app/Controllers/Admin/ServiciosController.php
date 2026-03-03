<?php
namespace App\Controllers\Admin;

use Core\Controller;
use Core\Security;
use Core\Database;

class ServiciosController extends Controller
{
    // ── helpers ────────────────────────────────────────────
    private function getSeccion(): array
    {
        $stmt = Database::getInstance()
            ->prepare("SELECT * FROM secciones WHERE tipo_seccion = 'servicios' LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    private function decodeServicios(array $seccion): array
    {
        $raw = trim($seccion['contenido'] ?? '');
        if (!$raw) return [];
        try { return json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { return []; }
    }

    private function saveServicios(array $servicios): void
    {
        $seccion = $this->getSeccion();
        if (empty($seccion)) return;
        // Preserve array order (do NOT sort here — sorting scrambles indices used as edit/delete keys)
        Database::getInstance()
            ->prepare("UPDATE secciones SET contenido = ? WHERE tipo_seccion = 'servicios'")
            ->execute([json_encode(array_values($servicios), JSON_UNESCAPED_UNICODE)]);
    }

    /** Find index by _id field; falls back to numeric idx for legacy items */
    private function findIdx(array $servicios, string $idOrIdx): int
    {
        foreach ($servicios as $i => $sv) {
            if (!empty($sv['_id']) && $sv['_id'] === $idOrIdx) return $i;
        }
        // legacy: numeric index
        $i = (int) $idOrIdx;
        return isset($servicios[$i]) ? $i : -1;
    }

    // ── actions ────────────────────────────────────────────
    public function index(): void
    {
        $seccion   = $this->getSeccion();
        $servicios = $this->decodeServicios($seccion);
        $this->render('admin.servicios.index', [
            'title'     => 'Servicios · Admin',
            'servicios' => $servicios,
            'seccion'   => $seccion,
        ], 'admin');
    }

    public function create(): void
    {
        $this->render('admin.servicios.form', [
            'title'    => 'Nuevo Servicio',
            'servicio' => null,
            'idx'      => null,
        ], 'admin');
    }

    public function store(): void
    {
        $seccion   = $this->getSeccion();
        $servicios = $this->decodeServicios($seccion);
        $items     = $this->parseItems($this->request->post('items', ''));

        $servicios[] = [
            '_id'    => uniqid('sv', true),
            'icon'   => Security::sanitize($this->request->post('icon', 'ri-settings-3-line')),
            'titulo' => Security::sanitize($this->request->post('titulo', '')),
            'desc'   => Security::sanitize($this->request->post('desc',   '')),
            'items'  => $items,
            'orden'  => (int) $this->request->post('orden', count($servicios)),
            'visible' => 1,
        ];
        $this->saveServicios($servicios);
        $this->redirect(APP_URL . '/admin/servicios');
    }

    public function edit(string $idx): void
    {
        $seccion   = $this->getSeccion();
        $servicios = $this->decodeServicios($seccion);
        $i         = $this->findIdx($servicios, $idx);

        if ($i < 0) $this->redirect(APP_URL . '/admin/servicios');

        $this->render('admin.servicios.form', [
            'title'    => 'Editar Servicio',
            'servicio' => $servicios[$i],
            'idx'      => $servicios[$i]['_id'] ?? (string)$i,
        ], 'admin');
    }

    public function update(string $idx): void
    {
        $seccion   = $this->getSeccion();
        $servicios = $this->decodeServicios($seccion);
        $i         = $this->findIdx($servicios, $idx);

        if ($i < 0) $this->redirect(APP_URL . '/admin/servicios');

        $items = $this->parseItems($this->request->post('items', ''));
        $servicios[$i] = [
            '_id'    => $servicios[$i]['_id'] ?? uniqid('sv', true),
            'icon'   => Security::sanitize($this->request->post('icon', 'ri-settings-3-line')),
            'titulo' => Security::sanitize($this->request->post('titulo', '')),
            'desc'   => Security::sanitize($this->request->post('desc',   '')),
            'items'  => $items,
            'orden'  => (int) $this->request->post('orden', $i),
            'visible' => isset($servicios[$i]['visible']) ? (int)$servicios[$i]['visible'] : 1,
        ];
        $this->saveServicios($servicios);
        $this->redirect(APP_URL . '/admin/servicios');
    }

    public function delete(string $idx): void
    {
        $seccion   = $this->getSeccion();
        $servicios = $this->decodeServicios($seccion);
        $i         = $this->findIdx($servicios, $idx);

        if ($i >= 0) {
            array_splice($servicios, $i, 1);
            $this->saveServicios(array_values($servicios));
        }
        $this->redirect(APP_URL . '/admin/servicios');
    }

    public function toggleVisible(string $idx): void
    {
        $seccion   = $this->getSeccion();
        $servicios = $this->decodeServicios($seccion);
        $i         = $this->findIdx($servicios, $idx);

        if ($i < 0) {
            $this->json(['success' => false, 'message' => 'Servicio no encontrado.'], 404);
            return;
        }
        $newVal = isset($servicios[$i]['visible']) && $servicios[$i]['visible'] ? 0 : 1;
        $servicios[$i]['visible'] = $newVal;
        $this->saveServicios($servicios);
        $this->json(['success' => true, 'visible' => $newVal]);
    }

    // ── util ───────────────────────────────────────────────
    private function parseItems(string $raw): array
    {
        return array_values(
            array_filter(
                array_map('trim', explode("\n", $raw))
            )
        );
    }
}
