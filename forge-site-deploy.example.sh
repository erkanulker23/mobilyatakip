# Forge Deploy Script — tüm siteler için aynı (forge-site-deploy.example.sh)
# Detaylı checklist: deploy/FORGE-SITELER.md

set -e

cd "$FORGE_SITE_PATH"

if [ ! -f forge-deploy.sh ]; then
  echo "HATA: forge-deploy.sh bulunamadı ($FORGE_SITE_PATH)."
  exit 1
fi

bash forge-deploy.sh
