<?php
// ============================================================
// CoDevNexus — Desinstalador Web
// ADVERTENCIA: Este archivo borra TODOS los datos del sitio.
// Eliminar del servidor después de usarlo.
// ============================================================
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('LOCK_FILE', APP_ROOT . '/storage/installed.lock');
define('CONFIG_FILE', APP_ROOT . '/config/config.php');

// Si no está instalado, no hay nada que desinstalar
if (!file_exists(LOCK_FILE)) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>No instalado</title>
    <style>body{font-family:sans-serif;background:#0b0f19;color:#e2e8f0;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
    .box{background:#111827;padding:2rem 3rem;border-radius:12px;border:1px solid #1e2d40;text-align:center}
    a{color:#00d4ff}h2{color:#ef4444}</style></head><body>
    <div class="box"><h2>⚠️ CoDevNexus no está instalado</h2>
    <p>No existe un archivo de bloqueo de instalación.</p>
    <p><a href="/install.php">Instalar</a></p></div></body></html>');
}

// Cargar config para conectar a la BD
if (!file_exists(CONFIG_FILE)) {
    die('No se encontró config/config.php. La instalación puede estar incompleta.');
}
require_once CONFIG_FILE;

function esc(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$error   = '';
$success = '';
$step    = 'confirm'; // confirm | done

// ── Brute-force protection ────────────────────────────────────
session_start();
$_SESSION['uninstall_tries'] ??= 0;
if ($_SESSION['uninstall_tries'] >= 5) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Bloqueado</title>
    <style>body{font-family:sans-serif;background:#0b0f19;color:#e2e8f0;display:flex;align-items:center;justify-content:center;height:100vh}
    .box{background:#111827;padding:2rem 3rem;border-radius:12px;border:1px solid #7f1d1d;text-align:center}h2{color:#ef4444}</style></head><body>
    <div class="box"><h2>🔒 Acceso bloqueado</h2><p>Demasiados intentos fallidos. Reinicia la sesión del servidor.</p></div></body></html>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'uninstall') {
    $token    = trim($_POST['confirm_token'] ?? '');
    $passIn   = trim($_POST['admin_pass'] ?? '');
    $delConf  = isset($_POST['delete_config']);
    $delSelf  = isset($_POST['delete_self']);

    if ($token !== 'DESINSTALAR') {
        $_SESSION['uninstall_tries']++;
        $error = 'Debes escribir exactamente DESINSTALAR para confirmar.';
    } elseif (empty($passIn)) {
        $error = 'La contraseña de administrador es requerida.';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Verificar contraseña admin
            $stmt = $pdo->prepare("SELECT password FROM admin_users LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($passIn, $row['password'])) {
                $_SESSION['uninstall_tries']++;
                $error = 'Contraseña de administrador incorrecta.';
            } else {
                // ── Drop all tables ──────────────────────────────
                $tables = [
                    'login_attempts', 'mensajes', 'portafolio',
                    'tecnologias', 'secciones', 'configuracion', 'admin_users'
                ];
                foreach ($tables as $t) {
                    $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
                }

                // ── Eliminar lock ────────────────────────────────
                if (file_exists(LOCK_FILE)) @unlink(LOCK_FILE);

                // ── Eliminar config.php ──────────────────────────
                if ($delConf && file_exists(CONFIG_FILE)) {
                    @unlink(CONFIG_FILE);
                }

                // ── Limpiar storage/cache ────────────────────────
                $cacheDir = APP_ROOT . '/storage/cache';
                if (is_dir($cacheDir)) {
                    foreach (glob($cacheDir . '/*') as $f) @unlink($f);
                }

                $_SESSION['uninstall_tries'] = 0;
                $step = 'done';

                // ── Auto-delete this file ────────────────────────
                if ($delSelf) {
                    register_shutdown_function(function() {
                        @unlink(__FILE__);
                    });
                }
            }
        } catch (Throwable $e) {
            $error = 'Error al desinstalar: ' . $e->getMessage();
        }
    }
}

$lockData  = file_exists(LOCK_FILE) ? json_decode(@file_get_contents(LOCK_FILE), true) : null;
$installed = $lockData['installed_at'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Desinstalador · CoDevNexus</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg: #0b0f19; --bg2: #111827; --bg3: #0f172a;
  --cyan: #00d4ff; --border: #1e2d40;
  --text: #e2e8f0; --muted: #94a3b8;
  --green: #10b981; --red: #ef4444; --yellow: #f59e0b;
}
body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif;
       min-height: 100vh; display: flex; align-items: flex-start; justify-content: center;
       padding: 2.5rem 1rem; }
.container { width: 100%; max-width: 560px; }
.logo { text-align: center; margin-bottom: 2rem; }
.logo h1 { font-size: 1.6rem; font-weight: 800; }
.logo h1 span { color: var(--cyan); }
.badge { display: inline-block; background: rgba(239,68,68,.15); color: var(--red);
         border: 1px solid rgba(239,68,68,.35); border-radius: 20px;
         font-size: .75rem; font-weight: 600; padding: .15rem .7rem; margin-top:.3rem; }
.card { background: var(--bg2); border: 1px solid var(--border); border-radius: 14px; padding: 2rem; }
.danger-header { display: flex; align-items: center; gap: .6rem; margin-bottom: 1.3rem; }
.danger-header h2 { font-size: 1.15rem; color: var(--red); }
.danger-header span { font-size: 1.5rem; }
.warn-block { background: rgba(239,68,68,.07); border: 1px solid rgba(239,68,68,.2);
              border-radius: 8px; padding: .9rem 1rem; font-size: .84rem;
              color: #fca5a5; margin-bottom: 1.3rem; line-height: 1.7; }
.warn-block strong { color: var(--red); }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-bottom: 1.3rem; }
.info-item { background: var(--bg3); border-radius: 7px; padding: .55rem .85rem; font-size: .81rem; }
.info-item span { display: block; color: var(--muted); font-size: .73rem; margin-bottom: .15rem; }
.fg { margin-bottom: 1rem; }
.fg label { display: block; font-size: .82rem; color: var(--muted); margin-bottom: .35rem; }
.fg input[type=text], .fg input[type=password] {
  width: 100%; padding: .65rem .85rem;
  background: var(--bg3); border: 1px solid var(--border);
  border-radius: 8px; color: var(--text); font-size: .93rem;
  transition: border-color .2s;
}
.fg input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(239,68,68,.12); }
.check-row { display: flex; align-items: center; gap: .5rem; font-size: .84rem;
             color: var(--muted); margin-bottom: .6rem; cursor: pointer; }
.check-row input { width: 16px; height: 16px; accent-color: var(--red); }
.btn { display: inline-flex; align-items: center; gap: .4rem; padding: .7rem 1.5rem;
       border: none; border-radius: 8px; font-size: .93rem; font-weight: 600;
       cursor: pointer; transition: opacity .2s; }
.btn:hover { opacity: .87; }
.btn-danger { background: var(--red); color: #fff; width: 100%; justify-content: center; }
.btn-secondary { background: var(--bg3); border: 1px solid var(--border); color: var(--text); }
.alert { border-radius: 8px; padding: .8rem 1rem; margin-bottom: 1rem; font-size: .88rem; }
.alert-error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
.alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
code { background: rgba(0,212,255,.1); color: var(--cyan); padding: .1rem .4rem;
       border-radius: 4px; font-size: .85em; }
.center { text-align: center; }
.success-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.note { background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.3);
        border-radius: 8px; padding: .8rem 1rem; font-size: .82rem;
        color: #fcd34d; margin-top: 1rem; line-height: 1.6; }
#confirm-input { font-family: monospace; letter-spacing: .08em; }
#confirm-input::placeholder { letter-spacing: 0; }
</style>
</head>
<body>
<div class="container">

  <div class="logo">
    <h1>CoDev<span>Nexus</span></h1>
    <div class="badge">⚠ DESINSTALADOR</div>
  </div>

  <?php if (!empty($error)): ?>
  <div class="alert alert-error">⚠ <?= esc($error) ?></div>
  <?php endif; ?>

  <!-- ════════════════════════════════════════ -->
  <!-- Paso: confirmación                       -->
  <!-- ════════════════════════════════════════ -->
  <?php if ($step === 'confirm'): ?>
  <div class="card">
    <div class="danger-header">
      <span>🗑</span>
      <h2>Desinstalar CoDevNexus</h2>
    </div>

    <div class="warn-block">
      <strong>⚠ Esta acción es irreversible.</strong><br>
      Se eliminarán <strong>todas las tablas</strong> de la base de datos:<br>
      admin_users, secciones, tecnologias, portafolio, mensajes, configuracion, login_attempts.
    </div>

    <?php if ($lockData): ?>
    <div class="info-grid">
      <div class="info-item">
        <span>Sitio instalado</span>
        <strong><?= esc($lockData['site_name'] ?? '—') ?></strong>
      </div>
      <div class="info-item">
        <span>Fecha de instalación</span>
        <strong><?= $installed ? esc(date('d/m/Y H:i', strtotime($installed))) : '—' ?></strong>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST" id="uninstall-form">
      <input type="hidden" name="action" value="uninstall">

      <div class="fg">
        <label>Escribe <strong style="color:var(--red)">DESINSTALAR</strong> para confirmar *</label>
        <input type="text" id="confirm-input" name="confirm_token"
               placeholder="DESINSTALAR" autocomplete="off" required>
      </div>

      <div class="fg">
        <label>Contraseña del administrador *</label>
        <input type="password" name="admin_pass" required
               placeholder="Contraseña del panel admin">
      </div>

      <div style="margin-bottom:1.2rem">
        <label class="check-row">
          <input type="checkbox" name="delete_config" value="1" checked>
          Eliminar también <code>config/config.php</code>
        </label>
        <label class="check-row">
          <input type="checkbox" name="delete_self" value="1" checked>
          Auto-eliminar este archivo <code>uninstall.php</code> después de desinstalar
        </label>
      </div>

      <button type="submit" class="btn btn-danger" id="submit-btn" disabled>
        🗑 Desinstalar todo
      </button>

      <div style="margin-top:1rem;text-align:center">
        <a href="/" style="color:var(--muted);font-size:.82rem;text-decoration:none">
          ← Cancelar y volver al sitio
        </a>
      </div>
    </form>
  </div>

  <!-- ════════════════════════════════════════ -->
  <!-- Paso: completado                         -->
  <!-- ════════════════════════════════════════ -->
  <?php elseif ($step === 'done'): ?>
  <div class="card center">
    <div class="success-icon">✅</div>
    <h2 style="color:var(--green);justify-content:center;margin-bottom:.6rem">
      Desinstalación completada
    </h2>
    <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.5rem">
      Todas las tablas han sido eliminadas y el archivo de instalación
      ha sido removido.
    </p>
    <a href="/install.php" class="btn"
       style="background:linear-gradient(135deg,var(--cyan),#7b2d8b);color:#0b0f19;
              display:inline-flex;justify-content:center;padding:.7rem 2rem">
      ↩ Reinstalar
    </a>
    <div class="note">
      <?php if (!file_exists(CONFIG_FILE)): ?>
        El archivo <code>config/config.php</code> fue eliminado.<br>
      <?php else: ?>
        El archivo <code>config/config.php</code> se conservó.<br>
      <?php endif; ?>
      <?php if (!file_exists(__FILE__)): ?>
        Este archivo de desinstalación ha sido eliminado del servidor.
      <?php else: ?>
        <strong>Recuerda eliminar</strong> este archivo del servidor: <code>public/uninstall.php</code>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
// Habilitar botón solo cuando el input coincide exactamente
const input = document.getElementById('confirm-input');
const btn   = document.getElementById('submit-btn');
if (input && btn) {
  input.addEventListener('input', () => {
    btn.disabled = input.value !== 'DESINSTALAR';
  });
}
// Confirmación extra antes de enviar
const form = document.getElementById('uninstall-form');
if (form) {
  form.addEventListener('submit', e => {
    if (!confirm('¿Estás absolutamente seguro? Esta acción eliminará TODOS los datos y no se puede deshacer.')) {
      e.preventDefault();
    }
  });
}
</script>
</body>
</html>
