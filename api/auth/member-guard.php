<?php
/**
 * member-guard.php — حارس مصادقة الأعضاء
 * يُضمَّن في كل endpoint خاص بالأعضاء
 * جلسة مستقلة عن جلسة الأدمن (awami_member)
 */
declare(strict_types=1);

/**
 * بدء جلسة الأعضاء بإعدادات آمنة
 */
function startMemberSession(): void
{
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
}

/**
 * التحقق من مصادقة العضو
 * - يتحقق من وجود الجلسة
 * - يتحقق من عدم انتهاء الصلاحية
 * - يجدد الصلاحية عند كل طلب ناجح (24 ساعة)
 *
 * @return array{member_id: string, awm_id: string, name: string}
 */
function requireMemberAuth(): array
{
    startMemberSession();

    // التحقق من وجود الجلسة
    if (empty($_SESSION['member_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'غير مصرح — يرجى تسجيل الدخول', 'redirect' => '/login'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    // التحقق من انتهاء الصلاحية
    if (($_SESSION['expires_at'] ?? 0) < time()) {
        session_unset();
        session_destroy();
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'انتهت الجلسة — يرجى تسجيل الدخول مجدداً', 'redirect' => '/login'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    // تجديد الصلاحية عند كل طلب ناجح (24 ساعة)
    $_SESSION['expires_at'] = time() + 86400;

    return [
        'member_id' => $_SESSION['member_id'],
        'awm_id'    => $_SESSION['awm_id'],
        'name'      => $_SESSION['name'],
    ];
}

/**
 * إنشاء أو جلب CSRF token لجلسة العضو
 */
function getMemberCsrfToken(): string
{
    startMemberSession();
    if (empty($_SESSION['member_csrf_token'])) {
        $_SESSION['member_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['member_csrf_token'];
}

/**
 * التحقق من صحة CSRF token لطلبات الكتابة (POST/PUT/DELETE)
 */
function verifyMemberCsrf(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        return;
    }
    startMemberSession();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals($_SESSION['member_csrf_token'] ?? '', $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'رمز الحماية غير صالح.'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}
