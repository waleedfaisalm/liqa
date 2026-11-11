# ============================================================
# لقاء — حاوية واحدة تصلح لـ Vercel (Container Runtime) و Render
# FrankenPHP (PHP + Caddy)، والبناء متعدد المراحل
# ============================================================

# ---------- 1) بناء ملفات الواجهة (Vite) ----------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- 2) مكتبات PHP ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ---------- 3) الصورة النهائية ----------
FROM dunglas/frankenphp:1-php8.4-bookworm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends libcap2-bin \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_pgsql pdo_mysql pdo_sqlite intl zip opcache gd \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "opcache.enable=1\nopcache.validate_timestamps=0\nopcache.memory_consumption=192\nopcache.max_accelerated_files=20000\nrealpath_cache_size=4096K\nrealpath_cache_ttl=600\n" > "$PHP_INI_DIR/conf.d/zz-liqa.ini"

WORKDIR /app
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
COPY Caddyfile /etc/frankenphp/Caddyfile
COPY docker/start.sh /usr/local/bin/liqa-start

RUN chmod +x /usr/local/bin/liqa-start \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

ENV PORT=80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    XDG_CONFIG_HOME=/tmp \
    XDG_DATA_HOME=/tmp

USER www-data
CMD ["liqa-start"]
