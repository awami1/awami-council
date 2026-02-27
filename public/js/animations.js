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
   * تفعيل جميع الحركات
   */
  function initAnimations() {
    initScrollReveal();
    initCounters();
  }

  // تصدير للاستخدام مع AJAX navigation
  window.initAnimations = initAnimations;

  document.addEventListener('DOMContentLoaded', initAnimations);
})();
