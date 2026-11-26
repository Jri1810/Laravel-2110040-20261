#!/bin/sh
set -e

PORT="${PORT:-10000}"

echo "[start] Menyiapkan aplikasi Laravel pada port ${PORT}"

# ---------------------------------------------------------------------------
# 1. Nginx mendengarkan port dinamis dari Render
# ---------------------------------------------------------------------------
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/http.d/default.conf

if [ -z "${APP_KEY}" ]; then
  echo "[start] PERINGATAN: APP_KEY kosong. Set variabel APP_KEY di Render."
fi

# ---------------------------------------------------------------------------
# 2. Diagnostik: pastikan ekstensi untuk Modul 12 (GD + WebP, EXIF) aktif
# ---------------------------------------------------------------------------
php -r 'echo "[start] GD=", (int) extension_loaded("gd"),
    " WebP=", (int) function_exists("imagewebp"),
    " EXIF=", (int) extension_loaded("exif"),
    " PDO_pgsql=", (int) extension_loaded("pdo_pgsql"), PHP_EOL;'

# ---------------------------------------------------------------------------
# 3. Direktori wajib (filesystem Render bersifat ephemeral, dibuat ulang tiap boot)
# ---------------------------------------------------------------------------
mkdir -p \
  storage/app/public/profiles \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ---------------------------------------------------------------------------
# 4. Package discovery dijalankan saat boot karena image dibangun
#    dengan `composer install --no-scripts` (APP_KEY belum tersedia).
# ---------------------------------------------------------------------------
php artisan package:discover --ansi || true

# ---------------------------------------------------------------------------
# 5. Cache konfigurasi & view memakai env dari Render
#    (route:cache sengaja dilewati: routes/web.php memakai closure,
#     yang tidak bisa diserialisasi Laravel)
# ---------------------------------------------------------------------------
echo "[start] Membuat cache konfigurasi..."
php artisan config:cache

echo "[start] Membuat cache view..."
php artisan view:cache || true

# ---------------------------------------------------------------------------
# 6. Migrasi database (aman dijalankan berulang)
# ---------------------------------------------------------------------------
echo "[start] Menjalankan migrasi database..."
php artisan migrate --force --no-interaction

# ---------------------------------------------------------------------------
# 7. Storage link (untuk kompatibilitas file lama di disk public)
# ---------------------------------------------------------------------------
php artisan storage:link || true

echo "[start] Aplikasi siap. Menjalankan supervisord (nginx + php-fpm)."
exec /usr/bin/supervisord -c /etc/supervisord.conf
