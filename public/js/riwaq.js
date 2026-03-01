/**
 * riwaq.js — الرِّوَاق: معرض سينمائي تفاعلي
 * التمرير الأفقي + overlay القصة + ستارة الافتتاح
 */
(function() {
  'use strict';

  var stories  = window.__RIWAQ_STORIES__ || [];
  var gallery  = document.getElementById('riwaqGallery');
  var overlay  = document.getElementById('riwaqOverlay');
  var curtain  = document.getElementById('riwaqCurtain');

  // ── ستارة الافتتاح ──

  if (curtain) {
    curtain.addEventListener('animationend', function(e) {
      if (e.animationName === 'curtainExit') {
        curtain.style.display = 'none';
      }
    });
  }

  // ── التمرير الأفقي (عجلة الماوس → أفقي) ──

  if (gallery && window.innerWidth >= 768) {
    gallery.addEventListener('wheel', function(e) {
      // تجاهل التمرير الصغير جداً
      if (Math.abs(e.deltaY) < 3) return;
      e.preventDefault();
      gallery.scrollLeft -= e.deltaY * 1.5;
    }, { passive: false });
  }

  // ── إظهار اللوحات عند الظهور (IntersectionObserver) ──

  var panelObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('riwaq-visible');
      }
    });
  }, {
    threshold: 0.15,
    root: window.innerWidth >= 768 ? gallery : null
  });

  document.querySelectorAll('.riwaq-panel--story').forEach(function(panel) {
    panelObserver.observe(panel);
  });

  // ── النقر على بطاقة → فتح القصة ──

  if (gallery) {
    gallery.addEventListener('click', function(e) {
      var panel = e.target.closest('.riwaq-panel--story');
      if (!panel) return;

      var id = panel.dataset.id;
      var story = stories.find(function(s) { return s.id === id; });
      if (!story) return;

      openOverlay(story, panel);
    });
  }

  // ── فتح overlay القصة ──

  function openOverlay(story, panel) {
    if (!overlay) return;

    var cp = panel.dataset.cp || '#0B3D2E';
    var cs = panel.dataset.cs || '#1A6B4A';
    var ca = panel.dataset.ca || '#D4AF37';

    // Hero
    var hero = document.getElementById('riwaqOverlayHero');
    hero.style.setProperty('--cp', cp);
    hero.style.setProperty('--cs', cs);
    hero.style.setProperty('--ca', ca);

    document.getElementById('riwaqBadge').textContent = story.type || '';
    document.getElementById('riwaqYear').textContent = story.year_range || '';
    document.getElementById('riwaqTitle').textContent = story.title || '';
    document.getElementById('riwaqSubtitle').textContent = story.subtitle || '';

    // Meta
    var authorEl = document.getElementById('riwaqAuthor');
    var readEl   = document.getElementById('riwaqReadTime');
    authorEl.textContent = story.author_name ? '✎ ' + story.author_name : '';
    readEl.textContent   = story.read_time ? story.read_time + ' دقائق قراءة' : '';

    // Body
    var body = document.getElementById('riwaqBody');
    body.style.setProperty('--ca', ca);

    var html = '';
    if (story.full_text) {
      html = story.full_text;
    }

    if (story.author_name) {
      html += '<div class="riwaq-overlay__author-tag">— ' + escapeHtml(story.author_name) + '</div>';
    }

    body.innerHTML = html;

    // تأثير fadeSlide متتابع للعناصر
    var elements = body.querySelectorAll('p, blockquote, h2, h3');
    for (var i = 0; i < elements.length; i++) {
      elements[i].style.animationDelay = (0.15 + i * 0.08) + 's';
    }

    // إظهار
    overlay.classList.add('riwaq-overlay--active');
    overlay.scrollTop = 0;
    document.body.style.overflow = 'hidden';
  }

  // ── إغلاق overlay ──

  function closeOverlay() {
    if (!overlay) return;
    overlay.classList.remove('riwaq-overlay--active');
    document.body.style.overflow = '';
  }

  var closeBtn = document.getElementById('riwaqOverlayClose');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeOverlay);
  }

  // Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && overlay && overlay.classList.contains('riwaq-overlay--active')) {
      closeOverlay();
    }
  });

  // ── لوحة المفاتيح — ← → للتنقل ──

  document.addEventListener('keydown', function(e) {
    if (!gallery || (overlay && overlay.classList.contains('riwaq-overlay--active'))) return;
    if (window.innerWidth < 768) return;

    var panels = gallery.querySelectorAll('.riwaq-panel--story');
    if (!panels.length) return;

    var panelWidth = panels[0].offsetWidth;

    if (e.key === 'ArrowLeft') {
      gallery.scrollBy({ left: -panelWidth, behavior: 'smooth' });
    } else if (e.key === 'ArrowRight') {
      gallery.scrollBy({ left: panelWidth, behavior: 'smooth' });
    }
  });

  // ── Helper ──

  function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

})();
