# Meemare gerçekçilik iyileştirmesi

Tarih: 1 Kasım 2026. Kapsam: `y26_tek_01` ve `y26_cift_01`. Canlıya gönderilmedi.

Bu turda kod değişti ve aynı stüdyo kamerasıyla yeniden render alındı. Sonuç profesyonel ürün fotoğrafı değildir. Gerçek zamanlı WebGL görüntüsüdür. 18 mm raf kalınlığı değiştirilmedi.

## Değiştirilen dosyalar

- `public/js/wardrobe-studio.js`
- `resources/views/wardrobe/index.blade.php` — betik sürümü `v=154`

Müşteri projelerine yazılmadı. Kaydet’e basılmadı.

## Düzeltilen geometri

Y26 `CEKMECE_KUTU` dolu prizması sahneden çıkarılıyor. Yerine, kutunun dış ölçüsü büyümeden, şu parçalar kuruluyor:

| Parça | Kalınlık |
| --- | --- |
| Çekmece önü | 18 mm, modeldeki mevcut parça |
| Sağ ve sol yan | 16 mm |
| Arka | 16 mm |
| Taban | 8 mm, arkalıkla aynı |
| Ray | 12 × 8 mm metal çubuk |

Yan ve taban 18 mm yapılmadı. Ön, yan, arka, taban ve ray aynı kayan gruptadır. Çekmece açılınca hepsi birlikte 26 cm’ye kadar öne gelir.

Üst üste binen önler arasında 3 mm’den dar boşluk varsa geometri Y ekseninde yarım fark kadar ayrılır. Bu iki modelde önler zaten yaklaşık 10 mm aralıklıydı, kalınlık değişmedi.

Raflar 18 mm kaldı. Ön yüz ayrı kenar bant malzemesi, alt yüz daha koyu ve pürüzlü bir malzeme aldı. Üst yüz ahşap dokuda kaldı. Pah 0,45 mm’den 0,8 mm’ye çıkarıldı ve kutunun ana yüz düzlemlerinin içinde tutuldu. Önden bakınca raf, 18 mm’den kalın görünmesi için şişirilmedi.

Parça adlarındaki büyük `I` harfi Türkçe yerelde `ı` oluyordu. Bu yüzden askılık metal sayılmıyor, arkalık da gövdeyle aynı role düşüyordu. Eşleştirme artık yerelden bağımsız.

## Malzeme

Gövde, raf, kapak, çekmece ve arkalık aynı düz renkle boyanıp bırakılmıyor.

- Albedo: katalogdaki dekor JPEG’i
- Pürüzlülük ve normal: bu JPEG’ten türetilen 512×512 harita
- Tekrar: yaklaşık 1 metre / 1 tekrar
- UV: mevcut panel UV’si
- Raf önü: normal haritasız, pürüzlülüğü 0,86 olan kenar bant
- Raf altı: aynı banttan daha koyu yüzey
- Damar yönü: panel UV’si boy yönünü takip eder
- Askılık: metal, dekor haritası yok

Kastamonu’nun resmî tarama dokusu yok. Panelde şu not var: dekor görseli temsili dijital dokudur. Yüksek parlak dekorlar bu türetilmiş haritaları kullanmaz.

## Render

Mevcut ürün ışığı duruyor: yumuşak gölge, önden-üstten ana ışık, dolap içine bakan zayıf dolgu. Gölge yoğunluğu 0,42’den 0,58’e alındı. Dolap içindeki 48000 şiddetli nokta ışık geri konulmadı. Füme cam ince film ayarı duruyor: kalınlık 0.

Doğrulanan görüntülerde raf altı ve çekmece aralığında temas gölgesi okunuyor. İç hacim siyah değil. Yüzey ayna gibi parlamıyor. Kamera stüdyonun mevcut ön ve 45 derece kadrajı. Bu bir path-trace ürün çekimi değil.

## Önce / sonra

Aynı oda, aynı Naturel Meşe A402, aynı ön ve sağ 45 kamera düğmeleri.

| Görünüş | Dosya |
| --- | --- |
| Tek, eski ön | `test-results/realism-audit/improve/before-tek-front.jpg` |
| Tek, yeni ön | `test-results/realism-audit/improve/after-tek-front.png` |
| Tek, eski 45° | `test-results/realism-audit/improve/before-tek-45.jpg` |
| Tek, yeni 45° | `test-results/realism-audit/improve/after-tek-45.png` |
| Çift, eski ön | `test-results/realism-audit/improve/before-cift-front.jpg` |
| Çift, yeni ön | `test-results/realism-audit/improve/after-cift-front.png` |
| Çift, eski 45° | `test-results/realism-audit/improve/before-cift-45.jpg` |
| Çift, yeni 45° | `test-results/realism-audit/improve/after-cift-45.png` |
| Raf makro | `test-results/realism-audit/improve/after-tek-shelf-macro.png` |
| Çekmece makro | `test-results/realism-audit/improve/after-tek-drawer-macro.png` |
| Kapak açık | `test-results/realism-audit/improve/after-cift-45.png` |
| Aksesuarlı | `test-results/realism-audit/improve/after-tek-front.png` ve `after-cift-front.png` |

Gözle görülen fark: yan ve kapakta damar, rafta üst yüzey ile ön dudak, çekmece önleri arasında gölgeli derz, askılıkta gömlek ve ceket, üst rafta kutu, alt rafta katlı tişört ve kazak, ayakkabı. 45 derece çekimde kapak, raf ve çekmece birer hacim.

Önden tam kadrajda 18 mm raf hâlâ ince bir banttır. Bu, kalınlığın değiştirilmemesinin sonucudur.

## Aksesuar

Kullanılanlar: `gomlek.glb`, `jacket.glb`, `t-shirt.glb`, `kazak.glb`, `heels.glb`, `jewelry-box.glb`.

Konulmayanlar: `clothes-rack.glb` ikinci bir ayaklık getirir. `hoodie.glb` bu örnek yerleşime alınmadı.

Konum, modeldeki raf ve `ASKILIK` kutusundan okunur. Gömlek ve ceket askılığın üstünden asılır. Kutu en üst rafın üstündedir. Katlı tişört ve kazak bir alt raftadır. Ayakkabı en alt raftadır. Ölçü, sarmalayıcının metre uzayındadır. Böylece kıyafet oda boyutuna fırlamaz.

`gomlek` ve `jacket` kendi askı ayağını da taşır. Dolap borusunun yanında bu ayak da görünür. Ayrı bir vitrin askılığı değildir. Düşük poly katalog modeli olarak kabul edildi.

## Yeni kalite puanı

Yalnızca bu iki modül, bu oturumun raster görüntülerine göre. Önceki denetim genel notu 4/10 idi.

| Başlık | Önce | Şimdi |
| --- | --- | --- |
| Çekmece geometrisi | 2 | 7 |
| Rafın hacmi | 3 | 6 |
| Malzeme | 3 | 6 |
| Işık ve gölge | 4 | 6 |
| Aksesuar | 1 | 6 |
| Genel, fotoğraf hedefi | 4 | 6 |

6, mobilya okunan bir stüdyo modeli demektir. Katalog çekimi demek değildir. Resmî Kastamonu eşleşmesi ve path-trace karşılaştırması yapılmadı.

## Performans

FPS ölçülmedi. Sayı yazılmıyor.

`y26_tek_01` sahnede, kapaklar ve aksesuarlar dahil:

- Dolap gövdesi, raf, çekmece parçaları ve kapaklar: 45 mesh, yaklaşık 850 üçgen
- Aksesuarlar: 9 mesh, yaklaşık 100 bin üçgen

Asıl yük aksesuar GLB’lerindedir. İçi boş çekmece, dolu prizmaya göre çekmece başına birkaç düzine üçgen ekler. Her kullanılan dekor için bir kez 512×512 pürüzlülük ve normal haritası üretilir.

## Diğer 43 modül

Ortak yol `placeGlb` içindedir. Köşe dışı her GLB aynı fırınlama, boyama, çekmece ve giydirme adımından geçer.

- Adında `CEKMECE_KUTU` olan Y26 kutuları 16 / 16 / 8 mm kutuya döner.
- Eski modellerde kutu yoksa mevcut ön, taban ve arka durur. Stüdyo onlara 12 mm yan eklemeye devam eder.
- Raf adı `raf` olan her panonun önü ve altı ayrılır. Kalınlık 18 mm kalır.
- `mod.props === false` değilse ve raf veya askılık varsa aksesuar konur.
- Köşe modülleri bu GLB yoluna girmez. Onlar parametrik gövdeyle çizilir.
- Y26 06–10 kartlarında katalog fotoğrafı yoktur. Kart görselleri bu turda değişmedi.

Bu sistem iki örnekte görüldü. Kalan 43 modülün her biri için ayrı ekran görüntüsü alınmadı.
