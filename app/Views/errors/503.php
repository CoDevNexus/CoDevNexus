<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mantenimiento — CoDevNexus</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#0b0f19; color:#e2e8f0; font-family:'Segoe UI',system-ui,sans-serif;
           display:flex; align-items:center; justify-content:center; min-height:100vh; text-align:center; }
    h1 { font-size:3rem; color:#00d4ff; text-shadow:0 0 20px rgba(0,212,255,.4); margin-bottom:1rem; }
    p  { color:#64748b; font-size:1.1rem; max-width:500px; }
    .icon { font-size:4rem; margin-bottom:1.5rem; display:block; }
  </style>
</head>
<body>
  <div>
    <span class="icon">🚧</span>
    <h1>Mantenimiento</h1>
    <p><?= isset($message) ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : 'Sitio en mantenimiento. Volvemos pronto.' ?></p>
  </div>
</body>
</html>
