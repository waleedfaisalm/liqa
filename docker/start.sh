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

# حذف بيانات التجربة مرة واحدة: اضبط LIQA_CLEAN_DEMO=1 (واختياريًا LIQA_CLEAN_ALSO ببريد أو أكثر مفصولة بفاصلة)
# ثم احذف المتغير بعد أول تشغيل. وأوقف LIQA_DEMO حتى لا تُنشأ البيانات مجددًا.
if [ "${LIQA_CLEAN_DEMO:-0}" = "1" ]; then
    if [ -n "${LIQA_CLEAN_ALSO:-}" ]; then
        php artisan liqa:clean-demo --force --also="$LIQA_CLEAN_ALSO" || true
    else
        php artisan liqa:clean-demo --force || true
    fi
fi

if [ "${LIQA_DEMO:-0}" = "1" ]; then
    php artisan liqa:demo-events || true
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec frankenphp run --config /etc/frankenphp/Caddyfile
