<?php use Core\Security; ?>

<div class="page-header">
  <h2>&#x2709;&#xfe0f; Mensaje #<?= (int)$mensaje['id'] ?></h2>
  <a href="/admin/mensajes" class="btn btn-secondary">&#8592; Volver</a>
</div>

<div class="card" style="margin-bottom:1.5rem">
  <div class="card-row">
    <span class="card-label">De:</span>
    <span>
      <?= Security::escape($mensaje['nombre']) ?>
      &lt;<?= Security::escape($mensaje['correo']) ?>&gt;
    </span>
  </div>
  <?php if (!empty($mensaje['telefono'])): ?>
  <div class="card-row">
    <span class="card-label">Tel&eacute;fono:</span>
    <span><?= Security::escape($mensaje['telefono']) ?></span>
  </div>
  <?php endif; ?>
  <?php if (!empty($mensaje['pais'])): ?>
  <div class="card-row">
    <span class="card-label">Pa&iacute;s:</span>
    <span><?= Security::escape($mensaje['pais']) ?></span>
  </div>
  <?php endif; ?>
  <div class="card-row">
    <span class="card-label">Asunto:</span>
    <span><?= Security::escape($mensaje['asunto'] ?? 'Sin asunto') ?></span>
  </div>
  <div class="card-row">
    <span class="card-label">Fecha:</span>
    <span><?= Security::escape($mensaje['fecha']) ?></span>
  </div>
  <div class="card-row">
    <span class="card-label">IP:</span>
    <span><?= Security::escape($mensaje['ip_origen'] ?? '&mdash;') ?></span>
  </div>
  <div class="card-row">
    <span class="card-label">Estado:</span>
    <span>
      <?= $mensaje['leido'] ? '<span class="badge-read">Le&iacute;do</span>' : '<span class="badge-unread">Nuevo</span>' ?>
      <?= !empty($mensaje['respondido']) ? '&nbsp;<span class="badge-read">Respondido</span>' : '' ?>
    </span>
  </div>
  <hr style="border-color:#1e2d40;margin:1.5rem 0">
  <div class="card-message" style="line-height:1.8">
    <?= nl2br(Security::escape($mensaje['mensaje'])) ?>
  </div>
</div>

<!-- Reply form -->
<div class="card" style="margin-bottom:1.5rem">
  <h3 style="font-size:1rem;font-weight:700;margin:0 0 1.2rem;color:var(--cyan)">
    &#x21a9;&#xfe0f; Responder a <?= Security::escape($mensaje['nombre']) ?>
  </h3>
  <?php if (!empty($mensaje['respondido'])): ?>
    <div class="alert-success" style="margin-bottom:1rem">
      &#x2705; Ya enviaste una respuesta a este mensaje.
    </div>
  <?php endif; ?>
  <form id="reply-form">
    <?= Security::csrfField() ?>
    <div class="form-group" style="margin-bottom:1rem">
      <label style="display:block;font-size:.82rem;color:var(--muted);margin-bottom:.35rem">
        Para: <strong><?= Security::escape($mensaje['correo']) ?></strong>
      </label>
      <textarea id="reply-text" name="reply_text" class="form-control"
                rows="6" placeholder="Escribe tu respuesta aqui..."
                style="width:100%;background:#0f172a;border:1px solid #1e2d40;border-radius:8px;
                       color:#e2e8f0;padding:.75rem 1rem;resize:vertical;font-size:.93rem;
                       outline:none;transition:border-color .2s"
                required></textarea>
    </div>
    <button type="button" id="btn-send-reply" class="btn btn-primary">
      <i class="ri-send-plane-line"></i> Enviar respuesta
    </button>
  </form>
</div>

<!-- Delete -->
<form method="POST" action="/admin/mensajes/delete/<?= (int)$mensaje['id'] ?>"
      onsubmit="return confirmDelete(this, 'Eliminar este mensaje permanentemente?')">
  <?= Security::csrfField() ?>
  <button type="submit" class="btn btn-danger">
    <i class="ri-delete-bin-line"></i> Eliminar mensaje
  </button>
</form>

<script>
(function () {
  const csrf = document.querySelector('#reply-form [name="_csrf"]').value;

  document.getElementById('btn-send-reply').addEventListener('click', async function () {
    const text = document.getElementById('reply-text').value.trim();
    if (!text) {
      adminToast('warning', 'Escribe el cuerpo de la respuesta.');
      return;
    }

    Swal.fire({
      title: 'Enviando respuesta...',
      html: '<div style="color:#94a3b8">Conectando con el servidor de correo</div>',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    try {
      const fd = new FormData();
      fd.append('_csrf', csrf);
      fd.append('reply_text', text);

      const res  = await fetch('/admin/mensajes/reply/<?= (int)$mensaje['id'] ?>', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd
      });
      const data = await res.json();

      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Respuesta enviada',
          text: data.message,
          confirmButtonColor: '#00d4ff',
          background: '#111827',
          color: '#e2e8f0'
        });
        document.getElementById('reply-text').value = '';
        // Update replied badge
        setTimeout(() => location.reload(), 1800);
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error al enviar',
          text: data.message || 'Revisa la configuracion de email.',
          background: '#111827',
          color: '#e2e8f0'
        });
      }
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexion',
        text: String(err),
        background: '#111827',
        color: '#e2e8f0'
      });
    }
  });
})();
</script>
