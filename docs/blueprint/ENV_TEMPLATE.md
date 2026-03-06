# ENV_TEMPLATE.md — متغيرات البيئة المطلوبة
# مجلس عائلة العوامي (Awami Council)

---

## ملف `.env`

يُوضع في جذر المشروع. يُقرأ تلقائياً بواسطة `api/config.php`.

```env
# ──────────────────────────────────────────────────
# قاعدة البيانات (Database)
# ──────────────────────────────────────────────────
# MySQL — بيئة الإنتاج (Production)
# اتركها فارغة لاستخدام SQLite في التطوير المحلي
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=
DB_PORT=3306

# ──────────────────────────────────────────────────
# بيانات المشرف (Admin Credentials)
# ──────────────────────────────────────────────────
ADMIN_USERNAME=admin

# كلمة المرور المشفرة (bcrypt) — الأولوية
# أنشئها بتنفيذ:
#   php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
ADMIN_PASSWORD_HASH=

# كلمة المرور كنص عادي — تُستخدم فقط إذا لم يُعيَّن HASH
# يُنصح باستخدام HASH في بيئة الإنتاج
# ADMIN_PASSWORD=

# ──────────────────────────────────────────────────
# إعدادات النظام (System Settings)
# ──────────────────────────────────────────────────
# اضبطها على "true" لتعطيل /api/setup.php في الإنتاج
SETUP_DISABLED=false
```

---

## شرح المتغيرات

### متغيرات قاعدة البيانات

| المتغير | النوع | مطلوب | الوصف |
|---------|-------|-------|-------|
| `DB_HOST` | string | لا* | عنوان خادم MySQL. اتركه فارغاً لاستخدام SQLite |
| `DB_NAME` | string | لا* | اسم قاعدة البيانات MySQL |
| `DB_USER` | string | لا* | اسم مستخدم MySQL |
| `DB_PASS` | string | لا* | كلمة مرور MySQL |
| `DB_PORT` | integer | لا | منفذ MySQL (افتراضي: 3306) |

> *مطلوبة جميعاً إذا أردت استخدام MySQL. إذا تُركت فارغة يُستخدم SQLite تلقائياً.

### متغيرات المصادقة

| المتغير | النوع | مطلوب | الوصف |
|---------|-------|-------|-------|
| `ADMIN_USERNAME` | string | نعم | اسم المستخدم للمشرف (افتراضي: admin) |
| `ADMIN_PASSWORD_HASH` | string | نعم** | كلمة المرور مشفرة بـ bcrypt |
| `ADMIN_PASSWORD` | string | لا | كلمة المرور كنص عادي (بديل أقل أماناً) |

> **يجب تعيين إما `ADMIN_PASSWORD_HASH` أو `ADMIN_PASSWORD`. إذا لم يُعيَّن أي منهما، يُرفض تسجيل الدخول مع رسالة خطأ 503.

### متغيرات النظام

| المتغير | النوع | مطلوب | الوصف |
|---------|-------|-------|-------|
| `SETUP_DISABLED` | boolean | لا | `true` لتعطيل إنشاء الجداول في الإنتاج |

---

## كيفية إنشاء كلمة مرور مشفرة

```bash
# طريقة 1: سطر أوامر PHP
php -r "echo password_hash('your_secure_password', PASSWORD_BCRYPT);"

# طريقة 2: سكريبت PHP
php -r "echo password_hash('كلمة_المرور_الآمنة', PASSWORD_BCRYPT) . PHP_EOL;"
```

انسخ الناتج (يبدأ بـ `$2y$...`) وضعه في `ADMIN_PASSWORD_HASH`.

---

## مثال لبيئة الإنتاج

```env
DB_HOST=mysql.example.com
DB_NAME=awami_council
DB_USER=awami_user
DB_PASS=strong_random_password_here
DB_PORT=3306

ADMIN_USERNAME=admin
ADMIN_PASSWORD_HASH=$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

SETUP_DISABLED=true
```

## مثال لبيئة التطوير

```env
# فارغ = SQLite
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=
DB_PORT=3306

ADMIN_USERNAME=admin
ADMIN_PASSWORD_HASH=$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

SETUP_DISABLED=false
```

---

## آلية تحميل المتغيرات

المتغيرات تُحمَّل تلقائياً في `api/config.php` عبر:

1. قراءة ملف `.env` من جذر المشروع (`__DIR__ . '/../.env'`)
2. تحليل كل سطر `KEY=VALUE` (تجاهل التعليقات والأسطر الفارغة)
3. تعيينها عبر `putenv()` و `$_ENV`
4. لا تُعيَّن إذا كانت موجودة مسبقاً في البيئة (الأولوية للنظام)

**ملاحظة أمنية**: ملف `.env` محمي من الوصول المباشر عبر `.htaccess` و `nginx.conf`. في بيئة التطوير (PHP built-in server)، الحماية تعتمد على `router.php`.
