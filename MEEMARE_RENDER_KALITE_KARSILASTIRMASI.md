# WebGL ve path tracing karşılaştırması

Tarih: 17 Kasım 2026. Motor: `three-gpu-pathtracer` 0.0.24, stüdyo ekranındaki Render al düğmesi. Sahne: izole test projesi, iki dolap, parametrik gövde, kapaksız gri. Tarayıcı: Cursor gömülü Chromium. Çözünürlük isteği 1600 uzun kenar. Tuval dikey olduğu için çıktı 1229×1600 oldu. GPU küçültmesi yok (`limited` yalnız örnek bütçesinden).

Fotogerçekçilik iddia edilmez. Path tracing, WebGL önizlemeden daha kötü duruyor.

## Ölçülen kareler

| Dosya | Ne | Örnek | Süre | Piksel | Sonuç |
|---|---|---|---:|---|---|
| `test-results/meemare/final/webgl-front.png` | Aynı sahnenin WebGL tuvali | — | anlık | 692×901 | İki dolap önden, raf, çekmece, askı ve arkalık okunuyor |
| `test-results/meemare/final/webgl-viewport-v323.png` | Araç çubuklu ekran | — | anlık | ekran görüntüsü | Duvar kadrajı kapatmıyor |
| `test-results/meemare/final/pathtrace-64-front-gray.png` | Eski ön kadraj | 64/64 | 12,5 sn | 1229×1600 | Dolap yok. Mavi-gri bir yüzey kadrajı dolduruyor. Kamera karşı duvara geçmiş |
| `test-results/meemare/final/pathtrace-64-current.png` | Mevcut kamera | 38/64 | 156,8 sn | 1229×1600 | Dolaplar var. Karanlık, yoğun kumlanma |
| `test-results/meemare/final/pathtrace-64-front.png` | Düzeltilmiş ön kadraj | 21/64 | 243,8 sn | 1229×1600 | Dolaplar var. Üst kesik, karanlık, dikey parlak bir çizgi var |

İlk gri kare, path tracing motorunun çalıştığını gösterir ve kadrajın yanlış olduğunu da gösterir. Sığdırma formülü `sin` ile küre yarıçapı kullanıyordu; göz, oda sınırının dışına çıkıp duvara dayanıyordu. Formül görüş açısının tanjantına çevrildi. Ardından alınan ön kare dolapları gösteriyor.

## Karşılaştırma

WebGL karesinde gövde açık gri, arkalık sıcak ahşap, çekmece blokları ve raflar ayrılıyor. Işık nötr ve sahne okunuyor.

Path tracing karelerinde aynı iç düzen seçiliyor: solda askı, raflar ve iki çekmece; sağda dikme, raflar ve çekmece yığını. Aydınlık yetersiz. 21 ve 38 örnek, 1229×1600 tuvalde bütçe dolmadan bitmiyor. Gürültü giderici çalıştı (4 örnekten sonra açılıyor) ama kumu temizlemedi. Cam ve metal bu sahnede yok; o malzemeler denenmedi.

Dikey parlak çizgi, ön kadraj karesinde sağ dolabın bölmesinin ortasında duruyor. WebGL karesinde bu kadar parlak bir hat yok. Büyük ihtimalle dikme üzerindeki emissive LED şeridi. Ayrıca bakılacak açık görüntü hatasıdır.

## Çalıştırılmayan kombinasyonlar

64, 256, 512 ve 1024 örneğin 1600, 1920×1080, 2560×1440 ve 3840×2160 ile eşlenmesi yapılmadı. 16 kombinasyon kör çalıştırılmadı. İptal düğmesi görünüyor, bu turda basılmadı. Render bittikten sonra tuval duruyor: draw call 110, üçgen 1208.

Kalite iyileşmesi kanıtlanmış değildir.
