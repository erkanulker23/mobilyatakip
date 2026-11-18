#!/usr/bin/env python3
"""Kaynak GLB'de yalnızca iki referans birleşimi düzeltir.

y26_cift_01: orta dikme çekmece bankosunun üstünden başlar. Askılık sol bölmede kalır.
kose_1: sol yan panel köşe kolonunu tek başına tutar. Arka ve ön paneller bu kolona bindirmez.
Diğer modüllere dokunmaz. İkinci kez çalışınca aynı koordinatı yeniden kesmez.
"""
import json
import struct
from pathlib import Path

ROOT = Path("/Users/erkanulker/mobilyatakip")


def load_glb(path: Path):
    data = path.read_bytes()
    if data[:4] != b"glTF":
        raise ValueError(path)
    length, ctype = struct.unpack_from("<II", data, 12)
    js = json.loads(data[20 : 20 + length])
    boff = 20 + length
    blen, btype = struct.unpack_from("<II", data, boff)
    blob = bytearray(data[boff + 8 : boff + 8 + blen])
    return js, blob


def save_glb(path: Path, js, blob: bytearray):
    js_bytes = json.dumps(js, separators=(",", ":")).encode()
    while len(js_bytes) % 4:
        js_bytes += b" "
    raw = bytes(blob)
    while len(raw) % 4:
        raw += b"\x00"
    total = 12 + 8 + len(js_bytes) + 8 + len(raw)
    out = struct.pack("<III", 0x46546C67, 2, total)
    out += struct.pack("<II", len(js_bytes), 0x4E4F534A) + js_bytes
    out += struct.pack("<II", len(raw), 0x004E4942) + raw
    path.write_bytes(out)


def mesh_positions(js, blob, mesh_index):
    prim = js["meshes"][mesh_index]["primitives"][0]
    acc_i = prim["attributes"]["POSITION"]
    acc = js["accessors"][acc_i]
    view = js["bufferViews"][acc["bufferView"]]
    stride = view.get("byteStride", 12)
    if stride != 12 or acc["componentType"] != 5126 or acc["type"] != "VEC3":
        raise ValueError(f"beklenmeyen konum tamponu {mesh_index}")
    start = view.get("byteOffset", 0) + acc.get("byteOffset", 0)
    return acc, start, acc["count"]


def read_verts(blob, start, count):
    verts = []
    for i in range(count):
        verts.append(list(struct.unpack_from("<fff", blob, start + i * 12)))
    return verts


def write_verts(blob, start, verts):
    for i, (x, y, z) in enumerate(verts):
        struct.pack_into("<fff", blob, start + i * 12, x, y, z)


def refresh_bounds(acc, verts):
    mn = [min(v[k] for v in verts) for k in range(3)]
    mx = [max(v[k] for v in verts) for k in range(3)]
    acc["min"] = mn
    acc["max"] = mx
    return mn, mx


def named_mesh(js, blob, name):
    for node in js["nodes"]:
        if node.get("name") == name and node.get("mesh") is not None:
            acc, start, count = mesh_positions(js, blob, node["mesh"])
            return acc, start, read_verts(blob, start, count)
    raise KeyError(name)


def lift_divider(js, blob):
    acc, start, verts = named_mesh(js, blob, "006_IC_DIKME")
    ys = {round(v[1], 3) for v in verts}
    if 0.118 not in ys:
        return "dikme zaten kesilmiş"
    for v in verts:
        if abs(v[1] - 0.118) < 0.0002:
            v[1] = 0.468
    write_verts(blob, start, verts)
    refresh_bounds(acc, verts)
    return "dikme altı 118 mm -> 468 mm"


def shorten_rail(js, blob):
    acc, start, verts = named_mesh(js, blob, "013_ASKILIK")
    max_x = max(v[0] for v in verts)
    if max_x <= 0.549:
        return "askılık zaten sol bölmede"
    min_x = min(v[0] for v in verts)
    new_max = 0.548
    span = max_x - min_x
    for v in verts:
        v[0] = min_x + (v[0] - min_x) * (new_max - min_x) / span
    write_verts(blob, start, verts)
    refresh_bounds(acc, verts)
    return f"askılık sağ uç {max_x * 1000:.1f} mm -> {new_max * 1000:.1f} mm"


def inset_face(js, blob, name, axis, old, new):
    acc, start, verts = named_mesh(js, blob, name)
    current = min(v[axis] for v in verts)
    if current >= new - 0.2:
        return f"{name} zaten {new}"
    moved = 0
    for v in verts:
        if abs(v[axis] - old) < 0.2:
            v[axis] = new
            moved += 1
    if not moved:
        raise RuntimeError(f"{name} içinde {old} bulunamadı")
    write_verts(blob, start, verts)
    refresh_bounds(acc, verts)
    return f"{name} eksen {axis} {old} -> {new} ({moved} köşe)"


def patch_cift_json():
    path = ROOT / "public/catalog/y26/y26_cift_01/module.json"
    spec = json.loads(path.read_text())
    for part in spec.get("parts", []):
        if part.get("name") == "IC_DIKME" and part.get("y_mm") == 118:
            part["y_mm"] = 468
            part["height_mm"] = 1650
    path.write_text(json.dumps(spec, ensure_ascii=False, indent=2) + "\n")


def patch_kose_json():
    path = ROOT / "public/catalog/meemare25/kose_1/module.json"
    spec = json.loads(path.read_text())
    for part in spec.get("components", []):
        if part.get("name") == "back_wall" and part["dimensions_mm"][0] == 900:
            part["dimensions_mm"][0] = 882
            part["center_mm"][0] = 459
        if part.get("name") == "front_left_end" and part["dimensions_mm"][0] == 450:
            part["dimensions_mm"][0] = 432
            part["center_mm"][0] = 234
    path.write_text(json.dumps(spec, ensure_ascii=False, indent=2) + "\n")


def main():
    notes = []
    cift = ROOT / "public/catalog/y26/y26_cift_01/model.glb"
    js, blob = load_glb(cift)
    notes.append(lift_divider(js, blob))
    notes.append(shorten_rail(js, blob))
    save_glb(cift, js, blob)
    patch_cift_json()
    for rel in (
        "public/catalog/meemare25/kose_1/model.glb",
        "public/catalog/meemare25-norm/kose_1.glb",
    ):
        path = ROOT / rel
        js, blob = load_glb(path)
        notes.append(rel + " " + inset_face(js, blob, "001_back_wall", 0, 0.0, 18.0))
        notes.append(rel + " " + inset_face(js, blob, "003_front_left_end", 0, 0.0, 18.0))
        save_glb(path, js, blob)
    patch_kose_json()
    print("\n".join(notes))


if __name__ == "__main__":
    main()
