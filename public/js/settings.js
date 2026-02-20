// Apply website settings from API — /api/settings.php
async function initSettings(obs) {
try {
const r  = await SettingsAPI.get();
const ws = r.settings;
if (!ws) return;

```
// Header
if (ws.header) {
  const h1 = document.querySelector('.logo-text h1');
  const hp = document.querySelector('.logo-text p');
  if (h1 && ws.header.title)    h1.textContent = ws.header.title;
  if (hp && ws.header.subtitle) hp.textContent = ws.header.subtitle;
}

// Hero
if (ws.hero) {
  const hh2 = document.querySelector('.hero h2');
  const hp  = document.querySelector('.hero p');
  if (hh2 && ws.hero.title)       hh2.textContent = ws.hero.title;
  if (hp  && ws.hero.description) hp.textContent  = ws.hero.description;
}

// Stats
if (ws.stats) {
  const nums = document.querySelectorAll('.hero-meta .num');
  if (nums[0] && ws.stats.years)      nums[0].textContent = ws.stats.years;
  if (nums[1] && ws.stats.committees) nums[1].textContent = ws.stats.committees;
  if (nums[2] && ws.stats.members)    nums[2].textContent = ws.stats.members;
}

// Logo
if (ws.logo) {
  document.querySelectorAll('.logo-svg').forEach(el => {
    el.outerHTML = `<img src="${ws.logo}" style="width:40px;height:40px;border-radius:8px" alt="Logo">`;
  });
  document.querySelectorAll('.footer-logo svg').forEach(el => {
    el.outerHTML = `<img src="${ws.logo}" style="width:48px;height:48px;border-radius:8px" alt="Logo">`;
  });
}

// About
if (ws.about) {
  const ac = document.querySelector('.about-content');
  if (ac) {
    let html = '';
    if (ws.about.mission) html += `<h3>رسالتنا</h3><p>${ws.about.mission}</p>`;
    if (ws.about.vision)  html += `<h3>رؤيتنا</h3><p>${ws.about.vision}</p>`;
    if (html) ac.innerHTML = html;
  }
}

// Values
if (ws.values && ws.values.length) {
  const vg = document.getElementById('values-grid');
  if (vg) {
    vg.innerHTML = ws.values.map(v =>
      `<div class="value-card animate-in"><div class="value-icon">${v.icon}</div><div class="value-title">${v.title}</div><div class="value-desc">${v.desc}</div></div>`
    ).join('');
    vg.querySelectorAll('.animate-in').forEach(el => obs.observe(el));
  }
}

// Council positions
if (ws.councilPositions && ws.councilPositions.length) {
  const pg = document.getElementById('positions-grid') || document.getElementById('council-grid');
  if (pg) {
    pg.innerHTML = ws.councilPositions.map(p =>
      `<div class="position-card animate-in">
        <div class="pos-icon">${p.icon || '👤'}</div>
        <div class="pos-role">${p.role}</div>
        <div class="pos-name">${p.name}</div>
       </div>`
    ).join('');
    pg.querySelectorAll('.animate-in').forEach(el => obs.observe(el));
  }
}
```

} catch (e) { console.warn(‘initSettings error:’, e); }
}
