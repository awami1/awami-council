# المرحلة 3: التقارير التلقائية من بيانات النظام

# tasks.md for Claude Code

-----

## السياق

هذه المرحلة تبني تبويب “تقارير تلقائية” — تقارير تُولَّد فوراً من بيانات النظام بضغطة زر.
**المتطلب المسبق:** تنفيذ المرحلة 1 (الهيكلة) والمرحلة 2 (الاستيراد).

**لا جداول جديدة** — كل البيانات تُسحب من الجداول الموجودة بـ SQL aggregation.

**الجداول المصدرية:**

- `transactions` — المعاملات المالية (type: إيراد/مصروف, amount, category, committee_id, tx_date)
- `payments` — مدفوعات الاشتراكات (member_id, period_id, amount, status, pay_date)
- `periods` — الفترات المالية (name, fee_amount, start_date, end_date)
- `members` — الأعضاء (name, status, branch_id)
- `committees` — اللجان (name, budget, icon, color)
- `committee_members` — أعضاء اللجان (member_id, committee_id, role, start_year, end_year)
- `events` — الفعاليات (name, date, status, budget, participants, committee_id)

**ملاحظة عن `committees.budget`:** هذا حقل الميزانية المخصصة لكل لجنة. تحقق أولاً من وجوده:

```bash
grep -n "budget" api/committees.php
```

إذا لم يكن موجوداً كحقل في الجدول، استخدم 0 كقيمة افتراضية واترك ملاحظة.

-----

## المهمة 1: إنشاء API التقارير التلقائية

### ملف جديد: `api/report-generator.php`

أنشئ ملف يتبع نفس نمط الـ APIs الموجودة (require config, auth_guard, verifyCsrf).

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
requireAuth();
verifyCsrf();

$type = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$committeeId = $_GET['committee_id'] ?? null;
$periodId = $_GET['period_id'] ?? null;

// الفلاتر المشتركة
// ... (بناء WHERE clauses حسب الفلاتر)

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
```

### 1.1 handleFinancial() — التقرير المالي الشامل

```php
function handleFinancial(): void {
    $pdo = getPDO();
    // ... (استخدم $GLOBALS أو params أعلى للفلاتر)

    // بناء شرط التاريخ
    $dateWhere = '';
    $params = [];
    if ($dateFrom) { $dateWhere .= ' AND tx_date >= :date_from'; $params[':date_from'] = $dateFrom; }
    if ($dateTo) { $dateWhere .= ' AND tx_date <= :date_to'; $params[':date_to'] = $dateTo; }
    $committeeWhere = '';
    if ($committeeId) { $committeeWhere = ' AND committee_id = :cid'; $params[':cid'] = $committeeId; }

    // 1) ملخص الصندوق
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN type = 'إيراد' THEN amount ELSE 0 END), 0) AS total_income,
            COALESCE(SUM(CASE WHEN type = 'مصروف' THEN amount ELSE 0 END), 0) AS total_expense
        FROM transactions
        WHERE 1=1 {$dateWhere} {$committeeWhere}
    ");
    $stmt->execute($params);
    $summary = $stmt->fetch();
    $summary['net_balance'] = $summary['total_income'] - $summary['total_expense'];

    // 2) إيرادات حسب الفئة
    $stmt = $pdo->prepare("
        SELECT category, SUM(amount) AS amount, COUNT(*) AS count
        FROM transactions
        WHERE type = 'إيراد' {$dateWhere} {$committeeWhere}
        GROUP BY category
        ORDER BY amount DESC
    ");
    $stmt->execute($params);
    $incomeByCategory = $stmt->fetchAll();
    // حساب النسب المئوية
    foreach ($incomeByCategory as &$row) {
        $row['percentage'] = $summary['total_income'] > 0
            ? round(($row['amount'] / $summary['total_income']) * 100, 1) : 0;
    }

    // 3) مصروفات حسب الفئة
    $stmt = $pdo->prepare("
        SELECT category, SUM(amount) AS amount, COUNT(*) AS count
        FROM transactions
        WHERE type = 'مصروف' {$dateWhere} {$committeeWhere}
        GROUP BY category
        ORDER BY amount DESC
    ");
    $stmt->execute($params);
    $expenseByCategory = $stmt->fetchAll();
    foreach ($expenseByCategory as &$row) {
        $row['percentage'] = $summary['total_expense'] > 0
            ? round(($row['amount'] / $summary['total_expense']) * 100, 1) : 0;
    }

    // 4) عُهَد اللجان
    // لكل لجنة: الميزانية المخصصة - المصروف الفعلي
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.icon,
            COALESCE(c.budget, 0) AS budget,
            COALESCE(t.spent, 0) AS spent
        FROM committees c
        LEFT JOIN (
            SELECT committee_id, SUM(amount) AS spent
            FROM transactions
            WHERE type = 'مصروف' {$dateWhere}
            GROUP BY committee_id
        ) t ON t.committee_id = c.id
        ORDER BY c.name
    ");
    $stmt->execute($params); // ملاحظة: params قد تحتاج تعديل لأن الـ subquery يستخدم date params فقط
    $committeeBudgets = $stmt->fetchAll();
    foreach ($committeeBudgets as &$row) {
        $row['remaining'] = $row['budget'] - $row['spent'];
    }

    // 5) ملخص الاشتراكات
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
    $subSummary['collection_rate'] = $subSummary['total_due'] > 0
        ? round(($subSummary['total_paid'] / $subSummary['total_due']) * 100, 1) : 0;

    respond(200, ['data' => [
        'generated_at' => date('c'),
        'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo, 'committee_id' => $committeeId],
        'summary' => $summary,
        'income_by_category' => $incomeByCategory,
        'expense_by_category' => $expenseByCategory,
        'committee_budgets' => $committeeBudgets,
        'subscription_summary' => $subSummary,
    ]]);
}
```

### 1.2 handleSubscriptions() — تقرير الاشتراكات

```php
function handleSubscriptions(): void {
    $pdo = getPDO();

    // 1) إحصائيات الأعضاء حسب الحالة
    $stmt = $pdo->query("
        SELECT status, COUNT(*) AS count
        FROM members
        GROUP BY status
    ");
    $memberStats = [];
    foreach ($stmt->fetchAll() as $row) {
        $memberStats[$row['status']] = (int)$row['count'];
    }
    $memberStats['total'] = array_sum($memberStats);

    // 2) لكل فترة: عدد من دفع / لم يدفع / نسبة الالتزام
    $activeCount = ($memberStats['مشترك'] ?? 0) + ($memberStats['نشط'] ?? 0);

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

    // 3) قائمة المتخلفين (آخر فترة أو فترة محددة)
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
        'generated_at' => date('c'),
        'members' => $memberStats,
        'periods' => $periods,
        'defaulters' => $defaulters,
        'defaulters_period' => $targetPeriod,
    ]]);
}
```

### 1.3 handleCommittees() — تقرير اللجان

```php
function handleCommittees(): void {
    $pdo = getPDO();

    $stmt = $pdo->query("
        SELECT c.id, c.name, c.icon, c.color, c.desc,
            COALESCE(c.budget, 0) AS budget,
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

    // أعضاء كل لجنة
    foreach ($committees as &$c) {
        $c['remaining'] = (float)$c['budget'] - (float)$c['spent'];

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

    // ملخص إجمالي
    $totalBudget = array_sum(array_column($committees, 'budget'));
    $totalSpent = array_sum(array_column($committees, 'spent'));

    respond(200, ['data' => [
        'generated_at' => date('c'),
        'committees' => $committees,
        'summary' => [
            'total_committees' => count($committees),
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalBudget - $totalSpent,
        ],
    ]]);
}
```

### 1.4 handleEvents() — تقرير الفعاليات

```php
function handleEvents(): void {
    $pdo = getPDO();

    // بناء فلاتر
    $where = '1=1';
    $params = [];
    if ($dateFrom) { $where .= ' AND e.date >= :df'; $params[':df'] = $dateFrom; }
    if ($dateTo) { $where .= ' AND e.date <= :dt'; $params[':dt'] = $dateTo; }
    if ($committeeId) { $where .= ' AND e.committee_id = :cid'; $params[':cid'] = $committeeId; }

    // إحصائيات حسب الحالة
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS count
        FROM events e WHERE {$where}
        GROUP BY status
    ");
    $stmt->execute($params);
    $statusStats = [];
    foreach ($stmt->fetchAll() as $row) {
        $statusStats[$row['status']] = (int)$row['count'];
    }

    // قائمة الفعاليات
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, e.date, e.status, e.budget, e.participants, e.icon,
            c.name AS committee_name
        FROM events e
        LEFT JOIN committees c ON c.id = e.committee_id
        WHERE {$where}
        ORDER BY e.date DESC
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    $totalBudget = array_sum(array_column($events, 'budget'));
    $totalParticipants = array_sum(array_column($events, 'participants'));

    respond(200, ['data' => [
        'generated_at' => date('c'),
        'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo, 'committee_id' => $committeeId],
        'status_stats' => $statusStats,
        'events' => $events,
        'summary' => [
            'total_events' => count($events),
            'total_budget' => $totalBudget,
            'total_participants' => $totalParticipants,
        ],
    ]]);
}
```

### 1.5 handleSummary() — الملخص التنفيذي

يجمع أهم الأرقام من كل التقارير أعلاه. **لا يستدعي الدوال الأخرى مباشرة** — يكتب SQL خفيف يجلب فقط الأرقام الرئيسية:

```php
function handleSummary(): void {
    $pdo = getPDO();

    // رصيد الصندوق
    $fin = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN type = 'إيراد' THEN amount ELSE 0 END), 0) AS total_income,
            COALESCE(SUM(CASE WHEN type = 'مصروف' THEN amount ELSE 0 END), 0) AS total_expense
        FROM transactions
    ")->fetch();
    $fin['net_balance'] = $fin['total_income'] - $fin['total_expense'];

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
    $sub['collection_rate'] = $sub['total_due'] > 0
        ? round(($sub['total_paid'] / $sub['total_due']) * 100, 1) : 0;

    // عُهَد اللجان (ملخص)
    $comms = $pdo->query("
        SELECT c.id, c.name, COALESCE(c.budget,0) AS budget,
            COALESCE(t.spent,0) AS spent
        FROM committees c
        LEFT JOIN (
            SELECT committee_id, SUM(amount) AS spent
            FROM transactions WHERE type = 'مصروف'
            GROUP BY committee_id
        ) t ON t.committee_id = c.id
        ORDER BY c.name
    ")->fetchAll();
    foreach ($comms as &$c) { $c['remaining'] = $c['budget'] - $c['spent']; }

    // فعاليات قادمة
    $upcoming = $pdo->query("
        SELECT e.name, e.date, c.name AS committee_name
        FROM events e
        LEFT JOIN committees c ON c.id = e.committee_id
        WHERE e.status IN ('قادم', 'جاري')
        ORDER BY e.date ASC
        LIMIT 5
    ")->fetchAll();

    respond(200, ['data' => [
        'generated_at' => date('c'),
        'financial' => $fin,
        'members' => $members,
        'subscriptions' => $sub,
        'committee_budgets' => $comms,
        'upcoming_events' => $upcoming,
    ]]);
}
```

### 1.6 ملاحظات تقنية مهمة

- **الفلاتر:** اجعل `$dateFrom`, `$dateTo`, `$committeeId`, `$periodId` متاحة لكل الدوال (إما `global` أو مرر كـ parameters).
- **MySQL vs SQLite:** الاستعلامات أعلاه متوافقة مع الاثنين. لكن تحقق من `CROSS JOIN` — في SQLite يعمل كـ `CROSS JOIN` عادي.
- **حقل `status` في members:** القيم الحالية قد تكون `مشترك`, `منقطع`, `غير مشترك` (الجديدة) أو `نشط`, `معفي`, `غير نشط` (القديمة). اجعل استعلام الاشتراكات يشمل الاثنين: `WHERE m.status IN ('مشترك', 'نشط')`.
- **حقل `committee_id` في transactions:** تأكد من اسم العمود الفعلي — قد يكون `committee` بدلاً من `committee_id`. تحقق:

```bash
grep -n "committee" api/transactions.php | head -20
```

-----

## المهمة 2: بناء واجهة التقارير التلقائية

### الملف: `admin/js/admin-app.js`

استبدل دالة `renderAutoReportsTab()` (الـ placeholder) بالكود الفعلي.

### 2.1 حالة التبويب

```javascript
var autoReportType = '';        // النوع المختار: summary, financial, subscriptions, committees, events
var autoReportData = null;      // بيانات التقرير المُولَّد
var autoReportLoading = false;
var autoReportExtraItems = [];  // البنود اليدوية الإضافية
```

### 2.2 واجهة الاختيار

`renderAutoReportsTab()` تعرض:

**بطاقات اختيار نوع التقرير** (5 بطاقات في صف، أو 2-3 على الموبايل):

```javascript
var reportTypes = [
  { key: 'summary',       icon: '📋', label: 'ملخص تنفيذي',     desc: 'أهم الأرقام في صفحة واحدة' },
  { key: 'financial',     icon: '💰', label: 'مالي شامل',        desc: 'إيرادات ومصروفات وعُهَد اللجان' },
  { key: 'subscriptions', icon: '💳', label: 'الاشتراكات',       desc: 'من دفع ومن لم يدفع' },
  { key: 'committees',    icon: '👥', label: 'اللجان',           desc: 'الأعضاء والميزانيات' },
  { key: 'events',        icon: '🎪', label: 'الفعاليات',        desc: 'الأنشطة والميزانيات' },
];
```

كل بطاقة: أيقونة + عنوان + وصف مختصر. عند النقر → `autoReportType = key` وتُعاد الرسم.

**الفلاتر** (تظهر بعد اختيار نوع):

```html
<div class="auto-report-filters">
  <label>من: <input type="date" id="ar-date-from"></label>
  <label>إلى: <input type="date" id="ar-date-to"></label>
  <label>اللجنة: <select id="ar-committee">
    <option value="">الكل</option>
    <!-- يُملأ من State.getCommittees() -->
  </select></label>
  <label>الفترة: <select id="ar-period">
    <option value="">الكل</option>
    <!-- يُملأ من State.getPeriods() أو apiFetch -->
  </select></label>
  <button class="btn btn-primary" onclick="generateAutoReport()">🔄 توليد التقرير</button>
</div>
```

**لا تظهر كل الفلاتر دائماً:**

- `summary` + `financial` → كل الفلاتر
- `subscriptions` → فقط فلتر الفترة
- `committees` → بدون فلاتر
- `events` → تاريخ + لجنة

### 2.3 دالة generateAutoReport()

```javascript
function generateAutoReport() {
  autoReportLoading = true;
  renderAutoReportResults(); // يعرض spinner

  var params = '?type=' + autoReportType;
  var dateFrom = document.getElementById('ar-date-from')?.value;
  var dateTo = document.getElementById('ar-date-to')?.value;
  var committee = document.getElementById('ar-committee')?.value;
  var period = document.getElementById('ar-period')?.value;
  if (dateFrom) params += '&date_from=' + dateFrom;
  if (dateTo) params += '&date_to=' + dateTo;
  if (committee) params += '&committee_id=' + committee;
  if (period) params += '&period_id=' + period;

  apiFetch('/api/report-generator.php' + params)
    .then(function(res) {
      autoReportData = res.data;
      autoReportLoading = false;
      renderAutoReportResults();
    })
    .catch(function(err) {
      autoReportLoading = false;
      toast('خطأ في توليد التقرير: ' + err.message, 'error');
      renderAutoReportResults();
    });
}
```

-----

## المهمة 3: عرض نتائج كل تقرير

### 3.1 دالة renderAutoReportResults()

تُنشئ div تحت الفلاتر وتملأه حسب `autoReportType`:

```javascript
function renderAutoReportResults() {
  var container = document.getElementById('ar-results');
  if (!container) return;

  if (autoReportLoading) {
    container.innerHTML = '<div class="loading-spinner" style="text-align:center;padding:40px">⏳ جاري توليد التقرير...</div>';
    return;
  }
  if (!autoReportData) {
    container.innerHTML = '';
    return;
  }

  var html = '';
  switch (autoReportType) {
    case 'summary':       html = renderSummaryReport(autoReportData); break;
    case 'financial':     html = renderFinancialReport(autoReportData); break;
    case 'subscriptions': html = renderSubscriptionsReport(autoReportData); break;
    case 'committees':    html = renderCommitteesReport(autoReportData); break;
    case 'events':        html = renderEventsReport(autoReportData); break;
  }

  // أزرار التصدير
  html += '<div class="ar-actions" style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">';
  html += '<button class="btn btn-primary" onclick="exportReportPDF()">📄 تصدير PDF</button>';
  html += '<button class="btn btn-outline" onclick="printReport()">🖨️ طباعة</button>';
  html += '</div>';

  container.innerHTML = html;
}
```

### 3.2 renderSummaryReport(data) — الملخص التنفيذي

يعرض:

- **4 بطاقات إحصائية** في صف: رصيد الصندوق، إجمالي الإيرادات، إجمالي المصروفات، صافي الرصيد
- **بطاقة الاشتراكات:** نسبة التحصيل (progress bar دائري أو خطي) + المحصّل / المستحق
- **بطاقة الأعضاء:** عدد مشترك / منقطع / غير مشترك
- **جدول عُهَد اللجان:** اللجنة | الميزانية | المصروف | المتبقي
- **قائمة الفعاليات القادمة:** اسم + تاريخ + اللجنة

استخدم نفس أنماط البطاقات الموجودة في الـ admin (مثل بطاقات الـ dashboard).

### 3.3 renderFinancialReport(data)

- 4 بطاقات: إيرادات، مصروفات، صافي، عدد المعاملات
- جدول الإيرادات حسب الفئة (فئة | المبلغ | العدد | النسبة)
- جدول المصروفات حسب الفئة
- جدول عُهَد اللجان
- ملخص الاشتراكات
- **قسم البنود اليدوية** (في التقرير المالي فقط)

### 3.4 البنود اليدوية (في التقرير المالي)

```html
<div class="ar-extra-items">
  <h4>📝 بنود إضافية (اختياري)</h4>
  <p style="font-size:12px;color:var(--text-muted)">أضف أرقاماً غير موجودة في النظام — تظهر في التقرير فقط ولا تُحفظ في قاعدة البيانات</p>
  <div id="ar-extra-list">
    <!-- يُملأ برمجياً -->
  </div>
  <div style="display:flex;gap:8px;align-items:center;margin-top:8px">
    <input type="text" id="ar-extra-label" placeholder="البند (مثال: رصيد بنكي)" class="form-control" style="flex:2">
    <input type="number" id="ar-extra-amount" placeholder="المبلغ" class="form-control" style="flex:1">
    <select id="ar-extra-type" class="form-control" style="flex:1">
      <option value="إيراد">إيراد</option>
      <option value="مصروف">مصروف</option>
      <option value="معلومة">معلومة</option>
    </select>
    <button class="btn btn-primary btn-sm" onclick="addExtraItem()">+ إضافة</button>
  </div>
</div>
```

```javascript
function addExtraItem() {
  var label = document.getElementById('ar-extra-label').value.trim();
  var amount = parseFloat(document.getElementById('ar-extra-amount').value) || 0;
  var type = document.getElementById('ar-extra-type').value;
  if (!label) { toast('أدخل اسم البند', 'error'); return; }
  autoReportExtraItems.push({ label: label, amount: amount, type: type });
  document.getElementById('ar-extra-label').value = '';
  document.getElementById('ar-extra-amount').value = '';
  renderExtraItemsList();
}

function removeExtraItem(idx) {
  autoReportExtraItems.splice(idx, 1);
  renderExtraItemsList();
}

function renderExtraItemsList() {
  var el = document.getElementById('ar-extra-list');
  if (!autoReportExtraItems.length) { el.innerHTML = ''; return; }
  el.innerHTML = autoReportExtraItems.map(function(item, i) {
    return '<div style="display:flex;gap:8px;align-items:center;padding:6px 0;border-bottom:1px solid var(--border)">' +
      '<span style="flex:2">' + item.label + '</span>' +
      '<span style="flex:1;font-weight:700;color:' + (item.type === 'مصروف' ? 'var(--danger)' : 'var(--green)') + '">' +
        item.amount.toLocaleString('ar-SA') + ' ريال</span>' +
      '<span style="font-size:11px;color:var(--text-muted)">' + item.type + '</span>' +
      '<button class="btn btn-xs" style="color:var(--danger)" onclick="removeExtraItem(' + i + ')">✕</button></div>';
  }).join('');
}
```

### 3.5 renderSubscriptionsReport, renderCommitteesReport, renderEventsReport

بنفس النمط — بطاقات إحصائية + جداول. كل دالة تأخذ `data` وتُرجع HTML string.

**تقرير الاشتراكات:**

- بطاقات: عدد الأعضاء حسب الحالة
- جدول الفترات: الفترة | المطلوب | المحصّل | المتبقي | نسبة الالتزام
- جدول المتخلفين: الاسم | الفترة | المبلغ

**تقرير اللجان:**

- بطاقات: عدد اللجان، إجمالي الميزانيات، إجمالي المصروف
- لكل لجنة: بطاقة بـ الاسم + الأعضاء + الميزانية/المصروف/المتبقي + عدد الفعاليات

**تقرير الفعاليات:**

- بطاقات حسب الحالة (قادم/جاري/مكتمل/ملغي)
- جدول: الفعالية | التاريخ | اللجنة | الميزانية | المشاركين | الحالة

-----

## المهمة 4: الطباعة

### 4.1 دالة printReport()

تفتح نافذة جديدة بمحتوى التقرير بتنسيق مناسب للطباعة:

```javascript
function printReport() {
  if (!autoReportData) { toast('لا توجد بيانات', 'error'); return; }

  var content = document.getElementById('ar-results').innerHTML;
  var win = window.open('', '_blank');
  win.document.write('<!DOCTYPE html><html dir="rtl" lang="ar"><head>');
  win.document.write('<meta charset="utf-8">');
  win.document.write('<title>تقرير — مجلس عائلة العوامي</title>');
  win.document.write('<style>');
  win.document.write('body { font-family: Cairo, Tajawal, sans-serif; direction: rtl; padding: 20px; color: #333; }');
  win.document.write('h2, h3 { color: #1B3456; }');
  win.document.write('table { width: 100%; border-collapse: collapse; margin: 10px 0; }');
  win.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: right; font-size: 13px; }');
  win.document.write('th { background: #1B3456; color: #fff; }');
  win.document.write('.ar-actions, button, .ar-extra-items { display: none !important; }');
  win.document.write('.stat-card { display: inline-block; border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin: 4px; min-width: 120px; text-align: center; }');
  win.document.write('@media print { body { padding: 0; } }');
  win.document.write('</style></head><body>');
  win.document.write('<h2 style="text-align:center;margin-bottom:4px">مجلس عائلة العوامي</h2>');
  win.document.write('<p style="text-align:center;color:#666;font-size:13px">تاريخ الإصدار: ' + new Date().toLocaleDateString('ar-SA') + '</p>');
  win.document.write('<hr style="border-color:#c8a84b">');
  win.document.write(content);
  win.document.write('<hr style="border-color:#c8a84b;margin-top:30px">');
  win.document.write('<p style="text-align:center;font-size:11px;color:#999">صدر من: الموقع الرسمي لعائلة العوامي — alawami.site</p>');
  win.document.write('</body></html>');
  win.document.close();
  setTimeout(function() { win.print(); }, 500);
}
```

### 4.2 دالة exportReportPDF() — placeholder

في هذه المرحلة، تصدير PDF يكون عبر الطباعة (print → Save as PDF).
تصدير PDF حقيقي من السيرفر (TCPDF) → المرحلة 4.

```javascript
function exportReportPDF() {
  // مرحلياً: نستخدم الطباعة
  toast('استخدم الطباعة ثم "حفظ كـ PDF" — تصدير PDF مباشر قريباً', 'info');
  printReport();
}
```

-----

## المهمة 5: CSS التقارير التلقائية

### الملف: `admin/css/admin.css`

```css
/* ═══ Auto Reports ═══ */
.ar-type-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
.ar-type-card {
  padding: 16px 12px;
  background: var(--bg-card);
  border: 2px solid var(--border);
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}
.ar-type-card:hover {
  border-color: var(--green);
  transform: translateY(-2px);
}
.ar-type-card.active {
  border-color: var(--green);
  background: rgba(71,145,92,0.08);
}
.ar-type-card .ar-type-icon {
  font-size: 28px;
  margin-bottom: 6px;
}
.ar-type-card .ar-type-label {
  font-weight: 700;
  font-size: 14px;
  margin-bottom: 2px;
}
.ar-type-card .ar-type-desc {
  font-size: 11px;
  color: var(--text-muted);
}

.ar-filters {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: end;
  margin-bottom: 20px;
  padding: 16px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
}
.ar-filters label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
}
.ar-filters input, .ar-filters select {
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--bg);
  color: var(--text);
  font-size: 13px;
}

/* بطاقات إحصائية للتقرير */
.ar-stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
.ar-stat-card {
  padding: 16px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  text-align: center;
}
.ar-stat-value {
  font-size: 24px;
  font-weight: 900;
  margin-bottom: 4px;
}
.ar-stat-label {
  font-size: 12px;
  color: var(--text-muted);
}

/* جداول التقارير */
.ar-table {
  width: 100%;
  border-collapse: collapse;
  margin: 16px 0;
  font-size: 13px;
}
.ar-table th {
  background: var(--bg-card);
  padding: 10px 12px;
  text-align: right;
  font-weight: 700;
  border-bottom: 2px solid var(--border);
}
.ar-table td {
  padding: 8px 12px;
  border-bottom: 1px solid var(--border);
}
.ar-table tr:hover {
  background: rgba(71,145,92,0.03);
}

@media (max-width: 768px) {
  .ar-type-grid { grid-template-columns: repeat(2, 1fr); }
  .ar-stat-grid { grid-template-columns: repeat(2, 1fr); }
  .ar-filters { flex-direction: column; }
  .ar-table { display: block; overflow-x: auto; }
}
```

-----

## المهمة 6: الاختبار

### 6.1 اختبار API

```bash
# ملخص تنفيذي
curl -s "https://alawami.site/api/report-generator.php?type=summary" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq .data

# مالي شامل مع فلتر تاريخ
curl -s "https://alawami.site/api/report-generator.php?type=financial&date_from=2025-01-01&date_to=2025-12-31" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq .data.summary

# اشتراكات
curl -s "https://alawami.site/api/report-generator.php?type=subscriptions" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq .data.members

# لجان
curl -s "https://alawami.site/api/report-generator.php?type=committees" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq '.data.committees | length'

# فعاليات
curl -s "https://alawami.site/api/report-generator.php?type=events" \
  -H "X-CSRF-Token: ..." --cookie "..." | jq .data.status_stats
```

### 6.2 اختبار الواجهة

1. افتح صفحة التقارير → تبويب “تقارير تلقائية”
1. اختر “ملخص تنفيذي” → توليد → تحقق من ظهور الأرقام
1. اختر “مالي شامل” → حدد نطاق زمني → توليد → تحقق من الجداول
1. تحقق أن الأرقام **متطابقة مع صفحة الميزانية** (إيرادات − مصروفات = صافي)
1. تقرير الاشتراكات → تحقق أن قائمة المتخلفين صحيحة
1. تقرير اللجان → تحقق أن أعضاء كل لجنة صحيحين
1. اختبر الطباعة → تفتح نافذة بتنسيق مقبول
1. أضف بند يدوي في التقرير المالي → يظهر في القائمة ويُحذف بالضغط ✕
1. الوضع المظلم يعمل
1. الموبايل: البطاقات + الجداول responsive

### 6.3 حالات حدية

- لا توجد معاملات → الأرقام كلها 0 (لا خطأ)
- لا توجد فترات → تقرير الاشتراكات يعرض “لا توجد فترات”
- لا توجد لجان → تقرير اللجان يعرض “لا توجد لجان”
- فلتر تاريخ بدون نتائج → أرقام صفرية
- نوع تقرير غير صالح → رسالة خطأ 400
