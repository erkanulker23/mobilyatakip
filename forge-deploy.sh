#!/usr/bin/env bash
# Laravel Forge deploy script — sunucuda bu script proje kökünde çalıştırılır.
# Forge "Deploy Script" alanına şunu yazabilirsiniz: bash forge-deploy.sh
#
# GÜVENLİK: Bu script veri silmez. migrate:fresh / db:wipe çalıştırmaz.
# Sadece yeni migration'ları uygular ve eksik admin kullanıcısını oluşturur.

set -e

echo "Deploy başladı: $(date -Iseconds)"

BRANCH="${FORGE_SITE_BRANCH:-main}"

# 1. Son kodu çek
git pull origin "$BRANCH"

# 2. PHP bağımlılıkları (production)
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 3. Veritabanı — sadece bekleyen migration'lar (idempotent, veri silmez)
php artisan migrate --force

# 3b. Admin kullanıcı yoksa oluştur; mevcut şifreye dokunma
php artisan db:seed --class=Database\\Seeders\\SuperAdminSeeder --force

# 3c. İl/ilçe — tablo boşsa bir kez doldur (mevcut kayıtları ezmez)
php artisan turkey-locations:sync --if-empty || echo "Turkiye konum senkronu atlandı."

# 4. Frontend build
if [ -f package-lock.json ]; then
  npm ci --no-audit --prefer-offline --no-progress
else
  npm install --no-audit --no-progress
fi
npm run build

# 5. Oturum dizini (file driver)
mkdir -p storage/framework/sessions
chmod -R ug+rwx storage bootstrap/cache || true

# 6. Cache (config/route/view — veritabanına dokunmaz)
php artisan optimize:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan route:list --name=sales.delivered --quiet || { echo "HATA: sales.delivered route kaydı yok."; exit 1; }
php artisan view:cache

# OPcache eski route/config dosyalarını tutmasın
if [ -n "${FORGE_PHP_FPM:-}" ]; then
  sudo service "$FORGE_PHP_FPM" reload || true
fi

# 7. Storage link (dosya silmez, sembolik link oluşturur)
if [ ! -L public/storage ]; then
  php artisan storage:link
else
  echo "Storage link zaten mevcut."
fi

# 8. Queue worker yenile
php artisan queue:restart || true

echo "Deploy tamamlandı: $(date -Iseconds)"
echo ""
echo "Not: Profil fotoğrafı 413 hatası alırsanız Forge Nginx yapılandırmasına şunu ekleyin:"
echo "  client_max_body_size 20M;"
echo "  (deploy/nginx-upload-limits.conf dosyasına bakın)"
