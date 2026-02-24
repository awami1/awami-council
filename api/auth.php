<?php
/**
 * auth.php — نقطة نهاية تسجيل الدخول / الخروج
 *
 * GET  ?action=check  → هل الجلسة نشطة؟
 * POST ?action=login  → { username, password }
 * POST ?action=logout → تدمير الجلسة
 *
 * بيانات الاعتماد تُقرأ من .env:
 *   ADMIN_USERNAME       (افتراضي: admin)
 *   ADMIN_PASSWORD_HASH  (bcrypt hash — الأولوية)
 *   ADMIN_PASSWORD       (نص عادي — إذا لم يُعيَّن hash)
 * إذا لم يُعيَّن شيء → كلمة السر الافتراضية: awami2024
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';

startAdminSession();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

// ── تحقق من حالة الجلسة ──
if ($method === 'GET' && $action === 'check') {
    respond(200, ['authenticated' => isAuthenticated()]);
}

// ── تسجيل الدخول ──
if ($method === 'POST' && $action === 'login') {
    $body     = bodyJson();
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    $envUser = getenv('ADMIN_USERNAME') ?: ($_ENV['ADMIN_USERNAME'] ?? 'admin');
    $envHash = getenv('ADMIN_PASSWORD_HASH') ?: ($_ENV['ADMIN_PASSWORD_HASH'] ?? '');
    $envPass = getenv('ADMIN_PASSWORD') ?: ($_ENV['ADMIN_PASSWORD'] ?? '');

    $valid = false;
    if ($username === $envUser) {
        if ($envHash !== '' && password_verify($password, $envHash)) {
            $valid = true;
        } elseif ($envPass !== '' && hash_equals($envPass, $password)) {
            $valid = true;
        } elseif ($envHash === '' && $envPass === '' && $password === 'awami2024') {
            // كلمة المرور الافتراضية — يُنصح بتغييرها في ملف .env
            $valid = true;
        }
    }

    if ($valid) {
        session_regenerate_id(true);
        $_SESSION['awami_admin']      = true;
        $_SESSION['awami_login_time'] = time();
        respond(200, ['success' => true]);
    }

    // تأخير بسيط لمنع هجمات التخمين
    usleep(300_000);
    respond(401, ['error' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
}

// ── تسجيل الخروج ──
if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
    respond(200, ['success' => true]);
}

respond(400, ['error' => 'طلب غير صالح']);
