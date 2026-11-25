#!/usr/bin/env python3
import bpy
import mathutils
from pathlib import Path

root = Path("/Users/erkanulker/mobilyatakip/public/catalog/showroom-modules")
for folder in sorted(root.glob("showroom_*")):
    glb = folder / "model.glb"
    if not glb.exists():
        continue
    bpy.ops.wm.read_factory_settings(use_empty=True)
    bpy.ops.import_scene.gltf(filepath=str(glb))
    bpy.ops.object.light_add(type="AREA", location=(1.2, -1.8, 2.2))
    light = bpy.context.active_object
    light.data.energy = 350
    light.data.size = 2.5
    bpy.ops.object.camera_add()
    cam = bpy.context.active_object
    bpy.context.scene.camera = cam
    mins = mathutils.Vector((1e9, 1e9, 1e9))
    maxs = mathutils.Vector((-1e9, -1e9, -1e9))
    for obj in bpy.context.scene.objects:
        if obj.type != "MESH":
            continue
        for corner in obj.bound_box:
            world = obj.matrix_world @ mathutils.Vector(corner)
            mins = mathutils.Vector((min(mins.x, world.x), min(mins.y, world.y), min(mins.z, world.z)))
            maxs = mathutils.Vector((max(maxs.x, world.x), max(maxs.y, world.y), max(maxs.z, world.z)))
    center = (mins + maxs) / 2
    size = max((maxs - mins).length, 0.5)
    cam.location = center + mathutils.Vector((size * 0.55, -size * 0.95, size * 0.32))
    direction = center - cam.location
    cam.rotation_euler = direction.to_track_quat("-Z", "Y").to_euler()
    scene = bpy.context.scene
    scene.render.engine = "BLENDER_EEVEE"
    scene.render.resolution_x = 512
    scene.render.resolution_y = 640
    scene.render.film_transparent = True
    scene.render.filepath = str(folder / "preview.png")
    scene.render.image_settings.file_format = "PNG"
    bpy.ops.render.render(write_still=True)
    print("preview", folder.name)
print("done")
