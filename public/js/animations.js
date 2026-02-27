/**
 * animations.js — حركات التمرير والعدادات
 */
(function() {
  /**
   * Scroll reveal باستخدام IntersectionObserver
   */
  function initScrollReveal() {
    var elements = document.querySelectorAll('.animate-in');
    if (!elements.length) return;

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          // تأخير متدرج حسب ترتيب العنصر
          var siblings = entry.target.parentElement ?
            Array.from(entry.target.parentElement.querySelectorAll('.animate-in')) : [];
          var index = siblings.indexOf(entry.target);
          var delay = Math.min(index * 80, 400);

          setTimeout(function() {
            entry.target.classList.add('visible');
          }, delay);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    elements.forEach(function(el) { observer.observe(el); });
  }

  /**
   * عدادات متحركة
   */
  function initCounters() {
    var counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting && !entry.target.dataset.counted) {
          entry.target.dataset.counted = '1';
          var target = parseInt(entry.target.dataset.count, 10);
          var prefix = entry.target.dataset.prefix || '';
          var suffix = entry.target.dataset.suffix || '';
          var duration = 2000;
          var start = performance.now();

          function easeOutQuart(t) { return 1 - Math.pow(1 - t, 4); }

          function update(now) {
            var elapsed = now - start;
            var progress = Math.min(elapsed / duration, 1);
            var value = Math.round(easeOutQuart(progress) * target);
            entry.target.textContent = prefix + value + suffix;
            if (progress < 1) requestAnimationFrame(update);
          }
          requestAnimationFrame(update);
        }
      });
    }, { threshold: 0.3 });

    counters.forEach(function(el) { observer.observe(el); });
  }

  /**
   * Accordion — أكورديون قابل للطي
   */
  function initAccordions() {
    document.querySelectorAll('.accordion-header').forEach(function(btn) {
      if (btn.dataset.accordionInit) return;
      btn.dataset.accordionInit = '1';

      btn.addEventListener('click', function() {
        var item = btn.closest('.accordion-item');
        var body = item.querySelector('.accordion-body');
        var inner = item.querySelector('.accordion-body-inner');
        if (!body || !inner) return;

        var isOpen = item.classList.contains('open');

        // إغلاق جميع العناصر المفتوحة في نفس الأكورديون
        var accordion = item.closest('.accordion');
        if (accordion) {
          accordion.querySelectorAll('.accordion-item.open').forEach(function(openItem) {
            if (openItem !== item) {
              openItem.classList.remove('open');
              openItem.querySelector('.accordion-body').style.maxHeight = '0';
            }
          });
        }

        if (isOpen) {
          item.classList.remove('open');
          body.style.maxHeight = '0';
        } else {
          item.classList.add('open');
          body.style.maxHeight = inner.scrollHeight + 'px';
        }
      });
    });
  }

  /**
   * Parallax scrolling للأقسام الكبيرة
   */
  function initParallax() {
    var hero = document.querySelector('.hero');
    var aboutVisual = document.querySelector('.about-visual');
    if (!hero && !aboutVisual) return;

    // التحقق من تفضيل تقليل الحركة
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var ticking = false;

    function updateParallax() {
      var scrollY = window.pageYOffset;

      if (hero) {
        // تحريك النص الخلفي (::before) عبر CSS variable
        var heroBottom = hero.offsetTop + hero.offsetHeight;
        if (scrollY < heroBottom) {
          var offset = scrollY * 0.3;
          hero.style.setProperty('--parallax-y', offset + 'px');
        }
      }

      if (aboutVisual) {
        var rect = aboutVisual.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
          var progress = (window.innerHeight - rect.top) / (window.innerHeight + rect.height);
          var shift = (progress - 0.5) * 40;
          aboutVisual.style.transform = 'translateY(' + shift + 'px)';
        }
      }

      ticking = false;
    }

    window.addEventListener('scroll', function() {
      if (!ticking) {
        requestAnimationFrame(updateParallax);
        ticking = true;
      }
    });
  }

  /**
   * تفعيل جميع الحركات
   */
  function initAnimations() {
    initScrollReveal();
    initCounters();
    initAccordions();
    initParallax();
  }

  // تصدير للاستخدام مع AJAX navigation
  window.initAnimations = initAnimations;

  document.addEventListener('DOMContentLoaded', initAnimations);
})();
