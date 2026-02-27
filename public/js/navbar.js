/**
 * navbar.js — القائمة المتجاوبة وزر العودة للأعلى
 */
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    var nav = document.getElementById('mainNav');
    var menuBtn = document.getElementById('menuBtn');
    var scrollBtn = document.getElementById('scrollTop');

    // Mobile menu toggle
    if (menuBtn && nav) {
      menuBtn.addEventListener('click', function() {
        var isOpen = nav.classList.toggle('open');
        menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
      nav.querySelectorAll('a').forEach(function(a) {
        a.addEventListener('click', function() {
          nav.classList.remove('open');
          menuBtn.setAttribute('aria-expanded', 'false');
        });
      });
    }

    // Scroll top button
    if (scrollBtn) {
      scrollBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
      window.addEventListener('scroll', function() {
        scrollBtn.classList.toggle('show', window.pageYOffset > 400);
      });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(a) {
      a.addEventListener('click', function(e) {
        var target = document.querySelector(a.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  });
})();
