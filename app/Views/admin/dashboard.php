<?php use Core\Security; ?>

<div class="dashboard-grid">
  <!-- Métricas -->
  <div class="metrics-row">
    <div class="metric-card">
      <div class="metric-icon">📄</div>
      <div class="metric-value"><?= (int)$secciones ?></div>
      <div class="metric-label">Secciones</div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">🗂</div>
      <div class="metric-value"><?= (int)$proyectos ?></div>
      <div class="metric-label">Proyectos</div>
    </div>
    <div class="metric-card">
      <div class="metric-icon">🔧</div>
      <div class="metric-value"><?= (int)$tecnologias ?></div>
      <div class="metric-label">Tecnologías</div>
    </div>
    <div class="metric-card <?= $mensajes_nuevos > 0 ? 'metric-alert' : '' ?>">
      <div class="metric-icon">✉️</div>
      <div class="metric-value"><?= (int)$mensajes_nuevos ?></div>
      <div class="metric-label">Mensajes nuevos</div>
    </div>
  </div>

  <!-- Toggles -->
  <div class="toggles-row">
    <div class="toggle-card">
      <h3>🔒 Modo Seguro</h3>
      <p>Oculta proyectos y secciones marcadas como "privadas".</p>
      <div class="toggle-status <?= $modo_seguro ? 'on' : 'off' ?>">
        <?= $modo_seguro ? '● ACTIVO' : '○ INACTIVO' ?>
      </div>
      <form method="POST" action="/admin/modo-seguro/toggle" style="margin-top:1rem">
        <?= \Core\Security::csrfField() ?>
        <button type="submit" class="btn btn-sm <?= $modo_seguro ? 'btn-danger' : 'btn-primary' ?>">
          <?= $modo_seguro ? 'Desactivar' : 'Activar' ?>
        </button>
      </form>
    </div>

    <div class="toggle-card">
      <h3>🚧 Modo Mantenimiento</h3>
      <p>Muestra la página 503 al público. Admins pueden navegar con normalidad.</p>
      <div class="toggle-status <?= $modo_mant ? 'on' : 'off' ?>">
        <?= $modo_mant ? '● ACTIVO' : '○ INACTIVO' ?>
      </div>
      <form method="POST" action="/admin/modo-mantenimiento/toggle" style="margin-top:1rem">
        <?= \Core\Security::csrfField() ?>
        <button type="submit" class="btn btn-sm <?= $modo_mant ? 'btn-danger' : 'btn-warning' ?>">
          <?= $modo_mant ? 'Desactivar' : 'Activar' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- Accesos rápidos -->
  <div class="quick-actions">
    <h3>Accesos rápidos</h3>
    <div class="quick-grid">
      <a href="/admin/secciones/create" class="quick-btn">+ Nueva Sección</a>
      <a href="/admin/portafolio/create" class="quick-btn">+ Nuevo Proyecto</a>
      <a href="/admin/tecnologias/create" class="quick-btn">+ Nueva Tecnología</a>
      <a href="/admin/mensajes" class="quick-btn">Ver Mensajes</a>
      <a href="/admin/configuracion" class="quick-btn">Configuración</a>
      <a href="/" target="_blank" class="quick-btn">Ver Sitio →</a>
    </div>
  </div>
</div>
