# Güvenlik Denetim Raporu — Mobilya Takip

**Tarih:** 2025-02-08  
**Kapsam:** Uygulama güvenliği, sızma testi perspektifi  
**Proje:** Laravel tabanlı Mobilya Takip uygulaması

---

## Özet

Proje genel olarak Laravel’in güvenlik özelliklerini (CSRF, auth, validation) kullanıyor. Aşağıda **tespit edilen riskler** ve **önerilen iyileştirmeler** listelenmiştir.

---

## Kritik / Yüksek Risk

### 1. Dosya yükleme güvenliği (Logo ve servis görselleri)

**Konum:**  
- `app/Http/Controllers/SettingsController.php` (logo)  
- `app/Http/Controllers/ServiceTicketController.php` (images)

**Sorun:**  
- Logo ve servis görselleri için **dosya tipi ve MIME kontrolü yok**.  
- Teorik risk: `.php`, `.phtml` vb. yüklenirse ve sunucu yanlış yapılandırılmışsa çalıştırılabilir (RCE).  
- Ayrıca **dosya boyutu sınırı** yok; DoS veya disk doldurma riski.

**Öneri:**  
- Logo: `mimes:jpeg,jpg,png,gif,webp|max:2048` (veya benzeri) validation ekleyin.  
- Servis görselleri: Aynı şekilde sadece resim tipleri ve makul bir `max` (örn. 5120 KB) kabul edin.  
- İsteğe bağlı: Yüklenen dosyayı yeniden adlandırın (örn. UUID) veya uzantıyı kaldırıp sadece güvenli uzantıyla kaydedin.

---

### 2. XML Feed URL ile SSRF riski

**Konum:** `app/Services/XmlFeedService.php` — `Http::get($feed->url)`

**Sorun:**  
- URL sadece `url` rule ile doğrulanıyor; **şema ve hedef kısıtı yok**.  
- Saldırgan `file:///etc/passwd` veya `http://169.254.169.254/` (cloud metadata) gibi adresler kaydedebilir; sunucu bu adreslere istek atar (SSRF).

**Öneri:**  
- Sadece `http://` ve `https://` kabul edin.  
- İsteğe bağlı: Private/local IP’leri (127.0.0.1, 10.x, 172.16.x, 169.254.x vb.) ve localhost’u reddeden bir rule veya helper kullanın.

---

### 3. Blade’de işlenmemiş HTML (XSS) — extraInfo ve personel adı

**Konum:**  
- `resources/views/partials/invoice-document.blade.php`: `{!! $extraInfo !!}`  
- `resources/views/quotes/show.blade.php` ve `quotes/print.blade.php`: `extraInfo` içinde `$quote->personnel?->name` **escape edilmeden** kullanılıyor.

**Sorun:**  
- Personel adı veritabanından geliyor; eğer bir yerde (örn. personel formu) HTML/script girilirse, fatura/teklif çıktısında **XSS** tetiklenir.  
- `status` alanı `in:taslak,onaylandi,reddedildi` ile kısıtlı; ek escape yine de savunma derinliği sağlar.

**Öneri:**  
- `extraInfo` oluştururken kullanıcı/veritabanı kaynaklı tüm metinleri `e()` ile escape edin (en azından personel adı ve varsa diğer serbest metinler).  
- Mümkünse `extraInfo`’yu HTML yerine güvenli parçalardan (Blade’de `{{ }}` ile) oluşturun; zorunlu HTML varsa strip_tags veya HTML Purifier kullanın.

---

### 4. İstisna mesajlarının kullanıcıya gösterilmesi (bilgi sızıntısı)

**Konum:**  
- `app/Http/Controllers/XmlFeedController.php`: `'Hata: ' . $e->getMessage()`  
- `app/Http/Controllers/SaleController.php`: `$e->getMessage()` (e-posta gönderimi)

**Sorun:**  
- `getMessage()` sunucu yolları, veritabanı hataları veya dahili detayları açığa çıkarabilir.  
- `APP_DEBUG=true` ise stack trace de sızabilir.

**Öneri:**  
- Kullanıcıya sadece genel bir mesaj gösterin (örn. “İşlem sırasında bir hata oluştu.”).  
- Gerçek hata detayını sadece log’a yazın (`Log::error($e)`).  
- **Production’da mutlaka `APP_DEBUG=false`** kullanın.

---

## Orta Risk

### 5. .env ve production ayarları

**Sorun:**  
- `.env` dosyasında `APP_DEBUG=true` ve zayıf/gerçek DB şifresi görüldü.  
- `.env` `.gitignore`’da; ancak yanlışlıkla commit edilirse tüm secret’lar sızar.

**Öneri:**  
- Production’da: `APP_DEBUG=false`, güçlü ve benzersiz `APP_KEY`, güçlü DB şifresi.  
- Şifreleri asla kod deposuna koymayın; deployment’ta env değişkenleri veya secret manager kullanın.  
- Örnek için `.env.example` kullanın; gerçek değerleri `.env`’e sadece sunucuda verin.

---

### 6. Session güvenliği

**Konum:** `config/session.php`

**Öneri:**  
- HTTPS kullanıyorsanız: `SESSION_SECURE_COOKIE=true` (ve env’den okutun).  
- `SESSION_DRIVER=database` veya `redis` production için daha uygun olabilir (dosya paylaşımlı host’larda risk azalır).

---

### 7. Rate limiting

**Konum:** `routes/web.php`  
- Sadece `Route::post('/login', ...)->middleware('throttle:5,1')` sınırlı; bu iyi.

**Öneri:**  
- Hassas veya pahalı işlemlere (Excel import, XML sync, toplu silme) genel bir throttle ekleyebilirsiniz (örn. dakikada 10 istek / kullanıcı).

---

## Düşük Risk / İyi Uygulamalar

- **Kimlik doğrulama:** Şifreler `password_verify` ve `bcrypt` ile; session regenerate ve logout’ta token yenileme var.  
- **Yetkilendirme:** Hassas sayfalar `auth` ve `role:admin` middleware ile korunuyor.  
- **CSRF:** Form’larda `@csrf` ve layout’ta meta csrf-token kullanılıyor.  
- **SQL:** Sorgular Eloquent/Query Builder ile; `whereRaw`/`selectRaw` kullanımlarında parametre binding var; doğrudan kullanıcı birleştirmesi yok.  
- **Mass assignment:** Controller’lar çoğunlukla `$request->validate()` sonrası sadece `$validated` ile create/update yapıyor; Company’deki fillable tek başına risk oluşturmuyor.  
- **Bulk işlemler:** `bulkDestroy` ve benzeri yerlerde `ids` için `exists:sales,id` / `exists:purchases,id` validation’ı var; yetki kontrolü iş mantığıyla uyumlu.

---

## Yapılacaklar Özeti

| Öncelik | Yapılacak |
|--------|-----------|
| Kritik | Logo ve servis görseli yüklemelerine mime/max validation ekleyin. |
| Kritik | XML Feed URL’de sadece http(s) kabul edin; isteğe bağlı private IP engeli. |
| Yüksek | extraInfo ve personel adını XSS’e karşı escape edin veya güvenli HTML ile sınırlayın. |
| Yüksek | Kullanıcıya dönen hata mesajlarında `$e->getMessage()` kullanmayın; log’a yazın. |
| Orta | Production’da APP_DEBUG=false ve güçlü secret’lar kullanın. |
| Orta | HTTPS’te SESSION_SECURE_COOKIE=true yapın. |

Bu rapor, proje kodunun statik incelemesine dayanmaktadır. Canlı ortamda sızma testi ve dependency taraması (örn. `composer audit`, npm audit) da önerilir.

---

## Uygulanan Düzeltmeler (2025-02-08)

- **Logo yükleme:** `SettingsController` — `image|mimes:jpeg,jpg,png,gif,webp|max:2048` validation eklendi.
- **Servis görselleri:** `ServiceTicketController` — `images.*` için `image|mimes:jpeg,jpg,png,gif,webp|max:5120` eklendi.
- **XML Feed SSRF:** `XmlFeedController::store` — URL için `regex:#^https?://#i` kuralı; `XmlFeedService::syncFeed` — istek öncesi http(s) kontrolü.
- **Hata mesajları:** `XmlFeedController` ve `SaleController` — kullanıcıya genel mesaj, detay `Log::error` ile kaydediliyor.
- **XSS (extraInfo):** `quotes/show.blade.php` ve `quotes/print.blade.php` — personel adı ve status `e()` ile escape edildi.
