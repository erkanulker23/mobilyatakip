# Meemare görsel kalite raporu

Görsel karşılaştırma bu turda tek WebGL karesi ile sınırlıdır. Path tracing çıktısı yoktur. Aynı kamera, aynı ışık ve artan örnek sayısıyla kalite artışı kanıtlanmadı.

## WebGL karesi

Dosya: `test-results/meemare/screenshots/kalite-turu-3d-webgl.png`.

Proje: Kalite turu. Sürüm: openplan `v=321`. Işık, sahnedeki mevcut stüdyo ışığıdır. Ayrı bir referans HDRI karşılaştırması yapılmadı.

Görünen: açık gri gövde, sol bölümde çekmece blokları, sağ bölümde raflar, bir dikme, iki askı, krem-sarı arkalık, açık tavan ve zemin. Yüzeyler düz renktir. Ahşap damarı, normal map, metal yansıması ve cam bu karede yok. Kapak yok. Bu görünüm fotogerçekçi değildir.

Kadraj: soldaki ikinci dolap kesik. Sağda kalın kahverengi duvar, görüntünün yaklaşık yarısı. Ürün ortaya alınmamış.

Arkalık rengi kodda `#c4a06a`, roughness 0.88. Sıcak ışık altında krem okunuyor. Ekran görüntüsünden Kastamonu renk kodu doğrulanamaz.

## 390×844 emülasyon

Dosya: `test-results/meemare/screenshots/emulation-390x844.png`.

Üst çubuk ve araçlar görünür. Sağdaki koyu alan, emülasyonun tarayıcı penceresindeki boşluğudur. Uygulama hatası olarak yazılmadı. Dokunma, katalog ve kayıt denenmedi. Fiziksel telefon değildir.

## Path tracing

| Örnek | 1600 | 1920×1080 | 2560×1440 | 3840×2160 |
|---|---|---|---|---|
| 64 | NOT RUN | NOT RUN | NOT RUN | NOT RUN |
| 256 | NOT RUN | NOT RUN | NOT RUN | NOT RUN |
| 512 | NOT RUN | NOT RUN | NOT RUN | NOT RUN |
| 1024 | NOT RUN | NOT RUN | NOT RUN | NOT RUN |

Kod bu dört örnek sayısını ve dört çerçeveyi tanıyor. GPU doku sınırı küçükse 4K küçültülüyor. Bu küçültme birim testte var. Bu turda hiçbir kombinasyonun PNG’si üretilmedi. Gürültü, denoise, cam, ahşap, gölge ve kenar keskinliği puanlanmadı.

## Malzeme

MDF, melamin, mat lake, parlak lake, ahşap desen, şeffaf, füme, bronz ve siyah cam, gold, inox, bronz ve siyah alüminyum bu turda seçilmedi. Birim test, parlak yüzeyin melaminden daha düzgün roughness aldığını ve normal map uydurulmadığını kontrol ediyor. Bu, ekranda renk doğruluğu değildir.

## Kamera listesi

Tam önden, sol çapraz, sağ çapraz, üstten, iç raf, cam, çekmece detayı ve oda geneli: NOT RUN. PNG indirme: NOT RUN. “Sahneyi indir” düğmesi 3D çubuğunda göründü. Basılmadı.

## 45 model karesi

Her model için ön, çapraz, iç detay, cam ve path tracing karesi yok. Model kimliğiyle saklanan tek sahne görüntüsü yukarıdaki Kalite turu karesidir. İç düzen farkı geometri tablosundan okundu, ekrandan değil.
