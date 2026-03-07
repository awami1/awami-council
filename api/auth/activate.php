<?php
/**
 * activate.php — نقطة تفعيل حساب العضو
 *
 * POST → { awm_id, temp_token }
 * - التحقق من صلاحية الرمز المؤقت (48 ساعة)
 * - تفعيل الحساب: is_active = 1
 * - حذف temp_token بعد التفعيل
 * - إنشاء جلسة عضو مباشرة
 * - إرجاع first_login flag لإجبار تغيير كلمة السر
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/member-guard.php';
require_once __DIR__ . '/../audit_helper.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    respond(405, ['error' => 'طريقة الطلب غير مسموحة']);
}

$body     = bodyJson();
$awmId    = trim($body['awm_id'] ?? '');
$tempToken = trim($body['temp_token'] ?? '');

// ── التحقق من المدخلات ──
if ($awmId === '' || $tempToken === '') {
    respond(400, ['error' => 'رقم العضوية ورمز التفعيل مطلوبان']);
}

if (!preg_match('/^AWM-\d{4}$/', $awmId)) {
    respond(400, ['error' => 'صيغة رقم العضوية غير صحيحة']);
}

if (mb_strlen($tempToken) > 100) {
    respond(400, ['error' => 'طول الرمز يتجاوز الحد المسموح']);
}

// ── جلب بيانات الحساب ──
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT mu.id AS user_id, mu.member_id, mu.awm_id, mu.temp_token,
            mu.token_expiry, mu.is_active, mu.first_login,
            m.name AS member_name
     FROM member_users mu
     JOIN members m ON m.id = mu.member_id
     WHERE mu.awm_id = :awm_id
     LIMIT 1'
);
$stmt->execute([':awm_id' => $awmId]);
$user = $stmt->fetch();

if (!$user) {
    usleep(300_000);
    respond(401, ['error' => 'رقم العضوية أو رمز التفعيل غير صحيح']);
}

// ── التحقق: هل الحساب مُفعَّل مسبقاً؟ ──
if ($user['is_active']) {
    respond(400, ['error' => 'الحساب مُفعَّل مسبقاً. استخدم صفحة تسجيل الدخول']);
}

// ── التحقق من وجود الرمز المؤقت ──
if (empty($user['temp_token'])) {
    respond(400, ['error' => 'لا يوجد رمز تفعيل لهذا الحساب. تواصل مع مدير الموقع']);
}

// ── التحقق من صلاحية الرمز (48 ساعة) ──
if ($user['token_expiry'] !== null) {
    $expiry = strtotime($user['token_expiry']);
    if ($expiry !== false && time() > $expiry) {
        respond(410, ['error' => 'انتهت صلاحية رمز التفعيل. تواصل مع مدير الموقع لإصدار رمز جديد']);
    }
}

// ── التحقق من تطابق الرمز ──
if (!hash_equals($user['temp_token'], $tempToken)) {
    usleep(300_000);
    respond(401, ['error' => 'رقم العضوية أو رمز التفعيل غير صحيح']);
}

// ── تفعيل الحساب ──
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare(
    'UPDATE member_users
     SET is_active = 1,
         temp_token = NULL,
         token_expiry = NULL,
         last_login = :now,
         updated_at = :updated
     WHERE id = :id'
);
$stmt->execute([
    ':now'     => $now,
    ':updated' => $now,
    ':id'      => $user['user_id'],
]);

// ── إنشاء جلسة العضو مباشرة ──
startMemberSession();

try {
    session_regenerate_id(true);
} catch (\Throwable $e) {
    error_log('session_regenerate_id failed: ' . $e->getMessage());
}

$_SESSION['member_id']         = $user['member_id'];
$_SESSION['awm_id']            = $user['awm_id'];
$_SESSION['name']              = $user['member_name'];
$_SESSION['expires_at']        = time() + 86400;
$_SESSION['member_csrf_token'] = bin2hex(random_bytes(32));

// ── تسجيل التفعيل في سجل التدقيق ──
logAudit('تفعيل حساب عضو', 'member_auth', $user['member_id'], $user['member_name']);

// ── إرجاع النتيجة ──
respond(200, [
    'success'     => true,
    'awm_id'      => $user['awm_id'],
    'name'        => $user['member_name'],
    'first_login' => true,
    'csrf_token'  => $_SESSION['member_csrf_token'],
    'message'     => 'تم تفعيل حسابك بنجاح. يرجى تعيين كلمة سر جديدة',
]);
