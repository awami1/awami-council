<?php
/**
 * audit.php — API سجل التدقيق
 * GET  /api/audit.php              → كل السجلات (مع ترقيم صفحات)
 * GET  /api/audit.php?entity_type= → تصفية بنوع الكيان
 * GET  /api/audit.php?action=      → تصفية بالإجراء
 * GET  /api/audit.php?from=&to=    → تصفية بالتاريخ
 * GET  /api/audit.php?page=&limit= → ترقيم صفحات
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    respond(405, ['error' => 'Method not allowed.']);
}

$pdo = getPDO();
ensureAuditTable($pdo);

// ── Filters ──
$where  = [];
$params = [];

if (!empty($_GET['entity_type'])) {
    $where[]               = 'entity_type = :entity_type';
    $params[':entity_type'] = $_GET['entity_type'];
}

if (!empty($_GET['action'])) {
    $where[]          = 'action = :action';
    $params[':action'] = $_GET['action'];
}

if (!empty($_GET['search'])) {
    $term = '%' . $_GET['search'] . '%';
    $where[] = '(entity_name LIKE :s1 OR user LIKE :s2 OR details LIKE :s3)';
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
}

if (!empty($_GET['from'])) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'])) {
        respond(422, ['error' => 'from must be YYYY-MM-DD.']);
    }
    $where[]          = 'created_at >= :from_date';
    $params[':from_date'] = $_GET['from'] . ' 00:00:00';
}

if (!empty($_GET['to'])) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'])) {
        respond(422, ['error' => 'to must be YYYY-MM-DD.']);
    }
    $where[]        = 'created_at <= :to_date';
    $params[':to_date'] = $_GET['to'] . ' 23:59:59';
}

// ── Pagination ──
$page  = max(1, (int) ($_GET['page'] ?? 1));
$limit = min(100, max(10, (int) ($_GET['limit'] ?? 50)));
$offset = ($page - 1) * $limit;

// ── Count total ──
$countSql = 'SELECT COUNT(*) FROM audit_log';
if ($where) {
    $countSql .= ' WHERE ' . implode(' AND ', $where);
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// ── Fetch rows ──
$sql = 'SELECT * FROM audit_log';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC';

if (isSQLite()) {
    $sql .= " LIMIT {$limit} OFFSET {$offset}";
} else {
    $sql .= " LIMIT {$limit} OFFSET {$offset}";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Parse JSON details
foreach ($rows as &$row) {
    if (is_string($row['details'])) {
        $row['details'] = json_decode($row['details'], true) ?? [];
    }
}
unset($row);

respond(200, [
    'data'  => $rows,
    'total' => $total,
    'page'  => $page,
    'limit' => $limit,
    'pages' => (int) ceil($total / $limit),
]);
