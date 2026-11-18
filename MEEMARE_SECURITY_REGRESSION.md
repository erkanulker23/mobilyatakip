# Güvenlik regresyonu

`WardrobeProjectAccessTest` bu turda yeniden geçti.

Doğrulanan kurallar:

- Başka kullanıcı projeyi göremez ve değiştiremez.
- 180 cm duvarda 200 cm modül 422 döner, önceki proje adı durur.
- Bilinmeyen ürün kimliği reddedilir.
- Köşe `offset: 10000` kaydedilir.
- Köşe olmayan modülde 10000 ofset “Bozuk konum verisi.” ile reddedilir.

Yeni bir yetki açığı bu turda aranıp kapatılmadı. Mevcut testler bozulmadı.
