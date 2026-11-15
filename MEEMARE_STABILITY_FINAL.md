# Stabilite — ara sonuç

İş emri: MEEMARE-MASTER-2026-002  
Tarih: 2026-11-02  
Karar: Uzun oturum stabilitesi kanıtlanmadı. Ticari kullanıma açılmadı.

## Ölçülen

Komut: `node scripts/qa-reference-quality.mjs`  
Kayıt: `test-results/reference-quality/results.json`

| İşlem | Sonuç |
| --- | --- |
| 50 ekle / sil, `y26_tek_01` | PASS. 50 eklendi, 50 silindi, sahnede 0 kaldı. Sayfa içi süre 372 ms |
| 100 boya | PASS. Sahnede 1 modül kaldı |
| 30 kapak aç/kapa | PASS. Son durum kapalı, adet 1 |
| Geri al / yinele | PASS. 2 modül, geri al 1, yinele 2 |
| Taslak | PASS. Belirteç yokken oturum taslağı iki modülü geri açtı |
| 60 kare raster | Ölçüldü. Ortalama 16,7 ms. Bu, ekran tazelemesine kilitli süredir; GPU karesi değildir |
| Geometri sayısı | 17’den 128’e çıktı. Yığın 28,2 MB’den 27,6 MB’ye indi, sonra 40,1 MB ölçüldü. Sızıntı kapanmadı |
| Sunucu kaydı | BLOCKED. HTTP 503, “Proje kaydı henüz hazır değil.” `userId` kolonu bağlı veritabanında yok. Migration çalıştırılmadı |

## Bu listede yok

25 sürükle-bırak, 20 kamera değişimi, art arda render, modüller arası hızlı geçiş, pencere boyutu, bağlantı kopup gelme ve GPU kaynak sayımı bu turda çalıştırılmadı. Bunlar NOT_TESTED. Başarılı yazılmadı.

Güvenlik testi aynı gün yeniden geçti: `php artisan test --filter=WardrobeProjectAccessTest`, 1 test, 56 assertion, PASS. Kanıt dosyası önceki koşu için `test-results/security-fix/phase1-phpunit.txt`. Düzeltme yerel commit `dbe7367` üzerindedir, `origin/main` hâlâ `98ef714`. Bağlı veritabanına migration uygulanmadı. Canlıda açık duruyor.
