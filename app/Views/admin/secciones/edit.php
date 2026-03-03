<?php use Core\Security; ?>
<?php
$tipo         = $seccion['tipo_seccion'] ?? 'otro';
$hasRichPanel = in_array($tipo, ['hero', 'sobre']); // editor grande en panel derecho
$hasContent   = !in_array($tipo, ['portafolio','tecnologias']) && !$hasRichPanel;
$items        = $items ?? [];

// Decodificar contenido: JSON {icono, texto} o HTML plano
$contenidoRaw  = $seccion['contenido'] ?? '';
$seccionIcono  = '';
$seccionTexto  = $contenidoRaw;
try {
    // NO strip_tags aquí: el JSON puede contener HTML formateado en el campo 'texto'
    $decoded = json_decode(trim($contenidoRaw), true, 4, JSON_THROW_ON_ERROR);
    if (is_array($decoded) && isset($decoded['texto'])) {
        $seccionIcono = $decoded['icono'] ?? '';
        $seccionTexto = $decoded['texto'];
    }
} catch (\Throwable) { /* contenido es HTML plano */ }



?>

<div class="page-header" style="margin-bottom:1.2rem">
  <h2><i class="ri-pencil-line"></i> <?= Security::escape($seccion['titulo']) ?></h2>
  <a href="/admin/secciones" class="btn btn-secondary"><i class="ri-arrow-left-line"></i> Volver</a>
</div>

<div class="section-edit-layout">

  <!-- ── Columna izquierda: metadatos ─────────────────────── -->
  <aside class="section-edit-meta">
    <div class="meta-panel">
      <h4><i class="ri-settings-3-line"></i> Configuración</h4>
      <form method="POST" action="/admin/secciones/update/<?= (int)$seccion['id'] ?>">
        <?= Security::csrfField() ?>

        <div class="form-group">
          <label>Título</label>
          <input type="text" name="titulo" value="<?= Security::escape($seccion['titulo']) ?>" required>
        </div>

        <div class="form-group">
          <label>Tipo de sección</label>
          <select name="tipo_seccion">
            <?php foreach (['hero','sobre','portafolio','tecnologias','servicios','contacto','blog','otro'] as $t): ?>
              <option value="<?= $t ?>" <?= $tipo===$t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Orden</label>
          <input type="number" name="orden" value="<?= (int)$seccion['orden'] ?>" min="0" max="255">
        </div>

        <div class="form-group form-check">
          <label>
            <input type="checkbox" name="visible" value="1" <?= $seccion['visible'] ? 'checked' : '' ?>>
            Visible en el sitio
          </label>
        </div>
        <div class="form-group form-check">
          <label>
            <input type="checkbox" name="modo_seguro" value="1" <?= $seccion['modo_seguro'] ? 'checked' : '' ?>>
            Ocultar en Modo Seguro
          </label>
        </div>

        <?php if ($hasRichPanel): ?>
          <!-- hero/sobre: icono preview + referencia al panel derecho -->
          <div class="form-group" style="background:rgba(0,212,255,.05);border:1px solid rgba(0,212,255,.18);border-radius:8px;padding:.75rem;font-size:.82rem;color:#94a3b8">
            <?php if ($seccionIcono): ?>
              <div style="font-size:2rem;color:var(--cyan);margin-bottom:.35rem">
                <i class="<?= Security::escape($seccionIcono) ?>"></i>
              </div>
            <?php endif; ?>
            <i class="ri-information-line"></i> Edita el contenido e icono en el panel de la derecha.
          </div>
          <input type="hidden" name="contenido" id="contenido-input">
        <?php elseif ($hasContent): ?>
          <div class="form-group">
            <button type="button" class="btn btn-sm btn-secondary" id="toggle-editor-btn"
                    style="width:100%;margin-bottom:.4rem;text-align:left"
                    onclick="toggleEditor()">
              <i class="ri-edit-box-line"></i> <span id="toggle-editor-label">Ver editor de texto</span>
              <i class="ri-arrow-down-s-line" id="toggle-editor-arrow" style="float:right;transition:transform .2s"></i>
            </button>
            <div id="quill-editor-wrap" style="display:none">
              <div class="editor-mode-bar" style="margin-bottom:0">
                <span style="font-size:.78rem;color:#64748b">Modo</span>
                <div class="editor-mode-btns">
                  <button type="button" class="emode-btn active" id="emode-visual" onclick="setEditorMode('visual')"><i class="ri-edit-2-line"></i> Visual</button>
                  <button type="button" class="emode-btn" id="emode-html" onclick="setEditorMode('html')"><i class="ri-code-line"></i> HTML</button>
                </div>
              </div>
              <div id="editor-visual"><div id="quill-editor" style="min-height:140px;background:#0f172a"></div></div>
              <div id="editor-html" style="display:none"><textarea id="raw-html" class="raw-html-editor" style="min-height:140px" placeholder="Pega o escribe HTML directamente..."></textarea></div>
            </div>
            <input type="hidden" name="contenido" id="contenido-input">
          </div>
        <?php elseif (!$hasRichPanel): ?>
          <input type="hidden" name="contenido" value="<?= Security::escape($seccion['contenido'] ?? '') ?>">
          <p style="font-size:.8rem;color:#475569;margin-top:.5rem">
            <i class="ri-information-line"></i> El contenido de esta sección se gestiona desde el panel de la derecha.
          </p>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.75rem">
          <i class="ri-save-line"></i> Guardar configuración
        </button>
      </form>
    </div>
  </aside>

  <!-- ── Columna derecha: panel de ítems ──────────────────── -->
  <main class="section-edit-content">

    <?php if ($tipo === 'hero' || $tipo === 'sobre'): ?>
      <!-- HERO / SOBRE: editor completo + selector de icono -->
      <div class="items-panel">
        <div class="items-panel-header">
          <h4><i class="ri-edit-2-line"></i> Contenido <?= $tipo === 'hero' ? '· Hero' : '· Sobre mí' ?></h4>
        </div>
        <div style="padding:1.25rem;display:flex;flex-direction:column;gap:1.2rem">

          <!-- Selector de icono -->
          <div>
            <?php
              $sipCurrent = $seccionIcono;
              require __DIR__ . '/_icon_picker_seccion.php';
            ?>
          </div>

          <!-- Editor Quill -->
          <div>
            <div class="editor-mode-bar" style="margin-top:.25rem">
              <span style="font-size:.78rem;color:#94a3b8"><i class="ri-file-text-line"></i> Texto / descripción</span>
              <div class="editor-mode-btns">
                <button type="button" class="emode-btn active" id="emode-rich-visual" onclick="setRichEditorMode('visual')"><i class="ri-edit-2-line"></i> Visual</button>
                <button type="button" class="emode-btn" id="emode-rich-html" onclick="setRichEditorMode('html')"><i class="ri-code-line"></i> HTML</button>
              </div>
            </div>
            <div id="rich-editor-visual"><div id="quill-rich-editor" style="min-height:220px;background:#0f172a"></div></div>
            <div id="rich-editor-html" style="display:none"><textarea id="raw-rich-html" class="raw-html-editor" style="min-height:220px" placeholder="Pega o escribe HTML directamente..."></textarea></div>
          </div>

          <!-- Guardar -->
          <div>
            <button type="button" id="save-rich-btn" class="btn btn-primary" style="width:100%">
              <i class="ri-save-line"></i> Guardar contenido
            </button>
          </div>
        </div>
      </div>

    <?php elseif ($tipo === 'portafolio'): ?>
      <!-- PORTAFOLIO -->
      <div class="items-panel">
        <div class="items-panel-header">
          <h4><i class="ri-image-2-line"></i> Proyectos <span class="count-badge"><?= count($items) ?></span></h4>
          <a href="/admin/portafolio/create" class="btn btn-sm btn-primary"><i class="ri-add-line"></i> Nuevo proyecto</a>
        </div>
        <div class="table-container" style="margin-top:0;border-radius:0;border:none">
          <table class="admin-table">
            <thead><tr><th>Img</th><th>Título</th><th>Categoría</th><th>Vis.</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $p): ?>
              <tr>
                <td>
                  <?php if ($p['imagen_url']): ?>
                    <img src="<?= Security::escape($p['imagen_url']) ?>" style="width:52px;height:36px;object-fit:cover;border-radius:4px">
                  <?php else: ?><i class="ri-image-line" style="color:#475569;font-size:1.2rem"></i><?php endif; ?>
                </td>
                <td><strong><?= Security::escape($p['titulo']) ?></strong><br>
                  <small style="color:#64748b"><?= Security::escape(mb_substr($p['descripcion_corta'] ?? '', 0, 50)) ?>…</small>
                </td>
                <td><span class="badge-type"><?= Security::escape($p['categoria']) ?></span></td>
                <td><?= $p['visible'] ? '<i class="ri-eye-line" style="color:#10b981"></i>' : '<i class="ri-eye-off-line" style="color:#475569"></i>' ?></td>
                <td class="actions">
                  <a href="/admin/portafolio/edit/<?= (int)$p['id'] ?>" class="btn btn-sm btn-secondary"><i class="ri-pencil-line"></i></a>
                  <form method="POST" action="/admin/portafolio/delete/<?= (int)$p['id'] ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                    <?= Security::csrfField() ?>
                    <button class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
              <tr><td colspan="5" class="empty">Sin proyectos. <a href="/admin/portafolio/create">Agregar</a></td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tipo === 'tecnologias'): ?>
      <!-- TECNOLOGÍAS -->
      <div class="items-panel">
        <div class="items-panel-header">
          <h4><i class="ri-cpu-line"></i> Tecnologías <span class="count-badge"><?= count($items) ?></span></h4>
          <a href="/admin/tecnologias/create" class="btn btn-sm btn-primary"><i class="ri-add-line"></i> Nueva</a>
        </div>
        <div class="table-container" style="margin-top:0;border-radius:0;border:none">
          <table class="admin-table">
            <thead><tr><th>Icono</th><th>Nombre</th><th>Nivel</th><th>Vis.</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $t): ?>
              <tr>
                <td style="font-size:1.5rem">
                  <?php if (($t['icono_tipo'] ?? '') === 'devicons'): ?>
                    <i class="<?= Security::escape($t['icono_valor'] ?? '') ?>"></i>
                  <?php elseif (!empty($t['icono_valor'])): ?>
                    <?= $t['icono_valor'] ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><strong><?= Security::escape($t['nombre']) ?></strong></td>
                <td>
                  <div style="display:flex;align-items:center;gap:.5rem">
                    <div style="width:70px;height:5px;background:#1e2d40;border-radius:3px">
                      <div style="width:<?= (int)$t['nivel'] ?>%;height:100%;background:var(--cyan);border-radius:3px"></div>
                    </div>
                    <span style="font-size:.78rem;color:#94a3b8"><?= (int)$t['nivel'] ?>%</span>
                  </div>
                </td>
                <td><?= ($t['visible'] ?? 1) ? '<i class="ri-eye-line" style="color:#10b981"></i>' : '<i class="ri-eye-off-line" style="color:#475569"></i>' ?></td>
                <td class="actions">
                  <a href="/admin/tecnologias/edit/<?= (int)$t['id'] ?>" class="btn btn-sm btn-secondary"><i class="ri-pencil-line"></i></a>
                  <form method="POST" action="/admin/tecnologias/delete/<?= (int)$t['id'] ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                    <?= Security::csrfField() ?>
                    <button class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
              <tr><td colspan="5" class="empty">Sin tecnologías. <a href="/admin/tecnologias/create">Agregar</a></td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tipo === 'servicios'): ?>
      <!-- SERVICIOS -->
      <div class="items-panel">
        <div class="items-panel-header">
          <h4><i class="ri-settings-3-line"></i> Servicios <span class="count-badge"><?= count($items) ?></span></h4>
          <a href="/admin/servicios/create" class="btn btn-sm btn-primary"><i class="ri-add-line"></i> Nuevo servicio</a>
        </div>
        <div class="table-container" style="margin-top:0;border-radius:0;border:none">
          <table class="admin-table">
            <thead><tr><th>Icono</th><th>Título</th><th>Descripción</th><th>Items</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $i => $sv):
                    $svKey = urlencode($sv['_id'] ?? (string)$i);
                ?>
              <tr>
                <td style="font-size:1.3rem;color:var(--cyan)">
                  <?php if (!empty($sv['icon']) && str_starts_with($sv['icon'], 'ri-')): ?>
                    <i class="<?= Security::escape($sv['icon']) ?>"></i>
                  <?php else: ?><?= Security::escape($sv['icon'] ?? '') ?><?php endif; ?>
                </td>
                <td><strong><?= Security::escape($sv['titulo'] ?? '') ?></strong></td>
                <td style="max-width:180px;font-size:.82rem;color:#94a3b8"><?= Security::escape(mb_substr($sv['desc'] ?? '', 0, 55)) ?>&hellip;</td>
                <td><span class="count-badge"><?= count($sv['items'] ?? []) ?></span></td>
                <td class="actions">
                  <a href="/admin/servicios/edit/<?= $svKey ?>" class="btn btn-sm btn-secondary"><i class="ri-pencil-line"></i></a>
                  <form method="POST" action="/admin/servicios/delete/<?= $svKey ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                    <?= Security::csrfField() ?>
                    <button class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
              <tr><td colspan="5" class="empty">Sin servicios. <a href="/admin/servicios/create">Agregar</a></td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php else: ?>
      <!-- CONTACTO / OTRO: vista previa del contenido guardado -->
      <div class="items-panel">
        <div class="items-panel-header">
          <h4><i class="ri-eye-line"></i> Vista previa del contenido</h4>
        </div>
        <div style="padding:1.25rem">
          <?php if (!empty($seccion['contenido'])): ?>
            <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:1.1rem;font-size:.88rem;color:#94a3b8;max-height:360px;overflow-y:auto;line-height:1.6">
              <?= $seccion['contenido'] ?>
            </div>
          <?php else: ?>
            <p style="color:#475569;font-size:.85rem">Sin contenido aún. Usa el editor de la izquierda para agregar.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php if ($hasRichPanel || $hasContent): ?>

<!-- ── Modal biblioteca de medios ──────────────────────────── -->
<div id="media-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.7);align-items:center;justify-content:center">
  <div style="background:#111827;border:1px solid #1e2d40;border-radius:14px;width:min(760px,95vw);max-height:90vh;display:flex;flex-direction:column;overflow:hidden">
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:1px solid #1e2d40">
      <h3 style="margin:0;font-size:.95rem;color:#00d4ff"><i class="ri-image-2-line"></i> Insertar imagen</h3>
      <button onclick="closeMediaModal()" style="background:none;border:none;color:#94a3b8;font-size:1.3rem;cursor:pointer;line-height:1">✕</button>
    </div>
    <!-- Tabs -->
    <div style="display:flex;border-bottom:1px solid #1e2d40">
      <button class="mm-tab active" id="mm-tab-upload" onclick="mmTab('upload')" style="flex:1;padding:.7rem;background:none;border:none;cursor:pointer;color:#00d4ff;border-bottom:2px solid #00d4ff;font-size:.85rem"><i class="ri-upload-2-line"></i> Subir nueva</button>
      <button class="mm-tab" id="mm-tab-repo"   onclick="mmTab('repo')"   style="flex:1;padding:.7rem;background:none;border:none;cursor:pointer;color:#64748b;border-bottom:2px solid transparent;font-size:.85rem"><i class="ri-grid-line"></i> Repositorio</button>
    </div>
    <!-- Panel upload -->
    <div id="mm-panel-upload" style="padding:1.4rem;display:flex;flex-direction:column;gap:1rem">
      <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;border:2px dashed #2a3f55;border-radius:10px;padding:2rem;cursor:pointer;color:#64748b;gap:.5rem;transition:border-color .2s" id="mm-drop-zone">
        <input type="file" id="mm-file-input" accept="image/*" style="display:none">
        <i class="ri-image-add-line" style="font-size:2rem;color:#00d4ff"></i>
        <span style="font-size:.9rem">Haz clic o arrastra una imagen aquí</span>
        <span style="font-size:.75rem;color:#475569">JPG, PNG, WEBP, GIF — máx. 5 MB</span>
      </label>
      <div id="mm-upload-preview" style="display:none;text-align:center">
        <img id="mm-preview-img" style="max-height:160px;border-radius:8px;border:1px solid #2a3f55">
        <p id="mm-preview-name" style="font-size:.8rem;color:#64748b;margin:.4rem 0 0"></p>
      </div>
      <div id="mm-upload-progress" style="display:none">
        <div style="height:4px;background:#1e2d40;border-radius:4px;overflow:hidden">
          <div id="mm-progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#00d4ff,#7c3aed);transition:width .3s"></div>
        </div>
        <p style="font-size:.8rem;color:#64748b;margin:.4rem 0 0;text-align:center">Subiendo…</p>
      </div>
      <div id="mm-upload-error" style="display:none;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.6rem .9rem;font-size:.85rem;color:#fca5a5"></div>
      <button id="mm-upload-btn" onclick="mmUpload()" disabled
        style="background:linear-gradient(135deg,#00d4ff,#7c3aed);border:none;color:#fff;padding:.65rem 1.4rem;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:600;opacity:.4">
        <i class="ri-upload-cloud-line"></i> Subir e insertar
      </button>
    </div>
    <!-- Panel repositorio -->
    <div id="mm-panel-repo" style="display:none;padding:1rem;overflow-y:auto;flex:1">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <span style="font-size:.8rem;color:#64748b" id="mm-repo-count">Cargando…</span>
        <button onclick="mmLoadRepo()" style="background:none;border:1px solid #2a3f55;color:#94a3b8;padding:.3rem .7rem;border-radius:6px;cursor:pointer;font-size:.78rem"><i class="ri-refresh-line"></i> Actualizar</button>
      </div>
      <div id="mm-repo-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.6rem"></div>
      <p id="mm-repo-empty" style="display:none;text-align:center;color:#475569;font-size:.85rem;padding:2rem">Sin imágenes subidas aún.</p>
    </div>
  </div>
</div>

<script>
const QUILL_TOOLBAR = [
  [{ header: [1, 2, 3, 4, false] }],
  [{ size: ['small', false, 'large', 'huge'] }],
  ['bold', 'italic', 'underline', 'strike'],
  [{ color: [] }, { background: [] }],
  [{ align: [] }],
  [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
  ['blockquote', 'code-block'],
  ['link', 'image', 'video'],
  ['clean']
];

// ── Media modal ───────────────────────────────────────────────
let _activeQuill = null;

function openMediaModal(quillInstance) {
  _activeQuill = quillInstance;
  document.getElementById('media-modal').style.display = 'flex';
  mmTab('upload');
  // Reset upload panel
  document.getElementById('mm-file-input').value = '';
  document.getElementById('mm-upload-preview').style.display = 'none';
  document.getElementById('mm-upload-error').style.display   = 'none';
  document.getElementById('mm-upload-progress').style.display = 'none';
  document.getElementById('mm-upload-btn').disabled  = true;
  document.getElementById('mm-upload-btn').style.opacity = '.4';
}

function closeMediaModal() {
  document.getElementById('media-modal').style.display = 'none';
  _activeQuill = null;
}

// Cerrar al hacer click fuera del contenido
document.getElementById('media-modal').addEventListener('click', function(e) {
  if (e.target === this) closeMediaModal();
});

function mmTab(tab) {
  ['upload','repo'].forEach(t => {
    document.getElementById('mm-panel-' + t).style.display = t === tab ? (t === 'repo' ? 'block' : 'flex') : 'none';
    const btn = document.getElementById('mm-tab-' + t);
    btn.style.color        = t === tab ? '#00d4ff' : '#64748b';
    btn.style.borderBottom = t === tab ? '2px solid #00d4ff' : '2px solid transparent';
  });
  if (tab === 'repo') mmLoadRepo();
}

// ── File pick / drag & drop ───────────────────────────────────
const mmFileInput = document.getElementById('mm-file-input');
const mmDropZone  = document.getElementById('mm-drop-zone');

mmDropZone.addEventListener('click', () => mmFileInput.click());
mmDropZone.addEventListener('dragover', e => { e.preventDefault(); mmDropZone.style.borderColor = '#00d4ff'; });
mmDropZone.addEventListener('dragleave', () => { mmDropZone.style.borderColor = '#2a3f55'; });
mmDropZone.addEventListener('drop', e => {
  e.preventDefault();
  mmDropZone.style.borderColor = '#2a3f55';
  if (e.dataTransfer.files[0]) mmSetFile(e.dataTransfer.files[0]);
});
mmFileInput.addEventListener('change', () => { if (mmFileInput.files[0]) mmSetFile(mmFileInput.files[0]); });

function mmSetFile(file) {
  document.getElementById('mm-upload-error').style.display = 'none';
  const preview = document.getElementById('mm-upload-preview');
  document.getElementById('mm-preview-img').src  = URL.createObjectURL(file);
  document.getElementById('mm-preview-name').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
  preview.style.display = 'block';
  document.getElementById('mm-upload-btn').disabled  = false;
  document.getElementById('mm-upload-btn').style.opacity = '1';
}

// ── Upload ────────────────────────────────────────────────────
async function mmUpload() {
  const file = mmFileInput.files[0];
  if (!file) return;
  document.getElementById('mm-upload-error').style.display   = 'none';
  document.getElementById('mm-upload-progress').style.display = 'flex';
  document.getElementById('mm-upload-progress').style.flexDirection = 'column';
  document.getElementById('mm-upload-btn').disabled = true;
  document.getElementById('mm-progress-bar').style.width = '30%';

  const fd = new FormData();
  fd.append('file', file);

  try {
    const res  = await fetch('/admin/media/upload', { method: 'POST', body: fd });
    document.getElementById('mm-progress-bar').style.width = '100%';
    const data = await res.json();
    if (data.success) {
      mmInsertImage(data.url);
      closeMediaModal();
    } else {
      showMmError(data.message || 'Error al subir.');
    }
  } catch (err) {
    showMmError('Error de red: ' + err.message);
  } finally {
    document.getElementById('mm-upload-progress').style.display = 'none';
    document.getElementById('mm-upload-btn').disabled = false;
    document.getElementById('mm-progress-bar').style.width = '0%';
  }
}

function showMmError(msg) {
  const el = document.getElementById('mm-upload-error');
  el.textContent = '⚠ ' + msg;
  el.style.display = 'block';
}

// ── Repositorio ───────────────────────────────────────────────
async function mmLoadRepo() {
  document.getElementById('mm-repo-count').textContent = 'Cargando…';
  document.getElementById('mm-repo-empty').style.display = 'none';
  document.getElementById('mm-repo-grid').innerHTML = '';
  try {
    const res  = await fetch('/admin/media/list');
    const data = await res.json();
    const grid = document.getElementById('mm-repo-grid');
    if (!data.success || !data.data.length) {
      document.getElementById('mm-repo-empty').style.display = 'block';
      document.getElementById('mm-repo-count').textContent = '0 imágenes';
      return;
    }
    document.getElementById('mm-repo-count').textContent = data.data.length + ' imagen(es)';
    data.data.forEach(img => {
      const item = document.createElement('div');
      item.style.cssText = 'position:relative;cursor:pointer;border:2px solid #1e2d40;border-radius:8px;overflow:hidden;aspect-ratio:1;background:#0f172a;transition:border-color .15s';
      item.innerHTML = `
        <img src="${img.url}" alt="${img.filename || ''}"
          style="width:100%;height:100%;object-fit:cover;display:block"
          onerror="this.parentElement.style.display='none'">
        <div class="mm-repo-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,.55);opacity:0;transition:opacity .15s;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.25rem">
          <i class="ri-check-line" style="color:#00d4ff;font-size:1.4rem"></i>
          <span style="font-size:.7rem;color:#e2e8f0">Insertar</span>
        </div>
        <button onclick="mmDeleteImg(event,${img.id},this.closest('div'))" title="Eliminar"
          style="position:absolute;top:3px;right:3px;background:rgba(239,68,68,.8);border:none;color:#fff;width:20px;height:20px;border-radius:4px;cursor:pointer;font-size:.75rem;line-height:1;display:none">✕</button>`;
      item.addEventListener('mouseenter', () => {
        item.style.borderColor = '#00d4ff';
        item.querySelector('.mm-repo-overlay').style.opacity = '1';
        item.querySelector('button').style.display = 'block';
      });
      item.addEventListener('mouseleave', () => {
        item.style.borderColor = '#1e2d40';
        item.querySelector('.mm-repo-overlay').style.opacity = '0';
        item.querySelector('button').style.display = 'none';
      });
      item.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') return;
        mmInsertImage(img.url);
        closeMediaModal();
      });
      grid.appendChild(item);
    });
  } catch(e) {
    document.getElementById('mm-repo-count').textContent = 'Error al cargar.';
  }
}

async function mmDeleteImg(e, id, el) {
  e.stopPropagation();
  if (!confirm('¿Eliminar esta imagen del repositorio?')) return;
  const fd = new FormData();
  fd.append('_csrf', document.querySelector('input[name="_csrf"]')?.value || '');
  const res  = await fetch('/admin/media/delete/' + id, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) el.remove();
}

function mmInsertImage(url) {
  if (!_activeQuill) return;
  const range = _activeQuill.getSelection(true);
  _activeQuill.insertEmbed(range ? range.index : 0, 'image', url);
}

// ── Registrar handler de imagen en instancia Quill ───────────
function registerImageHandler(quillInstance) {
  quillInstance.getModule('toolbar').addHandler('image', () => {
    openMediaModal(quillInstance);
  });
}
</script>
<?php endif; ?>

<?php if ($hasRichPanel): ?>
<script>
// ── Icon picker: sync selectedIcon via sipOnChange hook
window.sipOnChange = function(cls) { selectedIcon = cls; };
let selectedIcon = <?= json_encode($seccionIcono) ?>;

// ── Quill rich editor
const quillRich = new Quill('#quill-rich-editor', {
  theme: 'snow',
  modules: { toolbar: QUILL_TOOLBAR }
});
registerImageHandler(quillRich);
quillRich.root.innerHTML = <?= json_encode($seccionTexto, JSON_HEX_TAG) ?>;;

let richEditorMode = 'visual';
function setRichEditorMode(mode) {
  if (mode === 'html') {
    document.getElementById('raw-rich-html').value = quillRich.root.innerHTML;
    document.getElementById('rich-editor-visual').style.display = 'none';
    document.getElementById('rich-editor-html').style.display   = 'block';
    document.getElementById('emode-rich-visual').classList.remove('active');
    document.getElementById('emode-rich-html').classList.add('active');
  } else {
    quillRich.root.innerHTML = document.getElementById('raw-rich-html').value;
    document.getElementById('rich-editor-html').style.display   = 'none';
    document.getElementById('rich-editor-visual').style.display = 'block';
    document.getElementById('emode-rich-html').classList.remove('active');
    document.getElementById('emode-rich-visual').classList.add('active');
    quillRich.update();
  }
  richEditorMode = mode;
}

// ── Guardar: serializa icono + texto como JSON en el hidden input del form meta
document.getElementById('save-rich-btn').addEventListener('click', function() {
  const richHtml = richEditorMode === 'html'
    ? document.getElementById('raw-rich-html').value
    : quillRich.root.innerHTML;
  const payload = JSON.stringify({ icono: selectedIcon, texto: richHtml });
  document.getElementById('contenido-input').value = payload;
  document.querySelector('.section-edit-meta form').submit();
});
</script>
<?php endif; ?>

<?php if ($hasContent): ?>
<script>
const quill = new Quill('#quill-editor', {
  theme: 'snow',
  modules: { toolbar: QUILL_TOOLBAR }
});
registerImageHandler(quill);
quill.root.innerHTML = <?= json_encode($seccion['contenido'] ?? '', JSON_HEX_TAG) ?>;
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
function toggleEditor() {
  const wrap  = document.getElementById('quill-editor-wrap');
  const label = document.getElementById('toggle-editor-label');
  const arrow = document.getElementById('toggle-editor-arrow');
  const open  = wrap.style.display !== 'none';
  wrap.style.display  = open ? 'none' : 'block';
  label.textContent   = open ? 'Ver editor de texto' : 'Ocultar editor';
  arrow.style.transform = open ? '' : 'rotate(180deg)';
  // trigger quill resize
  if (!open) quill.update();
}
document.querySelector('.section-edit-meta form').addEventListener('submit', () => {
  document.getElementById('contenido-input').value =
    editorMode === 'html' ? document.getElementById('raw-html').value : quill.root.innerHTML;
});
</script>
<?php endif; ?>

<style>
.icon-picker {
  display: flex; flex-wrap: wrap; gap: .4rem;
  padding: .65rem;
  background: var(--bg3, #0f172a);
  border: 1px solid var(--border, #1e2d40);
  border-radius: 10px;
  max-height: 220px; overflow-y: auto;
}
.icon-option {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: .15rem; width: 64px; padding: .45rem .3rem;
  background: none; border: 1px solid var(--border, #1e2d40);
  border-radius: 8px; cursor: pointer; color: #94a3b8;
  font-size: .68rem; text-align: center;
  transition: border-color .15s, color .15s, background .15s;
}
.icon-option i { font-size: 1.25rem; line-height: 1; }
.icon-option:hover { border-color: rgba(0,212,255,.5); color: #e2e8f0; background: rgba(0,212,255,.06); }
.icon-option.selected { border-color: #00d4ff; color: #00d4ff; background: rgba(0,212,255,.1); }
.section-edit-layout {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 1.4rem;
  align-items: start;
}
@media (max-width: 900px) { .section-edit-layout { grid-template-columns: 1fr; } }
.section-edit-meta .meta-panel {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.3rem;
  position: sticky; top: 74px;
}
.meta-panel h4 {
  font-size: .92rem; font-weight: 700; margin-bottom: 1.1rem;
  display: flex; align-items: center; gap: .45rem; color: var(--cyan);
}
.items-panel {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}
.items-panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .9rem 1.2rem;
  border-bottom: 1px solid var(--border);
  background: var(--bg3);
  flex-wrap: wrap; gap: .5rem;
}
.items-panel-header h4 {
  font-size: .92rem; font-weight: 700;
  display: flex; align-items: center; gap: .45rem; margin: 0;
}
</style>
