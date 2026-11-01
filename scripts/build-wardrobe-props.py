# Original Meemare studio models for the reference wardrobe.
# These are modeled garments and objects, not photogrammetry scans.
# Blender 5, meters, Z-up. glTF export converts to Y-up.

import bpy
import bmesh
import math
import os

OUT = "/Users/erkanulker/mobilyatakip/public/models/wardrobe/library"
os.makedirs(OUT, exist_ok=True)


def fresh():
    bpy.ops.wm.read_factory_settings(use_empty=True)


def material(name, color, rough=0.72, metal=0.0, sheen=0.35):
    mat = bpy.data.materials.new(name)
    mat.use_nodes = True
    bsdf = mat.node_tree.nodes.get("Principled BSDF")
    def put(keys, value):
        for key in keys:
            sock = bsdf.inputs.get(key)
            if sock is not None:
                sock.default_value = value
                return
    put(["Base Color"], (*color, 1))
    put(["Roughness"], rough)
    put(["Metallic"], metal)
    put(["Sheen Weight", "Sheen"], sheen)
    return mat


def link(name, bm, mat):
    me = bpy.data.meshes.new(name)
    bm.normal_update()
    bm.to_mesh(me)
    bm.free()
    for poly in me.polygons:
        poly.use_smooth = True
    ob = bpy.data.objects.new(name, me)
    bpy.context.collection.objects.link(ob)
    ob.data.materials.append(mat)
    return ob


def sheet(name, w, h, nx, ny, z_at, mat, thickness=0.004):
    bm = bmesh.new()
    grid = []
    for iy in range(ny + 1):
        row = []
        v = iy / ny
        for ix in range(nx + 1):
            u = ix / nx
            x = (u - 0.5) * w
            z = v * h
            y = z_at(u, v)
            row.append(bm.verts.new((x, y, z)))
        grid.append(row)
    bm.verts.ensure_lookup_table()
    for iy in range(ny):
        for ix in range(nx):
            bm.faces.new((grid[iy][ix], grid[iy][ix + 1], grid[iy + 1][ix + 1], grid[iy + 1][ix]))
    ob = link(name, bm, mat)
    solid = ob.modifiers.new("solid", "SOLIDIFY")
    solid.thickness = thickness
    solid.offset = 0
    return ob


def export(filename):
    for obj in bpy.context.scene.objects:
        obj.select_set(True)
    bpy.ops.export_scene.gltf(
        filepath=os.path.join(OUT, filename),
        export_format="GLB",
        use_selection=True,
        export_apply=True,
        export_yup=True,
    )


def shirt():
    fresh()
    cloth = material("pamuk", (0.86, 0.84, 0.78), 0.78, 0, 0.45)
    wood = material("askı", (0.45, 0.28, 0.16), 0.5, 0, 0)
    metal = material("kanca", (0.72, 0.74, 0.76), 0.28, 0.85, 0)

    def folds(u, v):
        side = abs(u - 0.5) * 2
        hem = math.sin((u * 2 - 1) * math.pi * 3) * 0.012 * (0.25 + v)
        drape = math.sin(v * math.pi * 2.2) * 0.008
        pinch = (side ** 2) * 0.01
        return hem + drape + pinch

    sheet("govde", 0.46, 0.72, 18, 28, folds, cloth, 0.003)
    # sleeves hang from the shoulder line
    for sign in (-1, 1):
        bm = bmesh.new()
        steps, rings = 16, 10
        rings_v = []
        for i in range(steps):
            t = i / (steps - 1)
            cx = sign * (0.2 + math.sin(t * 1.2) * 0.11)
            cz = 0.62 - t * 0.28
            cy = 0.02 + math.sin(t * math.pi) * 0.03
            radius = 0.055 * (1 - t * 0.25)
            ring = []
            for k in range(rings):
                a = k / rings * math.pi * 2
                fold = math.sin(a * 3 + t * 6) * 0.004
                ring.append(bm.verts.new((
                    cx + math.cos(a) * (radius + fold),
                    cy + math.sin(a) * (radius * 0.72 + fold),
                    cz,
                )))
            rings_v.append(ring)
            if i:
                prev, nxt = rings_v[-2], rings_v[-1]
                for k in range(rings):
                    n = (k + 1) % rings
                    bm.faces.new((prev[k], prev[n], nxt[n], nxt[k]))
        link(f"kol_{sign}", bm, cloth)
    bm = bmesh.new()
    bmesh.ops.create_cone(bm, cap_ends=True, segments=12, radius1=0.008, radius2=0.008, depth=0.42)
    ob = link("cubuk", bm, wood)
    ob.rotation_euler[1] = math.pi / 2
    ob.location = (0, 0, 0.74)
    bm = bmesh.new()
    bmesh.ops.create_uvsphere(bm, u_segments=12, v_segments=8, radius=0.012)
    hook = link("kanca", bm, metal)
    hook.location = (0, -0.02, 0.8)
    export("gomlek.glb")


def blazer():
    fresh()
    cloth = material("yün", (0.16, 0.17, 0.2), 0.7, 0, 0.25)
    wood = material("askı", (0.4, 0.25, 0.14), 0.48, 0, 0)

    def folds(u, v):
        open_front = math.exp(-((u - 0.5) ** 2) / 0.02) * -0.02 * v
        return math.sin(u * math.pi * 5) * 0.01 * v + open_front

    sheet("on", 0.5, 0.78, 16, 24, folds, cloth, 0.004)
    for sign in (-1, 1):
        bm = bmesh.new()
        rings_v = []
        rings = 10
        for i in range(14):
            t = i / 13
            cx = sign * (0.22 + t * 0.08)
            cz = 0.66 - t * 0.42
            radius = 0.07 * (1 - t * 0.35)
            ring = []
            for k in range(rings):
                a = k / rings * math.pi * 2
                ring.append(bm.verts.new((
                    cx + math.cos(a) * radius,
                    0.03 + math.sin(a) * radius * 0.65 + math.sin(t * 8) * 0.004,
                    cz,
                )))
            rings_v.append(ring)
            if i:
                a, b = rings_v[-2], rings_v[-1]
                for k in range(rings):
                    n = (k + 1) % rings
                    bm.faces.new((a[k], a[n], b[n], b[k]))
        link(f"kol_{sign}", bm, cloth)
    bm = bmesh.new()
    bmesh.ops.create_cone(bm, cap_ends=True, segments=10, radius1=0.008, radius2=0.008, depth=0.46)
    bar = link("cubuk", bm, wood)
    bar.rotation_euler[1] = math.pi / 2
    bar.location = (0, 0, 0.8)
    export("ceket.glb")


def tee():
    fresh()
    colors = [(0.82, 0.8, 0.74), (0.55, 0.58, 0.62), (0.75, 0.7, 0.62)]
    for layer, color in enumerate(colors):
        cloth = material(f"kat_{layer}", color, 0.8, 0, 0.4)

        def folds(u, v, layer=layer):
            edge = max(abs(u - 0.5) * 2, abs(v - 0.5) * 2)
            roll = max(0, edge - 0.72) * 0.02
            return roll + math.sin(u * 9 + layer) * 0.003 + math.sin(v * 7) * 0.003

        ob = sheet(f"kat_{layer}", 0.28, 0.22, 14, 10, folds, cloth, 0.008)
        ob.location.z = layer * 0.012
    export("tisort.glb")


def shoes():
    fresh()
    upper_m = material("deri", (0.93, 0.93, 0.94), 0.45, 0, 0.05)
    sole_m = material("taban", (0.82, 0.82, 0.8), 0.7, 0, 0)
    for index, x in enumerate((-0.05, 0.05)):
        bm = bmesh.new()
        bmesh.ops.create_cube(bm, size=1)
        ob = link(f"taban_{index}", bm, sole_m)
        ob.scale = (0.1, 0.28, 0.025)
        ob.location = (x, 0, 0.012)
        bm = bmesh.new()
        bmesh.ops.create_uvsphere(bm, u_segments=16, v_segments=10, radius=1)
        body = link(f"saye_{index}", bm, upper_m)
        body.scale = (0.09, 0.13, 0.045)
        body.location = (x, -0.02, 0.05)
        bpy.context.view_layer.objects.active = body
        bevel = body.modifiers.new("alt", "DISPLACE") if False else None
    export("ayakkabi.glb")


def bag():
    fresh()
    leather = material("canta", (0.28, 0.16, 0.1), 0.42, 0, 0.08)
    metal = material("halka", (0.75, 0.72, 0.66), 0.3, 0.8, 0)
    bm = bmesh.new()
    bmesh.ops.create_cube(bm, size=1)
    body = link("govde", bm, leather)
    body.scale = (0.32, 0.12, 0.24)
    body.location = (0, 0, 0.12)
    bev = body.modifiers.new("pah", "BEVEL")
    bev.width = 0.012
    bev.segments = 2
    bm = bmesh.new()
    bmesh.ops.create_cone(bm, cap_ends=True, segments=8, radius1=0.006, radius2=0.006, depth=0.28)
    handle = link("sap", bm, metal)
    handle.rotation_euler[1] = math.pi / 2
    handle.location = (0, 0, 0.28)
    export("canta.glb")


def box():
    fresh()
    board = material("kutu", (0.9, 0.88, 0.82), 0.62, 0, 0)
    bm = bmesh.new()
    bmesh.ops.create_cube(bm, size=1)
    body = link("kutu", bm, board)
    body.scale = (0.3, 0.22, 0.12)
    body.location = (0, 0, 0.06)
    bm = bmesh.new()
    bmesh.ops.create_cube(bm, size=1)
    lid = link("kapak", bm, board)
    lid.scale = (0.31, 0.23, 0.012)
    lid.location = (0, 0, 0.128)
    export("kutu.glb")


shirt()
blazer()
tee()
shoes()
bag()
box()
print("props written", OUT)
