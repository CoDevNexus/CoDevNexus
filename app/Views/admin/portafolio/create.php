<?php use Core\Security; ?>

<div class="page-header">
  <h2>🗂 Nuevo Proyecto</h2>
  <a href="/admin/portafolio" class="btn btn-secondary">← Volver</a>
</div>

<form method="POST" action="/admin/portafolio/store" class="admin-form" autocomplete="off">
  <?= Security::csrfField() ?>

  <div class="form-row">
    <div class="form-group">
      <label>Título *</label>
      <input type="text" name="titulo" required maxlength="200">
    </div>
    <div class="form-group">
      <label>Categoría</label>
      <select name="categoria">
        <?php foreach (['redes','software','iot','automatizacion','web','otro'] as $c): ?>
          <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-group">
    <label>Descripción corta (máx 300 caracteres)</label>
    <input type="text" name="descripcion_corta" maxlength="300">
  </div>

  <div class="form-group">
    <div class="editor-mode-bar">
      <span style="font-size:.82rem;color:#94a3b8;font-weight:600">Descripción larga</span>
      <div class="editor-mode-btns">
        <button type="button" class="emode-btn active" id="emode-visual" onclick="setEditorMode('visual')"><i class="ri-edit-2-line"></i> Visual</button>
        <button type="button" class="emode-btn" id="emode-html" onclick="setEditorMode('html')"><i class="ri-code-line"></i> HTML</button>
      </div>
    </div>
    <div id="editor-visual"><div id="quill-editor" style="height:250px"></div></div>
    <div id="editor-html" style="display:none"><textarea id="raw-html" class="raw-html-editor" style="min-height:250px" placeholder="Pega o escribe HTML directamente..."></textarea></div>
    <input type="hidden" name="descripcion_larga" id="desc-larga">
  </div>

  <!-- ── Imagen (Media Library) ──────────────────────────── -->
  <div class="form-group">
    <label>Imagen</label>
    <button type="button" onclick="openMediaForField('campo-imagen-url','img-preview-portafolio')" class="btn btn-secondary">
      <i class="ri-image-add-line"></i> Seleccionar / Subir Imagen
    </button>
    <img id="img-preview-portafolio" style="display:none;max-width:200px;border-radius:8px;margin-top:.5rem">
    <input type="hidden" id="campo-imagen-url" name="imagen_url_externa" autocomplete="off">
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Enlace Demo</label>
      <input type="url" name="enlace_demo" placeholder="https://...">
    </div>
    <div class="form-group">
      <label>Enlace Repositorio</label>
      <input type="url" name="enlace_repo" placeholder="https://github.com/...">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group form-check">
      <label><input type="checkbox" name="visible" value="1" checked> Visible</label>
    </div>
    <div class="form-group form-check">
      <label><input type="checkbox" name="modo_seguro" value="1"> Ocultar en Modo Seguro</label>
    </div>
    <div class="form-group">
      <label>Orden</label>
      <input type="number" name="orden" value="0" min="0">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Guardar Proyecto</button>
</form>

<script>
const quill = new Quill('#quill-editor', { theme: 'snow', modules: { toolbar: QUILL_TOOLBAR } });
registerImageHandler(quill);
let editorMode = 'visual';
function setEditorMode(mode) {
  if (mode === 'html') {
    document.getElementById('raw-html').value = quill.root.innerHTML;
    document.getElementById('editor-visual').style.display = 'none';
    document.getElementById('editor-html').style.display   = 'block';
    document.getElementById('emode-visual').classList.remove('active');
    document.getElementById('emode-html').classList.add('active');
  } else {
    quill.root.innerHTML = document.getElementById('raw-html').value;
    document.getElementById('editor-html').style.display   = 'none';
    document.getElementById('editor-visual').style.display = 'block';
    document.getElementById('emode-html').classList.remove('active');
    document.getElementById('emode-visual').classList.add('active');
    quill.update();
  }
  editorMode = mode;
}
document.querySelector('form').addEventListener('submit', () => {
  document.getElementById('desc-larga').value =
    editorMode === 'html' ? document.getElementById('raw-html').value : quill.root.innerHTML;
});
// Sync preview when URL filled externally
document.getElementById('campo-imagen-url').addEventListener('input', function() {
  const p = document.getElementById('img-preview-portafolio');
  p.src = this.value; p.style.display = this.value ? 'block' : 'none';
});
// Reset form state when page is loaded from bfcache (Firefox/Safari)
window.addEventListener('pageshow', function(e) {
  if (e.persisted) {
    const campo  = document.getElementById('campo-imagen-url');
    const preview = document.getElementById('img-preview-portafolio');
    campo.value          = '';
    preview.src          = '';
    preview.style.display = 'none';
  }
});
</script>
