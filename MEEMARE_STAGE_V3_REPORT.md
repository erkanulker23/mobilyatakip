# Aşama v3 — y26_cift_01

Bu dosya onay belgesi değildir. Referans dolap profesyonel ürün fotoğrafı olarak tamamlanmadı. Canlıya push yapılmadı. Diğer 44 modül taranmadı. Ölçü 900 × 2500 × 600 mm, panel 18 mm. Cam kalınlığı 0 bırakıldı. Kayıtsız Kaydet yine “Önce müşteri bilgisi gerekli.” dedi ve adres çubuğu boş kaldı.

Görüntüler: `test-results/ultra-realism/stage-v3/`. Süreler `stats.json`.

## 1. Ahşap

Wood094 normal ve roughness, katalog meşe JPEG’inden başka bir ağaçtır. Bu eşleşme varsayılan olmaktan çıktı. Aynı kamerada üç hali çekildi.

| Kare | Ne |
|---|---|
| `wood-01-mismatch.jpg` | Katalog albedo + Wood094 normal/roughness |
| `wood-02-catalog.jpg` | Yalnız katalog albedo, yabancı harita yok |
| `wood-03-oak.jpg` | Poly Haven `oak_veneer_01`: albedo, normal ve roughness aynı taramadan |
| `wood-04-oak-shelf.jpg` | Aynı set, raf kenarı |

Uyumsuz haritayı kaldırmak baskı hissini değiştirmedi. `wood-01` ve `wood-02` aynı düz JPEG’e benziyor. Asıl fark, üçüncü sette: damar dik, kapı ve rafta aynı ölçek, kenar bandı düz renk.

`oak_veneer_01` CC0, 1830 × 1830 mm. UV metre cinsinden, tekrar `1/1.83`. Normal ölçeği 0.12; melamin gözenek gibi kabartılmadı. Kenar bandı albedo ortalamasından `#947455`, pürüzlülük 0.58, haritasız. Bu bir kaplama taramasıdır. Kastamonu Entegre dekoru değildir. Katalog JPEG’i resmi doku diye durmuyor.

Resmi melamin için hâlâ dışarıdan şu tarama gerekir: aynı dekora ait albedo, normal ve roughness; panel ölçüsü (tipik 2800 × 2070 mm) veya karo tekrar ölçüsü; mat melamin için 0.05 mm altı kabartma; ABS bant rengi aynı dekordan ölçülmüş. Kaplama taramasını katalog JPEG’inin üstüne normal diye yapıştırmak çözüm sayılmadı.

Kod: `public/js/wardrobe-studio.js` içinde `woodStudyMode`, `ensureOakMaps`, `assignDecorUv`, `edgeBandMaterial`. Referans modül `representative` ile bu seti kullanır. Diğer modüllerin malzemesine Wood094 normali artık bağlanmıyor. Lisans: `public/textures/wardrobe/LICENSE.txt`.

## 2. Cam kadrajı

Kök neden `takeRender` içindeki `frameRenderCamera`. Render, o anki kamerayı silip bütün dolabı kadrajlıyordu. `window.__traceReframe === true` olmadıkça pozisyon, hedef ve görüş açısı duruyor. Çözünürlük en-boy oranı bu kadrajdan geliyor.

Aynı makro:

| Kare | Kadraj | Ortam | Çözünürlük | Örnek | Süre |
|---|---|---|---|---|---|
| `glass-01-raster.jpg` | makro | raster oda | tuval | — | — |
| `glass-02-path-gradient.png` | aynı makro, `keepFrame: true` | gradyan | 1100 × 848 | 32 | 10 978 ms |
| `glass-03-path-hdri.png` | aynı makro, `keepFrame: true` | Poly Haven `studio_small_09` | 1100 × 848 | 32 | 10 775 ms |
| `glass-04-path-reframe.png` | bütün dolap, `keepFrame: false` | aynı HDRI | 900 × 900 | 24 | 7 833 ms |

Rasterde alüminyum kayıt, kulp, füme cam ve arkadaki raf birlikte duruyor. Path makro aynı kadrajda. Eski hata `glass-04`: kamera dolaba geri dönmüş.

Path makrosu karanlık. Raf çizgisi seçiliyor, iç hacim okunmuyor. HDRI, gradyana göre ayırt edilir bir oda yansıması üretmedi. Metal kulp ışık alıyor; cam yüzeyinde stüdyo yansıması seçilmiyor. Bu, kadraj hatası değil. 0.0.23 path tracer’da doğrudan ışık camın gölge ışınında içeriyi aydınlatmıyor; raster içeriyi gösteriyor çünkü bu gölgeyi izlemiyor. Kalınlık bu yüzden değiştirilmedi. `glassGuard: 0`.

HDRI dosyası: `public/textures/wardrobe/studio_small_09_1k.hdr`. Yükleme: `studioHdri()`.

## 3. Benek

Aynı açık cephe, aynı kamera, 960 × 600, 32 örnek, 4 yansıma, 5 geçirgen yansıma. BVH her karede yaklaşık 70–110 ms; sample döngüsünde yeniden kurulmuyor. Örnek başına süre yaklaşık 197 ms. Süreler birbirine yakın; fark görüntüde.

| Kare | Işık | Denoise | Süre | Görüntü |
|---|---|---|---|---|
| `noise-01-led-scene-key.png` | LED yoğunluğu 4.2 + emissive anahtar düzlem | kapalı | 6 372 ms | Arkalık ve raflar benekli |
| `noise-02-led-off.png` | LED kapalı, emissive anahtar yok | kapalı | 6 291 ms | Damar duruyor, benek belirgin değil |
| `noise-03-led-soft.png` | LED yoğunluğu 0.42 | kapalı | 6 302 ms | Üst rafta benek sürüyor |
| `noise-04-denoise-sigma5.png` | yumuşak LED | GPU sigma 5 | 6 275 ms | Yumuşak LED’e yakın |
| `noise-05-denoise-sigma1.png` | yumuşak LED | GPU sigma 1.6 | 6 347 ms | Sigma 5’ten ayırt edilir değil |

0.0.23 belgesi: emissive malzemeler var, MIS kullanmıyor. Sahadaki `DirectionalLight` MIS alıyor. Eski anahtar ışık ve LED şeritleri emissive yüzeydi; benek oradan. Aynı örnek sayısında ışığı kapatmak beneği düşürdü. Denoise, yumuşak LED’in kalan beneğini temizlemedi. Daha fazla sample ile gizlenmedi.

Varsayılan path artık emissive anahtar düzlem kurmuyor ve LED’i 0.42 ile sınırlıyor. LED’li kare hâlâ tamamen temiz değil. OIDN bu WebGL sürümünde yok; WebGPU sürümüne geçilmedi, küçük sahnede kazanç kanıtlanmadı.

Kod: `takeRender` içinde `ledMode`, `__traceKey`, `filterGlossyFactor`.

## 4. Gömlek

Blender cloth ile bir kumaş askı çubuğunun üzerinden düşürüldü. Önizleme `shirt-sim-preview.png`. Stüdyo: `shirt-01-front.jpg`, `shirt-02-side.jpg`.

Gövde kumaşı düşüyor ve yakada bir açıklık var. Kollar ayrı düz parçalar; omuza dikili değil. Yaka kanadı, pat, dikiş ve kumaş dokusu yok. Bu bir gömlek değil. Profesyonel kabul edilmedi. Eski tüp model `public/models/wardrobe/library/gomlek-tube.glb` olarak duruyor. Yeni dosya `gomlek.glb`. Betik: `scripts/build-shirt.py`.

Gerçek gömlek için kalıp dikimli bir sim (yakalık, kol evi, manşet) veya lisanslı bir giysi taraması gerekir. Diğer aksesuarlar yeniden üretilmedi.

## Dosyalar

- `public/js/wardrobe-studio.js` — ahşap seti, UV, kenar bandı, kamera koruma, LED ve anahtar ışık, HDRI
- `resources/views/wardrobe/index.blade.php` — betik `?v=178`
- `public/textures/wardrobe/` — meşe PBR, HDRI, lisans
- `public/models/wardrobe/library/gomlek.glb` — cloth denemesi
- `scripts/build-shirt.py`, `scripts/qa-stage-v3.mjs`

## Kabul

Ahşapta uyumsuz harita kalktı ve aynı taramadan bir PBR seti bağlandı. Bu set resmi Kastamonu melamini değil. Cam kadrajı path’te korunuyor; camın path yansıması ve iç aydınlığı düzelmedi. Benek, emissive ışıktan geliyor ve LED kapalıyken aynı örnekte azalıyor; LED açıkken denoise yetmedi. Gömlek profesyonel değil. Referans dolap tamamlanmadı.
