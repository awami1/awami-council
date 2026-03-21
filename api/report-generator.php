<?php
/**
 * report-generator.php — توليد تقارير تلقائية من بيانات النظام
 * GET ?type=summary|financial|subscriptions|committees|events
 * فلاتر: date_from, date_to, committee_id, period_id
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/report-queries.php';
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
$pdo         = getPDO();

try {
    match ($type) {
        'summary'       => respond(200, ['data' => fetchSummaryData($pdo)]),
        'financial'     => respond(200, ['data' => fetchFinancialData($pdo, $dateFrom, $dateTo, $committeeId, $periodId)]),
        'subscriptions' => respond(200, ['data' => fetchSubscriptionsData($pdo, $periodId)]),
        'committees'    => respond(200, ['data' => fetchCommitteesData($pdo)]),
        'events'        => respond(200, ['data' => fetchEventsData($pdo, $dateFrom, $dateTo, $committeeId)]),
        default         => respond(400, ['error' => 'نوع التقرير غير صالح. القيم المقبولة: summary, financial, subscriptions, committees, events']),
    };
} catch (\Throwable $e) {
    error_log('Report Generator error: ' . $e->getMessage());
    respond(500, ['error' => 'حدث خطأ في توليد التقرير.']);
}
