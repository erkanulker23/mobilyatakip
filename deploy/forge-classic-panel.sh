# =============================================================================
# Forge Deploy Script — ESKİ ŞABLONUNUZ + Vite düzeltmeleri (TÜM SİTELER)
# =============================================================================
# Tanıdık akış: git → composer → migrate → optimize → FPM reload
# npm YOK: CSS repoda (public/build). Sunucuda vite/ENOTEMPTY riski yok.
# package-lock sunucuda bozulmasın diye git reset --hard (git pull yerine).
#
# Forge → Deploy Script alanına aşağıdaki bash bloğunu yapıştırın.
# cd satırında $FORGE_SITE_PATH kullanın (site başına path yazmayın).
# =============================================================================

cd $FORGE_SITE_PATH

git fetch origin $FORGE_SITE_BRANCH
git reset --hard origin/$FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan db:seed --class=Database\\Seeders\\SuperAdminSeeder --force
    $FORGE_PHP artisan turkey-locations:sync --if-empty || true

    if [ ! -f public/build/manifest.json ]; then
        echo "HATA: public/build yok. Repodan son kodu çekin veya yerelde npm run build commit edin."
        exit 1
    fi

    if [ ! -L public/storage ]; then
        $FORGE_PHP artisan storage:link
    fi

    $FORGE_PHP artisan optimize:clear
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan view:cache
    $FORGE_PHP artisan queue:restart || true
fi

touch /tmp/fpmlock 2>/dev/null || true
( flock -w 10 9 || exit 1
    echo 'Reloading PHP FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9</tmp/fpmlock
