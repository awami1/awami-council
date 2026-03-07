<?php
/**
 * member-logout.php — نقطة تسجيل خروج العضو
 *
 * POST → تدمير جلسة العضو وإعادة التوجيه لـ /login
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../audit_helper.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    respond(405, ['error' => 'طريقة الطلب غير مسموحة']);
}

// بدء جلسة العضو
if (session_status() === PHP_SESSION_NONE) {
    session_name('awami_member');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly'  => true,
        'samesite'  => 'Strict',
    ]);
    session_start();
}

// تسجيل الخروج في سجل التدقيق (قبل تدمير الجلسة)
$memberId = $_SESSION['member_id'] ?? '';
$memberName = $_SESSION['name'] ?? '';
if ($memberId !== '') {
    logAudit('خروج عضو', 'member_auth', $memberId, $memberName);
}

// تدمير الجلسة
session_unset();

// حذف كوكي الجلسة
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']
    );
}

session_destroy();

respond(200, ['success' => true, 'redirect' => '/login']);
