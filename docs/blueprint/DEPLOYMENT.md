# DEPLOYMENT.md — دليل النشر
# مجلس عائلة العوامي (Awami Council)

---

## 1. متطلبات السيرفر

### 1.1 البرمجيات المطلوبة

| البرنامج | الإصدار | ملاحظات |
|----------|---------|---------|
| PHP | 8.1+ | مع إضافات: pdo, pdo_mysql, mbstring, json, session |
| MySQL | 8.0+ | charset: utf8mb4, collation: utf8mb4_unicode_ci |
| Nginx | 1.18+ | أو Apache 2.4+ مع mod_rewrite |
| SSL Certificate | — | HTTPS مطلوب في الإنتاج |

### 1.2 إضافات PHP المطلوبة

```
php-pdo
php-pdo-mysql
php-mbstring
php-json
php-session
```

### 1.3 لا يُطلب

- Node.js / npm
- Composer
- أي Build Tool

---

## 2. خطوات النشر

### الخطوة 1: نسخ ملفات المشروع

```bash
# استنساخ المستودع أو نقل الملفات
git clone <repository-url> /var/www/awami-council
cd /var/www/awami-council

# ضبط الصلاحيات
chown -R www-data:www-data /var/www/awami-council
chmod -R 755 /var/www/awami-council
```

### الخطوة 2: إعداد قاعدة البيانات MySQL

```sql
-- إنشاء قاعدة البيانات
CREATE DATABASE awami_council
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- إنشاء مستخدم
CREATE USER 'awami_user'@'localhost'
  IDENTIFIED BY 'كلمة_مرور_قوية_هنا';

-- منح الصلاحيات
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
  ON awami_council.*
  TO 'awami_user'@'localhost';

FLUSH PRIVILEGES;
```

### الخطوة 3: تكوين ملف البيئة

```bash
cp .env.example .env
```

حرر `.env`:

```env
DB_HOST=localhost
DB_NAME=awami_council
DB_USER=awami_user
DB_PASS=كلمة_مرور_قوية_هنا
DB_PORT=3306

ADMIN_USERNAME=admin
ADMIN_PASSWORD_HASH=$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

SETUP_DISABLED=false
```

إنشاء Hash لكلمة المرور:

```bash
php -r "echo password_hash('كلمة_المرور_الآمنة', PASSWORD_BCRYPT) . PHP_EOL;"
```

### الخطوة 4: تكوين Nginx

أنشئ ملف التكوين:

```nginx
server {
    listen 80;
    server_name your-domain.com;

    # إعادة توجيه إلى HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    root /var/www/awami-council;
    index index.php;

    # SSL
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # Cache للملفات الثابتة (30 يوم)
    location /public/ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    location /assets/ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # حماية الملفات الحساسة
    location ~ /\.env { deny all; return 404; }
    location ~ \.db$ { deny all; return 404; }
    location ~ /\.git { deny all; return 404; }
    location ~ /\.ht { deny all; return 404; }
    location = /api/config.php { deny all; return 404; }

    # معالجة PHP
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Front Controller — جميع الطلبات تمر عبر index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

```bash
# تفعيل الموقع
ln -s /etc/nginx/sites-available/awami-council /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### الخطوة 5: تكوين PHP-FPM

تأكد من إعدادات PHP-FPM المناسبة في `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
[www]
user = www-data
group = www-data
listen = /run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10

php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 12M
php_admin_value[session.gc_maxlifetime] = 86400
```

### الخطوة 6: إنشاء جداول قاعدة البيانات

1. افتح المتصفح وسجل الدخول في `/admin`
2. ثم اذهب إلى `/api/setup.php`
3. أو من سطر الأوامر:

```bash
# تشغيل الخادم مؤقتاً
php -S localhost:8080 router.php &

# تسجيل الدخول للحصول على Session
curl -c cookies.txt -X POST "http://localhost:8080/api/auth.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"كلمة_المرور"}'

# تشغيل Setup
curl -b cookies.txt "http://localhost:8080/api/setup.php"

# إيقاف الخادم المؤقت
kill %1
rm cookies.txt
```

### الخطوة 7: تعطيل Setup في الإنتاج

بعد إنشاء الجداول بنجاح:

```env
SETUP_DISABLED=true
```

### الخطوة 8: إعداد SSL (Let's Encrypt)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d your-domain.com
```

---

## 3. التحقق من النشر

### 3.1 اختبار الوصول

```bash
# الصفحة الرئيسية
curl -I https://your-domain.com/

# API
curl https://your-domain.com/api/auth.php?action=check

# لوحة التحكم
curl -I https://your-domain.com/admin
```

### 3.2 اختبار حماية الملفات

```bash
# يجب أن يُرجع 404 أو 403
curl -I https://your-domain.com/.env
curl -I https://your-domain.com/.git/config
curl -I https://your-domain.com/data/awami.db
curl -I https://your-domain.com/api/config.php
```

### 3.3 اختبار Security Headers

```bash
curl -I https://your-domain.com/ 2>&1 | grep -E "X-Content|X-Frame|Referrer|Strict"
```

التوقع:
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

---

## 4. التشغيل المحلي (Development)

```bash
# 1. نسخ .env
cp .env.example .env
# اترك DB_HOST فارغاً لاستخدام SQLite

# 2. تعيين كلمة مرور المشرف
# حرر .env واضبط ADMIN_PASSWORD_HASH

# 3. تشغيل الخادم
php -S 0.0.0.0:80 router.php
# أو
bash start.sh

# 4. افتح المتصفح
# http://localhost/

# 5. إنشاء الجداول
# سجل الدخول في /admin ثم اذهب إلى /api/setup.php
```

---

## 5. النشر على CranL

المشروع مُهيأ أصلاً للنشر على CranL:

1. ملف `.cranl` يحتوي تكوين المنفذ
2. ملف `Procfile` يُعرِّف عملية الويب: `web: php -S 0.0.0.0:80 router.php`
3. ضبط متغيرات البيئة في لوحة تحكم CranL

---

## 6. النسخ الاحتياطي

### قاعدة البيانات

```bash
# MySQL
mysqldump -u awami_user -p awami_council > backup_$(date +%Y%m%d).sql

# استعادة
mysql -u awami_user -p awami_council < backup_20240301.sql
```

### الملفات

```bash
# نسخ احتياطي كامل
tar -czf awami-backup-$(date +%Y%m%d).tar.gz \
  --exclude='.env' \
  --exclude='data/*.db' \
  /var/www/awami-council/
```

---

## 7. استكشاف الأخطاء

### مشاكل شائعة

| المشكلة | الحل |
|---------|------|
| خطأ اتصال DB | تحقق من .env (DB_HOST, DB_NAME, DB_USER, DB_PASS) |
| 500 Internal Server Error | تحقق من سجلات PHP: `tail -f /var/log/php8.1-fpm.log` |
| CSRF token invalid | تأكد أن الكوكيز تعمل (SameSite, Secure, Domain) |
| صفحة فارغة | تحقق من `display_errors` في php.ini (للتطوير فقط) |
| 404 لجميع الصفحات | تأكد من `try_files` في Nginx أو `.htaccess` لـ Apache |
| الخطوط لا تظهر | تأكد من وجود ملفات `public/fonts/*.ttf` |
| شجرة العائلة فارغة | تأكد من تشغيل Setup وإضافة بيانات في الشجرة |

### سجلات مفيدة

```bash
# Nginx
tail -f /var/log/nginx/error.log

# PHP-FPM
tail -f /var/log/php8.1-fpm.log

# التطبيق (إذا كان error_log مُفعل)
tail -f /var/log/syslog | grep php
```
