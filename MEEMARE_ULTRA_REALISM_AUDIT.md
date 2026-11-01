# MEEMARE 3D Studio — Ultra gerçekçilik denetimi

Tarih: 2026-11-01. Canlıya gönderilmedi.

Bu rapor, “raf ve çekmece neden çizgi gibi görünüyor?” sorusunun ölçülmüş cevabıdır. Fotoğraf gerçekçiliği iddiası yoktur. Resmî Kastamonu kataloğuyla renk eşlemesi, çoklu cihaz FPS ölçümü ve bu turda yeni bir path-trace füme cam denemesi yapılmadı. O maddeler aşağıda açıkça işaretlidir.

## 1. Genel kalite değerlendirmesi

Dolaplar CAD kutusuna benziyor çünkü kaynak geometri 8 köşeli, 12 üçgenlik kutular. Kalınlık kayıp değil. 18 mm’lik bir raf, 250 cm’lik dolap kadrajında yaklaşık 4 piksel yüksekliğindedir. Önden bakışta bu bant çizgi okunur. 45° ve makroda aynı rafın üst, ön ve yan yüzü görünür.

Kalınlığı 30–40 mm yapmak bu algıyı düzeltir ve ölçüyü bozar. Yapılmadı.

## 2. Mevcut sistemin teknik mimarisi

- Sahne birimi santimetre. GLB metre, Y-up. Yükleyici `scale.setScalar(100)` uygular.
- Katalog: `public/data/wardrobe-glb-modules.json` (25) ve `public/data/wardrobe-y26-modules.json` (20). 45 kaydın `modelPath` dosyası diskte var. Eksik dosya yok.
- Eski 25, `public/catalog/meemare25-norm/*.glb` üzerinden yüklenir.
- Köşe modülleri (`kose_1`…`kose_5`) sahnede GLB olarak çizilmez. `kind === 'corner'` parametrik gövde kurar. Köşe GLB dosyaları katalogda durur, ekrandaki köşe o dosya değildir.
- Kaynak GLB’lerde `NORMAL` yok. Stüdyo kutu parçalara 0,45 mm pah basar. Bu pah, tam dolap karesinde piksel altıdır; çizgi algısını kaldırmaz.
- Kıyafet, kutu, ayakkabı mesh’i yok. Aksesuar süzgeci bunları zaten atar.

## 3. 45 modülün geometri kontrolü

Her katalog GLB’sinin her mesh’i, pozisyon accessor min/max ile ölçüldü. Birim metre, rapor milimetre.

| Rol | İnce eksen | Adet |
|---|---|---|
| Raf | 18,0 mm | 210 |
| Gövde / tabla / yan | 18,0 mm | 140 |
| İç dikme | 18,0 mm | 23 |
| Çekmece önü | 18,0 mm | 43 |
| Çekmece önü | 22,0 mm | 19 |
| Arkalık | 8,0 mm | 40 |
| Çekmece kutusu (Y26, kapalı prizma) | yükseklik 120 mm, derinlik 465 mm | 21 |

18 mm’den ince raf, yan veya çekmece önü yok. Plane (kalınlık &lt; 2 mm) raf veya çekmece önü yok.

Tek kapılı örnek, net iç genişlik: dış 450 mm, iki yan 18+18, üst/raf genişliği 414 mm. Çift kapılıda dış 900, iç tabla/raf 864 mm. Bu, ortak dikmesiz bağımsız gövde hesabıyla uyumlu.

`tek_v1` arkalığı 450 × 2500 × 8 mm, yani dış genişliğin tamamı. Yan panellerle arka kenarda çakışabilir. `y26_tek_01` arkalığı 414 mm, yanların arasında.

Köşe GLB’leri bu tabloya dahildir ama sahnedeki köşe o mesh’ler değildir.

## 4. Raf ve 18 mm kalınlık kontrolü

Kontrol listesi, ölçülen kutular için:

1. Rafın 3D kalınlığı var. 210/210 rafın ince ekseni 18,0 mm.
2. Plane değil. 12 üçgen, 8 köşe.
3. Ön yüz var. Üst, alt, iki yan ve arka da var. Kutu kapalı.
4. Yan ve alt yüzeyler aynı kutunun yüzleri.
5. Yan paneller 18,0 mm.
6. İç dikmeler 18,0 mm.
7. `scale × 100` sonrası sahnede de 18 mm ölçüldü (`GOVDE_SOL`, `RAF_1`, `CEKMECE_ON` ince eksen 18 mm; arkalık 8 mm).
8. Yükseklik 2500 mm, derinlik 600 mm korunuyor.
9. Kaynak dosyada normal yok. Stüdyo yüz normallerini pah geometrisinde üretir. DoubleSide kullanıldığı için ters normal tek başına yüzeyi silmez.
10. Backface culling rafı sildiğine dair kanıt yok. İzole testte altı yüz de görünüyor.
11. Raf–arkalık z-fighting bu turda piksel piksel aranmadı. Y26 raf derinliği 588 mm, gövde 600 mm; raf arkası arkalığın önünde kalır.
12. Çekmece önleri 18 mm (Y26 ve bir kısım eski) veya 22 mm (19 adet eski ön). Kapaklar GLB içinde yok; stüdyo kapakları ayrıca kutu olarak ekler.

İzole kanıt: 800 × 18 × 550 mm `BoxGeometry`, malzemesiz ve MDF rengiyle.

- Önden: bant. Çizgi okunur. Ölçü 18 mm’dir.
- 45°: üst yüz, ön dudak ve yan yüz ayrı düzlemler.
- Makro: ön dudak geniş bir düzlem.

Görseller: `test-results/realism-audit/shelf18-clay-front.jpg`, `shelf18-clay-right45.jpg`, `shelf18-clay-macro.jpg`, `shelf18-mdf-right45.jpg`.

Piksel hesabı: dikey görüş 32°, kamera 1,15 m uzakta, 700 px yükseklik. Görünen yükseklik yaklaşık 660 mm. 18 mm ≈ 19 px. Aynı kareye 2500 mm dolap sığdırılırsa raf ≈ 4 px. Kök neden budur.

## 5. Çekmece kalite analizi

İki ayrı model var.

Eski seri (`tek_v1`): `CEKMECE_ON` 406 × 318 × 18 mm, `CEKMECE_TABAN` 12 mm, `CEKMECE_ARKA` 12 mm. Yan panel mesh’i yok. Stüdyo, ön+taban+arka üçlüsü varsa sentetik yan ekler. Açılışta bu üç parça birlikte kayar.

Y26 (`y26_tek_01`): `CEKMECE_ON` 406 × 145 × 18 mm ve `CEKMECE_KUTU` 384 × 120 × 465 mm. İkisi de 12 üçgenlik kapalı kutu. Ayrı sağ yan, sol yan, taban, arka yok. Kutunun yüzleri bu parçaların yerine geçer. Ön ve kutu birlikte kayacak şekilde gruplanır. Önden yalnızca 145 mm’lik ön yüz görünür; 465 mm derinlik önden okunmaz. 145 / 2500 ≈ %6, yani dolap karesinde bir şerit. Aynı ahşap gövdeyle bitişik durunca “yüzeye çizilmiş çizgi” okunur. Bu, ön yüzün plane olmasından değil.

45° karede kutu derinliği ve raf dudağı görünür: `test-results/realism-audit/after-tek-45.jpg`, `after-cift-45.jpg`.

Eski 45° kare boş yeşil tel kafesti. Kamera x=522, sağ duvar x≈304. Işın yan duvardan geçiyordu. Seçim çerçevesi `depthTest: false` olduğu için kutu çiziliyordu, gövde duvarın arkasındaydı. Bu bir U1 hatasıydı; kamera artık odanın boş tarafından bakıyor.

## 6. Ahşap malzeme değerlendirmesi

GLB’lerde ahşap doku yok. Renk, Kastamonu dekor görselinin UV’si. Damar ölçeği pozisyonun metre karşılığına göre tekrar eder. Normal map ve roughness map yok. Pürüzlülük mat dekor için 0,52, metalik 0. Bu, lake veya taranmış ahşap değil. 45 cm ve 90 cm dolapta damar ölçeği aynı UV kuralıyla gelir; panelden panele damar sürekliliği kurgulanmamıştır.

Plastik parlaklık riski yüksek parlaklık (`highgloss` roughness 0,22) seçilince artar. Varsayılan mat bu kadar yapay değildir. Yine de normal haritasız düz albedo, MDF kesiti hissi vermez.

## 7. Kastamonu Entegre doku doğrulaması

Bu turda resmî üretici kataloğuyla piksel karşılaştırması yapılmadı. Stüdyodaki görseller proje içindeki dekor dosyalarıdır. “Kum Taşı gerçek Kastamonu taramasıdır” denemez. Eşleşme yapılmayan dekorlar temsili dijital doku kabul edilmelidir. HEX ile doğruluk raporu üretilmedi.

Gövde, raf, kapak, çekmece, arkalık renkleri seçicide ayrı alanlar. Kenar bandı ayrı bir malzeme kanalı değil.

## 8. Cam kapak değerlendirmesi

GLB’lerde cam mesh’i yok. “Füme camlı” Y26 adları gövde ve raftır. Cam, stüdyonun kapak malzemesidir: `thickness: 0`, `attenuationDistance: Infinity`, ince film yolu. Bu turda yeni path-trace / raster füme karşılaştırması alınmadı. Önceki çalışmada kalın camın path tracer’da siyaha düştüğü görülmüş ve ince film yolu bırakılmıştı. Bu raporda yeniden ölçülmedi.

Aynalı kapak için ayrı ayna malzemesi doğrulanmadı.

## 9. Işıklandırma ve gölge

Ana ışık dolabın ön-üstünden, gölge haritası dolap merkezine bakar. Yarım küre ve dolgu ışığı içi tamamen karartmasın diye duruyor. Raf altı gölgesi bu haritadan gelir. Tam dolap karesinde 18 mm dudağın gölgesi de birkaç pikseldir; çizgiyi kalın panel yapmaz.

0,45 mm pah, 250 cm kadrajda piksel altıdır. Kenar ayrımını ancak yakın veya 45° planda verir.

## 10. Render motoru analizi

Tasarım görünümü Three.js r170, ACES, exposure 1,04. Path tracer ayrı bir “render al” yoludur. Bu turda path-trace karesi üretilmedi. Path tracing’in fotoğraf gerçekçiliği sağladığı varsayılmadı.

## 11. Gerçekçi aksesuar analizi

Kıyafet, askı kıyafeti, kutu, ayakkabı, çanta, valiz mesh’i katalog GLB’lerinde yok. Boş dolap testi geçerlidir. %30 / %60 / %85 doluluk karesi üretilemez; eklenecek model yoktur. Aksesuar puanı verilmedi.

## 12. Performans değerlendirmesi

Bu turda FPS, GPU veya CPU ölçülmedi. Sayı uydurulmadı. İki ayrı kalite modu (tasarım / sunum) ürünleştirilmiş bir anahtar olarak yok. Gölge haritası tasarım görünümünde de açık. Bunun kare hızına etkisi ölçülmedi.

## 13. Referans fotoğraf karşılaştırmaları

Meemare’nin kendi ürün fotoğrafı bu turda eşlenmedi. İzole raf, kontrollü referanstır: aynı 18 mm kutunun önden çizgi, 45°’de levha olması. Gerçek dolap fotoğrafıyla yan yana skor üretilmedi.

## 14. Modül bazlı kalite puanları

Yalnızca bu turda hem ölçülüp hem ekranı görülen iki modül. Diğer 43 modül geometri tablosuna girdi, tek tek fotoğraflanıp puanlanmadı. Aksesuar ve cam sütunları uygulanmadığı için boş.

| Modül | Geometri | Kalınlık | Raf | Çekmece | Ahşap | Kenar | Işık | Gölge | Derinlik | Genel |
|---|---|---|---|---|---|---|---|---|---|---|
| y26_tek_01 | 7 | 9 | 4 | 4 | 4 | 3 | 5 | 4 | 6 | 4 |
| y26_cift_01 | 7 | 9 | 4 | 4 | 4 | 3 | 5 | 4 | 6 | 4 |

Geometri 7: ölçüler doğru, kutu kapalı, normal kaynakta yok, çekmece ayrı panellere bölünmemiş. Kalınlık 9: 18 mm ve 8 mm sahnede doğrulandı, 1 puan kaynakta normal olmaması. Raf 4: levha doğru, tam karede çizgi. Çekmece 4: ön 18 mm ve kutu var, beş parçalı çekmece değil. Genel 4: CAD kutusu. Fotoğraf gerçekçiliği değil.

## 15. Önceliklendirilmiş düzeltme listesi

**G1 — Y26 çekmece kutusu.** Modül `y26_*`, nesne `CEKMECE_KUTU`. Kök neden: 12 üçgenlik kapalı prizma; yan, taban, arka ayrı değil. Önem: orta. Öneri: dış ölçü bozulmadan kutu boşaltılmadan önce üretim kesiti netleşmeli. Kalınlaştırarak çizgiyi gizlemek yanlış düzeltmedir. Etkilenen: 21 kutu.

**G1 — Eski çekmece yanları.** Modül `tek_v1` ve aynı isimlendirme. Nesne yok: `CEKMECE_YAN`. Kök neden: GLB’de yalnızca ön, taban, arka. Stüdyo yan ekliyor. Önem: orta.

**G1 — `tek_v1` arkalık genişliği 450 mm.** Yan panellerle arka kenarda örtüşme ihtimali. Y26 arkalığı 414 mm.

**U1 — Eski 45° kamera oda dışında.** Kök neden: yükseklikten türetilen mesafe, dolap duvar dibindeyken kamerayı x=522’ye taşıdı. Düzeltme: kamera odanın boş tarafından, görüş 30°. Ekran: `before-tek-45.jpg` boş tel, `after-tek-45.jpg` gövde.

**L1 — 18 mm’nin tam karede ~4 px olması.** Kök neden: projeksiyon. Işık eklemek kalınlığı artırmaz. Yakın kadraj ve 45° dudağı gösterir. İzole test bunu ayırır.

**M1 — Doku resmi tarama değil.** Normal ve roughness haritası yok. Resmî katalog eşlemesi yapılmadan “gerçek Kastamonu” denmemeli.

## 16. İyileştirme sonrası test sonuçları

Aynı ürün, ölçüler bozulmadan.

Önce 45° (kamera duvarın arkasındaydı): `test-results/realism-audit/before-tek-45.jpg`

Sonra 45°, 45 cm: `test-results/realism-audit/after-tek-45.jpg`

Sonra 45°, 90 cm: `test-results/realism-audit/after-cift-45.jpg`

Önce önden: `test-results/realism-audit/before-tek-front.jpg`

Sonraki sahnede ince eksen hâlâ 18 mm (raf, yan, çekmece önü) ve arkalık 8 mm. Pah dış kutuyu büyütmedi. Önden raf hâlâ ince banttır. Bu beklenen sonuçtur.

## 17. Müşteriye sunum için hazır olma

Hazır değil. Ölçü doğru, fotoğraf değil. Müşteri karesi için yakın 45° kadraj kullanılabilir; tam cephe, 18 mm rafı çizgi gösterir. Bunu kalınlık hatası diye düzeltmek üretimi bozar.

Ortak standart, bütün GLB’lere giden yol: metre model, ×100, kutu parçaya 0,45 mm pah, mat pürüzlülük 0,52, arkalık daha mat, gölge dolap merkezinden, kamera görüş 30° ve oda içinde. Yeni kalınlık uydurulmaz.
