<?php
/**
 * member-login.php — نقطة تسجيل دخول الأعضاء
 *
 * POST  → { awm_id, password }
 * - التحقق من الحساب المفعّل + كلمة السر
 * - Rate Limiting: 5 محاولات فاشلة → قفل 15 دقيقة
 * - إنشاء جلسة awami_member
 * - تحديث last_login
 * - إرجاع first_login flag إذا يحتاج تغيير كلمة السر
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/member-guard.php';
require_once __DIR__ . '/../audit_helper.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    respond(405, ['error' => 'طريقة الطلب غير مسموحة']);
}

$body   = bodyJson();
$awmId  = trim($body['awm_id'] ?? '');
$password = $body['password'] ?? '';

// ── التحقق من المدخلات ──
if ($awmId === '' || $password === '') {
    respond(400, ['error' => 'رقم العضوية وكلمة السر مطلوبان']);
}

if (mb_strlen($awmId) > 10 || mb_strlen($password) > 200) {
    respond(400, ['error' => 'طول المدخلات يتجاوز الحد المسموح']);
}

// التحقق من صيغة AWM-ID
if (!preg_match('/^AWM-\d{4}$/', $awmId)) {
    respond(400, ['error' => 'صيغة رقم العضوية غير صحيحة']);
}

// ── Rate Limiting — 5 محاولات فاشلة خلال 15 دقيقة ──
$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlKey  = 'member_login_' . md5($ip . '_' . $awmId);
$rlFile = sys_get_temp_dir() . '/awami_mrl_' . md5($ip . '_' . $awmId) . '.json';
$rlData = is_file($rlFile) ? json_decode((string) file_get_contents($rlFile), true) : null;

if ($rlData && ($rlData['count'] ?? 0) >= 5 && (time() - ($rlData['first'] ?? 0)) < 900) {
    $remaining = 900 - (time() - (int) $rlData['first']);
    logAudit('محاولة دخول مقفلة', 'member_auth', '', $awmId, ['ip' => $ip]);
    respond(429, ['error' => "تم تجاوز الحد الأقصى لمحاولات الدخول. حاول بعد {$remaining} ثانية"]);
}

// إعادة تعيين العداد بعد 15 دقيقة
if ($rlData && (time() - ($rlData['first'] ?? 0)) >= 900) {
    $rlData = null;
}

// ── جلب بيانات العضو ──
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT mu.id AS user_id, mu.member_id, mu.awm_id, mu.password_hash,
            mu.first_login, mu.is_active, mu.temp_token,
            m.name AS member_name
     FROM member_users mu
     JOIN members m ON m.id = mu.member_id
     WHERE mu.awm_id = :awm_id
     LIMIT 1'
);
$stmt->execute([':awm_id' => $awmId]);
$user = $stmt->fetch();

// ── التحقق من وجود الحساب ──
if (!$user) {
    recordFailedAttempt($rlFile, $rlData);
    usleep(300_000); // تأخير لمنع هجمات التخمين
    respond(401, ['error' => 'رقم العضوية أو كلمة السر غير صحيحة']);
}

// ── التحقق من تفعيل الحساب ──
if (!$user['is_active']) {
    usleep(300_000);
    respond(403, ['error' => 'الحساب غير مُفعَّل بعد. تواصل مع مدير الموقع']);
}

// ── التحقق من كلمة السر ──
// الدعم: كلمة سر عادية (بعد التفعيل) أو رمز مؤقت (أول دخول / إعادة تعيين)
$validPassword = password_verify($password, $user['password_hash']);

// إذا فشلت كلمة السر العادية — نتحقق من الرمز المؤقت (إن وجد وغير منتهي)
if (!$validPassword && !empty($user['temp_token'])) {
    if (hash_equals($user['temp_token'], $password)) {
        // التحقق من صلاحية الرمز (token_expiry)
        $stmt2 = $pdo->prepare('SELECT token_expiry FROM member_users WHERE id = :id');
        $stmt2->execute([':id' => $user['user_id']]);
        $tokenRow = $stmt2->fetch();
        $expiry = $tokenRow ? strtotime($tokenRow['token_expiry'] ?? '') : false;
        if ($expiry !== false && time() <= $expiry) {
            $validPassword = true;
            // مسح الرمز المؤقت بعد استخدامه
            $pdo->prepare('UPDATE member_users SET temp_token = NULL, token_expiry = NULL WHERE id = :id')
                ->execute([':id' => $user['user_id']]);
        }
    }
}

if (!$validPassword) {
    recordFailedAttempt($rlFile, $rlData);
    usleep(300_000);
    respond(401, ['error' => 'رقم العضوية أو كلمة السر غير صحيحة']);
}

// ── نجاح الدخول ──

// مسح عداد المحاولات الفاشلة
if (is_file($rlFile)) {
    @unlink($rlFile);
}

// تحديث last_login
$now = isSQLite() ? date('Y-m-d H:i:s') : null;
if (isSQLite()) {
    $pdo->prepare('UPDATE member_users SET last_login = :now WHERE id = :id')
        ->execute([':now' => $now, ':id' => $user['user_id']]);
} else {
    $pdo->prepare('UPDATE member_users SET last_login = NOW() WHERE id = :id')
        ->execute([':id' => $user['user_id']]);
}

// إنشاء جلسة العضو
startMemberSession();

// تجديد معرف الجلسة لمنع Session Fixation
try {
    session_regenerate_id(true);
} catch (\Throwable $e) {
    error_log('session_regenerate_id failed: ' . $e->getMessage());
}

$_SESSION['member_id']  = $user['member_id'];
$_SESSION['awm_id']     = $user['awm_id'];
$_SESSION['name']       = $user['member_name'];
$_SESSION['expires_at'] = time() + 86400; // 24 ساعة

// إنشاء CSRF token للعضو
$_SESSION['member_csrf_token'] = bin2hex(random_bytes(32));

// تسجيل الدخول في سجل التدقيق
logAudit('دخول عضو', 'member_auth', $user['member_id'], $user['member_name']);

// إرجاع البيانات مع flag تغيير كلمة السر
$response = [
    'success'      => true,
    'awm_id'       => $user['awm_id'],
    'name'         => $user['member_name'],
    'first_login'  => (bool) $user['first_login'],
    'csrf_token'   => $_SESSION['member_csrf_token'],
];

respond(200, $response);


// ── دالة مساعدة: تسجيل محاولة فاشلة ──
function recordFailedAttempt(string $rlFile, ?array $rlData): void
{
    if (!$rlData) {
        $rlData = ['count' => 1, 'first' => time()];
    } else {
        $rlData['count'] = ($rlData['count'] ?? 0) + 1;
    }
    file_put_contents($rlFile, json_encode($rlData), LOCK_EX);
}
