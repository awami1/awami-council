---
name: code-review
description: مراجعة الكود حسب معايير مشروع مجلس العوامي. استخدم هذه المهارة لفحص جودة الكود والتحقق من اتباع الأنماط الموحدة.
user-invocable: true
allowed-tools: Read, Glob, Grep
argument-hint: "[file-or-directory]"
context: fork
---

# مهارة: مراجعة الكود

افحص الملفات المحددة (أو آخر التعديلات) حسب معايير مشروع مجلس العوامي.

## المدخلات

- `$ARGUMENTS` — مسار ملف أو مجلد للفحص
- إذا لم يُحدد، افحص آخر الملفات المعدّلة عبر `git diff --name-only HEAD~1`

## قواعد الفحص

### PHP (`api/*.php`)

1. **strict_types**: كل ملف API لازم يبدأ بـ `declare(strict_types=1);`
2. **Requires**: تأكد من وجود `require_once` لـ `config.php`, `auth_guard.php`, `validation.php`
3. **Auth**: كل endpoint كتابة (POST/PUT/DELETE) لازم يستدعي `requireAuth()`
4. **CSRF**: `verifyCsrf()` لازم يُستدعى في كل endpoint محمي
5. **Prepared Statements**: كل SQL لازم يستخدم PDO prepared statements مع named parameters (`:id`, `:name`)
   - **ممنوع**: string concatenation في SQL queries
   - **ممنوع**: `$_GET` أو `$_POST` مباشرة في SQL
6. **respond()**: كل الاستجابات عبر `respond($code, $body)` — **ممنوع** `echo json_encode()`
7. **Validation**: استخدام `sanitizeString()` من `validation.php` لكل مدخل نصي
8. **Audit**: `logAudit()` على كل عملية POST/PUT/DELETE
9. **Error Messages**: رسائل الخطأ الموجهة للمستخدم **بالعربي**
10. **Dual DB**: إذا فيه `CREATE TABLE`، لازم يدعم SQLite **و** MySQL عبر `isSQLite()`

### JavaScript (`public/js/*.js`, `admin/js/*.js`)

1. **API Calls**: كل الاتصالات عبر `api.get/post/put/del` أو الـ API clients (`MembersAPI`, etc.)
   - **ممنوع**: `fetch()` مباشر للـ API endpoints
2. **var vs const/let**: في `ajax-nav.js` فقط استخدم `var` (IIFE). باقي الملفات: `const`/`let`
3. **XSS Prevention**: لا تدخل `innerHTML` من مدخلات المستخدم بدون escaping

### CSS (`public/css/*.css`, `admin/css/*.css`)

1. **Custom Properties**: استخدم `var(--green)`, `var(--text)`, etc. — **ممنوع** ألوان مباشرة (`#1A5C32`)
2. **Dark Mode**: كل لون جديد لازم يكون معرّف في `:root` **و** `[data-theme="dark"]` في `variables.css`
3. **RTL**: لا تستخدم `left`/`right` بدون التأكد من التوافق مع RTL. يُفضل `margin-inline-start/end`
4. **Radius**: استخدم `var(--radius)`, `var(--radius-lg)`, `var(--radius-xl)`

### عام

1. **Page Registration**: إذا فيه صفحة جديدة في `router.php`، تأكد إنها مسجلة في `ajax-nav.js` أيضاً
2. **SQL Injection**: أي SQL بدون prepared statements = **خطر أمني**
3. **Sensitive Data**: تأكد إن `.env`, `*.db`, credentials ما تنكشف

## شكل التقرير

اعرض النتائج بالشكل التالي:

```
## نتائج مراجعة الكود

### ✅ نجح
- [ملف:سطر] وصف ما هو صحيح

### ⚠️ تحذيرات
- [ملف:سطر] وصف التحذير — **السبب**: لماذا هذا مهم

### ❌ مشاكل يجب إصلاحها
- [ملف:سطر] وصف المشكلة — **الحل**: كيف تصلحها

### 📊 ملخص
- إجمالي الملفات المفحوصة: X
- نجح: X | تحذيرات: X | مشاكل: X
```

## ملاحظات
- ركّز على المشاكل الفعلية لا التجميلية
- لا تقترح تعديلات خارج نطاق الفحص
- إذا الملف يتبع النمط الصحيح 100%، أكّد ذلك باختصار
