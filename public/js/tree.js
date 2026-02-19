// Family tree - loads branches from API
async function initFamilyTree(obs) {
try {
const data = await loadBranches();
const branches = data && data.branches ? data.branches : (Array.isArray(data) ? data : []);
if (!branches.length) return;

```
const tg = document.getElementById('tree-grid');
let html = '';
for (const b of branches) {
  const cnt = (b.members && b.members.length) || b.count || 0;
  const col = b.color || '#47915C';
  html += `<div class="tree-branch animate-in" style="border-color:${col}">`;
  html += `<div style="font-size:28px;margin-bottom:10px">&#x1F33F;</div>`;
  html += `<div class="tree-branch-name">${b.name}</div>`;
  if (b.head) html += `<div class="tree-branch-head">${b.head}</div>`;
  html += `<div class="tree-branch-stat"><div class="tree-branch-num" style="color:${col}">${cnt}</div><div class="tree-branch-label">فرد</div></div></div>`;
}
tg.innerHTML = html;
tg.querySelectorAll('.animate-in').forEach(el => obs.observe(el));
```

} catch (e) {}
}
