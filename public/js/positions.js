// positions.js — يجلب المناصب من API ويبني الكروت ديناميكياً
// يُحمّل في صفحة المجلس (/council)

(function() {
  function escHtml(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s)));
    return d.innerHTML;
  }

  function init() {
    var grid = document.getElementById('council-positions-grid');
    if (!grid) return;

    fetch('/api/positions.php')
      .then(function(r) { return r.json(); })
      .then(function(json) {
        var positions = (json.data || []).sort(function(a, b) { return a.sort_order - b.sort_order; });
        if (!positions.length) {
          grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)"><div style="font-size:52px;margin-bottom:12px;opacity:.4">👑</div><p>لم تُضف مناصب بعد</p></div>';
          return;
        }

        grid.innerHTML = positions.map(function(p, i) {
          var isFirst = (i === 0);
          var isLast = (i === positions.length - 1);
          var cardClass = 'council-card animate-in';
          if (isFirst) cardClass += ' president';
          if (isLast && positions.length > 1) cardClass += ' advisory';

          var names = (p.members || []).map(function(m) { return m.name; }).join(' - ');
          var tasksHtml = (p.tasks || []).map(function(t) {
            return '<li>' + escHtml(t.task_text) + '</li>';
          }).join('');

          return '<div class="' + cardClass + '">' +
            '<div class="council-icon">' + escHtml(p.icon) + '</div>' +
            '<div class="council-role">' + escHtml(p.title) + '</div>' +
            '<div class="council-name">' + escHtml(names) + '</div>' +
            (tasksHtml ? '<ul class="council-tasks">' + tasksHtml + '</ul>' : '') +
          '</div>';
        }).join('');

        // Re-init scroll animations if available
        if (typeof initAnimations === 'function') {
          initAnimations();
        }
      })
      .catch(function(e) {
        console.error('Failed to load positions:', e);
      });
  }

  // كشف الدالة للـ AJAX re-navigation
  window.initPositions = init;

  // Run on page load or immediately if DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
