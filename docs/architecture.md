# معمارية مشروع مجلس العوامي

## نظرة عامة

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   المتصفح    │────▶│  index.php   │────▶│ router.php  │
│  (العميل)    │◀────│  (نقطة الدخول) │◀────│ (الموجّه)    │
└─────────────┘     └──────────────┘     └──────┬──────┘
                                                │
                    ┌───────────────────────────┼───────────────────┐
                    │                           │                   │
              ┌─────▼──────┐            ┌───────▼───────┐   ┌──────▼──────┐
              │  pages/*.php │            │  api/*.php    │   │ admin/      │
              │  (صفحات عامة) │            │  (REST API)   │   │ index.php   │
              └─────┬──────┘            └───────┬───────┘   └─────────────┘
                    │                           │
              ┌─────▼──────┐            ┌───────▼───────┐
              │ layout.php  │            │  config.php   │
              │ (القالب)     │            │  getPDO()     │
              └────────────┘            └───────┬───────┘
                                                │
                                        ┌───────▼───────┐
                                        │   MySQL /     │
                                        │   SQLite      │
                                        └───────────────┘
```

## تدفق الطلبات

### 1. الصفحات العامة
```
GET /news
  → index.php
  → router.php (يبحث عن '/news' في $routes)
  → يحدد: file=pages/news.php, scripts=[news.js]
  → إذا AJAX: يعرض pages/news.php فقط
  → إذا عادي: يعرض layout.php (head + header + pages/news.php + footer + scripts)
```

### 2. طلبات API
```
POST /api/members.php?id=abc123
  → config.php (getPDO, respond, bodyJson, uid)
  → auth_guard.php (requireAuth, verifyCsrf)
  → validation.php (parseId, sanitizeString)
  → handleMembersPut('abc123')
  → respond(200, ['data' => ...])
```

### 3. AJAX Navigation
```
نقر على رابط → ajax-nav.js يعترض
  → XHR GET /news?_ajax=1
  → router.php يعرض pages/news.php فقط (بدون layout)
  → ajax-nav.js يحدّث #page-content
  → يحمّل السكريبتات من pageScripts['/news']
  → يشغّل inline scripts
```

## نمط API الموحد

كل endpoint في `api/` يتبع نفس الهيكل:

| المكون | الوصف |
|--------|-------|
| `declare(strict_types=1)` | نوع صارم |
| `require config/auth/validation` | تبعيات مشتركة |
| `ensureTable()` | إنشاء الجدول (SQLite + MySQL) |
| `toShape()` | تطبيع صف قاعدة البيانات |
| `validatePayload()` | تحقق من المدخلات |
| `handleGetAll/GetOne/Post/Put/Delete` | الدوال الخمس |
| `match(true) { ... }` | توجيه الطلب حسب HTTP method |
| `try/catch PDOException` | معالجة أخطاء قاعدة البيانات |

## قاعدة البيانات المزدوجة

```php
if (isSQLite()) {
    // SQLite: TEXT types, datetime('now'), لا backticks
} else {
    // MySQL: VARCHAR/ENUM/JSON, CURRENT_TIMESTAMP, backticks, ENGINE=InnoDB
}
```

- **التطوير**: SQLite (ملف `data/council.db`)
- **الإنتاج**: MySQL 8.0+ (عبر متغيرات `.env`)
- **القاعدة**: كل SQL لازم يعمل مع الاثنين

## طبقة CSS

```
variables.css → base.css → layout.css → components.css → animations.css
     │
     ├── :root { --green: #1A5C32; ... }        ← الوضع الفاتح
     └── [data-theme="dark"] { --green: ... }    ← الوضع الداكن
```

## الأمان

| الطبقة | التقنية |
|--------|---------|
| المصادقة | Session + bcrypt + SameSite=Strict |
| CSRF | X-CSRF-Token header على كل كتابة |
| SQL Injection | PDO prepared statements |
| XSS | `esc()` wrapper لـ htmlspecialchars |
| Rate Limiting | 5 محاولات / 15 دقيقة على تسجيل الدخول |
| Audit | `logAudit()` على كل mutation |
