#!/usr/bin/env bash
# =============================================================================
# MOBILYATAKIP — Laravel Forge "Deploy Script" (TÜM SİTELER İÇİN AYNI)
# =============================================================================
# Her site (mobilya, meemare, muun, …) için Forge → Site → Deploy Script
# alanına AŞAĞIDAKİ BLOĞUN TAMAMINI yapıştırın. Site başına path değiştirmeyin;
# Forge $FORGE_SITE_PATH değişkenini otomatik verir.
#
# SİLİN (eski şablondan kalmasın):
#   git pull, $FORGE_COMPOSER, npm ci, npm install, npm run build,
#   artisan migrate/optimize (ayrı satırlar), FPM reload (ayrı satır)
#
# Başarılı deploy log’unda görmeniz gerekenler:
#   Deploy başladı:
#   public/build repoda güncel; sunucuda npm atlandı.
#   Deploy tamamlandı:
# =============================================================================

set -e

cd "$FORGE_SITE_PATH"

if [ ! -f forge-deploy.sh ]; then
  echo "HATA: forge-deploy.sh bulunamadı ($FORGE_SITE_PATH)."
  echo "Repo kökü public/ ile aynı dizinde olmalı."
  exit 1
fi

bash forge-deploy.sh
