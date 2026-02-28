<?php
/**
 * tree.php — شجرة العائلة التفاعلية بـ D3.js
 */
$ws = getWS();
$treeMembers = getFamilyTree();
?>

<style>
/* ═══ شجرة العائلة ═══ */
.tree-svg-container{width:100%;min-height:70vh;background:var(--surface);border-radius:16px;border:1px solid var(--border);position:relative;overflow:hidden;cursor:grab;touch-action:none}
.tree-svg-container:active{cursor:grabbing}
.tree-svg{display:block;font-family:'Cairo','Tajawal',sans-serif}
.tree-link{fill:none;stroke:var(--border);stroke-width:2;stroke-linecap:round}
.tree-node{cursor:pointer}
.tree-node-rect{transition:filter .2s,stroke .3s;filter:drop-shadow(0 2px 6px rgba(0,0,0,.12))}
.tree-node:hover .tree-node-rect{filter:drop-shadow(0 6px 20px rgba(0,0,0,.25))}
.tree-node-name{fill:#fff;font-size:13px;font-weight:700;font-family:'Cairo',sans-serif;pointer-events:none}
.tree-node-sub{fill:rgba(255,255,255,.75);font-size:10px;pointer-events:none}
.tree-node-badge{fill:var(--text-muted);font-size:9px;font-weight:600}
.tree-controls{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:16px}
.tree-search-wrap{position:relative;flex:1;max-width:320px}
.tree-search-input{width:100%;padding:10px 16px;border:1.5px solid var(--border);border-radius:10px;font-family:'Cairo',sans-serif;font-size:14px;background:var(--surface);color:var(--text);direction:rtl}
.tree-search-input:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(71,145,92,.15)}
.tree-search-dropdown{position:absolute;top:100%;right:0;left:0;background:var(--surface);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:10;display:none;max-height:260px;overflow-y:auto;margin-top:4px}
.tree-search-item{padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border);transition:background .15s}
.tree-search-item:last-child{border-bottom:none}
.tree-search-item:hover{background:var(--bg-alt)}
.tree-toolbar{display:flex;gap:8px;flex-wrap:wrap}
.tree-legend{display:flex;gap:16px;margin-bottom:14px;font-size:12px;color:var(--text-muted);flex-wrap:wrap;justify-content:center}
.tree-legend-item{display:flex;align-items:center;gap:6px}
.tree-legend-dot{width:14px;height:14px;border-radius:50%;display:inline-block;border:2px solid rgba(255,255,255,.3)}
.tree-legend-male{background:#2d7a4a}
.tree-legend-female{background:#8e44ad}
.tree-legend-deceased{background:#888;opacity:.6}
.tree-tooltip{display:none;position:fixed;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 18px;font-size:12px;line-height:2;box-shadow:0 10px 30px rgba(0,0,0,.18);z-index:100;pointer-events:none;max-width:280px;direction:rtl}
.tree-empty{text-align:center;padding:80px 20px;color:var(--text-muted)}
.tree-empty-icon{font-size:64px;margin-bottom:16px;opacity:.3}
@media(max-width:768px){.tree-svg-container{min-height:50vh;border-radius:12px}.tree-controls{flex-direction:column;align-items:stretch}.tree-search-wrap{max-width:100%}.tree-toolbar{justify-content:center}}
</style>

<section id="tree">
  <div class="section-header">
    <div class="section-badge">شجرة العائلة</div>
    <h2 class="section-title">شجرة عائلة العوامي</h2>
    <p class="section-subtitle">استكشف الأفرع والأجيال — اضغط على أي شخص لعرض فروعه</p>
  </div>

  <?php if (empty($treeMembers)): ?>
    <div class="tree-empty">
      <div class="tree-empty-icon">&#x1F333;</div>
      <p>لم تُضف بيانات لشجرة العائلة بعد</p>
      <p style="font-size:13px;margin-top:8px">يمكن إضافة الأفراد من لوحة التحكم</p>
    </div>
  <?php else: ?>
    <!-- أدوات التحكم -->
    <div class="tree-controls">
      <div class="tree-search-wrap">
        <input type="text" id="tree-search" class="tree-search-input" placeholder="&#128269; ابحث عن شخص..." autocomplete="off">
        <div id="tree-search-results" class="tree-search-dropdown"></div>
      </div>
      <div class="tree-toolbar">
        <button class="cta-btn cta-secondary" style="padding:8px 16px;font-size:12px" onclick="treeResetZoom()">&#127919; إعادة ضبط</button>
        <button class="cta-btn cta-secondary" style="padding:8px 16px;font-size:12px" onclick="treeExpandAll()">&#128300; توسيع الكل</button>
        <button class="cta-btn cta-secondary" style="padding:8px 16px;font-size:12px" onclick="treeCollapseAll()">&#128301; طي الكل</button>
      </div>
    </div>

    <!-- دليل الألوان -->
    <div class="tree-legend">
      <span class="tree-legend-item"><span class="tree-legend-dot tree-legend-male"></span> ذكر</span>
      <span class="tree-legend-item"><span class="tree-legend-dot tree-legend-female"></span> أنثى</span>
      <span class="tree-legend-item"><span class="tree-legend-dot tree-legend-deceased"></span> متوفى</span>
    </div>

    <!-- حاوية D3.js SVG -->
    <div id="tree-svg-container" class="tree-svg-container"></div>

    <!-- بطاقة التفاصيل -->
    <div id="tree-tooltip" class="tree-tooltip"></div>
  <?php endif; ?>
</section>

<?php if (!empty($treeMembers)): ?>
<script>
window.__TREE_DATA__ = <?= json_encode(array_map(function($m) {
    return [
        'id'          => $m['id'],
        'name'        => $m['name'],
        'parent_id'   => $m['parent_id'],
        'gender'      => $m['gender'],
        'is_alive'    => (bool)(int)$m['is_alive'],
        'spouse_name' => $m['spouse_name'] ?? '',
        'sort_order'  => (int)$m['sort_order'],
    ];
}, $treeMembers), JSON_UNESCAPED_UNICODE) ?>;
// تهيئة الشجرة بعد تعيين البيانات (مهم لـ AJAX navigation)
if (typeof window.initFamilyTree === 'function') window.initFamilyTree();
</script>
<?php endif; ?>
