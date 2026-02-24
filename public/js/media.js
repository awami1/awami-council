// Media gallery — يعمل في وضعين:
// 1) PHP SSR: العناصر موجودة في DOM → فلترة فقط بالـ data-type
// 2) Fallback: اقرأ من /api/media.php إذا لم يكن هناك محتوى مُعروض
let _mediaFilter = 'all';

function _filterMediaItems() {
const grid = document.getElementById('media-grid');
if (!grid) return;
grid.querySelectorAll('.media-item[data-type]').forEach(item => {
  const show = _mediaFilter === 'all' || item.dataset.type === _mediaFilter;
  item.style.display = show ? '' : 'none';
});
}

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
let items = await loadMedia(); // array
if (_mediaFilter !== 'all') items = items.filter(m => m.type === _mediaFilter);

if (!items.length) {
  grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)">
    <div style="font-size:52px;margin-bottom:12px;opacity:.4">📷</div>
    <p>لا توجد وسائط حالياً</p>
    <p style="font-size:12px;margin-top:8px">يمكن إضافة الوسائط من لوحة التحكم</p>
  </div>`;
  return;
}

grid.innerHTML = items.map(item => `
  <div class="media-item animate-in" data-type="${item.type || 'images'}">
    ${item.type === 'videos'
      ? `<video controls preload="metadata"><source src="${item.url}"></video>`
      : `<img src="${item.url}" alt="${item.title || ''}" loading="lazy">`}
    <div class="media-item-content">
      <div class="media-item-title">${item.title || ''}</div>
      ${item.date ? `<div class="media-item-date">${new Date(item.date).toLocaleDateString('ar-SA')}</div>` : ''}
      ${(item.tags || []).map(t => `<span class="media-item-tag">${t}</span>`).join('')}
    </div>
  </div>`).join('');

grid.querySelectorAll('.animate-in').forEach(el => obs.observe(el));

} catch (e) {
grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:#999"><p>لا توجد وسائط</p></div>';
}
}

function initMedia(obs) {
// تنسيق التواريخ للعناصر المُعروضة من PHP
document.querySelectorAll('#media-grid [data-date]').forEach(el => {
  if (!el.textContent.trim()) {
    try { el.textContent = new Date(el.dataset.date).toLocaleDateString('ar-SA'); } catch (e) {}
  }
});

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
