<?php
/**
 * report.php — توليد تقرير PDF رسمي للعضو
 *
 * GET → إرجاع ملف PDF يحتوي:
 *   - شعار المجلس + عنوان التقرير
 *   - البيانات الشخصية
 *   - اللجان الحالية والسابقة
 *   - سجل الاشتراكات + الملخص المالي
 *   - تذييل رسمي
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/member-guard.php';
require_once __DIR__ . '/../audit_helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    respond(405, ['error' => 'طريقة الطلب غير مسموحة']);
}

$member   = requireMemberAuth();
$pdo      = getPDO();
$memberId = $member['member_id'];

// ══════════════════════════════════════════
// جلب البيانات
// ══════════════════════════════════════════

// البيانات الأساسية
$stmt = $pdo->prepare(
    'SELECT m.name, m.family, m.phone, m.id_num, m.join_date, m.status,
            mu.awm_id, b.name AS branch_name
     FROM members m
     JOIN member_users mu ON mu.member_id = m.id
     LEFT JOIN branches b ON b.id = m.branch_id
     WHERE m.id = :id LIMIT 1'
);
$stmt->execute([':id' => $memberId]);
$info = $stmt->fetch();

if (!$info) {
    respond(404, ['error' => 'لم يتم العثور على بيانات العضو']);
}

// اللجان الحالية
$stmt = $pdo->prepare(
    'SELECT c.name, cm.role, cm.start_year
     FROM committee_members cm JOIN committees c ON c.id = cm.committee_id
     WHERE cm.member_id = :id AND cm.end_year IS NULL ORDER BY c.name'
);
$stmt->execute([':id' => $memberId]);
$currentCommittees = $stmt->fetchAll();

// اللجان السابقة
$stmt = $pdo->prepare(
    'SELECT c.name, cm.role, cm.start_year, cm.end_year
     FROM committee_members cm JOIN committees c ON c.id = cm.committee_id
     WHERE cm.member_id = :id AND cm.end_year IS NOT NULL ORDER BY cm.end_year DESC'
);
$stmt->execute([':id' => $memberId]);
$pastCommittees = $stmt->fetchAll();

// سجل الاشتراكات
$stmt = $pdo->prepare(
    'SELECT p.name AS period_name, p.fee_amount,
            pay.amount AS paid_amount, pay.pay_date, pay.status AS pay_status
     FROM periods p
     LEFT JOIN payments pay ON pay.period_id = p.id AND pay.member_id = :id
     ORDER BY p.start_date ASC'
);
$stmt->execute([':id' => $memberId]);
$subscriptions = $stmt->fetchAll();

$totalRequired = 0.0;
$totalPaid     = 0.0;
foreach ($subscriptions as &$sub) {
    $totalRequired += (float) ($sub['fee_amount'] ?? 0);
    $totalPaid     += (float) ($sub['paid_amount'] ?? 0);
    if ($sub['pay_status'] === null) {
        $sub['pay_status'] = 'لم يدفع';
    }
}
unset($sub);
$complianceRate = $totalRequired > 0 ? round(($totalPaid / $totalRequired) * 100, 1) : 0;

// ══════════════════════════════════════════
// بناء PDF
// ══════════════════════════════════════════

// ألوان التصميم
$navy  = [27, 52, 86];    // #1B3456
$green = [71, 145, 92];   // #47915C
$gold  = [200, 168, 75];  // #c8a84b
$white = [255, 255, 255];
$gray  = [100, 100, 100];
$lightGray = [240, 240, 240];

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// إعدادات المستند
$pdf->SetCreator('مجلس عائلة العوامي');
$pdf->SetAuthor('alawami.site');
$pdf->SetTitle('تقرير العضو — ' . $info['awm_id']);
$pdf->SetSubject('تقرير العضو الرسمي');

// إلغاء الرأس والتذييل الافتراضي
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// الهوامش
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 25);

// اتجاه RTL
$pdf->setRTL(true);

// إضافة خط عربي — استخدام الخط المحلي إذا متوفر
$fontPath = __DIR__ . '/../../public/fonts/saudi-bold.ttf';
if (is_file($fontPath)) {
    $arabicFont = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
} else {
    $arabicFont = 'dejavusans';
}

$fontPathNormal = __DIR__ . '/../../public/fonts/saudi-normal.ttf';
if (is_file($fontPathNormal)) {
    $arabicFontNormal = TCPDF_FONTS::addTTFfont($fontPathNormal, 'TrueTypeUnicode', '', 96);
} else {
    $arabicFontNormal = 'dejavusans';
}

$pdf->AddPage();

// ── رأس التقرير ──
$pdf->SetFillColor(...$navy);
$pdf->Rect(0, 0, 210, 45, 'F');

$pdf->SetTextColor(...$white);
$pdf->SetFont($arabicFont, '', 18);
$pdf->SetY(10);
$pdf->Cell(0, 10, 'مجلس عائلة العوامي', 0, 1, 'C');

$pdf->SetFont($arabicFont, '', 14);
$pdf->Cell(0, 8, 'تقرير العضو الرسمي', 0, 1, 'C');

$pdf->SetFont($arabicFontNormal, '', 9);
$pdf->SetTextColor(...$gold);
$issueDate = date('Y/m/d');
$pdf->Cell(0, 6, "تاريخ الإصدار: {$issueDate}  |  {$info['awm_id']}", 0, 1, 'C');

// ── خط فاصل ذهبي ──
$pdf->SetY(47);
$pdf->SetDrawColor(...$gold);
$pdf->SetLineWidth(0.8);
$pdf->Line(15, 47, 195, 47);

// ── البيانات الشخصية ──
$pdf->SetY(52);
$pdf->SetTextColor(...$navy);
$pdf->SetFont($arabicFont, '', 13);
$pdf->Cell(0, 8, 'البيانات الشخصية', 0, 1, 'R');

$pdf->SetDrawColor(...$green);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(3);

$pdf->SetTextColor(...$gray);
$pdf->SetFont($arabicFontNormal, '', 11);

$fullName = trim($info['name'] . ' ' . $info['family']);
$statusLabel = getStatusLabel($info['status']);
$personalData = [
    'الاسم' => $fullName,
    'رقم الهوية' => $info['id_num'] ?: '—',
    'الجوال' => $info['phone'] ?: '—',
    'الفرع' => $info['branch_name'] ?: '—',
    'تاريخ الانضمام' => $info['join_date'] ?: '—',
    'حالة العضوية' => $statusLabel,
];

foreach ($personalData as $label => $value) {
    $pdf->SetFont($arabicFont, '', 11);
    $pdf->Cell(40, 7, $label . ':', 0, 0, 'R');
    $pdf->SetFont($arabicFontNormal, '', 11);
    $pdf->Cell(0, 7, $value, 0, 1, 'R');
}

// ── اللجان ──
$pdf->Ln(5);
$pdf->SetTextColor(...$navy);
$pdf->SetFont($arabicFont, '', 13);
$pdf->Cell(0, 8, 'العضوية في اللجان', 0, 1, 'R');
$pdf->SetDrawColor(...$green);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(3);

$pdf->SetTextColor(...$gray);
$pdf->SetFont($arabicFontNormal, '', 11);

if (count($currentCommittees) > 0) {
    $pdf->SetFont($arabicFont, '', 11);
    $pdf->Cell(0, 7, 'الحالية:', 0, 1, 'R');
    $pdf->SetFont($arabicFontNormal, '', 10);
    foreach ($currentCommittees as $c) {
        $role = $c['role'] ? " ({$c['role']})" : '';
        $year = $c['start_year'] ? " — منذ {$c['start_year']}" : '';
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, "• {$c['name']}{$role}{$year}", 0, 1, 'R');
    }
} else {
    $pdf->Cell(0, 7, 'لا توجد عضوية حالية في لجان', 0, 1, 'R');
}

if (count($pastCommittees) > 0) {
    $pdf->Ln(2);
    $pdf->SetFont($arabicFont, '', 11);
    $pdf->Cell(0, 7, 'السابقة:', 0, 1, 'R');
    $pdf->SetFont($arabicFontNormal, '', 10);
    foreach ($pastCommittees as $c) {
        $role = $c['role'] ? " ({$c['role']})" : '';
        $years = ($c['start_year'] ?? '?') . ' - ' . ($c['end_year'] ?? '?');
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, "• {$c['name']}{$role} — {$years}", 0, 1, 'R');
    }
}

// ── سجل الاشتراكات ──
$pdf->Ln(5);
$pdf->SetTextColor(...$navy);
$pdf->SetFont($arabicFont, '', 13);
$pdf->Cell(0, 8, 'سجل الاشتراكات', 0, 1, 'R');
$pdf->SetDrawColor(...$green);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(3);

if (count($subscriptions) > 0) {
    // رأس الجدول
    $colWidths = [50, 30, 30, 30, 40];
    $headers = ['الفترة', 'المطلوب', 'المدفوع', 'الحالة', 'تاريخ الدفع'];

    $pdf->SetFillColor(...$navy);
    $pdf->SetTextColor(...$white);
    $pdf->SetFont($arabicFont, '', 10);

    foreach ($headers as $i => $h) {
        $pdf->Cell($colWidths[$i], 8, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // صفوف البيانات
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetFont($arabicFontNormal, '', 10);
    $fill = false;

    foreach ($subscriptions as $sub) {
        if ($fill) {
            $pdf->SetFillColor(...$lightGray);
        }

        $payStatus = $sub['pay_status'];
        $paidDisplay = $payStatus === 'مدفوع'
            ? number_format((float) $sub['paid_amount']) . ' ريال'
            : '—';
        $reqDisplay = number_format((float) $sub['fee_amount']) . ' ريال';
        $dateDisplay = $sub['pay_date'] ?: '—';

        $pdf->Cell($colWidths[0], 7, $sub['period_name'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[1], 7, $reqDisplay, 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[2], 7, $paidDisplay, 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[3], 7, $payStatus, 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[4], 7, $dateDisplay, 1, 0, 'C', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }

    // ملخص مالي
    $pdf->Ln(3);
    $pdf->SetFont($arabicFont, '', 11);
    $pdf->SetTextColor(...$navy);
    $pdf->Cell(0, 7, 'إجمالي المدفوع: ' . number_format($totalPaid) . ' ريال  |  إجمالي المطلوب: ' . number_format($totalRequired) . ' ريال  |  نسبة الالتزام: ' . $complianceRate . '%', 0, 1, 'C');
} else {
    $pdf->SetTextColor(...$gray);
    $pdf->SetFont($arabicFontNormal, '', 11);
    $pdf->Cell(0, 7, 'لا توجد فترات مالية مسجلة', 0, 1, 'R');
}

// ── التذييل ──
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
$pdf->SetFont($arabicFontNormal, '', 9);
$pdf->Cell(0, 5, 'صدر من: الموقع الرسمي لعائلة العوامي', 0, 1, 'C');
$pdf->Cell(0, 5, 'هذا التقرير معلوماتي  |  alawami.site', 0, 1, 'C');

// ── تسجيل التدقيق ──
logAudit('طباعة تقرير', 'member_report', $memberId, $info['awm_id']);

// ── إخراج PDF ──
$filename = "report_{$info['awm_id']}_" . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
exit;


/**
 * إرجاع تسمية الحالة مع رمز
 */
function getStatusLabel(string $status): string
{
    return match ($status) {
        'نشط'     => 'نشط ✅',
        'منقطع'    => 'منقطع ⚠️',
        'غير نشط' => 'غير نشط ❌',
        'معفي'     => 'معفي 🔒',
        default    => $status,
    };
}
