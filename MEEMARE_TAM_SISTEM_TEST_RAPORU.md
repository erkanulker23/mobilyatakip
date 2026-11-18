# Meemare tam sistem testi

Tarih: 17 Kasım 2026. Ortam: yerel Laravel `http://127.0.0.1:8765`. Tarayıcı: Cursor Chromium. Ölçülen proje: disposable “Kalite turu”. Müşteri projelerine yazılmadı. Teklif gönderilmedi.

Durum sözcükleri yalnızca şunlardır: PASS, FAIL, BLOCKED, NOT RUN. Kodun durması bir satırı PASS yapmaz. Emülasyon fiziksel telefon değildir. Ölçülmeyen FPS ve render süresi yazılmadı.

| Bölüm | Test sonucu | Başarılı | Hatalı | Test edilemedi |
|---|---|---|---|---|
| 45 GLB model | FAIL | 45 dosya diskte | 45 model sahnede GLB yüklemiyor | 0 |
| Parametrik geometri | FAIL | 45 modelde 18/18/5 mm ve dış ölçü | 15 modelde iç hacim çakışması; 30 mm LED kanalı yok | 0 |
| Modül yerleştirme | PASS | A–F duvar hesabı, örtüşme, köşe birleşimi | 0 | Sürükle-bırak, dokunma, 20 modül ekleme |
| Köşe birleşimi | FAIL | Algoritma komşu duvarı overlap saymıyor | 5 köşe modelinde paneller birbirinin içine giriyor | Beş modelin tarayıcı görüntüsü |
| MDF ve malzemeler | NOT RUN | 0 | 0 | Katalog renklerinin görsel turu |
| Cam ve profiller | NOT RUN | 0 | 0 | Cam ve alüminyum seçicisinin görsel turu |
| WebGL görüntüsü | FAIL | 1 proje 3D açıldı | Komşu dolap kadraj dışında, duvar görüntünün yarısını kaplıyor | 45 model karesi |
| Path Tracing | NOT RUN | 0 | 0 | 64/256/512/1024 × dört çözünürlük |
| Kamera ve PNG | NOT RUN | 0 | 0 | Ön, çapraz, üst, detay, PNG indirme |
| Kayıt ve API | PASS | 2 PHPUnit (yetki ve sınır) | 0 | Tarayıcıda kayıt, yenileme, undo, bozuk JSON |
| Performans | NOT RUN | 0 | 0 | 1–45 modül FPS, heap, draw call |
| Mobil kullanım | NOT RUN | 0 | 0 | 390×844 yalnızca emülasyon; diğer ölçüler ve Firefox/WebKit yok |
| Güvenlik | PASS | 1 yetki testi: başka kullanıcı projeyi açamıyor | 0 | CSRF, XSS ve oturum probu bu turda yok |

## Yığın

Kaynak bağımlılıklar doğrulandı: PHP `^8.3`, Laravel `^12.0`, PHPUnit `^11.5.3`, Svelte `^5.57`, TypeScript `^5.9`, Three.js `^0.182`, `three-gpu-pathtracer` `0.0.24`, Vitest `^5.0.5`, Playwright `1.63.0` (OpenPlan paketinde). Kökte Playwright config yok. MySQL şeması bu turda sorgulanmadı. Tasarım sayfası kayıtlı projeyi açtığı için uygulama veritabanına bağlanıyor.

Tasarım yolu OpenPlan editörüdür. Eski `wardrobe-studio.js` bu sayfada yüklü değildir.

## Aşamalar

Aşama 1 malzeme ve ışık: malzeme grupları için birim test var. Katalog renkleri ekranda karşılaştırılmadı. NOT RUN.

Aşama 2 path tracing: `TRACE_SAMPLES` 64, 256, 512 ve 1024 değerlerini tanımlıyor. Çerçeve hesabı birim testte geçti. Bu turda GPU görüntüsü üretilmedi. Kalite artışı kanıtlanmadı. NOT RUN.

Aşama 3 parametrik gövde: 45 layout yerinde. Kalınlık ve lef ölçüsü geçti. İç çakışmalar duruyor. FAIL.

Aşama 4 yerleştirme: paylaşılan milimetre kuralları Vitest ve PHPUnit ile geçti. Sürükleme arayüzü bu turda çalıştırılmadı.

## Ne çalıştırıldı

- `npx vitest run tests/wardrobe-system-audit.test.ts tests/wardrobe-realism.test.ts tests/module-placement.test.ts` — 35 test, geçti.
- `php artisan test --filter='ModulePlacementTest|WardrobeProjectAccessTest'` — 5 test, 98 assertion, geçti.
- 45 model `placeCabinet` ile katalog ölçüsünde kuruldu. Kanıt: `test-results/meemare/geometry-audit.json`.
- Disposable proje Chromium’da açıldı, 3D tuşuna basıldı, WebGL tuvali geldi. Görüntü: `test-results/meemare/screenshots/kalite-turu-3d-webgl.png`.
- 390×844 cihaz emülasyonu uygulandı. Bu bir telefon testi değildir. Görüntü: `test-results/meemare/screenshots/emulation-390x844.png`.

## Ölçü

Tolerans 0.05 cm, yani 0.5 mm. Sahne santimetre kullanır.

Düz modüllerin yan, üst ve alt tablası 18 mm. Raflar 18 mm. Arkalık 5 mm. Arkalığın arka yüzü, dolabın arka kenarından 30 mm içeride. 450 mm model 900 mm yapılınca yan kalınlık 18 mm kaldı. Kapak kanadı 18 mm, kasa 16 mm; bu kapak ölçüsü kodda `verified: true`. Gövde kesim listesi onaylı değil. `CONSTRUCTION.approved` false.

Köşe arkalığı 5 mm levha değil. 18 mm yapı paneli. Lef hesabı köşeye uygulanmıyor.

30 mm LED kanalı yok. 30 mm, arkalık lefidir (`BACK_GROOVE_MM`). LED, dikmede ince ışıklı şerittir. Fiziksel 30 mm LED boşluğu ölçülmedi çünkü geometride yok.

İlk ölçümde `edge()` kuralı, yüksekliği alt yüzde 12’de başlayan çekmece ve rafı zemine yapıştırıyordu. İki çekmece aynı 0–12 cm kutuya iniyordu. Kural daraltıldı: yalnızca dış kabuk (`minN <= 0.025` veya `minN >= 0.9`) kalınlığa kilitleniyor. Aynı turda düz dolapta baza yanların arasına alındı, dikme alt tablanın altına inmeyecek şekilde kesildi, tam genişlik raf ve askı dikmede bölündü, tam genişlik çekmecenin üstünden dikme başlatıldı. Yeniden ölçüm bu tablonun kaynağıdır. Zemine yapışan çekmece çifti tekrar ölçümde yok.

Kalan iç çakışma 15 modelde duruyor. Ayrıntı `MEEMARE_HATA_LISTESI.md` ve model tablosundadır.

## Duvar senaryoları

Montaj payı 0 mm. `packRun` ve `suggestRun` sonucu:

| Test | Duvar | Modüller | Sonuç |
|---|---|---|---|
| A | 3150 | 900+900+900+450 | PASS, sığar, kalan 0 |
| B | 2700 | 900+900+900 | PASS, sığar, kalan 0 |
| C | 3000 | 900+900+900+450 | PASS, reddedilir, kullanılan 3150, kalan −150 |
| D | 4000 | 900+900+450 | PASS, kalan 1750, paket baştan dizildiği için kalan tek aralıktır |
| E | örtüşme | aynı duvarda iki gövde | PASS, `overlap` |
| E köşe | iki duvar | dik birleşim | PASS, `ok` |
| F | otomatik | `suggestRun(3150, [900, 450])` | PASS, 900+900+900+450 önerir. `takeProposal` onay yoksa boş dizi döner |

Arayüzde Otomatik Yerleştir’e basılmadı. Uygula’ya basılmadı.

## Tarayıcı

Kalite turu 3D görünümünde açık gri gövde, raflar, bir dikme, iki askı ve açık renk arkalık göründü. İkinci dolap sol kenarda kesik. Sağ yarıyı kalın kahverengi bir duvar kaplıyor. Bu kare fotogerçekçi değildir. Path tracing aynı karede çalıştırılmadı.

Üst çubuk Türkçe: 2D, 3D, Kaydet. Konsol dökümü bu turda toplanmadı.

## En kritik 10 bulgu

1. 45 GLB diskte duruyor. Stüdyo bunları yüklemiyor. Sahne parametrik kutulardan oluşuyor.
2. 30 mm fiziksel LED kanalı yok. 30 mm yalnızca arkalık lefi.
3. 15 modelde raf, çekmece veya dikme hâlâ aynı hacmi paylaşıyor. `y26_cift_02`, `y26_cift_03`, `y26_cift_06`, `y26_cift_09` ve `cift_9` içinde raf ile çekmece çakışıyor.
4. Köşe modellerinin panelleri L dönüşünde birbirinin içine giriyor. Ortak panel onaylı değil.
5. Çekmece kutusu beş yüzlü kutu değil. v serisinde alt ve arka tahta aynı rolde ve köşede kesişiyor.
6. Gövde kesim listesi ve CNC kapalı. `CONSTRUCTION.approved` false.
7. WebGL karesinde ürün kadrajı bozuk: komşu dolap kesik, duvar görüntünün yarısı.
8. Path tracing’in örnek sayısı arttıkça görüntüyü iyileştirdiği bu turda kanıtlanmadı.
9. İlk ölçüm, alttaki çekmece ve rafları zemine yapıştırıyordu. Bu turda düzeltildi ve yeniden ölçüldü.
10. Sürükle-bırak, 20–45 modül, kayıt turu, bozuk JSON, Firefox, WebKit ve fiziksel telefon çalıştırılmadı. Bu satırlar PASS değildir.
