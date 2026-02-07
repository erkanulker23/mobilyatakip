# Tarayıcı Test Checklist — Mobilya Takip

**Temel URL:** https://mobilyatakip-v1.test  
**Dashboard:** https://mobilyatakip-v1.test/dashboard  

Giriş: `erkanulker0@gmail.com` / `password` (veya süper admin bilgileriniz)

Test öncesi örnek veri: `php artisan db:seed --class=TestDataSeeder`

---

## 1. Giriş ve Dashboard

- [ ] `/login` açılıyor, form görünüyor
- [ ] Yanlış şifre ile "hatalı giriş" mesajı
- [ ] Doğru giriş sonrası `/dashboard` yönlendirmesi
- [ ] Dashboard’da özet bilgiler (satış, stok vb.) görünüyor
- [ ] Çıkış (logout) çalışıyor, tekrar login sayfasına düşüyor

---

## 2. Müşteriler

- [ ] **Liste** `/customers` — Müşteri listesi, arama
- [ ] **Yeni** `/customers/create` — Form: Ad, e-posta, telefon, adres, **TC kimlik (11 hane)**, **Vergi no (10 hane)**, vergi dairesi
- [ ] Geçersiz TC kimlik (örn. 11 hane ama yanlış checksum) → validasyon hatası
- [ ] Geçersiz VKN (10 hane değil veya yanlış kontrol hanesi) → validasyon hatası
- [ ] Geçerli veri ile kaydet → listeye dön, **veritabanında** `customers` tablosunda kayıt var
- [ ] **Detay** bir müşteri → bilgiler doğru
- [ ] **Düzenle** → değiştir, kaydet → DB’de güncel
- [ ] **Yazdır** müşteri → PDF/sayfa açılıyor

---

## 3. Tedarikçiler

- [ ] **Liste** `/suppliers`
- [ ] **Yeni** `/suppliers/create` — Vergi no 10 hane + algoritma; boş bırakılabilmeli
- [ ] Kaydet → **veritabanında** `suppliers` kaydı
- [ ] **Detay / Düzenle / Yazdır** çalışıyor

---

## 4. Ürünler

- [ ] **Liste** `/products`
- [ ] **Yeni** ürün: ad, birim fiyat, KDV dahil/hariç, KDV oranı
- [ ] Kaydet → **veritabanında** `products` kaydı
- [ ] **Düzenle** → fiyat değişikliği DB’de kalıcı

---

## 5. Depolar ve Stok

- [ ] **Depolar** `/warehouses` — liste, yeni depo
- [ ] **Stok** `/stock` — ürün/depo bazlı miktar
- [ ] Stok güncelle → **veritabanında** `stocks` güncellenmiş

---

## 6. Kasa

- [ ] **Liste** `/kasa`
- [ ] **Yeni kasa** (nakit/banka)
- [ ] Kayıt **veritabanında** `kasa` tablosunda

---

## 7. Teklifler (fiyat / indirim tutarlılığı)

- [ ] **Liste** `/quotes`
- [ ] **Yeni teklif** `/quotes/create`  
  - Müşteri seç, KDV dahil/hariç  
  - En az 2 kalem: fiyat, adet, KDV %, **İnd. %** ve **İnd. ₺** (kalem indirimi)  
  - **Genel indirim** % veya tutar
- [ ] Kaydet → başarı mesajı, listeye dön
- [ ] **Veritabanı kontrolü:** `quotes` → `subtotal`, `kdvTotal`, `grandTotal`; `quote_items` → `lineTotal`, `lineDiscountPercent`, `lineDiscountAmount`
- [ ] **Tutarlılık:** Genel indirim sonrası: (subtotal - genel indirim) + KDV ≈ grandTotal (küçük yuvarlama farkı olabilir)
- [ ] **Detay** teklif → toplamlar ekranda doğru
- [ ] **Düzenle** → kalem indirimi değiştir, kaydet → DB’de güncel
- [ ] **Yazdır** teklif

---

## 8. Satışlar (stok ve DB)

- [ ] **Liste** `/sales`
- [ ] **Yeni satış** `/sales/create` — Müşteri, depo, kalemler (ürün, fiyat, adet, KDV)
- [ ] Yetersiz stokta hata mesajı
- [ ] Yeterli stokla kaydet → **veritabanında** `sales` ve `sale_items` kayıtları; **stok** ilgili depoda düşmüş (`stocks`, `stock_movements`)
- [ ] Satış detayda: subtotal, kdvTotal, grandTotal tutarlı
- [ ] **Yazdır** satış
- [ ] **İptal** satış → `sales.isCancelled = true`; stok geri alınmış mı kontrol et

---

## 9. Alışlar (tedarikçi indirimi ve DB)

- [ ] **Liste** `/purchases`
- [ ] **Yeni alış** `/purchases/create` — Tedarikçi, **Tedarikçi indirimi %** (örn. 10), kalemler
- [ ] Kaydet → **veritabanında** `purchases` → `subtotal`, `kdvTotal`, `grandTotal` indirimli (örn. %10 indirimli)
- [ ] **Detay** alış → toplamlar indirimli görünüyor
- [ ] **Yazdır** alış
- [ ] **İptal** alış → `purchases.isCancelled = true`

---

## 10. Giderler (KDV alanları ve DB)

- [ ] **Liste** `/expenses`
- [ ] **Yeni gider** `/expenses/create` — Tutar, **KDV dahil** işaretli, **KDV oranı %** (örn. 18)
- [ ] Kaydet → **veritabanında** `expenses` → `amount`, `kdvIncluded`, `kdvRate`, `kdvAmount` dolu
- [ ] KDV dahil 118 ₺, %18 → kdvAmount ≈ 18 ₺
- [ ] **Detay** giderde KDV satırı görünüyor
- [ ] **Düzenle** gider → KDV alanları güncelleniyor ve DB’de doğru

---

## 11. Ödeme Al / Ödeme Yap

- [ ] **Ödeme al** `/odeme-al` — Müşteri, satış seçimi, tutar, kasa
- [ ] Kaydet → **veritabanında** `customer_payments` ve satışta `paidAmount` güncel
- [ ] **Ödeme yap** `/odeme-yap` — Tedarikçi, alış, tutar, kasa
- [ ] Kaydet → **veritabanında** `supplier_payments` ve alışta `paidAmount` güncel

---

## 12. Raporlar (veri ve iptal filtresi)

- [ ] **Raporlar** `/raporlar`
- [ ] **Gelir–Gider** — Tarih aralığı, satış/tahsilat/gider/tedarikçi ödeme toplamları
- [ ] **KDV raporu** — Tarih aralığı  
  - Satış KDV, Alış KDV, **Gider KDV** bölümü görünüyor  
  - Özet: Satış KDV, Alış KDV, Gider KDV, Ödenecek KDV
- [ ] **Müşteri cari** — Müşteri bakiyeleri; bir müşteri detayı → borç/alacak hareketler
- [ ] **Tedarikçi cari** — Tedarikçi bakiyeleri  
  - **Kontrol:** İptal edilmiş bir alış yapılmış tedarikçi → bakiyede bu alış **yer almamalı** (iptal filtresi)
- [ ] **Veritabanı:** Raporlarda gördüğünüz toplamlar, ilgili tablolardaki (sales, purchases, expenses, payments) toplamlar ile mantıken uyumlu

---

## 13. E-Fatura (XML / gönderim)

- [ ] Bir **satış** detayında **E-Fatura XML İndir** → XML iniyor
- [ ] İndirilen XML’de kalemlerde **LineExtensionAmount** (matrah) KDV hariç tutar; **TaxCategory** (KDV oranı) var
- [ ] (Entegratör ayarlıysa) **E-Fatura Gönder** → durum mesajı; **veritabanında** `sales.efaturaStatus`, `efaturaSentAt` vb. güncelleniyor

---

## 14. Ayarlar (VKN ve E-Fatura)

- [ ] **Ayarlar** `/ayarlar` (admin)
- [ ] Şirket **Vergi no** 10 hane + algoritma; geçersiz VKN’de validasyon hatası
- [ ] E-Fatura alanları (sağlayıcı, endpoint, kullanıcı) kaydediliyor ve **veritabanında** `companies` güncel

---

## 15. Personel, Servis, XML Feed (kısa)

- [ ] **Personel** `/personnel` — liste, yeni, düzenle, **veritabanında** `personnel`
- [ ] **Servis talepleri** `/service-tickets` — liste, yeni, **veritabanında** `service_tickets`
- [ ] **XML Feed** `/xml-feeds` — liste (gerekirse oluştur)

---

## Veritabanı hızlı kontrol (örnek)

```bash
# Müşteri sayısı
php artisan tinker --execute="echo App\Models\Customer::count();"

# Son teklif toplamları
php artisan tinker --execute="\$q = App\Models\Quote::latest('createdAt')->first(); if (\$q) echo \$q->subtotal . ' ' . \$q->kdvTotal . ' ' . \$q->grandTotal;"

# Son gider KDV
php artisan tinker --execute="\$e = App\Models\Expense::latest('createdAt')->first(); if (\$e) echo \$e->amount . ' ' . \$e->kdvAmount;"
```

---

## Otomatik testler (hesaplama + DB)

Hesaplama ve veritabanı kaydı için otomatik testler:

```bash
php artisan test tests/Feature/CalculationsAndDbTest.php
```

- Teklif: kalem indirimi + genel indirim, toplamlar ve DB
- Alış: tedarikçi indirimi toplama yansıyor ve DB’de doğru
- Gider: KDV hesaplanıp DB’ye yazılıyor
- Tedarikçi cari: iptal edilen alışlar bakiyeye dahil değil

Bu checklist’i sırayla uygulayarak tüm sayfaları ve veritabanı tutarlılığını test edebilirsiniz.
