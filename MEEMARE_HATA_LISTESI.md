# Meemare hata listesi

P0 bu turda üretilmedi. Veri kaybı, yetkisiz okuma ve uygulamanın açılmaması görülmedi. `WardrobeProjectAccessTest` başka kullanıcının projeyi açamadığını doğruladı.

## MM-001 — P1 — 45 GLB sahnede yüklenmiyor

Açıklama: Katalogdaki 45 modelin GLB dosyası `public/catalog` altında var. `createFurnitureModelWithGLB` layout bulunca parametrik dolabı kurup dönüyor. GLB isteği yapılmıyor.

Yeniden üretme: Herhangi bir dolap kimliğiyle sahne oluştur. Ağ kaydında ilgili `model.glb` çağrısı yoktur.

Beklenen: Model dosyası yüklenir ya da arayüz bunun parametrik olduğunu açıkça söyler.

Gerçek: Sahne parametrik gövdedir. Eski kod `meemareGlb = 'ready'` yazıyordu. Bu turda değer `parametric` yapıldı.

Dosya: `tools/openplan3d/src/lib/utils/furnitureModelLoader.ts`, `createFurnitureModelWithGLB`.

Kanıt: `test-results/meemare/geometry-audit.json` içinde `glbFile: true`, `glbLoadedByStudio: false`.

Öneri: Parametrik gövde üretim modeli olarak kalacaksa GLB yolunu katalogdan ayır. GLB gösterilecekse layout erken dönüşünü kaldır. İkisini birden “yüklendi” diye işaretleme.

## MM-002 — P1 — Alt çekmece ve alçak raf zemine yapışıyordu

Açıklama: `edge()` ince ekseni, parça 0.12’nin altındaysa zemine kilitliyordu. `y26_tek_01` iki çekmece kutusu da y = 0–12 cm oluyordu ve alt tabla ile 18 mm kesişiyordu.

Yeniden üretme: Düzeltmeden önceki `placeCabinet('y26_tek_01')` çekmece kutularının altını 0 verir.

Beklenen: Çekmece kendi v konumunda, 120 mm yüksekliğinde durur.

Gerçek: İlk ölçümde ikisi de zemindeydi. Eşik 0.025 / 0.9 olacak şekilde daraltıldı. Yeniden ölçümde `bottom/drawerBox` kalmadı.

Dosya: `tools/openplan3d/src/lib/utils/wardrobeParametric.ts`, `edge`.

Öneri: Kabuk kilidini yalnızca yan, üst, alt ve arkalık rollerine bağla. Yükseklik eşiği başka iç parçayı yine yakalayabilir.

## MM-003 — P1 — Raf ile çekmece aynı hacimde

Açıklama: Düzeltmeden sonra da şu modellerde raf, çekmece önü veya çekmece kutusu kesişiyor: `y26_cift_02`, `y26_cift_03`, `y26_cift_06`, `y26_cift_09`, `cift_9`.

Yeniden üretme: `placeCabinet` sonrası `interiorCollides`.

Beklenen: Raf, çekmece bandının üstünde biter.

Gerçek: Kesişme derinliği 0.5 cm ile 1.8 cm arasında.

Dosya: `wardrobeLayouts.ts` kaynak kutuları ve `seatInteriorJoints`.

Kanıt: `geometry-audit.json` penetrations.

Öneri: Bu beş layout’ta çekmece bandındaki rafı, çekmece üst yüzünün üzerine taşı.

## MM-004 — P1 — 30 mm LED kanalı yok

Açıklama: İstenen tablodaki 30 mm LED kanalının fiziksel boşluğu yok. Aynı 30 mm, arkalık lefi olarak duruyor. LED, dikmede yaklaşık 0.7 cm ışıklı şerit.

Yeniden üretme: Düz dolap parçalarında adı LED kanalı olan bir oyuk ara.

Beklenen: 30 mm derinliğinde işlenmiş kanal.

Gerçek: Kanal yok. Lef var.

Dosya: `wardrobeParametric.ts` `BACK_GROOVE_MM`; LED şeridi `furnitureModelLoader.ts`.

Öneri: Üretim çiziminde 30 mm’yi lef olarak bırak. Ayrı bir LED kanalı ancak atölye ölçüsü gelince eklenir.

## MM-005 — P1 — Köşe panelleri birbirinin içine giriyor

Açıklama: Beş köşe modelinde yan, üst, alt ve arkalık L dönüşünde 18 mm kesişiyor. `kose_3` ayrıca çekmece taşıyor ve `interiorCollides` true.

Yeniden üretme: `placeCabinet('kose_1')` ile `kose_5`.

Beklenen: L dönüşü tek gövde gibi durur, paneller yalnızca bindirme payında birleşir, kapak salınımı tanımlıdır.

Gerçek: Eksen hizalı kutular köşe hacmini paylaşıyor. Ortak panel ve kapak salınımı onaylı değil.

Dosya: `wardrobeLayouts.ts` köşe parçaları. `CORNER_JOINT.approved` false.

Öneri: Köşe dönüşünü tek seferlik bir birleşim hacmiyle yeniden kur. Şimdilik kesim listesine alma.

## MM-006 — P2 — 3D kadraj ürünü kesiyor

Açıklama: Kalite turu 3D görünümünde ikinci dolap sol kenarda kesik. Sağ yarım kalın kahverengi duvar.

Yeniden üretme: `?proje=hUDQAyBxn2Bwqo8W3frZavrp` aç, 3D’ye geç.

Beklenen: İki modül kadrajda, duvar arkada.

Gerçek: Ekran görüntüsü `test-results/meemare/screenshots/kalite-turu-3d-webgl.png`.

Dosya: plan kamerası, `ThreeViewer`.

Öneri: Kamera hedefini modüllerin birleşik kutusuna al. Bu turda kamera kodu değiştirilmedi.

## MM-007 — P2 — Çekmece kutusu iki tahta

Açıklama: `tek_v1`, `tek_v3`, `tek_v4`, `tek_v5` ve `cift_v1`–`cift_v5` çekmeceyi alt tahta artı arka tahta olarak kuruyor. İkisi köşede 1.1 cm kesişiyor. Bu, iki çekmecenin üst üste binmesi değil. Kutu yine de kapalı bir çekmece değil.

Dosya: `wardrobeLayouts.ts` `drawerBox` çiftleri.

Öneri: Çekmeceyi yan, alt, arka ve ön olarak ayrı kur ya da tek kutu mesh’i kullan.

## MM-008 — P2 — Üretim onayı kapalı

Açıklama: Gövde kalınlıkları standart olarak uygulanıyor. Kesim listesi, ortak panel ve köşe birleşimi onaylı değil.

Dosya: `wardrobeParametric.ts` `CONSTRUCTION`, `wardrobeProduction.ts`.

Öneri: Onayı, çakışmalar kapanınca ve atölye ölçüsü yazılı onay alınca aç.

## MM-009 — P3 — Baza ile dikme

Açıklama: `cift_6`–`cift_10` dikmesi bazanın içinden geçiyor. `cift_v1` ve `cift_v4` rafı dikmede 0.9 cm kalıyor.

Öneri: Dikmenin altını baza üst yüzünde bitir. 0.9 cm kalan raf payını dikme yüzüne kadar kısalt.

## Bu turda kapatılanlar

MM-002 yeniden ölçüldü. GLB bayrağı `parametric` oldu. Düz dolapta tam genişlik raf ve askı dikmede bölündü. Bu, MM-003 ve MM-005’i kapatmıyor.
