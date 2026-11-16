# MEEMARE stabilite denetimi

Tarih: 16 Kasım 2026. Sayfa: `http://127.0.0.1:8765/dolap/tasarla`. Oturum: E2E QA User. Betik `wardrobe-studio.js?v=274`. Sunucuya push yapılmadı.

## Tarayıcıda ölçülen

| İşlem | Beklenen | Ölçülen | Durum |
| --- | --- | --- | --- |
| Sayfa açılışı | Stüdyo hatasız yüklenir | `window.__studioError` yok, tuval var | PASS |
| Taslağı yeniden açma | 5 modüllü “L giyinme odası” durur | Yenilemeden sonra ad aynı, modül sayısı 5 | PASS |
| 180 cm duvara modül | 45 cm’lik dört modül sığar, beşinci reddedilir | Ofsetler 0, 45, 90, 135. Beşincide tost: “Ön duvarda bu dolap için yer kalmadı.” | PASS |
| Geri al | Duvar 320 ve 5 modül geri gelir | 8 geri al ile parmak izi eşleşti; ikinci denemede 360 → 320 ve modül sayısı 5 | PASS |
| Kapak aç/kapa | Seçili modül açılır, geri al kapatır | Önce `door: solid, open: 0`. Sonra `open: 1`. Geri al sonrası `open: 0` | PASS |
| Kaydet | Geçerli tasarım yazılır | POST `/dolap/proje` 200, “Proje kaydedildi.” | PASS |
| Yeniden aç | Aynı modüller döner | GET 200, 5 modül, köşe ofseti 10000 duruyor | PASS |
| Kaydı sil | Deneme projesi kalkar | DELETE 200. Kayıt kasıtlı silindi | PASS |
| Geçersiz kayıt | Sessizce yazılmaz | `product: olmayan-modul` → 422 “Bilinmeyen modül kimliği.” | PASS |
| Bozuk ofset | Dolap 10000 ofsetle kaydolmaz | PHP testi 422 “Bozuk konum verisi.” Önceki proje adı durdu | PASS |
| Köşe kaydı | Stüdyonun 10000 ofsetli köşesi kaybolmaz | PHP testi 200, ofset 10000 saklandı | PASS |

`php artisan test --filter=WardrobeProjectAccessTest`: 2 test, 69 assertion, geçti. `node --test tests/js/wardrobe-layout.test.mjs`: 5 test geçti.

## Düzeltilen hatalar

Köşe modülü yerleşimde `offset: 10000` tutuyor. Doğrulama bunu 900 ile kesiyor ve “duvarın dışına taşıyor” diyordu. Geçerli bir L tasarımı kaydedilemezdi. Köşe, `cornerSide` ile yerleşir; ofset duvar hesabına katılmaz. Köşe ön duvardan genişse reddedilir. Köşe olmayan modülde 900 üstü ofset “Bozuk konum verisi.” olur.

Kayıt 422 dönerse tost, ilk doğrulama cümlesini gösterir. Sessiz başarısız kayıt yok.

## Bu turda tarayıcıda yapılmayan

Sürükle-bırak, modül sıralama, kapak komşu çarpışması, çekmece açma, malzeme renginin ekranda değişmesi, Ultra Render. Bunlar **NOT_TESTED**. Sahne donması veya model kaybı bu ölçülen akışta görülmedi.
