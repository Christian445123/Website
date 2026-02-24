/**
 * Chris_Gaming445 – Custom JS
 */
(function () {
  'use strict';

  // ---- Scroll progress bar ----
  const updateScrollProgress = () => {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
    document.documentElement.style.setProperty('--scroll-pct', pct.toFixed(2));
  };

  // ---- Navbar: add .scrolled class on scroll ----
  const navbar = document.getElementById('mainNav');
  const onScroll = () => {
    updateScrollProgress();
    if (navbar) {
      navbar.classList.toggle('scrolled', window.scrollY > 30);
    }
    // Scroll-to-top visibility
    if (scrollBtn) {
      scrollBtn.classList.toggle('visible', window.scrollY > 400);
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll(); // run on load

  // ---- Scroll to Top ----
  const scrollBtn = document.getElementById('scrollTop');
  if (scrollBtn) {
    scrollBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ---- Animate elements on scroll (Intersection Observer) ----
  const animEls = document.querySelectorAll('[data-cg-animate]');
  if (animEls.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          el.style.opacity = '';
          el.classList.add('animate__animated', el.dataset.cgAnimate || 'animate__fadeInUp');
          el.style.animationDelay = el.dataset.cgDelay || '0s';
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.12 });

    animEls.forEach(el => {
      el.style.opacity = '0';
      observer.observe(el);
    });
  }
})();
