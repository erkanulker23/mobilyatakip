# Kullanıcı deneyimi

Tarih: 2026-11-16 (QA-UX-002 güncelleme)  
Kanıt: `test-results/master-e2e/module-placement/ux002-focus-after-add.png` · `test-results/reference-quality/shots/mobile-390.jpg`

Bu bir puan kartı değildir.

## Düzelenler

**QA-UX-002 (2026-11-16):** `focusSelectedModule` artık yükseklik FOV mesafesi + oda sınırı clamp kullanıyor; sağ köşeye yaslı tek modülde yan duvar yüzeyi yerine gövde+zemin görünüyor (`camDebug.dist≈502`, `open` açık tarafa bakıyor).

Modül eklenince kamera, GLB yolunda zaten kadrajlıyordu. Aynı kadraj artık CAD ve diğer ekleme yollarında da çalışıyor.

Telefonda modül seçmek malzeme sayfasını kendiliğinden açmıyor. Sayfa yüksekliği `min(42dvh, 380px)`. 390 × 844 emülasyonda tuval 390 × 729 px ölçüldü. Malzeme sayfası kapalıydı. Dolap görünür kaldı. Görüntü: `shots/mobile-390.jpg`

Kayıt hata verirse kullanıcıya tasarımın ekranda durduğu söyleniyor. Belirteç yokken tarayıcı taslağı yenilemede geri geliyor. Testte iki modül yenilemeden sonra duruyordu.

Model dosyası açılmazsa sahne silinmiyor. Mesaj başka modül seçilebileceğini söylüyor.

## Durum

İlk kez açan kişiye adım adım anlatım yok. Katalog kartları duruyor. Ölçü kutuları hâlâ aralık dışını sessizce sınıra çekiyor. Bu turda o davranış değiştirilmedi.

390 px emülasyonda kamera düğmeleri dolabın üstüne biniyor. Altta seçim düğmeleri de duruyor. Sahne görünür, fakat düğmeler ürünün bir kısmını kapatıyor. Bu gerçek telefon testi değildir.

Sürükle-bırak bu turda ayrıca 50 kez tekrarlanmadı. Ekleme ve silme tekrarlandı.

Sunucuya kaydetme bu veritabanında 503 dönüyor. Kullanıcı Kaydet’e basınca tasarım ekranda kalır, hesaba yazılmaz. Migration uygulanmadan kaydetme tamamlanmış sayılmaz.

Geri al ve ileri al, testte modül sayısını 2, 1, 2 diye değiştirdi.
