<?php use Core\Security; ?>

<?php
$activeTab = Security::sanitize($_GET['tab'] ?? 'empresa');
$saved     = isset($_GET['saved']);
$error     = $_GET['error'] ?? null;
$tabs = [
  'empresa'  => '🏢 Empresa',
  'logos'    => '🖼 Logos',
  'tema'     => '🎨 Tema',
  'smtp'     => '📧 Email',
  'apis'     => '🔌 APIs',
  'sociales' => '🌐 Sociales',
  'sistema'  => '🔒 Sistema',
];
?>

<div class="page-header">
  <h2>⚙️ Configuración</h2>
</div>

<?php if ($saved): ?>
  <div class="alert-success">✅ Configuración guardada correctamente.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert-error">⚠ <?= $error === 'wrongpassword' ? 'Contraseña actual incorrecta.' : 'Error al guardar.' ?></div>
<?php endif; ?>

<!-- Tabs nav -->
<div class="tabs-nav">
  <?php foreach ($tabs as $key => $label): ?>
    <a href="/admin/configuracion?tab=<?= $key ?>"
       class="tab-item <?= $activeTab === $key ? 'active' : '' ?>">
      <?= $label ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="tab-content">

<!-- ══ PESTAÑA 1: EMPRESA ══════════════════════════════════════ -->
<?php if ($activeTab === 'empresa'): ?>
<form method="POST" action="/admin/configuracion/update" class="admin-form">
  <?= Security::csrfField() ?>
  <input type="hidden" name="tab" value="empresa">
  <div class="form-row">
    <div class="form-group">
      <label>Nombre del sitio</label>
      <input type="text" name="site_name" value="<?= Security::escape($cfg['site_name'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Tagline / Slogan</label>
      <input type="text" name="site_tagline" value="<?= Security::escape($cfg['site_tagline'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Correo de contacto</label>
      <input type="email" name="site_email" value="<?= Security::escape($cfg['site_email'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Teléfono</label>
      <input type="text" name="site_phone" value="<?= Security::escape($cfg['site_phone'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Dirección / Ciudad</label>
      <input type="text" name="site_address" value="<?= Security::escape($cfg['site_address'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Texto del Footer</label>
      <input type="text" name="site_footer_text" value="<?= Security::escape($cfg['site_footer_text'] ?? '') ?>">
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Guardar Empresa</button>
</form>

<!-- ══ PESTAÑA 2: LOGOS ════════════════════════════════════════ -->
<?php elseif ($activeTab === 'logos'): ?>
<div class="logos-grid">
  <?php
  $logoFields = [
    'logo_principal' => 'Logo Principal',
    'logo_admin'     => 'Logo Admin Panel',
    'favicon'        => 'Favicon',
  ];
  foreach ($logoFields as $key => $label):
    $current = $cfg[$key] ?? '';
  ?>
  <div class="logo-card">
    <h4><?= $label ?></h4>
    <?php if ($current): ?>
      <img src="<?= Security::escape($current) ?>" alt="<?= $label ?>"
           style="max-height:80px;max-width:200px;border-radius:8px;background:#0b0f19;padding:8px">
      <p style="color:#64748b;font-size:.8rem;word-break:break-all"><?= Security::escape($current) ?></p>
    <?php else: ?>
      <p style="color:#64748b">Sin imagen configurada</p>
    <?php endif; ?>
    <form class="logo-upload-form" data-key="<?= $key ?>">
      <?= Security::csrfField() ?>
      <input type="hidden" name="logo_key" value="<?= $key ?>">
      <input type="file" name="logo" accept="image/*" required>
      <button type="button" class="btn btn-sm btn-primary" onclick="uploadLogo(this.form)">Subir</button>
      <span class="upload-status"></span>
    </form>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══ PESTAÑA 3: TEMA ════════════════════════════════════════== -->
<?php elseif ($activeTab === 'tema'): ?>
<form method="POST" action="/admin/configuracion/update" class="admin-form">
  <?= Security::csrfField() ?>
  <input type="hidden" name="tab" value="tema">
  <div class="color-grid">
    <?php
    $colors = [
      'theme_color_cyan'   => 'Color Cian (activos/glow)',
      'theme_color_purple' => 'Color Morado (dev)',
      'theme_color_orange' => 'Color Naranja (nexus)',
      'theme_color_bg'     => 'Color Fondo',
      'theme_color_text'   => 'Color Texto',
    ];
    foreach ($colors as $k => $label):
      $val = $cfg[$k] ?? '#000000';
    ?>
    <div class="color-row">
      <label><?= $label ?></label>
      <input type="color" name="<?= $k ?>" value="<?= Security::escape($val) ?>"
             oninput="previewColor('<?= $k ?>', this.value)">
      <input type="text" class="color-hex" value="<?= Security::escape($val) ?>"
             oninput="syncColorPicker('<?= $k ?>', this.value)"
             pattern="#[0-9a-fA-F]{6}">
      <span class="color-preview" id="prev-<?= $k ?>" style="background:<?= Security::escape($val) ?>"></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="form-row" style="margin-top:1.5rem">
    <div class="form-group">
      <label>Intensidad Glow (0-100)</label>
      <input type="range" name="theme_glow_intensity" min="0" max="100"
             value="<?= (int)($cfg['theme_glow_intensity'] ?? 70) ?>">
      <span id="glow-val"><?= (int)($cfg['theme_glow_intensity'] ?? 70) ?></span>
    </div>
    <div class="form-group form-check" style="align-self:end">
      <label>
        <input type="checkbox" name="theme_particles" value="1"
               <?= ($cfg['theme_particles'] ?? '0') === '1' ? 'checked' : '' ?>>
        Partículas visibles en Hero
      </label>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Estilo de animación de partículas</label>
      <select name="particles_style">
        <?php
        $styles = [
          'network' => '🕸 Red de nodos (interactivo)',
          'bubbles' => '🫧 Burbujas flotantes',
          'snow'    => '❄️ Nieve / partículas cayendo',
          'stars'   => '✨ Campo de estrellas',
        ];
        $currentStyle = $cfg['particles_style'] ?? 'network';
        foreach ($styles as $val => $lbl): ?>
          <option value="<?= $val ?>" <?= $currentStyle === $val ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
      <small style="color:#64748b">Solo tiene efecto cuando las partículas están activadas.</small>
    </div>
  </div>

  <!-- ── Typewriter (texto animado Hero) ───────────────────── -->
  <div style="margin-top:1.75rem;padding-top:1.5rem;border-top:1px solid var(--border)">
    <h4 style="color:#e2e8f0;font-size:.9rem;margin:0 0 1rem;display:flex;align-items:center;gap:.45rem">
      <i class="ri-text-wrap"></i> Texto animado (Typewriter)
    </h4>
    <div class="form-row">
      <div class="form-group" style="flex:1 1 100%">
        <label>Frases animadas <small>(separadas por coma)</small></label>
        <input type="text" name="typewriter_lines"
               value="<?= Security::escape($cfg['typewriter_lines'] ?? 'Desarrollador Web,IoT,Redes') ?>"
               placeholder="Desarrollador Web,IoT,Redes,DevOps">
        <small style="color:#64748b">Cada texto separado por coma aparece y desaparece en loop en el Hero.</small>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Color del texto animado</label>
        <div class="color-row">
          <input type="color" name="typewriter_color"
                 value="<?= Security::escape($cfg['typewriter_color'] ?? '#00d4ff') ?>"
                 oninput="previewColor('typewriter_color', this.value)">
          <input type="text" class="color-hex"
                 value="<?= Security::escape($cfg['typewriter_color'] ?? '#00d4ff') ?>"
                 oninput="syncColorPicker('typewriter_color', this.value)"
                 pattern="#[0-9a-fA-F]{6}">
          <span class="color-preview" id="prev-typewriter_color"
                style="background:<?= Security::escape($cfg['typewriter_color'] ?? '#00d4ff') ?>"></span>
        </div>
      </div>
      <div class="form-group">
        <label>Tamaño del texto (rem)</label>
        <input type="number" name="typewriter_size" min="0.8" max="4" step="0.05"
               value="<?= Security::escape($cfg['typewriter_size'] ?? '1.25') ?>">
        <small style="color:#64748b">Valor en rem. Por defecto 1.25.</small>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Velocidad de escritura (ms / carácter)</label>
        <input type="range" name="typewriter_speed" min="30" max="200" step="5"
               value="<?= (int)($cfg['typewriter_speed'] ?? 80) ?>"
               oninput="this.nextElementSibling.textContent=this.value+'ms'">
        <span style="color:#00d4ff;font-size:.82rem"><?= (int)($cfg['typewriter_speed'] ?? 80) ?>ms</span>
        <small style="color:#64748b">Menor = más rápido.</small>
      </div>
      <div class="form-group">
        <label>Pausa entre frases (ms)</label>
        <input type="range" name="typewriter_pause" min="500" max="5000" step="100"
               value="<?= (int)($cfg['typewriter_pause'] ?? 1800) ?>"
               oninput="this.nextElementSibling.textContent=this.value+'ms'">
        <span style="color:#00d4ff;font-size:.82rem"><?= (int)($cfg['typewriter_pause'] ?? 1800) ?>ms</span>
        <small style="color:#64748b">Tiempo que se muestra la frase completa antes de borrarse.</small>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Guardar Tema</button>
</form>

<!-- ══ PESTAÑA 4: EMAIL ═══════════════════════════════════════== -->
<?php elseif ($activeTab === 'smtp'): ?>
<?php $mailDriver = $cfg['mail_driver'] ?? 'smtp'; ?>

<form method="POST" action="/admin/configuracion/update" class="admin-form" id="email-form">
  <?= Security::csrfField() ?>
  <input type="hidden" name="tab" value="smtp">

  <!-- ── Selector de driver ─────────────────────────────────── -->
  <div class="mail-driver-selector">
    <label class="driver-card <?= $mailDriver === 'gmail' ? 'active' : '' ?>" id="card-gmail">
      <input type="radio" name="mail_driver" value="gmail" <?= $mailDriver === 'gmail' ? 'checked' : '' ?> onchange="switchMailDriver(this.value)">
      <span class="driver-icon">📬</span>
      <span class="driver-title">Gmail</span>
      <span class="driver-desc">Cuenta Gmail con contraseña de aplicación</span>
    </label>
    <label class="driver-card <?= $mailDriver === 'smtp' ? 'active' : '' ?>" id="card-smtp">
      <input type="radio" name="mail_driver" value="smtp" <?= $mailDriver === 'smtp' ? 'checked' : '' ?> onchange="switchMailDriver(this.value)">
      <span class="driver-icon">🔧</span>
      <span class="driver-title">SMTP personalizado</span>
      <span class="driver-desc">Cualquier servidor SMTP (Mailgun, SendGrid, hosting, etc.)</span>
    </label>
  </div>

  <!-- ── Sección Gmail ───────────────────────────────────────── -->
  <div id="section-gmail" class="mail-section" style="<?= $mailDriver !== 'gmail' ? 'display:none' : '' ?>">
    <div class="section-notice" style="background:#1e2d3d;border-left:3px solid #00d4ff;padding:.75rem 1rem;margin-bottom:1rem;border-radius:0 6px 6px 0;font-size:.85rem;color:#94a3b8">
      💡 Usa una <strong style="color:#e2e8f0">Contraseña de aplicación</strong> de Google, no tu contraseña normal.<br>
      En tu cuenta Google → Seguridad → Verificación en 2 pasos → <em>Contraseñas de aplicaciones</em>.
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Cuenta Gmail</label>
        <input type="email" name="gmail_user" value="<?= Security::escape($cfg['gmail_user'] ?? '') ?>" placeholder="tucuenta@gmail.com" autocomplete="off">
      </div>
      <div class="form-group">
        <label>Contraseña de aplicación <small>(dejar vacío para no cambiar)</small></label>
        <input type="password" name="gmail_app_password_new" placeholder="<?= !empty($cfg['gmail_app_password']) ? '••••••••' : 'Sin configurar' ?>" autocomplete="new-password">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Nombre remitente</label>
        <input type="text" name="gmail_from_name" value="<?= Security::escape($cfg['gmail_from_name'] ?? $cfg['smtp_from_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Copia al admin (BCC)</label>
        <input type="email" name="gmail_admin_copy" value="<?= Security::escape($cfg['gmail_admin_copy'] ?? $cfg['smtp_admin_copy'] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- ── Sección SMTP personalizado ─────────────────────────── -->
  <div id="section-smtp" class="mail-section" style="<?= $mailDriver !== 'smtp' ? 'display:none' : '' ?>">
    <div class="form-row">
      <div class="form-group">
        <label>Host SMTP</label>
        <input type="text" name="smtp_host" value="<?= Security::escape($cfg['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
      </div>
      <div class="form-group" style="flex:0 0 120px">
        <label>Puerto</label>
        <input type="number" name="smtp_port" value="<?= (int)($cfg['smtp_port'] ?? 587) ?>">
      </div>
      <div class="form-group" style="flex:0 0 200px">
        <label>Cifrado</label>
        <select name="smtp_encryption">
          <?php foreach (['tls','ssl','none'] as $e): ?>
            <option value="<?= $e ?>" <?= ($cfg['smtp_encryption'] ?? 'tls')===$e ? 'selected' : '' ?>><?= strtoupper($e) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Usuario SMTP</label>
        <input type="text" name="smtp_user" value="<?= Security::escape($cfg['smtp_user'] ?? '') ?>" autocomplete="off">
      </div>
      <div class="form-group">
        <label>Contraseña SMTP <small>(dejar vacío para no cambiar)</small></label>
        <input type="password" name="smtp_password_new" placeholder="<?= !empty($cfg['smtp_password']) ? '••••••••' : 'Sin configurar' ?>" autocomplete="new-password">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Email remitente</label>
        <input type="email" name="smtp_from_email" value="<?= Security::escape($cfg['smtp_from_email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Nombre remitente</label>
        <input type="text" name="smtp_from_name" value="<?= Security::escape($cfg['smtp_from_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Copia al admin (BCC)</label>
        <input type="email" name="smtp_admin_copy" value="<?= Security::escape($cfg['smtp_admin_copy'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Guardar Email</button>
    <button type="button" class="btn btn-secondary" onclick="testEmail()">📧 Enviar email de prueba</button>
    <span id="test-email-result" class="test-result"></span>
  </div>
</form>

<!-- ══ PESTAÑA 5: APIs ════════════════════════════════════════== -->
<?php elseif ($activeTab === 'apis'): ?>
<form method="POST" action="/admin/configuracion/update" class="admin-form">
  <?= Security::csrfField() ?>
  <input type="hidden" name="tab" value="apis">

  <h4>🤖 Telegram</h4>
  <div style="background:#1e2d3d;border-left:3px solid #00d4ff;padding:.75rem 1rem;margin-bottom:1rem;border-radius:0 6px 6px 0;font-size:.82rem;color:#94a3b8;line-height:1.6">
    <strong style="color:#e2e8f0">Cómo configurarlo:</strong><br>
    1. Habla con <a href="https://t.me/BotFather" target="_blank" style="color:#00d4ff">@BotFather</a> en Telegram → <code>/newbot</code> → copia el <strong style="color:#e2e8f0">Token</strong>.<br>
    2. Para obtener el <strong style="color:#e2e8f0">Chat ID</strong>: envía un mensaje a tu bot, luego abre<br>
       &nbsp;&nbsp;&nbsp;<code>https://api.telegram.org/bot<em>TU_TOKEN</em>/getUpdates</code> y busca <code>"id"</code> dentro de <code>"chat"</code>.<br>
    3. Si usas un grupo o canal, el Chat ID comienza con <code>-100…</code>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Bot Token</label>
      <input type="text" name="telegram_bot_token" id="tg-token" value="<?= Security::escape($cfg['telegram_bot_token'] ?? '') ?>" placeholder="123456:ABC-...">
    </div>
    <div class="form-group">
      <label>Chat ID</label>
      <div style="display:flex;gap:.5rem;align-items:center">
        <input type="text" name="telegram_chat_id" id="tg-chatid" value="<?= Security::escape($cfg['telegram_chat_id'] ?? '') ?>" placeholder="-100123456789" style="flex:1">
        <button type="button" class="btn btn-sm btn-secondary" onclick="detectTgChats()" title="Detectar chats disponibles">🔍</button>
      </div>
      <div id="tg-chats-list" style="margin-top:.5rem;display:none"></div>
    </div>
    <div style="align-self:end">
      <button type="button" class="btn btn-secondary" onclick="testTelegram()">🤖 Probar conexión</button>
      <span id="test-telegram-result" class="test-result"></span>
    </div>
  </div>

  <h4 style="margin-top:1.5rem">Notificaciones Telegram</h4>
  <div class="form-row checkboxes">
    <?php
    $notifs = [
      'telegram_notify_contacto'   => 'Nuevo mensaje de contacto',
      'telegram_notify_login_fail' => 'Login fallido (3+ intentos)',
      'telegram_notify_nuevo_user' => 'Nuevo usuario registrado',
      'telegram_notify_config'     => 'Cambio de configuración',
    ];
    foreach ($notifs as $k => $label):
    ?>
    <label class="checkbox-label">
      <input type="checkbox" name="<?= $k ?>" value="1"
             <?= ($cfg[$k] ?? '0') === '1' ? 'checked' : '' ?>>
      <?= Security::escape($label) ?>
    </label>
    <?php endforeach; ?>
  </div>

  <h4 style="margin-top:1.5rem">📸 ImgBB</h4>
  <div class="form-row">
    <div class="form-group">
      <label>API Key</label>
      <input type="text" name="imgbb_api_key" value="<?= Security::escape($cfg['imgbb_api_key'] ?? '') ?>">
    </div>
    <div style="align-self:end">
      <button type="button" class="btn btn-secondary" onclick="testImgbb()">🔑 Verificar key</button>
      <span id="test-imgbb-result" class="test-result"></span>
    </div>
  </div>

  <h4 style="margin-top:1.5rem">🛡 reCAPTCHA v3</h4>
  <div class="form-row">
    <div class="form-group">
      <label>Site Key</label>
      <input type="text" name="recaptcha_site_key" value="<?= Security::escape($cfg['recaptcha_site_key'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Secret Key</label>
      <input type="password" name="recaptcha_secret" value="<?= Security::escape($cfg['recaptcha_secret'] ?? '') ?>">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Guardar APIs</button>
</form>

<!-- ══ PESTAÑA 6: SOCIALES ════════════════════════════════════== -->
<?php elseif ($activeTab === 'sociales'): ?>
<form method="POST" action="/admin/configuracion/update" class="admin-form">
  <?= Security::csrfField() ?>
  <input type="hidden" name="tab" value="sociales">
  <?php
  $sociales = [
    'social_whatsapp'  => ['WhatsApp (número)','+593999999999'],
    'social_linkedin'  => ['LinkedIn URL','https://linkedin.com/in/usuario'],
    'social_github'    => ['GitHub URL','https://github.com/usuario'],
    'social_telegram'  => ['Telegram (@usuario)','@usuario'],
    'social_twitter'   => ['Twitter/X URL','https://twitter.com/usuario'],
    'social_instagram' => ['Instagram URL','https://instagram.com/usuario'],
    'social_youtube'   => ['YouTube URL','https://youtube.com/@usuario'],
    'social_website'   => ['Sitio externo URL','https://...'],
  ];
  ?>
  <div class="form-row" style="flex-wrap:wrap">
    <?php foreach ($sociales as $k => [$label, $placeholder]): ?>
    <div class="form-group" style="flex:0 0 calc(50% - 1rem)">
      <label><?= $label ?></label>
      <input type="text" name="<?= $k ?>" value="<?= Security::escape($cfg[$k] ?? '') ?>"
             placeholder="<?= Security::escape($placeholder) ?>">
    </div>
    <?php endforeach; ?>
  </div>
  <p style="color:#64748b;font-size:.85rem">Solo se muestran en el footer los campos con valor.</p>
  <button type="submit" class="btn btn-primary">Guardar Redes Sociales</button>
</form>

<!-- ══ PESTAÑA 7: SISTEMA ═════════════════════════════════════== -->
<?php elseif ($activeTab === 'sistema'): ?>

<div class="system-sections">
  <!-- Modos -->
  <div class="card" style="margin-bottom:1.5rem">
    <h4>Modos del sistema</h4>
    <form method="POST" action="/admin/configuracion/update" class="admin-form" style="margin:0">
      <?= Security::csrfField() ?>
      <input type="hidden" name="tab" value="sistema">
      <div class="form-row">
        <div class="form-group form-check">
          <label>
            <input type="checkbox" name="modo_seguro" value="1" <?= ($cfg['modo_seguro'] ?? '0') === '1' ? 'checked' : '' ?>>
            🔒 Modo Seguro (oculta proyectos marcados)
          </label>
        </div>
        <div class="form-group form-check">
          <label>
            <input type="checkbox" name="modo_mantenimiento" value="1" <?= ($cfg['modo_mantenimiento'] ?? '0') === '1' ? 'checked' : '' ?>>
            🚧 Modo Mantenimiento (muestra 503 al público)
          </label>
        </div>
      </div>
      <div class="form-group">
        <label>Mensaje de mantenimiento</label>
        <input type="text" name="mantenimiento_mensaje"
               value="<?= Security::escape($cfg['mantenimiento_mensaje'] ?? 'Sitio en mantenimiento. Volvemos pronto.') ?>">
      </div>
      <button type="submit" class="btn btn-warning">Guardar Modos</button>
    </form>
  </div>

  <!-- Cambiar contraseña -->
  <div class="card">
    <h4>🔑 Cambiar contraseña admin</h4>
    <form method="POST" action="/admin/configuracion/change-password" class="admin-form" style="margin:0">
      <?= Security::csrfField() ?>
      <div class="form-row">
        <div class="form-group">
          <label>Contraseña actual</label>
          <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
          <label>Nueva contraseña (mín 8 chars)</label>
          <input type="password" name="new_password" required minlength="8">
        </div>
        <div class="form-group">
          <label>Confirmar nueva contraseña</label>
          <input type="password" name="confirm_password" required minlength="8">
        </div>
      </div>
      <button type="submit" class="btn btn-danger">Cambiar contraseña</button>
    </form>
  </div>
</div>

<?php endif; ?>

</div><!-- /.tab-content -->

<script>
// ── Mail driver switcher ───────────────────────────────────
function switchMailDriver(val) {
  document.getElementById('section-gmail').style.display = val === 'gmail' ? '' : 'none';
  document.getElementById('section-smtp').style.display  = val === 'smtp'  ? '' : 'none';
  document.getElementById('card-gmail').classList.toggle('active', val === 'gmail');
  document.getElementById('card-smtp').classList.toggle('active',  val === 'smtp');
}

// ── Preview de colores ─────────────────────────────────────
function previewColor(key, val) {
  const hex = document.querySelector(`input[type=text].color-hex[oninput*="${key}"]`);
  const prev = document.getElementById('prev-' + key);
  if (hex) hex.value = val;
  if (prev) prev.style.background = val;
}
function syncColorPicker(key, val) {
  const picker = document.querySelector(`input[type=color][name="${key}"]`);
  const prev   = document.getElementById('prev-' + key);
  if (picker && /^#[0-9a-fA-F]{6}$/.test(val)) picker.value = val;
  if (prev)   prev.style.background = val;
}

// Glow slider label
const glowInput = document.querySelector('input[name="theme_glow_intensity"]');
if (glowInput) glowInput.addEventListener('input', () => {
  document.getElementById('glow-val').textContent = glowInput.value;
});

// ── Test Email ─────────────────────────────────────────────
function testEmail() {
  const btn = event.target; btn.disabled = true;
  const el  = document.getElementById('test-email-result');
  el.textContent = 'Enviando...'; el.className = 'test-result';
  // Primero guardar form
  document.querySelector('form').submit();
}
// Override — solo llamar API (no submit aquí)
function testEmail() {
  const el = document.getElementById('test-email-result');
  el.textContent = 'Enviando...'; el.className = 'test-result';
  fetchTest('/admin/configuracion/test-email', el);
}
function testTelegram() {
  fetchTest('/admin/configuracion/test-telegram', document.getElementById('test-telegram-result'));
}

function detectTgChats() {
  const token  = document.getElementById('tg-token')?.value?.trim();
  const list   = document.getElementById('tg-chats-list');
  const result = document.getElementById('test-telegram-result');
  if (!token) { result.textContent = '⚠ Pega el token primero'; result.className = 'test-result fail'; return; }

  list.style.display = 'none'; list.innerHTML = '';
  result.textContent = '⏳ Buscando chats...'; result.className = 'test-result';

  const csrf = document.querySelector('input[name="_csrf"]')?.value || '';
  fetch('/admin/configuracion/telegram-chats', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: '_csrf=' + encodeURIComponent(csrf) + '&token=' + encodeURIComponent(token),
  })
  .then(r => r.json())
  .then(d => {
    if (!d.ok) { result.textContent = '❌ ' + d.error; result.className = 'test-result fail'; return; }

    result.textContent = ''; result.className = 'test-result';

    if (d.empty || !d.chats?.length) {
      list.innerHTML = '<p style="color:#f97316;font-size:.82rem">⚠ No se encontraron chats. Envía primero un mensaje a tu bot en Telegram y vuelve a intentarlo.</p>';
      list.style.display = '';
      return;
    }

    const typeIcon = { private:'👤', group:'👥', supergroup:'👥', channel:'📢' };
    list.innerHTML = '<p style="font-size:.78rem;color:#64748b;margin-bottom:.4rem">Haz clic para usar ese Chat ID:</p>'
      + d.chats.map(c =>
          `<button type="button" onclick="document.getElementById('tg-chatid').value='${c.id}';this.parentElement.querySelectorAll('button').forEach(b=>b.classList.remove('tg-sel'));this.classList.add('tg-sel')" `
          + `style="display:block;width:100%;text-align:left;margin-bottom:.3rem;padding:.4rem .7rem;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;cursor:pointer;font-size:.82rem" `
          + `class="tg-chat-btn${c.id === document.getElementById('tg-chatid').value ? ' tg-sel' : ''}">`
          + `${typeIcon[c.type]||'💬'} <strong>${c.title}</strong> <span style="color:#64748b">(${c.type})</span> — <code style="color:#00d4ff">${c.id}</code></button>`
        ).join('');
    list.style.display = '';
  })
  .catch(() => { result.textContent = '❌ Error de red'; result.className = 'test-result fail'; });
}
function testImgbb() {
  fetchTest('/admin/configuracion/test-imgbb', document.getElementById('test-imgbb-result'));
}

function fetchTest(url, el) {
  el.textContent = '⏳ Probando...'; el.className = 'test-result';
  const csrf = document.querySelector('input[name="_csrf"]')?.value || '';
  fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: '_csrf=' + encodeURIComponent(csrf),
  })
  .then(r => r.json())
  .then(d => {
    el.textContent = d.ok ? '✅ ' + (d.message || 'OK') : '❌ ' + (d.error || 'Error');
    el.className   = 'test-result ' + (d.ok ? 'ok' : 'fail');
  })
  .catch(() => { el.textContent = '❌ Error de red'; el.className = 'test-result fail'; });
}

// ── Upload Logo ────────────────────────────────────────────
async function uploadLogo(form) {
  const statusEl = form.querySelector('.upload-status');
  statusEl.textContent = '⏳ Subiendo...';
  const fd = new FormData(form);
  try {
    const r = await fetch('/admin/configuracion/upload-logo', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      statusEl.textContent = '✅ Subido!';
      // Actualizar imagen en la card
      const img = form.closest('.logo-card').querySelector('img');
      if (img) img.src = d.url;
      else {
        const p = document.createElement('img');
        p.src = d.url; p.style = 'max-height:80px;max-width:200px;border-radius:8px;background:#0b0f19;padding:8px';
        form.closest('.logo-card').insertBefore(p, form);
      }
    } else {
      statusEl.textContent = '❌ ' + d.error;
    }
  } catch { statusEl.textContent = '❌ Error de red'; }
}
</script>
