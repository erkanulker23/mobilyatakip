#!/usr/bin/env python3
"""Mevcut Y26 GLB dosyalarından katalog önizlemesi (Cycles) üretir."""
from __future__ import annotations

import json
import math
import os
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BLENDER = Path("/opt/homebrew/bin/blender")
SCRIPT = ROOT / "scripts/blender-render-y26-preview.py"
MAP = ROOT / "public/data/vm-v4-y26-mapping.json"


def main() -> None:
    if not BLENDER.exists():
        raise SystemExit("Blender yok")
    mapping = json.loads(MAP.read_text(encoding="utf-8"))
    only = sys.argv[1:] if len(sys.argv) > 1 else []
    for row in mapping["modules"]:
        y26 = row["y26Id"]
        if only and y26 not in only:
            continue
        glb = ROOT / "public/catalog/y26" / y26 / "model.glb"
        preview = ROOT / "public/catalog/y26" / y26 / "preview.png"
        if not glb.exists():
            print("skip", y26)
            continue
        subprocess.run(
            [
                str(BLENDER),
                "-b",
                "--python",
                str(SCRIPT),
                "--",
                "--glb",
                str(glb),
                "--out",
                str(preview),
                "--width",
                str(row["width"]),
                "--height",
                str(row["height"]),
                "--depth",
                str(row["depth"]),
            ],
            check=True,
        )
    catalog = ROOT / "public/data/wardrobe-y26-modules.json"
    data = json.loads(catalog.read_text(encoding="utf-8"))
    for mod in data["modules"]:
        if only and mod["id"] not in only:
            continue
        p = mod.get("previewPath", "")
        if "?v=" in p:
            mod["previewPath"] = p.split("?v=")[0] + "?v=5"
        mod["catalogRender"] = "cycles-v5"
    catalog.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print("done previews v=5")


if __name__ == "__main__":
    main()