# Meemare profesyonellik karnesi

Tarih: 2 Kasım 2026. Ortam: yerel `http://127.0.0.1:8765`, commit `98ef714`. Puan, bu denetimde ölçülen davranışa göredir. Test edilmeyen başlığa puan yazılmadı.

| # | Başlık | Puan | Profesyonel bulunan | Amatör bulunan |
|---|---|---|---|---|
| 1 | Yazılım mimarisi | 4/10 | Laravel 12, ayrı katalog JSON, throttle ve form doğrulaması var. | Stüdyo tek dosyada 6787 satır. Three CDN'den. Sahne, yerleşim, malzeme ve kayıt aynı kapsamda. |
| 2 | Kullanıcı deneyimi | 5/10 | Masaüstünde katalog, 2B/3B, kılavuz, genişlik ve yükseklik alanı duruyor. | Yeni dolap kadraja alınmıyor. Özet, duvar doluluğu değil bütün modüllerin genişlik toplamı. |
| 3 | Görsel arayüz | 5/10 | Masaüstü düzen okunaklı. Boş oda ve katalog kartı düzgün. | Önizlemeler teknik render. Mobilde sayfa sahneyi kapatıyor. |
| 4 | Mobil kullanım | Test edilmedi | Emülasyon görüntüsü var. | Gerçek iPhone, Android ve tablet yok. Puan verilmedi. |
| 5 | Modül kataloğu | 6/10 | 45 kart, 45 önizleme, 45 GLB. Dış ölçüler katalogla uyumlu. | Kapak mesh'i yok. Altı çift modülde çekmece kutusu dikmeyle çakışıyor. |
| 6 | Modül yerleştirme | 4/10 | 180 cm duvarda dört adet 45 cm modül, toplam 180 cm, görüntüde dört dolap. | 225–785 cm senaryoları sahne silinemediği için geçersiz. Kapak salınım payı yok. |
| 7 | Geometri doğruluğu | 5/10 | Dış genişlik, yükseklik ve derinlik sapması 8 mm içinde. Yan ve raf 18 mm. | Çekmece kutusu / dikme çarpışması. Köşe panelleri aynı hacmi paylaşıyor. |
| 8 | Mobilya üretilebilirliği | 3/10 | Kalınlıkların bir kısmı 18 ve 8 mm diye ayrılmış. | Pim, menteşe, bağlantı ve gerçek ray yok. DXF yok. Çakışan kutu kesilemez. |
| 9 | Kapak mekanizmaları | Test edilmedi | Kapak seçici ve açma düğmesi kodda var. | Bu turda açma, kapama ve menteşe yönü tıklanmadı. |
| 10 | Çekmece mekanizmaları | Test edilmedi | Çekmece parçaları GLB'de var. | Açma, kapama ve ray çarpışması tıklanmadı. |
| 11 | Malzeme doğruluğu | 4/10 | Arayüz dekoru temsili diye yazıyor. Gruplar gövde, kapak, raf, arkalık, çekmece diye ayrılmış. | Renklerin birbirine karışmadığı bu turda denenmedi. Kastamonu taraması resmi değil. |
| 12 | 3D gerçekçilik | 3/10 | Ahşap görseli panelde okunuyor. Köşe V1 L olarak görünüyor. | Menteşe, pim, kulp ve derz yok. Yeni modül kapaksız. Işık düz. |
| 13 | Ultra Render kalitesi | 2/10 | Path trace ayrı bir eylem. Cam kalınlığı sıfırlanarak siyah hacim yolu kapatılmaya çalışılmış. | Kullanıcı görseli benekli. Köşe yüzü siyah. Varsayılan 48 örnek ve 2 piksel denoise. |
| 14 | Performans | Test edilmedi | 1, 3, 5, 10 ve 17 modülde kare sayacı 61'de doydu. | Bu, ekran tazeleme sınırı. GPU süresi, draw call ve texture belleği ölçülmedi. |
| 15 | Kaydetme ve veri güvenliği | 3/10 | QA projesi oluştu, yenilemede aynı 785 / 765 cm özeti döndü, silme HTTP 200. | Proje listesi başkasının telefonunu ve belirtecini veriyor. Belirteçte sahip kontrolü yok. |
| 16 | Dışa aktarma | 2/10 | Ekranda üretim cümlesi var. | İndirilen DXF, DWG veya doğrulanmış kesim listesi yok. |
| 17 | Ölçeklenebilirlik | 3/10 | 45 kart tek istekte geldi. | 100 ve 500 modül ölçülmedi. Katalog DOM'a toplu basılıyor. Tek JS dosyası büyümüş. |
| 18 | Ticari kullanıma hazır olma | 3/10 | Tasarım oturumu açılıyor, modül ekleniyor, ölçü özeti var. | Veri sızıntısı, çakışan gövde, fotoğraf olmayan render ve üretim dosyası eksik. |

Genel sonuç: çekirdek tasarım oturumu kullanılabilir. Uluslararası özel üretim platformu olarak hazır değil.
