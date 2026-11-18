# Render kalitesi ve eksik testler

Tarih: 17 Kasım 2026. Embed `v=326`. Geometri, 45 model düzeni ve yerleşim kodu bu turda değiştirilmedi. Üretim onayı ve CNC kapalı kaldı. İzole test projesi malzeme denemesi için kullanıldı; müşteri projesi açılmadı.

## Path tracing neden karanlıktı

Hipotezler ayrı ayrı ölçüldü. Işık şiddeti bu turda değiştirilmedi. Aynı 7 nit panel duruyor. Yalnız yeri değişti.

| Hipotez | Sonuç |
|---|---|
| Mesh'ler sahneye giriyor mu | Evet. 118 görünür mesh |
| Normal eksik mi | Hayır. 0 |
| UV eksik mi | Hayır. 0 |
| Materyal uyumu | 163 MeshStandard, bu sahnede 0 fiziksel cam |
| HDRI yükleniyor mu | Evet. `studio_small_09_1k.hdr`, teşhis `hdri: true` |
| Ortam ve yarıküre ışığı | Sahnede 1 ortam + 1 yarıküre var. `three-gpu-pathtracer` `getLights` bunları almıyor |
| Yönlü ışıklar | 3 adet var. Tavan gerçek gölge ışınıyla iç hacmi kesiyor |
| Panel ışığı | Eski yer çatıdaydı, üst panel iç hacmi kapatıyordu. Kamera tarafına alındı |
| Exposure | 1.08, önceki karanlık karede de buydu. Karanlığın nedeni bu değil |
| Renk uzayı / denoise | Ham ve denoise ortalama parlaklık 178,8 ve 179,4. Denoise görüntüyü söndürmüyor |
| Çözünürlük | İstek 1600 uzun kenar. Dikey tuval olduğu için çıktı 1229×1600. GPU küçültmesi yok |
| Örnek birikimi | Gerçek. 53/64 örnek, 1.401.576 ms |
| Kamera | “Mevcut kamera”. WebGL ile aynı kadraj |

Eski path tracing ortalama parlaklık 41,7. Panel kameranın önüne alınınca 179,4. Aynı kadrajın WebGL ortalaması 190,0. Karanlık iç hacmin nedeni buydu: tracer'ın görmediği dolgu ışığı ve çatıda kalan panel.

## Karşılaştırma

| Çıktı | Örnek | Süre | Piksel | Ortalama parlaklık | Durum |
|---|---:|---:|---|---:|---|
| WebGL, aynı kamera | — | anlık | 692×901 | 190,0 | PASS |
| Eski path tracing, çatı paneli | 38/64 | 156,8 sn | 1229×1600 | 41,7 | FAIL, karanlık |
| Yeni panel, denoise öncesi | 21/64 | 226,8 sn | 1229×1600 | 178,8 | PASS, aydınlık, kumlu |
| Yeni panel, denoise | 21/64 | aynı iz + 0,2 sn | 1229×1600 | 179,4 | PASS, denoise bozmadı |
| Yeni panel, uzun bütçe | 53/64 | 1.401,6 sn | 1229×1600 | görüntü okunuyor | PARTIAL |

Dosyalar: `test-results/meemare/final/webgl-same-camera.png`, `pathtrace-64-current.png`, `pathtrace-softbox-64-raw.png`, `pathtrace-softbox-64.png`, `pathtrace-64-complete.png`.

53 örnekte arkalık, raf, çekmece ve askı seçiliyor. İnce kum duruyor. 64 tamamlanmadı. Bütçe 780 sn yazılmasına rağmen ölçülen iz 1.402 sn sürdü; döngü hedef süreden uzun taşıyor.

256, 512 ve 1024 örnek başlatılmadı. 53 örneğin 1.402 sn sürmesinden giderse 256 örnek yaklaşık 1,9 saat, 512 örnek 3,8 saat, 1024 örnek 7,5 saat. Bu çözünürlükte BLOCKED.

1920×1080, 2560×1440 ve 3840×2160 bu turda çalıştırılmadı. NOT RUN.

Işık ayarı kaydı: panel `#fff4e8`, 7 nit, kamera tarafı. Ortam şiddeti 0,8. Exposure 1,08. HDRI yüklü. Yönlü ışıklar tracer'a girer, ortam ve yarıküre girmez.

## Malzeme, tarayıcı

Seçilen dolap Çift kapılı V3. Diğer dolap gri kaldı. Renk yalnız seçili gövdeye işledi.

| Seçim | WebGL | Path tracing |
|---|---|---|
| Ahşap kapak | PASS. Krem kapak ve kulp | NOT RUN |
| Şeffaf cam | PASS. Raflar camın arkasında | NOT RUN |
| Füme cam | PASS. İç düzen camın arkasında | NOT RUN |
| Bronz cam | PASS. Bronz çıta, iç görünür | NOT RUN |
| Gold çıta | PASS. Sarı metal çerçeve | NOT RUN |
| Lake Beyaz | PASS. Seçili gövde krem, diğeri gri | NOT RUN |
| Inox | NOT RUN. Kayıt, şeffaf kareyle aynı bayt | NOT RUN |
| MDF adlı ayrı kontrol | Arayüzde yok | NOT RUN |
| Mat lake / parlak lake ayrımı | Ayrı iki düğme yok. Lake Beyaz denendi | NOT RUN |

Kanıt: `mat-ahsap.png`, `mat-seffaf.png`, `mat-fume.png`, `mat-bronz-cam.png`, `mat-gold.png`, `mat-lake-beyaz.png`.

## GLB

Orijinal GLB düğmesine basıldı. 2,5 sn içinde `.glb` isteği oluşmadı. 45 model tarayıcıda açılmadı. Önceki birim testte 45 dosyanın loader ile ayrışması duruyor; bu turun tarayıcı sonucu FAIL.

## Kayıt ve geri açma

NOT RUN. Beş modül, yenileme, konum ve malzeme karşılaştırması bu turda yapılmadı.

## Mobil ve performans

Yalnız 390×844 emülasyonu. `innerWidth` 390, `scrollWidth` 390, yatay taşma yok. Kaydet, 2D/3D, render paneli ve renk kartları görünüyor. Bu telefon testi değildir.

375, 430, 768, 1440 ve 1920 NOT RUN.

1, 5, 10, 15, 20, 25, 30 ve 45 modül FPS serisi NOT RUN. Tek sahne ölçüsü duruyor: 110 draw call, 1208 üçgen, tuval 692×901. FPS uydurulmadı.

## Köşe

Beş köşe modelinin ön, üst ve çapraz görüntüleri NOT RUN. Geometri tablosu görsel doğrulama sayılmaz.

## Render iptali

Başlatıldı, İptal basıldı. Metin: “Render iptal edildi. Sahne duruyor.” Ardından tuval duruyor: 110 draw call, 1208 üçgen. PASS.

Render sırasında kamera, kayıt, eksik doku, eksik HDRI ve WebGL bağlam kaybı NOT RUN. Bu sahnede HDRI vardı.

## Kalan

- 64 örneğin 11 eksiği ve bütçenin ölçülen süreyi kesmemesi.
- 256 ve üzeri örnek bu GPU süresinde BLOCKED.
- Cam ve metalin path tracing karesi yok.
- 45 modelin tarayıcı turu, kayıt turu, köşe fotoğrafları ve FPS serisi yok.
- İzole projedeki seçili dolap Lake Beyaz denemesinden sonra krem gövdeye dönmüş olabilir.
