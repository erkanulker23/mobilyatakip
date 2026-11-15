# Stabilite testi

Tarih: 2026-11-02  
Ortam: yerel `http://127.0.0.1:8765`, headless Chrome, oturum `erkanulker0@gmail.com`  
Komut: `node scripts/qa-reference-quality.mjs`  
Kanıt: `test-results/reference-quality/results.json` ve `performance.json`

Gerçek müşteri projesi açılmadı ve değiştirilmedi.

## Sonuçlar

| İş | Sonuç | Ölçüm |
| --- | --- | --- |
| 50 kez `y26_tek_01` ekle ve sil | PASS | Sayfa içi döngü 372 ms. Her turda sayı 1 oldu, silince 0 oldu |
| 100 malzeme ataması | PASS | Sayı 1 kaldı |
| 30 kapak aç-kapa | PASS | Sayı 1 kaldı. Son durum kapalı |
| Geri al / ileri al | PASS | 2 modül, geri alınca 1, ileri alınca 2. Kimlikler `y26_tek_01` ve `y26_cift_01` |
| Sayfa yenileme | PASS | Taslak 2 modülü geri getirdi |
| Sunucu kaydı | BLOCKED | `POST /dolap/proje` HTTP 503, “Proje kaydı henüz hazır değil.” Satır yazılmadı |
| Raster kare süresi | MEASURED | 59 kare, ortalama 16.7 ms, 987 ms. 2 modül |

16.7 ms, yaklaşık 60 kare/sn ekran hızıdır. GPU süresi ayrı ölçülmedi.

İki modüllü sahnede draw call 137, üçgen 24044, doku 10. JS yığın, 60 kare sonunda 40075663 bayt.

Stres öncesi geometri sayısı 17, yığın 28166562 bayt. Stres ve iki modülden sonra geometri sayısı 128, yığın 27559985 bayt. Yığın düştü, geometri sayısı başlangıca dönmedi. Saatler süren bir sızıntı ölçülmedi. Sayaç baz çizgisine inmediği için sızıntı kapanmış denmez.

## Kod

Her sahne kopyası kendi geometrisini taşır. Silinen dolabın geometrisi artık pişmiş şablonun geometrisini de silmez. `placeGlb` kopyadan sonra `geometry.clone()` çağırır.

Taslak `sessionStorage` anahtarı `meemare-wardrobe-draft`. Sunucu belirteci yokken her düzenleme yazılır. Proje adresi yoksa sayfa açılışı taslağı geri yükler. Yeni proje taslağı siler. Sunucu kaydı başarısız olursa metin, tasarımın ekranda durduğunu söyler.

Sunucu kaydı, `userId` kolonu eklenmeden açılmaz. Bu kolon bu turda da uygulanmadı.
