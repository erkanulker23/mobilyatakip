# MEEMARE güvenlik düzeltmesi — Aşama 1

İş emri: MR-2026-001  
Kapsam: QA-SEC-001, QA-SEC-002  
Tarih: 2026-11-02  
Karar: P0 listeleme ve belirteçle erişim kapatıldı. Aşama 2’ye geçilmedi. Canlıya deploy veya push yapılmadı.

## Kök neden

`wardrobe_projects` tablosunda sahip kolonu yoktu. `GET /dolap/projeler` son kayıtları okuyup telefon sorulmamışsa herkese, telefon verilmişse son on haneye göre süzüyordu. Yanıtta proje belirteci, müşteri adı ve telefon vardı. `GET`, `PUT` ve `DELETE /dolap/proje/{token}` yalnızca belirteci arıyordu. Belirteci bilmek kaydı açmaya, değiştirmeye ve silmeye yetiyordu.

Firma ayrımı yok. `users` tablosunda `companyId` yok. `companies` tek marka kaydı. Bu yüzden yetki, oturumdaki kullanıcıya bağlandı.

## Yapılan değişiklik

Yeni migration `userId` kolonunu boş bırakılabilir şekilde ekler. Eski satırlara kullanıcı yazılmaz.

Yeni kayıt, oturumdaki kullanıcının kimliğini alır. `WardrobeProjectPolicy` okuma, güncelleme ve silmeyi yalnızca bu kimliğe izin verir. Sahibi boş eski kaydı yalnızca `admin` rolü açabilir; bu işlem sahibi alanını doldurmaz. Yönetici, başka kullanıcıya ait kaydı göremez.

Liste telefonu yok sayar. Dönen alanlar: `id`, `projectName`, `updatedAt`. Belirteç, telefon ve müşteri adı listede yoktur.

Belirteç adresi ve `GET|PUT|DELETE /dolap/proje/kayit/{id}` aynı politikadan geçer. Başkasının kaydı ile olmayan kayıt aynı gövdeyi döner: HTTP 404, `{"ok":false,"message":"Proje bulunamadı."}`. Reddedilen deneme `audit_logs` tablosuna `denied` olarak yazılır. Günlüğe belirteç, telefon veya tasarım konmaz.

Sahibin kendi oluşturma, okuma ve güncelleme yanıtında belirteç durur. Stüdyo kaydı bununla günceller. Bu belirteç artık başka oturumda anahtar değildir.

Arayüz listeyi sahip kimliğiyle açar ve siler. Telefonla arama kalkmıştır.

## Test

Kanıt: `test-results/security-fix/phase1-phpunit.txt`  
Komut: `php artisan test --filter=WardrobeProjectAccessTest`  
Sonuç: 1 test, 56 assertion, PASS.

Veri: sqlite bellek. Kullanıcılar `wardrobe-a@example.test`, `wardrobe-b@example.test`, `wardrobe-admin@example.test`. Gerçek müşteri kaydı okunmadı ve değiştirilmedi.

| Senaryo | Sonuç |
| --- | --- |
| Oturumsuz `GET /dolap/projeler` JSON | 401 |
| Oturumsuz aynı adres, tarayıcı | girişe yönlendirme |
| A proje oluşturur | 200, kendi `userId` |
| A listeler, telefon sorgu dizesi verilmiş olsa da | yalnız kendi proje adı; yanıtta belirteç, telefon ve müşteri adı yok |
| A belirteç ve kayıt kimliği ile okur | 200 |
| A günceller | 200, ad değişir |
| B, A’nın telefonu ile listeler | boş liste |
| B, A’nın belirteci ve kimliği ile okur, günceller, siler | altı istek de 404, aynı gövde |
| B, var olmayan belirteç okur | 404, aynı gövde |
| Yönetici, A’nın projesini listeler ve okur | listede yok, okuma 404 |
| Sahibi boş eski kayıt | A ve B göremez; yönetici okur; `userId` boş kalır |
| A kendi kaydını siler | 200, ardından kendi okuması 404 |
| Red günlüğü | `denied` yazılır, belirteç metni günlüğe girmez |

Firma ayrımı testi uygulanamadı: kiracı firma kolonu yok. İki ayrı kullanıcı aynı uygulamada birbirinin kaydını görmedi.

## Uçlar

- `GET /dolap/projeler`
- `POST /dolap/proje`
- `GET /dolap/proje/{token}`
- `PUT /dolap/proje/{token}`
- `DELETE /dolap/proje/{token}`
- `GET /dolap/proje/kayit/{id}`
- `PUT /dolap/proje/kayit/{id}`
- `DELETE /dolap/proje/kayit/{id}`

## Değişen dosyalar

- `database/migrations/2026_11_02_000001_add_user_id_to_wardrobe_projects.php`
- `app/Policies/WardrobeProjectPolicy.php`
- `app/Models/WardrobeProject.php`
- `app/Http/Controllers/PublicWardrobeController.php`
- `app/Providers/AppServiceProvider.php`
- `app/Support/UserSchema.php`
- `routes/web.php`
- `public/js/wardrobe-studio.js`
- `resources/views/wardrobe/index.blade.php`
- `resources/views/wardrobe/projects.blade.php`
- `tests/Feature/WardrobeProjectAccessTest.php`

## Belirteçler ve eski kayıtlar

Dolap projesi erişimi daha önce `audit_logs` içine yazılmıyordu. Hangi belirtecin kim tarafından açıldığı bu tablodan çıkarılamaz. Yeni red kayıtları bu eksikliği yalnızca bundan sonrası için kapatır.

Liste daha önce belirteç döndürdüğü için bu değerlerin kopyalanmış olma ihtimali vardır. Düzeltmeden sonra belirteç, sahibi olmayan oturumda işe yaramaz. Kayıtlar silinmedi, belirteçler toplu değiştirilmedi.

Sahibi boş satırlar rastgele kullanıcıya yazılmadı. Canlıdaki eski tasarımlar, migration uygulandıktan sonra normal kullanıcı listesinde görünmez. Bunları açmak için yönetici oturumu gerekir. Sahibi yine boş kalır. Bir kullanıcıya bağlamak ayrı ve bilinçli bir işlem olmalıdır; bu aşamada yapılmadı.

## Kalan risk

Bu kod, `userId` kolonu olmadan yeni kayıt açmaz ve listeyi boş döner. Canlı veritabanına bu migration uygulanmadan deploy edilirse kayıt akışı durur. Migration bu oturumda çalıştırılmadı. Push ve deploy yapılmadı.

Yönetici, sahibi boş tüm eski tasarımları okuyabilir. Bu, eski kayıtların kaybolmaması içindir. Başka kullanıcının sahibi belli kaydını kapsamaz.

Oluşturma ve yetkili okuma yanıtı, oturum sahibine belirteci verir. Bu değer o oturumun sonraki kaydı içindir. Başka hesapta kullanılamaz.

Teklif formu (`POST /dolap/teklif`) bu aşamanın dışında kaldı.

Aşama 2 geometri işine geçilmedi.
