# Dolap stüdyosu — plan ve üretimle hizalama

## Adresler

| Sayfa | URL | Açıklama |
| --- | --- | --- |
| Dolap projeleri | `/dolap` | Kayıtlı odalar (giriş gerekir) |
| 3D tasarla | `/dolap/tasarla` | Modül stüdyosu |
| Oda planı | `/plan` | OpenPlan3D (iframe) |
| Üretim dolabı | `/dolap-uretim` | Cabinet Studio |

## Erişim düzeltmesi

Atölye kullanıcıları `RestrictWorkshopUser` ile kısıtlanıyordu; `tools.plan` ve `tools.cabinet` izin listesinde yoktu → **403**. `WorkshopUser::allowedRoutePrefixes` içine `tools.` eklendi.

Kontrol paneli yan menüsüne **Meemare tasarım** bölümü eklendi (tüm roller).

## Ortak gezinme

`resources/views/partials/meemare-apps-nav.blade.php` — plan, üretim, dolap sayfalarında aynı şerit.

## Oda → dolap aktarımı

`/plan` üstündeki form ölçüleri `?onduvar=&solduvar=&sagduvar=` ile `/dolap/tasarla`ya gönderir. Stüdyo `applyRoomQuery()` ile duvar cm değerlerini yükler.

Tam OpenPlan köşe/oda çizimi için repoda `tools/openplan3d` var; sonraki aşama: aynı domain’de derleyip otomatik handoff (postMessage).

## Stüdyo 2B (mevcut)

`wardrobe-studio.js` içinde L-oda, kapı/pencere, duvar kolu sürükleme, modül taşıma — OpenPlan’daki akışa yakın; tam serbest duvar çizimi henüz yok.

## Tamamlanan (sıra 2)

| # | Özellik | Dosya |
| --- | --- | --- |
| 1 | Plan → stüdyo `localStorage` handoff | `public/js/meemare-plan-bridge.js` |
| 2 | Stüdyo → üretim dolabı ölçü + handoff | `openCabinetStudio()`, `meemare-cabinet-bridge.js` |
| 3 | Birleşik proje JSON indirme | `downloadMeemareBundle()` (`.meemare.json`) |
| 4 | Giriş sonrası üst linkler | `public-gate-top-links.blade.php` |

## Sıradaki büyük adımlar

1. Self-hosted OpenPlan (`scripts/build-openplan-static.sh` — adapter-static gerekir)
2. Cabinet Studio’ya otomatik ölçü enjeksiyonu (cross-origin iframe sınırı)
3. Inspector’da panel/delik UI (Cabinet Studio parity)
4. Katalog önizleme v=3 kalitesi (Cycles + malzeme)