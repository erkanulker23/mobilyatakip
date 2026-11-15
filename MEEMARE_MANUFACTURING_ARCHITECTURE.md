# Üretim mimarisi

Tarih: 2026-11-02  
Durum: tasarım. Parça üreten yeni kod yok. 45 GLB dönüştürülmedi.

## Bugün üç kaynak var

Aynı dolap üç yerde tarif ediliyor.

| Kaynak | Ne tutuyor | Ne tutmuyor |
| --- | --- | --- |
| `public/catalog/.../module.json` | Milimetre kutu, merkez, malzeme adı | Delik, bant yönü, menteşe, ray |
| `public/data/wardrobe-cad.json` | 0–1 oranlı raf, askı ve çekmece | Dış panel, kalınlık, 45 GLB ile kimlik eşlemesi |
| GLB ve `wardrobe-y26-modules.json` | Görünen gövde, 18 mm ve 8 mm notu | Kapak gövdesi, delik, üretim kilidi |

Kapaklar GLB düğümü değil. `addDoors` ve `addCornerGlbDoors` sahnede üretiyor. `y26_cift_01`, `02`, `03`, `04`, `06` ve `09` çekmece kutusu orta dikmeyle kesişiyor. `kose_1` yan ve arka paneli köşede aynı 18 mm hacmi paylaşıyor. Bu hatalar düzelmeden o modeller üretim kaynağı olmaz.

`tek_v1/module.json` milimetre kutu listesi taşıyor: `SOL_YAN`, `SAG_YAN`, `UST_TABLA`, `ALT_TABLA`, `ARKALIK`, `BAZA`. Pilot budur. Diğer katalog notu da aynı şeyi söylüyor: geometri konsepttir, CNC için onay gerekir.

## Tek tanım

Pilot dosya bir modül tanımıdır. Sahne, kesim ve maliyet bu kaydın parçalarını okur. GLB, tanımın önizlemesi olabilir. Ölçü değişince GLB eksenleri şişirilmez. Tanım yeniden kesilir, görüntü o kutulardan kurulur.

Modül alanları:

- `id`, katalog kimliğiyle aynı
- `outerWidthMm`, `outerHeightMm`, `outerDepthMm`
- `join`, `independent` veya `shared`
- `parts[]`

Her parça:

- `id`, modül içinde benzersiz
- `kind`, yan, tabla, dikme, raf, arkalık, baza, kapak, çekmece önü, çekmece kutusu, dolgu, çıta
- `materialId`
- `thicknessMm`, `widthMm`, `heightMm`, `depthMm`
- `xMm`, `yMm`, `zMm`, kutunun köşesi veya merkezi tek kuralda. Pilot `module.json` merkezi kullanıyor. Yeni tanım köşeyi kullanır ve merkez yazılmaz. Böylece iki yorum kalmaz.
- `rotationDeg`, şimdilik 0. Eğik parça pilotta yok.
- `edgeBand`, dört kenar için `none` veya bant kimliği ve kalınlık
- `holes[]`, çap, derinlik, yüz ve konum. Bilinmiyorsa dizi boş kalır. Boş dizi “delik yok, doğrulandı” demek değildir. `holesVerified: false` ayrı durur.
- `joints[]`, minifix, kavela veya vida. Doğrulanmadıysa boş ve `jointsVerified: false`

Kapak tanımı parça olarak durur, GLB içinde olmak zorunda değildir. Alanlar: adet, ölçü, kalınlık, açılış, menteşe ekseni, derz, kulp noktası, cam veya MDF, açık açı sınırı.

Birim milimetredir. Sahne santimetre olarak kalır. Sınır, tanım milisini 10’a bölerek kurulur. Tersi, santimden milime yuvarlayarak üretim yazmak, pilot kuralı değildir.

## İki gövde tipi

A — Bağımsız. Her modülün sol ve sağ yanı vardır. Bugünkü `addCarcass` buna uyar. Varsayılan budur.

B — Ortak dikme. Yalnız aynı derinlikte, aynı yükseklikte ve aynı hizada bitişik çiftte. Paylaşılan yüz tek paneldir. Dış genişlik, iki bağımsız yana göre bir panel kalınlığı kadar kısadır. Köşe, farklı derinlik ve niş bu kurala girmez. Oralarda sistem A’ya döner ve bunu yazar.

Tip değişince parça kimlikleri yeniden üretilir. Eski kesim satırı yeni sürümde kalmaz.

## Engel ve pay

Oda alanları, modül parçasından ayrıdır: sol pay, sağ pay, tavan payı, süpürgelik, duvar eğriliği için montaj boşluğu. Kapı, pencere, kolon, kiriş, klima, priz, radyatör, tesisat ve niş `roomSketch` listesine eklenir. Bugün bu listede yalnızca kapı ve pencere var ve yalnız duvar mesh’ini kesiyor.

Engel yerleşim aralığını düşer. Dolgu ve bitiş çıtası, boşluk modülün içine yedirilerek değil, kendi parça kimliğiyle yazılır.

## Kesim ve dosya

Kesim satırı parça `id` taşır. Net ölçü, malzeme, kalınlık, adet ve bant yönü tanımla aynıdır. Alan, net en ve boy çarpımıdır. Fire fiyat belgesindedir, kesim ölçüsüne gizlice eklenmez.

DXF, bu kutuların 2B açınımıdır. Birim milimetre, katman parça türüdür. DXF ancak pilot kutu listesiyle aynı kimlikleri içeriyorsa üretilir.

DWG üreten kod yok. İndirme ve menü DWG demez.

“CNC’ye hazır” yazısı, delik ve bağlantı `verified` olduktan ve bir atölye parçası bu listeyle kesildikten sonra açılır. Pilotun ilk halinde bu yazı kapalıdır.

## Onaylı sürüm

Üretim listesi taslağın son halinden değil, kilitli revizyondan çıkar. Taslak değişince liste değişmez. Ayrıntı EK-08.

## Pilot kabulü

`tek_v1` tanımındaki her kutu sahnede bir mesh ve kesimde bir satırdır. Ölçü farkı 1 mm’yi geçmez. Kapak, tanımda varsa sahnede de vardır. Tanımda yoksa katalog “kapaklı” demez. Diğer 44 model bu pilot ölçüsü yeşil olmadan dönüştürülmez.
