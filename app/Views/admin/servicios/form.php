<?php use Core\Security; ?>

<?php
$isEdit    = $servicio !== null;
$action    = $isEdit ? "/admin/servicios/update/{$idx}" : '/admin/servicios/store';
$itemsList = implode("\n", $servicio['items'] ?? []);
$curIcon   = $servicio['icon'] ?? 'ri-settings-3-line';

// Iconos disponibles (Remixicon)
$iconOptions = [
  'ri-code-s-slash-line'    => 'Código',
  'ri-layout-grid-line'     => 'Layout',
  'ri-settings-3-line'      => 'Configuración',
  'ri-smartphone-line'      => 'Móvil',
  'ri-cloud-line'           => 'Cloud',
  'ri-database-2-line'      => 'Base de datos',
  'ri-shield-check-line'    => 'Seguridad',
  'ri-search-eye-line'      => 'SEO',
  'ri-palette-line'         => 'Diseño',
  'ri-rocket-line'          => 'Performance',
  'ri-brush-line'           => 'UI/UX',
  'ri-terminal-line'        => 'DevOps',
  'ri-globe-line'           => 'Web',
  'ri-store-3-line'         => 'E-commerce',
  'ri-bar-chart-line'       => 'Analytics',
  'ri-cpu-line'             => 'Backend',
  'ri-image-2-line'         => 'Multimedia',
  'ri-team-line'            => 'Consultoría',
  'ri-lightbulb-line'       => 'Innovación',
  'ri-puzzle-line'          => 'Integración',
  'ri-tools-line'           => 'Mantenimiento',
  'ri-bug-line'             => 'Testing / QA',
  'ri-mail-send-line'       => 'Email / Marketing',
  'ri-megaphone-line'       => 'Marketing digital',
];
?>

<div class="page-header">
  <h2>
    <i class="<?= Security::escape($curIcon) ?>"></i>
    <?= $isEdit ? 'Editar Servicio' : 'Nuevo Servicio' ?>
  </h2>
  <a href="/admin/servicios" class="btn btn-secondary">
    <i class="ri-arrow-left-line"></i> Volver
  </a>
</div>

<form method="POST" action="<?= $action ?>" class="admin-form">
  <?= Security::csrfField() ?>
  <input type="hidden" name="icon" id="icon-value" value="<?= Security::escape($curIcon) ?>">

  <!-- Selector de icono -->
  <div class="form-group" style="margin-bottom:1.5rem">
    <label>Icono del servicio</label>
    <div class="icon-picker" id="icon-picker">
      <?php foreach ($iconOptions as $cls => $label): ?>
        <button type="button"
                class="icon-option <?= $cls === $curIcon ? 'selected' : '' ?>"
                data-icon="<?= Security::escape($cls) ?>"
                title="<?= Security::escape($label) ?>">
          <i class="<?= Security::escape($cls) ?>"></i>
          <span><?= Security::escape($label) ?></span>
        </button>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:.5rem;font-size:.82rem;color:#94a3b8">
      Seleccionado: <code id="icon-preview-label"><?= Security::escape($curIcon) ?></code>
    </p>
  </div>

  <div class="form-grid" style="grid-template-columns:1fr 80px">
    <div class="form-group">
      <label>Título *</label>
      <input type="text" name="titulo" value="<?= Security::escape($servicio['titulo'] ?? '') ?>"
             placeholder="Ej: Desarrollo Web" required>
    </div>
    <div class="form-group">
      <label>Orden</label>
      <input type="number" name="orden" value="<?= (int)($servicio['orden'] ?? ($idx ?? 99)) ?>" min="0" max="999">
    </div>
  </div>

  <div class="form-group">
    <div class="editor-mode-bar">
      <span style="font-size:.82rem;color:#94a3b8;font-weight:600">Descripción corta *</span>
      <div class="editor-mode-btns">
        <button type="button" class="emode-btn active" id="emode-visual" onclick="setEditorMode('visual')"><i class="ri-edit-2-line"></i> Visual</button>
        <button type="button" class="emode-btn" id="emode-html" onclick="setEditorMode('html')"><i class="ri-code-line"></i> HTML</button>
      </div>
    </div>
    <div id="editor-visual"><div id="quill-desc" style="min-height:110px;background:#0f172a"></div></div>
    <div id="editor-html" style="display:none"><textarea id="raw-html" class="raw-html-editor" style="min-height:110px" placeholder="Pega o escribe HTML directamente..."></textarea></div>
    <input type="hidden" name="desc" id="desc-hidden">
  </div>

  <div class="form-group">
    <label>Items / características <small style="color:#94a3b8">(una por línea)</small></label>
    <textarea name="items" rows="5"
              placeholder="Responsive design&#10;Performance optimizada&#10;SEO básico"><?= Security::escape($itemsList) ?></textarea>
    <small style="color:#64748b">Cada línea se convierte en un ítem de lista en la tarjeta.</small>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i class="<?= $isEdit ? 'ri-save-line' : 'ri-add-circle-line' ?>"></i>
      <?= $isEdit ? 'Guardar cambios' : 'Crear servicio' ?>
    </button>
    <a href="/admin/servicios" class="btn btn-secondary">Cancelar</a>
  </div>
</form>

<style>
.icon-picker {
  display: flex; flex-wrap: wrap; gap: .5rem;
  padding: .75rem;
  background: var(--bg3, #0f172a);
  border: 1px solid var(--border, #1e2d40);
  border-radius: 10px;
  max-height: 260px; overflow-y: auto;
}
.icon-option {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: .2rem;
  width: 72px; padding: .55rem .4rem;
  background: none; border: 1px solid var(--border, #1e2d40);
  border-radius: 8px; cursor: pointer; color: #94a3b8;
  font-size: .7rem; text-align: center;
  transition: border-color .15s, color .15s, background .15s;
}
.icon-option i { font-size: 1.35rem; line-height: 1; }
.icon-option:hover { border-color: rgba(0,212,255,.5); color: #e2e8f0; background: rgba(0,212,255,.06); }
.icon-option.selected { border-color: #00d4ff; color: #00d4ff; background: rgba(0,212,255,.1); }
</style>
<script>
document.querySelectorAll('.icon-option').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.icon-option').forEach(function(b){ b.classList.remove('selected'); });
    this.classList.add('selected');
    var ic = this.dataset.icon;
    document.getElementById('icon-value').value = ic;
    document.getElementById('icon-preview-label').textContent = ic;
  });
});

// Quill for description
const quillDesc = new Quill('#quill-desc', {
  theme: 'snow',
  placeholder: 'Breve descripción del servicio…',
  modules: { toolbar: QUILL_TOOLBAR }
});
registerImageHandler(quillDesc);
quillDesc.root.innerHTML = <?= json_encode(html_entity_decode($servicio['desc'] ?? '', ENT_QUOTES), JSON_HEX_TAG) ?>;
let editorMode = 'visual';
function setEditorMode(mode) {
  if (mode === 'html') {
    document.getElementById('raw-html').value = quillDesc.root.innerHTML;
    document.getElementById('editor-visual').style.display = 'none';
    document.getElementById('editor-html').style.display   = 'block';
    document.getElementById('emode-visual').classList.remove('active');
    document.getElementById('emode-html').classList.add('active');
  } else {
    quillDesc.root.innerHTML = document.getElementById('raw-html').value;
    document.getElementById('editor-html').style.display   = 'none';
    document.getElementById('editor-visual').style.display = 'block';
    document.getElementById('emode-html').classList.remove('active');
    document.getElementById('emode-visual').classList.add('active');
    quillDesc.update();
  }
  editorMode = mode;
}
document.querySelector('.admin-form').addEventListener('submit', function() {
  document.getElementById('desc-hidden').value =
    editorMode === 'html' ? document.getElementById('raw-html').value : quillDesc.root.innerHTML;
});
</script>
