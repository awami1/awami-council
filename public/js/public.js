document.addEventListener('DOMContentLoaded', async () => {
  try {
    await loadSettings();
    await loadBranches();
    await loadMedia();
    await loadCommittees();
  } catch (err) {
    console.error(err);
  }
});

async function loadSettings() {
  const data = await API.getSettings();

  if (!data) return;

  if (data.header?.title)
    document.querySelector('.logo-text h1').textContent = data.header.title;

  if (data.hero?.title)
    document.querySelector('.hero h2').textContent = data.hero.title;

  if (data.hero?.description)
    document.querySelector('.hero p').textContent = data.hero.description;

  if (data.stats) {
    const nums = document.querySelectorAll('.hero-meta .num');
    if (nums[0]) nums[0].textContent = data.stats.years || 0;
    if (nums[1]) nums[1].textContent = data.stats.committees || 0;
    if (nums[2]) nums[2].textContent = data.stats.members || 0;
  }
}

async function loadBranches() {
  const branches = await API.getBranches();
  const grid = document.getElementById('tree-grid');
  if (!branches || !branches.length) return;

  let html = '';
  branches.forEach(b => {
    html += `
      <div class="tree-branch" style="border-color:${b.color}">
        <div style="font-size:28px;margin-bottom:10px">🌿</div>
        <div class="tree-branch-name">${b.name}</div>
        ${b.head ? `<div class="tree-branch-head">${b.head}</div>` : ''}
        <div class="tree-branch-stat">
          <div class="tree-branch-num" style="color:${b.color}">
            ${b.count}
          </div>
          <div class="tree-branch-label">فرد</div>
        </div>
      </div>
    `;
  });

  grid.innerHTML = html;
}

async function loadMedia() {
  const items = await API.getMedia();
  const grid = document.getElementById('media-grid');
  if (!items || !items.length) return;

  let html = '';
  items.forEach(item => {
    html += `
      <div class="media-item">
        ${
          item.type === 'videos'
            ? `<video controls><source src="${item.url}"></video>`
            : `<img src="${item.url}" alt="">`
        }
        <div class="media-item-content">
          <div class="media-item-title">${item.title || ''}</div>
        </div>
      </div>
    `;
  });

  grid.innerHTML = html;
}

async function loadCommittees() {
  const committees = await API.getCommittees();
  // اربطها بنفس طريقة HTML الأصلية لاحقاً إذا أردت
}
