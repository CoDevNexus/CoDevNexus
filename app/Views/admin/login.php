<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login · Admin CoDevNexus</title>
  <link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    body { background: #0b0f19; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .login-box { background: #111827; border: 1px solid #1e2d40; border-radius: 12px; padding: 2.5rem; width: 100%; max-width: 400px; box-shadow: 0 0 40px rgba(0,212,255,0.1); }
    .login-box h1 { color: #00d4ff; font-size: 1.6rem; margin: 0 0 0.5rem; text-align: center; }
    .login-box p.sub { color: #64748b; text-align: center; margin-bottom: 2rem; font-size: 0.9rem; }
    .form-group { margin-bottom: 1.2rem; }
    label { display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.4rem; }
    input[type=text], input[type=password] {
      width: 100%; padding: 0.7rem 1rem; background: #0b0f19; border: 1px solid #1e2d40;
      border-radius: 8px; color: #e2e8f0; font-size: 0.95rem; box-sizing: border-box;
      outline: none; transition: border-color .2s;
    }
    input:focus { border-color: #00d4ff; }
    .btn-login {
      width: 100%; padding: 0.8rem; background: linear-gradient(135deg,#00d4ff,#7b2d8b);
      border: none; border-radius: 8px; color: #fff; font-size: 1rem; font-weight: 600;
      cursor: pointer; transition: opacity .2s;
    }
    .btn-login:hover { opacity: 0.9; }
    .alert-error { background: #2d0f0f; border: 1px solid #7f1d1d; color: #fca5a5; padding: 0.8rem 1rem; border-radius: 8px; margin-bottom: 1.2rem; font-size: 0.9rem; }
  </style>
</head>
<body>
  <div class="login-box">
    <h1>⚡ CoDevNexus</h1>
    <p class="sub">Panel de administración</p>

    <?php if (!empty($error)): ?>
      <div class="alert-error">⚠ <?= \Core\Security::escape($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/login">
      <?= \Core\Security::csrfField() ?>
      <div class="form-group">
        <label for="username">Usuario</label>
        <input type="text" id="username" name="username" autocomplete="username" required
               value="<?= \Core\Security::escape($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-login">Ingresar</button>
    </form>
  </div>
</body>
</html>
