# Referans dolap v2 — y26_cift_01

Bu dosya onay belgesi değildir. `y26_cift_01` tamamlandı olarak işaretlenmedi. Canlıya push yapılmadı. Diğer 44 modüle geçilmedi.

Dış ölçü duruyor: 900 × 2500 × 600 mm. Panel 18 mm. Kalınlık artırılmadı. Kayıt sistemi aynı: müşteri bilgisi olmayan stüdyoda Kaydet, “Önce müşteri bilgisi gerekli.” uyarısı verdi ve adres çubuğu boş kaldı.

Görüntüler: `test-results/ultra-realism/reference-v2/`. Hepsi açılıp incelendi. Sol 45 ve sağ 45 dosya özeti farklı.

## Geometri

Sağ bölmeye, yalnızca bu modülde, üç ara raf eklendi. Adları `RAF_SAG_1..3`. Kalınlık 18 mm. Dikmenin sağ yüzüne ve sağ yan panele 1 mm payla oturuyorlar. Derinlik, alttaki tam boy rafın derinliği. Üst ve alt tam boy rafların arasındaki boşluk dört aralığa bölünüyor. Aralık 280 mm’nin altına düşerse raf üretilmiyor. Açık cephede üç raf dikmeyle, askılıkla ve çekmecelerle çakışmıyor. Raf pimi veya bağlantı metali yok; raflar panele dayanıyor.

Askılık sol bölmede, Ø25 mm.

Çekmece tam açıldığında ön panel gövdenin önüne çıkıyor. Eğik makro `04-drawer-open.jpg` kutu yanını, tabanı, arka paneli ve ince metal rayı gösteriyor. Ray 8 × 13 mm’lik bir kutu. Ticari ray mekanizması değil. Sabit gövde rayı da aynı sınıfta bir çubuk.

Kapakta iki gizli menteşe bileziği ve kol var. Kenar bandı raf makrosunda ön yüzde ayrı bir şerit olarak duruyor. LED şeridi hâlâ patlak beyaz.

## Ahşap

Katalog JPEG’i albedo olarak duruyor. Bu dosya düz bir fotoğraf; yüzey normali ve pürüzlülük içermiyor. Ekranda bu yüzden baskı gibi duruyor.

Albedo’nun üstüne agresif kabartma üretilmedi. Yerine ambientCG Wood094’ün CC0 normal ve roughness haritaları bağlandı (`public/textures/wardrobe/`). Wood094 yatay huş / akçaağaç figürüdür. Damar dik okunsun diye harita 90° döndürüldü ve tekrar 0.4’e ayarlandı. Renk haritası kullanılmadı. Desenli ahşap `MeshStandardMaterial` kaldı; path tracer’da siyaha boyayan clearcoat geri gelmedi.

Yakın plan `06-wood.jpg` hâlâ katalog baskısı gibi. Wood094 damarı meşe fotoğrafıyla örtüşmüyor. Kastamonu Entegre görselleri temsili. Resmî tarama değil.

Kenar bandı düz, haritasız, biraz daha koyu.

## Cam

Raster yakın plan `07-glass.jpg` kapak birleşimini, metal kulpları ve sağdaki iç rafı gösteriyor. Cam yüzeyinin kendi yansıması bu karede seçilmiyor.

Path karesi `07-glass-path.png` bütün dolabı yeniden kadrajlıyor. Ultra render, makro kamerayı korumuyor. 900 × 900, 10 geçiş, 40 sample, 4 yansıma, 5 geçirgen yansıma, denoise açık, 15,7 saniye. Füme cam iç mekânı gösteriyor; levha siyah değil. Geçirgenlik ayarı değiştirilmedi: `#9c9792`, transmission 0.8, roughness 0.055, thickness 0. Kare benekli ve karanlık. Cam, alüminyum çerçeve ve iç mekân ürün fotoğrafı gibi ayrılmıyor.

## Aksesuarlar

Ayrı kütüphane: `public/models/wardrobe/library/`.

| Dosya | Ne |
|---|---|
| `gomlek.glb` | Askı çubuğuna asılan gömlek |
| `ceket.glb` | Blazer |
| `tisort.glb` | Katlanmış tişört |
| `ayakkabi.glb` | Ayakkabı çifti |
| `canta.glb` | Çanta |
| `kutu.glb` | Saklama kutusu |

Blender ile modellenmiş basit gövdeler. Fotogrametri değil, kumaş simülasyonu değil. Eski Grab3D prosedürel dosyalar geri konmadı. Poly Haven apparel listesinde dolaba uygun gömlek, ceket veya ayakkabı yok.

`01` ve `08` içinde iki düz gömlek, koyu bir ceket levhası, rafta kutu ve alt rafta beyaz bir çift görünüyor. Kumaş katı, dikiş ve gerçek ayakkabı formu yok. Profesyonel aksesuar kabul edilmedi.

## Path tracing

Önceki ölçüm, aynı makine, `test-results/ultra-realism/reference/render-stats.json`:

- 1050 × 1280
- 32 geçiş, 128 sample
- 6 yansıma, 8 geçirgen yansıma
- 122 939 ms
- yığın yaklaşık 65 MB

1920 piksel / 80 geçiş o turda bitmedi.

Bu turda tracer sahneyi sample döngüsünden önce bir kez kuruyor. Döngü sırasında sahne değişmiyor. BVH her sample’da yeniden kurulmuyor. Darboğaz sample birikimi. three-gpu-pathtracer 0.0.23 WebGL üzerinde. OIDN bu sürümde yok; OIDN yolu WebGPU istiyor. Uyumlu seçenek kütüphanenin kendi `DenoiseMaterial` filtresi (sigma 1.6, kSigma 1.5, threshold 0.07).

Aynı sahnede ölçülen karşılaştırmalar:

| Kare | Çözünürlük | Sample | Yansıma | Geçirgen | Denoise | Süre |
|---|---|---|---|---|---|---|
| `10-compare-bounces6.png` | 1000 × 1000 | 32 | 6 | 8 | kapalı | 12 886 ms |
| `10-compare-bounces4-denoise.png` | 1000 × 1000 | 32 | 4 | 5 | açık | 10 653 ms |
| `10-path.png` | 1050 × 1100 | 64 | 4 | 5 | açık | 24 644 ms |
| `07-glass-path.png` | 900 × 900 | 40 | 4 | 5 | açık | 15 729 ms |

32 sample’da 6 yansıma 402 ms/sample, 4 yansıma ve denoise 333 ms/sample. Süre kazancı küçük. Yan yana bakınca (`12-denoise-compare.jpg`) 4 yansımalı denoise karesi daha temiz değil; arkalık ve zemin ikisinde de benekli. 64 sample’lık `10-path.png` daha sakin, benek hâlâ duruyor.

Önceki 128 sample / 6 yansıma turu 960 ms/sample idi (1050 × 1280). Bu turun 6 yansımalı 1000 × 1000 ölçümü 402 ms/sample. Piksel alanına oranlayınca yaklaşık 1,8 kat hız. Desenli ahşapta malzeme paylaşımı ve 4 yansıma varsayılanı bu farkın içinde. Kalite, sample sayısını artırarak alınmadı; alınan hız da beneği kabul edilir seviyeye indimedi.

Tasarım modu bu turda 61 kare/sn ölçüldü. Path trace tasarım döngüsünün dışında.

## Kareler

1. `01-front-open.jpg` — açık cephe. Sağda üç raf, solda askılık ve kıyafet, altta iki çekmece.
2. `02-right45.jpg` — sağ 45. Sol 45’ten farklı.
3. `03-left45.jpg` — sol 45. Kapaklar ve sağ raf sırası görünüyor.
4. `04-drawer-open.jpg` — açık çekmece. Kutu yan, taban, arka ve ince ray okunuyor. İlk kadraj duvarın içindeydi; bu kare düzeltilmiş kamera.
5. `05-shelf-edge.jpg` — 18 mm raf kenarı ve kenar bandı. LED patlıyor.
6. `06-wood.jpg` — ahşap yakın plan. Baskı hissi duruyor.
7. `07-glass.jpg` ve `07-glass-path.png` — raster yakın plan ve path. Cam geçirgen, yüzey yansıması zayıf, path karesi benekli.
8. `08-accessories.jpg` — kütüphane modelleri dolabın içinde. Basit geometri.
9. `09-raster.jpg` — standart raster, perspektif.
10. `10-path.png` — 64 sample, denoise’lı path.

`11-front-vs-path.jpg` bir ile onu yan yana koyuyor. Solda düz raster, sağda daha koyu kenar, içte sıcak ışık ve kalan benek. `12-denoise-compare.jpg` eşit sample’da 6 yansıma ile 4 yansıma + denoise.

## Kabul

| Koşul | Sonuç |
|---|---|
| Gerekli raflar doğru geometride | Üç sağ raf yerinde, çakışma yok. Pim yok. |
| Çekmece kutusu ve raylar | Kutu ve ince ray makroda görünüyor. Ticari ray değil. |
| Ahşap baskı gibi durmuyor | Duruyor. |
| Cam yüzey gerçekçi okunuyor | Geçirgenlik bozulmadı. Yüzey okuması zayıf. |
| Aksesuarlar profesyonel | Değil. |
| Benek kabul edilebilir seviyede | Değil. |
| Süre ve kalite arasında ölçülmüş iyileşme | Süre iyileşti. Eşit sample’da denoise kaliteyi taşımadı. |
| Ölçü ve kayıt | Ölçü duruyor. Kayıtsız Kaydet proje yazmadı. |

Referans dolap tamamlanmış değildir.
