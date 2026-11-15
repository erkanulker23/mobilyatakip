# MEEMARE SAVE LOAD INTEGRITY TEST

**Durum:** PASS (tek modül state diff)  
**Koşu:** `scripts/qa-e2e-master.mjs` · 2026-11-15 · yerel `:8765`

| Kontrol | Sonuç |
| --- | --- |
| Kaydet (`btn-save`) | PASS |
| Reload sonrası modül sayısı | PASS (1 → 1) |
| `wallWidth` | PASS (300) |
| Modül kimliği / glb / width / wall / offset | PASS (`vi6ubh9r`, `y26_tek_01`, 45, front, 255) |
| `carcassId` / `doorColorId` D123 | PASS |

Kanıt:
- `test-results/master-e2e/save-load/before.json`
- `test-results/master-e2e/save-load/after.json`
- `test-results/master-e2e/save-load/after-reload.png`

Not: Tam 45-modül / kapak-çekmece / malzeme kombinasyon matrisi henüz değil; kritik mutasyon (modül+duvar+renk) eşleşti.
