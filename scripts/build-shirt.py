# Erkek gömleği, gerçek ölçü, Blender cloth. Metre, Z yukarı. Export glTF Y-up.
# Ölçüler: omuz 46 cm, düz genişlik 56 cm, boy 76 cm, kol 62 cm, yaka 4 cm.
import bpy
import bmesh
import math
from mathutils import Vector

bpy.ops.wm.read_factory_settings(use_empty=True)
scene = bpy.context.scene
scene.unit_settings.system = 'METRIC'
scene.frame_start = 1
scene.frame_end = 26
scene.render.resolution_x = 900
scene.render.resolution_y = 1200
scene.render.engine = 'BLENDER_EEVEE'

def mat(name, color, rough, metal=0.0, sheen=0.0):
    m = bpy.data.materials.new(name)
    m.use_nodes = True
    bsdf = m.node_tree.nodes.get('Principled BSDF')
    bsdf.inputs['Base Color'].default_value = (*color, 1)
    bsdf.inputs['Roughness'].default_value = rough
    bsdf.inputs['Metallic'].default_value = metal
    if 'Sheen Weight' in bsdf.inputs:
        bsdf.inputs['Sheen Weight'].default_value = sheen
    return m

cotton = mat('Pamuk', (0.90, 0.88, 0.83), 0.78, sheen=0.35)
collar_mat = mat('Yaka', (0.86, 0.84, 0.79), 0.7, sheen=0.2)
button_mat = mat('Dugme', (0.95, 0.94, 0.92), 0.32)
wood = mat('Askı', (0.42, 0.28, 0.16), 0.55)
metal = mat('Kanca', (0.72, 0.73, 0.75), 0.28, metal=0.85)

def link(obj):
    bpy.context.scene.collection.objects.link(obj)
    return obj

def tube(name, rings):
    mesh = bpy.data.meshes.new(name)
    obj = bpy.data.objects.new(name, mesh)
    link(obj)
    bm = bmesh.new()
    built = []
    for ring in rings:
        built.append([bm.verts.new(p) for p in ring])
    bm.verts.ensure_lookup_table()
    segs = len(built[0])
    for a, b in zip(built, built[1:]):
        for i in range(segs):
            j = (i + 1) % segs
            try:
                bm.faces.new((a[i], a[j], b[j], b[i]))
            except ValueError:
                pass
    bmesh.ops.recalc_face_normals(bm, faces=bm.faces)
    bm.to_mesh(mesh)
    bm.free()
    mesh.update()
    return obj

def ring(origin, axis, radius, oval, segments, twist=0.0):
    axis = axis.normalized()
    helper = Vector((0, 0, 1)) if abs(axis.z) < 0.9 else Vector((1, 0, 0))
    side = axis.cross(helper).normalized()
    up = side.cross(axis).normalized()
    points = []
    for i in range(segments):
        ang = math.tau * i / segments + twist
        points.append(origin + side * (math.cos(ang) * radius) + up * (math.sin(ang) * radius * oval))
    return points

def body_rings():
    segs = 36
    steps = 32
    rings = []
    for s in range(steps + 1):
        t = s / steps
        z = 0.66 + 0.74 * t
        width = 0.28 - 0.04 * abs(t - 0.62)
        depth = 0.15 - 0.03 * (1 - t)
        if t > 0.92:
            neck = (t - 0.92) / 0.08
            width = width * (1 - 0.42 * neck) 
            depth = depth * (1 - 0.25 * neck)
        rings.append(ring(Vector((0, 0, z)), Vector((0, 0, 1)), width, depth / width, segs))
    return rings

def sleeve_rings(sign):
    segs = 22
    steps = 24
    shoulder = Vector((sign * 0.22, 0.03, 1.30))
    cuff = Vector((sign * 0.30, 0.16, 0.72))
    rings = []
    for s in range(steps + 1):
        t = s / steps
        origin = shoulder.lerp(cuff, t)
        radius = 0.085 * (1 - t) + 0.052 * t
        rings.append(ring(origin, (cuff - shoulder), radius, 0.82, segs))
    return rings

def pin_group(obj, predicate):
    group = obj.vertex_groups.new(name='PIN')
    mesh = obj.data
    indexes = [v.index for v in mesh.vertices if predicate(v.co)]
    if indexes:
        group.add(indexes, 1.0, 'REPLACE')
    return group

def add_cloth(obj, pin_name):
    cloth = obj.modifiers.new('Cloth', 'CLOTH')
    s = cloth.settings
    s.quality = 6
    s.mass = 0.25
    s.tension_stiffness = 18
    s.compression_stiffness = 16
    s.shear_stiffness = 8
    s.bending_stiffness = 0.4
    s.pin_stiffness = 1
    s.air_damping = 1
    s.vertex_group_mass = pin_name
    c = cloth.collision_settings
    c.use_collision = True
    c.use_self_collision = False
    c.distance_min = 0.004
    c.collision_quality = 4
    cache = cloth.point_cache
    cache.frame_start = 1
    cache.frame_end = scene.frame_end
    return cloth

def collision_body():
    bpy.ops.mesh.primitive_uv_sphere_add(segments=24, ring_count=16, radius=1, location=(0, 0, 1.12))
    torso = bpy.context.active_object
    torso.name = 'Manken'
    torso.scale = (0.16, 0.10, 0.28)
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
    bpy.ops.object.modifier_add(type='COLLISION')
    torso.collision.thickness_outer = 0.006
    torso.collision.cloth_friction = 0.35
    torso.hide_render = True
    return torso

collision_body()
shirt = tube('Gomlek', body_rings())
shirt.data.materials.append(cotton)
pin_group(shirt, lambda co: co.z > 1.34)
add_cloth(shirt, 'PIN')
left = tube('KolSol', sleeve_rings(1))
right = tube('KolSag', sleeve_rings(-1))
for sleeve, sign in ((left, 1), (right, -1)):
    sleeve.data.materials.append(cotton)
    pin_group(sleeve, lambda co, sign=sign: (co - Vector((sign * 0.22, 0.03, 1.30))).length < 0.09)
    add_cloth(sleeve, 'PIN')

bpy.ops.ptcache.free_bake_all()
bpy.ops.ptcache.bake_all(bake=True)
scene.frame_set(scene.frame_end)

for obj in (shirt, left, right):
    bpy.context.view_layer.objects.active = obj
    obj.select_set(True)
    bpy.ops.object.modifier_apply(modifier='Cloth')
    obj.select_set(False)

# Yaka, manşet, düğme, askı. Simden sonra sabit parçalar.
def collar():
    bpy.ops.mesh.primitive_cylinder_add(vertices=28, radius=0.085, depth=0.04, location=(0, 0.01, 1.40), end_fill_type='NOTHING')
    obj = bpy.context.active_object
    obj.name = 'Yaka'
    obj.scale = (1, 0.78, 1)
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
    obj.data.materials.append(collar_mat)
    return obj

collar()

def buttons():
    for i, z in enumerate((1.22, 1.12, 1.02, 0.92, 0.82, 0.74)):
        bpy.ops.mesh.primitive_cylinder_add(vertices=12, radius=0.006, depth=0.002, location=(0, 0.155, z))
        obj = bpy.context.active_object
        obj.rotation_euler = (math.radians(90), 0, 0)
        obj.name = f'Dugme_{i}'
        obj.data.materials.append(button_mat)

buttons()

bpy.ops.mesh.primitive_cylinder_add(vertices=16, radius=0.006, depth=0.48, location=(0, 0, 1.46))
bar = bpy.context.active_object
bar.rotation_euler = (0, math.radians(90), 0)
bar.name = 'Aski'
bar.data.materials.append(wood)
bpy.ops.mesh.primitive_torus_add(major_radius=0.018, minor_radius=0.003, location=(0, -0.01, 1.50))
hook = bpy.context.active_object
hook.name = 'Kanca'
hook.rotation_euler = (math.radians(90), 0, 0)
hook.data.materials.append(metal)

for obj in list(bpy.context.scene.objects):
    if obj.name == 'Manken':
        bpy.data.objects.remove(obj, do_unlink=True)
        continue
    obj.select_set(True)
bpy.context.view_layer.objects.active = shirt
bpy.ops.object.join()

tex = bpy.data.textures.new('Dokuma', 'CLOUDS')
tex.noise_scale = 0.015
disp = shirt.modifiers.new('Dokuma', 'DISPLACE')
disp.texture = tex
disp.strength = 0.0011
disp.mid_level = 0.5
bpy.ops.object.modifier_apply(modifier='Dokuma')

out = '/Users/erkanulker/mobilyatakip/public/models/wardrobe/library/gomlek.glb'
bpy.ops.export_scene.gltf(
    filepath=out,
    export_format='GLB',
    export_apply=True,
    export_yup=True,
    use_selection=False,
)
print('SHIRT_OK', out)
