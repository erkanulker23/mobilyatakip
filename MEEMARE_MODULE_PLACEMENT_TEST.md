# MEEMARE MODULE PLACEMENT TEST

**Durum:** PASS (matris + köşe)  
**Koşu:** `scripts/qa-e2e-master.mjs` · 2026-11-15 · yerel `:8765` · push yok

| Senaryo | Sonuç | Kanıt |
| --- | --- | --- |
| 180 cm ×4 ×45 | PASS | `test-results/master-e2e/module-placement/front-180.png` |
| 225 cm ×5 ×45 | PASS | `front-225.png` |
| 270 cm ×6 ×45 | PASS | `front-270.png` |
| 315 cm ×7 ×45 | PASS | `front-315.png` |
| 413 cm ×9 ×45 (kalan 8) | PASS | `front-413.png` |
| Varsayılan renk D123 | PASS | save before/after `carcassId=melamin-kapli-panel-D123` |
| L köşe (Köşe V1) | PASS | `test-results/master-e2e/corner/l-corner-attempt.png` |
| Sürükle-yapışma sihirbaz | NOT_TESTED (kod var) | — |
| Ortadan sil + yeniden ekle | NOT_TESTED | — |

Özet log: `test-results/master-e2e/functional-log.json` · `SUMMARY { PASS: 11 }`
