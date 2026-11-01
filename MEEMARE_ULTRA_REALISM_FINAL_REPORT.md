# MEEMARE 3D Studio — Ultra gerçekçilik raporu

Tarih: 1 Kasım 2026. Canlıya gönderilmedi.

Geri dönüş noktası, yalnızca bu makinede: `qa-ultra-realism-baseline-2026-11-01` → `479b172`. Etiket uzak depoya gönderilmedi.

Bu rapor Hasselblad veya Sony A7R V kalitesi iddia etmez. Stüdyo gerçek zamanlı WebGL’dir. `Render al` aynı three-gpu-pathtracer 0.0.23 yolunu kullanır. Bu geçişte yeni bir path trace görüntüsü alınmadı.

## 1. Mevcut kalite

Katalog: 25 eski GLB modülü (`public/data/wardrobe-glb-modules.json`) ve 20 Y26 modülü. Toplam 45 kayıt. Dosyanın katalogda olması, 45 modülün bu geçişte tek tek görüldüğü anlamına gelmez.

Referans olarak `y26_tek_01` ve `y26_cift_01` giriş yapılmış tarayıcıda, kayıtsız `/dolap/tasarla` sayfasında açıldı. Müşteri projesine kaydedilmedi.

Önceki görüntüler: `test-results/ultra-realism/before/`
Sonraki görüntüler: `test-results/ultra-realism/after/`

Önceki cephe ve 45° görüntülerde dolap düz, sarımsı ve oluklu bir bilgisayar modeli gibi duruyordu. Çekmece kapalıyken ince bant, açıkken kutuydu ama ışık yüzeyi eziyordu. Füme cam koyu ve yumuşak bir levhaydı.

## 2. Görülen sorunlar ve kök neden

- Tam cephede 18 mm raf çizgi gibi durur. Yükseklik 2500 mm, kamera dolabın tamamını kadrajlar. 18 mm, kadrajın çok küçük bir dilimidir. Kalınlık artırılmadı.
- Ahşap yüzey oluklu duruyordu. Albedo JPEG’den üretilen normal haritası (`normalScale` 0.22) damarı oyuk gibi yükseltiyordu. Bu, ölçülmüş bir yüzey değil, türetilmiş kabartmaydı.
- Dolgu ışığı (0.55) ve yarıküre (0.95) temas gölgesini yıkıyordu. Gölge yarıçapı 5, 18 mm rafın gölgesini dağıtıyordu. Işık rengi sarıya kayıyordu (`0xfff4ea`).
- Çekmece önünün üst ve alt yüzü gövdeyle aynı dokudaydı. Kapalıyken aralık okunmuyordu.
- 45° çekimde tek dolap çoğunlukla yan panel gösterir. Sol 45° ve sağ 45° bu sahnede aynı dosyayı üretti: dolap sol duvara yakınsa 70 cm’den dar tarafa kamera geçmez, istenen taraf diğer açık tarafa döner.
- Füme cam (`#6e6a66`, transmission 0.88, thickness 0) path tracer’ın siyah hacim hatasına düşmemesi için ince film yolunda duruyor. Rasterde iç düzen seçiliyor ama cam hâlâ koyu. Kalınlık 0’ın üstüne çıkarılmadı.
- Kıyafetler düşük çokgen aksesuar. Gömlek ve ceket fotoğraf kumaşı değil.
- Yeşil seçim çerçevesi ürün fotoğrafı değil, düzenleme işaretidir. Opaklığı 0.72’den 0.38’e indirildi. Seçili dolapta hâlâ görünür.

## 3. Yapılan kod

`public/js/wardrobe-studio.js` (`?v=160`) ve `resources/views/wardrobe/index.blade.php`.

- Desenli dekorlarda normal haritası artık uygulanmıyor. Albedo JPEG ve hafif roughness haritası duruyor. Düz renkler (Latte, Lake Beyaz, Buz Gri) düz boya olarak kaldı.
- Ahşap parlaklığı düşürüldü: roughness 0.62, specular 0.28, ortam yansıması 0.16.
- Tasarım ışığı nötrleştirildi. Yarıküre 0.62, ana ışık 0.78, dolgu 0.22, iç dolgu 0.20, zemin yansıması 0.12. Gölge yarıçapı 1.6, normal bias 0.12, gölge yoğunluğu 0.74.
- Çekmece önünün üst ve alt yüzü kenar bandı malzemesine ayrıldı. Ön kalınlık 18 mm kaldı.
- Sunum kadrajı biraz yaklaştı (mesafe katsayısı 0.62 → 0.50, görüş açısı 30°). 45° sapma 0.74 radyan.
- Path trace ortamı sıcak bejden nötr gri-bej’e alındı. Ortam şiddeti 1.05’ten 0.72’ye indi. Camın thickness değeri 0, attenuationDistance Infinity.

Raf, yan, üst, alt ve çekmece önü kalınlığı değiştirilmedi. 48000 şiddetindeki iç ampul geri konmadı.

## 4. Geometri

`y26_tek_01` ham GLB, bu oturumda ölçüldü. Ayrıntı: `test-results/ultra-realism/geometry-tests/y26_tek_01-thickness.txt`.

Yan, üst, alt ve üç raf 18.00 mm. Arkalık 8.00 mm. Çekmece önü 18.00 mm, yüksekliği 145 mm. Kaynak çekmece kutusu dolu prizmadır. Stüdyo bunu 16 mm yan, 16 mm arka ve 8 mm tabanla değiştirir.

45 modülün tamamının kalınlığı bu oturumda yeniden taranmadı. Köşe modülleri sahnede hâlâ parametrik gövde kullanır. Köşe GLB dosyası katalogdadır, sahneye o mesh çizilmez.

## 5. Malzeme ve Kastamonu

Resmî Kastamonu Entegre taraması yapılmadı. Dokular temsili. `test-results/ultra-realism/material-tests/NOT-OFFICIAL.txt`.

Kum Taşı gövde, Vizon kapak, Kaşmir raf, Latte çekmece ve beyaz arkalık bu oturumda tıklanarak doğrulanmadı. “Tümüne uygula” bu oturumda yeniden test edilmedi.

## 6. Cam

`y26_tek_01-glass-front.jpg` önce ve sonra incelendi. Kapalı füme camın ardında raf LED’i ve askılık seçiliyor. Çerçeve ince bir çizgi. Cam hâlâ koyu ve yumuşak. Aynalı kapak bu oturumda ayrı test edilmedi. Şeffaf, bronz ve füme path trace karşılaştırması bu oturumda yeniden üretilmedi.

## 7. Raf ve çekmece

Sonraki 45° tek dolap görüntüsünde raf ön kenarı ince bir bant olarak okunuyor. Cephede hâlâ incedir. Bu, 18 mm’nin tam boy kadrajdaki halidir.

Çekmece açılınca `y26_tek_01-drawers-right45.jpg` kutu yanını, metal rayı ve kulpu gösteriyor. Kapalı cephede önler hâlâ bant gibi. Aralık, tam boy karede birkaç pikseldir.

## 8. Kamera

Cephe görüş açısı 30°. Üst görünüm 42°. Dolap kadrajda eskisinden daha büyük. Sol 45° ve sağ 45° dosyaları bayt bayt aynı: tek dolap duvara yakın durunca kamera açık tarafa dönüyor. İki ayrı 45° kanıtı yoktur.

## 9. Aksesuar

Askılıkta gömlek ve ceket, raflarda kutu, katlı tişört ve ayakkabı duruyor. Bunlar basitleştirilmiş modeller. %30, %60 ve %85 doluluk ayrı ayrı kurulmadı. Yüksek kaliteli kıyafet olarak işaretlenmez.

## 10. İki mod

Tasarım modu: sahnedeki gerçek zamanlı ışık, gölge haritası ve orbit kamera. Ultra mod: `Render al`. Bu geçişte ultra modun ortam rengi nötrleştirildi. Yeni bir yüksek çözünürlüklü PNG üretilmedi. Süre ve GPU örneği ölçülmedi.

## 11. Puan — yalnız görülen iki modül

`y26_tek_01` ve `y26_cift_01`, sonraki raster görüntülere göre. 9/10 hedefi tutmadı.

| Ölçüt | Puan |
| --- | --- |
| Geometri | 8 |
| Panel kalınlığı | 8 |
| Raf | 6 |
| Çekmece | 7 |
| Kapak | 6 |
| Ahşap | 6 |
| Cam | 4 |
| Malzeme doğruluğu | 4 |
| Işık | 6 |
| Gölge | 6 |
| Derinlik | 6 |
| Aksesuar | 4 |
| Genel fotoğraf gerçekçiliği | 6 |

Diğer 43 modüle puan verilmedi. Görülmediler.

## 12. Yapılmayan testler

Şunlar bu oturumda çalıştırılmadı: 45 modülün beş açısı, 270 / 225 / 315 cm dizilim, köşe birleşimi, sürükle-bırak, kaydet, yenile, geri-ileri, mobil, FPS, RAM, GPU, path trace önce/sonra.

Mobil notu: `test-results/ultra-realism/mobile-tests/NOT-RUN.txt`.

## 13. Canlıya çıkış

Çıkmamalı. İki referans modül tasarım aracı olarak daha okunaklı. Profesyonel mobilya fotoğrafı değil. Müşteri projesine yazılmadı. `git push` yapılmadı.
