# المرحلة 1: التنظيف والهيكلة — صفحة التقارير

# tasks.md for Claude Code

-----

## السياق

صفحة “التقارير الذكية” (`smart-reports`) تُعاد بناؤها بالكامل. هذه المرحلة الأولى تشمل:

1. حذف كود التصنيف التلقائي القديم
1. تصحيح النصوص (إزالة “ذكاء اصطناعي”)
1. إعادة هيكلة HTML الصفحة إلى 3 تبويبات فارغة

**لا تُضاف وظائف جديدة في هذه المرحلة** — فقط تنظيف وإعادة هيكلة.

معرّف الصفحة `smart-reports` يبقى كما هو (مستخدم في routing و CSS).

-----

## المهمة 1: تصحيح النصوص في admin-app.js

### الملف: `admin/js/admin-app.js`

### 1.1 تعديل عنوان الصفحة في `showPage()`

ابحث عن كائن `T` داخل دالة `showPage` (سطر واحد طويل فيه كل عناوين الصفحات).

```
// ابحث عن:
'smart-reports':'التقارير الذكية|تحليل مدعوم بالذكاء الاصطناعي'

// استبدل بـ:
'smart-reports':'التقارير|إنتاج وأرشفة التقارير المالية'
```

### 1.2 تعديل renderer في `showPage()`

ابحث عن كائن `renderers` داخل `showPage`. غيّر renderer الخاص بالتقارير:

```
// ابحث عن:
'smart-reports':function(){ srLoadSavedReports('active'); }

// استبدل بـ:
'smart-reports':function(){ renderReportsPage(); }
```

### 1.3 تعديل تعليق القسم

```
// ابحث عن:
// AI SMART REPORTS - تحليل ذكي بالذكاء الاصطناعي

// استبدل بـ:
// REPORTS - التقارير المالية والإدارية
```

-----

## المهمة 2: حذف الكود القديم من admin-app.js

### الملف: `admin/js/admin-app.js`

احذف **بالكامل** كل الدوال والمتغيرات التالية. انتبه — بعضها متتابع في الملف ضمن قسم واحد، فيمكن حذف القسم كاملاً.

### 2.1 المتغيرات العامة (أعلى قسم REPORTS):

```javascript
// احذف هذه المتغيرات:
var aiAnalysisData = null;
var srWorkbook = null;
var srRawData = null;
var srFileName = '';
var srSelectedRows = new Set();
```

### 2.2 الدوال المحذوفة بالكامل:

**محرك التصنيف:**

- `smartCategorize(description, type)` — keyword matching

**محرك التحليل:**

- `analyzeWithAI(rawData, descCol, amountCol, dateCol, typeCol)`

**عرض النتائج:**

- `displayAIResults()`
- `srRecalcStats()`
- `srRenderCategories(data)`
- `srRenderTransactionTable(transactions)`
- `generateAIInsights(data)`
- `srFilterByConfidence(level)`

**معالجة الملف:**

- `srProcessFile(file)`
- `srShowPreview(sheetIndex)`
- `srSelectSheet(idx)`
- `srAutoDetectColumns(cols)`
- `srCleanData(rawData, cols)`
- `srStartAnalysis()`

**أحداث الرفع:**

- `handleAIFileUpload(event)`
- `handleAIDragOver(e)`
- `handleAIDragLeave(e)`
- `handleAIDrop(e)`

**تحرير inline:**

- `srInlineEditType(td, idx)`
- `srInlineEditCategory(td, idx)`

**تحديد جماعي:**

- `srToggleRow(idx, checked)`
- `srToggleSelectAll(checked)`
- `srUpdateBulkToolbar()`
- `srApplyBulkEdit()`
- `srDeleteSelected()`

**مزامنة وتصدير:**

- `syncAIDataToDB()`
- `downloadAIReport()`
- `srPrintReport()`

**إعادة تعيين:**

- `resetAIAnalysis()`

**Stepper:**

- `srSetStep(step)`

**حفظ واسترجاع التقارير القديمة:**

- `srSaveReport()`
- `srLoadSavedReports(status, tabEl)`
- `srViewReport(id)`
- `srArchiveReport(id)`
- `srRestoreReport(id)`
- `srDeleteReport(id)`

### 2.3 أيضاً احذف كود drag & drop في `admin-import.js`

في ملف `admin/js/admin-import.js`، ابحث عن القسم:

```
// Drag & Drop Enhancement for AI Upload
```

واحذف الـ IIFE بالكامل (من `(function() {` إلى `})();`).

### 2.4 التحقق

بعد الحذف، نفّذ:

```bash
grep -rn "smartCategorize\|analyzeWithAI\|aiAnalysisData\|srWorkbook\|srRawData\|displayAIResults\|generateAIInsights\|srFilterByConfidence\|syncAIDataToDB\|downloadAIReport\|srPrintReport\|srStartAnalysis\|handleAIDragOver\|handleAIDrop\|srInlineEdit\|srToggleSelectAll\|srApplyBulkEdit\|srDeleteSelected\|srSetStep\|srSaveReport\|srViewReport\|srArchiveReport\|srRestoreReport\|srDeleteReport\|resetAIAnalysis\|srCleanData\|srAutoDetectColumns\|srShowPreview\|srSelectSheet\|srProcessFile\|handleAIFileUpload\|handleAIDragLeave" admin/
```

يجب أن يكون الناتج فارغاً (لا نتائج).

-----

## المهمة 3: إضافة الدالة الهيكلية الجديدة

### الملف: `admin/js/admin-app.js`

في مكان قسم REPORTS المحذوف، أضف الكود الهيكلي التالي (placeholder — سيُملأ في المراحل اللاحقة):

```javascript
// =====================================================
// REPORTS - التقارير المالية والإدارية
// =====================================================

var reportsActiveTab = 'auto-reports';

function renderReportsPage() {
  switchReportsTab(reportsActiveTab);
}

function switchReportsTab(tab) {
  reportsActiveTab = tab;

  // تحديث التبويبات
  document.querySelectorAll('.reports-tab-btn').forEach(function(btn) {
    btn.classList.toggle('active', btn.dataset.tab === tab);
  });
  document.querySelectorAll('.reports-tab-content').forEach(function(el) {
    el.style.display = el.id === 'rt-' + tab ? 'block' : 'none';
  });

  // تحميل محتوى التبويب
  if (tab === 'auto-reports') {
    renderAutoReportsTab();
  } else if (tab === 'import') {
    renderImportTab();
  } else if (tab === 'archive') {
    renderArchiveTab();
  }
}

// ---- تبويب التقارير التلقائية (placeholder) ----
function renderAutoReportsTab() {
  var container = document.getElementById('rt-auto-reports');
  if (!container) return;
  container.innerHTML =
    '<div class="empty-state">' +
    '<div class="empty-icon">📈</div>' +
    '<p>التقارير التلقائية — قريباً</p>' +
    '<p style="font-size:13px;color:var(--text-muted)">توليد تقارير مالية وإدارية من بيانات النظام</p>' +
    '</div>';
}

// ---- تبويب استيراد المعاملات (placeholder) ----
function renderImportTab() {
  var container = document.getElementById('rt-import');
  if (!container) return;
  container.innerHTML =
    '<div class="empty-state">' +
    '<div class="empty-icon">📥</div>' +
    '<p>استيراد معاملات من Excel — قريباً</p>' +
    '<p style="font-size:13px;color:var(--text-muted)">رفع ملفات Excel ومراجعتها وإدخالها في النظام</p>' +
    '</div>';
}

// ---- تبويب الأرشيف (placeholder) ----
function renderArchiveTab() {
  var container = document.getElementById('rt-archive');
  if (!container) return;
  container.innerHTML =
    '<div class="empty-state">' +
    '<div class="empty-icon">📁</div>' +
    '<p>أرشيف التقارير — قريباً</p>' +
    '<p style="font-size:13px;color:var(--text-muted)">حفظ وتصنيف الملفات والوثائق المالية</p>' +
    '</div>';
}
```

-----

## المهمة 4: إعادة بناء HTML الصفحة

### الملف: `admin/index.php`

### 4.1 تعديل عنصر الـ sidebar

ابحث عن عنصر القائمة الجانبية الخاص بالتقارير الذكية. سيحتوي على نص “التقارير الذكية” وربما emoji `🤖` أو مشابه، و `onclick="showPage('smart-reports', this)"`.

غيّر:

- النص من “التقارير الذكية” إلى “التقارير”
- الـ emoji (إن وُجد) إلى `📊`

### 4.2 إعادة بناء محتوى الصفحة

ابحث عن `<div id="page-smart-reports"` — هذا هو الـ div الذي يحتوي كل محتوى الصفحة.

**استبدل كل محتواه الداخلي** (ليس الـ div نفسه) بالكود التالي:

```html
<!-- ══════════ التقارير ══════════ -->
<div class="reports-header" style="margin-bottom:20px">
  <div class="reports-tabs" style="display:flex;gap:8px;flex-wrap:wrap">
    <button class="reports-tab-btn active" data-tab="auto-reports" onclick="switchReportsTab('auto-reports')">
      📈 تقارير تلقائية
    </button>
    <button class="reports-tab-btn" data-tab="import" onclick="switchReportsTab('import')">
      📥 استيراد معاملات
    </button>
    <button class="reports-tab-btn" data-tab="archive" onclick="switchReportsTab('archive')">
      📁 الأرشيف
    </button>
  </div>
</div>

<!-- تبويب: التقارير التلقائية -->
<div id="rt-auto-reports" class="reports-tab-content">
  <div class="empty-state">
    <div class="empty-icon">📈</div>
    <p>التقارير التلقائية — قريباً</p>
  </div>
</div>

<!-- تبويب: استيراد معاملات -->
<div id="rt-import" class="reports-tab-content" style="display:none">
  <div class="empty-state">
    <div class="empty-icon">📥</div>
    <p>استيراد معاملات من Excel — قريباً</p>
  </div>
</div>

<!-- تبويب: الأرشيف -->
<div id="rt-archive" class="reports-tab-content" style="display:none">
  <div class="empty-state">
    <div class="empty-icon">📁</div>
    <p>أرشيف التقارير — قريباً</p>
  </div>
</div>
```

### 4.3 حذف CSS القديم

ابحث في `admin/index.php` عن أي `<style>` blocks تحتوي على classes خاصة بالتقارير القديمة مثل:

- `.sr-step`, `.sr-step-line`
- `#upload-area`, `.drag-over`
- `.confidence-high`, `.confidence-medium`, `.confidence-low`
- `#ai-processing`, `#ai-results`
- `.sr-saved-item`, `.sr-saved-icon`
- `.sr-bulk-toolbar`
- `#sr-upload-card`, `#sr-preview-card`

احذف هذه الـ styles.

-----

## المهمة 5: إضافة CSS للتبويبات الجديدة

### الملف: `admin/css/admin.css`

أضف في نهاية الملف:

```css
/* ═══ Reports Tabs ═══ */
.reports-tab-btn {
  padding: 10px 20px;
  border: 2px solid var(--border);
  border-radius: 10px;
  background: var(--bg-card);
  color: var(--text);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.reports-tab-btn:hover {
  border-color: var(--green);
  color: var(--green);
}
.reports-tab-btn.active {
  background: var(--green);
  border-color: var(--green);
  color: #fff;
}

@media (max-width: 768px) {
  .reports-tab-btn {
    padding: 8px 14px;
    font-size: 13px;
    flex: 1;
    text-align: center;
  }
}
```

-----

## المهمة 6: تنظيف شامل — grep والتأكد

### 6.1 البحث عن بقايا “الذكاء الاصطناعي”

```bash
grep -rn "الذكاء الاصطناعي" admin/
grep -rn "ذكي" admin/
grep -rn "التقارير الذكية" admin/
grep -rn "AI SMART" admin/
grep -rn "قوة الذكاء" admin/
grep -rn "معاينة ذكية" admin/
grep -rn "التصنيف الذكي" admin/
```

أي نتيجة يجب تصحيحها:

- “الذكاء الاصطناعي” → “التحليل التلقائي” أو حذف
- “التقارير الذكية” → “التقارير”
- “ذكي/ذكية” في سياق التقارير → “تلقائي/تلقائية” أو حذف
- أي تعليق بالعربية فيه “AI” → صحّحه

**استثناء:** لا تعدّل أي ملف خارج مجلد `admin/`.

### 6.2 التحقق من عدم وجود أخطاء JS

افتح الصفحة في المتصفح (أو تحقق بـ syntax check):

```bash
# تحقق أن الملف لا يحتوي syntax errors
node -c admin/js/admin-app.js
```

### 6.3 التحقق النهائي

بعد كل التعديلات:

1. الصفحة تفتح بدون أخطاء في console
1. الـ sidebar يعرض “📊 التقارير” بدل “التقارير الذكية”
1. عنوان الـ topbar يعرض “التقارير” مع “إنتاج وأرشفة التقارير المالية”
1. الصفحة تعرض 3 تبويبات (تقارير تلقائية + استيراد معاملات + الأرشيف)
1. كل تبويب يعرض placeholder (“قريباً”)
1. التنقل بين التبويبات يعمل
1. الوضع المظلم يعمل (التبويبات تستخدم –bg-card و –border و –green)
1. لا أخطاء JavaScript في console
1. لا ذكر لـ “ذكاء اصطناعي” في أي مكان بالصفحة
