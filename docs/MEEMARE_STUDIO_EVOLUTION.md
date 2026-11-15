# Dolap stüdyosu — plan ve üretimle hizalama

## Adresler

| Sayfa | URL | Açıklama |
| --- | --- | --- |
| Dolap projeleri | `/dolap` | Kayıtlı odalar; yeni proje ölçü veya OpenPlan |
| 3D tasarla | `/dolap/tasarla` | Modül stüdyosu |
| Oda planı | `/plan` | OpenPlan3D (iframe) |
| Üretim dolabı | `/dolap-uretim` | Cabinet Studio |

## Erişim düzeltmesi

Atölye kullanıcıları `RestrictWorkshopUser` ile kısıtlanıyordu; `tools.plan` ve `tools.cabinet` izin listesinde yoktu → **403**. `WorkshopUser::allowedRoutePrefixes` içine `tools.` eklendi.

Kontrol paneli yan menüsüne **Meemare tasarım** bölümü eklendi (tüm roller).

## Ortak gezinme

- `meemare-apps-nav` — uygulama geçişi (projeler / 3D / plan / üretim)
- `meemare-workflow` — aynı dört adım şeridi (tüm tasarım sayfalarında)
- `meemare-shell-styles` — üst bar + handoff satırı (Cabinet Studio ile uyumlu)

## Oda → dolap aktarımı

`/plan` üstündeki form ölçüleri `?onduvar=&solduvar=&sagduvar=` ile `/dolap/tasarla`ya gönderir. Stüdyo `applyRoomQuery()` ile duvar cm değerlerini yükler.

OpenPlan aynı domain’de `public/openplan` ( `scripts/build-openplan-static.sh` ) ile sunulur; dosyadan JSON import ve manuel ölçü handoff mevcut, tam postMessage export sonraki aşama.

## Stüdyo 2B (mevcut)

`/dolap/tasarla` içinde **1 · Oda planı** adımı OpenPlan iframe (plan sayfasıyla aynı motor); **2 · Dolap dizimi** 3D; **3 · Üretim** Cabinet Studio. Dahili «Yerleşim» görünümü yalnızca L-oda modül kaydırma içindir, serbest çizim değildir.

## Tamamlanan (sıra 2)

| # | Özellik | Dosya |
| --- | --- | --- |
| 1 | Plan → stüdyo `localStorage` handoff | `public/js/meemare-plan-bridge.js` |
| 2 | Stüdyo → üretim dolabı ölçü + handoff | `openCabinetStudio()`, `meemare-cabinet-bridge.js` |
| 3 | Birleşik proje JSON indirme | `downloadMeemareBundle()` (`.meemare.json`) |
| 4 | Giriş sonrası üst linkler | `public-gate-top-links.blade.php` |

## Tamamlanan (sıra 3)

| # | Özellik |
| --- | --- |
| 1 | OpenPlan statik build (`scripts/build-openplan-static.sh`) + `/openplan/*` |
| 2 | OpenPlan JSON → dolap ölçüsü (`openplan-import.js`) |
| 3 | Cabinet Studio otomatik mm (`cabinet-studio-handoff.js`, proxy sayfada) |
| 4 | Inspector üretim parçaları (`module.json` tablosu) |
| 5 | Cycles katalog önizleme (`scripts/render-y26-catalog-previews.py`, `?v=5`) |

OpenPlan build sunucuda bir kez çalıştırılmalı; yoksa `/plan` harici iframe’e düşer.