# MEEMARE Aşama 4 — Yerleşim raporu

Durum: **kısmi**. Yerleşim matematiği koda girdi, birim testleri geçti, OpenPlan derlendi ve örnek proje tarayıcıda açıldı. Yirmi iki senaryonun hepsi tarayıcıda çalıştırılmadı. Köşe birleşimi üretim onayı almış değildir.

Örnek proje `UX Deneme Odası` değiştirilmedi. Otomatik yerleştirme önerisi açıldı ve **Vazgeç** ile kapatıldı. **Uygula** tıklanmadı. Kayıt zamanı `2026-11-16T14:38:21+03:00` olarak kaldı. `placementRules` hâlâ kayıtta yok.

## Değiştirilen dosyalar

Yeni:

- `tools/openplan3d/src/lib/utils/modulePlacement.ts`
- `tools/openplan3d/tests/module-placement.test.ts`
- `app/Support/ModulePlacement.php`
- `tests/Unit/ModulePlacementTest.php`
- `tests/Fixtures/placement-rules-v1.json`

Bağlanan mevcut dosyalar:

- `tools/openplan3d/src/lib/utils/wardrobePlacement.ts`
- `tools/openplan3d/src/lib/utils/furnitureGeometry.ts`
- `tools/openplan3d/src/lib/utils/sceneSignature.ts`
- `tools/openplan3d/src/lib/utils/studioDesign.ts`
- `tools/openplan3d/src/lib/components/editor/FloorPlanCanvas.svelte`
- `tools/openplan3d/src/lib/components/viewer3d/ThreeViewer.svelte`
- `tools/openplan3d/src/lib/i18n/locales/tr.ts`
- `tools/openplan3d/src/lib/i18n/locales/en.ts`
- `app/Support/WardrobeDesign.php`
- `public/js/studio-shell.js`
- `resources/views/wardrobe/studio.blade.php`
- `public/openplan` (üretim derlemesi)

Canlı gömme sürümü `v=310`. Kabuk betiği `studio-shell.js?v=13`.

## Geometri ve çarpışma

Hesap milimetre tam sayılarıyla yapılır. `1 cm = 10 mm`. Kurallar `placement-rules-v1.json` içindedir: çakışma toleransı 4 mm, oda payı 15 mm, duvar yapışma mesafesi 120 mm, komşu yapışma mesafesi 80 mm, montaj boşluğu 0 mm, uzay hücresi 500 mm.

Düz modül tek dikdörtgendir. L köşe iki dikdörtgendir: derinlik bacağı ve arka bacak. Sağ köşe yalnızca `corner` ve `cornerSide === right` iken X ekseninde aynalanır. Çarpışma, döndürülmüş kutuların dört ekseninde ayrım teoremiyle bakılır. 4 mm ve altındaki temas çarpışma sayılmaz.

Köşe çakışması `corner` olarak döner. Kapı veya pencere aralığı `opening` olarak döner. Oda kontrolü hâlâ duvarların çevre dikdörtgeninden kalınlığın yarısı kadar içeri alınmış kutudur. Gerçek oda poligonu değildir.

Çarpışma çiftleri 500 mm hücreli bir uzay indeksinden geçer. Ölçülen ortalama süre, 10 veya 20 tekrar:

| Durum | Sonuç | Ortalama |
| --- | --- | --- |
| 20 ayrı modül | 0 çift | 0,195 ms |
| 45 ayrı modül | 0 çift | 0,100 ms |
| 45 modül aynı noktada | 990 çift | 1,885 ms |

FPS ölçülmedi.

## Duvar hizalama

`snapFurnitureToWalls` seçeneksiz çağrıldığında önceki davranışı korur. Stüdyo çağrısı kapı, pencere ve komşu modülleri verir. Modül açıklığın üstündeyse ve en yakın boş kenar 12 cm içindeyse oraya kayar. Daha uzaksa o duvara yapışmaz. Komşu kenara en fazla 8 cm yaklaşır. Duvar ucundaki merkez, genişliğin yarısı içeride kalır.

## Köşe birleşimi

Sol ve sağ köşe taban izi ayrı hesaplanır. 900 mm köşe, duvar üzerinde 900 mm izdüşüm verir; iki ayrı 900 mm modül sayılmaz.

`cornerJointStatus()` şunu döndürür: `approved: false`, ortak panel yok, kapak açılma boşluğu 0. Üretim onayı verilmiş bir birleşim yoktur. Montaj boşluğu 0 olduğu için matematiksel sığma ile montaj uygulanabilirliği şu an aynıdır. Bu bir üretim payı değildir.

## Otomatik yerleştirme

Katalog taşları 900 mm ve 450 mm. Dinamik program büyük taşı önce kullanır.

- 3150 mm: `900 + 900 + 900 + 450`, kalan 0
- 2700 mm: `900 + 900 + 900`, kalan 0
- 2500 mm: `900 + 900 + 450`, kalan 250 mm
- 3150 mm üzerine bir 450 mm daha: sığmaz

Öneri, seçilen duvardaki en uzun gerçek boş aralığa yazılır. Aralıklar birleştirilip tek kalan sayı gibi gösterilmez. Onay, mevcut modülleri silmez. Uygulamadan önce her yeni modül oda, açıklık ve çarpışma kontrolünden geçer. Vazgeç yalnızca öneri penceresini kapatır.

Tarayıcıda 300 cm duvar için metin şuydu: “300 cm duvarda 300 cm boşluk var. Öneri: 90 + 90 + 90 cm. 30 cm artar. Montaj payı ayrıca tanımlı değil.” Örnek projedeki mevcut modül bu duvarın 12 cm yapışma bandının dışında durduğu için o duvarın boşluğuna yazılmadı.

## 2D ve 3D

Geçersiz konumda amber uyarı metni `⚠` ile çıkar. Önizlemede kırmızı simge, kırmızı nokta ve genişlik santimetre olarak yazılır. Seçili modülde kalan uzunluk ve aralıklar metin olarak durur. Ayrı CAD ölçü çizgisi eklenmedi.

Konum veya dönüş değişince sahne gövde imzası aynı kalır. Üç boyutlu modeller duruyorsa yalnız konum ve dönüş güncellenir; kamera bu yolda yeniden kadrajlanmaz. Genişlik, malzeme veya duvar değişince sahne yeniden kurulur. Bu ayrım birim testte doğrulandı. Tarayıcıda sürükleyerek izlenmedi.

Path tracing bu turda çalıştırılmadı. İzleyici aynı sahne nesnelerini kullanır; ayrı bir yerleşim kopyası yoktur.

## Sunucu

`placementRules: 1` gönderilirse sunucu konum, oda kutusu, açıklık, dönüş ve modül çakışmasını aynı kurallarla denetler. Alan yoksa eski kayıt geometrik kontrolden geçmez ve açılır. Geçersiz eski kayıt silinmez.

Oda çizimindeki koordinat sınırı `-2000…8000` cm oldu. Eski `2000` cm tavanı gerçek odayı reddedebiliyordu.

## Testler

Vitest, `module-placement`, `wardrobe-realism`, `furniture-interactions`, `scene-signature`: **98 geçti, 0 kaldı**.

`php artisan test --filter=ModulePlacementTest`: **3 test, 25 doğrulama, geçti**.

`WorkshopToolsAccessTest`: **geçti**.

`npm run check`: önceki iki yerelleştirme hatası duruyor. `pt.ts` içinde `viewerNav.cutaway` yok. Yeni yerleşim dosyasında tip hatası yok.

OpenPlan üretim derlemesi **çıkış 0**, Vite `built in 10.83s`. 45 katalog kaydı ve 45 GLB yolu duruyor. Eksik dosya 0.

| # | Senaryo | Sonuç |
| --- | --- | --- |
| 1 | 3150 mm, 900+900+900+450 | Geçti, birim |
| 2 | Fazladan 450 mm | Geçti, birim |
| 3 | 2700 mm, üç 900 | Geçti, birim |
| 4 | 2500 mm, kalan 250 | Geçti, birim |
| 5 | Aynı koordinat | Geçti, birim |
| 6 | 90° çarpışma ve 45° ıskalama | Geçti, birim |
| 7 | L taban izi | Geçti, birim |
| 8 | Sol köşe | Geçti, birim |
| 9 | Sağ köşe | Geçti, birim |
| 10 | Köşe veya modülün dışarı taşması | Geçti, birim |
| 11 | Duvar sonuna hizalama | Geçti, birim |
| 12 | Modül kalkınca boşluk | Geçti, birim |
| 13 | Otomatik öneri | Geçti, birim. Tarayıcıda 300 cm duvar önerisi göründü |
| 14 | Öneriyi reddetme | Geçti. Vazgeç kaydı değiştirmedi |
| 15 | Eski proje | Geçti. Kuralsız kayıt ve örnek proje açıldı |
| 16 | Yeni yerleşimi kaydedip açma | Çalıştırılmadı. Kayıt değiştirilmedi |
| 17 | Undo/redo | Çalıştırılmadı. Butonlar göründü, tıklanmadı |
| 18 | 20 modül | Geçti, 0,195 ms |
| 19 | 45 modül | Geçti, 0,100 ms ve yoğun durumda 1,885 ms |
| 20 | Mobil sürükleme | Çalıştırılmadı. Fiziksel telefon yok |
| 21 | 2D–3D eşleşme | Kısmi. `planToScene` birim testi geçti. Sürükleme ölçülmedi. 3D görünüşte mevcut dolap duruyor |
| 22 | Path tracing yerleşimi | Çalıştırılmadı |

## Tarayıcı

`http://127.0.0.1:8765/dolap/tasarla?proje=qVMTxpzlbo7BNBzVciSraWMd`

- Kabuk `v=13`, iframe `v=310`
- 2D planda “Otomatik yerleştir” göründü
- Öneri penceresi açıldı, Vazgeç ile kapandı
- 3D tuşu WebGL tuvalini açtı: 692×901, dolap gövdesi görünüyor
- Ultra Render düğmesi duruyor. Render başlatılmadı
- Modül: `y26_tek_01`, `planX 264`, `planY 50`, dönüş 270, kapı ahşap, alüminyum `al-gumus`, cam `cam-fum`

## Kalan riskler

- Oda sınırı çevre dikdörtgenidir. L odanın girintisi yanlış iç veya dış sayılabilir.
- Duvara 12 cm’den uzak modül o duvarın boşluk hesabına girmez.
- Kapak salınımı ve ortak yan panel modellenmedi. Köşe birleşimi uygulanabilir üretim sayılmaz.
- Montaj payı 0 mm’dir.
- Sürüklemede sahne yeniden kurulmuyor yolu tarayıcıda ölçülmedi.
- Genişlik veya raf sayısı değişince model hâlâ baştan kurulur.
- Portekizce çeviri anahtarları eksik. Bu turda eklenmedi.
- Dağıtım yapılmadı. Commit atılmadı.
