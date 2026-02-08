# Mobilya Takip — Laravel Forge ile Yayına Alma

Bu doküman, projeyi **Laravel Forge** ile sunucuya deploy etmek ve canlıya almak için gerekli adımları anlatır.

---

## 1. Forge’da Site ve Sunucu

- Forge’da bir **Server** ve ilgili **Site** oluşturun.
- Site’ı **Git** ile bağlayın (repo URL, branch: örn. `main`).
- **Web Directory:** `public` (Laravel varsayılanı).
- **PHP Version:** 8.2 veya üzeri (composer.json ile uyumlu).
- **Node.js:** Vite build için sunucuda Node (örn. 18+) kurulu olmalı; Forge’da "Node" sekmesinden veya NVM ile ekleyebilirsiniz.

---

## 2. Ortam Dosyası (.env)

Sunucuda Forge, site kökünde `.env` oluşturmanızı ister. Aşağıdaki değişkenleri **canlıya uygun** doldurun:

| Değişken | Açıklama | Canlı örnek |
|----------|----------|-------------|
| `APP_NAME` | Uygulama adı | `Mobilya Takip` |
| `APP_ENV` | Ortam | `production` |
| `APP_KEY` | Şifreleme anahtarı | `php artisan key:generate` ile üretin |
| `APP_DEBUG` | Hata detayı | `false` |
| `APP_URL` | Site tam URL | `https://yourdomain.com` |
| `APP_TIMEZONE` | Saat dilimi | `Europe/Istanbul` |
| `DB_CONNECTION` | Veritabanı sürücü | `mysql` |
| `DB_HOST` | MySQL host | Forge DB host (örn. `127.0.0.1`) |
| `DB_PORT` | MySQL port | `3306` |
| `DB_DATABASE` | Veritabanı adı | Forge’da oluşturduğunuz DB adı |
| `DB_USERNAME` | DB kullanıcı | Forge DB kullanıcı |
| `DB_PASSWORD` | DB şifre | Forge DB şifre |
| `SESSION_DRIVER` | Oturum | `file` (veya `database` / `redis`) |
| `SESSION_SECURE_COOKIE` | HTTPS cookie | `true` (HTTPS kullanıyorsanız) |
| `CACHE_STORE` | Cache | `file` (veya `redis`) |
| `QUEUE_CONNECTION` | Kuyruk | `sync` (veya `redis` / `database`) |
| `LOG_LEVEL` | Log seviyesi | `error` veya `warning` |
| `MAIL_*` | E-posta | Canlı SMTP / Mailgun vb. |

**Önemli:** `.env` dosyası repoya **hiçbir zaman** commit edilmemeli.

---

## 3. Deploy Script (Forge’da)

Her deploy’da Forge’un çalıştırdığı script’i aşağıdaki gibi ayarlayın.

**Seçenek A — Repodaki script’i kullan (önerilen):**

Forge **Deploy Script** alanına sadece şunu yazın:

```bash
bash forge-deploy.sh
```

**Seçenek B — Script’i doğrudan yapıştırma:**

Forge **Deploy Script** alanına `forge-deploy.sh` dosyasının içeriğini kopyalayıp yapıştırabilirsiniz. Bu durumda ilk satırda `set -e` ve `BRANCH` ayarı aynı kalmalı.

Deploy script sırasıyla şunları yapar:

1. `git pull` (Forge’un seçtiği branch)
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. `npm ci` + `npm run build` (Vite asset’leri)
5. `php artisan config:cache` / `route:cache` / `view:cache`
6. `php artisan storage:link` (gerekirse)

---

## 4. İlk Deploy Öncesi Kontroller

- [ ] Forge’da **MySQL** veritabanı ve kullanıcı oluşturuldu, `.env` içine yazıldı.
- [ ] `.env` içinde `APP_KEY` var (yoksa sunucuda `php artisan key:generate` çalıştırın).
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` canlı adres.
- [ ] Deploy Script olarak `bash forge-deploy.sh` veya eşdeğeri ayarlandı.
- [ ] **İlk giriş:** Deploy sonrası süper admin için sunucuda bir kez `php artisan db:seed --force` çalıştırın (e-posta: erkanulker0@gmail.com, şifre: password). İsterseniz Deploy Script’e ekleyebilirsiniz: `php artisan db:seed --force`.

---

## 5. SSL (HTTPS)

Forge üzerinden site için **SSL** aktif edin (Let’s Encrypt önerilir). Sonrasında `.env` içinde:

- `APP_URL=https://yourdomain.com`
- `SESSION_SECURE_COOKIE=true`

---

## 6. Scheduler (Cron)

Laravel’de zamanlanmış görev varsa Forge **Scheduler** kullanın. Forge otomatik şu cron’u ekler:

```bash
* * * * * cd /home/forge/siteniz && php artisan schedule:run >> /dev/null 2>&1
```

`routes/console.php` içinde tanımlı `schedule()` komutları bu sayede çalışır.

---

## 7. Queue Worker — Forge’da Process ekleme

XML feed “Ürün Çek” ve diğer arka plan işleri için **queue worker** çalışmalıdır. Forge’da bunu **Processes** ile tanımlayın.

### Adımlar

1. Forge’da ilgili **Site** sayfasına gidin.
2. Sol menüden **Processes** (veya **Daemons**) sekmesine tıklayın.
3. **New Process** / **Add Process** ile yeni process ekleyin.
4. Şu değerleri girin:

| Alan | Değer |
|------|--------|
| **Command** | `php artisan queue:work --sleep=3 --tries=3 --max-time=3600` |
| **Directory** | Site kök dizini (örn. `/home/forge/siteniz` — Forge genelde otomatik doldurur) |
| **User** | `forge` (varsayılan) |
| **Processes** | `1` |

5. Kaydedin. Forge (Supervisor ile) bu komutu sürekli çalıştırır; durursa yeniden başlatır.

### .env ayarı

Queue’nun çalışması için `.env` içinde:

```env
QUEUE_CONNECTION=database
```

kullanın (`sync` değil). Veritabanı kuyruğu için `jobs` tablosu migration’da oluşturulmuş olmalıdır.

### Deploy sonrası worker yenileme

`forge-deploy.sh` içinde `php artisan queue:restart` açıktır. Her deploy’da worker’a “yeniden başla” sinyali gider; mevcut iş bitince worker yeni kodu alıp tekrar başlar.

---

## 8. Kısa Canlı Checklist

- [ ] Forge’da site + Git + `public` web directory ayarlı.
- [ ] `.env` canlı değerlerle dolduruldu; `APP_KEY` var.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` doğru.
- [ ] Deploy Script: `bash forge-deploy.sh` (veya eşdeğeri).
- [ ] İlk deploy sonrası `php artisan migrate --force` hatasız bitti.
- [ ] SSL açıldı; `SESSION_SECURE_COOKIE=true` (HTTPS kullanıyorsanız).
- [ ] Scheduler ve **Processes** (queue worker) Forge’da tanımlı; `QUEUE_CONNECTION=database`.

Bu adımlar tamamlandığında proje Laravel Forge üzerinden yayına hazırdır.

---

## 9. Testler (yerelde / CI)

Otomatik testler (PHPUnit) **sunucuda çalıştırılmaz**. Forge deploy script’i `composer install --no-dev` kullandığı için `vendor/bin/phpunit` sunucuda yoktur.

Testleri **sadece yerelde** (veya CI ortamında) çalıştırın:

```bash
composer install   # dev bağımlılıklar dahil
./vendor/bin/phpunit tests/Feature/CalculationsAndDbTest.php
```

---

## 10. Sorun giderme: 404 (toplu silme)

Ürün veya tedarikçi toplu silme "404 Not Found" veriyorsa: route cache’i temizleyin (`php artisan route:clear`). Route’lar `POST /products/actions/bulk-destroy` ve `POST /suppliers/actions/bulk-destroy` olarak tanımlıdır.
