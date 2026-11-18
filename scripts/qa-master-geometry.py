#!/usr/bin/env python3
"""Read-only geometry audit of catalog GLBs. Does not modify models."""
import json
import os
import struct
from pathlib import Path

ROOT = Path("/Users/erkanulker/mobilyatakip")
OUT = ROOT / "test-results/master-qa/geometry-tests"
OUT.mkdir(parents=True, exist_ok=True)

def glb_meshes(path: Path):
    data = path.read_bytes()
    if data[:4] != b"glTF":
        raise ValueError("not glb")
    off = 12
    clen, ctype = struct.unpack_from("<II", data, off)
    js = json.loads(data[off + 8 : off + 8 + clen])
    acc = js["accessors"]
    meshes = []
    for node in js.get("nodes", []):
        if node.get("mesh") is None:
            continue
        mesh = js["meshes"][node["mesh"]]
        prim = mesh["primitives"][0]
        a = acc[prim["attributes"]["POSITION"]]
        if "min" not in a or "max" not in a:
            continue
        mn = a["min"]
        mx = a["max"]
        size = [mx[i] - mn[i] for i in range(3)]
        meshes.append({
            "name": node.get("name") or mesh.get("name") or "",
            "min": mn,
            "max": mx,
            "size": size,
        })
    return meshes

def to_mm(meshes):
    peak = 0
    for mesh in meshes:
        peak = max(peak, max(mesh["size"]))
    scale = 1000.0 if peak < 20 else 1.0
    unit = "m" if scale == 1000 else "mm"
    out = []
    for mesh in meshes:
        size = [v * scale for v in mesh["size"]]
        out.append({
            "name": mesh["name"],
            "size_mm": [round(v, 2) for v in size],
            "thickness_mm": round(min(size), 2),
        })
    return unit, out

def role(name: str) -> str:
    n = name.lower()
    if any(k in n for k in ("cekmece", "drawer")):
        return "drawer"
    if any(k in n for k in ("raf", "shelf")):
        return "shelf"
    if any(k in n for k in ("arkalik", "back")):
        return "back"
    if any(k in n for k in ("askilik", "boru", "rail")):
        return "rail"
    if any(k in n for k in ("kapak", "door")):
        return "door"
    if any(k in n for k in ("baza", "plinth")):
        return "plinth"
    if any(k in n for k in ("ust", "top", "alt", "bottom")):
        return "panel"
    if any(k in n for k in ("sol", "sag", "left", "right", "outer", "dikme", "end")):
        return "side"
    return "other"

def overlap_mm(a, b):
    dims = []
    for i in range(3):
        lo = max(a["min"][i], b["min"][i])
        hi = min(a["max"][i], b["max"][i])
        dims.append(hi - lo)
    return dims

def load_catalog():
    rows = {}
    for name in ("wardrobe-y26-modules.json", "wardrobe-glb-modules.json"):
        data = json.loads((ROOT / "public/data" / name).read_text())
        series = "y26" if "y26" in name else "meemare25"
        for item in data["modules"]:
            rows[item["id"]] = {**item, "series": series, "catalogFile": name}
    return rows

def main():
    catalog = load_catalog()
    catalog_by_key = {key.casefold(): value for key, value in catalog.items()}
    modules = []
    for folder in sorted((ROOT / "public/catalog").glob("*/*/module.json")):
        spec = json.loads(folder.read_text())
        mid = spec.get("id") or folder.parent.name
        modules.append((mid, folder.parent, spec))
    seen = {mid.casefold() for mid, _, _ in modules}
    results = []
    for mid, folder, spec in modules:
        cat = catalog.get(mid) or catalog_by_key.get(mid.casefold(), {})
        model = folder / "model.glb"
        if not model.exists():
            alt = ROOT / "public/catalog/meemare25-norm" / f"{mid}.glb"
            model = alt if alt.exists() else model
        preview = folder / "preview.png"
        row = {
            "id": mid,
            "name": cat.get("name") or spec.get("name"),
            "series": cat.get("series") or ("y26" if mid.startswith("y26") else "meemare25"),
            "catalogId": cat.get("id"),
            "inCatalogJson": bool(cat),
            "previewExists": preview.exists(),
            "previewBytes": preview.stat().st_size if preview.exists() else 0,
            "glbPath": str(model.relative_to(ROOT)) if model.exists() else "",
            "glbExists": model.exists(),
            "catalogMm": {
                "w": round(float(cat.get("width") or 0) * (1000 if float(cat.get("width") or 0) < 20 else 1), 1),
                "h": round(float(cat.get("height") or 0) * (1000 if float(cat.get("height") or 0) < 20 else 1), 1),
                "d": round(float(cat.get("depth") or 0) * (1000 if float(cat.get("depth") or 0) < 20 else 1), 1),
            },
            "declaredBoardMm": cat.get("boardMm"),
            "declaredBackMm": cat.get("backMm"),
            "drawers": cat.get("drawers"),
            "shelves": cat.get("shelves"),
            "cornerShape": cat.get("cornerShape") or spec.get("type"),
        }
        if not model.exists():
            row["status"] = "FAIL"
            row["reason"] = "GLB yok"
            results.append(row)
            continue
        raw = glb_meshes(model)
        unit, parts = to_mm(raw)
        # overall bbox in source units then mm
        gmin = [min(m["min"][i] for m in raw) for i in range(3)]
        gmax = [max(m["max"][i] for m in raw) for i in range(3)]
        scale = 1000.0 if unit == "m" else 1.0
        span = [round((gmax[i] - gmin[i]) * scale, 1) for i in range(3)]
        # y-up meters: x width, y height, z depth. z-up mm: x width, y depth, z height
        if unit == "m":
            measured = {"w": span[0], "h": span[1], "d": span[2], "axis": "Y-up"}
        else:
            measured = {"w": span[0], "h": span[2], "d": span[1], "axis": "Z-up"}
        by_role = {}
        for part in parts:
            by_role.setdefault(role(part["name"]), []).append(part["thickness_mm"])
        # overlaps in mm space
        mm_boxes = []
        for mesh in raw:
            mm_boxes.append({
                "name": mesh["name"],
                "min": [v * scale for v in mesh["min"]],
                "max": [v * scale for v in mesh["max"]],
            })
        overlaps = []
        collisions = []
        for i in range(len(mm_boxes)):
            for j in range(i + 1, len(mm_boxes)):
                dims = overlap_mm(mm_boxes[i], mm_boxes[j])
                if min(dims) <= 3:
                    continue
                vol = dims[0] * dims[1] * dims[2]
                if vol < 20000:
                    continue
                pair = {
                    "a": mm_boxes[i]["name"],
                    "b": mm_boxes[j]["name"],
                    "overlap_mm": [round(v, 1) for v in dims],
                    "volume_cm3": round(vol / 1000, 1),
                }
                names = f"{pair['a']} {pair['b']}".lower()
                deep = sorted(dims)
                through_divider = "dikme" in names and ("cekmece" in names or "raf" in names) and deep[1] > 40
                overlaps.append(pair)
                if through_divider:
                    collisions.append(pair)
        cat_box = row["catalogMm"]
        delta = {
            "w": round(measured["w"] - cat_box["w"], 1) if cat_box["w"] else None,
            "h": round(measured["h"] - cat_box["h"], 1) if cat_box["h"] else None,
            "d": round(measured["d"] - cat_box["d"], 1) if cat_box["d"] else None,
        }
        side_t = by_role.get("side") or []
        shelf_t = by_role.get("shelf") or []
        back_t = by_role.get("back") or []
        issues = []
        if not row["inCatalogJson"]:
            issues.append("module.json katalog JSON'da yok")
        if not row["previewExists"]:
            issues.append("önizleme yok")
        for key in ("w", "h", "d"):
            if delta[key] is not None and abs(delta[key]) > 8:
                issues.append(f"ölçü sapması {key} {delta[key]} mm")
        if side_t and any(abs(t - 18) > 2 for t in side_t):
            issues.append(f"yan kalınlık {side_t}")
        if shelf_t and any(abs(t - 18) > 2 for t in shelf_t):
            issues.append(f"raf kalınlık {shelf_t}")
        if back_t:
            expect = row["declaredBackMm"] if row["declaredBackMm"] else (8 if min(back_t) <= 10 else 18)
            if any(abs(t - expect) > 2 for t in back_t):
                issues.append(f"arkalık {back_t} beklenen {expect}")
        if collisions:
            issues.append(f"iç çakışma {len(collisions)}")
        row["buttJointCount"] = len(overlaps) - len(collisions)
        row["collisionCount"] = len(collisions)
        row["collisions"] = collisions[:8]
        if "door" not in by_role:
            row["doorNote"] = "Kapak mesh'i GLB'de yok; stüdyo kapağı ayrıca üretir."
        row.update({
            "sourceUnit": unit,
            "measuredMm": measured,
            "deltaMm": delta,
            "partCount": len(parts),
            "thicknessByRole": {k: sorted(set(v)) for k, v in by_role.items()},
            "overlapCount": len(overlaps),
            "overlaps": overlaps[:6],
            "hasDoorMesh": "door" in by_role,
            "hasRail": "rail" in by_role,
            "hasDrawer": "drawer" in by_role,
            "issues": issues,
        })
        # status: dimension or missing glb is FAIL; overlap and missing door are recorded but door absence is expected
        hard = [i for i in issues if i.startswith("ölçü") or i.startswith("yan") or i.startswith("raf") or i.startswith("arkalık") or i.startswith("iç")]
        row["status"] = "FAIL" if hard else "PASS"
        results.append(row)
    missing_json = [mid for mid in catalog if mid.casefold() not in seen]
    summary = {
        "moduleJsonCount": len(modules),
        "catalogCount": len(catalog),
        "catalogMissingModuleJson": missing_json,
        "pass": sum(1 for r in results if r["status"] == "PASS"),
        "fail": sum(1 for r in results if r["status"] == "FAIL"),
        "withCollisions": sum(1 for r in results if r.get("collisionCount")),
        "withoutDoorMesh": sum(1 for r in results if not r.get("hasDoorMesh", True)),
        "withoutPreview": sum(1 for r in results if not r.get("previewExists")),
    }
    (OUT / "module-geometry.json").write_text(json.dumps({"summary": summary, "modules": results}, ensure_ascii=False, indent=2))
    print(json.dumps(summary, ensure_ascii=False, indent=2))

if __name__ == "__main__":
    main()
