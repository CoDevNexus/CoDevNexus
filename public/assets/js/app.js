/**
 * CoDevNexus — app.js
 * SPA boot script: fetch config → apply theme → render sections
 */
(function () {
  'use strict';

  /* ── State ──────────────────────────────────────────────── */
  let temaData   = {};
  let marcaData  = {};
  let socialesData = {};

  /* ── Boot ───────────────────────────────────────────────── */
  async function boot() {
    try {
      await Promise.all([fetchTema(), fetchMarca(), fetchSociales()]);
      applyTheme();
      applyMarca();
      renderApp();
    } catch (err) {
      console.error('[CDN] Boot error:', err);
      renderApp(); // still show site on partial failure
    }
  }

  /* ── API helpers ─────────────────────────────────────────── */
  async function apiFetch(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error('API ' + url + ' → ' + res.status);
    return res.json();
  }

  async function fetchTema() {
    const data = await apiFetch('/api/configuracion/tema');
    if (data.success) temaData = data.data;
  }

  async function fetchMarca() {
    const data = await apiFetch('/api/configuracion/marca');
    if (data.success) marcaData = data.data;
  }

  async function fetchSociales() {
    const data = await apiFetch('/api/configuracion/sociales');
    if (data.success) socialesData = data.data;
  }

  /* ── Theme injection ─────────────────────────────────────── */
  function applyTheme() {
    const root = document.documentElement;
    const map = {
      theme_color_cyan:   '--cyan',
      theme_color_purple: '--purple',
      theme_color_orange: '--orange',
      theme_bg_color:     '--bg',
      theme_text_color:   '--text',
    };
    Object.keys(map).forEach(key => {
      if (temaData[key]) root.style.setProperty(map[key], temaData[key]);
    });
    // Font
    if (temaData.theme_font) {
      document.body.style.fontFamily = temaData.theme_font + ', system-ui, sans-serif';
    }
  }

  /* ── Brand injection ─────────────────────────────────────── */
  function applyMarca() {
    // Logo
    const logoEl = document.getElementById('site-logo');
    if (logoEl && marcaData.logo_principal) logoEl.src = marcaData.logo_principal;

    // Favicon
    if (marcaData.favicon) {
      let fav = document.querySelector("link[rel~='icon']");
      if (!fav) {
        fav = document.createElement('link');
        fav.rel = 'icon';
        document.head.appendChild(fav);
      }
      fav.href = marcaData.favicon;
    }

    // Site name
    if (marcaData.site_name) {
      document.title = marcaData.site_name;
      const nameEl = document.getElementById('site-name');
      if (nameEl) nameEl.textContent = marcaData.site_name;
    }
  }

  /* ── Main render ─────────────────────────────────────────── */
  async function renderApp() {
    const app = document.getElementById('app');
    if (!app) return;

    const secciones = await loadSecciones();
    if (!secciones || !secciones.length) {
      app.innerHTML = '<p style="color:var(--muted);text-align:center;padding:4rem">Sin secciones activas.</p>';
      hideLoader();
      return;
    }

    // Clear loading placeholder
    app.innerHTML = '';

    for (const s of secciones) {
      const section = await renderSeccion(s);
      if (section) app.appendChild(section);
    }

    // Build dynamic navbar links from secciones
    buildNavLinks(secciones);

    // Init AOS after DOM population
    if (window.AOS) AOS.init({ duration: 800, once: true });

    // Scroll tracking for navbar active link
    initScrollSpy();

    // Hide loading screen
    hideLoader();
  }

  function hideLoader() {
    const loader = document.getElementById('loading-screen');
    if (loader) {
      loader.style.opacity = '0';
      loader.style.transition = 'opacity .4s';
      setTimeout(() => loader.remove(), 420);
    }
  }

  function buildNavLinks(secciones) {
    const container = document.getElementById('nav-links');
    if (!container) return;

    const labelMap = {
      hero:        'Inicio',
      sobre:       'Sobre mí',
      portafolio:  'Portafolio',
      tecnologias: 'Tecnologías',
      servicios:   'Servicios',
      contacto:    'Contacto',
      blog:        'Blog',
    };

    container.innerHTML = '';
    secciones.forEach(s => {
      const id    = s.slug || s.tipo || ('sec_' + s.id);
      const label = labelMap[s.tipo] || s.titulo || id;
      const a     = document.createElement('a');
      a.href      = '#' + id;
      a.className = 'nav-link';
      a.textContent = label;
      container.appendChild(a);
    });
  }

  /* ── Load secciones ──────────────────────────────────────── */
  async function loadSecciones() {
    try {
      const data = await apiFetch('/api/secciones');
      return data.success ? data.data : [];
    } catch { return []; }
  }

  /* ── Render dispatcher ───────────────────────────────────── */
  async function renderSeccion(s) {
    switch (s.tipo) {
      case 'hero':       return renderHero(s);
      case 'sobre':      return renderSobre(s);
      case 'portafolio': return await renderPortafolio(s);
      case 'tecnologias':return await renderTecnologias(s);
      case 'servicios':  return renderServicios(s);
      case 'contacto':   return renderContacto(s);
      default:           return renderGenerico(s);
    }
  }

  /* ── Hero ────────────────────────────────────────────────── */
  function renderHero(s) {
    const el = makeSection('hero', s.slug || 'hero');
    // Decode JSON contenido {icono, texto} or plain HTML
    let heroIcono = '', heroTexto = '';
    try {
      const c = (s.contenido || '').trim();
      if (c.startsWith('{')) { const d = JSON.parse(c); heroIcono = d.icono||''; heroTexto = d.texto||''; }
      else { heroTexto = c; }
    } catch(e) { heroTexto = s.contenido || ''; }

    // Typewriter config (needs to be before innerHTML template)
    const twLines = (temaData.typewriter_lines || 'Desarrollador Web,IoT,Redes')
      .split(',').map(l => l.trim()).filter(Boolean);
    const twSpeed = parseInt(temaData.typewriter_speed || 80);
    const twPause = parseInt(temaData.typewriter_pause || 1800);
    const twColor = temaData.typewriter_color || '#00d4ff';
    const twSize  = parseFloat(temaData.typewriter_size  || 1.25);

    // Usa site_name y site_tagline del API de marca; el titulo de seccion es solo etiqueta admin
    const siteName    = marcaData.site_name    || 'CoDevNexus';
    const siteTagline = marcaData.site_tagline  || '';
    el.innerHTML = `
      <div id="particles-js" class="particles-bg"></div>
      <div class="hero-content" data-aos="fade-up">
        ${siteTagline ? `<div class="hero-badge">${esc(siteTagline)}</div>` : ''}
        ${heroIcono ? `<div class="hero-icon"><i class="${esc(heroIcono)}"></i></div>` : ''}
        <h1 class="hero-title">${esc(siteName)}</h1>
        <p class="hero-sub">
          <span id="typewriter" class="typewriter"></span><span class="cursor" style="color:${twColor}">|</span>
        </p>
        ${heroTexto ? `<div class="hero-text">${heroTexto}</div>` : ''}
        <div class="hero-actions">
          <a href="#portafolio" class="btn-glow">Ver proyectos</a>
          <a href="#contacto" class="btn-outline">Contactar</a>
        </div>
      </div>
    `;

    // Particles
    if (window.particlesJS && typeof initParticles === 'function') {
      setTimeout(initParticles, 200);
    } else if (window.particlesJS) {
      setTimeout(() => {
        initParticles();
      }, 300);
    }

    // Typewriter
    const lines = twLines;
    const twEl = el.querySelector('#typewriter');
    if (twEl) {
      twEl.style.color    = twColor;
      twEl.style.fontSize = twSize + 'rem';
      const cursorEl = twEl.nextElementSibling;
      if (cursorEl && cursorEl.classList.contains('cursor')) cursorEl.style.color = twColor;
    }
    // Init typewriter after section is appended to DOM
    requestAnimationFrame(() => setTimeout(() => typewriter(twEl, lines, twSpeed, twPause), 200));

    return el;
  }

  /* ── Typewriter effect ───────────────────────────────────── */
  function typewriter(el, lines, speed, pause) {
    if (!el) return;
    speed = speed || 80;
    pause = pause || 1800;
    let li = 0, ci = 0, deleting = false;

    function tick() {
      const line = lines[li];
      if (!deleting) {
        el.textContent = line.slice(0, ci + 1);
        ci++;
        if (ci === line.length) { deleting = true; setTimeout(tick, pause); return; }
      } else {
        el.textContent = line.slice(0, ci - 1);
        ci--;
        if (ci === 0) { deleting = false; li = (li + 1) % lines.length; }
      }
      setTimeout(tick, deleting ? speed / 2 : speed);
    }
    tick();
  }

  /* ── Sobre ───────────────────────────────────────────────── */
  function renderSobre(s) {
    // Decode JSON contenido {icono, texto} or plain HTML
    let sobreIcono = '', sobreTexto = '';
    try {
      const c = (s.contenido || '').trim();
      if (c.startsWith('{')) { const d = JSON.parse(c); sobreIcono = d.icono||''; sobreTexto = d.texto||''; }
      else { sobreTexto = c; }
    } catch(e) { sobreTexto = s.contenido || ''; }
    const el = makeSection('sobre-section', s.slug || 'sobre');
    el.innerHTML = `
      <div class="container">
        <h2 class="section-title" data-aos="fade-up">
          ${sobreIcono ? `<i class="${esc(sobreIcono)}" style="margin-right:.45rem"></i>` : ''}
          ${esc(s.titulo || '')}
        </h2>
        <div class="sobre-content" data-aos="fade-up">
          ${sobreTexto || ''}
        </div>
      </div>
    `;
    return el;
  }

  /* ── Portafolio ──────────────────────────────────────────── */
  async function renderPortafolio(s) {
    const el = makeSection('portafolio-section', s.slug || 'portafolio');
    el.innerHTML = `
      <div class="container">
        <h2 class="section-title" data-aos="fade-up">${esc(s.titulo || '')}</h2>
        <div id="portafolio-grid" class="portfolio-grid"></div>
      </div>
    `;

    try {
      const data = await apiFetch('/api/portafolio');
      const grid = el.querySelector('#portafolio-grid');
      if (data.success && data.data.length) {
        data.data.forEach((p, i) => {
          grid.appendChild(makePortafolioCard(p, i));
        });
      } else {
        grid.innerHTML = '<p style="color:var(--muted)">Sin proyectos por el momento.</p>';
      }
    } catch { /* silent */ }

    initModal(el);
    return el;
  }

  function makePortafolioCard(p, i) {
    const card = document.createElement('div');
    card.className = 'portfolio-card';
    card.dataset.id = p.id;
    card.setAttribute('data-aos', 'fade-up');
    card.setAttribute('data-aos-delay', (i % 3) * 100);
    card.innerHTML = `
      <div class="card-image">
        <img src="${esc(p.imagen_url || '/assets/img/placeholder.svg')}" alt="${esc(p.titulo)}" loading="lazy">
        <div class="card-overlay">
          <button class="btn-detail" data-id="${esc(p.id)}">Ver detalles</button>
        </div>
      </div>
      <div class="card-info">
        <h3>${esc(p.titulo)}</h3>
        <p>${esc(p.descripcion_corta || '')}</p>
        ${p.categoria ? `<div class="card-tags"><span class="tag tag--cat">${esc(p.categoria)}</span></div>` : ''}
      </div>
    `;
    return card;
  }

  function initModal(container) {
    container.addEventListener('click', async (e) => {
      const btn = e.target.closest('.btn-detail');
      if (!btn) return;
      const id = btn.dataset.id;
      try {
        const data = await apiFetch('/api/portafolio/' + id);
        if (data.success) showModal(data.data);
      } catch { /* silent */ }
    });
  }

  /* ── Modal singleton & helpers ───────────────────────────── */
  let _modalOverlay = null;

  function getModalOverlay() {
    if (_modalOverlay) return _modalOverlay;

    _modalOverlay = document.createElement('div');
    _modalOverlay.id = 'modal-overlay';
    _modalOverlay.className = 'modal-overlay';
    document.body.appendChild(_modalOverlay);

    // Cierre al clicar el fondo
    _modalOverlay.addEventListener('click', e => {
      if (e.target === _modalOverlay) closeModal();
    });
    // Cierre con ESC
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeModal();
    });

    return _modalOverlay;
  }

  function closeModal() {
    if (_modalOverlay) _modalOverlay.classList.remove('active');
  }

  function showModal(p) {
    const overlay = getModalOverlay();

    const imgHtml = p.imagen_url
      ? `<img src="${esc(p.imagen_url)}" alt="${esc(p.titulo)}"
             style="width:100%;border-radius:8px;margin-bottom:1.25rem;display:block">`
      : '';

    const demoBtn = p.enlace_demo
      ? `<a href="${esc(p.enlace_demo)}" target="_blank" rel="noopener" class="btn-glow">🚀 Ver demo</a>`
      : '';
    const repoBtn = p.enlace_repo
      ? `<a href="${esc(p.enlace_repo)}" target="_blank" rel="noopener" class="btn-outline">⌥ Repositorio</a>`
      : '';

    overlay.innerHTML = `
      <div class="modal-box">
        <button class="modal-close" aria-label="Cerrar">✕</button>
        ${imgHtml}
        <h2>${esc(p.titulo)}</h2>
        ${p.categoria ? `<span class="tag tag--cat" style="margin-bottom:.75rem;display:inline-block">${esc(p.categoria)}</span>` : ''}
        <div class="modal-body">${p.descripcion_larga || p.descripcion_corta || ''}</div>
        ${demoBtn || repoBtn ? `<div class="modal-links">${demoBtn}${repoBtn}</div>` : ''}
      </div>
    `;

    // Usar event delegation para el botón de cierre
    overlay.querySelector('.modal-close').addEventListener('click', closeModal);
    overlay.classList.add('active');
  }

  /* ── Tecnologías ─────────────────────────────────────────── */
  async function renderTecnologias(s) {
    const el = makeSection('tech-section', s.slug || 'tecnologias');
    el.innerHTML = `
      <div class="container">
        <h2 class="section-title" data-aos="fade-up">${esc(s.titulo || '')}</h2>
        <div id="tech-grid" class="tech-grid"></div>
      </div>
    `;

    try {
      const data = await apiFetch('/api/tecnologias');
      const grid = el.querySelector('#tech-grid');
      if (data.success && data.data.length) {
        data.data.forEach((t, i) => {
          grid.appendChild(makeTechCard(t, i));
        });
      }
    } catch { /* silent */ }

    return el;
  }

  function makeTechCard(t, i) {
    const card = document.createElement('div');
    card.className = 'tech-card';
    card.setAttribute('data-aos', 'fade-up');
    card.setAttribute('data-aos-delay', (i % 4) * 80);

    let iconHtml = '';
    if (t.icono_svg) {
      iconHtml = `<div class="tech-icon">${t.icono_svg}</div>`;
    } else if (t.icono_clase) {
      iconHtml = `<div class="tech-icon"><i class="${esc(t.icono_clase)} colored" style="font-size:2rem"></i></div>`;
    }

    const pct = Math.max(0, Math.min(100, parseInt(t.nivel || 0)));
    card.innerHTML = `
      ${iconHtml}
      <div class="tech-name">${esc(t.nombre)}</div>
      <div class="tech-category">${esc(t.categoria || '')}</div>
      <div class="tech-bar">
        <div class="tech-bar-fill" style="width:${pct}%"></div>
      </div>
      <div class="tech-level">${pct}%</div>
    `;
    return card;
  }

  /* ── Servicios ─────────────────────────────────────────────*/
  function renderServicios(s) {
    const el = makeSection('servicios-section', s.slug || 'servicios');

    let servicios = [];
    try {
      const raw = (s.contenido || '').replace(/<[^>]+>/g, '').trim();
      servicios = JSON.parse(raw);
    } catch {
      // contenido normal (HTML intro)
    }

    // Ordenar por campo orden si existe y filtrar ocultos
    if (Array.isArray(servicios)) {
      servicios = servicios.filter(sv => sv.visible !== 0);
      servicios.sort((a, b) => (a.orden ?? 99) - (b.orden ?? 99));
    }

    const cardsHtml = servicios.length
      ? servicios.map((sv, i) => {
          // Soporta tanto clase ri- (Remixicon) como emoji/texto
          const iconHtml = (sv.icon && sv.icon.startsWith('ri-'))
            ? `<i class="${sv.icon}"></i>`
            : (sv.icon || '');

          const itemsHtml = (sv.items && sv.items.length)
            ? '<ul class="service-items">' +
                sv.items.map(it => `<li>${esc(it)}</li>`).join('') +
              '</ul>'
            : '';
          return `
            <div class="service-card" data-aos="fade-up" data-aos-delay="${(i % 3) * 120}">
              <div class="service-icon-wrap">
                <span class="service-icon">${iconHtml}</span>
              </div>
              <h3>${esc(sv.titulo || '')}</h3>
              <p>${esc(sv.desc || '')}</p>
              ${itemsHtml}
            </div>`;
        }).join('')
      : '';

    el.innerHTML = `
      <div class="container">
        <h2 class="section-title" data-aos="fade-up">${esc(s.titulo || '')}</h2>
        ${s.subtitulo ? `<p class="section-subtitle" data-aos="fade-up" data-aos-delay="80">${esc(s.subtitulo)}</p>` : ''}
        ${!servicios.length && s.contenido ? `<div class="servicios-intro" data-aos="fade-up">${s.contenido}</div>` : ''}
        <div class="services-grid">${cardsHtml}</div>
      </div>
    `;
    return el;
  }

  /* ── Contacto ────────────────────────────────────────────── */
  const PAISES = [
    'Ecuador','Colombia','Perú','Venezuela','Argentina','Bolivia','Brasil',
    'Chile','Cuba','Guatemala','Honduras','México','Nicaragua','Panamá',
    'Paraguay','República Dominicana','El Salvador','Uruguay','España',
    'Estados Unidos','Otro'
  ];

  function renderContacto(s) {
    const el = makeSection('contacto-section', s.slug || 'contacto');
    const paisOpts = PAISES.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join('');
    el.innerHTML = `
      <div class="container">
        <h2 class="section-title" data-aos="fade-up">${esc(s.titulo || '')}</h2>
        <div class="contact-wrapper" data-aos="fade-up">
          <form id="contact-form" class="contact-form" novalidate>
            <div class="form-row">
              <div class="form-group">
                <input type="text" name="nombre" placeholder="Tu nombre *" required>
              </div>
              <div class="form-group">
                <input type="email" name="email" placeholder="Tu correo *" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <input type="tel" name="telefono" placeholder="Teléfono (opcional)">
              </div>
              <div class="form-group">
                <select name="pais" class="contact-select">
                  <option value="">País de residencia</option>
                  ${paisOpts}
                </select>
              </div>
            </div>
            <div class="form-group">
              <input type="text" name="asunto" placeholder="Asunto">
            </div>
            <div class="form-group">
              <textarea name="mensaje" rows="5" placeholder="Tu mensaje *" required></textarea>
            </div>
            <button type="submit" class="btn-glow" id="contact-submit">
              <i class="ri-send-plane-line" style="margin-right:.4rem"></i>Enviar mensaje
            </button>
          </form>
        </div>
        ${renderSociales()}
      </div>
    `;

    el.querySelector('#contact-form').addEventListener('submit', handleContactSubmit);
    return el;
  }

  function renderSociales() {
    if (!socialesData || !Object.keys(socialesData).length) return '';
    const links = Object.entries(socialesData)
      .filter(([, v]) => v)
      .map(([k, v]) => {
        const name = k.replace('social_', '');
        const icon = getSocialIcon(name);
        return `<a href="${esc(v)}" target="_blank" rel="noopener" class="social-link" title="${esc(name)}">
          ${icon}<span>${esc(name)}</span>
        </a>`;
      }).join('');
    return `<div class="social-links">${links}</div>`;
  }

  function getSocialIcon(name) {
    const icons = {
      github: '<i class="devicon-github-original"></i>',
      linkedin: '<i class="devicon-linkedin-plain"></i>',
      twitter: '𝕏',
      instagram: '📷',
      youtube: '▶',
      telegram: '✈',
    };
    return icons[name.toLowerCase()] || '🔗';
  }

  async function loadCsrfToken(field) {
    try {
      const data = await apiFetch('/api/csrf');
      if (data.token && field) field.value = data.token;
    } catch { /* silent */ }
  }

  async function handleContactSubmit(e) {
    e.preventDefault();
    const form = e.target;

    // Client-side validation
    const nombre  = form.querySelector('[name=nombre]').value.trim();
    const email   = form.querySelector('[name=email]').value.trim();
    const mensaje = form.querySelector('[name=mensaje]').value.trim();
    if (!nombre || !email || !mensaje) {
      Swal.fire({
        icon: 'warning', title: 'Campos requeridos',
        text: 'Nombre, correo y mensaje son obligatorios.',
        background: '#111827', color: '#e2e8f0', confirmButtonColor: '#00d4ff'
      });
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      Swal.fire({
        icon: 'warning', title: 'Correo inválido',
        text: 'Por favor ingresa un correo electrónico válido.',
        background: '#111827', color: '#e2e8f0', confirmButtonColor: '#00d4ff'
      });
      return;
    }

    // Show loading
    Swal.fire({
      title: 'Enviando mensaje…',
      html: '<div style="color:#94a3b8;font-size:.9rem">Procesando tu mensaje</div>',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    try {
      const res  = await fetch('/api/contacto', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: new FormData(form),
      });

      let data;
      try {
        data = await res.json();
      } catch (parseErr) {
        const raw = await res.text().catch(() => '(no body)');
        console.error('[contacto] JSON parse error:', parseErr, '\nRaw response:', raw);
        Swal.fire({
          icon: 'error', title: 'Error del servidor',
          html: `<p style="font-size:.85rem;color:#94a3b8">Respuesta inesperada del servidor.</p>
                 <pre style="font-size:.7rem;text-align:left;overflow:auto;max-height:120px;background:#0b0f19;padding:.5rem;border-radius:.4rem">${esc(raw.substring(0, 300))}</pre>`,
          background: '#111827', color: '#e2e8f0', confirmButtonColor: '#ef4444'
        });
        return;
      }

      if (data.success) {
        const steps  = data.steps || [];
        const stepsHtml = steps.map(s => {
          const icon   = s.skipped ? '⚪' : (s.ok ? '✅' : '❌');
          const detail = s.detail
            ? `<small style="color:#64748b;display:block;padding-left:1.6rem">${esc(s.detail)}</small>` : '';
          return `<div style="display:flex;align-items:flex-start;gap:.45rem;padding:.3rem 0;font-size:.87rem">
            <span style="min-width:1.2rem">${icon}</span>
            <div style="flex:1"><span style="color:${s.ok ? '#6ee7b7' : (s.skipped ? '#64748b' : '#fca5a5')}">${esc(s.label)}</span>${detail}</div>
          </div>`;
        }).join('');

        Swal.fire({
          icon: 'success',
          title: '¡Mensaje enviado!',
          html: `<div style="text-align:left;margin-top:.5rem">${stepsHtml}</div>`,
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#00d4ff',
          background: '#111827',
          color: '#e2e8f0'
        });
        form.reset();
      } else {
        Swal.fire({
          icon: 'error', title: 'Error',
          text: data.message || 'No se pudo enviar el mensaje.',
          background: '#111827', color: '#e2e8f0', confirmButtonColor: '#ef4444'
        });
      }
    } catch(err) {
      console.error('[contacto] fetch error:', err);
      Swal.fire({
        icon: 'error', title: 'Error de conexión',
        text: 'No se pudo conectar. Intenta de nuevo.',
        background: '#111827', color: '#e2e8f0'
      });
    }
  }

  /* ── Generic section ─────────────────────────────────────── */
  function renderGenerico(s) {
    const el = makeSection('generic-section', s.slug || 'sec_' + s.id);
    el.innerHTML = `
      <div class="container">
        <h2 class="section-title" data-aos="fade-up">${esc(s.titulo || '')}</h2>
        <div data-aos="fade-up">${s.contenido || ''}</div>
      </div>
    `;
    return el;
  }

  /* ── Scroll spy ──────────────────────────────────────────── */
  function initScrollSpy() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    if (!sections.length || !navLinks.length) return;

    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          navLinks.forEach(a => {
            a.classList.toggle('active', a.getAttribute('href') === '#' + e.target.id);
          });
        }
      });
    }, { threshold: 0.35 });

    sections.forEach(s => obs.observe(s));
  }

  /* ── Utils ───────────────────────────────────────────────── */
  function makeSection(className, id) {
    const el = document.createElement('section');
    el.className = className;
    el.id = id;
    return el;
  }

  function esc(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
  }

  /* ── Smooth scroll for nav links ─────────────────────────── */
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const target = document.querySelector(a.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });

  /* ── Entry ───────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

})();
