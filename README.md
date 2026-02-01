# Mobilya Takip

Stok, Satış, Teklif, Tedarikçi Cari, Müşteri Cari, SSH (Satış Sonrası Hizmet), Bildirim ve Log sistemini kapsayan ticari yazılım.

## Projeyi çalıştırma

1. **Backend** (port 3001): Proje kökünde `npm run start:dev`
2. **Frontend** (port 5174): Ayrı terminalde `npm run dev:frontend`
3. Tarayıcıda **http://localhost:5174** açın. Giriş: `erkanulker0@gmail.com` / `password`

Port 3001 veya 5174 kullanımdaysa: `lsof -ti:3001 | xargs kill -9` (veya 5174) ile serbest bırakın.

**Not:** Mobilya Takip frontend 5174 kullanır; 5173 depotakip-v1.test için ayrılmıştır. İki proje birlikte çalışabilir.

### https://mobilyatakip-v1.test/ (Laravel Valet)

**Tek seferde kurulum (sudo şifresi istenir):**
```bash
./scripts/setup-mobilyatakip-v1-test.sh
```
Bu script: `valet trust` (sertifika güvenilir), `valet proxy` (site → 3001), backend ve URL testi yapar.

**Elle adımlar:**
1. **Frontend build** (bir kez): `npm run build:frontend`
2. **Backend:** `npm run start:dev` (port 3001)
3. **Valet:** `valet trust` sonra `valet proxy mobilyatakip-v1.test http://127.0.0.1:3001`
4. Tarayıcıda **https://mobilyatakip-v1.test/** açın. Hâlâ sertifika uyarısı varsa: **Gelişmiş** → **mobilyatakip-v1.test adresine git**
5. Giriş: `erkanulker0@gmail.com` / `password`

## Teknoloji

- **Backend:** NestJS (Node.js), TypeORM
- **Veritabanı:** MySQL — veritabanı adı: **mobilyatakip** (TabloPlus'ta hazır)
- **Admin panel:** API üzerinden tüm işlemler (frontend ayrı proje veya aynı repo’da eklenebilir)

## Veritabanı (MySQL)

TabloPlus’de **mobilyatakip** adında bir veritabanı oluşturun. Geliştirme ortamında `synchronize: true` ile tablolar otomatik oluşturulur.

`.env` dosyasında MySQL bağlantı bilgilerini ayarlayın: `DB_HOST`, `DB_PORT=3306`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE=mobilyatakip`. Geliştirme ortamında `synchronize: true` ile tablolar otomatik oluşturulur.

## Kurulum

```bash
# Backend
npm install
cp .env.example .env
# .env içinde DB_*, JWT_SECRET, PORT=3001, FRONTEND_URL düzenleyin
npm run start:dev

# Frontend (ayrı terminal)
npm run dev:frontend
```

- **Backend:** Port 3001 (depotakip 3000 kullandığı için). API: `http://mobilyatakip-v1.test` veya `http://localhost:3001`. Swagger: `http://localhost:3001/api/docs`.
- **Frontend:** Port 5174. Geliştirmede Vite proxy ile `/auth`, `/api`, `/products` vb. backend’e yönlenir. Tarayıcı: `http://localhost:5174`.

## Sunucu / Hosting Kurulumu (Kurulum Sihirbazı)

Projeyi herhangi bir sunucuya veya hostinge yükleyip **kurulum sihirbazı** ile tek sayfadan kurulum yapabilirsiniz. Sihirbaz veritabanı bağlantısını test eder, ayarları kaydeder, şemayı oluşturur ve frontend’i derler.

1. **Projeyi sunucuya yükleyin** (FTP, Git, vb.).
2. **Bağımlılıkları ve backend’i derleyin:**
   ```bash
   npm install
   npm run build
   ```
3. **Uygulamayı başlatın.** `.env` yoksa otomatik olarak `.env.example` kopyalanır ve kurulum modu açılır:
   ```bash
   node dist/main.js
   ```
   (Windows’ta önce `set INSTALL_MODE=1` yapıp aynı komutu çalıştırabilirsiniz.)
4. **Tarayıcıda** `http://sunucu-adresi:3001` açın. Kurulum sihirbazı açılır.
5. **Adımlar:**
   - **1. Gereksinimler:** Node sürümü ve API kontrolü.
   - **2. Veritabanı:** MySQL sunucu, port, kullanıcı, şifre, veritabanı adı. “Bağlantıyı test et” ile bağlantıyı doğrulayın; veritabanı yoksa “Veritabanı yoksa oluştur” ile oluşturulabilir.
   - **3. Uygulama:** JWT gizli anahtar (en az 16 karakter), port, uygulama URL ve frontend URL.
   - **4. Mail:** Opsiyonel; SMTP bilgileri.
   - **5. Kurulumu çalıştır:** “Kurulumu başlat” ile config yazılır, şema oluşturulur ve frontend derlenir.
6. **Kurulum bitince** sihirbaz `.env` dosyasına `INSTALL_MODE=0` yazar. Uygulamayı **yeniden başlatın** (`node dist/main.js` veya pm2 restart).
7. Aynı adreste uygulama açılır. **Giriş:** `erkanulker0@gmail.com` / `password` (ilk girişte şifreyi değiştirin).

**Not:** Kurulum modunu elle açmak için: `INSTALL_MODE=1 node dist/main.js` (Linux/Mac). `npm run start:install` da aynı işi yapar (önce `npm run build` gerekir).

## Canlıya (Server) Taşıma

Sunucuya kurulum ve canlıya hazırlık için **DEPLOYMENT.md** dosyasını kullanın. Özet:

- **Environment:** `.env` içinde `DB_*`, `JWT_SECRET`, `PORT`, `APP_URL`, `FRONTEND_URL` canlı değerlerle ayarlanmalı; `NODE_ENV=production`.
- **Build:** `npm run build:all` ile frontend + backend derlenir; `dist/` ve `frontend/dist/` hatasız oluşmalı.
- **Veritabanı:** Canlıda `synchronize` zaten `false` (NODE_ENV=production); şema değişikliği TypeORM migration ile yapılmalı (`npm run migration:run`).
- **Süreç yönetimi:** Backend’in 7/24 ayakta kalması için PM2 kullanın: `pm2 start ecosystem.config.cjs` (detay DEPLOYMENT.md).

## Modüller

### 1. Teklif Sistemi
- **Oluşturma:** Sadece sistemde kayıtlı ürünlerden; birim fiyat, adet, satır/genel indirim, KDV; ara toplam/KDV/genel toplam otomatik.
- **Durumlar:** Taslak, Müşteriye gönderildi, Onaylandı, Reddedildi, Satışa dönüştürüldü.
- **PDF:** Firma bilgileri, teklif numarası, revizyon (v1, v2…); indir / mail ile gönder.
- **Satışa dönüşüm:** Onaylanan teklif tek tıkla satış fişine dönüşür; stok düşer, cari borç oluşur.

### 2. Tedarikçi Muhasebesi (Cari)
- Alış fişleri, ödemeler, açık bakiye, borç/alacak, vade takibi.
- Alış → borç; ödeme → borç düşer; iade → ters kayıt; manuel cari düzeltme.
- Tarih aralığına göre mutabakat, PDF, mail, “Mutabakat onaylandı”.

### 3. SSH (Satış Sonrası Hizmet)
- Satışa bağlı servis kaydı, garanti, arıza/talep türü, açıklama.
- Süreç: Açıldı, İncelemede, Parça bekleniyor, Çözüldü, Kapandı.
- Servis detayı: personel, tarih, işlemler, kullanılan parçalar (stoktan düşüm).
- Raporlama: Açık servis sayısı, ortalama çözüm süresi.

### 4. Müşteri Borç/Alacak
- Satış fişleri, tahsilatlar, açık borç, vade, geciken borçlar.
- Tahsilat: Nakit, Havale, Kredi kartı, parçalı ödeme, manuel tahsilat fişi.
- (İleride) Vadesi yaklaşan/geçen borç uyarıları ve yönetici bildirimleri.

### 5. Mail, Bildirim ve Log
- Mail: Gönderim, durum (gönderildi/okundu/okunmadı), log.
- Bildirim merkezi: Mail okundu, kritik stok, vade, SSH güncellemeleri.
- Audit log: Kim, ne zaman, ne yaptı; fiyat/stok/mail/SSH değişiklikleri.

### 6. Stok
- Depo bazlı stok, rezerve stok, minimum stok seviyesi, stok hareket geçmişi.

### 7. Yetkilendirme
- Roller: Yönetici, Satış, Depo, Muhasebe, SSH personeli (her rol için ayrı yetki ileride genişletilebilir).

## Frontend yapısı (React SPA)

- **Sayfalar:** `frontend/src/pages/` — auth (Login), dashboard, products, customers, quotes, sales.
- **Layout’lar:** `frontend/src/layouts/` — DashboardLayout (sidebar + outlet).
- **Bileşenler:** `frontend/src/components/` (ileride modals, vb.).
- **API:** `frontend/src/services/api/` — authApi, productsApi, customersApi, quotesApi, salesApi (Axios).
- **Store:** `frontend/src/stores/` — authStore (Zustand, persist).
- **Context:** `frontend/src/contexts/` — AuthContext (AuthProvider, useAuth).
- **Routing:** `frontend/src/routes/AppRoutes.tsx` — React Router v6, ProtectedRoute.

Geliştirme: `npm run dev:frontend` → http://localhost:5174 (Vite proxy ile backend 3001’e yönlenir).

## API Özeti

| Modül        | Prefix                    | Örnekler |
|-------------|----------------------------|----------|
| Auth        | `/auth`                    | POST login, register; GET me |
| Firma       | `/company`                 | GET, PUT |
| Ürün        | `/products`                | CRUD, list |
| Depo        | `/warehouses`             | CRUD |
| Stok        | `/stock`                  | warehouse/:id, product/:id, low, movement |
| Tedarikçi   | `/suppliers`              | CRUD |
| Müşteri     | `/customers`              | CRUD |
| Teklif      | `/quotes`                 | CRUD, :id/status, :id/revision, :id/convert-to-sale, :id/pdf |
| Satış       | `/sales`                  | list, :id, POST from-quote |
| Alış        | `/purchases`              | list, :id, POST (warehouseId ile) |
| Tedarikçi ödeme | `/supplier-payments`  | supplier/:id, balance, POST |
| Mutabakat   | `/supplier-statements`    | generate, :id/approve |
| Müşteri tahsilat | `/customer-payments` | customer/:id, balance, POST |
| SSH         | `/service-tickets`        | CRUD, :id/status, :id/details, stats |
| Mail        | `/mail`                   | send, logs, log/:id/read |
| Bildirim    | `/notifications`          | user/:id, POST, :id/read, read-all |
| Audit       | `/audit-logs`             | entity/:entity, user/:userId, POST |

JWT: `Authorization: Bearer <token>` ile korumalı endpoint’ler kullanılabilir (auth guard modüllere göre eklenebilir).

## Final Stres Testi (Sıfır Hata)

### 1. Zincirleme işlem (uçtan uca)
- Personel ekle → Müşteri ekle → Bu personel üzerinden Teklif oluştur (kalemli) → Teklifi **Satışa Dönüştür** (depo seç).
- **Kontrol 1:** `warehouse_stocks` tablosunda ilgili ürün miktarları düştü mü?
- **Kontrol 2:** Satış detayda **PDF İndir** / **Yazdır**; jspdf şablonunda firma adı ve adres (Companies tablosu) doğru mu?
- **Kontrol 3:** **Ödeme Al** modülünde satışı seç, **Nakit** + **Kasa** seç, tahsilat yap → Seçilen kasada bakiye arttı mı, `kasa_hareket` kaydı **giris** olarak oluştu mu?

### 2. RBAC (yetki) sızma testi
- Rolü sadece **Satış** (UserRole.SATIS) olan bir kullanıcı oluştur.
- Bu kullanıcıyla **Kasa**, **Masraf Çıkışı**, **Muhasebe** sayfalarına girmeyi dene.
- **Beklenen:** API 403 döner; frontend toast ile "Bu sayfaya erişim yetkiniz yok." gösterir ve ana sayfaya yönlendirir.

### 3. Veri tutarlılığı ve null güvenliği
- Hiç alışı/ödemesi olmayan bir **Tedarikçi** ve hiç satışı olmayan bir **Müşteri** kartı aç; detay sayfalarına gir.
- **Beklenen:** Sayfa crash etmez (optional chaining); "Henüz alış kaydı yok", "Henüz ödeme kaydı yok" veya bakiye 0 gibi temiz UI gösterilir.

### 4. Performans ve log
- **Raporlar** ve **Gelir-Gider** sayfalarını aç; backend loglarında N+1 veya Integrity constraint uyarısı olmamalı.
- **50.000 ₺ üzeri** bir alış yap; terminalde `Büyük tutarlı alış: ALS-YYYY-NNNNN, tutar=...` log satırı görülmeli.

### 5. Şirket ayarları → PDF
- **Ayarlar** sayfasında firma adı veya adresi değiştir.
- Yeni oluşturulan bir **Teklif/Satış PDF** (jspdf fallback veya backend PDF) bu değişikliği anında yansıtmalı (company API'den güncel veri çekilir).

## Not

- E-fatura / e-irsaliye için modüler yapı uygun; ileride entegrasyon eklenebilir.
- Admin panel arayüzü bu API’yi tüketecek şekilde ayrı bir frontend (React/Vue vb.) veya aynı projede statik sayfalarla geliştirilebilir.
