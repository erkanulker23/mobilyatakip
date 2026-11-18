# MEEMARE – Aşama 2 render raporu

Tarih: 16 Kasım 2026. Bu rapor yalnız ölçülen sonuçları yazar. Fotogerçekçilik iddia edilmez.

Canlı önizleme WebGL raster kaldı. Path tracing yalnız `/dolap/tasarla` içindeki **Ultra gerçekçi render** düğmesinde çalışır. Motor `three-gpu-pathtracer` 0.0.24 ve `three-mesh-bvh` 0.9.10, Three.js 0.182, WebGLRenderer.

## Değişen dosyalar

Kaynak (bu klasör git’e girmez):

- `tools/openplan3d/src/lib/utils/studioPathTrace.ts`
- `tools/openplan3d/src/lib/utils/studioEnvironment.ts`
- `tools/openplan3d/src/lib/utils/wardrobePbr.ts`
- `tools/openplan3d/src/lib/utils/furnitureModelLoader.ts`
- `tools/openplan3d/src/lib/components/viewer3d/ThreeViewer.svelte`
- `tools/openplan3d/src/lib/i18n/locales/tr.ts`
- `tools/openplan3d/src/lib/i18n/locales/en.ts`
- `tools/openplan3d/src/lib/i18n/locales/pt.ts`
- `tools/openplan3d/tests/wardrobe-realism.test.ts`

Çalışan kopya:

- `public/openplan/` (üretim derlemesi)
- `public/js/studio-shell.js` (gömülü adres `v=305`)
- `resources/views/wardrobe/studio.blade.php` (betik `?v=8`)

Karşılaştırma görselleri `test-results/asama2/` altındadır. Bu klasör kayıt altına alınmamalı.

## Motor ve ayarlar

| Profil | Hedef örnek | Işık sıçraması | Süre sınırı |
|---|---:|---:|---:|
| Hızlı | 64 | 4 | 120 sn |
| Kaliteli | 256 | 5 | 240 sn |
| Ultra | 512 | 6 | 360 sn |
| Maksimum | 1024 | 6 | 480 sn |

Örnek sayısı `tracer.samples` değeridir. Karo 2×2 olduğu için bir tam örnek dört `renderSample` çağrısıdır. İlerleme metni bu gerçek sayıdır.

Çözünürlük:

- 1600 px uzun kenar, kamera oranı korunur
- 1920×1080, 2560×1440, 3840×2160 tam piksel kutusudur
- Kamera oranı bu kutuya uymazsa görüntü esnetilmez; sığan dikdörtgen ortalanır, boşluk `#d9d3c8` ile doldurulur
- `MAX_TEXTURE_SIZE` kutuyu küçültürse arayüz uyarı verir

Kamera: mevcut, önden, sol çapraz, sağ çapraz, yakın detay. Önden / çapraz, dolap kutusunu kadraja sığdırır. Yakın detay üst bölüme yaklaşır. İş bitince kamera eski yerine döner.

Path tracing sırasında seçim ışıması, sprite etiketler ve ızgara yardımcıları gizlenir. Camda `thickness` sıfırlanır, `attenuationDistance` sonsuz yapılır, yüzey iki yüzlü olur; bitince eski değerler geri yazılır. GLB yükü `userData.meemareGlb` ile en fazla 20 sn beklenir. Tracer, geçici ışık ve HDR yalnız render süresince durur, sonra bırakılır. Aynı anda ikinci render `busy` döner. İptal ve WebGL bağlam kaybı temizliği `finally` içindedir.

Ortam: canlı önizleme Three.js RoomEnvironment (MIT). Render süresince `public/textures/wardrobe/studio_small_09_1k.hdr` takılır. Dosya Poly Haven `studio_small_09`, CC0 (`public/textures/wardrobe/LICENSE.txt`). Şiddet 0.22. Nötr dikdörtgen alan ışığı şiddet 0.45. Sahne ışıkları render boyunca 0.85 katsayısıyla kısılır, sonra eski haline döner.

Malzeme: lake, boya ve Glossmax pürüz değerleri durur; albedo değişmez. Melamin, kompakt ve düz yüzeylere Poly Haven meşe gözenek normal/pürüz haritası bağlanır (ölçek 0.12, CC0, resmî Kastamonu taraması değildir). Bu örnek projedeki Antrasit D143 Glossmax olduğu için o yüzeye gözenek haritası uygulanmaz.

Telefon sınırı: yalnız dar **ve** dokunmatik ekranda hızlı profil ile 1600 px açık kalır. Dar masaüstü penceresi telefon sayılmaz.

## Derleme ve test

- `tests/wardrobe-realism.test.ts`: 13 test geçti
- `NODE_ENV=production OPENPLAN_STATIC=1 npm run build`: çıkış 0
- `php artisan test --filter=WorkshopToolsAccessTest`: 1 test, 16 doğrulama, geçti
- `svelte-check`: `ThreeViewer.svelte` hatasız. `tests/localization.test.ts` içinde önceden var olan Portekizce sözlük eksiği duruyor (`viewerNav.cutaway`). Bu aşama o sözlüğü tamamlamadı.

## Tarayıcıda ölçülenler

Sayfa: `http://127.0.0.1:8765/dolap/tasarla?proje=qVMTxpzlbo7BNBzVciSraWMd` (UX Deneme Odası, 1 modül). Gömülü adres `v=305`.

1. Hızlı, 1600 uzun kenar, önden. İlerleme 12/64 (%18) ve 28/64 (%43) olarak görüldü. Süre sınırı doldu. Sonuç: PNG IHDR **1229×1600**, imza `PNG`, 5.071.774 bayt, 28 örnek, `ms` 132018, `limited: true`, modeller `ready`. Uzun kenar 1600. En-boy oranı esnetilmedi.
2. Aynı profil, ışık kısılmış ikinci derleme. 18/64, `ms` 195293 (örnekleme artı gürültü giderme ve PNG). Yine 1229×1600, `limited: true`. 320 px önizlemede 245 üzeri beyaz piksel sayısı 0. Koyu (12 altı) piksel 8509 / 133440.
3. İptal: ikinci başlangıçta sayaç `0 / 64` iken İptal basıldı. Metin: “Render iptal edildi. Sahne duruyor.” Render düğmesi yine açık, tuval bağlamı kopmadı.
4. İlk render öncesi ve sonrası `GET /dolap/proje/qVMTxpzlbo7BNBzVciSraWMd` tasarım JSON’u aynıydı. Modül sayısı 1.
5. Emülasyon 390×844, `pointer: coarse`. Hızlı ve 1600 açık; 1920, 2560, 3840, 256, 512 ve 1024 kapalı. Bu fiziksel telefon değildir.

Görseller:

- `test-results/asama2/raster-onizleme.png` — raster, panel açık, dolap yandan
- `test-results/asama2/pathtrace-fast-front.jpg` — ilk path trace, 28 örnek, tavan köşesinde patlayan beyaz
- `test-results/asama2/pathtrace-18-ornek.png` — ikinci path trace, 18 örnek

## Görüntü gözlemi

Raster önizleme açık bej bir dolabı, krem duvarı ve zemini gösteriyor. Kamera gövdeye yakın.

İlk path trace dolabı kadrajın içine aldı, kesmedi. Görüntü yoğun kumlu. Sol taraf neredeyse siyah. Tavan-duvar köşesinde stüdyo HDR’den gelen patlak beyaz vardı.

İkinci ışık ayarından sonra o beyaz piksel eşiği aşılmadı. Küçük bir aydınlık kenar duruyor. Dolap bütün olarak karede. Ön yüz koyu bir girinti gibi okunuyor; yatay bantlar iç raf izine benziyor ama 18 örnekte kapak, çıta ve Kastamonu rengi rasterle aynı değil. Görüntü rasterden daha koyu ve kahverengi. Bu çıktı müşteri sunumu değildir.

## Senaryo durumu

| # | Senaryo | Sonuç |
|---|---|---|
| 1 | Tek kapılı ahşap | Kısmi. 1 modül, 28 ve 18 örnek. Kumlu, koyu. |
| 2 | Çift kapılı MDF | Ölçülmedi |
| 3 | Bronz cam | Ölçülmedi |
| 4 | Füme cam | Ölçülmedi |
| 5 | Parlak lake | Ölçülmedi |
| 6 | Köşe dolabı | Ölçülmedi |
| 7 | Yan yana beş modül | Ölçülmedi |
| 8 | Yirmi modül | Ölçülmedi |
| 9 | İptal | Geçti. 0/64 iken iptal, sahne durdu |
| 10 | Render sonrası kayıt | Kısmi. JSON değişmedi. Kaydet düğmesine basılmadı |
| 11 | Render sonrası renk | Ölçülmedi. Örnek proje boyanmadı |
| 12 | Mobil / düşük GPU | Kısmi. Yalnız 390×844 emülasyon. Fiziksel telefon yok |
| 13 | PNG çözünürlüğü | Geçti, yalnız 1600 uzun kenar: 1229×1600. 1080p, QHD ve 4K üretilmedi |
| 14 | Kadraj | Kısmi. Önden bütün dolabı aldı. Diğer kameralar üretilmedi |
| 15 | Camın arkasındaki iç modül | Ölçülmedi |

## Kalan sorunlar

- Bu oturumda 64 örneğin tamamı bitmedi. Tarayıcı sekmesi arka planda kareyi yavaşlatmış olabilir. 120 sn sınırı 18 ve 28 örnekte durdu.
- 18–28 örnek profesyonel çıktı değildir. Gürültü giderme kumu silmedi.
- Path trace rengi raster önizmeden koyu. Kastamonu seçimi piksel olarak korunmuş sayılmaz.
- Camın siyahlaşmadığı bu sahnede görülmedi.
- 1920×1080, 2560×1440 ve 3840×2160 dosyası üretilmedi. Düğmeler masaüstünde açıktı.
- Canlı önizleme hâlâ RoomEnvironment. HDR yalnız render anında takılır.
- Portekizce sözlük `svelte-check` hatası duruyor.

## Sonraki aşama

- Ön plandaki bir sekmede Hızlı 64’ü bitirip süreyi yeniden ölçmek.
- Aynı dolabın 1920×1080 çıktısında IHDR’yi doğrulamak.
- Cam, lake, köşe ve beş modüllük sahneleri örnek müşteri projesini kaydetmeden, geçici sahnede üretmek.
- Pozlamayı raster önizlemeye yaklaştırmak; koyu kapak yüzünü ayırmak.
- 256 örnek ancak 64 temiz bittikten sonra denenmeli.
