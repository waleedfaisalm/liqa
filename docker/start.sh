#!/bin/sh
# يعمل عند كل تشغيل للحاوية: تجهيز القاعدة والبيانات ثم تشغيل الخادم
set -e
cd /app
php artisan optimize:clear >/dev/null 2>&1 || true

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p database
    touch "${DB_DATABASE:-/app/database/database.sqlite}"
fi

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan migrate --force
php artisan liqa:bootstrap

if [ "${LIQA_DEMO:-0}" = "1" ]; then
    php artisan liqa:demo-events || true
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec frankenphp run --config /etc/frankenphp/Caddyfile
