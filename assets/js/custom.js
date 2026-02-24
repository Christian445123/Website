/**
 * Chris_Gaming445 – Custom JS
 */
(function () {
  'use strict';

  // Navbar: add .scrolled class on scroll
  const navbar = document.getElementById('mainNav');
  if (navbar) {
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 30);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

})();
