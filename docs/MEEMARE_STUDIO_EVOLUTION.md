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

## Sıradaki büyük adımlar

1. Self-hosted OpenPlan (`tools/openplan3d` build → `/plan` static)
2. Üretim dolabına seçili modülden DXF parametreleri
3. Plan/stüdyo tek proje JSON (duvar + modül + malzeme)
4. Cabinet Studio panel mantığını tekil modül inspector’a taşıma