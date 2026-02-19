// Apply website settings from API
async function initSettings(obs) {
try {
const data = await loadSettings();
const ws = data && data.settings ? data.settings : data;
if (!ws) return;

```
if (ws.header) {
  if (ws.header.title) document.querySelector('.logo-text h1').textContent = ws.header.title;
  if (ws.header.subtitle) document.querySelector('.logo-text p').textContent = ws.header.subtitle;
}
if (ws.hero) {
  if (ws.hero.title) document.querySelector('.hero h2').textContent = ws.hero.title;
  if (ws.hero.description) document.querySelector('.hero p').textContent = ws.hero.description;
}
if (ws.stats) {
  const nums = document.querySelectorAll('.hero-meta .num');
  if (nums[0] && ws.stats.years) nums[0].textContent = ws.stats.years;
  if (nums[1] && ws.stats.committees) nums[1].textContent = ws.stats.committees;
  if (nums[2] && ws.stats.members) nums[2].textContent = ws.stats.members;
}
if (ws.logo) {
  document.querySelectorAll('.logo-svg').forEach(el => {
    el.outerHTML = `<img src="${ws.logo}" style="width:40px;height:40px;border-radius:8px" alt="Logo">`;
  });
  document.querySelectorAll('.footer-logo svg').forEach(el => {
    el.outerHTML = `<img src="${ws.logo}" style="width:48px;height:48px;border-radius:8px" alt="Logo">`;
  });
}
if (ws.about) {
  const ac = document.querySelector('.about-content');
  if (ac) {
    let ahtml = '';
    if (ws.about.mission) ahtml += `<h3>رسالتنا</h3><p>${ws.about.mission}</p>`;
    if (ws.about.vision) ahtml += `<h3>رؤيتنا</h3><p>${ws.about.vision}</p>`;
    if (ahtml) ac.innerHTML = ahtml;
  }
}
if (ws.values && ws.values.length) {
  const vg = document.getElementById('values-grid');
  let vh = '';
  ws.values.forEach(v => {
    vh += `<div class="value-card animate-in"><div class="value-icon">${v.icon}</div><div class="value-title">${v.title}</div><div class="value-desc">${v.desc}</div></div>`;
  });
  vg.innerHTML = vh;
  vg.querySelectorAll('.animate-in').forEach(el => obs.observe(el));
}
```

} catch (e) {}
}
