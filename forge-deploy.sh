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

# 4. Frontend — repoda public/build varsa sunucuda npm çalıştırmayız (Forge npm hatalarını önler)
# CSS değiştirdiyseniz: yerelde npm run build + public/build ve .frontend-build-hash commit edin.
# Sunucuda zorunlu npm: FORCE_NPM_BUILD=1 bash forge-deploy.sh
sha256_files() {
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$@" | sha256sum | awk '{print $1}'
  elif command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$@" | shasum -a 256 | awk '{print $1}'
  else
    echo ""
  fi
}

frontend_deps_hash() {
  if [ -f package-lock.json ]; then
    sha256_files package.json package-lock.json
  elif [ -f package.json ]; then
    if command -v sha256sum >/dev/null 2>&1; then
      sha256sum package.json | awk '{print $1}'
    elif command -v shasum >/dev/null 2>&1; then
      shasum -a 256 package.json | awk '{print $1}'
    else
      echo ""
    fi
  else
    echo ""
  fi
}

need_npm_build=0
if [ ! -f public/build/manifest.json ]; then
  need_npm_build=1
elif [ "${FORCE_NPM_BUILD:-0}" = "1" ]; then
  need_npm_build=1
else
  CURRENT_HASH="$(frontend_deps_hash)"
  SAVED_HASH=""
  [ -f .frontend-build-hash ] && SAVED_HASH="$(tr -d '[:space:]' < .frontend-build-hash)"
  if [ -n "$CURRENT_HASH" ] && [ "$CURRENT_HASH" != "$SAVED_HASH" ]; then
    need_npm_build=1
  fi
fi

if [ "$need_npm_build" -eq 0 ]; then
  echo "public/build repoda güncel; sunucuda npm atlandı."
elif [ -f package.json ]; then
  export NPM_CONFIG_PRODUCTION=false
  export CI=true
  rm -rf node_modules node_modules.stale.* 2>/dev/null || true

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
  frontend_deps_hash > .frontend-build-hash
else
  echo "package.json yok; npm atlandı."
fi

if [ ! -f public/build/manifest.json ]; then
  echo "HATA: public/build/manifest.json yok. Yerelde npm run build yapıp public/build commit edin."
  exit 1
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
