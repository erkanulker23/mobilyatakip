# MEEMARE – Aşama 3: Parametrik dolap geometrisi

Bu aşama bitmiş değildir. Aşağıdaki sonuçlar bu oturumda çalışan testlerden ve tarayıcıdan okunan değerlerden gelir. Çalıştırılmayan maddeler geçti diye yazılmadı.

## Ne değişti

Dolap artık, katalog kaydı için güvenilir bir parça kutusu varsa, bütün GLB'yi X/Y/Z eksenlerinde gererek çizilmiyor. Gövde, raf, çekmece, arkalık, baza ve askılık ayrı kutulardan kuruluyor. Kalınlık milimetre olarak sabit kalıyor; dış ölçü santimetre.

45 GLB dosyası duruyor. `tools/openplan3d/src/lib/utils/meemareWardrobes.ts` içindeki 45 yolun tamamı diskte var (`mapped 45`, `missing 0`). Parça kutusu okunamayan bir model eski GLB yükleme yoluna düşer. Bu 45 kayıt için kutu çıkarıldı, bu yüzden ekranda artık GLB görünmüyor; dosyalar silinmedi.

## Yeni dosyalar

- `tools/openplan3d/src/lib/data/wardrobeLayouts.ts` — 45 modelin GLB düğüm kutularından üretilmiş yerleşimi. Konum 0–1, kalınlık mm. Üretim onayı değildir.
- `tools/openplan3d/src/lib/utils/wardrobeParametric.ts` — mm/cm dönüşümü, kalınlığı sabitleyen yerleştirme, raf ve çekmece sayısı, köşe aynası.
- `tools/openplan3d/src/lib/utils/wardrobeCabinetMesh.ts` — paylaşılan kutu/silindir geometrisi, parça başına malzeme, 1 mm ön kenar bandı.

## Değişen dosyalar

- `tools/openplan3d/src/lib/utils/furnitureModelLoader.ts` — yerleşimi olan modelde parametrik gövde; GLB ölçeklemesi bu yolda çağrılmıyor. Kapak gövdenin önüne alındı. Cam kapakta profil, yuva ve conta var.
- `tools/openplan3d/src/lib/utils/wardrobeProduction.ts` — yerleşimi olan modelde ölçü kilidi kalkıyor.
- `tools/openplan3d/src/lib/models/types.ts` — `shelfCount`, `drawerCount`, `cornerSide`.
- `tools/openplan3d/src/lib/utils/projectValidation.ts`, `studioDesign.ts`, `wardrobePlan.ts` — yeni alanlar yoksa eski proje açılır; varsa kontrol edilir.
- `tools/openplan3d/src/lib/components/sidebar/PropertiesPanel.svelte` — raf, çekmece ve köşe yönü; ölçü alanları kilitli değil.
- `tools/openplan3d/src/lib/i18n/locales/en.ts`, `tr.ts`
- `tools/openplan3d/tests/wardrobe-realism.test.ts`
- `public/js/studio-shell.js` — embed `v=309`. Raf, çekmece ve köşe yönü imzaya ve kayıt yüküne eklendi.
- `resources/views/wardrobe/studio.blade.php` — `studio-shell.js?v=12`.
- `app/Support/WardrobeDesign.php` — `planShelfCount` 0–12, `planDrawerCount` 0–8, `planCornerSide` left|right. Boş alan eski kaydı bozmaz.
- `public/openplan/` — üretim derlemesi yenilendi.

`tools/openplan3d` git tarafından yok sayılır. Bu raporun kaynağı oradadır; derlenmiş editör `public/openplan` içindedir.

## Geometri

İç hesap milimetredir. Sahne ve kayıt santimetredir: `cm = mm / 10`.

Başlangıç ölçüleri katalogdan gelir: tek modül 45×60×250 cm, çift 90×60×250 cm, köşe 90×90×250 cm. Panel kalınlığı GLB'nin ince ekseninden okunur. Yan, üst, alt ve raf için ölçülen değer 18 mm. Arkalık daha inceyse o kalınlık korunur; 18 mm diye şişirilmez.

Dış ölçü değişince dış yüze yaslı panellerin kalınlığı sabit kalır. Orta parçalar (raf, askılık) normalize merkezini korur. Askılık, uzun ekseni boyunca silindirdir; iki kısa eksen çap olarak kalır.

Birleşim varsayımı `CONSTRUCTION` içindedir ve `approved: false` dir: yanlar tam boy, üst ve alt tabla yanların arasında, arkalık arkada. Bu bir kesim onayı değildir.

Kenar bandı 1 mm'lik ayrı bir kutudur ve panelin ön yüzünün 0,2 mm önündedir. Panel kutusunun ölçüsü değişmez. Gerçek pah veya radius yok. Bıçak sırtı kenar bu yüzden duruyor.

Aynı kutu ölçüsü ikinci dolapta aynı `BufferGeometry` örneğini kullanır. Malzeme parça başınadır; bir dolabın boyası diğerinin geometrisini silmez.

## Cam ve profil

Cam ve profil rengi ayrı seçilir. Mevcut Kastamonu, alüminyum ve cam listeleri duruyor.

Cam kapak: 1,6 cm dikdörtgen profil, içe bakan yuva, siyah conta, 4 mm cam, kulp. Bu bir süpürülmüş alüminyum kesit değildir. Dört kalın kutunun yanına yuva ve conta eklendi.

Ahşap kapak 18 mm'dir ve gövdenin önündedir. Menteşe gövdesi yok. Çekmece açma animasyonu eklenmedi.

Füme ve siyah reflekte camın optik değerleri bu aşamada değiştirilmedi. İç rafın camın arkasından nasıl göründüğü tarayıcıda bakılmadı.

## Raf, çekmece, köşe

Raf ve çekmece sayısı boşsa GLB yerleşimi kullanılır. Sayı yazılırsa raflar iç açıklığa yeniden dizilir. Köşe dolapta çekmece sayısı ezilmez. Köşe raf sayısı ezilirse L rafın bir kolu kopyalanır; varsayılan yolda kopyalama yoktur.

Köşe, `kose_*` yerleşimindeki L parçalarından kurulur. Sağ yön X ekseninde aynalanır. Döndürülmüş tek dikdörtgen değildir. Komşu modülle ortak panel ve kapak salınım boşluğu ayrıca çözülmedi.

Kıyafet ve gevşek askı mesh'leri parametrik gövdeye alınmadı. Askı borusu alındı.

## Tarayıcıda ölçülen dolap

Örnek proje `UX Deneme Odası`, modül `y26_tek_01`, kayıtlı ölçü 45×60×250 cm. Sayfa `v=309` ile açıldı. 3D tuşu çalıştı. Tuval 554×721 px. WebGL bağlamı kaybolmadı.

Canlı geometri kutuları:

| Parça | Ölçü (cm) |
|---|---|
| Yan panel | 1,8 × 250 × 60 |
| Üst / alt tabla | 41,4 × 1,8 × 60 |
| Kenar bandı | kalınlık 0,1 |

41,4 cm, 45 cm dış genişlikten iki yan panelin düşmüş halidir. Parça sayıları: gövde 4, arkalık 1, baza 1, raf 3, çekmece önü 2, çekmece kutusu 2, askılık 1, kenar bandı 10. Bu, “üst depo + askılık + iki çekmece” düzenidir ve tek tip boş kutu değildir.

Özellik panelinde genişlik, derinlik ve yükseklik yazılabilir durumdaydı (45 / 60 / 250). Raf alanı boştu, yer tutucu 3. Çekmece alanı boştu, yer tutucu 2. Kastamonu listesi açıldı (307 ürün). Bu alanlara yeni değer yazılmadı; örnek projenin kaydı bu oturumda değiştirilmedi.

Kapalı ahşap kapakta kulp ve yan panel dönüşü görüldü. Kamera dolabın önüne yakındı. Aynı kamera ve ışıkla bir “önce” karesi alınmadı; yeni derleme eskisinin üzerine yüklendi.

Görüntüler: `test-results/asama3/asama3-webgl-front.png`, `asama3-webgl-orbit.png`, `asama3-webgl-selected.png`.

## Performans

`y26_tek_01` önizlemesi, birim test çalıştırmasında: 24 mesh, 12 paylaşılan geometri, 324 üçgen. İkinci aynı dolap birincinin geometri örneğini paylaştı. Kare hızı ve GPU bellek baytı ölçülmedi. Sahne hâlâ herhangi bir mobilya değişiminde bütün olarak kurulur; yalnız değişen modül güncellenmez.

## Testler

Birim: `npx vitest run tests/wardrobe-realism.test.ts` → 19 geçti, 0 kaldı.

Sunucu: `php artisan test --filter=WorkshopToolsAccessTest` → 1 geçti, 16 assertion.

`WardrobeDesign::validated` çağrısı: raf 4, çekmece 2 ve `planCornerSide=left` durdu. Raf 13 reddedildi (`en fazla 12`). Yalnız `id`, `width`, `height` olan eski modül kabul edildi.

Üretim derlemesi `NODE_ENV=production OPENPLAN_STATIC=1 npm run build` → çıkış 0.

`npx svelte-check --tsconfig ./tsconfig.json --threshold error` → 2 hata, ikisi de önceden var olan `tests/localization.test.ts` içinde. Portekizce sözlükte `viewerNav.cutaway` yok. Yeni dolap dosyalarında hata bildirmedi.

| # | Kontrol | Sonuç |
|---|---|---|
| 1 | 450×2500×600 tek modül | Geçti. `y26_tek_01` birim testi ve tarayıcıda 1,8×250×60 yan panel. |
| 2 | 900×2500×600 çift modül | Kısmi. `y26_cift_01` birim testinde çekmece ve 80 cm üstü dış açıklık var. Tarayıcıda açılmadı. |
| 3 | 900×900 köşe | Kısmi. `kose_1` birim testinde bir kol derin, diğer kol geniş. Tarayıcıda açılmadı. |
| 4 | 18 mm panel | Geçti. Birim test ve tarayıcı kutusu 1,8 cm. |
| 5 | Raf sayısı | Kısmi. Birim test 3 rafı 5 yaptı, yan paneller durdu. Tarayıcıdaki alan yazılmadı. |
| 6 | Çekmece sayısı | Kısmi. Birim test 2 çekmeceyi 3 yaptı. Tarayıcıdaki alan yazılmadı. |
| 7 | Ahşap kapak | Geçti, bu tek modül için. Kapalı kapak ve kulp görüldü. Derz milimetresi pikselden ölçülmedi. |
| 8 | Cam kapak | Test edilmedi. Kod var, sahnede açılmadı. |
| 9 | Siyah reflekte cam | Test edilmedi. |
| 10 | Gold profil | Test edilmedi. Açık projedeki profil `al-gumus`. |
| 11 | Füme cam | Test edilmedi. Kayıtta `cam-fum` var; kapak stili ahşap olduğu için cam çizilmedi. |
| 12 | Sol köşe | Kısmi. Birim test sol ve sağ ince yanın işaretini karşılaştırdı. Görüntü yok. |
| 13 | Sağ köşe | Kısmi. Aynı birim test. Görüntü yok. |
| 14 | Genişlik değişimi | Kısmi. 45 cm gövde 90 cm olunca yan kalınlık 1,8 cm kaldı. Tarayıcıda genişlik değiştirilmedi. |
| 15 | Yükseklik değişimi | Kısmi. `drawingSize` 240 cm yüksekliği kilitli saymadı. Tarayıcıda değiştirilmedi. |
| 16 | Renk / malzeme | Kısmi. Kastamonu seçici göründü. Yeni bir renk tıklanmadı. |
| 17 | Kaydet ve yeniden aç | Kısmi. Eski proje açıldı ve yeni gövdeyle çizildi. Yeni raf sayısı kaydedilip tekrar açılmadı. |
| 18 | Geri al / ileri al | Test edilmedi. Düğmeler göründü, basılmadı. |
| 19 | WebGL | Geçti, bu sahne için. Tuval 554×721, bağlam duruyor. |
| 20 | Path tracing | Kısmi. “0 / 64 örnek · %0” yazdı, iptal “Render iptal edildi. Sahne duruyor.” dedi. PNG yok. 64 örnek bitmedi. |
| 21 | 45 GLB | Kısmi. 45 dosya diskte. 45 yerleşim birim testte birbirinden farklı. 45'i tarayıcıda açılmadı. |
| 22 | Modüller arası çakışma | Test edilmedi. Sahnede tek modül vardı. |

## Kalan iş

- Pah veya yuvarlatılmış panel kenarı yok.
- Alüminyum profil süpürülmüş kesit değil.
- Menteşe, çekmece rayı ve çekmece animasyonu yok.
- Kıyafet mesh'leri parametrik modelde yok.
- Camın arkasından raf görünürlüğü ölçülmedi.
- Köşe dolabın komşu modülle ortak paneli ve kapak salınımı yok.
- Raf veya çekmece sayısı köşe dolapta L düzenini sadeleştirir; varsayılan sayıda bu yol kapalıdır.
- Değişiklik hâlâ bütün sahneyi kurar.
- 45 modelin görsel farkı tarayıcıda tek tek bakılmadı.
- Path tracing yeni geometriyle başlamayı kabul etti; bitmiş bir kare üretilmedi.
- `svelte-check` Portekizce sözlük eksikliği yüzünden temiz değil.
- Aynı kamera ve ışıkla önceki GLB görüntüsü ile yeni görüntü yan yana alınmadı.

Kesim listesi, fiyat ve CNC bu aşamanın dışında kaldı. Gövde birleşimi üretim onayı değildir.
