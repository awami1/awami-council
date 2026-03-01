/**
 * stories.js — تحميل وعرض سِيَر وقصص أبناء العائلة على الصفحة العامة
 */
(function() {
  var currentPage = 1;
  var perPage = 9;
  var currentCategory = '';
  var currentPersonStatus = '';
  var allStories = [];

  var categoryLabels = {
    'biography':  'سيرة ذاتية',
    'self_made':  'قصة عصامية',
    'eulogy':     'رثاء',
    'tribute':    'مقال تكريمي',
    'other':      'أخرى'
  };

  function init() {
    loadStoriesData();
    initTabs();
  }

  function loadStoriesData() {
    var container = document.getElementById('stories-container');
    if (!container) return;

    container.innerHTML = '<div style="text-align:center;padding:40px"><div class="skeleton" style="width:200px;height:20px;margin:0 auto 16px;border-radius:8px"></div><div class="skeleton" style="width:300px;height:14px;margin:0 auto;border-radius:8px"></div></div>';

    fetch('/api/stories.php', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      allStories = data.data || [];
      renderStoriesPage();
    })
    .catch(function() {
      container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-muted)"><div style="font-size:52px;margin-bottom:12px;opacity:.4">&#128214;</div><p>تعذّر تحميل القصص</p></div>';
    });
  }

  function initTabs() {
    // فلاتر التصنيف
    var catTabs = document.getElementById('stories-cat-tabs');
    if (catTabs) {
      catTabs.querySelectorAll('.stories-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
          catTabs.querySelectorAll('.stories-tab').forEach(function(t) { t.classList.remove('active'); });
          tab.classList.add('active');
          currentCategory = tab.dataset.category || '';
          currentPage = 1;
          renderStoriesPage();
        });
      });
    }

    // فلاتر حالة الشخصية
    var statusTabs = document.getElementById('stories-status-tabs');
    if (statusTabs) {
      statusTabs.querySelectorAll('.stories-status-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
          statusTabs.querySelectorAll('.stories-status-tab').forEach(function(t) { t.classList.remove('active'); });
          tab.classList.add('active');
          currentPersonStatus = tab.dataset.personStatus || '';
          currentPage = 1;
          renderStoriesPage();
        });
      });
    }
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function renderStoriesPage() {
    var container = document.getElementById('stories-container');
    if (!container) return;

    var filtered = allStories.filter(function(s) {
      if (currentCategory && s.category !== currentCategory) return false;
      if (currentPersonStatus && s.person_status !== currentPersonStatus) return false;
      return true;
    });

    var totalPages = Math.ceil(filtered.length / perPage);
    var start = (currentPage - 1) * perPage;
    var pageItems = filtered.slice(start, start + perPage);

    if (pageItems.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-muted)"><div style="font-size:52px;margin-bottom:12px;opacity:.4">&#128214;</div><p>لا توجد قصص' + (currentCategory ? ' في هذا التصنيف' : '') + (currentPersonStatus ? ' لهذه الفئة' : '') + '</p><p style="font-size:13px;margin-top:8px">ترقبوا قريباً سِيَر وقصص ملهمة من أبناء العائلة</p></div>';
      return;
    }

    var html = '<div class="stories-grid">';
    pageItems.forEach(function(s) {
      var date = (s.published_at || s.created_at || '').substring(0, 10);
      var catClass = 'cat-' + (s.category || 'other');
      var catLabel = categoryLabels[s.category] || 'أخرى';
      var slug = s.slug || s.id;

      html += '<article class="story-card animate-in">';
      html += '<a href="/stories/' + escHtml(slug) + '" class="story-card-link">';

      // صورة الغلاف
      html += '<div class="story-card-cover">';
      if (s.cover_image) {
        html += '<img src="' + escHtml(s.cover_image) + '" alt="' + escHtml(s.title) + '" loading="lazy">';
      } else {
        html += '<div class="story-card-placeholder">&#128214;</div>';
      }
      html += '<span class="story-category-badge ' + catClass + '">' + escHtml(catLabel) + '</span>';
      if (s.is_pinned) {
        html += '<span class="story-pin-badge" title="مثبّت">&#128204;</span>';
      }
      if (s.person_status) {
        html += '<span class="story-person-status status-' + escHtml(s.person_status) + '">';
        html += s.person_status === 'deceased' ? 'رحمه الله' : 'حفظه الله';
        html += '</span>';
      }
      html += '</div>';

      // محتوى البطاقة
      html += '<div class="story-card-body">';

      if (s.person_name) {
        html += '<div class="story-card-person">';
        if (s.person_image) {
          html += '<img src="' + escHtml(s.person_image) + '" alt="" class="story-card-person-img" loading="lazy">';
        } else {
          html += '<div class="story-card-person-img-placeholder">&#128100;</div>';
        }
        html += '<span class="story-card-person-name">' + escHtml(s.person_name) + '</span>';
        html += '</div>';
      }

      html += '<h3 class="story-card-title">' + escHtml(s.title) + '</h3>';
      if (s.excerpt) {
        html += '<p class="story-card-excerpt">' + escHtml(s.excerpt) + '</p>';
      }

      html += '<div class="story-card-footer">';
      if (s.author_name) {
        html += '<span class="story-card-author">&#9998; ' + escHtml(s.author_name) + '</span>';
      }
      html += '<span class="story-card-date">&#128197; ' + date + '</span>';
      html += '</div>';

      html += '</div></a></article>';
    });
    html += '</div>';

    // Pagination
    if (totalPages > 1) {
      html += '<div class="stories-pagination">';
      if (currentPage > 1) html += '<button class="stories-page-btn" data-page="' + (currentPage - 1) + '">&#8594; السابق</button>';
      for (var i = 1; i <= totalPages; i++) {
        html += '<button class="stories-page-btn' + (i === currentPage ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
      }
      if (currentPage < totalPages) html += '<button class="stories-page-btn" data-page="' + (currentPage + 1) + '">التالي &#8592;</button>';
      html += '</div>';
    }

    container.innerHTML = html;

    // ربط أزرار الصفحات
    container.querySelectorAll('.stories-page-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        currentPage = parseInt(btn.dataset.page);
        renderStoriesPage();
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });

    // تفعيل أنيميشنات
    if (typeof window.initAnimations === 'function') {
      window.initAnimations();
    }
  }

  // تشغيل عند تحميل الصفحة
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
