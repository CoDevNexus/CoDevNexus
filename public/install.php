<?php
// ============================================================
// CoDevNexus — Instalador Web
// Acceder SOLO durante el despliegue inicial.
// Eliminar o proteger este archivo después de instalar.
// ============================================================
declare(strict_types=1);

define('INSTALL_VERSION', '1.0.0');
define('APP_ROOT', dirname(__DIR__));
define('LOCK_FILE', APP_ROOT . '/storage/installed.lock');
define('CONFIG_FILE', APP_ROOT . '/config/config.php');
define('MIN_PHP', '8.1.0');

session_start();

// ── Reset forzado (elimina sesión de instalación anterior) ───
// Solo en GET para no interferir con los POST de los formularios.
// Redirige sin ?force para que los forms no lo incluyan en el action.
if (isset($_GET['force']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION = [];
    session_destroy();
    // Eliminar lock y config si existen (reinstalación completa)
    if (file_exists(LOCK_FILE))   @unlink(LOCK_FILE);
    if (file_exists(CONFIG_FILE)) @unlink(CONFIG_FILE);
    header('Location: install.php');
    exit;
}

// ── Si ya está instalado, bloquear ───────────────────────────
if (file_exists(LOCK_FILE) && !isset($_GET['force'])) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ya instalado</title>
    <style>body{font-family:sans-serif;background:#0b0f19;color:#e2e8f0;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
    .box{background:#111827;padding:2rem 3rem;border-radius:12px;border:1px solid #1e2d40;text-align:center}
    a{color:#00d4ff}h2{color:#00d4ff}</style></head><body>
    <div class="box"><h2>⚠️ CoDevNexus ya está instalado</h2>
    <p>Si necesitas reinstalar, elimina el archivo <code>storage/installed.lock</code> del servidor.</p>
    <p><a href="/">Ir al sitio</a> · <a href="/admin">Ir al admin</a></p></div></body></html>');
}

// ── Helpers ───────────────────────────────────────────────────
function esc(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function step(): int {
    return (int)($_SESSION['install_step'] ?? 1);
}

function setStep(int $s): void {
    $_SESSION['install_step'] = $s;
}

function sset(string $k, mixed $v): void {
    $_SESSION['install_data'][$k] = $v;
}

function sget(string $k, mixed $def = ''): mixed {
    return $_SESSION['install_data'][$k] ?? $def;
}

function testDb(string $host, string $port, string $db, string $user, string $pass): ?PDO {
    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db}`");
        return $pdo;
    } catch (Throwable) {
        return null;
    }
}

// Requirements
function checkReqs(): array {
    $errs = [];
    $warn = [];
    if (version_compare(PHP_VERSION, MIN_PHP, '<'))
        $errs[] = 'PHP ' . MIN_PHP . '+ requerido (actual: ' . PHP_VERSION . ')';
    foreach (['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'session'] as $ext)
        if (!extension_loaded($ext))
            $errs[] = "Extensión PHP requerida: {$ext}";
    $dirs = ['storage', 'storage/cache', 'public/uploads', 'config'];
    foreach ($dirs as $d) {
        $path = APP_ROOT . '/' . $d;
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path))
            $errs[] = "No se pudo crear directorio: {$d}";
        elseif (!is_writable($path))
            $warn[] = "Sin permisos de escritura: {$d}";
    }
    return ['errors' => $errs, 'warnings' => $warn];
}

$errors  = [];
$success = '';

// ── POST handler ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'check_reqs') {
        $r = checkReqs();
        if (empty($r['errors'])) {
            setStep(2);
        } else {
            $errors = $r['errors'];
        }
    }

    elseif ($action === 'save_db') {
        $host = trim($_POST['db_host'] ?? 'localhost');
        $port = trim($_POST['db_port'] ?? '3306');
        $db   = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = trim($_POST['db_pass'] ?? '');

        if (!$db || !$user) {
            $errors[] = 'Nombre de BD y usuario son requeridos.';
        } else {
            $pdo = testDb($host, $port, $db, $user, $pass);
            if (!$pdo) {
                $errors[] = 'No se pudo conectar a MySQL. Verifica host, usuario y contraseña.';
            } else {
                sset('db_host', $host); sset('db_port', $port);
                sset('db_name', $db);   sset('db_user', $user);
                sset('db_pass', $pass);
                setStep(3);
            }
        }
    }

    elseif ($action === 'save_admin') {
        $username = trim($_POST['admin_user'] ?? '');
        $password = trim($_POST['admin_pass'] ?? '');
        $confirm  = trim($_POST['admin_confirm'] ?? '');
        if (!$username || !$password) {
            $errors[] = 'Usuario y contraseña son requeridos.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        } else {
            sset('admin_user', $username);
            sset('admin_pass', password_hash($password, PASSWORD_BCRYPT));
            setStep(4);
        }
    }

    elseif ($action === 'save_site') {
        $name    = trim($_POST['site_name'] ?? 'CoDevNexus');
        $tagline = trim($_POST['site_tagline'] ?? '');
        $email   = trim($_POST['site_email'] ?? '');
        $url     = rtrim(trim($_POST['app_url'] ?? ''), '/');
        $env     = $_POST['app_env'] ?? 'production';
        if (!$name || !$url) {
            $errors[] = 'Nombre del sitio y URL son requeridos.';
        } else {
            sset('site_name', $name); sset('site_tagline', $tagline);
            sset('site_email', $email); sset('app_url', $url);
            sset('app_env', $env);
            setStep(5);
        }
    }

    elseif ($action === 'run_install') {
        $demo = ($_POST['data_mode'] ?? 'demo') === 'demo';
        sset('demo', $demo);

        // Connect
        $pdo = testDb(sget('db_host'), sget('db_port'), sget('db_name'), sget('db_user'), sget('db_pass'));
        if (!$pdo) {
            $errors[] = 'Error al reconectar con la BD. Vuelve al paso de BD.';
        } else {
            try {
                // ── Schema ──────────────────────────────────────
                $pdo->exec(installSchema());

                // ── Admin user ───────────────────────────────────
                $pdo->prepare("DELETE FROM admin_users")->execute();
                $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?,?)")
                    ->execute([sget('admin_user'), sget('admin_pass')]);

                // ── Configuracion base ───────────────────────────
                $pdo->prepare("DELETE FROM configuracion")->execute();
                $siteName = sget('site_name');
                $baseRows = baseConfig($siteName, sget('site_tagline'), sget('site_email'), sget('app_url'));
                $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
                foreach ($baseRows as $k => $v) $stmt->execute([$k, $v]);

                // ── Data seed ────────────────────────────────────
                if ($demo) {
                    installDemoData($pdo, $siteName);
                } else {
                    installEmptyData($pdo);
                }

                // ── config.php ───────────────────────────────────
                $appKey = bin2hex(random_bytes(16));
                writeConfig(sget('db_host'), sget('db_port'), sget('db_name'),
                            sget('db_user'), sget('db_pass'), $appKey,
                            sget('app_url'), sget('app_env'));

                // ── Lock ─────────────────────────────────────────
                @mkdir(dirname(LOCK_FILE), 0755, true);
                file_put_contents(LOCK_FILE, json_encode([
                    'installed_at' => date('c'),
                    'version'      => INSTALL_VERSION,
                    'site_name'    => $siteName,
                ]));

                setStep(6);
                session_write_close();
                header('Location: install.php');
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Error durante instalación: ' . $e->getMessage();
            }
        }
    }
}

// ── SQL Schema ────────────────────────────────────────────────
function installSchema(): string {
    return "
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(60)   NOT NULL,
  `password`   VARCHAR(255)  NOT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `secciones` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `titulo`       VARCHAR(200)     NOT NULL,
  `contenido`    LONGTEXT,
  `tipo_seccion` ENUM('hero','sobre','portafolio','tecnologias','servicios','contacto','blog','otro') NOT NULL DEFAULT 'otro',
  `orden`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `visible`      TINYINT(1)       NOT NULL DEFAULT 1,
  `modo_seguro`  TINYINT(1)       NOT NULL DEFAULT 0,
  `updated_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tecnologias` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(80)      NOT NULL,
  `nivel`       TINYINT UNSIGNED NOT NULL DEFAULT 50,
  `icono_tipo`  VARCHAR(20) NOT NULL DEFAULT 'devicon',
  `icono_valor` TEXT,
  `categoria`   ENUM('lenguaje','framework','base_datos','red','devops','iot','otro') NOT NULL DEFAULT 'otro',
  `visible`     TINYINT(1)       NOT NULL DEFAULT 1,
  `orden`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portafolio` (
  `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `titulo`            VARCHAR(200)     NOT NULL,
  `descripcion_corta` VARCHAR(300),
  `descripcion_larga` LONGTEXT,
  `categoria`         ENUM('redes','software','iot','automatizacion','web','otro') NOT NULL DEFAULT 'otro',
  `imagen_url`        VARCHAR(400),
  `enlace_demo`       VARCHAR(400),
  `enlace_repo`       VARCHAR(400),
  `modo_seguro`       TINYINT(1)       NOT NULL DEFAULT 0,
  `visible`           TINYINT(1)       NOT NULL DEFAULT 1,
  `orden`             TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mensajes` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(120)  NOT NULL,
  `correo`      VARCHAR(200)  NOT NULL,
  `telefono`    VARCHAR(30)   NULL,
  `pais`        VARCHAR(80)   NULL,
  `asunto`      VARCHAR(250),
  `mensaje`     TEXT          NOT NULL,
  `ip_origen`   VARCHAR(45),
  `user_agent`  VARCHAR(500),
  `leido`       TINYINT(1)    NOT NULL DEFAULT 0,
  `respondido`  TINYINT(1)    NOT NULL DEFAULT 0,
  `fecha`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` VARCHAR(80) NOT NULL,
  `valor` TEXT,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`           VARCHAR(45)  NOT NULL,
  `username`     VARCHAR(60),
  `intentado_en` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_library` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `url`         VARCHAR(500) NOT NULL,
  `driver`      ENUM('local','imgbb') NOT NULL DEFAULT 'local',
  `filename`    VARCHAR(255),
  `mime`        VARCHAR(80),
  `size`        INT UNSIGNED DEFAULT 0,
  `creado_en`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_driver` (`driver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
}

function baseConfig(string $name, string $tagline, string $email, string $url): array {
    return [
        'site_name'             => $name,
        'site_tagline'          => $tagline ?: 'Donde el código conecta mundos',
        'site_email'            => $email ?: 'contacto@example.com',
        'site_phone'            => '',
        'site_address'          => '',
        'site_footer_text'      => '© ' . date('Y') . ' ' . $name . '. Todos los derechos reservados.',
        'logo_principal'        => '/assets/img/logo.svg',
        'logo_admin'            => '/assets/img/logo.svg',
        'favicon'               => '/assets/img/logo.svg',
        'theme_color_cyan'      => '#00d4ff',
        'theme_color_purple'    => '#7b2d8b',
        'theme_color_orange'    => '#ff6b35',
        'theme_color_bg'        => '#0b0f19',
        'theme_color_text'      => '#e2e8f0',
        'theme_particles'       => '1',
        'theme_glow_intensity'  => '70',
        'particles_style'       => 'network',
        'typewriter_lines'      => $tagline ?: $name,
        'typewriter_color'      => '#00d4ff',
        'typewriter_size'       => '1.25',
        'typewriter_speed'      => '80',
        'typewriter_pause'      => '1800',
        'mail_driver'           => 'smtp',
        'gmail_user'            => '', 'gmail_app_password' => '',
        'gmail_from_name'       => $name, 'gmail_admin_copy' => '',
        'smtp_host'             => 'smtp.gmail.com', 'smtp_port' => '587',
        'smtp_encryption'       => 'tls', 'smtp_user' => '',
        'smtp_password'         => '', 'smtp_from_email' => $email ?: 'no-reply@example.com',
        'smtp_from_name'        => $name, 'smtp_admin_copy' => '',
        'telegram_bot_token'         => '', 'telegram_chat_id' => '',
        'telegram_notify_contacto'   => '1', 'telegram_notify_login_fail' => '1',
        'telegram_notify_nuevo_user' => '1', 'telegram_notify_config' => '0',
        'imgbb_api_key'         => '', 'recaptcha_site_key' => '',
        'recaptcha_secret'      => '',
        'social_whatsapp'       => '', 'social_linkedin' => '',
        'social_github'         => '', 'social_telegram' => '',
        'social_twitter'        => '', 'social_instagram'=> '',
        'social_youtube'        => '', 'social_website' => '',
        'modo_seguro'           => '0',
        'modo_mantenimiento'    => '0',
        'mantenimiento_mensaje' => 'Sitio en mantenimiento. Volvemos pronto.',
        'typewriter_lines'      => 'Desarrollador Web,IoT,Automatización',
    ];
}

function installDemoData(PDO $pdo, string $name): void {
    // Secciones
    $pdo->prepare("DELETE FROM secciones")->execute();
    $secs = [
        [$name, '<p>Bienvenido a mi portafolio profesional.</p>', 'hero', 1],
        ['Sobre mí', '<p>Desarrollador Full Stack con experiencia en Redes, IoT y Automatización.</p>', 'sobre', 2],
        ['Portafolio', '', 'portafolio', 3],
        ['Stack Tecnológico', '', 'tecnologias', 4],
        ['Servicios', '', 'servicios', 5],
        ['Contacto', '<p>¿Tienes un proyecto en mente? Hablemos.</p>', 'contacto', 6],
    ];
    $st = $pdo->prepare("INSERT INTO secciones (titulo,contenido,tipo_seccion,orden,visible) VALUES (?,?,?,?,1)");
    foreach ($secs as $s) $st->execute($s);

    // Tecnologías demo
    $pdo->prepare("DELETE FROM tecnologias")->execute();
    $techs = [
        ['PHP', 90, 'devicons', 'devicon-php-plain', 'lenguaje', 1],
        ['JavaScript', 85, 'devicons', 'devicon-javascript-plain', 'lenguaje', 2],
        ['MySQL', 80, 'devicons', 'devicon-mysql-plain', 'base_datos', 3],
        ['Linux', 75, 'devicons', 'devicon-linux-plain', 'devops', 4],
        ['Docker', 70, 'devicons', 'devicon-docker-plain', 'devops', 5],
        ['Python', 65, 'devicons', 'devicon-python-plain', 'lenguaje', 6],
    ];
    $st = $pdo->prepare("INSERT INTO tecnologias (nombre,nivel,icono_tipo,icono_valor,categoria,orden) VALUES (?,?,?,?,?,?)");
    foreach ($techs as $t) $st->execute($t);

    // Portafolio demo
    $pdo->prepare("DELETE FROM portafolio")->execute();
    $projs = [
        ['Sistema de Monitoreo IoT', 'Plataforma de monitoreo en tiempo real para dispositivos IoT industriales.', 'iot', 1],
        ['Portal Web Corporativo', 'Sitio web completo con CMS personalizado y SEO optimizado.', 'web', 2],
        ['Automatización de Redes', 'Scripts de automatización para configuración masiva de routers Cisco.', 'redes', 3],
    ];
    $st = $pdo->prepare("INSERT INTO portafolio (titulo,descripcion_corta,categoria,visible,orden) VALUES (?,?,?,1,?)");
    foreach ($projs as $p) $st->execute($p);

    // Servicios demo (en JSON dentro de secciones)
    $demos = json_encode([
        ['_id' => uniqid('sv',true), 'icon'=>'ri-code-s-slash-line', 'titulo'=>'Desarrollo Web', 'desc'=>'Aplicaciones web modernas y escalables.', 'items'=>['PHP / Laravel','React / Vue','APIs REST'], 'orden'=>1],
        ['_id' => uniqid('sv',true), 'icon'=>'ri-cpu-line', 'titulo'=>'Soluciones IoT', 'desc'=>'Conecta dispositivos físicos a la nube.', 'items'=>['MQTT / Node-RED','Dashboards','Alertas'], 'orden'=>2],
        ['_id' => uniqid('sv',true), 'icon'=>'ri-server-line', 'titulo'=>'Administración de Redes', 'desc'=>'Diseño y monitoreo de infraestructura.', 'items'=>['Cisco / MikroTik','VPN','Firewall'], 'orden'=>3],
    ], JSON_UNESCAPED_UNICODE);
    $pdo->prepare("UPDATE secciones SET contenido=? WHERE tipo_seccion='servicios'")->execute([$demos]);
}

function installEmptyData(PDO $pdo): void {
    $pdo->prepare("DELETE FROM secciones")->execute();
    $secs = [
        ['Hero', '', 'hero', 1],
        ['Sobre mí', '', 'sobre', 2],
        ['Portafolio', '', 'portafolio', 3],
        ['Stack Tecnológico', '', 'tecnologias', 4],
        ['Servicios', '', 'servicios', 5],
        ['Contacto', '', 'contacto', 6],
    ];
    $st = $pdo->prepare("INSERT INTO secciones (titulo,contenido,tipo_seccion,orden,visible) VALUES (?,?,?,?,1)");
    foreach ($secs as $s) $st->execute($s);
    $pdo->prepare("DELETE FROM tecnologias")->execute();
    $pdo->prepare("DELETE FROM portafolio")->execute();
}

function writeConfig(string $host, string $port, string $db, string $user,
                     string $pass, string $key, string $url, string $env): void {
    $passEsc = addslashes($pass);
    $content = <<<PHP
<?php
// ============================================================
// config/config.php — Generado por el instalador de CoDevNexus
// Generado: {date}
// ============================================================

define('DB_HOST',    '{$host}');
define('DB_PORT',    '{$port}');
define('DB_NAME',    '{$db}');
define('DB_USER',    '{$user}');
define('DB_PASS',    '{$passEsc}');
define('DB_CHARSET', 'utf8mb4');

define('APP_KEY', '{$key}');
define('APP_ENV', '{$env}');
define('APP_URL', '{$url}');
PHP;
    $content = str_replace('{date}', date('Y-m-d H:i:s'), $content);
    file_put_contents(CONFIG_FILE, $content);
}

// ── Read lock info for step 6 ─────────────────────────────────
$lockData = file_exists(LOCK_FILE) ? json_decode(file_get_contents(LOCK_FILE), true) : null;
$currentStep = ($lockData) ? 6 : step();

// ============================================================
// HTML
// ============================================================
$reqs = ($currentStep === 1) ? checkReqs() : ['errors'=>[],'warnings'=>[]];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalador · CoDevNexus</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:    #0b0f19; --bg2: #111827; --bg3: #0f172a;
  --cyan:  #00d4ff; --purple: #7b2d8b; --orange: #ff6b35;
  --text:  #e2e8f0; --muted: #94a3b8; --border: #1e2d40;
  --green: #10b981; --red: #ef4444; --yellow: #f59e0b;
}
body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif;
       min-height: 100vh; display: flex; flex-direction: column; align-items: center;
       justify-content: flex-start; padding: 2rem 1rem; }
.installer { width: 100%; max-width: 640px; }
.logo { text-align: center; margin-bottom: 2rem; }
.logo h1 { font-size: 1.8rem; font-weight: 800; }
.logo h1 span { color: var(--cyan); }
.logo p { color: var(--muted); font-size: .9rem; margin-top: .3rem; }
/* Steps */
.steps { display: flex; gap: .3rem; margin-bottom: 2rem; }
.step-dot { flex: 1; height: 4px; border-radius: 2px; background: var(--border);
            transition: background .3s; }
.step-dot.done { background: var(--green); }
.step-dot.active { background: var(--cyan); }
/* Card */
.card { background: var(--bg2); border: 1px solid var(--border);
        border-radius: 14px; padding: 2rem; margin-bottom: 1.5rem; }
.card h2 { font-size: 1.15rem; font-weight: 700; margin-bottom: 1.3rem;
            display: flex; align-items: center; gap: .5rem; }
.card h2 i { font-size: 1.3rem; }
/* Form */
.fg { margin-bottom: 1.1rem; }
.fg label { display: block; font-size: .82rem; color: var(--muted); margin-bottom: .35rem; }
.fg input, .fg select {
  width: 100%; padding: .65rem .85rem;
  background: var(--bg3); border: 1px solid var(--border);
  border-radius: 8px; color: var(--text); font-size: .93rem;
  transition: border-color .2s;
}
.fg input:focus, .fg select:focus {
  outline: none; border-color: var(--cyan);
  box-shadow: 0 0 0 3px rgba(0,212,255,.12);
}
.fg-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
/* Buttons */
.btn { display: inline-flex; align-items: center; gap: .4rem;
       padding: .7rem 1.5rem; border: none; border-radius: 8px;
       font-size: .93rem; font-weight: 600; cursor: pointer; transition: opacity .2s; }
.btn:hover { opacity: .88; }
.btn-primary { background: linear-gradient(135deg, var(--cyan), var(--purple)); color: #0b0f19; }
.btn-secondary { background: var(--bg3); border: 1px solid var(--border); color: var(--text); }
.btn-danger { background: var(--red); color: #fff; }
.btn-full { width: 100%; justify-content: center; }
.btn-row { display: flex; gap: .8rem; margin-top: 1.5rem; flex-wrap: wrap; }
/* Alerts */
.alert { border-radius: 8px; padding: .8rem 1rem; margin-bottom: 1rem; font-size: .88rem; }
.alert-error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
.alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
.alert-warn { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.3); color: #fcd34d; }
/* Req list */
.req-list { list-style: none; display: flex; flex-direction: column; gap: .5rem; }
.req-list li { display: flex; align-items: center; gap: .6rem; font-size: .88rem;
               padding: .5rem .75rem; border-radius: 6px; background: var(--bg3); }
.req-list li.ok { color: var(--green); }
.req-list li.fail { color: var(--red); }
.req-list li.warn { color: var(--yellow); }
/* Radio cards */
.radio-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.radio-card { border: 2px solid var(--border); border-radius: 10px; padding: 1.2rem;
              cursor: pointer; transition: border-color .2s; }
.radio-card input { position: absolute; opacity: 0; pointer-events: none; }
.radio-card:has(input:checked) { border-color: var(--cyan); background: rgba(0,212,255,.05); }
.radio-card h3 { font-size: .95rem; margin-bottom: .4rem; color: var(--text); }
.radio-card p  { font-size: .80rem; color: var(--muted); line-height: 1.5; }
/* Final */
.success-icon { font-size: 4rem; text-align: center; margin-bottom: 1rem; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.info-item { background: var(--bg3); border-radius: 8px; padding: .7rem 1rem; font-size: .82rem; }
.info-item span { display: block; color: var(--muted); font-size: .75rem; margin-bottom: .2rem; }
.info-item strong { color: var(--text); }
.warning-box { background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.3);
               border-radius: 8px; padding: .9rem 1rem; font-size: .84rem; color: #fcd34d;
               margin-top: 1rem; line-height: 1.6; }
code { background: rgba(0,212,255,.1); color: var(--cyan); padding: .1rem .4rem;
       border-radius: 4px; font-size: .85em; }
/* Separator */
.section-sep { border: none; border-top: 1px solid var(--border); margin: 1.2rem 0; }
</style>
</head>
<body>
<div class="installer">

  <div class="logo">
    <h1>CoDev<span>Nexus</span></h1>
    <p>Instalador Web · v<?= INSTALL_VERSION ?></p>
  </div>

  <!-- Steps indicator -->
  <?php if ($currentStep < 6): ?>
  <div class="steps">
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <div class="step-dot <?= $i < $currentStep ? 'done' : ($i === $currentStep ? 'active' : '') ?>"></div>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <!-- Errors -->
  <?php if (!empty($errors)): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div>⚠ <?= esc($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="alert alert-success">✓ <?= esc($success) ?></div>
  <?php endif; ?>

  <!-- ════════════════════════════════════════════════════════ -->
  <!-- STEP 1: Bienvenida + Requisitos                         -->
  <!-- ════════════════════════════════════════════════════════ -->
  <?php if ($currentStep === 1): ?>
  <div class="card">
    <h2>🚀 Bienvenido al instalador</h2>
    <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.3rem;line-height:1.6">
      Este asistente configurará la base de datos, el usuario administrador y el sitio en pocos pasos.
      <strong>Asegúrate de tener las credenciales de MySQL a mano.</strong>
    </p>
    <hr class="section-sep">
    <h3 style="font-size:.9rem;color:var(--cyan);margin-bottom:.8rem">Verificación del sistema</h3>
    <ul class="req-list">
      <li class="<?= version_compare(PHP_VERSION, MIN_PHP, '>=') ? 'ok' : 'fail' ?>">
        <?= version_compare(PHP_VERSION, MIN_PHP, '>=') ? '✓' : '✗' ?>
        PHP <?= MIN_PHP ?>+ &nbsp;<small style="color:var(--muted)">(actual: <?= PHP_VERSION ?>)</small>
      </li>
      <?php foreach (['pdo'=>'PDO','pdo_mysql'=>'PDO MySQL','json'=>'JSON','mbstring'=>'Mbstring','openssl'=>'OpenSSL'] as $ext => $label): ?>
      <li class="<?= extension_loaded($ext) ? 'ok' : 'fail' ?>">
        <?= extension_loaded($ext) ? '✓' : '✗' ?> <?= $label ?>
      </li>
      <?php endforeach; ?>
      <?php foreach (['config'=>'config/ (escritura)','storage'=>'storage/ (escritura)'] as $dir => $label): ?>
        <?php $path = APP_ROOT.'/'.$dir; @mkdir($path, 0755, true); ?>
        <li class="<?= is_writable($path) ? 'ok' : 'warn' ?>">
          <?= is_writable($path) ? '✓' : '⚠' ?> <?= $label ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if (!empty($reqs['warnings'])): ?>
    <div class="alert alert-warn" style="margin-top:1rem">
      <?php foreach ($reqs['warnings'] as $w): ?><div>⚠ <?= esc($w) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($reqs['errors'])): ?>
    <form method="POST">
      <input type="hidden" name="action" value="check_reqs">
      <div class="btn-row">
        <button type="submit" class="btn btn-primary btn-full">Continuar →</button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- ════════════════════════════════════════════════════════ -->
  <!-- STEP 2: Base de datos                                   -->
  <!-- ════════════════════════════════════════════════════════ -->
  <?php elseif ($currentStep === 2): ?>
  <div class="card">
    <h2>🗄 Configuración de base de datos</h2>
    <p style="color:var(--muted);font-size:.83rem;margin-bottom:1.2rem">
      El instalador creará la base de datos y todas las tablas automáticamente.
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="save_db">
      <div class="fg-row">
        <div class="fg">
          <label>Host MySQL</label>
          <input type="text" name="db_host" value="<?= esc(sget('db_host','localhost')) ?>" required>
        </div>
        <div class="fg">
          <label>Puerto</label>
          <input type="text" name="db_port" value="<?= esc(sget('db_port','3306')) ?>" required>
        </div>
      </div>
      <div class="fg">
        <label>Nombre de la base de datos</label>
        <input type="text" name="db_name" value="<?= esc(sget('db_name','codevnexus')) ?>"
               placeholder="codevnexus" required>
        <small style="color:var(--muted);font-size:.78rem;display:block;margin-top:.3rem">
          Si no existe, se creará automáticamente.
        </small>
      </div>
      <div class="fg-row">
        <div class="fg">
          <label>Usuario MySQL</label>
          <input type="text" name="db_user" value="<?= esc(sget('db_user','root')) ?>" required>
        </div>
        <div class="fg">
          <label>Contraseña MySQL</label>
          <input type="password" name="db_pass" value="" placeholder="(vacía si no tiene)">
        </div>
      </div>
      <div class="btn-row">
        <button type="submit" class="btn btn-primary">Probar y continuar →</button>
      </div>
    </form>
  </div>

  <!-- ════════════════════════════════════════════════════════ -->
  <!-- STEP 3: Cuenta admin                                    -->
  <!-- ════════════════════════════════════════════════════════ -->
  <?php elseif ($currentStep === 3): ?>
  <div class="card">
    <h2>👤 Cuenta de administrador</h2>
    <form method="POST">
      <input type="hidden" name="action" value="save_admin">
      <div class="fg">
        <label>Nombre de usuario</label>
        <input type="text" name="admin_user" value="<?= esc(sget('admin_user','admin')) ?>"
               required autocomplete="off">
      </div>
      <div class="fg-row">
        <div class="fg">
          <label>Contraseña</label>
          <input type="password" name="admin_pass" required minlength="8"
                 placeholder="Mínimo 8 caracteres">
        </div>
        <div class="fg">
          <label>Repetir contraseña</label>
          <input type="password" name="admin_confirm" required>
        </div>
      </div>
      <div class="btn-row">
        <button type="submit" class="btn btn-primary">Continuar →</button>
      </div>
    </form>
  </div>

  <!-- ════════════════════════════════════════════════════════ -->
  <!-- STEP 4: Información del sitio                           -->
  <!-- ════════════════════════════════════════════════════════ -->
  <?php elseif ($currentStep === 4): ?>
  <div class="card">
    <h2>🌐 Información del sitio</h2>
    <form method="POST">
      <input type="hidden" name="action" value="save_site">
      <div class="fg-row">
        <div class="fg">
          <label>Nombre del sitio *</label>
          <input type="text" name="site_name" value="<?= esc(sget('site_name')) ?>" required placeholder="Mi Portfolio">
        </div>
        <div class="fg">
          <label>Tagline</label>
          <input type="text" name="site_tagline" value="<?= esc(sget('site_tagline')) ?>" placeholder="Desarrollador Web, IoT, Redes...">
        </div>
      </div>
      <div class="fg">
        <label>Correo de contacto</label>
        <input type="email" name="site_email" value="<?= esc(sget('site_email')) ?>"
               placeholder="contacto@ejemplo.com">
      </div>
      <div class="fg">
        <label>URL del sitio * <small style="color:var(--muted)">(sin / al final)</small></label>
        <?php
          $guessedUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                      . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
          $guessedUrl = explode('/install', $guessedUrl)[0];
        ?>
        <input type="url" name="app_url" value="<?= esc(sget('app_url', $guessedUrl)) ?>" required>
      </div>
      <div class="fg">
        <label>Entorno</label>
        <select name="app_env">
          <option value="production" <?= sget('app_env','production')==='production'?'selected':'' ?>>Producción</option>
          <option value="development" <?= sget('app_env')==='development'?'selected':'' ?>>Desarrollo</option>
        </select>
      </div>
      <div class="btn-row">
        <button type="submit" class="btn btn-primary">Continuar →</button>
      </div>
    </form>
  </div>

  <!-- ════════════════════════════════════════════════════════ -->
  <!-- STEP 5: Datos iniciales + Ejecutar                      -->
  <!-- ════════════════════════════════════════════════════════ -->
  <?php elseif ($currentStep === 5): ?>
  <div class="card">
    <h2>📦 Datos iniciales</h2>
    <p style="color:var(--muted);font-size:.83rem;margin-bottom:1.2rem">
      Elige cómo quieres empezar tu sitio:
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="run_install">
      <div class="radio-cards">
        <label class="radio-card">
          <input type="radio" name="data_mode" value="demo" checked>
          <h3>🎭 Datos demo</h3>
          <p>Secciones, tecnologías de ejemplo, proyectos de muestra y servicios preconfigurados. Ideal para ver el sitio en funcionamiento de inmediato.</p>
        </label>
        <label class="radio-card">
          <input type="radio" name="data_mode" value="empty">
          <h3>🗒 Vacío</h3>
          <p>Solo la estructura básica de secciones sin contenido. Empieza desde cero y agrega tu propio contenido desde el admin.</p>
        </label>
      </div>

      <hr class="section-sep">
      <h3 style="font-size:.88rem;color:var(--muted);margin-bottom:.8rem">Resumen de instalación</h3>
      <div class="info-grid">
        <div class="info-item"><span>Base de datos</span><strong><?= esc(sget('db_name')) ?></strong></div>
        <div class="info-item"><span>Host</span><strong><?= esc(sget('db_host')) ?>:<?= esc(sget('db_port')) ?></strong></div>
        <div class="info-item"><span>Administrador</span><strong><?= esc(sget('admin_user')) ?></strong></div>
        <div class="info-item"><span>URL</span><strong><?= esc(sget('app_url')) ?></strong></div>
        <div class="info-item"><span>Sitio</span><strong><?= esc(sget('site_name')) ?></strong></div>
        <div class="info-item"><span>Entorno</span><strong><?= esc(sget('app_env')) ?></strong></div>
      </div>
      <div class="btn-row">
        <button type="submit" class="btn btn-primary btn-full">⚡ Instalar ahora</button>
      </div>
    </form>
  </div>

  <!-- ════════════════════════════════════════════════════════ -->
  <!-- STEP 6: Completado                                      -->
  <!-- ════════════════════════════════════════════════════════ -->
  <?php elseif ($currentStep === 6): ?>
  <div class="card" style="text-align:center">
    <div class="success-icon">🎉</div>
    <h2 style="justify-content:center;color:var(--green)">¡Instalación completada!</h2>
    <p style="color:var(--muted);margin-top:.5rem;margin-bottom:1.5rem">
      CoDevNexus ha sido instalado correctamente.
    </p>
    <?php if ($lockData): ?>
    <div class="info-grid" style="text-align:left;margin-bottom:1.5rem">
      <div class="info-item"><span>Sitio</span><strong><?= esc($lockData['site_name'] ?? '') ?></strong></div>
      <div class="info-item"><span>Instalado</span><strong><?= esc(date('d/m/Y H:i', strtotime($lockData['installed_at'] ?? 'now'))) ?></strong></div>
    </div>
    <?php endif; ?>
    <div class="btn-row" style="justify-content:center">
      <a href="/" class="btn btn-primary">Ver sitio →</a>
      <a href="/admin" class="btn btn-secondary">Panel admin →</a>
    </div>
    <div class="warning-box" style="text-align:left;margin-top:1.5rem">
      ⚠ <strong>Importante por seguridad:</strong><br>
      Elimina o renombra <code>public/install.php</code> del servidor.<br>
      Para desinstalar o reinstalar usa <code>public/uninstall.php</code> (requiere contraseña).
    </div>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
