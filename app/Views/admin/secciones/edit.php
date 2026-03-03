?<?php use Core\Security; ?>
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

// Bloques hero: orden y visibilidad (solo para tipo hero)
$heroBloquesDef = [
  ['id'=>'badge',      'label'=>'Badge / eslogan',   'icon'=>'ri-price-tag-3-line', 'visible'=>true, 'orden'=>0],
  ['id'=>'icon',       'label'=>'Icono decorativo',  'icon'=>'ri-emotion-line',      'visible'=>true, 'orden'=>1],
  ['id'=>'title',      'label'=>'Nombre del sitio',  'icon'=>'ri-text',              'visible'=>true, 'orden'=>2],
  ['id'=>'typewriter', 'label'=>'Efecto typewriter', 'icon'=>'ri-cursor-line',       'visible'=>true, 'orden'=>3],
  ['id'=>'text',       'label'=>'Texto descriptivo', 'icon'=>'ri-file-text-line',    'visible'=>true, 'orden'=>4],
  ['id'=>'actions',    'label'=>'Botones CTA',       'icon'=>'ri-mouse-line',        'visible'=>true, 'orden'=>5],
];
if ($tipo === 'hero') {
  $savedMap = [];
  $savedBlo = (isset($decoded) && is_array($decoded) && !empty($decoded['bloques'])) ? $decoded['bloques'] : [];
  foreach ($savedBlo as $b) { if (!empty($b['id'])) $savedMap[$b['id']] = $b; }
  foreach ($heroBloquesDef as &$b) {
    if (isset($savedMap[$b['id']])) {
      $b['visible'] = (bool)($savedMap[$b['id']]['visible'] ?? true);
      $b['orden']   = (int)($savedMap[$b['id']]['orden']   ?? $b['orden']);
    }
  }
  unset($b);
  usort($heroBloquesDef, fn($a, $b) => $a['orden'] <=> $b['orden']);
}
$heroBloques = $heroBloquesDef;

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

          <?php if ($tipo === 'hero'): ?>
          <!-- Bloque manager: orden + visibilidad -->
          <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.55rem">
              <span style="font-size:.83rem;font-weight:600;color:#94a3b8"><i class="ri-layout-row-line"></i> Bloques del Hero</span>
              <span style="font-size:.72rem;color:#475569"><i class="ri-drag-move-2-line"></i> arrastra para reordenar</span>
            </div>
            <div id="hb-list" style="display:flex;flex-direction:column;gap:.35rem">
              <?php foreach ($heroBloques as $hb): ?>
              <div class="hb-row"
                data-id="<?= $hb['id'] ?>"
                data-visible="<?= $hb['visible'] ? '1' : '0' ?>"
                draggable="true"
                style="display:flex;align-items:center;gap:.6rem;
                       background:#0f172a;
                       border:1px solid <?= $hb['visible'] ? '#2a3f55' : '#1a2332' ?>;
                       border-radius:8px;padding:.5rem .75rem;
                       cursor:grab;user-select:none;transition:all .15s">
                <i class="ri-drag-move-2-line" style="color:#334155;flex-shrink:0;font-size:.9rem"></i>
                <i class="<?= Security::escape($hb['icon']) ?>" style="color:#475569;flex-shrink:0;font-size:.88rem"></i>
                <span style="flex:1;font-size:.84rem;color:<?= $hb['visible'] ? '#e2e8f0' : '#475569' ?>"><?= Security::escape($hb['label']) ?></span>
                <button type="button" class="hb-toggle" title="Mostrar / Ocultar"
                  style="background:none;
                         border:1px solid <?= $hb['visible'] ? 'rgba(0,212,255,.4)' : '#1e2d40' ?>;
                         border-radius:6px;cursor:pointer;padding:.2rem .5rem;
                         color:<?= $hb['visible'] ? '#00d4ff' : '#334155' ?>;
                         font-size:.85rem;flex-shrink:0;transition:all .15s">
                  <i class="<?= $hb['visible'] ? 'ri-eye-line' : 'ri-eye-off-line' ?>"></i>
                </button>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

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

// ── Hero block drag-reorder + eye toggle
const _hbList = document.getElementById('hb-list');
if (_hbList) {
  let _hbDrag = null;
  _hbList.addEventListener('dragstart', e => {
    _hbDrag = e.target.closest('.hb-row');
    if (_hbDrag) { _hbDrag.style.opacity = '.35'; _hbDrag.style.cursor = 'grabbing'; }
  });
  _hbList.addEventListener('dragend', () => {
    if (_hbDrag) { _hbDrag.style.opacity = '1'; _hbDrag.style.cursor = 'grab'; _hbDrag = null; }
  });
  _hbList.addEventListener('dragover', e => {
    e.preventDefault();
    const target = e.target.closest('.hb-row');
    if (!target || target === _hbDrag) return;
    const r = target.getBoundingClientRect();
    _hbList.insertBefore(_hbDrag, e.clientY < r.top + r.height / 2 ? target : target.nextSibling);
  });
  _hbList.addEventListener('click', e => {
    const btn = e.target.closest('.hb-toggle');
    if (!btn) return;
    const row = btn.closest('.hb-row');
    const on  = row.dataset.visible === '1';
    row.dataset.visible                   = on ? '0' : '1';
    btn.querySelector('i').className      = on ? 'ri-eye-off-line' : 'ri-eye-line';
    btn.style.color                       = on ? '#334155' : '#00d4ff';
    btn.style.borderColor                 = on ? '#1e2d40' : 'rgba(0,212,255,.4)';
    row.style.borderColor                 = on ? '#1a2332' : '#2a3f55';
    row.querySelector('span').style.color = on ? '#475569' : '#e2e8f0';
  });
}

// ── Guardar: serializa icono + texto (+ bloques si hero) como JSON
document.getElementById('save-rich-btn').addEventListener('click', function() {
  const richHtml = richEditorMode === 'html'
    ? document.getElementById('raw-rich-html').value
    : quillRich.root.innerHTML;
  const payload = { icono: selectedIcon, texto: richHtml };
  if (_hbList) {
    payload.bloques = Array.from(_hbList.querySelectorAll('.hb-row')).map((row, i) => ({
      id:      row.dataset.id,
      visible: row.dataset.visible === '1',
      orden:   i
    }));
  }
  document.getElementById('contenido-input').value = JSON.stringify(payload);
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
