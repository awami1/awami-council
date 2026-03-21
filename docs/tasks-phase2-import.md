# المرحلة 2: استيراد معاملات من Excel

# tasks.md for Claude Code

-----

## السياق

هذه المرحلة تبني تبويب “استيراد معاملات” داخل صفحة التقارير (`smart-reports`).
**المتطلب المسبق:** تنفيذ المرحلة 1 (التنظيف والهيكلة — 3 تبويبات فارغة).

**السير المطلوب:** رفع Excel ← ربط الأعمدة ← مراجعة وتعديل يدوي ← إدخال في النظام + أرشفة تلقائية.

**APIs موجودة تُستخدم مباشرة:**

- `POST /api/transactions.php?bulk=1` — إدخال معاملات بالجملة (يكشف التكرار تلقائياً)
- `GET /api/committees.php` — قائمة اللجان (للـ dropdown)

**مكتبة موجودة:** SheetJS (XLSX 0.18.5) — محمّلة في `admin/index.php` من CDN.

**فئات الميزانية الموجودة في النظام:**
`رسوم الأعضاء`, `رحلة العمرة`, `غداء العيد`, `رحلة ترفيهية`, `مسابقة`, `مصاريف إدارية`, `استثمار`, `عقيقة جماعية`, `تبرعات`, `أخرى`

**حقول الـ bulk API المطلوبة لكل معاملة:**

```json
{
  "type": "إيراد",          // مطلوب: "إيراد" أو "مصروف"
  "amount": 500.00,         // مطلوب: رقم > 0
  "description": "...",     // مطلوب: نص
  "tx_date": "2025-01-15",  // مطلوب: YYYY-MM-DD
  "category": "اشتراكات",   // اختياري
  "committee_id": "c1"      // اختياري: UUID لجنة أو فارغ
}
```

-----

## المهمة 1: إنشاء جدول report_archive + API أساسي

### 1.1 ملف جديد: `api/report-archive.php`

أنشئ ملف API يتبع نفس نمط `api/reports.php` بالضبط (نفس البنية: require config, auth_guard, audit_helper, validation + ensureTable + handlers + router).

**الجدول:**

```sql
-- MySQL
CREATE TABLE IF NOT EXISTS `report_archive` (
    `id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(300) NOT NULL,
    `report_type` ENUM('مالي','إداري','محضر اجتماع','كشف حساب','أخرى') NOT NULL DEFAULT 'أخرى',
    `source` ENUM('manual','import','generated') NOT NULL DEFAULT 'manual',
    `description` TEXT,
    `file_url` VARCHAR(500) DEFAULT NULL,
    `file_type` VARCHAR(20) DEFAULT NULL,
    `report_date` DATE NOT NULL,
    `period_id` VARCHAR(36) DEFAULT NULL,
    `committee_id` VARCHAR(36) DEFAULT NULL,
    `import_stats` JSON DEFAULT NULL,
    `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
    `created_by` VARCHAR(36) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_archive_type` (`report_type`),
    INDEX `idx_archive_date` (`report_date`),
    INDEX `idx_archive_source` (`source`),
    INDEX `idx_archive_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SQLite
CREATE TABLE IF NOT EXISTS report_archive (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    report_type TEXT NOT NULL DEFAULT 'أخرى'
        CHECK(report_type IN ('مالي','إداري','محضر اجتماع','كشف حساب','أخرى')),
    source TEXT NOT NULL DEFAULT 'manual'
        CHECK(source IN ('manual','import','generated')),
    description TEXT,
    file_url VARCHAR(500) DEFAULT NULL,
    file_type VARCHAR(20) DEFAULT NULL,
    report_date DATE NOT NULL,
    period_id VARCHAR(36) DEFAULT NULL,
    committee_id VARCHAR(36) DEFAULT NULL,
    import_stats TEXT DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','archived')),
    created_by VARCHAR(36) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

**في هذه المرحلة يكفي handler واحد فقط:**

- `handlePost()` — لحفظ سجل أرشيف بعد الاستيراد

الـ handlers الباقية (GET, PUT, DELETE) تُضاف في مرحلة الأرشيف (المرحلة 5).

**حقول POST المقبولة:**

```json
{
  "title": "استيراد: كشف_حساب_2025.xlsx",
  "report_type": "كشف حساب",
  "source": "import",
  "description": "تم استيراد 42 معاملة من هذا الملف",
  "report_date": "2026-03-21",
  "import_stats": {
    "file_name": "كشف_حساب_2025.xlsx",
    "total_rows": 45,
    "imported": 42,
    "skipped": 3,
    "total_income": 12500,
    "total_expense": 8200
  }
}
```

### 1.2 إضافة API client

في `admin/js/admin-core.js` أو `public/js/api.js` (حيث كائنات API الأخرى)، أضف:

```javascript
var ReportArchiveAPI = {
  save: function(data) {
    return apiFetch('/api/report-archive.php', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }
};
```

-----

## المهمة 2: بناء واجهة الخطوة ① — رفع الملف

### الملف: `admin/js/admin-app.js`

استبدل دالة `renderImportTab()` (الـ placeholder من المرحلة 1) بالكود الفعلي.

### 2.1 المتغيرات

```javascript
// في أعلى قسم REPORTS
var importWorkbook = null;   // SheetJS workbook object
var importRawData = null;    // مصفوفة الصفوف بعد القراءة
var importFileName = '';     // اسم الملف
var importData = [];         // المعاملات بعد المراجعة (مصفوفة objects)
var importSelectedRows = new Set(); // الصفوف المحددة
var importStep = 1;          // الخطوة الحالية (1-4)
```

### 2.2 دالة renderImportTab()

تعرض الخطوة الحالية حسب `importStep`:

```javascript
function renderImportTab() {
  var container = document.getElementById('rt-import');
  if (!container) return;

  if (importStep === 1) renderImportStep1(container);
  else if (importStep === 2) renderImportStep2(container);
  else if (importStep === 3) renderImportStep3(container);
  else if (importStep === 4) renderImportStep4(container);
}
```

### 2.3 الخطوة 1: رفع الملف

`renderImportStep1(container)` تعرض:

**Stepper** (شريط الخطوات):

```
  ●──────○──────○──────○
  رفع     ربط    مراجعة   إدخال
```

أربع نقاط مع خطوط بينها. الخطوة الحالية ملونة بـ `var(--green)`.

**منطقة الرفع** (drag & drop):

- div بحدود متقطعة (dashed border)
- أيقونة 📊
- نص: “اسحب ملف Excel هنا أو انقر للاختيار”
- نص ثانوي: “يدعم: .xlsx, .xls, .csv (حد أقصى 10 ميجابايت)”
- input file مخفي (يُفعّل بالنقر على المنطقة)

**أحداث:**

- `click` على المنطقة → يفتح input file
- `dragover` / `dragleave` / `drop` → drag & drop
- عند اختيار ملف → `importProcessFile(file)`

**زر إعادة تعيين** (يظهر إذا كان فيه بيانات محمّلة سابقاً):
“🔄 بدء استيراد جديد” → يمسح كل المتغيرات ويعيد الخطوة لـ 1

### 2.4 دالة importProcessFile(file)

```javascript
function importProcessFile(file) {
  // التحقق من نوع الملف
  if (!file.name.match(/\.(xlsx|xls|csv)$/i)) {
    toast('نوع الملف غير مدعوم. يدعم: xlsx, xls, csv', 'error');
    return;
  }
  // التحقق من الحجم (10 MB)
  if (file.size > 10 * 1024 * 1024) {
    toast('حجم الملف يتجاوز 10 ميجابايت', 'error');
    return;
  }

  importFileName = file.name;

  var reader = new FileReader();
  reader.onload = function(e) {
    try {
      var data = new Uint8Array(e.target.result);
      importWorkbook = XLSX.read(data, { type: 'array', cellDates: true });
      // إذا فيه أكثر من sheet → يختار المستخدم
      // وإلا → يُقرأ أول sheet مباشرة
      importReadSheet(0);
    } catch (err) {
      toast('خطأ في قراءة الملف: ' + err.message, 'error');
    }
  };
  reader.readAsArrayBuffer(file);
}
```

### 2.5 دالة importReadSheet(sheetIndex)

```javascript
function importReadSheet(sheetIndex) {
  var sheetName = importWorkbook.SheetNames[sheetIndex];
  var sheet = importWorkbook.Sheets[sheetName];
  var json = XLSX.utils.sheet_to_json(sheet, { defval: '' });

  if (!json.length) {
    toast('الملف فارغ أو لا يحتوي بيانات', 'error');
    return;
  }

  importRawData = json;
  importStep = 2;
  renderImportTab();
}
```

-----

## المهمة 3: بناء واجهة الخطوة ② — ربط الأعمدة

### 3.1 دالة renderImportStep2(container)

تعرض:

**Stepper:** الخطوة 2 نشطة.

**معلومات الملف:**

- اسم الملف + عدد الصفوف
- إذا كان فيه أكثر من sheet → dropdown لاختيار الـ sheet

**ربط الأعمدة — 4 dropdowns:**

|الحقل                   |مطلوب|وصف                              |
|------------------------|-----|---------------------------------|
|عمود الوصف/البيان       |نعم  |نص المعاملة                      |
|عمود المبلغ             |نعم  |القيمة الرقمية                   |
|عمود التاريخ            |لا   |إذا فارغ → تاريخ اليوم           |
|عمود النوع (إيراد/مصروف)|لا   |إذا فارغ → يُحدد يدوياً في الخطوة 3|

كل dropdown يحتوي خيارات = أسماء أعمدة الملف + خيار “— لا يوجد —”.

**الكشف التلقائي:** عند عرض الخطوة، تُنفَّذ `importAutoDetectColumns()`:

- تبحث في أسماء الأعمدة عن كلمات مفتاحية:
  - وصف: `['الوصف', 'البيان', 'بيان', 'description', 'desc', 'وصف', 'تفاصيل', 'ملاحظة']`
  - مبلغ: `['المبلغ', 'القيمة', 'amount', 'value', 'مبلغ', 'قيمة', 'مدين', 'دائن']`
  - تاريخ: `['التاريخ', 'date', 'تاريخ']`
  - نوع: `['النوع', 'type', 'نوع']`
- تحدد الأعمدة مبدئياً في الـ dropdowns
- تعرض نص: “🔍 تم كشف X/4 أعمدة تلقائياً”

**معاينة** (أول 5 صفوف من البيانات) في جدول بسيط.

**أزرار:**

- “← رجوع” → `importStep = 1; renderImportTab()`
- “التالي: مراجعة المعاملات →” → `importPrepareReview()`

### 3.2 دالة importPrepareReview()

```javascript
function importPrepareReview() {
  var descCol = document.getElementById('import-col-desc').value;
  var amountCol = document.getElementById('import-col-amount').value;
  var dateCol = document.getElementById('import-col-date').value;
  var typeCol = document.getElementById('import-col-type').value;

  if (!descCol || !amountCol) {
    toast('يرجى تحديد عمود الوصف والمبلغ', 'error');
    return;
  }

  // تحويل البيانات الخام إلى مصفوفة معاملات
  importData = [];
  importSelectedRows = new Set();

  importRawData.forEach(function(row, i) {
    var desc = String(row[descCol] || '').trim();
    var amountStr = String(row[amountCol] || '0');
    var amount = parseFloat(amountStr.replace(/[^\d.\-]/g, '')) || 0;
    var dateStr = dateCol ? String(row[dateCol] || '') : '';
    var typeStr = typeCol ? String(row[typeCol] || '') : '';

    // تنظيف التاريخ
    var date = '';
    if (dateStr) {
      // إذا كان كائن Date (من SheetJS cellDates)
      if (dateStr instanceof Date || !isNaN(Date.parse(dateStr))) {
        var d = new Date(dateStr);
        date = d.toISOString().split('T')[0];
      } else if (dateStr.match(/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/)) {
        var parts = dateStr.split(/[\/\-]/);
        if (parts[2] && parts[2].length === 4) {
          date = parts[2] + '-' + parts[1].padStart(2, '0') + '-' + parts[0].padStart(2, '0');
        }
      } else {
        date = dateStr;
      }
    }
    if (!date) date = new Date().toISOString().split('T')[0];

    // تحديد النوع مبدئياً
    var type = '';
    if (typeStr) {
      var lt = typeStr.toLowerCase();
      if (lt.includes('إيراد') || lt.includes('دخل') || lt.includes('income') || lt.includes('دائن') || lt.includes('credit')) {
        type = 'إيراد';
      } else if (lt.includes('مصروف') || lt.includes('expense') || lt.includes('مدين') || lt.includes('debit')) {
        type = 'مصروف';
      }
    }
    // إذا ما تحدد النوع من العمود، نحاول من الوصف
    if (!type) {
      var ld = desc.toLowerCase();
      if (ld.includes('اشتراك') || ld.includes('تبرع') || ld.includes('إيراد') || ld.includes('دخل')) {
        type = 'إيراد';
      }
      // ما نحدد مصروف تلقائياً — نخلي المستخدم يختار
    }

    importData.push({
      index: i,
      description: desc,
      amount: Math.abs(amount),
      date: date,
      type: type,       // قد يكون فارغاً
      category: '',     // يُحدد يدوياً
      committee_id: '', // يُحدد يدوياً
      valid: desc.length > 0 && amount !== 0
    });

    // الصفوف الصالحة تُحدد تلقائياً
    if (desc.length > 0 && amount !== 0) {
      importSelectedRows.add(i);
    }
  });

  importStep = 3;
  renderImportTab();
}
```

-----

## المهمة 4: بناء واجهة الخطوة ③ — المراجعة والتعديل اليدوي

هذه أهم خطوة وأكبرها. الجدول يعرض كل صف مع حقول قابلة للتعديل.

### 4.1 دالة renderImportStep3(container)

**Stepper:** الخطوة 3 نشطة.

**شريط الإجراءات الجماعية:**

```html
<div class="import-bulk-bar">
  <label><input type="checkbox" id="import-select-all" onchange="importToggleAll(this.checked)"> تحديد الكل</label>
  <span id="import-selected-count">0 محدد</span>
  
  <!-- تظهر فقط عند وجود صفوف محددة -->
  <select id="import-bulk-type"><option value="">النوع</option><option value="إيراد">إيراد</option><option value="مصروف">مصروف</option></select>
  <select id="import-bulk-category"><!-- يُملأ برمجياً --></select>
  <select id="import-bulk-committee"><!-- يُملأ من API اللجان --></select>
  <button onclick="importApplyBulk()">✓ تطبيق على المحدد</button>
  <button onclick="importDeleteSelected()" style="color:var(--danger)">🗑️ حذف المحدد</button>
</div>
```

**الجدول:**

```html
<table class="import-review-table">
  <thead>
    <tr>
      <th>✓</th>
      <th>التاريخ</th>
      <th>الوصف</th>
      <th>المبلغ</th>
      <th>النوع</th>
      <th>الفئة</th>
      <th>اللجنة</th>
    </tr>
  </thead>
  <tbody id="import-review-tbody">
    <!-- يُولَّد برمجياً -->
  </tbody>
</table>
```

**لكل صف في الجدول:**

```html
<tr data-idx="0" class="selected">
  <td><input type="checkbox" class="import-row-check" data-idx="0" checked onchange="importToggleRow(0, this.checked)"></td>
  <td><input type="date" value="2025-01-05" onchange="importData[0].date=this.value" class="import-edit-input"></td>
  <td><input type="text" value="اشتراك أحمد" onchange="importData[0].description=this.value.trim()" class="import-edit-input import-edit-desc"></td>
  <td><input type="number" value="200" min="0" step="0.01" onchange="importData[0].amount=parseFloat(this.value)||0; importUpdateSummary()" class="import-edit-input import-edit-amount"></td>
  <td>
    <select onchange="importData[0].type=this.value; importUpdateSummary()" class="import-edit-select">
      <option value="">— اختر —</option>
      <option value="إيراد">إيراد</option>
      <option value="مصروف">مصروف</option>
    </select>
  </td>
  <td>
    <select onchange="importData[0].category=this.value" class="import-edit-select">
      <option value="">— بدون —</option>
      <option value="رسوم الأعضاء">رسوم الأعضاء</option>
      <!-- ... باقي الفئات ... -->
    </select>
  </td>
  <td>
    <select onchange="importData[0].committee_id=this.value" class="import-edit-select">
      <option value="">عام</option>
      <!-- يُملأ من State.getCommittees() -->
    </select>
  </td>
</tr>
```

**الصفوف غير الصالحة** (وصف فارغ أو مبلغ = 0): تظهر بخلفية رمادية وcheckbox غير محدد.

**dropdown الفئة يحتوي:**
`['', 'رسوم الأعضاء', 'رحلة العمرة', 'غداء العيد', 'رحلة ترفيهية', 'مسابقة', 'مصاريف إدارية', 'استثمار', 'عقيقة جماعية', 'تبرعات', 'اشتراكات', 'ضيافة', 'نقل', 'صيانة', 'تعليم', 'رحلات', 'مناسبات', 'أخرى']`

**dropdown اللجنة يُملأ من:**

```javascript
var committees = State.getCommittees();
// يُولَّد: <option value="">عام</option> + option لكل لجنة
```

**ملخص سفلي (يتحدث تلقائياً):**

```html
<div class="import-summary-bar">
  <span>محدد: <strong id="import-summary-count">42</strong> من <strong>45</strong></span>
  <span>إيرادات: <strong id="import-summary-income" style="color:var(--green)">12,500</strong> ريال</span>
  <span>مصروفات: <strong id="import-summary-expense" style="color:var(--danger)">8,200</strong> ريال</span>
  <span>صافي: <strong id="import-summary-net">+4,300</strong> ريال</span>
</div>
```

**أزرار:**

- “← رجوع لربط الأعمدة” → `importStep = 2; renderImportTab()`
- “إدخال في النظام →” → `importValidateAndConfirm()`

### 4.2 الدوال المساعدة

```javascript
function importToggleRow(idx, checked) { ... }     // إضافة/إزالة من importSelectedRows
function importToggleAll(checked) { ... }           // تحديد/إلغاء الكل
function importApplyBulk() { ... }                  // تطبيق النوع/الفئة/اللجنة على المحدد
function importDeleteSelected() { ... }             // حذف الصفوف المحددة من importData
function importUpdateSummary() { ... }              // إعادة حساب الملخص السفلي

function importValidateAndConfirm() {
  // التحقق من أن كل صف محدد عنده نوع (إيراد/مصروف)
  var missing = [];
  importSelectedRows.forEach(function(idx) {
    var row = importData[idx];
    if (!row.type) missing.push(idx + 1);
  });

  if (missing.length > 0) {
    toast('الصفوف التالية تحتاج تحديد النوع (إيراد/مصروف): ' + missing.slice(0, 5).join(', ') + (missing.length > 5 ? '...' : ''), 'error');
    return;
  }

  if (importSelectedRows.size === 0) {
    toast('لم يتم تحديد أي معاملة', 'error');
    return;
  }

  importStep = 4;
  renderImportTab();
}
```

-----

## المهمة 5: بناء واجهة الخطوة ④ — التأكيد والإدخال

### 5.1 دالة renderImportStep4(container)

**Stepper:** الخطوة 4 نشطة.

**ملخص التأكيد:**

```
┌─────────────────────────────────────┐
│  ⚠️  تأكيد الإدخال                  │
│                                       │
│  سيتم إدخال 42 معاملة في النظام:    │
│  • إيرادات: 25 معاملة (12,500 ريال)  │
│  • مصروفات: 17 معاملة (8,200 ريال)   │
│  • الملف: كشف_حساب_2025.xlsx         │
│                                       │
│  [← رجوع للمراجعة]  [✅ إدخال في النظام] │
└─────────────────────────────────────┘
```

يحسب: عدد الإيرادات، عدد المصروفات، إجمالي كل نوع.

### 5.2 دالة importExecute()

عند الضغط على “إدخال في النظام”:

```javascript
function importExecute() {
  var btn = document.getElementById('import-execute-btn');
  btn.disabled = true;
  btn.textContent = 'جاري الإدخال...';

  // بناء payload للـ bulk API
  var transactions = [];
  importSelectedRows.forEach(function(idx) {
    var row = importData[idx];
    transactions.push({
      type: row.type,            // "إيراد" أو "مصروف"
      amount: row.amount,
      description: row.description,
      tx_date: row.date,
      category: row.category || 'أخرى',
      committee_id: row.committee_id || ''
    });
  });

  // الخطوة 1: إدخال المعاملات
  apiFetch('/api/transactions.php?bulk=1', {
    method: 'POST',
    body: JSON.stringify({ transactions: transactions })
  }).then(function(res) {
    // الخطوة 2: حفظ في الأرشيف
    var incomeTotal = 0, expenseTotal = 0;
    transactions.forEach(function(tx) {
      if (tx.type === 'إيراد') incomeTotal += tx.amount;
      else expenseTotal += tx.amount;
    });

    return ReportArchiveAPI.save({
      title: 'استيراد: ' + importFileName,
      report_type: 'كشف حساب',
      source: 'import',
      description: 'تم استيراد ' + res.inserted + ' معاملة من هذا الملف' +
        (res.duplicates_skipped > 0 ? ' (تم تجاهل ' + res.duplicates_skipped + ' مكررة)' : ''),
      report_date: new Date().toISOString().split('T')[0],
      import_stats: {
        file_name: importFileName,
        total_rows: importData.length,
        imported: res.inserted,
        skipped: res.duplicates_skipped || 0,
        total_income: incomeTotal,
        total_expense: expenseTotal
      }
    }).then(function() { return res; });
  }).then(function(res) {
    // الخطوة 3: عرض النتيجة
    importShowResult(res);
    // تحديث بيانات الميزانية في State
    if (res.data && res.data.length) {
      res.data.forEach(function(row) { State.getBudget().push(row); });
    }
  }).catch(function(err) {
    toast('خطأ في الإدخال: ' + err.message, 'error');
    btn.disabled = false;
    btn.textContent = '✅ إدخال في النظام';
  });
}
```

### 5.3 دالة importShowResult(res)

تستبدل محتوى الخطوة 4 بنتيجة العملية:

```
┌─────────────────────────────────────┐
│  ✅ تم بنجاح!                        │
│                                       │
│  • تم إدخال: 42 معاملة               │
│  • تكرارات تم تجاهلها: 3             │
│  • الملف محفوظ في الأرشيف            │
│                                       │
│  [📊 عرض الميزانية]  [📥 استيراد آخر] │
└─────────────────────────────────────┘
```

**“عرض الميزانية”:** `showPage('budget')` — يأخذ المستخدم لصفحة الميزانية ليشوف المعاملات.

**“استيراد آخر”:** يمسح كل المتغيرات ويعيد الخطوة لـ 1:

```javascript
function importReset() {
  importWorkbook = null;
  importRawData = null;
  importFileName = '';
  importData = [];
  importSelectedRows = new Set();
  importStep = 1;
  renderImportTab();
}
```

-----

## المهمة 6: إضافة CSS

### الملف: `admin/css/admin.css`

```css
/* ═══ Import Stepper ═══ */
.import-stepper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  margin-bottom: 24px;
  padding: 16px 0;
}
.import-stepper-dot {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--border);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  transition: all 0.3s;
}
.import-stepper-dot.active {
  background: var(--green);
  color: #fff;
}
.import-stepper-dot.done {
  background: var(--green);
  color: #fff;
  opacity: 0.6;
}
.import-stepper-line {
  width: 60px;
  height: 3px;
  background: var(--border);
  transition: background 0.3s;
}
.import-stepper-line.done {
  background: var(--green);
}

/* ═══ Import Upload Area ═══ */
.import-upload-area {
  border: 2px dashed var(--border);
  border-radius: 16px;
  padding: 48px 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  background: var(--bg-card);
}
.import-upload-area:hover,
.import-upload-area.drag-over {
  border-color: var(--green);
  background: var(--green-light, rgba(71,145,92,0.05));
}

/* ═══ Import Review Table ═══ */
.import-review-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.import-review-table th {
  background: var(--bg-card);
  padding: 10px 8px;
  text-align: right;
  font-weight: 700;
  border-bottom: 2px solid var(--border);
  white-space: nowrap;
}
.import-review-table td {
  padding: 6px 4px;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.import-review-table tr.selected {
  background: rgba(71,145,92,0.05);
}
.import-review-table tr.invalid {
  background: rgba(150,150,150,0.08);
  opacity: 0.6;
}

.import-edit-input {
  width: 100%;
  padding: 5px 8px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--bg);
  color: var(--text);
  font-size: 12px;
}
.import-edit-desc { min-width: 150px; }
.import-edit-amount { width: 90px; text-align: left; direction: ltr; }

.import-edit-select {
  padding: 5px 6px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--bg);
  color: var(--text);
  font-size: 12px;
  min-width: 80px;
}

/* ═══ Import Bulk Bar ═══ */
.import-bulk-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  padding: 12px 16px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 10px;
  margin-bottom: 12px;
  font-size: 13px;
}

/* ═══ Import Summary Bar ═══ */
.import-summary-bar {
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
  padding: 12px 16px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 10px;
  margin-top: 12px;
  font-size: 13px;
  position: sticky;
  bottom: 0;
  z-index: 5;
}

/* ═══ Import Confirm Card ═══ */
.import-confirm-card {
  max-width: 500px;
  margin: 40px auto;
  padding: 32px;
  background: var(--bg-card);
  border: 2px solid var(--border);
  border-radius: 16px;
  text-align: center;
}

/* ═══ Import Result Card ═══ */
.import-result-card {
  max-width: 500px;
  margin: 40px auto;
  padding: 32px;
  background: var(--bg-card);
  border: 2px solid var(--green);
  border-radius: 16px;
  text-align: center;
}

/* ═══ Responsive ═══ */
@media (max-width: 768px) {
  .import-review-table {
    display: block;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .import-bulk-bar {
    font-size: 12px;
    gap: 6px;
  }
  .import-summary-bar {
    gap: 10px;
    font-size: 12px;
  }
  .import-stepper-line { width: 30px; }
}
```

-----

## المهمة 7: الاختبار

### 7.1 اختبار يدوي

1. **افتح صفحة التقارير** → انقر تبويب “استيراد معاملات”
1. **الخطوة 1:** اسحب ملف Excel (أو انقر وارفع) → يجب أن ينتقل للخطوة 2
1. **الخطوة 2:** تحقق أن الأعمدة اتكشفت تلقائياً + المعاينة تعرض بيانات صحيحة → انقر “التالي”
1. **الخطوة 3:** تحقق أن:
- كل صف يظهر بحقول قابلة للتعديل
- الصفوف الفارغة/صفرية غير محددة
- تعديل النوع/الفئة/اللجنة يعمل
- التعديل الجماعي يعمل (حدد عدة صفوف → اختر نوع → تطبيق)
- الملخص السفلي يتحدث مع كل تعديل
- لا يسمح بالمتابعة إذا فيه صفوف بدون نوع
1. **الخطوة 4:** تحقق أن:
- ملخص التأكيد صحيح (عدد + مبالغ)
- الضغط على “إدخال” يرسل للـ API ويعرض النتيجة
- المعاملات تظهر في صفحة الميزانية
- سجل أرشيف يُنشأ تلقائياً (تحقق من `GET /api/report-archive.php`)
1. **“استيراد آخر”:** يعود للخطوة 1 نظيف
1. **الوضع المظلم:** كل العناصر تظهر بشكل صحيح
1. **الموبايل:** الجدول scrollable أفقياً + الأزرار تتكدس بشكل مقبول

### 7.2 حالات حدية

- ملف فارغ → رسالة خطأ
- ملف كبير (> 10 MB) → رسالة خطأ
- ملف غير مدعوم (.pdf) → رسالة خطأ
- ملف بأكثر من sheet → يعرض اختيار
- كل الصفوف بدون نوع → لا يسمح بالمتابعة
- إدخال معاملات مكررة → الـ API يتجاهلها ويعرض العدد
- لا صفوف محددة → رسالة “لم يتم تحديد أي معاملة”

### 7.3 التحقق البرمجي

```bash
# تحقق من syntax الملفات
node -c admin/js/admin-app.js

# تحقق أن الـ API الجديد يعمل
curl -s -X POST "https://alawami.site/api/report-archive.php" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: ..." \
  --cookie "..." \
  -d '{"title":"test","report_type":"أخرى","source":"manual","report_date":"2026-03-21"}' | jq .

# تحقق أن جدول report_archive أُنشئ
# (سيُنشأ تلقائياً عند أول طلب لـ report-archive.php)
```
