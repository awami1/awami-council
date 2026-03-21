<?php
/**
 * report-generator.php — توليد تقارير تلقائية من بيانات النظام
 * GET ?type=summary|financial|subscriptions|committees|events
 * فلاتر: date_from, date_to, committee_id, period_id
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
requireAuth();
verifyCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, ['error' => 'Method not allowed.']);
}

$type        = $_GET['type'] ?? '';
$dateFrom    = $_GET['date_from'] ?? null;
$dateTo      = $_GET['date_to'] ?? null;
$committeeId = $_GET['committee_id'] ?? null;
$periodId    = $_GET['period_id'] ?? null;

// بناء شروط التاريخ للمعاملات
function txDateFilters(): array
{
    global $dateFrom, $dateTo, $committeeId;
    $where = '';
    $params = [];
    if ($dateFrom) { $where .= ' AND tx_date >= :date_from'; $params[':date_from'] = $dateFrom; }
    if ($dateTo)   { $where .= ' AND tx_date <= :date_to';   $params[':date_to']   = $dateTo; }
    if ($committeeId) { $where .= ' AND committee_id = :cid'; $params[':cid'] = $committeeId; }
    return [$where, $params];
}

// ---- الملخص التنفيذي ----
function handleSummary(): void
{
    $pdo = getPDO();

    // رصيد الصندوق
    $fin = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN type = 'إيراد' THEN amount ELSE 0 END), 0) AS total_income,
            COALESCE(SUM(CASE WHEN type = 'مصروف' THEN amount ELSE 0 END), 0) AS total_expense
        FROM transactions
    ")->fetch();
    $fin['net_balance'] = (float)$fin['total_income'] - (float)$fin['total_expense'];

    // أعضاء حسب الحالة
    $members = [];
    foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM members GROUP BY status")->fetchAll() as $r) {
        $members[$r['status']] = (int)$r['c'];
    }
    $members['total'] = array_sum($members);

    // اشتراكات
    $sub = $pdo->query("
        SELECT
            COALESCE(SUM(p.fee_amount), 0) AS total_due,
            COALESCE(SUM(CASE WHEN pay.status = 'مدفوع' THEN pay.amount ELSE 0 END), 0) AS total_paid
        FROM periods p
        CROSS JOIN members m
        LEFT JOIN payments pay ON pay.period_id = p.id AND pay.member_id = m.id
        WHERE m.status IN ('مشترك', 'نشط')
    ")->fetch();
    $sub['collection_rate'] = (float)$sub['total_due'] > 0
        ? round(((float)$sub['total_paid'] / (float)$sub['total_due']) * 100, 1) : 0;

    // عُهَد اللجان
    $comms = $pdo->query("
        SELECT c.id, c.name,
            COALESCE(t.spent, 0) AS spent
        FROM committees c
        LEFT JOIN (
            SELECT committee_id, SUM(amount) AS spent
            FROM transactions WHERE type = 'مصروف'
            GROUP BY committee_id
        ) t ON t.committee_id = c.id
        ORDER BY c.name
    ")->fetchAll();
    foreach ($comms as &$c) {
        $c['budget'] = 0;
        $c['remaining'] = 0 - (float)$c['spent'];
    }

    // فعاليات قادمة
    $upcoming = $pdo->query("
        SELECT e.name, e.event_date AS date, c.name AS committee_name
        FROM events e
        LEFT JOIN committees c ON c.id = e.committee_id
        WHERE e.status IN ('قادم', 'جاري')
        ORDER BY e.event_date ASC
        LIMIT 5
    ")->fetchAll();

    respond(200, ['data' => [
        'generated_at'     => date('c'),
        'financial'        => $fin,
        'members'          => $members,
        'subscriptions'    => $sub,
        'committee_budgets' => $comms,
        'upcoming_events'  => $upcoming,
    ]]);
}

// ---- التقرير المالي الشامل ----
function handleFinancial(): void
{
    $pdo = getPDO();
    [$dateWhere, $params] = txDateFilters();
    global $periodId;

    // ملخص الصندوق
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN type = 'إيراد' THEN amount ELSE 0 END), 0) AS total_income,
            COALESCE(SUM(CASE WHEN type = 'مصروف' THEN amount ELSE 0 END), 0) AS total_expense,
            COUNT(*) AS total_count
        FROM transactions
        WHERE 1=1 {$dateWhere}
    ");
    $stmt->execute($params);
    $summary = $stmt->fetch();
    $summary['net_balance'] = (float)$summary['total_income'] - (float)$summary['total_expense'];

    // إيرادات حسب الفئة
    $stmt = $pdo->prepare("
        SELECT category, SUM(amount) AS amount, COUNT(*) AS count
        FROM transactions
        WHERE type = 'إيراد' {$dateWhere}
        GROUP BY category ORDER BY amount DESC
    ");
    $stmt->execute($params);
    $incomeByCategory = $stmt->fetchAll();
    foreach ($incomeByCategory as &$row) {
        $row['percentage'] = (float)$summary['total_income'] > 0
            ? round(((float)$row['amount'] / (float)$summary['total_income']) * 100, 1) : 0;
    }

    // مصروفات حسب الفئة
    $stmt = $pdo->prepare("
        SELECT category, SUM(amount) AS amount, COUNT(*) AS count
        FROM transactions
        WHERE type = 'مصروف' {$dateWhere}
        GROUP BY category ORDER BY amount DESC
    ");
    $stmt->execute($params);
    $expenseByCategory = $stmt->fetchAll();
    foreach ($expenseByCategory as &$row) {
        $row['percentage'] = (float)$summary['total_expense'] > 0
            ? round(((float)$row['amount'] / (float)$summary['total_expense']) * 100, 1) : 0;
    }

    // عُهَد اللجان — بدون فلتر committee لأنها عرض شامل
    $dateOnlyParams = [];
    $dateOnlyWhere = '';
    if (isset($params[':date_from'])) { $dateOnlyWhere .= ' AND tx_date >= :date_from'; $dateOnlyParams[':date_from'] = $params[':date_from']; }
    if (isset($params[':date_to']))   { $dateOnlyWhere .= ' AND tx_date <= :date_to';   $dateOnlyParams[':date_to']   = $params[':date_to']; }

    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.icon,
            COALESCE(t.spent, 0) AS spent
        FROM committees c
        LEFT JOIN (
            SELECT committee_id, SUM(amount) AS spent
            FROM transactions
            WHERE type = 'مصروف' {$dateOnlyWhere}
            GROUP BY committee_id
        ) t ON t.committee_id = c.id
        ORDER BY c.name
    ");
    $stmt->execute($dateOnlyParams);
    $committeeBudgets = $stmt->fetchAll();
    foreach ($committeeBudgets as &$row) {
        $row['budget'] = 0;
        $row['remaining'] = 0 - (float)$row['spent'];
    }

    // اشتراكات
    $subParams = [];
    $periodWhere = '';
    if ($periodId) { $periodWhere = ' AND p.id = :pid'; $subParams[':pid'] = $periodId; }

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(p.fee_amount), 0) AS total_due,
            COALESCE(SUM(CASE WHEN pay.status = 'مدفوع' THEN pay.amount ELSE 0 END), 0) AS total_paid
        FROM periods p
        CROSS JOIN members m
        LEFT JOIN payments pay ON pay.period_id = p.id AND pay.member_id = m.id
        WHERE m.status IN ('مشترك', 'نشط') {$periodWhere}
    ");
    $stmt->execute($subParams);
    $subSummary = $stmt->fetch();
    $subSummary['collection_rate'] = (float)$subSummary['total_due'] > 0
        ? round(((float)$subSummary['total_paid'] / (float)$subSummary['total_due']) * 100, 1) : 0;

    respond(200, ['data' => [
        'generated_at'       => date('c'),
        'filters'            => ['date_from' => $GLOBALS['dateFrom'], 'date_to' => $GLOBALS['dateTo'], 'committee_id' => $GLOBALS['committeeId']],
        'summary'            => $summary,
        'income_by_category' => $incomeByCategory,
        'expense_by_category' => $expenseByCategory,
        'committee_budgets'  => $committeeBudgets,
        'subscription_summary' => $subSummary,
    ]]);
}

// ---- تقرير الاشتراكات ----
function handleSubscriptions(): void
{
    $pdo = getPDO();
    global $periodId;

    // أعضاء حسب الحالة
    $memberStats = [];
    foreach ($pdo->query("SELECT status, COUNT(*) AS count FROM members GROUP BY status")->fetchAll() as $row) {
        $memberStats[$row['status']] = (int)$row['count'];
    }
    $memberStats['total'] = array_sum($memberStats);

    $activeCount = ($memberStats['مشترك'] ?? 0) + ($memberStats['نشط'] ?? 0);

    // فترات مع إحصائيات الدفع
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.fee_amount, p.start_date, p.end_date,
            COUNT(CASE WHEN pay.status = 'مدفوع' THEN 1 END) AS paid_count,
            COALESCE(SUM(CASE WHEN pay.status = 'مدفوع' THEN pay.amount ELSE 0 END), 0) AS paid_amount
        FROM periods p
        LEFT JOIN payments pay ON pay.period_id = p.id
        GROUP BY p.id, p.name, p.fee_amount, p.start_date, p.end_date
        ORDER BY p.start_date ASC
    ");
    $periods = $stmt->fetchAll();
    foreach ($periods as &$p) {
        $p['unpaid_count'] = max(0, $activeCount - (int)$p['paid_count']);
        $p['total_due'] = (float)$p['fee_amount'] * $activeCount;
        $p['collection_rate'] = $p['total_due'] > 0
            ? round(((float)$p['paid_amount'] / $p['total_due']) * 100, 1) : 0;
    }

    // متخلفين
    $targetPeriod = $periodId ?? ($periods ? end($periods)['id'] : null);
    $defaulters = [];
    if ($targetPeriod) {
        $stmt = $pdo->prepare("
            SELECT m.id, m.name, m.phone, m.status, p.name AS period_name, p.fee_amount
            FROM members m
            CROSS JOIN periods p
            LEFT JOIN payments pay ON pay.member_id = m.id AND pay.period_id = p.id AND pay.status = 'مدفوع'
            WHERE p.id = :pid AND pay.id IS NULL AND m.status IN ('مشترك', 'نشط')
            ORDER BY m.name
        ");
        $stmt->execute([':pid' => $targetPeriod]);
        $defaulters = $stmt->fetchAll();
    }

    respond(200, ['data' => [
        'generated_at'    => date('c'),
        'members'         => $memberStats,
        'periods'         => $periods,
        'defaulters'      => $defaulters,
        'defaulters_period' => $targetPeriod,
    ]]);
}

// ---- تقرير اللجان ----
function handleCommittees(): void
{
    $pdo = getPDO();

    $stmt = $pdo->query("
        SELECT c.id, c.name, c.icon, c.color, c.description,
            COALESCE(t_expense.spent, 0) AS spent,
            COALESCE(cm_count.member_count, 0) AS member_count,
            COALESCE(ev_count.event_count, 0) AS event_count
        FROM committees c
        LEFT JOIN (
            SELECT committee_id, SUM(amount) AS spent
            FROM transactions WHERE type = 'مصروف'
            GROUP BY committee_id
        ) t_expense ON t_expense.committee_id = c.id
        LEFT JOIN (
            SELECT committee_id, COUNT(*) AS member_count
            FROM committee_members WHERE end_year IS NULL
            GROUP BY committee_id
        ) cm_count ON cm_count.committee_id = c.id
        LEFT JOIN (
            SELECT committee_id, COUNT(*) AS event_count
            FROM events
            GROUP BY committee_id
        ) ev_count ON ev_count.committee_id = c.id
        ORDER BY c.name
    ");
    $committees = $stmt->fetchAll();

    foreach ($committees as &$c) {
        $c['budget'] = 0;
        $c['remaining'] = 0 - (float)$c['spent'];

        $stmt2 = $pdo->prepare("
            SELECT m.name, cm.role
            FROM committee_members cm
            JOIN members m ON m.id = cm.member_id
            WHERE cm.committee_id = :cid AND cm.end_year IS NULL
            ORDER BY m.name
        ");
        $stmt2->execute([':cid' => $c['id']]);
        $c['members'] = $stmt2->fetchAll();
    }

    $totalSpent = array_sum(array_column($committees, 'spent'));

    respond(200, ['data' => [
        'generated_at' => date('c'),
        'committees'   => $committees,
        'summary'      => [
            'total_committees' => count($committees),
            'total_budget'     => 0,
            'total_spent'      => $totalSpent,
            'total_remaining'  => 0 - $totalSpent,
        ],
    ]]);
}

// ---- تقرير الفعاليات ----
function handleEvents(): void
{
    $pdo = getPDO();
    global $dateFrom, $dateTo, $committeeId;

    $where = '1=1';
    $params = [];
    if ($dateFrom)    { $where .= ' AND e.event_date >= :df';  $params[':df']  = $dateFrom; }
    if ($dateTo)      { $where .= ' AND e.event_date <= :dt';  $params[':dt']  = $dateTo; }
    if ($committeeId) { $where .= ' AND e.committee_id = :cid'; $params[':cid'] = $committeeId; }

    // حسب الحالة
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS count FROM events e WHERE {$where} GROUP BY status");
    $stmt->execute($params);
    $statusStats = [];
    foreach ($stmt->fetchAll() as $row) {
        $statusStats[$row['status']] = (int)$row['count'];
    }

    // قائمة
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, e.event_date AS date, e.status, e.budget, e.participants, e.icon,
            c.name AS committee_name
        FROM events e
        LEFT JOIN committees c ON c.id = e.committee_id
        WHERE {$where}
        ORDER BY e.event_date DESC
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    $totalBudget = array_sum(array_column($events, 'budget'));
    $totalParticipants = array_sum(array_column($events, 'participants'));

    respond(200, ['data' => [
        'generated_at' => date('c'),
        'filters'      => ['date_from' => $dateFrom, 'date_to' => $dateTo, 'committee_id' => $committeeId],
        'status_stats' => $statusStats,
        'events'       => $events,
        'summary'      => [
            'total_events'       => count($events),
            'total_budget'       => $totalBudget,
            'total_participants' => $totalParticipants,
        ],
    ]]);
}

// ---- Router ----
try {
    match ($type) {
        'summary'       => handleSummary(),
        'financial'     => handleFinancial(),
        'subscriptions' => handleSubscriptions(),
        'committees'    => handleCommittees(),
        'events'        => handleEvents(),
        default         => respond(400, ['error' => 'نوع التقرير غير صالح. القيم المقبولة: summary, financial, subscriptions, committees, events']),
    };
} catch (\Throwable $e) {
    error_log('Report Generator error: ' . $e->getMessage());
    respond(500, ['error' => 'حدث خطأ في توليد التقرير.']);
}
