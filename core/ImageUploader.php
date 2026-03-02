<?php
// ============================================================
// core/ImageUploader.php — Estrategia Local vs ImgBB
// ============================================================

namespace Core;

class ImageUploader
{
    private const ALLOWED_MIME = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    ];

    private const MAX_SIZE = 5 * 1024 * 1024; // 5 MB

    /**
     * @param array  $file   — $_FILES[key]
     * @param string $toggle — 'local' | 'imgbb'
     * @param string $folder — subcarpeta dentro de /public/uploads/
     * @return string|null   — URL final o null si falló
     */
    public static function upload(array $file, string $toggle, string $folder = 'portafolio'): ?string
    {
        if ($toggle === 'imgbb') {
            return self::uploadImgBB($file);
        }
        return self::uploadLocal($file, $folder);
    }

    /**
     * Upload local — guarda en /public/uploads/{folder}/
     * Devuelve ruta relativa p.ej. /uploads/portafolio/abc123.jpg
     */
    private static function uploadLocal(array $file, string $folder): ?string
    {
        if (!self::validate($file)) {
            return null;
        }

        $uploadDir = APP_ROOT . '/public/uploads/' . $folder . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return '/uploads/' . $folder . '/' . $filename;
    }

    /**
     * Upload a ImgBB — POST multipart a api.imgbb.com
     * Devuelve URL externa o null
     */
    private static function uploadImgBB(array $file): ?string
    {
        if (!self::validate($file)) {
            return null;
        }

        // Leer API key de BD
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = 'imgbb_api_key'");
            $stmt->execute();
            $apiKey = $stmt->fetchColumn();
        } catch (\Throwable) {
            return null;
        }

        if (empty($apiKey)) {
            return null;
        }

        $imageData = base64_encode(file_get_contents($file['tmp_name']));

        $ch = curl_init('https://api.imgbb.com/1/upload');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POSTFIELDS     => [
                'key'   => $apiKey,
                'image' => $imageData,
            ],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return null;

        $data = json_decode($response, true);
        if (!empty($data['data']['url'])) {
            return $data['data']['url'];
        }

        return null;
    }

    private static function validate(array $file): bool
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }
        if ($file['size'] > self::MAX_SIZE) {
            return false;
        }

        // Validar MIME real (no solo extensión)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        return in_array($mime, self::ALLOWED_MIME, true);
    }

    /**
     * Verificar API key de ImgBB con una imagen de prueba mínima
     */
    public static function verifyImgbbKey(string $apiKey): bool
    {
        // 1x1 PNG transparente en base64
        $pixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==';
        $ch    = curl_init('https://api.imgbb.com/1/upload');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => ['key' => $apiKey, 'image' => $pixel],
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        return !empty($data['data']['url']);
    }
}
