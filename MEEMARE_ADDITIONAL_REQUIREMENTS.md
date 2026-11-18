# Meemare ek gereksinimler

İş emri: MR-2026-001 ek liste  
Tarih: 2026-11-02  
Durum: teknik tasarım. Bu listedeki işler için ürün kodu yazılmadı. Canlıya push yok.

Ana denetim ve Aşama 1 duruyor. `QA-SEC-001` ve `QA-SEC-002` yerel testte kapandı (`dbe7367`, `MEEMARE_SECURITY_FIX_REPORT.md`). Bağlı veritabanında `userId` kolonu yok. Düzeltme orada çalışmıyor. Aşama 2 geometri, Aşama 3 yerleşim, Aşama 4 üretim ve Aşama 5 render kodu başlamadı.

Bu dosya ek işlerin kimliği, sırası ve kabulüdür. Ayrıntı:

- Üretim ve tek kaynak: `MEEMARE_MANUFACTURING_ARCHITECTURE.md`
- Ölçü: `MEEMARE_PARAMETRIC_MODULE_SPEC.md`
- Fiyat: `MEEMARE_PRICING_ENGINE_SPEC.md`
- Yayın kapısı: `MEEMARE_RELEASE_QUALITY_GATES.md`

Satırlar `MEEMARE_BUG_BACKLOG.csv` içine `EK-01` … `EK-13` olarak eklendi.

## Sıra

Kod sırası bağımlılığa göredir. Bir kutu bitmeden altındaki kutunun ürün kodu yazılmaz.

1. `userId` migration bağlı veritabanına uygulanır. Sahibi boş satıra kullanıcı yazılmaz.
2. EK-01 kurtarma, rol ve denetim iskeleti. EK-08 sürüm tablosu bununla birlikte tasarlanır, ayrı yazılır.
3. MR Aşama 2 ve Aşama 3: kaynak kesişim, kapak tanımı, sessiz köşe değişimi, `clearScene`, dokuz duvar.
4. EK-05 pilotu `tek_v1`. Diğer 44 GLB dönüştürülmez.
5. EK-02 ve EK-04 bu pilotun parçalarından hesaplanır.
6. EK-03 engelleri, yerleşim testleri temiz sahnede geçtikten sonra.
7. EK-06 kesim listesi, pilot parça kimlikleriyle.
8. EK-07 fiyat, ancak fiyat satırı gerçekten varsa. Eksik fiyat tutar uydurmaz.
9. EK-09 ekran ayrımı, EK-06 ve EK-07 dururken müşteriye kesim dökülmez.
10. EK-10 kapısı, elde çalışan testler iş hattına alınmadan “dağıtım engelli” denmez. Bugün böyle bir hat yok.
11. EK-11 render ölçütü MR Aşama 5 ile aynı iştir. Yeni trace bu turda alınmadı.
12. EK-12 dosya bölme, EK-10 karakterizasyon testleri yeşilken.
13. EK-13 metin ve sıkıştırma düzeltmesi EK-02 sınırlarıyla birlikte. Sunucu geri dönüşü EK-08 ister.

## Rol değerlendirmesi

Bugün `UserRole` iki değer: `admin`, `staff`. Dolap müşterisi kullanıcı değildir; projede `customerName` ve `phone` alanıdır. Telefon Aşama 1’den sonra yetki değildir. `companies` tek marka kaydıdır. `users.companyId` yoktur. Çok firma yoktur.

Önerilen matris, kod değildir:

| Rol | Bugün | Görebileceği | Yapamayacağı |
| --- | --- | --- | --- |
| admin | var | Kendi kayıtları ve sahibi boş eski kayıtlar | Başka kullanıcının sahibi belli kaydı |
| staff | var | Yalnızca kendi `userId` kayıtları | Başkasının kaydı, fiyat defteri, üretim kilidi |
| satış | yok, staff ile aynı tutulur | Kendi tasarımı ve teklif talebi | İç maliyet, kesim onayı |
| mimar | yok | İleride üretim görünümü ve kilit | Müşteri fiyatını serbest yazmak |
| müşteri | yok | İleride tek projeye bağlı bağlantı | Telefon numarasıyla liste |

Mimar ve müşteri rolü eklenmeden EK-09 arayüzü ayrılmaz. Rastgele kullanıcı ataması yok.

## EK-01 — Sahiplik, yetki, kurtarma

- Öncelik: P1
- Bağımlılık: QA-SEC-001, QA-SEC-002, EK-08
- Kod: `wardrobe_projects`, `WardrobeProjectPolicy`, `AuditService`, `UserRole`
- Yaklaşım: Migration uygulanır. Sahibi boş satır admin tarafından açılır, `userId` boş kalır. Bir kaydı bir kullanıcıya bağlamak ayrı ve elle onaylı bir komuttur; toplu yazılmaz. Silme `deletedAt` ile gizlenir. Geri yükleme sahip veya admin içindir. Kalıcı silme, onaylı üretim sürümü varken kapalıdır. Kimlik günlüğü `audit_logs` içine eylem ve sürüm numarası yazar. Tasarım gövdesi günlüğe değil revizyon tablosuna gider.
- Test: A kendi kaydını açar ve siler, sonra geri yükler. B hiçbiri göremez. Admin A’nın kaydını göremez. Sahibi boş satırın `userId` değeri işlem sonunda da boştur. Yedek, scratch veritabanına geri açılır; satır sayısı ve tasarım özeti aynıdır.
- Kabul: Başka kullanıcının projesine erişilmez. Yetkili kendi kaydını yönetir.
- Durum: TASARIM. Yedek geri yükleme testi çalıştırılmadı.

## EK-02 — Özel ölçü

- Öncelik: P1
- Bağımlılık: EK-05, QA-GEO-001, QA-PROD-001
- Kod: `wardrobe-studio.js` ölçü kutuları, pilot modül tanımı
- Yaklaşım: `MEEMARE_PARAMETRIC_MODULE_SPEC.md`. Genişlik, yükseklik ve derinlik paneli yeniden keser. GLB’nin X/Y/Z ölçeği üretim ölçüsü sayılmaz. Aralık dışı değer sıkıştırılmaz, reddedilir.
- Test: Pilot için izinli bir genişlik parça millerini değiştirir. İzin dışı genişlik sahneyi ve kaydı değiştirmez.
- Kabul: Parça listesi, kapak ve çekmece yeni ölçüyle yeniden üretilir. 45 model toplu dönüştürülmez.
- Durum: TASARIM

## EK-03 — Duvar, niş, montaj

- Öncelik: P1
- Bağımlılık: QA-PLACE-001, EK-05
- Kod: `projects.blade.php` `roomSketch`, `roomOpenings`, `layout`
- Yaklaşım: Kapı ve pencere bugün duvar mesh’ini keser. `layout` bu boşluklara bakmaz. Kolon, kiriş, klima, priz, radyatör, tesisat ve niş yoktur. Engel tek listede durur, sahnede görünür ve yerleşim aralığını düşer. Duvar payı, tavan boşluğu ve süpürgelik payı oda alanıdır. Baza onay kutusu bunları karşılamaz. Dolap duvara sıfır olmak zorunda değildir. Boşluk dolgu veya bitiş çıtası olarak ayrı parça olur.
- Test: Kapı genişliği kadar aralıkta modül duramaz. Kalan duvar ölçüsü özetle aynıdır. Engel kaydı kapanıp açılınca durur.
- Kabul: Engel hem görsel hem yerleşim hesabındadır.
- Durum: TASARIM. Dokuz duvar testi hâlâ Aşama 3’tedir ve geçmedi.

## EK-04 — Ortak panel

- Öncelik: P1
- Bağımlılık: EK-05, EK-02, EK-06
- Kod: `addCarcass`, `module.json` yan panelleri
- Yaklaşım: Varsayılan A, bağımsız gövde. Her modülün kendi sol ve sağ yanı vardır. B, ortak dikme, kullanıcı seçer. B’de bitişik çift tek yan panel paylaşır. Genişlik, panel sayısı, bağlantı, kesim, maliyet ve görünüş iki tipte farklıdır. Tip değişince parça listesi baştan yazılır.
- Test: İki özdeş modül. A’da dört yan panel. B’de üç yan panel ve dış genişlik bir panel kalınlığı kadar azalır. Kesim listesi aynı sayıyı yazar.
- Kabul: Seçim ile parça listesi çelişmez.
- Durum: TASARIM

## EK-05 — Tek kaynak

- Öncelik: P1
- Bağımlılık: QA-GEO-001, QA-GEO-002, QA-PROD-001
- Kod: `tek_v1/module.json`, `wardrobe-cad.json`, `wardrobe-y26-modules.json`, GLB
- Yaklaşım: `MEEMARE_MANUFACTURING_ARCHITECTURE.md`. Pilot `tek_v1`. Görüntü, kesim ve maliyet aynı parça kimliğini okur.
- Test: Pilot tanımındaki bir rafın mili, sahnedeki kutunun mili ve kesim satırı aynıdır.
- Kabul: Üç kaynak bir tanıma iner. Kalan 44 model dönüştürülmez.
- Durum: TASARIM

## EK-06 — Kesim, bant, donanım

- Öncelik: P1
- Bağımlılık: EK-05, EK-04, QA-PROD-001
- Kod: üretim belgesi, ileride ayrı üretim modülü
- Yaklaşım: Onaylı sürümden liste. Net ölçü, bant yönü, delik, menteşe, raf pimi, ray, askı ve bağlantı. DXF aynı geometriden. DWG yoksa DWG denmez. Parça adedi ve alan toplamı yazılır. Doğrulama yokken “CNC’ye hazır” yazılmaz.
- Test: Pilot kesim satırı ile tanım kimliği birebir. Eksik delik olan parça listede “doğrulanmadı” kalır.
- Kabul: Liste uydurma donanımla ticari işaret taşımaz.
- Durum: TASARIM

## EK-07 — Fiyat ve teklif

- Öncelik: P1
- Bağımlılık: EK-06, EK-01
- Kod: `POST /dolap/teklif`, `WardrobeQuote`, ileride fiyat defteri
- Yaklaşım: `MEEMARE_PRICING_ENGINE_SPEC.md`. Mevcut uç talep kaydıdır, tutar üretmez. Fiyat kod içine gömülmez. Eksik fiyat teklifi keser.
- Test: Fiyatı boş bir bant kalemi müşteri toplamı yazmaz. İç maliyet müşteri çıktısında görünmez.
- Kabul: Uydurma tutar yok. Kurallar onaylanmadan müşteri fiyatı yok.
- Durum: TASARIM

## EK-08 — Sürüm

- Öncelik: P1
- Bağımlılık: EK-01
- Kod: `wardrobe_projects.design`, `undo`, `redo`
- Yaklaşım: Her kayıt yeni revizyon. Onaylı revizyon kilitlenir ve üretim kopyası olur. Sonraki düzenleme yeni numara açar. Eşzamanlı kayıt, eski sürüm numarasını görünce reddeder. Tarayıcı geri alı yenilemede durmaz; kalıcı geçmiş tablodadır.
- Test: Onaydan sonra düzenleme onaylı gövdeyi değiştirmez. İkinci oturum eski numarayla yazınca 409 döner ve ilk gövde durur.
- Kabul: Üretim kopyası ile taslak karışmaz.
- Durum: TASARIM

## EK-09 — Müşteri ve mimar

- Öncelik: P2
- Bağımlılık: EK-01, EK-06, EK-07, EK-08
- Kod: `wardrobe/index.blade.php`, rol matrisi
- Yaklaşım: Müşteri seçer, ölçüyü okur, renk ve kapak seçer, önizler, talep eder, kaydeder. Mimar ölçü, parça, donanım, tolerans, teknik görünüm, kesim ve revizyon görür. Ayrı rol yokken ikinci ekran yapılmaz.
- Test: Müşteri oturumunun yanıtında kesim delikleri ve iç maliyet yoktur. Mimar onaylı sürümün parça kimliğini görür.
- Kabul: Müşteri ekranı teknik döküm taşımaz.
- Durum: TASARIM

## EK-10 — Yayın kapısı

- Öncelik: P1
- Bağımlılık: QA-SEC-001, EK-05, QA-PLACE-001
- Kod: `WardrobeProjectAccessTest`, `scripts/qa-master-geometry.py`
- Yaklaşım: `MEEMARE_RELEASE_QUALITY_GATES.md`
- Test: Güvenlik testi kırmızıysa paket adımı durur. Geometri betiği altı kesişen modülde hâlâ kırmızıdır; bu haliyle kapı açık sayılmaz.
- Kabul: Test edilmeyen madde geçti yazılmaz.
- Durum: TASARIM. Kapı bugün dağıtımı durdurmuyor.

## EK-11 — Render ölçütü

- Öncelik: P1
- Bağımlılık: QA-RENDER-001
- Kod: `takeRender`, malzeme ve sarım
- Yaklaşım: Aynı kamera, aynı çözünürlük, raster ve path. Siyah yüz ayrı teşhis. Benek, düz panel bölgesinde ölçülür. Eşik yazılmadan “temiz” denmez. Render `design` alanını değiştirmez. Kastamonu görselleri resmi katalog diye sunulmaz. Aksesuar gerçek değilse fotoğraf iddiası yok.
- Test: Eski kullanıcı görseli ile yeni kare aynı kadrajda yan yana. Tasarım JSON’u render öncesi ve sonrası aynıdır.
- Kabul: Siyah yüz gider. Benek, belgede yazılmış eşiğin altındadır. Bu turda yeni kare yok, eşik ölçülmedi.
- Durum: TASARIM

## EK-12 — Dosya bölme

- Öncelik: P2
- Bağımlılık: EK-10, QA-PLACE-001
- Kod: `public/js/wardrobe-studio.js`
- Yaklaşım: Dosya katalog, geometri, yerleşim, malzeme, kapak, çekmece, kamera, render, kayıt, fiyat, üretim ve doğrulamayı birlikte taşır. Bölme ancak aynı sahnenin ölçü ve kayıt testleri varken. Sırf satır azaltmak için bölünmez. Fiyat ve üretim modülü, o işler yazılmadan boş dosya olarak açılmaz.
- Test: Bölmeden önce ve sonra aynı duvarda aynı modül konumları ve aynı kayıt gövdesi.
- Kabul: Davranış değişmeden bir grup ayrılır.
- Durum: TASARIM. Bölünmedi.

## EK-13 — Hata kurtarma

- Öncelik: P2
- Bağımlılık: EK-08, EK-02
- Kod: ölçü kutuları, `persist`, `undo`
- Yaklaşım: Aralık dışı ölçü sessizce sıkışmaz. Eksik GLB modülü sahneye yarım gövde diye eklemez. Kayıt hatasında sahne bellekte kalır ve metin yeniden denemeyi söyler. Silme, bellek geri alı ile döner; kalıcı silme EK-01 geri yüklemesine bağlıdır. Uzun render ilerleme yazar. Klavye ile kaydet, geri al ve sil erişilir. Dokunmatik test gerçek cihazdan ayrı yazılır.
- Test: 10 cm genişlik reddedilir, önceki genişlik durur. Kayıt 500 olunca modül listesi ekranda kalır.
- Kabul: Mesaj durumu ve sonraki adımı söyler.
- Durum: TASARIM. Gerçek cihaz testi yok.
