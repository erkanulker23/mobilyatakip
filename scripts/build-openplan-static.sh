#!/usr/bin/env bash
# OpenPlan3D self-host (isteğe bağlı). Şu an iframe kullanılıyor; statik build için:
# cd tools/openplan3d && npm ci && npm run build
# Ardından PublicToolController plan src'sini public/openplan build çıktısına yönlendirin.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/tools/openplan3d"
if [[ ! -f package.json ]]; then
  echo "tools/openplan3d yok" >&2
  exit 1
fi
echo "Not: adapter-node kullanılıyor; tam self-host için @sveltejs/adapter-static gerekir."
echo "Şimdilik /plan harici OpenPlan iframe ile çalışır; meemare-plan-bridge.js handoff sağlar."
exit 0