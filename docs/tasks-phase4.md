# المرحلة 4: تصدير PDF رسمي من السيرفر

# tasks.md for Claude Code

-----

## السياق

هذه المرحلة تبني تصدير PDF حقيقي من السيرفر باستخدام TCPDF — نفس المكتبة المستخدمة في `api/member/report.php` (تقرير العضو).

**المتطلب المسبق:** المراحل 1-3 مكتملة.

**المرجع التقني:** ملف `api/member/report.php` يحتوي كل الأنماط المطلوبة:

- إعداد TCPDF مع خطوط عربية
- ألوان التصميم (navy, green, gold)
- رأس أزرق داكن + خط ذهبي
- جداول بألوان متناوبة
- تذييل رسمي

**الألوان:**

```php
$navy  = [27, 52, 86];    // #1B3456
$green = [71, 145, 92];   // #47915C
$gold  = [200, 168, 75];  // #c8a84b
$white = [255, 255, 255];
$gray  = [100, 100, 100];
$lightGray = [240, 240, 240];
```

**الخطوط:** يتحقق من وجود `public/fonts/saudi-bold.ttf` و `saudi-normal.ttf`، وإلا يستخدم `dejavusans`.

**vendor/autoload.php:** TCPDF محمّل عبر `vendor/autoload.php` — تحقق من المسار الصحيح نسبة لملف الـ API الجديد.

-----

## المهمة 1: إنشاء API تصدير PDF

### ملف جديد: `api/report-pdf.php`

```php
<?php
/**
 * report-pdf.php — تصدير تقارير PDF رسمية
 *
 * GET ?type=summary|financial|subscriptions|committees|events
 *     &date_from=...&date_to=...&committee_id=...&period_id=...
 *     &extra_items=JSON (اختياري — بنود يدوية للتقرير المالي)
 *
 * يُعيد: ملف PDF مباشرة (Content-Type: application/pdf)
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';

// تحميل TCPDF — نفس المسار المستخدم في report.php
// تحقق من المسار الصحيح:
// في report.php المسار: __DIR__ . '/../../vendor/autoload.php'
// هنا (api/report-pdf.php) المسار: __DIR__ . '/../vendor/autoload.php'
// عدّل حسب الهيكل الفعلي
require_once __DIR__ . '/../vendor/autoload.php';

requireAuth();
verifyCsrf();
```

### 1.1 البنية العامة

```php
$type = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$committeeId = $_GET['committee_id'] ?? null;
$periodId = $_GET['period_id'] ?? null;
$extraItems = [];
if (!empty($_GET['extra_items'])) {
    $extraItems = json_decode($_GET['extra_items'], true) ?: [];
}

// جلب البيانات — نفس استعلامات report-generator.php
// (أعد استخدام نفس SQL أو أنشئ ملف مشترك)
$pdo = getPDO();

// ... جلب البيانات حسب $type ...

// بناء PDF
$pdf = initPDF();

match ($type) {
    'summary'       => buildSummaryPDF($pdf, $data),
    'financial'     => buildFinancialPDF($pdf, $data, $extraItems),
    'subscriptions' => buildSubscriptionsPDF($pdf, $data),
    'committees'    => buildCommitteesPDF($pdf, $data),
    'events'        => buildEventsPDF($pdf, $data),
    default         => respond(400, ['error' => 'نوع تقرير غير صالح']),
};

// تسجيل التدقيق
logAudit('تصدير PDF', 'تقرير', $type, 'تقرير ' . $type);

// إخراج PDF
$titles = [
    'summary' => 'ملخص تنفيذي',
    'financial' => 'تقرير مالي شامل',
    'subscriptions' => 'تقرير الاشتراكات',
    'committees' => 'تقرير اللجان',
    'events' => 'تقرير الفعاليات',
];
$filename = ($titles[$type] ?? 'تقرير') . '_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D'); // D = download
exit;
```

### 1.2 دالة initPDF() — إعداد مشترك

انسخ إعدادات PDF من `api/member/report.php` بالضبط:

```php
function initPDF(): TCPDF {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('مجلس عائلة العوامي');
    $pdf->SetAuthor('alawami.site');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->setRTL(true);

    // خطوط عربية — نفس المسارات من report.php
    // عدّل __DIR__ حسب موقع الملف الجديد
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
```

### 1.3 دالة مشتركة: رأس التقرير

```php
function pdfHeader(TCPDF $pdf, string $title, string $subtitle = ''): void {
    $navy = [27, 52, 86];
    $white = [255, 255, 255];
    $gold = [200, 168, 75];

    $pdf->AddPage();

    // رأس أزرق داكن
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

    // خط فاصل ذهبي
    $pdf->SetY(47);
    $pdf->SetDrawColor(...$gold);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, 47, 195, 47);
    $pdf->SetY(52);
}
```

### 1.4 دالة مشتركة: عنوان قسم

```php
function pdfSectionTitle(TCPDF $pdf, string $title): void {
    $navy = [27, 52, 86];
    $green = [71, 145, 92];

    $pdf->Ln(5);
    $pdf->SetTextColor(...$navy);
    $pdf->SetFont($GLOBALS['arabicFont'], '', 13);
    $pdf->Cell(0, 8, $title, 0, 1, 'R');
    $pdf->SetDrawColor(...$green);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
}
```

### 1.5 دالة مشتركة: جدول

```php
function pdfTable(TCPDF $pdf, array $headers, array $rows, array $colWidths): void {
    $navy = [27, 52, 86];
    $white = [255, 255, 255];
    $lightGray = [240, 240, 240];

    // رأس الجدول
    $pdf->SetFillColor(...$navy);
    $pdf->SetTextColor(...$white);
    $pdf->SetFont($GLOBALS['arabicFont'], '', 10);
    foreach ($headers as $i => $h) {
        $pdf->Cell($colWidths[$i], 8, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // صفوف البيانات
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 10);
    $fill = false;

    foreach ($rows as $row) {
        if ($fill) $pdf->SetFillColor(...$lightGray);

        // التحقق من page break
        if ($pdf->GetY() > 260) {
            $pdf->AddPage();
            // إعادة رسم رأس الجدول
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
            $pdf->Cell($colWidths[$i], 7, $cell, 1, 0, 'C', $fill);
        }
        $pdf->Ln();
        $fill = !$fill;
    }
}
```

### 1.6 دالة مشتركة: التذييل

```php
function pdfFooter(TCPDF $pdf): void {
    $gold = [200, 168, 75];
    $gray = [100, 100, 100];

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
```

-----

## المهمة 2: بناء كل نوع تقرير PDF

### 2.1 buildSummaryPDF — الملخص التنفيذي

```php
function buildSummaryPDF(TCPDF $pdf, array $data): void {
    pdfHeader($pdf, 'الملخص التنفيذي');

    $navy = [27, 52, 86];
    $green = [71, 145, 92];
    $gray = [100, 100, 100];

    // الوضع المالي
    pdfSectionTitle($pdf, 'الوضع المالي');
    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 11);
    $pdf->SetTextColor(...$gray);

    $fin = $data['financial'];
    $pdf->Cell(60, 7, 'إجمالي الإيرادات:', 0, 0, 'R');
    $pdf->SetFont($GLOBALS['arabicFont'], '', 11);
    $pdf->Cell(0, 7, number_format($fin['total_income']) . ' ريال', 0, 1, 'R');

    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 11);
    $pdf->Cell(60, 7, 'إجمالي المصروفات:', 0, 0, 'R');
    $pdf->SetFont($GLOBALS['arabicFont'], '', 11);
    $pdf->Cell(0, 7, number_format($fin['total_expense']) . ' ريال', 0, 1, 'R');

    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 11);
    $pdf->Cell(60, 7, 'صافي الرصيد:', 0, 0, 'R');
    $pdf->SetFont($GLOBALS['arabicFont'], '', 11);
    $netColor = $fin['net_balance'] >= 0 ? $green : [220, 50, 50];
    $pdf->SetTextColor(...$netColor);
    $pdf->Cell(0, 7, number_format($fin['net_balance']) . ' ريال', 0, 1, 'R');
    $pdf->SetTextColor(...$gray);

    // الاشتراكات
    pdfSectionTitle($pdf, 'الاشتراكات');
    $sub = $data['subscriptions'];
    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 11);
    $pdf->Cell(60, 7, 'نسبة التحصيل:', 0, 0, 'R');
    $pdf->SetFont($GLOBALS['arabicFont'], '', 11);
    $pdf->Cell(0, 7, $sub['collection_rate'] . '%', 0, 1, 'R');

    // الأعضاء
    pdfSectionTitle($pdf, 'الأعضاء');
    $m = $data['members'];
    $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 11);
    foreach ($m as $status => $count) {
        if ($status === 'total') continue;
        $pdf->Cell(60, 7, $status . ':', 0, 0, 'R');
        $pdf->SetFont($GLOBALS['arabicFont'], '', 11);
        $pdf->Cell(0, 7, $count . ' عضو', 0, 1, 'R');
        $pdf->SetFont($GLOBALS['arabicFontNormal'], '', 11);
    }

    // عُهَد اللجان
    pdfSectionTitle($pdf, 'عُهَد اللجان');
    $comHeaders = ['اللجنة', 'الميزانية', 'المصروف', 'المتبقي'];
    $comWidths = [60, 35, 35, 35];
    $comRows = array_map(function($c) {
        return [
            $c['name'],
            number_format($c['budget']) . ' ريال',
            number_format($c['spent']) . ' ريال',
            number_format($c['remaining']) . ' ريال',
        ];
    }, $data['committee_budgets']);
    if (count($comRows)) pdfTable($pdf, $comHeaders, $comRows, $comWidths);

    // الفعاليات القادمة
    if (!empty($data['upcoming_events'])) {
        pdfSectionTitle($pdf, 'الفعاليات القادمة');
        $evHeaders = ['الفعالية', 'التاريخ', 'اللجنة'];
        $evWidths = [70, 45, 50];
        $evRows = array_map(function($e) {
            return [$e['name'], $e['date'] ?? '—', $e['committee_name'] ?? '—'];
        }, $data['upcoming_events']);
        pdfTable($pdf, $evHeaders, $evRows, $evWidths);
    }

    pdfFooter($pdf);
}
```

### 2.2 buildFinancialPDF — التقرير المالي الشامل

مثل الملخص لكن بتفصيل أكثر:

- ملخص الصندوق (3 أسطر)
- جدول الإيرادات حسب الفئة (فئة | المبلغ | العدد | النسبة)
- جدول المصروفات حسب الفئة
- جدول عُهَد اللجان
- ملخص الاشتراكات (سطرين)
- **البنود اليدوية** (إذا وُجدت في `$extraItems`): قسم منفصل بعنوان “بنود إضافية (إدخال يدوي)”

### 2.3 buildSubscriptionsPDF — تقرير الاشتراكات

- إحصائيات الأعضاء (3-4 أسطر)
- جدول الفترات (الفترة | المطلوب | المحصّل | من دفع | من لم يدفع | النسبة)
- جدول المتخلفين (الاسم | الفترة | المبلغ) — إذا كان طويلاً يكمل في صفحة ثانية

### 2.4 buildCommitteesPDF — تقرير اللجان

- ملخص إجمالي (عدد اللجان + إجمالي الميزانيات + المصروف + المتبقي)
- لكل لجنة قسم: الاسم + الأعضاء (أسماء بنقاط) + الميزانية/المصروف/المتبقي + عدد الفعاليات

### 2.5 buildEventsPDF — تقرير الفعاليات

- إحصائيات حسب الحالة (4 أسطر)
- جدول الفعاليات (الفعالية | التاريخ | اللجنة | الميزانية | المشاركين | الحالة)
- إجمالي الميزانيات

**ملاحظة:** كل دالة build تتبع نفس النمط: `pdfHeader()` → أقسام بـ `pdfSectionTitle()` → جداول بـ `pdfTable()` → `pdfFooter()`.

-----

## المهمة 3: إعادة استخدام استعلامات البيانات

لتجنب تكرار SQL بين `report-generator.php` و `report-pdf.php`، أنشئ ملف مشترك:

### ملف جديد: `api/report-queries.php`

```php
<?php
/**
 * report-queries.php — استعلامات مشتركة للتقارير
 * يُستخدم من report-generator.php و report-pdf.php
 */
declare(strict_types=1);

function fetchFinancialData(PDO $pdo, ?string $dateFrom, ?string $dateTo, ?string $committeeId): array {
    // ... نفس SQL من report-generator.php handleFinancial() ...
    // يُرجع مصفوفة ['summary' => ..., 'income_by_category' => ..., ...]
}

function fetchSubscriptionsData(PDO $pdo, ?string $periodId): array {
    // ... نفس SQL من handleSubscriptions() ...
}

function fetchCommitteesData(PDO $pdo): array {
    // ... نفس SQL من handleCommittees() ...
}

function fetchEventsData(PDO $pdo, ?string $dateFrom, ?string $dateTo, ?string $committeeId): array {
    // ... نفس SQL من handleEvents() ...
}

function fetchSummaryData(PDO $pdo): array {
    // ... نفس SQL من handleSummary() ...
}
```

ثم في كلا الملفين:

```php
require_once __DIR__ . '/report-queries.php';
```

**بديل أبسط:** إذا كان استخراج الدوال يعقّد الأمور، يمكن أن يستدعي `report-pdf.php` الـ API نفسه داخلياً عبر `fetchFinancialData()` مباشرة بدل HTTP call.

-----

## المهمة 4: ربط زر التصدير في الواجهة

### الملف: `admin/js/admin-app.js`

استبدل دالة `exportReportPDF()` (الـ placeholder من المرحلة 3):

```javascript
function exportReportPDF() {
  if (!autoReportData || !autoReportType) {
    toast('ولّد التقرير أولاً', 'error');
    return;
  }

  var params = '?type=' + autoReportType;
  var dateFrom = document.getElementById('ar-date-from')?.value;
  var dateTo = document.getElementById('ar-date-to')?.value;
  var committee = document.getElementById('ar-committee')?.value;
  var period = document.getElementById('ar-period')?.value;
  if (dateFrom) params += '&date_from=' + dateFrom;
  if (dateTo) params += '&date_to=' + dateTo;
  if (committee) params += '&committee_id=' + committee;
  if (period) params += '&period_id=' + period;

  // إرسال البنود اليدوية إذا كان التقرير المالي
  if (autoReportType === 'financial' && autoReportExtraItems.length > 0) {
    params += '&extra_items=' + encodeURIComponent(JSON.stringify(autoReportExtraItems));
  }

  // فتح رابط التحميل
  window.open('/api/report-pdf.php' + params, '_blank');
}
```

**ملاحظة:** الـ API يتحقق من الجلسة والـ CSRF. لكن `window.open` يرسل cookies تلقائياً. بالنسبة لـ CSRF، بما أن هذا GET request وليس POST، قد لا يحتاج CSRF token. تحقق:

- إذا `verifyCsrf()` تتحقق فقط من POST/PUT/DELETE → لا مشكلة
- إذا تتحقق من كل الطلبات → أضف token كـ query parameter أو استثنِ GET من التحقق في هذا الملف

-----

## المهمة 5: الاختبار

### 5.1 اختبار مباشر

1. ولّد تقرير مالي شامل → انقر “تصدير PDF” → يُحمّل ملف PDF
1. افتح الـ PDF → تحقق:
- الرأس أزرق داكن مع “مجلس عائلة العوامي”
- العنوان صحيح (مثل “تقرير مالي شامل”)
- الخط عربي مقروء
- الجداول بألوان متناوبة
- التذييل موجود
- النص RTL صحيح
1. جرّب كل نوع تقرير → كلهم يُنتجون PDF
1. أضف بنود يدوية في المالي → تظهر في PDF
1. استخدم فلاتر تاريخ → البيانات في PDF تتغير
1. طبع PDF → يطلع بتنسيق مقبول

### 5.2 حالات حدية

- لا توجد بيانات → PDF فارغ مع عناوين وتذييل (لا خطأ)
- جدول طويل (100+ صف) → يمتد على صفحات متعددة مع رأس جدول في كل صفحة
- تقرير المتخلفين كبير → pagination صحيح
- أحرف عربية خاصة (همزة، تنوين) → تظهر صحيحة

### 5.3 فحص المسارات

```bash
# تحقق أن TCPDF محمّل
php -r "require 'vendor/autoload.php'; echo class_exists('TCPDF') ? 'OK' : 'MISSING';"

# تحقق أن الخطوط موجودة
ls -la public/fonts/saudi-*.ttf

# تحقق أن الملف يعمل
curl -s -o /dev/null -w "%{http_code}" "https://alawami.site/api/report-pdf.php?type=summary" \
  -H "X-CSRF-Token: ..." --cookie "..."
# المتوقع: 200
```
