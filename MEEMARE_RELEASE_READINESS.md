# Meemare yayın kararı

Tarih: 2 Kasım 2026. Karar: ticari yayın için hazır değil.

Bu karar, yerel stüdyoda yapılan ölçüm ve tek bir QA projesine dayanır. Canlıya push yapılmadı. Müşteri projesi açılmadı. QA kaydı `2DdKtoSdqZswVLtynCYz7gsb` denetim sonunda silindi. HTTP 200.

## Yayını durduran işler

1. `QA-SEC-001` ve `QA-SEC-002`. Oturumlu her kullanıcı proje listesinden belirteç ve telefon alabiliyor. Belirteci olan silebiliyor.
2. `QA-GEO-001`. Altı Y26 çift modülde çekmece kutusu orta dikmeden geçiyor.
3. `QA-RENDER-001`. Varsayılan render benekli. Köşe yüzü kullanıcı görselinde siyah.
4. `QA-PROD-001`. Kesim dosyası, menteşe, raf pimi ve gerçek ray yok. Üretime gönderilemez.
5. `QA-CORNER-001`. Düz modül köşede başka modele dönüşüyor.

## Yayınlanabilir sayılmayan ama hemen güvenlik kadar acil olmayan işler

- 225 cm ve üzeri duvar senaryoları temiz sahnede tekrarlanacak.
- Kapak ve çekmece hareketi modül modül denenecek.
- Gerçek telefonda 3D alanın görünür kaldığı doğrulanacak.
- GPU süresi ve draw call ölçülecek.

## Bu turda çalışanlar

- Giriş sonrası tasarım sayfası açılıyor.
- Katalogda 45 kart var.
- 45 GLB yükleniyor. Dış ölçü katalogla uyumlu.
- 180 cm duvarda dört 45 cm modül, toplam 180 cm.
- Köşe V1, seçilince L gövde olarak görünüyor.
- QA projesi kaydolup yenilemede aynı özeti getiriyor.

## Sıradaki geliştirme

1. Proje listesini ve belirteci sahibe bağla.
2. Altı Y26 çekmece kutusunu dikmenin dışına al.
3. Köşedeki düz modülü kendi GLB'si olarak bırak.
4. Render örnek sayısını ve denoise yarıçapını yükselt. Aynı kadrajda yeni görüntü al.
5. Yerleşim testini boş sahneyle 180, 225, 270, 315, 360, 413, 440, 550 ve 785 cm için tekrarla.
6. Kapak ve çekmeceyi her modülde açıp kapat.
7. Üretim parçalarını bağlantısız kesim listesinden ayır. DXF'yi hazır diye işaretleme.
