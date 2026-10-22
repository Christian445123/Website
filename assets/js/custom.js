/**
 * Chris_Gaming445 – Custom JS
 * Kein Inline-JS im HTML nötig (strikte Content-Security-Policy):
 *   - data-confirm="Text"            → Bestätigungsdialog vor Submit/Klick
 *   - data-autosubmit                → <select> sendet sein Formular bei Änderung
 *   - data-copy-target="#id"         → Text in die Zwischenablage kopieren
 *   - data-preset-*                  → Icon-Preset im Admin-Projektformular
 *   - data-cg-consent-open           → Cookie-Banner erneut öffnen
 */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  /* ── Navbar: .scrolled bei Scroll ─────────────────────────── */
  const navbar = document.getElementById('mainNav');
  if (navbar) {
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 24);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Reveal-on-scroll ─────────────────────────────────────── */
  const revealEls = document.querySelectorAll('.cg-reveal');
  if (revealEls.length) {
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => {
          if (en.isIntersecting) {
            en.target.classList.add('is-visible');
            io.unobserve(en.target);
          }
        });
      }, { threshold: 0.12 });
      revealEls.forEach((el) => io.observe(el));
    } else {
      revealEls.forEach((el) => el.classList.add('is-visible'));
    }
  }

  /* ── Bestätigungsdialoge ──────────────────────────────────── */
  document.addEventListener('click', (ev) => {
    const el = ev.target.closest('[data-confirm]');
    if (el && !window.confirm(el.getAttribute('data-confirm'))) {
      ev.preventDefault();
      ev.stopPropagation();
    }
  });

  /* ── Select → Formular absenden ───────────────────────────── */
  document.addEventListener('change', (ev) => {
    const el = ev.target;
    if (el.matches && el.matches('select[data-autosubmit]') && el.form) {
      el.form.submit();
    }
  });

  /* ── Kopieren-Buttons ─────────────────────────────────────── */
  document.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-copy-target]');
    if (!btn) return;
    const src = document.querySelector(btn.getAttribute('data-copy-target'));
    if (!src || !navigator.clipboard) return;
    navigator.clipboard.writeText((src.textContent || '').trim()).then(() => {
      const icon = btn.querySelector('i');
      if (!icon) return;
      icon.className = 'fas fa-check';
      setTimeout(() => { icon.className = 'fas fa-copy'; }, 1800);
    });
  });

  /* ── Admin: Icon-Presets ──────────────────────────────────── */
  document.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-preset-class]');
    if (!btn) return;
    const set = (id, v) => { const f = document.getElementById(id); if (f) f.value = v; };
    set('pg_icon_class', btn.getAttribute('data-preset-class'));
    set('pg_icon_color', btn.getAttribute('data-preset-color'));
    set('pg_icon_bg',    btn.getAttribute('data-preset-bg'));
  });

  /* ── Cookie-Einwilligung + Google Analytics (opt-in) ──────── */
  const L = (document.documentElement.lang === 'en') ? {
    text: 'We use technically necessary cookies for the login. With your consent we also use Google Analytics to improve the site. Details in the ',
    link: 'privacy policy', deny: 'Necessary only', allow: 'Accept all', label: 'Cookie settings'
  } : {
    text: 'Wir nutzen technisch notwendige Cookies für den Login. Mit deiner Zustimmung setzen wir zusätzlich Google Analytics ein, um die Seite zu verbessern. Details in der ',
    link: 'Datenschutzerklärung', deny: 'Nur notwendige', allow: 'Alle akzeptieren', label: 'Cookie-Einstellungen'
  };
  const GA_ID = 'G-Q5C4N8B4VQ';
  const KEY   = 'cg_consent_v1';

  const store = {
    get() { try { return localStorage.getItem(KEY); } catch (e) { return null; } },
    set(v) { try { localStorage.setItem(KEY, v); } catch (e) { /* ignore */ } }
  };

  function loadAnalytics() {
    if (window.__cgGaLoaded) return;
    window.__cgGaLoaded = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', GA_ID, { anonymize_ip: true });
    const s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA_ID);
    document.head.appendChild(s);
  }

  function closeBanner() {
    const b = document.getElementById('cgConsent');
    if (b) b.remove();
  }

  function openBanner() {
    if (document.getElementById('cgConsent')) return;
    const box = document.createElement('div');
    box.id = 'cgConsent';
    box.className = 'cg-consent';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-live', 'polite');
    box.setAttribute('aria-label', L.label);

    const text = document.createElement('p');
    text.append(L.text);
    const link = document.createElement('a');
    link.href = '/datenschutz.php';
    link.textContent = L.link;
    text.append(link, '.');

    const actions = document.createElement('div');
    actions.className = 'cg-consent-actions';

    const deny = document.createElement('button');
    deny.type = 'button';
    deny.className = 'cg-btn cg-btn-outline';
    deny.textContent = L.deny;
    deny.addEventListener('click', () => { store.set('denied'); closeBanner(); });

    const allow = document.createElement('button');
    allow.type = 'button';
    allow.className = 'cg-btn cg-btn-primary';
    allow.textContent = L.allow;
    allow.addEventListener('click', () => { store.set('granted'); closeBanner(); loadAnalytics(); });

    actions.append(deny, allow);
    box.append(text, actions);
    document.body.appendChild(box);
  }

  document.addEventListener('click', (ev) => {
    if (ev.target.closest('[data-cg-consent-open]')) openBanner();
  });

  const consent = store.get();
  if (consent === 'granted') {
    loadAnalytics();
  } else if (consent === null) {
    openBanner();
  }
})();
