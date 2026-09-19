# Laravel Forge → Site → Deploy Script
# Eski satırları TAMAMEN silin: git pull, composer, FPM reload, npm ci, npm run build, artisan migrate...
# Log'da "Deploy başladı:" görünmüyorsa hâlâ eski script çalışıyordur → ENOTEMPTY / vite 127 devam eder.

cd $FORGE_SITE_PATH
bash forge-deploy.sh
