<?php
/**
 * forgot-password.php — نقطة نسيت كلمة السر
 *
 * POST → { awm_id }
 * - نقطة عامة بدون مصادقة
 * - توليد رمز مؤقت جديد + تحديث token_expiry = 48 ساعة
 * - إلغاء كلمة السر القديمة + first_login = 1
 * - إرسال رسالة واتساب (عبر WhatsApp API إذا متوفر)
 * - Rate Limiting: 3 طلبات لكل AWM-ID خلال 24 ساعة
 * - إرجاع رسالة عامة دائماً (عدم كشف وجود الحساب)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../audit_helper.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    respond(405, ['error' => 'طريقة الطلب غير مسموحة']);
}

$body  = bodyJson();
$awmId = trim($body['awm_id'] ?? '');

// ── التحقق من المدخلات ──
if ($awmId === '') {
    respond(400, ['error' => 'رقم العضوية مطلوب']);
}

if (!preg_match('/^AWM-\d{4}$/', $awmId)) {
    respond(400, ['error' => 'صيغة رقم العضوية غير صحيحة']);
}

// ── Rate Limiting: 3 طلبات لكل AWM-ID خلال 24 ساعة ──
$rlFile = sys_get_temp_dir() . '/awami_fp_' . md5($awmId) . '.json';
$rlData = is_file($rlFile) ? json_decode((string) file_get_contents($rlFile), true) : null;

if ($rlData && ($rlData['count'] ?? 0) >= 3 && (time() - ($rlData['first'] ?? 0)) < 86400) {
    // لا نكشف تفاصيل — نرجع نفس الرسالة العامة
    logAudit('نسيت كلمة السر — تجاوز الحد', 'member_auth', '', $awmId);
    respond(200, ['success' => true, 'message' => 'إذا كان رقم العضوية مسجلاً، ستصلك رسالة واتساب']);
}

// إعادة تعيين العداد بعد 24 ساعة
if ($rlData && (time() - ($rlData['first'] ?? 0)) >= 86400) {
    $rlData = null;
}

// ── جلب بيانات الحساب ──
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT mu.id AS user_id, mu.member_id, mu.awm_id, mu.is_active,
            m.name AS member_name, m.phone
     FROM member_users mu
     JOIN members m ON m.id = mu.member_id
     WHERE mu.awm_id = :awm_id
     LIMIT 1'
);
$stmt->execute([':awm_id' => $awmId]);
$user = $stmt->fetch();

// إذا لم يوجد الحساب أو غير مفعّل — نسجل ونرجع رسالة عامة
if (!$user || !$user['is_active']) {
    recordForgotAttempt($rlFile, $rlData);
    logAudit('نسيت كلمة السر — حساب غير موجود', 'member_auth', '', $awmId);
    // تأخير ثابت لمنع timing attacks
    usleep(500_000);
    respond(200, ['success' => true, 'message' => 'إذا كان رقم العضوية مسجلاً، ستصلك رسالة واتساب']);
}

// ── توليد رمز مؤقت جديد (8 أحرف عشوائية) ──
$tempToken   = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
$tokenExpiry = date('Y-m-d H:i:s', time() + 172800); // 48 ساعة
$now         = date('Y-m-d H:i:s');

// كلمة سر مؤقتة عشوائية (لا يمكن استخدامها — العضو يجب يستخدم الرمز)
$tempHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);

// ── تحديث قاعدة البيانات ──
$stmt = $pdo->prepare(
    'UPDATE member_users
     SET temp_token = :token,
         token_expiry = :expiry,
         password_hash = :hash,
         first_login = 1,
         updated_at = :updated
     WHERE id = :id'
);
$stmt->execute([
    ':token'   => $tempToken,
    ':expiry'  => $tokenExpiry,
    ':hash'    => $tempHash,
    ':updated' => $now,
    ':id'      => $user['user_id'],
]);

// ── تسجيل المحاولة في Rate Limiting ──
recordForgotAttempt($rlFile, $rlData);

// ── إرسال رسالة واتساب ──
$message = buildWhatsAppMessage($user['member_name'], $awmId, $tempToken);
sendWhatsAppNotification($user['phone'] ?? '', $message);

// ── تسجيل في سجل التدقيق ──
logAudit('نسيت كلمة السر — رمز جديد', 'member_auth', $user['member_id'], $user['member_name']);

// ── إرجاع رسالة عامة (لا نكشف وجود الحساب) ──
respond(200, ['success' => true, 'message' => 'إذا كان رقم العضوية مسجلاً، ستصلك رسالة واتساب']);


// ════════════════════════════════════════════
// دوال مساعدة
// ════════════════════════════════════════════

/**
 * تسجيل محاولة نسيت كلمة السر في ملف Rate Limiting
 */
function recordForgotAttempt(string $rlFile, ?array $rlData): void
{
    if (!$rlData) {
        $rlData = ['count' => 1, 'first' => time()];
    } else {
        $rlData['count'] = ($rlData['count'] ?? 0) + 1;
    }
    file_put_contents($rlFile, json_encode($rlData), LOCK_EX);
}

/**
 * بناء نص رسالة واتساب لإعادة تعيين كلمة السر
 */
function buildWhatsAppMessage(string $name, string $awmId, string $token): string
{
    return "مرحباً {$name}\n\n"
         . "تم إعادة تعيين كلمة السر لحسابك في موقع\n"
         . "عائلة العوامي\n\n"
         . "رقم العضوية: {$awmId}\n"
         . "رمز الدخول الجديد: {$token}\n\n"
         . "رابط الدخول: alawami.site/login\n\n"
         . "الرمز صالح لمدة 48 ساعة\n"
         . "سيُطلب منك تغيير كلمة السر فور الدخول";
}

/**
 * إرسال رسالة واتساب عبر API (إذا متوفر)
 * حالياً: يسجل الرسالة في error_log — قابل للتوسع لاحقاً
 */
function sendWhatsAppNotification(string $phone, string $message): void
{
    if (empty($phone)) {
        error_log('WhatsApp notification skipped: no phone number');
        return;
    }

    // TODO: تكامل WhatsApp Business API
    // عند توفر API، استبدل هذا القسم بالاتصال الفعلي
    // مثال: $waApiUrl = getenv('WA_API_URL');
    //        $waApiToken = getenv('WA_API_TOKEN');

    error_log("WhatsApp notification queued for {$phone}: " . mb_substr($message, 0, 50) . '...');
}
