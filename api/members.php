<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
requireAuth();
verifyCsrf();

/*
|--------------------------------------------------------------------------
| SETUP ROUTE (MUST RUN BEFORE ANY SELECT)
|--------------------------------------------------------------------------
*/

if (isset($_GET['setup'])) {

    $pdo = getPDO();

    if (isSQLite()) {
        $tables = [
            "CREATE TABLE IF NOT EXISTS members (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                family VARCHAR(200) NOT NULL DEFAULT '',
                phone VARCHAR(20) DEFAULT NULL,
                id_num VARCHAR(20) DEFAULT NULL,
                join_date DATE DEFAULT NULL,
                status TEXT NOT NULL DEFAULT 'مشترك' CHECK(status IN ('مشترك','منقطع','غير مشترك')),
                notes TEXT,
                branch_id VARCHAR(36) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE UNIQUE INDEX IF NOT EXISTS uq_id_num ON members(id_num)",
        ];
    } else {
        $tables = [
            "CREATE TABLE IF NOT EXISTS `members` (
                `id` VARCHAR(36) NOT NULL,
                `name` VARCHAR(200) NOT NULL,
                `family` VARCHAR(200) NOT NULL DEFAULT '',
                `phone` VARCHAR(20) DEFAULT NULL,
                `id_num` VARCHAR(20) DEFAULT NULL,
                `join_date` DATE DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'مشترك',
                `notes` TEXT,
                `branch_id` VARCHAR(36) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_id_num` (`id_num`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }

    $errors = [];
    $created = [];

    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $sql, $m);
            $created[] = $m[1] ?? '?';
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }
    }

    respond(
        empty($errors) ? 200 : 500,
        [
            'status'  => empty($errors)
                ? 'SUCCESS — remove ?setup=1 from URL'
                : 'PARTIAL',
            'created' => $created,
            'errors'  => $errors,
        ]
    );
}

/*
|--------------------------------------------------------------------------
| HANDLERS
|--------------------------------------------------------------------------
*/

function handleGetAll(): void
{
    $pdo = getPDO();

    $stmt = $pdo->query('SELECT * FROM members ORDER BY name ASC');
    $members = $stmt->fetchAll();

    respond(200, [
        'data'  => $members,
        'total' => count($members),
    ]);
}

function handleGetOne(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    $member = $stmt->fetch();

    if (!$member) {
        respond(404, ['error' => 'Member not found.']);
    }

    respond(200, ['data' => $member]);
}

function validateStatus(string $status): string
{
    $allowed = ['مشترك', 'منقطع', 'غير مشترك'];
    return in_array($status, $allowed, true) ? $status : 'مشترك';
}

function handlePost(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $id = uid();

    $stmt = $pdo->prepare(
        "INSERT INTO members
        (id, name, family, phone, id_num, join_date, status, notes, branch_id)
        VALUES
        (:id, :name, :family, :phone, :id_num, :join_date, :status, :notes, :branch_id)"
    );

    try {
        $stmt->execute([
            ':id'        => $id,
            ':name'      => $data['name'] ?? '',
            ':family'    => $data['family'] ?? '',
            ':phone'     => $data['phone'] ?? '',
            ':id_num'    => !empty($data['id_num']) ? $data['id_num'] : null,
            ':join_date' => !empty($data['join_date']) ? $data['join_date'] : null,
            ':status'    => validateStatus($data['status'] ?? 'مشترك'),
            ':notes'     => $data['notes'] ?? '',
            ':branch_id' => $data['branch_id'] ?? null,
        ]);
    } catch (\PDOException $e) {
        error_log('handlePost INSERT failed: ' . $e->getMessage());
        respond(500, ['error' => 'فشل إضافة العضو.']);
    }

    logAudit('إضافة', 'عضو', $id, $data['name'] ?? '');
    handleGetOne($id);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    // السماح فقط بالحقول المعروفة لمنع حقن أسماء أعمدة عشوائية
    $allowed = ['name', 'family', 'phone', 'id_num', 'join_date', 'status', 'notes', 'branch_id'];
    $fields = [];
    $params = [':id' => $id];

    foreach ($data as $key => $value) {
        if (!in_array($key, $allowed, true)) continue;
        $fields[] = "{$key} = :{$key}";
        $params[":{$key}"] = $value;
    }

    if (empty($fields)) {
        respond(422, ['error' => 'No fields provided for update.']);
    }

    $sql = "UPDATE members SET " . implode(', ', $fields) . " WHERE id = :id";
    $pdo->prepare($sql)->execute($params);

    logAudit('تعديل', 'عضو', $id, $data['name'] ?? '', $data);
    handleGetOne($id);
}

function handleDelete(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT name FROM members WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    $pdo->prepare('DELETE FROM members WHERE id = :id')->execute([':id' => $id]);

    logAudit('حذف', 'عضو', $id, $row['name'] ?? '');
    respond(200, ['message' => 'Member deleted successfully.']);
}

function handleAddToCommittee(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $committeeId = $data['committeeId'] ?? ($data['committee_id'] ?? '');
    $memberId    = $data['memberId']    ?? ($data['member_id'] ?? '');

    if (!$committeeId || !$memberId) {
        respond(422, ['error' => 'committeeId and memberId are required.']);
    }

    // Ensure committee_members table exists
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS committee_members (
            committee_id VARCHAR(36) NOT NULL,
            member_id VARCHAR(36) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (committee_id, member_id),
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        )");
    }

    // Upsert (ignore if already exists)
    if (isSQLite()) {
        $pdo->prepare(
            'INSERT OR IGNORE INTO committee_members (committee_id, member_id) VALUES (:cid, :mid)'
        )->execute([':cid' => $committeeId, ':mid' => $memberId]);
    } else {
        $pdo->prepare(
            'INSERT IGNORE INTO committee_members (committee_id, member_id) VALUES (:cid, :mid)'
        )->execute([':cid' => $committeeId, ':mid' => $memberId]);
    }

    respond(200, ['ok' => true]);
}

function handleRemoveFromCommittee(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $committeeId = $data['committeeId'] ?? ($data['committee_id'] ?? '');
    $memberId    = $data['memberId']    ?? ($data['member_id'] ?? '');

    if (!$committeeId || !$memberId) {
        respond(422, ['error' => 'committeeId and memberId are required.']);
    }

    $pdo->prepare(
        'DELETE FROM committee_members WHERE committee_id = :cid AND member_id = :mid'
    )->execute([':cid' => $committeeId, ':mid' => $memberId]);

    respond(200, ['ok' => true]);
}

/*
|--------------------------------------------------------------------------
| MEMBER ACCOUNT: ACTIVATE + RESET PASSWORD (Admin actions)
|--------------------------------------------------------------------------
*/

/**
 * تفعيل حساب عضو — يولّد AWM-ID تسلسلي + رمز مؤقت
 * POST /api/members.php?action=activate_account
 * Body: { member_id }
 */
function handleActivateAccount(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $memberId = trim($data['member_id'] ?? '');
    if ($memberId === '') {
        respond(422, ['error' => 'معرّف العضو مطلوب']);
    }

    // تأكد أن العضو موجود
    $stmt = $pdo->prepare('SELECT id, name, phone FROM members WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $memberId]);
    $member = $stmt->fetch();

    if (!$member) {
        respond(404, ['error' => 'العضو غير موجود']);
    }

    // تأكد أن العضو ليس لديه حساب مسبق
    $stmt = $pdo->prepare('SELECT id, awm_id FROM member_users WHERE member_id = :mid LIMIT 1');
    $stmt->execute([':mid' => $memberId]);
    $existing = $stmt->fetch();

    if ($existing) {
        respond(409, ['error' => 'العضو لديه حساب مسبق برقم ' . $existing['awm_id']]);
    }

    // توليد AWM-ID تسلسلي (أعلى رقم + 1)
    $stmt = $pdo->query("SELECT awm_id FROM member_users ORDER BY awm_id DESC LIMIT 1");
    $lastAwm = $stmt->fetchColumn();
    if ($lastAwm && preg_match('/^AWM-(\d+)$/', $lastAwm, $m)) {
        $nextNum = (int) $m[1] + 1;
    } else {
        $nextNum = 1;
    }
    $awmId = 'AWM-' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

    // توليد رمز مؤقت (32 حرف hex — 16 bytes)
    $tempToken   = strtoupper(bin2hex(random_bytes(16)));
    $tokenExpiry = date('Y-m-d H:i:s', time() + 172800); // 48 ساعة
    $now         = date('Y-m-d H:i:s');

    // كلمة سر عشوائية مؤقتة (لا يمكن استخدامها — العضو يستخدم الرمز)
    $tempHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);

    $userId = uid();

    $stmt = $pdo->prepare(
        "INSERT INTO member_users (id, member_id, awm_id, password_hash, first_login, temp_token, token_expiry, is_active, created_at, updated_at)
         VALUES (:id, :mid, :awm, :hash, 1, :token, :expiry, 0, :now, :now2)"
    );
    $stmt->execute([
        ':id'     => $userId,
        ':mid'    => $memberId,
        ':awm'    => $awmId,
        ':hash'   => $tempHash,
        ':token'  => $tempToken,
        ':expiry' => $tokenExpiry,
        ':now'    => $now,
        ':now2'   => $now,
    ]);

    logAudit('تفعيل حساب عضو', 'member_auth', $memberId, $member['name'], ['awm_id' => $awmId]);

    // بناء رسالة واتساب جاهزة
    $waMessage = "مرحباً {$member['name']}\n\n"
               . "تم إنشاء حسابك في موقع عائلة العوامي\n\n"
               . "رقم العضوية: {$awmId}\n"
               . "رمز الدخول: {$tempToken}\n\n"
               . "رابط الدخول: alawami.site/login\n\n"
               . "الرمز صالح لمدة 48 ساعة\n"
               . "عند أول دخول سيُطلب منك تغيير كلمة السر";

    respond(201, [
        'message'    => 'تم إنشاء الحساب بنجاح',
        'awm_id'     => $awmId,
        'temp_token' => $tempToken,
        'member_name'=> $member['name'],
        'phone'      => $member['phone'] ?? '',
        'wa_message' => $waMessage,
    ]);
}

/**
 * إعادة تعيين كلمة سر عضو — يولّد رمز مؤقت جديد + يلغي كلمة السر القديمة
 * POST /api/members.php?action=reset_password
 * Body: { member_id }
 */
function handleResetPassword(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $memberId = trim($data['member_id'] ?? '');
    if ($memberId === '') {
        respond(422, ['error' => 'معرّف العضو مطلوب']);
    }

    // جلب بيانات الحساب
    $stmt = $pdo->prepare(
        'SELECT mu.id AS user_id, mu.awm_id, mu.is_active,
                m.name AS member_name, m.phone
         FROM member_users mu
         JOIN members m ON m.id = mu.member_id
         WHERE mu.member_id = :mid
         LIMIT 1'
    );
    $stmt->execute([':mid' => $memberId]);
    $user = $stmt->fetch();

    if (!$user) {
        respond(404, ['error' => 'لا يوجد حساب لهذا العضو']);
    }

    // توليد رمز مؤقت جديد
    $tempToken   = strtoupper(bin2hex(random_bytes(4)));
    $tokenExpiry = date('Y-m-d H:i:s', time() + 172800); // 48 ساعة
    $now         = date('Y-m-d H:i:s');

    // إلغاء كلمة السر القديمة
    $tempHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);

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

    logAudit('إعادة تعيين كلمة السر', 'member_auth', $memberId, $user['member_name']);

    // بناء رسالة واتساب
    $waMessage = "مرحباً {$user['member_name']}\n\n"
               . "تم إعادة تعيين كلمة السر لحسابك في موقع\n"
               . "عائلة العوامي\n\n"
               . "رقم العضوية: {$user['awm_id']}\n"
               . "رمز الدخول الجديد: {$tempToken}\n\n"
               . "رابط الدخول: alawami.site/login\n\n"
               . "الرمز صالح لمدة 48 ساعة\n"
               . "سيُطلب منك تغيير كلمة السر فور الدخول";

    respond(200, [
        'message'    => 'تم إعادة تعيين كلمة السر بنجاح',
        'awm_id'     => $user['awm_id'],
        'temp_token' => $tempToken,
        'member_name'=> $user['member_name'],
        'phone'      => $user['phone'] ?? '',
        'wa_message' => $waMessage,
    ]);
}

/**
 * جلب حالات حسابات الأعضاء
 * GET /api/members.php?action=accounts_status
 */
function handleGetAccountsStatus(): void
{
    $pdo = getPDO();

    $stmt = $pdo->query(
        'SELECT mu.member_id, mu.awm_id, mu.is_active, mu.last_login, mu.first_login
         FROM member_users mu'
    );
    $accounts = $stmt->fetchAll();

    $map = [];
    foreach ($accounts as $a) {
        $map[$a['member_id']] = [
            'awm_id'      => $a['awm_id'],
            'is_active'   => (int) $a['is_active'],
            'last_login'  => $a['last_login'],
            'first_login' => (int) $a['first_login'],
        ];
    }

    respond(200, ['data' => $map]);
}

/*
|--------------------------------------------------------------------------
| STATUS REVIEW: حساب الحالة التلقائي (§4.1)
|--------------------------------------------------------------------------
*/

/**
 * مراجعة حالات الأعضاء — حساب الحالة المقترحة بناءً على سجل الدفع
 * GET /api/members.php?action=review_statuses
 *
 * القواعد:
 * - مشترك: دفع آخر فترتين متتاليتين (unpaidStreak = 0 أو 1)
 * - منقطع: انقطاع فترتين بالضبط (unpaidStreak = 2)
 * - غير مشترك: انقطاع أكثر من فترتين (unpaidStreak > 2)
 * - status_override = 1: يُتجاهل (تجاوز يدوي)
 */
function handleReviewStatuses(): void
{
    $pdo = getPDO();

    // جلب الفترات مرتبة من الأقدم للأحدث
    $periods = $pdo->query('SELECT id, name FROM periods ORDER BY start_date ASC, created_at ASC')->fetchAll();
    if (count($periods) < 1) {
        respond(200, ['data' => [], 'message' => 'لا توجد فترات مالية']);
    }

    // جلب الأعضاء
    $members = $pdo->query(
        "SELECT id, name, status, status_override, status_override_note FROM members"
    )->fetchAll();

    // جلب جميع المدفوعات
    $payments = $pdo->query(
        "SELECT member_id, period_id, status FROM payments"
    )->fetchAll();

    // بناء خريطة الدفع: member_id -> { period_id -> status }
    $payMap = [];
    foreach ($payments as $p) {
        $payMap[$p['member_id']][$p['period_id']] = $p['status'];
    }

    $periodIds = array_column($periods, 'id');
    $periodCount = count($periodIds);

    $changes = [];

    foreach ($members as $m) {
        // تجاهل الأعضاء بتجاوز يدوي
        if ((int)($m['status_override'] ?? 0) === 1) {
            continue;
        }

        // حساب عدد الفترات المتتالية غير المدفوعة من الأحدث
        $unpaidStreak = 0;
        for ($i = $periodCount - 1; $i >= 0; $i--) {
            $pid = $periodIds[$i];
            $payStatus = $payMap[$m['id']][$pid] ?? 'لم يدفع';
            if ($payStatus === 'مدفوع') {
                break;
            }
            $unpaidStreak++;
        }

        // تحديد الحالة المقترحة
        if ($unpaidStreak === 0 || $unpaidStreak === 1) {
            $suggested = 'مشترك';
        } elseif ($unpaidStreak === 2) {
            $suggested = 'منقطع';
        } else {
            $suggested = 'غير مشترك';
        }

        // فقط أضف إذا الحالة مختلفة
        if ($suggested !== $m['status']) {
            $changes[] = [
                'member_id'      => $m['id'],
                'member_name'    => $m['name'],
                'current_status' => $m['status'],
                'suggested'      => $suggested,
                'unpaid_periods' => $unpaidStreak,
            ];
        }
    }

    respond(200, [
        'data'          => $changes,
        'total'         => count($changes),
        'total_periods' => $periodCount,
    ]);
}

/**
 * تطبيق تغييرات الحالة — تأكيد الكل أو فردي
 * POST /api/members.php?action=apply_statuses
 * Body: { changes: [{ member_id, new_status, override?, override_note? }] }
 */
function handleApplyStatuses(): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    $changes = $data['changes'] ?? [];
    if (empty($changes)) {
        respond(422, ['error' => 'لا توجد تغييرات للتطبيق']);
    }

    $allowedStatus = ['مشترك', 'منقطع', 'غير مشترك'];
    $applied = 0;
    $now = date('Y-m-d H:i:s');

    foreach ($changes as $c) {
        $memberId  = trim($c['member_id'] ?? '');
        $newStatus = trim($c['new_status'] ?? '');
        $override  = (int)($c['override'] ?? 0);
        $overrideNote = trim($c['override_note'] ?? '');

        if ($memberId === '' || !in_array($newStatus, $allowedStatus, true)) {
            continue;
        }

        // تحديث بيانات العضو
        if (isSQLite()) {
            $sql = "UPDATE members SET status = :status, status_override = :ovr, status_override_note = :note, updated_at = :updated WHERE id = :id";
        } else {
            $sql = "UPDATE members SET status = :status, status_override = :ovr, status_override_note = :note, updated_at = :updated WHERE id = :id";
        }

        $pdo->prepare($sql)->execute([
            ':status'  => $newStatus,
            ':ovr'     => $override,
            ':note'    => $overrideNote ?: null,
            ':updated' => $now,
            ':id'      => $memberId,
        ]);

        $applied++;
    }

    logAudit('تحديث حالات الأعضاء', 'member_status', '', '', ['count' => $applied]);

    respond(200, [
        'message' => "تم تحديث {$applied} عضو بنجاح",
        'applied' => $applied,
    ]);
}

/*
|--------------------------------------------------------------------------
| ROUTER
|--------------------------------------------------------------------------
*/

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';

match (true) {

    $method === 'POST' && $action === 'activate_account'  => handleActivateAccount(),
    $method === 'POST' && $action === 'reset_password'     => handleResetPassword(),
    $method === 'POST' && $action === 'apply_statuses'     => handleApplyStatuses(),
    $method === 'GET'  && $action === 'accounts_status'    => handleGetAccountsStatus(),
    $method === 'GET'  && $action === 'review_statuses'    => handleReviewStatuses(),
    $method === 'POST' && $action === 'add_committee'      => handleAddToCommittee(),
    $method === 'POST' && $action === 'remove_committee'   => handleRemoveFromCommittee(),
    $method === 'GET'    && $id === null => handleGetAll(),
    $method === 'GET'    && $id !== null => handleGetOne($id),
    $method === 'POST'                   => handlePost(),
    $method === 'PUT'   && $id !== null  => handlePut($id),
    $method === 'DELETE' && $id !== null => handleDelete($id),

    default => respond(405, ['error' => 'Method not allowed.']),

};
