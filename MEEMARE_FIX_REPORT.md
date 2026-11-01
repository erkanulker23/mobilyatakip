# Meemare düzeltme raporu

Canlıya push yapılmadı. Yerel commitler `550687b` ve `c5bd96b`. Geri dönüş etiketi `qa-fix-baseline-2026-11-01`.

## 1. 315 cm duvarda dördüncü modül

Kök neden: ekranın soluna bırakılan ışın, ön duvardan önce sol duvar düzlemine çarpıyordu. Sol duvar 300 cm. 90+45+90 = 225 cm sığıyor, dördüncü 90 cm sığmıyor ve ekleme reddediliyordu. Toplam 225 cm kalıyordu.

Değişen dosyalar: `public/js/wardrobe-studio.js`, `resources/views/wardrobe/index.blade.php`.

Düzeltme: köşeye yakın yan duvar isabeti ön duvara yazılır. Yeni modül boşluğa yaslanır. Mevcut modüllerin sırası kendiliğinden kaydırılmaz. Katalog kartında Ekle düğmesi var. Sürüklerken bırakma bandı görünür. Her deneme `window.__wardrobeDropLog` içine yazılır.

Senaryo: 315 cm ön duvar, aynı bölgeye 90 + 45 + 90 + 90 sürükle. Kaydet, yeniden aç. Ayrıca 225 cm ve 270 cm.

Beklenen: dört modül, Dolap 315 cm, kayıt sonrası aynı sıra. 225 ve 270 bozulmasın.

Gerçek sonuç: Dolap 315 cm. Üretim sırası Çift V1, Tek V2, Çift V4, Çift V5. Yeniden açınca metin aynı. 225 cm ve 270 cm (0, 90, 180) geçti. Dolu 270 duvara Ekle, “Bu modül duvara sığmıyor” dedi ve 270 cm kaldı.

Ekran görüntüsü: `test-results/fixes/315-front.jpg`. Günlük: `test-results/fixes/drop-log.json`.

Durum: başarılı.

## 2. Mobilde 3D sahne

Kök neden: dar ekranda yan paneller sabit konumda sahnenin üstüne geliyordu. Tuvalin yüksekliği panelin altında kalıyordu.

Değişen dosyalar: `resources/views/wardrobe/index.blade.php`, `public/js/wardrobe-studio.js`.

Düzeltme: 900 px altında düzen `100dvh` ve güvenli alan ile kurulur. Üstte logo, proje adı ve Kaydet. Ortada sahne ve görünüm düğmeleri. Altta Modül Ekle, Malzeme, Kapaklar, Ölçüler, Proje. Bunlar kapanan alt sayfadır. Seçim sayfayı kendiliğinden açmaz.

Senaryo: 375×812, 390×844, 430×932, 768×1024, 1366×768. 390 px üzerinde sayfa aç, kapat, malzeme değiştir, kaydet.

Beklenen: 390 px telefonda dolap ilk açılışta görünsün. 1366 px masaüstü üç panel kalsın.

Gerçek sonuç: tuval yükseklikleri 697, 729, 831, 923 ve masaüstünde orta kolon 786×704. 390 px görüntüde dolap, üst çubuk ve alt araçlar görünüyor. Sayfa açılınca kapanıyor ve sahne duruyor. Gövde Lake Beyaz D129 iken Pasifik Mavi D230 oldu. Kayıt mesajı “Proje güncellendi.”

Ekran görüntüleri: `test-results/fixes/viewport-390x844.jpg`, `test-results/fixes/mobile-sheet-open.jpg`, `test-results/fixes/viewport-1366x768.jpg`. Günlük: `test-results/fixes/mobile-report.json`.

Durum: başarılı. Yakınlaştırma tekerleği tuvale ulaşıyor; kamera mesafesi ayrıca ölçülmedi.

## 3–6. Henüz tamamlanmadı

Köşe L geometrisi, füme cam path tracing, çekmece grubu ve Kastamonu doğrulaması bu turda yeniden yazılmadı ve başarılı diye işaretlenmedi.
