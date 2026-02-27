<?php
/**
 * committees.php — CRUD API for committees
 * GET    /api/committees.php         → List all committees
 * GET    /api/committees.php?id=X    → Single committee
 * POST   /api/committees.php         → Create committee
 * PUT    /api/committees.php?id=X    → Update committee
 * DELETE /api/committees.php?id=X    → Delete committee
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
requireAuth();

$pdo    = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

// Ensure committees table exists
ensureCommitteesTable($pdo);

// ── GET ──
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM committees WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) respond(404, ['error' => 'اللجنة غير موجودة']);

        // Get linked member count
        $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM committee_members WHERE committee_id = :cid');
        $cntStmt->execute([':cid' => $id]);
        $linkedCount = (int) $cntStmt->fetchColumn();
        $manualCount = (int) ($row['members_count'] ?? 0);
        $row['member_count'] = max($linkedCount, $manualCount);

        // Get event count
        $evStmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE committee_id = :cid');
        $evStmt->execute([':cid' => $id]);
        $row['event_count'] = (int) $evStmt->fetchColumn();

        respond(200, ['data' => $row]);
    }

    // List all
    $stmt = $pdo->query('SELECT * FROM committees ORDER BY sort_order ASC, created_at ASC');
    $committees = $stmt->fetchAll();

    // Attach member counts
    $counts = [];
    try {
        $rows = $pdo->query('SELECT committee_id, COUNT(*) as cnt FROM committee_members GROUP BY committee_id')->fetchAll();
        foreach ($rows as $r) $counts[$r['committee_id']] = (int) $r['cnt'];
    } catch (PDOException $e) {}

    // Attach event counts
    $eventCounts = [];
    try {
        $rows = $pdo->query('SELECT committee_id, COUNT(*) as cnt FROM events WHERE committee_id IS NOT NULL GROUP BY committee_id')->fetchAll();
        foreach ($rows as $r) $eventCounts[$r['committee_id']] = (int) $r['cnt'];
    } catch (PDOException $e) {}

    foreach ($committees as &$c) {
        $linkedCount = $counts[$c['id']] ?? 0;
        $manualCount = (int) ($c['members_count'] ?? 0);
        $c['member_count']  = max($linkedCount, $manualCount);
        $c['members_count'] = $manualCount;
        $c['event_count']   = $eventCounts[$c['id']] ?? 0;
        $c['advisory']      = (bool) ($c['advisory'] ?? false);
    }
    unset($c);

    respond(200, ['data' => $committees]);
}

// ── POST: Create ──
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $name = trim($body['name'] ?? '');
    if ($name === '') respond(422, ['error' => 'اسم اللجنة مطلوب']);

    $id = uid();
    $pdo->prepare(
        'INSERT INTO committees (id, name, icon, color, description, advisory, members_count, sort_order)
         VALUES (:id, :name, :icon, :color, :desc, :adv, :mcnt, :sort)'
    )->execute([
        ':id'    => $id,
        ':name'  => $name,
        ':icon'  => $body['icon']        ?? '🏛️',
        ':color' => $body['color']       ?? 'linear-gradient(135deg,#47915C,#2d6b40)',
        ':desc'  => $body['description'] ?? '',
        ':adv'   => ($body['advisory'] ?? false) ? 1 : 0,
        ':mcnt'  => (int) ($body['members_count'] ?? 0),
        ':sort'  => (int) ($body['sort_order'] ?? 0),
    ]);

    $stmt = $pdo->prepare('SELECT * FROM committees WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    $row['advisory'] = (bool) $row['advisory'];

    logAudit('إضافة', 'لجنة', $id, $name);
    respond(201, ['data' => $row]);
}

// ── PUT: Update ──
if ($method === 'PUT') {
    $id = $_GET['id'] ?? '';
    if (!$id) respond(422, ['error' => 'id مطلوب']);

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $fields = [];
    $params = [':id' => $id];

    $allowed = ['name', 'icon', 'color', 'description', 'advisory', 'members_count', 'sort_order'];
    foreach ($allowed as $col) {
        if (array_key_exists($col, $body)) {
            if ($col === 'advisory') {
                $fields[] = "advisory = :advisory";
                $params[':advisory'] = $body['advisory'] ? 1 : 0;
            } elseif ($col === 'members_count') {
                $fields[] = "members_count = :members_count";
                $params[':members_count'] = (int) $body['members_count'];
            } else {
                $fields[] = "{$col} = :{$col}";
                $params[":{$col}"] = $body[$col];
            }
        }
    }

    if (empty($fields)) respond(422, ['error' => 'لا توجد حقول للتحديث']);

    $sql = 'UPDATE committees SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $pdo->prepare($sql)->execute($params);

    $stmt = $pdo->prepare('SELECT * FROM committees WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) $row['advisory'] = (bool) $row['advisory'];

    logAudit('تعديل', 'لجنة', $id, $body['name'] ?? $row['name'] ?? '', $body);
    respond(200, ['data' => $row]);
}

// ── DELETE ──
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    if (!$id) respond(422, ['error' => 'id مطلوب']);

    // Get name before delete
    $stmt = $pdo->prepare('SELECT name FROM committees WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    // Delete committee members too
    $pdo->prepare('DELETE FROM committee_members WHERE committee_id = :cid')->execute([':cid' => $id]);
    $pdo->prepare('DELETE FROM committees WHERE id = :id')->execute([':id' => $id]);

    logAudit('حذف', 'لجنة', $id, $row['name'] ?? '');
    respond(200, ['message' => 'تم حذف اللجنة']);
}

respond(405, ['error' => 'Method not allowed.']);


// ── Helper: ensure committees table ──
function ensureCommitteesTable(PDO $pdo): void
{
    static $checked = false;
    if ($checked) return;

    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS committees (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            icon VARCHAR(10) NOT NULL DEFAULT '🏛️',
            color VARCHAR(200) NOT NULL DEFAULT 'linear-gradient(135deg,#47915C,#2d6b40)',
            description TEXT DEFAULT '',
            advisory INTEGER NOT NULL DEFAULT 0,
            members_count INTEGER NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        // Migration: add members_count if missing
        try { $pdo->exec("ALTER TABLE committees ADD COLUMN members_count INTEGER NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `committees` (
            `id` VARCHAR(36) NOT NULL,
            `name` VARCHAR(200) NOT NULL,
            `icon` VARCHAR(10) NOT NULL DEFAULT '🏛️',
            `color` VARCHAR(200) NOT NULL DEFAULT 'linear-gradient(135deg,#47915C,#2d6b40)',
            `description` TEXT DEFAULT NULL,
            `advisory` TINYINT(1) NOT NULL DEFAULT 0,
            `members_count` INT NOT NULL DEFAULT 0,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        // Migration: add members_count if missing
        try { $pdo->exec("ALTER TABLE `committees` ADD COLUMN `members_count` INT NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
    }

    // Seed from COMMITTEES_DATA if table is empty
    $count = (int) $pdo->query('SELECT COUNT(*) FROM committees')->fetchColumn();
    if ($count === 0) {
        seedDefaultCommittees($pdo);
    }

    $checked = true;
}

function seedDefaultCommittees(PDO $pdo): void
{
    $defaults = [
        ['c1', 'لجنة العمرة الرجبية', '🕋', 'linear-gradient(135deg,#1a6b3c,#2d9955)', 'تنظيم رحلة العمرة السنوية في شهر رجب', 0, 1],
        ['c2', 'لجنة غداء العيدين', '🍖', 'linear-gradient(135deg,#c8a84b,#e8c96a)', 'تنظيم وإدارة غداء عيد الفطر وعيد الأضحى', 0, 2],
        ['c3', 'لجنة المسابقة الرمضانية', '🌙', 'linear-gradient(135deg,#1a3a6b,#2d5ab9)', 'إعداد وتحكيم المسابقات الرمضانية', 0, 3],
        ['c4', 'لجنة الرحلات', '🎡', 'linear-gradient(135deg,#2980b9,#5dade2)', 'تخطيط وتنفيذ الرحلات الترفيهية للعائلة', 0, 4],
        ['c5', 'لجنة ليلة القدر', '✨', 'linear-gradient(135deg,#4a235a,#8e44ad)', 'إحياء ليلة القدر وتنظيم فعالياتها', 0, 5],
        ['c6', 'لجنة تنظيف المساجد', '🕌', 'linear-gradient(135deg,#117a65,#1abc9c)', 'تنسيق حملات تنظيف وصيانة المساجد (العمل التطوعي)', 0, 6],
        ['c7', 'لجنة مسابقة العيد', '🏆', 'linear-gradient(135deg,#b7950b,#d4ac0d)', 'تنظيم مسابقات وفعاليات العيد', 0, 7],
        ['c8', 'لجنة الاستثمار', '📈', 'linear-gradient(135deg,#1B3456,#2d5a85)', 'إدارة واستثمار أموال الصندوق', 0, 8],
        ['c9', 'اللجنة الاستشارية', '🎓', 'linear-gradient(135deg,#4a235a,#7b2d8b)', 'تقديم المشورة والتوجيه لإدارة المجلس', 1, 9],
        ['c10', 'اللجنة الإعلامية', '📢', 'linear-gradient(135deg,#c0392b,#e74c3c)', 'إدارة المنصات الإعلامية وتوثيق الفعاليات', 0, 10],
        ['c11', 'لجنة العقيقة الجماعية', '🐑', 'linear-gradient(135deg,#6b3a1a,#9b5a2d)', 'تنظيم مناسبات العقيقة الجماعية للعائلة', 0, 11],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO committees (id, name, icon, color, description, advisory, sort_order)
         VALUES (:id, :name, :icon, :color, :desc, :adv, :sort)'
    );

    foreach ($defaults as [$id, $name, $icon, $color, $desc, $adv, $sort]) {
        try {
            $stmt->execute([
                ':id'    => $id,
                ':name'  => $name,
                ':icon'  => $icon,
                ':color' => $color,
                ':desc'  => $desc,
                ':adv'   => $adv,
                ':sort'  => $sort,
            ]);
        } catch (PDOException $e) {
            // Skip if already exists
        }
    }
}
