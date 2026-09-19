# Laravel Forge → Site → Deploy Script
# Eski npm / composer / migrate satırlarını SİLİN; aşağıdakini yapıştırın.
# git pull, composer, migrate, npm, vite build, cache ve FPM reload forge-deploy.sh içindedir.

cd $FORGE_SITE_PATH
bash forge-deploy.sh
