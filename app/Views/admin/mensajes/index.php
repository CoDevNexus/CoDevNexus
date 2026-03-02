<?php
use Core\Security;

$sortUrl = function(string $col) use ($sort, $dir): string {
    $newDir = ($sort === $col && $dir === 'DESC') ? 'ASC' : 'DESC';
    return '/admin/mensajes?sort=' . urlencode($col) . '&dir=' . $newDir . '&page=1';
};
$sortIcon = function(string $col) use ($sort, $dir): string {
    if ($sort !== $col) return '<span class="sort-icon">↕</span>';
    return '<span class="sort-icon active">' . ($dir === 'ASC' ? '↑' : '↓') . '</span>';
};
$pageUrl = fn(int $p): string =>
    '/admin/mensajes?sort=' . urlencode($sort) . '&dir=' . $dir . '&page=' . $p;
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
  <h2>✉️ Mensajes</h2>
  <small style="color:#64748b"><?= $total ?> mensaje<?= $total !== 1 ? 's' : '' ?> en total</small>
</div>

<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th><a href="<?= $sortUrl('id') ?>" class="sort-link">#<?= $sortIcon('id') ?></a></th>
        <th><a href="<?= $sortUrl('nombre') ?>" class="sort-link">Nombre<?= $sortIcon('nombre') ?></a></th>
        <th><a href="<?= $sortUrl('correo') ?>" class="sort-link">Correo<?= $sortIcon('correo') ?></a></th>
        <th><a href="<?= $sortUrl('telefono') ?>" class="sort-link">Teléfono<?= $sortIcon('telefono') ?></a></th>
        <th><a href="<?= $sortUrl('pais') ?>" class="sort-link">País<?= $sortIcon('pais') ?></a></th>
        <th><a href="<?= $sortUrl('asunto') ?>" class="sort-link">Asunto<?= $sortIcon('asunto') ?></a></th>
        <th><a href="<?= $sortUrl('fecha') ?>" class="sort-link">Fecha<?= $sortIcon('fecha') ?></a></th>
        <th><a href="<?= $sortUrl('leido') ?>" class="sort-link">Estado<?= $sortIcon('leido') ?></a></th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($mensajes as $m): ?>
      <tr class="<?= !$m['leido'] ? 'row-unread' : '' ?>">
        <td><?= (int)$m['id'] ?></td>
        <td><?= Security::escape($m['nombre']) ?></td>
        <td><?= Security::escape($m['correo']) ?></td>
        <td><?= Security::escape($m['telefono'] ?? '—') ?></td>
        <td><?= Security::escape($m['pais'] ?? '—') ?></td>
        <td><?= Security::escape(mb_substr($m['asunto'] ?? '', 0, 45)) ?></td>
        <td><?= Security::escape($m['fecha']) ?></td>
        <td>
          <?= $m['leido'] ? '<span class="badge-read">Leído</span>' : '<span class="badge-unread">Nuevo</span>' ?>
          <?php if (!empty($m['respondido'])): ?>
            <span class="badge-read" style="margin-top:3px;display:block">Respondido</span>
          <?php endif; ?>
        </td>
        <td class="actions">
          <a href="/admin/mensajes/<?= (int)$m['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
          <form method="POST" action="/admin/mensajes/delete/<?= (int)$m['id'] ?>" style="display:inline"
                onsubmit="return confirmDelete(this, '¿Eliminar el mensaje de <?= Security::escape(addslashes($m['nombre'])) ?>?')">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($mensajes)): ?>
        <tr><td colspan="9" class="empty">No hay mensajes.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Paginación">
  <?php if ($page > 1): ?>
    <a href="<?= $pageUrl(1) ?>" class="page-btn" title="Primera">«</a>
    <a href="<?= $pageUrl($page - 1) ?>" class="page-btn">‹ Ant</a>
  <?php endif; ?>

  <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
    <a href="<?= $pageUrl($p) ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
  <?php endfor; ?>

  <?php if ($page < $pages): ?>
    <a href="<?= $pageUrl($page + 1) ?>" class="page-btn">Sig ›</a>
    <a href="<?= $pageUrl($pages) ?>" class="page-btn" title="Última">»</a>
  <?php endif; ?>

  <span class="page-info">Página <?= $page ?> / <?= $pages ?></span>
</nav>
<?php endif; ?>

<style>
.sort-link { color:inherit; text-decoration:none; display:flex; align-items:center; gap:.3rem; white-space:nowrap; }
.sort-link:hover { color:#00d4ff; }
.sort-icon { font-size:.75rem; opacity:.4; }
.sort-icon.active { opacity:1; color:#00d4ff; }
.pagination { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; margin-top:1.25rem; }
.page-btn {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:2rem; padding:.3rem .65rem; border-radius:.4rem;
  background:#1a2332; color:#94a3b8; border:1px solid #1e2d40;
  text-decoration:none; font-size:.82rem; transition:background .15s, color .15s;
}
.page-btn:hover { background:#1e3a5f; color:#e2e8f0; }
.page-btn.active { background:#00d4ff; color:#0b0f19; font-weight:700; border-color:#00d4ff; pointer-events:none; }
.page-info { margin-left:.5rem; font-size:.8rem; color:#64748b; }
</style>
