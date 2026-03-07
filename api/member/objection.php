<?php
/**
 * objection.php — نقطة تقديم اعتراض / ملاحظة من العضو
 *
 * POST → { subject, body, related_to, related_id? }
 * - محمية بـ requireMemberAuth() + verifyMemberCsrf()
 * - إنشاء سجل في member_objections بحالة 'جديد'
 * - إرسال إيميل تنبيه للمدير
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/member-guard.php';
require_once __DIR__ . '/../audit_helper.php';
require_once __DIR__ . '/../validation.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    respond(405, ['error' => 'طريقة الطلب غير مسموحة']);
}

$member = requireMemberAuth();
verifyMemberCsrf();

$body      = bodyJson();
$subject   = trim($body['subject'] ?? '');
$content   = trim($body['body'] ?? '');
$relatedTo = trim($body['related_to'] ?? 'أخرى');
$relatedId = trim($body['related_id'] ?? '');

// ── التحقق من المدخلات ──
if ($subject === '' || $content === '') {
    respond(422, ['error' => 'الموضوع والتفاصيل مطلوبان']);
}

if (mb_strlen($subject) > 200) {
    respond(422, ['error' => 'الموضوع يجب ألا يتجاوز 200 حرف']);
}

if (mb_strlen($content) > 5000) {
    respond(422, ['error' => 'التفاصيل يجب ألا تتجاوز 5000 حرف']);
}

$allowedTypes = ['دفعة', 'لجنة', 'بيانات', 'أخرى'];
if (!in_array($relatedTo, $allowedTypes, true)) {
    respond(422, ['error' => 'نوع الاعتراض غير صالح']);
}

if ($relatedId !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $relatedId)) {
    respond(422, ['error' => 'معرف الكيان غير صالح']);
}

// ── إنشاء السجل ──
$pdo = getPDO();
$id  = uid();
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare(
    'INSERT INTO member_objections (id, member_id, subject, body, related_to, related_id, status, created_at, updated_at)
     VALUES (:id, :member_id, :subject, :body, :related_to, :related_id, :status, :now, :now2)'
);
$stmt->execute([
    ':id'         => $id,
    ':member_id'  => $member['member_id'],
    ':subject'    => $subject,
    ':body'       => $content,
    ':related_to' => $relatedTo,
    ':related_id' => $relatedId ?: null,
    ':status'     => 'جديد',
    ':now'        => $now,
    ':now2'       => $now,
]);

// ── إرسال إيميل تنبيه للمدير ──
notifyAdmin($member['awm_id'], $member['name'], $subject);

// ── تسجيل في سجل التدقيق ──
logAudit('اعتراض جديد', 'member_objection', $id, $subject, [
    'member_id'  => $member['member_id'],
    'awm_id'     => $member['awm_id'],
    'related_to' => $relatedTo,
]);

respond(201, [
    'success' => true,
    'id'      => $id,
    'message' => 'تم استلام ملاحظتك بنجاح. سيتم الرد عليها في أقرب وقت',
]);


/**
 * إرسال إيميل تنبيه للمدير عند ورود اعتراض جديد
 */
function notifyAdmin(string $awmId, string $memberName, string $subject): void
{
    $adminEmail = getenv('ADMIN_EMAIL') ?: ($_ENV['ADMIN_EMAIL'] ?? '');
    if ($adminEmail === '') {
        error_log('Admin notification skipped: ADMIN_EMAIL not configured');
        return;
    }

    $emailSubject = "اعتراض جديد من {$awmId} — {$memberName}";
    $emailBody    = "ورد اعتراض جديد في موقع مجلس عائلة العوامي\n\n"
                  . "العضو: {$memberName} ({$awmId})\n"
                  . "الموضوع: {$subject}\n\n"
                  . "يرجى مراجعته من لوحة التحكم.";

    $headers = "From: noreply@alawami.site\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    $sent = @mail($adminEmail, $emailSubject, $emailBody, $headers);
    if (!$sent) {
        error_log("Failed to send admin notification email to {$adminEmail}");
    }
}
