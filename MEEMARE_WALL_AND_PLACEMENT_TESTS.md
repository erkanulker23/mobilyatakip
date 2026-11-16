# MEEMARE duvar ve yerleşim testleri

Tarih: 16 Kasım 2026.

## Canlı L odası

Yenilenen taslak. Ölçüler ve ofsetler `window.__exportState` ile okundu.

| Modül | Duvar | Genişlik | Ofset | Bitiş |
| --- | --- | --- | --- | --- |
| kose_2, sol köşe | front | 90 | 10000 (yerleşim işareti) | görsel başlangıç 0 |
| y26_cift_06 | front | 90 | 140 | 230 |
| y26_cift_01 | front | 90 | 230 | 320 |
| y26_tek_06 | left | 45 | 210 | 255 |
| y26_tek_01 | left | 45 | 255 | 300 |

Ön duvar 320 cm. Köşe 0–90, sonra 50 cm boşluk, sonra iki 90’lık modül 140’ta ve 230’da, bitiş 320. Üst üste binme yok. Sol duvar 300 cm; iki 45’lik modül 210’da başlayıp 300’de bitiyor. Sağ duvar 280 cm, modül yok.

Köşe başka bir modele dönüşmedi: `kind: corner`, `glb: kose_2`, `cornerSide: left`.

## 180 cm duvar

Dört `y26_tek_01` (45 cm): ofset 0, 45, 90, 135. Toplam 180. Beşinci ekleme reddedildi, uyarı metni göründü. Geri al ile 320 cm ve 5 modül geri geldi.

## Birim test, dokuz duvar

`tests/js/wardrobe-layout.test.mjs`. 90 cm modüller, duvarlar 180, 225, 270, 315, 360, 413, 440, 550, 785 cm.

| Duvar | Sığan 90 cm | Bir fazla | Durum |
| --- | --- | --- | --- |
| 180 | 2 | reddedildi | PASS (node) |
| 225 | 2 | reddedildi | PASS (node) |
| 270 | 3 | reddedildi | PASS (node) |
| 315 | 3 | reddedildi | PASS (node) |
| 360 | 4 | reddedildi | PASS (node) |
| 413 | 4 | reddedildi | PASS (node) |
| 440 | 4 | reddedildi | PASS (node) |
| 550 | 6 | reddedildi | PASS (node) |
| 785 | 8 | reddedildi | PASS (node) |

Aynı dosyada: sağ köşe varken sağ duvar modülü 90 cm’den önce başlamıyor; kapı açıklığı 10–120 cm aralığını kapatıyor; aynı tarafta iki köşe `null`. Bunlar tarayıcıda tekrar tıklanmadı: birim **PASS**, tarayıcı **NOT_TESTED**.

Tarayıcıda ayrıca 360 cm denendi: ön duvar ve kroki sağ duvar x’i 360 oldu, geri al 320’ye döndü.

## Kroki ile 3D

Yüklenen taslakta kroki ön duvarı 300 cm, 3D ön duvar 320 cm idi. Sağ kroki x=300, 3D sağ duvar 280 cm. Ayrışma vardı.

`syncSketchWalls` artık eksenel ön/sol/sağ krokisini 3D ölçüye çeker. Yenilemeden sonra kroki: ön 0–320, sol 0–300, sağ x=320 ve boy 280. 320’yi 360 yapmak sağ duvarın x’ini de 360 yaptı. Geri al krokisini de geri aldı.

U kanatları (`role: other`) 3D’de kutu olarak duruyor. Ölçüm:

| Kanat | Konum (cm) | Boyut |
| --- | --- | --- |
| sol | x 55, z 300, y 140 | 110 × 280 × 8 |
| sağ | x 265, z 280, y 140 | 110 × 280 × 8 |

Görüntü: `test-results/meemare-2026-11-16/meemare-u-kanatlar.png`. Kamera kanatların üstüne baktı; iki duvar ucu karenin altında. Kanatlar modül taşımaz, yalnızca duvar gövdesidir.

## Yapılmayan

Tek duvarı fareyle çizmek, çizimi iptal, duvar silmek, sürükleyerek 4 cm yapışma, kapakların komşuya çarpması. **NOT_TESTED**.
