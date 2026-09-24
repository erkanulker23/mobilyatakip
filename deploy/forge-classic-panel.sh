# =============================================================================
# Forge Deploy Script — TÜM SİTELER (npm YOK — vite 127 / ENOTEMPTY önlenir)
# =============================================================================
# Log'da "Reloading PHP FPM" npm'den ÖNCE görünüyorsa → hâlâ ESKİ script.
# ESKİ script'ten SİLİN: npm ci, npm install, npm run build
# =============================================================================

set -e

cd "$FORGE_SITE_PATH"

echo "Deploy başladı (klasik, npm yok): $(date -Iseconds)"

git fetch origin "$FORGE_SITE_BRANCH"
git reset --hard "origin/$FORGE_SITE_BRANCH"

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan db:seed --class=Database\\Seeders\\SuperAdminSeeder --force
    $FORGE_PHP artisan turkey-locations:sync --if-empty || true

    if [ ! -f public/build/manifest.json ]; then
        echo "HATA: public/build/manifest.json yok."
        exit 1
    fi

    if [ ! -L public/storage ]; then
        $FORGE_PHP artisan storage:link
    fi

    sudo chown -R forge:forge storage bootstrap/cache 2>/dev/null || true
    sudo chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

    $FORGE_PHP artisan optimize:clear
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:clear
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan route:list --name=catalog.index --quiet || { echo "HATA: catalog.index yok."; exit 1; }
    $FORGE_PHP artisan view:cache
    $FORGE_PHP artisan queue:restart || true
fi

touch /tmp/fpmlock 2>/dev/null || true
( flock -w 10 9 || exit 1
    echo 'Reloading PHP FPM...'
    sudo -S service "$FORGE_PHP_FPM" reload ) 9</tmp/fpmlock

echo "Deploy tamamlandı: $(date -Iseconds)"
