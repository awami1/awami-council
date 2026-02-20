// Media gallery — /api/media.php
let _mediaFilter = ‘all’;

async function renderMedia(obs) {
const grid = document.getElementById(‘media-grid’);
if (!grid) return;

try {
let items = await loadMedia(); // array
if (_mediaFilter !== ‘all’) items = items.filter(m => m.type === _mediaFilter);

```
if (!items.length) {
  grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)">
    <div style="font-size:52px;margin-bottom:12px;opacity:.4">📷</div>
    <p>لا توجد وسائط حالياً</p>
    <p style="font-size:12px;margin-top:8px">يمكن إضافة الوسائط من لوحة التحكم</p>
  </div>`;
  return;
}

grid.innerHTML = items.map(item => `
  <div class="media-item animate-in">
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
```

} catch (e) {
grid.innerHTML = ‘<div style="grid-column:1/-1;text-align:center;padding:60px;color:#999"><p>لا توجد وسائط</p></div>’;
}
}

function initMedia(obs) {
document.querySelectorAll(’.media-tab’).forEach(tab => {
tab.addEventListener(‘click’, function () {
document.querySelectorAll(’.media-tab’).forEach(t => t.classList.remove(‘active’));
this.classList.add(‘active’);
_mediaFilter = this.getAttribute(‘data-filter’);
renderMedia(obs);
});
});
renderMedia(obs);
}
