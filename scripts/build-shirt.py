# Erkek gömleği: tek kumaş parçası askı çubuğunun üstünden cloth ile düşer.
# Omuz çizgisi 46 cm, en 54 cm, düşen boy yaklaşık 76 cm. Metre, Z yukarı.
import bpy
import math
import os
from mathutils import Vector

bpy.ops.wm.read_factory_settings(use_empty=True)
scene = bpy.context.scene
scene.unit_settings.system = 'METRIC'
scene.frame_start = 1
scene.frame_end = 40

def mat(name, color, rough, metal=0.0):
    item = bpy.data.materials.new(name)
    item.use_nodes = True
    bsdf = item.node_tree.nodes.get('Principled BSDF')
    bsdf.inputs['Base Color'].default_value = (*color, 1)
    bsdf.inputs['Roughness'].default_value = rough
    bsdf.inputs['Metallic'].default_value = metal
    return item

cotton = mat('Pamuk', (0.72, 0.78, 0.86), 0.86)
collar_mat = mat('Yaka', (0.68, 0.74, 0.82), 0.74)
button_mat = mat('Dugme', (0.93, 0.93, 0.92), 0.34)
wood = mat('Aski', (0.34, 0.20, 0.11), 0.5)
metal = mat('Kanca', (0.75, 0.76, 0.77), 0.22, metal=0.9)

def apply(obj):
    bpy.context.view_layer.objects.active = obj
    obj.select_set(True)
    bpy.ops.object.transform_apply(location=True, rotation=True, scale=True)
    obj.select_set(False)

bpy.ops.mesh.primitive_cylinder_add(vertices=12, radius=0.005, depth=0.46, location=(0, 0, 1.40))
bar = bpy.context.active_object
bar.rotation_euler = (0, math.pi / 2, 0)
bar.name = 'Aski'
apply(bar)
bar.data.materials.append(wood)
bpy.ops.mesh.primitive_cylinder_add(vertices=10, radius=0.008, depth=0.42, location=(0, 0, 1.40))
hit = bpy.context.active_object
hit.rotation_euler = (0, math.pi / 2, 0)
hit.name = 'AskiCarpisma'
apply(hit)
bpy.ops.object.modifier_add(type='COLLISION')
hit.collision.thickness_outer = 0.006
hit.hide_render = True
bpy.ops.mesh.primitive_torus_add(major_radius=0.018, minor_radius=0.0025, major_segments=18, minor_segments=8, location=(0, -0.012, 1.445))
hook = bpy.context.active_object
hook.rotation_euler = (math.pi / 2, 0, 0)
hook.name = 'Kanca'
apply(hook)
hook.data.materials.append(metal)

bpy.ops.mesh.primitive_grid_add(x_subdivisions=48, y_subdivisions=96, size=1, location=(0, 0, 1.46))
cloth_obj = bpy.context.active_object
cloth_obj.name = 'Gomlek'
cloth_obj.scale = (0.54, 1.52, 1)
apply(cloth_obj)
cloth_obj.data.materials.append(cotton)
for vert in cloth_obj.data.vertices:
    vert.co.z += 0.012 * math.sin(vert.co.x * 18.0) * math.sin(vert.co.y * 9.0)

pins = cloth_obj.vertex_groups.new(name='PIN')
indexes = [v.index for v in cloth_obj.data.vertices if abs(v.co.y) < 0.012 and 0.06 < abs(v.co.x) < 0.23]
pins.add(indexes, 1.0, 'REPLACE')
print('PIN', len(indexes))

def sleeve(name, x):
    bpy.ops.mesh.primitive_grid_add(x_subdivisions=16, y_subdivisions=40, size=1, location=(x, 0, 1.44))
    obj = bpy.context.active_object
    obj.name = name
    obj.scale = (0.18, 0.62, 1)
    apply(obj)
    obj.data.materials.append(cotton)
    group = obj.vertex_groups.new(name='PIN')
    held = [v.index for v in obj.data.vertices if abs(v.co.y) < 0.012]
    group.add(held, 1.0, 'REPLACE')
    mod = obj.modifiers.new('Cloth', 'CLOTH')
    mod.settings.quality = 5
    mod.settings.mass = 0.18
    mod.settings.tension_stiffness = 6
    mod.settings.compression_stiffness = 5
    mod.settings.shear_stiffness = 3
    mod.settings.bending_stiffness = 0.02
    mod.settings.pin_stiffness = 4
    mod.settings.vertex_group_mass = 'PIN'
    mod.collision_settings.use_collision = False
    mod.point_cache.frame_start = 1
    mod.point_cache.frame_end = scene.frame_end
    print('SLEEVE', name, len(held))
    return obj

sleeve_l = sleeve('KolSol', 0.34)
sleeve_r = sleeve('KolSag', -0.34)

modifier = cloth_obj.modifiers.new('Cloth', 'CLOTH')
settings = modifier.settings
settings.quality = 6
settings.mass = 0.22
settings.tension_stiffness = 6
settings.compression_stiffness = 5
settings.shear_stiffness = 3
settings.bending_stiffness = 0.015
settings.pin_stiffness = 4
settings.air_damping = 1
settings.vertex_group_mass = 'PIN'
modifier.collision_settings.use_collision = True
modifier.collision_settings.use_self_collision = False
modifier.collision_settings.distance_min = 0.004
modifier.point_cache.frame_start = 1
modifier.point_cache.frame_end = scene.frame_end

bpy.ops.ptcache.free_bake_all()
bpy.ops.ptcache.bake_all(bake=True)
scene.frame_set(scene.frame_end)
bpy.context.view_layer.objects.active = cloth_obj
cloth_obj.select_set(True)
bpy.ops.object.modifier_apply(modifier='Cloth')
for obj in (sleeve_l, sleeve_r):
    bpy.context.view_layer.objects.active = obj
    obj.select_set(True)
    bpy.ops.object.modifier_apply(modifier='Cloth')
    bpy.ops.object.shade_smooth()
    obj.select_set(False)
bpy.context.view_layer.objects.active = cloth_obj
cloth_obj.select_set(True)
bpy.ops.object.shade_smooth()

zs = [v.co.z for v in cloth_obj.data.vertices]
print('DRAPE', min(zs), max(zs))

bpy.ops.mesh.primitive_cylinder_add(vertices=28, radius=0.072, depth=0.035, location=(0, 0, 1.43), end_fill_type='NOTHING')
band = bpy.context.active_object
band.name = 'Yaka'
band.scale = (1.1, 0.62, 1)
apply(band)
band.data.materials.append(collar_mat)

for index, z in enumerate((1.28, 1.16, 1.04, 0.92, 0.80)):
    bpy.ops.mesh.primitive_uv_sphere_add(radius=0.006, location=(0.012, 0.02, z))
    button = bpy.context.active_object
    button.name = f'Dugme_{index}'
    apply(button)
    button.data.materials.append(button_mat)

bpy.data.objects.remove(hit, do_unlink=True)
for obj in scene.objects:
    obj.select_set(True)
bpy.context.view_layer.objects.active = cloth_obj
bpy.ops.object.join()

out = '/Users/erkanulker/mobilyatakip/public/models/wardrobe/library/gomlek.glb'
bpy.ops.export_scene.gltf(filepath=out, export_format='GLB', export_apply=True, export_yup=True, use_selection=False)

scene.render.engine = 'BLENDER_EEVEE'
scene.render.resolution_x = 800
scene.render.resolution_y = 1100
preview = '/Users/erkanulker/mobilyatakip/test-results/ultra-realism/stage-v3/shirt-sim-preview.png'
scene.render.filepath = preview
os.makedirs(os.path.dirname(preview), exist_ok=True)
world = bpy.data.worlds.new('Studio')
scene.world = world
world.use_nodes = True
world.node_tree.nodes['Background'].inputs[0].default_value = (0.86, 0.86, 0.84, 1)
world.node_tree.nodes['Background'].inputs[1].default_value = 0.6
bpy.ops.object.camera_add(location=(0.7, -1.35, 1.15))
cam = bpy.context.active_object
cam.rotation_euler = (Vector((0.0, 0.95, 1.05)) - cam.location).to_track_quat('-Z', 'Y').to_euler()
scene.camera = cam
bpy.ops.object.light_add(type='AREA', location=(0.5, -0.9, 1.7))
lamp = bpy.context.active_object
lamp.data.energy = 40
lamp.data.size = 0.8
bpy.ops.render.render(write_still=True)
print('SHIRT_OK', out)
