<?php
/**
 * member/index.php — الصفحة الشخصية للعضو
 * تعرض: بطاقة الهوية + اللجان + الاشتراكات + الاعتراضات
 */
declare(strict_types=1);
require_once __DIR__ . '/../api/config.php';

// التحقق من الجلسة — إعادة توجيه لـ /login إذا غير مسجل
if (session_status() === PHP_SESSION_NONE) {
    session_name('awami_member');
    session_start();
}
if (empty($_SESSION['member_id']) || ($_SESSION['expires_at'] ?? 0) < time()) {
    header('Location: /login');
    exit;
}

$memberName = htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8');
$awmId      = htmlspecialchars($_SESSION['awm_id'] ?? '', ENT_QUOTES, 'UTF-8');
$csrfToken  = $_SESSION['member_csrf_token'] ?? '';

require_once __DIR__ . '/../includes/helpers.php';
$ws = getWS();
$siteTitle = htmlspecialchars($ws['header']['title'] ?? 'مجلس عائلة العوامي', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>صفحتي — <?= $siteTitle ?></title>
<meta name="robots" content="noindex, nofollow">
<script>
(function(){var t=localStorage.getItem('awami-theme')||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t)})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' rx='16' fill='%231A5C32'/%3E%3Cpath d='M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36' stroke='%23fff' stroke-width='6' stroke-linecap='round' fill='none'/%3E%3Cpath d='M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62' stroke='%23fff' stroke-width='5' stroke-linecap='round' fill='none'/%3E%3Ccircle cx='34' cy='62' r='5' fill='%23fff'/%3E%3C/svg%3E">
<style>
:root {
  --green-dark:#1A5C32;--green:#3D8B37;--green-light:#e8f5ec;
  --accent:#c8a84b;--primary:#1B3456;
  --bg:#FDFCF8;--bg-alt:#f5f9f6;--surface:#fff;
  --text:#1a2a1e;--text-muted:#546358;
  --border:#c2cec5;
  --radius:12px;--radius-lg:18px;--radius-xl:24px;
  --shadow-sm:0 2px 12px rgba(0,0,0,.06);--shadow-card:0 4px 16px rgba(0,0,0,.08);
  --font-body:'Cairo',sans-serif;--font-heading:'Amiri',serif;
  --gradient-header:linear-gradient(135deg,#1A5C32,#0f3d22 50%,#1B3456);
}
[data-theme="dark"]{
  --green-dark:#2A7A44;--green:#4CAF6A;--green-light:#1a3d28;
  --accent:#e0c56a;--bg:#0A0F0C;--bg-alt:#111e14;--surface:#1a2a1e;
  --text:#e8f0ea;--text-muted:#8ca892;--border:#2d4a35;
  --shadow-sm:0 2px 12px rgba(0,0,0,.2);--shadow-card:0 4px 16px rgba(0,0,0,.25);
  --gradient-header:linear-gradient(135deg,#0f2d18,#0a1f10 50%,#0f1a2e);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font-body);background:var(--bg);color:var(--text);min-height:100vh}

/* ─── Topbar ─── */
.topbar{background:var(--gradient-header);color:#fff;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.topbar-right{display:flex;align-items:center;gap:12px}
.topbar-logo{width:36px;height:36px}
.topbar h1{font-family:var(--font-heading);font-size:1.1rem;font-weight:700}
.topbar-left{display:flex;align-items:center;gap:10px}
.topbar-user{font-size:.85rem;opacity:.9}
.btn-sm{padding:6px 14px;border:1px solid rgba(255,255,255,.3);border-radius:var(--radius);background:transparent;color:#fff;font-size:.8rem;font-family:inherit;cursor:pointer;transition:all .2s}
.btn-sm:hover{background:rgba(255,255,255,.15)}

/* ─── Container ─── */
.container{max-width:900px;margin:0 auto;padding:24px 16px}

/* ─── Card ─── */
.card{background:var(--surface);border-radius:var(--radius-lg);padding:24px;border:1px solid var(--border);box-shadow:var(--shadow-card);margin-bottom:24px}
.card-title{font-family:var(--font-heading);font-size:1.15rem;color:var(--green-dark);margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--green-light);display:flex;align-items:center;gap:8px}

/* ─── بطاقة الهوية ─── */
.id-card{background:var(--gradient-header);color:#fff;border:none;display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.id-avatar{width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;border:3px solid rgba(255,255,255,.3)}
.id-info h2{font-family:var(--font-heading);font-size:1.4rem;margin-bottom:4px}
.id-info .awm-id{font-size:.9rem;opacity:.8;letter-spacing:1px;margin-bottom:4px}
.id-info .join-date{font-size:.85rem;opacity:.7}
.id-status{margin-right:auto;padding:6px 16px;border-radius:20px;font-size:.85rem;font-weight:700}
.status-active{background:rgba(76,175,80,.2);color:#a5d6a7}
.status-lapsed{background:rgba(255,193,7,.2);color:#ffe082}
.status-inactive{background:rgba(244,67,54,.2);color:#ef9a9a}
.status-exempt{background:rgba(158,158,158,.2);color:#bdbdbd}

/* ─── اللجان ─── */
.committee-list{list-style:none;display:flex;flex-direction:column;gap:10px}
.committee-item{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-alt);border-radius:var(--radius);border:1px solid var(--border)}
.committee-icon{font-size:1.4rem;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;flex-shrink:0}
.committee-name{font-weight:700;color:var(--text)}
.committee-role{font-size:.85rem;color:var(--text-muted)}
.committee-year{font-size:.8rem;color:var(--accent);margin-right:auto}
.empty-state{text-align:center;padding:20px;color:var(--text-muted);font-size:.9rem}

/* ─── أكورديون ─── */
.acc-toggle{width:100%;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:10px 0;font-family:inherit;font-size:1rem;color:var(--green-dark);font-weight:700}
.acc-toggle .arrow{transition:transform .25s;font-size:.8rem}
.acc-toggle.open .arrow{transform:rotate(180deg)}
.acc-body{max-height:0;overflow:hidden;transition:max-height .3s ease}
.acc-body.open{max-height:600px}

/* ─── جدول الاشتراكات ─── */
.summary-bar{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px}
.summary-item{flex:1;min-width:120px;background:var(--bg-alt);border-radius:var(--radius);padding:14px;text-align:center;border:1px solid var(--border)}
.summary-value{font-size:1.3rem;font-weight:700;color:var(--green-dark)}
.summary-label{font-size:.8rem;color:var(--text-muted);margin-top:2px}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.9rem}
th{background:var(--primary);color:#fff;padding:10px 12px;text-align:center;font-weight:600}
td{padding:10px 12px;text-align:center;border-bottom:1px solid var(--border)}
tr:nth-child(even){background:var(--bg-alt)}
.pay-paid{color:var(--green);font-weight:700}
.pay-unpaid{color:#d32f2f;font-weight:700}
.pay-exempt{color:var(--text-muted);font-weight:700}

/* ─── إجراءات ─── */
.actions{display:flex;gap:12px;flex-wrap:wrap}
.btn-action{padding:12px 20px;border:none;border-radius:var(--radius);font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
.btn-pdf{background:var(--primary);color:#fff}
.btn-pdf:hover{opacity:.85}
.btn-objection{background:var(--accent);color:#1a1a1a}
.btn-objection:hover{opacity:.85}

/* ─── اعتراضات ─── */
.obj-status{padding:3px 10px;border-radius:12px;font-size:.8rem;font-weight:700}
.obj-new{background:#e3f2fd;color:#1565c0}
.obj-review{background:#fff3e0;color:#e65100}
.obj-done{background:var(--green-light);color:var(--green-dark)}
.obj-rejected{background:#fce4ec;color:#c62828}
[data-theme="dark"] .obj-new{background:#0d2137;color:#64b5f6}
[data-theme="dark"] .obj-review{background:#2e1b00;color:#ffb74d}
[data-theme="dark"] .obj-rejected{background:#3b1111;color:#ef9a9a}

/* ─── Modal ─── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px}
.modal-overlay.active{display:flex}
.modal{background:var(--surface);border-radius:var(--radius-lg);padding:28px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 16px 48px rgba(0,0,0,.2)}
.modal h3{font-family:var(--font-heading);color:var(--green-dark);margin-bottom:20px;font-size:1.2rem}
.modal label{display:block;font-weight:700;margin-bottom:6px;color:var(--green-dark);font-size:.9rem}
.modal input,.modal textarea,.modal select{width:100%;padding:12px 14px;border:2px solid var(--border);border-radius:var(--radius);font-size:.9rem;font-family:inherit;background:var(--bg);color:var(--text);margin-bottom:14px;transition:border-color .2s}
.modal input:focus,.modal textarea:focus,.modal select:focus{outline:none;border-color:var(--green)}
.modal textarea{resize:vertical;min-height:100px}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:8px}
.btn-cancel{padding:10px 20px;border:1px solid var(--border);border-radius:var(--radius);background:transparent;color:var(--text-muted);font-family:inherit;cursor:pointer}
.btn-submit{padding:10px 20px;border:none;border-radius:var(--radius);background:var(--gradient-header);color:#fff;font-weight:700;font-family:inherit;cursor:pointer}

/* ─── Theme toggle ─── */
.theme-toggle{position:fixed;bottom:16px;left:16px;background:var(--surface);border:1px solid var(--border);border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:18px;box-shadow:var(--shadow-sm);z-index:100}

/* ─── Responsive ─── */
@media(max-width:640px){
  .id-card{flex-direction:column;text-align:center;gap:12px}
  .id-status{margin:8px auto 0}
  .summary-bar{flex-direction:column}
  .topbar{padding:12px 16px}
  .topbar h1{font-size:1rem}
  .container{padding:16px 12px}
  .actions{flex-direction:column}
  .btn-action{width:100%;justify-content:center}
  table{font-size:.8rem}
  th,td{padding:8px 6px}
}
</style>
</head>
<body>

<!-- الشريط العلوي -->
<div class="topbar">
  <div class="topbar-right">
    <svg class="topbar-logo" viewBox="0 0 80 80" fill="none">
      <rect width="80" height="80" rx="16" fill="rgba(255,255,255,.15)"/>
      <path d="M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36" stroke="#fff" stroke-width="6" stroke-linecap="round" fill="none"/>
      <path d="M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62" stroke="#fff" stroke-width="5" stroke-linecap="round" fill="none"/>
      <circle cx="34" cy="62" r="5" fill="#fff"/>
    </svg>
    <h1><?= $siteTitle ?></h1>
  </div>
  <div class="topbar-left">
    <span class="topbar-user"><?= $memberName ?></span>
    <button class="btn-sm" onclick="handleLogout()">تسجيل الخروج</button>
  </div>
</div>

<div class="container">
  <!-- حالة التحميل -->
  <div id="loading" style="text-align:center;padding:60px 0;color:var(--text-muted)">جاري تحميل البيانات...</div>
  <div id="content" style="display:none">

    <!-- أ. بطاقة الهوية -->
    <div class="card id-card">
      <div class="id-avatar" id="avatar">👤</div>
      <div class="id-info">
        <h2 id="full-name"></h2>
        <div class="awm-id" id="display-awm-id"></div>
        <div class="join-date" id="join-date"></div>
      </div>
      <div class="id-status" id="member-status"></div>
    </div>

    <!-- ب. اللجان الحالية -->
    <div class="card">
      <div class="card-title">🏛️ اللجان الحالية</div>
      <ul class="committee-list" id="current-committees"></ul>
    </div>

    <!-- ج. اللجان السابقة -->
    <div class="card" id="past-committees-card" style="display:none">
      <button class="acc-toggle" onclick="toggleAccordion(this)">
        <span>📁 اللجان السابقة</span>
        <span class="arrow">▼</span>
      </button>
      <div class="acc-body" id="past-committees-body">
        <ul class="committee-list" id="past-committees" style="padding-top:12px"></ul>
      </div>
    </div>

    <!-- د. سجل الاشتراكات -->
    <div class="card">
      <div class="card-title">💳 سجل الاشتراكات</div>
      <div class="summary-bar" id="financial-summary"></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>الفترة</th><th>المطلوب</th><th>المدفوع</th><th>الحالة</th><th>تاريخ الدفع</th></tr></thead>
          <tbody id="subscriptions-table"></tbody>
        </table>
      </div>
    </div>

    <!-- هـ. إجراءات العضو -->
    <div class="card">
      <div class="card-title">⚡ إجراءات</div>
      <div class="actions">
        <button class="btn-action btn-pdf" onclick="downloadReport()">📄 طباعة التقرير PDF</button>
        <button class="btn-action btn-objection" onclick="openObjectionModal()">✍️ تقديم ملاحظة أو اعتراض</button>
      </div>
    </div>

    <!-- و. اعتراضاتي السابقة -->
    <div class="card" id="objections-card" style="display:none">
      <div class="card-title">📋 اعتراضاتي السابقة</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>الموضوع</th><th>النوع</th><th>الحالة</th><th>التاريخ</th><th>رد المدير</th></tr></thead>
          <tbody id="objections-table"></tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Modal: تقديم اعتراض -->
<div class="modal-overlay" id="objection-modal">
  <div class="modal">
    <h3>تقديم ملاحظة أو اعتراض</h3>
    <div class="alert" id="obj-alert" style="display:none;padding:12px;border-radius:var(--radius);text-align:center;font-weight:600;font-size:.9rem;margin-bottom:14px"></div>
    <form id="objection-form" onsubmit="return submitObjection(event)">
      <label for="obj-type">نوع الملاحظة</label>
      <select id="obj-type" name="related_to">
        <option value="أخرى">أخرى</option>
        <option value="دفعة">دفعة مالية</option>
        <option value="لجنة">لجنة</option>
        <option value="بيانات">بيانات شخصية</option>
      </select>
      <label for="obj-subject">الموضوع</label>
      <input type="text" id="obj-subject" name="subject" required maxlength="200" placeholder="عنوان مختصر للملاحظة">
      <label for="obj-body">التفاصيل</label>
      <textarea id="obj-body" name="body" required maxlength="5000" placeholder="اكتب تفاصيل ملاحظتك أو اعتراضك هنا..."></textarea>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeObjectionModal()">إلغاء</button>
        <button type="submit" class="btn-submit" id="obj-submit-btn">إرسال</button>
      </div>
    </form>
  </div>
</div>

<!-- Theme toggle -->
<button class="theme-toggle" onclick="toggleTheme()" title="تغيير المظهر"><span id="theme-icon">🌙</span></button>

<script>
var CSRF = '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>';

/* ═══ تحميل البيانات ═══ */
document.addEventListener('DOMContentLoaded', loadProfile);

function loadProfile() {
  fetch('/api/member/profile.php')
    .then(function(r) {
      if (r.status === 401) { window.location.href = '/login'; return null; }
      return r.json();
    })
    .then(function(data) {
      if (!data) return;
      renderProfile(data);
      document.getElementById('loading').style.display = 'none';
      document.getElementById('content').style.display = 'block';
    })
    .catch(function() {
      document.getElementById('loading').textContent = 'حدث خطأ في تحميل البيانات. حاول تحديث الصفحة.';
    });
}

function renderProfile(data) {
  var p = data.profile;

  /* بطاقة الهوية */
  var initials = (p.name || '').charAt(0);
  document.getElementById('avatar').textContent = initials || '👤';
  document.getElementById('full-name').textContent = (p.name + ' ' + (p.family || '')).trim();
  document.getElementById('display-awm-id').textContent = p.awm_id;
  document.getElementById('join-date').textContent = p.join_date ? 'عضو منذ: ' + p.join_date : '';

  var statusEl = document.getElementById('member-status');
  var statusMap = {
    'نشط':     { cls: 'status-active',   label: '● نشط' },
    'منقطع':    { cls: 'status-lapsed',   label: '⚠️ منقطع' },
    'غير نشط': { cls: 'status-inactive', label: '❌ غير نشط' },
    'معفي':     { cls: 'status-exempt',   label: '🔒 معفي' }
  };
  var s = statusMap[p.status] || { cls: 'status-active', label: p.status };
  statusEl.className = 'id-status ' + s.cls;
  statusEl.textContent = s.label;

  /* اللجان الحالية */
  var ccEl = document.getElementById('current-committees');
  if (data.current_committees.length === 0) {
    ccEl.innerHTML = '<div class="empty-state">لا توجد عضوية حالية في لجان</div>';
  } else {
    ccEl.innerHTML = data.current_committees.map(function(c) {
      var role = c.role ? '<span class="committee-role">' + esc(c.role) + '</span>' : '';
      var year = c.start_year ? '<span class="committee-year">منذ ' + c.start_year + '</span>' : '';
      return '<li class="committee-item">'
        + '<span class="committee-icon" style="background:' + esc(c.color || 'var(--green-light)') + '">' + esc(c.icon || '🏛️') + '</span>'
        + '<div><span class="committee-name">' + esc(c.name) + '</span><br>' + role + '</div>'
        + year + '</li>';
    }).join('');
  }

  /* اللجان السابقة */
  if (data.past_committees.length > 0) {
    document.getElementById('past-committees-card').style.display = 'block';
    document.getElementById('past-committees').innerHTML = data.past_committees.map(function(c) {
      var role = c.role ? '<span class="committee-role">' + esc(c.role) + '</span>' : '';
      var years = (c.start_year || '?') + ' - ' + (c.end_year || '?');
      return '<li class="committee-item">'
        + '<span class="committee-icon" style="background:' + esc(c.color || 'var(--green-light)') + '">' + esc(c.icon || '🏛️') + '</span>'
        + '<div><span class="committee-name">' + esc(c.name) + '</span><br>' + role + '</div>'
        + '<span class="committee-year">' + years + '</span></li>';
    }).join('');
  }

  /* ملخص مالي */
  var fs = data.financial_summary;
  document.getElementById('financial-summary').innerHTML =
    summaryItem(formatNum(fs.total_paid) + ' ريال', 'المدفوع') +
    summaryItem(formatNum(fs.total_required) + ' ريال', 'المطلوب') +
    summaryItem(formatNum(fs.total_unpaid) + ' ريال', 'المتبقي') +
    summaryItem(fs.compliance_rate + '%', 'نسبة الالتزام');

  /* جدول الاشتراكات */
  var tbody = document.getElementById('subscriptions-table');
  if (data.subscriptions.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="empty-state">لا توجد فترات مالية</td></tr>';
  } else {
    tbody.innerHTML = data.subscriptions.map(function(sub) {
      var status = sub.pay_status || 'لم يدفع';
      var cls = status === 'مدفوع' ? 'pay-paid' : (status === 'معفي' ? 'pay-exempt' : 'pay-unpaid');
      var icon = status === 'مدفوع' ? '✅' : (status === 'معفي' ? '🔒' : '❌');
      var paid = status === 'مدفوع' ? formatNum(sub.paid_amount) + ' ريال' : '—';
      return '<tr><td>' + esc(sub.period_name) + '</td>'
        + '<td>' + formatNum(sub.fee_amount) + ' ريال</td>'
        + '<td>' + paid + '</td>'
        + '<td class="' + cls + '">' + icon + ' ' + status + '</td>'
        + '<td>' + (sub.pay_date || '—') + '</td></tr>';
    }).join('');
  }

  /* اعتراضات */
  if (data.objections.length > 0) {
    document.getElementById('objections-card').style.display = 'block';
    document.getElementById('objections-table').innerHTML = data.objections.map(function(o) {
      var stCls = { 'جديد': 'obj-new', 'قيد المراجعة': 'obj-review', 'تمت المعالجة': 'obj-done', 'مرفوض': 'obj-rejected' };
      return '<tr><td>' + esc(o.subject) + '</td>'
        + '<td>' + esc(o.related_to) + '</td>'
        + '<td><span class="obj-status ' + (stCls[o.status] || '') + '">' + esc(o.status) + '</span></td>'
        + '<td>' + (o.created_at || '').substring(0, 10) + '</td>'
        + '<td>' + (o.admin_reply ? esc(o.admin_reply) : '—') + '</td></tr>';
    }).join('');
  }
}

/* ═══ Helpers ═══ */
function esc(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function formatNum(n) { return Number(n || 0).toLocaleString('ar-SA'); }
function summaryItem(val, label) {
  return '<div class="summary-item"><div class="summary-value">' + val + '</div><div class="summary-label">' + label + '</div></div>';
}

/* ═══ أكورديون ═══ */
function toggleAccordion(btn) {
  btn.classList.toggle('open');
  var body = btn.nextElementSibling;
  body.classList.toggle('open');
}

/* ═══ تقرير PDF ═══ */
function downloadReport() {
  window.open('/api/member/report.php', '_blank');
}

/* ═══ اعتراض Modal ═══ */
function openObjectionModal() {
  document.getElementById('objection-modal').classList.add('active');
  document.getElementById('obj-alert').style.display = 'none';
  document.getElementById('objection-form').reset();
}
function closeObjectionModal() {
  document.getElementById('objection-modal').classList.remove('active');
}

function submitObjection(e) {
  e.preventDefault();
  var btn = document.getElementById('obj-submit-btn');
  var alert = document.getElementById('obj-alert');
  btn.disabled = true;
  btn.textContent = 'جاري الإرسال...';
  alert.style.display = 'none';

  var data = {
    subject: document.getElementById('obj-subject').value.trim(),
    body: document.getElementById('obj-body').value.trim(),
    related_to: document.getElementById('obj-type').value
  };

  fetch('/api/member/objection.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify(data)
  })
  .then(function(r) { return r.json(); })
  .then(function(res) {
    if (res.error) {
      alert.textContent = res.error;
      alert.style.display = 'block';
      alert.style.background = '#fee2e2';
      alert.style.color = '#991b1b';
      btn.disabled = false;
      btn.textContent = 'إرسال';
      return;
    }
    alert.textContent = res.message || 'تم إرسال ملاحظتك بنجاح';
    alert.style.display = 'block';
    alert.style.background = 'var(--green-light)';
    alert.style.color = 'var(--green-dark)';
    btn.disabled = false;
    btn.textContent = 'إرسال';
    setTimeout(function() { closeObjectionModal(); loadProfile(); }, 1500);
  })
  .catch(function() {
    alert.textContent = 'حدث خطأ في الاتصال';
    alert.style.display = 'block';
    alert.style.background = '#fee2e2';
    alert.style.color = '#991b1b';
    btn.disabled = false;
    btn.textContent = 'إرسال';
  });
  return false;
}

/* ═══ تسجيل الخروج ═══ */
function handleLogout() {
  fetch('/api/auth/member-logout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF }
  }).finally(function() { window.location.href = '/login'; });
}

/* ═══ Theme ═══ */
function toggleTheme() {
  var html = document.documentElement;
  var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('awami-theme', next);
  document.getElementById('theme-icon').textContent = next === 'dark' ? '☀️' : '🌙';
}
(function(){ document.getElementById('theme-icon').textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙'; })();

/* إغلاق Modal بالضغط خارجه */
document.getElementById('objection-modal').addEventListener('click', function(e) {
  if (e.target === this) closeObjectionModal();
});
</script>
</body>
</html>
