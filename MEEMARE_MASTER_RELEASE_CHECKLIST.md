# Yayın kontrol listesi

İş emri: MEEMARE-MASTER-2026-002  
Tarih: 2026-11-02  
Yayın kararı: Hayır. Push yok. Production verisi değişmedi.

| Kapı | Durum | Kanıt |
| --- | --- | --- |
| QA-SEC-001 / QA-SEC-002 yerel test | PASS, yayında değil | `php artisan test --filter=WardrobeProjectAccessTest` bugün 56 assertion. Commit `dbe7367` push edilmedi. Bağlı veritabanında `userId` yok |
| Üç referans geometri | Kısmi | `MEEMARE_REFERENCE_MODELS_FINAL.md` |
| Siyah yan yüz, tek kapılı, 48 örnek | Düzeltildi | `test-results/reference-quality/black-face-measure.json` |
| Benek, koyu iç, cam çeşitleri | FAIL | Aynı dosya. Cam karşılaştırması yok |
| 50 ekle/sil, 100 boya, 30 kapak, geri al | PASS | `test-results/reference-quality/results.json` |
| Sürükle, kamera, art arda render, yeniden bağlanma | NOT_TESTED | — |
| Bellek / geometri birikimi | FAIL | Geometri 17 → 128 |
| Sunucu kayıt ve aynı açılış | BLOCKED | HTTP 503 |
| Dokuz duvar × 45 modül | NOT_TESTED | Sessiz köşe değişimi duruyor |
| Gerçek telefon | NOT_TESTED | Yalnızca 390×844 taklit |
| Diğer 42 modül | Uygulanmadı | Üçlü onay yok |

Sıradaki iş, onaylanmış üçlüden önce değildir: benek ve iç aydınlatmayı aynı karede ölçerek düşürmek, kaynak çekmece kabuğunu sahneyle eşitlemek, `userId` migration’ını ancak operatör kararıyla uygulamak, sonra kayıt turunu müşteri projelerine dokunmadan tekrarlamak.
