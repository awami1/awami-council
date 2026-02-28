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
 * إذا لم يُعيَّن شيء → يُرفض تسجيل الدخول ويُطلب ضبط .env
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';

startAdminSession();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

// ── تحقق من حالة الجلسة ──
if ($method === 'GET' && $action === 'check') {
    respond(200, ['authenticated' => isAuthenticated()]);
}

// ── تسجيل الدخول ──
if ($method === 'POST' && $action === 'login') {
    // Rate limiting — 5 محاولات كحد أقصى خلال 15 دقيقة
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rlKey = 'login_attempts_' . md5($ip);
    $rlFile = sys_get_temp_dir() . '/awami_rl_' . md5($ip) . '.json';
    $rlData = is_file($rlFile) ? json_decode(file_get_contents($rlFile), true) : null;
    if ($rlData && ($rlData['count'] ?? 0) >= 5 && (time() - ($rlData['first'] ?? 0)) < 900) {
        $remaining = 900 - (time() - $rlData['first']);
        respond(429, ['error' => "تم تجاوز الحد الأقصى لمحاولات الدخول. حاول بعد {$remaining} ثانية"]);
    }
    // إعادة تعيين العداد بعد 15 دقيقة
    if ($rlData && (time() - ($rlData['first'] ?? 0)) >= 900) {
        $rlData = null;
    }

    $body     = bodyJson();
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    // حد أقصى لطول المدخلات
    if (mb_strlen($username) > 100 || mb_strlen($password) > 200) {
        respond(400, ['error' => 'طول المدخلات يتجاوز الحد المسموح']);
    }

    $envUser = getenv('ADMIN_USERNAME') ?: ($_ENV['ADMIN_USERNAME'] ?? 'admin');
    $envHash = getenv('ADMIN_PASSWORD_HASH') ?: ($_ENV['ADMIN_PASSWORD_HASH'] ?? '');
    $envPass = getenv('ADMIN_PASSWORD') ?: ($_ENV['ADMIN_PASSWORD'] ?? '');

    $valid = false;
    if ($username === $envUser) {
        if ($envHash !== '' && password_verify($password, $envHash)) {
            $valid = true;
        } elseif ($envPass !== '' && hash_equals($envPass, $password)) {
            $valid = true;
        }
        // لم يُعيَّن أي كلمة مرور — رفض تسجيل الدخول حتى يتم ضبط .env
        if (!$valid && $envHash === '' && $envPass === '') {
            error_log('SECURITY: Login attempt with no password configured. Set ADMIN_PASSWORD_HASH in .env');
            respond(503, ['error' => 'لم يتم تعيين كلمة المرور بعد. يرجى ضبط ملف .env']);
        }
    }

    if ($valid) {
        // مسح عداد المحاولات عند نجاح الدخول
        if (is_file($rlFile)) @unlink($rlFile);

        // تجديد معرف الجلسة لمنع هجمات Session Fixation
        // داخل try-catch لأن بعض بيئات PHP قد تُخفق في هذه العملية
        try {
            session_regenerate_id(true);
        } catch (\Throwable $e) {
            error_log('session_regenerate_id failed: ' . $e->getMessage());
        }
        $_SESSION['awami_admin']      = true;
        $_SESSION['awami_user']       = $username;
        $_SESSION['awami_login_time'] = time();
        logAudit('دخول', 'auth', '', $username);
        respond(200, ['success' => true]);
    }

    // تسجيل محاولة فاشلة لـ rate limiting
    if (!$rlData) {
        $rlData = ['count' => 1, 'first' => time()];
    } else {
        $rlData['count'] = ($rlData['count'] ?? 0) + 1;
    }
    file_put_contents($rlFile, json_encode($rlData), LOCK_EX);

    // تأخير بسيط لمنع هجمات التخمين
    usleep(300_000);
    respond(401, ['error' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
}

// ── تسجيل الخروج ──
if ($method === 'POST' && $action === 'logout') {
    logAudit('خروج', 'auth', '', $_SESSION['awami_user'] ?? 'admin');
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
