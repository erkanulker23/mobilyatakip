# Fiyat ve teklif

Tarih: 2026-11-02  
Durum: tasarım. Tutar hesaplayan kod yok. Bu belgede örnek fiyat yok.

## Bugün

`POST /dolap/teklif` ad, telefon, not ve tasarım JSON’u `wardrobe_quotes` tablosuna yazar. Yanıt tutar içermez. Ekran metni hazır fiyat olmadığını söyler. Bu doğru bir talep kaydıdır, teklif motoru değildir.

`products.unitPrice` ve `netPurchasePrice` genel stok içindir. Dolap parçasına bağlı değildir. Bu kolonlardan dolap toplamı türetmek, fiyatı varmış gibi gösterir. Yapılmaz.

Müşteri fiyatı ile iç maliyet aynı tabloda düz metin olarak durmaz.

## Ne zaman hesaplanır

Sıra:

1. Kilitli revizyon vardır.
2. Kesim listesi o revizyonun parça kimliklerindendir.
3. Her malzeme, bant ve donanım için fiyat defterinde satır vardır.
4. Kullanıcı üretim kurallarının onaylandığını işaretler.
5. Ancak bundan sonra toplam yazılır.

Bir adım eksikse müşteri çıktısında tire vardır, sıfır veya tahmin yoktur. Kayıt “talep” olarak kalır.

## Fiyat defteri

Yönetim ekranından değişir. Kod içine sayı yazılmaz.

Satır alanları:

- `sku`, parça tanımındaki `materialId` veya donanım kimliği
- `unit`, m2, metre, adet
- `purchaseAmount`, iç alış
- `saleAmount`, müşteri birimi
- `currency`
- `validFrom`
- `active`

Panel m2, bant metre, menteşe adet, ray çift adet, cam m2, aksesuar adet, işçilik saat veya gövde adedi, nakliye sefer, montaj sefer. Birimin tanımı satırdadır. Kod birimi varsaymaz.

Fire oranı defterdedir ve ayrıca gösterilir. Net alana gizlenmez. Kâr ve iskonto ayrı satırdır. Vergi, stoktaki `kdvRate` kuralı dolaba bağlanmadan kullanılmaz. Dolap vergisi fiyat satırının kendi oranıdır. Oran boşsa vergi hesaplanmaz, satır eksik sayılır.

## İki çıktı

Müşteri çıktısı: satış birimleri, iskonto, vergi, genel toplam. Alış, fire tutarı ve kâr yoktur.

İç rapor: alış, fire, işçilik, nakliye, montaj, kâr. Yalnızca admin görür. `staff` ve ilerideki müşteri rolü bu raporu alamaz. EK-01 rolü durmadan iç rapor ucu açılmaz.

## Eksik fiyat

Eksik `sku` toplamı durdurur ve eksik kimliği listeler. En yakın malzemenin fiyatı kopyalanmaz. Cam, bant veya ray fiyatı yoksa o kalem “fiyat yok” kalır. Teklif onay düğmesi kapalıdır.

## Test

- Fiyat defteri boşken teklif ucu tutar döndürmez. Mevcut talep kaydı çalışmaya devam eder.
- Bir panelin m2 fiyatı varken bant fiyatı yoksa toplam yoktur. Eksik kimlik banttır.
- Müşteri JSON’unda `purchaseAmount` anahtarı yoktur.
- Admin iç raporunda aynı revizyonun parça adedi kesim listesiyle aynıdır.
- Fiyat satırı değişince eski kilitli teklif tutarı değişmez. Yeni tutar yeni revizyon ister.

Onay kutusu işaretlense bile kesim listesi pilot doğrulamasından geçmemişse tutar üretilmez.
