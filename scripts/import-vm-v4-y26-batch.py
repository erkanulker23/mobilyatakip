#!/usr/bin/env python3
"""Split vm_v4 objects from desktop OBJ and export Y26 catalog GLB + preview."""
from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC_OBJ = Path("/Users/erkanulker/Desktop/Dolap Modülleri/dolap modülleri obj.obj")
MAP = ROOT / "public/data/vm-v4-y26-mapping.json"
SPLIT = ROOT / "scripts/split-obj-object.py"
BLENDER = Path("/opt/homebrew/bin/blender")
BLENDER_SCRIPT = ROOT / "scripts/blender-vm-v4-to-y26.py"
STAGING = ROOT / "storage/vm-v4-obj"
CATALOG = ROOT / "public/catalog/y26"


def run(cmd: list[str]) -> None:
    print("+", " ".join(cmd), flush=True)
    subprocess.run(cmd, check=True)


def main() -> None:
    if not SRC_OBJ.exists():
        raise SystemExit(f"Kaynak OBJ yok: {SRC_OBJ}")
    if not BLENDER.exists():
        raise SystemExit(f"Blender bulunamadı: {BLENDER}")

    mapping = json.loads(MAP.read_text(encoding="utf-8"))
    only = sys.argv[1:] if len(sys.argv) > 1 else None

    for row in mapping["modules"]:
        y26 = row["y26Id"]
        if only and y26 not in only:
            continue
        vm = row["vmObject"]
        piece = STAGING / f"{vm}.obj"
        if not piece.exists():
            run([sys.executable, str(SPLIT), str(SRC_OBJ), vm, str(piece)])

        out_dir = CATALOG / y26
        out_dir.mkdir(parents=True, exist_ok=True)
        glb = out_dir / "model.glb"
        bak = out_dir / "model.parametric.glb.bak"
        if glb.exists() and not bak.exists():
            glb.rename(bak)

        preview = out_dir / "preview.png"
        meta = out_dir / "vm-v4-import.json"
        run(
            [
                str(BLENDER),
                "-b",
                "--python",
                str(BLENDER_SCRIPT),
                "--",
                "--obj",
                str(piece),
                "--out-glb",
                str(glb),
                "--out-preview",
                str(preview),
                "--width",
                str(row["width"]),
                "--height",
                str(row["height"]),
                "--depth",
                str(row["depth"]),
                "--meta-out",
                str(meta),
            ]
        )

    imported = {
        row["y26Id"]
        for row in mapping["modules"]
        if not only or row["y26Id"] in only
    }
    catalog_path = ROOT / "public/data/wardrobe-y26-modules.json"
    data = json.loads(catalog_path.read_text(encoding="utf-8"))
    for mod in data["modules"]:
        if mod["id"] not in imported:
            continue
        p = mod.get("previewPath", "")
        if "?v=" in p:
            mod["previewPath"] = p.split("?v=")[0] + "?v=4"
        mod["sourceViessmann"] = next(
            (r["vmObject"] for r in mapping["modules"] if r["y26Id"] == mod["id"]),
            None,
        )
        mod["geometrySource"] = "vm_v4_obj"
        mod["version"] = 2
    catalog_path.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print("Done.", flush=True)


if __name__ == "__main__":
    main()