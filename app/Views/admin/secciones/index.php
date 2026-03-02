<?php use Core\Security; ?>
<?php
function secIconIdx(string $tipo): string {
    $map = ['hero'=>'ri-home-4-line','sobre'=>'ri-user-3-line','portafolio'=>'ri-image-2-line',
            'tecnologias'=>'ri-cpu-line','servicios'=>'ri-settings-3-line','contacto'=>'ri-mail-open-line'];
    return $map[$tipo] ?? 'ri-file-text-line';
}
function secColorIdx(string $tipo): string {
    $map = ['hero'=>'#6366f1','sobre'=>'#0ea5e9','portafolio'=>'#8b5cf6',
            'tecnologias'=>'#10b981','servicios'=>'#f59e0b','contacto'=>'#ec4899'];
    return $map[$tipo] ?? '#64748b';
}
function secCount(string $tipo, int $p, int $t, int $s): ?int {
    return match($tipo) { 'portafolio'=>$p, 'tecnologias'=>$t, 'servicios'=>$s, default=>null };
}
function secLabel(string $tipo): string {
    return match($tipo) { 'portafolio'=>'proyectos', 'tecnologias'=>'tecnologías', 'servicios'=>'servicios', default=>'ítems' };
}
?>

<div class="page-header">
  <h2><i class="ri-layout-row-line"></i> Secciones del sitio</h2>
  <a href="/admin/secciones/create" class="btn btn-primary">
    <i class="ri-add-circle-line"></i> Nueva sección
  </a>
</div>

<div class="sections-grid">
  <?php foreach ($secciones as $s):
    $tipo  = $s['tipo_seccion'] ?? 'otro';
    $icon  = secIconIdx($tipo);
    $color = secColorIdx($tipo);
    $count = secCount($tipo, $portafolioCount ?? 0, $tecnologiasCount ?? 0, $serviciosCount ?? 0);
  ?>
  <div class="section-card <?= $s['visible'] ? '' : 'section-card--hidden' ?>">
    <div class="section-card-bar" style="background:<?= $color ?>"></div>
    <div class="section-card-header">
      <span class="section-card-icon" style="background:<?= $color ?>22;color:<?= $color ?>">
        <i class="<?= $icon ?>"></i>
      </span>
      <div class="section-card-meta">
        <strong><?= Security::escape($s['titulo']) ?></strong>
        <span class="section-card-type" style="color:<?= $color ?>"><?= Security::escape($tipo) ?></span>
      </div>
      <?php if (!$s['visible']): ?>
        <span class="pill pill--off" id="pill-sec-<?= (int)$s['id'] ?>">Oculta</span>
      <?php else: ?>
        <span class="pill pill--on" id="pill-sec-<?= (int)$s['id'] ?>">Visible</span>
      <?php endif; ?>
      <label class="toggle-switch" style="margin-left:.3rem" title="<?= $s['visible'] ? 'Visible — click para ocultar' : 'Oculta — click para mostrar' ?>">
        <input type="checkbox" class="sec-visible-chk"
               data-id="<?= (int)$s['id'] ?>"
               data-csrf="<?= Security::generateCsrfToken() ?>"
               <?= $s['visible'] ? 'checked' : '' ?>>
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="section-card-body">
      <?php if ($count !== null): ?>
        <p class="section-card-stat"><i class="ri-list-check"></i> <strong><?= $count ?></strong> <?= secLabel($tipo) ?></p>
      <?php else: ?>
        <p class="section-card-preview"><?= htmlspecialchars(mb_substr(strip_tags($s['contenido'] ?? ''), 0, 90)) ?>…</p>
      <?php endif; ?>
      <p class="section-card-stat" style="margin-top:.4rem;color:#475569">
        <i class="ri-sort-asc"></i> Orden <?= (int)$s['orden'] ?>
        <?php if ($s['modo_seguro']): ?>
          &nbsp;<i class="ri-lock-line" title="Se oculta en Modo Seguro"></i>
        <?php endif; ?>
      </p>
    </div>
    <div class="section-card-actions">
      <a href="/admin/secciones/edit/<?= (int)$s['id'] ?>" class="btn btn-sm btn-primary">
        <i class="ri-pencil-line"></i> Gestionar
      </a>
      <form method="POST" action="/admin/secciones/delete/<?= (int)$s['id'] ?>" style="display:inline"
            onsubmit="return confirmDelete(this, '¿Eliminar &quot;<?= Security::escape(addslashes($s['titulo'])) ?>&quot;?')">
        <?= Security::csrfField() ?>
        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar"><i class="ri-delete-bin-line"></i></button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($secciones)): ?>
    <p class="empty" style="padding:2rem">No hay secciones. <a href="/admin/secciones/create">Crear la primera</a></p>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('.sec-visible-chk').forEach(function(chk) {
  chk.addEventListener('change', async function() {
    const id   = this.dataset.id;
    const csrf = this.dataset.csrf;
    const cb   = this;
    cb.disabled = true;
    const fd = new FormData();
    fd.append('_csrf', csrf);
    try {
      const res  = await fetch('/admin/secciones/toggle/' + id, { method:'POST', headers:{'Accept':'application/json'}, body:fd });
      const data = await res.json();
      if (data.success) {
        cb.checked = data.visible === 1;
        const card = document.getElementById('row-sec-' + id) || cb.closest('.section-card');
        const pill = document.getElementById('pill-sec-' + id);
        const label = cb.closest('label');
        if (card) {
          card.classList.toggle('section-card--hidden', !data.visible);
        }
        if (pill) {
          pill.textContent = data.visible ? 'Visible' : 'Oculta';
          pill.classList.toggle('pill--on', !!data.visible);
          pill.classList.toggle('pill--off', !data.visible);
        }
        if (label) label.title = data.visible ? 'Visible — click para ocultar' : 'Oculta — click para mostrar';
        adminToast(data.visible ? 'success' : 'info', data.visible ? 'Sección visible' : 'Sección oculta');
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

<style>
.sections-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:1.2rem; }
.section-card { background:var(--bg2); border:1px solid var(--border); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; transition:box-shadow .2s,border-color .2s; }
.section-card:hover { border-color:rgba(0,212,255,.3); box-shadow:0 6px 20px rgba(0,0,0,.3); }
.section-card--hidden { opacity:.55; }
.section-card-bar { height:3px; }
.section-card-header { display:flex; align-items:center; gap:.7rem; padding:.9rem 1rem .5rem; }
.section-card-icon { display:flex; align-items:center; justify-content:center; width:38px; height:38px; border-radius:10px; font-size:1.2rem; flex-shrink:0; }
.section-card-meta { flex:1; min-width:0; }
.section-card-meta strong { display:block; font-size:.93rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.section-card-type { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
.pill { font-size:.7rem; padding:.12rem .45rem; border-radius:10px; font-weight:700; flex-shrink:0; }
.pill--on  { background:rgba(16,185,129,.15); color:#10b981; }
.pill--off { background:rgba(100,116,139,.15); color:#94a3b8; }
.section-card-body { padding:.35rem 1rem .7rem; flex:1; }
.section-card-preview { font-size:.82rem; color:#64748b; line-height:1.5; }
.section-card-stat { font-size:.82rem; color:#64748b; display:flex; align-items:center; gap:.35rem; }
.section-card-actions { display:flex; align-items:center; gap:.5rem; padding:.7rem 1rem; border-top:1px solid var(--border); background:rgba(0,0,0,.12); }
.section-card-actions .btn-primary { flex:1; justify-content:center; }
</style>
