<?php
/**
 * login/index.php — صفحة تسجيل دخول الأعضاء
 * صفحة مستقلة (standalone) بتصميم متوافق مع هوية الموقع
 */
declare(strict_types=1);
require_once __DIR__ . '/../api/config.php';

// إذا العضو مسجل دخول → توجيه للصفحة الشخصية
if (session_status() === PHP_SESSION_NONE) {
    session_name('awami_member');
    session_start();
}
if (!empty($_SESSION['member_id']) && ($_SESSION['expires_at'] ?? 0) >= time()) {
    header('Location: /member');
    exit;
}

// جلب إعدادات الموقع للعنوان
require_once __DIR__ . '/../includes/helpers.php';
$ws = getWS();
$siteTitle = htmlspecialchars($ws['header']['title'] ?? 'مجلس عائلة العوامي', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>تسجيل الدخول — <?= $siteTitle ?></title>
<meta name="robots" content="noindex, nofollow">
<!-- Theme: prevent flash -->
<script>
(function(){var t=localStorage.getItem('awami-theme')||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t)})();
</script>
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' rx='16' fill='%231A5C32'/%3E%3Cpath d='M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36' stroke='%23fff' stroke-width='6' stroke-linecap='round' fill='none'/%3E%3Cpath d='M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62' stroke='%23fff' stroke-width='5' stroke-linecap='round' fill='none'/%3E%3Ccircle cx='34' cy='62' r='5' fill='%23fff'/%3E%3C/svg%3E">
<style>
/* ─── متغيرات ─── */
:root {
  --green-dark: #1A5C32; --green: #3D8B37; --green-light: #e8f5ec;
  --accent: #c8a84b; --primary: #1B3456;
  --bg: #FDFCF8; --surface: #fff;
  --text: #1a2a1e; --text-muted: #546358;
  --border: #c2cec5;
  --radius: 12px; --radius-lg: 18px;
  --font-body: 'Cairo', sans-serif; --font-heading: 'Amiri', serif;
  --gradient-header: linear-gradient(135deg, #1A5C32, #0f3d22 50%, #1B3456);
}
[data-theme="dark"] {
  --green-dark: #2A7A44; --green: #4CAF6A; --green-light: #1a3d28;
  --accent: #e0c56a; --bg: #0A0F0C; --surface: #1a2a1e;
  --text: #e8f0ea; --text-muted: #8ca892; --border: #2d4a35;
  --gradient-header: linear-gradient(135deg, #0f2d18, #0a1f10 50%, #0f1a2e);
}

/* ─── Base ─── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: var(--font-body);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

/* ─── Container ─── */
.login-wrapper {
  width: 100%;
  max-width: 420px;
}

/* ─── Header ─── */
.login-header {
  text-align: center;
  margin-bottom: 32px;
}
.login-logo {
  width: 64px; height: 64px;
  margin: 0 auto 16px;
}
.login-logo svg { width: 100%; height: 100%; }
.login-header h1 {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  color: var(--green-dark);
  margin-bottom: 4px;
}
.login-header p {
  color: var(--text-muted);
  font-size: 0.9rem;
}

/* ─── Card ─── */
.login-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 32px;
  border: 1px solid var(--border);
  box-shadow: 0 4px 16px rgba(0,0,0,.08);
}

/* ─── Form ─── */
.form-group {
  margin-bottom: 20px;
}
.form-group label {
  display: block;
  font-weight: 700;
  margin-bottom: 8px;
  color: var(--green-dark);
  font-size: 14px;
}
.form-group input {
  width: 100%;
  padding: 14px 18px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  font-size: 15px;
  font-family: inherit;
  background: var(--bg);
  color: var(--text);
  transition: border-color .25s;
}
.form-group input:focus {
  outline: none;
  border-color: var(--green);
}
.form-group input[dir="ltr"] {
  text-align: left;
  letter-spacing: 2px;
}

/* ─── Button ─── */
.btn-primary {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: var(--radius);
  background: var(--gradient-header);
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  transition: opacity .25s, transform .15s;
}
.btn-primary:hover { opacity: .9; }
.btn-primary:active { transform: scale(.98); }
.btn-primary:disabled {
  opacity: .5;
  cursor: not-allowed;
}

/* ─── Alert ─── */
.alert {
  display: none;
  padding: 14px 16px;
  border-radius: var(--radius);
  text-align: center;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 20px;
}
.alert-error { background: #fee2e2; color: #991b1b; }
.alert-success { background: var(--green-light); color: var(--green-dark); }
[data-theme="dark"] .alert-error { background: #3b1111; color: #fca5a5; }
[data-theme="dark"] .alert-success { background: #1a3d28; color: #6ee7b7; }

/* ─── Links ─── */
.login-links {
  text-align: center;
  margin-top: 16px;
}
.login-links a, .login-links button {
  background: none;
  border: none;
  color: var(--green);
  font-size: 14px;
  font-family: inherit;
  cursor: pointer;
  text-decoration: underline;
}
.login-links a:hover, .login-links button:hover {
  color: var(--green-dark);
}

/* ─── Footer ─── */
.login-footer {
  text-align: center;
  margin-top: 24px;
  color: var(--text-muted);
  font-size: 13px;
}
.login-footer a {
  color: var(--green);
  text-decoration: none;
}

/* ─── Theme toggle ─── */
.theme-toggle {
  position: fixed;
  top: 16px;
  left: 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 50%;
  width: 40px; height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 18px;
  box-shadow: 0 2px 8px rgba(0,0,0,.1);
}

/* ─── Responsive ─── */
@media (max-width: 480px) {
  .login-card { padding: 24px 20px; }
  .login-header h1 { font-size: 1.3rem; }
}
</style>
</head>
<body>

<!-- زر تغيير الثيم -->
<button class="theme-toggle" onclick="toggleTheme()" title="تغيير المظهر" aria-label="تغيير المظهر">
  <span id="theme-icon">🌙</span>
</button>

<div class="login-wrapper">
  <!-- الشعار والعنوان -->
  <div class="login-header">
    <div class="login-logo">
      <svg viewBox="0 0 80 80" fill="none">
        <rect width="80" height="80" rx="16" fill="#1A5C32"/>
        <path d="M55 12 C58 8,65 10,64 18 C63 26,54 30,50 38 C46 46,48 56,42 62 C36 68,26 66,24 58 C22 50,30 44,32 36" stroke="#fff" stroke-width="6" stroke-linecap="round" fill="none"/>
        <path d="M32 36 C28 44,20 46,20 54 C20 62,28 66,34 62" stroke="#fff" stroke-width="5" stroke-linecap="round" fill="none"/>
        <circle cx="34" cy="62" r="5" fill="#fff"/>
      </svg>
    </div>
    <h1><?= $siteTitle ?></h1>
    <p>تسجيل دخول الأعضاء</p>
  </div>

  <!-- نموذج تسجيل الدخول -->
  <div class="login-card" id="login-section">
    <div class="alert" id="login-alert"></div>
    <form id="login-form" onsubmit="return handleLogin(event)">
      <div class="form-group">
        <label for="awm-id">رقم العضوية</label>
        <input type="text" id="awm-id" name="awm_id" dir="ltr" required
               placeholder="AWM-0000" maxlength="8"
               pattern="AWM-\d{4}" autocomplete="username">
      </div>
      <div class="form-group">
        <label for="password">كلمة السر / رمز التفعيل</label>
        <input type="password" id="password" name="password" required
               placeholder="أدخل كلمة السر أو رمز التفعيل" autocomplete="current-password">
      </div>
      <button type="submit" class="btn-primary" id="login-btn">تسجيل الدخول</button>
    </form>
    <div class="login-links">
      <button type="button" onclick="showForgot()">نسيت كلمة السر؟</button>
    </div>
  </div>

  <!-- نموذج نسيت كلمة السر -->
  <div class="login-card" id="forgot-section" style="display:none">
    <div class="alert" id="forgot-alert"></div>
    <form id="forgot-form" onsubmit="return handleForgot(event)">
      <div class="form-group">
        <label for="forgot-awm-id">رقم العضوية</label>
        <input type="text" id="forgot-awm-id" name="awm_id" dir="ltr" required
               placeholder="AWM-0000" maxlength="8"
               pattern="AWM-\d{4}" autocomplete="username">
      </div>
      <button type="submit" class="btn-primary" id="forgot-btn">إرسال رمز جديد</button>
    </form>
    <div class="login-links">
      <button type="button" onclick="showLogin()">العودة لتسجيل الدخول</button>
    </div>
  </div>

  <!-- نموذج تغيير كلمة السر (first_login) -->
  <div class="login-card" id="change-pw-section" style="display:none">
    <div class="alert alert-success" id="change-pw-info" style="display:block">
      يجب تعيين كلمة سر جديدة قبل المتابعة
    </div>
    <div class="alert" id="change-pw-alert"></div>
    <form id="change-pw-form" onsubmit="return handleChangePassword(event)">
      <div class="form-group">
        <label for="new-password">كلمة السر الجديدة</label>
        <input type="password" id="new-password" name="new_password" required
               minlength="8" placeholder="8 أحرف على الأقل" autocomplete="new-password">
      </div>
      <div class="form-group">
        <label for="confirm-password">تأكيد كلمة السر</label>
        <input type="password" id="confirm-password" required
               minlength="8" placeholder="أعد كتابة كلمة السر" autocomplete="new-password">
      </div>
      <button type="submit" class="btn-primary" id="change-pw-btn">تعيين كلمة السر</button>
    </form>
  </div>

  <!-- رابط الرئيسية -->
  <div class="login-footer">
    <a href="/">&larr; العودة للموقع الرئيسي</a>
  </div>
</div>

<script>
/* ═══ State ═══ */
var csrfToken = '';

/* ═══ تسجيل الدخول ═══ */
function handleLogin(e) {
  e.preventDefault();
  var btn = document.getElementById('login-btn');
  var alert = document.getElementById('login-alert');
  var awmId = document.getElementById('awm-id').value.trim().toUpperCase();
  var password = document.getElementById('password').value;

  if (!awmId || !password) return false;

  btn.disabled = true;
  btn.textContent = 'جاري التحقق...';
  alert.style.display = 'none';

  fetch('/api/auth/member-login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ awm_id: awmId, password: password })
  })
  .then(function(res) { return res.json().then(function(data) { return { status: res.status, data: data }; }); })
  .then(function(res) {
    /* ── حساب غير مُفعَّل → محاولة تفعيل تلقائي بالرمز المؤقت ── */
    if (res.status === 403) {
      return fetch('/api/auth/activate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ awm_id: awmId, temp_token: password })
      })
      .then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }); })
      .then(function(actRes) {
        if (actRes.data.error) {
          showAlert(alert, actRes.data.error, 'error');
          btn.disabled = false;
          btn.textContent = 'تسجيل الدخول';
          return;
        }
        csrfToken = actRes.data.csrf_token || '';
        document.getElementById('login-section').style.display = 'none';
        document.getElementById('change-pw-section').style.display = 'block';
      });
    }

    if (res.data.error) {
      showAlert(alert, res.data.error, 'error');
      btn.disabled = false;
      btn.textContent = 'تسجيل الدخول';
      return;
    }

    csrfToken = res.data.csrf_token || '';

    if (res.data.first_login) {
      /* إظهار نموذج تغيير كلمة السر */
      document.getElementById('login-section').style.display = 'none';
      document.getElementById('change-pw-section').style.display = 'block';
    } else {
      /* توجيه للصفحة الشخصية */
      window.location.href = '/member';
    }
  })
  .catch(function(err) {
    showAlert(alert, 'حدث خطأ في الاتصال. حاول مرة أخرى', 'error');
    btn.disabled = false;
    btn.textContent = 'تسجيل الدخول';
  });
  return false;
}

/* ═══ نسيت كلمة السر ═══ */
function handleForgot(e) {
  e.preventDefault();
  var btn = document.getElementById('forgot-btn');
  var alert = document.getElementById('forgot-alert');
  var awmId = document.getElementById('forgot-awm-id').value.trim().toUpperCase();

  if (!awmId) return false;

  btn.disabled = true;
  btn.textContent = 'جاري الإرسال...';
  alert.style.display = 'none';

  fetch('/api/auth/forgot-password.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ awm_id: awmId })
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    showAlert(alert, data.message || 'إذا كان رقم العضوية مسجلاً، ستصلك رسالة واتساب', 'success');
    btn.disabled = false;
    btn.textContent = 'إرسال رمز جديد';
  })
  .catch(function() {
    showAlert(alert, 'حدث خطأ في الاتصال. حاول مرة أخرى', 'error');
    btn.disabled = false;
    btn.textContent = 'إرسال رمز جديد';
  });
  return false;
}

/* ═══ تغيير كلمة السر ═══ */
function handleChangePassword(e) {
  e.preventDefault();
  var btn = document.getElementById('change-pw-btn');
  var alert = document.getElementById('change-pw-alert');
  var newPw = document.getElementById('new-password').value;
  var confirmPw = document.getElementById('confirm-password').value;

  if (newPw.length < 8) {
    showAlert(alert, 'كلمة السر يجب أن تكون 8 أحرف على الأقل', 'error');
    return false;
  }
  if (newPw !== confirmPw) {
    showAlert(alert, 'كلمتا السر غير متطابقتين', 'error');
    return false;
  }

  btn.disabled = true;
  btn.textContent = 'جاري التحديث...';
  alert.style.display = 'none';

  fetch('/api/auth/change-password.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({ new_password: newPw })
  })
  .then(function(res) { return res.json().then(function(data) { return { status: res.status, data: data }; }); })
  .then(function(res) {
    if (res.data.error) {
      showAlert(alert, res.data.error, 'error');
      btn.disabled = false;
      btn.textContent = 'تعيين كلمة السر';
      return;
    }
    /* نجاح → توجيه للصفحة الشخصية */
    window.location.href = '/member';
  })
  .catch(function() {
    showAlert(alert, 'حدث خطأ في الاتصال. حاول مرة أخرى', 'error');
    btn.disabled = false;
    btn.textContent = 'تعيين كلمة السر';
  });
  return false;
}

/* ═══ تبديل الأقسام ═══ */
function showForgot() {
  document.getElementById('login-section').style.display = 'none';
  document.getElementById('forgot-section').style.display = 'block';
  document.getElementById('forgot-alert').style.display = 'none';
  document.getElementById('forgot-awm-id').focus();
}
function showLogin() {
  document.getElementById('forgot-section').style.display = 'none';
  document.getElementById('login-section').style.display = 'block';
  document.getElementById('login-alert').style.display = 'none';
  document.getElementById('awm-id').focus();
}

/* ═══ Alert helper ═══ */
function showAlert(el, msg, type) {
  el.textContent = msg;
  el.className = 'alert alert-' + type;
  el.style.display = 'block';
}

/* ═══ Theme toggle ═══ */
function toggleTheme() {
  var html = document.documentElement;
  var current = html.getAttribute('data-theme');
  var next = current === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('awami-theme', next);
  document.getElementById('theme-icon').textContent = next === 'dark' ? '☀️' : '🌙';
}
(function() {
  var theme = document.documentElement.getAttribute('data-theme');
  document.getElementById('theme-icon').textContent = theme === 'dark' ? '☀️' : '🌙';
})();
</script>
</body>
</html>
