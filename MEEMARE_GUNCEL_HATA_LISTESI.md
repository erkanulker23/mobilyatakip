# Meemare güncel hata listesi

17 Kasım 2026, embed `v=324`. Kapanan satırlar geometri ölçümü veya izole sahnedeki ekran görüntüsüyle kapanmıştır. Görülmeyen satır açık kalır.

| Kod | Konu | Önceki | Şimdi | Kanıt | Durum |
|---|---|---|---|---|---|
| MM-001 | 45 GLB stüdyoda yüklenmiyor | Layout varsa GLB hiç istenmiyordu | Parametrik / GLB / hibrit ayrıldı. 45 dosya loader ile ayrıştı. Canlı sahnede GLB düğmesi denenmedi | `wardrobe-final.test.ts`, audit `glbLoadedByStudio: false` | PARTIAL |
| MM-002 | Çekmece tabana yapışıyor | İki çekmece y=0 düzleminde | İç parça kenara yapışmıyor | audit, delme listesi boş | PASS |
| MM-003 | Raf–çekmece ve raf–dikme | Kesişim vardı | 45/45 iç çakışma yok | `geometry-audit.json` | PASS |
| MM-004 | LED kanalı ile lef karışık | Tek kanal sanılıyordu | Lef 30 mm. LED kesiti null ve onaysız | `LED_CHANNEL` | PASS |
| MM-005 | Köşe panelleri iç içe | 18 mm kesişim | Ölçülen delme yok. Fotoğraf ve atölye onayı yok | köşe satırları, `CORNER_JOINT.approved=false` | PARTIAL |
| MM-006 | Kamera duvarın içinde | Duvar kadrajı kapatıyordu | İki dolap önden görünüyor | `final/webgl-viewport-v323.png` | PASS |
| MM-007 | Çekmece kutusu eksik | Yalnız alt ve arka | Ön, iki yan, arka, alt. 16 mm / 13 mm onaysız | birim test, `DRAWER_BOX.approved=false` | PASS |
| MM-008 | Kesim listesi üretime açık görünmesin | Onay kapalıydı | Liste var, `cnc: false` | `cutList` testi | PASS |
| MM-009 | Baza içine giren dikme | Dikme bazanın içindeydi | Dikme, baza ve alt panelin üstünden başlıyor | audit | PASS |
| MM-010 | Path tracing PNG yoktu | Hiç PNG yoktu | Üç PNG var. Görüntü karanlık ve gürültülü. 64 örnek tamamlanmadı | `final/pathtrace-64-*.png` | FAIL (kalite) |
| MM-011 | Ön render kadrajı duvara bakıyordu | Küre formülü kamerayı karşı duvara taşıdı | Sığdırma `tan` ile yeniden hesaplandı. Yeni ön kare dolapları gösteriyor, üstten kesiyor | `pathtrace-64-front-gray.png`, `pathtrace-64-front.png` | PARTIAL |
| MM-012 | Path trace süresi bütçeyi aşıyor | Hızlı bütçe 120 sn | Mevcut kamera 156,8 sn ve 38/64. Ön kadraj 243,8 sn ve 21/64. İkisi de `limited` | render sonucu | FAIL |
| MM-013 | 45 modelin görsel turu | Yapılmamıştı | Hâlâ yok. Geometri tablosu görsel PASS değildir | — | NOT RUN |
| MM-014 | Malzeme turu | NOT RUN | MDF, lake, cam, metal tarayıcıda seçilmedi | — | NOT RUN |
| MM-015 | FPS ve bellek serisi | Tek modülde 324 üçgen vardı | Bir modül auditte 372 üçgen. İki modüllük sahnede 1208 üçgen, 110 draw call. FPS yok | audit, tuval verisi | NOT RUN |
| MM-016 | Kayıt, yenile, geri al | Yalnız yetki testi | Bu turda kullanıcı akışı yok | — | NOT RUN |
| MM-017 | Mobil ve dokunmatik | Eski 390 emülasyonu | 375, 390, 430, 768, 1440, 1920 bu turda yok. Emülasyon telefon değildir | — | NOT RUN |
| MM-018 | `pt` sözlük anahtarları | `en` ile eşit değil | Bu tur düzeltilmedi. Vitest kırmızı kalır | `tests/localization.test.ts` | FAIL (önceden) |

Açık üretim kilidi: gövde, raf, arkalık ve çekmece ölçüleri görsel standarttır. Atölye onayı yoktur. Kesim listesi ve köşe birleşimi üretime hazır gösterilmez.
