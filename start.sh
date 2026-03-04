#!/bin/bash
# تشغيل سيرفر التطوير المحلي
# Start development server

PORT=${PORT:-80}
HOST=${HOST:-0.0.0.0}

echo "🚀 تشغيل سيرفر مجلس عائلة العوامي..."
echo "📌 العنوان: http://localhost:${PORT}"
echo "📌 اضغط Ctrl+C للإيقاف"
echo ""

cd "$(dirname "$0")"
php -S "${HOST}:${PORT}" router.php
