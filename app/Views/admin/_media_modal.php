<!-- ============================================================
     Biblioteca de medios — Modal compartido (incluido en layout admin.php)
     Uso en Quill:  registerImageHandler(quillInstance)
     Uso en campo:  openMediaForField('input-id', 'preview-img-id')
     ============================================================ -->

<!-- ── Modal ─────────────────────────────────────────────────── -->
<div id="media-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.75);align-items:center;justify-content:center">
  <div style="background:#111827;border:1px solid #1e2d40;border-radius:14px;width:min(780px,96vw);max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.6)">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;border-bottom:1px solid #1e2d40;flex-shrink:0">
      <h3 style="margin:0;font-size:.95rem;color:#00d4ff;display:flex;align-items:center;gap:.45rem">
        <i class="ri-image-2-line"></i> Biblioteca de medios
      </h3>
      <button onclick="closeMediaModal()" style="background:none;border:none;color:#64748b;font-size:1.4rem;cursor:pointer;line-height:1;padding:0 .25rem" title="Cerrar">✕</button>
    </div>

    <!-- Tabs -->
    <div style="display:flex;border-bottom:1px solid #1e2d40;flex-shrink:0">
      <button class="mm-tab" id="mm-tab-upload" onclick="mmTab('upload')"
        style="flex:1;padding:.65rem;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;font-size:.84rem;transition:color .15s">
        <i class="ri-upload-cloud-2-line"></i> Subir nueva
      </button>
      <button class="mm-tab" id="mm-tab-repo" onclick="mmTab('repo')"
        style="flex:1;padding:.65rem;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;font-size:.84rem;transition:color .15s">
        <i class="ri-grid-line"></i> Repositorio
      </button>
    </div>

    <!-- Panel: subir -->
    <div id="mm-panel-upload" style="padding:1.4rem;display:flex;flex-direction:column;gap:1rem;overflow-y:auto">

      <!-- Driver selector -->
      <div style="display:flex;gap:.6rem">
        <label id="mm-drv-local" class="mm-driver-btn active" style="flex:1;display:flex;align-items:center;justify-content:center;gap:.5rem;border:1px solid #2a3f55;border-radius:8px;padding:.55rem .8rem;cursor:pointer;font-size:.85rem;transition:all .15s">
          <input type="radio" name="mm_driver" value="local" checked style="display:none">
          <i class="ri-hard-drive-2-line" style="font-size:1.1rem"></i> Local
        </label>
        <label id="mm-drv-imgbb" class="mm-driver-btn" style="flex:1;display:flex;align-items:center;justify-content:center;gap:.5rem;border:1px solid #2a3f55;border-radius:8px;padding:.55rem .8rem;cursor:pointer;font-size:.85rem;transition:all .15s">
          <input type="radio" name="mm_driver" value="imgbb" style="display:none">
          <i class="ri-cloud-line" style="font-size:1.1rem"></i> ImgBB
        </label>
      </div>
      <p id="mm-imgbb-note" style="display:none;font-size:.78rem;color:#64748b;margin:-.5rem 0 0;background:rgba(0,212,255,.05);border:1px solid rgba(0,212,255,.15);border-radius:6px;padding:.45rem .75rem">
        <i class="ri-information-line"></i> Requiere API key de ImgBB en Configuración → Imágenes.
      </p>

      <!-- Drop zone -->
      <label id="mm-drop-zone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;border:2px dashed #2a3f55;border-radius:10px;padding:2rem 1rem;cursor:pointer;color:#64748b;gap:.5rem;transition:border-color .2s,background .2s">
        <input type="file" id="mm-file-input" accept="image/*" style="display:none">
        <i class="ri-image-add-line" style="font-size:2.25rem;color:#00d4ff"></i>
        <span style="font-size:.9rem;font-weight:600;color:#cbd5e1">Haz clic o arrastra una imagen</span>
        <span style="font-size:.75rem">JPG · PNG · WEBP · GIF · SVG — máx 5 MB</span>
      </label>

      <!-- Preview -->
      <div id="mm-upload-preview" style="display:none;text-align:center">
        <img id="mm-preview-img" style="max-height:150px;border-radius:8px;border:1px solid #2a3f55">
        <p id="mm-preview-name" style="font-size:.78rem;color:#64748b;margin:.35rem 0 0"></p>
      </div>

      <!-- Progress -->
      <div id="mm-progress-wrap" style="display:none">
        <div style="height:4px;background:#1e2d40;border-radius:4px;overflow:hidden">
          <div id="mm-progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#00d4ff,#7c3aed);transition:width .3s"></div>
        </div>
        <p style="font-size:.78rem;color:#64748b;text-align:center;margin:.35rem 0 0">Subiendo…</p>
      </div>

      <!-- Error -->
      <div id="mm-upload-error" style="display:none;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.55rem .9rem;font-size:.85rem;color:#fca5a5"></div>

      <button id="mm-upload-btn" onclick="mmDoUpload()" disabled
        style="background:linear-gradient(135deg,#00d4ff,#7c3aed);border:none;color:#fff;padding:.65rem;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:600;opacity:.35;transition:opacity .15s">
        <i class="ri-upload-cloud-line"></i> Subir e insertar
      </button>
    </div>

    <!-- Panel: repositorio -->
    <div id="mm-panel-repo" style="display:none;flex-direction:column;overflow:hidden;flex:1;min-height:0">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;border-bottom:1px solid #1e2d40;flex-shrink:0">
        <span id="mm-repo-count" style="font-size:.8rem;color:#64748b">Cargando…</span>
        <button onclick="mmLoadRepo()" style="background:none;border:1px solid #2a3f55;color:#94a3b8;padding:.3rem .75rem;border-radius:6px;cursor:pointer;font-size:.78rem">
          <i class="ri-refresh-line"></i> Actualizar
        </button>
      </div>
      <div style="overflow-y:auto;padding:1rem;flex:1">
        <div id="mm-repo-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(108px,1fr));gap:.55rem"></div>
        <p id="mm-repo-empty" style="display:none;text-align:center;color:#475569;font-size:.85rem;padding:2.5rem 0">Sin imágenes subidas aún.</p>
      </div>
    </div>

  </div>
</div>

<style>
.mm-tab { color:#64748b }
.mm-tab.active { color:#00d4ff; border-bottom-color:#00d4ff !important }
.mm-driver-btn { color:#64748b }
.mm-driver-btn.active { border-color:#00d4ff !important; color:#00d4ff; background:rgba(0,212,255,.07) }
#mm-drop-zone:hover { border-color:#00d4ff; background:rgba(0,212,255,.04) }
</style>

<script>
/* ══════════════════════════════════════════════════════════════
   Media Library — lógica compartida
   ══════════════════════════════════════════════════════════════ */

/* ── modo: 'quill' = insertar en editor | 'field' = rellenar input ── */
let _mmMode         = 'quill';   // 'quill' | 'field'
let _mmQuill        = null;
let _mmFieldInput   = null;      // id del <input> destino
let _mmFieldPreview = null;      // id del <img> preview (opcional)

/* ── Abrir para Quill ────────────────────────────────────────── */
function openMediaModal(quillInstance) {
  _mmMode         = 'quill';
  _mmQuill        = quillInstance;
  _mmFieldInput   = null;
  _mmFieldPreview = null;
  _mmOpen();
}

/* ── Abrir para campo de imagen ──────────────────────────────── */
function openMediaForField(inputId, previewImgId) {
  _mmMode         = 'field';
  _mmQuill        = null;
  _mmFieldInput   = inputId;
  _mmFieldPreview = previewImgId || null;
  _mmOpen();
}

function _mmOpen() {
  // reset
  document.getElementById('mm-file-input').value = '';
  document.getElementById('mm-upload-preview').style.display = 'none';
  document.getElementById('mm-upload-error').style.display   = 'none';
  document.getElementById('mm-progress-wrap').style.display  = 'none';
  const btn = document.getElementById('mm-upload-btn');
  btn.disabled = true; btn.style.opacity = '.35';
  document.getElementById('media-modal').style.display = 'flex';
  mmTab('upload');
  mmSyncDriver();
}

function closeMediaModal() {
  document.getElementById('media-modal').style.display = 'none';
}
document.getElementById('media-modal').addEventListener('click', e => {
  if (e.target === document.getElementById('media-modal')) closeMediaModal();
});

/* ── Tabs ────────────────────────────────────────────────────── */
function mmTab(tab) {
  document.getElementById('mm-panel-upload').style.display = tab === 'upload' ? 'flex' : 'none';
  document.getElementById('mm-panel-repo').style.display   = tab === 'repo'   ? 'flex' : 'none';
  document.getElementById('mm-tab-upload').classList.toggle('active', tab === 'upload');
  document.getElementById('mm-tab-repo').classList.toggle('active',   tab === 'repo');
  if (tab === 'repo') mmLoadRepo();
}

/* ── Driver selector ─────────────────────────────────────────── */
function mmSyncDriver() {
  const radios = document.querySelectorAll('input[name="mm_driver"]');
  radios.forEach(r => {
    r.parentElement.classList.toggle('active', r.checked);
  });
  const isImgbb = document.querySelector('input[name="mm_driver"]:checked')?.value === 'imgbb';
  document.getElementById('mm-imgbb-note').style.display = isImgbb ? 'block' : 'none';
}
document.querySelectorAll('input[name="mm_driver"]').forEach(r => {
  r.addEventListener('change', mmSyncDriver);
});

/* ── File pick / drag & drop ─────────────────────────────────── */
const mmInput    = document.getElementById('mm-file-input');
const mmDropZone = document.getElementById('mm-drop-zone');
mmDropZone.addEventListener('click', () => mmInput.click());
mmDropZone.addEventListener('dragover',  e => { e.preventDefault(); mmDropZone.style.borderColor = '#00d4ff'; });
mmDropZone.addEventListener('dragleave', () => { mmDropZone.style.borderColor = '#2a3f55'; });
mmDropZone.addEventListener('drop', e => {
  e.preventDefault(); mmDropZone.style.borderColor = '#2a3f55';
  if (e.dataTransfer.files[0]) mmSetFile(e.dataTransfer.files[0]);
});
mmInput.addEventListener('change', () => { if (mmInput.files[0]) mmSetFile(mmInput.files[0]); });

function mmSetFile(file) {
  document.getElementById('mm-upload-error').style.display = 'none';
  document.getElementById('mm-preview-img').src = URL.createObjectURL(file);
  document.getElementById('mm-preview-name').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
  document.getElementById('mm-upload-preview').style.display = 'block';
  const btn = document.getElementById('mm-upload-btn');
  btn.disabled = false; btn.style.opacity = '1';
}

/* ── Upload ──────────────────────────────────────────────────── */
async function mmDoUpload() {
  const file = mmInput.files[0];
  if (!file) return;
  const driver = document.querySelector('input[name="mm_driver"]:checked')?.value || 'local';

  document.getElementById('mm-upload-error').style.display  = 'none';
  document.getElementById('mm-progress-wrap').style.display = 'block';
  document.getElementById('mm-upload-btn').disabled = true;
  document.getElementById('mm-progress-bar').style.width = '25%';

  const fd = new FormData();
  fd.append('file', file);
  fd.append('driver', driver);

  try {
    document.getElementById('mm-progress-bar').style.width = '60%';
    const res  = await fetch('/admin/media/upload', { method: 'POST', body: fd });
    document.getElementById('mm-progress-bar').style.width = '100%';
    const data = await res.json();
    if (data.success) {
      _mmInsert(data.url);
      closeMediaModal();
    } else {
      _mmShowErr(data.message || 'Error al subir imagen.');
    }
  } catch (err) {
    _mmShowErr('Error de red: ' + err.message);
  } finally {
    document.getElementById('mm-progress-wrap').style.display = 'none';
    document.getElementById('mm-upload-btn').disabled = false;
    document.getElementById('mm-progress-bar').style.width = '0%';
  }
}

function _mmShowErr(msg) {
  const el = document.getElementById('mm-upload-error');
  el.textContent = '⚠ ' + msg; el.style.display = 'block';
}

/* ── Insertar según modo ─────────────────────────────────────── */
function _mmInsert(url) {
  if (_mmMode === 'quill' && _mmQuill) {
    const range = _mmQuill.getSelection(true);
    _mmQuill.insertEmbed(range ? range.index : 0, 'image', url);
  } else if (_mmMode === 'field' && _mmFieldInput) {
    const inp = document.getElementById(_mmFieldInput);
    if (inp) inp.value = url;
    if (_mmFieldPreview) {
      const img = document.getElementById(_mmFieldPreview);
      if (img) { img.src = url; img.style.display = 'block'; }
    }
  }
}

/* ── Repositorio ─────────────────────────────────────────────── */
async function mmLoadRepo() {
  document.getElementById('mm-repo-count').textContent = 'Cargando…';
  document.getElementById('mm-repo-empty').style.display = 'none';
  document.getElementById('mm-repo-grid').innerHTML = '';
  try {
    const data = await (await fetch('/admin/media/list')).json();
    const grid = document.getElementById('mm-repo-grid');
    if (!data.success || !data.data.length) {
      document.getElementById('mm-repo-empty').style.display = 'block';
      document.getElementById('mm-repo-count').textContent   = '0 imágenes';
      return;
    }
    document.getElementById('mm-repo-count').textContent = data.data.length + ' imagen(es)';
    data.data.forEach(img => {
      const item = document.createElement('div');
      item.style.cssText = 'position:relative;cursor:pointer;border:2px solid #1e2d40;border-radius:8px;overflow:hidden;aspect-ratio:1;background:#0f172a;transition:border-color .15s';
      item.innerHTML = `
        <img src="${img.url}" alt="${img.filename||''}"
          style="width:100%;height:100%;object-fit:cover;display:block"
          loading="lazy"
          onerror="this.closest('div').style.display='none'">
        <div class="mm-repo-ov" style="position:absolute;inset:0;background:rgba(0,0,0,.55);opacity:0;transition:opacity .15s;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.2rem">
          <i class="ri-check-circle-line" style="color:#00d4ff;font-size:1.5rem"></i>
          <span style="font-size:.7rem;color:#e2e8f0">Usar</span>
        </div>
        <button onclick="mmDeleteRepo(event,${img.id},this.closest('div'))"
          style="display:none;position:absolute;top:3px;right:3px;background:rgba(239,68,68,.85);border:none;color:#fff;width:20px;height:20px;border-radius:4px;cursor:pointer;font-size:.7rem;line-height:1" title="Eliminar">✕</button>`;
      item.addEventListener('mouseenter', () => {
        item.style.borderColor = '#00d4ff';
        item.querySelector('.mm-repo-ov').style.opacity = '1';
        item.querySelector('button').style.display = 'block';
      });
      item.addEventListener('mouseleave', () => {
        item.style.borderColor = '#1e2d40';
        item.querySelector('.mm-repo-ov').style.opacity = '0';
        item.querySelector('button').style.display = 'none';
      });
      item.addEventListener('click', e => {
        if (e.target.tagName === 'BUTTON') return;
        _mmInsert(img.url); closeMediaModal();
      });
      grid.appendChild(item);
    });
  } catch { document.getElementById('mm-repo-count').textContent = 'Error al cargar.'; }
}

async function mmDeleteRepo(e, id, el) {
  e.stopPropagation();
  if (!confirm('¿Eliminar imagen del repositorio?')) return;
  const fd = new FormData();
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_csrf"]')?.value || '';
  fd.append('_csrf', csrf);
  const data = await (await fetch('/admin/media/delete/' + id, { method: 'POST', body: fd })).json();
  if (data.success) el.remove();
}

</script>
