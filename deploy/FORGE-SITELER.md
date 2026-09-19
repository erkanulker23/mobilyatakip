# Forge — 10+ site deploy checklist

Tüm siteler **aynı GitHub repo** (`mobilyatakip`) ve **aynı deploy script** kullanır. Site farkı yalnızca `.env` (APP_URL, veritabanı) ve Forge domain ayarıdır.

## 1. Deploy Script (her site — iki seçenek)

### A) Klasik (eski Forge alışkanlığınız — önerilen)

Tanınık sıra: composer → migrate → cache → **en sonda** FPM. `deploy/forge-classic-panel.sh` içeriğini Forge’a yapıştırın.

### B) Tek satır wrapper

Forge → **Site** → **Deploy Script** → içeriği **tamamen sil**, şunu yapıştır:

```bash
set -e

cd "$FORGE_SITE_PATH"

if [ ! -f forge-deploy.sh ]; then
  echo "HATA: forge-deploy.sh bulunamadı ($FORGE_SITE_PATH)."
  exit 1
fi

bash forge-deploy.sh
```

Kaynak dosya: [`deploy/forge-panel-deploy.sh`](forge-panel-deploy.sh)

## 2. Site ayarları (site başına kontrol)

| Ayar | Değer |
|------|--------|
| Web Directory | `public` |
| Branch | `main` (veya kullandığınız branch) |
| PHP | 8.2+ |
| `.env` | Canlı DB, `APP_URL`, `APP_DEBUG=false` |

## 3. İlk geçiş / bozuk node_modules

Bir kez SSH (site dizinini kendi path’inizle değiştirin):

```bash
cd /home/forge/ORNEK.awapanel.com
rm -rf node_modules node_modules.stale.*
git pull origin main
bash forge-deploy.sh
```

## 4. CSS değişince (geliştirici makinesi)

```bash
npm run build
git add public/build .frontend-build-hash
git commit -m "Build frontend assets"
git push
```

Sonra tüm sitelerde normal **Deploy** yeterli (sunucuda npm çalışmaz).

## 5. Zorunlu sunucuda npm (nadir)

```bash
cd $FORGE_SITE_PATH
FORCE_NPM_BUILD=1 bash forge-deploy.sh
```

## 6. `package-lock.json would be overwritten by merge`

Sunucuda eski npm denemeleri lock dosyasını değiştirmiş olabilir. Güncel `forge-deploy.sh` `git reset --hard origin/main` kullanır; bir kez deploy yeterli. Manuel: `git fetch origin main && git reset --hard origin/main`

## 7. Deploy log doğrulama

- **Yanlış script:** `git pull` + hemen `Reloading PHP FPM` + `npm error` — panel script’i hâl eski.
- **Doğru script:** `Deploy başladı:` ile başlar, npm atlanır veya kontrollü çalışır, `Deploy tamamlandı:` ile biter.
