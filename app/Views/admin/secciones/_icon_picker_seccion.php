<?php
/**
 * Partial: compact icon picker — Secciones (hero / sobre)
 * Variables (set before including):
 *   $sipCurrent  string  current icon class (e.g. 'ri-user-3-line')
 * IP_DATA + ipIconName are defined in layouts/admin.php.
 * On change, calls window.sipOnChange(cls) if defined — used by edit.php
 * to keep `selectedIcon` in sync.
 */
$sipCurrent = $sipCurrent ?? '';

// Detect library from prefix
if (str_starts_with($sipCurrent, 'devicon-'))        $sipLib = 'devicon';
elseif (str_starts_with($sipCurrent, 'ri-'))         $sipLib = 'ri';
elseif (str_starts_with($sipCurrent, 'fa-brands '))  $sipLib = 'fab';
elseif (str_starts_with($sipCurrent, 'fa-solid '))   $sipLib = 'fas';
else                                                  $sipLib = 'ri'; // default for secciones
?>
<div class="ico-picker-wrap" style="margin-bottom:.5rem">
  <label style="font-size:.82rem;color:#94a3b8;display:block;margin-bottom:.3rem">
    <i class="ri-apps-line"></i> Icono de sección
  </label>
  <div class="ico-picker-row">
    <div class="ico-picker-col">
      <label class="ico-sub-label">Tipo de fuente</label>
      <select id="sip-lib" class="ico-sel ip-lib-sel" onchange="sipLibChange(this.value)">
        <option value="ri"      <?= $sipLib==='ri'      ? 'selected':'' ?>>Remix Icons</option>
        <option value="fas"     <?= $sipLib==='fas'     ? 'selected':'' ?>>FA Solid</option>
        <option value="fab"     <?= $sipLib==='fab'     ? 'selected':'' ?>>FA Brands</option>
        <option value="devicon" <?= $sipLib==='devicon' ? 'selected':'' ?>>Devicons</option>
      </select>
    </div>
    <div class="ico-picker-col" id="sip-ico-col">
      <label class="ico-sub-label">Icono</label>
      <select id="sip-ico" class="ico-sel ip-ico-sel" onchange="sipIcoChange(this.value)"></select>
    </div>
    <div class="ico-preview-box" id="sip-prev-box">
      <i id="sip-preview" class="<?= htmlspecialchars($sipCurrent) ?>"></i>
      <span id="sip-prev-name"></span>
    </div>
  </div>
</div>
<script>
/* ── Secciones icon picker ── uses global IP_DATA + ipIconName ── */
function sipPopulate(lib, restore) {
  const sel = document.getElementById('sip-ico');
  sel.innerHTML = '';
  const data = IP_DATA[lib];
  if (!data) return;
  data.groups.forEach(grp => {
    const og = document.createElement('optgroup');
    og.label = grp.g;
    grp.i.forEach(cls => {
      const o = document.createElement('option');
      o.value = cls; o.textContent = ipIconName(cls);
      og.appendChild(o);
    });
    sel.appendChild(og);
  });
  if (restore && [...sel.options].some(o => o.value === restore)) sel.value = restore;
  sipIcoChange(sel.value);
}
function sipLibChange(lib) {
  sipPopulate(lib, null);
}
function sipIcoChange(cls) {
  if (!cls) return;
  document.getElementById('sip-preview').className = cls;
  document.getElementById('sip-prev-name').textContent = ipIconName(cls);
  if (typeof window.sipOnChange === 'function') window.sipOnChange(cls);
}
(function sipInit() {
  const lib = '<?= htmlspecialchars($sipLib) ?>';
  const cur = '<?= htmlspecialchars($sipCurrent) ?>';
  document.getElementById('sip-lib').value = lib;
  sipPopulate(lib, cur);
})();
</script>
