/**
 * stories.js — تحميل وعرض سِيَر وقصص أبناء العائلة على الصفحة العامة
 * يدعم صفحة القائمة (/stories) وصفحة القصة المفردة (/stories/{slug})
 */
(function() {
  var currentPage = 1;
  var perPage = 9;
  var currentCategory = '';
  var allStories = [];

  var categoryLabels = {
    'biography':  'سيرة ذاتية',
    'self_made':  'قصة عصامية',
    'eulogy':     'رثاء',
    'tribute':    'مقال تكريمي',
    'other':      'أخرى'
  };

  var categoryColors = {
    'biography':  'rgba(26,92,50,.88)',
    'self_made':  'rgba(200,168,75,.92)',
    'eulogy':     'rgba(27,52,86,.88)',
    'tribute':    'rgba(139,69,19,.88)',
    'other':      'rgba(107,124,110,.88)'
  };

  function init() {
    // صفحة القصة المفردة — التحميل الديناميكي عند فشل SSR
    var storyLoading = document.getElementById('story-loading');
    var storySingle = document.getElementById('story-single');
    if (storyLoading && storySingle && storySingle.dataset.slug) {
      loadSingleStory(storySingle.dataset.slug);
      return;
    }

    // صفحة القائمة
    loadStoriesData();
    initTabs();
  }

  // ──────────────────────────────────────────────────
  // صفحة القائمة
  // ──────────────────────────────────────────────────

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
  }

  function escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function estimateReadingTime(text) {
    if (!text) return '';
    var plainText = text.replace(/<[^>]*>/g, '').trim();
    var words = plainText.split(/\s+/).filter(function(w) { return w.length > 0; });
    var minutes = Math.ceil(words.length / 180);
    if (minutes < 1) minutes = 1;
    return minutes + ' دقيقة للقراءة';
  }

  function renderStoriesPage() {
    var container = document.getElementById('stories-container');
    if (!container) return;

    var filtered = allStories.filter(function(s) {
      if (currentCategory && s.category !== currentCategory) return false;
      return true;
    });

    var totalPages = Math.ceil(filtered.length / perPage);
    var start = (currentPage - 1) * perPage;
    var pageItems = filtered.slice(start, start + perPage);

    if (pageItems.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-muted)"><div style="font-size:52px;margin-bottom:12px;opacity:.4">&#128214;</div><p>لا توجد قصص' + (currentCategory ? ' في هذا التصنيف' : '') + '</p><p style="font-size:13px;margin-top:8px">ترقبوا قريباً سِيَر وقصص ملهمة من أبناء العائلة</p></div>';
      return;
    }

    var html = '<div class="stories-grid">';
    pageItems.forEach(function(s) {
      var date = (s.published_at || s.created_at || '').substring(0, 10);
      var catClass = 'cat-' + (s.category || 'other');
      var catLabel = categoryLabels[s.category] || 'أخرى';
      var slug = s.slug || s.id;
      var readTime = estimateReadingTime(s.content || s.excerpt || '');

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
      if (readTime) {
        html += '<span class="story-card-readtime">&#9201; ' + readTime + '</span>';
      }
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

  // ──────────────────────────────────────────────────
  // صفحة القصة المفردة — التحميل الديناميكي
  // ──────────────────────────────────────────────────

  function loadSingleStory(slug) {
    var wrapper = document.getElementById('story-single');
    if (!wrapper) return;

    fetch('/api/stories.php?id=' + encodeURIComponent(slug), {
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function(res) { return res.json(); })
    .then(function(result) {
      var story = result.data;
      if (!story) {
        renderStoryNotFound(wrapper);
        return;
      }
      renderSingleStory(wrapper, story);
    })
    .catch(function() {
      renderStoryNotFound(wrapper);
    });
  }

  function renderSingleStory(wrapper, story) {
    var catLabel = categoryLabels[story.category] || 'أخرى';
    var catColor = categoryColors[story.category] || 'rgba(107,124,110,.88)';
    var date = (story.published_at || story.created_at || '').substring(0, 10);
    var readTime = estimateReadingTime(story.content || '');
    var html = '';

    // شريط تقدم القراءة
    html += '<div class="story-progress-bar" id="story-progress-bar"></div>';

    // زر العودة
    html += '<div style="max-width:800px;margin:0 auto;padding:20px 24px 0">';
    html += '<a href="/stories" class="story-back-btn">&#8594; العودة لجميع القصص</a>';
    html += '</div>';

    // صورة الغلاف أو العنوان
    if (story.cover_image) {
      html += '<div class="story-hero">';
      html += '<img src="' + escHtml(story.cover_image) + '" alt="' + escHtml(story.title) + '">';
      html += '<div class="story-hero-overlay">';
      html += '<span class="story-hero-category" style="background:' + catColor + '">' + escHtml(catLabel) + '</span>';
      html += '<h1 class="story-hero-title">' + escHtml(story.title) + '</h1>';
      html += '<div class="story-hero-meta">';
      if (story.author_name) html += '<span>&#9998; ' + escHtml(story.author_name) + '</span>';
      html += '<span>&#128197; ' + date + '</span>';
      if (readTime) html += '<span>&#9201; ' + readTime + '</span>';
      if (story.person_name) html += '<span>&#128100; ' + escHtml(story.person_name) + '</span>';
      html += '</div></div></div>';
    } else {
      html += '<div style="max-width:800px;margin:0 auto;padding:0 24px 20px">';
      html += '<span class="story-category-badge cat-' + escHtml(story.category || 'other') + '" style="position:static;display:inline-block;margin-bottom:12px">' + escHtml(catLabel) + '</span>';
      html += '<h1 style="font-family:var(--font-heading);font-size:32px;font-weight:700;color:var(--text);line-height:1.5;margin-bottom:12px">' + escHtml(story.title) + '</h1>';
      html += '<div style="display:flex;gap:20px;font-size:14px;color:var(--text-muted);flex-wrap:wrap">';
      if (story.author_name) html += '<span>&#9998; ' + escHtml(story.author_name) + '</span>';
      html += '<span>&#128197; ' + date + '</span>';
      if (readTime) html += '<span>&#9201; ' + readTime + '</span>';
      html += '</div></div>';
    }

    html += '<div class="story-content-wrap">';

    // بطاقة الشخصية
    if (story.person_name) {
      html += '<div class="story-person-card">';
      if (story.person_image) {
        html += '<img src="' + escHtml(story.person_image) + '" alt="' + escHtml(story.person_name) + '" class="story-person-card-img">';
      } else {
        html += '<div class="story-person-card-img-placeholder">&#128100;</div>';
      }
      html += '<div class="story-person-card-info">';
      html += '<div class="story-person-card-name">' + escHtml(story.person_name) + '</div>';
      if (story.person_bio) {
        html += '<div class="story-person-card-bio">' + escHtml(story.person_bio) + '</div>';
      }
      html += '</div></div>';
    }

    // المحتوى الرئيسي
    html += '<div class="story-article-content">' + (story.content || '') + '</div>';

    // معلومات الكاتب
    if (story.author_name) {
      html += '<div class="story-author-info">';
      html += '<div class="story-author-avatar">&#9998;</div>';
      html += '<div class="story-author-details">';
      html += '<strong>' + escHtml(story.author_name) + '</strong>';
      html += '<span>&#128197; ' + date + '</span>';
      html += '</div></div>';
    }

    // أزرار المشاركة
    var pageUrl = window.location.href;
    var shareText = encodeURIComponent(story.title + ' — ');
    var shareUrl = encodeURIComponent(pageUrl);
    html += '<div class="story-share-bar">';
    html += '<span class="story-share-label">&#128279; مشاركة:</span>';
    html += '<a class="story-share-btn whatsapp" href="https://wa.me/?text=' + shareText + '%20' + shareUrl + '" target="_blank" rel="noopener">واتساب</a>';
    html += '<a class="story-share-btn twitter" href="https://twitter.com/intent/tweet?text=' + shareText + '&url=' + shareUrl + '" target="_blank" rel="noopener">تويتر</a>';
    html += '<button class="story-share-btn copy" onclick="copyStoryLink()">&#128203; نسخ الرابط</button>';
    html += '</div>';

    // زر العودة
    html += '<div style="text-align:center;margin-top:40px">';
    html += '<a href="/stories" class="story-back-btn">&#8594; العودة لجميع القصص</a>';
    html += '</div>';

    html += '</div>'; // story-content-wrap

    wrapper.className = 'story-single';
    wrapper.innerHTML = html;

    // تفعيل شريط تقدم القراءة
    initProgressBar();
  }

  function renderStoryNotFound(wrapper) {
    wrapper.innerHTML = '<div style="max-width:800px;margin:0 auto;padding:20px 24px 0">' +
      '<a href="/stories" class="story-back-btn">&#8594; العودة لجميع القصص</a>' +
      '</div>' +
      '<div style="text-align:center;padding:80px 20px;color:var(--text-muted)">' +
      '<div style="font-size:64px;margin-bottom:16px;opacity:.4">&#128214;</div>' +
      '<h2 style="font-size:22px;font-weight:700;color:var(--text);margin-bottom:8px">الموضوع غير موجود</h2>' +
      '<p style="font-size:15px">قد يكون الموضوع محذوفاً أو الرابط غير صحيح</p>' +
      '<a href="/stories" style="display:inline-block;margin-top:24px;padding:10px 28px;background:var(--green);color:#fff;border-radius:var(--radius);text-decoration:none;font-weight:600">تصفّح جميع القصص</a>' +
      '</div>';
  }

  // ──────────────────────────────────────────────────
  // شريط تقدم القراءة
  // ──────────────────────────────────────────────────

  function initProgressBar() {
    var bar = document.getElementById('story-progress-bar');
    if (!bar) return;
    window.addEventListener('scroll', function updateProgress() {
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      var docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      var progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      bar.style.width = Math.min(progress, 100) + '%';
    });
  }

  // ──────────────────────────────────────────────────
  // نسخ الرابط مع إشعار
  // ──────────────────────────────────────────────────

  window.copyStoryLink = function() {
    var url = window.location.href;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url).then(function() {
        showStoryToast('تم نسخ الرابط بنجاح');
      });
    } else {
      var temp = document.createElement('input');
      temp.value = url;
      document.body.appendChild(temp);
      temp.select();
      document.execCommand('copy');
      document.body.removeChild(temp);
      showStoryToast('تم نسخ الرابط بنجاح');
    }
  };

  function showStoryToast(message) {
    var existing = document.querySelector('.story-toast');
    if (existing) existing.remove();
    var toast = document.createElement('div');
    toast.className = 'story-toast';
    toast.textContent = '\u2705 ' + message;
    document.body.appendChild(toast);
    requestAnimationFrame(function() { toast.classList.add('visible'); });
    setTimeout(function() {
      toast.classList.remove('visible');
      setTimeout(function() { toast.remove(); }, 300);
    }, 2500);
  }

  // تصدير دالة التحميل الديناميكي
  window.__storiesLoadSingle = loadSingleStory;

  // تشغيل عند تحميل الصفحة
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
