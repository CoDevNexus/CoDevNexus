<?php
namespace App\Controllers\Api;

use Core\Controller;
use App\Models\ConfiguracionModel;

class ConfiguracionController extends Controller
{
    /** Endpoint público — solo expone claves social_* */
    public function sociales(): void
    {
        $whitelist = [
            'social_whatsapp', 'social_linkedin', 'social_github',
            'social_telegram', 'social_twitter',  'social_instagram',
            'social_youtube',  'social_website',
        ];

        $model = new ConfiguracionModel();
        $data  = $model->getPublic($whitelist);

        // Eliminar entradas vacías
        $data = array_filter($data, fn($v) => !empty($v));

        $this->json(['success' => true, 'data' => $data]);
    }

    /** Endpoint público — variables de tema/colores */
    public function tema(): void
    {
        $whitelist = [
            'theme_color_cyan', 'theme_color_purple', 'theme_color_orange',
            'theme_color_bg',   'theme_color_text',   'theme_particles',
            'theme_glow_intensity', 'particles_style',
            'typewriter_lines', 'typewriter_speed', 'typewriter_pause',
            'typewriter_color', 'typewriter_size',
        ];

        $model = new ConfiguracionModel();
        $this->json(['success' => true, 'data' => $model->getPublic($whitelist)]);
    }

    /** Endpoint público — logos y nombre del sitio */
    public function marca(): void
    {
        $whitelist = [
            'site_name', 'site_tagline', 'logo_principal', 'logo_admin', 'favicon',
        ];

        $model = new ConfiguracionModel();
        $this->json(['success' => true, 'data' => $model->getPublic($whitelist)]);
    }
}
