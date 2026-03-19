#!/bin/bash
# start-prod.sh — تشغيل الموقع في الإنتاج باستخدام Nginx + PHP-FPM
# يُستخدم من CranL/Nixpacks عبر nixpacks.toml

set -e

PORT=${PORT:-80}
APP_DIR="$(cd "$(dirname "$0")" && pwd)"

# ─── إعداد PHP-FPM ───
PHP_FPM_BIN=$(command -v php-fpm8.4 || command -v php-fpm8.3 || command -v php-fpm8.2 || command -v php-fpm8.1 || command -v php-fpm || echo "")

if [ -z "$PHP_FPM_BIN" ]; then
    echo "⚠️  PHP-FPM غير موجود — يتم استخدام سيرفر PHP المدمج كبديل"
    exec php -S "0.0.0.0:${PORT}" -t "$APP_DIR" "$APP_DIR/router.php"
fi

# إنشاء المجلدات المطلوبة
mkdir -p /tmp/php-fpm /tmp/nginx /var/log/nginx "$APP_DIR/data"

# ─── Graceful shutdown: إيقاف PHP-FPM عند إيقاف الحاوية ───
trap 'kill $(cat /tmp/php-fpm/php-fpm.pid) 2>/dev/null; exit 0' SIGTERM SIGINT

# ─── إعداد PHP-FPM pool ───
cat > /tmp/php-fpm.conf <<FPMEOF
[global]
pid = /tmp/php-fpm/php-fpm.pid
error_log = /dev/stderr
daemonize = yes

[www]
user = $(whoami)
group = $(id -gn)
listen = 127.0.0.1:9000
listen.allowed_clients = 127.0.0.1

pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests = 500

; Sessions تُحفظ في /tmp — مقبول لـ single-container deployment
; لا تُشارَك بين حاويات متعددة
php_admin_value[session.save_handler] = files
php_admin_value[session.save_path] = /tmp

catch_workers_output = yes
FPMEOF

# ─── إعداد Nginx ───
cat > /tmp/nginx.conf <<NGINXEOF
worker_processes auto;
pid /tmp/nginx/nginx.pid;
error_log /dev/stderr warn;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    access_log /dev/stdout;

    sendfile on;
    tcp_nopush on;
    keepalive_timeout 65;
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    server {
        listen ${PORT};
        server_name _;
        root ${APP_DIR};
        index index.php;
        charset utf-8;

        # ─── Security Headers ───
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-Frame-Options "DENY" always;
        add_header Referrer-Policy "strict-origin-when-cross-origin" always;

        # ─── ملفات ثابتة ───
        location /public/ {
            expires 30d;
            add_header Cache-Control "public, must-revalidate";
            try_files \$uri =404;
        }
        location /assets/ {
            expires 30d;
            add_header Cache-Control "public, must-revalidate";
            try_files \$uri =404;
        }

        # ─── Admin SPA ───
        location /admin/ {
            try_files \$uri \$uri/ /admin/index.php?\$query_string;
            location ~ \.php\$ {
                fastcgi_pass 127.0.0.1:9000;
                fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                include fastcgi_params;
            }
        }

        # ─── API ───
        location /api/ {
            try_files \$uri \$uri/ =404;
            location ~ \.php\$ {
                fastcgi_pass 127.0.0.1:9000;
                fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                include fastcgi_params;
            }
        }

        # ─── Sitemap ───
        location = /sitemap.xml {
            rewrite ^ /sitemap.php last;
        }

        # ─── كل الصفحات → router ───
        location / {
            try_files \$uri \$uri/ /index.php?\$query_string;
        }

        # ─── PHP-FPM ───
        location ~ \.php\$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_intercept_errors off;
            fastcgi_buffer_size 16k;
            fastcgi_buffers 4 16k;
        }

        # ─── حماية الملفات الحساسة ───
        location ~ /\.ht { deny all; }
        location ~ /\.git { deny all; }
        location ~ /\.env { deny all; }
        location ~ \.db\$ { deny all; }
        location /data/ { deny all; }
        location = /api/config.php { deny all; }
    }
}
NGINXEOF

# ─── تشغيل ───
echo "🚀 تشغيل PHP-FPM (max_children=20)..."
$PHP_FPM_BIN --fpm-config /tmp/php-fpm.conf

# انتظار جاهزية PHP-FPM قبل تشغيل Nginx
echo "⏳ انتظار PHP-FPM..."
for i in $(seq 1 30); do
    if [ -f /tmp/php-fpm/php-fpm.pid ] && kill -0 "$(cat /tmp/php-fpm/php-fpm.pid)" 2>/dev/null; then
        break
    fi
    sleep 0.1
done

echo "🚀 تشغيل Nginx على البورت ${PORT}..."
exec nginx -c /tmp/nginx.conf -g 'daemon off;'
