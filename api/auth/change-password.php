<?php
/**
 * change-password.php — نقطة تغيير كلمة سر العضو
 *
 * POST → { new_password }
 * - محمية بـ requireMemberAuth()
 * - كلمة السر الجديدة: 8 أحرف minimum
 * - تشفير بـ password_hash() مع BCRYPT
 * - تحديث first_login = 0
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/member-guard.php';
require_once __DIR__ . '/../audit_helper.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    respond(405, ['error' => 'طريقة الطلب غير مسموحة']);
}

// ── حماية: مصادقة + CSRF ──
$member = requireMemberAuth();
verifyMemberCsrf();

$body        = bodyJson();
$newPassword = $body['new_password'] ?? '';

// ── التحقق من المدخلات ──
if (!is_string($newPassword) || mb_strlen($newPassword) < 8) {
    respond(422, ['error' => 'كلمة السر يجب أن تكون 8 أحرف على الأقل']);
}

if (mb_strlen($newPassword) > 200) {
    respond(422, ['error' => 'طول كلمة السر يتجاوز الحد المسموح']);
}

// ── تشفير كلمة السر الجديدة ──
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

// ── تحديث قاعدة البيانات ──
$pdo = getPDO();
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare(
    'UPDATE member_users
     SET password_hash = :hash,
         first_login = 0,
         updated_at = :updated
     WHERE member_id = :member_id'
);
$stmt->execute([
    ':hash'      => $hash,
    ':updated'   => $now,
    ':member_id' => $member['member_id'],
]);

// ── تسجيل التغيير في سجل التدقيق ──
logAudit('تغيير كلمة سر', 'member_auth', $member['member_id'], $member['name']);

respond(200, [
    'success' => true,
    'message' => 'تم تغيير كلمة السر بنجاح',
]);
