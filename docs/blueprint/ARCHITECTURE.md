# ARCHITECTURE.md — البنية التقنية
# مجلس عائلة العوامي (Awami Council)

---

## 1. التقنيات المستخدمة (Tech Stack)

| الطبقة | التقنية | الإصدار |
|--------|---------|---------|
| **Backend** | PHP (strict types, PDO) | 8.1+ |
| **قاعدة البيانات (Production)** | MySQL | 8.0+ |
| **قاعدة البيانات (Development)** | SQLite | 3.x |
| **Frontend** | Vanilla JavaScript (ES6+) | — |
| **التنسيق** | CSS3 مع Custom Properties | — |
| **خادم Production** | Nginx + PHP-FPM | — |
| **خادم Development** | PHP Built-in Server | — |
| **المصادقة** | Session-based + bcrypt + CSRF | — |
| **PWA** | Service Worker | — |
| **شجرة العائلة** | D3.js | v7 |
| **استيراد Excel** | SheetJS (XLSX) | 0.18.5 |
| **الخطوط** | Google Fonts (Cairo, Amiri, Tajawal) | — |

### لا يوجد:
- Package Manager (لا npm، لا Composer)
- Build System (لا Webpack، لا Vite)
- Framework (لا Laravel، لا React)
- ORM (SQL مباشر عبر PDO)
- اختبارات آلية

---

## 2. هيكل المجلدات

```
awami-council/
├── index.php              # نقطة الدخول → يُحيل إلى router.php
├── router.php             # Front Controller — توجيه الطلبات
│
├── api/                   # نقاط REST API (ملف PHP لكل مورد)
│   ├── config.php         # اتصال DB، تحميل .env، أدوات مشتركة
│   ├── auth.php           # تسجيل دخول/خروج
│   ├── auth_guard.php     # حراسة الجلسة + CSRF
│   ├── audit_helper.php   # تسجيل التدقيق
│   ├── validation.php     # أدوات تحقق مشتركة
│   ├── members.php        # أعضاء CRUD
│   ├── payments.php       # مدفوعات CRUD + Upsert
│   ├── transactions.php   # معاملات مالية CRUD + Bulk
│   ├── periods.php        # فترات مالية
│   ├── events.php         # فعاليات CRUD
│   ├── committees.php     # لجان CRUD + Seed
│   ├── news.php           # أخبار CRUD (GET عام)
│   ├── family-tree.php    # شجرة عائلة CRUD (GET عام)
│   ├── media.php          # معرض CRUD
│   ├── polls.php          # تصويت CRUD + Vote
│   ├── settings.php       # إعدادات الموقع
│   ├── meeting.php        # الاجتماع القادم
│   ├── branches.php       # فروع العائلة
│   ├── messages.php       # رسائل التواصل (POST عام)
│   ├── reminders.php      # تذكيرات الدفع
│   ├── audit.php          # عارض سجل التدقيق
│   ├── export.php         # تصدير CSV
│   ├── reports.php        # تقارير محفوظة
│   ├── stories.php        # قصص وسير
│   ├── gallery-stories.php # قصص المعرض
│   ├── public_events.php  # فعاليات عامة
│   ├── diagnostics.php    # تشخيص النظام
│   └── setup.php          # إنشاء جداول DB
│
├── admin/                 # لوحة التحكم (SPA مستقلة)
│   ├── index.php          # لوحة التحكم الرئيسية (~1036 سطر)
│   ├── css/admin.css      # أنماط الإدارة
│   ├── js/
│   │   ├── admin-core.js  # أدوات أساسية (State, UI, Toast, Modal)
│   │   ├── admin-app.js   # منطق التطبيق الكامل (~2350 سطر)
│   │   └── admin-import.js # استيراد Excel
│   └── pages/modals.php   # قوالب النوافذ المنبثقة
│
├── includes/              # قوالب PHP مشتركة للموقع العام
│   ├── layout.php         # غلاف HTML الكامل
│   ├── helpers.php        # دوال بيانات عامة
│   ├── head.php           # محتوى <head>
│   ├── header.php         # الشريط العلوي + القائمة
│   └── footer.php         # التذييل + Service Worker
│
├── pages/                 # قوالب الصفحات العامة
│   ├── home.php           # الصفحة الرئيسية
│   ├── council.php        # صفحة المجلس
│   ├── tree.php           # شجرة العائلة (D3.js)
│   ├── news.php           # الأخبار
│   ├── events.php         # الفعاليات
│   ├── gallery.php        # المعرض
│   ├── contact.php        # تواصل معنا
│   ├── eid.php            # تهنئة العيد
│   ├── riwaq.php          # الرِّوَاق (مستقلة)
│   └── 404.php            # صفحة غير موجودة
│
├── public/                # ملفات ثابتة
│   ├── css/
│   │   ├── variables.css  # Design Tokens + Dark Mode
│   │   ├── base.css       # إعادة تعيين + أنماط أساسية
│   │   ├── layout.css     # تخطيط + Header/Footer
│   │   ├── components.css # مكونات واجهة المستخدم
│   │   └── animations.css # حركات CSS
│   ├── js/
│   │   ├── api.js         # طبقة عميل API
│   │   ├── ajax-nav.js    # تصفح SPA عبر AJAX
│   │   ├── navbar.js      # سلوك القائمة
│   │   ├── theme.js       # تبديل الوضع الداكن/الفاتح
│   │   ├── animations.js  # حركات بالتمرير
│   │   ├── countdown.js   # مؤقت الاجتماع
│   │   ├── tree.js        # عارض D3.js للشجرة
│   │   ├── news.js        # منطق صفحة الأخبار
│   │   ├── media.js       # منطق صفحة المعرض
│   │   ├── eid.js         # منشئ بطاقة العيد
│   │   └── settings.js    # تحميل الإعدادات
│   └── fonts/             # خطوط Saudi العربية (TTF)
│
├── core/                  # خدمات Frontend مشتركة
│   ├── state.js           # إدارة حالة مركزية
│   └── services/
│       ├── finance.service.js  # تنسيق عمليات مالية
│       ├── member.service.js   # مزامنة أعضاء مع API
│       └── poll.service.js     # تنسيق عمليات تصويت
│
├── assets/                # صور ثابتة (قوالب العيد)
├── data/                  # قاعدة SQLite (تطوير فقط)
├── .env.example           # قالب متغيرات البيئة
├── nginx.conf             # تكوين Nginx
├── .htaccess              # قواعد Apache
├── Procfile               # تعريف العملية للاستضافة
├── start.sh               # سكريبت تشغيل التطوير
├── manifest.json          # PWA manifest (عربي، RTL)
├── sw.js                  # Service Worker
├── sitemap.php            # مولد Sitemap ديناميكي
└── offline.html           # صفحة عدم الاتصال
```

---

## 3. أنماط التصميم المعماري

### 3.1 Front Controller Pattern

```
المستخدم → index.php → router.php
                            │
                            ├── طلب عادي → includes/layout.php (Header + Page + Footer)
                            ├── طلب AJAX → محتوى الصفحة فقط (بدون Layout)
                            ├── /api/*   → ملف API مباشر
                            └── /admin   → admin/index.php
```

**كيف يعمل**:
1. `index.php` يُحمّل `router.php`
2. `router.php` يُحلل URI ويبحث في جدول المسارات
3. إذا كان طلب AJAX (`X-Requested-With: XMLHttpRequest` أو `?_ajax=1`): يُعيد محتوى الصفحة فقط
4. إذا كان طلب عادي: يُعيد الصفحة داخل `layout.php` (Header + Content + Footer + Scripts)
5. إذا كان `/api/*`: PHP Built-in Server يُحيل مباشرة للملف
6. إذا كان `/admin`: يُحمّل `admin/index.php`

### 3.2 API Endpoint Pattern

كل ملف API يتبع نفس الهيكل:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/validation.php';

requireAuth();   // → 401 إذا لم يكن مصادق
verifyCsrf();    // → 403 إذا كان CSRF token غير صالح

// --- Handler Functions ---
function handleGetAll(): void { ... respond(200, [...]); }
function handleGetOne(string $id): void { ... }
function handlePost(): void { ... }
function handlePut(string $id): void { ... }
function handleDelete(string $id): void { ... }

// --- Router ---
$method = $_SERVER['REQUEST_METHOD'];
$id = parseId(); // من $_GET['id']

match (true) {
    $method === 'GET' && $id === null  => handleGetAll(),
    $method === 'GET' && $id !== null  => handleGetOne($id),
    $method === 'POST'                 => handlePost(),
    $method === 'PUT' && $id !== null  => handlePut($id),
    $method === 'DELETE' && $id !== null => handleDelete($id),
    default => respond(405, ['error' => 'Method not allowed.']),
};
```

### 3.3 AJAX SPA Navigation

```
المستخدم ينقر رابط → ajax-nav.js يعترض
  → XHR GET /page?_ajax=1 (يعيد HTML بدون Layout)
  → تحديث #page-content
  → History.pushState()
  → تحميل Scripts خاصة بالصفحة من pageScripts map
  → إعادة تهيئة Animations
```

**pageScripts Map**:
```javascript
{
  '/':        ['/public/js/countdown.js'],
  '/council': ['/public/js/countdown.js'],
  '/tree':    ['https://d3js.org/d3.v7.min.js', '/public/js/tree.js'],
  '/gallery': ['/public/js/media.js'],
  '/news':    ['/public/js/news.js'],
  '/eid':     ['/public/js/eid.js'],
}
```

### 3.4 Dual Database Architecture

```
getPDO() في config.php
    │
    ├── DB_HOST مُعيَّن → MySQL (Production)
    │   └── mysql:host=X;dbname=Y;charset=utf8mb4
    │
    └── DB_HOST فارغ → SQLite (Development)
        └── sqlite:data/awami.db + WAL mode + Foreign Keys ON
```

**اختلافات SQL**:
- MySQL: `ENUM(...)`, `JSON`, `TINYINT(1)`, `BIGINT UNSIGNED AUTO_INCREMENT`, `ON DUPLICATE KEY UPDATE`
- SQLite: `TEXT ... CHECK(...)`, `TEXT`, `INTEGER`, `INTEGER PRIMARY KEY AUTOINCREMENT`, `ON CONFLICT(...) DO UPDATE`
- دالة `isSQLite()` للتفريق بين الاستعلامات

---

## 4. نظام المصادقة والأمان

### 4.1 المصادقة (Authentication)

```
POST /api/auth.php?action=login
  Body: { username, password }
  │
  ├── Rate Limiting Check (5 محاولات / 15 دقيقة لكل IP)
  │   └── ملف مؤقت: /tmp/awami_rl_{md5(IP)}.json
  │
  ├── التحقق من بيانات الاعتماد
  │   ├── ADMIN_USERNAME من .env (افتراضي: admin)
  │   ├── أولاً: password_verify() مع ADMIN_PASSWORD_HASH
  │   └── ثانياً: hash_equals() مع ADMIN_PASSWORD (نص عادي)
  │
  ├── نجاح →
  │   ├── session_regenerate_id(true) — منع Session Fixation
  │   ├── $_SESSION['awami_admin'] = true
  │   ├── $_SESSION['csrf_token'] = bin2hex(random_bytes(32))
  │   └── logAudit('دخول', ...)
  │
  └── فشل →
      ├── usleep(300_000) — تأخير ضد التخمين
      └── تسجيل المحاولة في ملف Rate Limit
```

### 4.2 حراسة CSRF

```
كل طلب POST/PUT/DELETE يمر عبر verifyCsrf()
  → يقرأ X-CSRF-Token من Header
  → يقارنه مع $_SESSION['csrf_token'] عبر hash_equals()
  → فشل → 403 "CSRF token invalid."
```

### 4.3 إعدادات الجلسة

```php
session_name('awami_session');
session_set_cookie_params([
    'lifetime' => 0,        // حتى إغلاق المتصفح
    'path'     => '/',
    'secure'   => true,      // HTTPS فقط
    'httponly'  => true,      // لا يمكن الوصول عبر JavaScript
    'samesite' => 'Strict',  // حماية CSRF
]);
```

### 4.4 Security Headers

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains (HTTPS فقط)
```

### 4.5 حماية الملفات

| الملف | Nginx | .htaccess |
|-------|-------|-----------|
| `.env` | `location ~ /\.env { deny all; }` | `FilesMatch` |
| `*.db` | `location ~ \.db$ { deny all; }` | `FilesMatch` |
| `.git/` | `location ~ /\.git { deny all; }` | — |
| `api/config.php` | `location = /api/config.php { deny all; }` | — |

---

## 5. تدفق الطلبات (Request Flow)

### 5.1 طلب صفحة عامة (مباشر)
```
GET /news
  → index.php → router.php
  → $route = ['file' => 'pages/news.php', 'scripts' => ['/public/js/news.js']]
  → $isAjax = false
  → include layout.php
      → head.php (meta, CSS, fonts)
      → header.php (navbar)
      → pages/news.php (المحتوى)
      → footer.php (تذييل + SW)
      → scripts: api.js, theme.js, navbar.js, animations.js, news.js, ajax-nav.js
```

### 5.2 طلب AJAX (تصفح SPA)
```
GET /news?_ajax=1
  Headers: X-Requested-With: XMLHttpRequest
  → router.php
  → $isAjax = true
  → include pages/news.php (المحتوى فقط — بدون Layout)
  → Content-Type: text/html; Cache-Control: no-store
```

### 5.3 طلب API محمي
```
PUT /api/members.php?id=abc123
  Headers: X-CSRF-Token: xxxxx
  Body: { "name": "أحمد", "status": "نشط" }
  → config.php (DB connection, security headers, CORS)
  → auth_guard.php → requireAuth() → isAuthenticated()
  → verifyCsrf() → يتحقق من X-CSRF-Token
  → handlePut('abc123') → UPDATE members ... → respond(200, {...})
  → logAudit('تعديل', 'عضو', 'abc123', 'أحمد')
```

---

## 6. Frontend Architecture

### 6.1 CSS Architecture

```
variables.css (Design Tokens + Dark Mode)
    ↓
base.css (Reset + Body + Sections + Typography)
    ↓
layout.css (Header + Footer + Navigation + Mobile Bottom Nav)
    ↓
components.css (Cards + Forms + Tables + Modals + Gallery + Tree + Eid + ...)
    ↓
animations.css (Keyframes + Transitions + Loading)

admin/css/admin.css (مستقل — Dashboard + Sidebar + Tables + Forms + Mobile)
```

### 6.2 JavaScript Architecture

**الموقع العام**:
```
api.js (API Client Layer — جميع كائنات API)
  ↕
settings.js (تحميل إعدادات الموقع من API)
theme.js (Dark/Light mode)
navbar.js (سلوك القائمة + Scroll)
animations.js (Scroll Reveal + Counters + Accordion + Parallax)
ajax-nav.js (SPA Navigation + Script Loading)

Page-specific:
  countdown.js → الصفحة الرئيسية + المجلس
  tree.js → شجرة العائلة (D3.js)
  news.js → الأخبار (Fetch + Filter + Pagination)
  media.js → المعرض (Filter + Lightbox)
  eid.js → بطاقة العيد (Canvas)
```

**لوحة التحكم (Admin)**:
```
admin-core.js (State, UI Utilities, Toast, Modal, Pagination, Avatar)
  ↕
admin-app.js (15+ أقسام: Dashboard, Members, Fees, Budget, Events, ...)
  ↕
admin-import.js (استيراد Excel عبر XLSX library)
  ↕
api.js (يُضمن عبر readfile() مع حقن CSRF token)
```

### 6.3 State Management (Admin)

```
core/state.js — مصدر الحقيقة الوحيد لبيانات الإدارة
  │
  ├── State.init(data) — تهيئة من بيانات محفوظة
  ├── State.getMembers() — قراءة مباشرة
  ├── State.saveMember(m) — طفرة + تسجيل نشاط
  └── State.replaceDB(data) — استبدال كامل (استيراد/استعادة)

core/services/ — طبقة تنسيق بين UI و State و API
  ├── finance.service.js — فترات + مدفوعات + معاملات
  ├── member.service.js — مزامنة أعضاء مع MySQL API
  └── poll.service.js — تصويت
```

---

## 7. الخدمات الخارجية

| الخدمة | الغرض | مصدر التحميل |
|--------|-------|-------------|
| D3.js v7 | شجرة العائلة التفاعلية | `https://d3js.org/d3.v7.min.js` |
| SheetJS (XLSX) 0.18.5 | استيراد ملفات Excel | `https://cdnjs.cloudflare.com/...` |
| Google Fonts | خطوط Cairo, Amiri, Tajawal | `https://fonts.googleapis.com` |
| خط Saudi | خط عرض مخصص | ملفات TTF محلية في `public/fonts/` |

---

## 8. PWA (Progressive Web App)

```json
// manifest.json
{
  "name": "مجلس عائلة العوامي",
  "short_name": "العوامي",
  "start_url": "/",
  "display": "standalone",
  "theme_color": "#1A5C32",
  "background_color": "#FDFCF8",
  "dir": "rtl",
  "lang": "ar"
}
```

**Service Worker** (`sw.js`):
- Cache: `awami-offline-v2`
- يُخزن فقط `offline.html`
- عند فشل الاتصال يعرض صفحة Offline
- لا يُخزن CSS أو JS أو صور (دعم offline محدود)
