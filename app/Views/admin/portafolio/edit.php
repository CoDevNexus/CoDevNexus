<?php use Core\Security; ?>

<div class="page-header">
  <h2>✏️ Editar Proyecto</h2>
  <a href="/admin/portafolio" class="btn btn-secondary">← Volver</a>
</div>

<form method="POST" action="/admin/portafolio/update/<?= (int)$proyecto['id'] ?>" enctype="multipart/form-data" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="form-row">
    <div class="form-group">
      <label>Título *</label>
      <input type="text" name="titulo" required maxlength="200" value="<?= Security::escape($proyecto['titulo']) ?>">
    </div>
    <div class="form-group">
      <label>Categoría</label>
      <select name="categoria">
        <?php foreach (['redes','software','iot','automatizacion','web','otro'] as $c): ?>
          <option value="<?= $c ?>" <?= $proyecto['categoria']===$c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-group">
    <label>Descripción corta</label>
    <input type="text" name="descripcion_corta" maxlength="300" value="<?= Security::escape($proyecto['descripcion_corta'] ?? '') ?>">
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

  <!-- Imagen actual -->
  <?php if ($proyecto['imagen_url']): ?>
  <div class="form-group">
    <label>Imagen actual</label>
    <img src="<?= Security::escape($proyecto['imagen_url']) ?>" alt="" style="max-width:200px;border-radius:8px;display:block;margin-top:.5rem">
  </div>
  <?php endif; ?>

  <!-- Upload Dual -->
  <div class="form-group">
    <label>Reemplazar imagen (opcional)</label>
    <div class="image-source-toggle">
      <label class="radio-btn">
        <input type="radio" name="imagen_source" value="local" checked onchange="toggleSource(this.value)"> 📁 Local
      </label>
      <label class="radio-btn">
        <input type="radio" name="imagen_source" value="imgbb" onchange="toggleSource(this.value)"> ☁️ ImgBB
      </label>
    </div>
    <div id="source-local"><input type="file" name="imagen" accept="image/*"></div>
    <div id="source-imgbb" style="display:none">
      <input type="file" name="imagen" accept="image/*">
      <p style="color:#64748b;font-size:.85rem;margin:.5rem 0">— o pega una URL directamente —</p>
      <input type="text" name="imagen_url_externa" placeholder="https://i.ibb.co/...">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Enlace Demo</label>
      <input type="url" name="enlace_demo" value="<?= Security::escape($proyecto['enlace_demo'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Enlace Repositorio</label>
      <input type="url" name="enlace_repo" value="<?= Security::escape($proyecto['enlace_repo'] ?? '') ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group form-check">
      <label><input type="checkbox" name="visible" value="1" <?= $proyecto['visible'] ? 'checked' : '' ?>> Visible</label>
    </div>
    <div class="form-group form-check">
      <label><input type="checkbox" name="modo_seguro" value="1" <?= $proyecto['modo_seguro'] ? 'checked' : '' ?>> Ocultar en Modo Seguro</label>
    </div>
    <div class="form-group">
      <label>Orden</label>
      <input type="number" name="orden" value="<?= (int)$proyecto['orden'] ?>" min="0">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Actualizar Proyecto</button>
</form>

<script>
const quill = new Quill('#quill-editor', { theme: 'snow' });
quill.root.innerHTML = <?= json_encode($proyecto['descripcion_larga'] ?? '', JSON_HEX_TAG) ?>;
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
function toggleSource(val) {
  document.getElementById('source-local').style.display = val === 'local' ? '' : 'none';
  document.getElementById('source-imgbb').style.display = val === 'imgbb' ? '' : 'none';
}
</script>
