# tasks.md — خطة تنفيذ نظام المناصب

## المرجع: PRD-positions-v1.1.md

## القاعدة الذهبية

> **لا تلمس جدول `members` أبداً.** لا تُنشئ أعضاء جدد. لا تعدّل أعضاء موجودين. لا تربط أعضاء بمناصب في الـ Seed — الربط يتم يدوياً من المدير عبر لوحة التحكم. جدول `position_members` يبقى فارغاً بعد التهيئة الأولية.

-----

## المرحلة 1: قاعدة البيانات

### مهمة 1.1 — إنشاء الجداول

اقرأ `api/config.php` لفهم طريقة الاتصال (`getPDO()`). ثم أنشئ الجداول الثلاثة.

**ملف:** أضف `ensureTable()` داخل `api/positions.php` (يُنشأ في المرحلة 2) — أو نفّذ كـ migration منفصل إذا كان المشروع يتبع هذا النمط.

```sql
-- 1) positions
CREATE TABLE IF NOT EXISTS positions (
  id VARCHAR(36) NOT NULL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  icon VARCHAR(10) NOT NULL DEFAULT '📌',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_core TINYINT(1) NOT NULL DEFAULT 0,
  committee_id VARCHAR(36) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) position_members
CREATE TABLE IF NOT EXISTS position_members (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position_id VARCHAR(36) NOT NULL,
  member_id VARCHAR(36) NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pos_member (position_id, member_id),
  FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) position_tasks
CREATE TABLE IF NOT EXISTS position_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position_id VARCHAR(36) NOT NULL,
  task_text VARCHAR(500) NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**تحقق:** اتصل بقاعدة البيانات وتأكد أن الجداول الثلاثة موجودة وفارغة.

-----

### مهمة 1.2 — إدراج البيانات الأولية (Seed)

> ⚠️ **تنبيه حرج:** لا تُنشئ أي سجل في جدول `members`. لا تربط أي عضو بأي منصب. جدول `position_members` يبقى **فارغاً**. المدير سيربط الأعضاء يدوياً من لوحة التحكم لاحقاً.

**أدرج المناصب الستة فقط** (الكل `is_core = 1`):

|sort_order|title            |icon|committee_id                                                            |
|----------|-----------------|----|------------------------------------------------------------------------|
|1         |الرئيس           |👑   |NULL                                                                    |
|2         |نائب الرئيس      |🤝   |NULL                                                                    |
|3         |أمين الصندوق     |💰   |ابحث في `committees` عن اللجنة المالية — إن وُجدت ضع الـ id، وإلا NULL   |
|4         |المنسق العام     |📋   |NULL                                                                    |
|5         |أمين السر        |📝   |NULL                                                                    |
|6         |اللجنة الاستشارية|🛡️   |ابحث في `committees` عن اللجنة الاستشارية — إن وُجدت ضع الـ id، وإلا NULL|

**ثم أدرج مهام كل منصب في `position_tasks`:**

**الرئيس:**

1. الإشراف العام على أعمال المجلس
1. إدارة الاجتماعات وتمثيل المجلس
1. تمثيل المجلس أمام الجهات الرسمية
1. اتخاذ القرارات النهائية بالتشاور مع الأعضاء

**نائب الرئيس:**

1. مساعدة الرئيس في مهامه
1. إدارة المجلس في غياب الرئيس
1. متابعة تنفيذ القرارات
1. التنسيق بين اللجان المختلفة

**أمين الصندوق:**

1. إدارة الحسابات المالية للمجلس
1. تحصيل الاشتراكات ومتابعة المتأخرين
1. إعداد التقارير المالية الدورية
1. صرف المبالغ المعتمدة حسب الميزانية

**المنسق العام:**

1. تنسيق الأنشطة والفعاليات
1. التواصل مع الأعضاء وإبلاغهم بالمستجدات
1. متابعة تنفيذ خطط العمل
1. إعداد جدول أعمال الاجتماعات

**أمين السر:**

1. تدوين محاضر الاجتماعات
1. حفظ الوثائق والمستندات الرسمية
1. إرسال الدعوات والإشعارات
1. توثيق القرارات والمتابعة

**اللجنة الاستشارية:**

1. تقديم المشورة والتوجيه لأعضاء المجلس
1. المشاركة في اتخاذ القرارات المصيرية
1. حل النزاعات والخلافات بين الأعضاء
1. مراجعة أداء المجلس وتقديم التوصيات

**تحقق:**

- `SELECT COUNT(*) FROM positions` → 6
- `SELECT COUNT(*) FROM position_tasks` → 24
- `SELECT COUNT(*) FROM position_members` → **0** (فارغ — هذا صحيح)

-----

## المرحلة 2: الـ API

> **اقرأ أولاً:** `.claude/skills/new-api-endpoint` — واتبع التعليمات.
> **المرجع:** اقرأ `api/members.php` أو `api/events.php` كنموذج وقلّد نفس الهيكل بالضبط.

### مهمة 2.1 — إنشاء `api/positions.php`

**الملف:** `/api/positions.php`

**الهيكل المطلوب** (نفس نمط بقية الـ APIs):

```
declare(strict_types=1)
require config.php
require auth_guard.php (للعمليات الكتابية فقط)
require validation.php

ensureTable()         → إنشاء الجداول إذا لم تكن موجودة
toShape($row)         → تطبيع صف قاعدة البيانات
validatePayload()     → تحقق من المدخلات

handleGetAll()        → GET بدون id (مع أعضاء ومهام nested)
handleGetOne($id)     → GET مع id
handlePost()          → POST إنشاء منصب
handlePut($id)        → PUT تعديل منصب
handleDelete($id)     → DELETE حذف (يفشل إذا is_core = 1)
handleUpdateMembers($id)  → POST ?action=members
handleUpdateTasks($id)    → POST ?action=tasks

match(true) { ... }   → توجيه الطلب حسب HTTP method + action
try/catch PDOException
```

**قواعد مهمة:**

1. **GET لا يحتاج auth** — بيانات عامة (مثل `family-tree.php`).
1. **POST/PUT/DELETE تحتاج** `requireAuth()` + `verifyCsrf()`.
1. **GET الكل** يرجع المناصب مع `members` و `tasks` كـ nested arrays — استخدم JOIN أو queries منفصلة.
1. **شكل الاستجابة:** `{ "data": [...], "total": N }` — نفس نمط بقية الـ APIs.
1. **action=members:**
- اقرأ `member_ids` من الـ body (مصفوفة UUIDs).
- تحقق أن كل UUID موجود في `members` — إذا أي واحد غير موجود، ارفض الطلب كاملاً (400).
- احذف كل `position_members` لهذا المنصب.
- أدرج القائمة الجديدة مع `sort_order` = index + 1.
1. **action=tasks:**
- اقرأ `tasks` من الـ body (مصفوفة strings).
- احذف كل `position_tasks` لهذا المنصب.
- أدرج القائمة الجديدة مع `sort_order` = index + 1.
1. **DELETE:** تحقق من `is_core` — إذا كان 1 ارجع 403 مع `{ "error": "لا يمكن حذف منصب أساسي" }`.
1. **logAudit()** على كل POST/PUT/DELETE.
1. **CORS headers** مثل بقية الـ APIs.

### مهمة 2.2 — اختبار الـ API

اختبر كل عملية وتأكد:

```
✅ GET /api/positions.php → يرجع 6 مناصب مع مهام (بدون أعضاء مبدئياً)
✅ GET /api/positions.php?id=xxx → يرجع منصب واحد
✅ POST /api/positions.php → ينشئ منصب جديد (بعد auth)
✅ PUT /api/positions.php?id=xxx → يعدّل المنصب
✅ DELETE منصب غير أساسي → ينجح
✅ DELETE منصب أساسي → يفشل مع 403
✅ POST action=members مع member_id غير موجود في members → يفشل مع 400
✅ POST action=members مع IDs صحيحة موجودة في members → ينجح والترتيب صحيح
✅ POST action=tasks → ينجح والمهام تتحدث
✅ GET بعد كل تعديل → البيانات محدّثة
```

-----

## المرحلة 3: لوحة التحكم

> **اقرأ أولاً:** `admin/index.html` بالكامل — ركّز على كيف يُبنى قسم اللجان (committees). قلّد نفس الأنماط: DOM structure, event handling, modal pattern, fetch calls.

### مهمة 3.1 — قراءة الكود الحالي

قبل أي تعديل:

1. اقرأ `admin/index.html` وحدد قسم “مناصب المجلس” الحالي.
1. اقرأ قسم “اللجان” كمرجع للأنماط.
1. لاحظ: كيف تُفتح الـ modals، كيف تُستدعى الـ APIs، كيف يُعاد تحميل البيانات بعد التعديل.
1. لا تعدّل أي قسم آخر.

### مهمة 3.2 — استبدال القسم الحالي

احذف المحتوى الثابت لقسم “مناصب المجلس” واستبدله بـ:

**أ) شريط أدوات:**

- عنوان “مناصب المجلس — الهيئة الإدارية”
- زر “+ إضافة منصب”
- تبديل عرض جدول / كروت

**ب) جدول الإدارة:**

- الأعمدة: # | المنصب | الأيقونة | الأعضاء | عدد المهام | أساسي | إجراءات
- الصفوف قابلة لإعادة الترتيب (drag & drop أو أزرار ↑↓)
- المناصب الأساسية: badge “أساسي” + لا زر حذف
- المناصب المُضافة: زر حذف مع confirm

**ج) عرض الكروت (preview):**

- نفس شكل الكروت الحالي
- بدون أزرار تعديل
- للمراجعة البصرية فقط

### مهمة 3.3 — بناء Modal التعديل/الإضافة

Modal واحد يخدم الإضافة والتعديل:

- **اسم المنصب:** text input
- **الأيقونة:** emoji picker (ممكن مكتبة بسيطة أو input مع أمثلة شائعة)
- **اللجنة المرتبطة:** dropdown اختياري يسحب من `/api/committees.php`
- **الأعضاء:** autocomplete/search يسحب من `/api/members.php` — عند الاختيار يضيف tag قابل للحذف
- **المهام:** حقول نصية ديناميكية — إضافة/حذف/ترتيب

**عند الحفظ:**

1. إذا إنشاء → `POST /api/positions.php` بالعنوان والأيقونة والترتيب
1. ثم `POST ?action=members` بقائمة الأعضاء
1. ثم `POST ?action=tasks` بقائمة المهام
1. إذا تعديل → `PUT` ثم members ثم tasks
1. أعد تحميل الجدول بعد النجاح

### مهمة 3.4 — ربط الإجراءات بالـ API

تأكد أن:

- زر “تعديل” → يفتح modal معبأ بالبيانات الحالية
- زر “أعضاء” → يفتح modal أعضاء
- زر “مهام” → يفتح modal مهام
- زر “حذف” → confirm ثم DELETE
- السحب / ↑↓ → يرسل PUT لتحديث sort_order
- كل عملية تُحدّث الجدول والكروت فوراً

### مهمة 3.5 — اختبار لوحة التحكم

```
✅ الجدول يعرض 6 مناصب بشكل صحيح
✅ الكروت تعرض نفس البيانات
✅ تعديل اسم منصب → يتحدث في الجدول والكروت
✅ تعديل أيقونة → تتحدث
✅ إضافة/إزالة عضو → ينعكس
✅ إضافة/حذف/ترتيب مهام → ينعكس
✅ إضافة منصب جديد → يظهر في الجدول
✅ حذف منصب غير أساسي → ينجح
✅ محاولة حذف منصب أساسي → لا يظهر زر الحذف أصلاً
✅ إعادة ترتيب → ينعكس
✅ الاقتباس من أبو حسين ما تغيّر
✅ لا أخطاء في Console
```

-----

## المرحلة 4: الصفحة العامة

> **اقرأ أولاً:** `.claude/skills/new-page` — واتبع التعليمات.
> **المرجع:** اقرأ ملفات JS الموجودة في `public/js/` (مثل `media.js`, `tree.js`) لفهم النمط: async/await, relative paths, read-only.

### مهمة 4.1 — تحديث HTML

1. افتح ملف الصفحة العامة الذي يحتوي على قسم “إدارة المجلس” (قد يكون `public/index.html` أو ملف منفصل).
1. حدد كتلة HTML الثابتة للهيئة الإدارية (الكروت الستة).
1. **لا تحذف:** العنوان (“إدارة المجلس” / “الهيئة الإدارية”) + الاقتباس من أبو حسين.
1. **احذف:** كروت المناصب الثابتة فقط.
1. **أضف مكانها:**

```html
<div id="council-positions-grid" class="council-grid">
  <!-- يُملأ ديناميكيًا من API -->
</div>
```

### مهمة 4.2 — إنشاء `public/js/positions.js`

```javascript
// public/js/positions.js
// يجلب المناصب من API ويبني الكروت ديناميكياً

(async function() {
  // 1. fetch('/api/positions.php')
  // 2. loop على data
  // 3. لكل منصب: أنشئ كرت بنفس الكلاسات الحالية:
  //    .council-card, .council-icon, .council-name,
  //    .council-tasks, .council-role
  // 4. الكرت الأول (sort_order = 1) يأخذ .president
  // 5. الكرت الأخير يأخذ .advisory
  // 6. أضف الكروت إلى #council-positions-grid
})();
```

**مهم:** اقرأ CSS الحالي للكروت وتأكد أنك تستخدم **نفس الكلاسات بالضبط** — الهدف أن لا يكون هناك أي فرق بصري.

### مهمة 4.3 — ربط الملف بالصفحة

أضف `<script src="js/positions.js"></script>` في المكان المناسب — اتبع نفس نمط تحميل السكريبتات الأخرى (قد يكون عبر `pageScripts` في `ajax-nav.js` أو في الصفحة مباشرة).

### مهمة 4.4 — اختبار الصفحة العامة

```
✅ الكروت الستة تظهر بنفس الشكل القديم بالضبط
✅ الأسماء والمهام صحيحة
✅ الأيقونات تظهر
✅ كرت الرئيس له ستايل مميز (.president)
✅ كرت الاستشارية له ستايل مميز (.advisory)
✅ العنوان والاقتباس ما تغيروا
✅ Responsive على الجوال
✅ RTL صحيح
✅ لا أخطاء في Console
✅ غيّر اسم عضو من لوحة التحكم → تأكد أنه تغيّر في الصفحة العامة
```

-----

## الاختبار النهائي

بعد إنهاء كل المراحل:

```
✅ أضف منصب جديد من لوحة التحكم → يظهر في الصفحة العامة
✅ عدّل أعضاء منصب → ينعكس
✅ عدّل مهام → ينعكس
✅ غيّر أيقونة → ينعكس
✅ أعد ترتيب المناصب → ينعكس
✅ احذف المنصب الجديد → يختفي
✅ حاول حذف "الرئيس" → يُرفض
✅ لا أخطاء في Console في أي صفحة
✅ لا تأثير على أي وظيفة أخرى (أعضاء، لجان، مدفوعات، أخبار...)
✅ Audit log يسجّل كل العمليات
```

-----

## تنبيهات حرجة

1. **لا تُنشئ أعضاء جدد في `members`** — لا INSERT ولا UPDATE على هذا الجدول إطلاقاً.
1. **لا تربط أعضاء بمناصب في الـ Seed** — جدول `position_members` يبقى فارغاً. الربط يتم يدوياً من المدير عبر لوحة التحكم.
1. **لا تُنشئ حسابات عضوية** — لا تلمس جدول `member_users` أبداً.
1. **لا تعدّل** أي ملف API آخر (members.php, committees.php, إلخ).
1. **لا تعدّل** CSS الصفحة العامة — استخدم الكلاسات الموجودة كما هي.
1. **لا تحذف** الاقتباس من أبو حسين أو عنوان القسم.
1. **نفّذ بالتسلسل:** DB → API → Admin → Public. لا تقفز.
1. **اقرأ الكود الحالي** قبل أي تعديل — لا تفترض.
