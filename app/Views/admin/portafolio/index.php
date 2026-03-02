<?php use Core\Security; ?>

<div class="page-header">
  <h2>🗂 Portafolio</h2>
  <a href="/admin/portafolio/create" class="btn btn-primary">+ Nuevo Proyecto</a>
</div>

<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr><th>#</th><th>Imagen</th><th>Título</th><th>Categoría</th><th>Visible</th><th>Modo Seguro</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      <?php foreach ($proyectos as $p): ?>
      <tr id="row-port-<?= (int)$p['id'] ?>">
        <td><?= (int)$p['id'] ?></td>
        <td>
          <?php if ($p['imagen_url']): ?>
            <img src="<?= Security::escape($p['imagen_url']) ?>" alt="" style="width:60px;height:40px;object-fit:cover;border-radius:4px">
          <?php else: ?>—<?php endif; ?>
        </td>
        <td><?= Security::escape($p['titulo']) ?></td>
        <td><span class="badge-type"><?= Security::escape($p['categoria']) ?></span></td>
        <td>
          <label class="toggle-switch" title="<?= $p['visible'] ? 'Visible — click para ocultar' : 'Oculto — click para mostrar' ?>">
            <input type="checkbox" class="toggle-visible-chk"
                   data-id="<?= (int)$p['id'] ?>"
                   data-url="/admin/portafolio/toggle/<?= (int)$p['id'] ?>"
                   data-csrf="<?= Security::generateCsrfToken() ?>"
                   data-label-on="Proyecto visible" data-label-off="Proyecto oculto"
                   <?= $p['visible'] ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
          </label>
        </td>
        <td><?= $p['modo_seguro'] ? '🔒' : '—' ?></td>
        <td class="actions">
          <a href="/admin/portafolio/edit/<?= (int)$p['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
          <form method="POST" action="/admin/portafolio/delete/<?= (int)$p['id'] ?>" style="display:inline"
                onsubmit="return confirmDelete(this, '¿Eliminar <?= Security::escape(addslashes($p['titulo'])) ?>?')">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($proyectos)): ?>
        <tr><td colspan="7" class="empty">No hay proyectos.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php /* shared toggle script — same pattern as tecnologias */ ?>
<script>
document.querySelectorAll('.toggle-visible-chk').forEach(function(chk) {
  chk.addEventListener('change', async function() {
    const url  = this.dataset.url;
    const csrf = this.dataset.csrf;
    const on   = this.dataset.labelOn  || 'Visible';
    const off  = this.dataset.labelOff || 'Oculto';
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
        if (label) label.title = data.visible ? on + ' — click para ocultar' : off + ' — click para mostrar';
        adminToast(data.visible ? 'success' : 'info', data.visible ? on : off);
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
