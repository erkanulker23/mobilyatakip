# Meemare görsel kalite — v4

Tarih: 1 Kasım 2026. Kapsam: yalnızca `y26_cift_01`. Canlıya gönderilmedi.

Bu tur bir fotoğraf stüdyosu sonucu değildir. Katalog JPEG’inin kapak ortasında tekrar etmesi kalktı, çekmece kutusu açılınca okunuyor, path trace ahşabı siyaha düşürmedi. Profesyonel mobilya fotoğrafı eşiği tutulmadı.

Karşılaştırma, aynı modülün bir önceki stüdyo karesiyledir: `test-results/ultra-realism/reference/`. Üretici kataloğundan bir fotoğraf depoya kopyalanmadı. Malzeme referansı ambientCG Wood 094, CC0 1.0. Bu, Kastamonu Entegre’nin resmî dekor taraması değildir. Seçilen dekorun ortalama rengi taramayı boyar.

## Ne değişti

- `y26_cift_01` gövde, kapak, raf, arkalık ve çekmece artık katalog JPEG’ini albedo diye tekrarlamıyor. Renk, normal ve roughness aynı Wood 094 taramasından. Karo 2 m. Damar, dikey panoda yükseklik boyunca.
- Kenar bandı düz ve taramadan biraz koyu. Displacement uygulanmadı: paneller tek yüz, bölmek path tracer’ı şişirirdi. Mikro detay normal ölçeği 0.16.
- Cam malzemesinin path tracer kuralı duruyor: `thickness = 0`, `attenuationDistance = Infinity`. Render öncesi bu kural bozulmuşsa geri yazılıyor. Bu ölçümde `glassGuard` 0 kaldı. Kapak camının kenarına 1,8 mm’lik bir kenar şeridi eklendi.
- Açık çekmece kutusu yan, arka ve tabandan kuruluyor. Ray, tek çubuk yerine altlı üstlü iki metal çubuk. Askılık uçlarına 18 mm’lik burç eklendi. 18 mm gövde ve raf kalınlığına dokunulmadı.
- Path trace varsayılanı 12 geçiş, süre tavanı 150 sn. Denoise duruyor. Ölçülen kapalı cephe 20,6 sn sürdü. Önceki referans path 123 sn ve 128 sample idi.

Ortak etki: menteşeli cam kapağın kenar şeridi bütün cam kapılarda var. Tasarım sahnesinin ortam şiddeti 0,18’den 0,46’ya alındı. Bu, yalnızca `y26_cift_01` dosyasına kilitli değil.

## Performans

Kapalı cephe, `test-results/visual-quality-v4/render-stats.json`:

| Ölçü | Değer |
| --- | --- |
| Çözünürlük | 1050 × 1280 |
| Süre | 20575 ms |
| Sample | 48 |
| Geçiş | 12 |
| Sample başına | 429 ms |
| BVH | 143 ms |
| Mesh | 108 |
| Üçgen | 12626 |
| Malzeme | 42 |
| Bounce | 4, cam için 5 |
| Denoise | açık |
| Cam koruması | 0 düzeltme |

Füme cam path’i 15014 ms, 32 sample, 8 geçiş, BVH 120 ms, 116 mesh, 12658 üçgen. Darboğaz sample süresi. BVH bu sahnede küçük. Denoise, sample’ı 128’den 48’e indirirken süreyi yaklaşık altıda bire çekti. Görüntüde hâlâ benek var.

## Görseller

Hepsi `test-results/visual-quality-v4/` içinde, bu turda tarayıcıdan alındı.

| İstenen kare | Dosya | Gözle |
| --- | --- | --- |
| Tam cephe | `01-front.jpg` | Dikey damar, dikiş yok. Seçim çerçevesi yok. Hâlâ düz stüdyo ışığı. |
| Sağ 45° | `02-right45.jpg` | Cepheden farklı dosya. Yan yüz okunuyor. |
| Sol 45° | `03-left45.jpg` | Sağ 45 ile aynı dosya değil. |
| Ahşap makro | `04-wood-macro.jpg` | Wood 094 damarı. Eski JPEG dikişi yok. |
| Raf ve bant | `05-shelf-macro.jpg` | Bant ince bir açık çizgi. Ayrı bir bant kalınlığı gibi durmuyor. |
| Açık çekmece | `06-drawer-macro.jpg` | Ön, yan, taban ve iç boşluk var. Ray çifti bu kadrajda seçilmiyor. |
| Füme cam makro | `07-glass-macro.jpg` | Raf ve LED camın arkasında. Çerçeve ve kulp var. Yansıma zayıf. |
| Füme cam path | `07-glass-path.png` | Kapak siyah levha değil; sağ kanatta iç hacim seçiliyor. Üst bölge hâlâ çok koyu. |
| Aksesuarlı | `08-accessories.jpg` | Mevcut kütüphane modelleri. Kumaş kıvrımı yok. |
| Path trace | `09-path.png` | Ahşap siyah değil. 20,6 sn. Benek duruyor. |
| Önce / sonra | `10-before-front.jpg`, `10-after-front.jpg`, `10-before-wood.jpg`, `10-after-wood.jpg` | Önceki kareler `ultra-realism/reference` kopyası. |

Sol ve sağ 45 özetleri farklı: `ca2ec668aa0cc559` ve `cde70fae08d36d3b`.

## Önce / sonra

| Sorun | Önce | Sonra | Durum |
| --- | --- | --- | --- |
| Kapakta JPEG dikişi | `10-before-wood.jpg` yatay kesik | `10-after-wood.jpg` sürekli damar | Azaldı |
| Dikey tekrarlayan katalog dokusu | Kapak boyunca katalog JPEG | 2 m Wood 094, temsili boya | Azaldı |
| Path trace’de siyah ahşap | Önceki risk | `09-path.png` ahşabı okunuyor | Bu karede yok |
| Füme camın siyah levha olması | İnce film yolu korunuyor | `07-glass-path.png` içini gösteriyor, üstü koyu | Kısmen |
| Açık çekmece kutusu | Ön şerit | Yan, taban, iç boşluk | Kutu görünüyor, ray seçilmiyor |
| Aksesuar | Basit modeller | Aynı modeller | Tamamlanmadı |
| Render süresi | 123 sn, 128 sample | 20,6 sn, 48 sample, denoise | Süre kontrol altında, benek duruyor |

## Aksesuar

Tamamlanmış sayılmadı. `public/models/wardrobe/library/LICENSE.txt` bu GLB’lerin Blender’da elle kurulduğunu, tarama veya kumaş simülasyonu olmadığını yazıyor. Bu turda lisansı doğrulanmış, gerçek kıvrımlı yeni bir giysi modeli konmadı.

## Tutulmayanlar

- Fotoğraf gerçekçiliği. Işık hâlâ düz, kulp hâlâ çubuk, bant makroda bir çizgi.
- Füme camın pencere yansıması. Raster makroda cam yüzeyi okunmuyor; path’te koyu.
- Çekmece rayının bu makro kadrajda seçilmesi.
- Resmî Kastamonu dokusu. Kullanılan tarama temsili.
- Diğer 44 modül. Onlar için kalite turu açılmadı.
