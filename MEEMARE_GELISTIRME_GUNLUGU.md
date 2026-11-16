# MEEMARE geliştirme günlüğü

Tarih: 16 Kasım 2026  
Kapsam: canlı `/dolap/tasarla` OpenPlan editörü. Eski `wardrobe-studio.js` rotası açılmadı. Üretim sunucusuna dağıtım yok. Veritabanı silinmedi.

Altı aşama aynı çalışma ağacında uygulandı ve tek derlemede doğrulandı. Ayrı aşama commit'i yok: sonraki aşama bir öncekinin dosyalarına bağlıydı, test de tek seferde geçti. Geri alma noktası bu değişiklik kümesidir.

## Başlangıç

Canlı sayfa `PublicWardrobeController::design` → `wardrobe.studio`. 3D, `ThreeViewer.svelte` içinde `WebGLRenderer`. Path tracer yalnızca `renderLab/viewer.ts` ve eski stüdyodaydı. 45 GLB `meemareWardrobes.ts` içinde duruyor. Ölçüler santimetre.

## Aşama 1 — Malzeme ve ışık

Yapılan:

- `wardrobePbr.ts`: Glossmax, laklı, boyalı, melamin ve compact için ayrı roughness / metalness. Sınıf, kayıtlı görsel adresinden okunur. Eski projeler yeni alan istemez.
- Normal haritası üretilmedi. Doku yoksa yalnız bu sayılar kullanılır.
- Dekor, UV adasında bir kez durur. Tekrarlama yok. Raf dokusu 90° döner. Kapak UV'si prosedürel kutudadır. GLB'nin kendi UV açılımı ölçülmedi.
- Cam: füme, bronz, şeffaf, gri, antrasit, mavi, yeşil ve reflekte. Opaklık 1. Geçirgenlik `transmission` ile. IOR 1.52.
- Çıta metalness 0.94. Clearcoat yok. Eski not, path tracer'da clearcoat'un metali siyaha boyadığını söylüyor.
- Ortam: harici HDRI yok. Three.js 0.182 `RoomEnvironment` (MIT) `PMREMGenerator` ile yansımaya çevrildi (`studioEnvironment.ts`).
- Işıklar sarı baskından çıkarıldı. ACES duruyor. Stüdyo pozlaması 1.02.
- Gölge normalBias 0.8 → 0.35. Duvarlar hâlâ gölge düşürmez; oda içi kararır.
- Masaüstü piksel oranı tavanı 1.75, gölge 2048. Dar ekranda 1.25 ve 1024.

Dosyalar: `wardrobePbr.ts`, `studioEnvironment.ts`, `wardrobeHardware.ts`, `furnitureModelLoader.ts`, `ThreeViewer.svelte`.

Tarayıcı: örnek proje 3D'de açıldı. Dolap, zemin ve duvar görünüyor. WebGL bağlamı kayıp değil. Karşılaştırmalı fotogerçekçilik puanı yok.

## Aşama 2 — Path tracing

Yapılan:

- `studioPathTrace.ts` düğmeye basınca `three-gpu-pathtracer` 0.0.24 yükler. Önizleme raster kalır.
- 3D'de "Ultra gerçekçi render" paneli. Hızlı 6 geçiş, dengeli 18, ultra 32. Karo 2×2. Sekme 3, 4 veya 5.
- Uzun kenar 1600, 1920, 2560, 3840. En-boy kameradan gelir. Sabit 1920×1080 zorlanmaz.
- `MAX_TEXTURE_SIZE` 4096 altındaysa veya ekran darsa 4K kapalıdır. Ölçülmeden 4K vaadi yok.
- İlerleme `örnek / hedef`. İptal bayrağı döngüyü keser. GPU hatasında fotoğraf düğmesine düşülür.
- Cam kalınlığı iz süresince 0 yapılır, sonra geri yazılır. Sahne ve proje deposu silinmez.
- Çıktı `meemare-ultra.png`. Denoise sample 4'ten sonra.

Dosyalar: `studioPathTrace.ts`, `ThreeViewer.svelte`, `tr.ts`, `en.ts`.

Test: düğme örnek projede göründü. Path trace PNG'si bu oturumda üretilmedi. "Fotogerçekçilik tamam" denmez.

## Aşama 3 — Geometri

Yapılan:

- 45 GLB dosyasına dokunulmadı.
- Dolap görseli katalog ölçüsünde kalır (`drawingSize`). Kayıttaki farklı genişlik silinmez ama GLB'yi germez.
- Kapak 18 mm panel, çıta 16 mm, önde ince bir pah dudağı. Cam fitilin içinde durur.
- Çekmece rayı uydurulmadı. GLB'de yoksa eklenmez.

Dosyalar: `wardrobeProduction.ts`, `furnitureModelLoader.ts`, `PropertiesPanel.svelte`.

Test: birim test katalog 45×60×250 iken istenen 80×40×200'ün çizilmediğini doğrular. 45 modelin ekseni tek tek ölçülmedi.

## Aşama 4 — Yerleşim

Yapılan:

- `wardrobePlacement.ts`: oda iç kutusu, dolap kutusu, 0.4 cm'den fazla binen komşu, köşe çakışması.
- Stüdyo tuvalinde sürükleme ve yeni yerleştirme reddedilir. Uyarı metni çıkar. Duvar yapışması durur.
- Sunucu `planX`, `planY`, `planRotation` için geniş sayı sınırı koyar (`-2000…8000`). Oda dışını kayıtta reddetmez. Eski, oda dışına taşmış proje kaydı kırılmasın diye. Yeni yerleşimi istemci tutar.

Dosyalar: `wardrobePlacement.ts`, `FloorPlanCanvas.svelte`, `WardrobeDesign.php`.

Test: birim testler çakışma, değme, köşe ve oda dışını geçti. Tarayıcıda sürükleyerek denenmedi.

## Aşama 5 — Performans

Yapılan:

- `cloneModel` geometriyi paylaşır. `disposeModel` paylaşılan geometriyi silmez. Malzeme kopyası durur. Böylece renkler karışmaz.
- Piksel oranı ve gölge haritası cihaza göre sınırlandı.
- Instancing yok. Her dolabın malzemesi ayrı. Instancing renkleri birleştirirdi.
- Malzeme değişince sahne imzası hâlâ tüm katı kurabilir. Bu dar boğaz duruyor.
- FPS ölçülmedi. 1, 5, 10, 20, 30, 45 modül sayıları yazılmaz.

Dosya: `furnitureModelResources.ts`, `ThreeViewer.svelte`.

## Aşama 6 — Üretim ve fiyat

Yapılan:

- Doğrulanan parça yalnız prosedürel kapaktır: kanat sayısı, en, boy, 18 mm, çıta 16 mm.
- Gövde, raf, çekmece ve arkalık için kesim listesi yazılmadı. GLB düğüm ölçüsü üretim ölçüsü diye işaretlenmedi.
- DXF/CNC açılmadı.
- Fiyat uydurulmadı. Panelde "Fiyatlandırma için inceleme gerekli".
- `quote()` aynı. Teklif talebi ile fiyat hesabı ayrı. Fiyat hesabı yok.

Dosyalar: `wardrobeProduction.ts`, `PropertiesPanel.svelte`.

## Testler

| Kontrol | Sonuç |
|---|---|
| `tests/wardrobe-realism.test.ts` | 7 geçti |
| `WorkshopToolsAccessTest` | geçti, 16 assertion |
| OpenPlan production build | çıktı kodu 0 |
| Örnek proje 3D açılışı | dolap, zemin, duvar ve Ultra düğmesi göründü |
| WebGL bağlamı | kayıp değil |
| 45 GLB tek tek | yapılmadı |
| Path trace PNG | yapılmadı |
| Sürükle-bırak çarpışması tarayıcıda | yapılmadı |
| Mobil cihaz | yapılmadı. Dar ekran profili kodda |
| Undo/redo bu turda | yapılmadı. Mevcut yığın değiştirilmedi |
| Konsol hata taraması | yapılmadı |
| FPS | ölçülmedi |

## Kalan riskler

- Kapak hâlâ profil kutularından. Fotoğraf gibi bir doğrama değil.
- Ortam, oda HDRI'si değil. Three.js oda sahnesinin yansıması.
- Path tracer çıktısı görülmedi. Cam hâlâ kararabilir.
- Sunucu, oda dışındaki eski `planX` değerini kabul eder.
- Geometri paylaşımı, bir örneğin geometriyi yerinde değiştirmesi halinde hepsini etkiler. Boyama malzeme kopyasında.
- Çok modülde sahne yeniden kurulumu sürüyor.

## Sonraki iş, bu turda yapılmayan

Path trace PNG'sini gerçek sahnede üretip camın siyah olup olmadığına bakmak. 45 GLB'nin sınır kutusunu katalog ölçüsüyle karşılaştırmak. Çarpışmayı tarayıcıda sürükleyerek denemek.
