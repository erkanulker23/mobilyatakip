# Yayın kapısı

Tarih: 2026-11-02  
Durum: tasarım. Bu kapı bugün dağıtımı durdurmuyor. Canlı sürüm `98ef714`. Yerel güvenlik commit’i `dbe7367` push edilmedi.

Test edilmeyen madde geçti sayılmaz.

## Kapı tablosu

| Kapı | Komut veya kanıt | Bugünkü sonuç | Dağıtım |
| --- | --- | --- | --- |
| Yetkisiz proje | `php artisan test --filter=WardrobeProjectAccessTest` | PASS, 56 assertion, sqlite | Kod bağlı veritabanında `userId` olmadan çalışmaz. Migration uygulanmadan deploy yok |
| Kaynak kesişim | `scripts/qa-master-geometry.py` | 6 modül FAIL | Kırmızıysa katalog “üretime hazır” işaretlenmez |
| Kapak tanımı | Aynı betik, kapak alanı | 45 GLB’de kapak düğümü yok | Kapak tanımı yazılmadan kapaklı ürün denmez |
| Yerleşim | Dokuz duvar, boş sahne | Yalnız 180 cm temiz sayıldı | Diğer ölçüler BLOCKED iken yerleşim geçti denmez |
| Kayıt bütünlüğü | Sahip kaydeder, kapatır, açar | Güvenlik testinde API gövdesi doğrulandı. Sahne konumları doğrulanmadı | Konum farkı varsa geçti denmez |
| Render | Aynı kadraj, raster ve path | Yeni trace yok. Kullanıcı görselinde siyah yüz ve benek var | Eşik ölçülmeden kaliteli denmez |
| Fiyat | Fiyat defteri testi | Test yok | Tutar gösteren sürüm çıkmaz |
| DWG ve CNC | İndirilen dosya | DWG yok. CNC doğrulaması yok | Menüde bu sözler yok |

## Modül kataloğa girmeden

Yeni bir modül şu maddeler yeşil olmadan listeye eklenmez:

- GLB açılıyor
- Dış ölçü katalog miliyle, tolerans 1 mm
- Yan, tabla ve raf kalınlığı tanımla aynı
- İç AABB örtüşmesi, dikme ile çekmece veya raf için 0 mm
- Kapak tanımı var veya ürün kapaksız diye işaretli
- Çekmece varsa ön ve kutu hareket aralığı dikmeyi kesmiyor
- Önizleme dosyası var ve sahne aynı kapak durumunu gösteriyor
- Malzeme kanalı tanım kimliğine bağlı
- Kayıt ve aynı kullanıcıyla geri açma sahne konumunu koruyor

Bu liste bir CI işi değildir. Betiğin bir kısmı `qa-master-geometry.py` içindedir. Hareket, kayıt ve malzeme kanalı otomatik değildir. Onlar elle doğrulanana kadar `NOT_TESTED` kalır.

## Her değişiklik

Güvenlik testi, pilot geometri ölçümü ve boş sahne yerleşimi yeniden çalışır. Biri kırmızıysa paket adımı başarısız olur. Böyle bir paket adımı yazılmadı. Yazılana kadar “kritik test dağıtımı kesiyor” denmez.

## Render eşiği

Ölçüm, uygulanmadan önce tanımlıdır. Sonuç bu turda yoktur.

- Çözünürlük ve kamera kayıt altına alınır. Karşılaştırma aynı değerlerle yapılır.
- Düz yan panelden 64×64 pencere alınır. Pencere içi parlaklık standart sapması yazılır.
- Kabul eşiği, ilk temiz referans ölçülünce sabitleşir. Bugünkü 48 örnek ve 2 piksel denoise bu eşiği geçti sayılmaz.
- Siyah yüz, aynı pencerenin ortalama parlaklığı komşu ahşap yüzün yüzde 15’inin altındaysa ayrıca kusurdur. Bu oran teşhis eşiğidir, geçti iddiası değildir.
- Render süresi kaydedilir. Süre hedefi, ölçüm yokken ilan edilmez.
- Render bitince proje `design` özeti aynıdır.

## Gerçek cihaz

390 piksel emülasyon mobil geçti değildir. Dokunmatik kamera ve sürükleme gerçek telefonda ayrı yazılır. Emülasyon sonucu `EMULATED` kalır.

## Bu turda yapılmayanlar

Push yok. Migration yok. Aşama 2 ve sonrası kodu yok. Fiyat yok. Dosya bölme yok. Yedek geri yükleme denenmedi.
