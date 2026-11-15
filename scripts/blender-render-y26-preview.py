import argparse
import math
import os
import sys

import bpy
from mathutils import Vector


def clear_scene():
    bpy.ops.wm.read_factory_settings(use_empty=True)


def import_glb(path: str):
    bpy.ops.import_scene.gltf(filepath=path)


def studio_material():
    mat = bpy.data.materials.new("Oak_Catalog")
    mat.use_nodes = True
    nt = mat.node_tree
    bsdf = nt.nodes.get("Principled BSDF")
    bsdf.inputs["Base Color"].default_value = (0.55, 0.38, 0.26, 1.0)
    bsdf.inputs["Roughness"].default_value = 0.42
    for obj in bpy.data.objects:
        if obj.type == "MESH":
            if obj.data.materials:
                obj.data.materials[0] = mat
            else:
                obj.data.materials.append(mat)


def frame_scene(w: float, h: float, d: float):
    mins = Vector((1e9, 1e9, 1e9))
    maxs = Vector((-1e9, -1e9, -1e9))
    for obj in bpy.data.objects:
        if obj.type != "MESH":
            continue
        for corner in obj.bound_box:
            p = obj.matrix_world @ Vector(corner)
            mins = Vector((min(mins.x, p.x), min(mins.y, p.y), min(mins.z, p.z)))
            maxs = Vector((max(maxs.x, p.x), max(maxs.y, p.y), max(maxs.z, p.z)))
    center = (mins + maxs) / 2
    for obj in bpy.data.objects:
        if obj.type == "MESH":
            obj.location -= Vector((center.x - mins.x, mins.y, center.z - maxs.z))


def render_preview(out: str, w: float, h: float, d: float):
    scene = bpy.context.scene
    scene.render.engine = "CYCLES"
    scene.cycles.samples = 96
    scene.cycles.use_denoising = True
    scene.render.resolution_x = 1024
    scene.render.resolution_y = 1280
    scene.render.film_transparent = False

    world = bpy.data.worlds.new("StudioWorld")
    scene.world = world
    world.use_nodes = True
    bg = world.node_tree.nodes.get("Background")
    bg.inputs[0].default_value = (0.93, 0.9, 0.86, 1)
    bg.inputs[1].default_value = 1.1

    studio_material()

    cam_data = bpy.data.cameras.new("Cam")
    cam = bpy.data.objects.new("Cam", cam_data)
    bpy.context.scene.collection.objects.link(cam)
    scene.camera = cam
    cam.data.lens = 50
    cam.location = Vector((w * 0.15, h * 0.42, d * 2.8 + w * 0.6))
    cam.rotation_euler = (math.radians(72), 0, math.radians(8))

    key = bpy.data.lights.new("Key", type="AREA")
    key.energy = 280
    key.size = max(w, d)
    key_obj = bpy.data.objects.new("Key", key)
    bpy.context.scene.collection.objects.link(key_obj)
    key_obj.location = (w * 0.5, h * 1.1, d * 1.2)
    key_obj.rotation_euler = (math.radians(55), math.radians(15), 0)

    fill = bpy.data.lights.new("Fill", type="AREA")
    fill.energy = 120
    fill.size = h * 0.6
    fill_obj = bpy.data.objects.new("Fill", fill)
    bpy.context.scene.collection.objects.link(fill_obj)
    fill_obj.location = (-w, h * 0.5, d * 0.8)

    os.makedirs(os.path.dirname(out), exist_ok=True)
    scene.render.filepath = out
    bpy.ops.render.render(write_still=True)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--glb", required=True)
    parser.add_argument("--out", required=True)
    parser.add_argument("--width", type=float, default=0.45)
    parser.add_argument("--height", type=float, default=2.5)
    parser.add_argument("--depth", type=float, default=0.6)
    argv = sys.argv
    if "--" in argv:
        argv = argv[argv.index("--") + 1 :]
    args = parser.parse_args(argv)
    clear_scene()
    import_glb(args.glb)
    frame_scene(args.width, args.height, args.depth)
    render_preview(args.out, args.width, args.height, args.depth)
    print(args.out)


if __name__ == "__main__":
    main()