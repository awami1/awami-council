/**
 * ajax-nav.js — تنقل AJAX سلس بين الصفحات بدون إعادة تحميل كاملة
 */
(function() {
  // الصفحات المعروفة والسكريبتات الخاصة بها
  var pageScripts = {
    '/':        ['/public/js/countdown.js'],
    '/council': ['/public/js/countdown.js'],
    '/tree':    ['https://d3js.org/d3.v7.min.js', '/public/js/tree.js'],
    '/gallery': ['/public/js/media.js'],
    '/news':    ['/public/js/news.js'],
    '/events':  [],
    '/stories': ['/public/js/stories.js'],
    '/eid':     ['/public/js/eid.js']
  };

  // سكريبتات تم تحميلها
  var loadedScripts = {};

  // هل يدعم المتصفح History API
  if (!window.history || !window.history.pushState) return;

  /**
   * تحميل سكريبت ديناميكياً
   */
  function loadScript(src) {
    return new Promise(function(resolve, reject) {
      if (loadedScripts[src]) { resolve(); return; }
      var script = document.createElement('script');
      var v = window.__ASSET_V__;
      script.src = src + (v ? (src.indexOf('?') === -1 ? '?v=' : '&v=') + v : '');
      script.onload = function() { loadedScripts[src] = true; resolve(); };
      script.onerror = reject;
      document.body.appendChild(script);
    });
  }

  /**
   * تحديث الرابط النشط في القائمة
   */
  function updateActiveNav(path) {
    var navLinks = document.querySelectorAll('#mainNav a');
    navLinks.forEach(function(a) {
      var href = a.getAttribute('href');
      if (href === path || (path === '/' && href === '/')) {
        a.classList.add('active');
      } else {
        a.classList.remove('active');
      }
    });
  }

  /**
   * تنقل AJAX لصفحة معينة
   */
  function navigateTo(url, pushState) {
    var pageContent = document.getElementById('page-content');
    if (!pageContent) return;

    // أنيميشن الخروج
    pageContent.classList.add('page-loading');

    var xhr = new XMLHttpRequest();
    var separator = url.indexOf('?') === -1 ? '?' : '&';
    xhr.open('GET', url + separator + '_ajax=1', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onload = function() {
      if (xhr.status >= 200 && xhr.status < 400) {
        // حماية: إذا رجعت صفحة كاملة بدل محتوى جزئي، نستخرج #page-content فقط
        var html = xhr.responseText;
        if (html.indexOf('<!DOCTYPE') !== -1 || html.indexOf('<html') !== -1) {
          var tmp = document.createElement('div');
          tmp.innerHTML = html;
          var inner = tmp.querySelector('#page-content');
          if (inner) html = inner.innerHTML;
        }
        // تحديث المحتوى
        pageContent.innerHTML = html;

        // تحديث URL
        if (pushState !== false) {
          history.pushState({ path: url }, '', url);
        }

        // تحديث الرابط النشط
        updateActiveNav(url);

        // تمرير لأعلى الصفحة
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // تحميل السكريبتات الخاصة بالصفحة
        var scripts = pageScripts[url] || [];
        // مسارات ديناميكية: /stories/{slug}
        if (!scripts.length && url.indexOf('/stories/') === 0) {
          scripts = pageScripts['/stories'] || [];
        }
        var promises = scripts.map(function(src) { return loadScript(src); });

        function runInlineScripts() {
          var inlineScripts = pageContent.querySelectorAll('script');
          inlineScripts.forEach(function(s) {
            var newScript = document.createElement('script');
            if (s.src) {
              newScript.src = s.src;
            } else {
              newScript.textContent = s.textContent;
            }
            s.parentNode.replaceChild(newScript, s);
          });
        }

        function afterScriptsLoaded() {
          // تنفيذ السكريبتات inline الموجودة في المحتوى
          runInlineScripts();

          // إعادة تفعيل الحركات
          if (typeof window.initAnimations === 'function') {
            window.initAnimations();
          }

          // شبكة أمان: تهيئة الشجرة بعد تحميل كل السكريبتات والبيانات
          if (typeof window.initFamilyTree === 'function' && window.__TREE_DATA__) {
            window.initFamilyTree();
          }

          // أنيميشن الدخول
          requestAnimationFrame(function() {
            pageContent.classList.remove('page-loading');
          });
        }

        Promise.all(promises).then(afterScriptsLoaded).catch(function() {
          // حتى لو فشل سكريبت خارجي، نفّذ السكريبتات inline وأظهر المحتوى
          runInlineScripts();
          requestAnimationFrame(function() {
            pageContent.classList.remove('page-loading');
          });
        });
      } else {
        // خطأ — تحميل عادي
        window.location.href = url;
      }
    };

    xhr.onerror = function() {
      window.location.href = url;
    };

    xhr.send();
  }

  /**
   * هل الرابط داخلي ومناسب للـ AJAX
   */
  function isInternalLink(a) {
    // تجاهل الروابط الخارجية وروابط الأنكور والأدمن
    if (!a.href) return false;
    if (a.target === '_blank') return false;
    if (a.hasAttribute('download')) return false;
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#') return false;
    if (href.indexOf('http') === 0 && href.indexOf(location.origin) !== 0) return false;
    if (href.indexOf('/admin') === 0 || href.indexOf('/api/') === 0) return false;
    if (href.indexOf('wa.me') !== -1 || href.indexOf('whatsapp') !== -1) return false;
    return true;
  }

  /**
   * اعتراض النقر على الروابط
   */
  document.addEventListener('click', function(e) {
    var a = e.target.closest('a');
    if (!a || !isInternalLink(a)) return;

    var href = a.getAttribute('href');
    // لا نفعل شيئاً إذا كنا بالفعل في نفس الصفحة
    if (href === location.pathname) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    e.preventDefault();
    navigateTo(href, true);

    // إغلاق قائمة الجوال إن كانت مفتوحة
    var nav = document.getElementById('mainNav');
    var menuBtn = document.getElementById('menuBtn');
    if (nav && nav.classList.contains('open')) {
      nav.classList.remove('open');
      if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
    }
  });

  /**
   * التعامل مع زر الرجوع/التقدم في المتصفح
   */
  window.addEventListener('popstate', function(e) {
    navigateTo(location.pathname, false);
  });

  // تسجيل الحالة الأولية
  history.replaceState({ path: location.pathname }, '', location.pathname);

  // تسجيل السكريبتات الموجودة حالياً
  document.querySelectorAll('script[src]').forEach(function(s) {
    loadedScripts[s.src] = true;
    // أيضاً نسجل بالمسار النسبي
    try {
      var url = new URL(s.src);
      loadedScripts[url.pathname] = true;
    } catch(e) {}
  });
})();
