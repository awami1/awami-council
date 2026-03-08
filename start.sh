#!/bin/bash
# Start the web server (development & production)

PORT=${PORT:-80}
HOST=${HOST:-0.0.0.0}

cd "$(dirname "$0")"
exec php -S "${HOST}:${PORT}" router.php
