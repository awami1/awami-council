<?php
/**
 * login.php — صفحة تسجيل دخول لوحة التحكم
 */
declare(strict_types=1);

require_once __DIR__ . '/../api/auth_guard.php';

// إذا كان مسجلاً دخوله، أعد توجيهه للوحة التحكم
if (isAuthenticated()) {
    header('Location: /admin/');
    exit;
}

$expired = isset($_GET['expired']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>تسجيل الدخول — مجلس عائلة العوامي</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'Cairo',sans-serif;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(135deg,#1a3d28 0%,#1B3456 100%);
  padding:20px;
}
.login-card{
  background:#fff;
  border-radius:20px;
  padding:48px 40px;
  width:100%;
  max-width:420px;
  box-shadow:0 20px 60px rgba(0,0,0,.35);
  text-align:center;
}
.logo-wrap{
  width:72px;height:72px;
  background:linear-gradient(135deg,#47915C,#1B3456);
  border-radius:18px;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 20px;
  box-shadow:0 4px 16px rgba(71,145,92,.4);
}
.logo-wrap svg{width:40px;height:40px;fill:#fff;}
h1{font-size:22px;font-weight:900;color:#1a3d28;margin-bottom:4px;}
.subtitle{font-size:13px;color:#6b7c6e;margin-bottom:32px;}
.form-group{margin-bottom:18px;text-align:right;}
label{display:block;font-size:13px;font-weight:600;color:#2d5a30;margin-bottom:6px;}
input{
  width:100%;padding:12px 16px;
  border:1.5px solid #d4ddd6;border-radius:10px;
  font-family:'Cairo',sans-serif;font-size:14px;color:#1a2a1e;
  transition:border-color .2s,box-shadow .2s;
  outline:none;
  background:#f9fafb;
}
input:focus{border-color:#47915C;box-shadow:0 0 0 3px rgba(71,145,92,.15);background:#fff;}
.btn-login{
  width:100%;padding:13px;
  background:linear-gradient(135deg,#47915C,#2d6b40);
  color:#fff;border:none;border-radius:12px;
  font-family:'Cairo',sans-serif;font-size:15px;font-weight:700;
  cursor:pointer;transition:all .2s;margin-top:8px;
  display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-login:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(71,145,92,.4);}
.btn-login:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.error-box{
  background:#fef2f2;border:1.5px solid #fca5a5;
  color:#b91c1c;border-radius:10px;padding:12px 16px;
  font-size:13px;margin-bottom:18px;display:none;
}
.error-box.show{display:block;}
.warning-box{
  background:#fffbeb;border:1.5px solid #fcd34d;
  color:#92400e;border-radius:10px;padding:12px 16px;
  font-size:12px;margin-top:20px;line-height:1.7;
}
.spinner{
  width:18px;height:18px;border:2.5px solid rgba(255,255,255,.4);
  border-top-color:#fff;border-radius:50%;
  animation:spin .7s linear infinite;display:none;
}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="login-card">
  <div class="logo-wrap">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 2L3 7v10l9 5 9-5V7L12 2zm0 2.18L19 8.5v7l-7 3.88L5 15.5v-7L12 4.18z"/>
    </svg>
  </div>
  <h1>مجلس عائلة العوامي</h1>
  <p class="subtitle">لوحة الإدارة — تسجيل الدخول</p>

  <?php if ($expired): ?>
  <div class="error-box show">انتهت جلستك، يرجى تسجيل الدخول مجدداً</div>
  <?php endif; ?>

  <div class="error-box" id="error-msg"></div>

  <form id="login-form" onsubmit="doLogin(event)">
    <div class="form-group">
      <label for="username">اسم المستخدم</label>
      <input type="text" id="username" name="username" autocomplete="username"
             placeholder="admin" required autofocus>
    </div>
    <div class="form-group">
      <label for="password">كلمة المرور</label>
      <input type="password" id="password" name="password" autocomplete="current-password"
             placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn-login" id="login-btn">
      <div class="spinner" id="spinner"></div>
      <span id="btn-text">دخول</span>
    </button>
  </form>

  <div class="warning-box">
    ⚙️ لتغيير بيانات الدخول، أضف إلى ملف <strong>.env</strong>:<br>
    <code>ADMIN_USERNAME=اسمك</code><br>
    <code>ADMIN_PASSWORD=كلمتك</code>
  </div>
</div>

<script>
async function doLogin(e) {
  e.preventDefault();
  const btn    = document.getElementById('login-btn');
  const spinner = document.getElementById('spinner');
  const btnText = document.getElementById('btn-text');
  const errBox  = document.getElementById('error-msg');

  btn.disabled   = true;
  spinner.style.display = 'block';
  btnText.textContent   = 'جارٍ التحقق...';
  errBox.classList.remove('show');

  try {
    const res = await fetch('/api/auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: document.getElementById('username').value.trim(),
        password: document.getElementById('password').value,
      }),
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.success) {
      window.location.href = '/admin/';
    } else {
      errBox.textContent = data.error || 'فشل تسجيل الدخول';
      errBox.classList.add('show');
    }
  } catch {
    errBox.textContent = 'تعذّر الاتصال بالخادم';
    errBox.classList.add('show');
  } finally {
    btn.disabled = false;
    spinner.style.display = 'none';
    btnText.textContent   = 'دخول';
  }
}
</script>
</body>
</html>
