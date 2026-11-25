#!/usr/bin/env python3
"""Split 3DZIP showroom wall GLB (mm units) into bay modules for wardrobe-studio."""
from __future__ import annotations

import bpy
import json
from pathlib import Path

SRC = Path("/Users/erkanulker/mobilyatakip/storage/import/3dzip-dolap-modulleri/exports/dolap-modulleri.glb")
OUT = Path("/Users/erkanulker/mobilyatakip/public/catalog/showroom-modules")
DATA = Path("/Users/erkanulker/mobilyatakip/public/data/wardrobe-showroom-modules.json")
GAP_MM = 80.0  # split when X gap exceeds this
MIN_WIDTH_M = 0.28
MAX_MODULES = 28

OUT.mkdir(parents=True, exist_ok=True)
bpy.ops.wm.read_factory_settings(use_empty=True)
bpy.ops.import_scene.gltf(filepath=str(SRC))


def mesh_aabb(obj):
    corners = [obj.matrix_world @ v.co for v in obj.data.vertices]
    if not corners:
        return None
    xs = [c.x for c in corners]
    ys = [c.y for c in corners]
    zs = [c.z for c in corners]
    return min(xs), min(ys), min(zs), max(xs), max(ys), max(zs)


meshes = []
for obj in bpy.context.scene.objects:
    if obj.type != "MESH" or not obj.data or len(obj.data.vertices) < 3:
        continue
    bb = mesh_aabb(obj)
    if not bb:
        continue
    minx, miny, minz, maxx, maxy, maxz = bb
    meshes.append(
        {
            "obj": obj,
            "minx": minx,
            "maxx": maxx,
            "miny": miny,
            "maxy": maxy,
            "minz": minz,
            "maxz": maxz,
            "cx": (minx + maxx) * 0.5,
        }
    )

meshes.sort(key=lambda m: m["cx"])
print("meshes", len(meshes))

clusters: list[list[dict]] = []
cur: list[dict] = []
cur_maxx = None
for m in meshes:
    if not cur:
        cur = [m]
        cur_maxx = m["maxx"]
        continue
    if m["minx"] - cur_maxx > GAP_MM:
        clusters.append(cur)
        cur = [m]
        cur_maxx = m["maxx"]
    else:
        cur.append(m)
        cur_maxx = max(cur_maxx, m["maxx"])
if cur:
    clusters.append(cur)

print("raw clusters", len(clusters))

entries = []
kept = 0
for cluster in clusters:
    minx = min(m["minx"] for m in cluster)
    maxx = max(m["maxx"] for m in cluster)
    miny = min(m["miny"] for m in cluster)
    maxy = max(m["maxy"] for m in cluster)
    minz = min(m["minz"] for m in cluster)
    maxz = max(m["maxz"] for m in cluster)
    width_m = (maxx - minx) / 1000.0
    depth_m = (maxy - miny) / 1000.0
    height_m = (maxz - minz) / 1000.0
    if width_m < MIN_WIDTH_M or height_m < 0.6 or depth_m < 0.12:
        continue
    if width_m > 2.4:  # skip oversized merged bays
        # re-split oversized by equal chunks ~0.9m
        continue

    kept += 1
    mod_id = f"showroom_{kept:02d}"
    folder = OUT / mod_id
    folder.mkdir(parents=True, exist_ok=True)

    bpy.ops.object.select_all(action="DESELECT")
    for m in cluster:
        m["obj"].hide_set(False)
        m["obj"].select_set(True)
    bpy.ops.object.duplicate()
    dupes = [o for o in bpy.context.selected_objects if o.type == "MESH"]

    bpy.ops.object.empty_add(type="PLAIN_AXES", location=(0.0, 0.0, 0.0))
    empty = bpy.context.active_object
    empty.name = mod_id

    # Parent dupes keeping world transform, then scale empty 0.001 (mm->m)
    for d in dupes:
        mw = d.matrix_world.copy()
        d.parent = empty
        d.matrix_world = mw

    empty.scale = (0.001, 0.001, 0.001)
    bpy.context.view_layer.update()

    # Recenter on floor / XY center after scale
    # compute bbox in world
    corners = []
    for d in empty.children_recursive:
        if d.type != "MESH" or not d.data:
            continue
        corners.extend(d.matrix_world @ v.co for v in d.data.vertices)
    if corners:
        xs = [c.x for c in corners]
        ys = [c.y for c in corners]
        zs = [c.z for c in corners]
        empty.location.x -= (min(xs) + max(xs)) * 0.5
        empty.location.y -= (min(ys) + max(ys)) * 0.5
        empty.location.z -= min(zs)
        bpy.context.view_layer.update()
        corners = []
        for d in empty.children_recursive:
            if d.type != "MESH" or not d.data:
                continue
            corners.extend(d.matrix_world @ v.co for v in d.data.vertices)
        xs = [c.x for c in corners]
        ys = [c.y for c in corners]
        zs = [c.z for c in corners]
        width_m = max(xs) - min(xs)
        depth_m = max(ys) - min(ys)
        height_m = max(zs) - min(zs)

    out_glb = folder / "model.glb"
    bpy.ops.object.select_all(action="DESELECT")
    empty.select_set(True)
    for c in empty.children_recursive:
        c.select_set(True)
    bpy.ops.export_scene.gltf(
        filepath=str(out_glb),
        export_format="GLB",
        use_selection=True,
        export_apply=True,
        export_yup=True,
    )

    # delete empties+dupes
    bpy.ops.object.delete()

    entries.append(
        {
            "id": mod_id,
            "name": f"Showroom Modül {kept:02d}",
            "category": "showroom",
            "modelPath": f"/catalog/showroom-modules/{mod_id}/model.glb",
            "previewPath": f"/catalog/showroom-modules/{mod_id}/preview.png",
            "width": round(float(width_m), 3),
            "height": round(float(height_m), 3),
            "depth": round(float(depth_m), 3),
            "geometrySource": "3dzip-showroom",
            "sourceFile": "dolap-modulleri.max",
        }
    )
    print(f"OK {mod_id} {width_m:.3f}x{height_m:.3f}x{depth_m:.3f}m  meshes={len(cluster)}  bytes={out_glb.stat().st_size}")
    if kept >= MAX_MODULES:
        break

# Also keep full wall scaled for reference
full_path = OUT / "_full_wall.glb"
bpy.ops.object.select_all(action="SELECT")
# scale root
roots = [o for o in bpy.context.scene.objects if o.parent is None]
for r in roots:
    r.scale = (0.001, 0.001, 0.001)
bpy.context.view_layer.update()
bpy.ops.export_scene.gltf(filepath=str(full_path), export_format="GLB", export_apply=True, export_yup=True)
print("FULL", full_path, full_path.stat().st_size)

DATA.write_text(
    json.dumps({"version": 1, "source": "3dzip-dolap-modulleri", "modules": entries}, ensure_ascii=False, indent=2),
    encoding="utf-8",
)
print("WROTE", DATA, "modules", len(entries))
