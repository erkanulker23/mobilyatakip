# Yerleşim motoru

Otomatik test, `tests/js/wardrobe-layout.test.mjs`, 16 Kasım 2026’da 7 geçti.

| Vaka | Beklenen | Sonuç | Nerede |
| --- | --- | --- | --- |
| 180, 225, 270, 315, 360, 413, 440, 550, 785 cm | 90 cm modüller sığar, biri fazla reddedilir | PASS | node |
| Sağ köşe payı | sağ modül 90 cm’den önce başlamaz | PASS | node |
| Kapı açıklığı | 10–120 cm aralığı boş kalır | PASS | node |
| 3,9 / 4,0 / 4,1 cm | 3,9 yapışır, 4,0 ve 4,1 kalır | PASS | node, fare değil |
| 180 cm, dört adet 45 cm | beşinci reddedilir | önceki oturumda tarayıcı PASS | bu oturumda tekrar tıklanmadı |
| 315, 413, 440, 785 kombinasyonları | istenen boşluklar | NOT_TESTED tarayıcı | |
| Sürükle-bırak | sahne ile state aynı | NOT_TESTED | |
