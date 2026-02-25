// Media gallery — يعمل في وضعين:
// 1) PHP SSR: العناصر موجودة في DOM → فلترة فقط بالـ data-type
// 2) Fallback: اقرأ من /api/media.php إذا لم يكن هناك محتوى مُعروض
let _mediaFilter = 'all';

// ── Lightbox ──────────────────────────────────────────────
let _lbItems  = [];
let _lbIndex  = 0;

function openLightbox(imgEl) {
  const grid = document.getElementById('media-grid');
  if (!grid) return;
  _lbItems  = [...grid.querySelectorAll('.media-item img')].filter(
    el => el.closest('.media-item').style.display !== 'none'
  );
  _lbIndex  = _lbItems.indexOf(imgEl);
  if (_lbIndex === -1) _lbIndex = 0;
  _lbShow();
}

function _lbShow() {
  const img = _lbItems[_lbIndex];
  if (!img) return;
  document.getElementById('lightbox-img').src = img.src;
  document.getElementById('lightbox-img').alt = img.alt;
  const title = img.closest('.media-item')?.querySelector('.media-item-title')?.textContent || '';
  document.getElementById('lightbox-caption').textContent = title;
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.body.style.overflow = '';
}

function lightboxNav(dir) {
  if (!_lbItems.length) return;
  _lbIndex = (_lbIndex + dir + _lbItems.length) % _lbItems.length;
  _lbShow();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
document.addEventListener('click', e => { if (e.target.id === 'lightbox') closeLightbox(); });

// ── Filter ────────────────────────────────────────────────
function _filterMediaItems() {
  const grid = document.getElementById('media-grid');
  if (!grid) return;
  grid.querySelectorAll('.media-item[data-type]').forEach(item => {
    const show = _mediaFilter === 'all' || item.dataset.type === _mediaFilter;
    if (show) {
      item.classList.remove('hiding');
      item.style.display = '';
    } else {
      item.classList.add('hiding');
      setTimeout(() => { if (item.classList.contains('hiding')) item.style.display = 'none'; }, 250);
    }
  });
  // أعِد تهيئة lightbox items بعد الفلترة
  _lbItems = [];
}

// ── Tab counts ────────────────────────────────────────────
function _updateTabCounts() {
  const allItems = document.querySelectorAll('#media-grid .media-item[data-type]');
  if (!allItems.length) return;
  document.querySelectorAll('.media-tab[data-filter]').forEach(tab => {
    const f = tab.getAttribute('data-filter');
    const baseText = tab.textContent.split('(')[0].trim();
    if (f === 'all') {
      tab.textContent = `${baseText} (${allItems.length})`;
    } else {
      const count = [...allItems].filter(i => i.dataset.type === f).length;
      if (count > 0) tab.textContent = `${baseText} (${count})`;
    }
  });
}

// ── Render (fallback: API) ─────────────────────────────────
async function renderMedia(obs) {
  const grid = document.getElementById('media-grid');
  if (!grid) return;

  // وضع SSR: العناصر مُعروضة من PHP — فقط فلترة
  if (grid.querySelector('.media-item')) {
    _filterMediaItems();
    return;
  }

  // وضع Fallback: اقرأ من API
  try {
    let items = await loadMedia();
    if (_mediaFilter !== 'all') items = items.filter(m => m.type === _mediaFilter);

    if (!items.length) {
      grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)">
        <div style="font-size:52px;margin-bottom:12px;opacity:.4">📷</div>
        <p>لا توجد وسائط حالياً</p>
        <p style="font-size:12px;margin-top:8px">يمكن إضافة الوسائط من لوحة التحكم</p>
      </div>`;
      return;
    }

    grid.innerHTML = items.map(item => {
      let media = '';
      if (item.type === 'videos') {
        media = `<video controls preload="metadata"><source src="${item.url}"></video>`;
      } else if (item.type === 'youtube') {
        const m = (item.url || '').match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        const ytId = m ? m[1] : '';
        media = ytId
          ? `<div class="media-item-youtube"><iframe src="https://www.youtube.com/embed/${ytId}" loading="lazy" allowfullscreen title="${item.title || ''}"></iframe></div>`
          : '';
      } else {
        media = `<img src="${item.url}" alt="${item.title || ''}" loading="lazy" onclick="openLightbox(this)">`;
      }
      return `
        <div class="media-item animate-in" data-type="${item.type || 'images'}">
          ${media}
          <div class="media-item-content">
            <div class="media-item-title">${item.title || ''}</div>
            ${item.date ? `<div class="media-item-date">${new Date(item.date).toLocaleDateString('ar-SA')}</div>` : ''}
            ${(item.tags || []).map(t => `<span class="media-item-tag">${t}</span>`).join('')}
          </div>
        </div>`;
    }).join('');

    grid.querySelectorAll('.animate-in').forEach(el => obs.observe(el));
  } catch (e) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:#999"><p>لا توجد وسائط</p></div>';
  }
}

// ── Init ──────────────────────────────────────────────────
function initMedia(obs) {
  // تنسيق التواريخ للعناصر المُعروضة من PHP
  document.querySelectorAll('#media-grid [data-date]').forEach(el => {
    if (!el.textContent.trim()) {
      try { el.textContent = new Date(el.dataset.date).toLocaleDateString('ar-SA'); } catch (e) {}
    }
  });

  // أعداد التبويبات
  _updateTabCounts();

  document.querySelectorAll('.media-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.media-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      _mediaFilter = this.getAttribute('data-filter');
      renderMedia(obs);
    });
  });

  renderMedia(obs);
}
