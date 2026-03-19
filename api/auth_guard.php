<?php
/**
 * auth_guard.php — حارس المصادقة
 * يُضمَّن في كل endpoint يحتاج حماية
 */
declare(strict_types=1);

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('awami_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function isAuthenticated(): bool
{
    startAdminSession();
    $auth = isset($_SESSION['awami_admin']) && $_SESSION['awami_admin'] === true;
    // فك قفل ملف الـ session فوراً لتحسين التزامن
    // (لا نحتاج كتابة على الـ session هنا)
    session_write_close();
    return $auth;
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'غير مصرح — يرجى تسجيل الدخول', 'redirect' => '/admin/login.php'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}

/**
 * إنشاء أو جلب CSRF token من الجلسة
 */
function getCsrfToken(): string
{
    startAdminSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $token = $_SESSION['csrf_token'];
    // فك قفل ملف الـ session بعد القراءة/الكتابة
    session_write_close();
    return $token;
}

/**
 * التحقق من صحة CSRF token لطلبات POST/PUT/DELETE
 */
function verifyCsrf(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        return;
    }
    startAdminSession();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $storedToken = $_SESSION['csrf_token'] ?? '';
    // فك قفل ملف الـ session فوراً لتحسين التزامن
    session_write_close();
    if (!$token || !hash_equals($storedToken, $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'CSRF token invalid.'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}
