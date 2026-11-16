# MEEMARE 3D model kalitesi

Tarih: 16 Kasım 2026. Ölçümler çalışan stüdyoda (`wardrobe-studio.js?v=274`), `window.__studio.rawGlb` ile GLB kutularından alındı. Birim metre. 18 mm = 0.018 m.

Katalog: `wardrobe-glb-modules.json` 25 kayıt, `wardrobe-y26-modules.json` 20 kayıt. Görsel olarak açılıp katalog PNG ile karşılaştırılan modül sayısı: 2. Diğer 43 modül **NOT_TESTED**.

## Ölçülen geometri

### y26_tek_01 (45 × 250 × 60 cm)

| Parça | Ölçü (m) | Sonuç |
| --- | --- | --- |
| 000_GOVDE_SOL / 001_GOVDE_SAG | 0.018 × 2.5 × 0.6 | 18 mm panel |
| 002_GOVDE_UST / 003_GOVDE_ALT | 0.414 × 0.018 × 0.6 | 18 mm panel |
| 006–008 raflar | yükseklik 0.018, derinlik 0.588 | 18 mm raf |
| 004_ARKALIK | derinlik 0.008 | 8 mm arkalık |
| 009 / 011 çekmece önü | kalınlık 0.018 | 18 mm ön |
| 010 / 012 çekmece kutusu | 0.834 × 0.12 × 0.465 | kutu var |
| 013_ASKILIK | 0.4 × 0.018 × 0.018 | boru var |

Parçalar 12 üçgenlik kutu. Kenar bandı ayrı bir geometri değil. Kıyafet parçası bu GLB’de yok.

### y26_cift_01 (90 × 250 × 60 cm)

Yan, üst, alt ve raflar 0.018 m. Arkalık 0.008 m. İki çekmece önü ve iki çekmece kutusu (`010_CEKMECE_KUTU_1`, `012_CEKMECE_KUTU_2`) var. İç dikme var. Yine 12 üçgenlik kutular.

## Ekranda görülen

`test-results/meemare-2026-11-16/meemare-180-iki-modul.png`

180 cm ön duvara konan iki `y26_tek_01`. Askılık, raflar ve çekmece önleri görünüyor. Kapak yok (ürün kapaksız açıldı). Ahşap dokusu düz tekrar. Cam, LED ve kıyafet bu karede yok. Katalog fotoğrafı ile yan yana karşılaştırma yapılmadı.

## Durum

| Kontrol | Durum |
| --- | --- |
| 18 mm gövde ve raf, iki modül | PASS |
| Çekmece kutusu, iki modül | PASS |
| 8 mm arkalık, iki modül | PASS |
| Kenar bandı geometrisi | FAIL — ayrı mesh yok, kutu yüzeyi |
| Cam yansıması | NOT_TESTED |
| Metal donanım | NOT_TESTED |
| Kıyafet ve aksesuar | NOT_TESTED |
| Katalog PNG ile iç düzen, 43 modül | NOT_TESTED |
| PBR ahşap, doğal ışık | NOT_TESTED — tek karede doku düz |

Yeni modül eklenmedi. Bu turda geometri ölçümü ve bir ekran görüntüsü var; 45 modülün görsel kalitesi yükseltilmedi.
