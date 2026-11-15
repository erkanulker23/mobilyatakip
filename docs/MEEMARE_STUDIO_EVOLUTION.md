# Dolap stüdyosu — plan ve üretimle hizalama

## Adresler

| Sayfa | URL | Açıklama |
| --- | --- | --- |
| Dolap projeleri | `/dolap` | Kayıtlı projeler, arama, yeni proje + oda şablonu |
| 3D tasarla | `/dolap/tasarla` | Meemare modül stüdyosu (Y26 katalog, 3D oda, gömülü oda çizimi) |
| Oda planı | `/plan` | OpenPlan3D (genel plan aracı; tasarla sayfasından bağımsız) |
| Üretim dolabı | `/dolap-uretim` | Cabinet Studio |

## Stüdyo 3D (güncel)

- **Gömülü oda çizimi:** `Oda` → tam ekran editör (`wardrobe-room-sketch.js`): duvar, kapı, pencere, L / dikdörtgen şablon.
- **Yeni proje akışı:** Proje oluştur → `?oda=1` ile oda çizimi açılır → **3D odaya uygula**.
- **Hızlı çubuk:** Seçili modülün yanında kapak tipi, renk, klon, sil.
- **Yerleşim:** Kapı/pencere açıklıkları duvara modül koymayı engeller (layout).
- **Mobil:** Varsayılan katalog açık; 3D sahne görünür (inspector sheet alttan).

## Ortak gezinme

- `meemare-apps-nav` — projeler / 3D / plan / üretim
- Projeler sayfasında ek workflow şeridi yok (çift menü kaldırıldı).

## Oda → dolap aktarımı

- **Tasarla içi:** Ölçüler çizimden veya formdan → `applyWallsCm` → 3D duvar + açıklık mesh.
- **`/plan`:** İsteğe bağlı; `meemare-room-handoff` localStorage ile ölçü aktarımı.

## Tamamlanan (sıra 4)

| # | Özellik |
| --- | --- |
| 1 | Projeler hub (arama, şablon, kart özeti) |
| 2 | Gömülü oda sketch (plan sayfasından bağımsız) |
| 3 | UX: hızlı rail, zemin, sade kamera |
| 4 | Modül ekleyince kadraj (`focusSelectedModule`) |
| 5 | Bundle’da `room.walls` + openings |
| 6 | Duvar tıklama → renk paneli; sahne temizle; köşe GLB yalnız `kind=corner` |
| 7 | Render varsayılan 18 pass, güçlü denoise |
| 8 | Oda çizim kısayolları V/W/D/P |

## Sırada (mimari / üretim — büyük işler)

Bkz. `MEEMARE_BUG_BACKLOG.csv`: parametrik modül (EK-02), kesim listesi (EK-06), köşe GLB karışması (QA-CORNER-001), render kalitesi (QA-RENDER-001).