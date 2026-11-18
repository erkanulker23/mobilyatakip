#!/usr/bin/env bash
# Fail if a catalog-glb Y26 model is a stub (< 80 KB).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MIN=80000
bad=0
while IFS= read -r file; do
  size=$(wc -c < "$file" | tr -d ' ')
  if (( size < MIN )); then
    echo "STUB: $file ($size B)" >&2
    bad=1
  fi
done < <(find "$ROOT/public/catalog/y26" -name 'model.glb' 2>/dev/null)
if (( bad )); then
  echo "Replace stub GLBs with full catalog meshes (>= ${MIN} B)." >&2
  exit 1
fi
echo "Y26 GLB size check OK (>= ${MIN} B)"
