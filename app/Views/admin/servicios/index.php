<?php use Core\Security; ?>

<div class="page-header">
  <h2><i class="ri-settings-3-line"></i> Servicios</h2>
  <a href="/admin/servicios/create" class="btn btn-primary">
    <i class="ri-add-circle-line"></i> Nuevo Servicio
  </a>
</div>

<?php if (!empty($seccion)): ?>
<div class="card mb-3" style="padding:.75rem 1rem;font-size:.85rem;color:#94a3b8;border-left:3px solid var(--cyan)">
  Sección: <strong><?= Security::escape($seccion['titulo'] ?? 'Servicios') ?></strong>
  &nbsp;—&nbsp;
  <a href="/admin/secciones/edit/<?= (int)($seccion['id'] ?? 0) ?>" style="color:var(--cyan)">
    <i class="ri-pencil-line"></i> Editar metadatos de sección
  </a>
</div>
<?php endif; ?>

<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Orden</th>
        <th>Icono</th>
        <th>Título</th>
        <th>Descripción</th>
        <th>Items</th>
        <th>Visible</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($servicios as $i => $sv):
            $svKey = $sv['_id'] ?? (string)$i;
        ?>
      <tr>
        <td><?= (int)($sv['orden'] ?? $i) ?></td>
        <td style="font-size:1.5rem;line-height:1;color:var(--cyan)">
          <?php
            $ic = $sv['icon'] ?? '';
            if ($ic && str_starts_with($ic, 'ri-')) {
              echo '<i class="'.Security::escape($ic).'"></i>';
            } else {
              echo Security::escape($ic);
            }
          ?>
        </td>
        <td><strong><?= Security::escape($sv['titulo'] ?? '') ?></strong></td>
        <td style="max-width:280px;white-space:normal;font-size:.85rem;color:#94a3b8"><?= Security::escape($sv['desc'] ?? '') ?></td>
        <td><?= count($sv['items'] ?? []) ?></td>
        <td>
          <?php $svVisible = isset($sv['visible']) ? (int)$sv['visible'] : 1; ?>
          <label class="toggle-switch" title="<?= $svVisible ? 'Visible — click para ocultar' : 'Oculto — click para mostrar' ?>">
            <input type="checkbox" class="sv-visible-chk"
                   data-url="/admin/servicios/toggle/<?= urlencode($svKey) ?>"
                   data-csrf="<?= Security::generateCsrfToken() ?>"
                   <?= $svVisible ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
          </label>
        </td>
        <td class="actions">
          <a href="/admin/servicios/edit/<?= urlencode($svKey) ?>" class="btn btn-sm btn-secondary">
            <i class="ri-pencil-line"></i> Editar
          </a>
          <form method="POST" action="/admin/servicios/delete/<?= urlencode($svKey) ?>" style="display:inline"
                onsubmit="return confirmDelete(this, '¿Eliminar <?= Security::escape(addslashes($sv['titulo'] ?? '')) ?>?')">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-sm btn-danger">
              <i class="ri-delete-bin-line"></i> Eliminar
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($servicios)): ?>
        <tr><td colspan="7" class="empty">
          No hay servicios. <a href="/admin/servicios/create">Crear el primero</a>
        </td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
document.querySelectorAll('.sv-visible-chk').forEach(function(chk) {
  chk.addEventListener('change', async function() {
    const url  = this.dataset.url;
    const csrf = this.dataset.csrf;
    const cb   = this;
    cb.disabled = true;
    const fd = new FormData();
    fd.append('_csrf', csrf);
    try {
      const res  = await fetch(url, { method:'POST', headers:{'Accept':'application/json'}, body:fd });
      const data = await res.json();
      if (data.success) {
        cb.checked = data.visible === 1;
        const label = cb.closest('label');
        if (label) label.title = data.visible ? 'Visible — click para ocultar' : 'Oculto — click para mostrar';
        adminToast(data.visible ? 'success' : 'info', data.visible ? 'Servicio visible' : 'Servicio oculto');
      } else {
        cb.checked = !cb.checked;
        adminToast('error', data.message || 'Error al cambiar estado');
      }
    } catch(e) {
      cb.checked = !cb.checked;
      adminToast('error', 'Error de conexión');
    } finally {
      cb.disabled = false;
    }
  });
});
</script>
