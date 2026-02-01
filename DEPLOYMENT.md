# Mobilya Takip — Canlıya Taşıma ve Deployment

Bu doküman, projeyi sunucuya (production) taşırken uygulamanız gereken adımları ve veri tutarlılığı / güvenlik özetini içerir.

---

## 1. Veri Tutarlılığı Mührü (Data Integrity)

- **Stok doğruluğu:** Teklif → Satışa dönüşümde `stockService.movement(..., 'cikis')` tetiklenir; `warehouse_stocks` (stok tabloları) anlık güncellenir. Envanter kaçakları önlenir.
- **Finansal iz:** Nakit tahsilatta seçilen kasa ile `kasa_hareket` tablosuna **giris** kaydı düşer; ileride "Kasa Defteri" raporları bu veriden üretilebilir.
- **RBAC:** Kasa, Masraf, Muhasebe API’leri `@Roles(ADMIN, MUHASEBE)` ile korunur; sadece Satış rolü 403 alır.
- **Null-safe UI:** Optional chaining ve boş veri durumları sayfa crash’lerini engeller.
- **PDF:** Backend PDF hata verirse jspdf fallback ile tarayıcıda PDF üretilir; firma adı/adres `company` API’den alınır.

---

## 2. Environment Check (Canlı Sunucu .env)

Canlıda mutlaka aşağıdaki değişkenleri **sunucuya göre** ayarlayın. `.env` dosyası **asla** repoya commit edilmemeli.

| Değişken | Açıklama | Canlı örnek |
|----------|----------|-------------|
| `NODE_ENV` | Ortam | `production` |
| `PORT` | Backend dinleyeceği port | `3001` veya reverse proxy’nin yönlendiği port |
| `DB_HOST` | MySQL sunucu | Canlı DB IP/host |
| `DB_PORT` | MySQL port | `3306` |
| `DB_USERNAME` | Veritabanı kullanıcı | Canlı DB kullanıcı |
| `DB_PASSWORD` | Veritabanı şifre | **Güçlü, benzersiz şifre** |
| `DB_DATABASE` | Veritabanı adı | `mobilyatakip` |
| `JWT_SECRET` | JWT imza anahtarı | **En az 32 karakter, rastgele** (değiştirilmezse token’lar tahmin edilebilir) |
| `JWT_EXPIRES_IN` | Token süresi | `7d` veya `1d` |
| `APP_URL` | Backend tam URL | `https://api.siteniz.com` veya `https://siteniz.com` |
| `FRONTEND_URL` | Frontend tam URL | `https://app.siteniz.com` (CORS için) |

**Not:** Proje `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` kullanıyor; tek satırlık `DATABASE_URL` kullanılmıyor. İsterseniz ileride bir wrapper ile `DATABASE_URL`’den bu değişkenlere parse edebilirsiniz.

---

## 3. Build Optimization (Frontend + Backend)

### Backend
```bash
npm install --production=false   # devDependencies gerekebilir (build için)
npm run build
# Çıktı: dist/ (main.js, app.module.js, vb.)
```

### Frontend
```bash
cd frontend
npm ci
npm run build
# Çıktı: frontend/dist/ (index.html, assets/*.js, *.css)
```

### Tek komutla hepsi (proje kökünde)
```bash
npm run build:all
```
Önce frontend build, sonra backend build alır.

**Kontrol:** `frontend/dist/index.html` ve `dist/main.js` dosyalarının oluştuğunu, build log’unda hata olmadığını doğrulayın.

---

## 4. Database Migration (Canlıda Veri Kaybını Önleme)

Geliştirmede `synchronize: true` (sadece `NODE_ENV === 'development'`) ile şema otomatik güncellenir. **Canlıda `NODE_ENV=production` olduğu için `synchronize` zaten `false`** (`src/config/database.config.ts`). Canlıda şema değişikliği yapmak için TypeORM migration kullanın.

### İlk kez migration kullanacaksanız
1. Mevcut şemayı migration’a dönüştürün (boş veritabanında bir kez `synchronize: true` ile şema oluşturup `migration:generate` ile üretmek yerine, entity’lerden ilk migration’ı elle veya CLI ile üretebilirsiniz).
2. Projede `src/migrations/` klasörü yoksa oluşturun.
3. Örnek komut (geliştirme ortamında):
   ```bash
   npm run migration:generate -- src/migrations/InitialSchema
   ```
4. Canlıda sadece migration çalıştırın:
   ```bash
   NODE_ENV=production npm run migration:run
   ```

### Canlıda kesin kurallar
- **Asla** canlı veritabanında `synchronize: true` kullanmayın.
- Şema değişikliği = yeni migration ekleyip `migration:run` ile uygulayın.
- Migration’dan önce **veritabanı yedeği** alın.

---

## 5. Process Management (PM2 ile 7/24)

Backend’in sürekli ayakta kalması ve restart sonrası otomatik başlaması için PM2 kullanın.

### PM2 kurulumu
```bash
npm install -g pm2
```

### Proje kökünde ecosystem dosyası
`ecosystem.config.cjs` (veya `ecosystem.config.js`) kullanın; örnek içerik aşağıda. Çalıştırma:
```bash
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup   # Sunucu açılışında PM2’yi başlatır (komutta çıkan komutu root ile çalıştırın)
```

**Kontrol:** `pm2 list` ile process’in “online” olduğunu, `pm2 logs` ile log çıktısını doğrulayın.

---

## 6. Canlı Checklist (Kısa Özet)

- [ ] `.env` canlı değerlerle dolduruldu (`DB_*`, `JWT_SECRET`, `PORT`, `APP_URL`, `FRONTEND_URL`).
- [ ] `NODE_ENV=production` ayarlandı.
- [ ] `npm run build:all` hatasız tamamlandı; `dist/` ve `frontend/dist/` oluştu.
- [ ] Canlı veritabanında `synchronize` kapalı; şema migration ile yönetiliyor.
- [ ] Backend PM2 (veya benzeri) ile sürekli çalışıyor; restart sonrası otomatik başlıyor.
- [ ] Reverse proxy (Nginx/Apache) varsa: API için `PORT` (örn. 3001), frontend için `frontend/dist` statik dosya veya ayrı frontend host’u yapılandırıldı.
- [ ] HTTPS ve güçlü `JWT_SECRET` kullanılıyor.

Bu adımlar tamamlandığında proje canlıya hazır kabul edilir.
