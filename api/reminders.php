<?php
/**
 * reminders.php — API تذكيرات الأعضاء غير الدافعين
 * GET  /api/reminders.php?period_id=X  → قائمة الأعضاء غير الدافعين مع بيانات التواصل
 * POST /api/reminders.php              → تسجيل إرسال تذكير (للتتبع)
 * GET  /api/reminders.php?history=1    → سجل التذكيرات المرسلة
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
requireAuth();

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

// ── Ensure reminders table exists ──
ensureRemindersTable($pdo);

// ── GET: unpaid members or reminder history ──
if ($method === 'GET') {

    // History mode
    if (!empty($_GET['history'])) {
        $sql = 'SELECT * FROM reminders ORDER BY sent_at DESC';
        $params = [];

        if (!empty($_GET['period_id'])) {
            $sql = 'SELECT * FROM reminders WHERE period_id = :pid ORDER BY sent_at DESC';
            $params[':pid'] = $_GET['period_id'];
        }

        $limit = min(200, max(10, (int) ($_GET['limit'] ?? 50)));
        $page  = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $countSql = str_replace('SELECT *', 'SELECT COUNT(*)', $sql);
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        respond(200, [
            'data'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    // Default: list unpaid members for current/specified period
    $periodId = $_GET['period_id'] ?? null;

    if (!$periodId) {
        // Get latest period
        $row = $pdo->query('SELECT id FROM periods ORDER BY created_at DESC LIMIT 1')->fetch();
        $periodId = $row ? $row['id'] : null;
    }

    if (!$periodId) {
        respond(200, ['data' => [], 'period' => null, 'message' => 'لا توجد دورة مفعّلة']);
    }

    // Get period info
    $pStmt = $pdo->prepare('SELECT * FROM periods WHERE id = :id LIMIT 1');
    $pStmt->execute([':id' => $periodId]);
    $period = $pStmt->fetch();

    if (!$period) {
        respond(404, ['error' => 'الدورة غير موجودة']);
    }

    // Get unpaid members with their info
    $sql = "SELECT m.id, m.name, m.phone, m.family, m.status AS member_status,
                   p.status AS pay_status, p.required, p.amount, p.id AS payment_id,
                   (SELECT COUNT(*) FROM reminders r WHERE r.member_id = m.id AND r.period_id = :pid2) AS reminder_count,
                   (SELECT MAX(r.sent_at) FROM reminders r WHERE r.member_id = m.id AND r.period_id = :pid3) AS last_reminder
            FROM payments p
            JOIN members m ON m.id = p.member_id
            WHERE p.period_id = :pid AND p.status = 'لم يدفع'
            ORDER BY m.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pid' => $periodId, ':pid2' => $periodId, ':pid3' => $periodId]);
    $unpaid = $stmt->fetchAll();

    // Cast numeric fields
    foreach ($unpaid as &$row) {
        $row['required']       = (float) ($row['required'] ?? 0);
        $row['amount']         = (float) ($row['amount'] ?? 0);
        $row['reminder_count'] = (int)   ($row['reminder_count'] ?? 0);
    }
    unset($row);

    respond(200, [
        'data'   => $unpaid,
        'period' => $period,
        'total'  => count($unpaid),
    ]);
}

// ── POST: log a reminder sent ──
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $memberId = $body['member_id'] ?? '';
    $periodId = $body['period_id'] ?? '';
    $channel  = $body['channel']   ?? 'whatsapp'; // whatsapp, sms, manual
    $message  = $body['message']   ?? '';
    $bulk     = $body['bulk']      ?? false;       // bulk reminder to all unpaid

    if ($bulk) {
        // Log reminders for all unpaid members in this period
        $stmt = $pdo->prepare(
            "SELECT p.member_id FROM payments p WHERE p.period_id = :pid AND p.status = 'لم يدفع'"
        );
        $stmt->execute([':pid' => $periodId]);
        $unpaidIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $insertStmt = $pdo->prepare(
            'INSERT INTO reminders (id, member_id, period_id, channel, message, sent_by)
             VALUES (:id, :mid, :pid, :ch, :msg, :by)'
        );

        $count = 0;
        $user = $_SESSION['awami_user'] ?? 'admin';
        foreach ($unpaidIds as $mid) {
            $insertStmt->execute([
                ':id'  => uid(),
                ':mid' => $mid,
                ':pid' => $periodId,
                ':ch'  => $channel,
                ':msg' => $message,
                ':by'  => $user,
            ]);
            $count++;
        }

        logAudit('تذكير', 'دفعة', $periodId, "تذكير جماعي ({$count} عضو) عبر {$channel}");
        respond(200, ['success' => true, 'count' => $count]);
    }

    // Single reminder
    if (!$memberId || !$periodId) {
        respond(422, ['error' => 'member_id و period_id مطلوبان']);
    }

    $user = $_SESSION['awami_user'] ?? 'admin';
    $pdo->prepare(
        'INSERT INTO reminders (id, member_id, period_id, channel, message, sent_by)
         VALUES (:id, :mid, :pid, :ch, :msg, :by)'
    )->execute([
        ':id'  => uid(),
        ':mid' => $memberId,
        ':pid' => $periodId,
        ':ch'  => $channel,
        ':msg' => $message,
        ':by'  => $user,
    ]);

    // Get member name for audit
    $mStmt = $pdo->prepare('SELECT name FROM members WHERE id = :id LIMIT 1');
    $mStmt->execute([':id' => $memberId]);
    $mRow = $mStmt->fetch();

    logAudit('تذكير', 'دفعة', $memberId, $mRow['name'] ?? '', ['channel' => $channel]);
    respond(200, ['success' => true]);
}

respond(405, ['error' => 'Method not allowed.']);


// ── Helper: create reminders table ──
function ensureRemindersTable(PDO $pdo): void
{
    static $checked = false;
    if ($checked) return;

    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS reminders (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            member_id VARCHAR(36) NOT NULL,
            period_id VARCHAR(36) NOT NULL,
            channel VARCHAR(50) NOT NULL DEFAULT 'whatsapp',
            message TEXT DEFAULT '',
            sent_by VARCHAR(100) NOT NULL DEFAULT 'admin',
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
            FOREIGN KEY (period_id) REFERENCES periods(id) ON DELETE CASCADE
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `reminders` (
            `id` VARCHAR(36) NOT NULL,
            `member_id` VARCHAR(36) NOT NULL,
            `period_id` VARCHAR(36) NOT NULL,
            `channel` VARCHAR(50) NOT NULL DEFAULT 'whatsapp',
            `message` TEXT DEFAULT NULL,
            `sent_by` VARCHAR(100) NOT NULL DEFAULT 'admin',
            `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_rem_member` (`member_id`),
            INDEX `idx_rem_period` (`period_id`),
            CONSTRAINT `fk_rem_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rem_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $checked = true;
}
