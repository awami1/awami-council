# Tasks — تحسين صفحة إدارة الأعضاء

**alawami.site — لوحة التحكم**  
**مبني على PRD-members-ui-v1.0**  
**مارس 2026**

-----

## المرحلة أ: البنية التحتية — API الحذف والتعديل الجماعي (التبعية: لاشيء)

### Task 1: إضافة bulk_delete action في members.php

> **راجع** `.claude/rules/api-pattern.md` قبل البدء.

في ملف `api/members.php`، أضف handler جديد `handleBulkDelete()`:

- يُستدعى عبر: `DELETE /api/members.php?action=bulk_delete`
- يقرأ Body JSON: `{ "ids": ["uuid1", "uuid2", ...] }`
- يتحقق أن `ids` مصفوفة غير فارغة، الحد الأقصى 50 عنصر
- يتحقق من كل id باستخدام `parseId()` من `validation.php`
- ينفذ الحذف داخل transaction واحد: `$pdo->beginTransaction()` → حلقة DELETE → `$pdo->commit()`
- يسجل عملية واحدة في audit_log عبر `logAudit('حذف جماعي', 'عضو', '', '', ['count' => $count, 'names' => $names])`
- الاستجابة: `respond(200, ['deleted' => $count, 'failed' => $failed, 'errors' => $errors])`
- أضف الـ action في الـ `match` router بالأسفل: `$method === 'DELETE' && $action === 'bulk_delete' => handleBulkDelete()`

**اختبار:** إرسال طلب DELETE مع مصفوفة IDs فارغة → 422، مصفوفة من 51 عنصر → 422، مصفوفة صحيحة → 200 مع العدد الصحيح.

-----

### Task 2: إضافة bulk_status action في members.php

> **راجع** `.claude/rules/api-pattern.md` قبل البدء.

في ملف `api/members.php`، أضف handler جديد `handleBulkStatus()`:

- يُستدعى عبر: `PUT /api/members.php?action=bulk_status`
- يقرأ Body JSON: `{ "ids": ["uuid1", ...], "status": "منقطع" }`
- يتحقق أن `ids` مصفوفة غير فارغة، الحد الأقصى 50
- يتحقق أن `status` واحدة من: `['مشترك', 'منقطع', 'غير مشترك']` — إذا قيمة غير صالحة → 422
- ينفذ التعديل داخل transaction واحد: `UPDATE members SET status = :status WHERE id = :id`
- يسجل في audit_log: `logAudit('تعديل حالة جماعي', 'عضو', '', '', ['count' => $count, 'new_status' => $status])`
- الاستجابة: `respond(200, ['updated' => $count, 'failed' => $failed, 'errors' => $errors])`
- أضف الـ action في الـ `match` router: `$method === 'PUT' && $action === 'bulk_status' => handleBulkStatus()`

**اختبار:** إرسال حالة “معفي” → 422 (غير مسموحة)، إرسال حالة “مشترك” مع IDs صحيحة → 200.

-----

## المرحلة ب: شريط الإحصائيات (التبعية: لاشيء)

### Task 3: إضافة شريط إحصائيات فوق جدول الأعضاء

> **راجع** `.claude/rules/frontend.md` و `.claude/rules/admin.md` قبل البدء.

**HTML — في `admin/index.php`:**

داخل `<div id="page-members">` وفوق شريط الفلاتر الحالي، أضف:

```html
<div id="members-stats" class="stats-bar">
  <div class="stat-card stat-total" onclick="quickFilter('')">
    <div class="stat-number" id="stat-total">0</div>
    <div class="stat-label">الإجمالي</div>
  </div>
  <div class="stat-card stat-active" onclick="quickFilter('مشترك')">
    <div class="stat-number" id="stat-active">0</div>
    <div class="stat-label">مشترك</div>
  </div>
  <div class="stat-card stat-lapsed" onclick="quickFilter('منقطع')">
    <div class="stat-number" id="stat-lapsed">0</div>
    <div class="stat-label">منقطع</div>
  </div>
  <div class="stat-card stat-inactive" onclick="quickFilter('غير مشترك')">
    <div class="stat-number" id="stat-inactive">0</div>
    <div class="stat-label">غير مشترك</div>
  </div>
</div>
```

**CSS — في `admin/css/admin.css`:**

- `.stats-bar`: `display: flex; gap: 10px; margin-bottom: 16px; overflow-x: auto;`
- `.stat-card`: `flex: 1; min-width: 80px; background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius); padding: 12px; text-align: center; cursor: pointer; transition: all 0.2s;`
- `.stat-card:hover`: `border-color: var(--green); transform: translateY(-2px);`
- `.stat-number`: `font-size: 24px; font-weight: 900; color: var(--green-dark);`
- `.stat-active .stat-number`: `color: #166534;`
- `.stat-lapsed .stat-number`: `color: #854d0e;`
- `.stat-inactive .stat-number`: `color: #6b7280;`
- دعم dark mode عبر `[data-theme="dark"]` لجميع الألوان

**JS — في `admin/js/admin-app.js`:**

أضف دالة `updateMembersStats(filteredList)`:

- تحسب عدد كل حالة من القائمة المفلترة
- تحدّث الأرقام في `#stat-total`, `#stat-active`, `#stat-lapsed`, `#stat-inactive`
- تُستدعى من `renderMembers()` بعد تطبيق الفلاتر وقبل عرض الجدول

أضف دالة `quickFilter(status)`:

- تضبط قيمة `#m-flt-status` على الحالة المختارة
- تستدعي `renderMembers()`

**اختبار:** افتح صفحة الأعضاء → الأرقام تظهر صحيحة. غيّر الفلتر → الأرقام تتحدث. اضغط على بطاقة “منقطع” → الجدول يتفلتر.

-----

## المرحلة ج: العرض المضغوط + التبديل (التبعية: المرحلة ب)

### Task 4: إضافة زر toggle وعرض الجدول المضغوط

> **راجع** `.claude/rules/frontend.md` و `.claude/rules/admin.md` قبل البدء.

**HTML — في `admin/index.php`:**

فوق الجدول (بعد شريط الفلاتر)، أضف زر التبديل:

```html
<div class="view-toggle">
  <button id="view-cards" class="toggle-btn active" onclick="setMembersView('cards')">☰ بطاقات</button>
  <button id="view-table" class="toggle-btn" onclick="setMembersView('table')">⊞ جدول</button>
</div>
```

أضف حاوية الجدول المضغوط (بجانب الجدول الحالي `#members-tbody`):

```html
<div id="members-compact" style="display:none">
  <table class="compact-table">
    <thead>
      <tr>
        <th class="th-check"><input type="checkbox" id="select-all-members" onchange="toggleSelectAll(this)"></th>
        <th class="sortable" onclick="sortMembers('name')"># العضو <span class="sort-arrow"></span></th>
        <th>AWM-ID</th>
        <th>الجوال</th>
        <th>اللجان</th>
        <th class="sortable" onclick="sortMembers('status')">الحالة <span class="sort-arrow"></span></th>
        <th>الدفع</th>
        <th>الحساب</th>
      </tr>
    </thead>
    <tbody id="compact-tbody"></tbody>
  </table>
  <div id="compact-pagination"></div>
</div>
```

**CSS — في `admin/css/admin.css`:**

- `.view-toggle`: `display: flex; gap: 4px; margin-bottom: 12px;`
- `.toggle-btn`: `padding: 6px 14px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--bg); cursor: pointer; font-size: 13px;`
- `.toggle-btn.active`: `background: var(--green); color: white; border-color: var(--green);`
- `.compact-table`: `width: 100%; border-collapse: collapse; font-size: 13px;`
- `.compact-table th, .compact-table td`: `padding: 8px 10px; border-bottom: 1px solid var(--border); text-align: right;`
- `.compact-table tr:hover`: `background: var(--bg-hover);`
- `.compact-table tr.expanded`: `background: var(--bg-hover);`
- `.accordion-row`: `display: none; padding: 12px 20px; background: var(--bg); border-bottom: 2px solid var(--border);`
- `.accordion-row.open`: `display: table-row;`
- Responsive: `@media (max-width: 768px)` → hide `#members-compact`, show cards view

**JS — في `admin/js/admin-app.js`:**

أضف دالة `setMembersView(mode)`:

- تحفظ الاختيار في `localStorage.setItem('members-view-mode', mode)`
- تعرض/تخفي الحاوية المناسبة
- تحدّث أزرار الـ toggle (active class)
- تستدعي `renderMembers()`

عدّل دالة `renderMembers(page)` الموجودة:

- بعد تطبيق الفلاتر والترتيب، تتحقق من `localStorage.getItem('members-view-mode')`
- إذا `'table'` → تعرض البيانات في `#compact-tbody` بتنسيق صف أفقي واحد لكل عضو
- كل صف يحتوي: checkbox + # + avatar+اسم+عائلة + AWM-ID + جوال + عدد اللجان + badge حالة + badge دفع + badge حساب
- كل صف فيه `onclick="toggleAccordion(this, 'MEMBER_ID')"` (ما عدا الـ checkbox)
- إذا `'cards'` → تعرض بالطريقة الحالية (لا تعديل)

أضف دالة `toggleAccordion(row, memberId)`:

- تنشئ/تُظهر صف accordion تحت الصف المضغوط
- تعرض فيه: تاريخ الانضمام + الملاحظات + أسماء اللجان كاملة + أزرار (تعديل/حذف/تفعيل حساب)
- الضغط مرة ثانية يخفي الـ accordion

أضف auto-detection: عند تحميل الصفحة:

- إذا لم يكن هناك قيمة محفوظة في localStorage → تحقق من `window.innerWidth`
- إذا ≥ 768px → `'table'`، وإلا → `'cards'`

**اختبار:** الشاشة الكبيرة → الجدول المضغوط افتراضي. الجوال → البطاقات. التبديل يعمل. الضغط على صف يوسعه. الاختيار محفوظ بعد إعادة تحميل الصفحة.

-----

## المرحلة د: الترتيب + تحسين الفلاتر (التبعية: المرحلة ج)

### Task 5: إضافة ترتيب الأعمدة (sortable columns)

> **راجع** `.claude/rules/frontend.md` قبل البدء.

**JS — في `admin/js/admin-app.js`:**

أضف متغير عام:

```js
var _memberSort = { field: 'name', dir: 'asc' }; // الافتراضي
```

أضف دالة `sortMembers(field)`:

- إذا نفس الحقل: toggle الاتجاه (asc → desc → reset إلى name/asc)
- إذا حقل جديد: اضبط `dir = 'asc'`
- حدّث `_memberSort`
- استدعِ `renderMembers()`

عدّل `renderMembers()` — بعد الفلترة وقبل الـ pagination:

```js
list.sort((a, b) => {
  if (_memberSort.field === 'name') {
    return _memberSort.dir === 'asc' 
      ? a.name.localeCompare(b.name, 'ar') 
      : b.name.localeCompare(a.name, 'ar');
  }
  if (_memberSort.field === 'joinDate') {
    return _memberSort.dir === 'asc'
      ? (a.join_date || '').localeCompare(b.join_date || '')
      : (b.join_date || '').localeCompare(a.join_date || '');
  }
  if (_memberSort.field === 'status') {
    var order = {'مشترك': 0, 'منقطع': 1, 'غير مشترك': 2};
    var diff = (order[a.status] || 9) - (order[b.status] || 9);
    return _memberSort.dir === 'asc' ? diff : -diff;
  }
  return 0;
});
```

حدّث عناوين الأعمدة:

- أضف سهم `▲` أو `▼` بجانب العمود النشط
- أزِل السهم من الأعمدة الأخرى

**اختبار:** اضغط على “العضو” → ترتيب أبجدي تصاعدي. اضغط مرة ثانية → تنازلي. اضغط ثالثة → يعود للافتراضي. نفس السلوك للحالة.

-----

### Task 6: تحسين شريط الفلاتر (layout)

> **راجع** `.claude/rules/frontend.md` قبل البدء.

**CSS — في `admin/css/admin.css`:**

عدّل تنسيق شريط الفلاتر الموجود في `#page-members`:

على الشاشات الكبيرة (≥ 768px):

- `.members-filters`: `display: flex; gap: 10px; align-items: center; flex-wrap: wrap;`
- حقل البحث: `flex: 2; min-width: 200px;`
- كل dropdown فلتر: `flex: 1; min-width: 140px;`
- زر “ضبط”: `flex: 0 0 auto;`

على الجوال (< 768px):

- `.members-filters`: `display: flex; flex-direction: column; gap: 8px;`
- كل عنصر: `width: 100%;`

**HTML — في `admin/index.php`:**

- لُف عناصر الفلاتر الموجودة (البحث + حالة + حساب + ضبط) في `<div class="members-filters">`
- تأكد أن فلتر الحالة يعرض فقط: كل الحالات / مشترك / منقطع / غير مشترك (أزِل “معفي” و “نشط” و “غير نشط” إن وُجدت)

**اختبار:** على الكمبيوتر → الفلاتر في صف واحد أفقي. على الجوال → عمودية. فلتر الحالة يعرض 3 حالات فقط.

-----

## المرحلة هـ: أدوات التنظيف (التبعية: المرحلة أ + ج)

### Task 7: كشف التكرار التلقائي كفلتر

> **راجع** `.claude/rules/frontend.md` قبل البدء.

**HTML — في `admin/index.php`:**

أضف فلتر جديد في `.members-filters`:

```html
<button id="btn-duplicates" class="btn btn-outline" onclick="toggleDuplicatesFilter()">
  🔍 المكررين (<span id="dup-count">0</span>)
</button>
```

**JS — في `admin/js/admin-app.js`:**

أضف متغير عام:

```js
var _showDuplicates = false;
```

أضف دالة `findDuplicates(members)`:

- تأخذ مصفوفة الأعضاء
- تُنشئ map بالمفتاح: `(m.name + ' ' + (m.family || '')).trim().replace(/\s+/g, ' ')`
- ترجع فقط المجموعات اللي عددها ≥ 2
- ترجع: `{ groups: [[m1, m2], [m3, m4, m5], ...], totalDuplicates: N }`

أضف دالة `toggleDuplicatesFilter()`:

- تعكس `_showDuplicates`
- تحدّث مظهر الزر (active/inactive)
- تستدعي `renderMembers()`

عدّل `renderMembers()`:

- بعد الفلاتر الموجودة، إذا `_showDuplicates === true`:
  - استدعِ `findDuplicates(list)`
  - اعرض فقط الأعضاء الموجودين في مجموعات التكرار
  - في العرض المضغوط: لوّن كل مجموعة بلون خلفية مختلف (alternating) لتمييزها
  - في عرض البطاقات: أضف badge “🔁 مكرر (N نسخ)” على كل بطاقة
- حدّث عداد المكررين في `#dup-count` دائماً (حتى لو الفلتر غير مفعّل)

**اختبار:** إذا يوجد عضوين بنفس الاسم بالضبط → العداد يظهر العدد. الضغط على الزر → يعرض فقط المكررين مجمّعين. الضغط مرة ثانية → يعود للعرض العادي.

-----

### Task 8: الحذف الجماعي (bulk delete) — واجهة المستخدم

> **راجع** `.claude/rules/frontend.md` و `.claude/rules/admin.md` قبل البدء.

**HTML — في `admin/index.php`:**

أضف شريط الإجراءات العائم (فوق `</body>` مباشرة):

```html
<div id="bulk-action-bar" class="bulk-bar" style="display:none">
  <span id="bulk-count">تم تحديد 0 عضو</span>
  <button class="btn btn-danger" onclick="bulkDelete()">🗑️ حذف المحددين</button>
  <button class="btn btn-primary" onclick="openBulkStatusModal()">📝 تغيير الحالة</button>
  <button class="btn btn-outline" onclick="clearSelection()">✕ إلغاء</button>
</div>
```

**CSS — في `admin/css/admin.css`:**

- `.bulk-bar`: `position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: var(--green-dark); color: white; padding: 12px 20px; border-radius: var(--radius-lg); display: flex; align-items: center; gap: 12px; z-index: 1000; box-shadow: 0 4px 20px rgba(0,0,0,0.3);`
- `.bulk-bar .btn`: `font-size: 13px; padding: 6px 14px;`
- على الجوال: `flex-wrap: wrap; justify-content: center;`

**JS — في `admin/js/admin-app.js`:**

أضف متغير عام:

```js
var _selectedMembers = new Set();
```

أضف دالة `toggleMemberSelect(id, checkbox)`:

- إذا محدد → أضف للـ Set، وإلا → أزل
- حدّث `#bulk-count` والعرض/الإخفاء لـ `#bulk-action-bar`
- حدّث حالة “تحديد الكل” checkbox

أضف دالة `toggleSelectAll(masterCheckbox)`:

- إذا محدد → أضف كل أعضاء الصفحة الحالية
- إذا غير محدد → أزل كل أعضاء الصفحة الحالية
- حدّث العداد والشريط

أضف دالة `clearSelection()`:

- فرّغ الـ Set
- أخفِ الشريط
- ألغِ تحديد كل الـ checkboxes

أضف دالة `bulkDelete()`:

- إذا `_selectedMembers.size === 0` → return
- اعرض modal تأكيد:
  - “سيتم حذف X عضو نهائياً. هذا الإجراء لا يمكن التراجع عنه.”
  - قائمة بأسماء أول 10 أعضاء + “و N آخرين” إذا أكثر
  - زران: “تأكيد الحذف” (أحمر) + “إلغاء”
- عند التأكيد: أرسل `DELETE /api/members.php?action=bulk_delete` مع `{ ids: [..._selectedMembers] }`
- عند النجاح: `toast('تم حذف X عضو ✅')` → `clearSelection()` → أعد تحميل البيانات → `renderMembers()`

عدّل `renderMembers()`:

- أضف checkbox في بداية كل صف/بطاقة: `<input type="checkbox" onchange="toggleMemberSelect('${m.id}', this)" ${_selectedMembers.has(m.id) ? 'checked' : ''}>`
- الـ checkbox يحافظ على حالته عند التنقل بين الصفحات

**اختبار:** حدد 3 أعضاء → الشريط يظهر “تم تحديد 3 عضو”. اضغط “حذف المحددين” → modal تأكيد. أكّد → يُحذفون. “تحديد الكل” يعمل. “إلغاء التحديد” يمسح الكل.

-----

### Task 9: تعديل جماعي للحالة (bulk status) — واجهة المستخدم

> **راجع** `.claude/rules/frontend.md` و `.claude/rules/admin.md` قبل البدء.

**HTML — في `admin/index.php`:**

أضف modal تغيير الحالة الجماعي:

```html
<div id="modal-bulk-status" class="modal" style="display:none">
  <div class="modal-content" style="max-width:400px">
    <div class="modal-header">
      <h3>📝 تغيير حالة <span id="bulk-status-count">0</span> عضو</h3>
      <button onclick="closeModal('modal-bulk-status')">✕</button>
    </div>
    <div class="modal-body">
      <label>الحالة الجديدة:</label>
      <select id="bulk-new-status" class="form-control">
        <option value="مشترك">مشترك</option>
        <option value="منقطع">منقطع</option>
        <option value="غير مشترك">غير مشترك</option>
      </select>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" onclick="confirmBulkStatus()">تأكيد التغيير</button>
      <button class="btn btn-outline" onclick="closeModal('modal-bulk-status')">إلغاء</button>
    </div>
  </div>
</div>
```

**JS — في `admin/js/admin-app.js`:**

أضف دالة `openBulkStatusModal()`:

- إذا `_selectedMembers.size === 0` → return
- حدّث `#bulk-status-count`
- اعرض modal `modal-bulk-status`

أضف دالة `confirmBulkStatus()`:

- اقرأ الحالة من `#bulk-new-status`
- أرسل `PUT /api/members.php?action=bulk_status` مع `{ ids: [..._selectedMembers], status: newStatus }`
- عند النجاح: `toast('تم تحديث حالة X عضو ✅')` → `closeModal('modal-bulk-status')` → `clearSelection()` → أعد تحميل البيانات → `renderMembers()`

**اختبار:** حدد 5 أعضاء → اضغط “تغيير الحالة” → اختر “منقطع” → أكّد → الحالة تتغير لكل الخمسة. شريط الإحصائيات يتحدث.

-----

## المرحلة و: مراجعة واختبار (التبعية: كل ما سبق)

### Task 10: مراجعة شاملة واختبار

> **راجع** `.claude/rules/known-issues.md` و استخدم skill الـ `/code-review` لمراجعة الكود.

**قائمة الاختبارات:**

- شريط الإحصائيات يعرض أرقام صحيحة ويتحدث مع الفلاتر
- البطاقات قابلة للضغط في شريط الإحصائيات (تفلتر الجدول)
- التبديل بين عرض البطاقات والجدول المضغوط يعمل
- العرض الافتراضي مناسب لحجم الشاشة (بطاقات على الجوال، جدول على الكمبيوتر)
- الاختيار محفوظ في localStorage
- الضغط على صف → accordion يتوسع/يُطوى
- الترتيب (اسم/حالة) يعمل في كلا العرضين مع سهم واضح
- الفلاتر في صف أفقي على الكمبيوتر، عمودي على الجوال
- فلتر الحالة يعرض 3 حالات فقط (مشترك/منقطع/غير مشترك)
- كشف التكرار يعرض العدد الصحيح ويعمل كفلتر toggle
- المكررون يظهرون مجمّعين بألوان مميزة
- الحذف الجماعي: تحديد + تأكيد + حذف فعلي من قاعدة البيانات
- تعديل الحالة الجماعي: تحديد + اختيار حالة + تأكيد + تحديث فعلي
- شريط الإجراءات العائم يظهر/يختفي حسب التحديد
- “تحديد الكل” يحدد الصفحة الحالية فقط
- الـ audit_log يسجل الحذف والتعديل الجماعي
- dark mode يعمل على جميع العناصر الجديدة
- RTL صحيح على جميع العناصر
- الأداء مقبول مع 100+ عضو
- Responsive: الجوال (Safari iOS) + الكمبيوتر (Chrome)

-----

## ملاحظات التنفيذ

**الترتيب:** المراحل (أ → ب → ج → د → هـ) تعتمد على بعضها بالتسلسل. المرحلة و (المراجعة) تأتي بعد إكمال الجميع.

**Claude Code:** كل Task مصمم ليكون prompt مستقل قابل للتنفيذ في جلسة واحدة.

**الملفات المتأثرة:**

- `api/members.php` — Tasks 1, 2
- `admin/index.php` — Tasks 3, 4, 6, 8, 9
- `admin/js/admin-app.js` — Tasks 3, 4, 5, 7, 8, 9
- `admin/css/admin.css` — Tasks 3, 4, 6, 8

**Rules المطلوب مراجعتها:**

- `.claude/rules/api-pattern.md` — Tasks 1, 2
- `.claude/rules/frontend.md` — Tasks 3-9
- `.claude/rules/admin.md` — Tasks 3, 4, 8, 9
- `.claude/rules/known-issues.md` — Task 10

**Skills المستخدمة:**

- `/code-review` — Task 10

**الريبو:** `github.com/awami1/awami-council`
