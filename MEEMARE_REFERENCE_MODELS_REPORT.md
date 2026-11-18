# Referans modeller

Tarih: 2026-11-02  
Durum: üç model seçildi. Kalite değişikliği diğer 42 modele yayılmadı. Canlıya push yok.

| Rol | Kimlik | Dosya |
| --- | --- | --- |
| Tek kapılı | `y26_tek_01` | `public/catalog/y26/y26_tek_01/model.glb` |
| Çift kapılı | `y26_cift_01` | `public/catalog/y26/y26_cift_01/model.glb` |
| Köşe | `kose_1` | `public/catalog/meemare25/kose_1/model.glb` ve stüdyonun yüklediği `public/catalog/meemare25-norm/kose_1.glb` |

Tek kapılı kaynakta dikme-çekmece çakışması yoktu. Geometrisi değiştirilmedi. Çift kapılı ve köşe kaynakları değiştirildi. Ayrıntı `MEEMARE_MODEL_QUALITY_REPORT.md`.

## Ne aynı standartta değil

Üç model de hâlâ düz panel ve katalog JPEG ahşabıdır. Metal kulp basit çubuktur. Kumaş aksesuar eklenmedi. Ultra Render tek kapalıda siyah yan yüz ve benek üretti. Bu üçlü fotoğraf gerçekçiliği onayı almadı. Kalan modüllere bu görünüm yayılmadı.

## Test

`node scripts/qa-reference-quality.mjs`  
Kanıt: `test-results/reference-quality/results.json`  
Görüntüler: `test-results/reference-quality/shots/`

Sunucu kaydı HTTP 503. `userId` kolonu bağlı veritabanında yok. Kayıt satırı açılmadı.
