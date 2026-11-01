# Referans dolap — y26_cift_01

Bu dosya onay belgesi değildir. Model profesyonel ürün fotoğrafı seviyesinde değildir.

Dış ölçü duruyor: 900 × 2500 × 600 mm. Panel 18 mm, arkalık 8 mm. Kalınlık artırılmadı.

## Geometride düzelenler

Kaynak GLB’de orta dikme çekmecenin ve alt rafın içinden geçiyordu. Askılık da 18 × 18 mm kare çubuk olarak dikmenin içinden geçiyordu.

- Dikme artık çekmece kuşağının üstünde, alt rafın üzerinde duruyor.
- Askılık sol bölmede, Ø25 mm yuvarlak metal boru. Dikmeyi delmiyor.
- Gövde, raf ve çekmece ön kenarında ayrı kenar bandı yüzeyi var.
- Çekmece kutusunun yanında metal ray çifti var. Kapakta iki menteşe bileziği var.
- Alüminyum, path tracer’da siyaha düşen clearcoat malzemesinden çıkarıldı.

## Ölçülen görüntüler

Klasör: `test-results/ultra-realism/reference/`

Sol 45 ve sağ 45 dosya özeti farklı: `66aef8e36bbf` ve `ab9287adcb05`.

Açık cephede sol bölmede yuvarlak askılık, orta dikme, 18 mm raf ve iki çekmece önü görünüyor. Sağ bölmede ara raf yok. Kaynak parça listesinde de yok. Konsept fotoğraftaki yan raflar bu GLB’de yok; eklenmedi.

Raf makrosunda ön kenar ve raf altı LED okunuyor. LED şeridi patlıyor.

Çekmece makrosunda ön panel, kulp ve aralık var. Ray ve çekmece kutusu bu kadrajda seçilmiyor.

Ahşap makrosu katalog JPEG’inin yakın hali. Damar kabartması yok. Yüzey hâlâ basılı dekor gibi.

Cam yakın çekim, kapalı füme kapakta yaklaşık 13 cm’lik bir pencere. Camın kendisi okunmuyor; arkadaki ahşap doluyor.

Aksesuarlı kare boş. Poly Haven apparel listesi boş döndü. Mevcut gömlek, ceket ve ayakkabı dosyaları 10–16 bin üçgenlik prosedürel CC0 modeller. Geri konmadı.

## Malzeme

Raf rengi İtalyan Ceviz A398 yapıldı. Kapak, arkalık ve çekmece Naturel Meşe A402 kaldı. Kanallar birbirinden bağımsız.

Bu renkler resmî Kastamonu taraması değil. Katalogdaki temsili JPEG duruyor.

## Performans

Tasarım modu, path trace kapalı. Bir ölçümde 61 kare/sn, son ölçümde 21 kare/sn.

Ultra render ayrı. 1050 × 1280, 32 geçiş, 128 sample, 123 saniye, tarayıcı yığını yaklaşık 65 MB. Görüntü `10-path.png`. Ahşap, askılık, dikme ve çekmece seçiliyor. Cam siyah levha değil. Örnek sayısı düşük olduğu için benek var.

Aynı makinede 1920 piksel ve 80 geçiş bir saatte 61/80’de kaldı ve durduruldu. O kare kaydedilmedi.

Kayıt denemesi proje adresine yazmadı. Uyarı: “Önce müşteri bilgisi gerekli.” Sürükle-bırak bu turda yeniden ölçülmedi.

## Onaylanmayanlar

- Sağ bölmede üretim rafı yok.
- Çekmece rayı fotoğrafta okunmuyor.
- Ahşap plastik-baskı hissini kaybetmedi.
- Cam yakın plan cam gibi durmuyor.
- Kıyafet, ayakkabı, çanta ve kutu yok. Bunun için kumaş simülasyonuyla, gerçek ölçüde, normal haritalı modeller gerekir. Hazır CC0 kütüphane bulunamadı.
- Path trace ürün fotoğrafı değil.
- Diğer 44 modül bu standarda çekilmedi.
