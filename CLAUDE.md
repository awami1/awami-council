# Awami Council — مجلس عائلة العوامي

منصة ويب لإدارة مجلس عائلي: أعضاء، مالية، فعاليات، لجان، شجرة عائلة، أخبار.
موقع عربي بالكامل (RTL). بدون build system أو package manager.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.1+, PDO |
| Database | MySQL 8.0+ (production) / SQLite (development) |
| Frontend | Vanilla JS (ES6+), CSS3 custom properties |
| Server | Nginx (production), `php -S 0.0.0.0:80 router.php` (dev) |
| Auth | Session-based, bcrypt, CSRF tokens |

## Project Structure

```
├── index.php / router.php    # Entry point → front controller
├── api/                      # REST API (one PHP file per resource)
│   ├── config.php            # DB connection, respond(), bodyJson(), uid()
│   ├── auth_guard.php        # requireAuth(), verifyCsrf()
│   ├── validation.php        # parseId(), sanitizeString()
│   └── ...                   # ~20 resource endpoints
├── admin/                    # Admin panel (SPA, monolithic)
├── pages/                    # Public page templates
├── includes/                 # Shared layout (header, footer, helpers)
├── public/css/               # variables → base → layout → components → animations
├── public/js/                # api.js, ajax-nav.js, page-specific scripts
└── docs/                     # Architecture docs & runbooks
```

## Core Rules

1. **Arabic RTL** — كل النصوص والواجهات بالعربي. رسائل خطأ API بالعربي. `direction: rtl` على `<html>`
2. **Dual-mode DB** — SQL لازم يشتغل مع MySQL **و** SQLite. استخدم `isSQLite()` للفروق
3. **UUIDs for IDs** — كل السجلات تستخدم `uid()` لتوليد ID
4. **No frameworks** — vanilla JS فقط. API calls عبر clients في `api.js`. لا `fetch()` مباشر
5. **Prepared statements only** — PDO named parameters (`:id`). ممنوع string concatenation في SQL
6. **Dual registration** — صفحة جديدة = تسجيل في `router.php` **و** `ajax-nav.js`
7. **Dark mode support** — كل CSS يستخدم custom properties. ألوان جديدة تتعرف في `:root` و `[data-theme="dark"]`

## Dev Server

```bash
cp .env.example .env          # اترك DB_HOST فارغ لاستخدام SQLite
bash start.sh                 # أو: php -S localhost:8080 router.php
# ثم زُر /api/setup.php لإنشاء الجداول
```

## Key References

- @docs/architecture.md — هيكل النظام ومسار الطلبات
- @docs/runbooks/add-feature.md — دليل إضافة ميزات (صفحات، API، أدمن)
- @docs/runbooks/database-changes.md — دليل تعديل قاعدة البيانات
- @api/config.php — الدوال المشتركة: `getPDO`, `respond`, `bodyJson`, `uid`, `isSQLite`
- @api/news.php — مرجع لنمط API endpoint كامل

## Skills & Hooks

| Skill | Purpose |
|-------|---------|
| `/new-api-endpoint` | إنشاء API endpoint كامل مع DB schema و JS client |
| `/new-page` | إنشاء صفحة عامة مع التسجيل المزدوج (router + ajax-nav) |
| `/code-review` | مراجعة الكود حسب معايير المشروع |

| Hook | Trigger | Action |
|------|---------|--------|
| `check-page-sync.sh` | After file edit/write | يتحقق من تطابق routes في `router.php` مع `pageScripts` في `ajax-nav.js` |

## Rules (auto-loaded by path)

| Rule | Paths | Content |
|------|-------|---------|
| `rules/api-pattern.md` | `api/**/*.php` | نمط API، دوال PHP، قواعد SQL |
| `rules/frontend.md` | `public/**/*`, `pages/**/*` | JS/CSS conventions، تسجيل مزدوج |
| `rules/admin.md` | `admin/**/*` | بنية الأدمن، CSRF، توصيات |
| `rules/known-issues.md` | (always loaded) | SW limitations، حماية ملفات، cache busting |
