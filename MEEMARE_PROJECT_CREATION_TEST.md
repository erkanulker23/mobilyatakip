# MEEMARE PROJECT CREATION TEST

**Test ID:** S1-PROJECT-CREATE  
**Tarih:** 2026-11-15  
**Ortam:** `127.0.0.1:8765` + yerel MySQL  

| Adım | Beklenen | Gerçek | Sonuç | Kanıt |
| --- | --- | --- | --- | --- |
| Giriş e2e-qa | Dashboard | Dashboard | PASS | browser session |
| `/dolap` aç | Projeler hub | Hub + form | PASS | snapshot |
| Müşteri + proje adı | Form dolu | E2E Test Müşteri / E2E-MASTER-QA-001 | PASS | |
| Proje oluştur | Redirect `tasarla?proje=` | `proje=kIWVNI7ikNxqCFukSxKb287Q` | PASS | URL |
| DB kayıt | userId = e2e user, modules=[] | Eşleşti | PASS | tinker JSON |
| Çift kayıt | Tek satır | Tek satır (manuel) | PASS | |
| Boş sahne | Modül yok | 0 modül | PASS | DB + 3D |
| Plan adımı | Gömülü çizim | Native sketch, OpenPlan 404 yok | PASS | `S1-plan-first-native-sketch.png` |

**Bulunan hata:** Plan form varsayılanları (320/280) tuval L ölçüsü (300) ile uyumsuz → `QA-WALL-SYNC-001` düzeltildi (`syncFormFields` + default 300).
