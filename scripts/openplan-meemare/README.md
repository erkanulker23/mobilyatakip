# OpenPlan3D — Meemare statik build yamaları

Kaynak: [laanlabs/openPlan3D](https://github.com/laanlabs/openPlan3D) → `tools/openplan3d` (gitignored vendor).

`scripts/build-openplan-static.sh` derlemeden önce bu dosyaları kopyalar:

| Patch | Hedef |
| --- | --- |
| `svelte.config.static.mjs` | `tools/openplan3d/svelte.config.js` |
| `app.html` | `tools/openplan3d/src/app.html` |
| `layout.ts` | `tools/openplan3d/src/routes/+layout.ts` |
| `editor-page.ts` | `tools/openplan3d/src/routes/editor/+page.ts` |
| `render-lab-page.ts` | `tools/openplan3d/src/routes/render-lab/+page.ts` |

Çıktı: `public/openplan/` ( `/plan` yerel iframe ).