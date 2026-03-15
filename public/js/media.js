// ============================================================
// media.js — معرض الألبومات + lightbox محسّن (صور + فيديو + يوتيوب)
// ============================================================

var _lbItems = []; // [{type, src, title}]
var _lbIndex = 0;

// ── Focus trap (a11y) ──
function trapFocus(el) {
  var focusable = el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
  if (!focusable.length) return;
  var first = focusable[0], last = focusable[focusable.length - 1];
  el.addEventListener('keydown', function(e) {
    if (e.key !== 'Tab') return;
    if (e.shiftKey) { if (document.activeElement === first) { e.preventDefault(); last.focus(); } }
    else { if (document.activeElement === last) { e.preventDefault(); first.focus(); } }
  });
}

// ── Lightbox ──

function _lbShow() {
  var item = _lbItems[_lbIndex];
  if (!item) return;
  var img   = document.getElementById('lightbox-img');
  var video = document.getElementById('lightbox-video');
  var iWrap = document.getElementById('lightbox-iframe-wrap');
  var caption = document.getElementById('lightbox-caption');

  // إخفاء الكل
  if (img)   img.style.display = 'none';
  if (video) { video.style.display = 'none'; video.pause(); video.removeAttribute('src'); }
  if (iWrap) { iWrap.style.display = 'none'; var ifr = document.getElementById('lightbox-iframe'); if (ifr) ifr.src = ''; }

  if (item.type === 'images') {
    if (img) { img.src = item.src; img.alt = item.title; img.style.display = 'block'; }
  } else if (item.type === 'videos') {
    if (video) { video.src = item.src; video.style.display = 'block'; }
  } else if (item.type === 'youtube') {
    if (iWrap) {
      var ifr = document.getElementById('lightbox-iframe');
      if (ifr) ifr.src = 'https://www.youtube.com/embed/' + item.src + '?autoplay=1';
      iWrap.style.display = 'block';
    }
  }

  if (caption) caption.textContent = item.title || '';

  // إخفاء/إظهار أسهم التنقل
  var prev = document.querySelector('.lightbox-prev');
  var next = document.querySelector('.lightbox-next');
  if (prev) prev.style.display = _lbItems.length > 1 ? '' : 'none';
  if (next) next.style.display = _lbItems.length > 1 ? '' : 'none';
}

function openLightbox(type, src, title) {
  var lb = document.getElementById('lightbox');
  if (!lb) return;

  // بناء قائمة العناصر من الميديا المرئية
  _lbItems = _collectLightboxItems();
  _lbIndex = _lbItems.findIndex(function(i) { return i.type === type && i.src === src; });
  if (_lbIndex < 0) {
    _lbItems = [{ type: type, src: src, title: title }];
    _lbIndex = 0;
  }

  lb.classList.add('active');
  document.body.style.overflow = 'hidden';
  _lbShow();
  trapFocus(lb);
}

function closeLightbox() {
  var lb = document.getElementById('lightbox');
  if (!lb) return;
  lb.classList.remove('active');
  document.body.style.overflow = '';

  var video = document.getElementById('lightbox-video');
  if (video) { video.pause(); video.removeAttribute('src'); }
  var ifr = document.getElementById('lightbox-iframe');
  if (ifr) ifr.src = '';
}

function lightboxNav(dir) {
  _lbIndex = (_lbIndex + dir + _lbItems.length) % _lbItems.length;
  _lbShow();
}

function _collectLightboxItems() {
  var items = [];
  var detailView = document.getElementById('gallery-album-detail');
  var gridId = (detailView && detailView.style.display !== 'none') ? 'album-media-grid' : null;

  var grids = gridId ? [document.getElementById(gridId)] : document.querySelectorAll('.media-grid');
  grids.forEach(function(grid) {
    if (!grid) return;
    grid.querySelectorAll('.media-item').forEach(function(el) {
      var clickEl = el.querySelector('[onclick]');
      if (!clickEl) return;
      var onclick = clickEl.getAttribute('onclick') || '';
      var match = onclick.match(/openLightbox\('([^']+)','([^']+)','([^']*)'\)/);
      if (match) {
        items.push({ type: match[1], src: match[2], title: match[3] });
      }
    });
  });
  return items;
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
  var lb = document.getElementById('lightbox');
  if (!lb || !lb.classList.contains('active')) return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowLeft') lightboxNav(1);
  if (e.key === 'ArrowRight') lightboxNav(-1);
});

// إغلاق بالضغط على الخلفية
document.addEventListener('click', function(e) {
  var lb = document.getElementById('lightbox');
  if (!lb || !lb.classList.contains('active')) return;
  if (e.target === lb) closeLightbox();
});

// ── عرض ألبوم ──

async function openAlbum(albumId) {
  var albumsView = document.getElementById('gallery-albums-view');
  var detailView = document.getElementById('gallery-album-detail');
  var grid       = document.getElementById('album-media-grid');
  var titleEl    = document.getElementById('album-detail-title');
  var descEl     = document.getElementById('album-detail-desc');

  if (!albumsView || !detailView || !grid) return;

  albumsView.style.display = 'none';
  detailView.style.display = 'block';
  grid.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">جاري التحميل...</div>';

  try {
    var r = await AlbumsAPI.get(albumId);
    var album = r.data;
    if (titleEl) titleEl.textContent = album.title || '';
    if (descEl) descEl.textContent = album.description || '';

    var mediaItems = album.media || [];
    if (!mediaItems.length) {
      grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)"><p>لا توجد وسائط في هذا الألبوم</p></div>';
      return;
    }

    var html = '';
    mediaItems.forEach(function(item) {
      html += _renderMediaItem(item);
    });
    grid.innerHTML = html;

    var obs = new IntersectionObserver(function(entries) {
      entries.forEach(function(e) { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.1 });
    grid.querySelectorAll('.animate-in').forEach(function(el) { obs.observe(el); });

    _formatDates(grid);
  } catch (e) {
    grid.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">خطأ في تحميل الألبوم</div>';
  }
}

function closeAlbum() {
  var albumsView = document.getElementById('gallery-albums-view');
  var detailView = document.getElementById('gallery-album-detail');
  if (albumsView) albumsView.style.display = '';
  if (detailView) detailView.style.display = 'none';
}

function _renderMediaItem(item) {
  var type = item.type || 'images';
  var tags = item.tags || [];
  if (typeof tags === 'string') { try { tags = JSON.parse(tags); } catch(e) { tags = []; } }
  var html = '<div class="media-item animate-in" data-type="' + type + '" data-id="' + _escAttr(item.id || '') + '">';

  if (type === 'videos') {
    html += '<div class="media-item-thumb" onclick="openLightbox(\'videos\',\'' + _escAttr(item.url) + '\',\'' + _escAttr(item.title) + '\')">';
    html += '<video preload="metadata" style="width:100%;height:100%;object-fit:cover"><source src="' + _escAttr(item.url) + '"></video>';
    html += '<div class="media-play-overlay">▶</div></div>';
  } else if (type === 'youtube') {
    var ytMatch = (item.url || '').match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
    var ytId = ytMatch ? ytMatch[1] : '';
    if (ytId) {
      html += '<div class="media-item-thumb" onclick="openLightbox(\'youtube\',\'' + ytId + '\',\'' + _escAttr(item.title) + '\')">';
      html += '<img src="https://img.youtube.com/vi/' + ytId + '/mqdefault.jpg" alt="' + _escAttr(item.title) + '" loading="lazy" style="width:100%;height:100%;object-fit:cover">';
      html += '<div class="media-play-overlay">▶</div></div>';
    }
  } else {
    html += '<img src="' + _escAttr(item.url) + '" alt="' + _escAttr(item.title) + '" loading="lazy" onclick="openLightbox(\'images\',\'' + _escAttr(item.url) + '\',\'' + _escAttr(item.title) + '\')">';
  }

  html += '<div class="media-item-content">';
  html += '<div class="media-item-title">' + _escHtml(item.title || '') + '</div>';
  if (item.date) html += '<div class="media-item-date" data-date="' + _escAttr(item.date) + '"></div>';
  tags.forEach(function(tag) { html += '<span class="media-item-tag">' + _escHtml(tag) + '</span>'; });
  html += '</div></div>';
  return html;
}

function _escAttr(s) { return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
function _escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── تنسيق التواريخ ──

function _formatDates(container) {
  (container || document).querySelectorAll('[data-date]').forEach(function(el) {
    var d = el.getAttribute('data-date');
    if (!d || el.textContent.trim()) return;
    try {
      var dateObj = new Date(d);
      if (!isNaN(dateObj.getTime())) {
        el.textContent = dateObj.toLocaleDateString('ar-SA', { year: 'numeric', month: 'long', day: 'numeric' });
      }
    } catch (e) {}
  });
}

// ── نقطة الدخول الرئيسية ──

function initGallery(obs) {
  _formatDates();
  if (obs) {
    document.querySelectorAll('.animate-in').forEach(function(el) { obs.observe(el); });
  }
}
