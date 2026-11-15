# Meemare 3D Studio ana kalite raporu

Tarih: 2 Kasım 2026. İncelenen sürüm: `98ef714` on `main`. Ortam: yerel PHP sunucusu `127.0.0.1:8765`. Canlıya push yok. Production veritabanında müşteri kaydı açılmadı. Tek yazma, `QA Master Denetim` projesiydi ve denetim sonunda silindi.

Bu rapor uygulamayı çalışan diye profesyonel saymaz. Ölçülmeyen başlıklar `NOT_TESTED` veya `BLOCKED` olarak durur.

## Yönetici özeti

Stüdyo bir tasarım oturumu açıyor, 45 dolap kartını listeliyor ve dış ölçüleri katalogla tutan GLB dosyalarını sahneye koyuyor. Bu, satılabilir bir özel üretim platformu olduğu anlamına gelmiyor.

Durdurucu bulgular:

- Oturum açmış kullanıcı, telefon filtresi olmadan son projelerin belirtecini, adını ve telefonunu alıyor.
- Belirteci olan kullanıcı o projeyi okuyup silebiliyor. Sahiplik kontrolü yok.
- Altı Y26 çift modülde çekmece kutusu orta dikmenin içinden geçiyor.
- 45 modelin hiçbirinde kapak mesh'i yok.
- Kullanıcının render görseli benekli. Köşenin kameraya bakan yüzü siyah.
- Kesim dosyası, menteşe, raf pimi ve gerçek çekmece rayı yok.

180 cm duvarda dört adet 45 cm modülün toplamı 180 cm ve görüntüde dört dolap var. Bu yerleşim örneği tuttu. 225 cm ve daha geniş duvarlar, sahne silinemediği için ayrı senaryo diye kabul edilmedi.

## 1. Hangi alanlar profesyonel?

- Laravel 12 iskeleti, oturum, throttle ve sunucu tarafı alan doğrulaması duruyor. Proje oluşturma `customerName` ve `projectName` istiyor.
- Katalog iki JSON dosyasında. Y26 20 kayıt, eski seri 25 kayıt. Stüdyo ikisini birleştiriyor. Sayfada 45 kart sayıldı.
- 45 önizleme dosyası var. 45 GLB açıldı. Dış genişlik, yükseklik ve derinlik katalogdan 8 mm'den fazla sapmıyor.
- Gövde ve raf kalınlığı ölçülen modellerde 18 mm. Y26 arkalık 8 mm. Köşe arkalığı 18 mm.
- Masaüstü çalışma alanı katalog, tuval ve ölçü şeridi olarak ayrılmış. `test-results/master-qa/screenshots/desktop-studio.jpg`
- Arayüz, Kastamonu görsellerinin temsili olduğunu yazıyor. Mobil sayfada da aynı cümle duruyor.

## 2. Hangi alanlar amatör?

- Stüdyo mantığı `public/js/wardrobe-studio.js` içinde 6787 satır. Yerleşim, malzeme, kapak, kayıt ve path trace aynı dosyada.
- Three.js `0.170.0` ve `three-gpu-pathtracer@0.0.23` sayfaya CDN import map ile geliyor. Sürüm kilidi paket yöneticisinde değil.
- Yeni dolap kapaksız geliyor. Kapak sonradan prosedürel ekleniyor. Kaynak modelle müşterinin gördüğü kapak aynı parça değil.
- Ray silindir. Menteşe ve raf pimi yok.
- Render varsayılanı 12 geçiş. 2x2 karo ile 48 örnek. Denoise yarıçapı `round(1.6 * 1.5) = 2` piksel.
- Mobil emülasyonda özelleştirme sayfası 3D alanı kapatıyor. Bu gerçek cihaz ölçümü değil.

## 3. Hangi özellikler eksik?

- Sahip bazlı proje yetkisi.
- Fiyat motoru. Teklif formu talebi kaydediyor. Birim fiyat, vergi ve kesim maliyeti yok.
- DXF veya DWG indirme. `wardrobe-cad.json` içinde 25 tarif var. Dosya üreten bir uç bulunmadı.
- Raf pimi, menteşe, kavela, minifix ve metal çekmece rayı.
- Kapak salınımının komşu dolaba çarpmasını engelleyen denetim.
- 100 ve 500 modüllük katalog ölçümü.
- Resmi Kastamonu tarama dokusu. Uygulama bunu kendi metninde de söylüyor.

## 4. Hangi özellikler yanlış çalışıyor?

- `GET /dolap/projeler` telefonsuz çağrıda son 20 projenin `token`, `customerName`, `phone` alanlarını veriyor. Ayrıntı rapora kopyalanmadı. Kanıt: `test-results/master-qa/security/project-index-meta.json`.
- Köşeye gelen düz modül, `cornerModelId` ile `kose_1`, `kose_2` veya `kose_3` olarak çiziliyor. Seçilen ürünün kimliği değişmiyor, görünen gövde değişiyor.
- Özet satırındaki `Dolap N cm`, modüllerin genişlik toplamı. Hangi duvarda durdukları ayrı değil.
- `y26_tek_02` eklemesinde HUD adı bir kez katalog adından farklı geldi. Tekrar edilmedi. `QA-NAME-001`.

## 5. Hangi 3D modeller geometrik olarak hatalı?

Kaynak GLB ölçümü: `scripts/qa-master-geometry.py`. Sonuç: `test-results/master-qa/geometry-tests/module-geometry.json`.

Dış kutusu hatalı model yok. Hata, iç kesişimde.

| Modül | Kesişen parçalar | Kesişim |
|---|---|---|
| y26_cift_01 | IC_DIKME ve iki çekmece kutusu | 18 x 120 x 465 mm, yaklaşık 1004 cm3 |
| y26_cift_02 | aynı | iki kutu |
| y26_cift_03 | aynı | iki kutu |
| y26_cift_04 | aynı | bir kutu |
| y26_cift_06 | aynı | iki kutu |
| y26_cift_09 | aynı | dört kutu |

Köşe panelleri ayrıca köşede 18 mm üst üste biniyor. Bu, iki parçanın aynı hacmi paylaşmasıdır. Çarpışma listesine alınmadı çünkü birleşim bindirmesi. Kesim listesine iki kez yazılırsa parça fazla çıkar. `QA-GEO-003`.

45 modelde kapak düğümü yok.

## 6. Hangi modüller üretim için uygun değil?

Altı Y26 çift modül, çekmece kutusu dikmeden geçtiği için bu haliyle kesilemez.

Köşe modelleri bindirmeli birleşimle duruyor. Üretim çizimi yok.

Diğer 39 modelin dış ölçüsü ve 18 mm yan / raf kalınlığı kaynak dosyada tutuyor. Bu, atölyeye gönderilebilir oldukları anlamına gelmiyor. Pim deliği, kapak payı, bant ve ray eksiği hepsinde var.

Meemare25 çekmece parçalarında 12 mm ölçülen kalınlık var (`tek_v1`, `cift_v1` ve kardeşleri). Arayüz metni çekmece yanını 16 mm, tabanı 8 mm diye yazıyor. Kaynak ölçü ile bu cümle her modülde doğrulanmadı.

CNC'ye hazır ibaresi kullanılmadı.

## 7. Hangi malzemeler gerçekçi değil?

- Dekorlar katalog JPEG'i. Uygulama resmi Kastamonu taraması olmadığını yazıyor.
- Yalnızca `y26_cift_01` için ayrı meşe temsil malzemesi var. Diğer modüller aynı JPEG yolunu kullanıyor.
- Cam `MeshPhysicalMaterial` ve `clearcoat: 0.12`. Bu path tracer sürümünde clearcoat metalde siyaha boyuyor. Camda da risk. Kalınlık 0 ve attenuation sonsuz bırakılmış. Bu, siyah hacim yolunu kapatmak için yapılmış.
- Renk değiştirince yalnız o parçanın değiştiği bu turda denenmedi. `NOT_TESTED`.

## 8. Hangi ekranlarda deneyim kötü?

- Mobil emülasyon, 390 x 844. Malzeme sayfası sahneyi kapatıyor. Üstte iki sıra kamera düğmesi. Kanıt: `test-results/master-qa/mobile-tests/iphone-390.jpg`. Gerçek telefon değil.
- Modül eklenince kamera o dolaba gitmiyor. `y26_tek_02` tuvali boş köşe. HUD aynı anda 45 cm yazıyor. Kanıt: `test-results/master-qa/module-tests/y26_tek_02.jpg`.
- Masaüstü ilk açılış düzeni daha düzgün. Kanıt: `test-results/master-qa/screenshots/desktop-studio.jpg`.

## 9. Mobilde hangi sorunlar var?

Yalnızca Chrome emülasyonu.

- 390 px: 3D alan görünmüyor. Modül, malzeme, kapak, ölçü ve proje sekmeleri altta. Duvar dolu uyarısı ortada.
- 768 px ekran görüntüsü alındı: `test-results/master-qa/mobile-tests/tablet-768.jpg`. Ayrı etkileşim turu yok.
- Dokunma ile sürükleme, kapak tıklaması, klavye ve döndürme `NOT_TESTED`.

## 10. Kaydetme ve veri bütünlüğü ne kadar güvenilir?

QA projesi oluşturuldu. Adres ` /dolap/tasarla?proje=2DdKtoSdqZswVLtynCYz7gsb`. Kaydetten sonra sayfa yenilendi. Özet yine `Ön 785 cm` ve `Dolap 765 cm` geldi. Bu, son sahnenin geri geldiğini gösterir. Ardından DELETE HTTP 200.

Güvenilirlik sahibi kontrolü olmadığı için ticari anlamda zayıf. Başka bir oturumun bu belirteci silip silemeyeceği, belirteci ele geçiren için kodda engellenmiyor. İki sekme, kopuk ağ ve hatalı GLB `NOT_TESTED`.

## 11. Render neden fotoğraf gibi değil?

Bu turda yeni path trace çalıştırılmadı. Kanıt, kullanıcının 2 Kasım 2026 görseli: `test-results/master-qa/render-comparisons/user-supplied-2026-11-02.jpg`.

Kaynak ayarlar `takeRender` içinde:

- 12 geçiş, karo 2x2, hedef 48 örnek. Bütçe 150 saniye.
- Denoise `sigma 1.6`, `kSigma 1.5`, `threshold 0.07`. Çekirdek yarıçapı 2 piksel. Bu, 1400 piksel civarı bir karedeki benekleri temizlemez.
- Anahtar ışık ancak `window.__traceKey` açıksa kuruluyor. Varsayılan kapalı.
- Ortam şiddeti 0.7.
- Camda clearcoat 0.12.

Görseldeki düz siyah yüz, ışık almayan veya ters sarımlı bir dış panel ile uyumlu. Bu denetim o yüzün normalini kare üzerinde yeniden ölçmedi. Gürültü ise düşük örnek ve küçük denoise ile açıklanıyor. Fotoğraf gerçekçiliği elde edilmedi.

## 12. Performansın gerçek darboğazı ne?

1, 3, 5, 10 ve 17 modülde `requestAnimationFrame` bir saniyede 61 kare saydı. Bu, tazeleme hızının tavanıdır. GPU süresi, draw call, üçgen sayısı ve texture belleği bu turda ölçülmedi. `test-results/master-qa/performance/fps.json`.

Darboğaz iddiası bu sayılara dayanamaz. Bilinen yapısal yük, her modülün GLB'sinin klonlanıp chamfer edilerek yeniden kurulması ve path trace BVH'sidir. İkisi de bu turda kronometrelenmedi.

## 13. Güvenlik riskleri

Kanıtlanmış:

- `QA-SEC-001`. Liste sızıntısı. 20 kayıt, alanlar belirteç, ad, telefon.
- `QA-SEC-002`. `findProject` yalnız 24 karakterlik belirtece bakıyor. QA kaydı bu uçla silindi.

Var olan korumalar: dolap rotaları `auth` arkasında. Doğrulama kuralları var. Throttle var. CSRF meta etiketi silme isteğinde kullanıldı ve 200 döndü. Belirteç `Str::random(24)`, kaba kuvvet için kısa değil. Asıl açık, listelemenin belirteci vermesi.

Yapılmayanlar: `composer audit`, rate limit doyurma, dosya yükleme istismarı, CSRF'siz istek denemesi. Bunlar `NOT_TESTED`.

## 14. Ticarete geçmek için ne bitmeli?

1. Proje listesi ve belirteç sahibe bağlanacak.
2. Altı Y26 çekmece kutusu dikmenin dışına alınacak.
3. Köşedeki ürün, seçilen katalog kimliği olarak kalacak.
4. Render aynı kadrajda, daha yüksek örnek ve çalışan denoise ile yeniden alınacak. Sonuç hâlâ benekliyse yayın metninde fotoğraf denmeyecek.
5. Kapak, çekmece ve 225–785 cm duvarlar boş sahnede tekrarlanacak.
6. Üretim çıktısı ya gerçek bir parça listesi olacak ya da arayüzden üretime hazır ifadesi çıkacak.

## 15. Eksikler hangi sırayla gitmeli?

1. `QA-SEC-001`, `QA-SEC-002`
2. `QA-GEO-001`
3. `QA-CORNER-001`
4. `QA-RENDER-001`
5. `QA-PLACE-001` temiz tekrar
6. Kapak ve çekmece hareket testi
7. `QA-PROD-001` ve `QA-GEO-003`
8. Mobil gerçek cihaz
9. GPU ölçümü
10. Fiyat ve resmi malzeme, ancak yukarıdakiler kapanınca

## Envanter

| Parça | Nerede |
|---|---|
| Laravel 12, PHP 8.3 | `composer.json` |
| Stüdyo arayüzü | `resources/views/wardrobe/index.blade.php` |
| Stüdyo uygulaması | `public/js/wardrobe-studio.js`, 6787 satır |
| Three.js 0.170.0, path tracer 0.0.23 | Blade import map |
| Y26 katalog, 20 modül | `public/data/wardrobe-y26-modules.json` |
| Eski katalog, 25 modül | `public/data/wardrobe-glb-modules.json` |
| Prosedürel tarif, 15 ve 25 | `wardrobe-modules.json`, `wardrobe-cad.json` |
| GLB ve module.json | `public/catalog/y26`, `public/catalog/meemare25` |
| Kayıt | `PublicWardrobeController`, `wardrobe_projects` |
| Malzeme | `materials()`, Kastamonu JSON, görsel `?v=3` |
| Teklif | `quote()`, fiyat hesabı yok |

45 `module.json` sayıldı. Katalog kimlikleri de 45. Eksik eşleşme yok. Y26 `module.json` kimliği büyük harf. Katalog kimliği küçük harf. Stüdyo katalog JSON'unu kullanıyor.

Ölçek notu: 45 kart DOM'a basıldı ve hepsi tıklandı. 500 kart ölçülmedi. Tahmin yok.

## Modül turu

Her kart için Ekle basıldı, HUD genişlik ve yükseklik okundu, tuval kaydedildi, Sil basıldı. 45 kayıt `MEEMARE_MODULE_TEST_MATRIX.csv` ve `test-results/master-qa/functional-log.json`.

HUD genişliği katalogla uyumlu: tek ve köşe dışı dar modüller 45x250, çift ve köşe 90x250. Derinlik kutusu bu okumada yok. Kaynak derinlik Y26'da 600 mm, eski seride katalogla aynı.

Görsel karelerin 45'i de birbirinden farklı dosya. Kadraj ürünü her karede göstermiyor. Yalnız `module-tests/kose_1.jpg` bu turda tek tek incelendi ve L gövdeyi gösteriyor. Kapak yok, raflar açık, seçim çizgisi L. Diğer 44 karenin iç düzeni `NOT_TESTED`.

Kapak açma, çekmece sürme, komşuyla çarpışma ve silince komşunun kayması her modül için `NOT_TESTED`. Silme, bir sonraki kartın HUD'unda toplamın yeniden 45 veya 90 cm olmasıyla dolaylı görüldü. Komşu dizilimi ayrıca ölçülmedi.

## Yerleşim

| Duvar | Bu tur | Sonuç |
|---|---|---|
| 180 cm | 4 ekleme, Dolap 180 cm, görüntüde dört açık raf dolap | PASS, `placement-tests/front-180.jpg` |
| 225–785 cm | Silme, seçim arayüzü gizliyken durdu. Toplamlar birikmiş sahneye ait | BLOCKED |

413 cm satırında Dolap 405 cm, 440 cm satırında da 405 cm yazıyor. 45 cm modül bu artıkları tek başına kapatamaz. Sahne temiz olmadığı için bunu o duvarın kanıtı saymıyorum.

Kapak açılma payı ve çekmece önündeki engel kodda ayrı bir çarpışma hacmi olarak durmuyor. Test de edilmedi.

## Köşe

`kose_1` doğrudan eklenince L görünüyor. İki kol, farklı raf derinlikleri, kapak yok. Ölçü kutusu 90x250. Kaynak kutu 900 x 900 x 2500 mm.

Sağ köşe, iki duvar toplamı, kapak yönü ve çekmece çarpışması bu turda `NOT_TESTED`.

Düz modülün köşede başka GLB'ye dönmesi kodda duruyor. Bu turda temiz bir sol duvar köşesi yeniden kurulmadı. Önceki ürün şikayetiyle aynı fonksiyon.

## Malzeme, kapak, çekmece

Malzeme gruplarının birbirine karışmadığı `NOT_TESTED`.

Kapak ve çekmece hareketi `NOT_TESTED`. Yeni modül `door: none` ile geliyor. Cam veya ahşap seçilmeden açılacak kapak yok.

## Test sınıfları

| Alan | Durum |
|---|---|
| 45 GLB ölçü ve kalınlık | PASS / 6 çarpışma FAIL |
| 45 kartın sahneye girmesi ve HUD ölçüsü | PASS, tek_02 adı şüpheli |
| 45 tuval karesinin ürünü gösterdiği | kose_1 PASS, diğerleri NOT_TESTED |
| 180 cm yerleşim | PASS |
| 225–785 cm yerleşim | BLOCKED |
| Kapak ve çekmece hareketi | NOT_TESTED |
| Malzeme yalıtımı | NOT_TESTED |
| Kayıt, yenileme, QA kaydını silme | PASS |
| Proje listesi yetkisi | FAIL |
| Render, bu turda yeni trace | NOT_TESTED. Kullanıcı görseli FAIL kanıtı |
| GPU performans | NOT_TESTED |
| Gerçek telefon | NOT_TESTED |
| composer audit ve saldırı denemeleri | NOT_TESTED |

Ayrıntılı iş listesi `MEEMARE_BUG_BACKLOG.csv`. Puanlar `MEEMARE_PROFESSIONALISM_SCORECARD.md`. Yayın kararı `MEEMARE_RELEASE_READINESS.md`.
