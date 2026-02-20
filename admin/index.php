<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>مجلس عائلة العوامي - نظام الإدارة</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#1B3456;--primary-light:#2d5a85;--green:#47915C;--green-light:#5aad72;--green-dark:#2d6b40;
  --accent:#c8a84b;--accent-light:#e8c96a;--bg:#f4f7f4;--bg-card:#fff;--text:#1a2a1e;--text-muted:#6b7c6e;
  --border:#d4ddd6;--danger:#c0392b;--success:#27ae60;--warning:#e67e22;--info:#2980b9;
  --shadow:0 4px 24px rgba(71,145,92,.10);--shadow-lg:0 8px 40px rgba(71,145,92,.16);
  --radius:16px;--radius-sm:10px;
}
  <script src="admin-db.js"></script>
<script src="admin-overrides.js"></script>

*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Cairo',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;}

/* SIDEBAR */
.sidebar{position:fixed;right:0;top:0;bottom:0;width:270px;background:linear-gradient(160deg,var(--green-dark) 0%,#1a3d28 60%,var(--primary) 100%);display:flex;flex-direction:column;z-index:100;box-shadow:-4px 0 30px rgba(0,0,0,.18);}
.sidebar-header{padding:20px 16px 16px;border-bottom:1px solid rgba(255,255,255,.1);}
.logo-wrap{display:flex;align-items:center;gap:10px;}
.logo-img{width:48px;height:48px;background:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;box-shadow:0 2px 10px rgba(0,0,0,.2);}
.logo-svg{width:36px;height:36px;}
.logo-text h2{color:#fff;font-size:14px;font-weight:700;line-height:1.3;}
.logo-text span{color:rgba(255,255,255,.45);font-size:10px;}
.nav{flex:1;padding:10px 0;overflow-y:auto;}
.nav-section{padding:8px 16px 3px;font-size:10px;color:rgba(255,255,255,.3);letter-spacing:1.5px;text-transform:uppercase;font-weight:700;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 16px;margin:2px 8px;border-radius:10px;color:rgba(255,255,255,.65);cursor:pointer;transition:all .2s;font-size:13px;font-weight:500;border:1px solid transparent;}
.nav-item:hover{background:rgba(255,255,255,.08);color:#fff;}
.nav-item.active{background:linear-gradient(135deg,rgba(71,145,92,.35),rgba(71,145,92,.15));color:#a8e6bb;border-color:rgba(71,145,92,.3);}
.nav-item .icon{width:20px;text-align:center;font-size:15px;}
.sidebar-footer{padding:12px;border-top:1px solid rgba(255,255,255,.08);}
.stats-mini{background:rgba(255,255,255,.05);border-radius:10px;padding:10px;display:flex;justify-content:space-around;}
.stat-mini-val{color:#a8e6bb;font-size:15px;font-weight:700;text-align:center;}
.stat-mini-lbl{color:rgba(255,255,255,.4);font-size:10px;text-align:center;}

/* MAIN */
.main{margin-right:270px;min-height:100vh;display:flex;flex-direction:column;}
.topbar{background:var(--bg-card);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:50;box-shadow:0 2px 12px rgba(71,145,92,.08);}
.page-title{font-size:17px;font-weight:700;color:var(--green-dark);}
.page-title span{color:var(--text-muted);font-weight:400;font-size:12px;margin-right:8px;}
.topbar-actions{display:flex;gap:8px;align-items:center;}
.btn{padding:8px 16px;border-radius:10px;border:none;font-family:'Cairo',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:6px;}
.btn-primary{background:var(--green);color:#fff;}
.btn-primary:hover{background:var(--green-dark);transform:translateY(-1px);box-shadow:0 4px 12px rgba(71,145,92,.3);}
.btn-accent{background:linear-gradient(135deg,var(--accent),var(--accent-light));color:var(--primary);}
.btn-accent:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(200,168,75,.35);}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text-muted);}
.btn-outline:hover{border-color:var(--green);color:var(--green);}
.btn-danger{background:#fef2f2;color:var(--danger);border:1.5px solid #fca5a5;}
.btn-danger:hover{background:var(--danger);color:#fff;}
.btn-success{background:#dcfce7;color:#166534;border:1.5px solid #86efac;}
.btn-whatsapp{background:#25D366;color:#fff;}
.btn-whatsapp:hover{background:#128C7E;transform:translateY(-1px);}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:8px;}
.btn-xs{padding:3px 9px;font-size:11px;border-radius:6px;}
.content{padding:22px 26px;flex:1;}

/* PAGES */
.page{display:none;animation:fadeIn .3s ease;}
.page.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}

/* AI Upload Area */
#upload-area:hover{background-color:#f8fffe;border-color:var(--green);}
#upload-area:active{transform:scale(0.98);}

/* CARDS */
.card{background:var(--bg-card);border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);}
.card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.card-title{font-size:14px;font-weight:700;color:var(--green-dark);}
.card-body{padding:18px;}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;}
.stat-card{background:var(--bg-card);border-radius:var(--radius);padding:18px;border:1px solid var(--border);box-shadow:var(--shadow);position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s;}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg);}
.stat-card::before{content:'';position:absolute;top:0;right:0;width:100%;height:3px;border-radius:var(--radius) var(--radius) 0 0;}
.stat-card.green::before{background:linear-gradient(90deg,var(--green),var(--green-light));}
.stat-card.gold::before{background:linear-gradient(90deg,var(--accent),var(--accent-light));}
.stat-card.blue::before{background:linear-gradient(90deg,var(--primary),var(--primary-light));}
.stat-card.orange::before{background:linear-gradient(90deg,var(--warning),#f39c12);}
.stat-label{font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.stat-value{font-size:24px;font-weight:900;color:var(--green-dark);margin:5px 0 3px;font-family:'Tajawal',sans-serif;}
.stat-sub{font-size:11px;color:var(--text-muted);}
.stat-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);font-size:30px;opacity:.1;}

/* TABLE */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{background:#f0f5f1;padding:11px 14px;text-align:right;font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:.5px;border-bottom:2px solid var(--border);}
td{padding:12px 14px;border-bottom:1px solid #f0f5f1;font-size:13px;color:var(--text);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8fbf8;}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-success{background:#dcfce7;color:#166534;}
.badge-warning{background:#fef9c3;color:#854d0e;}
.badge-danger{background:#fee2e2;color:#991b1b;}
.badge-info{background:#dbeafe;color:#1e40af;}
.badge-gray{background:#f1f5f9;color:#475569;}
.badge-gold{background:#fef3c7;color:#92400e;}
.badge-green{background:#dcfce7;color:#166534;}
.badge-purple{background:#f3e8ff;color:#7e22ce;}
.badge-dark{background:#1B3456;color:#fff;}

/* GRID */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;display:none;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg-card);border-radius:var(--radius);width:100%;max-width:540px;box-shadow:0 20px 60px rgba(0,0,0,.2);animation:slideUp .3s ease;max-height:90vh;overflow-y:auto;}
.modal-wide{max-width:720px;}
@keyframes slideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#f8fbf8,#fff);}
.modal-title{font-size:15px;font-weight:700;color:var(--green-dark);}
.modal-close{background:none;border:none;font-size:18px;cursor:pointer;color:var(--text-muted);width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.modal-close:hover{background:#f1f5f9;}
.modal-body{padding:20px;}
.modal-footer{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;}

/* FORMS */
.form-group{margin-bottom:14px;}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text);margin-bottom:5px;}
.form-control{width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-family:'Cairo',sans-serif;font-size:13px;color:var(--text);background:var(--bg-card);transition:border-color .2s;outline:none;}
.form-control:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(71,145,92,.1);}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}

/* SEARCH */
.search-bar{display:flex;gap:10px;align-items:center;padding:12px 18px;background:var(--bg-card);border-bottom:1px solid var(--border);}
.search-input{flex:1;padding:8px 13px 8px 34px;border:1.5px solid var(--border);border-radius:10px;font-family:'Cairo',sans-serif;font-size:13px;background:var(--bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='%236b7c6e' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 11px center;outline:none;}
.search-input:focus{border-color:var(--green);background-color:var(--bg-card);}

/* PROGRESS */
.progress-bar{height:7px;background:#e4ede6;border-radius:100px;overflow:hidden;}
.progress-fill{height:100%;border-radius:100px;background:linear-gradient(90deg,var(--green),var(--green-light));transition:width .5s ease;}
.progress-fill.gold{background:linear-gradient(90deg,var(--accent),var(--accent-light));}

/* AVATAR */
.avatar{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff;flex-shrink:0;}

/* COMMITTEE CARDS */
.committee-card{background:var(--bg-card);border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;transition:transform .2s,box-shadow .2s;cursor:pointer;}
.committee-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg);}
.committee-banner{height:88px;display:flex;align-items:center;justify-content:center;font-size:36px;}
.committee-body{padding:12px 14px;}
.committee-title{font-size:13px;font-weight:700;margin-bottom:3px;color:var(--green-dark);}
.committee-meta{font-size:11px;color:var(--text-muted);}
.committee-footer{padding:9px 14px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}

/* POSITION CARDS */
.position-card{background:var(--bg-card);border-radius:var(--radius);border:2px solid var(--border);padding:18px;transition:all .2s;}
.position-card:hover{border-color:var(--green);box-shadow:var(--shadow);}
.position-card.president{border-color:var(--accent);background:linear-gradient(135deg,#fffbf0,#fff);}
.position-card.advisory{border-color:var(--primary);background:linear-gradient(135deg,#f0f5ff,#fff);}
.position-title{font-size:14px;font-weight:700;color:var(--green-dark);margin-bottom:4px;}
.position-name{font-size:13px;color:var(--primary);font-weight:600;}

/* TABS */
.tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:18px;}
.tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s;}
.tab:hover{color:var(--green);}
.tab.active{color:var(--green);border-bottom-color:var(--green);}
.tab-content{display:none;}
.tab-content.active{display:block;}

/* EMPTY */
.empty-state{text-align:center;padding:36px;}
.empty-state .empty-icon{font-size:40px;margin-bottom:10px;opacity:.4;}
.empty-state p{color:var(--text-muted);font-size:13px;}

/* SCROLLBAR */
::-webkit-scrollbar{width:5px;height:5px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:10px;}

/* TOAST */
#toast{position:fixed;bottom:22px;left:22px;z-index:999;display:none;align-items:center;gap:8px;}

/* HERO BANNER for Council page */
.council-hero{background:linear-gradient(135deg,var(--green-dark),var(--primary));border-radius:var(--radius);padding:26px;color:#fff;margin-bottom:18px;position:relative;overflow:hidden;}
.council-hero::after{content:'١٩٩٢';position:absolute;left:-10px;top:50%;transform:translateY(-50%);font-size:80px;opacity:.07;font-weight:900;font-family:'Tajawal',sans-serif;}

/* MOBILE RESPONSIVE */
@media(max-width:1024px){
  .sidebar{width:240px;}
  .main{margin-right:240px;}
  .stats-grid{grid-template-columns:repeat(2,1fr);}
  .committees-grid{grid-template-columns:repeat(2,1fr);}
}

@media(max-width:768px){
  .sidebar{transform:translateX(100%);transition:transform .3s;position:fixed;width:280px;}
  .sidebar.open{transform:translateX(0);}
  .main{margin-right:0;}
  
  .topbar{padding:12px 16px;}
  .page-title{font-size:15px;}
  .topbar-actions{display:none;}
  
  .content{padding:16px;}
  .stats-grid{grid-template-columns:1fr;gap:10px;}
  .grid-2,.grid-3{grid-template-columns:1fr;}
  .committees-grid{grid-template-columns:1fr;}
  .positions-grid{grid-template-columns:1fr!important;}
  #events-grid{grid-template-columns:1fr!important;}
  
  .card{border-radius:12px;}
  .card-header{padding:12px 16px;flex-direction:column;align-items:flex-start;gap:8px;}
  .card-body{padding:14px;}
  
  .modal{max-width:100%;margin:0;border-radius:16px 16px 0 0;}
  .modal-wide{max-width:100%;}
  
  .form-grid{grid-template-columns:1fr;}
  
  table{font-size:12px;}
  th,td{padding:8px 6px;}
  
  .search-bar{flex-direction:column;align-items:stretch;}
  .search-input{width:100%;}
  
  .tabs{overflow-x:auto;white-space:nowrap;}
  .tab{font-size:12px;padding:8px 14px;}
  
  .council-hero{padding:18px;font-size:13px;}
  .council-hero::after{font-size:50px;}
}

@media(max-width:480px){
  .page-title{font-size:14px;}
  .page-title span{display:block;margin-top:2px;}
  .btn{padding:6px 12px;font-size:12px;}
  .btn-sm{padding:4px 10px;font-size:11px;}
  .stat-value{font-size:20px;}
  .stat-label{font-size:10px;}
  .committee-banner{height:70px;font-size:28px;}
}

/* Mobile Menu Toggle */
.mobile-toggle{display:none;position:fixed;bottom:20px;right:20px;width:56px;height:56px;background:var(--green);border-radius:50%;border:none;color:#fff;font-size:24px;cursor:pointer;box-shadow:0 4px 20px rgba(71,145,92,.4);z-index:101;}
@media(max-width:768px){
  .mobile-toggle{display:flex;align-items:center;justify-content:center;}
}

@media print{.sidebar,.topbar,.btn,.modal-overlay,.mobile-toggle{display:none!important;}.main{margin-right:0!important;}}
</style>
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
    <div class="nav-section">التنظيم</div>
    <div class="nav-item" onclick="showPage('committees',this)"><span class="icon">🏛️</span>اللجان</div>
    <div class="nav-item" onclick="showPage('orgchart',this)"><span class="icon">🗂️</span>الهيكل التنظيمي</div>
    <div class="nav-section">المالية</div>
    <div class="nav-item" onclick="showPage('budget',this)"><span class="icon">💰</span>الميزانية والمصاريف</div>
    <div class="nav-section">الأنشطة</div>
    <div class="nav-item" onclick="showPage('events',this)"><span class="icon">🗓️</span>الفعاليات</div>
    <div class="nav-item" onclick="showPage('calendar',this)"><span class="icon">📅</span>التقويم</div>
    <div class="nav-item" onclick="showPage('voting',this)"><span class="icon">🗳️</span>التصويت</div>
    <div class="nav-section">العائلة</div>
    <div class="nav-item" onclick="showPage('familytree',this)"><span class="icon">🌳</span>شجرة العائلة</div>
    <div class="nav-section">التقارير</div>
    <div class="nav-item" onclick="showPage('smart-reports',this)"><span class="icon">🤖</span>التقارير الذكية</div>
    <div class="nav-item" onclick="showPage('portal',this)"><span class="icon">👤</span>بوابة العضو</div>
    <div class="nav-item" onclick="showPage('reports',this)"><span class="icon">📈</span>التقارير</div>
    <div class="nav-section">الإعدادات</div>
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
    <div class="topbar-actions" id="topbar-action"></div>
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
          <input class="search-input" id="m-search" placeholder="بحث بالاسم أو الجوال..." oninput="renderMembers()">
          <select class="form-control" style="width:130px" id="m-flt-status" onchange="renderMembers()"><option value="">كل الحالات</option><option>نشط</option><option>معفي</option><option>غير نشط</option></select>
          <button class="btn btn-outline btn-sm" onclick="exportMembersExcel()">📊 Excel</button>
        </div>
        <div class="table-wrap"><table><thead><tr><th>#</th><th>العضو</th><th>الجوال</th><th>اللجان</th><th>الانضمام</th><th>الحالة</th><th>الدفع</th><th>إجراءات</th></tr></thead><tbody id="members-tbody"></tbody></table></div>
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
          <input class="search-input" id="fees-search" placeholder="بحث..." oninput="renderFees()">
          <select class="form-control" style="width:160px" id="fees-flt" onchange="renderFees()"><option value="">كل الحالات</option><option value="مدفوع">مدفوع ✅</option><option value="لم يدفع">لم يدفع ⏳</option><option value="معفي">معفي 🔖</option></select>
        </div>
        <div class="table-wrap"><table><thead><tr><th>العضو</th><th>المطلوب</th><th>المدفوع</th><th>التاريخ</th><th>الطريقة</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody id="fees-tbody"></tbody></table></div>
      </div>
    </div>

    <!-- COMMITTEES -->
    <div class="page" id="page-committees">
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
            <select class="form-control btn-sm" style="width:130px" id="b-flt" onchange="renderBudget()"><option value="">الكل</option><option value="إيراد">إيرادات</option><option value="مصروف">مصاريف</option></select>
            <button class="btn btn-outline btn-sm" onclick="exportBudgetExcel()">📊 Excel</button>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-tx')">+ معاملة</button>
          </div>
        </div>
        <div class="table-wrap"><table><thead><tr><th>التاريخ</th><th>الوصف</th><th>الفئة</th><th>اللجنة</th><th>النوع</th><th>المبلغ</th><th></th></tr></thead><tbody id="budget-tbody"></tbody></table></div>
      </div>
    </div>

    <!-- EVENTS -->
    <div class="page" id="page-events">
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

    <!-- FAMILY TREE -->
    <div class="page" id="page-familytree">
      <div class="card" style="margin-bottom:16px">
        <div class="card-header">
          <div class="card-title">🌳 إدارة شجرة عائلة العوامي</div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-primary btn-sm" onclick="openAddBranch()">+ إضافة فرع جديد</button>
            <button class="btn btn-outline btn-sm" onclick="exportTree()">📥 تصدير</button>
          </div>
        </div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
            ${State.getFamilyBranches().map(b=>`
              <div style="border:2px solid ${b.color};border-radius:10px;padding:12px;cursor:pointer;transition:all .2s" onclick="editBranch('${b.id}')" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                  <div style="width:30px;height:30px;background:${b.color};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px">🌿</div>
                  <div style="flex:1;font-weight:700;font-size:13px;color:var(--green-dark)">${b.name}</div>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">👤 ${b.head || 'غير محدد'}</div>
                <div style="font-size:11px;color:var(--text-muted)">${(b.members||[]).length} فرد مُدخل</div>
              </div>
            `).join('')}
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="card-header">
          <div class="card-title">👁️ معاينة الشجرة</div>
          <select class="form-control btn-sm" style="width:140px" id="tree-view" onchange="renderFamilyTreePreview()">
            <option value="vertical">عمودي</option>
            <option value="horizontal">أفقي</option>
            <option value="list">قائمة</option>
          </select>
        </div>
        <div class="card-body" id="tree-container" style="overflow:auto;padding:30px"></div>
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

    <!-- REPORTS -->
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

      <!-- LOGO -->
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

  </div>
</main>

<!-- MODALS -->
<div class="modal-overlay" id="modal-member">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="modal-member-title">➕ إضافة عضو</div><button class="modal-close" onclick="closeModal('modal-member')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="mm-id">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">الاسم الكامل *</label><input class="form-control" id="mm-name" placeholder="الاسم"></div>
        <div class="form-group"><label class="form-label">رقم الجوال</label><input class="form-control" id="mm-phone" placeholder="05xxxxxxxx"></div>
        <div class="form-group"><label class="form-label">رقم الهوية</label><input class="form-control" id="mm-idnum"></div>
        <div class="form-group"><label class="form-label">الفرع العائلي</label><input class="form-control" id="mm-family" placeholder="مثال: آل محمد"></div>
        <div class="form-group"><label class="form-label">تاريخ الانضمام</label><input class="form-control" id="mm-join" type="date"></div>
        <div class="form-group"><label class="form-label">الحالة</label><select class="form-control" id="mm-status"><option>نشط</option><option>معفي</option><option>غير نشط</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">ملاحظات</label><textarea class="form-control" id="mm-notes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-member')">إلغاء</button><button class="btn btn-primary" onclick="saveMember()">حفظ</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-pay">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">💳 تسجيل دفعة</div><button class="modal-close" onclick="closeModal('modal-pay')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="pay-mid">
      <div class="form-group"><label class="form-label">العضو</label><input class="form-control" id="pay-mname" disabled></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">المبلغ (ريال)</label><input class="form-control" id="pay-amount" type="number"></div>
        <div class="form-group"><label class="form-label">التاريخ</label><input class="form-control" id="pay-date" type="date"></div>
        <div class="form-group"><label class="form-label">الطريقة</label><select class="form-control" id="pay-method"><option>تحويل بنكي</option><option>نقدي</option><option>STCPay</option></select></div>
        <div class="form-group"><label class="form-label">الحالة</label><select class="form-control" id="pay-status"><option>مدفوع</option><option>لم يدفع</option><option>معفي</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">ملاحظات</label><textarea class="form-control" id="pay-notes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-pay')">إلغاء</button><button class="btn btn-primary" onclick="savePayment()">تسجيل</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-period">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">🗓️ دورة دفع جديدة</div><button class="modal-close" onclick="closeModal('modal-period')">✕</button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">اسم الدورة *</label><input class="form-control" id="pd-name" placeholder="الدورة الأولى 2025"></div>
        <div class="form-group"><label class="form-label">مبلغ الرسوم (ريال) *</label><input class="form-control" id="pd-amount" type="number" placeholder="400"></div>
        <div class="form-group"><label class="form-label">تاريخ البدء</label><input class="form-control" id="pd-start" type="date"></div>
        <div class="form-group"><label class="form-label">تاريخ الانتهاء</label><input class="form-control" id="pd-end" type="date"></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-period')">إلغاء</button><button class="btn btn-primary" onclick="createPeriod()">إنشاء</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-tx">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">💵 إضافة معاملة مالية</div><button class="modal-close" onclick="closeModal('modal-tx')">✕</button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">النوع</label><select class="form-control" id="tx-type"><option>إيراد</option><option>مصروف</option></select></div>
        <div class="form-group"><label class="form-label">المبلغ (ريال) *</label><input class="form-control" id="tx-amount" type="number"></div>
        <div class="form-group"><label class="form-label">التاريخ</label><input class="form-control" id="tx-date" type="date"></div>
        <div class="form-group"><label class="form-label">الفئة</label><select class="form-control" id="tx-cat"><option>رسوم الأعضاء</option><option>رحلة العمرة</option><option>غداء العيد</option><option>رحلة ترفيهية</option><option>مسابقة</option><option>مصاريف إدارية</option><option>استثمار</option><option>عقيقة جماعية</option><option>تبرعات</option><option>أخرى</option></select></div>
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">اللجنة</label><select class="form-control" id="tx-committee"><option value="">عام</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">الوصف *</label><input class="form-control" id="tx-desc" placeholder="وصف المعاملة"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-tx')">إلغاء</button><button class="btn btn-primary" onclick="addTransaction()">إضافة</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-event">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">🎉 إضافة فعالية</div><button class="modal-close" onclick="closeModal('modal-event')">✕</button></div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">اسم الفعالية *</label><input class="form-control" id="ev-name"></div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">اللجنة المنظِّمة</label><select class="form-control" id="ev-committee"><option value="">غير محدد</option></select></div>
        <div class="form-group"><label class="form-label">الحالة</label><select class="form-control" id="ev-status"><option>قادم</option><option>جاري</option><option>مكتمل</option><option>ملغي</option></select></div>
        <div class="form-group"><label class="form-label">التاريخ</label><input class="form-control" id="ev-date" type="date"></div>
        <div class="form-group"><label class="form-label">الميزانية (ريال)</label><input class="form-control" id="ev-budget" type="number"></div>
        <div class="form-group"><label class="form-label">المشاركون</label><input class="form-control" id="ev-participants" type="number"></div>
        <div class="form-group"><label class="form-label">المسؤول</label><input class="form-control" id="ev-lead"></div>
      </div>
      <div class="form-group">
        <label class="form-label">📸 صور الفعالية</label>
        <input type="file" class="form-control" id="ev-images" accept="image/*" multiple style="padding:6px">
        <div id="ev-images-preview" style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap"></div>
      </div>
      <div class="form-group"><label class="form-label">ملاحظات</label><textarea class="form-control" id="ev-notes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-event')">إلغاء</button><button class="btn btn-primary" onclick="addEvent()">إضافة</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-committee-detail">
  <div class="modal modal-wide">
    <div class="modal-header"><div class="modal-title" id="cdetail-title">تفاصيل اللجنة</div><button class="modal-close" onclick="closeModal('modal-committee-detail')">✕</button></div>
    <div class="modal-body" id="cdetail-body"></div>
  </div>
</div>

<div class="modal-overlay" id="modal-whatsapp">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">📱 إشعارات واتساب للمتأخرين</div><button class="modal-close" onclick="closeModal('modal-whatsapp')">✕</button></div>
    <div class="modal-body">
      <div style="background:#f0fdf4;border-radius:10px;padding:12px;margin-bottom:14px;border:1px solid #86efac"><div style="font-size:12px;color:#166534">💡 اضغط على زر "إرسال" لكل عضو لفتح واتساب برسالة جاهزة</div></div>
      <div id="whatsapp-list"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-whatsapp')">إغلاق</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-confirm">
  <div class="modal" style="max-width:360px">
    <div class="modal-header"><div class="modal-title">⚠️ تأكيد</div><button class="modal-close" onclick="closeModal('modal-confirm')">✕</button></div>
    <div class="modal-body"><p style="color:var(--text-muted);font-size:14px" id="confirm-msg"></p></div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-confirm')">إلغاء</button><button class="btn btn-danger" id="confirm-btn">تأكيد</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-add-branch">
  <div class="modal modal-wide">
    <div class="modal-header"><div class="modal-title" id="branch-modal-title">🌳 إضافة فرع عائلي</div><button class="modal-close" onclick="closeModal('modal-add-branch')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="branch-id">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">اسم الفرع *</label><input class="form-control" id="branch-name" placeholder="مثال: آل محمد"></div>
        <div class="form-group"><label class="form-label">رب العائلة / المؤسس</label><input class="form-control" id="branch-head" placeholder="الاسم"></div>
        <div class="form-group"><label class="form-label">اللون</label><input class="form-control" id="branch-color" type="color" value="#47915C"></div>
      </div>
      <div class="form-group">
        <label class="form-label">أفراد الفرع (سطر لكل فرد)</label>
        <textarea class="form-control" id="branch-members" rows="8" placeholder="محمد&#10;علي&#10;أحمد&#10;..."></textarea>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">💡 اكتب كل اسم في سطر منفصل</div>
      </div>
      <div class="form-group"><label class="form-label">ملاحظات</label><textarea class="form-control" id="branch-notes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-add-branch')">إلغاء</button>
      <button class="btn btn-danger" id="branch-delete-btn" onclick="deleteBranchConfirm()" style="display:none">🗑️ حذف</button>
      <button class="btn btn-primary" onclick="saveBranch()">💾 حفظ</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-backups">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">📂 النسخ الاحتياطية التلقائية</div><button class="modal-close" onclick="closeModal('modal-backups')">✕</button></div>
    <div class="modal-body">
      <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px;margin-bottom:14px">
        <div style="font-size:12px;color:#166534">💡 يتم حفظ نسخة احتياطية تلقائياً كل يوم (آخر 7 أيام)</div>
      </div>
      <div id="backup-list"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-backups')">إغلاق</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-import-excel">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">📥 استيراد أعضاء من Excel</div><button class="modal-close" onclick="closeModal('modal-import-excel')">✕</button></div>
    <div class="modal-body">
      <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:14px;margin-bottom:16px">
        <div style="font-size:13px;color:#854d0e;font-weight:600;margin-bottom:6px">📊 الأعمدة المطلوبة في Excel:</div>
        <div style="font-size:12px;color:#854d0e">الاسم • الجوال (اختياري) • رقم الهوية (اختياري) • الحالة (اختياري)</div>
      </div>
      <input type="file" id="excel-import-input" accept=".xlsx,.xls,.csv" style="display:none">
      <button class="btn btn-primary" style="width:100%" onclick="document.getElementById('excel-import-input').click()">📁 اختر ملف Excel</button>
      <div id="import-result" style="margin-top:16px;display:none"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-import-excel')">إغلاق</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-add-media">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">📷 إضافة ميديا</div><button class="modal-close" onclick="closeModal('modal-add-media')">✕</button></div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">العنوان *</label><input class="form-control" id="media-title" required></div>
      <div class="form-group"><label class="form-label">النوع *</label><select class="form-control" id="media-type"><option value="images">📷 صورة</option><option value="videos">🎥 فيديو</option><option value="events">🎉 فعالية</option></select></div>
      <div class="form-group"><label class="form-label">رابط الصورة/الفيديو *</label><input class="form-control" id="media-url" placeholder="https://..."></div>
      <div class="form-group"><label class="form-label">التاريخ</label><input class="form-control" id="media-date" type="date"></div>
      <div class="form-group"><label class="form-label">الوسوم (افصل بفاصلة)</label><input class="form-control" id="media-tags" placeholder="مثال: فعالية، رحلة، اجتماع"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-add-media')">إلغاء</button><button class="btn btn-primary" onclick="saveMedia()">💾 حفظ</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-branch-detail">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="branch-detail-title">تفاصيل الفرع</div><button class="modal-close" onclick="closeModal('modal-branch-detail')">✕</button></div>
    <div class="modal-body" id="branch-detail-body"></div>
    <div class="modal-footer"><button class="btn btn-outline" onclick="closeModal('modal-branch-detail')">إغلاق</button></div>
  </div>
</div>

<div class="modal-overlay" id="modal-position">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="position-modal-title">👑 إضافة منصب</div><button class="modal-close" onclick="closeModal('modal-position')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="position-index">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">المنصب *</label><input class="form-control" id="position-role" placeholder="مثال: الرئيس"></div>
        <div class="form-group"><label class="form-label">الأيقونة</label><input class="form-control" id="position-icon" placeholder="👑"></div>
      </div>
      <div class="form-group"><label class="form-label">الاسم / الأسماء *</label><input class="form-control" id="position-name" placeholder="مثال: منصور علي"></div>
      <div class="form-group"><label class="form-label">المهام (سطر لكل مهمة)</label><textarea class="form-control" id="position-tasks" rows="4" placeholder="الإشراف العام&#10;إدارة الاجتماعات"></textarea></div>
      <div class="form-group"><label class="form-label">النوع</label><select class="form-control" id="position-type"><option value="">عادي</option><option value="president">رئيس</option><option value="advisory">استشاري</option></select></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-position')">إلغاء</button>
      <button class="btn btn-danger" id="position-delete-btn" onclick="deletePosition()" style="display:none">🗑️ حذف</button>
      <button class="btn btn-primary" onclick="savePosition()">💾 حفظ</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-value">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="value-modal-title">💎 إضافة قيمة</div><button class="modal-close" onclick="closeModal('modal-value')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="value-index">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">الأيقونة *</label><input class="form-control" id="value-icon" placeholder="🤝"></div>
        <div class="form-group"><label class="form-label">العنوان *</label><input class="form-control" id="value-title" placeholder="مثال: الترابط الأسري"></div>
      </div>
      <div class="form-group"><label class="form-label">الوصف</label><textarea class="form-control" id="value-desc" rows="3"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-value')">إلغاء</button>
      <button class="btn btn-danger" id="value-delete-btn" onclick="deleteValue()" style="display:none">🗑️ حذف</button>
      <button class="btn btn-primary" onclick="saveValue()">💾 حفظ</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')" aria-label="القائمة">☰</button>

<script>




// =================== REAL DATA FROM PDF ===================
const COUNCIL_POSITIONS = [
  {role:'الرئيس',name:'منصور علي',type:'president',icon:'👑',tasks:['الإشراف العام على أعمال المجلس','إدارة الاجتماعات والتزام الأعضاء بجدول الأعمال','تمثيل المجلس أمام الجهات الرسمية','اتخاذ القرارات النهائية بعد التشاور']},
  {role:'نائب الرئيس',name:'حسين عبدالحميد - عبدالله عماد',type:'vp',icon:'🤝',tasks:['مساندة الرئيس في جميع مهامه','إدارة المجلس في غياب الرئيس','متابعة تنفيذ القرارات','الإشراف على اللجان الفرعية']},
  {role:'أمين الصندوق',name:'محمود حسن - عبدالله عماد - راضي ابراهيم',type:'treasurer',icon:'💰',tasks:['إدارة الشؤون المالية (إيرادات – مصروفات)','إعداد التقارير المالية الدورية','حفظ سجلات دقيقة وشفافة','متابعة تحصيل الاشتراكات']},
  {role:'المنسق العام',name:'راضي ابراهيم - عبدالله عماد - محمود حسن',type:'coordinator',icon:'📋',tasks:['تنظيم الفعاليات والأنشطة','التواصل مع جميع الأعضاء','وضع جداول زمنية للاجتماعات','متابعة تنفيذ قرارات المجلس']},
  {role:'أمين السر',name:'منصور علي - حسين عبدالحميد',type:'secretary',icon:'📝',tasks:['تدوين محاضر الاجتماعات','متابعة المراسلات الرسمية','إعداد جدول أعمال الاجتماعات','أرشفة جميع القرارات والمكاتبات']},
  {role:'اللجنة الاستشارية',name:'علي العوامي (أبو حيدر) - فخري العوامي (أبو عقيل) - حسين علي سلمان العوامي (أبو علي)',type:'advisory',icon:'🎓',tasks:['دعم وتوجيه عمل اللجان العائلية','وضع رؤية عامة وخطة مستقبلية','مراجعة ومتابعة خطط اللجان','تقديم الدعم الاستشاري في الأمور التنظيمية والمالية']},
];

const COMMITTEES_DATA = [
  {id:'c1',name:'لجنة العمرة الرجبية',icon:'🕋',color:'linear-gradient(135deg,#1a6b3c,#2d9955)',desc:'تنظيم رحلة العمرة السنوية في شهر رجب',members:['عبدالله عماد','عبدالخالق علي','حسين عبدالله','تيسير محمد','أحمد علي السلمان','مهند مكي','مرتضى','محمد حسين']},
  {id:'c2',name:'لجنة غداء العيدين',icon:'🍖',color:'linear-gradient(135deg,#c8a84b,#e8c96a)',desc:'تنظيم وإدارة غداء عيد الفطر وعيد الأضحى',members:['محمود حسن (أبومراد)','معين علي (أبو علي)','محمد راضي (أبو ميرزا)','علي حسين علي السلمان','أحمد غازي']},
  {id:'c3',name:'لجنة المسابقة الرمضانية',icon:'🌙',color:'linear-gradient(135deg,#1a3a6b,#2d5ab9)',desc:'إعداد وتحكيم المسابقات الرمضانية',members:['منصور علي','عماد عبدالحميد','مجتبى سلمان','رضا حسين','أحمد غازي']},
  {id:'c4',name:'لجنة الرحلات',icon:'🎡',color:'linear-gradient(135deg,#2980b9,#5dade2)',desc:'تخطيط وتنفيذ الرحلات الترفيهية للعائلة',members:['محمود حسن (أبومراد)']},
  {id:'c5',name:'لجنة ليلة القدر',icon:'✨',color:'linear-gradient(135deg,#4a235a,#8e44ad)',desc:'إحياء ليلة القدر وتنظيم فعالياتها',members:['عبدالله عماد','علي العوامي (أبو حيدر)','محمد حسن','أحمد علي السلمان','حسن علي (حسنكو)','سجاد علي','محمد حسين']},
  {id:'c6',name:'لجنة تنظيف المساجد',icon:'🕌',color:'linear-gradient(135deg,#117a65,#1abc9c)',desc:'تنسيق حملات تنظيف وصيانة المساجد (العمل التطوعي)',members:['راضي ابراهيم','علي العوامي (أبو محمد)','عماد علي (أبو عبدالله)','عماد عبدالحميد','رضا حسين','باسل حسين']},
  {id:'c7',name:'لجنة مسابقة العيد',icon:'🏆',color:'linear-gradient(135deg,#b7950b,#d4ac0d)',desc:'تنظيم مسابقات وفعاليات العيد',members:['راضي ابراهيم','محمد عبدالله','حسين عبدالله','علي عبدالله','حسن علي (حسنكو)','رضا حسين','مجتبى سلمان','ليلى عبدالله']},
  {id:'c8',name:'لجنة الاستثمار',icon:'📈',color:'linear-gradient(135deg,#1B3456,#2d5a85)',desc:'إدارة واستثمار أموال الصندوق',members:['حسين عبدالحميد','عبدالله عماد','حسين علي سلمان','أحمد عبد الحميد','أحمد عبدالله','سعيد علي راضي']},
  {id:'c9',name:'اللجنة الاستشارية',icon:'🎓',color:'linear-gradient(135deg,#4a235a,#7b2d8b)',desc:'تقديم المشورة والتوجيه لإدارة المجلس',advisory:true,members:['علي العوامي (أبو حيدر)','فخري العوامي (أبو عقيل)','حسين علي سلمان العوامي (أبو علي)']},
  {id:'c10',name:'اللجنة الإعلامية',icon:'📢',color:'linear-gradient(135deg,#c0392b,#e74c3c)',desc:'إدارة المنصات الإعلامية وتوثيق الفعاليات',members:['منصور علي','حسن علي (حسنكو)','مجتبى سلمان','رضا حسين']},
  {id:'c11',name:'لجنة العقيقة الجماعية',icon:'🐑',color:'linear-gradient(135deg,#6b3a1a,#9b5a2d)',desc:'تنظيم مناسبات العقيقة الجماعية للعائلة',members:['حسين عبدالحميد','حسين علي سلمان','محمد مصطفى','علي عماد']},
];

// =================== DB ===================
// ── State initialisation ──────────────────────────────────────────
State.init(
  JSON.parse(localStorage.getItem('awami_db_v4') || 'null'),
  COMMITTEES_DATA,
  COUNCIL_POSITIONS
);
// Bridge: gives old mutation functions access to the private DB object.
// Property-level writes (DB.x = ...) propagate back into State because
// objects are passed by reference. Replaced in Phase 3.
// DB bridge removed — all access via State API

function saveDB(){ 
  localStorage.setItem('awami_db_v4', JSON.stringify(State.getDB())); 
  // Auto-backup every save
  autoBackup();
}

// =================== BACKUP & RESTORE ===================
function autoBackup(){
  const backupKey = 'awami_backup_' + new Date().toISOString().split('T')[0];
  const backups = JSON.parse(localStorage.getItem('awami_backups') || '{}');
  backups[backupKey] = {data: State.getDB(), timestamp: new Date().toISOString()};
  
  // Keep only last 7 days
  const keys = Object.keys(backups).sort().reverse();
  if(keys.length > 7){
    keys.slice(7).forEach(k => delete backups[k]);
  }
  
  localStorage.setItem('awami_backups', JSON.stringify(backups));
}

function exportData(){
  const dataStr = JSON.stringify(State.getDB(), null, 2);
  const dataBlob = new Blob([dataStr], {type: 'application/json'});
  const url = URL.createObjectURL(dataBlob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `awami-data-${new Date().toISOString().split('T')[0]}.json`;
  a.click();
  URL.revokeObjectURL(url);
  toast('تم تصدير البيانات بنجاح 📥');
  log('تصدير البيانات','💾');
}

function importData(file){
  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const imported = JSON.parse(e.target.result);
      
      // Validate data structure
      if(!imported.members || !Array.isArray(imported.members)){
        toast('ملف البيانات غير صحيح','error');
        return;
      }
      
      confirm2('هل تريد استيراد البيانات؟ سيتم استبدال البيانات الحالية!', () => {
        // Backup current data before import
        localStorage.setItem('awami_before_import', JSON.stringify(State.getDB()));
        
        State.replaceDB(imported, COMMITTEES_DATA);
        saveDB();
        toast('تم استيراد البيانات بنجاح ✅');
        log('استيراد البيانات','📤');
        
        // Refresh all views
        const currentPage = document.querySelector('.page.active').id.replace('page-','');
        showPage(currentPage, document.querySelector('.nav-item.active'));
      });
    } catch(err) {
      toast('خطأ في قراءة الملف','error');
      console.error(err);
    }
  };
  reader.readAsText(file);
}

function showBackups(){
  const backups = JSON.parse(localStorage.getItem('awami_backups') || '{}');
  const keys = Object.keys(backups).sort().reverse();
  
  if(!keys.length){
    toast('لا توجد نسخ احتياطية','error');
    return;
  }
  
  const list = keys.map(k => {
    const b = backups[k];
    const date = new Date(b.timestamp).toLocaleString('ar-SA');
    return `<div style="display:flex;align-items:center;justify-content:space-between;padding:10px;border-bottom:1px solid var(--border)">
      <div><div style="font-weight:600;font-size:13px">${k.replace('awami_backup_','')}</div><div style="font-size:11px;color:var(--text-muted)">${date}</div></div>
      <button class="btn btn-primary btn-xs" onclick="restoreBackup('${k}')">استرجاع</button>
    </div>`;
  }).join('');
  
  document.getElementById('backup-list').innerHTML = list;
  openModal('modal-backups');
}

function restoreBackup(key){
  const backups = JSON.parse(localStorage.getItem('awami_backups') || '{}');
  if(!backups[key]){
    toast('النسخة الاحتياطية غير موجودة','error');
    return;
  }
  
  confirm2('هل تريد استرجاع هذه النسخة؟', () => {
    State.replaceDB(backups[key].data, COMMITTEES_DATA);
    saveDB();
    closeModal('modal-backups');
    toast('تم استرجاع النسخة الاحتياطية ✅');
    log('استرجاع نسخة احتياطية','⏮️');
    location.reload();
  });
}

function clearAllData(){
  confirm2('⚠️ هل أنت متأكد؟ سيتم حذف جميع البيانات!\n\nسيتم حفظ نسخة احتياطية قبل الحذف.', () => {
    // Final backup before clear
    localStorage.setItem('awami_before_clear', JSON.stringify(State.getDB()));
    
    State.resetDB(COMMITTEES_DATA);
    saveDB();
    toast('تم مسح جميع البيانات');
    log('مسح جميع البيانات','🗑️');
    location.reload();
  });
}

// =================== UTILS ===================
function uid(){ return Date.now().toString(36)+Math.random().toString(36).slice(2); }
function today(){ return new Date().toISOString().split('T')[0]; }
function fmt(n){ return Number(n||0).toLocaleString('ar-SA'); }
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function log(action,icon='📝'){ State.getActivity().unshift({id:uid(),action,icon,time:new Date().toLocaleString('ar-SA')}); if(State.getActivity().length>40) State.getActivity().pop(); saveDB(); }
function toast(msg,type='success'){
  const t=document.getElementById('toast');
  t.innerHTML=(type==='success'?'✅':'❌')+' '+msg;
  t.style.cssText=`display:flex;align-items:center;gap:8px;background:${type==='success'?'var(--green)':'var(--danger)'};color:#fff;padding:11px 18px;border-radius:10px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.2);`;
  setTimeout(()=>t.style.display='none',3000);
}
function confirm2(msg,cb){ document.getElementById('confirm-msg').textContent=msg; document.getElementById('confirm-btn').onclick=()=>{ cb(); closeModal('modal-confirm'); }; openModal('modal-confirm'); }
const aColors=['#2d6b40','#1B3456','#c8a84b','#b7950b','#8e44ad','#c0392b','#117a65','#2980b9','#6b3a1a'];
function avColor(n){ let h=0; for(let c of n||'') h+=c.charCodeAt(0); return aColors[h%aColors.length]; }
function avInit(n){ let p=(n||'?').trim().split(' '); return p.length>1?p[0][0]+p[p.length-1][0]:p[0].slice(0,2); }
function curPeriod(){ return State.getPeriods()[State.getPeriods().length-1]||null; }
function committeeSelectOptions(){ return State.getCommittees().map(c=>`<option value="${c.id}">${c.name}</option>`).join(''); }
function memberCommittees(mid){ return State.getCommittees().filter(c=>(State.getCommitteeMembers()[c.id]||[]).includes(mid)).map(c=>c.name).join(', ')||'—'; }
function sBadge(s){ return s==='قادم'?'badge-info':s==='جاري'?'badge-success':s==='مكتمل'?'badge-gray':'badge-danger'; }

// =================== SIDEBAR ===================
function showPage(name,el){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('page-'+name).classList.add('active');
  if(el) el.classList.add('active');
  
  // Close mobile menu
  if(window.innerWidth<=768){
    document.querySelector('.sidebar').classList.remove('open');
  }
  
  const T={dashboard:'لوحة التحكم|مجلس عائلة العوامي',council:'مناصب المجلس|الهيئة الإدارية',members:'الأعضاء|إدارة الأعضاء',fees:'الرسوم|متابعة المدفوعات',committees:'اللجان|اللجان الفرعية للمجلس',orgchart:'الهيكل التنظيمي|مجلس عائلة العوامي',budget:'الميزانية|السجل المالي',events:'الفعاليات|الأنشطة',calendar:'التقويم|عرض تقويمي',familytree:'شجرة العائلة|الأفرع العائلية',voting:'التصويت|استطلاعات الرأي',portal:'بوابة العضو|الملف الشخصي','smart-reports':'التقارير الذكية|تحليل مدعوم بالذكاء الاصطناعي',reports:'التقارير|إحصائيات',websettings:'الموقع العام|إدارة المحتوى',settings:'النسخ الاحتياطي|إدارة البيانات'};
  const [t,s]=(T[name]||'--|--').split('|');
  document.getElementById('topbar-title').innerHTML=t+` <span>${s}</span>`;
  const A={members:`<button class="btn btn-primary" onclick="openAddMember()">+ إضافة عضو</button>`,budget:`<button class="btn btn-primary" onclick="openModal('modal-tx')">+ معاملة</button>`,events:`<button class="btn btn-primary" onclick="openAddEvent()">+ فعالية</button>`};
  document.getElementById('topbar-action').innerHTML=A[name]||'';
  const renderers={dashboard:renderDashboard,council:renderCouncil,members:renderMembers,fees:renderFees,committees:renderCommittees,orgchart:renderOrgChart,budget:renderBudget,events:renderEvents,calendar:renderCalendar,familytree:renderFamilyTree,voting:renderVoting,portal:renderPortalSelect,'smart-reports':function(){},reports:renderReports,websettings:renderWebsiteSettings,settings:renderSettings};
  if(renderers[name]) renderers[name]();
  updateSidebar();
}

// =================== WEBSITE SETTINGS ===================
function renderWebsiteSettings(){
  const ws = State.getWebsiteSettings();
  document.getElementById('ws-header-title').value = ws.header.title;
  document.getElementById('ws-header-subtitle').value = ws.header.subtitle;
  document.getElementById('ws-hero-title').value = ws.hero.title;
  document.getElementById('ws-hero-desc').value = ws.hero.description;
  document.getElementById('ws-stats-years').value = ws.stats.years;
  document.getElementById('ws-stats-committees').value = ws.stats.committees;
  document.getElementById('ws-stats-members').value = ws.stats.members;
  
  if(ws.about){
    document.getElementById('ws-about-mission').value = ws.about.mission || '';
    document.getElementById('ws-about-vision').value = ws.about.vision || '';
  }
  
  renderPositionsList();
  renderCommitteesList();
  renderValuesList();
  renderLogoPreview();
  renderMediaList();
}

function renderCommitteesList(){
  const list = document.getElementById('committees-website-list');
  const committees = State.getCommittees() || [];
  list.innerHTML = committees.map(c=>`
    <div style="border:2px solid ${c.color};border-radius:10px;padding:14px;margin-bottom:10px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
        <div style="width:40px;height:40px;background:${c.color};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px">${c.icon}</div>
        <div style="flex:1"><div style="font-weight:700;font-size:14px">${c.name}</div><div style="font-size:11px;color:var(--text-muted)">${c.description}</div></div>
        ${c.type==='advisory'?'<span class="badge badge-purple">استشارية</span>':''}
      </div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:6px">📝 اللجان الأساسية من النظام - للتعديل ارجع لصفحة "اللجان" في لوحة التحكم</div>
    </div>
  `).join('');
}

function saveAboutSettings(){
  if(!State.getWebsiteSettings().about) State.getWebsiteSettings().about = {};
  State.getWebsiteSettings().about = {
    mission: document.getElementById('ws-about-mission').value.trim(),
    vision: document.getElementById('ws-about-vision').value.trim()
  };
  saveDB(); toast('تم حفظ "عن المجلس" ✅'); log('تحديث عن المجلس','📖');
}

function renderLogoPreview(){
  const ws = State.getWebsiteSettings();
  if(ws.logo){
    document.getElementById('current-logo-preview').innerHTML = `<img src="${ws.logo}" style="max-width:44px;max-height:44px">`;
  }
  
  const upload = document.getElementById('logo-upload');
  if(upload && !upload.hasAttribute('data-initialized')){
    upload.setAttribute('data-initialized', 'true');
    upload.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if(file){
        if(file.size > 500*1024){
          toast('حجم الصورة أكبر من 500KB','error');
          return;
        }
        const reader = new FileReader();
        reader.onload = (ev) => {
          document.getElementById('logo-preview-img').src = ev.target.result;
          document.getElementById('logo-preview-container').style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }
}

function saveLogo(){
  const preview = document.getElementById('logo-preview-img').src;
  if(!preview || preview === window.location.href){
    toast('لم يتم اختيار شعار','error');
    return;
  }
  
  State.getWebsiteSettings().logo = preview;
  saveDB();
  toast('تم حفظ الشعار ✅');
  log('تحديث الشعار','🎨');
  document.getElementById('current-logo-preview').innerHTML = `<img src="${preview}" style="max-width:44px;max-height:44px">`;
  document.getElementById('logo-preview-container').style.display = 'none';
  document.getElementById('logo-upload').value = '';
}

function clearLogo(){
  document.getElementById('logo-preview-container').style.display = 'none';
  document.getElementById('logo-upload').value = '';
}

function resetLogoToDefault(){
  confirm2('استعادة الشعار الافتراضي؟',()=>{
    State.getWebsiteSettings().logo = null;
    saveDB();
    toast('تم استعادة الشعار الافتراضي');
    log('استعادة الشعار الافتراضي','🔄');
    document.getElementById('current-logo-preview').innerHTML = `
      <svg width="44" height="44" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M55 12 C58 8, 65 10, 64 18 C63 26, 54 30, 50 38 C46 46, 48 56, 42 62 C36 68, 26 66, 24 58 C22 50, 30 44, 32 36" stroke="#47915C" stroke-width="6" stroke-linecap="round" fill="none"/>
        <path d="M32 36 C28 44, 20 46, 20 54 C20 62, 28 66, 34 62" stroke="#47915C" stroke-width="5" stroke-linecap="round" fill="none"/>
        <circle cx="34" cy="62" r="5" fill="#47915C"/>
      </svg>`;
  });
}

function switchWSTab(id,el){
  document.querySelectorAll('#page-websettings .tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('#page-websettings .tab').forEach(t=>t.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  el.classList.add('active');
}

function saveHeaderSettings(){
  State.getWebsiteSettings().header = {
    title: document.getElementById('ws-header-title').value,
    subtitle: document.getElementById('ws-header-subtitle').value
  };
  saveDB(); toast('تم حفظ إعدادات الهيدر ✅'); log('تحديث الهيدر','📌');
}

function saveHeroSettings(){
  State.getWebsiteSettings().hero = {
    title: document.getElementById('ws-hero-title').value,
    description: document.getElementById('ws-hero-desc').value
  };
  saveDB(); toast('تم حفظ إعدادات البانر ✅'); log('تحديث البانر','🎯');
}

function saveStatsSettings(){
  State.getWebsiteSettings().stats = {
    years: parseInt(document.getElementById('ws-stats-years').value) || 0,
    committees: parseInt(document.getElementById('ws-stats-committees').value) || 0,
    members: document.getElementById('ws-stats-members').value
  };
  saveDB(); toast('تم حفظ الإحصائيات ✅'); log('تحديث الإحصائيات','📊');
}

// Positions
function renderPositionsList(){
  const list = document.getElementById('positions-list');
  const positions = State.getWebsiteSettings().councilPositions || [];
  list.innerHTML = positions.length ? positions.map((p,i)=>`
    <div style="border:2px solid var(--border);border-radius:10px;padding:14px;margin-bottom:10px;cursor:pointer" onclick="editPosition(${i})">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="font-size:24px">${p.icon||'👤'}</div>
        <div style="flex:1"><div style="font-weight:700;font-size:14px">${p.role}</div><div style="font-size:12px;color:var(--text-muted)">${p.name}</div></div>
        <span class="badge ${p.type==='president'?'badge-gold':p.type==='advisory'?'badge-purple':'badge-gray'}">${p.type==='president'?'رئيس':p.type==='advisory'?'استشاري':'عادي'}</span>
      </div>
    </div>
  `).join('') : '<div class="empty-state"><div class="empty-icon">👑</div><p>لا توجد مناصب</p></div>';
}

function openAddPosition(){
  document.getElementById('position-index').value = '';
  document.getElementById('position-role').value = '';
  document.getElementById('position-name').value = '';
  document.getElementById('position-icon').value = '';
  document.getElementById('position-tasks').value = '';
  document.getElementById('position-type').value = '';
  document.getElementById('position-modal-title').textContent = '👑 إضافة منصب جديد';
  document.getElementById('position-delete-btn').style.display = 'none';
  openModal('modal-position');
}

function editPosition(idx){
  const p = State.getWebsiteSettings().councilPositions[idx];
  document.getElementById('position-index').value = idx;
  document.getElementById('position-role').value = p.role;
  document.getElementById('position-name').value = p.name;
  document.getElementById('position-icon').value = p.icon || '';
  document.getElementById('position-tasks').value = (p.tasks||[]).join('\n');
  document.getElementById('position-type').value = p.type || '';
  document.getElementById('position-modal-title').textContent = '✏️ تعديل: ' + p.role;
  document.getElementById('position-delete-btn').style.display = 'inline-flex';
  openModal('modal-position');
}

function savePosition(){
  const role = document.getElementById('position-role').value.trim();
  const name = document.getElementById('position-name').value.trim();
  if(!role || !name){toast('المنصب والاسم مطلوبان','error');return;}
  
  const tasksText = document.getElementById('position-tasks').value.trim();
  const data = {
    role, name,
    icon: document.getElementById('position-icon').value.trim() || '👤',
    type: document.getElementById('position-type').value,
    tasks: tasksText ? tasksText.split('\n').map(t=>t.trim()).filter(Boolean) : []
  };
  
  const idx = document.getElementById('position-index').value;
  if(idx !== ''){
    State.getWebsiteSettings().councilPositions[idx] = data;
    log(`تعديل منصب: ${role}`,'✏️');
  }else{
    if(!State.getWebsiteSettings().councilPositions) State.getWebsiteSettings().councilPositions = [];
    State.getWebsiteSettings().councilPositions.push(data);
    log(`إضافة منصب: ${role}`,'👑');
  }
  
  saveDB(); closeModal('modal-position'); toast(idx!==''?'تم التحديث':'تم الإضافة'); renderPositionsList();
}

function deletePosition(){
  const idx = document.getElementById('position-index').value;
  const p = State.getWebsiteSettings().councilPositions[idx];
  confirm2(`حذف منصب "${p.role}"؟`,()=>{
    State.getWebsiteSettings().councilPositions.splice(idx,1);
    saveDB(); closeModal('modal-position'); toast('تم الحذف'); renderPositionsList(); log(`حذف منصب: ${p.role}`,'🗑️');
  });
}

// Values
function renderValuesList(){
  const list = document.getElementById('values-list');
  const values = State.getWebsiteSettings().values || [];
  list.innerHTML = values.length ? values.map((v,i)=>`
    <div style="border:2px solid var(--border);border-radius:10px;padding:14px;margin-bottom:10px;cursor:pointer" onclick="editValue(${i})">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="font-size:28px">${v.icon}</div>
        <div style="flex:1"><div style="font-weight:700;font-size:14px">${v.title}</div><div style="font-size:12px;color:var(--text-muted)">${v.desc}</div></div>
      </div>
    </div>
  `).join('') : '<div class="empty-state"><div class="empty-icon">💎</div><p>لا توجد قيم</p></div>';
}

function openAddValue(){
  document.getElementById('value-index').value = '';
  document.getElementById('value-icon').value = '';
  document.getElementById('value-title').value = '';
  document.getElementById('value-desc').value = '';
  document.getElementById('value-modal-title').textContent = '💎 إضافة قيمة جديدة';
  document.getElementById('value-delete-btn').style.display = 'none';
  openModal('modal-value');
}

function editValue(idx){
  const v = State.getWebsiteSettings().values[idx];
  document.getElementById('value-index').value = idx;
  document.getElementById('value-icon').value = v.icon;
  document.getElementById('value-title').value = v.title;
  document.getElementById('value-desc').value = v.desc;
  document.getElementById('value-modal-title').textContent = '✏️ تعديل: ' + v.title;
  document.getElementById('value-delete-btn').style.display = 'inline-flex';
  openModal('modal-value');
}

function saveValue(){
  const icon = document.getElementById('value-icon').value.trim();
  const title = document.getElementById('value-title').value.trim();
  if(!icon || !title){toast('الأيقونة والعنوان مطلوبان','error');return;}
  
  const data = {icon, title, desc: document.getElementById('value-desc').value.trim()};
  const idx = document.getElementById('value-index').value;
  
  if(idx !== ''){
    State.getWebsiteSettings().values[idx] = data;
    log(`تعديل قيمة: ${title}`,'✏️');
  }else{
    if(!State.getWebsiteSettings().values) State.getWebsiteSettings().values = [];
    State.getWebsiteSettings().values.push(data);
    log(`إضافة قيمة: ${title}`,'💎');
  }
  
  saveDB(); closeModal('modal-value'); toast(idx!==''?'تم التحديث':'تم الإضافة'); renderValuesList();
}

function deleteValue(){
  const idx = document.getElementById('value-index').value;
  const v = State.getWebsiteSettings().values[idx];
  confirm2(`حذف قيمة "${v.title}"؟`,()=>{
    State.getWebsiteSettings().values.splice(idx,1);
    saveDB(); closeModal('modal-value'); toast('تم الحذف'); renderValuesList(); log(`حذف قيمة: ${v.title}`,'🗑️');
  });
}

// =================== MEDIA MANAGEMENT ===================
function openAddMedia(){
  document.getElementById('media-title').value='';
  document.getElementById('media-type').value='images';
  document.getElementById('media-url').value='';
  document.getElementById('media-date').value=today();
  document.getElementById('media-tags').value='';
  openModal('modal-add-media');
}

function saveMedia(){
  var title=document.getElementById('media-title').value.trim();
  var type=document.getElementById('media-type').value;
  var url=document.getElementById('media-url').value.trim();
  
  if(!title||!url){toast('الرجاء ملء العنوان والرابط','error');return;}
  
  var tags=document.getElementById('media-tags').value.split(',').map(function(t){return t.trim();}).filter(function(t){return t;});
  
  var mediaItem={
    id:uid(),
    title:title,
    type:type,
    url:url,
    date:document.getElementById('media-date').value||today(),
    tags:tags,
    createdAt:new Date().toISOString()
  };
  
  State.ensureMedia();
  State.getMedia().push(mediaItem);
  saveDB();
  log('إضافة ميديا: '+title,'📷');
  toast('تم إضافة الميديا ✅');
  closeModal('modal-add-media');
  renderMediaList();
}

function renderMediaList(){
  var container=document.getElementById('media-list-admin');
  var media=State.getMedia()||[];
  
  if(!media.length){
    container.innerHTML='<div class="empty-state"><div class="empty-icon">📷</div><p>لا توجد وسائط</p></div>';
    return;
  }
  
  var html='<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px">';
  media.forEach(function(item){
    var icon=item.type==='images'?'📷':item.type==='videos'?'🎥':'🎉';
    html+='<div style="border:2px solid var(--border);border-radius:10px;overflow:hidden">';
    if(item.type==='images'){
      html+='<img src="'+item.url+'" style="width:100%;height:150px;object-fit:cover">';
    }else if(item.type==='videos'){
      html+='<video src="'+item.url+'" style="width:100%;height:150px;object-fit:cover"></video>';
    }else{
      html+='<div style="width:100%;height:150px;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:48px">'+icon+'</div>';
    }
    html+='<div style="padding:12px">';
    html+='<div style="font-weight:700;margin-bottom:6px">'+icon+' '+item.title+'</div>';
    html+='<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px">'+new Date(item.date).toLocaleDateString('ar-SA')+'</div>';
    if(item.tags&&item.tags.length){
      item.tags.forEach(function(tag){
        html+='<span class="badge badge-gray" style="margin:2px">'+tag+'</span>';
      });
    }
    html+='<div style="margin-top:10px"><button class="btn btn-danger btn-xs" onclick="deleteMedia(\''+item.id+'\')">حذف</button></div>';
    html+='</div></div>';
  });
  html+='</div>';
  container.innerHTML=html;
}

function deleteMedia(id){
  if(!confirm('حذف هذه الميديا؟'))return;
  State.setMedia(State.getMedia().filter(function(m){return m.id!==id;}));
  saveDB();
  toast('تم الحذف');
  renderMediaList();
}

// =================== SETTINGS ===================
function renderSettings(){
  document.getElementById('stats-members').textContent = State.getMembers().length;
  document.getElementById('stats-events').textContent = State.getEvents().length;
  document.getElementById('stats-tx').textContent = State.getTransactions().length;
  
  const dataSize = new Blob([JSON.stringify(State.getDB())]).size;
  document.getElementById('stats-size').textContent = (dataSize / 1024).toFixed(1);
  
  // Load meeting data
  if(State.getNextMeeting()){
    const datetime = State.getNextMeeting().date.split('T');
    document.getElementById('meeting-date').value = datetime[0];
    document.getElementById('meeting-time').value = datetime[1] ? datetime[1].slice(0,5) : '10:00';
    document.getElementById('meeting-title').value = State.getNextMeeting().title || 'الجلسة العمومية للمجلس';
  }
  
  // Setup import file handler
  const importFile = document.getElementById('import-file');
  if(importFile && !importFile.hasAttribute('data-initialized')){
    importFile.setAttribute('data-initialized', 'true');
    importFile.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if(file) importData(file);
      e.target.value = ''; // Reset input
    });
  }
}

function saveMeeting(){
  const date = document.getElementById('meeting-date').value;
  const time = document.getElementById('meeting-time').value || '10:00';
  const title = document.getElementById('meeting-title').value.trim() || 'الجلسة العمومية للمجلس';
  
  if(!date){
    toast('اختر تاريخ الجلسة','error');
    return;
  }
  
  State.setNextMeetingObj({
    date: date + 'T' + time,
    title: title,
    visible: true
  });
  
  saveDB();
  updateCountdown();
  toast('تم حفظ موعد الجلسة ✅');
  log('تحديث موعد الجلسة','⏰');
  
  // Show preview
  const preview = document.getElementById('meeting-preview');
  const previewText = document.getElementById('meeting-preview-text');
  const dateObj = new Date(State.getNextMeeting().date);
  previewText.textContent = `${title} - ${dateObj.toLocaleDateString('ar-SA', {weekday:'long', year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit'})}`;
  preview.style.display = 'block';
}

function hideMeeting(){
  if(!State.getNextMeeting()){
    toast('لا توجد جلسة محفوظة','error');
    return;
  }
  
  State.getNextMeeting().visible = !State.getNextMeeting().visible;
  saveDB();
  updateCountdown();
  toast(State.getNextMeeting().visible ? 'تم إظهار العد التنازلي' : 'تم إخفاء العد التنازلي');
}

function clearMeeting(){
  confirm2('هل تريد حذف موعد الجلسة القادمة؟', () => {
    State.clearNextMeeting();
    saveDB();
    updateCountdown();
    document.getElementById('meeting-date').value = '';
    document.getElementById('meeting-time').value = '10:00';
    document.getElementById('meeting-title').value = 'الجلسة العمومية للمجلس';
    document.getElementById('meeting-preview').style.display = 'none';
    toast('تم حذف الجلسة');
    log('حذف موعد الجلسة','🗑️');
  });
}

function updateSidebar(){
  document.getElementById('sb-members').textContent=State.getMembers().length;
  document.getElementById('sb-committees').textContent=State.getCommittees().length;
  const inc=State.getTransactions().filter(t=>t.type==='إيراد').reduce((s,t)=>s+t.amount,0);
  const exp=State.getTransactions().filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  document.getElementById('sb-balance').textContent=fmt(inc-exp);
  updateCountdown();
}

// =================== COUNTDOWN ===================
function updateCountdown(){
  const widget=document.getElementById('countdown-widget');
  const display=document.getElementById('countdown-display');
  const dateEl=document.getElementById('countdown-date');
  
  if(!State.getNextMeeting() || State.getNextMeeting().visible === false){
    widget.style.display='none';
    return;
  }
  
  widget.style.display='block';
  const target=new Date(State.getNextMeeting().date);
  const now=new Date();
  const diff=target-now;
  
  if(diff<0){
    display.textContent='انتهت';
    dateEl.textContent='';
    return;
  }
  
  const days=Math.floor(diff/(1000*60*60*24));
  const hours=Math.floor((diff%(1000*60*60*24))/(1000*60*60));
  const mins=Math.floor((diff%(1000*60*60))/(1000*60));
  display.textContent=`${days} يوم ${hours} س ${mins} د`;
  dateEl.textContent=target.toLocaleDateString('ar-SA',{weekday:'short',year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
}
setInterval(updateCountdown,60000); // update every minute

// =================== COUNCIL ===================
function renderCouncil(){
  const posColors={president:'border-color:var(--accent);background:linear-gradient(135deg,#fffbf0,#fff)',vp:'',treasurer:'',coordinator:'',secretary:'',advisory:'border-color:var(--primary);background:linear-gradient(135deg,#f0f5ff,#fff)'};
  document.getElementById('positions-grid').innerHTML=COUNCIL_POSITIONS.map(p=>`
    <div class="position-card ${p.type==='president'?'president':p.type==='advisory'?'advisory':''}" style="${posColors[p.type]||''}">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--green-dark),var(--green));display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">${p.icon}</div>
        <div>
          <div style="font-size:12px;color:var(--text-muted);font-weight:600">${p.role}</div>
          <div style="font-size:13px;font-weight:700;color:var(--green-dark)">${p.name}</div>
        </div>
        ${p.type==='advisory'?'<span class="badge badge-purple" style="margin-right:auto">استشارية</span>':''}
        ${p.type==='president'?'<span class="badge badge-gold" style="margin-right:auto">⭐ رئيس</span>':''}
      </div>
      <div style="font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:6px">المهام الرئيسية:</div>
      <div style="display:flex;flex-direction:column;gap:4px">
        ${p.tasks.map(t=>`<div style="display:flex;align-items:flex-start;gap:6px;font-size:12px"><span style="color:var(--green);flex-shrink:0">•</span><span>${t}</span></div>`).join('')}
      </div>
    </div>`).join('');
}

// =================== MEMBERS ===================
function openAddMember(){ clearMemberForm(); document.getElementById('modal-member-title').textContent='➕ إضافة عضو جديد'; openModal('modal-member'); }
function clearMemberForm(){ ['mm-id','mm-name','mm-phone','mm-idnum','mm-family','mm-notes'].forEach(id=>document.getElementById(id).value=''); document.getElementById('mm-join').value=today(); document.getElementById('mm-status').value='نشط'; }

function saveMember(){
  const name=document.getElementById('mm-name').value.trim();
  if(!name){toast('الاسم مطلوب','error');return;}
  const id=document.getElementById('mm-id').value;
  const data={name,phone:document.getElementById('mm-phone').value,idNum:document.getElementById('mm-idnum').value,family:document.getElementById('mm-family').value||'غير محدد',joinDate:document.getElementById('mm-join').value||today(),status:document.getElementById('mm-status').value,notes:document.getElementById('mm-notes').value};
  MemberService.saveMember(id, data);
}

function editMember(id){
  const m=State.getMembers().find(x=>x.id===id); if(!m) return;
  ['mm-id','mm-name','mm-phone','mm-idnum','mm-family','mm-join','mm-notes'].forEach(fid=>document.getElementById(fid).value=m[fid.replace('mm-',''=='mm-id'?'id':fid.replace('mm-',''))]||'');
  document.getElementById('mm-id').value=id;
  document.getElementById('mm-name').value=m.name;
  document.getElementById('mm-phone').value=m.phone||'';
  document.getElementById('mm-idnum').value=m.idNum||'';
  document.getElementById('mm-family').value=m.family||'';
  document.getElementById('mm-join').value=m.joinDate||'';
  document.getElementById('mm-notes').value=m.notes||'';
  document.getElementById('mm-status').value=m.status;
  document.getElementById('modal-member-title').textContent='✏️ تعديل بيانات العضو';
  openModal('modal-member');
}

function deleteMember(id){
  MemberService.deleteMember(id);
}

function renderMembers(){
  const s=document.getElementById('m-search').value;
  const sf=document.getElementById('m-flt-status').value;
  let list=State.getMembers();
  if(s) list=list.filter(m=>m.name.includes(s)||m.phone?.includes(s));
  if(sf) list=list.filter(m=>m.status===sf);
  const p=curPeriod();
  const tbody=document.getElementById('members-tbody');
  if(!list.length){tbody.innerHTML='<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">👥</div><p>لا يوجد أعضاء</p></div></td></tr>';return;}
  tbody.innerHTML=list.map((m,i)=>{
    const pay=p?State.getPayments().find(x=>x.memberId===m.id&&x.periodId===p.id):null;
    const pb=!p?'<span class="badge badge-gray">لا دورة</span>':pay?.status==='مدفوع'?'<span class="badge badge-success">✅ مدفوع</span>':pay?.status==='معفي'?'<span class="badge badge-purple">🔖 معفي</span>':'<span class="badge badge-warning">⏳ لم يدفع</span>';
    const sb=m.status==='نشط'?'<span class="badge badge-success">نشط</span>':m.status==='معفي'?'<span class="badge badge-purple">معفي</span>':'<span class="badge badge-gray">غير نشط</span>';
    return `<tr><td style="color:var(--text-muted);font-size:11px">${i+1}</td>
    <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div><div style="font-weight:600">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.family}</div></div></div></td>
    <td>${m.phone||'—'}</td>
    <td style="font-size:11px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${memberCommittees(m.id)}</td>
    <td style="font-size:11px">${m.joinDate||'—'}</td><td>${sb}</td><td>${pb}</td>
    <td><div style="display:flex;gap:4px">
      <button class="btn btn-outline btn-xs" onclick="editMember('${m.id}')">✏️</button>
      ${p?`<button class="btn btn-accent btn-xs" onclick="openPayModal('${m.id}')">💳</button>`:''}
      <button class="btn btn-danger btn-xs" onclick="deleteMember('${m.id}')">🗑️</button>
    </div></td></tr>`;
  }).join('');
  document.getElementById('members-count').textContent=`${list.length} عضو`;
}

// =================== FEES ===================
function createPeriod(){
  const name=document.getElementById('pd-name').value.trim(); const amount=parseFloat(document.getElementById('pd-amount').value);
  if(!name||!amount){toast('اسم الدورة والمبلغ مطلوبان','error');return;}
  const data={name,feeAmount:amount,start:document.getElementById('pd-start').value||today(),end:document.getElementById('pd-end').value||''};
  FinanceService.createPeriod(data);
}

function openPayModal(memberId){
  const m=State.getMembers().find(x=>x.id===memberId); if(!m) return;
  const p=curPeriod(); if(!p){toast('لا توجد دورة مفعّلة','error');return;}
  const pay=State.getPayments().find(x=>x.memberId===memberId&&x.periodId===p.id);
  document.getElementById('pay-mid').value=memberId;
  document.getElementById('pay-mname').value=m.name;
  document.getElementById('pay-amount').value=pay?.amount||p.feeAmount;
  document.getElementById('pay-date').value=pay?.date||today();
  document.getElementById('pay-method').value=pay?.method||'تحويل بنكي';
  document.getElementById('pay-status').value=pay?.status||'لم يدفع';
  document.getElementById('pay-notes').value=pay?.notes||'';
  openModal('modal-pay');
}

function savePayment(){
  const memberId=document.getElementById('pay-mid').value; const p=curPeriod(); if(!p) return;
  const paymentData={
    status: document.getElementById('pay-status').value,
    amount: parseFloat(document.getElementById('pay-amount').value)||0,
    date:   document.getElementById('pay-date').value,
    method: document.getElementById('pay-method').value,
    notes:  document.getElementById('pay-notes').value
  };
  FinanceService.savePayment(memberId, paymentData);
}

function renderFees(){
  const p=curPeriod();
  document.getElementById('fees-period-lbl').textContent=p?p.name:'لا توجد دورة';
  if(!p){ document.getElementById('fees-stats').innerHTML='<div style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:16px">أنشئ دورة أولاً</div>'; document.getElementById('fees-tbody').innerHTML=''; return; }
  const pays=State.getPayments().filter(x=>x.periodId===p.id);
  const paid=pays.filter(x=>x.status==='مدفوع'); const unpaid=pays.filter(x=>x.status==='لم يدفع'); const exempt=pays.filter(x=>x.status==='معفي');
  const collected=paid.reduce((s,x)=>s+x.amount,0);
  document.getElementById('fees-stats').innerHTML=`
    <div style="background:#dcfce7;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#166534">${paid.length}</div><div style="font-size:11px;color:#166534">دفعوا ✅</div></div>
    <div style="background:#fef9c3;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#854d0e">${unpaid.length}</div><div style="font-size:11px;color:#854d0e">لم يدفعوا ⏳</div></div>
    <div style="background:#f3e8ff;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#7e22ce">${exempt.length}</div><div style="font-size:11px;color:#7e22ce">معفيون 🔖</div></div>
    <div style="background:#dbeafe;border-radius:10px;padding:12px;text-align:center"><div style="font-size:20px;font-weight:900;color:#1e40af">${fmt(collected)}</div><div style="font-size:11px;color:#1e40af">ريال محصّلة 💰</div></div>`;
  let list=pays; const s=document.getElementById('fees-search').value; const sf=document.getElementById('fees-flt').value;
  if(s) list=list.filter(x=>{ const m=State.getMembers().find(y=>y.id===x.memberId); return m?.name.includes(s); });
  if(sf) list=list.filter(x=>x.status===sf);
  document.getElementById('fees-tbody').innerHTML=list.map(pay=>{
    const m=State.getMembers().find(x=>x.id===pay.memberId); if(!m) return '';
    const sb=pay.status==='مدفوع'?'<span class="badge badge-success">✅ مدفوع</span>':pay.status==='معفي'?'<span class="badge badge-purple">🔖 معفي</span>':'<span class="badge badge-warning">⏳ لم يدفع</span>';
    return `<tr><td><div style="display:flex;align-items:center;gap:8px"><div class="avatar" style="background:${avColor(m.name)};width:30px;height:30px;font-size:11px">${avInit(m.name)}</div><div><div style="font-weight:600">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.family}</div></div></div></td>
    <td>${fmt(pay.required||p.feeAmount)} ريال</td>
    <td style="font-weight:700;color:${pay.status==='مدفوع'?'var(--green)':'var(--text-muted)'}">${pay.status==='مدفوع'?fmt(pay.amount)+' ريال':'—'}</td>
    <td style="font-size:11px">${pay.date||'—'}</td><td style="font-size:11px">${pay.method||'—'}</td>
    <td>${sb}</td><td><button class="btn btn-accent btn-xs" onclick="openPayModal('${m.id}')">💳 تحديث</button></td></tr>`;
  }).join('');
}

// =================== COMMITTEES ===================
function renderCommittees(){
  document.getElementById('committees-grid').innerHTML=State.getCommittees().map(c=>{
    const mems=(State.getCommitteeMembers()[c.id]||c.members||[]).length;
    const evs=State.getEvents().filter(e=>e.committeeId===c.id).length;
    return `<div class="committee-card" onclick="showCommitteeDetail('${c.id}')">
      <div class="committee-banner" style="background:${c.color}">${c.icon}</div>
      <div class="committee-body"><div class="committee-title">${c.name}</div><div class="committee-meta">${c.desc||''}</div>${c.advisory?'<div style="margin-top:5px"><span class="badge badge-purple">🎓 استشارية</span></div>':''}</div>
      <div class="committee-footer"><span style="font-size:11px;color:var(--text-muted)">👥 ${mems} عضو  •  🗓️ ${evs} فعالية</span><button class="btn btn-outline btn-xs" onclick="event.stopPropagation();showCommitteeDetail('${c.id}')">تفاصيل</button></div>
    </div>`;
  }).join('');
}

function showCommitteeDetail(cid){
  const c=State.getCommittees().find(x=>x.id===cid); if(!c) return;
  document.getElementById('cdetail-title').innerHTML=`${c.icon} ${c.name}`;
  const defaultMembers=(c.members||[]);
  const customIds=State.getCommitteeMembers()[cid]||[];
  const allMembers=State.getMembers().filter(m=>customIds.includes(m.id));
  const notIn=State.getMembers().filter(m=>!customIds.includes(m.id));
  const events=State.getEvents().filter(e=>e.committeeId===cid);
  const txs=State.getTransactions().filter(t=>t.committee===cid);
  const spent=txs.filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  document.getElementById('cdetail-body').innerHTML=`
    <div style="padding:12px;background:#f8fbf8;border-radius:10px;margin-bottom:14px">
      <div style="font-size:13px;color:var(--text-muted)">${c.desc||''}</div>
      ${c.advisory?'<div style="margin-top:6px"><span class="badge badge-purple">🎓 لجنة استشارية - تقدم المشورة للإدارة</span></div>':''}
    </div>
    <div class="grid-3" style="margin-bottom:14px;text-align:center">
      <div style="background:#dcfce7;padding:10px;border-radius:10px"><div style="font-size:18px;font-weight:900;color:#166534">${defaultMembers.length}</div><div style="font-size:11px;color:#166534">عضو مُعيَّن</div></div>
      <div style="background:#dbeafe;padding:10px;border-radius:10px"><div style="font-size:18px;font-weight:900;color:#1e40af">${events.length}</div><div style="font-size:11px;color:#1e40af">فعالية</div></div>
      <div style="background:#fef9c3;padding:10px;border-radius:10px"><div style="font-size:18px;font-weight:900;color:#854d0e">${fmt(spent)}</div><div style="font-size:11px;color:#854d0e">ريال مصاريف</div></div>
    </div>
    <div style="font-size:13px;font-weight:700;margin-bottom:8px">👥 أعضاء اللجنة (من الـ PDF)</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
      ${defaultMembers.map(name=>`<span style="background:linear-gradient(135deg,${c.color.split(',')[0].replace('linear-gradient(135deg,','')},transparent);color:var(--green-dark);border:1px solid rgba(71,145,92,.2);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600">${name}</span>`).join('')}
    </div>
    ${allMembers.length?`<div style="font-size:13px;font-weight:700;margin-bottom:8px">🔗 أعضاء مرتبطون من قاعدة البيانات</div><div style="margin-bottom:12px">${allMembers.map(m=>`<div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #f0ebe0"><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div style="flex:1"><div style="font-weight:600;font-size:13px">${m.name}</div></div><button class="btn btn-danger btn-xs" onclick="removeMemberFromCommittee('${cid}','${m.id}')">✕</button></div>`).join('')}</div>`:''}
    ${notIn.length&&State.getMembers().length?`<div style="display:flex;gap:8px"><select class="form-control" id="add-to-c-select" style="flex:1">${notIn.map(m=>`<option value="${m.id}">${m.name}</option>`).join('')}</select><button class="btn btn-primary btn-sm" onclick="addMemberToCommittee('${cid}')">+ ربط عضو</button></div>`:''}
    ${events.length?`<div style="margin-top:14px;font-size:13px;font-weight:700;margin-bottom:8px">🗓️ فعاليات اللجنة</div><div>${events.map(e=>`<div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f0f0f0"><span>${e.icon||'🎉'}</span><div style="flex:1"><div style="font-size:13px;font-weight:600">${e.name}</div><div style="font-size:11px;color:var(--text-muted)">${e.date||'—'}</div></div><span class="badge ${sBadge(e.status)}">${e.status}</span></div>`).join('')}</div>`:''}
  `;
  openModal('modal-committee-detail');
}

function addMemberToCommittee(cid){ const mid=document.getElementById('add-to-c-select')?.value; MemberService.addMemberToCommittee(cid, mid); }
function removeMemberFromCommittee(cid,mid){ MemberService.removeMemberFromCommittee(cid, mid); }

// =================== ORG CHART ===================
function renderOrgChart(){
  const advisory=State.getCommittees().find(c=>c.advisory);
  const regular=State.getCommittees().filter(c=>!c.advisory);
  document.getElementById('org-body').innerHTML=`
    <div style="text-align:center">
      ${advisory?`<div style="margin-bottom:16px;display:flex;justify-content:center"><div style="border:2px dashed var(--accent);border-radius:12px;padding:10px 20px;background:#fffbf0;display:inline-flex;align-items:center;gap:10px"><span style="font-size:20px">${advisory.icon}</span><div><div style="font-weight:700;font-size:13px;color:var(--primary)">${advisory.name}</div><div style="font-size:10px;color:var(--text-muted)">جهة استشارية</div></div></div></div>`:''}
      <div style="display:flex;justify-content:center;margin-bottom:6px">
        <div style="background:linear-gradient(135deg,var(--green-dark),var(--primary));color:#fff;border-radius:14px;padding:14px 28px;display:inline-flex;align-items:center;gap:12px">
          <span style="font-size:22px">🏛️</span>
          <div style="text-align:right"><div style="font-size:14px;font-weight:700">إدارة مجلس صندوق عائلة العوامي</div><div style="font-size:10px;opacity:.7">الرئيس: منصور علي</div></div>
        </div>
      </div>
      <div style="display:flex;justify-content:center;margin:2px 0"><div style="width:2px;height:20px;background:var(--border)"></div></div>
      <div style="width:80%;height:2px;background:var(--border);margin:0 auto"></div>
      <div style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;padding-top:0">
        ${regular.map(c=>{ const mems=(State.getCommitteeMembers()[c.id]||c.members||[]).length; return `<div style="display:flex;flex-direction:column;align-items:center"><div style="width:2px;height:20px;background:var(--border)"></div><div style="border:2px solid var(--border);border-radius:10px;padding:10px 12px;min-width:120px;background:var(--bg-card);cursor:pointer;transition:all .2s" onmouseover="this.style.borderColor='var(--green)'" onmouseout="this.style.borderColor='var(--border)'" onclick="showPage('committees',document.querySelector('[onclick*=committees]'));setTimeout(()=>showCommitteeDetail('${c.id}'),300)"><div style="font-size:20px;margin-bottom:3px">${c.icon}</div><div style="font-size:11px;font-weight:700;color:var(--green-dark)">${c.name}</div><div style="font-size:10px;color:var(--text-muted);margin-top:2px">👥 ${mems} عضو</div></div></div>`; }).join('')}
      </div>
    </div>`;
}

// =================== BUDGET ===================
function addTransaction(){
  const desc=document.getElementById('tx-desc').value.trim(); const amount=parseFloat(document.getElementById('tx-amount').value);
  if(!desc||!amount){toast('الوصف والمبلغ مطلوبان','error');return;}
  const data={
    type:      document.getElementById('tx-type').value,
    amount,
    category:  document.getElementById('tx-cat').value,
    committee: document.getElementById('tx-committee').value,
    desc,
    date:      document.getElementById('tx-date').value||today()
  };
  FinanceService.addTransaction(data);
}

function renderBudget(){
  document.getElementById('tx-committee').innerHTML='<option value="">عام</option>'+committeeSelectOptions();
  const flt=document.getElementById('b-flt').value;
  let txs=[...State.getTransactions()].sort((a,b)=>b.date.localeCompare(a.date));
  if(flt) txs=txs.filter(t=>t.type===flt);
  const income=State.getTransactions().filter(t=>t.type==='إيراد').reduce((s,t)=>s+t.amount,0);
  const expense=State.getTransactions().filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  document.getElementById('b-income').textContent=fmt(income);
  document.getElementById('b-expense').textContent=fmt(expense);
  document.getElementById('b-net').textContent=fmt(income-expense);
  const catClr={'رسوم الأعضاء':'#27ae60','رحلة العمرة':'#2980b9','غداء العيد':'#e67e22','رحلة ترفيهية':'#8e44ad','مسابقة':'#c8a84b','مصاريف إدارية':'#95a5a6','استثمار':'#1B3456','عقيقة جماعية':'#6b3a1a','تبرعات':'#c8a84b','أخرى':'#7f8c8d'};
  document.getElementById('budget-tbody').innerHTML=txs.length?txs.map(tx=>{ const c=State.getCommittees().find(x=>x.id===tx.committee); return `<tr><td style="font-size:11px;color:var(--text-muted)">${tx.date}</td><td style="font-weight:600">${tx.desc}</td><td><span style="background:${(catClr[tx.category]||'#777')}22;color:${catClr[tx.category]||'#777'};padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600">${tx.category}</span></td><td style="font-size:11px">${c?c.name:'عام'}</td><td><span class="badge ${tx.type==='إيراد'?'badge-success':'badge-danger'}">${tx.type==='إيراد'?'⬆️':'⬇️'} ${tx.type}</span></td><td style="font-weight:700;color:${tx.type==='إيراد'?'var(--green)':'var(--danger)'}">${tx.type==='إيراد'?'+':'-'}${fmt(tx.amount)} ريال</td><td><button class="btn btn-danger btn-xs" onclick="deleteTx('${tx.id}')">🗑️</button></td></tr>`; }).join(''):'<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">💰</div><p>لا معاملات</p></div></td></tr>';
}

function deleteTx(id){ FinanceService.deleteTx(id); }

// =================== CALENDAR ===================
let calendarDate = new Date();

function renderCalendar(){
  const year = calendarDate.getFullYear();
  const month = calendarDate.getMonth();
  const monthNames = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  document.getElementById('cal-month-year').textContent = `${monthNames[month]} ${year}`;
  
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const startDay = firstDay === 6 ? 0 : firstDay + 1; // adjust for Sunday start
  
  const eventsInMonth = State.getEvents().filter(e => {
    if(!e.date) return false;
    const d = new Date(e.date);
    return d.getMonth() === month && d.getFullYear() === year;
  });
  
  let html = '<table style="width:100%;border-collapse:collapse;text-align:center"><thead><tr>';
  ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'].forEach(day => {
    html += `<th style="padding:10px;background:var(--bg);border:1px solid var(--border);font-size:12px;font-weight:700">${day}</th>`;
  });
  html += '</tr></thead><tbody><tr>';
  
  for(let i = 0; i < startDay; i++) html += '<td style="padding:10px;border:1px solid var(--border);background:#fafafa"></td>';
  
  for(let day = 1; day <= daysInMonth; day++){
    if((startDay + day - 1) % 7 === 0 && day !== 1) html += '</tr><tr>';
    const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
    const dayEvents = eventsInMonth.filter(e => e.date === dateStr);
    const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();
    const bgColor = isToday ? 'background:#dcfce7;' : dayEvents.length ? 'background:#f0fdf4;' : '';
    html += `<td style="padding:10px;border:1px solid var(--border);vertical-align:top;height:80px;position:relative;${bgColor}">
      <div style="font-weight:${isToday?'900':'600'};font-size:14px;color:${isToday?'var(--green)':'var(--text)'};margin-bottom:4px">${day}</div>
      ${dayEvents.map(e => {
        const c = State.getCommittees().find(x => x.id === e.committeeId);
        return `<div style="background:${c?c.color:'var(--green)'};color:#fff;font-size:9px;padding:2px 4px;border-radius:4px;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${e.name}">${e.icon||'🎉'} ${e.name}</div>`;
      }).join('')}
    </td>`;
  }
  
  const remainingCells = (7 - ((startDay + daysInMonth) % 7)) % 7;
  for(let i = 0; i < remainingCells; i++) html += '<td style="padding:10px;border:1px solid var(--border);background:#fafafa"></td>';
  html += '</tr></tbody></table>';
  
  document.getElementById('calendar-container').innerHTML = html;
  
  // Month events list
  document.getElementById('month-events-list').innerHTML = eventsInMonth.length ? eventsInMonth.map(e => {
    const c = State.getCommittees().find(x => x.id === e.committeeId);
    return `<div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f0f0f0"><div style="font-size:22px">${e.icon||'🎉'}</div><div style="flex:1"><div style="font-size:13px;font-weight:600">${e.name}</div><div style="font-size:11px;color:var(--text-muted)">${e.date} ${c?'• '+c.name:''}</div></div><span class="badge ${sBadge(e.status)}">${e.status}</span></div>`;
  }).join('') : '<div class="empty-state"><div class="empty-icon">📅</div><p>لا فعاليات هذا الشهر</p></div>';
  
  // Upcoming events
  const upcoming = State.getEvents().filter(e => e.date && new Date(e.date) > new Date()).sort((a,b) => a.date.localeCompare(b.date)).slice(0,5);
  document.getElementById('upcoming-events-list').innerHTML = upcoming.length ? upcoming.map(e => {
    const c = State.getCommittees().find(x => x.id === e.committeeId);
    const daysUntil = Math.ceil((new Date(e.date) - new Date()) / (1000*60*60*24));
    return `<div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f0f0f0"><div style="font-size:22px">${e.icon||'🎉'}</div><div style="flex:1"><div style="font-size:13px;font-weight:600">${e.name}</div><div style="font-size:11px;color:var(--text-muted)">${e.date} (بعد ${daysUntil} يوم)</div></div></div>`;
  }).join('') : '<div class="empty-state"><div class="empty-icon">🗓️</div><p>لا فعاليات قادمة</p></div>';
}

function changeMonth(delta){
  calendarDate.setMonth(calendarDate.getMonth() + delta);
  renderCalendar();
}

function resetCalendar(){
  calendarDate = new Date();
  renderCalendar();
}

// =================== EVENTS ===================
const evBg={'أخرى':'linear-gradient(135deg,#47915C,#2d6b40)'};
const evIcon={'رحلة عمرة':'🕋','غداء العيد':'🍖','رحلة ترفيهية':'🎡','اجتماع':'🤝','مسابقة':'🏆','عقيقة':'🐑','أخرى':'🎉'};

function openAddEvent(){
  document.getElementById('ev-committee').innerHTML='<option value="">غير محدد</option>'+committeeSelectOptions();
  ['ev-name','ev-budget','ev-participants','ev-lead','ev-notes'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('ev-date').value=today();
  document.getElementById('ev-images-preview').innerHTML='';
  openModal('modal-event');
}

function addEvent(){
  const name=document.getElementById('ev-name').value.trim(); if(!name){toast('الاسم مطلوب','error');return;}
  const imgFiles=document.getElementById('ev-images').files;
  const images=[];
  
  // Convert images to base64
  const readImages = async () => {
    for(let file of imgFiles){
      if(file.size > 2*1024*1024){toast('حجم الصورة أكبر من 2MB','error');continue;}
      const reader = new FileReader();
      await new Promise((resolve) => {
        reader.onload = (e) => {
          images.push({name:file.name, data:e.target.result});
          resolve();
        };
        reader.readAsDataURL(file);
      });
    }
    
    State.getEvents().push({id:uid(),name,committeeId:document.getElementById('ev-committee').value,status:document.getElementById('ev-status').value,date:document.getElementById('ev-date').value||'',budget:parseFloat(document.getElementById('ev-budget').value)||0,participants:parseInt(document.getElementById('ev-participants').value)||0,lead:document.getElementById('ev-lead').value,notes:document.getElementById('ev-notes').value,icon:'🎉',images});
    log(`فعالية: ${name}`,'🎉'); saveDB(); closeModal('modal-event'); toast('تم'); renderEvents(); renderCalendar(); renderDashboard();
  };
  
  readImages();
}

// Preview images on selection
document.addEventListener('DOMContentLoaded', () => {
  const imgInput = document.getElementById('ev-images');
  if(imgInput){
    imgInput.addEventListener('change', (e) => {
      const preview = document.getElementById('ev-images-preview');
      preview.innerHTML = '';
      for(let file of e.target.files){
        const reader = new FileReader();
        reader.onload = (ev) => {
          preview.innerHTML += `<div style="width:60px;height:60px;border-radius:8px;overflow:hidden;border:2px solid var(--border)"><img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover"></div>`;
        };
        reader.readAsDataURL(file);
      }
    });
  }
});

function renderEvents(){
  const el=document.getElementById('events-grid');
  if(!State.getEvents().length){el.innerHTML='<div style="grid-column:1/-1"><div class="empty-state"><div class="empty-icon">🗓️</div><p>لا فعاليات</p></div></div>';return;}
  el.innerHTML=State.getEvents().map(ev=>{ 
    const c=State.getCommittees().find(x=>x.id===ev.committeeId); 
    const imgs=(ev.images||[]).slice(0,3);
    return `<div class="committee-card">
    <div class="committee-banner" style="background:${c?c.color:evBg['أخرى']};position:relative">
      ${imgs.length?`<div style="position:absolute;inset:0;display:flex;gap:2px">${imgs.map(img=>`<div style="flex:1;background:url('${img.data}') center/cover"></div>`).join('')}</div>`:`${ev.icon||'🎉'}`}
      <div style="position:absolute;top:8px;right:8px"><span class="badge ${sBadge(ev.status)}">${ev.status}</span></div>
      ${imgs.length?`<div style="position:absolute;bottom:8px;left:8px;background:rgba(0,0,0,.6);color:#fff;padding:3px 8px;border-radius:12px;font-size:18px">${ev.icon||'🎉'}</div>`:''}
    </div>
    <div class="committee-body"><div class="committee-title">${ev.name}</div><div class="committee-meta">${c?'🏛️ '+c.name:''}${ev.date?' • 📅 '+ev.date:''}</div>${imgs.length?`<div style="font-size:10px;color:var(--text-muted);margin-top:4px">📸 ${imgs.length} صورة</div>`:''}</div>
    <div class="committee-footer"><span style="font-size:11px;color:var(--text-muted)">${ev.budget?'💰 '+fmt(ev.budget)+' ريال':''} ${ev.participants?'👥 '+ev.participants:''}</span><button class="btn btn-danger btn-xs" onclick="deleteEvent('${ev.id}')">🗑️</button></div>
  </div>`; }).join('');
}
function deleteEvent(id){
  confirm2('حذف الفعالية؟',()=>{
    State.setEvents(State.getEvents().filter(x=>x.id!==id));
    saveDB();
    renderEvents();
    renderCalendar();
    renderDashboard();
    toast('تم');
  });
}

// =================== FAMILY TREE ===================
function openAddBranch(){
  document.getElementById('branch-id').value = '';
  document.getElementById('branch-name').value = '';
  document.getElementById('branch-head').value = '';
  document.getElementById('branch-color').value = '#47915C';
  document.getElementById('branch-members').value = '';
  document.getElementById('branch-notes').value = '';
  document.getElementById('branch-modal-title').textContent = '🌳 إضافة فرع عائلي جديد';
  document.getElementById('branch-delete-btn').style.display = 'none';
  openModal('modal-add-branch');
}

function editBranch(id){
  const b = State.getFamilyBranches().find(x => x.id === id);
  if(!b) return;
  
  document.getElementById('branch-id').value = id;
  document.getElementById('branch-name').value = b.name;
  document.getElementById('branch-head').value = b.head || '';
  document.getElementById('branch-color').value = b.color || '#47915C';
  document.getElementById('branch-members').value = (b.members || []).join('\n');
  document.getElementById('branch-notes').value = b.notes || '';
  document.getElementById('branch-modal-title').textContent = '✏️ تعديل: ' + b.name;
  document.getElementById('branch-delete-btn').style.display = 'inline-flex';
  openModal('modal-add-branch');
}

function saveBranch(){
  const name = document.getElementById('branch-name').value.trim();
  if(!name){ toast('اسم الفرع مطلوب','error'); return; }
  
  const id = document.getElementById('branch-id').value;
  const membersText = document.getElementById('branch-members').value.trim();
  const members = membersText ? membersText.split('\n').map(m => m.trim()).filter(Boolean) : [];
  
  const data = {
    name,
    head: document.getElementById('branch-head').value.trim(),
    color: document.getElementById('branch-color').value,
    members,
    count: members.length,
    notes: document.getElementById('branch-notes').value.trim()
  };
  
  if(id){
    // Edit existing
    const b = State.getFamilyBranches().find(x => x.id === id);
    if(b) Object.assign(b, data);
    log(`تعديل فرع: ${name}`,'✏️');
  }else{
    // Add new
    State.getFamilyBranches().push({id:uid(), ...data});
    log(`إضافة فرع: ${name}`,'🌳');
  }
  
  saveDB();
  closeModal('modal-add-branch');
  toast(id ? 'تم التحديث ✅' : 'تم الإضافة ✅');
  renderFamilyTree();
}

function deleteBranchConfirm(){
  const id = document.getElementById('branch-id').value;
  const b = State.getFamilyBranches().find(x => x.id === id);
  if(!b) return;
  
  confirm2(`حذف فرع "${b.name}" نهائياً؟`, () => {
    State.setFamilyBranches(State.getFamilyBranches().filter(x => x.id !== id));
    log(`حذف فرع: ${b.name}`,'🗑️');
    saveDB();
    closeModal('modal-add-branch');
    toast('تم الحذف');
    renderFamilyTree();
  });
}

function exportTree(){
  const dataStr = JSON.stringify({familyBranches: State.getFamilyBranches()}, null, 2);
  const dataBlob = new Blob([dataStr], {type: 'application/json'});
  const url = URL.createObjectURL(dataBlob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `awami-family-tree-${new Date().toISOString().split('T')[0]}.json`;
  a.click();
  URL.revokeObjectURL(url);
  toast('تم تصدير الشجرة 🌳');
}

function renderFamilyTree(){
  const container = document.querySelector('#page-familytree .card-body');
  if(!container) return;
  
  if(!State.getFamilyBranches().length){
    container.innerHTML = '<div class="empty-state"><div class="empty-icon">🌳</div><p>لا توجد أفرع. اضغط "إضافة فرع جديد"</p></div>';
    return;
  }
  
  container.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
    ${State.getFamilyBranches().map(b=>`
      <div style="border:2px solid ${b.color};border-radius:10px;padding:12px;cursor:pointer;transition:all .2s" onclick="editBranch('${b.id}')" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
          <div style="width:30px;height:30px;background:${b.color};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px">🌿</div>
          <div style="flex:1;font-weight:700;font-size:13px;color:var(--green-dark)">${b.name}</div>
        </div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">👤 ${b.head || 'غير محدد'}</div>
        <div style="font-size:11px;color:var(--text-muted)">${(b.members||[]).length} فرد مُدخل</div>
      </div>
    `).join('')}
  </div>`;
  
  renderFamilyTreePreview();
}

function renderFamilyTreePreview(){
  const view=document.getElementById('tree-view').value;
  const container=document.getElementById('tree-container');
  
  if(!State.getFamilyBranches().length){
    container.innerHTML='<div class="empty-state"><div class="empty-icon">🌳</div><p>لا توجد أفرع عائلية</p></div>';
    return;
  }
  
  if(view==='list'){
    container.innerHTML=`<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">${State.getFamilyBranches().map(b=>`
      <div style="background:#fff;border-radius:12px;border:2px solid ${b.color};padding:18px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          <div style="width:42px;height:42px;background:${b.color};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px">🌿</div>
          <div style="flex:1"><div style="font-size:15px;font-weight:700;color:var(--green-dark)">${b.name}</div>${b.head?`<div style="font-size:11px;color:var(--text-muted)">👤 ${b.head}</div>`:''}</div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;text-align:center;margin-top:10px">
          <div style="background:${b.color}11;padding:8px;border-radius:8px"><div style="font-size:16px;font-weight:700;color:${b.color}">${b.count||0}</div><div style="font-size:10px;color:var(--text-muted)">فرد</div></div>
          <div style="background:${b.color}11;padding:8px;border-radius:8px"><button class="btn btn-outline btn-xs" onclick="showBranchMembers('${b.id}')">👁️ عرض</button></div>
        </div>
        ${b.notes?`<div style="margin-top:8px;font-size:11px;color:var(--text-muted);padding-top:8px;border-top:1px solid #f0f0f0">${b.notes}</div>`:''}
      </div>`).join('')}</div>`;
  }else if(view==='vertical'){
    container.innerHTML=`<div style="text-align:center">
      <div style="background:linear-gradient(135deg,var(--green-dark),var(--green));color:#fff;border-radius:14px;padding:18px 32px;display:inline-block;margin-bottom:30px">
        <div style="font-size:24px;font-weight:900;font-family:'Amiri',serif">عائلة العوامي</div>
        <div style="font-size:12px;opacity:.7;margin-top:4px">AL AWAMI • ١٩٩٢</div>
      </div>
      <div style="width:3px;height:30px;background:var(--border);margin:0 auto"></div>
      <div style="display:flex;justify-content:center;gap:20px;flex-wrap:wrap">
        ${State.getFamilyBranches().map(b=>`
          <div style="text-align:center;cursor:pointer" onclick="showBranchMembers('${b.id}')">
            <div style="width:3px;height:20px;background:var(--border);margin:0 auto"></div>
            <div style="background:${b.color};color:#fff;border-radius:12px;padding:14px 20px;min-width:140px;transition:transform .2s" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
              <div style="font-size:20px;margin-bottom:6px">🌿</div>
              <div style="font-size:14px;font-weight:700">${b.name}</div>
              ${b.head?`<div style="font-size:10px;opacity:.8;margin-top:3px">${b.head}</div>`:''}
              <div style="font-size:11px;margin-top:6px;padding-top:6px;border-top:1px solid rgba(255,255,255,.2)">${b.count||0} فرد</div>
            </div>
          </div>`).join('')}
      </div>
    </div>`;
  }else{
    // horizontal
    container.innerHTML=`<div style="display:flex;align-items:center;gap:24px;overflow-x:auto;padding-bottom:20px">
      <div style="background:linear-gradient(135deg,var(--green-dark),var(--green));color:#fff;border-radius:14px;padding:20px 28px;flex-shrink:0">
        <div style="font-size:20px;font-weight:900;font-family:'Amiri',serif">عائلة<br>العوامي</div>
        <div style="font-size:10px;opacity:.7;margin-top:4px">١٩٩٢</div>
      </div>
      ${State.getFamilyBranches().map((b,i)=>`
        <div style="display:flex;align-items:center;gap:8px">
          <div style="width:30px;height:2px;background:var(--border)"></div>
          <div style="background:${b.color};color:#fff;border-radius:12px;padding:12px 18px;min-width:130px;flex-shrink:0;cursor:pointer;transition:transform .2s" onclick="showBranchMembers('${b.id}')" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="font-size:18px;margin-bottom:4px">🌿</div>
            <div style="font-size:13px;font-weight:700">${b.name}</div>
            ${b.head?`<div style="font-size:9px;opacity:.8">${b.head}</div>`:''}
            <div style="font-size:10px;margin-top:4px">${b.count||0} فرد</div>
          </div>
        </div>`).join('')}
    </div>`;
  }
}

function showBranchMembers(bid){
  const b = State.getFamilyBranches().find(x => x.id === bid);
  if(!b) return;
  
  const members = b.members || [];
  document.getElementById('branch-detail-title').textContent = b.name;
  document.getElementById('branch-detail-body').innerHTML = `
    <div style="background:linear-gradient(135deg,${b.color},${b.color}aa);color:#fff;border-radius:12px;padding:18px;margin-bottom:16px">
      <div style="font-size:18px;font-weight:700;margin-bottom:6px">${b.name}</div>
      <div style="font-size:13px;opacity:.9">رب العائلة: ${b.head}</div>
      <div style="font-size:13px;opacity:.9;margin-top:4px">عدد الأفراد: ${b.count || members.length}</div>
    </div>
    ${members.length ? `
      <div style="font-size:14px;font-weight:700;margin-bottom:10px">👥 أفراد الفرع:</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">
        ${members.map(m => `<div style="background:${b.color}11;border:1px solid ${b.color}44;padding:10px;border-radius:8px;font-size:13px;font-weight:600">• ${m}</div>`).join('')}
      </div>
    ` : '<div style="text-align:center;padding:20px;color:var(--text-muted)">لم يتم إضافة الأفراد بعد</div>'}
  `;
  openModal('modal-branch-detail');
}

function deleteBranch(id){
  const b=State.getFamilyBranches().find(x=>x.id===id);
  confirm2(`حذف فرع "${b?.name}"؟`,()=>{
    State.setFamilyBranches(State.getFamilyBranches().filter(x=>x.id!==id));
    log(`حذف فرع: ${b?.name}`,'🗑️');
    saveDB();
    toast('تم الحذف');
    renderFamilyTree();
  });
}

// =================== VOTING ===================
function createPoll(){
  const title=document.getElementById('poll-title').value.trim(); const optsRaw=document.getElementById('poll-options').value.trim();
  if(!title||!optsRaw){toast('العنوان والخيارات مطلوبة','error');return;}
  const options=optsRaw.split('\n').map(o=>o.trim()).filter(Boolean); if(options.length<2){toast('خيارين على الأقل','error');return;}
  const data={title,options,committee:document.getElementById('poll-committee').value,end:document.getElementById('poll-end').value||''};
  PollService.createPoll(data);
}

function vote(pollId,optIdx){ PollService.vote(pollId, optIdx); }

function renderVoting(){
  document.getElementById('poll-committee').innerHTML='<option value="">عام</option>'+committeeSelectOptions();
  const el=document.getElementById('polls-list');
  if(!State.getPolls().length){el.innerHTML='<div class="empty-state"><div class="empty-icon">🗳️</div><p>لا تصويتات. أنشئ أول تصويت</p></div>';return;}
  const uid2='user_default';
  el.innerHTML=[...State.getPolls()].reverse().map(poll=>{
    const total=poll.options.reduce((s,o)=>s+o.votes.length,0); const uv=poll.options.findIndex(o=>o.votes.includes(uid2));
    const c=State.getCommittees().find(x=>x.id===poll.committee);
    return `<div class="card" style="margin-bottom:14px">
      <div class="card-header"><div><div class="card-title">${poll.title}</div><div style="font-size:11px;color:var(--text-muted)">${c?'🏛️ '+c.name:'عام'} • ${total} صوت</div></div>
      <div style="display:flex;gap:6px"><span class="badge ${poll.active?'badge-success':'badge-gray'}">${poll.active?'نشط':'مغلق'}</span>${poll.active?`<button class="btn btn-outline btn-xs" onclick="closePollF('${poll.id}')">إغلاق</button>`:''}<button class="btn btn-danger btn-xs" onclick="deletePollF('${poll.id}')">🗑️</button></div></div>
      <div class="card-body">${poll.options.map((opt,i)=>{ const pct=total>0?Math.round(opt.votes.length/total*100):0; const iv=i===uv; return `<div style="border:2px solid ${iv?'var(--green)':'var(--border)'};border-radius:10px;padding:12px;cursor:pointer;margin-bottom:8px;background:${iv?'#f0fdf4':'#fff'};transition:all .2s" onclick="${poll.active?`vote('${poll.id}',${i})`:''}"><div style="display:flex;justify-content:space-between;margin-bottom:5px"><span style="font-size:13px;font-weight:${iv?700:500}">${iv?'✅ ':''} ${opt.text}</span><span style="font-size:12px;color:var(--text-muted)">${opt.votes.length} (${pct}%)</span></div><div style="height:5px;background:#e4ede6;border-radius:100px;overflow:hidden"><div style="height:100%;width:${pct}%;background:linear-gradient(90deg,var(--green),var(--green-light));border-radius:100px"></div></div></div>`; }).join('')}</div>
    </div>`;
  }).join('');
}
function closePollF(id){ PollService.closePoll(id); }
function deletePollF(id){ PollService.deletePoll(id); }

// =================== PORTAL ===================
function renderPortalSelect(){
  document.getElementById('portal-select').innerHTML='<option value="">-- اختر العضو --</option>'+State.getMembers().map(m=>`<option value="${m.id}">${m.name}</option>`).join('');
}

function loadPortal(){
  const id=document.getElementById('portal-select').value; const el=document.getElementById('portal-content'); if(!id){el.innerHTML='';return;}
  const m=State.getMembers().find(x=>x.id===id); if(!m){el.innerHTML='';return;}
  const coms=State.getCommittees().filter(c=>(State.getCommitteeMembers()[c.id]||[]).includes(id));
  const allPays=State.getPayments().filter(p=>p.memberId===id); const paid=allPays.filter(p=>p.status==='مدفوع'); const totalPaid=paid.reduce((s,p)=>s+p.amount,0);
  const curP=curPeriod(); const curPay=curP?State.getPayments().find(p=>p.memberId===id&&p.periodId===curP.id):null;
  el.innerHTML=`
    <div style="background:linear-gradient(135deg,var(--green-dark),var(--primary));border-radius:var(--radius);padding:22px;color:#fff;margin-bottom:14px">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
        <div class="avatar" style="background:${avColor(m.name)};width:52px;height:52px;font-size:18px">${avInit(m.name)}</div>
        <div><div style="font-size:20px;font-weight:800">${m.name}</div><div style="opacity:.7;font-size:12px">عضو منذ ${m.joinDate||'—'} • ${m.family}</div></div>
        <div style="margin-right:auto"><span class="badge ${m.status==='نشط'?'badge-success':'badge-gold'}" style="font-size:12px">${m.status}</span></div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;text-align:center">
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:8px"><div style="font-size:16px;font-weight:700">${fmt(totalPaid)}</div><div style="font-size:10px;opacity:.7">ريال مدفوع</div></div>
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:8px"><div style="font-size:16px;font-weight:700">${paid.length}</div><div style="font-size:10px;opacity:.7">دورات مدفوعة</div></div>
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:8px"><div style="font-size:16px;font-weight:700">${coms.length}</div><div style="font-size:10px;opacity:.7">لجنة</div></div>
      </div>
    </div>
    ${curP?`<div class="card" style="margin-bottom:12px"><div class="card-header"><div class="card-title">الدورة الحالية: ${curP.name}</div></div><div class="card-body"><div style="display:flex;align-items:center;justify-content:space-between"><div><div style="font-size:13px;color:var(--text-muted)">المطلوب: ${fmt(curP.feeAmount)} ريال</div><div style="font-size:13px">المدفوع: ${curPay?.status==='مدفوع'?fmt(curPay.amount)+' ريال':'لم يُسدَّد'}</div></div><span class="badge ${curPay?.status==='مدفوع'?'badge-success':curPay?.status==='معفي'?'badge-purple':'badge-warning'}" style="font-size:14px">${curPay?.status||'لم يدفع'}</span></div></div></div>`:''}
    ${coms.length?`<div class="card" style="margin-bottom:12px"><div class="card-header"><div class="card-title">🏛️ اللجان</div></div><div class="card-body"><div style="display:flex;flex-wrap:wrap;gap:6px">${coms.map(c=>`<span style="background:${c.color};color:#fff;padding:5px 12px;border-radius:20px;font-size:12px">${c.icon} ${c.name}</span>`).join('')}</div></div></div>`:''}
    <div class="card"><div class="card-header"><div class="card-title">سجل المدفوعات</div></div><div class="table-wrap"><table><thead><tr><th>الدورة</th><th>المطلوب</th><th>المدفوع</th><th>التاريخ</th><th>الحالة</th></tr></thead><tbody>${allPays.map(pay=>{ const p=State.getPeriods().find(x=>x.id===pay.periodId); return `<tr><td>${p?.name||'—'}</td><td>${fmt(pay.required||0)} ريال</td><td style="font-weight:700">${pay.status==='مدفوع'?fmt(pay.amount)+' ريال':'—'}</td><td style="font-size:11px">${pay.date||'—'}</td><td><span class="badge ${pay.status==='مدفوع'?'badge-success':pay.status==='معفي'?'badge-purple':'badge-warning'}">${pay.status}</span></td></tr>`; }).join('')}</tbody></table></div></div>
  `;
}

// =================== WHATSAPP ===================
function sendWhatsappReminders(){
  const p=curPeriod(); if(!p){toast('لا توجد دورة مفعّلة','error');return;}
  const unpaid=State.getPayments().filter(x=>x.periodId===p.id&&x.status==='لم يدفع');
  if(!unpaid.length){toast('جميع الأعضاء دفعوا! 🎉');return;}
  const list=unpaid.map(pay=>State.getMembers().find(x=>x.id===pay.memberId)).filter(Boolean);
  document.getElementById('whatsapp-list').innerHTML=list.map(m=>{ const phone=m.phone?m.phone.replace(/^0/,'966'):''; const msg=encodeURIComponent(`السلام عليكم ${m.name} 👋\n\nنذكركم بسداد رسوم مجلس عائلة العوامي للدورة "${p.name}"\nالمبلغ المطلوب: ${fmt(p.feeAmount)} ريال\n\nشكراً لكم 🙏`); return `<div style="display:flex;align-items:center;gap:10px;padding:10px;border-bottom:1px solid #f0ebe0"><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div style="flex:1"><div style="font-weight:600">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.phone||'لا يوجد رقم'}</div></div>${phone?`<a href="https://wa.me/${phone}?text=${msg}" target="_blank" class="btn btn-whatsapp btn-sm">📱 إرسال</a>`:'<span class="badge badge-gray">بدون رقم</span>'}</div>`; }).join('');
  openModal('modal-whatsapp');
}

// =================== EXPORT ===================
function exportToCSV(headers,rows,filename){ const bom='\uFEFF'; const csv=bom+[headers.join(','),...rows.map(r=>r.map(c=>`"${String(c).replace(/"/g,'""')}"`).join(','))].join('\n'); const a=document.createElement('a'); a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv); a.download=filename+'.csv'; a.click(); }
function exportMembersExcel(){ exportToCSV(['الاسم','الجوال','رقم الهوية','الفرع','تاريخ الانضمام','الحالة'],State.getMembers().map(m=>[m.name,m.phone||'',m.idNum||'',m.family,m.joinDate||'',m.status]),'أعضاء_عائلة_العوامي'); toast('تم تصدير الملف 📊'); }
function exportFeesExcel(){ const p=curPeriod(); if(!p){toast('لا توجد دورة','error');return;} const pays=State.getPayments().filter(x=>x.periodId===p.id); exportToCSV(['الاسم','المطلوب','المدفوع','التاريخ','الطريقة','الحالة'],pays.map(pay=>{ const m=State.getMembers().find(x=>x.id===pay.memberId)||{}; return [m.name||'',pay.required||p.feeAmount,pay.amount||0,pay.date||'',pay.method||'',pay.status]; }),`رسوم_${p.name}`); toast('تم التصدير 📊'); }
function exportBudgetExcel(){ exportToCSV(['التاريخ','الوصف','الفئة','اللجنة','النوع','المبلغ'],State.getTransactions().map(t=>{ const c=State.getCommittees().find(x=>x.id===t.committee); return [t.date,t.desc,t.category,c?c.name:'عام',t.type,t.amount]; }),'المعاملات_المالية'); toast('تم التصدير 📊'); }

// =================== DASHBOARD ===================
function renderDashboard(){
  const inc=State.getTransactions().filter(t=>t.type==='إيراد').reduce((s,t)=>s+t.amount,0);
  const exp=State.getTransactions().filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  document.getElementById('d-balance').textContent=fmt(inc-exp);
  document.getElementById('d-members').textContent=State.getMembers().filter(m=>m.status==='نشط').length;
  document.getElementById('d-total').textContent=State.getMembers().length;
  document.getElementById('d-committees').textContent=State.getCommittees().length;
  const p=curPeriod(); const pays=p?State.getPayments().filter(x=>x.periodId===p.id):[];
  const paid=pays.filter(x=>x.status==='مدفوع'); const unpaid=pays.filter(x=>x.status==='لم يدفع'); const exempt=pays.filter(x=>x.status==='معفي');
  document.getElementById('d-unpaid').textContent=unpaid.length;
  document.getElementById('d-period-lbl').textContent=p?p.name:'لا دورة';
  const pct=pays.length>0?Math.round(paid.length/pays.length*100):0;
  document.getElementById('d-pct').textContent=pct+'%';
  document.getElementById('d-bar').style.width=pct+'%';
  document.getElementById('d-paid').textContent=paid.length;
  document.getElementById('d-pending').textContent=unpaid.length;
  document.getElementById('d-exempt').textContent=exempt.length;
  const recent=[...State.getTransactions()].sort((a,b)=>b.date.localeCompare(a.date)).slice(0,5);
  document.getElementById('d-recent').innerHTML=recent.length?recent.map(tx=>`<div style="display:flex;align-items:center;gap:10px;padding:9px 18px;border-bottom:1px solid #f0f5f1"><div style="width:34px;height:34px;border-radius:10px;background:${tx.type==='إيراد'?'#dcfce7':'#fee2e2'};display:flex;align-items:center;justify-content:center;font-size:14px">${tx.type==='إيراد'?'⬆️':'⬇️'}</div><div style="flex:1"><div style="font-size:13px;font-weight:600">${tx.desc}</div><div style="font-size:11px;color:var(--text-muted)">${tx.date} · ${tx.category}</div></div><div style="font-weight:700;color:${tx.type==='إيراد'?'var(--green)':'var(--danger)'}">${tx.type==='إيراد'?'+':'-'}${fmt(tx.amount)}</div></div>`).join(''):'<div class="empty-state"><div class="empty-icon">📋</div><p>لا معاملات</p></div>';
  const upcoming=State.getEvents().filter(e=>e.status==='قادم'||e.status==='جاري').slice(0,4);
  document.getElementById('d-events').innerHTML=upcoming.length?upcoming.map(ev=>{ const c=State.getCommittees().find(x=>x.id===ev.committeeId); return `<div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #f0f5f1"><div style="width:38px;height:38px;border-radius:10px;background:${c?c.color:'linear-gradient(135deg,var(--green-dark),var(--green))'};display:flex;align-items:center;justify-content:center;font-size:16px">${ev.icon||'🎉'}</div><div style="flex:1"><div style="font-size:13px;font-weight:600">${ev.name}</div><div style="font-size:11px;color:var(--text-muted)">${ev.date||'—'}</div></div><span class="badge ${sBadge(ev.status)}">${ev.status}</span></div>`; }).join(''):'<div class="empty-state"><div class="empty-icon">🗓️</div><p>لا فعاليات قادمة</p></div>';
  const activePolls=State.getPolls().filter(x=>x.active).slice(0,3);
  document.getElementById('d-polls').innerHTML=activePolls.length?activePolls.map(poll=>{ const total=poll.options.reduce((s,o)=>s+o.votes.length,0); return `<div style="padding:9px 0;border-bottom:1px solid #f0f5f1"><div style="font-size:13px;font-weight:600">${poll.title}</div><div style="font-size:11px;color:var(--text-muted)">${total} صوت</div></div>`; }).join(''):'<div class="empty-state"><div class="empty-icon">🗳️</div><p>لا تصويتات نشطة</p></div>';
}

// =================== REPORTS ===================
// =====================================================
// AI SMART REPORTS - تحليل ذكي بالذكاء الاصطناعي
// =====================================================

var aiAnalysisData = null;

function handleAIFileUpload(event) {
  var file = event.target.files[0];
  if (!file) return;

  // التحقق من نوع الملف
  var validTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
  if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/i)) {
    alert('نوع الملف غير مدعوم. يرجى رفع ملف Excel أو CSV');
    return;
  }

  // التحقق من حجم الملف (10MB)
  if (file.size > 10 * 1024 * 1024) {
    alert('حجم الملف يتجاوز 10 ميجابايت');
    return;
  }

  // عرض حالة المعالجة
  document.getElementById('upload-area').style.display = 'none';
  document.getElementById('ai-processing').style.display = 'block';
  document.getElementById('ai-results').style.display = 'none';
  document.getElementById('ai-status-text').textContent = 'جاري قراءة الملف...';
  document.getElementById('ai-progress-bar').style.width = '20%';

  // قراءة الملف
  var reader = new FileReader();
  reader.onload = function(e) {
    try {
      var data = new Uint8Array(e.target.result);
      var workbook = XLSX.read(data, {type: 'array'});
      
      // الحصول على أول ورقة
      var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
      var jsonData = XLSX.utils.sheet_to_json(firstSheet, {raw: false});

      if (!jsonData || jsonData.length === 0) {
        alert('الملف فارغ أو لا يحتوي على بيانات');
        resetAIAnalysis();
        return;
      }

      // بدء التحليل
      document.getElementById('ai-status-text').textContent = 'جاري التحليل الذكي...';
      document.getElementById('ai-progress-bar').style.width = '50%';

      setTimeout(function() {
        analyzeWithAI(jsonData);
      }, 1000);

    } catch (error) {
      console.error('خطأ في قراءة الملف:', error);
      alert('حدث خطأ في قراءة الملف: ' + error.message);
      resetAIAnalysis();
    }
  };

  reader.onerror = function() {
    alert('فشل قراءة الملف');
    resetAIAnalysis();
  };

  reader.readAsArrayBuffer(file);
}

function analyzeWithAI(rawData) {
  document.getElementById('ai-status-text').textContent = 'جاري التصنيف الذكي...';
  document.getElementById('ai-progress-bar').style.width = '70%';

  var transactions = [];
  var categories = {};
  var totalIncome = 0;
  var totalExpense = 0;

  // استخراج وتحليل البيانات
  for (var i = 0; i < rawData.length; i++) {
    var row = rawData[i];
    
    // اكتشاف الأعمدة (مرن)
    var description = row['الوصف'] || row['البيان'] || row['Description'] || row['وصف'] || row['تفاصيل'] || '';
    var amountStr = row['المبلغ'] || row['القيمة'] || row['Amount'] || row['Value'] || row['مبلغ'] || '0';
    var dateStr = row['التاريخ'] || row['Date'] || row['تاريخ'] || '';
    var typeStr = row['النوع'] || row['Type'] || row['نوع'] || '';

    // تنظيف المبلغ
    var amount = parseFloat(String(amountStr).replace(/[^\d.-]/g, '')) || 0;
    if (amount === 0) continue;

    // تحديد النوع (إيراد/مصروف)
    var type = 'expense';
    var lowerType = String(typeStr).toLowerCase();
    var lowerDesc = String(description).toLowerCase();
    
    if (lowerType.includes('دخل') || lowerType.includes('إيراد') || lowerType.includes('income') || 
        lowerType.includes('دائن') || lowerType.includes('credit') ||
        lowerDesc.includes('اشتراك') || lowerDesc.includes('تبرع')) {
      type = 'income';
    }

    // التصنيف الذكي بناءً على الوصف
    var category = smartCategorize(description, type);
    
    // إضافة المعاملة
    var transaction = {
      date: dateStr || new Date().toISOString().split('T')[0],
      description: description,
      amount: Math.abs(amount),
      type: type,
      category: category
    };
    
    transactions.push(transaction);

    // تجميع حسب الفئة
    if (!categories[category]) {
      categories[category] = {income: 0, expense: 0, count: 0};
    }
    categories[category].count++;
    
    if (type === 'income') {
      totalIncome += Math.abs(amount);
      categories[category].income += Math.abs(amount);
    } else {
      totalExpense += Math.abs(amount);
      categories[category].expense += Math.abs(amount);
    }
  }

  // حفظ النتائج
  aiAnalysisData = {
    transactions: transactions,
    categories: categories,
    totalIncome: totalIncome,
    totalExpense: totalExpense,
    netProfit: totalIncome - totalExpense,
    analyzedAt: new Date().toISOString()
  };

  // إنهاء المعالجة
  document.getElementById('ai-progress-bar').style.width = '100%';
  
  setTimeout(function() {
    displayAIResults();
  }, 500);
}

function smartCategorize(description, type) {
  var desc = String(description).toLowerCase();
  
  // قاموس الكلمات المفتاحية
  var keywords = {
    'رواتب': ['راتب', 'مرتب', 'أجر', 'salary', 'wage'],
    'إيجار': ['إيجار', 'rent', 'lease'],
    'مرافق': ['كهرباء', 'ماء', 'غاز', 'electricity', 'water', 'utility'],
    'تسويق': ['تسويق', 'إعلان', 'marketing', 'advertising', 'دعاية'],
    'قرطاسية': ['قرطاسية', 'مكتب', 'طباعة', 'office', 'supplies', 'stationery'],
    'صيانة': ['صيانة', 'إصلاح', 'maintenance', 'repair'],
    'اشتراكات': ['اشتراك', 'عضوية', 'subscription', 'membership'],
    'تبرعات': ['تبرع', 'donation', 'هبة'],
    'مبيعات': ['مبيعات', 'sales'],
    'خدمات': ['خدمة', 'service']
  };

  for (var category in keywords) {
    var words = keywords[category];
    for (var i = 0; i < words.length; i++) {
      if (desc.includes(words[i])) {
        return category;
      }
    }
  }

  return type === 'income' ? 'مبيعات' : 'أخرى';
}

function displayAIResults() {
  document.getElementById('ai-processing').style.display = 'none';
  document.getElementById('ai-results').style.display = 'block';

  var data = aiAnalysisData;

  // عرض الإحصائيات
  document.getElementById('ai-total-count').textContent = data.transactions.length.toLocaleString();
  document.getElementById('ai-total-income').textContent = data.totalIncome.toLocaleString('ar-SA', {maximumFractionDigits: 0});
  document.getElementById('ai-total-expense').textContent = data.totalExpense.toLocaleString('ar-SA', {maximumFractionDigits: 0});
  document.getElementById('ai-net-profit').textContent = data.netProfit.toLocaleString('ar-SA', {maximumFractionDigits: 0});

  // تغيير لون صافي الربح
  var profitEl = document.getElementById('ai-net-profit');
  profitEl.style.color = data.netProfit >= 0 ? 'var(--green)' : 'var(--red)';

  // إنشاء رؤى ذكية
  var insights = generateAIInsights(data);
  document.getElementById('ai-insights').innerHTML = insights;

  // عرض التصنيفات
  var categoriesHTML = '<div style="display:grid;gap:12px">';
  var sortedCategories = Object.keys(data.categories).sort(function(a, b) {
    var totalA = data.categories[a].income + data.categories[a].expense;
    var totalB = data.categories[b].income + data.categories[b].expense;
    return totalB - totalA;
  });

  for (var i = 0; i < sortedCategories.length; i++) {
    var cat = sortedCategories[i];
    var catData = data.categories[cat];
    var total = catData.income + catData.expense;
    var percentage = ((total / (data.totalIncome + data.totalExpense)) * 100).toFixed(1);

    categoriesHTML += '<div style="padding:12px;background:#f8f9fa;border-radius:8px">';
    categoriesHTML += '<div style="display:flex;justify-content:space-between;margin-bottom:8px">';
    categoriesHTML += '<div style="font-weight:700">' + cat + '</div>';
    categoriesHTML += '<div style="color:var(--green)">' + total.toLocaleString('ar-SA', {maximumFractionDigits: 0}) + ' ريال</div>';
    categoriesHTML += '</div>';
    categoriesHTML += '<div style="font-size:12px;color:var(--text-muted)">عدد المعاملات: ' + catData.count + ' • نسبة: ' + percentage + '%</div>';
    categoriesHTML += '</div>';
  }
  categoriesHTML += '</div>';

  document.getElementById('ai-categories').innerHTML = categoriesHTML;
}

function generateAIInsights(data) {
  var insights = '<div style="background:#f0f9ff;border-right:4px solid #3b82f6;padding:16px;border-radius:8px;margin-bottom:16px">';
  insights += '<div style="font-weight:700;color:#1e40af;margin-bottom:8px">📊 الملخص التنفيذي</div>';
  insights += '<div style="font-size:14px;color:#1e3a8a;line-height:1.8">';
  
  insights += 'تم تحليل <strong>' + data.transactions.length + '</strong> معاملة مالية ';
  insights += 'بإجمالي إيرادات <strong>' + data.totalIncome.toLocaleString('ar-SA', {maximumFractionDigits: 0}) + '</strong> ريال ';
  insights += 'ومصروفات <strong>' + data.totalExpense.toLocaleString('ar-SA', {maximumFractionDigits: 0}) + '</strong> ريال، ';
  
  if (data.netProfit >= 0) {
    insights += 'بصافي ربح قدره <strong style="color:var(--green)">' + data.netProfit.toLocaleString('ar-SA', {maximumFractionDigits: 0}) + '</strong> ريال.';
  } else {
    insights += 'بعجز قدره <strong style="color:var(--red)">' + Math.abs(data.netProfit).toLocaleString('ar-SA', {maximumFractionDigits: 0}) + '</strong> ريال.';
  }
  
  insights += '</div></div>';

  // أكبر فئة مصروفات
  var largestExpense = null;
  var largestAmount = 0;
  for (var cat in data.categories) {
    if (data.categories[cat].expense > largestAmount) {
      largestAmount = data.categories[cat].expense;
      largestExpense = cat;
    }
  }

  if (largestExpense) {
    insights += '<div style="background:#fef3c7;border-right:4px solid #f59e0b;padding:16px;border-radius:8px;margin-bottom:16px">';
    insights += '<div style="font-weight:700;color:#92400e;margin-bottom:8px">💡 ملاحظة مهمة</div>';
    insights += '<div style="font-size:14px;color:#78350f">أكبر بند مصروفات هو <strong>' + largestExpense + '</strong> ';
    insights += 'بقيمة <strong>' + largestAmount.toLocaleString('ar-SA', {maximumFractionDigits: 0}) + '</strong> ريال ';
    insights += '(' + ((largestAmount / data.totalExpense) * 100).toFixed(1) + '% من إجمالي المصروفات).</div>';
    insights += '</div>';
  }

  // توصية
  insights += '<div style="background:#dcfce7;border-right:4px solid #22c55e;padding:16px;border-radius:8px">';
  insights += '<div style="font-weight:700;color:#166534;margin-bottom:8px">✅ توصية</div>';
  insights += '<div style="font-size:14px;color:#14532d">';
  insights += 'تم تصنيف المعاملات تلقائياً بنسبة ثقة عالية. ';
  insights += 'يمكنك مراجعة التصنيفات أدناه ثم مزامنة البيانات مع النظام مباشرة.';
  insights += '</div></div>';

  return insights;
}

function syncAIDataToDB() {
  if (!aiAnalysisData) {
    alert('لا توجد بيانات للمزامنة');
    return;
  }

  if (!confirm('هل تريد مزامنة ' + aiAnalysisData.transactions.length + ' معاملة مع قاعدة البيانات؟')) {
    return;
  }

  var synced = 0;
  var duplicates = 0;

  // إضافة المعاملات إلى قاعدة البيانات
  for (var i = 0; i < aiAnalysisData.transactions.length; i++) {
    var tx = aiAnalysisData.transactions[i];
    
    // التحقق من التكرار (بسيط)
    var isDuplicate = false;
    for (var j = 0; j < State.getBudget().length; j++) {
      var existing = State.getBudget()[j];
      if (existing.description === tx.description && 
          Math.abs(existing.amount - tx.amount) < 0.01 &&
          existing.date === tx.date) {
        isDuplicate = true;
        duplicates++;
        break;
      }
    }

    if (!isDuplicate) {
      State.getBudget().push({
        id: uid(),
        type: tx.type,
        category: tx.category,
        amount: tx.amount,
        description: tx.description,
        date: tx.date,
        createdAt: new Date().toISOString()
      });
      synced++;
    }
  }

  saveDB();
  
  var message = 'تمت المزامنة بنجاح!\n\n';
  message += '✅ تمت إضافة: ' + synced + ' معاملة\n';
  if (duplicates > 0) {
    message += '⚠️ تم تجاهل: ' + duplicates + ' معاملة مكررة';
  }
  
  alert(message);
  
  // تحديث لوحة التحكم
  renderDashboard();
}

function downloadAIReport() {
  if (!aiAnalysisData) {
    alert('لا توجد بيانات لتحميلها');
    return;
  }

  // إنشاء CSV
  var csv = 'التاريخ,الوصف,المبلغ,النوع,الفئة\n';
  for (var i = 0; i < aiAnalysisData.transactions.length; i++) {
    var tx = aiAnalysisData.transactions[i];
    csv += '"' + tx.date + '","' + tx.description + '",' + tx.amount + ',"' + tx.type + '","' + tx.category + '"\n';
  }

  // تحميل
  var blob = new Blob(['\uFEFF' + csv], {type: 'text/csv;charset=utf-8;'});
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'ai-report-' + Date.now() + '.csv';
  link.click();
}

function resetAIAnalysis() {
  aiAnalysisData = null;
  document.getElementById('upload-area').style.display = 'block';
  document.getElementById('ai-processing').style.display = 'none';
  document.getElementById('ai-results').style.display = 'none';
  document.getElementById('file-input-ai').value = '';
  document.getElementById('ai-progress-bar').style.width = '0%';
}

// =====================================================

function renderReports(){
  const expByCat={}; State.getTransactions().filter(t=>t.type==='مصروف').forEach(t=>{ expByCat[t.category]=(expByCat[t.category]||0)+t.amount; });
  const colors=['#47915C','#1B3456','#c8a84b','#e67e22','#8e44ad','#c0392b','#117a65','#2980b9'];
  const total=Object.values(expByCat).reduce((s,v)=>s+v,0);
  const cats=Object.entries(expByCat);
  const donutEl=document.getElementById('report-donut');
  if(!cats.length){donutEl.innerHTML='<div class="empty-state"><div class="empty-icon">📊</div><p>لا بيانات</p></div>';}
  else{ const circ=2*Math.PI*50; let offset=0; const segs=cats.map(([cat,val],i)=>{ const pct=val/total; const dash=pct*circ; const seg=`<circle cx="65" cy="65" r="50" fill="none" stroke="${colors[i%colors.length]}" stroke-width="18" stroke-dasharray="${dash} ${circ-dash}" stroke-dashoffset="${-offset*circ}" transform="rotate(-90 65 65)"/>`; offset+=pct; return seg; }).join(''); donutEl.innerHTML=`<div style="display:flex;align-items:center;gap:14px"><svg width="130" height="130" viewBox="0 0 130 130"><circle cx="65" cy="65" r="50" fill="none" stroke="#e4ede6" stroke-width="18"/>${segs}<text x="65" y="68" text-anchor="middle" fill="var(--green-dark)" font-size="10" font-weight="700" font-family="Cairo">${fmt(total)}</text><text x="65" y="80" text-anchor="middle" fill="var(--text-muted)" font-size="8" font-family="Cairo">ريال</text></svg><div>${cats.map(([cat,val],i)=>`<div style="display:flex;align-items:center;gap:6px;margin-bottom:5px"><div style="width:10px;height:10px;border-radius:50%;background:${colors[i%colors.length]}"></div><div><div style="font-size:12px;font-weight:600">${cat}</div><div style="font-size:11px;color:var(--text-muted)">${fmt(val)} (${Math.round(val/total*100)}%)</div></div></div>`).join('')}</div></div>`; }
  const income=State.getTransactions().filter(t=>t.type==='إيراد').reduce((s,t)=>s+t.amount,0);
  const expense=State.getTransactions().filter(t=>t.type==='مصروف').reduce((s,t)=>s+t.amount,0);
  const mx=Math.max(income,expense,1);
  document.getElementById('report-compare').innerHTML=`<div style="margin-bottom:10px"><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:12px">الإيرادات</span><span style="font-weight:700;color:var(--green)">${fmt(income)} ريال</span></div><div class="progress-bar"><div class="progress-fill" style="width:${income/mx*100}%"></div></div></div><div style="margin-bottom:10px"><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:12px">المصاريف</span><span style="font-weight:700;color:var(--danger)">${fmt(expense)} ريال</span></div><div class="progress-bar"><div class="progress-fill" style="width:${expense/mx*100}%;background:linear-gradient(90deg,var(--danger),#e74c3c)"></div></div></div><div style="border-top:1px solid var(--border);padding-top:10px;display:flex;justify-content:space-between"><span style="font-weight:600">صافي الرصيد</span><span style="font-weight:900;font-size:18px;color:${income-expense>=0?'var(--green)':'var(--danger)'}">${fmt(income-expense)} ريال</span></div>`;
  const byStatus={}; State.getMembers().forEach(m=>{ byStatus[m.status]=(byStatus[m.status]||0)+1; });
  document.getElementById('report-members').innerHTML=`<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px">${Object.entries(byStatus).map(([s,v])=>`<div style="background:var(--bg);border-radius:8px;padding:10px;text-align:center"><div style="font-size:18px;font-weight:900;color:var(--green-dark)">${v}</div><div style="font-size:11px;color:var(--text-muted)">${s}</div></div>`).join('')}</div>`;
  const ptot={}; State.getPayments().filter(p=>p.status==='مدفوع').forEach(p=>{ ptot[p.memberId]=(ptot[p.memberId]||0)+p.amount; });
  const top=Object.entries(ptot).sort((a,b)=>b[1]-a[1]).slice(0,5);
  document.getElementById('report-top-payers').innerHTML=top.length?top.map(([id,tot],i)=>{ const m=State.getMembers().find(x=>x.id===id); if(!m) return ''; return `<div style="display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid #f0ebe0"><div style="font-size:15px;color:var(--accent);width:20px;font-weight:900">${i+1}</div><div class="avatar" style="background:${avColor(m.name)}">${avInit(m.name)}</div><div style="flex:1"><div style="font-weight:600;font-size:13px">${m.name}</div><div style="font-size:11px;color:var(--text-muted)">${m.family}</div></div><div style="font-weight:700;color:var(--green)">${fmt(tot)} ريال</div></div>`; }).join(''):'<div class="empty-state"><div class="empty-icon">🏆</div><p>لا بيانات</p></div>';
  document.getElementById('report-committees-tbody').innerHTML=State.getCommittees().map(c=>{ const mems=(State.getCommitteeMembers()[c.id]||c.members||[]).length; const evs=State.getEvents().filter(e=>e.committeeId===c.id).length; const spent=State.getTransactions().filter(t=>t.committee===c.id&&t.type==='مصروف').reduce((s,t)=>s+t.amount,0); return `<tr><td>${c.icon} ${c.name} ${c.advisory?'<span class="badge badge-purple">استشارية</span>':''}</td><td>${mems}</td><td>${evs}</td><td style="font-weight:700;color:var(--danger)">${fmt(spent)} ريال</td></tr>`; }).join('');
}

function switchTab(id,el){ document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active')); document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active')); document.getElementById(id).classList.add('active'); el.classList.add('active'); renderReports(); }

// =================== SAMPLE DATA ===================
function loadSampleData(){
  if(State.getMembers().length) return;
  const names=[['منصور علي العوامي','العوامي','0501111111'],['حسين عبدالحميد العوامي','العوامي','0502222222'],['عبدالله عماد العوامي','العوامي','0503333333'],['محمود حسن العوامي','العوامي','0504444444'],['راضي ابراهيم العوامي','العوامي','0505555555'],['رضا حسين العوامي','العوامي','0506666666'],['مجتبى سلمان العوامي','العوامي','0507777777'],['حسن علي العوامي','العوامي','0508888888'],['أحمد غازي العوامي','العوامي','0509999999'],['عماد عبدالحميد العوامي','العوامي','0501010101']];
  names.forEach(([name,family,phone],i)=>{ State.getMembers().push({id:'m'+i,name,family,phone,idNum:`1${100000000+i}`,joinDate:`2023-0${(i%9)+1}-01`,status:i===7?'معفي':'نشط',notes:''}); });
  const p={id:'p1',name:'الدورة الأولى 2025',feeAmount:400,start:'2025-01-01',end:'2025-06-30'};
  State.getPeriods().push(p);
  State.getMembers().forEach((m,i)=>{ const s=m.status==='معفي'?'معفي':i<7?'مدفوع':'لم يدفع'; State.getPayments().push({id:'pay'+i,memberId:m.id,periodId:p.id,amount:s==='مدفوع'?400:0,required:400,date:s==='مدفوع'?`2025-0${(i%6)+1}-15`:'',method:'تحويل بنكي',status:s,notes:''}); });
  [{type:'إيراد',amount:2800,category:'رسوم الأعضاء',committee:'',desc:'رسوم الأعضاء الدورة الأولى 2025',date:'2025-01-15'},{type:'مصروف',amount:8000,category:'رحلة العمرة',committee:'c1',desc:'تكاليف رحلة العمرة الرجبية',date:'2025-02-10'},{type:'مصروف',amount:3200,category:'غداء العيد',committee:'c2',desc:'غداء عيد الفطر',date:'2025-04-11'},{type:'إيراد',amount:1200,category:'تبرعات',committee:'',desc:'تبرع أحد الأعضاء',date:'2025-01-20'},{type:'مصروف',amount:500,category:'مصاريف إدارية',committee:'',desc:'مصاريف إدارية',date:'2025-03-05'}].forEach((t,i)=>State.getTransactions().push({id:'tx'+i,...t}));
  [{name:'رحلة العمرة الرجبية 2025',committeeId:'c1',status:'مكتمل',date:'2025-02-10',budget:8000,participants:15,lead:'عبدالله عماد',icon:'🕋',images:[]},{name:'غداء عيد الفطر',committeeId:'c2',status:'مكتمل',date:'2025-04-11',budget:3200,participants:40,lead:'محمود حسن',icon:'🍖',images:[]},{name:'المسابقة الرمضانية',committeeId:'c3',status:'قادم',date:'2025-03-01',budget:2000,participants:30,icon:'🌙',images:[]},{name:'رحلة الباحة الترفيهية',committeeId:'c4',status:'قادم',date:'2025-09-01',budget:5000,participants:25,icon:'🎡',images:[]},{name:'ليلة القدر',committeeId:'c5',status:'قادم',date:'2025-04-25',budget:1500,participants:35,icon:'✨',images:[]}].forEach((e,i)=>State.getEvents().push({id:'ev'+i,...e,type:'أخرى',notes:''}));
  State.setCommitteeMembers({'c1':['m2','m0'],'c2':['m3','m4'],'c3':['m0','m6'],'c4':['m3'],'c8':['m1','m2'],'c10':['m0','m7']});
  State.getPolls().push({id:'poll1',title:'هل توافق على رفع قيمة الاشتراك من 300 إلى 400 ريال؟',options:[{text:'نعم، أوافق',votes:['u1','u2','u3','u4']},{text:'لا، أرفض',votes:['u5']},{text:'محايد',votes:['u6']}],committee:'',end:'2025-07-01',active:true,created:'2025-06-01'});
  
  // Sample next meeting
  const nextMonth = new Date();
  nextMonth.setMonth(nextMonth.getMonth() + 1);
  nextMonth.setDate(15);
  State.setNextMeetingObj({date:nextMonth.toISOString().split('T')[0], title:'الجلسة العمومية للمجلس'});
  
  // Real family branches from PDF
  State.setFamilyBranches([
    {id:'b_kazim',name:'آل كاظم',head:'كاظم العوامي',count:7,color:'#47915C',notes:'الفرع الرئيسي',members:['علي','محمد','علي (أبو الحجي)','محمد','علي','هاني','تيسير']},
    {id:'b_ibrahim',name:'آل إبراهيم (أبو خليل)',head:'إبراهيم (أبو خليل)',count:3,color:'#1B3456',notes:'',members:['سعيد (أبو خديجة)','عارف هنيدي','هاجر (أم وائل)']},
    {id:'b_radi',name:'آل راضي',head:'راضي',count:8,color:'#c8a84b',notes:'',members:['علي (أبو صبري)','صبري','راضي','أمين','فخري','عماد','سعيد','معين']},
    {id:'b_salman',name:'آل سلمان علي',head:'سلمان علي',count:11,color:'#8e44ad',notes:'',members:['سلمان علي','عبدالله','مصطفى','مدينة (أم علي)','فاطمة (أم حسين)','حسن','علي','وديع','زكي','محمد','مروان']},
    {id:'b_ali_haidar',name:'آل علي (أبو حيدر)',head:'علي (أبو حيدر)',count:5,color:'#2980b9',notes:'',members:['محمد (أبو عبدالله)','عبدالله','غازي','سعيد','جهاد']},
    {id:'b_ahmed',name:'آل أحمد عبد الحميد',head:'أحمد عبد الحميد (أبو عالء)',count:3,color:'#c0392b',notes:'',members:['مكي (أبو أحمد)','زهراء','زينب (أم أمجد وقع)']},
    {id:'b_naser',name:'آل ناصر العوامي',head:'ناصر العوامي',count:10,color:'#27ae60',notes:'',members:['علي','محمد','ناصر','طارق','محمود','عزيز','مكي','عبدالله','مهند','علي']},
    {id:'b_ibrahim2',name:'آل إبراهيم (أبو مصطفى)',head:'إبراهيم (أبو مصطفى)',count:5,color:'#e67e22',notes:'',members:['مصطفى','خليل','مرتضى','مجتبى','أمين']},
    {id:'b_hussein',name:'آل حسين',head:'حسين',count:0,color:'#34495e',notes:'',members:[]}
  ]);
  
  log('تم تهيئة النظام ببيانات عائلة العوامي 🚀','🎯'); saveDB();
}

// =================== INIT ===================
document.querySelectorAll('.modal-overlay').forEach(ov=>ov.addEventListener('click',e=>{ if(e.target===ov) ov.classList.remove('open'); }));

// Close mobile sidebar when clicking outside
document.addEventListener('click',e=>{
  const sidebar=document.querySelector('.sidebar');
  const toggle=document.querySelector('.mobile-toggle');
  if(window.innerWidth<=768 && sidebar.classList.contains('open')){
    if(!sidebar.contains(e.target) && !toggle.contains(e.target)){
      sidebar.classList.remove('open');
    }
  }
});

loadSampleData();
(async function initApp() {
  await MemberService.syncMembersFromAPI();
  updateSidebar();
  renderDashboard();
})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function openImportExcel(){
  document.getElementById('import-result').style.display='none';
  openModal('modal-import-excel');
}
function isValidName(n){if(!n||n.length<3)return false;var bad=['المجموع','الإجمالي','Total','Sum','إجمالي','مجموع','العدد','المبلغ','الرصيد','ريال'];for(var i=0;i<bad.length;i++){if(n.indexOf(bad[i])!==-1)return false;}if(/^\d+$/.test(n))return false;if(/^05\d{8}$/.test(n))return false;return true;}
function detectPayment(r){var a=parseFloat(r['المبلغ']||r['Amount']||r['المدفوع']||0);var st=r['الحالة']||r['Status']||'';if(a>0)return {paid:true,amount:a};if(st){var s=st.toLowerCase();if(s.indexOf('مدفوع')!==-1||s.indexOf('paid')!==-1)return {paid:true,amount:a||500};if(s.indexOf('متأخر')!==-1||s.indexOf('لم يدفع')!==-1)return {paid:false,amount:0};}return {paid:false,amount:0};}
function findMember(n){for(var i=0;i<State.getMembers().length;i++){var m=State.getMembers()[i];if(m.name.toLowerCase()===n.toLowerCase())return m;var n1=n.toLowerCase().replace(/^(أبو|أبي|أم|ابن|بن)\s+/g,'');var n2=m.name.toLowerCase().replace(/^(أبو|أبي|أم|ابن|بن)\s+/g,'');if(n1===n2)return m;}return null;}

document.getElementById('excel-import-input').onchange=function(){
  var file=this.files[0];
  if(!file){return;}
  var reader=new FileReader();
  reader.onload=function(e){
    try{
      var data=new Uint8Array(e.target.result);
      var wb=XLSX.read(data,{type:'array'});
      var ws=wb.Sheets[wb.SheetNames[0]];
      var json=XLSX.utils.sheet_to_json(ws);
      if(!json.length){toast('الملف فارغ','error');return;}
      
      var total=0,valid=0,skip=0,newM=0,upd=0,paid=0,unpaid=0;
      var skipped=[];
      
      json.forEach(function(row){
        var name=(row['الاسم']||row['Name']||row['name']||'').trim();
        total++;
        
        if(!isValidName(name)){
          skip++;
          skipped.push(name);
          return;
        }
        
        valid++;
        var pay=detectPayment(row);
        var member=findMember(name);
        
        if(member){
          upd++;
          if(row['الجوال'])member.phone=row['الجوال'];
          if(row['رقم الهوية'])member.idNum=row['رقم الهوية'];
          if(pay.paid){
            paid++;
            if(!member.payments)member.payments=[];
            member.payments.push({amount:pay.amount,date:today(),imported:true});
          }else{unpaid++;}
        }else{
          newM++;
          var newMember={
            id:uid(),
            name:name,
            phone:row['الجوال']||'',
            idNum:row['رقم الهوية']||'',
            family:'العوامي',
            joinDate:today(),
            status:'نشط',
            type:'عادي',
            notes:'مستورد',
            payments:pay.paid?[{amount:pay.amount,date:today(),imported:true}]:[]
          };
          State.getMembers().push(newMember);
          if(pay.paid){paid++;}else{unpaid++;}
        }
      });
      
      saveDB();
      
      var html='<div style="background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:14px">';
      html+='<div style="font-weight:700;color:#166534;margin-bottom:10px">✅ تم المعالجة</div>';
      html+='<div style="font-size:12px;color:#166534">📊 السجلات: '+total+' • ✅ صحيح: '+valid+' • ⚠️ متجاهل: '+skip+'<br>';
      html+='➕ جديد: '+newM+' • 🔄 محدث: '+upd+'<br>';
      html+='💰 دفعوا: '+paid+' • ⏳ لم يدفعوا: '+unpaid+'</div>';
      if(skip>0){
        html+='<div style="margin-top:8px;padding:8px;background:#fff;border-radius:6px;font-size:11px;max-height:60px;overflow:auto">';
        html+='<b>متجاهل:</b> '+skipped.join(', ')+'</div>';
      }
      html+='</div>';
      
      document.getElementById('import-result').style.display='block';
      document.getElementById('import-result').innerHTML=html;
      toast('تم معالجة '+valid+' سجل ✅');
      renderMembers();
    }catch(err){toast('خطأ في قراءة الملف','error');console.error(err);}
  };
  reader.readAsArrayBuffer(file);
};

// =====================================================
// Drag & Drop Enhancement for AI Upload
// =====================================================
(function() {
  function setupDragDrop() {
    var uploadArea = document.getElementById('upload-area');
    if (!uploadArea) return;
    
    uploadArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--green)';
      this.style.backgroundColor = '#f0fdf4';
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--border)';
      this.style.backgroundColor = 'transparent';
    });
    
    uploadArea.addEventListener('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = 'var(--border)';
      this.style.backgroundColor = 'transparent';
      
      var files = e.dataTransfer.files;
      if (files.length > 0) {
        var fileInput = document.getElementById('file-input-ai');
        fileInput.files = files;
        handleAIFileUpload({target: {files: files}});
      }
    });
  }
  
  // Setup on page load and page switches
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupDragDrop);
  } else {
    setupDragDrop();
  }
})();
</script>
<script src="core/state.js"></script>
<script src="core/services/member.service.js"></script>
<script src="core/services/finance.service.js"></script>
<script src="core/services/poll.service.js"></script>
</body>
</html>
