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
    return isset($_SESSION['awami_admin']) && $_SESSION['awami_admin'] === true;
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
