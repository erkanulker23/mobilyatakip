# Viessmann vm_v4 OBJ parçasını Y26 stüdyo kutusuna oturtur ve GLB + isteğe bağlı önizleme üretir.
import argparse
import json
import math
import os
import sys

import bpy
from mathutils import Vector


def clear_scene():
    bpy.ops.wm.read_factory_settings(use_empty=True)
    scene = bpy.context.scene
    scene.unit_settings.system = "METRIC"
    scene.unit_settings.scale_length = 1.0


def import_obj(path: str):
    # Kaynak Max koordinatları milimetre; Blender metreye çevir.
    bpy.ops.wm.obj_import(
        filepath=path,
        forward_axis="NEGATIVE_Z",
        up_axis="Y",
        global_scale=0.001,
    )


def mesh_bounds():
    mins = Vector((1e18, 1e18, 1e18))
    maxs = Vector((-1e18, -1e18, -1e18))
    found = False
    for obj in bpy.data.objects:
        if obj.type != "MESH":
            continue
        found = True
        for corner in obj.bound_box:
            world = obj.matrix_world @ Vector(corner)
            mins.x = min(mins.x, world.x)
            mins.y = min(mins.y, world.y)
            mins.z = min(mins.z, world.z)
            maxs.x = max(maxs.x, world.x)
            maxs.y = max(maxs.y, world.y)
            maxs.z = max(maxs.z, world.z)
    if not found:
        raise RuntimeError("No mesh in OBJ import")
    size = maxs - mins
    return mins, maxs, size


def fit_to_target(target_w: float, target_h: float, target_d: float):
    mins, maxs, size = mesh_bounds()
    sx = target_w / size.x if size.x > 1e-6 else 1.0
    sy = target_h / size.y if size.y > 1e-6 else 1.0
    sz = target_d / size.z if size.z > 1e-6 else 1.0
    root = bpy.data.objects.new("Y26_ROOT", None)
    bpy.context.scene.collection.objects.link(root)
    for obj in list(bpy.data.objects):
        if obj.type != "MESH":
            continue
        obj.parent = root
    root.location = Vector((0.0, 0.0, 0.0))
    root.scale = Vector((sx, sy, sz))
    bpy.context.view_layer.update()
    mins, maxs, size = mesh_bounds()
    shift = Vector((-mins.x - size.x * 0.5, -mins.y, -mins.z - size.z * 0.5))
    root.location += shift
    bpy.context.view_layer.update()
    return size, Vector((sx, sy, sz))


def export_glb(path: str):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    bpy.ops.export_scene.gltf(
        filepath=path,
        export_format="GLB",
        export_yup=True,
        export_apply=True,
        use_selection=False,
    )


def wood_material():
    mat = bpy.data.materials.new("MDF_Walnut")
    mat.use_nodes = True
    bsdf = mat.node_tree.nodes.get("Principled BSDF")
    bsdf.inputs["Base Color"].default_value = (0.42, 0.28, 0.18, 1.0)
    bsdf.inputs["Roughness"].default_value = 0.55
    for obj in bpy.data.objects:
        if obj.type == "MESH" and not obj.data.materials:
            obj.data.materials.append(mat)


def render_preview(path: str, target_w: float, target_h: float, target_d: float):
    scene = bpy.context.scene
    scene.render.engine = "BLENDER_EEVEE"
    scene.render.resolution_x = 1024
    scene.render.resolution_y = 1024
    scene.render.film_transparent = False
    world = scene.world or bpy.data.worlds.new("World")
    scene.world = world
    world.use_nodes = True
    bg = world.node_tree.nodes.get("Background")
    bg.inputs[0].default_value = (0.94, 0.91, 0.87, 1.0)
    bg.inputs[1].default_value = 0.85

    wood_material()
    cam_data = bpy.data.cameras.new("Cam")
    cam = bpy.data.objects.new("Cam", cam_data)
    bpy.context.scene.collection.objects.link(cam)
    scene.camera = cam
    dist = max(target_w, target_h, target_d) * 2.2
    cam.location = Vector((0, target_h * 0.45, dist))
    cam.data.lens = 48
    direction = Vector((0, target_h * 0.45, 0)) - cam.location
    cam.rotation_euler = direction.to_track_quat("-Z", "Y").to_euler()

    key = bpy.data.lights.new("Key", "AREA")
    key.energy = 450
    key_obj = bpy.data.objects.new("Key", key)
    bpy.context.scene.collection.objects.link(key_obj)
    key_obj.location = (target_w, target_h * 1.2, target_d * 1.5)
    key_obj.rotation_euler = (math.radians(50), 0, math.radians(25))

    os.makedirs(os.path.dirname(path), exist_ok=True)
    scene.render.filepath = path
    bpy.ops.render.render(write_still=True)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--obj", required=True)
    parser.add_argument("--out-glb", required=True)
    parser.add_argument("--out-preview", default="")
    parser.add_argument("--width", type=float, default=0.45)
    parser.add_argument("--height", type=float, default=2.5)
    parser.add_argument("--depth", type=float, default=0.6)
    parser.add_argument("--meta-out", default="")
    argv = sys.argv
    if "--" in argv:
        argv = argv[argv.index("--") + 1 :]
    else:
        argv = []
    args = parser.parse_args(argv)

    clear_scene()
    import_obj(args.obj)
    size_before, scale = fit_to_target(args.width, args.height, args.depth)
    export_glb(args.out_glb)
    if args.out_preview:
        render_preview(args.out_preview, args.width, args.height, args.depth)
    if args.meta_out:
        meta = {
            "sourceObj": os.path.basename(args.obj),
            "sizeBeforeFit": [round(size_before.x, 4), round(size_before.y, 4), round(size_before.z, 4)],
            "scaleApplied": [round(scale.x, 6), round(scale.y, 6), round(scale.z, 6)],
            "targetM": [args.width, args.height, args.depth],
            "glb": args.out_glb,
        }
        with open(args.meta_out, "w", encoding="utf-8") as fh:
            json.dump(meta, fh, indent=2)
    print(json.dumps({"ok": True, "glb": args.out_glb}))


if __name__ == "__main__":
    main()