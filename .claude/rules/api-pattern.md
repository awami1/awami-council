---
paths:
  - "api/**/*.php"
---

# API Endpoint Pattern

## بنية كل endpoint

كل ملف API يتبع نفس الترتيب:

1. `require_once` لـ `config.php`, `auth_guard.php`, `audit_helper.php`, `validation.php`
2. `requireAuth()` + `verifyCsrf()` للـ endpoints المحمية
3. دالة `ensure{Resource}Table()` — إنشاء الجدول (SQLite + MySQL عبر `isSQLite()`)
4. دالة `{resource}ToShape()` — تحويل row لـ response shape
5. دالة `validate{Resource}Payload()` — تحقق من المدخلات
6. دوال CRUD: `handleGetAll()`, `handleGetOne($id)`, `handlePost()`, `handlePut($id)`, `handleDelete($id)`
7. `match` router على `$_SERVER['REQUEST_METHOD']` + `parseId()`

## دوال مشتركة (من `api/config.php`)

| الدالة | الاستخدام |
|--------|-----------|
| `respond($code, $body)` | إرجاع JSON — **لا تستخدم** `echo json_encode()` أبداً |
| `bodyJson()` | قراءة request body (مع حد 64KB) |
| `uid()` | توليد UUID للسجلات الجديدة |
| `getPDO()` | singleton PDO connection |
| `isSQLite()` | كشف نوع قاعدة البيانات للفروق في SQL |

## دوال من ملفات أخرى

| الدالة | الملف | الاستخدام |
|--------|-------|-----------|
| `parseId()` | `validation.php` | قراءة `$_GET['id']` بأمان |
| `sanitizeString()` | `validation.php` | تنظيف المدخلات النصية |
| `logAudit($action, $type, $id, $name)` | `audit_helper.php` | تسجيل كل POST/PUT/DELETE |

## قواعد SQL

- **دائماً** prepared statements مع named parameters (`:id`, `:title`)
- **ممنوع** string concatenation في queries
- CREATE TABLE لازم يدعم MySQL **و** SQLite — استخدم `isSQLite()`
- في PUT، ابني SET clauses ديناميكياً (انظر `api/news.php` كمرجع)

## قواعد عامة

- رسائل الخطأ الموجهة للمستخدم **بالعربي** (مثل: `'لا توجد حقول للتحديث.'`)
- في POST، اقبل `$data['id']` الاختياري للـ seeding
- في DELETE، اجلب الاسم قبل الحذف لرسالة logAudit
- أضف API client في `public/js/api.js` لكل endpoint جديد
