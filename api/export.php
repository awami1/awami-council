<?php
/**
 * export.php — تصدير البيانات بصيغة CSV
 * GET /api/export.php?type=members
 * GET /api/export.php?type=payments
 * GET /api/export.php?type=transactions&from=2025-01-01&to=2025-12-31
 * GET /api/export.php?type=events
 * GET /api/export.php?type=audit
 * GET /api/export.php?type=committees
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

$type = $_GET['type'] ?? '';
if (!in_array($type, ['members', 'payments', 'transactions', 'events', 'audit', 'committees'], true)) {
    respond(422, ['error' => 'type must be one of: members, payments, transactions, events, audit, committees']);
}

$pdo = getPDO();

// ── Generate CSV ──
$headers = [];
$rows    = [];

switch ($type) {
    case 'members':
        $headers = ['#', 'الاسم', 'الجوال', 'رقم الهوية', 'الفرع العائلي', 'تاريخ الانضمام', 'الحالة', 'ملاحظات'];
        $stmt = $pdo->query('SELECT * FROM members ORDER BY name ASC');
        $i = 1;
        while ($r = $stmt->fetch()) {
            $rows[] = [
                $i++,
                $r['name'],
                $r['phone'] ?? '',
                $r['id_num'] ?? '',
                $r['family'] ?? '',
                $r['join_date'] ?? '',
                $r['status'],
                $r['notes'] ?? '',
            ];
        }
        break;

    case 'payments':
        $headers = ['العضو', 'الدورة', 'المطلوب', 'المدفوع', 'التاريخ', 'الطريقة', 'الحالة', 'ملاحظات'];

        $sql = 'SELECT p.*, m.name AS member_name, pr.name AS period_name
                FROM payments p
                LEFT JOIN members m ON m.id = p.member_id
                LEFT JOIN periods pr ON pr.id = p.period_id
                ORDER BY p.created_at DESC';

        // Optional period filter
        if (!empty($_GET['period_id'])) {
            $sql = 'SELECT p.*, m.name AS member_name, pr.name AS period_name
                    FROM payments p
                    LEFT JOIN members m ON m.id = p.member_id
                    LEFT JOIN periods pr ON pr.id = p.period_id
                    WHERE p.period_id = :pid
                    ORDER BY m.name ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':pid' => $_GET['period_id']]);
        } else {
            $stmt = $pdo->query($sql);
        }

        while ($r = $stmt->fetch()) {
            $rows[] = [
                $r['member_name'] ?? '',
                $r['period_name'] ?? '',
                $r['required'] ?? 0,
                $r['amount'] ?? 0,
                $r['pay_date'] ?? '',
                $r['method'] ?? '',
                $r['status'],
                $r['notes'] ?? '',
            ];
        }
        break;

    case 'transactions':
        $headers = ['التاريخ', 'الوصف', 'الفئة', 'النوع', 'المبلغ'];

        $where  = [];
        $params = [];

        if (!empty($_GET['from'])) {
            $where[]              = 'tx_date >= :from_date';
            $params[':from_date'] = $_GET['from'];
        }
        if (!empty($_GET['to'])) {
            $where[]            = 'tx_date <= :to_date';
            $params[':to_date'] = $_GET['to'];
        }
        if (!empty($_GET['tx_type']) && in_array($_GET['tx_type'], ['إيراد', 'مصروف'], true)) {
            $where[]          = 'type = :type';
            $params[':type']  = $_GET['tx_type'];
        }

        $sql = 'SELECT * FROM transactions';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY tx_date DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        while ($r = $stmt->fetch()) {
            $rows[] = [
                $r['tx_date'] ?? '',
                $r['description'] ?? '',
                $r['category'] ?? '',
                $r['type'],
                $r['amount'],
            ];
        }
        break;

    case 'events':
        $headers = ['الاسم', 'التاريخ', 'الحالة', 'الميزانية', 'المشاركون', 'المسؤول', 'ملاحظات'];
        $stmt = $pdo->query('SELECT * FROM events ORDER BY event_date DESC');
        while ($r = $stmt->fetch()) {
            $rows[] = [
                $r['name'],
                $r['event_date'] ?? '',
                $r['status'],
                $r['budget'] ?? 0,
                $r['participants'] ?? 0,
                $r['lead'] ?? '',
                $r['notes'] ?? '',
            ];
        }
        break;

    case 'committees':
        $headers = ['اللجنة', 'الوصف', 'عدد الأعضاء', 'الأعضاء'];
        // Try committees table first
        try {
            $stmt = $pdo->query(
                'SELECT c.name, c.description,
                        (SELECT COUNT(*) FROM committee_members cm WHERE cm.committee_id = c.id) AS cnt,
                        (SELECT GROUP_CONCAT(m.name) FROM committee_members cm LEFT JOIN members m ON m.id = cm.member_id WHERE cm.committee_id = c.id) AS member_names
                 FROM committees c
                 ORDER BY c.sort_order ASC'
            );
            while ($r = $stmt->fetch()) {
                $rows[] = [
                    $r['name'],
                    $r['description'] ?? '',
                    $r['cnt'],
                    str_replace(',', '، ', $r['member_names'] ?? ''),
                ];
            }
        } catch (PDOException $e) {
            // Fallback to old method
            $stmt = $pdo->query(
                'SELECT cm.committee_id, GROUP_CONCAT(m.name) AS member_names, COUNT(*) AS cnt
                 FROM committee_members cm
                 LEFT JOIN members m ON m.id = cm.member_id
                 GROUP BY cm.committee_id'
            );
            while ($r = $stmt->fetch()) {
                $rows[] = [$r['committee_id'], '', $r['cnt'], str_replace(',', '، ', $r['member_names'] ?? '')];
            }
        }
        break;

    case 'audit':
        ensureAuditTable($pdo);
        $headers = ['التاريخ', 'المستخدم', 'الإجراء', 'النوع', 'الكيان', 'العنوان IP'];

        $where  = [];
        $params = [];
        if (!empty($_GET['from'])) {
            $where[]              = 'created_at >= :from_date';
            $params[':from_date'] = $_GET['from'] . ' 00:00:00';
        }
        if (!empty($_GET['to'])) {
            $where[]            = 'created_at <= :to_date';
            $params[':to_date'] = $_GET['to'] . ' 23:59:59';
        }

        $sql = 'SELECT * FROM audit_log';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        while ($r = $stmt->fetch()) {
            $rows[] = [
                $r['created_at'],
                $r['user'],
                $r['action'],
                $r['entity_type'],
                $r['entity_name'] ?? '',
                $r['ip_address'] ?? '',
            ];
        }
        break;
}

// ── Log the export action ──
logAudit('تصدير', $type, '', "تصدير {$type} (" . count($rows) . " سجل)");

// ── Output CSV ──
$filename = "awami-{$type}-" . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Cache-Control: no-cache, no-store, must-revalidate');

// BOM for Excel Arabic support
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, $headers);
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
