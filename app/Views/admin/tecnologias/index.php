<?php use Core\Security; ?>

<div class="page-header">
  <h2>🔧 Tecnologías</h2>
  <a href="/admin/tecnologias/create" class="btn btn-primary">+ Nueva Tecnología</a>
</div>

<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr><th>#</th><th>Icono</th><th>Nombre</th><th>Nivel</th><th>Categoría</th><th>Tipo</th><th>Visible</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      <?php foreach ($tecnologias as $t): ?>
      <tr id="row-tec-<?= (int)$t['id'] ?>">
        <td><?= (int)$t['id'] ?></td>
        <td>
          <?php if ($t['icono_tipo'] === 'svg_custom'): ?>
            <span style="width:24px;display:inline-block"><?= $t['icono_valor'] ?></span>
          <?php elseif (!empty($t['icono_valor'])): ?>
            <i class="<?= Security::escape($t['icono_valor']) ?>" style="font-size:1.5rem;color:#00d4ff"></i>
          <?php else: ?>&mdash;<?php endif; ?>
        </td>
        <td><?= Security::escape($t['nombre']) ?></td>
        <td>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?= (int)$t['nivel'] ?>%"></div>
          </div>
          <?= (int)$t['nivel'] ?>%
        </td>
        <td><span class="badge-type"><?= Security::escape($t['categoria']) ?></span></td>
        <td><?= Security::escape($t['icono_tipo']) ?></td>
        <td>
          <label class="toggle-switch" title="<?= $t['visible'] ? 'Visible — haz click para ocultar' : 'Oculto — haz click para mostrar' ?>">
            <input type="checkbox" class="tec-visible-chk"
                   data-id="<?= (int)$t['id'] ?>"
                   data-csrf="<?= Security::generateCsrfToken() ?>"
                   <?= $t['visible'] ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
          </label>
        </td>
        <td class="actions">
          <a href="/admin/tecnologias/edit/<?= (int)$t['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
          <form method="POST" action="/admin/tecnologias/delete/<?= (int)$t['id'] ?>" style="display:inline"
                onsubmit="return confirmDelete(this, '¿Eliminar <?= Security::escape(addslashes($t['nombre'])) ?>?')">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($tecnologias)): ?>
        <tr><td colspan="8" class="empty">No hay tecnologías.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
document.querySelectorAll('.tec-visible-chk').forEach(function(chk) {
  chk.addEventListener('change', async function() {
    const id   = this.dataset.id;
    const csrf = this.dataset.csrf;
    const cb   = this;
    cb.disabled = true;

    const fd = new FormData();
    fd.append('_csrf', csrf);

    try {
      const res  = await fetch('/admin/tecnologias/toggle/' + id, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd
      });
      const data = await res.json();
      if (data.success) {
        cb.checked = data.visible === 1;
        const label = cb.closest('label');
        if (label) label.title = data.visible ? 'Visible — haz click para ocultar' : 'Oculto — haz click para mostrar';
        adminToast(data.visible ? 'success' : 'info', data.visible ? 'Tecnología visible' : 'Tecnología oculta');
      } else {
        cb.checked = !cb.checked; // revert
        adminToast('error', data.message || 'Error al cambiar estado');
      }
    } catch(err) {
      cb.checked = !cb.checked; // revert
      adminToast('error', 'Error de conexión');
    } finally {
      cb.disabled = false;
    }
  });
});
</script>
