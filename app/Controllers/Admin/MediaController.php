<?php
// ============================================================
// app/Controllers/Admin/MediaController.php
// Gestión de biblioteca de medios (imágenes) para el editor Quill
// ============================================================

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Database;
use Core\ImageUploader;

class MediaController extends Controller
{
    /**
     * POST /admin/media/upload
     * Recibe: file (multipart), driver ignorado — se detecta de configuración
     * Devuelve: JSON {success, url, id, filename}
     */
    public function upload(): void
    {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'No se recibió archivo válido.'], 400);
            return;
        }

        $db = Database::getInstance();

        // Usar driver enviado desde el modal; si no viene, leer configuración de BD
        $postDriver = trim($_POST['driver'] ?? '');
        if (in_array($postDriver, ['local', 'imgbb'], true)) {
            $driver = $postDriver;
        } else {
            $driver = $db->query("SELECT valor FROM configuracion WHERE clave = 'img_driver' LIMIT 1")
                         ->fetchColumn() ?: 'local';
        }

        $url = ImageUploader::upload($_FILES['file'], $driver, 'media');

        if (!$url) {
            $this->json(['success' => false, 'message' => 'Error al subir imagen. Verifica el driver y la API key.'], 500);
            return;
        }

        // Guardar en media_library
        $stmt = $db->prepare(
            "INSERT INTO media_library (url, driver, filename, mime, size)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $url,
            $driver,
            basename($_FILES['file']['name']),
            $_FILES['file']['type'] ?? '',
            (int)$_FILES['file']['size'],
        ]);
        $id = (int)$db->lastInsertId();

        $this->json(['success' => true, 'url' => $url, 'id' => $id, 'filename' => basename($_FILES['file']['name'])]);
    }

    /**
     * GET /admin/media/list
     * Devuelve: JSON {success, data: [{id, url, driver, filename, creado_en}...]}
     */
    public function list(): void
    {
        $db    = Database::getInstance();
        $items = $db->query(
            "SELECT id, url, driver, filename, size, creado_en
             FROM media_library
             ORDER BY creado_en DESC
             LIMIT 200"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->json(['success' => true, 'data' => $items]);
    }

    /**
     * POST /admin/media/delete/{id}
     * Elimina registro de BD (y archivo local si aplica)
     */
    public function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT url, driver FROM media_library WHERE id = ?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $this->json(['success' => false, 'message' => 'No encontrado.'], 404);
            return;
        }

        // Eliminar archivo físico si es local
        if ($row['driver'] === 'local') {
            $localPath = APP_ROOT . '/public' . $row['url'];
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }

        $db->prepare("DELETE FROM media_library WHERE id = ?")->execute([$id]);
        $this->json(['success' => true]);
    }
}
