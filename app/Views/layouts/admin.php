<?php
use App\Models\ConfiguracionModel;
use App\Models\MensajeModel;
use App\Models\SeccionModel;
use Core\Security;

$cfg         = new ConfiguracionModel();
$siteName    = $cfg->get('site_name', 'CoDevNexus');
$logoAdmin   = $cfg->get('logo_admin', '/assets/img/logo.svg');
$mensajesNew = (new MensajeModel())->getUnread();
$currentUri  = $_SERVER['REQUEST_URI'] ?? '';
$allSections = (new SeccionModel())->getAll();   // para el sub-menú dinámico

function isActive(string $path, string $current): string {
    return str_starts_with($current, $path) ? 'active' : '';
}

// Icono Remix por tipo de sección
function sectionIcon(string $tipo): string {
    $map = [
        'hero'        => 'ri-home-4-line',
        'sobre'       => 'ri-user-3-line',
        'portafolio'  => 'ri-image-2-line',
        'tecnologias' => 'ri-cpu-line',
        'servicios'   => 'ri-settings-3-line',
        'contacto'    => 'ri-mail-open-line',
    ];
    return $map[$tipo] ?? 'ri-file-text-line';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars(\Core\Security::generateCsrfToken(), ENT_QUOTES) ?>">
  <title><?= Security::escape($title ?? 'Admin · CoDevNexus') ?></title>
  <link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
  <!-- Remixicon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/3.5.0/remixicon.min.css">
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5/dark.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Quill.js -->
  <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
  <script>
  const QUILL_TOOLBAR = [
    [{ header: [1, 2, 3, 4, false] }],
    [{ size: ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ align: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
    ['blockquote', 'code-block'],
    ['link', 'image', 'video'],
    ['clean']
  ];
  function registerImageHandler(quillInstance) {
    quillInstance.getModule('toolbar').addHandler('image', () => {
      openMediaModal(quillInstance);
    });
  }
  </script>
  <!-- Devicons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/devicon.min.css">
  <!-- Font Awesome 6 Free -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/brands.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
  <!-- Admin CSS -->
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
  /* ── Toggle switch (shared across all admin views) ── */
  .toggle-switch{position:relative;display:inline-block;width:44px;height:24px;cursor:pointer}
  .toggle-switch input{opacity:0;width:0;height:0}
  .toggle-slider{position:absolute;inset:0;border-radius:24px;background:#1e2d40;border:1px solid #334155;transition:background .2s,border-color .2s}
  .toggle-slider::before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;background:#64748b;border-radius:50%;transition:transform .2s,background .2s}
  .toggle-switch input:checked+.toggle-slider{background:rgba(0,212,255,.15);border-color:rgba(0,212,255,.5)}
  .toggle-switch input:checked+.toggle-slider::before{transform:translateX(20px);background:#00d4ff}
  .toggle-switch input:disabled+.toggle-slider{opacity:.5;cursor:not-allowed}
  </style>
  <script>
  /* ── Shared Icon Picker data (must be in <head> — used by inline IIFEs in partials) ── */
  const IP_DATA = {
    devicon: {
      label: 'Devicons',
      groups: [
        { g: 'Lenguajes', i: [
          'devicon-php-plain','devicon-javascript-plain','devicon-typescript-plain',
          'devicon-python-plain','devicon-java-plain','devicon-csharp-plain',
          'devicon-c-plain','devicon-cplusplus-plain','devicon-go-plain',
          'devicon-rust-plain','devicon-ruby-plain','devicon-swift-plain',
          'devicon-kotlin-plain','devicon-dart-plain','devicon-scala-plain',
          'devicon-r-plain','devicon-haskell-plain','devicon-elixir-plain',
          'devicon-lua-original','devicon-perl-plain','devicon-groovy-plain',
          'devicon-objectivec-plain','devicon-dot-net-plain','devicon-coffeescript-original',
          'devicon-clojure-line','devicon-solidity-plain','devicon-matlab-plain',
          'devicon-fortran-original','devicon-erlang-plain','devicon-crystal-original',
          'devicon-zig-original',
        ]},
        { g: 'Frontend', i: [
          'devicon-html5-plain','devicon-css3-plain','devicon-sass-original',
          'devicon-tailwindcss-plain','devicon-bootstrap-plain',
          'devicon-react-original','devicon-vuejs-plain','devicon-angularjs-plain',
          'devicon-svelte-plain','devicon-nextjs-original','devicon-nuxtjs-plain',
          'devicon-jquery-plain','devicon-redux-original','devicon-graphql-plain',
          'devicon-gatsby-original','devicon-remix-original','devicon-storybook-plain',
          'devicon-webpack-plain','devicon-vite-original','devicon-astro-plain',
        ]},
        { g: 'Backend', i: [
          'devicon-nodejs-plain','devicon-express-original','devicon-django-plain',
          'devicon-laravel-plain','devicon-rails-plain','devicon-spring-original',
          'devicon-flask-original','devicon-nestjs-plain','devicon-symfony-original',
          'devicon-codeigniter-plain','devicon-dotnetcore-plain','devicon-fastapi-original',
          'devicon-fastify-original',
        ]},
        { g: 'Bases de datos', i: [
          'devicon-mysql-plain','devicon-postgresql-plain','devicon-mongodb-original',
          'devicon-redis-plain','devicon-sqlite-plain','devicon-mariadb-plain',
          'devicon-oracle-original','devicon-elasticsearch-plain','devicon-neo4j-original',
          'devicon-cassandra-plain','devicon-microsoftsqlserver-plain','devicon-firebase-plain',
          'devicon-supabase-plain','devicon-dynamodb-original','devicon-couchdb-plain',
          'devicon-influxdb-plain',
        ]},
        { g: 'DevOps / Cloud', i: [
          'devicon-docker-plain','devicon-kubernetes-plain','devicon-git-plain',
          'devicon-github-original','devicon-gitlab-plain','devicon-bitbucket-original',
          'devicon-nginx-original','devicon-apache-plain','devicon-linux-plain',
          'devicon-ubuntu-plain','devicon-debian-plain','devicon-centos-plain',
          'devicon-fedora-plain','devicon-amazonwebservices-plain','devicon-azure-plain',
          'devicon-googlecloud-plain','devicon-heroku-plain','devicon-terraform-plain',
          'devicon-ansible-plain','devicon-jenkins-line','devicon-vagrant-plain',
          'devicon-grafana-original','devicon-prometheus-original','devicon-circleci-plain',
          'devicon-vercel-original','devicon-netlify-plain',
        ]},
        { g: 'Herramientas / IDEs', i: [
          'devicon-vscode-plain','devicon-vim-plain','devicon-neovim-plain',
          'devicon-intellij-plain','devicon-pycharm-plain','devicon-webstorm-plain',
          'devicon-phpstorm-plain','devicon-figma-plain','devicon-xd-plain',
          'devicon-illustrator-plain','devicon-photoshop-plain','devicon-slack-plain',
          'devicon-trello-plain','devicon-jira-plain','devicon-confluence-original',
          'devicon-arduino-plain','devicon-raspberrypi-line','devicon-android-plain',
          'devicon-apple-original','devicon-flutter-plain','devicon-electron-original',
          'devicon-ionic-original','devicon-npm-original-wordmark','devicon-yarn-plain',
          'devicon-jest-plain','devicon-pytest-plain','devicon-vitest-plain',
          'devicon-postman-plain','devicon-insomnia-plain','devicon-cloudflare-plain',
        ]},
      ]
    },
    ri: {
      label: 'Remix Icons',
      groups: [
        { g: 'Código / Dev', i: [
          'ri-code-line','ri-code-box-line','ri-code-s-slash-line','ri-braces-line',
          'ri-terminal-box-line','ri-terminal-line','ri-file-code-line',
          'ri-git-repository-line','ri-github-line','ri-gitlab-line','ri-bug-line',
          'ri-stack-line','ri-node-tree','ri-puzzle-line',
        ]},
        { g: 'Infraestructura', i: [
          'ri-database-line','ri-database-2-line','ri-server-line','ri-cloud-line',
          'ri-cloud-fill','ri-hard-drive-line','ri-cpu-line','ri-cpu-fill',
          'ri-router-line','ri-wifi-line','ri-global-line','ri-network-line',
        ]},
        { g: 'Dispositivos', i: [
          'ri-computer-line','ri-laptop-line','ri-macbook-line','ri-tablet-line',
          'ri-smartphone-line','ri-window-line','ri-layout-line','ri-apps-line',
        ]},
        { g: 'Seguridad', i: [
          'ri-lock-line','ri-lock-fill','ri-lock-unlock-line','ri-key-line',
          'ri-shield-line','ri-shield-fill','ri-shield-check-line','ri-secure-payment-line',
        ]},
        { g: 'IA / Data', i: [
          'ri-robot-line','ri-robot-fill','ri-brain-line','ri-ai-generate',
          'ri-bar-chart-line','ri-pie-chart-line','ri-line-chart-line',
          'ri-dashboard-line','ri-speed-line','ri-search-eye-line',
        ]},
        { g: 'Perfil / Personal', i: [
          'ri-user-3-line','ri-user-smile-line','ri-account-circle-line',
          'ri-briefcase-line','ri-award-line','ri-medal-line','ri-trophy-line',
          'ri-map-pin-line','ri-home-4-line','ri-team-line','ri-group-line',
        ]},
        { g: 'General', i: [
          'ri-tools-line','ri-settings-line','ri-settings-3-line','ri-magic-line',
          'ri-lightbulb-line','ri-fire-line','ri-star-line','ri-heart-line',
          'ri-rocket-launch-line','ri-mail-line','ri-notification-line',
          'ri-bookmark-line','ri-folder-line','ri-image-line','ri-video-line',
          'ri-linkedin-box-line','ri-github-line','ri-brush-line','ri-pen-nib-line',
        ]},
      ]
    },
    fab: {
      label: 'FA Brands',
      groups: [
        { g: 'Lenguajes', i: [
          'fa-brands fa-html5','fa-brands fa-css3-alt','fa-brands fa-js',
          'fa-brands fa-php','fa-brands fa-python','fa-brands fa-java',
          'fa-brands fa-swift','fa-brands fa-rust','fa-brands fa-golang',
        ]},
        { g: 'Frameworks / Libs', i: [
          'fa-brands fa-react','fa-brands fa-vuejs','fa-brands fa-angular',
          'fa-brands fa-node-js','fa-brands fa-npm','fa-brands fa-sass',
          'fa-brands fa-less','fa-brands fa-bootstrap',
        ]},
        { g: 'DevOps / Cloud', i: [
          'fa-brands fa-docker','fa-brands fa-git-alt','fa-brands fa-github',
          'fa-brands fa-gitlab','fa-brands fa-bitbucket','fa-brands fa-aws',
          'fa-brands fa-microsoft','fa-brands fa-google','fa-brands fa-digital-ocean',
          'fa-brands fa-cloudflare',
        ]},
        { g: 'SO / Plataformas', i: [
          'fa-brands fa-linux','fa-brands fa-ubuntu','fa-brands fa-centos',
          'fa-brands fa-fedora','fa-brands fa-debian','fa-brands fa-redhat',
          'fa-brands fa-apple','fa-brands fa-android','fa-brands fa-windows',
          'fa-brands fa-raspberry-pi',
        ]},
        { g: 'Herramientas / Apps', i: [
          'fa-brands fa-figma','fa-brands fa-sketch','fa-brands fa-adobe',
          'fa-brands fa-chrome','fa-brands fa-firefox','fa-brands fa-edge',
          'fa-brands fa-safari','fa-brands fa-wordpress','fa-brands fa-shopify',
          'fa-brands fa-stripe','fa-brands fa-paypal',
        ]},
        { g: 'Redes / Comunidad', i: [
          'fa-brands fa-slack','fa-brands fa-discord','fa-brands fa-telegram',
          'fa-brands fa-x-twitter','fa-brands fa-linkedin','fa-brands fa-stackoverflow',
          'fa-brands fa-dev','fa-brands fa-codepen','fa-brands fa-youtube','fa-brands fa-twitch',
        ]},
      ]
    },
    fas: {
      label: 'FA Solid',
      groups: [
        { g: 'Código / Dev', i: [
          'fa-solid fa-code','fa-solid fa-terminal','fa-solid fa-laptop-code',
          'fa-solid fa-file-code','fa-solid fa-braces','fa-solid fa-bug',
          'fa-solid fa-cube','fa-solid fa-cubes','fa-solid fa-layer-group',
          'fa-solid fa-sitemap','fa-solid fa-diagram-project',
        ]},
        { g: 'Infraestructura', i: [
          'fa-solid fa-database','fa-solid fa-server','fa-solid fa-cloud',
          'fa-solid fa-hard-drive','fa-solid fa-microchip','fa-solid fa-network-wired',
          'fa-solid fa-wifi','fa-solid fa-globe','fa-solid fa-plug','fa-solid fa-memory',
        ]},
        { g: 'Herramientas', i: [
          'fa-solid fa-wrench','fa-solid fa-screwdriver-wrench','fa-solid fa-gear',
          'fa-solid fa-gears','fa-solid fa-puzzle-piece','fa-solid fa-wand-magic-sparkles',
          'fa-solid fa-toolbox','fa-solid fa-pen-nib','fa-solid fa-pencil',
        ]},
        { g: 'Data / IA', i: [
          'fa-solid fa-chart-line','fa-solid fa-chart-bar','fa-solid fa-chart-pie',
          'fa-solid fa-magnifying-glass','fa-solid fa-magnifying-glass-chart',
          'fa-solid fa-robot','fa-solid fa-brain','fa-solid fa-infinity',
          'fa-solid fa-atom','fa-solid fa-flask',
        ]},
        { g: 'General', i: [
          'fa-solid fa-lightbulb','fa-solid fa-fire','fa-solid fa-bolt',
          'fa-solid fa-star','fa-solid fa-palette','fa-solid fa-lock',
          'fa-solid fa-shield-halved','fa-solid fa-key','fa-solid fa-user-tie',
          'fa-solid fa-users','fa-solid fa-envelope','fa-solid fa-bell',
          'fa-solid fa-folder-open','fa-solid fa-hand-pointer','fa-solid fa-circle-check',
          'fa-solid fa-rocket','fa-solid fa-award','fa-solid fa-briefcase',
          'fa-solid fa-map-pin','fa-solid fa-home',
        ]},
      ]
    }
  };
  function ipIconName(cls) {
    return cls
      .replace(/^devicon-/, '').replace(/-(plain|original|line|fill)(-(wordmark|colored))?$/, '')
      .replace(/^ri-/, '').replace(/^fa-brands\s+fa-/, '').replace(/^fa-solid\s+fa-/, '')
      .replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }
  </script>
</head>
<body class="admin-layout">

<div class="admin-wrapper">
  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img src="<?= Security::escape($logoAdmin) ?>" alt="<?= Security::escape($siteName) ?>" class="sidebar-logo">
      <span><?= Security::escape($siteName) ?></span>
    </div>

    <nav class="sidebar-nav">

      <!-- Dashboard -->
      <a href="/admin" class="nav-item <?= isActive('/admin/dashboard', $currentUri) ?: (rtrim($currentUri,'/')==='/admin'?'active':'') ?>">
        <i class="ri-dashboard-3-line nav-icon"></i> Dashboard
      </a>

      <!-- Secciones (grupo colapsable) -->
      <?php
        $seccionesActive = isActive('/admin/secciones', $currentUri)
                        || isActive('/admin/servicios',   $currentUri)
                        || isActive('/admin/portafolio',  $currentUri)
                        || isActive('/admin/tecnologias', $currentUri);
      ?>
      <div class="nav-group <?= $seccionesActive ? 'open' : '' ?>">
        <button class="nav-group-header" type="button" onclick="toggleNavGroup(this)">
          <i class="ri-layout-row-line nav-icon"></i>
          <span>Secciones</span>
          <i class="ri-arrow-right-s-line nav-arrow"></i>
        </button>
        <div class="nav-subitems">
          <!-- Crear nueva sección -->
          <a href="/admin/secciones" class="nav-subitem <?= (rtrim($currentUri,'/') === '/admin/secciones' ? 'active' : '') ?>">
            <i class="ri-list-check nav-icon"></i> Ver todas
          </a>
          <a href="/admin/secciones/create" class="nav-subitem <?= (str_contains($currentUri,'/secciones/create') ? 'active' : '') ?>">
            <i class="ri-add-circle-line nav-icon"></i> Nueva sección
          </a>
          <?php if (!empty($allSections)): ?>
            <div class="nav-subitems-divider"></div>
            <?php foreach ($allSections as $sec):
                $tipo    = $sec['tipo_seccion'] ?? '';
                $managed = ['servicios' => '/admin/servicios',
                            'portafolio' => '/admin/portafolio',
                            'tecnologias' => '/admin/tecnologias'];
                if (isset($managed[$tipo])) {
                    $secHref   = $managed[$tipo];
                    $secActive = isActive($managed[$tipo], $currentUri);
                } else {
                    $secHref   = '/admin/secciones/edit/'.(int)$sec['id'];
                    $secActive = str_contains($currentUri, '/secciones/edit/'.(int)$sec['id']) ? 'active' : '';
                }
            ?>
            <a href="<?= $secHref ?>"
               class="nav-subitem <?= $secActive ?>">
              <i class="<?= sectionIcon($sec['tipo_seccion'] ?? '') ?> nav-icon"></i>
              <?= Security::escape($sec['titulo'] ?? $sec['tipo_seccion']) ?>
              <?php if (!$sec['visible']): ?>
                <span class="nav-badge-off" title="Oculta">—</span>
              <?php endif; ?>
            </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Mensajes -->
      <a href="/admin/mensajes" class="nav-item <?= isActive('/admin/mensajes', $currentUri) ?>">
        <i class="ri-mail-line nav-icon"></i> Mensajes
        <?php if ($mensajesNew > 0): ?>
          <span class="badge"><?= $mensajesNew ?></span>
        <?php endif; ?>
      </a>

      <!-- Configuración -->
      <a href="/admin/configuracion" class="nav-item <?= isActive('/admin/configuracion', $currentUri) ?>">
        <i class="ri-equalizer-2-line nav-icon"></i> Configuración
      </a>

    </nav>

    <div class="sidebar-footer">
      <a href="/admin/logout" class="nav-item nav-logout">
        <i class="ri-logout-box-line nav-icon"></i> Cerrar sesión
      </a>
    </div>
  </aside>

  <!-- ── Main Content ─────────────────────────────────────── -->
  <main class="admin-main">
    <header class="admin-header">
      <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
        <i class="ri-menu-line"></i>
      </button>
      <span class="admin-header-title"><?= Security::escape($title ?? 'Admin') ?></span>
      <span class="admin-user">
        <i class="ri-user-line"></i> <?= Security::escape($_SESSION['admin_username'] ?? 'admin') ?>
      </span>
    </header>

    <div class="admin-content">
      <?= $content ?>
    </div>
  </main>
</div>

<script>
/* ── Nav / UI helpers ─────────────────────────────── */
/* IP_DATA + ipIconName are defined in <head> */
/* (IP_DATA + ipIconName are in <head>) */
const _ip_placeholder = {
  devicon: {
    label: 'Devicons',
    groups: [
      { g: 'Lenguajes', i: [
        'devicon-php-plain','devicon-javascript-plain','devicon-typescript-plain',
        'devicon-python-plain','devicon-java-plain','devicon-csharp-plain',
        'devicon-c-plain','devicon-cplusplus-plain','devicon-go-plain',
        'devicon-rust-plain','devicon-ruby-plain','devicon-swift-plain',
        'devicon-kotlin-plain','devicon-dart-plain','devicon-scala-plain',
        'devicon-r-plain','devicon-haskell-plain','devicon-elixir-plain',
        'devicon-lua-original','devicon-perl-plain','devicon-groovy-plain',
        'devicon-objectivec-plain','devicon-dot-net-plain','devicon-coffeescript-original',
        'devicon-clojure-line','devicon-solidity-plain','devicon-matlab-plain',
        'devicon-fortran-original','devicon-erlang-plain','devicon-crystal-original',
        'devicon-zig-original',
      ]},
      { g: 'Frontend', i: [
        'devicon-html5-plain','devicon-css3-plain','devicon-sass-original',
        'devicon-tailwindcss-plain','devicon-bootstrap-plain',
        'devicon-react-original','devicon-vuejs-plain','devicon-angularjs-plain',
        'devicon-svelte-plain','devicon-nextjs-original','devicon-nuxtjs-plain',
        'devicon-jquery-plain','devicon-redux-original','devicon-graphql-plain',
        'devicon-gatsby-original','devicon-remix-original','devicon-storybook-plain',
        'devicon-webpack-plain','devicon-vite-original','devicon-astro-plain',
      ]},
      { g: 'Backend', i: [
        'devicon-nodejs-plain','devicon-express-original','devicon-django-plain',
        'devicon-laravel-plain','devicon-rails-plain','devicon-spring-original',
        'devicon-flask-original','devicon-nestjs-plain','devicon-symfony-original',
        'devicon-codeigniter-plain','devicon-dotnetcore-plain','devicon-fastapi-original',
        'devicon-fastify-original',
      ]},
      { g: 'Bases de datos', i: [
        'devicon-mysql-plain','devicon-postgresql-plain','devicon-mongodb-original',
        'devicon-redis-plain','devicon-sqlite-plain','devicon-mariadb-plain',
        'devicon-oracle-original','devicon-elasticsearch-plain','devicon-neo4j-original',
        'devicon-cassandra-plain','devicon-microsoftsqlserver-plain','devicon-firebase-plain',
        'devicon-supabase-plain','devicon-dynamodb-original','devicon-couchdb-plain',
        'devicon-influxdb-plain',
      ]},
      { g: 'DevOps / Cloud', i: [
        'devicon-docker-plain','devicon-kubernetes-plain','devicon-git-plain',
        'devicon-github-original','devicon-gitlab-plain','devicon-bitbucket-original',
        'devicon-nginx-original','devicon-apache-plain','devicon-linux-plain',
        'devicon-ubuntu-plain','devicon-debian-plain','devicon-centos-plain',
        'devicon-fedora-plain','devicon-amazonwebservices-plain','devicon-azure-plain',
        'devicon-googlecloud-plain','devicon-heroku-plain','devicon-terraform-plain',
        'devicon-ansible-plain','devicon-jenkins-line','devicon-vagrant-plain',
        'devicon-grafana-original','devicon-prometheus-original','devicon-circleci-plain',
        'devicon-vercel-original','devicon-netlify-plain',
      ]},
      { g: 'Herramientas / IDEs', i: [
        'devicon-vscode-plain','devicon-vim-plain','devicon-neovim-plain',
        'devicon-intellij-plain','devicon-pycharm-plain','devicon-webstorm-plain',
        'devicon-phpstorm-plain','devicon-figma-plain','devicon-xd-plain',
        'devicon-illustrator-plain','devicon-photoshop-plain','devicon-slack-plain',
        'devicon-trello-plain','devicon-jira-plain','devicon-confluence-original',
        'devicon-arduino-plain','devicon-raspberrypi-line','devicon-android-plain',
        'devicon-apple-original','devicon-flutter-plain','devicon-electron-original',
        'devicon-ionic-original','devicon-npm-original-wordmark','devicon-yarn-plain',
        'devicon-jest-plain','devicon-pytest-plain','devicon-vitest-plain',
        'devicon-postman-plain','devicon-insomnia-plain','devicon-cloudflare-plain',
      ]},
    ]
  },
  ri: {
    label: 'Remix Icons',
    groups: [
      { g: 'Código / Dev', i: [
        'ri-code-line','ri-code-box-line','ri-code-s-slash-line','ri-braces-line',
        'ri-terminal-box-line','ri-terminal-line','ri-file-code-line',
        'ri-git-repository-line','ri-github-line','ri-gitlab-line','ri-bug-line',
        'ri-stack-line','ri-node-tree','ri-puzzle-line',
      ]},
      { g: 'Infraestructura', i: [
        'ri-database-line','ri-database-2-line','ri-server-line','ri-cloud-line',
        'ri-cloud-fill','ri-hard-drive-line','ri-cpu-line','ri-cpu-fill',
        'ri-router-line','ri-wifi-line','ri-global-line','ri-network-line',
      ]},
      { g: 'Dispositivos', i: [
        'ri-computer-line','ri-laptop-line','ri-macbook-line','ri-tablet-line',
        'ri-smartphone-line','ri-window-line','ri-layout-line','ri-apps-line',
      ]},
      { g: 'Seguridad', i: [
        'ri-lock-line','ri-lock-fill','ri-lock-unlock-line','ri-key-line',
        'ri-shield-line','ri-shield-fill','ri-shield-check-line','ri-secure-payment-line',
      ]},
      { g: 'IA / Data', i: [
        'ri-robot-line','ri-robot-fill','ri-brain-line','ri-ai-generate',
        'ri-bar-chart-line','ri-pie-chart-line','ri-line-chart-line',
        'ri-dashboard-line','ri-speed-line','ri-search-eye-line',
      ]},
      { g: 'Perfil / Personal', i: [
        'ri-user-3-line','ri-user-smile-line','ri-account-circle-line',
        'ri-briefcase-line','ri-award-line','ri-medal-line','ri-trophy-line',
        'ri-map-pin-line','ri-home-4-line','ri-team-line','ri-group-line',
      ]},
      { g: 'General', i: [
        'ri-tools-line','ri-settings-line','ri-settings-3-line','ri-magic-line',
        'ri-lightbulb-line','ri-fire-line','ri-star-line','ri-heart-line',
        'ri-rocket-launch-line','ri-mail-line','ri-notification-line',
        'ri-bookmark-line','ri-folder-line','ri-image-line','ri-video-line',
        'ri-linkedin-box-line','ri-github-line','ri-brush-line','ri-pen-nib-line',
      ]},
    ]
  },
  fab: {
    label: 'FA Brands',
    groups: [
      { g: 'Lenguajes', i: [
        'fa-brands fa-html5','fa-brands fa-css3-alt','fa-brands fa-js',
        'fa-brands fa-php','fa-brands fa-python','fa-brands fa-java',
        'fa-brands fa-swift','fa-brands fa-rust','fa-brands fa-golang',
      ]},
      { g: 'Frameworks / Libs', i: [
        'fa-brands fa-react','fa-brands fa-vuejs','fa-brands fa-angular',
        'fa-brands fa-node-js','fa-brands fa-npm','fa-brands fa-sass',
        'fa-brands fa-less','fa-brands fa-bootstrap',
      ]},
      { g: 'DevOps / Cloud', i: [
        'fa-brands fa-docker','fa-brands fa-git-alt','fa-brands fa-github',
        'fa-brands fa-gitlab','fa-brands fa-bitbucket','fa-brands fa-aws',
        'fa-brands fa-microsoft','fa-brands fa-google','fa-brands fa-digital-ocean',
        'fa-brands fa-cloudflare',
      ]},
      { g: 'SO / Plataformas', i: [
        'fa-brands fa-linux','fa-brands fa-ubuntu','fa-brands fa-centos',
        'fa-brands fa-fedora','fa-brands fa-debian','fa-brands fa-redhat',
        'fa-brands fa-apple','fa-brands fa-android','fa-brands fa-windows',
        'fa-brands fa-raspberry-pi',
      ]},
      { g: 'Herramientas / Apps', i: [
        'fa-brands fa-figma','fa-brands fa-sketch','fa-brands fa-adobe',
        'fa-brands fa-chrome','fa-brands fa-firefox','fa-brands fa-edge',
        'fa-brands fa-safari','fa-brands fa-wordpress','fa-brands fa-shopify',
        'fa-brands fa-stripe','fa-brands fa-paypal',
      ]},
      { g: 'Redes / Comunidad', i: [
        'fa-brands fa-slack','fa-brands fa-discord','fa-brands fa-telegram',
        'fa-brands fa-x-twitter','fa-brands fa-linkedin','fa-brands fa-stackoverflow',
        'fa-brands fa-dev','fa-brands fa-codepen','fa-brands fa-youtube','fa-brands fa-twitch',
      ]},
    ]
  },
  fas: {
    label: 'FA Solid',
    groups: [
      { g: 'Código / Dev', i: [
        'fa-solid fa-code','fa-solid fa-terminal','fa-solid fa-laptop-code',
        'fa-solid fa-file-code','fa-solid fa-braces','fa-solid fa-bug',
        'fa-solid fa-cube','fa-solid fa-cubes','fa-solid fa-layer-group',
        'fa-solid fa-sitemap','fa-solid fa-diagram-project',
      ]},
      { g: 'Infraestructura', i: [
        'fa-solid fa-database','fa-solid fa-server','fa-solid fa-cloud',
        'fa-solid fa-hard-drive','fa-solid fa-microchip','fa-solid fa-network-wired',
        'fa-solid fa-wifi','fa-solid fa-globe','fa-solid fa-plug','fa-solid fa-memory',
      ]},
      { g: 'Herramientas', i: [
        'fa-solid fa-wrench','fa-solid fa-screwdriver-wrench','fa-solid fa-gear',
        'fa-solid fa-gears','fa-solid fa-puzzle-piece','fa-solid fa-wand-magic-sparkles',
        'fa-solid fa-toolbox','fa-solid fa-pen-nib','fa-solid fa-pencil',
      ]},
      { g: 'Data / IA', i: [
        'fa-solid fa-chart-line','fa-solid fa-chart-bar','fa-solid fa-chart-pie',
        'fa-solid fa-magnifying-glass','fa-solid fa-magnifying-glass-chart',
        'fa-solid fa-robot','fa-solid fa-brain','fa-solid fa-infinity',
        'fa-solid fa-atom','fa-solid fa-flask',
      ]},
      { g: 'General', i: [
        'fa-solid fa-lightbulb','fa-solid fa-fire','fa-solid fa-bolt',
        'fa-solid fa-star','fa-solid fa-palette','fa-solid fa-lock',
        'fa-solid fa-shield-halved','fa-solid fa-key','fa-solid fa-user-tie',
        'fa-solid fa-users','fa-solid fa-envelope','fa-solid fa-bell',
        'fa-solid fa-folder-open','fa-solid fa-hand-pointer','fa-solid fa-circle-check',
        'fa-solid fa-rocket','fa-solid fa-award','fa-solid fa-briefcase',
        'fa-solid fa-map-pin','fa-solid fa-home',
      ]},
    ]
  }
};
function ipIconName(cls) {
  return cls
    .replace(/^devicon-/, '').replace(/-(plain|original|line|fill)(-(wordmark|colored))?$/, '')
    .replace(/^ri-/, '').replace(/^fa-brands\s+fa-/, '').replace(/^fa-solid\s+fa-/, '')
    .replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}
function toggleNavGroup(btn) {
  const group = btn.closest('.nav-group');
  group.classList.toggle('open');
}
document.querySelectorAll('.nav-subitems .nav-subitem.active').forEach(function(el) {
  el.closest('.nav-group').classList.add('open');
});

// Confirmación de eliminación con SweetAlert2
function confirmDelete(form, msg) {
  Swal.fire({
    title: '\u00bfEliminar?',
    text: msg || 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
    background: '#111827',
    color: '#e2e8f0',
    customClass: { popup: 'admin-swal' }
  }).then(result => { if (result.isConfirmed) form.submit(); });
  return false;
}

// Toast helper
function adminToast(icon, title) {
  Swal.fire({
    toast: true, position: 'top-end', icon, title,
    showConfirmButton: false, timer: 3000, timerProgressBar: true,
    background: '#111827', color: '#e2e8f0'
  });
}
</script>
<style>
/* ── Editor Visual/HTML toggle ─────────────────── */
.editor-mode-bar{display:flex;align-items:center;justify-content:space-between;background:#0f172a;border:1px solid #1e2d40;border-radius:6px 6px 0 0;padding:.3rem .65rem;gap:.5rem;border-bottom:none}
.editor-mode-btns{display:flex;gap:.25rem}
.emode-btn{display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .6rem;border-radius:.35rem;font-size:.78rem;border:1px solid #1e2d40;background:transparent;color:#64748b;cursor:pointer;transition:background .15s,color .15s,border-color .15s}
.emode-btn:hover{color:#e2e8f0;border-color:#334155;background:#1a2332}
.emode-btn.active{background:rgba(0,212,255,.1);color:#00d4ff;border-color:rgba(0,212,255,.4)}
.editor-mode-bar ~ div .ql-toolbar,.editor-mode-bar ~ div .ql-container{border-radius:0!important}
.raw-html-editor{width:100%;background:#0b0f19;color:#6ee7b7;border:1px solid #1e2d40;border-radius:0 0 6px 6px;padding:.75rem;font-family:'Fira Mono',monospace,monospace;font-size:.82rem;resize:vertical;line-height:1.6;outline:none;transition:border-color .2s}
.raw-html-editor:focus{border-color:#00d4ff55}
</style>
<?php require __DIR__ . '/../admin/_media_modal.php'; ?>
</body>
</html>
