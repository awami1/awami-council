/**
 * news.js — تحميل وعرض الأخبار على الصفحة العامة
 */
(function() {
  var currentPage = 1;
  var perPage = 6;
  var currentCategory = '';
  var allNews = [];

  function init() {
    loadNewsData();
    initTabs();
  }

  function loadNewsData() {
    var container = document.getElementById('news-container');
    if (!container) return;

    container.innerHTML = '<div style="text-align:center;padding:40px"><div class="skeleton" style="width:200px;height:20px;margin:0 auto 16px;border-radius:8px"></div><div class="skeleton" style="width:300px;height:14px;margin:0 auto;border-radius:8px"></div></div>';

    fetch('/api/news.php', {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      allNews = data.data || [];
      renderNewsPage();
    })
    .catch(function() {
      container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-muted)"><div style="font-size:52px;margin-bottom:12px;opacity:.4">&#128240;</div><p>تعذّر تحميل الأخبار</p></div>';
    });
  }

  function initTabs() {
    var tabsEl = document.getElementById('news-tabs');
    if (!tabsEl) return;
    tabsEl.querySelectorAll('.news-tab').forEach(function(tab) {
      tab.addEventListener('click', function() {
        tabsEl.querySelectorAll('.news-tab').forEach(function(t) { t.classList.remove('active'); });
        tab.classList.add('active');
        currentCategory = tab.dataset.category || '';
        currentPage = 1;
        renderNewsPage();
      });
    });
  }

  function renderNewsPage() {
    var container = document.getElementById('news-container');
    if (!container) return;

    var filtered = currentCategory
      ? allNews.filter(function(n) { return n.category === currentCategory; })
      : allNews;

    var totalPages = Math.ceil(filtered.length / perPage);
    var start = (currentPage - 1) * perPage;
    var pageItems = filtered.slice(start, start + perPage);

    if (pageItems.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-muted)"><div style="font-size:52px;margin-bottom:12px;opacity:.4">&#128240;</div><p>لا توجد أخبار' + (currentCategory ? ' في هذا التصنيف' : ' حالياً') + '</p><p style="font-size:13px;margin-top:8px">ترقبوا قريباً آخر أخبار وفعاليات العائلة</p></div>';
      return;
    }

    var html = '<div class="news-grid">';
    pageItems.forEach(function(n) {
      var date = (n.created_at || '').substring(0, 10);
      html += '<article class="news-card animate-in">';
      if (n.image) {
        html += '<div class="news-card-img"><img src="' + n.image + '" alt="' + n.title + '" loading="lazy"></div>';
      } else {
        html += '<div class="news-card-img news-card-placeholder"><span>&#128240;</span></div>';
      }
      html += '<div class="news-card-body">';
      html += '<div class="news-card-meta"><span class="news-card-cat">' + (n.category || 'عام') + '</span><span class="news-card-date">&#128197; ' + date + '</span></div>';
      html += '<h3 class="news-card-title">' + n.title + '</h3>';
      if (n.excerpt) html += '<p class="news-card-excerpt">' + n.excerpt + '</p>';
      if (n.content) html += '<details class="news-card-details"><summary>اقرأ المزيد</summary><div class="news-card-content">' + n.content.replace(/\n/g, '<br>') + '</div></details>';
      if (n.author) html += '<div class="news-card-author">&#128100; ' + n.author + '</div>';
      html += '</div></article>';
    });
    html += '</div>';

    // Pagination
    if (totalPages > 1) {
      html += '<div class="news-pagination">';
      if (currentPage > 1) html += '<button class="news-page-btn" data-page="' + (currentPage - 1) + '">&#8594; السابق</button>';
      for (var i = 1; i <= totalPages; i++) {
        html += '<button class="news-page-btn' + (i === currentPage ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
      }
      if (currentPage < totalPages) html += '<button class="news-page-btn" data-page="' + (currentPage + 1) + '">التالي &#8592;</button>';
      html += '</div>';
    }

    container.innerHTML = html;

    // ربط أزرار الصفحات
    container.querySelectorAll('.news-page-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        currentPage = parseInt(btn.dataset.page);
        renderNewsPage();
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
