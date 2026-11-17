# Meemare düzeltme sonuç raporu

Tarih: 17 Kasım 2026. Canlı adres: yerel `127.0.0.1:8765`. Embed `v=324`. Üretim ortamına dağıtım yapılmadı. Müşteri projelerine yazılmadı. `CONSTRUCTION.approved` ve köşe birleşim onayı `false` kaldı. CNC açılmadı.

Bu tur sistemi tamamlanmış saymaz. Aşağıdaki durumlar ölçülen sonuçlardır.

## Kabul özeti

| # | Ölçüt | Durum |
|---|---|---|
| 1 | 45 model yüklenir veya geçerli parametrik modda durur | PARTIAL. Varsayılan parametrik. 45 GLB, GLTFLoader ile ayrıştırıldı. Tarayıcıda 45 model tek tek açılmadı. |
| 2 | İç düzen farkları durur | PASS (ölçü). 45 ayrı imza. Görsel tur yok. |
| 3 | Geçersiz raf/çekmece çakışması sıfır | PASS (geometri ölçümü). 45/45 çakışma yok. |
| 4 | Köşe geometrisi | PARTIAL. Ölçülen delme yok. Ön/üst/çapraz görüntü yok. Üretim onayı verilmedi. |
| 5 | Çekmece kutusu eksiksiz | PASS (birim testi). 16 mm ve 13 mm ray payı atölye onayı değildir. |
| 6 | Gövde 18 mm | PASS |
| 7 | Raf 18 mm | PASS |
| 8 | Düz modül arkalığı 5 mm | PASS. Köşe dönüş paneli sac arkalık değildir. |
| 9 | 30 mm arkalık lefi | PASS (düz modüller). LED kanalı ayrı ve onaysız. |
| 10 | Kamera bütün dolapları kadraja alır | PASS (iki modüllük izole sahne). 45 modüllük sahne denenmedi. |
| 11 | Malzeme seçimi tarayıcıda | NOT RUN |
| 12 | Path tracing gerçek PNG | PASS (dosya var). Kalite FAIL. |
| 13 | WebGL ve path tracing karşılaştırması | PASS (karşılaştırma yapıldı). Path tracing daha kötü. İyileşme iddiası yok. |
| 14 | Kaydet / yeniden aç | NOT RUN |
| 15 | Modül çarpışması | PARTIAL. Birim test geçti. Ekranda Uygula çalıştırılmadı. |
| 16 | Mobil arayüz | NOT RUN |
| 17 | Performans sayıları | PARTIAL. Tek sahne. FPS yok. |
| 18 | Mevcut otomatik testler | PARTIAL. Geometri testleri geçti. `pt`/`en` anahtar testi önceden kırmızı. |
| 19 | Onaysız çizim CNC'ye açılmaz | PASS |
| 20 | Kritik güvenlik / veri kaybı | PARTIAL. Bu turda P0 üretilmedi. CSRF, XSS ve bağlam kaybı çalıştırılmadı. |

## Hata kayıtları

### MM-001 — GLB kullanılmıyor

- Önceki durum: layout bulunan her model parametrik dönüyor, GLB isteği yok, durum `parametric` idi.
- Değişiklik: gövde görünümü `parametric` | `glb` | `hybrid`. Varsayılan parametrik. `glb` yüklenince gövde değişir ve durum `ready` olur. `hybrid` GLB'yi `glb-reference` adıyla görünmez tutar; gövde iki kez çizilmez. Diskte dosya olması `glbStatus` için başarı sayılmaz; kayıt `not-loaded` başlar.
- Yeni sonuç: Vitest, 45 katalog GLB dosyasını `GLTFLoader.parseAsync` ile açtı, her birinde mesh var. İzole sahnede parametrik mod açıkken `.glb` isteği yok. Bu, sahte yükleme değil. Ekranda GLB düğmesine basılmadı; basılırsa izole proje kaydı değişirdi.
- Kanıt: `tools/openplan3d/tests/wardrobe-final.test.ts`, `test-results/meemare/geometry-audit.json` (`glbFile: true`, `glbLoadedByStudio: false`).
- Durum: PARTIAL

### MM-002, MM-003, MM-007, MM-009 — iç parça çakışması ve çekmece

- Önceki durum: çekmeceler tabana yapışıyor, raf çekmeceyle ve dikmeyle kesişiyor, bazı çekmeceler yalnız alt ve arka paneldi.
- Değişiklik: dış kabuk dışında `edge()` yapışması yok. Çekmece önü için iki yan, arka ve alt panel üretiliyor. Raf, çekmece üstünden 0,3 cm yukarı alınıyor. Dikme, baza ve alt panelin üstünden başlıyor. Düz dolapta oturtma, raf kalktıktan sonra yeniden çalışıyor.
- Yeni sonuç: 45 modelde `interiorCollide` yok, `penetrations` boş, gövde/raf/arkalık hataları boş. Çekmece önü olan modellerde yan sayısı önün iki katı, arka ve alt sayısı öne eşit (birim test).
- Kanıt: `test-results/meemare/geometry-audit.json` (`measuredAt` 2026-11-17T00:00:40.639Z).
- Durum: PASS (geometri). Çekmece kalınlığı ve ray payı PASS sayılmaz; `DRAWER_BOX.approved` false.

### MM-005 — köşe

- Önceki durum: köşe panelleri aynı hacimde kesişiyordu.
- Değişiklik: köşede ince eksen boyunca alın birleştirme. Kalan boy 4 cm altına inerse çekme yapılmıyor. `CORNER_JOINT.approved` false.
- Yeni sonuç: `kose_1`–`kose_5` delme listesi boş. Ön, üst ve çapraz görüntü üretilmedi.
- Kanıt: aynı audit dosyası, köşe satırları.
- Durum: PARTIAL

### MM-004 — LED kanalı ile 30 mm lef

- Değişiklik yok: lef 30 mm duruyor. `LED_CHANNEL` ölçüleri null, `approved` false. Görsel şerit duruyor. CNC kanalı yok.
- Durum: PASS (ayrım). Kanal üretimi NOT RUN ve kapalı.

### MM-008 — kesim listesi

- `cutList` parça kimliği, tipi, malzemesi, kalınlığı, eni, boyu, ekseni, kenar bandı, adedi ve modül kimliğini döner. `approved: false`, `cnc: false`.
- Durum: PASS (liste var, üretime açık değil).

### MM-006 — kamera

- Önceki durum: kalın duvar kadrajın yarısını kapatıyor, ikinci dolap dışarıda kalıyordu. İlk düzeltme kamerayı iki dolabın arasına soktu.
- Değişiklik: göz, dolap önüne bakar. Mesafe görüş açısına göre hesaplanır ve oda içinde tutulur. Işının ilk kestiği duvar gizlenir. Kamera değişimi geometriyi yeniden kurmaz.
- Yeni sonuç: izole sahnede iki dolap önden kadrajda. Duvar görüntüyü kapatmıyor. Tuval 692×901, draw call 110, üçgen 1208.
- Kanıt: `test-results/meemare/final/webgl-viewport-v323.png`, `test-results/meemare/final/webgl-front.png`.
- Durum: PASS (bu sahne). 45 modül ve telefon NOT RUN.

## Bu turda çalışmayanlar

Malzeme turu, 45 modelin ekran görüntüsü, 1–45 modül FPS serisi, kayıt/geri alma, mobil ölçüler, Firefox ve WebKit, path tracing iptali ve 16 kalite kombinasyonu çalıştırılmadı. Bunlar başarılı işaretlenmedi.
