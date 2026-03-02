<?php
namespace App\Controllers\Admin;

use Core\Controller;
use Core\Security;
use Core\Telegram;
use App\Models\AdminUserModel;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['admin_logged_in'])) {
            $this->redirect(APP_URL . '/admin');
        }
        $this->render('admin.login', ['title' => 'Login · Admin'], 'none');
    }

    public function login(): void
    {
        $ip       = $this->request->ip();
        $username = trim($this->request->post('username', ''));
        $password = $this->request->post('password', '');

        // Rate limit: 5 intentos por 5 minutos
        if (!Security::rateLimit($ip, 'login_' . $username, 5, 300)) {
            $this->render('admin.login', [
                'title' => 'Login · Admin',
                'error' => 'Demasiados intentos. Espera 5 minutos.',
            ], 'none');
            return;
        }

        $model = new AdminUserModel();
        $user  = $model->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            // Notificar Telegram
            try { (new Telegram())->notifyLoginFail($username, $ip); } catch (\Throwable) {}

            $this->render('admin.login', [
                'title' => 'Login · Admin',
                'error' => 'Credenciales incorrectas.',
            ], 'none');
            return;
        }

        // Login exitoso
        Security::cleanOldAttempts($ip);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = $user['id'];
        $_SESSION['admin_username']  = $user['username'];

        $this->redirect(APP_URL . '/admin');
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect(APP_URL . '/admin/login');
    }
}
