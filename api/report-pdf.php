<?php
/**
 * report-pdf.php — تصدير تقارير PDF رسمية
 *
 * GET ?type=summary|financial|subscriptions|committees|events
 *     &date_from=...&date_to=...&committee_id=...&period_id=...
 *     &extra_items=JSON (اختياري — بنود يدوية للتقرير المالي)
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/report-queries.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
$extraItems  = [];
if (!empty($_GET['extra_items'])) {
    $extraItems = json_decode($_GET['extra_items'], true) ?: [];
}

$pdo = getPDO();

// جلب البيانات
try {
    $data = match ($type) {
        'summary'       => fetchSummaryData($pdo),
        'financial'     => fetchFinancialData($pdo, $dateFrom, $dateTo, $committeeId, $periodId),
        'subscriptions' => fetchSubscriptionsData($pdo, $periodId),
        'committees'    => fetchCommitteesData($pdo),
        'events'        => fetchEventsData($pdo, $dateFrom, $dateTo, $committeeId),
        default         => null,
    };
} catch (\Throwable $e) {
    error_log('Report PDF data error: ' . $e->getMessage());
    respond(500, ['error' => 'حدث خطأ في جلب بيانات التقرير.']);
}

if ($data === null) {
    respond(400, ['error' => 'نوع تقرير غير صالح']);
}

// ---- ألوان ----
$navy     = [27, 52, 86];
$green    = [71, 145, 92];
$gold     = [200, 168, 75];
$white    = [255, 255, 255];
$gray     = [100, 100, 100];
$lightGray = [240, 240, 240];

// ---- إعداد TCPDF ----
function initPDF(): TCPDF
{
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('مجلس عائلة العوامي');
    $pdf->SetAuthor('alawami.site');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->setRTL(true);

    $fontPath = __DIR__ . '/../public/fonts/saudi-bold.ttf';
    if (is_file($fontPath)) {
        $GLOBALS['arabicFont'] = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
    } else {
        $GLOBALS['arabicFont'] = 'dejavusans';
    }

    $fontPathNormal = __DIR__ . '/../public/fonts/saudi-normal.ttf';
    if (is_file($fontPathNormal)) {
        $GLOBALS['arabicFontNormal'] = TCPDF_FONTS::addTTFfont($fontPathNormal, 'TrueTypeUnicode', '', 96);
    } else {
        $GLOBALS['arabicFontNormal'] = 'dejavusans';
    }

    return $pdf;
}

// ---- رأس التقرير ----
function pdfHeader(TCPDF $pdf, string $title, string $subtitle = ''): void
{
    global $navy, $white, $gold;

    $pdf->AddPage();
    $pdf->SetFillColor(...$navy);
    $pdf->Rect(0, 0, 210, 45, 'F');

    $pdf->SetTextColor(...$white);
    $pdf->SetFont($GLOBALS['arabicFont'], '', 18);
    $pdf->SetY(10);
    $pdf->Cell(0, 10, 'مجلس عائلة العوامي', 0, 1, 'C');

    $pdf->SetFont($GLOBALS['arabicFont'], '', 14);
    $pdf->Cell(0, 8, $title, 0, 1, 'C');

    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 9);
    $pdf->SetTextColor(...$gold);
    $issueDate = date('Y/m/d');
    $text = "تاريخ الإصدار: {$issueDate}";
    if ($subtitle) $text .= "  |  {$subtitle}";
    $pdf->Cell(0, 6, $text, 0, 1, 'C');

    $pdf->SetY(47);
    $pdf->SetDrawColor(...$gold);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, 47, 195, 47);
    $pdf->SetY(52);
}

// ---- عنوان قسم ----
function pdfSectionTitle(TCPDF $pdf, string $title): void
{
    global $navy, $green;

    $pdf->Ln(5);
    $pdf->SetTextColor(...$navy);
    $pdf->SetFont($GLOBALS['arabicFont'], '', 13);
    $pdf->Cell(0, 8, $title, 0, 1, 'R');
    $pdf->SetDrawColor(...$green);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
}

// ---- جدول ----
function pdfTable(TCPDF $pdf, array $headers, array $rows, array $colWidths): void
{
    global $navy, $white, $lightGray;

    $pdf->SetFillColor(...$navy);
    $pdf->SetTextColor(...$white);
    $pdf->SetFont($GLOBALS['arabicFont'], '', 10);
    foreach ($headers as $i => $h) {
        $pdf->Cell($colWidths[$i], 8, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 10);
    $fill = false;

    foreach ($rows as $row) {
        if ($fill) $pdf->SetFillColor(...$lightGray);

        if ($pdf->GetY() > 260) {
            $pdf->AddPage();
            $pdf->SetFillColor(...$navy);
            $pdf->SetTextColor(...$white);
            $pdf->SetFont($GLOBALS['arabicFont'], '', 10);
            foreach ($headers as $i => $h) {
                $pdf->Cell($colWidths[$i], 8, $h, 1, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetTextColor(50, 50, 50);
            $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 10);
        }

        foreach ($row as $i => $cell) {
            $pdf->Cell($colWidths[$i], 7, (string)$cell, 1, 0, 'C', $fill);
        }
        $pdf->Ln();
        $fill = !$fill;
    }
}

// ---- سطر بيانات (تسمية: قيمة) ----
function pdfDataLine(TCPDF $pdf, string $label, string $value, array $valueColor = [50, 50, 50]): void
{
    global $gray;
    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 11);
    $pdf->SetTextColor(...$gray);
    $pdf->Cell(60, 7, $label, 0, 0, 'R');
    $pdf->SetFont($GLOBALS['arabicFont'], '', 11);
    $pdf->SetTextColor(...$valueColor);
    $pdf->Cell(0, 7, $value, 0, 1, 'R');
}

// ---- تذييل ----
function pdfFooter(TCPDF $pdf): void
{
    global $gold, $gray;

    $footerY = max($pdf->GetY() + 15, 265);
    if ($footerY > 275) {
        $pdf->AddPage();
        $footerY = 265;
    }

    $pdf->SetY($footerY);
    $pdf->SetDrawColor(...$gold);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $footerY, 195, $footerY);

    $pdf->Ln(3);
    $pdf->SetTextColor(...$gray);
    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 9);
    $pdf->Cell(0, 5, 'صدر من: الموقع الرسمي لعائلة العوامي', 0, 1, 'C');
    $pdf->Cell(0, 5, 'هذا التقرير معلوماتي  |  alawami.site', 0, 1, 'C');
}

// =====================================================
// بناء التقارير
// =====================================================

function buildSummaryPDF(TCPDF $pdf, array $data): void
{
    global $green, $gray;
    pdfHeader($pdf, 'الملخص التنفيذي');

    $fin = $data['financial'];
    pdfSectionTitle($pdf, 'الوضع المالي');
    pdfDataLine($pdf, 'إجمالي الإيرادات:', number_format((float)$fin['total_income']) . ' ريال', $green);
    pdfDataLine($pdf, 'إجمالي المصروفات:', number_format((float)$fin['total_expense']) . ' ريال', [220, 50, 50]);
    $netColor = (float)$fin['net_balance'] >= 0 ? $green : [220, 50, 50];
    pdfDataLine($pdf, 'صافي الرصيد:', number_format((float)$fin['net_balance']) . ' ريال', $netColor);

    $sub = $data['subscriptions'];
    pdfSectionTitle($pdf, 'الاشتراكات');
    pdfDataLine($pdf, 'نسبة التحصيل:', $sub['collection_rate'] . '%');
    pdfDataLine($pdf, 'المحصّل:', number_format((float)$sub['total_paid']) . ' ريال', $green);
    pdfDataLine($pdf, 'المستحق:', number_format((float)$sub['total_due']) . ' ريال');

    $m = $data['members'];
    pdfSectionTitle($pdf, 'الأعضاء (' . ($m['total'] ?? 0) . ')');
    foreach ($m as $status => $count) {
        if ($status === 'total') continue;
        pdfDataLine($pdf, $status . ':', $count . ' عضو');
    }

    $comms = $data['committee_budgets'] ?? [];
    if (count($comms)) {
        pdfSectionTitle($pdf, 'مصروفات اللجان');
        $rows = array_map(fn($c) => [$c['name'], number_format((float)$c['spent']) . ' ريال'], $comms);
        pdfTable($pdf, ['اللجنة', 'المصروف'], $rows, [100, 65]);
    }

    $upcoming = $data['upcoming_events'] ?? [];
    if (count($upcoming)) {
        pdfSectionTitle($pdf, 'الفعاليات القادمة');
        $rows = array_map(fn($e) => [$e['name'], $e['date'] ?? '—', $e['committee_name'] ?? '—'], $upcoming);
        pdfTable($pdf, ['الفعالية', 'التاريخ', 'اللجنة'], $rows, [70, 45, 50]);
    }

    pdfFooter($pdf);
}

function buildFinancialPDF(TCPDF $pdf, array $data, array $extraItems): void
{
    global $green, $gray;
    pdfHeader($pdf, 'التقرير المالي الشامل');

    $s = $data['summary'];
    pdfSectionTitle($pdf, 'ملخص الصندوق');
    pdfDataLine($pdf, 'إجمالي الإيرادات:', number_format((float)$s['total_income']) . ' ريال', $green);
    pdfDataLine($pdf, 'إجمالي المصروفات:', number_format((float)$s['total_expense']) . ' ريال', [220, 50, 50]);
    $netColor = (float)$s['net_balance'] >= 0 ? $green : [220, 50, 50];
    pdfDataLine($pdf, 'صافي الرصيد:', number_format((float)$s['net_balance']) . ' ريال', $netColor);
    pdfDataLine($pdf, 'عدد المعاملات:', (string)($s['total_count'] ?? 0));

    $inc = $data['income_by_category'] ?? [];
    if (count($inc)) {
        pdfSectionTitle($pdf, 'الإيرادات حسب الفئة');
        $rows = array_map(fn($r) => [$r['category'] ?: 'بدون', number_format((float)$r['amount']), $r['count'], $r['percentage'] . '%'], $inc);
        pdfTable($pdf, ['الفئة', 'المبلغ', 'العدد', 'النسبة'], $rows, [55, 40, 30, 30]);
    }

    $exp = $data['expense_by_category'] ?? [];
    if (count($exp)) {
        pdfSectionTitle($pdf, 'المصروفات حسب الفئة');
        $rows = array_map(fn($r) => [$r['category'] ?: 'بدون', number_format((float)$r['amount']), $r['count'], $r['percentage'] . '%'], $exp);
        pdfTable($pdf, ['الفئة', 'المبلغ', 'العدد', 'النسبة'], $rows, [55, 40, 30, 30]);
    }

    $comms = $data['committee_budgets'] ?? [];
    if (count($comms)) {
        pdfSectionTitle($pdf, 'مصروفات اللجان');
        $rows = array_map(fn($c) => [($c['icon'] ?? '') . ' ' . $c['name'], number_format((float)$c['spent']) . ' ريال'], $comms);
        pdfTable($pdf, ['اللجنة', 'المصروف'], $rows, [100, 65]);
    }

    $sub = $data['subscription_summary'] ?? [];
    if ($sub) {
        pdfSectionTitle($pdf, 'ملخص الاشتراكات');
        pdfDataLine($pdf, 'المستحق:', number_format((float)($sub['total_due'] ?? 0)) . ' ريال');
        pdfDataLine($pdf, 'المحصّل:', number_format((float)($sub['total_paid'] ?? 0)) . ' ريال', $green);
        pdfDataLine($pdf, 'نسبة التحصيل:', ($sub['collection_rate'] ?? 0) . '%');
    }

    if (count($extraItems)) {
        pdfSectionTitle($pdf, 'بنود إضافية (إدخال يدوي)');
        $rows = array_map(fn($it) => [$it['label'] ?? '', number_format((float)($it['amount'] ?? 0)) . ' ريال', $it['type'] ?? ''], $extraItems);
        pdfTable($pdf, ['البند', 'المبلغ', 'النوع'], $rows, [75, 45, 35]);
    }

    pdfFooter($pdf);
}

function buildSubscriptionsPDF(TCPDF $pdf, array $data): void
{
    global $gray;
    pdfHeader($pdf, 'تقرير الاشتراكات');

    $m = $data['members'] ?? [];
    pdfSectionTitle($pdf, 'إحصائيات الأعضاء');
    foreach ($m as $status => $count) {
        pdfDataLine($pdf, $status . ':', $count . (is_int($count) ? ' عضو' : ''));
    }

    $periods = $data['periods'] ?? [];
    if (count($periods)) {
        pdfSectionTitle($pdf, 'الفترات المالية');
        $rows = array_map(fn($p) => [
            $p['name'],
            number_format((float)$p['fee_amount']),
            $p['paid_count'],
            $p['unpaid_count'],
            number_format((float)$p['paid_amount']),
            $p['collection_rate'] . '%',
        ], $periods);
        pdfTable($pdf, ['الفترة', 'الرسم', 'دفعوا', 'لم يدفعوا', 'المحصّل', 'الالتزام'], $rows, [30, 22, 22, 25, 30, 25]);
    }

    $defaulters = $data['defaulters'] ?? [];
    if (count($defaulters)) {
        pdfSectionTitle($pdf, 'المتخلفون عن الدفع (' . count($defaulters) . ')');
        $rows = array_map(fn($d) => [$d['name'], $d['phone'] ?? '—', $d['period_name'] ?? '', number_format((float)$d['fee_amount'])], $defaulters);
        pdfTable($pdf, ['الاسم', 'الهاتف', 'الفترة', 'المبلغ'], $rows, [50, 35, 40, 30]);
    }

    pdfFooter($pdf);
}

function buildCommitteesPDF(TCPDF $pdf, array $data): void
{
    global $navy, $gray, $green;
    pdfHeader($pdf, 'تقرير اللجان');

    $summary = $data['summary'] ?? [];
    pdfSectionTitle($pdf, 'ملخص إجمالي');
    pdfDataLine($pdf, 'عدد اللجان:', (string)($summary['total_committees'] ?? 0));
    pdfDataLine($pdf, 'إجمالي المصروف:', number_format((float)($summary['total_spent'] ?? 0)) . ' ريال', [220, 50, 50]);

    $committees = $data['committees'] ?? [];
    foreach ($committees as $c) {
        pdfSectionTitle($pdf, ($c['icon'] ?? '🏛️') . ' ' . ($c['name'] ?? ''));

        pdfDataLine($pdf, 'عدد الأعضاء:', (string)($c['member_count'] ?? 0));
        pdfDataLine($pdf, 'المصروف:', number_format((float)($c['spent'] ?? 0)) . ' ريال', [220, 50, 50]);
        pdfDataLine($pdf, 'عدد الفعاليات:', (string)($c['event_count'] ?? 0));

        $members = $c['members'] ?? [];
        if (count($members)) {
            $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 10);
            $pdf->SetTextColor(...$gray);
            $names = array_map(fn($m) => $m['name'] . ($m['role'] ? ' (' . $m['role'] . ')' : ''), $members);
            $pdf->MultiCell(0, 6, 'الأعضاء: ' . implode('، ', $names), 0, 'R');
        }
        $pdf->Ln(2);
    }

    pdfFooter($pdf);
}

function buildEventsPDF(TCPDF $pdf, array $data): void
{
    global $gray;
    pdfHeader($pdf, 'تقرير الفعاليات');

    $summary = $data['summary'] ?? [];
    $stats = $data['status_stats'] ?? [];
    pdfSectionTitle($pdf, 'إحصائيات');
    pdfDataLine($pdf, 'إجمالي الفعاليات:', (string)($summary['total_events'] ?? 0));
    pdfDataLine($pdf, 'إجمالي الميزانيات:', number_format((float)($summary['total_budget'] ?? 0)) . ' ريال');
    pdfDataLine($pdf, 'إجمالي المشاركين:', (string)($summary['total_participants'] ?? 0));
    foreach ($stats as $status => $count) {
        pdfDataLine($pdf, $status . ':', (string)$count);
    }

    $events = $data['events'] ?? [];
    if (count($events)) {
        pdfSectionTitle($pdf, 'قائمة الفعاليات');
        $rows = array_map(fn($e) => [
            $e['name'],
            $e['date'] ?? '—',
            $e['committee_name'] ?? '—',
            number_format((float)($e['budget'] ?? 0)),
            (string)($e['participants'] ?? 0),
            $e['status'] ?? '',
        ], $events);
        pdfTable($pdf, ['الفعالية', 'التاريخ', 'اللجنة', 'الميزانية', 'المشاركين', 'الحالة'], $rows, [35, 25, 30, 25, 22, 22]);
    }

    pdfFooter($pdf);
}

// =====================================================
// تنفيذ
// =====================================================

$pdf = initPDF();

match ($type) {
    'summary'       => buildSummaryPDF($pdf, $data),
    'financial'     => buildFinancialPDF($pdf, $data, $extraItems),
    'subscriptions' => buildSubscriptionsPDF($pdf, $data),
    'committees'    => buildCommitteesPDF($pdf, $data),
    'events'        => buildEventsPDF($pdf, $data),
};

logAudit('تصدير PDF', 'تقرير', $type, 'تقرير ' . $type);

$titles = [
    'summary'       => 'ملخص تنفيذي',
    'financial'     => 'تقرير مالي شامل',
    'subscriptions' => 'تقرير الاشتراكات',
    'committees'    => 'تقرير اللجان',
    'events'        => 'تقرير الفعاليات',
];
$filename = ($titles[$type] ?? 'تقرير') . '_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
exit;
