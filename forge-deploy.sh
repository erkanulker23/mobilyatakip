#!/usr/bin/env bash
# Laravel Forge deploy script — sunucuda bu script proje kökünde çalıştırılır.
# Forge "Deploy Script" alanına şunu yazabilirsiniz: bash forge-deploy.sh
#
# GÜVENLİK: Bu script veri silmez. migrate:fresh / db:wipe çalıştırmaz.
# Sadece yeni migration'ları uygular ve eksik admin kullanıcısını oluşturur.

set -e

echo "Deploy başladı: $(date -Iseconds)"

# Aynı anda iki deploy (Forge + manuel) npm'i bozar → ENOTEMPTY
DEPLOY_LOCK="${FORGE_SITE_PATH:-$(pwd)}/.forge-deploy.lock"
exec 200>"$DEPLOY_LOCK"
if ! flock -n 200; then
  echo "Başka bir deploy çalışıyor, sıra bekleniyor..."
  flock 200
fi

BRANCH="${FORGE_SITE_BRANCH:-main}"
FORGE_COMPOSER="${FORGE_COMPOSER:-composer}"
FORGE_PHP="${FORGE_PHP:-php}"

# 1. Son kodu çek
git pull origin "$BRANCH"

# 2. PHP bağımlılıkları (production)
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 3. Veritabanı — sadece bekleyen migration'lar (idempotent, veri silmez)
$FORGE_PHP artisan migrate --force

# 3b. Admin kullanıcı yoksa oluştur; mevcut şifreye dokunma
$FORGE_PHP artisan db:seed --class=Database\\Seeders\\SuperAdminSeeder --force

# 3c. İl/ilçe — tablo boşsa bir kez doldur (mevcut kayıtları ezmez)
$FORGE_PHP artisan turkey-locations:sync --if-empty || echo "Turkiye konum senkronu atlandı."

# 4. Frontend build (Vite production dependencies — Forge NODE_ENV=production güvenli)
# Forge panelinde ekstra "npm install && npm run build" SATIRI OLMASIN; sadece bu script.
export NPM_CONFIG_PRODUCTION=false
export CI=true

if [ -f package.json ]; then
  if [ -d node_modules ]; then
    STALE_NM="node_modules.stale.$$"
    rm -rf "$STALE_NM" 2>/dev/null || true
    if mv node_modules "$STALE_NM" 2>/dev/null; then
      rm -rf "$STALE_NM" &
    else
      rm -rf node_modules || true
    fi
  fi

  npm_install_frontend() {
    if [ -f package-lock.json ]; then
      npm ci --no-audit --no-fund
    else
      npm install --no-audit --no-fund
    fi
  }

  if ! npm_install_frontend; then
    echo "npm kurulumu başarısız; node_modules silinip yeniden denenecek..."
    rm -rf node_modules
    sleep 2
    npm_install_frontend
  fi

  if [ ! -x node_modules/.bin/vite ]; then
    echo "HATA: vite bulunamadı (node_modules/.bin/vite)."
    exit 1
  fi
  node_modules/.bin/vite build
else
  echo "package.json yok; npm atlandı."
fi

# 5. storage / bootstrap/cache — web ve deploy kullanıcısı yazabilsin (laravel.log Permission denied önlenir)
mkdir -p storage/logs storage/framework/{sessions,views,cache,data} storage/app/public bootstrap/cache
touch storage/logs/laravel.log
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || chmod -R 775 storage bootstrap/cache
if id forge >/dev/null 2>&1; then
  chown -R forge:forge storage bootstrap/cache 2>/dev/null || true
fi

# 6. Cache (config/route/view — veritabanına dokunmaz)
$FORGE_PHP artisan optimize:clear
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:clear
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan route:list --name=sales.delivered --quiet || { echo "HATA: sales.delivered route kaydı yok."; exit 1; }
$FORGE_PHP artisan view:cache

# OPcache eski route/config dosyalarını tutmasın (eşzamanlı reload kilidi)
if [ -n "${FORGE_PHP_FPM:-}" ]; then
  touch /tmp/fpmlock 2>/dev/null || true
  (
    flock -w 10 9 || exit 1
    echo 'Reloading PHP FPM...'
    sudo service "$FORGE_PHP_FPM" reload
  ) 9</tmp/fpmlock || true
fi

# 7. Storage link (dosya silmez, sembolik link oluşturur)
if [ ! -L public/storage ]; then
  $FORGE_PHP artisan storage:link
else
  echo "Storage link zaten mevcut."
fi

# 8. Queue worker yenile
$FORGE_PHP artisan queue:restart || true

echo "Deploy tamamlandı: $(date -Iseconds)"
echo ""
echo "Not: Profil fotoğrafı 413 hatası alırsanız Forge Nginx yapılandırmasına şunu ekleyin:"
echo "  client_max_body_size 20M;"
echo "  (deploy/nginx-upload-limits.conf dosyasına bakın)"
