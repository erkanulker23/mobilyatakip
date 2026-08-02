# Mobilya Takip Sistemi

Laravel + Blade tabanlı mobilya takip uygulaması. Stok, teklif, satış, alış, cari hesap, kasa, SSH ve raporlama modüllerini içerir.

## Gereksinimler

- PHP 8.2+
- Composer
- Node.js 18+ (Vite build için)
- MySQL 8+

## Yerel kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate
# .env içinde DB_* değerlerini düzenleyin
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

Tarayıcı: http://localhost:8000

İlk giriş (seed sonrası): `erkanulker0@gmail.com` / `password` — canlıda mutlaka değiştirin.

## Laravel Forge ile yayına alma

Canlı sunucu kurulumu için **[DEPLOYMENT.md](DEPLOYMENT.md)** dosyasını izleyin.

Forge **Deploy Script** alanına:

```bash
bash forge-deploy.sh
```

yazmanız yeterlidir. Script sırasıyla `git pull`, `composer install --no-dev`, migration, seed, Vite build, cache ve `storage:link` işlemlerini yapar.

## Modüller

- **Auth:** Session tabanlı giriş
- **Müşteriler / Tedarikçiler:** CRUD, cari hesap, Excel import/export
- **Ürünler / Depolar / Stok:** CRUD, kritik stok, XML feed
- **Teklifler / Satışlar / Alışlar:** PDF, çizim dosyaları, sevkiyat fişi
- **Ödeme Al / Ödeme Yap:** Tahsilat ve tedarikçi ödemeleri, kasa hareketleri
- **SSH:** Servis kayıtları, nakliye firması ve araç filosu
- **Kasa / Giderler / Raporlar**
- **Ayarlar:** Firma bilgileri, SMS, e-posta, PayTR

## Mimari

- **Controller:** Validation, Service çağrısı, View/Redirect
- **Service:** İş mantığı (stok, satış, kasa vb.)
- **Model:** Eloquent + migration'lar
