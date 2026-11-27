# syntax=docker/dockerfile:1

# Laravel 13 butuh PHP >= 8.3.
FROM php:8.4-fpm-alpine

# Ekstensi yang dibutuhkan aplikasi (fileinfo, mbstring, dan opcache sudah
# tersedia bawaan di image php:fpm-alpine sehingga tidak perlu dipasang lagi):
#  - pdo_mysql : database MySQL (Railway / Laragon)
#  - pdo_pgsql : database PostgreSQL (Render / Neon)
#  - gd        : crop/resize + imagewebp() untuk foto profil (Modul 12)
#  - exif      : auto-rotate foto kamera HP
#  - intl, zip : kebutuhan Laravel
COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
        pdo_mysql \
        pdo_pgsql \
        gd \
        exif \
        intl \
        zip \
        @composer \
    && apk add --no-cache nginx supervisor

# Konfigurasi PHP, PHP-FPM, nginx, dan supervisor
COPY docker/php.ini /usr/local/etc/php/conf.d/zzz-app.ini
# Nama berawalan "zzz" agar dimuat SETELAH zz-docker.conf bawaan image
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-app.conf
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh

WORKDIR /var/www/html

# Kode aplikasi
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1

# Script composer dimatikan saat build karena package:discover butuh APP_KEY
# dari environment Render. Dijalankan di start.sh saat container boot.
RUN composer install --no-dev --no-interaction --prefer-dist \
        --optimize-autoloader --no-scripts \
    && mkdir -p \
        storage/app/public/profiles \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod +x /usr/local/bin/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]
