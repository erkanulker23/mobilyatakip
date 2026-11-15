# Parametrik modül ölçüsü

Tarih: 2026-11-02  
Durum: tasarım. Ölçü kutuları bugün gövdeyi ölçekliyor. Bu belge o davranışı üretim kuralı saymaz.

## Bugünkü sınır

Arayüz genişliği 30–200 cm, yüksekliği 160–270 cm, üst dolapta 40 cm. Aralık dışı değer en yakın sınıra çekiliyor. GLB modülünde bu sayı `mod.width` ve `mod.height` oluyor, sarmalayıcı ölçek değişiyor. Panel kalınlığı, çekmece kutusu, delik ve kapak ayrı kesilmiyor.

Katalogdaki y26 ve meemare25 gövdelerinin çoğu 450 veya 900 mm genişlik, 2500 mm yükseklik, 600 mm derinlik. `wardrobe-cad.json` ayrı bir 600–1740 mm tarifidir ve GLB kimliğiyle bağlı değildir.

## Kural

Ölçü değişince tanım yeniden kesilir.

- Yan paneller dış yüksekliği ve dış derinliği taşır. Kalınlık 18 mm kalır. Yükseklik ölçeği kalınlığı 18 mm’den uzaklaştırmaz.
- Üst ve alt tabla, iki yan arasındaki net açıklıktır. Dış genişlik değişince tabla boyu değişir, kalınlık değişmez.
- Arkalık, kanal payı tanımda yazılıysa o pay düşülür. Pay yazılmamışsa arkalık ölçüsü “doğrulanmadı” kalır. 8 mm ile 18 mm karıştırılmaz. `tek_v1` arkalığı 8 mm, `kose` arkalığı 18 mm ölçülmüştür. Pilot tek değeri kopyalar.
- Raf sayısı değişince aralık eşit bölünmez. Kullanıcı aralığı girer. Kalan boşluk son rafa yedirilmez. Sığmıyorsa işlem reddedilir.
- Çekmece adedi ve ön yüksekliği, dikmenin iç yüzünde kalır. Ön, dikmeyi delmez. Kaynak GLB’de bu delinme vardır. Parametre o geometriyi ölçekleyerek çoğaltmaz.
- Kapak adedi, gövde bölmesine uyar. Tek bölmeye iki kapak, tanım izin vermiyorsa reddedilir. Cam ve MDF malzeme değişir, ölçü aynı kalır. Cam kalınlığı path tracer sınırından ayrıdır. Görüntü kalınlığı 0 ise bu, üretim cam kalınlığının 0 olduğu anlamına gelmez. Üretim kalınlığı tanımda milimetredir.
- Derinlik değişince tabla, raf ve çekmece derinliği yeniden kesilir. Yan panelin derinlik ölçüsü dış derinliğe eşitlenir. Yalnız Z ölçeği yetmez.
- Desteklenmeyen sayı sahneyi, kaydı ve kesim listesini değiştirmez. Kutu eski değerde kalır. Mesaj, kabul edilen aralığı söyler.

## Pilot aralığı

İlk aralık yalnızca `tek_v1` içindir ve atölye onayına kadar taslaktır. Onaylanmadan müşteriye “her ölçü üretilir” denmez.

Başlangıç tekniği, mevcut kutunun yeniden kurulmasıdır:

- Genişlik, iki yan ve tablalar yeniden kesilecek şekilde 300–1200 mm. Alt ve üst sınır atölye onayı olmadan ticari vaat değildir.
- Yükseklik 2000–2700 mm, baza ayrı.
- Derinlik 350–600 mm. 600 mm bugünkü katalog derinliğidir. Daha derin çekmece rayı, ray tanımı doğrulanmadan açılmaz.
- Raf adedi, net iç yükseklik her rafa en az 150 mm bırakıyorsa artar. Bırakmıyorsa reddedilir.
- Çekmece önü 140 mm altına inmez. Bu sınır sahnedeki mevcut `minFront` ile aynıdır. Kutu yüksekliği önden ayrı hesaplanır ve dikme ile çakışma testi 0 mm örtüşme arar.

Bu sayılar kod değildir. Pilot test onları tanım dosyasına yazar. Aralık dışı bir istek 422 ile döner.

## Ortak panel ile ilişki

Bağımsız tipte iki modül yan yana gelince her biri kendi yanını keser. Ortak tipte paylaşılmayan dış yanlar kalır, birleşim tek dikmedir. Genişlik kutusu dıştan dışa ölçüdür. Ortak tipte kullanıcı 900 mm isterse tanım, paylaşılan panelin bir kez sayıldığını gösterir. Ayrıntı üretim belgesindedir.

## Test

1. Pilot 450 mm iken tabla boyu tanımla aynıdır.
2. İzinli yeni genişlikte tabla boyu iki yan arasındaki net açıklıktır, yan kalınlığı 18 mm’dir.
3. 100 mm genişlik reddedilir. Kayıt gövdesindeki genişlik değişmez.
4. Raf aralığı sığmadığında adet artmaz.
5. Çekmece kutusu ile dikme AABB örtüşmesi 0’dır.
6. Kapak tanımındaki adet ile sahnedeki kanat adedi aynıdır.

45 modül bu altı madde `tek_v1` için geçmeden parametrik kataloga alınmaz.
