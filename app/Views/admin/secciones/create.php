<?php use Core\Security; ?>

<div class="page-header">
  <h2>📄 Nueva Sección</h2>
  <a href="/admin/secciones" class="btn btn-secondary">← Volver</a>
</div>

<form method="POST" action="/admin/secciones/store" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="form-row">
    <div class="form-group">
      <label>Título *</label>
      <input type="text" name="titulo" required maxlength="200">
    </div>
    <div class="form-group">
      <label>Tipo de sección</label>
      <select name="tipo_seccion">
        <?php foreach (['hero','sobre','portafolio','servicios','contacto','blog','otro'] as $t): ?>
          <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-group">
    <div class="editor-mode-bar">
      <span style="font-size:.82rem;color:#94a3b8;font-weight:600">Contenido (HTML enriquecido)</span>
      <div class="editor-mode-btns">
        <button type="button" class="emode-btn active" id="emode-visual" onclick="setEditorMode('visual')"><i class="ri-edit-2-line"></i> Visual</button>
        <button type="button" class="emode-btn" id="emode-html" onclick="setEditorMode('html')"><i class="ri-code-line"></i> HTML</button>
      </div>
    </div>
    <div id="editor-visual"><div id="quill-editor" style="height:300px"></div></div>
    <div id="editor-html" style="display:none"><textarea id="raw-html" class="raw-html-editor" style="min-height:300px" placeholder="Pega o escribe HTML directamente..."></textarea></div>
    <input type="hidden" name="contenido" id="contenido-input">
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
      <input type="number" name="orden" value="0" min="0" max="255">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Guardar Sección</button>
</form>

<script>
const quill = new Quill('#quill-editor', { theme: 'snow' });
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
  document.getElementById('contenido-input').value =
    editorMode === 'html' ? document.getElementById('raw-html').value : quill.root.innerHTML;
});
</script>
