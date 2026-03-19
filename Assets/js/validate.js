/* ============================================================
   CAMPUS CONNECT — core.js
   Boots: Lenis · GSAP + ScrollTrigger · Lucide · Cursor
   Exposes globals: CC.loadComponent(), CC.makeMagnetic(), CC.cardHover()
   
   Load order in every HTML page (inside <head> or before </body>):
     1. gsap.min.js
     2. ScrollTrigger.min.js
     3. lenis.min.js
     4. lucide.js
     5. THIS FILE (core.js)  ← always last
   ============================================================ */

(function () {
  'use strict';

  // ── Namespace ─────────────────────────────────────────────
  window.CC = window.CC || {};

  // ── 1. Component Loader (header / footer via fetch) ───────
  /**
   * CC.loadComponent(containerSelector, filePath)
   * Fetches an HTML partial and injects it into the matching element.
   * After ALL components load, fires 'cc:ready' on document.
   *
   * Usage in any page:
   *   CC.loadComponent('#site-header', '/components/header.html');
   *   CC.loadComponent('#site-footer', '/components/footer.html');
   */
  const _pendingComponents = [];

  CC.loadComponent = function (selector, filePath) {
    const el = document.querySelector(selector);
    if (!el) return;

    const promise = fetch(filePath)
      .then(res => {
        if (!res.ok) throw new Error(`Failed to load ${filePath}: ${res.status}`);
        return res.text();
      })
      .then(html => {
        el.innerHTML = html;
      })
      .catch(err => console.error('[CC] Component load error:', err));

    _pendingComponents.push(promise);
  };

  // Call this after registering all loadComponent() calls
  CC.initComponents = function () {
    Promise.all(_pendingComponents).then(() => {
      // Re-run Lucide so icons inside injected HTML render
      if (window.lucide) lucide.createIcons();

      // Mark active nav link
      _setActiveNav();

      // Cursor hover targets include newly injected elements
      _bindCursorTargets();

      document.dispatchEvent(new Event('cc:ready'));
    });
  };

  // ── 2. Active Nav Link ────────────────────────────────────
  function _setActiveNav() {
    const current = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('nav a[data-page]').forEach(link => {
      if (link.dataset.page === current) {
        link.classList.add('text-red-500', 'font-black');
      }
    });
  }

  // ── 3. Custom Cursor ──────────────────────────────────────
  function _initCursor() {
    const cursor = document.getElementById('cursor');
    if (!cursor) return;

    document.addEventListener('mousemove', e => {
      gsap.to(cursor, {
        x: e.clientX,
        y: e.clientY,
        duration: 0.08,
        ease: 'power2.out',
      });
    });

    document.addEventListener('mouseleave', () =>
      gsap.to(cursor, { opacity: 0, duration: 0.2 })
    );
    document.addEventListener('mouseenter', () =>
      gsap.to(cursor, { opacity: 1, duration: 0.2 })
    );
  }

  function _bindCursorTargets() {
    const cursor = document.getElementById('cursor');
    if (!cursor) return;
    document.querySelectorAll(
      'a, button, .book-card, .pill-tag, .footer-social, [data-cursor-hover]'
    ).forEach(el => {
      if (el._ccCursorBound) return; // prevent duplicate listeners
      el._ccCursorBound = true;
      el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
      el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
    });
  }

  // ── 4. Lenis Smooth Scroll ────────────────────────────────
  function _initLenis() {
    if (!window.Lenis) return;

    const lenis = new Lenis({
      duration: 1.2,
      easing: t => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
      smoothWheel: true,
    });

    // Sync with GSAP ticker
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(time => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);

    window.CC.lenis = lenis; // expose so pages can lenis.scrollTo() etc.
  }

  // ── 5. GSAP ScrollTrigger Registration ───────────────────
  function _initGSAP() {
    if (!window.gsap || !window.ScrollTrigger) return;
    gsap.registerPlugin(ScrollTrigger);
  }

  // ── 6. Nav entrance animation ─────────────────────────────
  function _animateNav() {
    const nav = document.querySelector('#site-header nav, nav.site-nav');
    if (!nav) return;
    gsap.from(nav, { y: -80, opacity: 0, duration: 0.6, ease: 'power3.out' });
  }

  // ── 7. Shared Utilities exposed on CC namespace ───────────

  /**
   * CC.makeMagnetic(elementOrSelector, strength?)
   * Makes a button pull toward the cursor like a magnet.
   * strength: 0.0 – 1.0, default 0.35
   */
  CC.makeMagnetic = function (target, strength = 0.35) {
    const btn = typeof target === 'string' ? document.querySelector(target) : target;
    if (!btn) return;

    btn.addEventListener('mousemove', e => {
      const rect = btn.getBoundingClientRect();
      const dx = (e.clientX - (rect.left + rect.width  / 2)) * strength;
      const dy = (e.clientY - (rect.top  + rect.height / 2)) * strength;
      gsap.to(btn, { x: dx, y: dy, duration: 0.3, ease: 'power2.out' });
    });
    btn.addEventListener('mouseleave', () => {
      gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: 'elastic.out(1, 0.4)' });
    });
    btn.addEventListener('mousedown', () =>
      gsap.to(btn, { scale: 0.95, duration: 0.1 })
    );
    btn.addEventListener('mouseup', () =>
      gsap.to(btn, { scale: 1, duration: 0.25, ease: 'back.out(2)' })
    );
  };

  /**
   * CC.cardHover(cardElements)
   * Applies the Neobrutalist hover (lift + shadow expand) to book cards.
   * Pass a NodeList, array, or single element.
   */
  CC.cardHover = function (cards) {
    const els = cards instanceof NodeList ? Array.from(cards) : [].concat(cards);
    els.forEach(card => {
      if (!card || card._ccHoverBound) return;
      card._ccHoverBound = true;

      const isFeatured   = card.classList.contains('featured');
      const shadowNormal = isFeatured ? '8px 8px 0px 0px #ef4444' : '6px 6px 0px 0px rgba(0,0,0,1)';
      const shadowHover  = isFeatured ? '12px 12px 0px 0px #ef4444' : '12px 12px 0px 0px rgba(0,0,0,1)';

      card.addEventListener('mouseenter', () =>
        gsap.to(card, { x: -6, y: -6, boxShadow: shadowHover, duration: 0.25, ease: 'power2.out' })
      );
      card.addEventListener('mouseleave', () =>
        gsap.to(card, { x: 0, y: 0, boxShadow: shadowNormal, duration: 0.3, ease: 'elastic.out(1,0.6)' })
      );
      card.addEventListener('mousedown', () =>
        gsap.to(card, { scale: 0.97, duration: 0.1 })
      );
      card.addEventListener('mouseup', () =>
        gsap.to(card, { scale: 1, duration: 0.2, ease: 'back.out(2)' })
      );
    });
  };

  /**
   * CC.scrollReveal(selector, options?)
   * Animate elements into view when they enter the viewport.
   * options: { y, opacity, duration, stagger, ease, start }
   */
  CC.scrollReveal = function (selector, options = {}) {
    const defaults = {
      y: 50, opacity: 0, duration: 0.7,
      stagger: 0.12, ease: 'back.out(1.7)',
      start: 'top 85%',
    };
    const cfg = Object.assign({}, defaults, options);
    const els = document.querySelectorAll(selector);
    if (!els.length) return;

    gsap.from(els, {
      y: cfg.y,
      opacity: cfg.opacity,
      duration: cfg.duration,
      stagger: cfg.stagger,
      ease: cfg.ease,
      scrollTrigger: { trigger: els[0], start: cfg.start },
    });
  };

  /**
   * CC.floatElement(id, xAmp, yAmp, duration)
   * Infinite sine-wave float animation (for decorative elements).
   */
  CC.floatElement = function (id, xAmp = 8, yAmp = 12, dur = 3.2) {
    const el = document.getElementById(id);
    if (!el) return;
    gsap.to(el, {
      x: `+=${xAmp}`, y: `+=${yAmp}`,
      duration: dur,
      ease: 'sine.inOut',
      repeat: -1,
      yoyo: true,
    });
  };

  /**
   * CC.mouseParallax(targets)
   * Makes elements subtly react to mouse movement.
   * targets: array of { id, xStrength, yStrength }
   */
  CC.mouseParallax = function (targets) {
    document.addEventListener('mousemove', e => {
      const nx = (e.clientX / window.innerWidth  - 0.5) * 2;
      const ny = (e.clientY / window.innerHeight - 0.5) * 2;
      targets.forEach(({ id, xStrength = 14, yStrength = 10 }) => {
        const el = document.getElementById(id);
        if (!el) return;
        gsap.to(el, {
          x: nx * xStrength,
          y: ny * yStrength,
          duration: 1.3,
          ease: 'power2.out',
          overwrite: 'auto',
        });
      });
    });
  };

  // ── 8. Boot Sequence ──────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    _initGSAP();
    _initLenis();
    _initCursor();
    _bindCursorTargets();

    // Lucide initial render (for icons outside components)
    if (window.lucide) lucide.createIcons();

    // Nav animation fires after components are ready
    document.addEventListener('cc:ready', _animateNav, { once: true });
  });

})();