// Family tree — /api/branches.php
async function initFamilyTree(obs) {
try {
const branches = await loadBranches(); // array
if (!branches || !branches.length) return;

const tg = document.getElementById('tree-grid');
if (!tg) return;

tg.innerHTML = branches.map(b => {
  const cnt = (b.members && b.members.length) || b.count || 0;
  const col = b.color || '#47915C';
  return `<div class="tree-branch animate-in" style="border-color:${col}">
    <div style="font-size:28px;margin-bottom:10px">🌿</div>
    <div class="tree-branch-name">${b.name}</div>
    ${b.head ? `<div class="tree-branch-head">${b.head}</div>` : ''}
    <div class="tree-branch-stat">
      <div class="tree-branch-num" style="color:${col}">${cnt}</div>
      <div class="tree-branch-label">فرد</div>
    </div>
  </div>`;
}).join('');

tg.querySelectorAll('.animate-in').forEach(el => obs.observe(el));

} catch (e) { console.warn('initFamilyTree error:', e); }
}
