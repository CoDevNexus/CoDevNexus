<?php
namespace App\Controllers\Admin;

use Core\Controller;
use Core\Security;
use Core\ImageUploader;
use Core\Telegram;
use Core\Mailer;
use App\Models\ConfiguracionModel;
use App\Models\AdminUserModel;

class ConfiguracionController extends Controller
{
    public function index(): void
    {
        $cfg  = new ConfiguracionModel();
        $data = $cfg->getAllGrouped();

        // No pasar passwords al frontend en texto plano
        $data['smtp_password'] = $data['smtp_password'] ? '••••••••' : '';

        $this->render('admin.configuracion.index', [
            'title' => 'Configuración · Admin',
            'cfg'   => $data,
        ], 'admin');
    }

    public function update(): void
    {
        $model  = new ConfiguracionModel();
        $tab    = $this->request->post('tab', 'empresa');
        $batch  = [];

        switch ($tab) {
            case 'empresa':
                foreach (['site_name','site_tagline','site_email','site_phone','site_address','site_footer_text'] as $k) {
                    $batch[$k] = Security::sanitize($this->request->post($k, ''));
                }
                break;

            case 'tema':
                foreach ([
                    'theme_color_cyan','theme_color_purple','theme_color_orange',
                    'theme_color_bg','theme_color_text','theme_glow_intensity',
                    'typewriter_lines','typewriter_color','typewriter_size',
                    'typewriter_speed','typewriter_pause',
                ] as $k) {
                    $batch[$k] = Security::sanitize($this->request->post($k, ''));
                }
                $batch['theme_particles'] = $this->request->post('theme_particles') ? '1' : '0';
                $allowed_styles = ['network','bubbles','snow','stars'];
                $ps = Security::sanitize($this->request->post('particles_style', 'network'));
                $batch['particles_style'] = in_array($ps, $allowed_styles) ? $ps : 'network';
                break;

            case 'smtp':
                // driver selector
                $driver = $this->request->post('mail_driver', 'smtp');
                $batch['mail_driver'] = in_array($driver, ['smtp','gmail']) ? $driver : 'smtp';

                // Gmail fields
                $batch['gmail_user']      = Security::sanitize($this->request->post('gmail_user', ''));
                $batch['gmail_from_name'] = Security::sanitize($this->request->post('gmail_from_name', ''));
                $batch['gmail_admin_copy']= Security::sanitize($this->request->post('gmail_admin_copy', ''));
                $gmailPass = $this->request->post('gmail_app_password_new', '');
                if (!empty($gmailPass)) {
                    $batch['gmail_app_password'] = Security::encryptSmtpPassword($gmailPass);
                }

                // SMTP custom fields
                foreach (['smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_from_email','smtp_from_name','smtp_admin_copy'] as $k) {
                    $batch[$k] = Security::sanitize($this->request->post($k, ''));
                }
                $newPass = $this->request->post('smtp_password_new', '');
                if (!empty($newPass)) {
                    $batch['smtp_password'] = Security::encryptSmtpPassword($newPass);
                }
                break;

            case 'apis':
                foreach ([
                    'telegram_bot_token','telegram_chat_id',
                    'telegram_notify_contacto','telegram_notify_login_fail',
                    'telegram_notify_nuevo_user','telegram_notify_config',
                    'imgbb_api_key','recaptcha_site_key','recaptcha_secret',
                ] as $k) {
                    $val = $this->request->post($k, '');
                    // checkboxes
                    if (in_array($k, ['telegram_notify_contacto','telegram_notify_login_fail','telegram_notify_nuevo_user','telegram_notify_config'])) {
                        $val = $val ? '1' : '0';
                    }
                    $batch[$k] = Security::sanitize((string) $val);
                }
                break;

            case 'sociales':
                foreach ([
                    'social_whatsapp','social_linkedin','social_github','social_telegram',
                    'social_twitter','social_instagram','social_youtube','social_website',
                ] as $k) {
                    $batch[$k] = Security::sanitize($this->request->post($k, ''));
                }
                break;

            case 'sistema':
                $batch['modo_seguro']           = $this->request->post('modo_seguro')       ? '1' : '0';
                $batch['modo_mantenimiento']     = $this->request->post('modo_mantenimiento') ? '1' : '0';
                $batch['mantenimiento_mensaje']  = Security::sanitize($this->request->post('mantenimiento_mensaje', ''));
                break;
        }

        if ($batch) {
            $model->setBatch($batch);
        }

        $this->redirect(APP_URL . '/admin/configuracion?tab=' . $tab . '&saved=1');
    }

    public function testEmail(): void
    {
        try {
            $result = (new Mailer())->testConnection();
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'error' => $e->getMessage()];
        }
        $this->json($result);
    }

    public function testTelegram(): void
    {
        $result = (new Telegram())->testConnection();
        $this->json($result);
    }

    /** Detecta chats disponibles a partir del token enviado en el form (no necesita estar guardado) */
    public function telegramChats(): void
    {
        $token = trim($this->request->post('token', ''));
        if (empty($token)) {
            $this->json(['ok' => false, 'error' => 'Pega el token primero.']);
            return;
        }

        $url = "https://api.telegram.org/bot{$token}/getUpdates";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $res     = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            $this->json(['ok' => false, 'error' => 'Error de red: ' . $curlErr]);
            return;
        }

        $data = json_decode($res, true);
        if (empty($data['ok'])) {
            $desc = $data['description'] ?? 'Token inválido';
            $this->json(['ok' => false, 'error' => "Token incorrecto — Telegram dice: {$desc}"]);
            return;
        }

        // Extraer chats únicos de los updates
        $chats = [];
        foreach (($data['result'] ?? []) as $upd) {
            $chat = $upd['message']['chat']
                 ?? $upd['channel_post']['chat']
                 ?? $upd['my_chat_member']['chat']
                 ?? null;
            if (!$chat) continue;
            $id = (string) $chat['id'];
            if (!isset($chats[$id])) {
                $title = $chat['title']
                      ?? trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''))
                      ?: $chat['username']
                      ?? $id;
                $chats[$id] = ['id' => $id, 'title' => $title, 'type' => $chat['type'] ?? '?'];
            }
        }

        if (empty($chats)) {
            $this->json(['ok' => true, 'chats' => [], 'empty' => true]);
            return;
        }

        $this->json(['ok' => true, 'chats' => array_values($chats)]);
    }

    public function testImgbb(): void
    {
        $cfg    = new ConfiguracionModel();
        $apiKey = $cfg->get('imgbb_api_key');

        if (empty($apiKey)) {
            $this->json(['ok' => false, 'error' => 'API key no configurada.']);
        }

        $ok = ImageUploader::verifyImgbbKey($apiKey);
        $this->json($ok
            ? ['ok' => true,  'message' => 'API key de ImgBB válida.']
            : ['ok' => false, 'error'   => 'API key inválida o sin permisos.']
        );
    }

    public function uploadLogo(): void
    {
        $key    = $this->request->post('logo_key', 'logo_principal');
        $folder = 'branding';

        if (!in_array($key, ['logo_principal', 'logo_admin', 'favicon'])) {
            $this->json(['ok' => false, 'error' => 'Clave inválida.'], 400);
        }

        $file = $this->request->file('logo');
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $this->json(['ok' => false, 'error' => 'No se recibió archivo.'], 400);
        }

        $url = ImageUploader::upload($file, 'local', $folder);
        if (!$url) {
            $this->json(['ok' => false, 'error' => 'Error al subir el archivo. Verifica tipo y tamaño.'], 400);
        }

        $cfg = new ConfiguracionModel();
        $cfg->set($key, $url);
        $this->json(['ok' => true, 'url' => $url]);
    }

    public function changePassword(): void
    {
        $current = $this->request->post('current_password', '');
        $new     = $this->request->post('new_password', '');
        $confirm = $this->request->post('confirm_password', '');

        if ($new !== $confirm || strlen($new) < 8) {
            $this->redirect(APP_URL . '/admin/configuracion?tab=sistema&error=password');
            return;
        }

        $model = new AdminUserModel();
        $user  = $model->find((int) $_SESSION['admin_id']);

        if (!$user || !password_verify($current, $user['password'])) {
            $this->redirect(APP_URL . '/admin/configuracion?tab=sistema&error=wrongpassword');
            return;
        }

        $model->updatePassword($user['id'], password_hash($new, PASSWORD_BCRYPT));
        $this->redirect(APP_URL . '/admin/configuracion?tab=sistema&saved=1');
    }
}
