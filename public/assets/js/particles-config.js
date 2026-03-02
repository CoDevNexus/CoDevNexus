/**
 * CoDevNexus — particles-config.js
 * Reads tema config and initialises Particles.js
 * Called by app.js after /api/configuracion/tema is loaded.
 *
 * Supported particles_style values:
 *   network  — connected nodes (default)
 *   bubbles  — floating bubbles (no lines)
 *   snow     — falling snow/particles
 *   stars    — static star field with slow drift
 */
function initParticles(temaOverride) {
  if (!window.particlesJS) return;

  const container = document.getElementById('particles-js');
  if (!container) return;

  const tema    = temaOverride || (typeof temaData !== 'undefined' ? temaData : {});
  const color   = tema.theme_color_cyan  || '#00d4ff';
  const enabled = tema.theme_particles   !== '0';
  const style   = tema.particles_style   || 'network';

  if (!enabled) {
    container.style.display = 'none';
    return;
  }

  container.style.display = '';

  /* ── preset configurations ────────────────────────────────── */

  const presets = {

    /** Red de nodos conectados (interactivo) */
    network: {
      particles: {
        number: { value: 80, density: { enable: true, value_area: 800 } },
        color: { value: color },
        shape: { type: 'circle' },
        opacity: { value: 0.45, random: true, anim: { enable: true, speed: 1, opacity_min: 0.1 } },
        size: { value: 4, random: true },
        line_linked: { enable: true, distance: 150, color: color, opacity: 0.25, width: 1 },
        move: { enable: true, speed: 3, direction: 'none', random: true, out_mode: 'out' }
      },
      interactivity: {
        detect_on: 'canvas',
        events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' }, resize: true },
        modes: { grab: { distance: 160, line_linked: { opacity: 0.6 } }, push: { particles_nb: 4 } }
      },
      retina_detect: true
    },

    /** Burbujas flotantes (sin líneas, semi-transparentes) */
    bubbles: {
      particles: {
        number: { value: 60, density: { enable: true, value_area: 900 } },
        color: { value: [color, '#a855f7', '#f97316'] },
        shape: { type: 'circle' },
        opacity: { value: 0.35, random: true, anim: { enable: true, speed: 0.6, opacity_min: 0.1 } },
        size: { value: 12, random: true, anim: { enable: true, speed: 2, size_min: 4 } },
        line_linked: { enable: false },
        move: { enable: true, speed: 1.5, direction: 'top', random: true, straight: false, out_mode: 'out' }
      },
      interactivity: {
        detect_on: 'canvas',
        events: { onhover: { enable: true, mode: 'bubble' }, onclick: { enable: true, mode: 'push' }, resize: true },
        modes: { bubble: { distance: 250, size: 20, duration: 1.5, opacity: 0.6, speed: 3 }, push: { particles_nb: 3 } }
      },
      retina_detect: true
    },

    /** Nieve / partículas cayendo */
    snow: {
      particles: {
        number: { value: 120, density: { enable: true, value_area: 800 } },
        color: { value: '#ffffff' },
        shape: { type: 'circle' },
        opacity: { value: 0.7, random: true },
        size: { value: 3, random: true },
        line_linked: { enable: false },
        move: {
          enable: true, speed: 2, direction: 'bottom',
          random: true, straight: false, out_mode: 'out',
          bounce: false
        }
      },
      interactivity: {
        detect_on: 'canvas',
        events: { onhover: { enable: false }, onclick: { enable: false }, resize: true }
      },
      retina_detect: true
    },

    /** Campo de estrellas (deriva lenta, sin líneas) */
    stars: {
      particles: {
        number: { value: 160, density: { enable: true, value_area: 1000 } },
        color: { value: '#ffffff' },
        shape: { type: 'circle' },
        opacity: { value: 0.8, random: true, anim: { enable: true, speed: 0.4, opacity_min: 0.2, sync: false } },
        size: { value: 2, random: true },
        line_linked: { enable: false },
        move: { enable: true, speed: 0.4, direction: 'none', random: true, out_mode: 'out' }
      },
      interactivity: {
        detect_on: 'canvas',
        events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: false }, resize: true },
        modes: { repulse: { distance: 100, duration: 0.6 } }
      },
      retina_detect: true
    }

  };

  const config = presets[style] || presets.network;
  particlesJS('particles-js', config);
}
