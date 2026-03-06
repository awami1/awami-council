---
name: new-api-endpoint
description: إنشاء API endpoint جديد كامل مع قاعدة البيانات و JavaScript client. استخدم هذه المهارة عند إضافة مورد جديد للـ API.
user-invocable: true
allowed-tools: Read, Edit, Write, Glob, Grep
argument-hint: "[resource-name] [field1:type field2:type ...]"
---

# مهارة: إنشاء API Endpoint جديد

أنشئ API endpoint كامل لمورد جديد في مشروع مجلس العوامي. اتبع النمط الموحد المستخدم في جميع الـ 28 endpoint الموجودة.

## المدخلات المطلوبة

استخرج من `$ARGUMENTS`:
- **اسم المورد** (مثل: `documents`, `announcements`) — يُستخدم كاسم الملف والجدول
- **الحقول** (اختياري) — قائمة حقول مع أنواعها (مثل: `title:string content:text status:enum`)

إذا لم يُحدد المستخدم الحقول، اسأله عنها.

## الخطوات

### الخطوة 1: إنشاء `api/{resource}.php`

اقرأ أولاً الملفات المرجعية لفهم النمط:
- `api/news.php` — مثال كامل (public GET + auth writes)
- `api/config.php` — الدوال المشتركة (`getPDO`, `respond`, `bodyJson`, `uid`, `isSQLite`)
- `api/validation.php` — دوال التحقق (`parseId`, `sanitizeString`)
- `api/audit_helper.php` — تسجيل المراجعة (`logAudit`)

أنشئ الملف بالهيكل التالي **بالضبط**:

```php
<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';
requireAuth();
verifyCsrf();

// ──────────────────────────────────────────────────────────────
// إنشاء الجدول إن لم يكن موجوداً
// ──────────────────────────────────────────────────────────────

function ensure{Resource}Table(): void
{
    $pdo = getPDO();
    if (isSQLite()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS {table} (
            id TEXT PRIMARY KEY,
            {fields_sqlite}
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{table}` (
            `id` VARCHAR(64) NOT NULL,
            {fields_mysql}
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

ensure{Resource}Table();

// ──────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────

function {resource}ToShape(array $row): array
{
    return [
        'id' => $row['id'],
        // ... map all fields with ?? defaults
        'created_at' => $row['created_at'] ?? '',
        'updated_at' => $row['updated_at'] ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────
// VALIDATION
// ──────────────────────────────────────────────────────────────

function validate{Resource}Payload(array $data, bool $requireAll = true): array
{
    $fields = [];
    // For each field: use sanitizeString() from validation.php
    // Required fields: respond(422, ['error' => '"field" مطلوب.']) if missing
    return $fields;
}

// ──────────────────────────────────────────────────────────────
// HANDLERS
// ──────────────────────────────────────────────────────────────

function handle{Resource}GetAll(): void { /* SELECT * with filters, ORDER BY created_at DESC */ }
function handle{Resource}GetOne(string $id): void { /* SELECT WHERE id = :id */ }
function handle{Resource}Post(): void { /* requireAuth(), bodyJson(), validate, INSERT, logAudit */ }
function handle{Resource}Put(string $id): void { /* requireAuth(), bodyJson(), validate(requireAll:false), dynamic SET, logAudit */ }
function handle{Resource}Delete(string $id): void { /* requireAuth(), fetch name, DELETE, logAudit */ }

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$id     = parseId();

try {
    match (true) {
        $method === 'GET'    && $id === null  => handle{Resource}GetAll(),
        $method === 'GET'    && $id !== null  => handle{Resource}GetOne($id),
        $method === 'POST'                    => handle{Resource}Post(),
        $method === 'PUT'    && $id !== null  => handle{Resource}Put($id),
        $method === 'DELETE' && $id !== null  => handle{Resource}Delete($id),
        $method === 'PUT'    && $id === null  => respond(400, ['error' => '?id= required for PUT.']),
        $method === 'DELETE' && $id === null  => respond(400, ['error' => '?id= required for DELETE.']),
        default                               => respond(405, ['error' => 'Method not allowed.']),
    };
} catch (PDOException $e) {
    error_log('PDOException in {resource}: ' . $e->getMessage());
    respond(500, ['error' => 'Database error.']);
}
```

### قواعد مهمة للـ PHP:
- استخدم `sanitizeString()` لكل حقل نصي
- استخدم `uid()` لتوليد ID جديد
- اقبل `$data['id']` الاختياري في POST (للـ seeding): `$id = isset($data['id']) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', (string) $data['id']) ? $data['id'] : uid();`
- في PUT، ابني SET clauses ديناميكياً (انظر `api/news.php` سطر 238-243)
- في DELETE، اجلب الاسم/العنوان قبل الحذف لرسالة الاستجابة
- استخدم `logAudit('إنشاء', '{resource}', $id, $name)` للـ POST
- استخدم `logAudit('تعديل', '{resource}', $id, $name)` للـ PUT
- استخدم `logAudit('حذف', '{resource}', $id, $name)` للـ DELETE
- رسائل الخطأ بالعربي (مثل: `'لا توجد حقول للتحديث.'`)

### الخطوة 2: إضافة API Client في `public/js/api.js`

اقرأ `public/js/api.js` واضف block جديد بنفس النمط:

```javascript
// –– {Resource} ––
const {Resource}API = {
  getAll: ()          => api.get('{resource}.php'),
  create: (data)      => api.post('{resource}.php', data),
  update: (id, data)  => api.put('{resource}.php', data, id),
  delete: (id)        => api.del('{resource}.php', id),
};
```

### الخطوة 3: إضافة الجدول في `api/setup.php`

اقرأ `api/setup.php` وأضف SQL الجدول الجديد بنفس نمط الجداول الموجودة (SQLite + MySQL).

## تحقق نهائي

بعد الانتهاء تأكد من:
- [ ] `declare(strict_types=1)` في أول سطر
- [ ] جميع الـ requires موجودة (config, auth_guard, audit_helper, validation)
- [ ] SQL يعمل مع SQLite **و** MySQL (استخدم `isSQLite()`)
- [ ] prepared statements مع named parameters (`:id`, `:title`, etc.)
- [ ] `logAudit()` على كل POST/PUT/DELETE
- [ ] رسائل الخطأ بالعربي
- [ ] API client مضاف في `api.js`
