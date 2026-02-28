/**
 * tree.js — شجرة العائلة التفاعلية بـ D3.js v7
 * عمودية (الجد أعلى → الأبناء → الأحفاد)
 * مع expand/collapse + zoom/pan + بحث + tooltip
 */
(function () {
'use strict';

/* ════════════════════════════════════════════
   ثوابت و إعدادات
   ════════════════════════════════════════════ */
var COLORS = {
  male:       { bg: '#1a6b3c', bg2: '#145530', stroke: '#0f4424' },
  female:     { bg: '#7b2d8e', bg2: '#612372', stroke: '#4a1a57' },
  deceased:   { bg: '#6b7280', bg2: '#4b5563', stroke: '#374151' },
  highlight:  '#f59e0b',
  link:       '#c8d6c0',
};
// Adaptive sizing for mobile
var _isMobile = window.innerWidth < 600;
var NODE_W      = _isMobile ? 120 : 160;
var NODE_H      = _isMobile ? 48  : 58;
var NODE_RX     = 12;
var DURATION    = 500;
var DEPTH_GAP   = _isMobile ? 90 : 120;
var SIBLING_GAP = _isMobile ? 8  : 14;
var INITIAL_DEPTH = 2;     // أول مستويين مفتوحين

/* ════════════════════════════════════════════
   بيانات
   ════════════════════════════════════════════ */
var rawData = window.__TREE_DATA__;
if (!rawData || !rawData.length) return;

/* ════════════════════════════════════════════
   بناء الهيكل الشجري من مصفوفة مسطحة
   ════════════════════════════════════════════ */
var nodeMap = {};
rawData.forEach(function (d) { nodeMap[d.id] = Object.assign({}, d, { children: [] }); });

var roots = [];
rawData.forEach(function (d) {
  var node = nodeMap[d.id];
  if (d.parent_id && nodeMap[d.parent_id]) {
    nodeMap[d.parent_id].children.push(node);
  } else {
    roots.push(node);
  }
});

// ترتيب الأبناء حسب sort_order
(function sortChildren(nodes) {
  nodes.sort(function (a, b) { return (a.sort_order || 0) - (b.sort_order || 0); });
  nodes.forEach(function (n) { if (n.children.length) sortChildren(n.children); });
})(roots);

// إذا عدة جذور → إنشاء جذر وهمي
var treeData;
if (roots.length === 1) {
  treeData = roots[0];
} else {
  treeData = { id: '__root__', name: 'عائلة العوامي', gender: 'ذكر', is_alive: true, spouse_name: '', children: roots, _virtual: true };
}

/* ════════════════════════════════════════════
   إعداد SVG + Zoom
   ════════════════════════════════════════════ */
var container = document.getElementById('tree-svg-container');
if (!container) return;

var width  = container.clientWidth  || 800;
var height = container.clientHeight || 600;

var svg = d3.select(container)
  .append('svg')
  .attr('class', 'tree-svg')
  .attr('width', '100%')
  .attr('height', '100%')
  .attr('viewBox', '0 0 ' + width + ' ' + height);

var gRoot = svg.append('g').attr('class', 'tree-root');
var gLinks = gRoot.append('g').attr('class', 'tree-links');
var gNodes = gRoot.append('g').attr('class', 'tree-nodes');

var zoom = d3.zoom()
  .scaleExtent([0.1, 3])
  .on('zoom', function (e) { gRoot.attr('transform', e.transform); });

svg.call(zoom);

/* ════════════════════════════════════════════
   D3 Hierarchy & Layout
   ════════════════════════════════════════════ */
var root = d3.hierarchy(treeData, function (d) { return d.children; });

// طي المستويات > INITIAL_DEPTH
root.descendants().forEach(function (d) {
  if (d.depth >= INITIAL_DEPTH && d.children) {
    d._children = d.children;
    d.children = null;
  }
});

// حساب عدد الأحفاد
function countDescendants(node) {
  if (!node._children && !node.children) return 0;
  var kids = node._children || node.children || [];
  var total = kids.length;
  kids.forEach(function (c) { total += countDescendants(c); });
  return total;
}

var treeFn = d3.tree().nodeSize([NODE_W + SIBLING_GAP, NODE_H + DEPTH_GAP]);

// معرّف فريد تصاعدي لكل عقدة (لـ d3 data join)
var _nodeId = 0;

/* ════════════════════════════════════════════
   دالة الرسم الرئيسية
   ════════════════════════════════════════════ */
function update(source) {
  // حساب الـ layout
  treeFn(root);

  var nodes = root.descendants();
  var links = root.links();

  // لكل عقدة: x أفقي, y عمودي
  nodes.forEach(function (d) {
    d.y = d.depth * (NODE_H + DEPTH_GAP);
  });

  /* ─── الروابط (Links) ─── */
  var linkSel = gLinks.selectAll('path.tree-link').data(links, function (d) {
    return (d.target.data.id || d.target.id);
  });

  var linkEnter = linkSel.enter()
    .append('path')
    .attr('class', 'tree-link')
    .attr('d', function () {
      return linkPath({ x: source.x0 || source.x, y: source.y0 || source.y },
                       { x: source.x0 || source.x, y: source.y0 || source.y });
    });

  var linkMerge = linkEnter.merge(linkSel);
  linkMerge.transition().duration(DURATION)
    .attr('d', function (d) { return linkPath(d.source, d.target); });

  linkSel.exit().transition().duration(DURATION)
    .attr('d', function () {
      return linkPath({ x: source.x, y: source.y }, { x: source.x, y: source.y });
    })
    .remove();

  /* ─── العقد (Nodes) ─── */
  var nodeSel = gNodes.selectAll('g.tree-node').data(nodes, function (d) {
    return d.data.id || (d.id = ++_nodeId);
  });

  var nodeEnter = nodeSel.enter()
    .append('g')
    .attr('class', 'tree-node')
    .attr('transform', function () {
      return 'translate(' + (source.x0 || source.x) + ',' + (source.y0 || source.y) + ')';
    })
    .on('click', function (e, d) {
      if (d.data._virtual) return;
      toggleNode(d);
      update(d);
    })
    .on('mouseenter', function (e, d) { showTooltip(e, d); })
    .on('mouseleave', hideTooltip)
    .on('touchstart', function (e, d) {
      // Touch: toggle tooltip on tap (don't interfere with click for expand)
      if (tooltipEl && tooltipEl.style.display === 'block') {
        hideTooltip();
      } else {
        var touch = e.touches[0];
        showTooltip({ clientX: touch.clientX, clientY: touch.clientY }, d);
      }
    }, { passive: true });

  // خلفية العقدة
  nodeEnter.append('rect')
    .attr('class', 'tree-node-rect')
    .attr('x', -NODE_W / 2)
    .attr('y', -NODE_H / 2)
    .attr('width', NODE_W)
    .attr('height', NODE_H)
    .attr('rx', NODE_RX)
    .attr('fill', function (d) { return nodeGrad(d); })
    .attr('stroke', function (d) { return nodeStroke(d); })
    .attr('stroke-width', 2);

  // الاسم
  nodeEnter.append('text')
    .attr('class', 'tree-node-name')
    .attr('text-anchor', 'middle')
    .attr('dy', function (d) { return d.data.spouse_name ? '-0.15em' : '0.35em'; })
    .text(function (d) { return truncate(d.data.name, 18); });

  // اسم الزوج/الزوجة
  nodeEnter.append('text')
    .attr('class', 'tree-node-sub')
    .attr('text-anchor', 'middle')
    .attr('dy', '1.2em')
    .text(function (d) {
      if (d.data.spouse_name) return (d.data.gender === 'أنثى' ? 'زوج: ' : 'زوجة: ') + truncate(d.data.spouse_name, 14);
      return '';
    });

  // عدد الأبناء (إذا مطوي)
  nodeEnter.append('text')
    .attr('class', 'tree-node-badge')
    .attr('text-anchor', 'middle')
    .attr('dy', NODE_H / 2 + 14)
    .text(function (d) { return badgeText(d); });

  // مؤشر التوسيع (+/-) في الأسفل
  nodeEnter.filter(function (d) { return d.children || d._children; })
    .append('circle')
    .attr('class', 'tree-expand-btn')
    .attr('cy', NODE_H / 2)
    .attr('r', 8)
    .attr('fill', '#fff')
    .attr('stroke', function (d) { return nodeStroke(d); })
    .attr('stroke-width', 1.5);

  nodeEnter.filter(function (d) { return d.children || d._children; })
    .append('text')
    .attr('class', 'tree-expand-icon')
    .attr('text-anchor', 'middle')
    .attr('dy', NODE_H / 2 + 4)
    .attr('font-size', '11px')
    .attr('font-weight', '700')
    .attr('fill', function (d) { return nodeStroke(d); })
    .attr('pointer-events', 'none')
    .text(function (d) { return d._children ? '+' : '\u2013'; });

  /* تحديث (merge) */
  var nodeMerge = nodeEnter.merge(nodeSel);

  nodeMerge.transition().duration(DURATION)
    .attr('transform', function (d) { return 'translate(' + d.x + ',' + d.y + ')'; });

  // تحديث الألوان والنصوص عند الانتقال
  nodeMerge.select('rect.tree-node-rect')
    .attr('fill', function (d) { return nodeGrad(d); })
    .attr('stroke', function (d) { return nodeStroke(d); });

  nodeMerge.select('.tree-node-badge')
    .text(function (d) { return badgeText(d); });

  nodeMerge.select('.tree-expand-icon')
    .text(function (d) { return d._children ? '+' : (d.children ? '\u2013' : ''); });

  /* خروج (exit) */
  var nodeExit = nodeSel.exit().transition().duration(DURATION)
    .attr('transform', function () { return 'translate(' + source.x + ',' + source.y + ')'; })
    .style('opacity', 0)
    .remove();

  /* حفظ المواقع القديمة */
  nodes.forEach(function (d) {
    d.x0 = d.x;
    d.y0 = d.y;
  });
}

/* ════════════════════════════════════════════
   دوال مساعدة
   ════════════════════════════════════════════ */
function linkPath(s, t) {
  return 'M' + s.x + ',' + (s.y + NODE_H / 2) +
         'C' + s.x + ',' + (s.y + NODE_H / 2 + DEPTH_GAP / 2) +
         ' ' + t.x + ',' + (t.y - NODE_H / 2 - DEPTH_GAP / 2) +
         ' ' + t.x + ',' + (t.y - NODE_H / 2);
}

function nodeGrad(d) {
  if (!d.data.is_alive) return COLORS.deceased.bg;
  return d.data.gender === 'أنثى' ? COLORS.female.bg : COLORS.male.bg;
}

function nodeStroke(d) {
  if (!d.data.is_alive) return COLORS.deceased.stroke;
  return d.data.gender === 'أنثى' ? COLORS.female.stroke : COLORS.male.stroke;
}

function badgeText(d) {
  if (!d._children) return '';
  var n = d._children.length;
  var total = countDescendants(d);
  if (total > n) return n + ' (' + total + ')';
  return n + '';
}

function truncate(str, max) {
  if (!str) return '';
  return str.length > max ? str.substring(0, max - 1) + '..' : str;
}

function toggleNode(d) {
  if (d.children) {
    d._children = d.children;
    d.children = null;
  } else if (d._children) {
    d.children = d._children;
    d._children = null;
  }
}

/* ════════════════════════════════════════════
   Tooltip
   ════════════════════════════════════════════ */
var tooltipEl = document.getElementById('tree-tooltip');

function showTooltip(e, d) {
  if (!tooltipEl || d.data._virtual) return;
  var data = d.data;
  var genderIcon = data.gender === 'أنثى' ? '👩' : '👨';
  var statusIcon = data.is_alive ? '🟢 على قيد الحياة' : '⚫ متوفى';
  var kids = (d.children || d._children || []).length;

  var html = '<div style="font-weight:700;font-size:14px;margin-bottom:4px">' + genderIcon + ' ' + data.name + '</div>';
  html += '<div>' + statusIcon + '</div>';
  if (data.spouse_name) {
    html += '<div>' + (data.gender === 'أنثى' ? '💑 الزوج: ' : '💑 الزوجة: ') + data.spouse_name + '</div>';
  }
  if (kids > 0) html += '<div>👶 الأبناء: ' + kids + '</div>';
  if (d.parent && d.parent.data && !d.parent.data._virtual) {
    html += '<div>👤 الأب: ' + d.parent.data.name + '</div>';
  }

  tooltipEl.innerHTML = html;
  tooltipEl.style.display = 'block';

  var tx = e.clientX + 16;
  var ty = e.clientY + 16;
  if (tx + 280 > window.innerWidth) tx = e.clientX - 290;
  if (ty + 200 > window.innerHeight) ty = e.clientY - 180;
  tooltipEl.style.left = tx + 'px';
  tooltipEl.style.top  = ty + 'px';
}

function hideTooltip() {
  if (tooltipEl) tooltipEl.style.display = 'none';
}

/* ════════════════════════════════════════════
   البحث
   ════════════════════════════════════════════ */
var searchInput   = document.getElementById('tree-search');
var searchResults = document.getElementById('tree-search-results');
var _highlightedId = null;

/* تطبيع النص العربي: توحيد أشكال الألف وإزالة التشكيل */
function normalizeAr(str) {
  return str
    .replace(/[أإآٱ]/g, 'ا')
    .replace(/[ؤ]/g, 'و')
    .replace(/[ئ]/g, 'ي')
    .replace(/ة/g, 'ه')
    .replace(/[\u0610-\u061A\u064B-\u065F\u0670]/g, '');
}

/* بناء سلسلة النسب: "حسن بن محمد بن علي" */
function getLineage(member) {
  var parts = [member.name];
  var current = member;
  var depth = 0;
  while (current.parent_id && depth < 2) {
    var parent = rawData.find(function (m) { return m.id === current.parent_id; });
    if (!parent) break;
    parts.push(parent.name);
    current = parent;
    depth++;
  }
  return parts.join(' بن ');
}

if (searchInput) {
  searchInput.addEventListener('input', function () {
    var q = this.value.trim();
    if (!q || q.length < 2) {
      searchResults.style.display = 'none';
      clearHighlight();
      return;
    }
    var nq = normalizeAr(q);
    var matches = rawData.filter(function (m) {
      return normalizeAr(m.name).indexOf(nq) !== -1;
    }).slice(0, 12);

    if (!matches.length) {
      searchResults.innerHTML = '<div class="tree-search-item" style="color:var(--text-muted)">لا نتائج</div>';
      searchResults.style.display = 'block';
      return;
    }

    searchResults.innerHTML = matches.map(function (m) {
      var icon = m.gender === 'أنثى' ? '👩' : '👨';
      return '<div class="tree-search-item" data-id="' + m.id + '">' + icon + ' ' + getLineage(m) + '</div>';
    }).join('');
    searchResults.style.display = 'block';
  });

  searchResults.addEventListener('click', function (e) {
    var item = e.target.closest('.tree-search-item');
    if (!item || !item.dataset.id) return;
    focusOnNode(item.dataset.id);
    searchResults.style.display = 'none';
    searchInput.value = '';
  });

  // إغلاق القائمة عند النقر خارجها
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.tree-search-wrap')) {
      searchResults.style.display = 'none';
    }
  });
}

function focusOnNode(id) {
  // فتح كل الأجداد حتى نصل للعقدة المطلوبة
  var targetNode = null;
  root.each(function (d) {
    if (d.data.id === id) targetNode = d;
  });

  // إذا العقدة غير موجودة في الشجرة الحالية (مطوية)
  if (!targetNode) {
    expandPathToId(id);
    update(root);
    // بعد التحديث، ابحث مرة أخرى
    root.each(function (d) {
      if (d.data.id === id) targetNode = d;
    });
  }

  if (!targetNode) return;

  // تسليط الضوء
  highlightNode(id);

  // تحريك الكاميرا للعقدة
  var t = d3.zoomIdentity
    .translate(width / 2 - targetNode.x, height / 3 - targetNode.y)
    .scale(1);

  svg.transition().duration(700).call(zoom.transform, t);
}

function expandPathToId(targetId) {
  // نحتاج إيجاد المسار من الجذر للعقدة المطلوبة في البيانات الأصلية
  var parentChain = [];
  var current = targetId;
  var safety = 0;
  while (current && safety < 100) {
    parentChain.unshift(current);
    var item = rawData.find(function (m) { return m.id === current; });
    current = item ? item.parent_id : null;
    safety++;
  }

  // افتح كل عقدة في المسار
  parentChain.forEach(function (pid) {
    root.each(function (d) {
      if (d.data.id === pid && d._children) {
        d.children = d._children;
        d._children = null;
      }
    });
  });
}

function highlightNode(id) {
  clearHighlight();
  _highlightedId = id;
  gNodes.selectAll('g.tree-node').select('rect')
    .attr('stroke', function (d) { return d.data.id === id ? COLORS.highlight : nodeStroke(d); })
    .attr('stroke-width', function (d) { return d.data.id === id ? 4 : 2; });
}

function clearHighlight() {
  if (!_highlightedId) return;
  _highlightedId = null;
  gNodes.selectAll('g.tree-node').select('rect')
    .attr('stroke', function (d) { return nodeStroke(d); })
    .attr('stroke-width', 2);
}

/* ════════════════════════════════════════════
   أزرار التحكم العامة
   ════════════════════════════════════════════ */
window.treeResetZoom = function () {
  // Fit all visible nodes to screen
  var nodes = root.descendants();
  if (!nodes.length) return;
  var xMin = Infinity, xMax = -Infinity, yMin = Infinity, yMax = -Infinity;
  nodes.forEach(function (d) {
    if (d.x < xMin) xMin = d.x;
    if (d.x > xMax) xMax = d.x;
    if (d.y < yMin) yMin = d.y;
    if (d.y > yMax) yMax = d.y;
  });
  var tw = (xMax - xMin) + NODE_W * 2;
  var th = (yMax - yMin) + NODE_H * 2;
  var scale = Math.min(width / tw, height / th, 1.2) * 0.9;
  var cx = (xMin + xMax) / 2;
  var cy = (yMin + yMax) / 2;
  var t = d3.zoomIdentity.translate(width / 2 - cx * scale, height / 2 - cy * scale).scale(scale);
  svg.transition().duration(600).call(zoom.transform, t);
};

window.treeExpandAll = function () {
  root.each(function (d) {
    if (d._children) {
      d.children = d._children;
      d._children = null;
    }
  });
  update(root);
};

window.treeCollapseAll = function () {
  root.each(function (d) {
    if (d.depth > 0 && d.children) {
      d._children = d.children;
      d.children = null;
    }
  });
  update(root);
};

/* ════════════════════════════════════════════
   أول رسم + ضبط الموقع
   ════════════════════════════════════════════ */
root.x0 = 0;
root.y0 = 0;
update(root);

// مركزة الشجرة
setTimeout(function () {
  window.treeResetZoom();
}, 100);

// إعادة الحجم عند تغيير النافذة
var _resizeTimer;
window.addEventListener('resize', function () {
  clearTimeout(_resizeTimer);
  _resizeTimer = setTimeout(function () {
    width  = container.clientWidth  || 800;
    height = container.clientHeight || 600;
    svg.attr('viewBox', '0 0 ' + width + ' ' + height);
  }, 200);
});

// Hide tooltip when tapping outside nodes on mobile
document.addEventListener('touchstart', function (e) {
  if (!e.target.closest('.tree-node')) hideTooltip();
}, { passive: true });

})();
