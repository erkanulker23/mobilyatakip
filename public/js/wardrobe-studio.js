import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const T = 1.8;
const BACK = 0.8;
const DOOR_T = 1.8;
const SAVE_KEY = 'meemare-wardrobe-saves';
const INTRO_KEY = 'meemare-wardrobe-intro';

const MATERIALS = [
    { id: 'kum', name: 'Kum Taşı', color: '#d9cfc2', roughness: 0.72, metalness: 0 },
    { id: 'kasmir', name: 'Kaşmir', color: '#e6e0d6', roughness: 0.7, metalness: 0 },
    { id: 'latte', name: 'Latte', color: '#c8b39a', roughness: 0.62, metalness: 0 },
    { id: 'aytasi', name: 'Aytaşı', color: '#f4f1ea', roughness: 0.55, metalness: 0 },
    { id: 'vizon', name: 'Vizon', color: '#8d7b6a', roughness: 0.66, metalness: 0 },
    { id: 'cappuccino', name: 'Cappuccino', color: '#6e5344', roughness: 0.6, metalness: 0 },
    { id: 'mese', name: 'Açık Meşe', color: '#c4a574', roughness: 0.5, metalness: 0, wood: true },
    { id: 'ceviz', name: 'Anadolu Cevizi', color: '#6b4226', roughness: 0.48, metalness: 0, wood: true },
    { id: 'beyaz', name: 'Beyaz', color: '#f7f7f5', roughness: 0.45, metalness: 0 },
    { id: 'antrasit', name: 'Antrasit', color: '#3a3d40', roughness: 0.5, metalness: 0.04 },
    { id: 'buz', name: 'Buz Gri', color: '#c5c8c6', roughness: 0.4, metalness: 0.05 },
    { id: 'siyah', name: 'Mat Siyah', color: '#1c1c1c', roughness: 0.78, metalness: 0.02 },
];

const DOOR_TYPES = [
    { id: 'mdf', name: 'Düz MDF' },
    { id: 'lake-mat', name: 'Mat lake' },
    { id: 'lake-parlak', name: 'Parlak lake' },
    { id: 'ahsap', name: 'Ahşap desen' },
    { id: 'bronz', name: 'Bronz cam' },
    { id: 'fume', name: 'Füme cam' },
    { id: 'seffaf', name: 'Şeffaf cam' },
    { id: 'ayna', name: 'Aynalı' },
    { id: 'aluminyum', name: 'Alüminyum çerçeveli cam' },
    { id: 'yok', name: 'Kapaksız' },
];

const CATALOG = [
    { type: 'tek-askilik', cat: 'Askılık', name: 'Tek askılık', desc: 'Üstte askı borusu, isteğe bağlı raflar.', width: 60, shelves: 1, drawers: 0, door: 'mdf' },
    { type: 'cift-askilik', cat: 'Askılık', name: 'Çift askılık', desc: 'Üstte ve altta iki askı borusu.', width: 60, shelves: 1, drawers: 0, door: 'mdf' },
    { type: 'uzun-askilik', cat: 'Askılık', name: 'Uzun kıyafet askılığı', desc: 'Elbise ve pardösü için yüksek askı.', width: 80, shelves: 0, drawers: 0, door: 'mdf' },
    { type: 'tam-rafli', cat: 'Raf', name: 'Tam raflı dolap', desc: 'İç alan eşit raflara bölünür.', width: 50, shelves: 5, drawers: 0, door: 'mdf' },
    { type: 'acik-nis', cat: 'Raf', name: 'Açık niş', desc: 'Kapaksız dekoratif raf.', width: 40, shelves: 4, drawers: 0, door: 'yok' },
    { type: 'cekmeceli', cat: 'Çekmece', name: 'Çekmeceli dolap', desc: '2–6 çekmece, açılır.', width: 50, shelves: 0, drawers: 4, door: 'yok' },
    { type: 'askilik-cekmece', cat: 'Çekmece', name: 'Askılık + çekmece', desc: 'Üstte askı, altta çekmece.', width: 60, shelves: 0, drawers: 3, door: 'mdf' },
    { type: 'raf-cekmece', cat: 'Çekmece', name: 'Raf + çekmece', desc: 'Üstte raf, altta çekmece.', width: 50, shelves: 3, drawers: 3, door: 'mdf' },
    { type: 'pantolonluk', cat: 'Özel', name: 'Pantolonluk', desc: 'Çekilen pantolon askıları.', width: 50, shelves: 0, drawers: 0, door: 'mdf' },
    { type: 'ayakkabilik', cat: 'Özel', name: 'Ayakkabılık', desc: 'Düz veya eğimli raflar.', width: 50, shelves: 5, drawers: 0, door: 'mdf' },
    { type: 'canta', cat: 'Özel', name: 'Çanta ve aksesuar', desc: 'Bölmeli raf ve küçük çekmece.', width: 45, shelves: 3, drawers: 1, door: 'mdf' },
    { type: 'ust-depolama', cat: 'Özel', name: 'Üst depolama', desc: 'Dolabın üzerine oturan kapaklı göz.', width: 60, shelves: 1, drawers: 0, door: 'mdf', band: 'top', height: 55 },
    { type: 'vitrin', cat: 'Özel', name: 'Cam kapaklı vitrin', desc: 'Cam kapak, raf ve isteğe bağlı ışık.', width: 50, shelves: 4, drawers: 0, door: 'bronz' },
    { type: 'kose', cat: 'Özel', name: 'Köşe dolap', desc: 'Yalnızca L yerleşiminde.', width: 90, shelves: 3, drawers: 0, door: 'mdf' },
    { type: 'ozel', cat: 'Özel', name: 'Özel boş modül', desc: 'Raf, askı ve çekmeceyi kendin diz.', width: 60, shelves: 0, drawers: 0, door: 'mdf', zones: [{ kind: 'rail' }, { kind: 'shelf' }, { kind: 'drawer' }] },
];

const VIEWS = [
    ['perspektif', 'Perspektif'],
    ['on', 'Karşıdan'],
    ['sag', 'Sağ'],
    ['sol', 'Sol'],
    ['ust', 'Üst'],
    ['ic', 'İç'],
    ['olcu', 'Ölçülü'],
];

const INTRO = [
    ['Dolabının ölçülerini gir.', 'Sağ panelden genişlik, yükseklik ve derinliği santimetre olarak değiştir. Görüntü hemen yenilenir.'],
    ['Modülleri seç ve yerleştir.', 'Soldan Dolaba ekle veya kartı sahneye sürükle. Modülü tutup yana çekerek sırasını değiştir.'],
    ['Rengini belirle ve kaydet.', 'Gövde ile kapağı ayrı seç. Kaydet, paylaş veya fiyat teklifi iste.'],
];

const el = (id) => document.getElementById(id);
const uid = () => Math.random().toString(36).slice(2, 10);
const clamp = (n, a, b) => Math.min(b, Math.max(a, n));
const materialById = (id) => MATERIALS.find((m) => m.id === id) || MATERIALS[0];
const catalogByType = (type) => CATALOG.find((m) => m.type === type);

let state = blankState();
let history = [];
let future = [];
let selectedId = null;
let pro = false;
let viewMode = 'show';
let cameraMode = 'perspektif';
let measure = false;
let catFilter = 'Tümü';
let search = '';
let toastTimer = 0;

let renderer;
let scene;
let camera;
let controls;
let cabinetRoot;
let clock;
const doorPivots = [];
const slides = [];
let drag = null;
let ghost = null;

function blankState() {
    return {
        name: 'Dolabım',
        width: 240,
        height: 240,
        depth: 60,
        layout: 'straight',
        plinth: 8,
        carcassId: 'kum',
        doorColorId: 'aytasi',
        interiorId: 'aytasi',
        modules: [],
    };
}

function createModule(type) {
    const def = catalogByType(type);
    return {
        id: uid(),
        type,
        width: def.width,
        height: def.height || null,
        depth: null,
        shelves: def.shelves,
        drawers: def.drawers,
        topShelf: true,
        bottomShelf: type === 'tek-askilik',
        tilted: false,
        led: false,
        door: def.door,
        hinge: 'left',
        open: 0,
        carcassId: null,
        doorColorId: null,
        zones: (def.zones || []).map((z) => ({ kind: z.kind })),
    };
}

function moduleHeight(mod) {
    if (mod.type === 'ust-depolama') return mod.height || 55;
    return mod.height || state.height;
}

function moduleDepth(mod) {
    return mod.depth || state.depth;
}

function snapshot() {
    history.push(JSON.stringify(state));
    if (history.length > 40) history.shift();
    future = [];
}

function commit(fn) {
    snapshot();
    fn();
    render();
}

function undo() {
    if (!history.length) return;
    future.push(JSON.stringify(state));
    state = JSON.parse(history.pop());
    if (!state.modules.some((m) => m.id === selectedId)) selectedId = null;
    render();
}

function redo() {
    if (!future.length) return;
    history.push(JSON.stringify(state));
    state = JSON.parse(future.pop());
    render();
}

function floorModules() {
    return state.modules.filter((m) => m.type !== 'ust-depolama');
}

function topModules() {
    return state.modules.filter((m) => m.type === 'ust-depolama');
}

function usedWidth(list) {
    return list.reduce((sum, m) => sum + Number(m.width), 0);
}

function toast(text) {
    const node = el('toast');
    node.textContent = text;
    node.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => node.classList.add('hidden'), 2600);
}

function canAdd(mod) {
    if (mod.type === 'kose') {
        if (state.layout !== 'corner') {
            toast('Köşe modülü için yerleşimi L köşe yapın.');
            return false;
        }
        if (state.modules.some((m) => m.type === 'kose')) {
            toast('Bir dolapta tek köşe modülü olur.');
            return false;
        }
    }
    const list = mod.type === 'ust-depolama' ? topModules() : floorModules().filter((m) => m.type !== 'kose');
    const corner = floorModules().find((m) => m.type === 'kose');
    const extra = mod.type === 'kose' ? 0 : mod.width;
    const base = mod.type === 'ust-depolama' ? usedWidth(list) : usedWidth(floorModules().filter((m) => m.type !== 'kose'));
    const cornerW = mod.type === 'kose' ? mod.width : (corner ? corner.width : 0);
    if (base + extra + (mod.type === 'ust-depolama' ? 0 : cornerW) > state.width + 0.05 && mod.type !== 'ust-depolama') {
        toast('Bu modül dolap genişliğine sığmıyor.');
        return false;
    }
    if (mod.type === 'ust-depolama' && usedWidth(list) + mod.width > state.width + 0.05) {
        toast('Üst depolama dolap genişliğine sığmıyor.');
        return false;
    }
    return true;
}

function addModule(type) {
    const mod = createModule(type);
    if (!canAdd(mod)) return;
    commit(() => state.modules.push(mod));
    selectedId = mod.id;
    render();
}

function removeModule(id) {
    commit(() => {
        state.modules = state.modules.filter((m) => m.id !== id);
    });
    if (selectedId === id) selectedId = null;
    render();
}

function duplicateModule(id) {
    const source = state.modules.find((m) => m.id === id);
    if (!source) return;
    const copy = JSON.parse(JSON.stringify(source));
    copy.id = uid();
    if (!canAdd(copy)) return;
    commit(() => {
        const index = state.modules.findIndex((m) => m.id === id);
        state.modules.splice(index + 1, 0, copy);
    });
    selectedId = copy.id;
    render();
}

function moveModule(id, dir) {
    const index = state.modules.findIndex((m) => m.id === id);
    const next = index + dir;
    if (index < 0 || next < 0 || next >= state.modules.length) return;
    commit(() => {
        const [item] = state.modules.splice(index, 1);
        state.modules.splice(next, 0, item);
    });
}

function updateModule(id, patch) {
    const mod = state.modules.find((m) => m.id === id);
    if (!mod) return;
    const next = { ...mod, ...patch };
    if (patch.width != null) {
        next.width = clamp(Number(patch.width), 30, 140);
        const others = state.modules.filter((m) => m.id !== id && (m.type === 'ust-depolama') === (mod.type === 'ust-depolama') && m.type !== 'kose' && mod.type !== 'kose');
        const corner = state.modules.find((m) => m.type === 'kose' && m.id !== id);
        const limit = state.width - usedWidth(others) - (mod.type === 'ust-depolama' || mod.type === 'kose' ? 0 : (corner && mod.type !== 'kose' ? corner.width : 0));
        if (next.width > limit + 0.05) {
            toast('Genişlik dolabın kalan yerine sığmıyor.');
            next.width = Math.max(30, Math.floor(limit));
        }
    }
    if (patch.shelves != null) next.shelves = clamp(Number(patch.shelves), 2, 10);
    if (patch.drawers != null) next.drawers = clamp(Number(patch.drawers), mod.type === 'canta' ? 0 : 2, 6);
    commit(() => Object.assign(mod, next));
}

function layoutOf() {
    const spots = [];
    let x = 0;
    let onReturn = false;
    let returnZ = 0;
    let cornerX = 0;
    floorModules().forEach((mod) => {
        if (mod.type === 'kose') {
            spots.push({ id: mod.id, x, z: 0, rotY: 0, y: 0 });
            cornerX = x + mod.width - moduleDepth(mod);
            onReturn = true;
            returnZ = moduleDepth(mod);
            return;
        }
        if (!onReturn) {
            spots.push({ id: mod.id, x, z: 0, rotY: 0, y: 0 });
            x += mod.width;
        } else {
            spots.push({ id: mod.id, x: cornerX, z: returnZ, rotY: Math.PI / 2, y: 0 });
            returnZ += mod.width;
        }
    });
    let tx = 0;
    topModules().forEach((mod) => {
        spots.push({ id: mod.id, x: tx, z: 0, rotY: 0, y: state.height });
        tx += mod.width;
    });
    return spots;
}

function warnings() {
    const notes = [];
    const floor = usedWidth(floorModules().filter((m) => m.type !== 'kose'));
    const corner = floorModules().find((m) => m.type === 'kose');
    const floorTotal = floor + (corner ? corner.width : 0);
    if (floorTotal > state.width + 0.05) notes.push('Zemin modülleri dolap genişliğini aşıyor.');
    if (usedWidth(topModules()) > state.width + 0.05) notes.push('Üst depolama dolap genişliğini aşıyor.');
    if (state.layout === 'straight' && corner) notes.push('Köşe modülü duruyor. Yerleşimi L köşe yapın veya köşeyi kaldırın.');
    state.modules.forEach((mod) => {
        const innerH = moduleHeight(mod) - state.plinth - 2 * T;
        if (mod.drawers > 0 && innerH / mod.drawers < 12) notes.push(`${catalogByType(mod.type).name} için çekmece yüksekliği 12 cm altında.`);
    });
    return notes;
}

function zonesFor(mod) {
    if (mod.type === 'ozel') return mod.zones.length ? mod.zones : [{ kind: 'shelf' }];
    if (mod.type === 'tam-rafli' || mod.type === 'acik-nis' || mod.type === 'vitrin' || mod.type === 'kose') {
        return Array.from({ length: mod.shelves }, () => ({ kind: mod.type === 'ayakkabilik' ? 'shoe' : 'shelf' }));
    }
    if (mod.type === 'ayakkabilik') return Array.from({ length: mod.shelves }, () => ({ kind: 'shoe' }));
    if (mod.type === 'cekmeceli') return Array.from({ length: mod.drawers }, () => ({ kind: 'drawer' }));
    if (mod.type === 'tek-askilik') {
        const zones = [];
        if (mod.topShelf) zones.push({ kind: 'shelf', flex: 0.12 });
        zones.push({ kind: 'rail', flex: 0.7 });
        if (mod.bottomShelf) zones.push({ kind: 'shelf', flex: 0.18 });
        return zones;
    }
    if (mod.type === 'cift-askilik') return [{ kind: 'rail', flex: 0.45 }, { kind: 'shelf', flex: 0.1 }, { kind: 'rail', flex: 0.45 }];
    if (mod.type === 'uzun-askilik') return [{ kind: 'rail', flex: 1, at: 0.82 }];
    if (mod.type === 'askilik-cekmece') {
        return [{ kind: 'rail', flex: 0.5 }, ...Array.from({ length: mod.drawers }, () => ({ kind: 'drawer', flex: 0.5 / mod.drawers }))];
    }
    if (mod.type === 'raf-cekmece') {
        return [
            ...Array.from({ length: mod.shelves }, () => ({ kind: 'shelf', flex: 0.5 / mod.shelves })),
            ...Array.from({ length: mod.drawers }, () => ({ kind: 'drawer', flex: 0.5 / mod.drawers })),
        ];
    }
    if (mod.type === 'pantolonluk') return [{ kind: 'pants' }];
    if (mod.type === 'canta') {
        const zones = Array.from({ length: mod.shelves }, (_, i) => ({ kind: i === 1 && mod.glassShelf ? 'glass' : 'shelf' }));
        if (mod.drawers > 0) zones.push({ kind: 'drawer' });
        return zones;
    }
    if (mod.type === 'ust-depolama') return [{ kind: 'shelf' }];
    return [{ kind: 'shelf' }];
}

function mat(id, role) {
    const item = materialById(id);
    const material = new THREE.MeshStandardMaterial({
        color: item.color,
        roughness: role === 'door' && state.modules ? item.roughness : item.roughness,
        metalness: item.metalness,
    });
    material.userData.shared = false;
    return material;
}

function doorMaterial(doorId, colorId) {
    const color = materialById(colorId).color;
    if (doorId === 'lake-parlak') return new THREE.MeshStandardMaterial({ color, roughness: 0.08, metalness: 0.12 });
    if (doorId === 'lake-mat') return new THREE.MeshStandardMaterial({ color, roughness: 0.38, metalness: 0.02 });
    if (doorId === 'ahsap') return new THREE.MeshStandardMaterial({ color: materialById(colorId).wood ? color : '#b8895a', roughness: 0.5, metalness: 0 });
    if (doorId === 'ayna') return new THREE.MeshStandardMaterial({ color: '#d5d8dc', roughness: 0.04, metalness: 1 });
    if (doorId === 'bronz' || doorId === 'fume' || doorId === 'seffaf' || doorId === 'aluminyum') {
        const map = { bronz: '#8a6844', fume: '#4a4e52', seffaf: '#eef3f4', aluminyum: '#d5e2e4' };
        return new THREE.MeshPhysicalMaterial({
            color: map[doorId],
            roughness: 0.06,
            metalness: 0,
            transmission: doorId === 'seffaf' ? 0.9 : 0.55,
            transparent: true,
            opacity: doorId === 'seffaf' ? 0.35 : 0.72,
            thickness: 0.4,
        });
    }
    return new THREE.MeshStandardMaterial({ color, roughness: 0.62, metalness: 0 });
}

function panel(target, w, h, d, cx, cy, cz, material) {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), material);
    mesh.position.set(cx, cy, cz);
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    target.add(mesh);
    return mesh;
}

function buildModule(mod) {
    const w = mod.width;
    const h = moduleHeight(mod);
    const d = moduleDepth(mod);
    const group = new THREE.Group();
    group.userData.moduleId = mod.id;
    const carcass = mat(mod.carcassId || state.carcassId);
    const interior = mat(mod.carcassId || state.interiorId);
    const plinth = state.plinth;
    panel(group, T, h, d, T / 2, h / 2, d / 2, carcass);
    panel(group, T, h, d, w - T / 2, h / 2, d / 2, carcass);
    panel(group, w - 2 * T, T, d, w / 2, plinth + T / 2, d / 2, carcass);
    panel(group, w - 2 * T, T, d, w / 2, h - T / 2, d / 2, carcass);
    const backH = Math.max(4, h - plinth - 2 * T);
    panel(group, w - 2 * T, backH, BACK, w / 2, plinth + T + backH / 2, BACK / 2, carcass);
    if (plinth > 0) panel(group, w - 1, plinth, d - 1, w / 2, plinth / 2, d / 2, new THREE.MeshStandardMaterial({ color: '#2c2c2c', roughness: 0.8 }));

    const x0 = T;
    const x1 = w - T;
    const y0 = plinth + T;
    const y1 = h - T;
    const iw = x1 - x0;
    const ih = Math.max(8, y1 - y0);
    const id = Math.max(8, d - BACK - 2);
    const zones = zonesFor(mod);
    const flexSum = zones.reduce((sum, zone) => sum + (zone.flex || 1), 0);
    let y = y0;
    zones.forEach((zone) => {
        const band = ih * (zone.flex || 1) / flexSum;
        const cy = zone.at ? y0 + ih * zone.at : y + band / 2;
        if (zone.kind === 'shelf' || zone.kind === 'glass' || zone.kind === 'shoe') {
            const board = panel(group, iw - 0.4, T, id - 1, w / 2, zone.at ? cy : y + T / 2, BACK + (id / 2), zone.kind === 'glass'
                ? new THREE.MeshPhysicalMaterial({ color: '#d9e6ea', transmission: 0.85, transparent: true, opacity: 0.4, roughness: 0.05 })
                : interior);
            if (zone.kind === 'shoe' && mod.tilted) board.rotation.x = -0.28;
        }
        if (zone.kind === 'rail') {
            const rail = new THREE.Mesh(new THREE.CylinderGeometry(0.7, 0.7, iw - 4, 16), new THREE.MeshStandardMaterial({ color: '#c0c4c8', metalness: 0.85, roughness: 0.25 }));
            rail.rotation.z = Math.PI / 2;
            rail.position.set(w / 2, cy, d * 0.45);
            group.add(rail);
        }
        if (zone.kind === 'pants') {
            const slider = new THREE.Group();
            slider.position.set(0, 0, BACK);
            slider.userData.kind = 'slide';
            slider.userData.moduleId = mod.id;
            slider.userData.baseZ = BACK;
            slider.userData.travel = d * 0.4;
            for (let i = 0; i < 6; i += 1) {
                const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.45, 0.45, iw - 8, 10), new THREE.MeshStandardMaterial({ color: '#b7b7b7', metalness: 0.7, roughness: 0.3 }));
                bar.rotation.z = Math.PI / 2;
                bar.position.set(w / 2, y0 + 10 + i * 6, 8);
                slider.add(bar);
            }
            group.add(slider);
            slides.push(slider);
        }
        if (zone.kind === 'drawer') {
            const slider = new THREE.Group();
            const dh = Math.max(8, band - 0.6);
            slider.position.set(0, 0, 0);
            slider.userData.kind = 'slide';
            slider.userData.moduleId = mod.id;
            slider.userData.baseZ = 0;
            slider.userData.travel = d * 0.42;
            const frontMat = doorMaterial(mod.door === 'yok' ? 'mdf' : 'mdf', mod.doorColorId || state.doorColorId);
            panel(slider, iw - 0.6, dh - 0.4, DOOR_T, w / 2, y + dh / 2, d + DOOR_T / 2, frontMat);
            panel(slider, iw - 2, dh - 2, id * 0.75, w / 2, y + dh / 2, BACK + (id * 0.4), interior);
            group.add(slider);
            slides.push(slider);
        }
        y += band;
    });

    if (mod.led) {
        const light = panel(group, iw - 4, 0.4, 1.2, w / 2, y1 - 1, d * 0.55, new THREE.MeshStandardMaterial({ color: '#fff4d2', emissive: '#fff4d2', emissiveIntensity: 0.8 }));
        light.castShadow = false;
    }

    if (mod.door !== 'yok' && mod.type !== 'cekmeceli') {
        const leaves = w >= 70 || mod.hinge === 'pair' ? 2 : 1;
        const gap = 0.35;
        const leafW = (w - gap * (leaves + 1)) / leaves;
        const leafH = Math.max(10, h - plinth - gap * 2);
        for (let i = 0; i < leaves; i += 1) {
            const left = leaves === 1 ? mod.hinge !== 'right' : i === 0;
            const pivot = new THREE.Group();
            const hingeX = left ? gap + i * leafW : gap + (i + 1) * leafW;
            pivot.position.set(left ? hingeX : hingeX, plinth + gap, d);
            pivot.userData.kind = 'door';
            pivot.userData.moduleId = mod.id;
            pivot.userData.sign = left ? -1 : 1;
            const doorMat = doorMaterial(mod.door, mod.doorColorId || state.doorColorId);
            const door = panel(pivot, leafW, leafH, DOOR_T, left ? leafW / 2 : -leafW / 2, leafH / 2, DOOR_T / 2, doorMat);
            if (mod.door === 'aluminyum') {
                door.scale.set(0.86, 0.9, 1);
                panel(pivot, leafW, 2, DOOR_T + 0.1, left ? leafW / 2 : -leafW / 2, 1, DOOR_T / 2, new THREE.MeshStandardMaterial({ color: '#bfc5c8', metalness: 0.8, roughness: 0.3 }));
                panel(pivot, leafW, 2, DOOR_T + 0.1, left ? leafW / 2 : -leafW / 2, leafH - 1, DOOR_T / 2, new THREE.MeshStandardMaterial({ color: '#bfc5c8', metalness: 0.8, roughness: 0.3 }));
            }
            const handle = new THREE.Mesh(new THREE.BoxGeometry(1.2, 10, 1.2), new THREE.MeshStandardMaterial({ color: '#222', metalness: 0.6, roughness: 0.35 }));
            handle.position.set(left ? leafW - 4 : 4 - leafW, leafH * 0.45, DOOR_T + 0.6);
            pivot.add(handle);
            group.add(pivot);
            doorPivots.push(pivot);
        }
    }

    if (mod.type === 'kose') {
        const wing = panel(group, d, h, T, w - d / 2, h / 2, d + d / 2, carcass);
        wing.userData.wing = true;
        panel(group, d - T, T, d - T, w - d / 2, plinth + T / 2, d + d / 2, carcass);
        panel(group, d - T, T, d - T, w - d / 2, h - T / 2, d + d / 2, carcass);
    }
    return group;
}

function cutList() {
    const rows = [];
    state.modules.forEach((mod, index) => {
        const h = moduleHeight(mod);
        const d = moduleDepth(mod);
        const name = `${index + 1}. ${catalogByType(mod.type).name}`;
        rows.push(`${name}: yan ${T}×${h}×${d} cm ×2`);
        rows.push(`${name}: tabla ${Math.max(1, mod.width - 2 * T).toFixed(1)}×${T}×${d} cm ×2`);
        rows.push(`${name}: arkalık ${(mod.width - 2 * T).toFixed(1)}×${Math.max(4, h - state.plinth - 2 * T).toFixed(1)}×${BACK} cm`);
        if (mod.door !== 'yok' && mod.type !== 'cekmeceli') rows.push(`${name}: kapak ${mod.door}`);
    });
    return rows;
}

function rebuild() {
    doorPivots.length = 0;
    slides.length = 0;
    while (cabinetRoot.children.length) {
        const child = cabinetRoot.children.pop();
        child.traverse((obj) => {
            if (obj.geometry) obj.geometry.dispose();
            if (obj.material && !obj.material.userData.keep) {
                const list = Array.isArray(obj.material) ? obj.material : [obj.material];
                list.forEach((item) => item.dispose && item.dispose());
            }
        });
    }
    const spots = layoutOf();
    state.modules.forEach((mod) => {
        const spot = spots.find((item) => item.id === mod.id);
        if (!spot) return;
        const mesh = buildModule(mod);
        mesh.position.set(spot.x, spot.y, spot.z);
        mesh.rotation.y = spot.rotY;
        if (mod.id === selectedId) {
            const edges = new THREE.LineSegments(
                new THREE.EdgesGeometry(new THREE.BoxGeometry(mod.width, moduleHeight(mod), moduleDepth(mod))),
                new THREE.LineBasicMaterial({ color: 0x1f6b4a }),
            );
            edges.position.set(mod.width / 2, moduleHeight(mod) / 2, moduleDepth(mod) / 2);
            mesh.add(edges);
        }
        cabinetRoot.add(mesh);
    });
    const footprint = new THREE.Mesh(
        new THREE.BoxGeometry(state.width, 0.4, state.depth),
        new THREE.MeshStandardMaterial({ color: '#c8d7cc', transparent: true, opacity: 0.35 }),
    );
    footprint.position.set(state.width / 2, 0.2, state.depth / 2);
    cabinetRoot.add(footprint);
    cabinetRoot.position.x = -state.width / 2;
    applyViewMaterials();
    drawMeasures();
}

function applyViewMaterials() {
    doorPivots.forEach((pivot) => {
        const mod = state.modules.find((m) => m.id === pivot.userData.moduleId);
        pivot.visible = viewMode !== 'hide' && mod && mod.door !== 'yok';
        pivot.traverse((obj) => {
            if (!obj.material || !obj.material.transparent && viewMode !== 'ghost') return;
            if (viewMode === 'ghost' && obj.material) {
                obj.material.transparent = true;
                obj.material.opacity = 0.22;
            }
        });
    });
}

function frameCamera(mode) {
    const w = state.width;
    const h = state.height + (topModules().length ? 55 : 0);
    const d = state.depth;
    const target = new THREE.Vector3(0, h * 0.45, d / 2);
    const spots = {
        perspektif: new THREE.Vector3(w * 0.55, h * 0.72, d + w * 0.85),
        on: new THREE.Vector3(0, h * 0.48, d + Math.max(w, 180)),
        sag: new THREE.Vector3(w * 0.9, h * 0.5, d / 2),
        sol: new THREE.Vector3(-w * 0.9, h * 0.5, d / 2),
        ust: new THREE.Vector3(0, h + w, d / 2),
        ic: new THREE.Vector3(0, h * 0.5, d * 0.2),
        olcu: new THREE.Vector3(0, h * 0.48, d + Math.max(w, 180)),
    };
    cameraMode = mode;
    measure = mode === 'olcu';
    const next = spots[mode] || spots.perspektif;
    camera.userData.to = next;
    controls.target.copy(target);
}

function init3d() {
    const viewport = el('viewport');
    scene = new THREE.Scene();
    scene.background = new THREE.Color('#cfc8bc');
    camera = new THREE.PerspectiveCamera(38, 1, 1, 5000);
    renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 0.95;
    renderer.shadowMap.enabled = true;
    viewport.appendChild(renderer.domElement);
    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.add(new THREE.HemisphereLight(0xfff6ea, 0x8a8175, 0.55));
    const key = new THREE.DirectionalLight(0xfff7ee, 0.85);
    key.position.set(180, 320, 220);
    key.castShadow = true;
    key.shadow.mapSize.set(1024, 1024);
    scene.add(key);
    const floor = new THREE.Mesh(new THREE.PlaneGeometry(2000, 2000), new THREE.MeshStandardMaterial({ color: '#d9d3c8', roughness: 0.95 }));
    floor.rotation.x = -Math.PI / 2;
    floor.receiveShadow = true;
    scene.add(floor);
    const wall = new THREE.Mesh(new THREE.PlaneGeometry(2000, 800), new THREE.MeshStandardMaterial({ color: '#f6f3ee', roughness: 1 }));
    wall.position.set(0, 400, -8);
    scene.add(wall);
    cabinetRoot = new THREE.Group();
    scene.add(cabinetRoot);
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.maxPolarAngle = Math.PI * 0.49;
    controls.target.set(0, 110, 30);
    frameCamera('perspektif');
    camera.position.copy(camera.userData.to);
    clock = new THREE.Clock();
    window.addEventListener('resize', resize);
    renderer.domElement.addEventListener('pointerdown', onPointerDown);
    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onPointerUp);
    viewport.addEventListener('dragover', (event) => event.preventDefault());
    viewport.addEventListener('drop', onDrop);
    resize();
    requestAnimationFrame(tick);
}

function resize() {
    const viewport = el('viewport');
    const width = viewport.clientWidth || 600;
    const height = viewport.clientHeight || 400;
    camera.aspect = width / Math.max(1, height);
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, true);
}

function tick() {
    const dt = Math.min(0.05, clock.getDelta());
    if (camera.userData.to) {
        camera.position.lerp(camera.userData.to, 0.08);
        if (camera.position.distanceTo(camera.userData.to) < 1) camera.userData.to = null;
    }
    doorPivots.forEach((pivot) => {
        const mod = state.modules.find((m) => m.id === pivot.userData.moduleId);
        const amount = viewMode === 'open' ? 1 : (mod ? mod.open : 0);
        const target = pivot.userData.sign * amount * 1.15;
        pivot.rotation.y = THREE.MathUtils.damp(pivot.rotation.y, target, 8, dt);
        pivot.visible = viewMode !== 'hide';
    });
    slides.forEach((slider) => {
        const mod = state.modules.find((m) => m.id === slider.userData.moduleId);
        const amount = viewMode === 'open' ? 1 : (mod ? mod.open : 0);
        const target = slider.userData.baseZ + amount * slider.userData.travel;
        slider.position.z = THREE.MathUtils.damp(slider.position.z, target, 8, dt);
    });
    controls.update();
    renderer.render(scene, camera);
    requestAnimationFrame(tick);
}

let measureGroup = null;
function drawMeasures() {
    if (measureGroup) {
        scene.remove(measureGroup);
        measureGroup.traverse((obj) => obj.geometry && obj.geometry.dispose());
        measureGroup = null;
    }
    if (!measure) return;
    measureGroup = new THREE.Group();
    const points = [
        new THREE.Vector3(-state.width / 2, 2, state.depth + 18),
        new THREE.Vector3(state.width / 2, 2, state.depth + 18),
    ];
    measureGroup.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints(points), new THREE.LineBasicMaterial({ color: 0x1f6b4a })));
    scene.add(measureGroup);
}

function pointerRay(event) {
    const rect = renderer.domElement.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    const y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
    const raycaster = new THREE.Raycaster();
    raycaster.setFromCamera(new THREE.Vector2(x, y), camera);
    return raycaster;
}

function moduleFromHit(event) {
    const hits = pointerRay(event).intersectObjects(cabinetRoot.children, true);
    for (const hit of hits) {
        let node = hit.object;
        while (node) {
            if (node.userData.moduleId) return node.userData.moduleId;
            node = node.parent;
        }
    }
    return null;
}

function onPointerDown(event) {
    if (event.button !== 0) return;
    const id = moduleFromHit(event);
    selectedId = id;
    renderInspector();
    rebuild();
    if (!id) return;
    drag = { id, startX: event.clientX, moved: false };
    controls.enabled = false;
}

function onPointerMove(event) {
    if (!drag) return;
    if (Math.abs(event.clientX - drag.startX) < 8) return;
    drag.moved = true;
    const raycaster = pointerRay(event);
    const plane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
    const point = new THREE.Vector3();
    raycaster.ray.intersectPlane(plane, point);
    const localX = point.x - cabinetRoot.position.x;
    const order = floorModules();
    let cursor = 0;
    let index = order.length - 1;
    order.forEach((mod, i) => {
        if (localX < cursor + mod.width / 2) index = Math.min(index, i);
        cursor += mod.width;
    });
    const valid = localX > -20 && localX < state.width + 20;
    showGhost(order[index], valid);
    drag.index = index;
    drag.valid = valid;
}

function onPointerUp() {
    if (drag?.moved && drag.valid) {
        const order = floorModules();
        const from = state.modules.findIndex((m) => m.id === drag.id);
        const target = order[drag.index];
        const to = target ? state.modules.findIndex((m) => m.id === target.id) : from;
        if (from !== to && from >= 0) {
            commit(() => {
                const [item] = state.modules.splice(from, 1);
                state.modules.splice(to, 0, item);
            });
        }
    } else if (drag?.moved && !drag.valid) {
        toast('Modül dolabın dışına bırakılamaz.');
    }
    drag = null;
    controls.enabled = true;
    clearGhost();
}

function showGhost(mod, valid) {
    clearGhost();
    if (!mod) return;
    ghost = new THREE.Mesh(
        new THREE.BoxGeometry(mod.width, moduleHeight(mod), moduleDepth(mod)),
        new THREE.MeshStandardMaterial({ color: valid ? 0x1f6b4a : 0x9f2d2d, transparent: true, opacity: 0.28 }),
    );
    const spot = layoutOf().find((item) => item.id === mod.id);
    if (!spot) return;
    ghost.position.set(spot.x + mod.width / 2, moduleHeight(mod) / 2, spot.z + moduleDepth(mod) / 2);
    cabinetRoot.add(ghost);
}

function clearGhost() {
    if (!ghost) return;
    cabinetRoot.remove(ghost);
    ghost.geometry.dispose();
    ghost.material.dispose();
    ghost = null;
}

function onDrop(event) {
    event.preventDefault();
    const type = event.dataTransfer.getData('module-type');
    if (type) addModule(type);
}

function renderLibrary() {
    const cats = ['Tümü', ...new Set(CATALOG.map((item) => item.cat))];
    el('cats').innerHTML = cats.map((cat) => `<button type="button" class="cat ${cat === catFilter ? 'is-on' : ''}" data-cat="${cat}">${cat}</button>`).join('');
    el('cats').querySelectorAll('[data-cat]').forEach((button) => {
        button.onclick = () => { catFilter = button.dataset.cat; renderLibrary(); };
    });
    const items = CATALOG.filter((item) => (catFilter === 'Tümü' || item.cat === catFilter) && (`${item.name} ${item.desc}`).toLocaleLowerCase('tr').includes(search.toLocaleLowerCase('tr')));
    el('module-list').innerHTML = items.map((item) => `
        <article class="card" draggable="true" data-type="${item.type}">
            <div class="swatch-preview">${item.width} cm</div>
            <div>
                <h3>${item.name}</h3>
                <p>${item.desc}</p>
                <div class="meta">Varsayılan ${item.width} cm</div>
                <button type="button" class="btn" data-add="${item.type}" style="margin-top:6px">Dolaba ekle</button>
            </div>
        </article>
    `).join('');
    el('module-list').querySelectorAll('.card').forEach((card) => {
        card.addEventListener('dragstart', (event) => event.dataTransfer.setData('module-type', card.dataset.type));
    });
    el('module-list').querySelectorAll('[data-add]').forEach((button) => {
        button.onclick = () => addModule(button.dataset.add);
    });
}

function colorDots(current, attr) {
    return `<div class="colors">${MATERIALS.map((item) => `<button type="button" class="dot ${item.id === current ? 'is-on' : ''}" style="background:${item.color}" title="${item.name}" data-${attr}="${item.id}"></button>`).join('')}</div><p class="hint">Yaklaşık renk. Lisanslı üretici dokusu değil.</p>`;
}

function renderInspector() {
    const mod = state.modules.find((m) => m.id === selectedId);
    const body = el('inspector-body');
    if (!mod) {
        body.innerHTML = `
            <h2 style="margin:0 0 8px;font-size:16px">Dolap ayarları</h2>
            <div class="row2">
                <div class="field"><label>Genişlik (cm)</label><input id="cab-w" type="number" min="40" max="800" value="${state.width}"></div>
                <div class="field"><label>Yükseklik (cm)</label><input id="cab-h" type="number" min="80" max="320" value="${state.height}"></div>
            </div>
            <div class="row2">
                <div class="field"><label>Derinlik (cm)</label><input id="cab-d" type="number" min="30" max="80" value="${state.depth}"></div>
                <div class="field"><label>Yerleşim</label>
                    <select id="cab-layout"><option value="straight" ${state.layout === 'straight' ? 'selected' : ''}>Düz</option><option value="corner" ${state.layout === 'corner' ? 'selected' : ''}>L köşe</option></select>
                </div>
            </div>
            ${pro ? `<div class="field"><label>Baza (cm)</label><input id="cab-plinth" type="number" min="0" max="20" value="${state.plinth}"></div>` : ''}
            <div class="field"><label>Gövde rengi</label>${colorDots(state.carcassId, 'carcass')}</div>
            <div class="field"><label>Kapak rengi</label>${colorDots(state.doorColorId, 'doorcolor')}</div>
            <div class="field"><label>İç renk</label>${colorDots(state.interiorId, 'interior')}</div>
            <button type="button" class="btn" id="apply-all">Tüm dolaba uygula</button>
            <h3 style="font-size:13px;margin:16px 0 8px">Otomatik modüllere böl</h3>
            <div class="cats">
                <button type="button" class="btn" data-split="equal">Eşit</button>
                <button type="button" class="btn" data-split="rail">Askılık</button>
                <button type="button" class="btn" data-split="shelf">Raf</button>
                <button type="button" class="btn" data-split="drawer">Çekmece</button>
            </div>
            <h3 style="font-size:13px;margin:16px 0 8px">Tasarım asistanı</h3>
            <div class="field"><label>Kim kullanacak?</label><select id="who"><option value="tek">Tek kişi</option><option value="cift">Çift</option><option value="cocuk">Çocuk</option><option value="ortak">Ortak</option></select></div>
            <div class="field"><label>Öncelik</label><select id="priority"><option value="rail">Daha fazla askılık</option><option value="shelf">Daha fazla raf</option><option value="drawer">Daha fazla çekmece</option><option value="balance">Dengeli</option><option value="shoe">Ayakkabı ve çanta</option></select></div>
            <div class="field"><label>Kapak</label><select id="pref-door">${DOOR_TYPES.map((door) => `<option value="${door.id}">${door.name}</option>`).join('')}</select></div>
            <button type="button" class="btn-primary" id="apply-assistant">Öneriyi uygula</button>
            <button type="button" class="btn" id="clear-all" style="margin-top:8px">Tasarımı sil</button>
            ${pro ? `<div class="parts"><strong>Parça listesi</strong><ul>${cutList().map((row) => `<li>${row}</li>`).join('') || '<li>Modül yok</li>'}</ul></div>` : ''}
        `;
        bindCabinetInputs(body);
        return;
    }
    const def = catalogByType(mod.type);
    body.innerHTML = `
        <button type="button" class="btn-ghost" id="clear-selection">← Dolap ayarları</button>
        <h2 style="margin:8px 0;font-size:16px">${def.name}</h2>
        <div class="field"><label>Genişlik (cm)</label><input id="mod-w" type="number" min="30" max="140" value="${mod.width}"></div>
        ${pro ? `<div class="row2"><div class="field"><label>Yükseklik</label><input id="mod-h" type="number" min="40" max="320" value="${moduleHeight(mod)}"></div><div class="field"><label>Derinlik</label><input id="mod-d" type="number" min="30" max="80" value="${moduleDepth(mod)}"></div></div>` : ''}
        ${['tam-rafli', 'acik-nis', 'vitrin', 'kose', 'ayakkabilik', 'raf-cekmece', 'canta'].includes(mod.type) ? `<div class="field"><label>Raf sayısı</label><input id="mod-shelves" type="number" min="2" max="10" value="${mod.shelves}"></div>` : ''}
        ${['cekmeceli', 'askilik-cekmece', 'raf-cekmece'].includes(mod.type) ? `<div class="field"><label>Çekmece sayısı</label><input id="mod-drawers" type="number" min="2" max="6" value="${mod.drawers}"></div>` : ''}
        ${mod.type === 'tek-askilik' ? `<label class="hint"><input id="mod-top" type="checkbox" ${mod.topShelf ? 'checked' : ''}> Üst raf</label><label class="hint"><input id="mod-bottom" type="checkbox" ${mod.bottomShelf ? 'checked' : ''}> Alt raf</label>` : ''}
        ${mod.type === 'ayakkabilik' ? `<label class="hint"><input id="mod-tilt" type="checkbox" ${mod.tilted ? 'checked' : ''}> Eğimli raf</label>` : ''}
        ${mod.type === 'vitrin' || mod.type === 'acik-nis' ? `<label class="hint"><input id="mod-led" type="checkbox" ${mod.led ? 'checked' : ''}> LED</label>` : ''}
        ${mod.type === 'canta' ? `<label class="hint"><input id="mod-glass" type="checkbox" ${mod.glassShelf ? 'checked' : ''}> Cam raf</label>` : ''}
        <div class="field"><label>Kapak</label><select id="mod-door">${DOOR_TYPES.map((door) => `<option value="${door.id}" ${door.id === mod.door ? 'selected' : ''}>${door.name}</option>`).join('')}</select></div>
        <div class="field"><label>Menteşe</label><select id="mod-hinge"><option value="left" ${mod.hinge === 'left' ? 'selected' : ''}>Sol</option><option value="right" ${mod.hinge === 'right' ? 'selected' : ''}>Sağ</option><option value="pair" ${mod.hinge === 'pair' ? 'selected' : ''}>Çift</option></select></div>
        <div class="field"><label>Gövde</label>${colorDots(mod.carcassId || state.carcassId, 'mcarcass')}</div>
        <div class="field"><label>Kapak rengi</label>${colorDots(mod.doorColorId || state.doorColorId, 'mdoor')}</div>
        ${mod.type === 'ozel' ? `<div class="cats"><button type="button" class="btn" data-zone="shelf">Raf ekle</button><button type="button" class="btn" data-zone="rail">Askı ekle</button><button type="button" class="btn" data-zone="drawer">Çekmece ekle</button><button type="button" class="btn" id="zone-pop">Son parçayı sil</button></div>` : ''}
        <div class="cats">
            <button type="button" class="btn" id="mod-left">Sola al</button>
            <button type="button" class="btn" id="mod-right">Sağa al</button>
            <button type="button" class="btn" id="mod-open">Aç / kapat</button>
            <button type="button" class="btn" id="mod-copy">Çoğalt</button>
            <button type="button" class="btn" id="mod-focus">Odakla</button>
            <button type="button" class="btn" id="mod-remove">Kaldır</button>
        </div>
    `;
    const read = () => updateModule(mod.id, {
        width: Number(body.querySelector('#mod-w').value),
        height: body.querySelector('#mod-h') ? Number(body.querySelector('#mod-h').value) : mod.height,
        depth: body.querySelector('#mod-d') ? Number(body.querySelector('#mod-d').value) : mod.depth,
        shelves: body.querySelector('#mod-shelves') ? Number(body.querySelector('#mod-shelves').value) : mod.shelves,
        drawers: body.querySelector('#mod-drawers') ? Number(body.querySelector('#mod-drawers').value) : mod.drawers,
        topShelf: body.querySelector('#mod-top') ? body.querySelector('#mod-top').checked : mod.topShelf,
        bottomShelf: body.querySelector('#mod-bottom') ? body.querySelector('#mod-bottom').checked : mod.bottomShelf,
        tilted: body.querySelector('#mod-tilt') ? body.querySelector('#mod-tilt').checked : mod.tilted,
        led: body.querySelector('#mod-led') ? body.querySelector('#mod-led').checked : mod.led,
        glassShelf: body.querySelector('#mod-glass') ? body.querySelector('#mod-glass').checked : mod.glassShelf,
        door: body.querySelector('#mod-door').value,
        hinge: body.querySelector('#mod-hinge').value,
    });
    body.querySelectorAll('input, select').forEach((input) => {
        if (input.dataset.mcarcass || input.dataset.mdoor) return;
        input.addEventListener('change', read);
    });
    body.querySelectorAll('[data-mcarcass]').forEach((button) => { button.onclick = () => updateModule(mod.id, { carcassId: button.dataset.mcarcass }); });
    body.querySelectorAll('[data-mdoor]').forEach((button) => { button.onclick = () => updateModule(mod.id, { doorColorId: button.dataset.mdoor }); });
    body.querySelector('#clear-selection').onclick = () => { selectedId = null; render(); };
    body.querySelector('#mod-left').onclick = () => moveModule(mod.id, -1);
    body.querySelector('#mod-right').onclick = () => moveModule(mod.id, 1);
    body.querySelector('#mod-copy').onclick = () => duplicateModule(mod.id);
    body.querySelector('#mod-remove').onclick = () => removeModule(mod.id);
    body.querySelector('#mod-open').onclick = () => updateModule(mod.id, { open: mod.open > 0.5 ? 0 : 1 });
    body.querySelector('#mod-focus').onclick = () => frameCamera('ic');
    body.querySelectorAll('[data-zone]').forEach((button) => {
        button.onclick = () => updateModule(mod.id, { zones: [...mod.zones, { kind: button.dataset.zone }] });
    });
    const pop = body.querySelector('#zone-pop');
    if (pop) pop.onclick = () => updateModule(mod.id, { zones: mod.zones.slice(0, -1) });
}

function bindCabinetInputs(body) {
    const applyCab = () => commit(() => {
        state.width = clamp(Number(body.querySelector('#cab-w').value), 40, 800);
        state.height = clamp(Number(body.querySelector('#cab-h').value), 80, 320);
        state.depth = clamp(Number(body.querySelector('#cab-d').value), 30, 80);
        state.layout = body.querySelector('#cab-layout').value;
        if (body.querySelector('#cab-plinth')) state.plinth = clamp(Number(body.querySelector('#cab-plinth').value), 0, 20);
    });
    body.querySelectorAll('#cab-w,#cab-h,#cab-d,#cab-layout,#cab-plinth').forEach((input) => input.addEventListener('change', applyCab));
    body.querySelectorAll('[data-carcass]').forEach((button) => { button.onclick = () => commit(() => { state.carcassId = button.dataset.carcass; }); });
    body.querySelectorAll('[data-doorcolor]').forEach((button) => { button.onclick = () => commit(() => { state.doorColorId = button.dataset.doorcolor; }); });
    body.querySelectorAll('[data-interior]').forEach((button) => { button.onclick = () => commit(() => { state.interiorId = button.dataset.interior; }); });
    body.querySelector('#apply-all').onclick = () => commit(() => {
        state.modules.forEach((mod) => { mod.carcassId = null; mod.doorColorId = null; mod.door = body.querySelector('#pref-door') ? mod.door : mod.door; });
    });
    body.querySelectorAll('[data-split]').forEach((button) => { button.onclick = () => autoSplit(button.dataset.split); });
    body.querySelector('#apply-assistant').onclick = () => applyAssistant(body.querySelector('#who').value, body.querySelector('#priority').value, body.querySelector('#pref-door').value);
    body.querySelector('#clear-all').onclick = () => { if (confirm('Tasarım silinsin mi?')) commit(() => { state.modules = []; }); };
}

function autoSplit(kind) {
    const types = { equal: 'tam-rafli', rail: 'tek-askilik', shelf: 'tam-rafli', drawer: 'cekmeceli' };
    const type = types[kind];
    const sample = catalogByType(type).width;
    let count = Math.max(1, Math.round(state.width / sample));
    while (count > 1 && state.width / count < 35) count -= 1;
    const width = Math.round((state.width / count) * 10) / 10;
    const modules = [];
    let used = 0;
    for (let i = 0; i < count; i += 1) {
        const mod = createModule(type);
        mod.width = i === count - 1 ? Math.round((state.width - used) * 10) / 10 : width;
        used += mod.width;
        modules.push(mod);
    }
    commit(() => { state.modules = modules; });
    toast('Bölümleme uygulandı. Modülleri tek tek değiştirebilirsiniz.');
}

function applyAssistant(who, priority, door) {
    const map = { rail: 'rail', shelf: 'shelf', drawer: 'drawer', shoe: 'shelf', balance: 'equal' };
    if (who === 'cift') state.width = Math.max(state.width, 300);
    if (who === 'cocuk') state.height = Math.min(state.height, 200);
    autoSplit(map[priority] || 'equal');
    commit(() => {
        state.modules.forEach((mod) => { mod.door = door; });
        if (priority === 'shoe') {
            const shoe = createModule('ayakkabilik');
            const bag = createModule('canta');
            if (state.modules.length) state.modules.splice(-1, 1, shoe, bag);
        }
    });
}

function renderSummary() {
    const overhead = topModules().reduce((max, mod) => Math.max(max, moduleHeight(mod)), 0);
    const totalH = state.height + overhead;
    el('summary').innerHTML = `Ölçü <b>${state.width} × ${totalH} × ${state.depth} cm</b> · Modül <b>${state.modules.length}</b>`;
    const notes = warnings();
    el('warn').classList.toggle('hidden', notes.length === 0);
    el('warn').textContent = notes.join(' ');
    el('project-name').value = state.name;
    el('btn-undo').disabled = history.length === 0;
    el('btn-redo').disabled = future.length === 0;
    el('btn-mode').textContent = pro ? 'Profesyonel mod' : 'Kolay mod';
}

function render() {
    renderLibrary();
    renderInspector();
    renderSummary();
    if (cabinetRoot) rebuild();
}

function saves() {
    try { return JSON.parse(localStorage.getItem(SAVE_KEY) || '[]'); } catch { return []; }
}

function saveDesign() {
    const list = saves().filter((item) => item.name !== state.name);
    list.unshift({ name: state.name, savedAt: new Date().toISOString(), state });
    localStorage.setItem(SAVE_KEY, JSON.stringify(list.slice(0, 20)));
    toast('Tasarım bu tarayıcıya kaydedildi.');
}

function shareDesign() {
    const hash = btoa(unescape(encodeURIComponent(JSON.stringify(state))));
    const url = `${location.origin}${location.pathname}#${hash}`;
    navigator.clipboard.writeText(url).then(() => toast('Paylaşım bağlantısı kopyalandı.')).catch(() => toast(url));
}

function loadHash() {
    const hash = location.hash.slice(1);
    if (!hash) return false;
    try {
        state = JSON.parse(decodeURIComponent(escape(atob(hash))));
        return true;
    } catch {
        return false;
    }
}

function showTemplates() {
    const box = el('template-list');
    box.classList.remove('hidden');
    el('saved-list').classList.add('hidden');
    box.innerHTML = `
        <div class="choices">
            <button type="button" class="choice" data-template="balance"><strong>Dengeli</strong><span>Askı, raf ve çekmece.</span></button>
            <button type="button" class="choice" data-template="rail"><strong>Askılık ağırlıklı</strong><span>Geniş askı modülleri.</span></button>
            <button type="button" class="choice" data-template="shelf"><strong>Raf ağırlıklı</strong><span>Eşit raflı gözler.</span></button>
            <button type="button" class="choice" data-template="drawer"><strong>Çekmece ağırlıklı</strong><span>Çekmece sütunları.</span></button>
        </div>`;
    box.querySelectorAll('[data-template]').forEach((button) => {
        button.onclick = () => {
            state = blankState();
            autoSplit(button.dataset.template === 'balance' ? 'equal' : button.dataset.template);
            if (button.dataset.template === 'balance') {
                state.modules = [createModule('tek-askilik'), createModule('tam-rafli'), createModule('cekmeceli'), createModule('ayakkabilik')];
                const each = Math.round((state.width / state.modules.length) * 10) / 10;
                state.modules.forEach((mod, index) => { mod.width = index === state.modules.length - 1 ? state.width - each * (state.modules.length - 1) : each; });
            }
            begin();
        };
    });
}

function showSaved() {
    const box = el('saved-list');
    box.classList.remove('hidden');
    el('template-list').classList.add('hidden');
    const list = saves();
    box.innerHTML = list.length ? list.map((item, index) => `<button type="button" class="choice" data-saved="${index}" style="width:100%;margin-bottom:8px"><strong>${item.name}</strong><span>${new Date(item.savedAt).toLocaleString('tr-TR')}</span></button>`).join('') : '<p class="hint">Kayıtlı tasarım yok.</p>';
    box.querySelectorAll('[data-saved]').forEach((button) => {
        button.onclick = () => {
            state = list[Number(button.dataset.saved)].state;
            begin();
        };
    });
}

function begin() {
    el('welcome').classList.add('hidden');
    history = [];
    future = [];
    render();
    frameCamera('perspektif');
    if (!localStorage.getItem(INTRO_KEY)) {
        let step = 0;
        const intro = el('intro');
        const paint = () => {
            el('intro-title').textContent = `${step + 1} / 3`;
            el('intro-body').textContent = `${INTRO[step][0]} ${INTRO[step][1]}`;
            el('intro-next').textContent = step === 2 ? 'Başla' : 'Devam';
        };
        intro.classList.remove('hidden');
        paint();
        el('intro-next').onclick = () => {
            step += 1;
            if (step > 2) { intro.classList.add('hidden'); localStorage.setItem(INTRO_KEY, '1'); return; }
            paint();
        };
        el('intro-skip').onclick = () => { intro.classList.add('hidden'); localStorage.setItem(INTRO_KEY, '1'); };
    }
}

function bindChrome() {
    el('module-search').addEventListener('input', (event) => { search = event.target.value; renderLibrary(); });
    el('project-name').addEventListener('change', (event) => { state.name = event.target.value || 'Dolabım'; });
    el('btn-undo').onclick = undo;
    el('btn-redo').onclick = redo;
    el('btn-mode').onclick = () => { pro = !pro; renderInspector(); renderSummary(); };
    el('btn-save').onclick = saveDesign;
    el('btn-share').onclick = shareDesign;
    el('btn-shot').onclick = () => {
        renderer.render(scene, camera);
        const link = document.createElement('a');
        link.href = renderer.domElement.toDataURL('image/png');
        link.download = `${state.name || 'dolap'}.png`;
        link.click();
    };
    el('btn-quote').onclick = () => el('quote-modal').classList.remove('hidden');
    el('quote-close').onclick = () => el('quote-modal').classList.add('hidden');
    el('quote-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(event.target);
        const msg = el('quote-msg');
        msg.textContent = 'Gönderiliyor…';
        try {
            const response = await fetch(window.WARDROBE.quoteUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.WARDROBE.csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ name: form.get('name'), phone: form.get('phone'), note: form.get('note'), design: state }),
            });
            const data = await response.json();
            msg.textContent = data.message || 'Gönderildi.';
            if (data.ok) toast('Teklif talebi alındı.');
        } catch {
            msg.textContent = 'Bağlantı kurulamadı. Tasarımı kaydedip daha sonra deneyin.';
        }
    });
    el('start-empty').onclick = () => { state = blankState(); begin(); };
    el('start-template').onclick = showTemplates;
    el('start-saved').onclick = showSaved;
    el('open-library').onclick = () => el('library').classList.toggle('is-open');
    el('open-inspector').onclick = () => el('inspector').classList.toggle('is-open');
    el('view-bar').innerHTML = [
        ...VIEWS.map(([id, label]) => `<button type="button" class="btn" data-view="${id}">${label}</button>`),
        '<button type="button" class="btn" data-doors="show">Kapaklar</button>',
        '<button type="button" class="btn" data-doors="hide">Kapakları gizle</button>',
        '<button type="button" class="btn" data-doors="ghost">Şeffaf kapak</button>',
        '<button type="button" class="btn" data-doors="open">Kapakları aç</button>',
        '<button type="button" class="btn" id="show-all">Tüm dolabı göster</button>',
    ].join('');
    el('view-bar').querySelectorAll('[data-view]').forEach((button) => { button.onclick = () => frameCamera(button.dataset.view); });
    el('view-bar').querySelectorAll('[data-doors]').forEach((button) => {
        button.onclick = () => { viewMode = button.dataset.doors; rebuild(); };
    });
    el('show-all').onclick = () => frameCamera('perspektif');
}

init3d();
bindChrome();
if (loadHash()) begin();
else renderLibrary();
