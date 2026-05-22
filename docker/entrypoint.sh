#!/bin/sh
set -e

cd /var/www/html

# Clear any stale bootstrap cache from a previous build
php artisan optimize:clear --no-ansi 2>/dev/null || true

# Run pending migrations
php artisan migrate --force --no-ansi

# Warm up the config and route caches now that .env is present
php artisan config:cache --no-ansi
php artisan route:cache --no-ansi

exec "$@"
