/**
 * navbar.js — القائمة المتجاوبة وزر العودة للأعلى + Smart Navbar
 */
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    var header = document.querySelector('header');
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

    // Smart Navbar — يختفي عند التمرير لأسفل ويظهر عند التمرير لأعلى
    if (header) {
      var lastScrollY = 0;
      var ticking = false;
      var scrollThreshold = 80; // لا يختفي إلا بعد تجاوز 80px

      window.addEventListener('scroll', function() {
        if (!ticking) {
          requestAnimationFrame(function() {
            var currentScrollY = window.pageYOffset;
            if (currentScrollY > scrollThreshold) {
              if (currentScrollY > lastScrollY && currentScrollY - lastScrollY > 5) {
                // التمرير لأسفل — إخفاء
                header.classList.add('header-hidden');
                // إغلاق قائمة الجوال إن كانت مفتوحة
                if (nav && nav.classList.contains('open')) {
                  nav.classList.remove('open');
                  if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
                }
              } else if (lastScrollY > currentScrollY && lastScrollY - currentScrollY > 5) {
                // التمرير لأعلى — إظهار
                header.classList.remove('header-hidden');
              }
            } else {
              header.classList.remove('header-hidden');
            }
            lastScrollY = currentScrollY;
            ticking = false;
          });
          ticking = true;
        }
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
