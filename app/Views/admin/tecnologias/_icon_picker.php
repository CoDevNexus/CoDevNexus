<?php
/**
 * Partial: compact icon picker — Tecnologías form
 * Variables (set before including):
 *   $icoTipo  string  'devicon'|'ri'|'fab'|'fas'|'svg_custom'
 *   $icoCls   string  full CSS class string for selected icon
 *   $icoSvg   string  SVG source when icono_tipo=svg_custom
 * IP_DATA + ipIconName are defined in layouts/admin.php
 */
$icoTipo = $icoTipo ?? 'devicon';
$icoCls  = $icoCls  ?? '';
$icoSvg  = $icoSvg  ?? '';
?>
<div class="form-group ico-picker-wrap">
  <label>Icono de sección</label>
  <div class="ico-picker-row">
    <div class="ico-picker-col">
      <label class="ico-sub-label">Tipo de fuente</label>
      <select id="ip-lib" class="ico-sel ip-lib-sel" onchange="ipLibChange(this.value)">
        <option value="devicon"    <?= $icoTipo==='devicon'    ? 'selected':'' ?>>Devicons (Dev Tech)</option>
        <option value="ri"         <?= $icoTipo==='ri'         ? 'selected':'' ?>>Remix Icons</option>
        <option value="fab"        <?= $icoTipo==='fab'        ? 'selected':'' ?>>Font Awesome Brands</option>
        <option value="fas"        <?= $icoTipo==='fas'        ? 'selected':'' ?>>Font Awesome Solid</option>
        <option value="svg_custom" <?= $icoTipo==='svg_custom' ? 'selected':'' ?>>SVG personalizado</option>
      </select>
    </div>
    <div class="ico-picker-col" id="ip-ico-col">
      <label class="ico-sub-label">Icono</label>
      <select id="ip-ico" class="ico-sel ip-ico-sel" onchange="ipIcoChange(this.value)"></select>
    </div>
    <div class="ico-preview-box" id="ip-prev-box">
      <i id="ip-preview" class="<?= htmlspecialchars($icoCls) ?>"></i>
      <span id="ip-prev-name"></span>
    </div>
  </div>
  <div id="ico-svg-block" style="<?= $icoTipo==='svg_custom' ? '' : 'display:none' ?>;margin-top:.8rem">
    <label class="ico-sub-label">Código SVG</label>
    <textarea name="icono_svg" rows="5" placeholder="<svg xmlns='http://www.w3.org/2000/svg' ...>...</svg>"
              style="width:100%;background:#0b0f19;border:1px solid #1e2d40;color:#e2e8f0;padding:.8rem;border-radius:8px;font-family:monospace;font-size:.88rem;box-sizing:border-box;resize:vertical"><?= htmlspecialchars($icoSvg) ?></textarea>
  </div>
  <input type="hidden" name="icono_tipo"    id="ip-tipo" value="<?= htmlspecialchars($icoTipo) ?>">
  <input type="hidden" name="icono_devicon" id="ip-cls"  value="<?= htmlspecialchars($icoCls) ?>">
</div>
<script>
/* ── Icon Picker (tecnologías) ── IP_DATA + ipIconName come from admin.php ── */
function ipPopulate(lib, restore) {
  const sel = document.getElementById('ip-ico');
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
  ipIcoChange(sel.value);
}
function ipLibChange(lib) {
  const isSvg = lib === 'svg_custom';
  document.getElementById('ip-tipo').value = lib;
  document.getElementById('ico-svg-block').style.display = isSvg ? '' : 'none';
  document.getElementById('ip-ico-col').style.display    = isSvg ? 'none' : '';
  document.getElementById('ip-prev-box').style.display   = isSvg ? 'none' : '';
  if (!isSvg) ipPopulate(lib, null);
  else document.getElementById('ip-cls').value = '';
}
function ipIcoChange(cls) {
  if (!cls) return;
  document.getElementById('ip-cls').value = cls;
  document.getElementById('ip-preview').className = cls;
  document.getElementById('ip-prev-name').textContent = ipIconName(cls);
}
(function ipInit() {
  const lib = '<?= htmlspecialchars($icoTipo) ?>';
  const cur = '<?= htmlspecialchars($icoCls) ?>';
  const isSvg = lib === 'svg_custom';
  document.getElementById('ip-lib').value = lib;
  document.getElementById('ico-svg-block').style.display = isSvg ? '' : 'none';
  document.getElementById('ip-ico-col').style.display    = isSvg ? 'none' : '';
  document.getElementById('ip-prev-box').style.display   = isSvg ? 'none' : '';
  if (!isSvg) ipPopulate(lib, cur);
})();
</script>
<style>
.ico-picker-wrap    { margin-bottom:1.2rem }
.ico-picker-row     { display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-top:.5rem }
.ico-picker-col     { display:flex;flex-direction:column;gap:.3rem;flex:1;min-width:150px }
.ico-sub-label      { font-size:.8rem;color:#94a3b8;margin:0 }
.ico-sel            { background:#0b0f19;border:1px solid #1e2d40;color:#e2e8f0;padding:.45rem .7rem;border-radius:8px;font-size:.9rem;cursor:pointer }
.ico-sel:focus      { outline:none;border-color:#00d4ff;box-shadow:0 0 0 2px rgba(0,212,255,.2) }
.ip-lib-sel         { max-width:210px }
.ip-ico-sel         { min-width:180px }
.ico-preview-box    { display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:80px;min-height:70px;gap:.3rem;background:#0b0f19;border:1px solid #1e2d40;border-radius:10px;padding:.6rem 1rem }
.ico-preview-box i  { font-size:2.2rem;color:#00d4ff }
.ico-preview-box span { font-size:.7rem;color:#64748b;text-align:center;max-width:90px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
</style>
