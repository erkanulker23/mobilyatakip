# MEEMARE kullanıcı deneyimi

Tarih: 16 Kasım 2026. Görüntüler dar pencerede alındı (genişlik yaklaşık 680 px).

## Gözlenen akış

1. Taslak “L giyinme odası” yenilemede açıldı. Müşteri adı boştu; sunucu kaydı yoktu.
2. Katalogdan modül eklenince kamera dolaba yaklaştı. `meemare-180-iki-modul.png`.
3. Seçili dolabın Aç / Klon / Sil düğmeleri modelin önünde duruyor.
4. Kapak paneli “Ahşap” seçiliyken “Kapağı aç” görünüyor. Açma ölçümü `open` değerini 0’dan 1’e, geri al 0’a getirdi.
5. Dar ekranda yapılandırma sayfası 3D sahneyi kapattı. `meemare-u-ust-gorunum.png`. Kapat deyince sahne geri geldi. `meemare-u-ust-acik.png`.
6. Alt şerit (Modül Ekle, Malzeme, Kapaklar, Ölçüler, Proje) sahneden bir pay alıyor, tamamını kapatmıyor.
7. Geçersiz kayıtta sunucu cümlesi döndü: “Bilinmeyen modül kimliği.” Tost bu cümleyi gösterecek şekilde bağlandı; o 422 bu oturumda fetch ile alındı, tost piksel olarak fotoğraflanmadı.

## Düzeltilen

Duvar ölçüsü değişince kroki aynı sayıları kullanıyor. Önce kroki 300, sahne 320 gösteriyordu. Ayrıntı `MEEMARE_WALL_AND_PLACEMENT_TESTS.md`.

## Bilerek değiştirilmeyen

Mobil panelin sahneyi kaplaması duruyor. Panel kapatılınca sahne açılıyor; yerleşim CSS’i bu turda yeniden kurulmadı. Fiyat, teklif formu ve Cabinet Studio’ya dokunulmadı.

## Durum

| Konu | Durum |
| --- | --- |
| Modül ekleyince kameranın ürüne bakması | PASS — ekran görüntüsü |
| Seçili modülün bulunması | PASS — panel adı ve ölçüleri geldi |
| Geri al | PASS |
| Hata cümlesinin anlaşılır olması | PASS — 422 metni |
| Mobil menünün sahneyi kapatmaması | FAIL — panel kaplıyor, düzeltilmedi |
| Malzeme renginin anında görünmesi | NOT_TESTED |
| İlk kullanıcının yardım almadan 10 adımı bitirmesi | NOT_TESTED |
