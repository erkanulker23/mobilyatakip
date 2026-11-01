# MEEMARE 3D Studio — İkinci aşama tarayıcı testi

Tarih: 1 Kasım 2026. Ortam: yerel `http://127.0.0.1:8765`, headless Chrome, Laravel oturumu. Üretim girişi kapatılmadı. Canlıya push yapılmadı.

Füme camın ilk malzeme değişikliği ayrı commit olarak duruyor: `b6d9b53`. Sonraki raf, aksesuar, kamera, sıkıştırma ve cam denemeleri commit edilmedi.

Test projeleri müşteri kaydı değildir. Adları `QA Denetim` ile başlar. Ötüken ve diğer müşteri projelerine dokunulmadı.

## Kanıt klasörü

- `test-results/module-renders/<modül>/` — 25 modül × 5 açı = 125 görüntü. Ek çekmece kareleri dahil toplam 141 jpg.
- `test-results/visual.json` — modül başına kapak ve çekmece düğmesi.
- `test-results/interior-signatures.json` — raf ve çekmece yükseklikleri.
- `test-results/renders/QA Denetim füme-render.png` — ilk path trace, 231 sn, 80 kare.
- `test-results/renders/fume-path-2.png` ve `fume-path-3.png` — sonraki iki path trace.
- `test-results/pages/` — yerleşim, füme raster, köşe üst, mobil 390.
- `test-results/qa-log.json`, `followup-log.json`, `perf.json`.

## Test sonuçları

| Test | Çalıştı mı | Sonuç | Kanıt |
|---|---|---|---|
| Yerel üye girişi | Evet | Başarılı. Oturum `/dashboard` açtı. | `qa-log.json` |
| 25 katalog kartı | Evet | 25/25 kart sahnede seçilebildi. | `visual.json` |
| 125 kontrol görüntüsü | Evet | 125/125 dosya yazıldı, betik hatası 0. | `module-renders/` |
| 270 cm, üç sürükle | Evet | Dolap 270 cm. | `qa-log.json` |
| 270 kayıt, yenileme, çıkış-giriş | Evet | Üretim listesi aynı kaldı. Proje `Nu8UagEpdd4F3NkHO86bnuYh`. | `qa-log.json` |
| 225 cm, dağınık bırakma | Evet | Başarısız. Toplam 135 cm. | `qa-log.json` |
| 225 cm, sola yaslı bırakma | Evet | Başarılı. Dolap 225 cm. | `followup-log.json` |
| 315 cm, dört modül | Evet, üç kez | Başarısız. Hep 225 cm, üç modül. Dördüncü düşmedi. | `followup-315.txt`, `followup-315c.txt` |
| Köşe + düz modül | Evet | Genişlik toplamı 225 cm. Üst kare tek L göstermiyor. | `pages/kose-top.jpg` |
| Tek tıkla seçim | Evet | Seçim adı `Çift kapılı V1`. | `followup-log.json` |
| Renkleri tümüne uygula | Evet | Sahnedeki 3 modülün hepsinde Antrasit yazıldı. | `followup-log.json` |
| Füme path trace | Evet, 3 kez | Kapak hâlâ düz siyah. İç görünmüyor. | `renders/` |
| Şeffaf raster | Evet | Raf bantları camın arkasında seçiliyor. | `pages/seffaf-raster.jpg` |
| Performans 1, 5, 10, 17 modül | Evet | Aşağıda. 20 modül duvara sığmadığı için yok. | `perf.json` |
| Mobil 390×844 | Evet | 3D sahne kadrajda yok. Ayar paneli ekranı kaplıyor. | `pages/mobile-390.jpg` |
| Modül sırasını sürükleyerek değiştirme | Hayır | Bu turda ayrı bir sıra değişimi kaydı yok. | — |
| Lake / ahşap dokusunun resmi katalogla piksel karşılaştırması | Hayır | Yapılmadı. | — |

## 25 modülün iç düzeni

Karşılaştırma dosya özeti değil. Her GLB’nin kök dönüşümünden sonraki raf yüksekliği, çekmece önü yüksekliği ve askılık sayısı çıkarıldı. 25 modülün 25’i de bu imzada farklı. Aynı raf dizisinin kopyası yok.

Köşe rafları aynı yükseklikte çift geliyor. Bu, L’nin iki kolundaki eş raf. Tek bir rafın kopyası sayılmadı.

| Modül | Raf yükseklikleri (mm) | Çekmece önü (mm) | Askılık |
|---|---|---|---|
| tek_v1 | 170, 850, 2110 | 345, 670 | 1 |
| tek_v2 | 160, 830, 1130, 1440, 1770, 2110 | — | 1 |
| tek_v3 | 160, 310, 2230 | 655, 945 | 1 |
| tek_v4 | 160, 1020, 1230, 1440, 2180 | 575, 745, 915 | 1 |
| tek_v5 | 170, 990, 1250, 2240 | 625, 855 | 1 |
| tek_6 | 340, 640, 940, 1240, 1540, 1840, 2135 | — | 0 |
| tek_7 | 1240, 2135 | — | 2 |
| tek_8 | 310…2135, sekiz raf | — | 0 |
| tek_9 | 1190, 1490, 2135 | 352, 562, 772, 982 | 1 |
| tek_10 | 400, 2135 | — | 1 |
| cift_v1 | 160, 1440, 1740, 2150 | 665, 935, 1205 | 1 |
| cift_v2 | 160, 1280, 1580, 1850, 2170 | 720, 1000 | 1 |
| cift_v3 | 160, 850, 1170, 1490, 1810, 2200 | 660, 960, 1260, 1560 | 2 |
| cift_v4 | 160, 860, 1190, 1520, 1860, 2150 | 665, 935 | 1 |
| cift_v5 | 160, 1440, 1770, 2170 | 720, 1020 | 3 |
| cift_6 | 2135 | 380, 585 | 2 |
| cift_7 | 400…2135, yedi raf | 370, 615, 860, 1105 | 1 |
| cift_8 | 1120, 2135 | 385, 600, 815 | 3 |
| cift_9 | on raf yüksekliği | 270, 415 | 0 |
| cift_10 | 980, 2135 | 308, 508 | 3 |
| kose_1 | 430, 830, 1230, 1630, 2050, her biri çift | — | 0 |
| kose_2 | 450, 1100, 2050, çift | — | 1 |
| kose_3 | 340, 750, 1200, 2050, çift | 248, 408 | 1 |
| kose_4 | 340, 610, 880, 1150, 1420, 2050, çift | — | 0 |
| kose_5 | 400, 900, 1500, 2050, çift | — | 1 |

`tek_v1` ile `tek_v3` aynı parça adlarını taşır. Raf ve çekmece yükseklikleri farklıdır.

Görüntü incelemesi, 125 karenin tamamı tek tek estetik puanlanmadı. Bakılan kareler:

- `tek_v1` kapalı: düz kapak, seçim çerçevesi yeşil.
- `tek_v1` açık: kapak sola döndü, içte raf ve çekmece kutusu var.
- `cift_10` açık: iki kapak, askılık boruları, raf, çekmece. Kıyafet, askı ve ayakkabı yok.
- `cift_v1` sağ 45: kapalı gövde duruyor.
- `kose_1` ön: kapak yok, raflar görünüyor. Köşe modülünde kapak düğmesi yok; beş köşenin hepsi böyle.
- `kose-top.jpg`: üstten bakış tek birleşik L değil. Parçalar ayrı kütleler gibi duruyor.

## Malzeme

Yeni projede üretim listesi şunu yazdı: gövde Lake Beyaz D129, dolap Vizon(Mink) D122, arkalık Opak Beyaz D152, raf Bronz D145. Bu kodlar Kastamonu katalog kaydından geliyor. Doku, katalogdaki ürün görseli. Bu turda resmi sayfayla piksel karşılaştırması yapılmadı. Arayüzde “temsili doku” yazısı yok.

Şeffaf camın raster görüntüsünde raf çizgileri seçiliyor. Füme, üç path trace’te de düz siyah kapak. `b6d9b53` geçirgenliği artırmıştı; bu yetmedi. Sonraki denemede cam kalınlığı 0,6 cm’ye indirildi ve füme renk sönüm rengine alındı. `fume-path-3.png` yine siyah. Path trace füme içini göstermiyor. Düzeltildi denmez.

## Yerleşim

270 cm’lik duvara üç 90 cm modül sürüklenince toplam 270 cm oldu. Kayıt, sayfa yenileme ve çıkıştan sonra aynı liste geldi.

225 cm, üç modül aynı sol bölgeye bırakılınca 225 cm oldu. Aynı modüller ekrana dağınık bırakılınca arada boşluk kaldı ve toplam 135 cm’de durdu.

315 cm dört kez denendi, üçünde de sonuç 225 cm ve üç modül. Dördüncü modül sahneye girmiyor. Sığmayan modülde boşlukları kapatıp yeniden deneme kodu eklendi (`v=90`). Aynı bırakma ile tekrar test yine 225 cm. Bu hata açık.

Köşe testi genişlik toplamını yazıyor. Üst görüntü L birleşimini doğrulamıyor.

## Eklenen ve doğrulanan işlevler

- Ön, sol, sağ, sol 45, sağ 45, üst ve perspektif düğmeleri görüntü turunda kullanıldı.
- “Renkleri tümüne uygula”, sahnedeki modüllere Antrasit yazdı.
- Kısa tıklama modül seçti.
- Düz modüllerde “Kapağı aç” 20 modülde çalıştı. Beş köşede bu düğme yok; kapak ayrı parça değil.
- Çekmece düğmesi 16 modülde var. `tek_v1-drawers.jpg` altta koyu bir kütle gösteriyor. Kapak kapalıyken çekmece öne temiz çıkmıyor. Mekanizma eksiksiz değil.

## Performans

Ölçüm headless Chrome, bu Mac, GLB’ler önceden yüklü. Süre, belirtilen adede kadar tıklayarak ekleme süresidir. FPS, bir saniyelik `requestAnimationFrame` sayısıdır. Bellek `performance.memory` JS yığınıdır, GPU değil.

| Modül | Ekleme | FPS | JS yığın |
|---|---|---|---|
| 1 | 1,7 sn | 61 | 30 MB |
| 5 | 3,9 sn | 61 | 34 MB |
| 10 | 4,6 sn | 62 | 22 MB |
| 17 | 6,1 sn | 61 | 26 MB |

17 × 45 cm = 765 cm. Ön duvar sınırı 800 cm. 20 × 45 cm = 900 cm olduğu için 20 modül ölçülmedi. Mobilde 17 modüllük projenin özeti 765 cm yazıyor; 3D alanı görünmüyor.

## Açık kalanlar

1. Füme path trace içi göstermiyor.
2. 315 cm’lik dört modül sürüklemesi dördüncü modülü almıyor.
3. Köşe üst görünümü birleşik L değil.
4. 390 px genişlikte sahne, ayar panelinin altında kalıyor. Üst düğmeler kesiliyor.
5. Çekmece, kapak kapalıyken anlaşılır bir çekmece gibi çıkmıyor.
6. Modül sırasını elde sürükleyerek değiştirme bu turda kayda geçmedi.

Canlıya alma koşulu bu listede sağlanmıyor.
