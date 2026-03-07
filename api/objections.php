<?php
/**
 * objections.php — إدارة اعتراضات الأعضاء (لوحة التحكم)
 *
 * GET              → جلب جميع الاعتراضات مع بيانات العضو
 * GET ?id=X        → جلب اعتراض واحد
 * PUT ?id=X        → تحديث الحالة + كتابة رد
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';

requireAuth();
verifyCsrf();

/*
|--------------------------------------------------------------------------
| HANDLERS
|--------------------------------------------------------------------------
*/

function handleGetAll(): void
{
    $pdo = getPDO();

    $statusFilter = trim($_GET['status'] ?? '');
    $where = '';
    $params = [];

    if ($statusFilter !== '') {
        $where = ' WHERE o.status = :status';
        $params[':status'] = $statusFilter;
    }

    $sql = "SELECT o.*, m.name AS member_name, mu.awm_id
            FROM member_objections o
            JOIN members m ON m.id = o.member_id
            LEFT JOIN member_users mu ON mu.member_id = o.member_id
            {$where}
            ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // عد حسب الحالة
    $counts = ['جديد' => 0, 'قيد المراجعة' => 0, 'تمت المعالجة' => 0, 'مرفوض' => 0];
    $stmtCounts = $pdo->query(
        "SELECT status, COUNT(*) as cnt FROM member_objections GROUP BY status"
    );
    foreach ($stmtCounts->fetchAll() as $row) {
        $counts[$row['status']] = (int) $row['cnt'];
    }

    respond(200, [
        'data'   => $data,
        'total'  => count($data),
        'counts' => $counts,
    ]);
}

function handleGetOne(string $id): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare(
        "SELECT o.*, m.name AS member_name, m.phone AS member_phone, mu.awm_id
         FROM member_objections o
         JOIN members m ON m.id = o.member_id
         LEFT JOIN member_users mu ON mu.member_id = o.member_id
         WHERE o.id = :id LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $obj = $stmt->fetch();

    if (!$obj) {
        respond(404, ['error' => 'الاعتراض غير موجود']);
    }

    respond(200, ['data' => $obj]);
}

function handlePut(string $id): void
{
    $pdo  = getPDO();
    $data = bodyJson();

    // تأكد أن الاعتراض موجود
    $stmt = $pdo->prepare(
        'SELECT o.id, o.subject, o.member_id, m.name AS member_name
         FROM member_objections o
         JOIN members m ON m.id = o.member_id
         WHERE o.id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $obj = $stmt->fetch();

    if (!$obj) {
        respond(404, ['error' => 'الاعتراض غير موجود']);
    }

    $allowedStatus = ['جديد', 'قيد المراجعة', 'تمت المعالجة', 'مرفوض'];
    $newStatus = trim($data['status'] ?? '');
    $adminReply = trim($data['admin_reply'] ?? '');
    $now = date('Y-m-d H:i:s');

    $fields = [];
    $params = [':id' => $id, ':updated' => $now];

    if ($newStatus !== '' && in_array($newStatus, $allowedStatus, true)) {
        $fields[] = 'status = :status';
        $params[':status'] = $newStatus;
    }

    if ($adminReply !== '') {
        if (mb_strlen($adminReply) > 5000) {
            respond(422, ['error' => 'الرد يجب ألا يتجاوز 5000 حرف']);
        }
        $fields[] = 'admin_reply = :reply';
        $fields[] = 'replied_at = :replied';
        $params[':reply'] = $adminReply;
        $params[':replied'] = $now;
    }

    if (empty($fields)) {
        respond(422, ['error' => 'لا توجد بيانات للتحديث']);
    }

    $fields[] = 'updated_at = :updated';

    $sql = "UPDATE member_objections SET " . implode(', ', $fields) . " WHERE id = :id";
    $pdo->prepare($sql)->execute($params);

    logAudit('تحديث اعتراض', 'member_objection', $id, $obj['subject'], [
        'member_name' => $obj['member_name'],
        'new_status'  => $newStatus ?: null,
        'has_reply'   => $adminReply !== '',
    ]);

    // إرجاع السجل المحدث
    handleGetOne($id);
}

/*
|--------------------------------------------------------------------------
| ROUTER
|--------------------------------------------------------------------------
*/

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

match (true) {
    $method === 'GET'  && $id === null  => handleGetAll(),
    $method === 'GET'  && $id !== null  => handleGetOne($id),
    $method === 'PUT'  && $id !== null  => handlePut($id),
    default => respond(405, ['error' => 'Method not allowed.']),
};
