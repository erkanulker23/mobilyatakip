# MEEMARE WALL DRAWING TEST

**Test ID:** S2-WALL-DRAW  
**Durum:** PARTIAL  

| Senaryo | Sonuç | Not |
| --- | --- | --- |
| Plan-first native sketch görünür | PASS | Harici iframe yok |
| L şablon (3 duvar) | PASS | ~9 m², snap 25 cm |
| Dikdörtgen şablon | NOT_TESTED | |
| Manuel W ile çizim | NOT_TESTED | |
| Kapı / pencere yerleştirme | NOT_TESTED | |
| İptal / Escape / sıfır uzunluk | NOT_TESTED | |
| 180–785 cm ölçü matrisi | NOT_TESTED | |
| Form ↔ tuval senkron | FAIL→FIXED_LOCAL | `QA-WALL-SYNC-001` |
| Çizimi uygula → 3D duvar 300 | PASS | Inspector 300/300/300 |

Kanıt: `test-results/master-e2e/project-creation/S1-plan-first-native-sketch.png`
