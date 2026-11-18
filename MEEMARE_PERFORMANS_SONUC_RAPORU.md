# Performans sonuç raporu

Tarih: 17 Kasım 2026. Ölçüm yeri: Cursor gömülü Chromium, yerel stüdyo, embed `v=324`. Tuval 692×901. Çizim, sahne kirlenince yapılıyor. Boşta geçen süre FPS sayılmadı.

## Ölçülen tek sahne

İzole test projesi, iki dolap.

| Ölçü | Değer | Kaynak |
|---|---|---|
| Draw call | 110 | tuval `dataset.drawCalls` |
| Üçgen | 1208 | tuval `dataset.triangles` |
| Tek modül üçgeni (`y26_tek_01`) | 372 | `geometry-audit.json` `trianglesOneModule` |
| Tuval | 692×901 | WebGL tuvali |
| Konsol hatası | bu okumada yakalanmadı | sahne açılışı |

Eski 324 üçgen ölçüsü çekmece panellerinden öncedir. Güncel tek modül sayısı 372'dir.

## İstenen seri

1, 5, 10, 15, 20, 25, 30 ve 45 modül için FPS, kare süresi, yığın, GPU belleği, doku sayısı, sahne yükleme, modül ekleme ve renk değiştirme ölçülmedi. Sayı uydurulmadı.

| Modül | FPS | Kare süresi | Yığın | GPU | Durum |
|---:|---|---|---|---|---|
| 1 | — | — | — | — | NOT RUN |
| 5 | — | — | — | — | NOT RUN |
| 10 | — | — | — | — | NOT RUN |
| 15 | — | — | — | — | NOT RUN |
| 20 | — | — | — | — | NOT RUN |
| 25 | — | — | — | — | NOT RUN |
| 30 | — | — | — | — | NOT RUN |
| 45 | — | — | — | — | NOT RUN |

Path tracing süreleri performans tablosuna karıştırılmadı. Onlar `MEEMARE_RENDER_KALITE_KARSILASTIRMASI.md` içindedir. Hızlı hedef 64 örnek, 1229×1600 tuvalde 120 saniyelik bütçenin üstüne çıktı: mevcut kamerada 156,8 sn ve 38 örnek, ön kadrajda 243,8 sn ve 21 örnek.

Bu turda FPS'i yükseltmek için sahne kodu değiştirilmedi. Darboğaz ölçülmeden optimizasyon uygulanmadı.
