#!/usr/bin/env bash
# Laravel Forge deploy script — sunucuda bu script proje kökünde çalıştırılır.
# Forge "Deploy Script" alanına şunu yazabilirsiniz: bash forge-deploy.sh
# veya bu dosyanın içeriğini doğrudan yapıştırın.

set -e

echo "Deploy başladı: $(date -Iseconds)"

# Forge ortam değişkeni (yoksa main kullan)
BRANCH="${FORGE_SITE_BRANCH:-main}"

# 1. Son kodu çek
git pull origin "$BRANCH"

# 2. PHP bağımlılıkları (production, dev paketleri yok)
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 3. Veritabanı migration (ilk deploy veya şema güncellemeleri)
php artisan migrate --force

# 4. Frontend (Vite) build — CSS/JS asset'leri
npm ci --no-audit --prefer-offline --no-progress
npm run build

# 5. Laravel cache'leri (production performansı)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Storage link (yoksa oluşturur, varsa atlar)
php artisan storage:link --quiet || true

# 7. Queue kullanıyorsanız worker'ı yeniden başlatın (Forge'da ayrı Daemon tanımlı olmalı)
# php artisan queue:restart

echo "Deploy tamamlandı: $(date -Iseconds)"
