# Mobilya Takip Sistemi

Laravel + Blade tabanlı mobilya takip uygulaması. Stok, teklif, satış, alış, cari hesap, kasa ve SSH modüllerini içerir.

## Gereksinimler

- PHP 8.2+
- Composer
- MySQL (mevcut mobilyatakip veritabanı)

## Kurulum

1. Bağımlılıkları yükle: `composer install`
2. `.env.example` dosyasını `.env` olarak kopyala
3. `.env` içinde veritabanı bilgilerini düzenle (mobilyatakip)
4. Uygulama anahtarı oluştur: `php artisan key:generate`

**Not:** Migration çalıştırılmaz. Mevcut MySQL veritabanı kullanılır.

**XML Feed kullanımı için:** `scripts/add-xml-feed-schema.sql` dosyasını veritabanında çalıştırın (xml_feeds tablosu ve products tablosuna externalId, externalSource kolonları eklenir).

## Çalıştırma

```bash
php artisan serve
```

Tarayıcıda: http://localhost:8000

## Modüller

- **Auth:** Session tabanlı giriş
- **Müşteriler / Tedarikçiler:** CRUD
- **Ürünler / Depolar:** CRUD
- **Stok:** Depo bazlı stok listesi, kritik stok uyarısı
- **Teklifler:** Oluşturma, satışa dönüştürme
- **Satışlar:** Tekliften satış, stok düşümü
- **Alışlar:** Tedarikçiden alış kaydı
- **Ödeme Al:** Müşteri tahsilatı
- **Kasa:** Kasa/banka hesapları
- **SSH:** Servis kayıtları (satış sonrası hizmet)
- **Personel:** Personel tanımları
- **XML Ürün Çekme:** XML feed URL ile tedarikçi ürünlerini otomatik import
- **Ayarlar:** Firma bilgileri, SMS (NTGSM), PayTR, E-posta ayarları

## Mimari

- **Controller:** Validation, Service çağrısı, View/Redirect
- **Service:** İş mantığı (stok düşümü, cari hesap vb.)
- **Model:** Eloquent (migration yok, mevcut tablolar)
