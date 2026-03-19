#!/bin/bash
# Start the PHP built-in development server (للتطوير المحلي فقط)
# ⚠️  لا تستخدم هذا في الإنتاج — استخدم start-prod.sh (Nginx + PHP-FPM)

PORT=${PORT:-80}
HOST=${HOST:-0.0.0.0}

cd "$(dirname "$0")"
exec php -S "${HOST}:${PORT}" router.php
