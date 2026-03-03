#!/bin/bash
# تشغيل سيرفر PHP المدمج
# Start PHP built-in server

PORT=${PORT:-80}
HOST=${HOST:-0.0.0.0}

echo "[startup] PHP version: $(php -v | head -1)"
echo "[startup] Listening on ${HOST}:${PORT}"
echo "[startup] Working directory: $(pwd)"
echo "[startup] DB_HOST=${DB_HOST:-not set}"
echo "[startup] DATABASE_URL=${DATABASE_URL:+set (hidden)}"

cd "$(dirname "$0")"
exec php -S "${HOST}:${PORT}" router.php
