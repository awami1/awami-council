<?php
require_once __DIR__ . '/../api/auth_guard.php';
if (!isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>مجلس عائلة العوامي - نظام الإدارة</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/admin.css">
<script><?php readfile(dirname(__DIR__) . '/public/js/api.js'); ?></script>
<script>
// ── معالج 401: انتهاء الجلسة → إعادة توجيه لصفحة الدخول ──
(function () {
    const _orig = apiFetch;
    apiFetch = async function (url, options) {
        const res = await fetch(url, {
            headers: { 'Content-Type': 'application/json' },
            ...(options || {}),
        });
        if (res.status === 401) {
            window.location.href = '/admin/login.php?expired=1';
            return new Promise(() => {});
        }
        const json = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(json.error || 'HTTP ' + res.status);
        return json;
    };
})();
</script>
<script src="admin.db.js"></script>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="logo-wrap">
      <div class="logo-img">
        <!-- SVG logo inspired by Al-Awami calligraphic mark -->
        <svg class="logo-svg" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M55 12 C58 8, 65 10, 64 18 C63 26, 54 30, 50 38 C46 46, 48 56, 42 62 C36 68, 26 66, 24 58 C22 50, 30 44, 32 36" stroke="#47915C" stroke-width="6" stroke-linecap="round" fill="none"/>
          <path d="M32 36 C28 44, 20 46, 20 54 C20 62, 28 66, 34 62" stroke="#47915C" stroke-width="5" stroke-linecap="round" fill="none"/>
          <circle cx="34" cy="62" r="5" fill="#47915C"/>
        </svg>
      </div>
      <div class="logo-text">
        <h2>مجلس عائلة العوامي</h2>
        <span>AL AWAMI • 1413 - 1992</span>
      </div>
    </div>
  </div>
  <nav class="nav">
    <div class="nav-section">الرئيسية</div>
    <div class="nav-item active" onclick="showPage('dashboard',this)"><span class="icon">📊</span>لوحة التحكم</div>
    <div class="nav-section">المجلس</div>
    <div class="nav-item" onclick="showPage('council',this)"><span class="icon">👑</span>مناصب المجلس</div>
    <div class="nav-item" onclick="showPage('members',this)"><span class="icon">👥</span>الأعضاء</div>
    <div class="nav-item" onclick="showPage('fees',this)"><span class="icon">💳</span>الرسوم والمدفوعات</div>
    <div class="nav-item" onclick="showPage('reminders',this)"><span class="icon">🔔</span>التذكيرات</div>
    <div class="nav-section">التنظيم</div>
    <div class="nav-item" onclick="showPage('committees',this)"><span class="icon">🏛️</span>اللجان</div>
    <div class="nav-item" onclick="showPage('orgchart',this)"><span class="icon">🗂️</span>الهيكل التنظيمي</div>
    <div class="nav-section">المالية</div>
    <div class="nav-item" onclick="showPage('budget',this)"><span class="icon">💰</span>الميزانية والمصاريف</div>
    <div class="nav-section">الأنشطة</div>
    <div class="nav-item" onclick="showPage('events',this)"><span class="icon">🗓️</span>الفعاليات</div>
    <div class="nav-item" onclick="showPage('news',this)"><span class="icon">📰</span>الأخبار</div>
    <div class="nav-item" onclick="showPage('calendar',this)"><span class="icon">📅</span>التقويم</div>
    <div class="nav-item" onclick="showPage('voting',this)"><span class="icon">🗳️</span>التصويت</div>
    <div class="nav-section">التواصل</div>
    <div class="nav-item" onclick="showPage('messages',this)"><span class="icon">✉️</span>الرسائل <span id="msg-badge" class="nav-badge" style="display:none">0</span></div>
    <div class="nav-section">العائلة</div>
    <div class="nav-item" onclick="showPage('familytree',this)"><span class="icon">🌳</span>شجرة العائلة</div>
    <div class="nav-section">التقارير</div>
    <div class="nav-item" onclick="showPage('smart-reports',this)"><span class="icon">🤖</span>التقارير الذكية</div>
    <div class="nav-item" onclick="showPage('portal',this)"><span class="icon">👤</span>بوابة العضو</div>
    <div class="nav-item" onclick="showPage('reports',this)"><span class="icon">📈</span>التقارير</div>
    <div class="nav-item" onclick="showPage('export',this)"><span class="icon">📥</span>تصدير البيانات</div>
    <div class="nav-section">الإعدادات</div>
    <div class="nav-item" onclick="showPage('audit',this)"><span class="icon">📋</span>سجل التدقيق</div>
    <div class="nav-item" onclick="showPage('websettings',this)"><span class="icon">🌐</span>الموقع العام</div>
    <div class="nav-item" onclick="showPage('settings',this)"><span class="icon">⚙️</span>النسخ الاحتياطي</div>
  </nav>
  <div class="sidebar-footer">
    <div id="countdown-widget" style="background:rgba(255,255,255,.08);border-radius:10px;padding:12px;margin-bottom:10px;text-align:center;display:none">
      <div style="font-size:10px;color:rgba(255,255,255,.5);margin-bottom:4px">الجلسة العمومية القادمة</div>
      <div id="countdown-display" style="font-size:16px;font-weight:700;color:#a8e6bb;font-family:'Tajawal',sans-serif"></div>
      <div id="countdown-date" style="font-size:9px;color:rgba(255,255,255,.4);margin-top:3px"></div>
    </div>
    <div class="stats-mini">
      <div><div class="stat-mini-val" id="sb-members">0</div><div class="stat-mini-lbl">عضو</div></div>
      <div><div class="stat-mini-val" id="sb-committees">0</div><div class="stat-mini-lbl">لجنة</div></div>
      <div><div class="stat-mini-val" id="sb-balance">0</div><div class="stat-mini-lbl">ريال</div></div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <div class="page-title" id="topbar-title">لوحة التحكم <span>مجلس عائلة العوامي</span></div>
    <div style="display:flex;gap:8px;align-items:center;">
      <div class="topbar-actions" id="topbar-action"></div>
      <button class="dark-toggle" onclick="toggleDarkMode()" id="dark-mode-btn" title="الوضع الليلي">🌙</button>
      <button class="btn btn-outline btn-sm" onclick="adminLogout()" title="تسجيل الخروج"
              style="border-color:#fca5a5;color:#b91c1c;">🚪 خروج</button>
    </div>
  </div>
  <div class="content">

    <!-- DASHBOARD -->
    <div class="page active" id="page-dashboard">
      <div class="stats-grid">
        <div class="stat-card green"><div class="stat-label">الرصيد الكلي</div><div class="stat-value" id="d-balance">0</div><div class="stat-sub">ريال سعودي</div><div class="stat-icon">💰</div></div>
        <div class="stat-card gold"><div class="stat-label">الأعضاء النشطون</div><div class="stat-value" id="d-members">0</div><div class="stat-sub">من أصل <span id="d-total">0</span></div><div class="stat-icon">👥</div></div>
        <div class="stat-card blue"><div class="stat-label">عدد اللجان</div><div class="stat-value" id="d-committees">0</div><div class="stat-sub">لجنة نشطة</div><div class="stat-icon">🏛️</div></div>
        <div class="stat-card orange"><div class="stat-label">المتأخرون</div><div class="stat-value" id="d-unpaid">0</div><div class="stat-sub">عضو لم يدفع</div><div class="stat-icon">⏳</div></div>
      </div>
      <div class="grid-2" style="margin-bottom:16px">
        <div class="card">
          <div class="card-header"><div class="card-title">حالة الدفع - الدورة الحالية</div><span id="d-period-lbl" class="badge badge-info">--</span></div>
          <div class="card-body">
            <div style="margin-bottom:12px"><div style="display:flex;justify-content:space-between;margin-bottom:5px"><span style="font-size:12px;color:var(--text-muted)">نسبة الإنجاز</span><span style="font-weight:700;color:var(--green)" id="d-pct">0%</span></div><div class="progress-bar"><div class="progress-fill" id="d-bar" style="width:0%"></div></div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;text-align:center">
              <div style="background:#dcfce7;border-radius:10px;padding:10px"><div style="font-size:18px;font-weight:900;color:#166534" id="d-paid">0</div><div style="font-size:11px;color:#166534">دفعوا ✅</div></div>
              <div style="background:#fef9c3;border-radius:10px;padding:10px"><div style="font-size:18px;font-weight:900;color:#854d0e" id="d-pending">0</div><div style="font-size:11px;color:#854d0e">لم يدفعوا ⏳</div></div>
              <div style="background:#f3e8ff;border-radius:10px;padding:10px"><div style="font-size:18px;font-weight:900;color:#7e22ce" id="d-exempt">0</div><div style="font-size:11px;color:#7e22ce">معفيون 🔖</div></div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">آخر المعاملات</div></div>
          <div id="d-recent" style="padding:6px 0;max-height:200px;overflow-y:auto"></div>
        </div>
      </div>
      <div class="grid-2">
        <div class="card"><div class="card-header"><div class="card-title">الفعاليات القادمة</div></div><div id="d-events" style="padding:10px"></div></div>
        <div class="card"><div class="card-header"><div class="card-title">التصويتات النشطة</div></div><div id="d-polls" style="padding:10px"></div></div>
      </div>
      <div class="grid-2" style="margin-top:16px">
        <div class="card"><div class="card-header"><div class="card-title">آخر الرسائل الواردة</div><span class="badge badge-info" id="d-msg-badge">0 جديدة</span></div><div id="d-messages" style="padding:10px"></div></div>
        <div class="card"><div class="card-header"><div class="card-title">آخر الأخبار</div></div><div id="d-news" style="padding:10px"></div></div>
      </div>
    </div>

    <!-- COUNCIL POSITIONS -->
    <div class="page" id="page-council">
      <div class="council-hero">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px">
          <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:28px">👑</div>
          <div>
            <div style="font-size:20px;font-weight:800">إدارة مجلس عائلة العوامي</div>
            <div style="opacity:.7;font-size:13px">الهيئة الإدارية • تأسس 1992م - 1413هـ</div>
          </div>
        </div>
        <div style="font-size:13px;opacity:.75;line-height:1.7;font-style:italic">"أتقدم إليكم بجزيل الشكر وعظيم التقدير على تقدمكم لخدمة عائلتكم، مثالاً للحرص والمسؤولية لتحقيق المصلحة العامة لعائلتنا المترابطة"<br><span style="font-size:12px;opacity:.8">— الوالد أبو حسين علي سلمان</span></div>
      </div>
      <div id="positions-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px"></div>
    </div>

    <!-- MEMBERS -->
    <div class="page" id="page-members">
      <div class="card">
        <div class="search-bar">
          <button class="btn btn-primary btn-sm" onclick="openAddMember()">+ إضافة عضو</button>
          <button class="btn btn-accent btn-sm" onclick="openImportExcel()">📥 استيراد Excel</button>
          <input class="search-input" id="m-search" placeholder="بحث بالاسم أو الجوال..." oninput="debouncedRenderMembers()">
          <select class="filter-select" style="width:130px" id="m-flt-status" onchange="_pageState.members=1;renderMembers()"><option value="">كل الحالات</option><option>نشط</option><option>معفي</option><option>غير نشط</option></select>
          <button class="btn btn-outline btn-sm" onclick="exportMembersExcel()">📊 Excel</button>
        </div>
        <div class="table-wrap mobile-cards"><table><thead><tr><th>#</th><th>العضو</th><th>الجوال</th><th>اللجان</th><th>الانضمام</th><th>الحالة</th><th>الدفع</th><th>إجراءات</th></tr></thead><tbody id="members-tbody"></tbody></table></div>
        <div id="members-pagination"></div>
        <div style="padding:12px 18px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:12px;color:var(--text-muted)" id="members-count">0 عضو</span>
          <button class="btn btn-whatsapp btn-sm" onclick="sendWhatsappReminders()">📱 واتساب للمتأخرين</button>
        </div>
      </div>
    </div>

    <!-- FEES -->
    <div class="page" id="page-fees">
      <div class="card" style="margin-bottom:16px">
        <div class="card-header">
          <div class="card-title">الدورة الحالية</div>
          <div style="display:flex;gap:8px;align-items:center">
            <span id="fees-period-lbl" class="badge badge-info">--</span>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-period')">+ دورة جديدة</button>
            <button class="btn btn-outline btn-sm" onclick="exportFeesExcel()">📊 Excel</button>
          </div>
        </div>
        <div class="card-body"><div id="fees-stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px"></div></div>
      </div>
      <div class="card">
        <div class="search-bar">
          <input class="search-input" id="fees-search" placeholder="بحث..." oninput="debouncedRenderFees()">
          <select class="filter-select" style="width:160px" id="fees-flt" onchange="_pageState.fees=1;renderFees()"><option value="">كل الحالات</option><option value="مدفوع">مدفوع ✅</option><option value="لم يدفع">لم يدفع ⏳</option><option value="معفي">معفي 🔖</option></select>
        </div>
        <div class="table-wrap mobile-cards"><table><thead><tr><th>العضو</th><th>المطلوب</th><th>المدفوع</th><th>التاريخ</th><th>الطريقة</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody id="fees-tbody"></tbody></table></div>
        <div id="fees-pagination"></div>
      </div>
    </div>

    <!-- REMINDERS -->
    <div class="page" id="page-reminders">
      <div class="card" style="margin-bottom:16px">
        <div class="card-header">
          <div class="card-title">🔔 تذكيرات الأعضاء غير الدافعين</div>
          <div style="display:flex;gap:8px;align-items:center">
            <span id="rem-period-lbl" class="badge badge-info">--</span>
            <button class="btn btn-whatsapp btn-sm" onclick="sendBulkReminder('whatsapp')">📱 واتساب جماعي</button>
            <button class="btn btn-primary btn-sm" onclick="sendBulkReminder('sms')">💬 SMS جماعي</button>
          </div>
        </div>
        <div class="card-body">
          <div id="rem-stats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px"></div>
          <div id="rem-list"></div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">📋 سجل التذكيرات المرسلة</div>
        </div>
        <div class="table-wrap"><table><thead><tr><th>التاريخ</th><th>العضو</th><th>القناة</th><th>المُرسِل</th></tr></thead><tbody id="rem-history-tbody"></tbody></table></div>
        <div id="rem-history-pagination" style="display:flex;gap:4px;justify-content:center;padding:10px"></div>
      </div>
    </div>

    <!-- COMMITTEES -->
    <div class="page" id="page-committees">
      <div style="display:flex;justify-content:flex-end;margin-bottom:14px">
        <button class="btn btn-primary btn-sm" onclick="openAddCommitteeModal()">+ إضافة لجنة</button>
      </div>
      <div id="committees-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px"></div>
    </div>

    <!-- ORG CHART -->
    <div class="page" id="page-orgchart">
      <div class="card"><div class="card-header"><div class="card-title">الهيكل التنظيمي — مجلس عائلة العوامي</div></div><div class="card-body" id="org-body" style="overflow-x:auto;padding:20px"></div></div>
    </div>

    <!-- BUDGET -->
    <div class="page" id="page-budget">
      <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
        <div class="stat-card green"><div class="stat-label">إجمالي الإيرادات</div><div class="stat-value" id="b-income">0</div><div class="stat-sub">ريال</div><div class="stat-icon">⬆️</div></div>
        <div class="stat-card" style="--primary:var(--danger)"><div class="stat-card__before"></div><div class="stat-label">إجمالي المصاريف</div><div class="stat-value" id="b-expense">0</div><div class="stat-sub">ريال</div><div class="stat-icon">⬇️</div></div>
        <div class="stat-card gold"><div class="stat-label">صافي الرصيد</div><div class="stat-value" id="b-net">0</div><div class="stat-sub">ريال</div><div class="stat-icon">💎</div></div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">سجل المعاملات</div>
          <div style="display:flex;gap:8px">
            <select class="filter-select btn-sm" style="width:130px" id="b-flt" onchange="_pageState.budget=1;renderBudget()"><option value="">الكل</option><option value="إيراد">إيرادات</option><option value="مصروف">مصاريف</option></select>
            <button class="btn btn-outline btn-sm" onclick="exportBudgetExcel()">📊 Excel</button>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-tx')">+ معاملة</button>
          </div>
        </div>
        <div class="table-wrap mobile-cards"><table><thead><tr><th>التاريخ</th><th>الوصف</th><th>الفئة</th><th>اللجنة</th><th>النوع</th><th>المبلغ</th><th></th></tr></thead><tbody id="budget-tbody"></tbody></table></div>
        <div id="budget-pagination"></div>
      </div>
    </div>

    <!-- EVENTS -->
    <div class="page" id="page-events">
      <div class="card" style="margin-bottom:16px">
        <div class="card-header">
          <div class="card-title">🗓️ الفعاليات</div>
          <button class="btn btn-primary btn-sm" onclick="openAddEvent()">+ فعالية جديدة</button>
        </div>
      </div>
      <div id="events-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px"></div>
    </div>

    <!-- CALENDAR -->
    <div class="page" id="page-calendar">
      <div class="card">
        <div class="card-header">
          <div class="card-title">📅 تقويم الفعاليات</div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-outline btn-sm" id="cal-prev" onclick="changeMonth(-1)">❮</button>
            <span id="cal-month-year" style="font-weight:600;min-width:120px;text-align:center;display:flex;align-items:center"></span>
            <button class="btn btn-outline btn-sm" id="cal-next" onclick="changeMonth(1)">❯</button>
            <button class="btn btn-primary btn-sm" onclick="resetCalendar()">اليوم</button>
          </div>
        </div>
        <div class="card-body" id="calendar-container"></div>
      </div>
      <div style="margin-top:16px;display:grid;grid-template-columns:repeat(2,1fr);gap:14px">
        <div class="card">
          <div class="card-header"><div class="card-title">فعاليات الشهر</div></div>
          <div id="month-events-list" style="padding:10px"></div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">فعاليات قادمة</div></div>
          <div id="upcoming-events-list" style="padding:10px"></div>
        </div>
      </div>
    </div>

    <!-- NEWS -->
    <div class="page" id="page-news">
      <div class="card">
        <div class="search-bar">
          <button class="btn btn-primary btn-sm" onclick="openAddNews()">+ إضافة خبر</button>
          <input class="search-input" id="news-search" placeholder="بحث في الأخبار..." oninput="debouncedRenderNews()">
          <select class="filter-select" style="width:130px" id="news-flt-status" onchange="_pageState.news=1;renderNews()"><option value="">كل الحالات</option><option value="published">منشور</option><option value="draft">مسودة</option></select>
          <select class="filter-select" style="width:130px" id="news-flt-cat" onchange="_pageState.news=1;renderNews()"><option value="">كل التصنيفات</option><option>عام</option><option>فعاليات</option><option>إعلانات</option><option>اجتماعات</option><option>مالية</option><option>اجتماعية</option></select>
        </div>
        <div class="table-wrap mobile-cards"><table><thead><tr><th>#</th><th>العنوان</th><th>التصنيف</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr></thead><tbody id="news-tbody"></tbody></table></div>
        <div id="news-pagination"></div>
        <div style="padding:12px 18px;border-top:1px solid var(--border)"><span style="font-size:12px;color:var(--text-muted)" id="news-count">0 خبر</span></div>
      </div>
    </div>

    <!-- MESSAGES -->
    <div class="page" id="page-messages">
      <div class="card">
        <div class="search-bar">
          <input class="search-input" id="msg-search" placeholder="بحث في الرسائل..." oninput="debouncedRenderMessages()">
          <select class="filter-select" style="width:130px" id="msg-flt-read" onchange="_pageState.messages=1;renderMessages()"><option value="">كل الرسائل</option><option value="0">غير مقروءة</option><option value="1">مقروءة</option></select>
        </div>
        <div class="table-wrap mobile-cards"><table><thead><tr><th>#</th><th>المرسل</th><th>الموضوع</th><th>الرسالة</th><th>التاريخ</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody id="msg-tbody"></tbody></table></div>
        <div id="messages-pagination"></div>
        <div style="padding:12px 18px;border-top:1px solid var(--border)"><span style="font-size:12px;color:var(--text-muted)" id="msg-count">0 رسالة</span></div>
      </div>
    </div>

    <!-- FAMILY TREE -->
    <div class="page" id="page-familytree">
      <!-- إدارة أفراد الشجرة -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-header">
          <div class="card-title">🌳 شجرة عائلة العوامي</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-primary btn-sm" onclick="openAddTreeMember()">+ إضافة شخص</button>
            <button class="btn btn-outline btn-sm" onclick="exportTreeJSON()">📥 تصدير JSON</button>
          </div>
        </div>
        <div class="card-body">
          <div class="search-bar" style="margin-bottom:12px">
            <input class="search-input" id="ftm-search" placeholder="بحث بالاسم..." oninput="renderFamilyTreeList()">
          </div>
          <div id="ftm-list"></div>
          <div style="padding:8px 0;font-size:12px;color:var(--text-muted)" id="ftm-count">0 شخص</div>
        </div>
      </div>

      <!-- معاينة الشجرة -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">👁️ معاينة الشجرة</div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-outline btn-sm" onclick="treePreviewExpandAll()">توسيع</button>
            <button class="btn btn-outline btn-sm" onclick="treePreviewCollapseAll()">طي</button>
          </div>
        </div>
        <div class="card-body" id="tree-container" style="overflow:auto;padding:20px;max-height:500px"></div>
      </div>

      <!-- الأفرع القديمة (ثانوي) -->
      <div class="card" style="margin-top:16px">
        <div class="card-header">
          <div class="card-title">🌿 الأفرع العائلية (قديم)</div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-outline btn-sm" onclick="openAddBranch()">+ إضافة فرع</button>
          </div>
        </div>
        <div class="card-body" id="branches-list"></div>
      </div>
    </div>

    <!-- VOTING -->
    <div class="page" id="page-voting">
      <div class="grid-2">
        <div><div id="polls-list"></div></div>
        <div class="card" style="height:fit-content">
          <div class="card-header"><div class="card-title">إنشاء تصويت جديد</div></div>
          <div class="card-body">
            <div class="form-group"><label class="form-label">عنوان التصويت</label><input class="form-control" id="poll-title" placeholder="مثال: هل توافق على رحلة الباحة؟"></div>
            <div class="form-group"><label class="form-label">الخيارات (سطر لكل خيار)</label><textarea class="form-control" id="poll-options" rows="4" placeholder="نعم&#10;لا&#10;ربما"></textarea></div>
            <div class="form-grid">
              <div class="form-group"><label class="form-label">تاريخ الانتهاء</label><input class="form-control" id="poll-end" type="date"></div>
              <div class="form-group"><label class="form-label">اللجنة المعنية</label><select class="form-control" id="poll-committee"><option value="">عام</option></select></div>
            </div>
            <button class="btn btn-primary" style="width:100%" onclick="createPoll()">🗳️ إنشاء التصويت</button>
          </div>
        </div>
      </div>
    </div>

    <!-- PORTAL -->
    <div class="page" id="page-portal">
      <div style="max-width:600px;margin:0 auto">
        <div class="card" style="margin-bottom:16px">
          <div class="card-header"><div class="card-title">🔍 ملف العضو الشخصي</div></div>
          <div class="card-body"><select class="form-control" id="portal-select" onchange="loadPortal()"><option value="">-- اختر العضو --</option></select></div>
        </div>
        <div id="portal-content"></div>
      </div>
    </div>

    <!-- SMART REPORTS AI -->
    <div class="page" id="page-smart-reports">
      <div class="card">
        <div class="card-header">
          <div class="card-title">🤖 التقارير الذكية - تحليل مدعوم بالذكاء الاصطناعي</div>
        </div>
        <div class="card-body">
          <div style="background:#e0f2fe;border:2px solid#7dd3fc;border-radius:12px;padding:20px;margin-bottom:24px">
            <div style="font-size:16px;font-weight:700;color:#075985;margin-bottom:10px">✨ قوة الذكاء الاصطناعي في خدمتك</div>
            <div style="font-size:13px;color:#075985;line-height:1.6">
              • رفع ملفات Excel أو CSV<br>
              • تحليل ذكي تلقائي للبيانات<br>
              • تصنيف المعاملات باستخدام Claude AI<br>
              • كشف التكرار والأخطاء<br>
              • إنشاء تقارير احترافية فورية
            </div>
          </div>

          <!-- File Upload Area -->
          <div id="upload-area" style="border:3px dashed var(--border);border-radius:16px;padding:40px;text-align:center;cursor:pointer;transition:all .3s;margin-bottom:24px" onclick="document.getElementById('file-input-ai').click()">
            <input type="file" id="file-input-ai" accept=".xlsx,.xls,.csv" style="display:none" onchange="handleAIFileUpload(event)">
            <div style="font-size:64px;margin-bottom:12px">📊</div>
            <div style="font-size:18px;font-weight:700;margin-bottom:8px">اسحب ملف Excel هنا أو انقر للاختيار</div>
            <div style="font-size:13px;color:var(--text-muted)">يدعم: .xlsx, .xls, .csv (حد أقصى 10 ميجابايت)</div>
          </div>

          <!-- Processing Status -->
          <div id="ai-processing" style="display:none;padding:20px;background:#fef9c3;border-radius:12px;margin-bottom:24px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
              <div class="spinner" style="width:24px;height:24px;border:3px solid #fde047;border-top-color:#854d0e;border-radius:50%;animation:spin 1s linear infinite"></div>
              <div style="font-weight:700;color:#854d0e" id="ai-status-text">جاري تحليل الملف...</div>
            </div>
            <div style="background:#fff;border-radius:8px;height:8px;overflow:hidden">
              <div id="ai-progress-bar" style="height:100%;background:#854d0e;width:0%;transition:width .3s"></div>
            </div>
          </div>

          <!-- AI Analysis Results -->
          <div id="ai-results" style="display:none">
            <!-- Summary Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px">
              <div class="stat-card green">
                <div class="stat-label">إجمالي المعاملات</div>
                <div class="stat-value" id="ai-total-count">0</div>
                <div class="stat-icon">📊</div>
              </div>
              <div class="stat-card blue">
                <div class="stat-label">إجمالي الإيرادات</div>
                <div class="stat-value" id="ai-total-income">0</div>
                <div class="stat-sub">ريال</div>
                <div class="stat-icon">💰</div>
              </div>
              <div class="stat-card orange">
                <div class="stat-label">إجمالي المصروفات</div>
                <div class="stat-value" id="ai-total-expense">0</div>
                <div class="stat-sub">ريال</div>
                <div class="stat-icon">💸</div>
              </div>
              <div class="stat-card gold">
                <div class="stat-label">صافي الربح</div>
                <div class="stat-value" id="ai-net-profit">0</div>
                <div class="stat-sub">ريال</div>
                <div class="stat-icon">📈</div>
              </div>
            </div>

            <!-- AI Insights -->
            <div class="card" style="margin-bottom:24px">
              <div class="card-header"><div class="card-title">🧠 رؤى ذكية من Claude AI</div></div>
              <div class="card-body">
                <div id="ai-insights" style="font-size:14px;line-height:1.8;color:#333"></div>
              </div>
            </div>

            <!-- Categorization Results -->
            <div class="card" style="margin-bottom:24px">
              <div class="card-header"><div class="card-title">🏷️ التصنيف الذكي</div></div>
              <div class="card-body">
                <div id="ai-categories"></div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div style="display:flex;gap:12px;flex-wrap:wrap">
              <button class="btn btn-primary" onclick="syncAIDataToDB()">✅ مزامنة البيانات مع النظام</button>
              <button class="btn btn-accent" onclick="downloadAIReport()">📥 تحميل التقرير</button>
              <button class="btn btn-outline" onclick="resetAIAnalysis()">🔄 تحليل ملف جديد</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="page" id="page-reports">
      <div class="tabs">
        <div class="tab active" onclick="switchTab('tab-financial',this)">📊 مالي</div>
        <div class="tab" onclick="switchTab('tab-members-report',this)">👥 الأعضاء</div>
        <div class="tab" onclick="switchTab('tab-committees-report',this)">🏛️ اللجان</div>
      </div>
      <div class="tab-content active" id="tab-financial">
        <div class="grid-2">
          <div class="card"><div class="card-header"><div class="card-title">توزيع المصاريف</div><button class="btn btn-outline btn-sm" onclick="window.print()">🖨️ طباعة</button></div><div class="card-body" id="report-donut"></div></div>
          <div class="card"><div class="card-header"><div class="card-title">مقارنة الإيرادات والمصاريف</div></div><div class="card-body" id="report-compare"></div></div>
        </div>
      </div>
      <div class="tab-content" id="tab-members-report">
        <div class="grid-2">
          <div class="card"><div class="card-header"><div class="card-title">ملخص الأعضاء</div></div><div class="card-body" id="report-members"></div></div>
          <div class="card"><div class="card-header"><div class="card-title">أعلى المدفوعين</div></div><div id="report-top-payers"></div></div>
        </div>
      </div>
      <div class="tab-content" id="tab-committees-report">
        <div class="card"><div class="card-header"><div class="card-title">ملخص اللجان</div></div><div class="table-wrap"><table><thead><tr><th>اللجنة</th><th>عدد الأعضاء</th><th>الفعاليات</th><th>المصاريف</th></tr></thead><tbody id="report-committees-tbody"></tbody></table></div></div>
      </div>
    </div>

    <!-- WEBSITE SETTINGS -->
    <div class="page" id="page-websettings">
      <div class="tabs">
        <div class="tab active" onclick="switchWSTab('ws-header',this)">📌 الهيدر</div>
        <div class="tab" onclick="switchWSTab('ws-hero',this)">🎯 البانر الرئيسي</div>
        <div class="tab" onclick="switchWSTab('ws-stats',this)">📊 الإحصائيات</div>
        <div class="tab" onclick="switchWSTab('ws-positions',this)">👑 المناصب</div>
        <div class="tab" onclick="switchWSTab('ws-committees',this)">🏛️ اللجان</div>
        <div class="tab" onclick="switchWSTab('ws-about',this)">📖 عن المجلس</div>
        <div class="tab" onclick="switchWSTab('ws-values',this)">💎 القيم</div>
        <div class="tab" onclick="switchWSTab('ws-media',this)">📷 الميديا</div>
        <div class="tab" onclick="switchWSTab('ws-logo',this)">🎨 الشعار</div>
        <div class="tab" onclick="switchWSTab('ws-contact',this)">📞 التواصل</div>
      </div>

      <!-- HEADER -->
      <div class="tab-content active" id="ws-header">
        <div class="card">
          <div class="card-header"><div class="card-title">📌 إعدادات الهيدر</div></div>
          <div class="card-body">
            <div class="form-group"><label class="form-label">العنوان الرئيسي</label><input class="form-control" id="ws-header-title"></div>
            <div class="form-group"><label class="form-label">العنوان الفرعي</label><input class="form-control" id="ws-header-subtitle"></div>
            <button class="btn btn-primary" onclick="saveHeaderSettings()">💾 حفظ</button>
          </div>
        </div>
      </div>

      <!-- HERO -->
      <div class="tab-content" id="ws-hero">
        <div class="card">
          <div class="card-header"><div class="card-title">🎯 البانر الرئيسي (Hero Section)</div></div>
          <div class="card-body">
            <div class="form-group"><label class="form-label">العنوان</label><input class="form-control" id="ws-hero-title"></div>
            <div class="form-group"><label class="form-label">الوصف</label><textarea class="form-control" id="ws-hero-desc" rows="4"></textarea></div>
            <button class="btn btn-primary" onclick="saveHeroSettings()">💾 حفظ</button>
          </div>
        </div>
      </div>

      <!-- STATS -->
      <div class="tab-content" id="ws-stats">
        <div class="card">
          <div class="card-header"><div class="card-title">📊 الإحصائيات</div></div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-group"><label class="form-label">عدد السنوات</label><input class="form-control" id="ws-stats-years" type="number"></div>
              <div class="form-group"><label class="form-label">عدد اللجان</label><input class="form-control" id="ws-stats-committees" type="number"></div>
              <div class="form-group"><label class="form-label">عدد الأعضاء</label><input class="form-control" id="ws-stats-members" placeholder="مثال: 100+"></div>
            </div>
            <button class="btn btn-primary" onclick="saveStatsSettings()">💾 حفظ</button>
          </div>
        </div>
      </div>

      <!-- POSITIONS -->
      <div class="tab-content" id="ws-positions">
        <div class="card">
          <div class="card-header">
            <div class="card-title">👑 مناصب المجلس</div>
            <button class="btn btn-primary btn-sm" onclick="openAddPosition()">+ إضافة منصب</button>
          </div>
          <div class="card-body" id="positions-list"></div>
        </div>
      </div>

      <!-- COMMITTEES -->
      <div class="tab-content" id="ws-committees">
        <div class="card">
          <div class="card-header">
            <div class="card-title">🏛️ اللجان (تظهر في الموقع العام)</div>
          </div>
          <div class="card-body">
            <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:12px;margin-bottom:14px">
              <div style="font-size:12px;color:#854d0e">💡 يمكنك تعديل وصف وأيقونة كل لجنة. اللجان الأساسية من النظام الداخلي.</div>
            </div>
            <div id="committees-website-list"></div>
          </div>
        </div>
      </div>

      <!-- ABOUT -->
      <div class="tab-content" id="ws-about">
        <div class="card">
          <div class="card-header"><div class="card-title">📖 عن المجلس (الرسالة والرؤية)</div></div>
          <div class="card-body">
            <div class="form-group"><label class="form-label">الرسالة</label><textarea class="form-control" id="ws-about-mission" rows="4"></textarea></div>
            <div class="form-group"><label class="form-label">الرؤية</label><textarea class="form-control" id="ws-about-vision" rows="4"></textarea></div>
            <button class="btn btn-primary" onclick="saveAboutSettings()">💾 حفظ</button>
          </div>
        </div>
      </div>

      <!-- VALUES -->
      <div class="tab-content" id="ws-values">
        <div class="card">
          <div class="card-header">
            <div class="card-title">💎 القيم</div>
            <button class="btn btn-primary btn-sm" onclick="openAddValue()">+ إضافة قيمة</button>
          </div>
          <div class="card-body" id="values-list"></div>
        </div>
      </div>

      <!-- MEDIA -->
      <div class="tab-content" id="ws-media">
        <div class="card">
          <div class="card-header">
            <div class="card-title">📷 إدارة الميديا</div>
            <button class="btn btn-primary btn-sm" onclick="openAddMedia()">+ إضافة ميديا</button>
          </div>
          <div class="card-body">
            <div id="media-list-admin"></div>
          </div>
        </div>
      </div>

      <!-- LOGO -->
      <div class="tab-content" id="ws-logo">
        <div class="card">
          <div class="card-header"><div class="card-title">🎨 الشعار</div></div>
          <div class="card-body">
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px;margin-bottom:16px">
              <div style="font-size:12px;color:#166534;font-weight:600;margin-bottom:4px">✅ الشعار الحالي:</div>
              <div style="display:flex;align-items:center;gap:12px;margin-top:10px">
                <div style="width:60px;height:60px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;border:2px solid var(--border)">
                  <div id="current-logo-preview">
                    <svg width="44" height="44" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M55 12 C58 8, 65 10, 64 18 C63 26, 54 30, 50 38 C46 46, 48 56, 42 62 C36 68, 26 66, 24 58 C22 50, 30 44, 32 36" stroke="#47915C" stroke-width="6" stroke-linecap="round" fill="none"/>
                      <path d="M32 36 C28 44, 20 46, 20 54 C20 62, 28 66, 34 62" stroke="#47915C" stroke-width="5" stroke-linecap="round" fill="none"/>
                      <circle cx="34" cy="62" r="5" fill="#47915C"/>
                    </svg>
                  </div>
                </div>
                <div style="font-size:11px;color:#166534">الشعار الافتراضي (SVG)</div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">رفع شعار جديد (صورة)</label>
              <input type="file" class="form-control" id="logo-upload" accept="image/*" style="padding:8px">
              <div style="font-size:11px;color:var(--text-muted);margin-top:4px">📸 يُفضل PNG بخلفية شفافة، الحجم الأمثل: 200x200 بكسل</div>
            </div>
            <div id="logo-preview-container" style="margin-top:12px;display:none">
              <label class="form-label">معاينة الشعار الجديد:</label>
              <div style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--bg);border-radius:10px">
                <img id="logo-preview-img" style="max-width:80px;max-height:80px;border-radius:8px">
                <button class="btn btn-danger btn-sm" onclick="clearLogo()">🗑️ إلغاء</button>
              </div>
            </div>
            <div style="margin-top:12px">
              <button class="btn btn-primary" onclick="saveLogo()">💾 حفظ الشعار</button>
              <button class="btn btn-outline" onclick="resetLogoToDefault()">🔄 استعادة الشعار الافتراضي</button>
            </div>
          </div>
        </div>
      </div>

      <!-- CONTACT -->
      <div class="tab-content" id="ws-contact">
        <div class="card">
          <div class="card-header"><div class="card-title">📞 معلومات التواصل</div></div>
          <div class="card-body">
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px;margin-bottom:16px;font-size:12px;color:#166534">
              💡 سيظهر زر واتساب في الفوتر وفي البانر الرئيسي عند إضافة الرقم.
            </div>
            <div class="form-group">
              <label class="form-label">رقم الواتساب</label>
              <input class="form-control" id="ws-contact-whatsapp" type="tel" placeholder="مثال: 966501234567" style="direction:ltr;text-align:left">
              <div style="font-size:11px;color:var(--text-muted);margin-top:4px">أدخل الرقم مع رمز الدولة بدون + (مثال: 966501234567)</div>
            </div>
            <button class="btn btn-primary" onclick="saveContactSettings()">💾 حفظ</button>
          </div>
        </div>
      </div>
    </div>

    <!-- SETTINGS -->
    <div class="page" id="page-settings">
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><div class="card-title">⏰ إدارة الجلسة القادمة</div></div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">تاريخ الجلسة</label>
              <input class="form-control" type="date" id="meeting-date" value="">
            </div>
            <div class="form-group">
              <label class="form-label">الوقت</label>
              <input class="form-control" type="time" id="meeting-time" value="10:00">
            </div>
            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label">عنوان الجلسة</label>
              <input class="form-control" id="meeting-title" placeholder="الجلسة العمومية للمجلس" value="الجلسة العمومية للمجلس">
            </div>
          </div>
          <div style="display:flex;gap:8px;margin-top:12px">
            <button class="btn btn-primary" onclick="saveMeeting()">💾 حفظ الجلسة</button>
            <button class="btn btn-outline" onclick="hideMeeting()">👁️‍🗨️ إخفاء العد التنازلي</button>
            <button class="btn btn-danger" onclick="clearMeeting()">🗑️ حذف الجلسة</button>
          </div>
          <div id="meeting-preview" style="margin-top:12px;padding:12px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;display:none">
            <div style="font-size:12px;font-weight:600;color:#166534;margin-bottom:4px">✅ معاينة:</div>
            <div id="meeting-preview-text" style="font-size:13px;color:#166534"></div>
          </div>
        </div>
      </div>

      <div class="grid-2">
        <div class="card">
          <div class="card-header"><div class="card-title">💾 النسخ الاحتياطي والاستيراد</div></div>
          <div class="card-body">
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px;margin-bottom:16px">
              <div style="font-size:12px;color:#166534;font-weight:600;margin-bottom:4px">✅ النسخ الاحتياطي التلقائي مفعّل</div>
              <div style="font-size:11px;color:#166534">يتم حفظ نسخة احتياطية يومياً تلقائياً (آخر 7 أيام)</div>
            </div>

            <div class="form-group">
              <label class="form-label">تصدير البيانات</label>
              <button class="btn btn-primary" style="width:100%" onclick="exportData()">📥 تنزيل ملف البيانات (JSON)</button>
              <div style="font-size:11px;color:var(--text-muted);margin-top:6px">احفظ جميع البيانات في ملف واحد</div>
            </div>

            <div class="form-group">
              <label class="form-label">استيراد البيانات</label>
              <input type="file" class="form-control" id="import-file" accept=".json" style="padding:8px">
              <div style="font-size:11px;color:var(--text-muted);margin-top:6px">⚠️ سيتم استبدال البيانات الحالية</div>
            </div>

            <div class="form-group">
              <label class="form-label">النسخ الاحتياطية التلقائية</label>
              <button class="btn btn-accent" style="width:100%" onclick="showBackups()">📂 عرض النسخ الاحتياطية</button>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">📊 معلومات النظام</div></div>
          <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
              <div style="background:var(--bg);padding:12px;border-radius:10px;text-align:center">
                <div style="font-size:20px;font-weight:900;color:var(--green)" id="stats-members">0</div>
                <div style="font-size:11px;color:var(--text-muted)">عضو</div>
              </div>
              <div style="background:var(--bg);padding:12px;border-radius:10px;text-align:center">
                <div style="font-size:20px;font-weight:900;color:var(--green)" id="stats-events">0</div>
                <div style="font-size:11px;color:var(--text-muted)">فعالية</div>
              </div>
              <div style="background:var(--bg);padding:12px;border-radius:10px;text-align:center">
                <div style="font-size:20px;font-weight:900;color:var(--green)" id="stats-tx">0</div>
                <div style="font-size:11px;color:var(--text-muted)">معاملة مالية</div>
              </div>
              <div style="background:var(--bg);padding:12px;border-radius:10px;text-align:center">
                <div style="font-size:20px;font-weight:900;color:var(--green)" id="stats-size">0</div>
                <div style="font-size:11px;color:var(--text-muted)">KB حجم</div>
              </div>
            </div>

            <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:14px;margin-bottom:16px">
              <div style="font-size:12px;color:#854d0e;font-weight:600;margin-bottom:4px">💡 نصيحة</div>
              <div style="font-size:11px;color:#854d0e">احفظ نسخة احتياطية بشكل دوري، خاصة قبل التحديثات الكبيرة</div>
            </div>

            <div class="form-group">
              <label class="form-label">الانتقال إلى Firebase</label>
              <button class="btn btn-outline" style="width:100%" onclick="alert('اتبع دليل Firebase للانتقال إلى قاعدة بيانات حقيقية')">🔥 دليل الانتقال</button>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px">
        <div class="card-header"><div class="card-title">⚠️ منطقة الخطر</div></div>
        <div class="card-body">
          <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:14px;margin-bottom:12px">
            <div style="font-size:12px;color:#991b1b;font-weight:600">تحذير: لا يمكن التراجع عن هذا الإجراء</div>
          </div>
          <button class="btn btn-danger" onclick="clearAllData()">🗑️ مسح جميع البيانات</button>
        </div>
      </div>
    </div>

    <!-- AUDIT LOG -->
    <div class="page" id="page-audit">
      <div class="card" style="margin-bottom:16px">
        <div class="card-header">
          <div class="card-title">📋 سجل التدقيق — من غيّر ماذا ومتى</div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-outline btn-sm" onclick="downloadExport('audit')">📥 تصدير CSV</button>
          </div>
        </div>
        <div class="card-body">
          <div class="search-bar" style="flex-wrap:wrap">
            <input class="search-input" id="audit-search" placeholder="بحث في السجل..." oninput="renderAuditDebounced()">
            <select class="form-control" style="width:140px" id="audit-flt-type" onchange="renderAuditLog()">
              <option value="">كل الأنواع</option>
              <option value="عضو">عضو</option>
              <option value="دفعة">دفعة</option>
              <option value="معاملة">معاملة</option>
              <option value="فعالية">فعالية</option>
              <option value="تصويت">تصويت</option>
              <option value="إعدادات">إعدادات</option>
              <option value="فرع">فرع</option>
              <option value="ميديا">ميديا</option>
            </select>
            <select class="form-control" style="width:140px" id="audit-flt-action" onchange="renderAuditLog()">
              <option value="">كل الإجراءات</option>
              <option value="إضافة">إضافة</option>
              <option value="تعديل">تعديل</option>
              <option value="حذف">حذف</option>
              <option value="تصدير">تصدير</option>
              <option value="دخول">دخول</option>
            </select>
            <input class="form-control" style="width:140px" id="audit-from" type="date" onchange="renderAuditLog()" title="من تاريخ">
            <input class="form-control" style="width:140px" id="audit-to" type="date" onchange="renderAuditLog()" title="إلى تاريخ">
          </div>
        </div>
      </div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th style="width:160px">التاريخ</th><th>المستخدم</th><th>الإجراء</th><th>النوع</th><th>الكيان</th><th>التفاصيل</th></tr></thead>
            <tbody id="audit-tbody"></tbody>
          </table>
        </div>
        <div style="padding:12px 18px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:12px;color:var(--text-muted)" id="audit-count">0 سجل</span>
          <div id="audit-pagination" style="display:flex;gap:6px;align-items:center"></div>
        </div>
      </div>
    </div>

    <!-- EXPORT DATA -->
    <div class="page" id="page-export">
      <div class="card" style="margin-bottom:16px">
        <div class="card-header">
          <div class="card-title">📥 مركز تصدير البيانات</div>
        </div>
        <div class="card-body">
          <div style="background:#e0f2fe;border:2px solid #7dd3fc;border-radius:12px;padding:20px;margin-bottom:24px">
            <div style="font-size:16px;font-weight:700;color:#075985;margin-bottom:10px">تصدير بيانات المجلس</div>
            <div style="font-size:13px;color:#075985;line-height:1.6">
              اختر نوع البيانات والتنسيق المطلوب لتصدير تقرير جاهز. ملفات CSV متوافقة مع Excel وGoogle Sheets.
            </div>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px">
        <!-- Members Export -->
        <div class="card">
          <div class="card-body" style="text-align:center;padding:24px">
            <div style="font-size:40px;margin-bottom:10px">👥</div>
            <div style="font-size:16px;font-weight:700;margin-bottom:4px">الأعضاء</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px">قائمة جميع الأعضاء وبياناتهم</div>
            <div style="display:flex;gap:8px;justify-content:center">
              <button class="btn btn-primary btn-sm" onclick="downloadExport('members')">📥 CSV</button>
              <button class="btn btn-accent btn-sm" onclick="exportMembersExcel()">📊 Excel</button>
            </div>
          </div>
        </div>

        <!-- Payments Export -->
        <div class="card">
          <div class="card-body" style="text-align:center;padding:24px">
            <div style="font-size:40px;margin-bottom:10px">💳</div>
            <div style="font-size:16px;font-weight:700;margin-bottom:4px">المدفوعات</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px">سجل جميع المدفوعات والرسوم</div>
            <div style="display:flex;gap:8px;justify-content:center">
              <button class="btn btn-primary btn-sm" onclick="downloadExport('payments')">📥 CSV</button>
              <button class="btn btn-accent btn-sm" onclick="exportFeesExcel()">📊 Excel</button>
            </div>
          </div>
        </div>

        <!-- Transactions Export -->
        <div class="card">
          <div class="card-body" style="text-align:center;padding:24px">
            <div style="font-size:40px;margin-bottom:10px">💰</div>
            <div style="font-size:16px;font-weight:700;margin-bottom:4px">المعاملات المالية</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px">إيرادات ومصاريف المجلس</div>
            <div style="display:flex;gap:8px;justify-content:center;margin-bottom:10px">
              <input class="form-control" style="width:130px;font-size:11px" id="export-tx-from" type="date" title="من تاريخ">
              <input class="form-control" style="width:130px;font-size:11px" id="export-tx-to" type="date" title="إلى تاريخ">
            </div>
            <div style="display:flex;gap:8px;justify-content:center">
              <button class="btn btn-primary btn-sm" onclick="downloadExport('transactions',{from:document.getElementById('export-tx-from').value,to:document.getElementById('export-tx-to').value})">📥 CSV</button>
              <button class="btn btn-accent btn-sm" onclick="exportBudgetExcel()">📊 Excel</button>
            </div>
          </div>
        </div>

        <!-- Events Export -->
        <div class="card">
          <div class="card-body" style="text-align:center;padding:24px">
            <div style="font-size:40px;margin-bottom:10px">🗓️</div>
            <div style="font-size:16px;font-weight:700;margin-bottom:4px">الفعاليات</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px">قائمة جميع الفعاليات والأنشطة</div>
            <div style="display:flex;gap:8px;justify-content:center">
              <button class="btn btn-primary btn-sm" onclick="downloadExport('events')">📥 CSV</button>
            </div>
          </div>
        </div>

        <!-- Audit Log Export -->
        <div class="card">
          <div class="card-body" style="text-align:center;padding:24px">
            <div style="font-size:40px;margin-bottom:10px">📋</div>
            <div style="font-size:16px;font-weight:700;margin-bottom:4px">سجل التدقيق</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px">سجل كل التعديلات والإجراءات</div>
            <div style="display:flex;gap:8px;justify-content:center">
              <button class="btn btn-primary btn-sm" onclick="downloadExport('audit')">📥 CSV</button>
            </div>
          </div>
        </div>

        <!-- Full Backup JSON -->
        <div class="card">
          <div class="card-body" style="text-align:center;padding:24px">
            <div style="font-size:40px;margin-bottom:10px">💾</div>
            <div style="font-size:16px;font-weight:700;margin-bottom:4px">نسخة احتياطية كاملة</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px">جميع البيانات بصيغة JSON</div>
            <div style="display:flex;gap:8px;justify-content:center">
              <button class="btn btn-primary btn-sm" onclick="exportData()">📥 JSON</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- MODALS -->
<?php include __DIR__ . '/pages/modals.php'; ?>

<!-- Modal: News -->
<div class="modal-overlay" id="modal-news">
  <div class="modal" style="max-width:600px">
    <div class="modal-header"><span id="news-modal-title">إضافة خبر جديد</span><button onclick="closeModal('modal-news')">&times;</button></div>
    <div class="modal-body">
      <label class="form-label">العنوان *</label>
      <input class="form-control" id="news-title" placeholder="عنوان الخبر">
      <label class="form-label">المقتطف</label>
      <input class="form-control" id="news-excerpt" placeholder="ملخص قصير للخبر">
      <label class="form-label">المحتوى</label>
      <textarea class="form-control" id="news-content" rows="5" placeholder="نص الخبر كاملاً"></textarea>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div><label class="form-label">التصنيف</label><select class="form-control" id="news-category"><option>عام</option><option>فعاليات</option><option>إعلانات</option><option>اجتماعات</option><option>مالية</option><option>اجتماعية</option></select></div>
        <div><label class="form-label">الحالة</label><select class="form-control" id="news-status"><option value="published">منشور</option><option value="draft">مسودة</option></select></div>
      </div>
      <label class="form-label">صورة (رابط URL)</label>
      <input class="form-control" id="news-image" placeholder="https://example.com/image.jpg">
      <label class="form-label">الكاتب</label>
      <input class="form-control" id="news-author" placeholder="اسم الكاتب">
      <input type="hidden" id="news-edit-id">
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-news')">إلغاء</button>
      <button class="btn btn-primary" onclick="saveNews()">حفظ</button>
    </div>
  </div>
</div>

<!-- Modal: Message Detail -->
<div class="modal-overlay" id="modal-msg-detail">
  <div class="modal" style="max-width:500px">
    <div class="modal-header"><span>تفاصيل الرسالة</span><button onclick="closeModal('modal-msg-detail')">&times;</button></div>
    <div class="modal-body" id="msg-detail-body"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-msg-detail')">إغلاق</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<div id="toast" style="display:none"></div>

<button class="mobile-toggle" aria-label="القائمة">☰</button>

<script>
// تحميل تفضيل الوضع الداكن
(function(){
  var saved = localStorage.getItem('awami_theme');
  if(saved === 'dark') document.documentElement.setAttribute('data-theme','dark');
})();
</script>
<script src="js/admin-app.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="js/admin-import.js"></script>
<script src="admin-overrides.js"></script>
</body>
</html>
