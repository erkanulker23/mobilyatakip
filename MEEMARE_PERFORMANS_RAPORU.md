# Meemare performans raporu

FPS, kare süresi, JS heap, GPU belleği, draw call ve sahne yükleme süresi bu turda ölçülmedi. Değer uydurulmadı.

## Ölçülen tek sayı

`buildParametricCabinet('y26_tek_01')` üçgen sayısı: 324. Bu, tek modülün parametrik mesh bütçesidir. 5, 10, 15, 20, 25, 30 ve 45 modüllük sahnede ölçülmedi. 324 × modül sayısı bir FPS tahmini değildir.

## Çalışan testlerin süresi

Vitest, geometri, gerçekçilik ve yerleştirme dosyaları: 35 test, yaklaşık 0.5–0.8 saniye. Bu, tarayıcı kare süresi değildir.

PHPUnit `ModulePlacementTest` ve `WardrobeProjectAccessTest`: 0.75 saniye, 5 test. Bu, API gecikmesi değildir.

OpenPlan üretim derlemesi bu turda tamamlandı. Süre yaklaşık 15 saniye. Bu, kullanıcının sayfa açılış süresi değildir.

## Darboğaz notu

Yerleştirme kodunda poz senkronu var. Bu turda taşıma sırasında mesh’in yeniden kurulup kurulmadığı ölçülmedi. Path tracing bütçeleri kodda 64 örnek için 120 saniye, 256 için 240, 512 için 360, 1024 için 480 saniye olarak yazılı. Bu tavanlardır. Gerçek süre ölçülmedi.

## Sonuç

| Modül sayısı | FPS | Kare süresi | Heap | GPU | Draw call | Durum |
|---|---|---|---|---|---|---|
| 1 | — | — | — | — | — | NOT RUN |
| 5 | — | — | — | — | — | NOT RUN |
| 10 | — | — | — | — | — | NOT RUN |
| 15 | — | — | — | — | — | NOT RUN |
| 20 | — | — | — | — | — | NOT RUN |
| 25 | — | — | — | — | — | NOT RUN |
| 30 | — | — | — | — | — | NOT RUN |
| 45 | — | — | — | — | — | NOT RUN |
