// Media gallery - loads from API
let mediaFilter = ‘all’;

async function renderMedia(obs) {
const grid = document.getElementById(‘media-grid’);
try {
const data = await loadMedia();
let items = data && data.media ? data.media : (Array.isArray(data) ? data : []);
if (mediaFilter !== ‘all’) items = items.filter(m => m.type === mediaFilter);

```
if (!items.length) {
  grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)"><div style="font-size:52px;margin-bottom:12px;opacity:.4">&#x1F4F7;</div><p>لا توجد وسائط حالياً</p><p style="font-size:12px;margin-top:8px">يمكن إضافة الوسائط من لوحة التحكم</p></div>';
  return;
}

let html = '';
for (const item of items) {
  html += '<div class="media-item animate-in">';
  if (item.type === 'videos') html += `<video controls preload="metadata"><source src="${item.url}"></video>`;
  else html += `<img src="${item.url}" alt="${item.title || ''}" loading="lazy">`;
  html += `<div class="media-item-content"><div class="media-item-title">${item.title || ''}</div>`;
  if (item.date) html += `<div class="media-item-date">${new Date(item.date).toLocaleDateString('ar-SA')}</div>`;
  if (item.tags && item.tags.length) {
    item.tags.forEach(t => { html += `<span class="media-item-tag">${t}</span>`; });
  }
  html += '</div></div>';
}
grid.innerHTML = html;
grid.querySelectorAll('.animate-in').forEach(el => obs.observe(el));
```

} catch (e) {
grid.innerHTML = ‘<div style="grid-column:1/-1;text-align:center;padding:60px;color:#999"><p>لا توجد وسائط</p></div>’;
}
}

function initMedia(obs) {
const tabs = document.querySelectorAll(’.media-tab’);
tabs.forEach(tab => {
tab.addEventListener(‘click’, function () {
tabs.forEach(t => t.classList.remove(‘active’));
this.classList.add(‘active’);
mediaFilter = this.getAttribute(‘data-filter’);
renderMedia(obs);
});
});
renderMedia(obs);
}
