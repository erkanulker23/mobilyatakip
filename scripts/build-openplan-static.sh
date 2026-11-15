#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/tools/openplan3d"
if [[ ! -f package.json ]]; then
  echo "tools/openplan3d yok — laanlabs/openPlan3D klonlayın" >&2
  exit 1
fi
PATCH="$ROOT/scripts/openplan-meemare"
cp "$PATCH/svelte.config.static.mjs" svelte.config.js
cp "$PATCH/app.html" src/app.html
cp "$PATCH/layout.ts" src/routes/+layout.ts
mkdir -p src/routes/editor src/routes/render-lab
cp "$PATCH/editor-page.ts" src/routes/editor/+page.ts
cp "$PATCH/render-lab-page.ts" src/routes/render-lab/+page.ts
npm install -D @sveltejs/adapter-static@3
cp svelte.config.js svelte.config.node.bak
export NODE_ENV=production
export OPENPLAN_STATIC=1
npm run build
if [[ -f svelte.config.node.bak ]]; then
  mv svelte.config.node.bak svelte.config.js
fi
echo "OpenPlan build → $ROOT/public/openplan"