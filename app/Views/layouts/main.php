<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?= \Core\Security::escape($title ?? 'CoDevNexus') ?></title>

  <!-- Favicon dinámico (placeholder, JS lo actualiza) -->
  <link rel="icon" id="dynamic-favicon" href="/assets/img/logo.svg" type="image/svg+xml">

  <!-- AOS.js -->
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Remixicon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/3.5.0/remixicon.min.css">
  <!-- Devicons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/devicon.min.css">
  <!-- Font Awesome 6 Free -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/brands.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
  <!-- App CSS -->
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <!-- ── Navbar fijo ──────────────────────────────────────── -->
  <nav id="navbar">
    <a href="/">
      <img id="site-logo" src="/assets/img/logo.svg" alt="CoDevNexus" class="nav-logo">
    </a>
    <button class="nav-toggle" id="nav-toggle" aria-label="Menú">☰</button>
  </nav>

  <!-- ── Mobile / Desktop nav links (outside navbar to avoid backdrop-filter containing block) -->
  <div class="nav-links" id="nav-links">
    <a href="#hero"        class="nav-link">Inicio</a>
    <a href="#sobre"       class="nav-link">Sobre mí</a>
    <a href="#portafolio"  class="nav-link">Portafolio</a>
    <a href="#tecnologias" class="nav-link">Tecnologías</a>
    <a href="#contacto"    class="nav-link">Contacto</a>
  </div>

  <!-- ── SPA Shell ─────────────────────────────────────────── -->
  <div id="app">
    <div id="loading-screen">
      <div class="loader-text">Cargando CoDevNexus<span class="dots">...</span></div>
    </div>
  </div>

  <!-- particles.js -->
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
  <!-- AOS.js -->
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <!-- App JS -->
  <script src="/assets/js/particles-config.js?v=3"></script>
  <script src="/assets/js/app.js?v=13"></script>
  <div id="nav-overlay"></div>
  <script>
    (function() {
      const toggle  = document.getElementById('nav-toggle');
      const links   = document.getElementById('nav-links');
      const overlay = document.getElementById('nav-overlay');

      function openMenu() {
        links.classList.add('open');
        overlay.classList.add('show');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.textContent = '✕';
      }
      function closeMenu() {
        links.classList.remove('open');
        overlay.classList.remove('show');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.textContent = '☰';
      }

      toggle.addEventListener('click', () => {
        links.classList.contains('open') ? closeMenu() : openMenu();
      });

      // Close on any nav link click (delegation — app.js rebuilds links dynamically)
      links.addEventListener('click', e => { if (e.target.closest('a')) closeMenu(); });

      // Close on overlay click
      overlay.addEventListener('click', closeMenu);

      // Close on Escape key
      document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
    })();
  </script>
</body>
</html>
