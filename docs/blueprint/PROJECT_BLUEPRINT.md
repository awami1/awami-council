# PROJECT_BLUEPRINT.md — المخطط الشامل لإعادة البناء
# مجلس عائلة العوامي (Awami Council)

---

## 1. ملخص تنفيذي

**مجلس عائلة العوامي** هو منصة ويب متكاملة لإدارة مجلس عائلي حقيقي تأسس عام 1992م. المنصة تخدم كمركز رقمي شامل يغطي: إدارة الأعضاء، النظام المالي، اللجان، الفعاليات، شجرة العائلة، الأخبار، المعرض المرئي، التصويت، التقارير الذكية، والتواصل المجتمعي.

**التقنيات**: PHP 8.1+ / MySQL 8.0+ / Vanilla JavaScript / CSS3 — بدون أي framework أو build system.

**هذه الوثيقة** تصف كيفية إعادة بناء المشروع بالكامل من الصفر وربطه بقاعدة بيانات جديدة.

---

## 2. الوثائق المرجعية

| الملف | المحتوى |
|-------|---------|
| [PRD.md](./PRD.md) | متطلبات المنتج — 18 ميزة كاملة مع User Flows |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | البنية التقنية — Tech Stack، أنماط التصميم، تدفق الطلبات |
| [DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md) | 20 جدول بالتفصيل — MySQL + SQLite |
| [API_SPEC.md](./API_SPEC.md) | 22+ endpoint — مواصفات REST كاملة |
| [UI_DESIGN_SYSTEM.md](./UI_DESIGN_SYSTEM.md) | نظام التصميم — ألوان، خطوط، مكونات، responsive |
| [ROUTES_MAP.md](./ROUTES_MAP.md) | خريطة المسارات — عامة + admin + API |
| [ENV_TEMPLATE.md](./ENV_TEMPLATE.md) | متغيرات البيئة المطلوبة |
| [DEPLOYMENT.md](./DEPLOYMENT.md) | دليل النشر خطوة بخطوة |

---

## 3. خطة إعادة البناء — الترتيب المقترح

### المرحلة 1: البنية الأساسية (الأساس)

**المدة التقريبية**: الأساس

1. **إعداد هيكل المجلدات** وفق ARCHITECTURE.md
2. **إنشاء `api/config.php`**:
   - تحميل `.env`
   - اتصال PDO (MySQL + SQLite)
   - دوال مساعدة: `respond()`, `bodyJson()`, `uid()`, `isSQLite()`, `today()`
   - Security Headers
   - CORS handling
3. **إنشاء `api/auth_guard.php`**: Session management + CSRF
4. **إنشاء `api/audit_helper.php`**: تسجيل التدقيق
5. **إنشاء `api/validation.php`**: `parseId()`, `sanitizeString()`
6. **إنشاء `api/auth.php`**: Login/Logout/Check مع Rate Limiting
7. **إنشاء `api/setup.php`**: إنشاء جميع الجداول (MySQL + SQLite)
8. **إنشاء `router.php`**: Front Controller + AJAX detection
9. **إنشاء `index.php`**: نقطة الدخول

### المرحلة 2: نظام CSS

1. **`public/css/variables.css`**: Design Tokens + Dark Mode
2. **`public/css/base.css`**: Reset + Typography + Sections
3. **`public/css/layout.css`**: Header + Footer + Navigation + Mobile
4. **`public/css/components.css`**: جميع مكونات UI
5. **`public/css/animations.css`**: Keyframes + Transitions

### المرحلة 3: الموقع العام (Public Site)

1. **`includes/helpers.php`**: دوال البيانات العامة
2. **`includes/head.php`**: Meta + CSS + Fonts
3. **`includes/header.php`**: Navbar + Theme Toggle + Mobile Menu
4. **`includes/footer.php`**: Footer + SW registration + Bottom Nav
5. **`includes/layout.php`**: HTML Wrapper
6. **`pages/home.php`**: الصفحة الرئيسية
7. **`pages/404.php`**: صفحة غير موجودة

### المرحلة 4: JavaScript الأساسي

1. **`public/js/api.js`**: API Client Layer (جميع كائنات API)
2. **`public/js/theme.js`**: Dark/Light mode toggle
3. **`public/js/navbar.js`**: Smart navbar + Scroll-to-top
4. **`public/js/animations.js`**: Scroll reveal + Counters
5. **`public/js/ajax-nav.js`**: SPA navigation + Script loading
6. **`public/js/settings.js`**: تحميل إعدادات الموقع

### المرحلة 5: API Endpoints الأساسية

بناء كل endpoint وفق النمط الموحد في API_SPEC.md:

1. **`api/members.php`** + **`api/committees.php`** + **`api/committee_members`**
2. **`api/periods.php`** + **`api/payments.php`** + **`api/transactions.php`**
3. **`api/events.php`** + **`api/public_events.php`**
4. **`api/news.php`**
5. **`api/family-tree.php`** + **`api/branches.php`**
6. **`api/settings.php`** + **`api/meeting.php`** + **`api/media.php`**
7. **`api/polls.php`**
8. **`api/messages.php`**
9. **`api/reminders.php`** + **`api/audit.php`** + **`api/export.php`**
10. **`api/reports.php`** + **`api/stories.php`** + **`api/gallery-stories.php`**

### المرحلة 6: الصفحات العامة

1. **`pages/council.php`** + **`public/js/countdown.js`**
2. **`pages/tree.php`** + **`public/js/tree.js`** (D3.js)
3. **`pages/news.php`** + **`public/js/news.js`**
4. **`pages/events.php`**
5. **`pages/gallery.php`** + **`public/js/media.js`**
6. **`pages/contact.php`**
7. **`pages/eid.php`** + **`public/js/eid.js`**

### المرحلة 7: لوحة التحكم (Admin Panel)

1. **`admin/css/admin.css`**: أنماط الإدارة الكاملة
2. **`admin/index.php`**: HTML Structure + Sidebar + Pages
3. **`admin/pages/modals.php`**: قوالب النوافذ المنبثقة
4. **`admin/js/admin-core.js`**: State, Toast, Modal, Pagination, Validation
5. **`admin/js/admin-app.js`**: 15+ قسم (Dashboard, Members, Fees, Budget, ...)
6. **`admin/js/admin-import.js`**: استيراد Excel

### المرحلة 8: Core Services

1. **`core/state.js`**: إدارة الحالة المركزية
2. **`core/services/finance.service.js`**: تنسيق مالي
3. **`core/services/member.service.js`**: مزامنة أعضاء
4. **`core/services/poll.service.js`**: تنسيق تصويت

### المرحلة 9: PWA والملفات المساندة

1. **`manifest.json`**: PWA manifest (عربي، RTL)
2. **`sw.js`**: Service Worker (offline fallback)
3. **`offline.html`**: صفحة عدم الاتصال
4. **`sitemap.php`**: مولد Sitemap
5. **`nginx.conf`**: تكوين الخادم
6. **`.htaccess`**: قواعد Apache
7. **`start.sh`** + **`Procfile`**: تشغيل

---

## 4. الاعتماديات الخارجية (External Dependencies)

| المكتبة | الإصدار | الغرض | مصدر التحميل |
|---------|---------|-------|-------------|
| D3.js | v7 | شجرة العائلة التفاعلية | `https://d3js.org/d3.v7.min.js` |
| SheetJS (XLSX) | 0.18.5 | استيراد ملفات Excel | `https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js` |
| Google Fonts | — | خطوط Cairo, Amiri, Tajawal | `https://fonts.googleapis.com` |
| خط Saudi | — | خط عرض عربي مخصص | ملفات TTF محلية في `public/fonts/` |

**ملاحظة**: جميع المكتبات تُحمَّل من CDN. لا يوجد package manager. للموثوقية القصوى، يمكن استضافة نسخ محلية في `public/vendor/`.

---

## 5. نقاط التكامل

### 5.1 قاعدة بيانات جديدة

للربط بقاعدة بيانات جديدة:

1. أنشئ قاعدة MySQL جديدة (utf8mb4_unicode_ci)
2. أنشئ مستخدم بصلاحيات: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
3. عدّل `.env` ببيانات الاتصال
4. شغّل `/api/setup.php` لإنشاء جميع الجداول
5. أضف بيانات اللجان الافتراضية (تُزرع تلقائياً)
6. ابدأ بإضافة البيانات عبر لوحة التحكم

### 5.2 تغيير الدومين

1. حدّث `server_name` في `nginx.conf`
2. أعد إصدار شهادة SSL
3. تحقق أن CORS يعمل (same-origin)
4. حدّث `start_url` في `manifest.json` إذا لزم

### 5.3 تغيير اللغة أو الهوية

- جميع النصوص العربية موجودة في ملفات PHP/JS (لا يوجد نظام i18n)
- ألوان الهوية في `public/css/variables.css`
- بيانات اللجان الافتراضية في `api/committees.php` → `seedDefaultCommittees()`
- الإعدادات الافتراضية في `api/settings.php` → `defaultSettings()`
- manifest.json: اسم التطبيق واتجاهه

---

## 6. إرشادات الجودة والأمان

### 6.1 الأمان (Security Checklist)

- [ ] جميع كلمات المرور مشفرة بـ bcrypt
- [ ] CSRF token على جميع عمليات الكتابة
- [ ] Rate Limiting على تسجيل الدخول (5/15min) والرسائل (5/hr)
- [ ] Prepared Statements لجميع استعلامات SQL
- [ ] htmlspecialchars لجميع المخرجات
- [ ] حد حجم الطلب 64KB
- [ ] Session: HttpOnly, Secure, SameSite=Strict
- [ ] Security Headers: X-Content-Type-Options, X-Frame-Options, HSTS
- [ ] حماية `.env`, `*.db`, `.git` من الوصول
- [ ] Honeypot في نماذج الاتصال
- [ ] Session Regeneration عند تسجيل الدخول
- [ ] تأخير 300ms عند فشل تسجيل الدخول

### 6.2 الكود (Code Quality)

- [ ] `declare(strict_types=1)` في جميع ملفات API
- [ ] `respond()` لجميع استجابات JSON — لا `echo` مباشر
- [ ] `bodyJson()` لقراءة Body — مع حد الحجم
- [ ] `uid()` لجميع المعرفات الجديدة
- [ ] `logAudit()` لجميع عمليات التعديل
- [ ] SQL متوافق مع MySQL و SQLite (استخدم `isSQLite()`)
- [ ] رسائل الخطأ باللغة العربية

### 6.3 الواجهة (UI Quality)

- [ ] RTL بالكامل
- [ ] Dark Mode يعمل لجميع المكونات
- [ ] Responsive لجميع الأحجام
- [ ] إمكانية الوصول (ARIA, Focus, Keyboard)
- [ ] Scripts مسجلة في router.php و ajax-nav.js
- [ ] Animations مع prefers-reduced-motion

---

## 7. اختبار شامل (End-to-End Verification)

### 7.1 الموقع العام

- [ ] الصفحة الرئيسية تُحمَّل بالكامل (Hero + Countdown + Events + News)
- [ ] التصفح AJAX يعمل بين جميع الصفحات
- [ ] زر الرجوع في المتصفح يعمل
- [ ] شجرة العائلة تعرض البيانات + البحث يعمل
- [ ] الأخبار تُفلتر حسب الفئة
- [ ] المعرض يعرض الصور + Lightbox يعمل
- [ ] نموذج التواصل يُرسل بنجاح
- [ ] بطاقة العيد تُنشأ وتُحمَّل
- [ ] العد التنازلي يعمل (إذا أُعدّ اجتماع)
- [ ] Dark Mode يعمل + يُحفظ في localStorage

### 7.2 لوحة التحكم

- [ ] تسجيل الدخول يعمل
- [ ] CSRF token يُرسل في جميع الطلبات
- [ ] جميع أقسام CRUD تعمل (أعضاء، لجان، فعاليات، أخبار، ...)
- [ ] إضافة/إزالة أعضاء من لجان
- [ ] النظام المالي يعمل (فترات → مدفوعات → معاملات)
- [ ] التصويت يعمل (إنشاء → تصويت → إغلاق)
- [ ] التقارير الذكية (رفع Excel → تحليل → حفظ)
- [ ] إعدادات الموقع تُحفظ وتنعكس على الموقع العام
- [ ] سجل التدقيق يُسجل جميع العمليات
- [ ] التصدير CSV يعمل

### 7.3 الأمان

- [ ] Rate Limiting يعمل (5 محاولات → رفض)
- [ ] CSRF يُرفض عند إرسال token خاطئ
- [ ] Honeypot يعمل في نموذج التواصل
- [ ] الملفات الحساسة (.env, .db) محمية
- [ ] 401 عند محاولة الوصول لـ API بدون auth

---

## 8. ملاحظات ختامية

- المشروع **لا يحتوي على اختبارات آلية** — الاختبار يدوي بالكامل
- المشروع **لا يستخدم أي package manager** — لا npm ولا Composer
- **لا يوجد نظام migrations** رسمي — التغييرات عبر `setup.php` و `ALTER TABLE` يدوي
- **Admin Panel** هو ملف واحد كبير (~1036 سطر HTML + ~2350 سطر JS) — قابل للتقسيم مستقبلاً
- **Media Gallery** يُخزن في `website_settings` كـ JSON وليس في جدول منفصل
- جميع المكتبات الخارجية تُحمَّل من **CDN** — إذا تعطل CDN تتعطل الميزة المعتمدة عليه
