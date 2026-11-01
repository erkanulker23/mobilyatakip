import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const T = 1.8;
const BACK = 0.8;
const DOOR_T = 1.8;
const H_MIN = 160;
const H_MAX = 270;
const ALUMINUM = [
    { id: 'al-gumus', name: 'Gümüş', color: '#d5d8dc' },
    { id: 'al-inox', name: 'Inox', color: '#b7bcc2' },
    { id: 'al-beyaz', name: 'Beyaz', color: '#f3f3f1' },
    { id: 'al-antrasit', name: 'Antrasit', color: '#4a4e52' },
    { id: 'al-siyah', name: 'Mat siyah', color: '#2a2a2a' },
    { id: 'al-bronz', name: 'Bronz', color: '#8d6b45' },
    { id: 'al-sampanya', name: 'Şampanya', color: '#d9c6a6' },
    { id: 'al-gold', name: 'Gold', color: '#c6a56a' },
];
const GLASS = [
    { id: 'cam-seffaf', name: 'Şeffaf', color: '#e7eef2', transmission: 0.86 },
    { id: 'cam-fum', name: 'Füme', color: '#6e6a66', transmission: 0.88 },
    { id: 'cam-bronz', name: 'Bronz', color: '#a56b3c', transmission: 0.42 },
    { id: 'cam-gri', name: 'Gri', color: '#9aa1a6', transmission: 0.5 },
    { id: 'cam-antrasit', name: 'Antrasit', color: '#3e4246', transmission: 0.28 },
    { id: 'cam-mavi', name: 'Mavi', color: '#7f9aaf', transmission: 0.48 },
    { id: 'cam-yesil', name: 'Yeşil', color: '#7d947c', transmission: 0.48 },
    { id: 'cam-siyah', name: 'Siyah', color: '#2a2a2a', transmission: 0.28 },
];
const PLINTH = 8;
const WALL_X = 520;
const WALL_Z = 400;
const ROOM_H = 280;
const SAVE_KEY = 'meemare-wardrobe-saves';

const SERIES = [
    { id: 'duz', name: 'Düz kapak' },
    { id: 'cam', name: 'Camlı' },
    { id: 'karisik', name: 'Karışık' },
    { id: 'akordeon', name: 'Akordeon' },
];

const PRODUCTS = [
    { id: 'tek-raf', kind: 'wardrobe', width: 45, name: 'Tek Kapılı Raflı', layout: 'shelves', shelves: 5, rail: false, drawers: 0, spec: '5 raf', photo: 'tek-raf.jpg' },
    { id: 'cift-raf-pantolon', kind: 'wardrobe', width: 90, name: 'İki Kapılı Raflı Pantolonluk', layout: 'raf-pantolon', shelves: 3, rail: true, drawers: 0, spec: 'Raf ve pantolonluk', photo: 'cift-raf-pantolon.jpg' },
    { id: 'cift-cekmece', kind: 'wardrobe', width: 90, name: 'İki Kapılı Çekmeceli', layout: 'cekmece', shelves: 2, rail: true, drawers: 3, spec: 'Askı, raf, 3 çekmece', photo: 'cift-cekmece.jpg' },
    { id: 'cift-pantolon', kind: 'wardrobe', width: 90, name: 'İki Kapılı Pantolonluk', layout: 'pantolon', shelves: 2, rail: false, drawers: 0, spec: 'Pantolonluk', photo: 'cift-pantolon.jpg' },
    { id: 'cift-raf', kind: 'wardrobe', width: 90, name: 'İki Kapılı Komple Raflı', layout: 'shelves', shelves: 5, rail: false, drawers: 0, spec: 'Komple raf', photo: 'cift-raf.jpg' },
    { id: 'tek-boy', kind: 'wardrobe', width: 45, name: 'Tek Kapılı Boy Askılık', layout: 'boy-aski', shelves: 2, rail: true, drawers: 0, spec: 'Boy askılık', photo: 'tek-boy.jpg' },
    { id: 'cift-aski', kind: 'wardrobe', width: 90, name: 'İki Kapılı Çift Askılık', layout: 'cift-aski', shelves: 3, rail: true, drawers: 0, spec: 'Üst ve alt askı', photo: 'cift-aski.jpg' },
    { id: 'kose-raf', kind: 'corner', width: 90, name: 'Raflı Köşe', layout: 'shelves', shelves: 4, rail: false, drawers: 0, spec: 'Köşe rafları', photo: 'kose-raf.jpg' },
    { id: 'cift-ayakkabi', kind: 'wardrobe', width: 90, name: 'İki Kapılı Ayakkabılıklı Raf', layout: 'ayakkabi', shelves: 2, rail: true, drawers: 0, spec: 'Ayakkabılık, raf, askı', photo: 'cift-ayakkabi.jpg' },
    { id: 'cift-panjur', kind: 'wardrobe', width: 90, name: 'İki Kapılı Panjur', door: 'panjur', layout: 'cift-aski', shelves: 3, rail: true, drawers: 0, spec: 'Panjur, çift askı', photo: 'cift-panjur.jpg' },
    { id: 'tek-panjur', kind: 'wardrobe', width: 45, name: 'Tek Kapılı Panjur', door: 'panjur', layout: 'shelves', shelves: 5, rail: false, drawers: 0, spec: 'Panjur, raf' },
    { id: 'end', kind: 'end', width: 2, name: 'Yan Kapama', shelves: 0, rail: false, drawers: 0, spec: 'Bitiş paneli' },
];

const SETS = [
    { id: 'giyinme', name: 'Giyinme', parts: ['tek-boy', 'tek-raf', 'cift-aski'], spec: 'Askı, raf, çift askı', photo: 'set-giyinme.jpg' },
    { id: 'cekmece', name: 'Çekmeceli takım', parts: ['cift-cekmece', 'tek-raf'], spec: 'Çekmece ve raf', photo: 'cift-cekmece.jpg' },
    { id: 'kose-set', name: 'Köşeli takım', parts: ['kose-raf', 'cift-raf-pantolon'], spec: 'Köşe ve pantolonluk', photo: 'set-kose.jpg' },
];

const el = (id) => document.getElementById(id);
const uid = () => Math.random().toString(36).slice(2, 10);
const clamp = (n, a, b) => Math.min(b, Math.max(a, n));
const productById = (id) => PRODUCTS.find((item) => item.id === id);
let materials = [];
let projectToken = null;
let saveTimer = 0;

let state = blankState();
let history = [];
let future = [];
let selectedId = null;
let toastTimer = 0;
let heightDrag = null;

let renderer;
let keyLight;
let cavityLight;
let scene;
let camera;
let controls;
let cabinetRoot;
const room = { floor: null, back: null };
let clock;
let thumbRenderer;
let thumbScene;
let thumbCamera;
const doorPivots = [];
const slides = [];
const matCache = new Map();
const thumbCache = new Map();
const texCache = new Map();
let thumbTimer = 0;
const raycaster = new THREE.Raycaster();
const pointer = new THREE.Vector2();
let pointerDown = null;
let pointerPress = null;
let catalogDrag = null;
let dropGuide = null;
let catalogBound = false;
const glbTemplates = new Map();
const glbFailed = new Set();
let glbCatalog = [];
let showMeasures = false;
let lightsOn = true;
let hideAllDoors = false;
let addDoor = 'solid';
let addInteriorKind = 'tek-aski';
let addWidth = 60;
let dxfModules = [];
let cadModules = [];
let moduleDrag = null;
let dragId = null;
let dragGhost = null;
const hiddenDoorIds = new Set();
const measureNodes = [];
const measureMat = new THREE.LineBasicMaterial({ color: 0x1f6b4a });

function blankState() {
    return {
        name: 'Dolabım',
        customerName: '',
        phone: '',
        note: '',
        series: 'duz',
        height: 250,
        depth: 60,
        carcassId: '',
        doorColorId: '',
        interiorId: '',
        backId: '',
        shelfId: '',
        aluminumId: 'al-gumus',
        glassId: 'cam-fum',
        plinth: true,
        plinthHidden: false,
        kulp: true,
        led: true,
        wallWidth: 300,
        leftWall: 300,
        rightWall: 300,
        roomHeight: 270,
        modules: [],
    };
}

function doorFor(product, series = state.series) {
    if (product.kind === 'end') return 'none';
    if (product.door === 'panjur') return 'panjur';
    if (product.kind === 'accordion') return 'accordion';
    if (series === 'cam') return 'glass';
    if (series === 'karisik') return 'mixed';
    if (series === 'akordeon') return 'accordion';
    return 'solid';
}

function doorCount(mod) {
    return mod.width < 55 ? 1 : 2;
}

function swingOf(mod) {
    if (mod?.swing === 'left' || mod?.swing === 'right' || mod?.swing === 'pair') return mod.swing;
    return doorCount(mod) === 1 ? 'left' : 'pair';
}

function hingeFor(mod, index, count) {
    const swing = swingOf(mod);
    if (swing === 'right') return 'right';
    if (swing === 'left') return 'left';
    return count === 1 || index === 0 ? 'left' : 'right';
}

function seriesOf(mod) {
    if (!mod || mod.kind === 'end' || mod.door === 'panjur' || mod.door === 'none') return state.series;
    if (mod.door === 'glass') return 'cam';
    if (mod.door === 'mixed') return 'karisik';
    if (mod.door === 'accordion') return 'akordeon';
    return 'duz';
}

function doorLabel(mod) {
    if (mod.door === 'panjur') return 'Panjur';
    if (mod.door === 'glass') return 'Camlı';
    if (mod.door === 'mixed') return 'Karışık';
    if (mod.door === 'accordion') return 'Akordeon';
    if (mod.door === 'none') return '';
    return 'Düz kapak';
}

function swingLabel(mod) {
    if (!mod || mod.kind === 'end' || mod.door === 'none' || mod.door === 'panjur' || mod.door === 'accordion') return '';
    const swing = swingOf(mod);
    if (swing === 'right') return 'Sağa açılır';
    if (swing === 'pair') return 'Karşılıklı açılır';
    return 'Sola açılır';
}

function createModule(productId) {
    const product = productById(productId);
    return {
        id: uid(),
        product: product.id,
        kind: product.kind,
        name: product.name,
        width: product.width,
        door: doorFor(product),
        shelves: product.shelves,
        rail: product.rail,
        drawers: product.drawers,
        layout: product.layout || '',
        spec: product.spec,
        open: 0,
        carcassId: state.carcassId,
        doorColorId: state.doorColorId,
        interiorId: state.interiorId,
        backId: state.backId || state.interiorId,
        shelfId: state.shelfId || state.interiorId,
        drawerColorId: state.doorColorId || solidDoorColor(),
        aluminumId: state.aluminumId,
        glassId: state.glassId,
        height: state.height,
        plinth: state.plinth !== false,
        plinthHidden: state.plinthHidden === true,
        upper: 0,
        kulp: state.kulp !== false,
        led: state.led !== false,
        ledStyle: 'shelf',
    };
}

function hasKulp(mod) {
    return mod?.kulp !== false;
}

function plinthOf(mod) {
    return mod?.plinth === false ? 0 : PLINTH;
}

function moduleHeight(mod) {
    if (mod?.glbHeight) return mod.glbHeight;
    const raw = Number(mod?.height) || state.height;
    const floor = mod?.cad === '11_ust_dolap' || mod?.dxf === 'ust' || raw < H_MIN ? 40 : H_MIN;
    return clamp(raw, floor, H_MAX);
}

function dxfById(id) {
    return dxfModules.find((item) => item.id === id) || null;
}

function cadById(id) {
    return cadModules.find((item) => item.id === id) || null;
}

function aluminumOf(mod) {
    return ALUMINUM.find((item) => item.id === (mod?.aluminumId || state.aluminumId)) || ALUMINUM[0];
}

function glassOf(mod) {
    return GLASS.find((item) => item.id === (mod?.glassId || state.glassId)) || GLASS[1];
}

function specOf(mod) {
    return mod.spec || [mod.rail ? 'askı' : '', mod.shelves ? `${mod.shelves} raf` : '', mod.drawers ? `${mod.drawers} çekmece` : ''].filter(Boolean).join(', ') || 'Hazır gövde';
}

function upperHeight(mod) {
    const height = Number(mod?.upper);
    return Number.isFinite(height) && height >= 30 ? Math.min(90, height) : 0;
}

function footprint(mod) {
    const h = moduleHeight(mod) + upperHeight(mod);
    if (mod.glb && mod.kind !== 'corner' && mod.kind !== 'end') return { w: mod.width, d: mod.glbDepth || 60, h };
    if (mod.kind === 'corner') return { w: mod.width, d: mod.width, h };
    if (mod.kind === 'end') return { w: 2, d: state.depth, h };
    return { w: mod.width, d: state.depth, h };
}

function wallLimit() {
    const width = Number(state.wallWidth);
    return Number.isFinite(width) && width >= 80 ? Math.min(800, width) : WALL_X;
}

function sideLimit(side) {
    const width = Number(side === 'left' ? state.leftWall : state.rightWall);
    return Number.isFinite(width) && width >= 80 ? Math.min(800, width) : 300;
}

function roomHeightLimit() {
    const height = Number(state.roomHeight);
    return Number.isFinite(height) && height >= 160 ? Math.min(400, height) : 270;
}

function roomDepthLimit() {
    return Math.max(sideLimit('left'), sideLimit('right'));
}

function alongSize(mod) {
    if (mod.kind === 'end') return 2;
    return Math.max(30, Number(mod.width) || 45);
}

function wallName(mod) {
    if (mod.kind === 'corner') return 'front';
    if (mod.wall === 'left' || mod.wall === 'right') return mod.wall;
    return 'front';
}

function ensurePlacement(modules) {
    const cursor = { left: 0, front: 0, right: 0 };
    modules.forEach((mod) => {
        if (mod.kind === 'corner' && mod.cornerSide !== 'left') mod.cornerSide = 'right';
        if (mod.wall !== 'left' && mod.wall !== 'right') mod.wall = 'front';
        if (!Number.isFinite(Number(mod.offset))) {
            const wall = wallName(mod);
            mod.offset = cursor[wall];
            cursor[wall] += alongSize(mod);
        }
    });
}

function layout(modules) {
    ensurePlacement(modules);
    const front = wallLimit();
    const leftLen = sideLimit('left');
    const rightLen = sideLimit('right');
    const leftCorner = modules.find((mod) => mod.kind === 'corner' && mod.cornerSide === 'left');
    const rightCorner = modules.find((mod) => mod.kind === 'corner' && mod.cornerSide !== 'left');
    if (modules.filter((mod) => mod.kind === 'corner' && mod.cornerSide === 'left').length > 1) return null;
    if (modules.filter((mod) => mod.kind === 'corner' && mod.cornerSide !== 'left').length > 1) return null;
    const leftHold = leftCorner ? alongSize(leftCorner) : 0;
    const rightHold = rightCorner ? alongSize(rightCorner) : 0;
    if (leftHold + rightHold > front + 0.05) return null;
    const spots = [];
    if (leftCorner) spots.push({ mod: leftCorner, x: 0, z: 0, rot: 0 });
    if (rightCorner) spots.push({ mod: rightCorner, x: front - rightHold, z: 0, rot: 0 });
    const pack = (wall, begin, limit) => {
        const list = modules
            .filter((mod) => mod.kind !== 'corner' && wallName(mod) === wall)
            .sort((a, b) => Number(a.offset) - Number(b.offset));
        const occupied = [];
        const fits = (at, along) => at >= begin - 0.1 && at + along <= limit + 0.05 && !occupied.some((seg) => at < seg.end - 0.4 && at + along > seg.start + 0.4);
        const placeOne = (mod) => {
            const along = alongSize(mod);
            const wanted = Number(mod.offset);
            let at = Number.isFinite(wanted) ? wanted : begin;
            at = Math.max(begin, Math.min(at, Math.max(begin, limit - along)));
            if (!fits(at, along)) {
                let best = null;
                for (let cursor = begin; cursor + along <= limit + 0.05; cursor += 1) {
                    if (!fits(cursor, along)) continue;
                    if (best === null || Math.abs(cursor - at) < Math.abs(best - at)) best = cursor;
                }
                if (best === null) return null;
                at = best;
            }
            const movable = mod.id === dragId || !state.modules.includes(mod);
            if (movable) {
                occupied.forEach((seg) => {
                    if (at >= seg.end && at - seg.end < 30 && fits(seg.end, along)) at = seg.end;
                    const before = seg.start - along;
                    if (at > begin && at + along <= seg.start && seg.start - (at + along) < 30 && fits(before, along)) at = before;
                });
                if (at - begin < 30 && fits(begin, along)) at = begin;
                mod.offset = at;
            }
            occupied.push({ start: at, end: at + along });
            if (wall === 'front') spots.push({ mod, x: at, z: 0, rot: 0 });
            else if (wall === 'left') spots.push({ mod, x: 0, z: at + along, rot: Math.PI / 2 });
            else spots.push({ mod, x: front, z: at, rot: -Math.PI / 2 });
            return occupied;
        };
        const dragged = list.find((mod) => mod.id === dragId);
        if (dragged && !placeOne(dragged)) return null;
        for (const mod of list) {
            if (mod === dragged) continue;
            if (!placeOne(mod)) return null;
        }
        return occupied;
    };
    const frontOcc = pack('front', leftHold, front - rightHold);
    if (!frontOcc) return null;
    const depth = Math.max(30, Number(state.depth) || 60);
    const covers = (segments, start, end) => segments.some((seg) => seg.start < end - 0.1 && seg.end > start + 0.1);
    const leftBegin = leftHold > 0 ? leftHold : (covers(frontOcc, 0, depth) ? depth : 0);
    const rightBegin = rightHold > 0 ? rightHold : (covers(frontOcc, front - depth, front) ? depth : 0);
    if (!pack('left', leftBegin, leftLen) || !pack('right', rightBegin, rightLen)) return null;
    return spots;
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
    scheduleSave();
}

function undo() {
    if (!history.length) return;
    future.push(JSON.stringify(state));
    state = JSON.parse(history.pop());
    if (!state.modules.some((mod) => mod.id === selectedId)) selectedId = null;
    render();
    scheduleSave();
}

function redo() {
    if (!future.length) return;
    history.push(JSON.stringify(state));
    state = JSON.parse(future.pop());
    if (!state.modules.some((mod) => mod.id === selectedId)) selectedId = null;
    render();
    scheduleSave();
}

function toast(text) {
    const node = el('toast');
    node.textContent = text;
    node.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => node.classList.add('hidden'), 2600);
}

function selected() {
    return state.modules.find((mod) => mod.id === selectedId) || null;
}

function decor(id) {
    return materials.find((item) => item.id === id) || materials[0] || { id: 'plain', name: 'Renk', code: '', image: '', finish_hint: 'matte', groupLabel: '' };
}

function decorExists(id) {
    return materials.some((item) => item.id === id);
}

function finishRoughness(finish) {
    if (finish === 'matte') return 0.72;
    if (finish === 'gloss') return 0.28;
    if (finish === 'highgloss') return 0.08;
    return 0.55;
}

function textureFor(url) {
    if (!url) return null;
    if (texCache.has(url)) return texCache.get(url);
    const tex = new THREE.TextureLoader().load(url, () => {
        clearTimeout(thumbTimer);
        thumbTimer = setTimeout(() => {
            thumbCache.clear();
            if (document.getElementById('library-body')) renderLibrary();
        }, 250);
    });
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
    tex.repeat.set(1, 1);
    tex.anisotropy = 8;
    texCache.set(url, tex);
    buildSurfaceMaps(url);
    return tex;
}

const surfaceMapCache = new Map();
const flatColorCache = new Map();

function loadFlatColor(url) {
    if (!url || flatColorCache.get(url)) return flatColorCache.get(url);
    if (flatColorCache.has(url)) return null;
    flatColorCache.set(url, null);
    const image = new Image();
    image.onload = () => {
        const size = 32;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(image, 0, 0, size, size);
        const data = ctx.getImageData(0, 0, size, size).data;
        let r = 0;
        let g = 0;
        let b = 0;
        const count = data.length / 4;
        for (let i = 0; i < data.length; i += 4) {
            r += data[i];
            g += data[i + 1];
            b += data[i + 2];
        }
        const color = new THREE.Color(r / count / 255, g / count / 255, b / count / 255);
        flatColorCache.set(url, color);
        const apply = (mat) => {
            if (!mat?.userData || mat.userData.flatUrl !== url) return;
            mat.color.copy(color);
            if (mat.userData.flatDark) mat.color.multiplyScalar(mat.userData.flatDark);
            mat.map = null;
            mat.normalMap = null;
            mat.roughnessMap = null;
            mat.roughness = mat.userData.flatDark ? 0.86 : 0.78;
            mat.needsUpdate = true;
        };
        matCache.forEach(apply);
        scene?.traverse((obj) => {
            if (!obj.isMesh) return;
            const list = Array.isArray(obj.material) ? obj.material : [obj.material];
            list.forEach(apply);
        });
    };
    image.src = url;
    return null;
}

function buildSurfaceMaps(url) {
    if (!url || surfaceMapCache.has(url)) return surfaceMapCache.get(url) || null;
    surfaceMapCache.set(url, null);
    const image = new Image();
    image.onload = () => {
        const size = 512;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(image, 0, 0, size, size);
        const src = ctx.getImageData(0, 0, size, size).data;
        const rough = new Uint8Array(size * size);
        const heightAt = (x, y) => {
            const i = ((y * size) + x) * 4;
            return src[i] * 0.25 + src[i + 1] * 0.65 + src[i + 2] * 0.1;
        };
        for (let y = 0; y < size; y += 1) {
            for (let x = 0; x < size; x += 1) {
                const h = heightAt(x, y);
                rough[(y * size) + x] = Math.round(Math.min(220, Math.max(160, 190 + (h - 150) * 0.12)));
            }
        }
        const roughness = new THREE.DataTexture(rough, size, size);
        roughness.colorSpace = THREE.NoColorSpace;
        roughness.wrapS = roughness.wrapT = THREE.RepeatWrapping;
        roughness.needsUpdate = true;
        const pack = { roughness, normal: null };
        surfaceMapCache.set(url, pack);
        matCache.forEach((mat) => {
            if (mat.userData.decorUrl !== url || mat.userData.finish === 'highgloss') return;
            mat.roughnessMap = roughness;
            mat.roughness = mat.userData.finish === 'gloss' ? 0.72 : 1;
            mat.normalMap = null;
            mat.needsUpdate = true;
        });
    };
    image.src = url;
    return null;
}

function material(key, source, opts = {}) {
    const plain = opts.metal || opts.glass || (typeof source === 'string' && source.startsWith('#'));
    const item = plain ? null : decor(source);
    const flat = Boolean(item && isPlainDecor(item));
    const id = `${key}|${item ? item.id : source}|${opts.glass ? 1 : 0}|${opts.metal ? 1 : 0}|${opts.transmission ?? ''}|${opts.attenuation ?? ''}`;
    if (matCache.has(id)) return matCache.get(id);
    // Volume attenuation (thickness > 0) makes three-gpu-pathtracer 0.0.23 return black
    // for a pane a few millimeters thick. Thickness 0 and an infinite attenuation
    // distance select its thin-film path, which tints by the base color and still transmits.
    const mat = opts.glass
        ? new THREE.MeshPhysicalMaterial({
            color: source,
            roughness: 0.3,
            metalness: 0,
            specularIntensity: 0.16,
            transmission: opts.transmission ?? 1,
            thickness: 0,
            transparent: false,
            opacity: 1,
            depthWrite: true,
            ior: 1.5,
            attenuationDistance: Infinity,
            envMapIntensity: 0.12,
        })
        : new THREE.MeshStandardMaterial({
            color: item ? '#ffffff' : source,
            map: item && !flat ? textureFor(item.image) : null,
            roughness: flat ? 0.78 : item ? (item.finish_hint === 'highgloss' ? 0.22 : item.finish_hint === 'gloss' ? 0.42 : 0.62) : (opts.metal ? 0.48 : 0.62),
            metalness: opts.metal ? 0.55 : (item?.finish_hint === 'highgloss' ? 0.04 : 0),
            specularIntensity: opts.metal ? 0.32 : 0.28,
            envMapIntensity: opts.metal ? 0.08 : 0.16,
            side: opts.glass ? THREE.FrontSide : THREE.DoubleSide,
        });
    if (flat && item.image) {
        mat.userData.flatUrl = item.image;
        const known = flatColorCache.get(item.image);
        if (known) mat.color.copy(known);
        else loadFlatColor(item.image);
    } else if (item?.image) {
        mat.userData.decorUrl = item.image;
        mat.userData.finish = item.finish_hint || 'matte';
        const pack = surfaceMapCache.get(item.image);
        if (pack && item.finish_hint !== 'highgloss') {
            mat.roughnessMap = pack.roughness;
            mat.roughness = item.finish_hint === 'gloss' ? 0.72 : 1;
            mat.normalMap = null;
        }
    }
    matCache.set(id, mat);
    return mat;
}

function panel(parent, w, h, d, x, y, z, mat) {
    if (w <= 0.2 || h <= 0.2 || d <= 0.2) return null;
    const geometry = new THREE.BoxGeometry(w, h, d);
    scaleBoxUv(geometry, w, h, d);
    const mesh = new THREE.Mesh(geometry, mat);
    mesh.position.set(x, y, z);
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    parent.add(mesh);
    return mesh;
}

function scaleBoxUv(geometry, w, h, d) {
    const uv = geometry.attributes.uv;
    if (!uv) return;
    const uScale = [d, d, w, w, w, w];
    const vScale = [h, h, d, d, h, h];
    for (let face = 0; face < 6; face += 1) {
        for (let i = 0; i < 4; i += 1) {
            const index = face * 4 + i;
            uv.setXY(index, uv.getX(index) * uScale[face] / 100, uv.getY(index) * vScale[face] / 100);
        }
    }
    uv.needsUpdate = true;
}

function assignPanelUv(geometry) {
    const position = geometry.attributes.position;
    const normal = geometry.attributes.normal;
    if (!position || !normal) return;
    const uv = new THREE.BufferAttribute(new Float32Array(position.count * 2), 2);
    for (let i = 0; i < position.count; i += 1) {
        const nx = Math.abs(normal.getX(i));
        const ny = Math.abs(normal.getY(i));
        const nz = Math.abs(normal.getZ(i));
        let u;
        let v;
        if (nx >= ny && nx >= nz) {
            u = position.getZ(i);
            v = position.getY(i);
        } else if (ny >= nx && ny >= nz) {
            u = position.getX(i);
            v = position.getZ(i);
        } else {
            u = position.getX(i);
            v = position.getY(i);
        }
        uv.setXY(i, u, v);
    }
    geometry.setAttribute('uv', uv);
}

function carcassOf(mod) {
    return mod?.carcassId || state.carcassId;
}

function doorOf(mod) {
    return mod?.doorColorId || state.doorColorId;
}

function interiorOf(mod) {
    return mod?.interiorId || state.interiorId;
}

function backOf(mod) {
    return (mod?.backId && decorExists(mod.backId) ? mod.backId : '') || state.backId || interiorOf(mod);
}

function shelfOf(mod) {
    return (mod?.shelfId && decorExists(mod.shelfId) ? mod.shelfId : '') || state.shelfId || interiorOf(mod);
}

function drawerOf(mod) {
    if (mod?.drawerColorId && decorExists(mod.drawerColorId)) return mod.drawerColorId;
    return solidDoorColor() || doorOf(mod);
}

function addCarcass(parent, w, h, d, mod) {
    const outside = material('outside', carcassOf(mod));
    const body = material('body', carcassOf(mod));
    const inner = material('inner', interiorOf(mod));
    const back = material('back', backOf(mod));
    const base = plinthOf(mod);
    const skin = 0.35;
    const hiddenKick = base > 0 && mod?.plinthHidden === true;
    const plinthMat = material('plinth', '#2a2927');
    const sideH = hiddenKick ? h - base : h;
    const sideY = hiddenKick ? base + sideH / 2 : h / 2;
    panel(parent, skin, sideH, d, skin / 2, sideY, d / 2, outside);
    panel(parent, skin, sideH, d, w - skin / 2, sideY, d / 2, outside);
    panel(parent, Math.max(0.4, T - skin), h - base - T, d - BACK, skin + (T - skin) / 2, base + (h - base - T) / 2, BACK + (d - BACK) / 2, body);
    panel(parent, Math.max(0.4, T - skin), h - base - T, d - BACK, w - T + (T - skin) / 2, base + (h - base - T) / 2, BACK + (d - BACK) / 2, body);
    panel(parent, w - 2 * T, T, d - BACK, w / 2, base + T / 2, BACK + (d - BACK) / 2, inner);
    panel(parent, w - 2 * skin, skin, d, w / 2, h - skin / 2, d / 2, outside);
    panel(parent, w - 2 * T, Math.max(0.4, T - skin), d - BACK, w / 2, h - T + (T - skin) / 2, BACK + (d - BACK) / 2, inner);
    panel(parent, w - 2 * T, h - base - 2 * T, BACK, w / 2, base + T + (h - base - 2 * T) / 2, BACK / 2, back);
    if (base > 0) {
        const kickD = hiddenKick ? Math.max(16, d - 14) : d - 1.2;
        const kickZ = hiddenKick ? kickD / 2 + 0.6 : d / 2;
        panel(parent, hiddenKick ? w - 2 * T : w - 1, base, kickD, w / 2, base / 2, kickZ, plinthMat);
    }
}

function shareCounts(weights, total) {
    const sum = weights.reduce((acc, value) => acc + value, 0) || 1;
    const raw = weights.map((value) => (total * value) / sum);
    const counts = raw.map((value) => Math.floor(value));
    let left = total - counts.reduce((acc, value) => acc + value, 0);
    raw.map((value, index) => ({ index, frac: value - Math.floor(value) }))
        .sort((a, b) => b.frac - a.frac)
        .forEach((item) => {
            if (left <= 0) return;
            counts[item.index] += 1;
            left -= 1;
        });
    return counts;
}

function pieceSpan(piece) {
    if (piece.x0 != null && piece.x1 != null) return { x0: piece.x0, x1: piece.x1 };
    if (piece.x != null) return { x0: Math.max(0, piece.x - 0.02), x1: Math.min(1, piece.x + 0.02) };
    return { x0: 0, x1: 1 };
}

function spansOverlap(a, b) {
    return Math.min(a.x1, b.x1) - Math.max(a.x0, b.x0) > 0.04;
}

function columnKey(piece) {
    const span = pieceSpan(piece);
    return `${span.x0.toFixed(3)}|${span.x1.toFixed(3)}`;
}

function blocksStack(pieces, sample, low, high) {
    return pieces.some((piece) => {
        if (piece.kind !== 'rail' && piece.kind !== 'pants' && piece.kind !== 'drawer') return false;
        if (!spansOverlap(pieceSpan(sample), pieceSpan(piece))) return false;
        const z = piece.kind === 'drawer' ? (piece.z0 + piece.z1) / 2 : piece.z;
        return z > low + 0.01 && z < high - 0.01;
    });
}

function shelfClusters(pieces) {
    const groups = new Map();
    pieces.filter((piece) => piece.kind === 'shelf').forEach((piece) => {
        const key = columnKey(piece);
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(piece);
    });
    const clusters = [];
    groups.forEach((list) => {
        list.sort((a, b) => a.z - b.z);
        let current = [list[0]];
        for (let i = 1; i < list.length; i += 1) {
            if (blocksStack(pieces, list[i], list[i - 1].z, list[i].z)) {
                clusters.push(current);
                current = [list[i]];
            } else current.push(list[i]);
        }
        clusters.push(current);
    });
    return clusters;
}

function freeShelfSpan(pieces, shelf) {
    const span = pieceSpan(shelf);
    let low = 0.08;
    let high = 0.92;
    pieces.forEach((piece) => {
        if (piece === shelf || piece.kind === 'shelf' || piece.kind === 'post') return;
        if (!spansOverlap(span, pieceSpan(piece))) return;
        if (piece.kind === 'drawer') {
            if (piece.z1 <= shelf.z + 0.01) low = Math.max(low, piece.z1 + 0.02);
            if (piece.z0 >= shelf.z - 0.01) high = Math.min(high, piece.z0 - 0.02);
            return;
        }
        if (piece.z == null) return;
        if (piece.z <= shelf.z) low = Math.max(low, piece.z + 0.07);
        else high = Math.min(high, piece.z - 0.07);
    });
    if (high - low < 0.06) return { low: shelf.z, high: shelf.z };
    return { low, high };
}

function scaledShelves(pieces, count) {
    const clusters = shelfClusters(pieces);
    if (!clusters.length) return [];
    const counts = shareCounts(clusters.map((cluster) => cluster.length), count);
    const next = [];
    clusters.forEach((cluster, index) => {
        const amount = counts[index];
        if (!amount) return;
        const template = cluster[0];
        if (amount === cluster.length) {
            next.push(...cluster);
            return;
        }
        let low;
        let high;
        if (cluster.length >= 2) {
            low = cluster[0].z;
            high = cluster[cluster.length - 1].z;
        } else {
            const free = freeShelfSpan(pieces, cluster[0]);
            const z = cluster[0].z;
            const nearLow = z - free.low <= 0.08;
            const nearHigh = free.high - z <= 0.08;
            if (nearLow && !nearHigh) {
                low = z;
                high = free.high;
            } else if (nearHigh && !nearLow) {
                low = free.low;
                high = z;
            } else {
                low = free.low;
                high = free.high;
            }
        }
        if (amount === 1 || high - low < 0.001) {
            next.push({ ...template, z: (low + high) / 2 });
            return;
        }
        for (let i = 0; i < amount; i += 1) {
            next.push({ ...template, z: low + ((high - low) * i) / (amount - 1) });
        }
    });
    return next;
}

function scaledDrawers(pieces, count) {
    const groups = new Map();
    pieces.filter((piece) => piece.kind === 'drawer').forEach((piece) => {
        const key = columnKey(piece);
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(piece);
    });
    const clusters = [...groups.values()].map((list) => list.sort((a, b) => a.z0 - b.z0));
    if (!clusters.length) return [];
    const counts = shareCounts(clusters.map((cluster) => cluster.length), count);
    const next = [];
    clusters.forEach((cluster, index) => {
        const amount = counts[index];
        if (!amount) return;
        if (amount === cluster.length) {
            next.push(...cluster);
            return;
        }
        const low = Math.min(...cluster.map((piece) => piece.z0));
        const high = Math.max(...cluster.map((piece) => piece.z1));
        const gap = Math.min(0.008, (high - low) / amount * 0.08);
        const slot = (high - low) / amount;
        for (let i = 0; i < amount; i += 1) {
            const z0 = low + i * slot;
            const z1 = i === amount - 1 ? high : z0 + slot - gap;
            next.push({ ...cluster[0], z0, z1: Math.max(z0 + 0.03, z1) });
        }
    });
    return next;
}

function refitCadPieces(pieces, shelfCount, drawerCount) {
    const source = pieces || [];
    const shelves = source.filter((piece) => piece.kind === 'shelf');
    const drawers = source.filter((piece) => piece.kind === 'drawer');
    const sameShelves = shelfCount == null || shelfCount === shelves.length;
    const sameDrawers = drawerCount == null || drawerCount === drawers.length;
    if (sameShelves && sameDrawers) return source;
    return source
        .filter((piece) => piece.kind !== 'shelf' && piece.kind !== 'drawer')
        .concat(sameShelves ? shelves : scaledShelves(source, shelfCount))
        .concat(sameDrawers ? drawers : scaledDrawers(source, drawerCount));
}

function moduleShelfCount(mod) {
    if (mod?.glb) return 0;
    const cad = cadById(mod.cad);
    if (cad) return mod.shelfCount ?? cad.shelves ?? 0;
    return mod.shelves || 0;
}

function glbDrawerCount(mod) {
    const item = glbCatalog.find((entry) => entry.id === mod?.glb);
    return Number(item?.drawers) > 0 ? Number(item.drawers) : 0;
}

function glbAccessoryName(name) {
    const n = String(name || '').toLowerCase();
    if (/askilik|boru|rail/.test(n)) return false;
    return /aski|kiyafet|ayakkabi|valiz|tekstil|canta|bavul/.test(n);
}

function glbPartRole(name) {
    const n = String(name || '').toLowerCase();
    if (/aski|boru|rail/.test(n)) return 'metal';
    if (/cekmece/.test(n)) return 'drawer';
    if (/raf|shelf/.test(n)) return 'shelf';
    if (/arkalik|back/.test(n)) return 'back';
    return 'carcass';
}

function extractFaces(geometry, predicate) {
    const pos = geometry.attributes.position;
    const nor = geometry.attributes.normal;
    const uv = geometry.attributes.uv;
    if (!pos || !nor) return { taken: null, left: geometry };
    const taken = [];
    const left = [];
    for (let i = 0; i < pos.count; i += 3) {
        let nx = 0;
        let ny = 0;
        let nz = 0;
        for (let k = 0; k < 3; k += 1) {
            nx += nor.getX(i + k);
            ny += nor.getY(i + k);
            nz += nor.getZ(i + k);
        }
        (predicate(nx / 3, ny / 3, nz / 3) ? taken : left).push(i);
    }
    const build = (indices) => {
        if (!indices.length) return null;
        const p = [];
        const n = [];
        const u = [];
        indices.forEach((index) => {
            for (let k = 0; k < 3; k += 1) {
                p.push(pos.getX(index + k), pos.getY(index + k), pos.getZ(index + k));
                n.push(nor.getX(index + k), nor.getY(index + k), nor.getZ(index + k));
                if (uv) u.push(uv.getX(index + k), uv.getY(index + k));
            }
        });
        const next = new THREE.BufferGeometry();
        next.setAttribute('position', new THREE.Float32BufferAttribute(p, 3));
        next.setAttribute('normal', new THREE.Float32BufferAttribute(n, 3));
        if (u.length) next.setAttribute('uv', new THREE.Float32BufferAttribute(u, 2));
        next.computeBoundingBox();
        return next;
    };
    return { taken: build(taken), left: build(left) };
}

function edgeBandMaterial(sourceId) {
    const next = material('edge-band', sourceId).clone();
    next.roughness = 0.86;
    next.roughnessMap = null;
    next.normalMap = null;
    next.userData.flatDark = 0.9;
    if (next.userData.flatUrl) {
        const known = flatColorCache.get(next.userData.flatUrl);
        if (known) next.color.copy(known).multiplyScalar(0.9);
    } else next.color.multiplyScalar(0.9);
    return next;
}

function paintGlb(root, mod) {
    const drawerId = drawerOf(mod);
    const added = [];
    root.traverse((child) => {
        if (!child.isMesh) return;
        const role = glbPartRole(child.name);
        const alum = aluminumOf(mod);
        const source = role === 'metal'
            ? material(`glb-metal-${alum.id}`, alum.color, { metal: true })
            : role === 'drawer'
                ? material('glb-drawer', drawerId)
                : role === 'shelf'
                    ? material('glb-shelf', shelfOf(mod))
                    : role === 'back'
                        ? material('glb-back', backOf(mod))
                        : material('glb-body', carcassOf(mod));
        const next = source.clone();
        next.vertexColors = false;
        if (role === 'back') next.roughness = Math.min(0.9, (next.roughness || 0.62) + 0.18);
        child.castShadow = true;
        child.receiveShadow = true;
        child.material = next;
        const name = String(child.name || '').toLowerCase();
        if (role === 'drawer' && /_on_/.test(name) && child.geometry?.attributes?.normal) {
            const cap = extractFaces(child.geometry, (_nx, ny) => Math.abs(ny) > 0.72);
            if (cap.taken && cap.left) {
                child.geometry = cap.left;
                const lip = new THREE.Mesh(cap.taken, edgeBandMaterial(drawerId));
                lip.name = `${child.name}_BANT`;
                lip.castShadow = true;
                lip.receiveShadow = true;
                added.push([child, lip]);
            }
        }
        if (role !== 'shelf' || !child.geometry?.attributes?.normal) return;
        const front = extractFaces(child.geometry, (_nx, _ny, nz) => nz > 0.72);
        if (!front.taken || !front.left) return;
        const under = extractFaces(front.left, (_nx, ny) => ny < -0.72);
        child.geometry = under.left || front.left;
        const lip = new THREE.Mesh(front.taken, edgeBandMaterial(shelfOf(mod)));
        lip.name = `${child.name}_BANT`;
        lip.castShadow = true;
        lip.receiveShadow = true;
        added.push([child, lip]);
        if (!under.taken) return;
        const sole = new THREE.Mesh(under.taken, edgeBandMaterial(shelfOf(mod)));
        sole.name = `${child.name}_ALT`;
        sole.castShadow = true;
        sole.receiveShadow = true;
        added.push([child, sole]);
    });
    added.forEach(([parent, mesh]) => parent.add(mesh));
}

function moduleDrawerCount(mod) {
    if (mod?.glb) return 0;
    const cad = cadById(mod.cad);
    if (cad) return mod.drawerCount ?? cad.drawers ?? 0;
    return mod.drawers || 0;
}

function interiorZone(mod, h) {
    const bottom = plinthOf(mod) + T;
    const top = h - T;
    const span = Math.max(20, top - bottom);
    return { bottom, top, span, at: (t) => bottom + span * t };
}

function interiorPlan(mod, h) {
    if (!mod.layout && !mod.product) return null;
    const zone = interiorZone(mod, h);
    const at = (t) => zone.at(t);
    const id = mod.product || '';
    const plan = {
        shelves: [],
        leftShelves: [],
        rightShelves: [],
        rails: [],
        leftRails: [],
        rightRails: [],
        shoes: [],
        pants: [],
        trays: [],
        drawers: 0,
        drawerTop: zone.bottom,
        bottom: zone.bottom,
        split: false,
        splitTop: zone.top,
        dress: 'plain',
    };
    const cad = cadById(mod.cad);
    if (cad && mod.layout !== 'custom') {
        plan.pieces = refitCadPieces(cad.pieces, mod.shelfCount, mod.drawerCount).filter((piece) => {
            if (!piece.arm || !mod.dxfPart) return true;
            return piece.arm === 'both' || piece.arm === (mod.dxfPart === 'wing' ? 'wing' : 'body');
        });
        plan.dress = cad.shoes || cad.id === '08_ayakkabilik' ? 'cad-shoes' : 'cad';
        return plan;
    }
    const recipe = dxfById(mod.dxf);
    if (recipe && mod.layout !== 'custom') {
        fillDxfInterior(mod, zone, plan, recipe);
    } else if (id === 'custom' || mod.layout === 'custom' || mod.interior) {
        fillStyledInterior(mod, zone, plan);
    } else if (id === 'cift-raf') {
        plan.split = true;
        plan.splitTop = at(0.86);
        plan.shelves.push(at(0.93));
        plan.leftRails = [at(0.74)];
        plan.rightRails = [at(0.74)];
        plan.leftShelves = [at(0.32), at(0.12)];
        plan.rightShelves = [at(0.48), at(0.3), at(0.12)];
        plan.dress = 'bolme';
    } else if (id === 'cift-ayakkabi' || (mod.layout === 'ayakkabi' && id !== 'kose-raf')) {
        plan.split = true;
        plan.splitTop = at(0.96);
        plan.leftShelves = [0.9, 0.74, 0.58, 0.42, 0.26, 0.1].map(at);
        plan.rightRails = [at(0.74)];
        plan.rightShelves = [at(0.5)];
        plan.pants.push({ y: at(0.4), from: 0.56, to: 0.94 });
        plan.shoes.push(at(0.08));
        plan.dress = 'ayakkabi';
    } else if (id === 'cift-pantolon' || mod.layout === 'pantolon') {
        plan.split = true;
        plan.splitTop = at(0.96);
        plan.leftRails = [at(0.78), at(0.36)];
        plan.leftShelves = [at(0.56)];
        plan.rightShelves = [at(0.9), at(0.74), at(0.6)];
        plan.trays = [at(0.46), at(0.34)];
        plan.pants.push({ y: at(0.18), from: 0.56, to: 0.94 });
        plan.dress = 'pantolon';
    } else if (id === 'cift-raf-pantolon' || mod.layout === 'raf-pantolon') {
        plan.drawers = 2;
        plan.drawerTop = zone.bottom + Math.min(zone.span * 0.2, 36);
        plan.shelves.push(plan.drawerTop + 8, at(0.56));
        plan.rails.push(at(0.84));
        plan.pants.push({ y: at(0.44), from: 0.08, to: 0.92 });
        plan.dress = 'shirts-pants';
    } else if (id === 'cift-cekmece' || mod.layout === 'cekmece') {
        plan.drawers = mod.drawers || 3;
        const drawerH = Math.min(zone.span * 0.36, plan.drawers * 18);
        plan.drawerTop = zone.bottom + drawerH;
        plan.shelves.push(plan.drawerTop + 8, at(0.9));
        plan.rails.push(at(0.66));
        plan.dress = 'cekmece';
    } else if (id === 'cift-aski' || id === 'cift-panjur' || mod.layout === 'cift-aski') {
        plan.shelves.push(at(0.52), at(0.1));
        plan.rails.push(at(0.8), at(0.34));
        plan.dress = 'cift-aski';
    } else if (id === 'tek-boy' || mod.layout === 'boy-aski' || id === 'kose-aski') {
        plan.shelves.push(at(0.08), at(0.24), at(0.88));
        plan.rails.push(at(0.76));
        plan.dress = 'boy';
    } else if (id === 'kose-raf') {
        plan.shelves = [0.9, 0.74, 0.58, 0.42, 0.26, 0.1].map(at);
        plan.dress = 'stacks';
    } else if (id === 'tek-raf' || id === 'tek-panjur' || mod.layout === 'shelves') {
        const count = clamp(mod.shelves || 5, 1, 8);
        for (let i = 1; i <= count; i += 1) plan.shelves.push(at(i / (count + 1)));
        plan.dress = 'stacks';
    } else {
        return null;
    }
    return plan;
}

function fillStyledInterior(mod, zone, plan) {
    const type = mod.interior || (mod.rail ? 'aski' : 'raf');
    const shelves = clamp(Number(mod.shelves) || 0, 0, 8);
    const drawers = clamp(Number(mod.drawers) || 0, 0, 6);
    const slot = clamp(Number(mod.drawerHeight) || 18, 12, 40);
    plan.drawers = drawers;
    plan.drawerTop = drawers ? zone.bottom + drawers * slot : zone.bottom;
    const span = Math.max(28, zone.top - plan.drawerTop);
    const y = (t) => plan.drawerTop + span * t;
    if (type === 'pantolon') {
        if (mod.rail !== false) plan.rails.push(y(0.86));
        const count = Math.max(shelves, 0);
        for (let i = 1; i <= count; i += 1) plan.shelves.push(y(i / (count + 2)));
        plan.pants.push({ y: y(0.58), from: 0.08, to: 0.92 });
        plan.dress = 'shirts-pants';
    } else if (type === 'ayakkabi') {
        plan.split = true;
        plan.splitTop = y(0.98);
        const rows = Math.max(shelves, 4);
        for (let i = 0; i < rows; i += 1) plan.leftShelves.push(y(0.1 + (0.72 * i) / rows));
        if (mod.rail !== false) plan.rightRails.push(y(0.72));
        plan.rightShelves.push(y(0.42));
        plan.shoes.push(y(0.08));
        plan.dress = 'ayakkabi';
    } else if (type === 'cekmece') {
        if (mod.rail !== false) plan.rails.push(y(0.7));
        const count = Math.max(shelves, 1);
        for (let i = 1; i <= count; i += 1) plan.shelves.push(y(i / (count + 1)));
        plan.dress = 'cekmece';
    } else if (type === 'cift') {
        plan.rails.push(y(0.78), y(0.42));
        const count = Math.max(shelves, 1);
        for (let i = 1; i <= count; i += 1) plan.shelves.push(y(0.12 * i));
        plan.dress = 'cift-aski';
    } else if (type === 'raf') {
        const count = Math.max(shelves, 1);
        for (let i = 1; i <= count; i += 1) plan.shelves.push(y(i / (count + 1)));
        plan.dress = 'stacks';
    } else {
        const count = Math.max(shelves, 1);
        if (mod.rail !== false) plan.rails.push(y(0.72));
        for (let i = 1; i <= count; i += 1) {
            const t = i / (count + 1);
            if (Math.abs(t - 0.72) > 0.06) plan.shelves.push(y(t));
        }
        plan.dress = 'boy';
    }
}

function addShelfBoard(parent, w, d, y, mat) {
    panel(parent, w - 2 * T - 0.4, 1.8, d - BACK - 2, w / 2, y, BACK + (d - BACK) / 2, mat);
}

function addRailBar(parent, w, d, y) {
    const rail = new THREE.Mesh(
        new THREE.CylinderGeometry(0.8, 0.8, Math.max(8, w - 2 * T - 4), 12),
        material('rail', '#b9b3aa', { metal: true })
    );
    rail.rotation.z = Math.PI / 2;
    rail.position.set(w / 2, y, d * 0.46);
    rail.castShadow = true;
    parent.add(rail);
}

function addTrouserRack(parent, w, d, y) {
    const metal = material('trouser', '#c2bbb2', { metal: true });
    const span = Math.max(12, w - 2 * T - 8);
    for (let i = 0; i < 5; i += 1) {
        const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.4, 0.4, d * 0.5, 8), metal);
        bar.rotation.x = Math.PI / 2;
        bar.position.set(w / 2 + (i - 2) * (span / 5), y, d * 0.42);
        parent.add(bar);
    }
}

function addShoeShelf(parent, w, d, y, mat) {
    const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(w - 2 * T - 0.6, 1.2, (d - BACK) * 0.7),
        mat
    );
    mesh.position.set(w / 2, y, BACK + (d - BACK) * 0.4);
    mesh.rotation.x = -0.22;
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    parent.add(mesh);
}

function addDrawers(parent, w, h, d, mod, preview, bottom, drawerTop) {
    const count = mod.drawers || 0;
    if (!count || drawerTop <= bottom) return;
    const inner = material('shelf', interiorOf(mod));
    const frontMat = material('drawer', drawerOf(mod));
    const slot = (drawerTop - bottom) / count;
    for (let i = 0; i < count; i += 1) {
        const group = new THREE.Group();
        group.position.set(0, bottom + i * slot, 0);
        panel(group, w - 2 * T - 1, slot - 1.2, d - BACK - 6, w / 2, slot / 2, BACK + 2 + (d - BACK - 6) / 2, inner);
        panel(group, w - 2 * T - 0.6, slot - 0.8, DOOR_T, w / 2, slot / 2, d + DOOR_T / 2, frontMat);
        parent.add(group);
        if (!preview) {
            group.userData.slide = mod;
            group.userData.travel = Math.min(36, Math.max(18, d * 0.5));
            slides.push(group);
        }
    }
}

function addHalfShelf(parent, w, d, y, side, mat) {
    const span = (w - 3 * T) / 2;
    const x = side === 'left' ? T + span / 2 : w - T - span / 2;
    panel(parent, span, 1.8, d - BACK - 2, x, y, BACK + (d - BACK) / 2, mat);
}

function addHalfRail(parent, w, d, y, side) {
    const span = Math.max(8, (w - 3 * T) / 2 - 2);
    const x = side === 'left' ? T + 1 + span / 2 : w - T - 1 - span / 2;
    const rail = new THREE.Mesh(
        new THREE.CylinderGeometry(0.7, 0.7, span, 10),
        material('rail', '#b9b3aa', { metal: true })
    );
    rail.rotation.z = Math.PI / 2;
    rail.position.set(x, y, d * 0.46);
    rail.castShadow = true;
    parent.add(rail);
}

function addPantsBar(parent, w, d, pants) {
    const metal = material('trouser', '#8d877f', { metal: true });
    pants.forEach((item) => {
        const x0 = T + (w - 2 * T) * item.from;
        const x1 = T + (w - 2 * T) * item.to;
        const span = Math.max(8, x1 - x0);
        const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.55, 0.55, span, 10), metal);
        bar.rotation.z = Math.PI / 2;
        bar.position.set((x0 + x1) / 2, item.y, d * 0.5);
        parent.add(bar);
    });
}

function addTray(parent, w, d, y, mat) {
    const span = (w - 3 * T) / 2 - 1.5;
    const x = w - T - span / 2;
    const depth = (d - BACK) * 0.62;
    const z = BACK + 2 + depth / 2;
    panel(parent, span, 1.2, depth, x, y, z, mat);
    panel(parent, span, 5, 1.1, x, y + 3, z + depth / 2 - 0.6, mat);
}

function fillDxfInterior(mod, zone, plan, recipe) {
    if (recipe.id === 'kose' && mod.dxfPart !== 'wing') return;
    const at = (t) => zone.at(t);
    plan.shelves = (recipe.shelves || []).map(at);
    plan.rails = (recipe.rails || []).map(at);
    plan.drawers = recipe.drawers || 0;
    plan.drawerTop = plan.drawers ? at(recipe.drawerTop) : zone.bottom;
    plan.pants = (recipe.pants || []).map((t) => ({ y: at(t), from: 0.08, to: 0.92 }));
    plan.hooks = recipe.hooks || [];
    plan.hookY = recipe.hookY ? at(recipe.hookY) : at(0.4);
    plan.hang = recipe.hang || 58;
    plan.dress = recipe.dress || 'plain';
}

function cadY(mod, h, t) {
    const base = plinthOf(mod);
    return base + Math.max(20, h - base) * t;
}

function drawCadPieces(parent, w, h, d, mod, pieces, preview) {
    const inner = material('shelf', shelfOf(mod));
    const body = material('body', carcassOf(mod));
    const frontMat = material('drawer', drawerOf(mod));
    const metal = material('rail', '#b9b3aa', { metal: true });
    const shelfYs = [];
    pieces.forEach((piece) => {
        if (piece.kind === 'shelf' || piece.kind === 'tray') {
            const x0 = w * piece.x0;
            const x1 = w * piece.x1;
            const y = cadY(mod, h, piece.z);
            shelfYs.push(y);
            panel(parent, Math.max(6, x1 - x0 - 0.4), piece.kind === 'tray' ? 1.2 : 1.8, d - BACK - 2, (x0 + x1) / 2, y, BACK + (d - BACK) / 2, inner);
        } else if (piece.kind === 'rail' || piece.kind === 'pants') {
            const x0 = w * piece.x0;
            const x1 = w * piece.x1;
            const span = Math.max(8, x1 - x0);
            const rail = new THREE.Mesh(
                new THREE.CylinderGeometry(piece.kind === 'pants' ? 0.45 : 0.75, piece.kind === 'pants' ? 0.45 : 0.75, span, 10),
                metal
            );
            rail.rotation.z = Math.PI / 2;
            rail.position.set((x0 + x1) / 2, cadY(mod, h, piece.z), d * 0.46);
            rail.castShadow = true;
            parent.add(rail);
        } else if (piece.kind === 'post') {
            const y0 = cadY(mod, h, piece.z0);
            const y1 = cadY(mod, h, piece.z1);
            panel(parent, T, Math.max(8, y1 - y0), d - BACK - 3, w * piece.x, (y0 + y1) / 2, BACK + (d - BACK) / 2, body);
        } else if (piece.kind === 'drawer') {
            const x0 = w * piece.x0;
            const x1 = w * piece.x1;
            const y0 = cadY(mod, h, piece.z0);
            const y1 = cadY(mod, h, piece.z1);
            const dh = Math.max(8, y1 - y0);
            const group = new THREE.Group();
            group.position.set(0, y0, 0);
            const mid = (x0 + x1) / 2;
            const span = Math.max(8, x1 - x0);
            panel(group, span - 1, dh - 1.2, d - BACK - 6, mid, dh / 2, BACK + 2 + (d - BACK - 6) / 2, inner);
            panel(group, span - 0.6, dh - 0.8, DOOR_T, mid, dh / 2, d + DOOR_T / 2, frontMat);
            parent.add(group);
            if (!preview) {
                group.userData.slide = mod;
                group.userData.travel = Math.min(36, Math.max(18, d * 0.5));
                slides.push(group);
            }
        }
    });
    if (!preview) addCabinetLeds(parent, w, h, d, mod, shelfYs);
}

function dressCadPieces(parent, w, d, mod, plan) {
    const z = d * 0.48;
    const h = moduleHeight(mod);
    (plan.pieces || []).forEach((piece) => {
        if (piece.kind === 'rail') {
            const y = cadY(mod, h, piece.z);
            const below = (plan.pieces || [])
                .filter((item) => item !== piece && (item.z || item.z1 || 0) < piece.z - 0.02)
                .map((item) => item.z || item.z1 || 0);
            const next = below.length ? Math.max(...below) : 0.08;
            const room = Math.max(28, (piece.z - next) * Math.max(20, h - plinthOf(mod)) * 0.8);
            hangSpan(parent, w * piece.x0, w * piece.x1, y, z, (piece.x1 - piece.x0) > 0.7 ? ['shirt', 'sweater', 'shirt', 'sweater'] : ['shirt', 'sweater'], Math.min(room, 150));
        }
        if (piece.kind === 'pants') {
            hangPants(parent, w, d, [{ y: cadY(mod, h, piece.z), from: piece.x0, to: piece.x1 }]);
        }
        if (plan.dress === 'cad-shoes' && piece.kind === 'shelf') {
            const y = cadY(mod, h, piece.z);
            placeShoes(parent, w * (piece.x0 + piece.x1) / 2 - w * 0.12, y, z);
            placeShoes(parent, w * (piece.x0 + piece.x1) / 2 + w * 0.12, y, z);
        }
    });
}

function addInterior(parent, w, h, d, mod, preview) {
    const plan = interiorPlan(mod, h);
    const inner = material('shelf', shelfOf(mod));
    const body = material('body', carcassOf(mod));
    if (plan?.pieces) {
        drawCadPieces(parent, w, h, d, mod, plan.pieces, preview);
        return;
    }
    if (!plan) {
        const base = plinthOf(mod);
        const innerH = h - base - 2 * T;
        const innerY = base + T;
        const drawerH = mod.drawers > 0 ? Math.min(innerH * 0.55, mod.drawers * 18) : 0;
        const shelfSpace = innerH - drawerH;
        const count = clamp(mod.shelves || 0, 0, 8);
        const shelfYs = [];
        for (let i = 1; i <= count; i += 1) {
            const y = innerY + drawerH + (shelfSpace * i) / (count + 1);
            shelfYs.push(y);
            addShelfBoard(parent, w, d, y, inner);
        }
        if (mod.rail && w > 40) addRailBar(parent, w, d, innerY + drawerH + shelfSpace * 0.72);
        addDrawers(parent, w, h, d, mod, preview, innerY, innerY + drawerH);
        if (!preview) addCabinetLeds(parent, w, h, d, mod, shelfYs);
        return;
    }
    if (plan.split) {
        panel(parent, T, Math.max(8, plan.splitTop - plan.bottom), d - BACK - 3, w / 2, plan.bottom + (plan.splitTop - plan.bottom) / 2, BACK + (d - BACK) / 2, body);
    }
    plan.shelves.forEach((y) => addShelfBoard(parent, w, d, y, inner));
    plan.leftShelves.forEach((y) => addHalfShelf(parent, w, d, y, 'left', inner));
    plan.rightShelves.forEach((y) => addHalfShelf(parent, w, d, y, 'right', inner));
    plan.rails.forEach((y) => addRailBar(parent, w, d, y));
    plan.leftRails.forEach((y) => addHalfRail(parent, w, d, y, 'left'));
    plan.rightRails.forEach((y) => addHalfRail(parent, w, d, y, 'right'));
    plan.shoes.forEach((y) => addShoeShelf(parent, w, d, y, inner));
    addPantsBar(parent, w, d, plan.pants);
    plan.trays.forEach((y) => addTray(parent, w, d, y, inner));
    addDrawers(parent, w, h, d, { ...mod, drawers: plan.drawers }, preview, plan.bottom, plan.drawerTop);
    if (plan.hooks && plan.hooks.length) {
        const metal = material('hook', '#8d877f', { metal: true });
        plan.hooks.forEach((x) => panel(parent, 1.2, 12, 1.2, w * x, plan.hookY, d * 0.42, metal));
    }
    if (!preview) {
        addCabinetLeds(parent, w, h, d, mod, [...plan.shelves, ...plan.leftShelves, ...plan.rightShelves]);
    }
}

const CLOTH = ['#f3efe6', '#243044', '#7c2f2f', '#d9c3a1', '#1c1c1c', '#6d7b88', '#c56a45', '#efe6d4', '#3c5140', '#b7a088', '#8e6a86', '#d8d4cc'];
const clothCache = new Map();

function clothMaterial(hex) {
    if (clothCache.has(hex)) return clothCache.get(hex);
    const mat = new THREE.MeshStandardMaterial({ color: hex, roughness: 0.9, metalness: 0 });
    clothCache.set(hex, mat);
    return mat;
}

function ledMaterial(on) {
    const key = on ? 'led-on' : 'led-dim';
    if (clothCache.has(key)) return clothCache.get(key);
    const mat = on
        ? new THREE.MeshStandardMaterial({ color: '#fff6df', emissive: '#ffe3a4', emissiveIntensity: 4.2, roughness: 0.32 })
        : new THREE.MeshStandardMaterial({ color: '#d9d3c8', emissive: '#000000', emissiveIntensity: 0, roughness: 0.55 });
    clothCache.set(key, mat);
    return mat;
}

const alumFrameCache = new Map();

function alumFrameMaterial(alum) {
    const key = alum?.id || 'al-gumus';
    if (alumFrameCache.has(key)) return alumFrameCache.get(key);
    const mat = new THREE.MeshPhysicalMaterial({
        color: alum?.color || '#d7dbe0',
        metalness: 0.58,
        roughness: 0.32,
        anisotropy: 0.7,
        anisotropyRotation: Math.PI / 2,
        clearcoat: 0.35,
        clearcoatRoughness: 0.28,
        envMapIntensity: 1.35,
        specularIntensity: 0.85,
    });
    alumFrameCache.set(key, mat);
    return mat;
}

function ledStyleOf(mod) {
    if (!mod || mod.led === false || mod.kind === 'end') return 'off';
    return mod.ledStyle === 'side' ? 'side' : 'shelf';
}

function addGarment(parent, x, railY, z, kind, seed) {
    const mat = clothMaterial(CLOTH[Math.abs(seed) % CLOTH.length]);
    const hanger = material('hanger', '#d7d2cb', { metal: true });
    const group = new THREE.Group();
    group.position.set(x, railY, z);
    const hook = new THREE.Mesh(new THREE.TorusGeometry(1.15, 0.16, 6, 12, Math.PI), hanger);
    hook.position.y = 0.2;
    hook.rotation.y = Math.PI;
    group.add(hook);
    const bar = new THREE.Mesh(new THREE.BoxGeometry(12, 0.35, 0.35), hanger);
    bar.position.y = -1.1;
    group.add(bar);
    if (kind === 'dress') {
        panel(group, 16, 40, 2.2, 0, -23, 0, mat);
        panel(group, 7, 8, 2.2, -9, -7, 0, mat);
        panel(group, 7, 8, 2.2, 9, -7, 0, mat);
    } else if (kind === 'pants') {
        panel(group, 14, 3.5, 1.8, 0, -4, 0, mat);
        panel(group, 6.5, 28, 1.8, -3.2, -19, 0, mat);
        panel(group, 6.5, 28, 1.8, 3.2, -19, 0, mat);
    } else {
        panel(group, 15, 24, 2.4, 0, -15, 0, mat);
        panel(group, 6, 11, 2.2, -9, -9, 0, mat);
        panel(group, 6, 11, 2.2, 9, -9, 0, mat);
    }
    parent.add(group);
}

function addHanging(parent, w, d, y, kinds, salt) {
    const span = Math.max(10, w - 2 * T - 10);
    kinds.forEach((kind, i) => {
        const x = T + 5 + (span * (i + 0.5)) / kinds.length;
        addGarment(parent, x, y, d * 0.48, kind, salt + i * 3);
    });
}

function addFolds(parent, w, d, y, count, salt) {
    const span = Math.max(10, w - 2 * T - 12);
    for (let i = 0; i < count; i += 1) {
        const x = T + 6 + (span * (i + 0.5)) / count;
        panel(parent, 16, 2.2, 14, x, y + 2.4, d * 0.46, clothMaterial(CLOTH[(salt + i) % CLOTH.length]));
        panel(parent, 15, 2, 13, x + 0.5, y + 4.6, d * 0.46, clothMaterial(CLOTH[(salt + i + 5) % CLOTH.length]));
    }
}

function addShoes(parent, w, d, y, count, salt) {
    const span = Math.max(8, w - 2 * T - 8);
    for (let i = 0; i < count; i += 1) {
        const shoe = new THREE.Mesh(
            new THREE.BoxGeometry(8, 4.2, 18),
            clothMaterial(CLOTH[(salt + i * 2) % CLOTH.length])
        );
        shoe.position.set(T + 4 + (span * (i + 0.5)) / count, y + 3.4, d * 0.42);
        shoe.rotation.x = -0.22;
        shoe.castShadow = true;
        parent.add(shoe);
    }
}

function addHatBox(parent, x, y, z, seed) {
    const mesh = new THREE.Mesh(new THREE.CylinderGeometry(5.5, 5.5, 5, 18), clothMaterial(CLOTH[seed % CLOTH.length]));
    mesh.position.set(x, y + 4.2, z);
    mesh.castShadow = true;
    parent.add(mesh);
}

function addBasket(parent, w, d, y) {
    const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(Math.min(28, w - 2 * T - 6), 12, 18),
        clothMaterial('#6b5344')
    );
    mesh.position.set(w / 2, y + 7, d * 0.5);
    mesh.castShadow = true;
    parent.add(mesh);
}

function addCabinetLeds(parent, w, h, d, mod, shelves) {
    const style = ledStyleOf(mod);
    if (style === 'off') return;
    const on = lightsOn === true;
    const strip = ledMaterial(on);
    const channel = alumFrameMaterial(aluminumOf(mod));
    const floor = plinthOf(mod) + 4;
    const top = h - T - 2;
    if (style === 'side') {
        const height = Math.max(24, top - floor);
        const y = floor + height / 2;
        const z = d * 0.58;
        [T + 0.7, w - T - 0.7].forEach((x) => {
            const rail = new THREE.Mesh(new THREE.BoxGeometry(1.2, height, 1.6), channel);
            rail.position.set(x, y, z);
            rail.castShadow = true;
            parent.add(rail);
            const glow = new THREE.Mesh(new THREE.BoxGeometry(0.28, height - 1.4, 0.28), strip);
            glow.position.set(x, y, z + 0.7);
            parent.add(glow);
        });
        if (!on) return;
        const light = new THREE.PointLight(0xffe6c0, 2.2, Math.max(50, w * 0.9), 2);
        light.position.set(w / 2, (floor + top) / 2, d * 0.48);
        parent.add(light);
        return;
    }
    const spots = [h - T - 2.2, ...(shelves || [])];
    const width = Math.max(8, w - 2 * T - 8);
    spots.forEach((y) => {
        if (y < floor || y > h - 1) return;
        const rail = new THREE.Mesh(new THREE.BoxGeometry(width, 1.15, 1.8), channel);
        rail.position.set(w / 2, y - 1.15, T + 2.4);
        rail.castShadow = true;
        parent.add(rail);
        const glow = new THREE.Mesh(new THREE.BoxGeometry(width - 1.4, 0.45, Math.max(8, d * 0.55)), strip);
        glow.position.set(w / 2, y - 1.7, d * 0.42);
        parent.add(glow);
        if (!on) return;
        const light = new THREE.PointLight(0xffe0a8, 1.6, 55, 2);
        light.position.set(w / 2, y - 5, d * 0.42);
        parent.add(light);
    });
}

let propTemplates = null;

function placeModel(parent, template, opts) {
    if (!template) return false;
    const obj = template.clone(true);
    obj.rotation.set(opts.rx || 0, opts.yaw || 0, 0);
    obj.updateMatrixWorld(true);
    const raw = new THREE.Box3().setFromObject(obj);
    const size = raw.getSize(new THREE.Vector3());
    const fit = Math.min(
        opts.maxW / Math.max(size.x, 1e-4),
        opts.maxH / Math.max(size.y, 1e-4),
        opts.maxD / Math.max(size.z, 1e-4)
    );
    if (!Number.isFinite(fit) || fit <= 0) return false;
    obj.scale.multiplyScalar(fit);
    obj.updateMatrixWorld(true);
    const fitted = new THREE.Box3().setFromObject(obj);
    const center = fitted.getCenter(new THREE.Vector3());
    obj.position.x += opts.x - center.x;
    obj.position.z += opts.z - center.z;
    obj.position.y += opts.anchor === 'top' ? opts.y - fitted.max.y : opts.y - fitted.min.y;
    parent.add(obj);
    return true;
}

function addHangerHook(parent, x, y, z, width) {
    const hanger = material('hanger', '#c8c2ba', { metal: true });
    const group = new THREE.Group();
    group.position.set(x, y, z);
    const hook = new THREE.Mesh(new THREE.TorusGeometry(1.15, 0.16, 8, 14, Math.PI), hanger);
    hook.rotation.y = Math.PI;
    hook.position.y = 0.35;
    group.add(hook);
    const bar = new THREE.Mesh(new THREE.BoxGeometry(Math.max(10, width), 0.38, 0.38), hanger);
    bar.position.y = -1.15;
    group.add(bar);
    parent.add(group);
}

function hangClothes(parent, w, d, railY, keys) {
    const span = Math.max(12, w - 2 * T - 8);
    const slot = span / keys.length;
    const maxW = Math.min(26, slot * 0.92);
    const maxD = Math.min(18, d * 0.42);
    keys.forEach((key, index) => {
        const x = T + 4 + slot * (index + 0.5);
        addHangerHook(parent, x, railY, d * 0.48, Math.min(16, maxW * 0.72));
        placeModel(parent, propTemplates[key], {
            x,
            y: railY - 1.2,
            z: d * 0.48,
            maxW,
            maxH: 70,
            maxD,
            anchor: 'top',
        });
    });
}

function fillShelfProps(parent, w, d, shelves, wide) {
    const span = Math.max(12, w - 2 * T - 8);
    shelves.forEach((y, index) => {
        const left = T + span * (wide ? 0.28 : 0.5);
        const right = T + span * 0.74;
        const tee = {
            y: y + 1.05,
            z: d * 0.5,
            maxW: Math.min(28, span * (wide ? 0.42 : 0.7)),
            maxH: 7,
            maxD: Math.min(22, d * 0.48),
            anchor: 'bottom',
        };
        if (index % 2 === 0) {
            placeModel(parent, propTemplates.tee, { ...tee, x: left, yaw: 0.18 });
            if (wide) placeModel(parent, propTemplates.box, { x: right, y: y + 1.05, z: d * 0.46, maxW: 16, maxH: 14, maxD: 14, anchor: 'bottom', yaw: -0.2 });
        } else {
            placeModel(parent, propTemplates.box, { x: wide ? left : w / 2, y: y + 1.05, z: d * 0.46, maxW: 16, maxH: 14, maxD: 14, anchor: 'bottom' });
            if (wide) placeModel(parent, propTemplates.tee, { ...tee, x: right, yaw: -0.25 });
        }
    });
}

function fillShoeProps(parent, w, d, shoes, wide) {
    const count = wide ? 3 : 2;
    const span = Math.max(10, w - 2 * T - 6);
    shoes.forEach((y, row) => {
        for (let i = 0; i < count; i += 1) {
            placeModel(parent, propTemplates.heels, {
                x: T + 3 + (span * (i + 0.5)) / count,
                y: y + 0.9,
                z: BACK + (d - BACK) * 0.42,
                maxW: Math.min(16, (span / count) * 0.86),
                maxH: 11,
                maxD: Math.min(18, (d - BACK) * 0.5),
                anchor: 'bottom',
                yaw: (row + i) % 2 ? 0.35 : -0.2,
                rx: -0.22,
            });
        }
    });
}

function foldPile(parent, x, y, z, width, seed) {
    const depth = Math.min(18, Math.max(10, width * 0.75));
    for (let i = 0; i < 3; i += 1) {
        panel(parent, width, 2.2, depth, x + (i % 2) * 0.4, y + 1.4 + i * 2.4, z, clothMaterial(CLOTH[(seed + i * 2) % CLOTH.length]));
    }
}

function hangSpan(parent, x0, x1, y, z, keys, maxH) {
    const span = Math.max(8, x1 - x0);
    keys.forEach((key, index) => {
        const x = x0 + span * (index + 0.5) / keys.length;
        const slot = span / keys.length;
        addHangerHook(parent, x, y, z, Math.min(14, slot * 0.72));
        const template = propTemplates && propTemplates[key];
        if (template) {
            placeModel(parent, template, {
                x, y: y - 1.2, z, maxW: Math.min(22, slot * 0.9), maxH, maxD: 16, anchor: 'top',
            });
        } else {
            addGarment(parent, x, y, z, key === 'pants' ? 'pants' : 'shirt', index + 2);
        }
    });
}

function hangPants(parent, w, d, pants) {
    pants.forEach((item, row) => {
        const x0 = T + (w - 2 * T) * item.from;
        const x1 = T + (w - 2 * T) * item.to;
        const count = Math.max(2, Math.round((x1 - x0) / 12));
        for (let i = 0; i < count; i += 1) {
            const x = x0 + (x1 - x0) * (i + 0.5) / count;
            panel(parent, 6.5, 24, 1.5, x, item.y - 15, d * 0.5, clothMaterial(CLOTH[(row * 3 + i) % CLOTH.length]));
        }
    });
}

function placeShoes(parent, x, y, z) {
    const placed = propTemplates && placeModel(parent, propTemplates.heels, {
        x, y: y + 0.8, z, maxW: 14, maxH: 10, maxD: 16, anchor: 'bottom', yaw: 0.2, rx: -0.05,
    });
    if (!placed) {
        panel(parent, 8, 4, 16, x, y + 2.2, z, clothMaterial('#cbbba8'));
    }
}

function addDressing(parent, w, h, d, mod, plan) {
    const z = d * 0.48;
    const left0 = T + 3;
    const left1 = w / 2 - T;
    const right0 = w / 2 + T;
    const right1 = w - T - 3;
    const shirts = w >= 70 ? ['shirt', 'sweater', 'shirt', 'sweater'] : ['shirt', 'sweater'];
    if (plan.dress === 'stacks') {
        plan.shelves.forEach((y, index) => {
            foldPile(parent, w * 0.36, y, z, Math.min(16, w * 0.28), index + 1);
            if (index % 2 === 0) foldPile(parent, w * 0.66, y, z, Math.min(16, w * 0.28), index + 4);
            else if (propTemplates?.box) placeModel(parent, propTemplates.box, { x: w * 0.66, y: y + 1, z, maxW: 14, maxH: 12, maxD: 12, anchor: 'bottom' });
        });
        return;
    }
    if (plan.dress === 'dxf-shoes') {
        plan.shelves.forEach((y) => {
            placeShoes(parent, w * 0.34, y, z);
            placeShoes(parent, w * 0.66, y, z);
        });
        return;
    }
    if (plan.dress === 'boy') {
        plan.rails.forEach((y) => hangSpan(parent, left0, right1, y, z, shirts.slice(0, 3), plan.hang || 58));
        const top = plan.shelves[plan.shelves.length - 1];
        const mid = plan.shelves[1];
        const low = plan.shelves[0];
        if (top) foldPile(parent, w * 0.38, top, z, Math.min(16, w * 0.3), 2);
        if (mid) {
            foldPile(parent, w * 0.62, mid, z, Math.min(14, w * 0.28), 5);
            placeShoes(parent, w * 0.32, mid, z);
        }
        if (low) {
            placeShoes(parent, w * 0.34, low, z);
            placeShoes(parent, w * 0.66, low, z);
        }
        return;
    }
    if (plan.dress === 'shirts-pants') {
        plan.rails.forEach((y) => hangSpan(parent, left0, right1, y, z, shirts, 42));
        hangPants(parent, w, d, plan.pants);
        const shelf = plan.shelves[0];
        if (shelf) {
            foldPile(parent, w * 0.3, shelf, z, 16, 3);
            placeShoes(parent, w * 0.7, shelf, z);
        }
        return;
    }
    if (plan.dress === 'cekmece') {
        plan.rails.forEach((y) => hangSpan(parent, left0, right1, y, z, shirts, 48));
        const top = plan.shelves[plan.shelves.length - 1];
        const mid = plan.shelves[0];
        if (top) {
            foldPile(parent, w * 0.32, top, z, 18, 1);
            foldPile(parent, w * 0.68, top, z, 16, 4);
        }
        if (mid) {
            foldPile(parent, w * 0.28, mid, z, 16, 6);
            foldPile(parent, w * 0.55, mid, z, 16, 8);
            foldPile(parent, w * 0.78, mid, z, 14, 2);
        }
        return;
    }
    if (plan.dress === 'cift-aski') {
        const [high, low] = plan.rails;
        if (high) hangSpan(parent, left0, right1, high, z, shirts, 40);
        if (low) hangSpan(parent, left0, right1, low, z, ['sweater', 'shirt', 'sweater', 'shirt'], 32);
        const bottom = plan.shelves[plan.shelves.length - 1];
        if (bottom) {
            foldPile(parent, w * 0.28, bottom, z, 16, 2);
            foldPile(parent, w * 0.55, bottom, z, 16, 7);
            if (propTemplates?.box) placeModel(parent, propTemplates.box, { x: w * 0.78, y: bottom + 1, z, maxW: 14, maxH: 12, maxD: 12, anchor: 'bottom' });
        }
        return;
    }
    if (plan.dress === 'pantolon') {
        plan.leftRails.forEach((y, index) => hangSpan(parent, left0, left1, y, z, index === 0 ? shirts.slice(0, 3) : ['sweater', 'shirt'], index === 0 ? 36 : 34));
        plan.rightShelves.forEach((y, index) => foldPile(parent, (right0 + right1) / 2, y, z, Math.min(18, (right1 - right0) * 0.7), index + 2));
        plan.trays.forEach((y, index) => foldPile(parent, (right0 + right1) / 2, y, z, 14, index + 5));
        hangPants(parent, w, d, plan.pants);
        return;
    }
    if (plan.dress === 'bolme') {
        const top = plan.shelves[0];
        if (top) {
            foldPile(parent, w * 0.3, top, z, 18, 1);
            foldPile(parent, w * 0.7, top, z, 18, 4);
        }
        plan.leftRails.forEach((y) => hangSpan(parent, left0, left1, y, z, ['sweater', 'shirt'], 40));
        plan.rightRails.forEach((y) => hangSpan(parent, right0, right1, y, z, shirts.slice(0, 3), 40));
        plan.leftShelves.forEach((y, index) => foldPile(parent, (left0 + left1) / 2, y, z, Math.min(18, left1 - left0 - 4), index + 3));
        plan.rightShelves.forEach((y, index) => {
            if (index === 0 && propTemplates?.box) {
                placeModel(parent, propTemplates.box, { x: (right0 + right1) / 2, y: y + 1, z, maxW: 16, maxH: 14, maxD: 14, anchor: 'bottom' });
            } else foldPile(parent, (right0 + right1) / 2, y, z, 16, index + 6);
        });
        return;
    }
    if (plan.dress === 'ayakkabi') {
        plan.leftShelves.forEach((y, index) => {
            if (index < 2) {
                placeShoes(parent, (left0 + left1) / 2 - 6, y, z);
                placeShoes(parent, (left0 + left1) / 2 + 6, y, z);
            } else if (index % 3 === 2 && propTemplates?.box) placeModel(parent, propTemplates.box, { x: (left0 + left1) / 2, y: y + 1, z, maxW: 14, maxH: 12, maxD: 12, anchor: 'bottom' });
            else foldPile(parent, (left0 + left1) / 2, y, z, Math.min(16, left1 - left0 - 4), index + 1);
        });
        plan.rightRails.forEach((y) => hangSpan(parent, right0, right1, y, z, shirts.slice(0, 3), 40));
        plan.rightShelves.forEach((y) => foldPile(parent, (right0 + right1) / 2, y, z, 16, 4));
        hangPants(parent, w, d, plan.pants);
        plan.shoes.forEach((y) => {
            placeShoes(parent, right0 + 8, y, z);
            placeShoes(parent, right1 - 8, y, z);
        });
    }
}

async function loadProps() {
    const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
    const loader = new GLTFLoader();
    const base = String(window.WARDROBE.modelsUrl || '').replace(/\/$/, '');
    const files = {
        shirt: 'gomlek.glb',
        jacket: 'jacket.glb',
        sweater: 'kazak.glb',
        tee: 't-shirt.glb',
        heels: 'heels.glb',
        box: 'jewelry-box.glb',
    };
    const loaded = {};
    await Promise.all(Object.entries(files).map(async ([key, file]) => {
        try {
            const gltf = await loader.loadAsync(`${base}/${file}`);
            gltf.scene.traverse((child) => {
                if (!child.isMesh) return;
                child.castShadow = true;
                child.receiveShadow = true;
            });
            loaded[key] = gltf.scene;
        } catch (error) {
            console.warn('Dolap modeli yüklenemedi', file, error);
        }
    }));
    propTemplates = loaded;
    if (state.modules.some((item) => item.glb)) render();
}

function addHingedDoor(parent, x, y, w, h, d, hinge, style, mod, preview) {
    const pivot = new THREE.Group();
    pivot.position.set(hinge === 'right' ? x + w : x, y, d + 0.15);
    const leaf = new THREE.Group();
    leaf.position.x = hinge === 'right' ? -w / 2 : w / 2;
    if (style === 'glass') {
        const alum = aluminumOf(mod);
        const glass = glassOf(mod);
        const frame = alumFrameMaterial(alum);
        const bar = 2.05;
        panel(leaf, w, bar, 1.45, 0, h / 2 - bar / 2, 0.72, frame);
        panel(leaf, w, bar, 1.45, 0, -h / 2 + bar / 2, 0.72, frame);
        panel(leaf, bar, h, 1.45, -w / 2 + bar / 2, 0, 0.72, frame);
        panel(leaf, bar, h, 1.45, w / 2 - bar / 2, 0, 0.72, frame);
        const glassOpts = { glass: true, transmission: glass.transmission, attenuation: glass.attenuation };
        const pane = panel(leaf, Math.max(1, w - bar * 2), Math.max(1, h - bar * 2), 0.6, 0, 0, DOOR_T * 0.55, material(`glass-${glass.id}`, glass.color, glassOpts));
        if (pane) pane.castShadow = false;
    } else {
        panel(leaf, w - 0.35, h - 0.35, DOOR_T, 0, 0, DOOR_T / 2, material('door', doorOf(mod)));
    }
    if (hasKulp(mod)) {
        const handle = new THREE.Mesh(
            new THREE.BoxGeometry(1.15, Math.min(18, h * 0.16), 1.35),
            alumFrameMaterial(aluminumOf(mod))
        );
        handle.position.set(hinge === 'right' ? -w / 2 + 3.2 : w / 2 - 3.2, 0, 1.55);
        leaf.add(handle);
    }
    pivot.add(leaf);
    pivot.userData.hinge = hinge;
    pivot.userData.mod = mod;
    pivot.userData.kind = 'hinge';
    parent.add(pivot);
    if (!preview) doorPivots.push(pivot);
}

function addTambour(parent, w, h, d, mod, preview) {
    const base = plinthOf(mod);
    const doorH = h - base - 0.6;
    const group = new THREE.Group();
    group.position.set(0, base, d + 0.2);
    const slats = Math.max(14, Math.round((w - 1) / 2.4));
    const slatW = (w - 0.8) / slats;
    const face = material('door', doorOf(mod));
    const groove = material('tambour-groove', '#141311');
    for (let i = 0; i < slats; i += 1) {
        const x = 0.4 + i * slatW + slatW / 2;
        panel(group, Math.max(0.8, slatW - 0.38), doorH - 0.4, DOOR_T * 0.9, x, doorH / 2, DOOR_T / 2, face);
        panel(group, 0.22, doorH - 0.4, DOOR_T * 0.4, x + slatW / 2 - 0.12, doorH / 2, DOOR_T * 0.95, groove);
    }
    if (hasKulp(mod)) {
        const pull = new THREE.Mesh(
            new THREE.BoxGeometry(1.15, Math.min(18, doorH * 0.14), 1.3),
            material(`handle-${aluminumOf(mod).id}`, aluminumOf(mod).color, { metal: true })
        );
        pull.position.set(w - 3.2, doorH * 0.5, DOOR_T + 0.7);
        group.add(pull);
    }
    group.userData.kind = 'tambour';
    group.userData.mod = mod;
    group.userData.baseX = 0;
    group.userData.travel = Math.max(14, w * 0.62);
    parent.add(group);
    if (!preview) doorPivots.push(group);
}

function doorsAreHidden(mod) {
    return hideAllDoors || hiddenDoorIds.has(mod.id);
}

function addUpperCabinet(parent, w, d, mod, preview) {
    const uh = upperHeight(mod);
    if (!uh) return;
    const group = new THREE.Group();
    group.position.y = moduleHeight(mod);
    const upper = { ...mod, plinth: false, plinthHidden: false, upper: 0, kind: 'wardrobe' };
    addCarcass(group, w, uh, d, upper);
    addShelfBoard(group, w, d, uh * 0.55, material('shelf', shelfOf(mod)));
    if (upper.door !== 'none' && (preview || !doorsAreHidden(mod))) addDoors(group, w, uh, d, upper, preview);
    parent.add(group);
}

function addDoors(parent, w, h, d, mod, preview) {
    if (mod.door === 'panjur') {
        addTambour(parent, w, h, d, mod, preview);
        return;
    }
    const base = plinthOf(mod);
    const doorH = h - base - 0.6;
    const y = base + doorH / 2;
    if (mod.door === 'accordion') {
        const leafW = 22;
        const count = Math.max(2, Math.round(w / leafW));
        const width = w / count;
        for (let i = 0; i < count; i += 1) {
            const pair = Math.floor(i / 2) % 2 === 0;
            const hinge = i % 2 === 0 ? 'left' : 'right';
            const pivot = new THREE.Group();
            const x0 = i * width;
            pivot.position.set(hinge === 'right' ? x0 + width : x0, y, d + 0.15);
            const leaf = new THREE.Group();
            leaf.position.x = hinge === 'right' ? -width / 2 : width / 2;
            panel(leaf, width - 0.4, doorH - 0.4, DOOR_T, 0, 0, DOOR_T / 2, material('door', doorOf(mod)));
            if (hasKulp(mod)) {
                const handle = new THREE.Mesh(
                    new THREE.BoxGeometry(1.1, Math.min(12, doorH * 0.12), 1.2),
                    material(`handle-${aluminumOf(mod).id}`, aluminumOf(mod).color, { metal: true })
                );
                handle.position.set(hinge === 'right' ? -width / 2 + 2.4 : width / 2 - 2.4, 0, DOOR_T + 0.6);
                leaf.add(handle);
            }
            pivot.add(leaf);
            pivot.userData.kind = 'accordion';
            pivot.userData.sign = pair ? (hinge === 'left' ? -1 : 1) : (hinge === 'left' ? 1 : -1);
            pivot.userData.mod = mod;
            parent.add(pivot);
            if (!preview) doorPivots.push(pivot);
        }
        return;
    }
    const count = w < 55 ? 1 : 2;
    const gap = 0.35;
    const leafW = (w - gap * (count + 1)) / count;
    for (let i = 0; i < count; i += 1) {
        const hinge = hingeFor(mod, i, count);
        const style = mod.door === 'glass' || (mod.door === 'mixed' && i % 2 === 1) ? 'glass' : 'solid';
        addHingedDoor(parent, gap + i * (leafW + gap), y, leafW, doorH, d, hinge, style, mod, preview);
    }
}

function addWardrobe(parent, mod, w, d, preview) {
    const h = moduleHeight(mod);
    addCarcass(parent, w, h, d, mod);
    if (mod.kind !== 'end') addInterior(parent, w, h, d, mod, preview);
    if (mod.door !== 'none' && (preview || !doorsAreHidden(mod))) addDoors(parent, w, h, d, mod, preview);
    if (mod.kind !== 'end') addUpperCabinet(parent, w, d, mod, preview);
}

function addEndPanel(parent, mod) {
    const h = moduleHeight(mod);
    const d = state.depth;
    panel(parent, 2, h, d, 1, h / 2, d / 2, material('end', carcassOf(mod)));
}

let selectionEdgeMat = null;
let selectionWashMat = null;

function selectionFrame(mod) {
    if (!selectionEdgeMat) {
        selectionEdgeMat = new THREE.MeshBasicMaterial({ color: 0x149152, depthTest: false, depthWrite: false, transparent: true, opacity: 0.38 });
        selectionWashMat = new THREE.MeshBasicMaterial({ color: 0x149152, depthTest: false, depthWrite: false, transparent: true, opacity: 0 });
    }
    const box = footprint(mod);
    if (mod.kind === 'corner') {
        const group = new THREE.Group();
        const arm = cornerArm();
        const right = mod.cornerSide === 'right';
        const place = (w, d, x, z) => {
            const wash = new THREE.Mesh(new THREE.BoxGeometry(w, box.h, d), selectionWashMat);
            wash.position.set(right ? 90 - x : x, box.h / 2, z);
            wash.renderOrder = 19;
            group.add(wash);
        };
        place(arm, 90, arm / 2, 45);
        place(90 - arm, arm, arm + (90 - arm) / 2, arm / 2);
        return group;
    }
    const group = new THREE.Group();
    const w = box.w + 1.2;
    const h = box.h + 1.2;
    const d = box.d + 1.2;
    const t = 0.55;
    const bars = [
        [w, t, t, 0, -h / 2, -d / 2], [w, t, t, 0, -h / 2, d / 2], [t, t, d, -w / 2, -h / 2, 0], [t, t, d, w / 2, -h / 2, 0],
        [w, t, t, 0, h / 2, -d / 2], [w, t, t, 0, h / 2, d / 2], [t, t, d, -w / 2, h / 2, 0], [t, t, d, w / 2, h / 2, 0],
        [t, h, t, -w / 2, 0, -d / 2], [t, h, t, w / 2, 0, -d / 2], [t, h, t, -w / 2, 0, d / 2], [t, h, t, w / 2, 0, d / 2],
    ];
    bars.forEach(([bw, bh, bd, x, y, z]) => {
        const mesh = new THREE.Mesh(new THREE.BoxGeometry(bw, bh, bd), selectionEdgeMat);
        mesh.position.set(x, y, z);
        mesh.renderOrder = 20;
        group.add(mesh);
    });
    const wash = new THREE.Mesh(new THREE.BoxGeometry(box.w, box.h, box.d), selectionWashMat);
    wash.renderOrder = 19;
    group.add(wash);
    group.position.set(box.w / 2, box.h / 2, box.d / 2);
    return group;
}

function flipWinding(geometry) {
    const index = geometry.index;
    if (!index) return;
    for (let i = 0; i < index.count; i += 3) {
        const first = index.getX(i);
        index.setX(i, index.getX(i + 1));
        index.setX(i + 1, first);
    }
    index.needsUpdate = true;
}

function rewritePositions(root, mutator) {
    root.traverse((child) => {
        if (!child.isMesh) return;
        const position = child.geometry.attributes.position;
        const normal = child.geometry.attributes.normal;
        for (let i = 0; i < position.count; i += 1) {
            const next = mutator(
                position.getX(i),
                position.getY(i),
                position.getZ(i),
                normal ? normal.getX(i) : 0,
                normal ? normal.getY(i) : 0,
                normal ? normal.getZ(i) : 0,
            );
            position.setXYZ(i, next.x, next.y, next.z);
            if (normal) normal.setXYZ(i, next.nx, next.ny, next.nz);
        }
        position.needsUpdate = true;
        if (normal) normal.needsUpdate = true;
        flipWinding(child.geometry);
        child.geometry.computeBoundingBox();
        child.geometry.computeBoundingSphere();
    });
}

function geometryBounds(root) {
    const box = new THREE.Box3();
    root.traverse((child) => {
        if (!child.isMesh || !child.geometry.boundingBox) return;
        box.union(child.geometry.boundingBox);
    });
    return box;
}

function orientGlbToFront(root) {
    root.traverse((child) => {
        if (child.isMesh) child.geometry.computeBoundingBox();
    });
    let box = geometryBounds(root);
    const size = box.getSize(new THREE.Vector3());
    if (size.z > 20 && size.y < size.z * 0.5) {
        rewritePositions(root, (x, y, z, nx, ny, nz) => ({
            x: x / 1000,
            y: z / 1000,
            z: y / 1000,
            nx,
            ny: nz,
            nz: ny,
        }));
        box = geometryBounds(root);
    }
    const depth = box.max.z - box.min.z;
    if (depth < 0.05) return;
    let backZ = null;
    root.traverse((child) => {
        if (!child.isMesh || !/arkalik|back/i.test(String(child.name || ''))) return;
        const panel = child.geometry.boundingBox;
        if (!panel || panel.max.z - panel.min.z > depth * 0.2) return;
        backZ = (panel.min.z + panel.max.z) / 2;
    });
    if (backZ == null || backZ < box.min.z + depth * 0.5) return;
    const limit = box.min.z + box.max.z;
    rewritePositions(root, (x, y, z, nx, ny, nz) => ({
        x,
        y,
        z: limit - z,
        nx,
        ny,
        nz: -nz,
    }));
}

function mirrorModuleX(root, widthMeters) {
    root.traverse((child) => {
        if (!child.isMesh) return;
        const position = child.geometry.attributes.position;
        for (let i = 0; i < position.count; i += 1) position.setX(i, widthMeters - position.getX(i));
        position.needsUpdate = true;
        const normal = child.geometry.attributes.normal;
        if (normal) {
            for (let i = 0; i < normal.count; i += 1) normal.setX(i, -normal.getX(i));
            normal.needsUpdate = true;
        }
        const index = child.geometry.index;
        if (index) {
            for (let i = 0; i < index.count; i += 3) {
                const first = index.getX(i);
                index.setX(i, index.getX(i + 1));
                index.setX(i + 1, first);
            }
            index.needsUpdate = true;
        }
        child.geometry.computeBoundingBox();
        child.geometry.computeBoundingSphere();
    });
}

const bakedGlb = new Map();

function aimCabinetShadow(center, size) {
    if (!keyLight || !center) return;
    const span = size || new THREE.Vector3(160, 250, 60);
    keyLight.position.set(center.x + span.x * 0.12, center.y + Math.max(span.y * 0.55, 120), center.z + Math.max(span.z + 160, 200));
    keyLight.target.position.copy(center);
    keyLight.target.updateMatrixWorld();
    const cam = keyLight.shadow.camera;
    cam.left = -Math.max(span.x, 90) * 0.62 - 36;
    cam.right = Math.max(span.x, 90) * 0.62 + 36;
    cam.top = Math.max(span.y, 90) * 0.62 + 24;
    cam.bottom = -Math.max(span.y, 90) * 0.62 - 24;
    cam.updateProjectionMatrix();
    if (!cavityLight) return;
    cavityLight.position.set(center.x - span.x * 0.2, center.y + span.y * 0.42, center.z + Math.max(span.z + 180, 220));
    cavityLight.target.position.set(center.x, center.y, center.z - 20);
    cavityLight.target.updateMatrixWorld();
}

function axisBox(geometry) {
    geometry.computeBoundingBox();
    const box = geometry.boundingBox;
    if (!box) return false;
    const position = geometry.attributes.position;
    const eps = 1e-4;
    for (let i = 0; i < position.count; i += 1) {
        const x = position.getX(i);
        const y = position.getY(i);
        const z = position.getZ(i);
        const on = Math.abs(x - box.min.x) < eps || Math.abs(x - box.max.x) < eps
            || Math.abs(y - box.min.y) < eps || Math.abs(y - box.max.y) < eps
            || Math.abs(z - box.min.z) < eps || Math.abs(z - box.max.z) < eps;
        if (!on) return false;
    }
    return true;
}

function chamferBoxGeometry(box, bevel) {
    const x0 = box.min.x;
    const x1 = box.max.x;
    const y0 = box.min.y;
    const y1 = box.max.y;
    const z0 = box.min.z;
    const z1 = box.max.z;
    const b = Math.min(bevel, (x1 - x0) * 0.2, (y1 - y0) * 0.2, (z1 - z0) * 0.2);
    if (!(b > 0.00005)) return null;
    const xi0 = x0 + b;
    const xi1 = x1 - b;
    const yi0 = y0 + b;
    const yi1 = y1 - b;
    const zi0 = z0 + b;
    const zi1 = z1 - b;
    const positions = [];
    const normals = [];
    const quad = (a, b2, c, d, n) => {
        [a, b2, c, a, c, d].forEach((v) => {
            positions.push(v[0], v[1], v[2]);
            normals.push(n[0], n[1], n[2]);
        });
    };
    const tri = (a, b2, c, n) => {
        [a, b2, c].forEach((v) => {
            positions.push(v[0], v[1], v[2]);
            normals.push(n[0], n[1], n[2]);
        });
    };
    const norm = (x, y, z) => {
        const len = Math.hypot(x, y, z) || 1;
        return [x / len, y / len, z / len];
    };
    quad([x1, yi0, zi1], [x1, yi1, zi1], [x1, yi1, zi0], [x1, yi0, zi0], [1, 0, 0]);
    quad([x0, yi0, zi0], [x0, yi1, zi0], [x0, yi1, zi1], [x0, yi0, zi1], [-1, 0, 0]);
    quad([xi0, y1, zi1], [xi1, y1, zi1], [xi1, y1, zi0], [xi0, y1, zi0], [0, 1, 0]);
    quad([xi0, y0, zi0], [xi1, y0, zi0], [xi1, y0, zi1], [xi0, y0, zi1], [0, -1, 0]);
    quad([xi0, yi0, z1], [xi1, yi0, z1], [xi1, yi1, z1], [xi0, yi1, z1], [0, 0, 1]);
    quad([xi1, yi0, z0], [xi0, yi0, z0], [xi0, yi1, z0], [xi1, yi1, z0], [0, 0, -1]);
    quad([xi0, y1, zi1], [xi0, yi1, z1], [xi1, yi1, z1], [xi1, y1, zi1], norm(0, 1, 1));
    quad([xi1, y1, zi0], [xi1, yi1, z0], [xi0, yi1, z0], [xi0, y1, zi0], norm(0, 1, -1));
    quad([xi0, y0, zi0], [xi0, yi0, z0], [xi1, yi0, z0], [xi1, y0, zi0], norm(0, -1, -1));
    quad([xi1, y0, zi1], [xi1, yi0, z1], [xi0, yi0, z1], [xi0, y0, zi1], norm(0, -1, 1));
    quad([xi1, yi0, z1], [x1, yi0, zi1], [x1, yi1, zi1], [xi1, yi1, z1], norm(1, 0, 1));
    quad([xi1, yi1, z0], [x1, yi1, zi0], [x1, yi0, zi0], [xi1, yi0, z0], norm(1, 0, -1));
    quad([xi0, yi1, z1], [x0, yi1, zi1], [x0, yi0, zi1], [xi0, yi0, z1], norm(-1, 0, 1));
    quad([xi0, yi0, z0], [x0, yi0, zi0], [x0, yi1, zi0], [xi0, yi1, z0], norm(-1, 0, -1));
    quad([x1, yi1, zi1], [xi1, y1, zi1], [xi1, y1, zi0], [x1, yi1, zi0], norm(1, 1, 0));
    quad([x0, yi1, zi0], [xi0, y1, zi0], [xi0, y1, zi1], [x0, yi1, zi1], norm(-1, 1, 0));
    quad([x1, yi0, zi0], [xi1, y0, zi0], [xi1, y0, zi1], [x1, yi0, zi1], norm(1, -1, 0));
    quad([x0, yi0, zi1], [xi0, y0, zi1], [xi0, y0, zi0], [x0, yi0, zi0], norm(-1, -1, 0));
    tri([x1, yi1, zi1], [xi1, y1, zi1], [xi1, yi1, z1], norm(1, 1, 1));
    tri([x1, yi1, zi0], [xi1, yi1, z0], [xi1, y1, zi0], norm(1, 1, -1));
    tri([x1, yi0, zi1], [xi1, yi0, z1], [xi1, y0, zi1], norm(1, -1, 1));
    tri([x1, yi0, zi0], [xi1, y0, zi0], [xi1, yi0, z0], norm(1, -1, -1));
    tri([x0, yi1, zi1], [xi0, yi1, z1], [xi0, y1, zi1], norm(-1, 1, 1));
    tri([x0, yi1, zi0], [xi0, y1, zi0], [xi0, yi1, z0], norm(-1, 1, -1));
    tri([x0, yi0, zi1], [xi0, y0, zi1], [xi0, yi0, z1], norm(-1, -1, 1));
    tri([x0, yi0, zi0], [xi0, yi0, z0], [xi0, y0, zi0], norm(-1, -1, -1));
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setAttribute('normal', new THREE.Float32BufferAttribute(normals, 3));
    return geometry;
}

function bakeGlb(template, mirror, widthMeters) {
    const clone = template.clone(true);
    clone.updateMatrixWorld(true);
    clone.traverse((child) => {
        if (!child.isMesh) return;
        child.geometry = child.geometry.clone();
        child.geometry.applyMatrix4(child.matrixWorld);
        child.position.set(0, 0, 0);
        child.rotation.set(0, 0, 0);
        child.scale.set(1, 1, 1);
        child.castShadow = true;
        child.receiveShadow = true;
        const sources = Array.isArray(child.material) ? child.material : [child.material];
        const tuned = sources.filter(Boolean).map((mat) => {
            const next = mat.clone();
            next.roughness = 0.42;
            next.metalness = 0.04;
            next.vertexColors = Boolean(child.geometry.getAttribute('color'));
            return next;
        });
        if (tuned.length) child.material = tuned.length === 1 ? tuned[0] : tuned;
    });
    const drop = [];
    clone.traverse((child) => {
        if (child.isMesh && glbAccessoryName(child.name)) drop.push(child);
    });
    drop.forEach((child) => child.parent?.remove(child));
    orientGlbToFront(clone);
    clone.traverse((child) => {
        if (child.isMesh) return;
        child.position.set(0, 0, 0);
        child.rotation.set(0, 0, 0);
        child.scale.set(1, 1, 1);
        child.matrix.identity();
        child.matrixAutoUpdate = true;
    });
    if (mirror) mirrorModuleX(clone, widthMeters);
    clone.traverse((child) => {
        if (!child.isMesh) return;
        if (axisBox(child.geometry)) {
            const beveled = chamferBoxGeometry(child.geometry.boundingBox, 0.0008);
            if (beveled) child.geometry = beveled;
        } else if (!child.geometry.getAttribute('normal')) {
            const flat = child.geometry.index ? child.geometry.toNonIndexed() : child.geometry;
            flat.computeVertexNormals();
            child.geometry = flat;
        }
        assignPanelUv(child.geometry);
        child.geometry.computeBoundingBox();
        child.geometry.computeBoundingSphere();
    });
    return clone;
}

function placeGlb(template, mod) {
    const side = mod.kind === 'corner' && mod.cornerSide === 'right' ? 'right' : 'left';
    const key = `${mod.glb}:${side}`;
    if (!bakedGlb.has(key)) bakedGlb.set(key, bakeGlb(template, side === 'right', mod.glbWidth || mod.width / 100));
    const wrap = new THREE.Group();
    wrap.scale.setScalar(100);
    const model = bakedGlb.get(key).clone(true);
    paintGlb(model, mod);
    mountGlbDrawers(model, mod);
    wrap.add(model);
    dressGlb(wrap, model, mod);
    addGlbLeds(wrap, model, mod);
    return wrap;
}

function dressGlb(wrap, model, mod) {
    if (mod?.props === false || !propTemplates) return;
    const shelves = [];
    let rail = null;
    model.updateMatrixWorld(true);
    model.traverse((child) => {
        if (!child.isMesh || !child.geometry) return;
        const name = String(child.name || '').toLowerCase();
        if (!child.geometry.boundingBox) child.geometry.computeBoundingBox();
        const bounds = child.geometry.boundingBox;
        if (!bounds) return;
        const center = bounds.getCenter(new THREE.Vector3());
        const size = bounds.getSize(new THREE.Vector3());
        if (/raf/.test(name) && !/arkalik|bant|_alt/.test(name)) {
            shelves.push({
                x: center.x,
                y: center.y,
                z: center.z,
                w: size.x,
                d: size.z,
                h: size.y,
            });
        }
        if (/askilik/.test(name)) {
            rail = {
                x: center.x,
                y: center.y,
                z: center.z,
                w: Math.max(size.x, size.z),
            };
        }
    });
    shelves.sort((a, b) => b.y - a.y);
    const host = new THREE.Group();
    host.name = 'dolap-aksesuar';
    if (rail && propTemplates.shirt) {
        const hanging = [
            { key: 'shirt', x: rail.x - rail.w * 0.16, maxW: Math.min(0.32, rail.w * 0.78) },
            { key: 'jacket', x: rail.x + rail.w * 0.16, maxW: Math.min(0.3, rail.w * 0.72) },
        ];
        hanging.forEach((item, index) => {
            if (!propTemplates[item.key]) return;
            placeModel(host, propTemplates[item.key], {
                x: item.x,
                y: rail.y + 0.01,
                z: rail.z + 0.02,
                maxW: item.maxW,
                maxH: 0.95,
                maxD: 0.24,
                anchor: 'top',
                yaw: (index - 0.5) * 0.08,
            });
        });
    }
    const top = shelves[0];
    if (top && propTemplates.box) {
        placeModel(host, propTemplates.box, {
            x: top.x,
            y: top.y + top.h / 2 + 0.002,
            z: top.z,
            maxW: Math.min(0.32, top.w * 0.62),
            maxH: 0.16,
            maxD: Math.min(0.34, top.d * 0.5),
            anchor: 'bottom',
        });
    }
    const fold = shelves.find((shelf) => !top || shelf.y < top.y - 0.08) || null;
    if (fold && propTemplates.tee) {
        for (let i = 0; i < 3; i += 1) {
            placeModel(host, propTemplates.tee, {
                x: fold.x - fold.w * 0.12,
                y: fold.y + fold.h / 2 + 0.002 + i * 0.012,
                z: fold.z,
                maxW: Math.min(0.28, fold.w * 0.55),
                maxH: 0.04,
                maxD: Math.min(0.26, fold.d * 0.42),
                anchor: 'bottom',
                yaw: i * 0.04,
            });
        }
    }
    if (fold && propTemplates.sweater) {
        placeModel(host, propTemplates.sweater, {
            x: fold.x + fold.w * 0.16,
            y: fold.y + fold.h / 2 + 0.002,
            z: fold.z,
            maxW: Math.min(0.18, fold.w * 0.4),
            maxH: 0.1,
            maxD: Math.min(0.2, fold.d * 0.35),
            anchor: 'bottom',
        });
    }
    const shoeShelf = shelves.length ? shelves[shelves.length - 1] : null;
    if (shoeShelf && shoeShelf !== fold && shoeShelf !== top && propTemplates.heels) {
        placeModel(host, propTemplates.heels, {
            x: shoeShelf.x,
            y: shoeShelf.y + shoeShelf.h / 2 + 0.002,
            z: shoeShelf.z,
            maxW: 0.18,
            maxH: 0.12,
            maxD: 0.26,
            anchor: 'bottom',
            yaw: 0.15,
        });
    }
    if (host.children.length) wrap.add(host);
}

function addGlbLeds(wrap, model, mod) {
    const style = ledStyleOf(mod);
    if (style === 'off') return;
    const shelves = [];
    const box = new THREE.Box3();
    let sized = false;
    model.updateMatrixWorld(true);
    model.traverse((child) => {
        if (!child.isMesh || !child.geometry) return;
        const name = String(child.name || '').toLowerCase();
        if (!child.geometry.boundingBox) child.geometry.computeBoundingBox();
        const bounds = child.geometry.boundingBox;
        if (!bounds) return;
        if (/govde|arkalik|baza/.test(name)) {
            box.union(bounds);
            sized = true;
        }
        if (/raf/.test(name) && !/arkalik|bant|_alt/.test(name)) {
            const center = bounds.getCenter(new THREE.Vector3());
            const size = bounds.getSize(new THREE.Vector3());
            shelves.push({ x: center.x, y: center.y, z: center.z, w: size.x, d: size.z, h: size.y });
        }
    });
    if (!sized) return;
    const host = new THREE.Group();
    host.name = 'dolap-led';
    const on = lightsOn === true;
    const strip = ledMaterial(on);
    const channel = alumFrameMaterial(aluminumOf(mod));
    if (style === 'side') {
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        const height = Math.max(0.45, size.y - 0.16);
        [box.min.x + 0.028, box.max.x - 0.028].forEach((x) => {
            const inward = x < center.x ? 1 : -1;
            const rail = new THREE.Mesh(new THREE.BoxGeometry(0.012, height, 0.016), channel);
            rail.position.set(x, center.y, center.z);
            rail.castShadow = true;
            host.add(rail);
            const glow = new THREE.Mesh(new THREE.BoxGeometry(0.007, height - 0.04, 0.008), strip);
            glow.position.set(x + inward * 0.011, center.y, center.z + 0.006);
            host.add(glow);
        });
        if (on) {
            const light = new THREE.PointLight(0xffe6c0, 1.8, Math.max(0.4, size.x * 0.9), 2);
            light.position.set(center.x, center.y, center.z);
            host.add(light);
        }
    } else {
        const spots = shelves.slice();
        if (!spots.length) {
            const center = box.getCenter(new THREE.Vector3());
            const size = box.getSize(new THREE.Vector3());
            spots.push({ x: center.x, y: box.max.y - 0.02, z: center.z, w: size.x - 0.06, d: size.z, h: 0.018 });
        }
        spots.forEach((shelf) => {
            const width = Math.max(0.08, shelf.w - 0.03);
            const y = shelf.y - shelf.h / 2 - 0.007;
            const z = shelf.z - shelf.d / 2 + 0.018;
            const rail = new THREE.Mesh(new THREE.BoxGeometry(width, 0.012, 0.018), channel);
            rail.position.set(shelf.x, y, z);
            rail.castShadow = true;
            host.add(rail);
            const glow = new THREE.Mesh(
                new THREE.BoxGeometry(Math.max(0.04, width - 0.012), 0.006, Math.max(0.05, shelf.d * 0.62)),
                strip
            );
            glow.position.set(shelf.x, y - 0.004, shelf.z + shelf.d * 0.08);
            host.add(glow);
            if (!on) return;
            const light = new THREE.PointLight(0xffe0a8, 1.15, 0.55, 2);
            light.position.set(shelf.x, y - 0.04, shelf.z);
            host.add(light);
        });
    }
    if (host.children.length) wrap.add(host);
}

function shiftGeometryY(mesh, dy) {
    const geometry = mesh.geometry.clone();
    const position = geometry.attributes.position;
    for (let i = 0; i < position.count; i += 1) position.setY(i, position.getY(i) + dy);
    position.needsUpdate = true;
    geometry.computeBoundingBox();
    geometry.computeBoundingSphere();
    mesh.geometry = geometry;
}

function separateDrawerFronts(meshes) {
    const fronts = meshes.filter((mesh) => /_on_/.test(String(mesh.name || '').toLocaleLowerCase('tr')));
    const gap = 0.003;
    for (let pass = 0; pass < fronts.length; pass += 1) {
        const ordered = fronts.map((mesh) => {
            if (!mesh.geometry.boundingBox) mesh.geometry.computeBoundingBox();
            return mesh;
        }).sort((a, b) => a.geometry.boundingBox.min.y - b.geometry.boundingBox.min.y);
        let moved = false;
        for (let i = 0; i < ordered.length - 1; i += 1) {
            const lower = ordered[i].geometry.boundingBox;
            const upper = ordered[i + 1].geometry.boundingBox;
            const space = upper.min.y - lower.max.y;
            if (space >= gap) continue;
            const need = (gap - space) / 2;
            shiftGeometryY(ordered[i], -need);
            shiftGeometryY(ordered[i + 1], need);
            moved = true;
        }
        if (!moved) break;
    }
}

function hollowDrawerBox(group, kutu, mod) {
    if (!kutu.geometry.boundingBox) kutu.geometry.computeBoundingBox();
    const box = kutu.geometry.boundingBox;
    const x0 = box.min.x;
    const x1 = box.max.x;
    const y0 = box.min.y;
    const y1 = box.max.y;
    const z0 = box.min.z;
    const z1 = box.max.z;
    const sideT = 0.016;
    const backT = 0.016;
    const bottomT = 0.008;
    const height = y1 - y0;
    const depth = z1 - z0;
    const innerW = Math.max(0.04, (x1 - x0) - sideT * 2);
    const shell = material('drawer-box', carcassOf(mod)).clone();
    shell.roughness = Math.max(0.72, shell.roughness || 0.72);
    shell.metalness = 0;
    const midY = (y0 + y1) / 2;
    const midZ = (z0 + z1) / 2;
    const sideGeo = new THREE.BoxGeometry(sideT, height, depth);
    const left = new THREE.Mesh(sideGeo, shell);
    left.name = 'CEKMECE_YAN_SOL';
    left.position.set(x0 + sideT / 2, midY, midZ);
    const right = new THREE.Mesh(sideGeo, shell);
    right.name = 'CEKMECE_YAN_SAG';
    right.position.set(x1 - sideT / 2, midY, midZ);
    const back = new THREE.Mesh(new THREE.BoxGeometry(innerW, Math.max(0.03, height - bottomT), backT), shell);
    back.name = 'CEKMECE_ARKA';
    back.position.set((x0 + x1) / 2, y0 + bottomT + (height - bottomT) / 2, z0 + backT / 2);
    const bottom = new THREE.Mesh(new THREE.BoxGeometry(innerW, bottomT, Math.max(0.04, depth - backT)), shell);
    bottom.name = 'CEKMECE_TABAN';
    bottom.position.set((x0 + x1) / 2, y0 + bottomT / 2, z0 + backT + (depth - backT) / 2);
    const alum = aluminumOf(mod);
    const railMat = material(`drawer-rail-${alum.id}`, alum.color, { metal: true });
    const railGeo = new THREE.BoxGeometry(0.012, 0.008, Math.max(0.08, depth - 0.03));
    const railL = new THREE.Mesh(railGeo, railMat);
    railL.name = 'CEKMECE_RAY_SOL';
    railL.position.set(x0 + sideT + 0.008, y0 + bottomT + 0.004, midZ);
    const railR = new THREE.Mesh(railGeo, railMat);
    railR.name = 'CEKMECE_RAY_SAG';
    railR.position.set(x1 - sideT - 0.008, y0 + bottomT + 0.004, midZ);
    [left, right, back, bottom, railL, railR].forEach((mesh) => {
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        group.add(mesh);
    });
    kutu.removeFromParent();
}

function mountGlbDrawers(model, mod) {
    const meshes = [];
    model.traverse((child) => {
        if (child.isMesh && /cekmece/.test(String(child.name || '').toLocaleLowerCase('tr'))) meshes.push(child);
    });
    separateDrawerFronts(meshes);
    const groups = new Map();
    meshes.forEach((mesh) => {
        const match = String(mesh.name || '').match(/_(\d+)$/);
        const key = match ? match[1] : '';
        if (!key) return;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(mesh);
    });
    groups.forEach((parts) => {
        const named = (pattern) => parts.some((mesh) => pattern.test(String(mesh.name || '').toLocaleLowerCase('tr')));
        const legacy = named(/_on_/) && named(/taban/) && named(/arka/);
        const boxed = named(/_on_/) && named(/kutu/);
        if (!legacy && !boxed) return;
        const group = new THREE.Group();
        group.name = 'drawer-slide';
        model.add(group);
        parts.forEach((mesh) => group.attach(mesh));
        const box = new THREE.Box3();
        parts.forEach((mesh) => {
            if (!mesh.geometry.boundingBox) mesh.geometry.computeBoundingBox();
            if (mesh.geometry.boundingBox) box.union(mesh.geometry.boundingBox);
        });
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        if (boxed) {
            const kutu = parts.find((mesh) => /kutu/.test(String(mesh.name || '').toLocaleLowerCase('tr')));
            if (kutu) hollowDrawerBox(group, kutu, mod);
        }
        if (legacy && size.x > 0.04 && size.y > 0.04 && size.z > 0.05) {
            const sideT = 0.012;
            const sideH = Math.max(0.04, size.y - 0.006);
            const sideD = Math.max(0.05, size.z - 0.012);
            const shell = material('glb-body', carcassOf(mod));
            const side = new THREE.BoxGeometry(sideT, sideH, sideD);
            const left = new THREE.Mesh(side, shell);
            left.name = 'DRAWER_YAN_SOL';
            left.position.set(box.min.x + sideT / 2, center.y, center.z);
            const right = new THREE.Mesh(side, shell);
            right.name = 'DRAWER_YAN_SAG';
            right.position.set(box.max.x - sideT / 2, center.y, center.z);
            group.add(left, right);
        }
        const front = parts.find((mesh) => /_on_/.test(String(mesh.name || '').toLowerCase()));
        if (front?.geometry.boundingBox) {
            const face = front.geometry.boundingBox;
            const pull = new THREE.Mesh(
                new THREE.BoxGeometry(Math.min(0.14, (face.max.x - face.min.x) * 0.42), 0.012, 0.016),
                alumFrameMaterial(aluminumOf(mod))
            );
            pull.name = 'CEKMECE_KULP';
            pull.castShadow = true;
            pull.position.set((face.min.x + face.max.x) / 2, (face.min.y + face.max.y) / 2, face.max.z + 0.01);
            group.add(pull);
        }
        group.userData.slide = mod;
        group.userData.rest = 0.02;
        group.userData.travel = Math.min(0.42, Math.max(0.28, size.z * 0.72));
        slides.push(group);
    });
}

function cornerRecipe(id) {
    return {
        kose_1: { shelves: [42, 82, 122, 162, 204] },
        kose_2: { shelves: [44, 109, 204], rail: 181 },
        kose_3: { shelves: [33, 74, 119, 204], rail: 181, drawers: [17, 33] },
        kose_4: { shelves: [33, 60, 87, 114, 141, 204] },
        kose_5: { shelves: [39, 89, 149, 204], rail: 181 },
    }[id] || { shelves: [40, 90, 140, 200] };
}

function cornerArm() {
    return Math.min(60, Math.max(50, Number(state.depth) || 60));
}

function buildCornerCabinet(mod, preview) {
    const outer = 90;
    const arm = cornerArm();
    const right = mod.cornerSide === 'right';
    const h = moduleHeight(mod);
    const base = plinthOf(mod);
    const recipe = cornerRecipe(mod.glb);
    const group = new THREE.Group();
    const body = material('glb-body', carcassOf(mod));
    const backMat = material('glb-back', backOf(mod));
    const shelfMat = material('glb-shelf', shelfOf(mod));
    const cx = (left, width) => (right ? outer - left - width : left) + width / 2;
    const sideH = h - base;
    const sideY = base + sideH / 2;
    panel(group, T, sideH, outer - BACK, cx(0, T), sideY, BACK + (outer - BACK) / 2, body);
    panel(group, outer - T, sideH, BACK, outer / 2, sideY, BACK / 2, backMat);
    panel(group, T, sideH, arm - BACK, cx(outer - T, T), sideY, BACK + (arm - BACK) / 2, body);
    panel(group, arm - T, sideH, T, cx(T, arm - T), sideY, outer - T / 2, body);
    const yBottom = base + T / 2;
    const yTop = h - T / 2;
    const layL = (y, thick, mat) => {
        panel(group, arm - T + 0.6, thick, outer - BACK - T, cx(T, arm - T + 0.6), y, BACK + (outer - BACK - T) / 2, mat);
        panel(group, outer - arm, thick, Math.max(4, arm - BACK - 1.4), cx(arm, outer - arm), y, BACK + Math.max(4, arm - BACK - 1.4) / 2, mat);
    };
    layL(yBottom, T, body);
    layL(yTop, T, body);
    const drawerTop = recipe.drawers ? Math.max(...recipe.drawers) + 10 : 0;
    recipe.shelves.forEach((height) => {
        if (height <= base + T || height >= h - T) return;
        if (height > drawerTop) layL(height, T, shelfMat);
        else panel(group, outer - arm - T, T, arm - BACK - T, cx(arm + T, outer - arm - T), height, BACK + T + (arm - BACK - T) / 2, shelfMat);
    });
    if (recipe.rail) {
        const rail = new THREE.Mesh(
            new THREE.CylinderGeometry(1.1, 1.1, Math.max(12, arm - T * 3), 10),
            material(`glb-metal-${aluminumOf(mod).id}`, aluminumOf(mod).color, { metal: true })
        );
        rail.rotation.z = Math.PI / 2;
        rail.position.set(cx(T * 1.5, arm - T * 3), recipe.rail, arm * 0.72);
        rail.name = 'askilik';
        group.add(rail);
    }
    if (!preview && recipe.drawers) addCornerDrawers(group, mod, recipe, { outer, arm, right, h, base, cx });
    if (mod.door !== 'none' && (preview || !doorsAreHidden(mod))) addCornerDoors(group, mod, { outer, arm, right, h, base, cx, drawerTop }, preview);
    return group;
}

function addCornerDrawers(group, mod, recipe, box) {
    const { arm, right, cx } = box;
    const span = arm - T * 2;
    const depth = Math.max(16, span - 4);
    const frontT = DOOR_T;
    const boxH = 14;
    recipe.drawers.forEach((centerY) => {
        const slide = new THREE.Group();
        slide.name = 'drawer-slide';
        const face = material('glb-drawer', drawerOf(mod));
        const shell = material('glb-body', carcassOf(mod));
        const z0 = arm + 2;
        const z1 = 90 - T - 2;
        const zSpan = Math.max(16, z1 - z0);
        panel(slide, frontT, boxH, zSpan - 0.4, cx(arm - frontT, frontT), centerY, z0 + zSpan / 2, face);
        panel(slide, depth, 1.2, zSpan - 2, cx(arm - frontT - depth, depth), centerY - boxH / 2 + 0.8, z0 + zSpan / 2, shell);
        panel(slide, depth, boxH - 2, 1.2, cx(arm - frontT - depth, depth), centerY, z0 + 1, shell);
        panel(slide, depth, boxH - 2, 1.2, cx(arm - frontT - depth, depth), centerY, z0 + zSpan - 1, shell);
        panel(slide, 1.2, boxH - 2, zSpan - 2, cx(arm - frontT - 1.2, 1.2), centerY, z0 + zSpan / 2, shell);
        panel(slide, 1.2, boxH - 2, zSpan - 2, cx(arm - frontT - depth, 1.2), centerY, z0 + zSpan / 2, shell);
        slide.userData.slide = mod;
        slide.userData.axis = 'x';
        slide.userData.travel = (right ? -1 : 1) * Math.min(28, depth * 0.62);
        group.add(slide);
        slides.push(slide);
    });
}

function addCornerDoors(group, mod, box, preview) {
    const { outer, arm, right, h, base, drawerTop } = box;
    const doorH = h - Math.max(base, drawerTop) - T - 0.6;
    const y = Math.max(base, drawerTop) + doorH / 2;
    const doorW = outer - arm - T;
    if (doorH < 20 || doorW < 12) return;
    const style = mod.door === 'glass' ? 'glass' : 'solid';
    const front = new THREE.Group();
    const hingeX = right ? T : outer - T;
    front.position.set(hingeX, y, arm);
    const frontLeaf = new THREE.Group();
    frontLeaf.position.x = right ? doorW / 2 : -doorW / 2;
    addCornerLeaf(frontLeaf, doorW, doorH, style, mod, right ? 'left' : 'right');
    front.add(frontLeaf);
    front.userData.kind = 'corner-door';
    front.userData.sign = right ? 1 : -1;
    front.userData.mod = mod;
    group.add(front);
    const side = new THREE.Group();
    side.position.set(right ? outer - arm : arm, y, outer - T);
    const sideLeaf = new THREE.Group();
    sideLeaf.position.z = -doorW / 2;
    const turned = new THREE.Group();
    turned.rotation.y = Math.PI / 2;
    addCornerLeaf(turned, doorW, doorH, style, mod, 'left');
    sideLeaf.add(turned);
    side.add(sideLeaf);
    side.userData.kind = 'corner-door';
    side.userData.sign = right ? -1 : 1;
    side.userData.mod = mod;
    group.add(side);
    if (!preview) {
        doorPivots.push(front, side);
    }
}

function addCornerLeaf(parent, w, h, style, mod, hinge) {
    if (style === 'glass') {
        const alum = aluminumOf(mod);
        const glass = glassOf(mod);
        const frame = alumFrameMaterial(alum);
        const bar = 2.05;
        panel(parent, w, bar, 1.45, 0, h / 2 - bar / 2, 0.72, frame);
        panel(parent, w, bar, 1.45, 0, -h / 2 + bar / 2, 0.72, frame);
        panel(parent, bar, h, 1.45, -w / 2 + bar / 2, 0, 0.72, frame);
        panel(parent, bar, h, 1.45, w / 2 - bar / 2, 0, 0.72, frame);
        const pane = panel(parent, Math.max(1, w - bar * 2), Math.max(1, h - bar * 2), 0.6, 0, 0, DOOR_T * 0.55, material(`glass-${glass.id}`, glass.color, { glass: true, transmission: glass.transmission, attenuation: glass.attenuation }));
        if (pane) pane.castShadow = false;
    } else {
        panel(parent, w - 0.35, h - 0.35, DOOR_T, 0, 0, DOOR_T / 2, material('door', doorOf(mod)));
    }
    if (hasKulp(mod)) {
        const handle = new THREE.Mesh(
            new THREE.BoxGeometry(1.15, Math.min(18, h * 0.16), 1.35),
            alumFrameMaterial(aluminumOf(mod))
        );
        handle.position.set(hinge === 'right' ? -w / 2 + 3.2 : w / 2 - 3.2, 0, 1.55);
        parent.add(handle);
    }
}

function buildModule(mod, preview) {
    const group = new THREE.Group();
    group.name = `mod-${mod.id}`;
    group.userData.moduleId = mod.id;
    if (mod.glb) {
        if (mod.kind === 'corner') {
            group.add(buildCornerCabinet(mod, preview));
        } else {
            const template = glbTemplates.get(mod.glb);
            if (!template) {
                const box = footprint(mod);
                const missing = new THREE.Mesh(
                    new THREE.BoxGeometry(box.w, box.h, box.d),
                    new THREE.MeshBasicMaterial({ color: '#c0392b', wireframe: true })
                );
                missing.position.set(box.w / 2, box.h / 2, box.d / 2);
                group.add(missing);
            } else {
                try {
                    group.add(placeGlb(template, mod));
                } catch (error) {
                    console.error(error);
                }
                const box = footprint(mod);
                if (mod.door && mod.door !== 'none' && (preview || !doorsAreHidden(mod))) {
                    addDoors(group, box.w, box.h, box.d, mod, preview);
                }
            }
        }
    } else if (mod.kind === 'end') addEndPanel(group, mod);
    else if (mod.kind === 'corner') {
        addWardrobe(group, { ...mod, kind: 'wardrobe', product: 'kose-raf', dxfPart: 'body' }, mod.width, state.depth, preview);
        const wing = new THREE.Group();
        addWardrobe(wing, { ...mod, kind: 'wardrobe', product: mod.dxf === 'kose' ? 'custom' : 'kose-aski', dxfPart: 'wing' }, mod.width, state.depth, preview);
        if (mod.cornerSide === 'left') {
            wing.position.set(0.2, 0, mod.width);
            wing.rotation.y = Math.PI / 2;
        } else {
            wing.position.set(mod.width - 0.2, 0, 0.2);
            wing.rotation.y = -Math.PI / 2;
        }
        group.add(wing);
    } else addWardrobe(group, mod, mod.width, state.depth, preview);
    if (!preview && mod.id === selectedId) group.add(selectionFrame(mod));
    if (!preview && showMeasures) addModuleMeasures(group, mod);
    return group;
}

function cmText(n) {
    const value = Math.round(n * 10) / 10;
    return `${Number.isInteger(value) ? value : value.toFixed(1)} cm`;
}

function interiorGaps(mod, h) {
    const plan = interiorPlan(mod, h);
    if (plan) {
        const marks = [plan.bottom, ...[...plan.shoes, ...plan.shelves, ...(plan.leftShelves || []), ...(plan.rightShelves || [])].sort((a, b) => a - b), h - T];
        const gaps = [];
        for (let i = 0; i < marks.length - 1; i += 1) {
            const start = i === 0 ? marks[i] : marks[i] + 0.9;
            const end = i === marks.length - 2 ? marks[i + 1] : marks[i + 1] - 0.9;
            if (end - start > 1) gaps.push({ y: (start + end) / 2, size: end - start });
        }
        const drawers = [];
        if (plan.drawers > 0) {
            const slot = (plan.drawerTop - plan.bottom) / plan.drawers;
            for (let i = 0; i < plan.drawers; i += 1) drawers.push({ y: plan.bottom + slot * i + slot / 2, size: slot });
        }
        return { gaps, drawers };
    }
    const base = plinthOf(mod);
    const innerY = base + T;
    const innerH = h - base - 2 * T;
    const drawerH = mod.drawers > 0 ? Math.min(innerH * 0.55, mod.drawers * 18) : 0;
    const shelfSpace = innerH - drawerH;
    const count = mod.kind === 'end' ? 0 : clamp(mod.shelves || 0, 0, 8);
    const shelves = [];
    for (let i = 1; i <= count; i += 1) shelves.push(innerY + drawerH + (shelfSpace * i) / (count + 1));
    const half = 0.9;
    const marks = [innerY + drawerH, ...shelves, h - T];
    const gaps = [];
    for (let i = 0; i < marks.length - 1; i += 1) {
        const start = i === 0 ? marks[i] : marks[i] + half;
        const end = i === marks.length - 2 ? marks[i + 1] : marks[i + 1] - half;
        if (end - start > 1) gaps.push({ y: (start + end) / 2, size: end - start });
    }
    const drawers = [];
    if (mod.drawers > 0 && mod.kind !== 'end') {
        const slot = drawerH / mod.drawers;
        for (let i = 0; i < mod.drawers; i += 1) drawers.push({ y: innerY + slot * i + slot / 2, size: slot });
    }
    return { gaps, drawers };
}

function dimLine(parent, x1, y1, z1, x2, y2, z2) {
    const geom = new THREE.BufferGeometry().setFromPoints([
        new THREE.Vector3(x1, y1, z1),
        new THREE.Vector3(x2, y2, z2),
    ]);
    parent.add(new THREE.Line(geom, measureMat));
}

function measureLabel(parent, x, y, z, text) {
    const anchor = new THREE.Object3D();
    anchor.position.set(x, y, z);
    parent.add(anchor);
    const node = document.createElement('div');
    node.className = 'measure';
    node.textContent = text;
    el('measure-layer').appendChild(node);
    measureNodes.push({ anchor, node });
}

function clearMeasures() {
    measureNodes.splice(0).forEach((item) => item.node.remove());
}

function addModuleMeasures(group, mod) {
    const box = footprint(mod);
    const w = box.w;
    const h = box.h;
    const d = mod.kind === 'corner' ? state.depth : box.d;
    const face = d + 5;
    dimLine(group, 0, -6, face, w, -6, face);
    dimLine(group, 0, -8, face, 0, -4, face);
    dimLine(group, w, -8, face, w, -4, face);
    measureLabel(group, w / 2, -14, face, cmText(w));
    dimLine(group, -8, 0, face, -8, h, face);
    dimLine(group, -10, 0, face, -6, 0, face);
    dimLine(group, -10, h, face, -6, h, face);
    measureLabel(group, w * 0.68, h * 0.5, face, cmText(h));
    dimLine(group, w + 8, 10, 0, w + 8, 10, d);
    measureLabel(group, w + 16, 18, d / 2, cmText(d));
    if (mod.kind === 'end') return;
    const bays = interiorGaps(mod, h);
    bays.gaps.forEach((gap) => {
        dimLine(group, w * 0.28, gap.y - gap.size / 2, face, w * 0.28, gap.y + gap.size / 2, face);
        measureLabel(group, w * 0.28, gap.y, face + 2, cmText(gap.size));
    });
    bays.drawers.forEach((drawer) => {
        measureLabel(group, w * 0.72, drawer.y, face + 2, cmText(drawer.size));
    });
}

function updateMeasures() {
    const layer = el('measure-layer');
    layer.classList.toggle('hidden', !showMeasures);
    if (!showMeasures) return;
    const rect = el('viewport').getBoundingClientRect();
    measureNodes.forEach((item) => {
        const world = item.anchor.getWorldPosition(new THREE.Vector3());
        const point = project(world);
        const inside = !point.behind && point.x > -40 && point.y > -20 && point.x < rect.width + 40 && point.y < rect.height + 20;
        item.node.classList.toggle('hidden', !inside);
        item.node.style.left = `${point.x}px`;
        item.node.style.top = `${point.y}px`;
    });
}

function clearGroup(group) {
    while (group.children.length) {
        const child = group.children.pop();
        child.traverse((obj) => {
            if (obj.geometry) obj.geometry.dispose();
        });
    }
}

function rebuild() {
    if (!cabinetRoot) return;
    doorPivots.length = 0;
    slides.length = 0;
    clearMeasures();
    clearGroup(cabinetRoot);
    const spots = layout(state.modules);
    if (!spots) return;
    spots.forEach((spot) => {
        const group = buildModule(spot.mod, false);
        group.position.set(spot.x, 0, spot.z);
        group.rotation.y = spot.rot;
        cabinetRoot.add(group);
    });
    snapDoors();
}

function doorAngle(pivot) {
    const open = pivot.userData.mod?.open || 0;
    const sign = pivot.userData.kind === 'accordion'
        ? pivot.userData.sign
        : pivot.userData.kind === 'corner-door'
            ? (pivot.userData.sign || 1)
            : (pivot.userData.hinge === 'left' ? -1 : 1);
    return sign * open * (pivot.userData.kind === 'accordion' ? 1.25 : 1.45);
}

function snapDoors() {
    doorPivots.forEach((pivot) => {
        const open = pivot.userData.mod?.open || 0;
        if (pivot.userData.kind === 'tambour') {
            pivot.position.x = (pivot.userData.baseX || 0) + open * pivot.userData.travel;
            return;
        }
        pivot.rotation.y = doorAngle(pivot);
    });
    slides.forEach((group) => {
        const open = group.userData.slide?.drawersOpen > 0.5 ? 1 : 0;
        const amount = (group.userData.rest || 0) + open * (group.userData.travel || 28);
        if (group.userData.axis === 'x') group.position.x = amount;
        else group.position.z = amount;
    });
}

function paintDoors(dt) {
    doorPivots.forEach((pivot) => {
        const open = pivot.userData.mod?.open || 0;
        if (pivot.userData.kind === 'tambour') {
            pivot.position.x = THREE.MathUtils.damp(pivot.position.x, (pivot.userData.baseX || 0) + open * pivot.userData.travel, 8, dt);
            return;
        }
        pivot.rotation.y = THREE.MathUtils.damp(pivot.rotation.y, doorAngle(pivot), 8, dt);
    });
    slides.forEach((group) => {
        const open = group.userData.slide?.drawersOpen > 0.5 ? 1 : 0;
        const amount = (group.userData.rest || 0) + open * (group.userData.travel || 28);
        if (group.userData.axis === 'x') group.position.x = THREE.MathUtils.damp(group.position.x, amount, 8, dt);
        else group.position.z = THREE.MathUtils.damp(group.position.z, amount, 8, dt);
    });
}

function project(vector) {
    const v = vector.clone().project(camera);
    const rect = el('viewport').getBoundingClientRect();
    return {
        x: (v.x * 0.5 + 0.5) * rect.width,
        y: (-v.y * 0.5 + 0.5) * rect.height,
        behind: v.z > 1,
    };
}

function updateHud() {
    const ui = el('selection-ui');
    const handle = el('height-handle');
    const mod = selected();
    const group = mod ? cabinetRoot.getObjectByName(`mod-${mod.id}`) : null;
    if (!group) {
        ui.classList.add('hidden');
        handle.classList.add('hidden');
        return;
    }
    const box = new THREE.Box3().setFromObject(group);
    const center = new THREE.Vector3();
    box.getCenter(center);
    const under = project(new THREE.Vector3(center.x, box.min.y + 8, box.max.z));
    const top = project(new THREE.Vector3(center.x, box.max.y + 6, center.z));
    el('selection-name').textContent = mod.name;
    ui.classList.toggle('hidden', under.behind);
    handle.classList.toggle('hidden', top.behind || Boolean(mod.glb));
    ui.style.left = `${under.x}px`;
    ui.style.top = `${under.y}px`;
    handle.style.left = `${top.x}px`;
    handle.style.top = `${top.y}px`;
}

function frameCamera() {
    setCameraView('perspective');
}

function setCameraView(mode) {
    if (!camera || !controls) return;
    const box = new THREE.Box3();
    if (cabinetRoot && cabinetRoot.children.length) box.setFromObject(cabinetRoot);
    if (box.isEmpty()) box.set(new THREE.Vector3(0, 0, 0), new THREE.Vector3(wallLimit(), roomHeightLimit(), 60));
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3());
    const wall = wallLimit();
    const fov = mode === 'top' ? 42 : 30;
    camera.fov = fov;
    const half = (fov * Math.PI) / 360;
    const dist = Math.max(size.y, size.x, 80) * 0.5 / Math.tan(half);
    const yawOf = { front: 0, left: -0.62, right: 0.62, left45: -0.74, right45: 0.74, perspective: 0.62, top: 0 };
    let yaw = yawOf[mode] ?? 0.42;
    const openRight = wall - (center.x + size.x * 0.5);
    const openLeft = center.x - size.x * 0.5;
    if (mode === 'perspective' || mode === 'right45' || mode === 'left45') {
        const want = mode === 'left45' ? -1 : 1;
        const room = want > 0 ? openRight : openLeft;
        yaw = (room > 70 ? want : (openRight >= openLeft ? 1 : -1)) * Math.abs(yaw);
    }
    let x = center.x + Math.sin(yaw) * dist * (mode === 'top' ? 0 : 1);
    x = Math.min(wall - 28, Math.max(28, x));
    const dx = x - center.x;
    const forward = Math.sqrt(Math.max(dist * dist - dx * dx, (size.z + 70) ** 2));
    const y = mode === 'top' ? center.y + dist : Math.min(162, center.y + size.y * 0.02);
    const z = mode === 'top' ? center.z + 1 : center.z + forward;
    controls.minPolarAngle = mode === 'top' ? 0 : 0.08;
    controls.maxPolarAngle = mode === 'top' ? Math.PI : Math.PI / 2 - 0.02;
    controls.target.copy(center);
    camera.position.set(x, y, z);
    camera.near = 1;
    camera.far = Math.max(4000, dist * 8);
    camera.updateProjectionMatrix();
    controls.update();
    aimCabinetShadow(center, size);
}

function tick() {
    const dt = Math.min(0.05, clock.getDelta());
    paintDoors(dt);
    controls.update();
    updateHud();
    updateMeasures();
    renderer.render(scene, camera);
}

function openPhoneSheet(name) {
    const phone = window.matchMedia('(max-width: 900px)').matches;
    if (!phone) {
        if (name === 'modules') el('library').scrollIntoView();
        else el('inspector').classList.add('is-open');
        return;
    }
    const same = document.body.dataset.sheet === name;
    el('library').classList.remove('is-open');
    el('inspector').classList.remove('is-open');
    el('project-sheet').classList.remove('is-open');
    if (same) {
        document.body.dataset.sheet = '';
        requestAnimationFrame(resize);
        return;
    }
    document.body.dataset.sheet = name;
    if (name === 'modules') el('library').classList.add('is-open');
    else if (name === 'project') el('project-sheet').classList.add('is-open');
    else el('inspector').classList.add('is-open');
    requestAnimationFrame(resize);
}

function resize() {
    const viewport = el('viewport');
    const width = viewport.clientWidth || 600;
    const height = viewport.clientHeight || 400;
    camera.aspect = width / Math.max(1, height);
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, true);
}

function init3d() {
    const viewport = el('viewport');
    scene = new THREE.Scene();
    scene.background = new THREE.Color('#e7e6e3');
    camera = new THREE.PerspectiveCamera(42, 1, 2, 4000);
    renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 0.98;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    viewport.appendChild(renderer.domElement);

    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.18).texture;
    scene.environmentIntensity = 0.18;

    scene.add(new THREE.HemisphereLight(0xf4f2ee, 0xd8d4cc, 0.62));
    const key = new THREE.DirectionalLight(0xfffaf6, 0.78);
    key.position.set(200, 280, 360);
    key.castShadow = true;
    key.shadow.mapSize.set(2048, 2048);
    key.shadow.radius = 1.6;
    key.shadow.bias = -0.00018;
    key.shadow.normalBias = 0.12;
    key.shadow.camera.near = 10;
    key.shadow.camera.far = 900;
    key.shadow.camera.left = -160;
    key.shadow.camera.right = 160;
    key.shadow.camera.top = 160;
    key.shadow.camera.bottom = -160;
    scene.add(key);
    scene.add(key.target);
    key.shadow.intensity = 0.74;
    keyLight = key;
    const cavity = new THREE.DirectionalLight(0xfff8f4, 0.2);
    cavity.position.set(180, 150, 420);
    scene.add(cavity);
    scene.add(cavity.target);
    cavityLight = cavity;
    const fill = new THREE.DirectionalLight(0xf7f6f4, 0.22);
    fill.position.set(40, 150, 280);
    scene.add(fill);
    const bounce = new THREE.DirectionalLight(0xe7e4de, 0.12);
    bounce.position.set(-160, 80, 60);
    scene.add(bounce);

    const floor = new THREE.Mesh(
        new THREE.BoxGeometry(WALL_X + 160, 2, WALL_Z + 160),
        new THREE.MeshStandardMaterial({ color: '#c4b29a', roughness: 0.58, metalness: 0.02 })
    );
    floor.position.set(WALL_X / 2, -1, WALL_Z / 2);
    floor.receiveShadow = true;
    scene.add(floor);
    const wallMat = new THREE.MeshStandardMaterial({ color: '#f7f6f4', roughness: 0.9 });
    const back = new THREE.Mesh(new THREE.BoxGeometry(WALL_X, ROOM_H, 8), wallMat);
    back.position.set(WALL_X / 2, ROOM_H / 2, -4);
    back.receiveShadow = true;
    scene.add(back);
    room.floor = floor;
    room.back = back;
    const left = new THREE.Mesh(new THREE.BoxGeometry(8, ROOM_H, WALL_Z), wallMat);
    left.position.set(-4, ROOM_H / 2, WALL_Z / 2);
    left.receiveShadow = true;
    scene.add(left);
    const right = new THREE.Mesh(new THREE.BoxGeometry(8, ROOM_H, WALL_Z), wallMat);
    right.receiveShadow = true;
    scene.add(right);
    room.left = left;
    room.right = right;

    cabinetRoot = new THREE.Group();
    scene.add(cabinetRoot);
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.maxPolarAngle = Math.PI * 0.49;
    controls.minDistance = 80;
    controls.maxDistance = 1100;
    controls.enablePan = true;
    controls.screenSpacePanning = true;
    controls.mouseButtons.LEFT = THREE.MOUSE.ROTATE;
    controls.touches.ONE = THREE.TOUCH.ROTATE;
    controls.touches.TWO = THREE.TOUCH.DOLLY_PAN;
    frameCamera();
    clock = new THREE.Clock();
    resize();
    window.addEventListener('resize', resize);
    renderer.setAnimationLoop(tick);
    el('hold-select')?.classList.add('hidden');

    const clickSlop = 8;
    renderer.domElement.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        if (pointerPress && pointerPress.pointerId !== event.pointerId) {
            pointerPress.moved = true;
            return;
        }
        const id = moduleUnder(event);
        pointerDown = { x: event.clientX, y: event.clientY, t: performance.now() };
        pointerPress = {
            x: event.clientX,
            y: event.clientY,
            pointerId: event.pointerId,
            id,
            moved: false,
        };
        if (!id) return;
        controls.enabled = false;
        if (selectedId !== id) {
            selectedId = id;
            render();
        }
    }, true);
    window.addEventListener('pointermove', (event) => {
        if (pointerPress && Math.hypot(event.clientX - pointerPress.x, event.clientY - pointerPress.y) > clickSlop) {
            pointerPress.moved = true;
            if (pointerPress.id && !moduleDrag) beginModuleDrag(pointerPress.id, event);
        }
        if (!moduleDrag || heightDrag) return;
        if (Math.hypot(event.clientX - moduleDrag.x, event.clientY - moduleDrag.y) < 8) return;
        if (!moduleDrag.active) {
            snapshot();
            moduleDrag.active = true;
            dragId = moduleDrag.id;
            controls.enabled = false;
        }
        dragModule(moduleDrag.id, event);
    });
    const beginModuleDrag = (id, event) => {
        pointerDown = { x: event.clientX, y: event.clientY };
        moduleDrag = { id, x: event.clientX, y: event.clientY, active: false };
        controls.enabled = false;
    };
    el('selection-name').addEventListener('pointerdown', (event) => {
        const mod = selected();
        if (!mod) return;
        event.preventDefault();
        event.stopPropagation();
        beginModuleDrag(mod.id, event);
    });
    const finishModuleDrag = () => {
        moduleDrag = null;
        dragId = null;
        pointerDown = null;
        controls.enabled = true;
        clearDragGhost();
        render();
        scheduleSave();
    };
    renderer.domElement.addEventListener('pointerup', (event) => {
        const press = pointerPress;
        pointerPress = null;
        if (moduleDrag?.active) {
            finishModuleDrag();
            return;
        }
        controls.enabled = true;
        moduleDrag = null;
        pointerDown = null;
        if (!press || press.moved || !press.id) return;
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        if (Math.hypot(event.clientX - press.x, event.clientY - press.y) > clickSlop) return;
        if (selectedId === press.id) return;
        selectedId = press.id;
        render();
    });
    window.addEventListener('pointerup', (event) => {
        if (!moduleDrag?.active || event.target === renderer.domElement) return;
        finishModuleDrag();
    });

    const handle = el('height-handle');
    handle.addEventListener('pointerdown', (event) => {
        event.stopPropagation();
        const mod = selected();
        heightDrag = { y: event.clientY, h: moduleHeight(mod), id: mod?.id || null };
        controls.enabled = false;
        snapshot();
    });
    window.addEventListener('pointermove', (event) => {
        if (!heightDrag) return;
        const next = clamp(Math.round(heightDrag.h + (heightDrag.y - event.clientY) * 0.35), H_MIN, H_MAX);
        const mod = state.modules.find((item) => item.id === heightDrag.id);
        if (mod) mod.height = next;
        else {
            state.height = next;
            state.modules.forEach((item) => { item.height = next; });
        }
        rebuild();
        renderSummary();
    });
    window.addEventListener('pointerup', () => {
        if (!heightDrag) return;
        heightDrag = null;
        controls.enabled = true;
        render();
        scheduleSave();
    });
}

function renderThumb(product) {
    const key = `${state.series}|${product.id}|${state.carcassId}|${state.doorColorId}|${state.height}|${state.kulp !== false ? 1 : 0}`;
    if (thumbCache.has(key)) return thumbCache.get(key);
    if (!thumbRenderer) {
        thumbRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        thumbRenderer.setSize(320, 200, false);
        thumbRenderer.outputColorSpace = THREE.SRGBColorSpace;
        thumbRenderer.toneMapping = THREE.ACESFilmicToneMapping;
        thumbScene = new THREE.Scene();
        thumbScene.background = new THREE.Color('#f3f2ef');
        thumbScene.environment = scene.environment;
        thumbScene.add(new THREE.HemisphereLight(0xffffff, 0xc8c2b8, 1.1));
        const light = new THREE.DirectionalLight(0xffffff, 1.1);
        light.position.set(40, 80, 90);
        thumbScene.add(light);
        thumbCamera = new THREE.PerspectiveCamera(32, 320 / 200, 1, 2000);
    }
    const previous = [...thumbScene.children];
    previous.forEach((child) => {
        if (child.isLight) return;
        child.traverse((obj) => { if (obj.geometry) obj.geometry.dispose(); });
        thumbScene.remove(child);
    });
    const sample = createModule(product.id);
    sample.id = `thumb-${product.id}`;
    const group = buildModule(sample, true);
    thumbScene.add(group);
    const box = footprint(sample);
    const dist = Math.max(box.w, 70) * 1.45;
    thumbCamera.position.set(box.w * 0.72, state.height * 0.62, state.depth + dist);
    thumbCamera.lookAt(box.w / 2, state.height * 0.4, 0);
    thumbRenderer.render(thumbScene, thumbCamera);
    const url = thumbRenderer.domElement.toDataURL('image/png');
    thumbCache.set(key, url);
    return url;
}

function renderSeries() {
    if (!document.getElementById('series')) return;
    const active = seriesOf(selected());
    el('series').innerHTML = SERIES.map((item) => `<button type="button" data-series="${item.id}" class="${item.id === active ? 'is-on' : ''}">${item.name}</button>`).join('');
    el('series').querySelectorAll('[data-series]').forEach((button) => {
        button.onclick = () => {
            const mod = selected();
            const product = mod ? productById(mod.product) : null;
            commit(() => {
                const series = button.dataset.series;
                const plain = series === 'duz' ? solidDoorColor() : '';
                if (mod && product && product.kind !== 'end' && product.door !== 'panjur') {
                    mod.door = doorFor(product, series);
                    if (plain) mod.doorColorId = plain;
                    return;
                }
                state.series = series;
                if (plain) state.doorColorId = plain;
            });
        };
    });
}

function catalogPhoto(file) {
    const base = String(window.WARDROBE.photosUrl || '').replace(/\/$/, '');
    return `${base}/${file}`;
}

function moduleChoices() {
    if (dxfModules.length) return dxfModules.map((item) => [item.id, item.name]);
    return [['aski', 'Askılık'], ['raf', 'Raflı'], ['pantolon', 'Pantolonluk'], ['ayakkabi', 'Ayakkabılık'], ['cekmece', 'Çekmeceli'], ['cift', 'Çift askı']];
}

function foldText(text) {
    return String(text || '').toLocaleLowerCase('tr').replaceAll('ı', 'i').replaceAll('ğ', 'g').replaceAll('ü', 'u').replaceAll('ş', 's').replaceAll('ö', 'o').replaceAll('ç', 'c');
}

function renderLibrary() {
    const host = el('library-body');
    const query = foldText(el('module-search')?.value || '');
    const matches = (item) => !query || foldText(`${item.name} ${item.spec} ${item.width} ${item.category || ''}`).includes(query);
    const labels = { 'y26-tek': 'Yeni tek kapılı', 'y26-cift': 'Yeni çift kapılı', tek: 'Tek Kapılı', cift: 'Çift Kapılı', kose: 'Köşe' };
    const body = ['y26-tek', 'y26-cift', 'tek', 'cift', 'kose'].map((key) => {
        const cards = glbCatalog.filter((item) => item.category === key && matches(item)).map((item) => `
            <div class="card${glbFailed.has(item.id) ? ' is-broken' : ''}" data-glb="${item.id}">
                <img src="${item.previewPath}" alt="">
                <strong>${item.name}</strong>
                <span>${String(item.spec || '').startsWith('Y26') ? `${item.spec} · ` : ''}${Math.round(item.width * 100)} × ${Math.round(item.height * 100)} × ${Math.round(item.depth * 100)} cm</span>
                <button type="button" class="card-add">Ekle</button>
            </div>`).join('');
        return cards ? `<p class="catalog-kicker">${labels[key]}</p><div class="catalog">${cards}</div>` : '';
    }).join('');
    host.innerHTML = body ? `<p class="hint">Modülü sahneye sürükle. Basınca da eklenir.</p>${body}` : '<p class="hint">Eşleşen modül yok.</p>';
    const bindCard = (button, kind, id) => {
        button.addEventListener('pointerdown', (event) => {
            if (event.button !== 0 || event.target.closest('.card-add')) return;
            button.dataset.skipClick = '';
            const listed = glbCatalog.find((item) => item.id === id) || cadById(id);
            catalogDrag = {
                kind,
                id,
                x: event.clientX,
                y: event.clientY,
                active: false,
                width: Math.round(Number(listed?.width) > 3 ? listed.width : (listed?.width || 0.9) * 100),
                name: button.querySelector('strong')?.textContent || 'Modül',
            };
            noteDrop({ phase: 'down', id, x: Math.round(event.clientX), y: Math.round(event.clientY) });
        });
        button.addEventListener('click', (event) => {
            if (event.target.closest('.card-add')) return;
            if (button.dataset.skipClick === '1') {
                button.dataset.skipClick = '';
                event.preventDefault();
                return;
            }
            if (kind === 'glb') addGlbModule(id);
            else if (kind === 'cad') addCadModule(id);
            else addProduct(id);
        });
        button.querySelector('.card-add')?.addEventListener('click', (event) => {
            event.stopPropagation();
            if (kind === 'glb') addGlbModule(id);
            else if (kind === 'cad') addCadModule(id);
            else addProduct(id);
        });
    };
    host.querySelectorAll('[data-glb]').forEach((button) => bindCard(button, 'glb', button.dataset.glb));
    host.querySelectorAll('[data-cad]').forEach((button) => bindCard(button, 'cad', button.dataset.cad));
    host.querySelectorAll('[data-product]').forEach((button) => bindCard(button, 'product', button.dataset.product));
    ensureCatalogDrag();
    bindCardZoom();
}

function bindCardZoom() {
    const host = el('library-body');
    const zoom = el('card-zoom');
    if (!host || !zoom || host.dataset.zoom === '1') return;
    host.dataset.zoom = '1';
    const hide = () => zoom.classList.add('hidden');
    const show = (card) => {
        const source = card.querySelector('img');
        if (!source?.src) return;
        const image = zoom.querySelector('img');
        image.src = source.currentSrc || source.src;
        image.alt = card.querySelector('strong')?.textContent || '';
        zoom.querySelector('strong').textContent = card.querySelector('strong')?.textContent || '';
        zoom.querySelector('span').textContent = card.querySelector('span')?.textContent || '';
        zoom.classList.remove('hidden');
        const rect = card.getBoundingClientRect();
        const width = zoom.offsetWidth;
        const height = zoom.offsetHeight;
        let left = rect.right + 14;
        if (left + width > window.innerWidth - 12) left = Math.max(12, rect.left - width - 14);
        let top = rect.top + rect.height / 2 - height / 2;
        top = Math.min(Math.max(12, top), Math.max(12, window.innerHeight - height - 12));
        zoom.style.left = `${left}px`;
        zoom.style.top = `${top}px`;
    };
    host.addEventListener('pointerover', (event) => {
        if (window.matchMedia('(hover: none)').matches) return;
        const card = event.target.closest?.('.card');
        if (!card || !host.contains(card)) return;
        show(card);
    });
    host.addEventListener('pointerout', (event) => {
        const card = event.target.closest?.('.card');
        if (!card) return;
        if (event.relatedTarget?.closest?.('.card') === card) return;
        hide();
    });
    host.addEventListener('pointerdown', hide);
    host.addEventListener('scroll', hide, { passive: true });
}

function decorLabel(id) {
    const item = decor(id);
    return `${item.name}${item.code ? ` ${item.code}` : ''}`;
}

function decorPicker(active, attr, label, strong) {
    return `
        <div class="field${strong ? ' field-key' : ''}" data-picker="${attr}">
            <label>${label}: ${decorLabel(active)}</label>
            <input class="decor-search" data-search="${attr}" placeholder="Kastamonu rengi ara" value="">
            <div class="decors"></div>
        </div>`;
}

function isPlainDecor(item) {
    const name = (item.name || '').toLocaleLowerCase('tr');
    const patterned = /meşe|ceviz|çam|mermer|marmo|marmol|granit|galaxy|kayın|kiraz|huş|bambu|wenge|teak|sonoma|alpi|legna|traverten|akasya|söğüt|erik|palisander|akçaağaç|armut|calcutta|calacatta|carrara|luanda|padova|dafne|elvira|kuba|costa|botanik|kaktüs|jasper|orient|lotus|bermuda|cosmos|belek|spring|gaia|agra|hotan|herat|kaşgar|tunus|verde|rosso|keten|sofia|legna/;
    if (patterned.test(name)) return false;
    return !/^[AF]/i.test(item.code || '');
}

function paintDecors(list, active, query) {
    const q = query.trim().toLocaleLowerCase('tr');
    const pool = materials.filter((item) => {
        if (!q) return true;
        return `${item.name} ${item.code} ${item.groupLabel}`.toLocaleLowerCase('tr').includes(q);
    });
    const sortFn = (a, b) => {
        if (a.id === active) return -1;
        if (b.id === active) return 1;
        const rank = (item) => (item.group === 'melamin-kapli-panel' ? 0 : 1);
        return rank(a) - rank(b);
    };
    const plain = pool.filter((item) => isPlainDecor(item)).sort(sortFn);
    const fancy = pool.filter((item) => !isPlainDecor(item)).sort(sortFn);
    const button = (item) => `
        <button type="button" class="decor ${item.id === active ? 'is-on' : ''}" data-decor="${item.id}">
            <img alt="" src="${item.image || ''}">
            <span><b>${item.name}</b>${item.code || ''} · ${item.groupLabel || ''}</span>
        </button>`;
    const block = (title, items, limit) => {
        const shown = items.slice(0, q ? 40 : limit);
        if (!shown.length) return '';
        return `<p class="decor-kicker">${title}</p>${shown.map(button).join('')}`;
    };
    list.innerHTML = `${block('Düz renkler', plain, 24)}${block('Kastamonu desenleri', fancy, 18)}` || '<p class="hint">Renk bulunamadı.</p>';
}

function bindDecor(root, attr, active, onPick) {
    const picker = root.querySelector(`[data-picker="${attr}"]`);
    if (!picker) return;
    const list = picker.querySelector('.decors');
    const search = picker.querySelector('[data-search]');
    const refresh = () => paintDecors(list, active, search.value);
    refresh();
    search.addEventListener('input', refresh);
    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-decor]');
        if (!button) return;
        onPick(button.dataset.decor);
    });
}

function swatchPicker(list, active, attr, label, strong) {
    const current = list.find((item) => item.id === active) || list[0];
    return `<div class="field${strong ? ' field-key' : ''}" data-picker="${attr}"><label>${label}: ${current.name}</label><div class="swatches">${list.map((item) => `<button type="button" class="swatch ${item.id === active ? 'is-on' : ''}" style="background:${item.color}" title="${item.name}" data-${attr}="${item.id}"></button>`).join('')}</div></div>`;
}

function bindSwatch(root, attr, onPick) {
    root.querySelectorAll(`[data-${attr}]`).forEach((button) => {
        button.onclick = () => onPick(button.dataset[attr]);
    });
}

function swingPicker(mod) {
    if (mod.kind === 'end' || mod.door === 'none' || mod.door === 'panjur' || mod.door === 'accordion') return '';
    const swing = swingOf(mod);
    const options = doorCount(mod) === 1
        ? [['left', 'Sola açılsın'], ['right', 'Sağa açılsın']]
        : [['pair', 'Karşılıklı'], ['left', 'Sola'], ['right', 'Sağa']];
    const openLabel = mod.open > 0.5 ? 'Kapağı kapat' : 'Kapağı aç';
    return `<div class="field field-key"><label>Kapak açılışı</label><div class="seg">${options.map(([id, name]) => `<button type="button" data-swing="${id}" class="${id === swing ? 'is-on' : ''}">${name}</button>`).join('')}</div><button type="button" class="btn" id="mod-open" style="width:100%;margin-top:8px">${openLabel}</button>${doorTools(mod)}</div>`;
}

function doorsAreOpen() {
    const doors = state.modules.filter((mod) => mod.kind !== 'end' && mod.door !== 'none');
    return doors.length > 0 && doors.every((mod) => (mod.open || 0) > 0.5);
}

function allDoorButton() {
    if (!state.modules.some((mod) => mod.kind !== 'end' && mod.door !== 'none')) return '';
    const label = doorsAreOpen() ? 'Tüm kapakları kapat' : 'Tüm kapakları aç';
    return `<button type="button" class="btn" id="mod-doors-all" style="width:100%;margin-top:8px">${label}</button>`;
}

function ledPicker(mod) {
    if (!mod || mod.kind === 'end') return '';
    const style = ledStyleOf(mod);
    const options = [['shelf', 'Raflarda LED'], ['side', 'Yanlarda boydan LED'], ['off', 'LED kapalı']];
    return `<div class="field field-key"><label>LED</label><div class="seg">${options.map(([id, name]) => `<button type="button" data-led="${id}" class="${id === style ? 'is-on' : ''}">${name}</button>`).join('')}</div></div>`;
}

function doorTools(mod) {
    if (!mod || mod.kind === 'end' || mod.door === 'none') return allDoorButton();
    const one = hiddenDoorIds.has(mod.id) ? 'Kapakları göster' : 'Kapakları gösterme';
    const all = hideAllDoors ? 'Tüm kapakları göster' : 'Tüm dolapların kapaklarını gösterme';
    return `<button type="button" class="btn" id="mod-hide-door" style="width:100%;margin-top:8px">${one}</button><button type="button" class="btn" id="mod-hide-all" style="width:100%;margin-top:8px">${all}</button>${allDoorButton()}`;
}

function renderProduction() {
    const lines = state.modules.map((item, index) => {
        const swing = swingLabel(item);
        return `<li${item.id === selectedId ? ' class="is-on"' : ''}><b>${index + 1}. ${item.name}</b><br>${item.width} × ${moduleHeight(item)} × ${item.kind === 'corner' ? item.width : state.depth} cm · ${specOf(item)} · ${doorLabel(item)}${swing ? ` · ${swing}` : ''} · ${item.plinth === false ? 'Bazasız' : 'Baza'} · ${item.kulp === false ? 'Kulpsuz' : 'Kulp'} · ${item.led === false ? 'LEDsiz' : 'LED'}<br>${decorLabel(item.doorColorId)} dolap · ${decorLabel(backOf(item))} arkalık · ${decorLabel(shelfOf(item))} raf · ${decorLabel(item.carcassId)} gövde<br>${aluminumOf(item).name} alüminyum · ${glassOf(item).name} cam</li>`;
    }).join('');
    el('production').innerHTML = lines || '<li>Henüz dolap yok.</li>';
    el('production-meta').textContent = state.customerName ? [state.customerName, state.phone].filter(Boolean).join(' · ') : 'Müşteri kaydı yok.';
    const count = state.modules.length;
    el('btn-production').textContent = count ? `Üretim listesi (${count})` : 'Üretim listesi';
}

function moduleConfigHtml(mod) {
    const shelves = moduleShelfCount(mod);
    const drawers = moduleDrawerCount(mod);
    const shelfField = shelves > 0
        ? `<div class="field"><label>Raf adedi</label><input id="mod-shelves" type="number" min="1" max="12" value="${shelves}"></div>`
        : '';
    const drawerField = mod.glb && glbDrawerCount(mod) > 0
        ? `<div class="field field-key"><label>Çekmece</label>${decorPicker(drawerOf(mod), 'drawer', 'Çekmece rengi', false)}<button type="button" class="btn" id="mod-drawers" style="width:100%">${mod.drawersOpen > 0.5 ? 'Çekmeceleri kapat' : 'Çekmeceleri aç'}</button></div>`
        : drawers > 0
        ? `<div class="field field-key"><label>Çekmece</label>
            <div class="field"><label>Adet</label><input id="mod-drawer-count" type="number" min="1" max="8" value="${drawers}"></div>
            ${decorPicker(drawerOf(mod), 'drawer', 'Çekmece rengi', false)}
            <button type="button" class="btn" id="mod-drawers" style="width:100%">${mod.drawersOpen > 0.5 ? 'Çekmeceleri kapat' : 'Çekmeceleri aç'}</button>
        </div>`
        : '';
    const listed = glbCatalog.find((entry) => entry.id === mod.glb);
    const boardNote = listed?.boardMm
        ? `<p class="hint">Raf, yan panel ve çekmece önü ${listed.boardMm} mm. Arkalık ${listed.backMm} mm. Çekmece kutusu: yan ve arka 16 mm, taban 8 mm. Dekor görseli temsili dijital dokudur.</p>`
        : '';
    return `
        <p class="picked">Seçili dolap</p>
        <h2>${mod.name}</h2>
        <p class="hint">${mod.width} cm. Yerini sahnede sürükleyerek değiştir. Kapak sağda seçilir.</p>
        ${boardNote}
        ${mod.kind === 'end' ? '' : `<div class="field field-key"><label>Kapak</label><div class="seg"><button type="button" data-door="solid" class="${mod.door === 'solid' ? 'is-on' : ''}">Kapaklı</button><button type="button" data-door="glass" class="${mod.door === 'glass' ? 'is-on' : ''}">Cam kapaklı</button></div></div>`}
        ${mod.glb ? '' : `<div class="field"><label>Genişlik (cm)</label><input id="mod-w" type="number" min="30" max="200" value="${mod.width}"></div>`}
        ${shelfField}
        ${drawerField}
        ${ledPicker(mod)}
        ${swingPicker(mod)}
        ${!swingPicker(mod) ? doorTools(mod) : ''}
        ${decorPicker(mod.carcassId, 'carcass', 'Gövde rengi', true)}
        ${decorPicker(mod.doorColorId, 'door', 'Dolap rengi', true)}
        ${decorPicker(backOf(mod), 'back', 'Arkalık rengi', true)}
        ${decorPicker(shelfOf(mod), 'shelf', 'Rafların rengi', true)}
        ${swatchPicker(ALUMINUM, aluminumOf(mod).id, 'alum', 'Alüminyum kapak', true)}
        ${swatchPicker(GLASS, glassOf(mod).id, 'glass', 'Cam')}
        <button type="button" class="btn" id="apply-all" style="width:100%;margin-top:8px">Renkleri tümüne uygula</button>
        <label class="check"><input id="mod-plinth" type="checkbox" ${mod.plinth === false ? '' : 'checked'}> Bu dolapta baza olsun</label>
        <label class="check"><input id="mod-plinth-hidden" type="checkbox" ${mod.plinthHidden ? 'checked' : ''} ${mod.plinth === false ? 'disabled' : ''}> Gizli baza olsun</label>
        <label class="check"><input id="mod-kulp" type="checkbox" ${mod.kulp === false ? '' : 'checked'}> Bu dolapta kulp olsun</label>
        ${mod.glb ? '' : `<div class="field"><label>Bu dolabın yüksekliği (cm)</label><input id="mod-h" type="number" min="${mod.dxf === 'ust' ? 40 : H_MIN}" max="${H_MAX}" value="${moduleHeight(mod)}"></div>`}
        ${mod.kind !== 'end' && !mod.glb ? `<button type="button" class="btn" id="mod-upper" style="width:100%;margin-bottom:12px">${upperHeight(mod) ? 'Üst dolabı kaldır' : 'Üst dolap ekle'}</button>` : ''}
        ${mod.kind !== 'end' && (mod.door === 'panjur' || mod.door === 'accordion') ? `<div class="field"><button type="button" class="btn" id="mod-open">${mod.open > 0.5 ? 'Kapağı kapat' : 'Kapağı aç'}</button></div>` : ''}
        <div style="display:flex;gap:8px">
            <button type="button" class="btn" id="mod-left">Sola al</button>
            <button type="button" class="btn" id="mod-right">Sağa al</button>
        </div>`;
}

function bindModuleConfig(root, mod) {
    root.querySelectorAll('[data-door]').forEach((button) => {
        button.onclick = () => {
            if (mod.door === button.dataset.door) return;
            commit(() => { mod.door = button.dataset.door; });
        };
    });
    root.querySelectorAll('[data-swing]').forEach((button) => {
        button.onclick = () => commit(() => { mod.swing = button.dataset.swing; });
    });
    root.querySelectorAll('[data-interior]').forEach((button) => {
        button.onclick = () => commit(() => applyInterior(mod, button.dataset.interior));
    });
    const widthInput = root.querySelector('#mod-w');
    if (widthInput) widthInput.addEventListener('change', (event) => commit(() => {
        mod.width = clamp(Number(event.target.value), 30, 200);
    }));
    const shelfInput = root.querySelector('#mod-shelves');
    if (shelfInput) shelfInput.addEventListener('change', (event) => commit(() => {
        const count = clamp(Number(event.target.value), 1, 12);
        if (cadById(mod.cad)) {
            mod.shelfCount = count;
            mod.shelves = count;
            return;
        }
        mod.shelves = count;
        mod.layout = 'custom';
        mod.product = 'custom';
        if (!mod.interior) mod.interior = 'raf';
    }));
    const drawerCount = root.querySelector('#mod-drawer-count');
    if (drawerCount) drawerCount.addEventListener('change', (event) => commit(() => {
        const count = clamp(Number(event.target.value), 1, 8);
        if (cadById(mod.cad)) {
            mod.drawerCount = count;
            mod.drawers = count;
            return;
        }
        mod.drawers = count;
        mod.layout = 'custom';
        mod.product = 'custom';
    }));
    const drawerHeight = root.querySelector('#mod-drawer-h');
    if (drawerHeight) drawerHeight.addEventListener('change', (event) => commit(() => {
        mod.drawerHeight = clamp(Number(event.target.value), 12, 40);
        mod.layout = 'custom';
        mod.product = 'custom';
    }));
    bindDecor(root, 'drawer', drawerOf(mod), (id) => commit(() => { mod.drawerColorId = id; }));
    root.querySelector('#mod-plinth').addEventListener('change', (event) => commit(() => {
        mod.plinth = event.target.checked;
        if (!mod.plinth) mod.plinthHidden = false;
    }));
    const hiddenKick = root.querySelector('#mod-plinth-hidden');
    if (hiddenKick) hiddenKick.addEventListener('change', (event) => commit(() => { mod.plinthHidden = event.target.checked; }));
    root.querySelector('#mod-kulp').addEventListener('change', (event) => commit(() => { mod.kulp = event.target.checked; }));
    bindSwatch(root, 'led', (id) => commit(() => {
        mod.ledStyle = id === 'off' ? 'off' : id;
        mod.led = id !== 'off';
        if (mod.led) lightsOn = true;
        syncLightsButton();
    }));
    const heightInput = root.querySelector('#mod-h');
    if (heightInput) heightInput.addEventListener('change', (event) => commit(() => {
        mod.height = clamp(Number(event.target.value), mod.dxf === 'ust' ? 40 : H_MIN, H_MAX);
    }));
    bindSwatch(root, 'alum', (id) => commit(() => { mod.aluminumId = id; }));
    bindSwatch(root, 'glass', (id) => commit(() => { mod.glassId = id; }));
    bindDecor(root, 'carcass', mod.carcassId, (id) => commit(() => { mod.carcassId = id; }));
    bindDecor(root, 'door', mod.doorColorId, (id) => commit(() => { mod.doorColorId = id; }));
    bindDecor(root, 'back', backOf(mod), (id) => commit(() => { mod.backId = id; }));
    bindDecor(root, 'shelf', shelfOf(mod), (id) => commit(() => { mod.shelfId = id; }));
    const applyAll = root.querySelector('#apply-all');
    if (applyAll) applyAll.onclick = () => commit(() => applyColorsToAll(mod));
    const opener = root.querySelector('#mod-open');
    if (opener) opener.onclick = () => commit(() => { mod.open = mod.open > 0.5 ? 0 : 1; });
    const hideOne = root.querySelector('#mod-hide-door');
    if (hideOne) hideOne.onclick = () => {
        if (hiddenDoorIds.has(mod.id)) hiddenDoorIds.delete(mod.id);
        else hiddenDoorIds.add(mod.id);
        render();
    };
    const hideAll = root.querySelector('#mod-hide-all');
    if (hideAll) hideAll.onclick = () => {
        hideAllDoors = !hideAllDoors;
        render();
    };
    const upper = root.querySelector('#mod-upper');
    if (upper) upper.onclick = () => {
        if (upperHeight(mod)) {
            commit(() => { mod.upper = 0; });
            return;
        }
        const fit = Math.floor(roomHeightLimit() - moduleHeight(mod));
        if (fit < 30) {
            toast('Tavan payı üst dolap için yetmiyor.');
            return;
        }
        commit(() => { mod.upper = Math.min(48, fit); });
    };
    const drawers = root.querySelector('#mod-drawers');
    if (drawers) drawers.onclick = () => commit(() => {
        mod.drawersOpen = mod.drawersOpen > 0.5 ? 0 : 1;
        if (mod.drawersOpen > 0.5) mod.open = 1;
    });
    const allDoors = root.querySelector('#mod-doors-all');
    if (allDoors) allDoors.onclick = () => commit(() => {
        const open = doorsAreOpen() ? 0 : 1;
        state.modules.forEach((item) => {
            if (item.kind === 'end' || item.door === 'none') return;
            item.open = open;
        });
    });
    root.querySelector('#mod-left').onclick = () => moveModule(mod.id, -1);
    root.querySelector('#mod-right').onclick = () => moveModule(mod.id, 1);
}

function renderInspector() {
    const body = el('inspector-body');
    const mod = selected();
    renderProduction();
    const config = el('config-modal');
    if (!mod) {
        config.classList.add('hidden');
        body.innerHTML = `
            <p class="hint">Dolap seçili değil. Burada seçili renkler yeni eklenen dolaba yazılır. Dekor görselleri temsili dijital dokudur, resmî Kastamonu taraması değildir.</p>
            <div class="field"><label>Sol duvar genişliği (cm)</label><input id="wall-left" type="number" min="80" max="800" value="${sideLimit('left')}"></div>
            <div class="field"><label>Ön duvar genişliği (cm)</label><input id="wall-front" type="number" min="80" max="800" value="${wallLimit()}"></div>
            <div class="field"><label>Sağ duvar genişliği (cm)</label><input id="wall-right" type="number" min="80" max="800" value="${sideLimit('right')}"></div>
            <div class="field"><label>Yükseklik (cm)</label><input id="room-h" type="number" min="160" max="400" value="${roomHeightLimit()}"></div>
            ${decorPicker(state.carcassId, 'carcass', 'Gövde rengi', true)}
            ${decorPicker(state.doorColorId, 'door', 'Dolap rengi', true)}
            ${decorPicker(state.backId, 'back', 'Arkalık rengi', true)}
            ${decorPicker(state.shelfId, 'shelf', 'Rafların rengi', true)}
            <button type="button" class="btn" id="apply-all" style="width:100%;margin:8px 0">Renkleri tümüne uygula</button>
            ${swatchPicker(ALUMINUM, state.aluminumId, 'alum', 'Alüminyum kapak', true)}
            ${swatchPicker(GLASS, state.glassId, 'glass', 'Cam')}
            <label class="check"><input id="cab-plinth" type="checkbox" ${state.plinth === false ? '' : 'checked'}> Baza olsun</label>
            <label class="check"><input id="cab-plinth-hidden" type="checkbox" ${state.plinthHidden ? 'checked' : ''}> Gizli baza olsun</label>
            <label class="check"><input id="cab-kulp" type="checkbox" ${state.kulp === false ? '' : 'checked'}> Kulp olsun</label>
            <label class="check"><input id="cab-led" type="checkbox" ${state.led === false ? '' : 'checked'}> Yeni dolapta LED olsun</label>
            ${allDoorButton()}
            <div class="field"><label>Tüm dolapların yüksekliği (cm)</label><input id="cab-h" type="number" min="${H_MIN}" max="${H_MAX}" value="${state.height}"></div>
            <div class="field"><label>Derinlik (cm)</label><input id="cab-d" type="number" min="40" max="70" value="${state.depth}"></div>`;
        body.querySelector('#wall-left').addEventListener('change', (event) => commit(() => {
            state.leftWall = clamp(Number(event.target.value), 80, 800);
        }));
        body.querySelector('#wall-front').addEventListener('change', (event) => commit(() => {
            state.wallWidth = clamp(Number(event.target.value), 80, 800);
        }));
        body.querySelector('#wall-right').addEventListener('change', (event) => commit(() => {
            state.rightWall = clamp(Number(event.target.value), 80, 800);
        }));
        body.querySelector('#room-h').addEventListener('change', (event) => commit(() => {
            state.roomHeight = clamp(Number(event.target.value), 160, 400);
        }));
        body.querySelector('#cab-plinth').addEventListener('change', (event) => commit(() => {
            state.plinth = event.target.checked;
            if (!state.plinth) state.plinthHidden = false;
            state.modules.forEach((item) => {
                item.plinth = state.plinth;
                if (!state.plinth) item.plinthHidden = false;
            });
        }));
        body.querySelector('#cab-plinth-hidden').addEventListener('change', (event) => commit(() => {
            state.plinthHidden = event.target.checked;
            state.plinth = true;
            state.modules.forEach((item) => { item.plinth = true; item.plinthHidden = state.plinthHidden; });
        }));
        body.querySelector('#cab-kulp').addEventListener('change', (event) => commit(() => {
            state.kulp = event.target.checked;
            state.modules.forEach((item) => { item.kulp = state.kulp; });
        }));
        body.querySelector('#cab-led').addEventListener('change', (event) => commit(() => {
            state.led = event.target.checked;
            state.modules.forEach((item) => {
                item.led = state.led;
                if (state.led && item.ledStyle === 'off') item.ledStyle = 'shelf';
            });
            if (state.led) lightsOn = true;
            syncLightsButton();
        }));
        const allDoors = body.querySelector('#mod-doors-all');
        if (allDoors) allDoors.onclick = () => commit(() => {
            const open = doorsAreOpen() ? 0 : 1;
            state.modules.forEach((item) => {
                if (item.kind === 'end' || item.door === 'none') return;
                item.open = open;
            });
        });
        body.querySelector('#cab-h').addEventListener('change', (event) => commit(() => {
            state.height = clamp(Number(event.target.value), H_MIN, H_MAX);
            state.modules.forEach((item) => { item.height = state.height; });
        }));
        body.querySelector('#cab-d').addEventListener('change', (event) => commit(() => { state.depth = clamp(Number(event.target.value), 40, 70); }));
        bindSwatch(body, 'alum', (id) => commit(() => { state.aluminumId = id; }));
        bindSwatch(body, 'glass', (id) => commit(() => { state.glassId = id; }));
        bindDecor(body, 'carcass', state.carcassId, (id) => commit(() => { state.carcassId = id; }));
        bindDecor(body, 'door', state.doorColorId, (id) => commit(() => { state.doorColorId = id; }));
        bindDecor(body, 'back', state.backId, (id) => commit(() => { state.backId = id; }));
        bindDecor(body, 'shelf', state.shelfId, (id) => commit(() => { state.shelfId = id; }));
        const applyAll = body.querySelector('#apply-all');
        if (applyAll) applyAll.onclick = () => commit(() => applyColorsToAll(state));
        return;
    }
    config.classList.add('hidden');
    if (!window.matchMedia('(max-width: 900px)').matches) el('inspector').classList.add('is-open');
    body.innerHTML = moduleConfigHtml(mod);
    bindModuleConfig(body, mod);
}

function renderSummary() {
    const run = state.modules.reduce((sum, mod) => sum + footprint(mod).w, 0);
    const heights = state.modules.map((mod) => moduleHeight(mod));
    const heightText = heights.length ? (Math.min(...heights) === Math.max(...heights) ? `${heights[0]} cm` : `${Math.min(...heights)}–${Math.max(...heights)} cm`) : `${state.height} cm`;
    el('summary').innerHTML = `Sol <b>${sideLimit('left')} cm</b> · Ön <b>${wallLimit()} cm</b> · Sağ <b>${sideLimit('right')} cm</b> · Yükseklik <b>${roomHeightLimit()} cm</b> · Dolap yüksekliği <b>${heightText}</b> · Dolap <b>${run} cm</b>`;
    el('project-name').value = state.name;
    el('btn-undo').disabled = history.length === 0;
    el('btn-redo').disabled = future.length === 0;
    const spots = layout(state.modules);
    el('warn').classList.toggle('hidden', Boolean(spots));
    el('warn').textContent = spots ? '' : 'Modüller duvara sığmıyor.';
}

function roomOpenings(wallId) {
    const items = state.roomSketch?.openings;
    return Array.isArray(items) ? items.filter((item) => item.wallId === wallId) : [];
}

function wallPieces(length, height, openings) {
    const pieces = [];
    const sorted = openings
        .map((item) => ({
            start: clamp(Number(item.offset) || 0, 0, length),
            end: clamp((Number(item.offset) || 0) + (Number(item.width) || 0), 0, length),
            bottom: item.kind === 'window' ? clamp(Number(item.sill) || 0, 0, height) : 0,
            top: item.kind === 'window'
                ? clamp((Number(item.sill) || 0) + (Number(item.height) || 120), 0, height)
                : clamp(Number(item.height) || 210, 0, height),
        }))
        .filter((item) => item.end - item.start > 4)
        .sort((a, b) => a.start - b.start);
    let cursor = 0;
    sorted.forEach((item) => {
        if (item.start > cursor + 1) pieces.push({ along: cursor, length: item.start - cursor, y: 0, h: height });
        if (item.bottom > 1) pieces.push({ along: item.start, length: item.end - item.start, y: 0, h: item.bottom });
        if (height - item.top > 1) pieces.push({ along: item.start, length: item.end - item.start, y: item.top, h: height - item.top });
        cursor = Math.max(cursor, item.end);
    });
    if (length - cursor > 1) pieces.push({ along: cursor, length: length - cursor, y: 0, h: height });
    return pieces;
}

function replaceWall(key, object) {
    if (room[key]) {
        scene.remove(room[key]);
        room[key].traverse((node) => { if (node.geometry) node.geometry.dispose(); });
    }
    room[key] = object;
    scene.add(object);
}

function fitRoom() {
    const width = wallLimit();
    const height = roomHeightLimit();
    const left = sideLimit('left');
    const right = sideLimit('right');
    const depth = roomDepthLimit();
    if (!room.wallMat) room.wallMat = new THREE.MeshStandardMaterial({ color: '#f7f6f4', roughness: 0.9 });
    const wallMat = room.wallMat;
    const build = (pieces, place) => {
        const group = new THREE.Group();
        pieces.forEach((piece) => {
            const mesh = new THREE.Mesh(place.geometry(piece), wallMat);
            mesh.position.copy(place.position(piece));
            mesh.receiveShadow = true;
            group.add(mesh);
        });
        return group;
    };
    replaceWall('back', build(wallPieces(width, height, roomOpenings('front')), {
        geometry: (piece) => new THREE.BoxGeometry(piece.length, piece.h, 8),
        position: (piece) => new THREE.Vector3(piece.along + piece.length / 2, piece.y + piece.h / 2, -4),
    }));
    replaceWall('left', build(wallPieces(left, height, roomOpenings('left')), {
        geometry: (piece) => new THREE.BoxGeometry(8, piece.h, piece.length),
        position: (piece) => new THREE.Vector3(-4, piece.y + piece.h / 2, piece.along + piece.length / 2),
    }));
    replaceWall('right', build(wallPieces(right, height, roomOpenings('right')), {
        geometry: (piece) => new THREE.BoxGeometry(8, piece.h, piece.length),
        position: (piece) => new THREE.Vector3(width + 4, piece.y + piece.h / 2, piece.along + piece.length / 2),
    }));
    if (room.floor) {
        room.floor.geometry.dispose();
        room.floor.geometry = new THREE.BoxGeometry(width, 2, depth + 40);
        room.floor.position.set(width / 2, -1, (depth + 40) / 2 - 8);
    }
}

function render() {
    renderSeries();
    renderLibrary();
    renderInspector();
    renderSummary();
    rebuild();
    fitRoom();
}

function addSet(setId) {
    const set = SETS.find((item) => item.id === setId);
    const mods = set.parts.map((id) => {
        const mod = createModule(id);
        preferCatalogWood(mod);
        return mod;
    });
    if (mods.some((mod) => mod.kind === 'corner') && state.modules.some((mod) => mod.kind === 'corner')) {
        toast('Köşe dolap bir kez konur.');
        return;
    }
    if (!layout([...state.modules, ...mods])) {
        toast('Bu takım duvara sığmıyor.');
        return;
    }
    commit(() => {
        state.modules.push(...mods);
        selectedId = null;
    });
    el('library').classList.remove('is-open');
}

function syncLightsButton() {
    const button = el('btn-lights');
    if (!button) return;
    button.classList.toggle('is-on', lightsOn);
    button.textContent = lightsOn ? 'Işıkları kapat' : 'Işıkları aç';
}

function moduleUnder(event) {
    if (!renderer || !cabinetRoot) return null;
    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
    raycaster.setFromCamera(pointer, camera);
    const hit = raycaster.intersectObjects(cabinetRoot.children, true)[0];
    let node = hit?.object || null;
    while (node) {
        if (node.userData?.moduleId) return node.userData.moduleId;
        node = node.parent;
    }
    return null;
}

const floorPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
const floorHit = new THREE.Vector3();
const wallPoint = new THREE.Vector3();
const dropLog = [];
window.__wardrobeDropLog = dropLog;

function noteDrop(entry) {
    const row = { at: new Date().toISOString(), ...entry };
    dropLog.push(row);
    if (dropLog.length > 40) dropLog.shift();
    console.info('[dolap-birak]', row);
}

function hideDropGuide() {
    if (dropGuide) dropGuide.visible = false;
}

function showDropGuide(mod, pick, ok) {
    if (!scene || !pick) return;
    if (!dropGuide) {
        dropGuide = new THREE.Mesh(
            new THREE.BoxGeometry(1, 1, 1),
            new THREE.MeshBasicMaterial({ color: 0x1f6b4a, transparent: true, opacity: 0.38, depthWrite: false })
        );
        dropGuide.name = 'drop-guide';
        scene.add(dropGuide);
    }
    const along = alongSize(mod);
    const depth = Math.max(30, Number(mod.glbDepth) || Number(state.depth) || 60);
    const front = wallLimit();
    const limit = pick.wall === 'front' ? front : sideLimit(pick.wall);
    const at = Math.max(0, Math.min(Number(pick.offset) || 0, Math.max(0, limit - along)));
    dropGuide.scale.set(pick.wall === 'front' ? along : depth, 8, pick.wall === 'front' ? depth : along);
    if (pick.wall === 'front') dropGuide.position.set(at + along / 2, 4, depth / 2);
    else if (pick.wall === 'left') dropGuide.position.set(depth / 2, 4, at + along / 2);
    else dropGuide.position.set(front - depth / 2, 4, at + along / 2);
    dropGuide.material.color.set(ok ? 0x1f6b4a : 0x9f2d2d);
    dropGuide.visible = true;
}

function dragTarget(event, mod) {
    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
    raycaster.setFromCamera(pointer, camera);
    const along = alongSize(mod);
    const front = wallLimit();
    const tall = Math.max(roomHeightLimit(), moduleHeight(mod));
    const ray = raycaster.ray;
    const hits = [];
    const consider = (wall, plane, ok, offsetOf) => {
        const hit = ray.intersectPlane(plane, wallPoint);
        if (!hit) return;
        const dx = hit.x - ray.origin.x;
        const dy = hit.y - ray.origin.y;
        const dz = hit.z - ray.origin.z;
        if (dx * ray.direction.x + dy * ray.direction.y + dz * ray.direction.z <= 0) return;
        if (!ok(hit)) return;
        hits.push({ wall, offset: offsetOf(hit), distance: Math.hypot(dx, dy, dz), x: hit.x, y: hit.y, z: hit.z });
    };
    const inHeight = (p) => p.y >= -20 && p.y <= tall + 40;
    const frontDepth = Math.max(30, Number(state.depth) || 60);
    const sideNear = frontDepth + 70;
    const watchingLeft = camera.position.x < frontDepth;
    const watchingRight = camera.position.x > front - frontDepth;
    consider('front', new THREE.Plane(new THREE.Vector3(0, 0, 1), 0), (p) => inHeight(p) && p.x >= -40 && p.x <= front + 40, (p) => p.x - along / 2);
    consider('left', new THREE.Plane(new THREE.Vector3(1, 0, 0), 0), (p) => inHeight(p) && p.z >= (watchingLeft ? -40 : sideNear) && p.z <= sideLimit('left') + 40, (p) => p.z - along / 2);
    consider('right', new THREE.Plane(new THREE.Vector3(-1, 0, 0), -front), (p) => inHeight(p) && p.z >= (watchingRight ? -40 : sideNear) && p.z <= sideLimit('right') + 40, (p) => p.z - along / 2);
    if (hits.length) return hits.sort((a, b) => a.distance - b.distance)[0];
    if (!ray.intersectPlane(floorPlane, floorHit)) return null;
    const choices = [{ wall: 'front', dist: Math.abs(floorHit.z), offset: floorHit.x - along / 2 }];
    if (floorHit.z >= frontDepth) {
        choices.push(
            { wall: 'left', dist: Math.abs(floorHit.x), offset: floorHit.z - along / 2 },
            { wall: 'right', dist: Math.abs(floorHit.x - front), offset: floorHit.z - along / 2 },
        );
    }
    return choices.sort((a, b) => a.dist - b.dist)[0];
}

function dragModule(id, event) {
    const mod = state.modules.find((item) => item.id === id);
    if (!mod || !renderer) return;
    const pick = dragTarget(event, mod);
    if (!pick) return;
    const front = wallLimit();
    const previous = { wall: mod.wall, offset: mod.offset, cornerSide: mod.cornerSide };
    if (mod.kind === 'corner') {
        mod.cornerSide = pick.wall === 'right' || (pick.wall === 'front' && pick.offset > front / 2) ? 'right' : 'left';
        mod.wall = 'front';
        mod.offset = mod.cornerSide === 'left' ? -1 : 10000;
    } else {
        mod.wall = pick.wall;
        mod.offset = pick.offset;
    }
    if (!layout(state.modules)) {
        Object.assign(mod, previous);
        return;
    }
    selectedId = id;
    rebuild();
    showDragGhost(mod);
    renderSummary();
    updateHud();
}

function clearDragGhost() {
    if (!dragGhost) return;
    scene.remove(dragGhost);
    dragGhost.geometry.dispose();
    dragGhost.material.dispose();
    dragGhost = null;
}

function showDragGhost(mod) {
    clearDragGhost();
    const spots = layout(state.modules);
    const spot = spots?.find((item) => item.mod.id === mod.id);
    if (!spot || !scene) return;
    const width = alongSize(mod);
    const depth = mod.kind === 'corner' ? mod.width : state.depth;
    const height = moduleHeight(mod) + upperHeight(mod);
    const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(width, height, depth),
        new THREE.MeshStandardMaterial({ color: 0x1f6b4a, transparent: true, opacity: 0.35, depthWrite: false })
    );
    mesh.name = 'drag-ghost';
    mesh.position.set(spot.x, 0, spot.z);
    mesh.rotation.y = spot.rot;
    mesh.position.add(new THREE.Vector3(width / 2, height / 2, depth / 2).applyAxisAngle(new THREE.Vector3(0, 1, 0), spot.rot));
    scene.add(mesh);
    dragGhost = mesh;
}

function applyDxf(mod, recipe) {
    const wasUpper = mod.dxf === 'ust';
    mod.dxf = recipe.id;
    mod.interior = recipe.kind === 'corner' ? '' : recipe.id;
    mod.product = recipe.kind === 'corner' ? 'kose-raf' : 'custom';
    mod.layout = recipe.id;
    mod.spec = '';
    mod.name = recipe.name;
    mod.shelves = (recipe.shelves || []).length;
    mod.drawers = Math.min(6, recipe.drawers || 0);
    mod.drawerHeight = clamp(Number(recipe.drawerHeight) || 18, 12, 40);
    mod.rail = (recipe.rails || []).length > 0;
    mod.door = recipe.door;
    if (recipe.id === 'ust') mod.height = 65;
    else if (wasUpper) mod.height = state.height;
    if (recipe.kind === 'corner') {
        mod.kind = 'corner';
        mod.wall = 'front';
        if (mod.cornerSide !== 'left' && mod.cornerSide !== 'right') mod.cornerSide = 'left';
    } else if (mod.kind === 'corner') {
        mod.kind = 'wardrobe';
    }
}

function applyInterior(mod, type) {
    const recipe = dxfById(type);
    if (recipe) {
        applyDxf(mod, recipe);
        return;
    }
    const preset = {
        aski: { shelves: 2, rail: true, drawers: 0, name: 'Askılık' },
        raf: { shelves: 5, rail: false, drawers: 0, name: 'Raflı' },
        pantolon: { shelves: 1, rail: true, drawers: 2, drawerHeight: 18, name: 'Pantolonluk' },
        ayakkabi: { shelves: 5, rail: true, drawers: 0, name: 'Ayakkabılık' },
        cekmece: { shelves: 2, rail: true, drawers: 3, drawerHeight: 16, name: 'Çekmeceli' },
        cift: { shelves: 1, rail: true, drawers: 0, name: 'Çift askı' },
    }[type] || { shelves: 3, rail: true, drawers: 0, name: 'Dolap' };
    mod.interior = preset.name === 'Dolap' ? 'raf' : type;
    mod.product = 'custom';
    mod.layout = 'custom';
    mod.spec = '';
    Object.assign(mod, preset);
}

function addCabinet(target) {
    const recipe = dxfById(addInteriorKind);
    let place = target;
    if (recipe?.kind === 'corner' && place !== 'corner-left' && place !== 'corner-right') {
        place = place === 'right' ? 'corner-right' : 'corner-left';
    }
    const corner = place === 'corner-left' || place === 'corner-right';
    const width = clamp(Number(el('add-w')?.value) || addWidth, 30, 120);
    const side = place.endsWith('left') ? 'left' : place.endsWith('right') ? 'right' : 'front';
    if (corner && state.modules.some((mod) => mod.kind === 'corner' && mod.cornerSide === (place === 'corner-left' ? 'left' : 'right'))) {
        toast('Bu köşe dolu.');
        return;
    }
    const mod = createModule(corner ? 'kose-raf' : 'tek-raf');
    preferCatalogWood(mod);
    mod.kind = corner ? 'corner' : 'wardrobe';
    mod.width = recipe?.kind === 'corner' ? recipe.width : width;
    mod.door = corner ? 'solid' : addDoor;
    if (recipe) applyDxf(mod, recipe);
    else if (corner) {
        mod.product = 'kose-raf';
        mod.layout = 'shelves';
        mod.interior = '';
        mod.name = place === 'corner-left' ? 'Sol köşe' : 'Sağ köşe';
        mod.spec = '';
        mod.shelves = 4;
        mod.rail = false;
        mod.drawers = 0;
    } else applyInterior(mod, addInteriorKind);
    if (recipe && recipe.door !== 'none') mod.door = addDoor;
    mod.kind = corner ? 'corner' : 'wardrobe';
    mod.wall = corner ? 'front' : side;
    mod.cornerSide = place === 'corner-left' ? 'left' : 'right';
    mod.offset = 10000;
    if (!layout([...state.modules, mod])) {
        toast('Bu duvarda yer kalmadı.');
        return;
    }
    commit(() => {
        state.modules.push(mod);
        selectedId = mod.id;
    });
    el('library').classList.remove('is-open');
    if (!window.matchMedia('(max-width: 900px)').matches) el('inspector').classList.add('is-open');
}

async function ensureGlb(item) {
    if (glbTemplates.has(item.id)) return glbTemplates.get(item.id);
    if (glbFailed.has(item.id)) throw new Error(item.id);
    const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
    const loader = new GLTFLoader();
    try {
        const gltf = await loader.loadAsync(item.modelPath);
        glbTemplates.set(item.id, gltf.scene);
        return gltf.scene;
    } catch (error) {
        glbFailed.add(item.id);
        throw error;
    }
}

function ensureCatalogDrag() {
    if (catalogBound) return;
    catalogBound = true;
    const ghost = document.createElement('div');
    ghost.id = 'catalog-ghost';
    ghost.style.cssText = 'position:fixed;z-index:30;pointer-events:none;display:none;background:#149152;color:#fff;padding:6px 10px;border-radius:8px;font:600 12px Montserrat,sans-serif;';
    document.body.appendChild(ghost);
    window.addEventListener('pointermove', (event) => {
        if (!catalogDrag) return;
        if (Math.hypot(event.clientX - catalogDrag.x, event.clientY - catalogDrag.y) > 6) catalogDrag.active = true;
        if (!catalogDrag.active) return;
        ghost.style.display = 'block';
        ghost.style.left = `${event.clientX + 14}px`;
        ghost.style.top = `${event.clientY + 14}px`;
        ghost.textContent = catalogDrag.name;
        const probe = { id: 'guide', kind: 'wardrobe', width: catalogDrag.width || 90, wall: 'front', offset: 0, glbDepth: 60 };
        const pick = dragTarget(event, probe);
        if (!pick) {
            hideDropGuide();
            return;
        }
        probe.wall = pick.wall;
        probe.offset = pick.offset;
        showDropGuide(probe, pick, Boolean(layout([...state.modules, probe])));
    });
    window.addEventListener('pointerup', (event) => {
        if (!catalogDrag) return;
        const drag = catalogDrag;
        catalogDrag = null;
        ghost.style.display = 'none';
        hideDropGuide();
        const rect = el('viewport')?.getBoundingClientRect();
        const inside = Boolean(rect && event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom);
        noteDrop({
            phase: 'up',
            id: drag.id,
            active: Boolean(drag.active),
            inside,
            x: Math.round(event.clientX),
            y: Math.round(event.clientY),
            view: rect ? [Math.round(rect.width), Math.round(rect.height)] : null,
        });
        if (!drag.active || !inside) return;
        const card = document.querySelector(`[data-${drag.kind}="${drag.id}"]`);
        if (card) card.dataset.skipClick = '1';
        if (drag.kind === 'glb') addGlbModule(drag.id, event);
        else if (drag.kind === 'cad') addCadModule(drag.id, event);
        else addProduct(drag.id, event);
    });
}

function placeDropped(mod, dropEvent) {
    if (!dropEvent) return true;
    const pick = dragTarget(dropEvent, mod);
    noteDrop({
        id: mod.glb || mod.product || mod.id,
        width: alongSize(mod),
        clientX: Math.round(dropEvent.clientX || 0),
        clientY: Math.round(dropEvent.clientY || 0),
        wall: pick?.wall || '',
        offset: pick ? Math.round(pick.offset * 10) / 10 : null,
        hitX: pick ? Math.round(pick.x * 10) / 10 : null,
        hitZ: pick ? Math.round(pick.z * 10) / 10 : null,
        front: wallLimit(),
        left: sideLimit('left'),
        existing: state.modules.map((item) => ({ id: item.glb || item.name, wall: item.wall, offset: Math.round(Number(item.offset) || 0), width: alongSize(item) })),
    });
    if (!pick) {
        toast('Duvarın üzerine bırak.');
        return false;
    }
    if (mod.kind === 'corner') {
        const wantRight = pick.wall === 'right' || (pick.wall === 'front' && pick.offset > wallLimit() / 2);
        const taken = state.modules.some((entry) => entry.kind === 'corner' && (wantRight ? entry.cornerSide !== 'left' : entry.cornerSide === 'left'));
        if (taken) {
            toast(wantRight ? 'Sağ köşe dolu.' : 'Sol köşe dolu.');
            return false;
        }
        mod.cornerSide = wantRight ? 'right' : 'left';
        mod.wall = 'front';
        mod.offset = wantRight ? 10000 : -1;
        return true;
    }
    mod.wall = pick.wall;
    mod.offset = pick.offset;
    return true;
}

async function addGlbModule(id, dropEvent) {
    const item = glbCatalog.find((entry) => entry.id === id);
    if (!item) return;
    noteDrop({ phase: 'load', id, cached: glbTemplates.has(item.id) });
    try {
        await ensureGlb(item);
        const mod = createModule(item.category === 'tek' || item.category === 'y26-tek' ? 'tek-raf' : 'cift-raf');
        mod.glb = item.id;
        mod.glbWidth = item.width;
        mod.glbDepth = Math.round(item.depth * 100);
        mod.glbHeight = Math.round(item.height * 100);
        mod.name = item.name;
        mod.spec = item.spec;
        mod.width = Math.round(item.width * 100);
        mod.height = mod.glbHeight;
        mod.door = 'solid';
        preferCatalogWood(mod);
        mod.open = 1;
        mod.kind = item.category === 'kose' ? 'corner' : 'wardrobe';
        mod.wall = 'front';
        mod.offset = 10000;
        if (dropEvent) {
            if (!placeDropped(mod, dropEvent)) return;
        } else if (mod.kind === 'corner') {
            const hasLeft = state.modules.some((entry) => entry.kind === 'corner' && entry.cornerSide === 'left');
            const hasRight = state.modules.some((entry) => entry.kind === 'corner' && entry.cornerSide !== 'left');
            if (hasLeft && hasRight) {
                toast('İki köşe de dolu.');
                return;
            }
            mod.cornerSide = hasLeft ? 'right' : 'left';
        }
        if (!layout([...state.modules, mod])) {
            const run = state.modules.filter((entry) => entry.wall === mod.wall && entry.kind !== 'corner').reduce((sum, entry) => sum + alongSize(entry), 0);
            const room = (mod.wall === 'front' ? wallLimit() : sideLimit(mod.wall)) - run;
            noteDrop({ id: mod.glb || mod.name, result: 'rejected', wall: mod.wall, offset: mod.offset, free: room });
            toast(room >= alongSize(mod)
                ? 'Bu noktada bitişik boşluk yok. Karttaki Ekle, sıranın sonuna koyar.'
                : 'Bu modül duvara sığmıyor.');
            return;
        }
        noteDrop({ id: mod.glb || mod.name, result: 'accepted', wall: mod.wall, offset: Math.round(Number(mod.offset) || 0), count: state.modules.length + 1 });
        commit(() => {
            state.modules.push(mod);
            selectedId = mod.id;
        });
        frameCamera();
        snapDoors();
        el('library').classList.remove('is-open');
        if (!window.matchMedia('(max-width: 900px)').matches) el('inspector').classList.add('is-open');
    } catch (error) {
        noteDrop({ phase: 'error', id, message: String(error?.stack || error).slice(0, 500) });
    }
}

function addCadModule(id, dropEvent) {
    const item = cadById(id);
    if (!item) return;
    const mod = createModule(item.width > 70 ? 'cift-raf' : 'tek-raf');
    preferCatalogWood(mod);
    mod.cad = item.id;
    mod.name = item.name;
    mod.width = item.width;
    mod.height = item.height < 160 ? item.height : state.height;
    mod.door = item.door;
    mod.kind = item.kind === 'corner' ? 'corner' : 'wardrobe';
    if (mod.kind === 'corner') {
        const hasLeft = state.modules.some((entry) => entry.kind === 'corner' && entry.cornerSide === 'left');
        const hasRight = state.modules.some((entry) => entry.kind === 'corner' && entry.cornerSide !== 'left');
        if (hasLeft && hasRight) {
            toast('İki köşe de dolu.');
            return;
        }
        mod.cornerSide = hasLeft ? 'right' : 'left';
    }
    mod.product = 'custom';
    mod.layout = item.id;
    mod.interior = '';
    mod.spec = item.spec;
    mod.shelves = item.shelves;
    mod.drawers = item.drawers;
    mod.rail = item.rails > 0;
    mod.wall = 'front';
    mod.offset = 10000;
    if (dropEvent) {
        if (!placeDropped(mod, dropEvent)) return;
    }
    if (!layout([...state.modules, mod])) {
        const run = state.modules.filter((item) => item.wall === mod.wall && item.kind !== 'corner').reduce((sum, item) => sum + alongSize(item), 0);
        const room = (mod.wall === 'front' ? wallLimit() : sideLimit(mod.wall)) - run;
        noteDrop({ id: mod.glb || mod.name, result: 'rejected', wall: mod.wall, offset: mod.offset, free: room });
        toast(room >= alongSize(mod)
            ? 'Bu noktada bitişik boşluk yok. Karttaki Ekle, sıranın sonuna koyar.'
            : 'Bu modül duvara sığmıyor.');
        return;
    }
    noteDrop({ id: mod.glb || mod.name, result: 'accepted', wall: mod.wall, offset: Math.round(Number(mod.offset) || 0), count: state.modules.length + 1 });
    commit(() => {
        state.modules.push(mod);
        selectedId = mod.id;
    });
    el('library').classList.remove('is-open');
    if (!window.matchMedia('(max-width: 900px)').matches) el('inspector').classList.add('is-open');
}

function addProduct(productId, dropEvent) {
    const product = productById(productId);
    if (product.kind === 'corner' && state.modules.some((mod) => mod.kind === 'corner')) {
        toast('Köşe modülü bir kez konur.');
        return;
    }
    const mod = createModule(productId);
    preferCatalogWood(mod);
    if (dropEvent && !placeDropped(mod, dropEvent)) return;
    if (!layout([...state.modules, mod])) {
        toast('Bu modül duvarın dışında kalıyor.');
        return;
    }
    commit(() => {
        state.modules.push(mod);
        selectedId = mod.id;
    });
    el('library').classList.remove('is-open');
}

function moveModule(id, step) {
    const mod = state.modules.find((item) => item.id === id);
    if (!mod) return;
    if (mod.kind === 'corner') {
        commit(() => {
            mod.cornerSide = step < 0 ? 'left' : 'right';
            mod.wall = 'front';
            mod.offset = mod.cornerSide === 'left' ? 0 : 10000;
        });
        return;
    }
    const list = state.modules
        .filter((item) => item.kind !== 'corner' && wallName(item) === wallName(mod))
        .sort((a, b) => Number(a.offset) - Number(b.offset) || String(a.id).localeCompare(String(b.id)));
    const index = list.findIndex((item) => item.id === id);
    const other = list[index + step];
    if (!Number.isFinite(Number(mod.offset))) mod.offset = 0;
    commit(() => {
        if (other) {
            if (!Number.isFinite(Number(other.offset))) other.offset = Number(mod.offset) + step * alongSize(mod);
            const offset = Number(mod.offset);
            mod.offset = Number(other.offset);
            other.offset = offset === Number(mod.offset) ? offset - step * alongSize(mod) : offset;
            return;
        }
        mod.offset = Number(mod.offset) + step * Math.max(20, alongSize(mod));
    });
}

function cloneSelected() {
    const mod = selected();
    if (!mod) return;
    if (mod.kind === 'corner') {
        toast('Köşe modülü bir kez konur.');
        return;
    }
    const copy = { ...mod, id: uid(), open: 0, offset: Number(mod.offset) + 0.5 };
    const index = state.modules.findIndex((item) => item.id === mod.id);
    const modules = state.modules.slice();
    modules.splice(index + 1, 0, copy);
    if (!layout(modules)) {
        toast('Kopya duvara sığmıyor.');
        return;
    }
    commit(() => {
        state.modules = modules;
        selectedId = copy.id;
    });
}

function deleteSelected() {
    const mod = selected();
    if (!mod) return;
    commit(() => {
        state.modules = state.modules.filter((item) => item.id !== mod.id);
        selectedId = null;
    });
}

function saves() {
    try { return JSON.parse(localStorage.getItem(SAVE_KEY) || '[]'); } catch { return []; }
}

function saveDesign() {
    const list = saves().filter((item) => item.name !== state.name);
    list.unshift({ name: state.name, savedAt: new Date().toISOString(), state, token: projectToken });
    localStorage.setItem(SAVE_KEY, JSON.stringify(list.slice(0, 20)));
    persist(true);
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
        const data = JSON.parse(decodeURIComponent(escape(atob(hash))));
        if (!Array.isArray(data.modules) || data.modules.some((mod) => !mod.kind)) return false;
        state = data;
        return true;
    } catch {
        return false;
    }
}

function openSaved() {
    el('saved-phone').value = state.phone || '';
    el('saved-modal').classList.remove('hidden');
    searchProjects(el('saved-phone').value, el('saved-list'), () => el('saved-modal').classList.add('hidden'));
}

function cabinetRenderBox() {
    const box = new THREE.Box3();
    let found = false;
    cabinetRoot?.children.forEach((child) => {
        const next = new THREE.Box3().setFromObject(child);
        if (next.isEmpty()) return;
        box.union(next);
        found = true;
    });
    if (!found) box.set(new THREE.Vector3(0, 0, 0), new THREE.Vector3(Math.min(wallLimit(), 180), state.height, state.depth));
    return box;
}

function renderCorners(box) {
    const corners = [];
    for (const x of [box.min.x, box.max.x]) {
        for (const y of [box.min.y, box.max.y]) {
            for (const z of [box.min.z, box.max.z]) corners.push(new THREE.Vector3(x, y, z));
        }
    }
    return corners;
}

function projectedAspect(cam, corners) {
    let minX = Infinity;
    let maxX = -Infinity;
    let minY = Infinity;
    let maxY = -Infinity;
    corners.forEach((point) => {
        const ndc = point.clone().project(cam);
        minX = Math.min(minX, ndc.x);
        maxX = Math.max(maxX, ndc.x);
        minY = Math.min(minY, ndc.y);
        maxY = Math.max(maxY, ndc.y);
    });
    return (maxX - minX) / Math.max(0.001, maxY - minY);
}

function frameRenderCamera(cam, box) {
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3());
    const corners = renderCorners(box);
    const span = Math.max(size.x, size.y);
    cam.fov = THREE.MathUtils.clamp(27 + (span - 140) * 0.035, 26, 40);
    cam.near = 1;
    cam.far = 8000;
    const view = new THREE.Vector3(Math.min(8, size.x * 0.02), size.y * 0.03, Math.max(70, size.z)).normalize();
    let dist = Math.max(span, 140);
    for (let i = 0; i < 28; i += 1) {
        cam.position.copy(center).addScaledVector(view, dist);
        cam.position.z = Math.max(cam.position.z, box.max.z + 48);
        cam.lookAt(center);
        cam.updateMatrixWorld();
        cam.updateProjectionMatrix();
        const maxAbs = corners.reduce((peak, point) => {
            const ndc = point.clone().project(cam);
            return Math.max(peak, Math.abs(ndc.x), Math.abs(ndc.y));
        }, 0);
        if (maxAbs > 0.96) dist *= 1.06;
        else if (maxAbs < 0.91) dist *= 0.95;
        else break;
    }
    return { center, size, aspect: projectedAspect(cam, corners) };
}

function faceLight(mesh, target) {
    const away = mesh.position.clone().sub(target).normalize();
    mesh.lookAt(mesh.position.clone().add(away));
}

function hideLightFromFrame(mesh, cam, target) {
    const retreat = cam.position.clone().sub(target).normalize();
    const probe = new THREE.Vector3();
    for (let step = 0; step < 10; step += 1) {
        mesh.updateMatrixWorld(true);
        const bounds = new THREE.Box3().setFromObject(mesh);
        const visible = [
            bounds.min,
            bounds.max,
            probe.set(bounds.min.x, bounds.min.y, bounds.max.z),
            probe.clone().set(bounds.max.x, bounds.min.y, bounds.min.z),
            probe.clone().set(bounds.min.x, bounds.max.y, bounds.min.z),
            probe.clone().set(bounds.max.x, bounds.max.y, bounds.max.z),
        ].some((point) => {
            const ndc = point.clone().project(cam);
            return ndc.z < 1 && Math.abs(ndc.x) < 1.08 && Math.abs(ndc.y) < 1.08;
        });
        if (!visible) return;
        mesh.position.addScaledVector(retreat, 36);
        faceLight(mesh, target);
    }
}

function renderBackdrop(box) {
    const center = box.getCenter(new THREE.Vector3());
    const width = Math.max(wallLimit(), box.getSize(new THREE.Vector3()).x) + 900;
    const height = Math.max(roomHeightLimit(), box.max.y) + 520;
    const wall = new THREE.Mesh(
        new THREE.PlaneGeometry(width, height),
        new THREE.MeshStandardMaterial({ color: '#f6f4f1', roughness: 0.78, metalness: 0 })
    );
    wall.name = 'render-backdrop';
    wall.position.set(center.x, height / 2 - 40, -14);
    scene.add(wall);
    return wall;
}

function studioKeyLight(cam, box) {
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3());
    const span = Math.max(size.x, size.z, 90);
    const group = new THREE.Group();
    group.name = 'studio-key';
    const retreat = cam.position.clone().sub(center).normalize();
    const lightDist = cam.position.distanceTo(center) + 90;
    const intensity = THREE.MathUtils.clamp(2.45 * (lightDist / 460) ** 2, 0.55, 3.2);
    const keyMat = new THREE.MeshStandardMaterial({
        color: '#000000', emissive: '#fff1e2', emissiveIntensity: intensity, roughness: 1, side: THREE.FrontSide,
    });
    const key = new THREE.Mesh(new THREE.PlaneGeometry(span * 1.35, Math.max(80, size.y * 0.85)), keyMat);
    const up = new THREE.Vector3(0, 1, 0);
    const side = new THREE.Vector3().crossVectors(retreat, up).normalize();
    key.position.copy(center).addScaledVector(retreat, lightDist).addScaledVector(up, size.y * 0.16);
    faceLight(key, center);
    hideLightFromFrame(key, cam, center);
    const fillMat = keyMat.clone();
    fillMat.emissive.set('#f4f7fb');
    fillMat.emissiveIntensity = keyMat.emissiveIntensity * 0.38;
    const fill = new THREE.Mesh(new THREE.PlaneGeometry(span * 0.85, Math.max(50, size.y * 0.5)), fillMat);
    fill.position.copy(center).addScaledVector(retreat, lightDist + 24).addScaledVector(side, span * 0.28).addScaledVector(up, -size.y * 0.04);
    faceLight(fill, center);
    hideLightFromFrame(fill, cam, center);
    group.add(key, fill);
    scene.add(group);
    return group;
}

function prepareTraceTextures() {
    const textures = new Set();
    scene.traverse((obj) => {
        const list = obj.material ? (Array.isArray(obj.material) ? obj.material : [obj.material]) : [];
        list.forEach((mat) => {
            if (!mat) return;
            Object.values(mat).forEach((value) => {
                if (value && value.isTexture) textures.add(value);
            });
        });
    });
    textures.forEach((tex) => {
        tex.wrapS = THREE.RepeatWrapping;
        tex.wrapT = THREE.RepeatWrapping;
        tex.minFilter = THREE.LinearFilter;
        tex.magFilter = THREE.LinearFilter;
        tex.generateMipmaps = false;
    });
}

let rendering = false;

function denoiseRender(source) {
    const width = source.width;
    const height = source.height;
    const copy = document.createElement('canvas');
    copy.width = width;
    copy.height = height;
    const ctx = copy.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(source, 0, 0);
    const image = ctx.getImageData(0, 0, width, height);
    const count = width * height;
    let src = new Float32Array(count * 3);
    const dst = new Float32Array(count * 3);
    for (let i = 0; i < count; i += 1) {
        src[i * 3] = image.data[i * 4];
        src[i * 3 + 1] = image.data[i * 4 + 1];
        src[i * 3 + 2] = image.data[i * 4 + 2];
    }
    const kernel = [1, 4, 6, 4, 1];
    const colorSigma = 28 * 28 * 2;
    let read = src;
    let write = dst;
    let step = 1;
    for (let pass = 0; pass < 3; pass += 1) {
        for (let y = 0; y < height; y += 1) {
            for (let x = 0; x < width; x += 1) {
                const center = (y * width + x) * 3;
                const cr = read[center];
                const cg = read[center + 1];
                const cb = read[center + 2];
                let r = 0;
                let g = 0;
                let b = 0;
                let weight = 0;
                for (let ky = -2; ky <= 2; ky += 1) {
                    const yy = Math.min(height - 1, Math.max(0, y + ky * step));
                    for (let kx = -2; kx <= 2; kx += 1) {
                        const xx = Math.min(width - 1, Math.max(0, x + kx * step));
                        const sample = (yy * width + xx) * 3;
                        const dr = read[sample] - cr;
                        const dg = read[sample + 1] - cg;
                        const db = read[sample + 2] - cb;
                        const influence = Math.exp(-(dr * dr + dg * dg + db * db) / colorSigma) * kernel[kx + 2] * kernel[ky + 2];
                        r += read[sample] * influence;
                        g += read[sample + 1] * influence;
                        b += read[sample + 2] * influence;
                        weight += influence;
                    }
                }
                write[center] = r / weight;
                write[center + 1] = g / weight;
                write[center + 2] = b / weight;
            }
        }
        const swap = read;
        read = write;
        write = swap;
        step *= 2;
    }
    for (let i = 0; i < count; i += 1) {
        image.data[i * 4] = read[i * 3];
        image.data[i * 4 + 1] = read[i * 3 + 1];
        image.data[i * 4 + 2] = read[i * 3 + 2];
    }
    ctx.putImageData(image, 0, 0);
    return copy;
}

async function takeRender() {
    if (rendering || !renderer) return;
    rendering = true;
    const button = el('btn-shot');
    const label = button.textContent;
    const opens = state.modules.map((mod) => mod.open);
    const wasSelected = selectedId;
    const measures = showMeasures;
        const exposure = renderer.toneMappingExposure;
        const envMap = scene.environment;
        const env = scene.environmentIntensity;
        const background = scene.background;
        const shot = { pos: camera.position.clone(), target: controls.target.clone(), fov: camera.fov, near: camera.near, far: camera.far };
    let key = null;
    let floorShot = null;
    let backdrop = null;
    button.disabled = true;
    try {
        showMeasures = false;
        selectedId = null;
        rebuild();
        snapDoors();
        el('selection-ui').classList.add('hidden');
        el('height-handle').classList.add('hidden');
        const ratio = renderer.getPixelRatio();
        const size = new THREE.Vector2();
        renderer.getSize(size);
        const subject = cabinetRenderBox();
        if (room.floor) {
            floorShot = { geometry: room.floor.geometry, position: room.floor.position.clone() };
            const reach = Math.max(wallLimit(), subject.max.x) + 280;
            const deep = subject.max.z + 640;
            room.floor.geometry = new THREE.BoxGeometry(reach, 2, deep);
            room.floor.position.set(reach / 2 - 60, -1, deep / 2 - 30);
        }
        const subjectSize = subject.getSize(new THREE.Vector3());
        const aspect = THREE.MathUtils.clamp(subjectSize.x / Math.max(subjectSize.y, 1), 0.82, 1.7);
        const capW = aspect >= 1 ? 1920 : Math.round(1440 * aspect);
        const capH = aspect >= 1 ? Math.round(1920 / aspect) : 1440;
        renderer.setPixelRatio(1);
        renderer.setSize(capW, capH, false);
        camera.aspect = capW / capH;
        backdrop = renderBackdrop(subject);
        frameRenderCamera(camera, subject);
        key = studioKeyLight(camera, subject);
        controls.target.copy(subject.getCenter(new THREE.Vector3()));
        scene.background = new THREE.Color('#f4f1ec');
        renderer.toneMappingExposure = 1;
        prepareTraceTextures();
        renderer.setAnimationLoop(null);
        button.textContent = 'Işık ayarlanıyor';
        toast('Dolaba göre kadraj ve ışık kuruldu. Render birkaç dakika sürebilir.');
        const { WebGLPathTracer, GradientEquirectTexture } = await import('three-gpu-pathtracer');
        const envTex = new GradientEquirectTexture();
        envTex.topColor.set('#f6f5f3');
        envTex.bottomColor.set('#d4cfc6');
        envTex.update();
        scene.environment = envTex;
        scene.environmentIntensity = 0.72;
        const tracer = new WebGLPathTracer(renderer);
        const tilesX = 2;
        const tilesY = 2;
        const passes = 80;
        tracer.tiles.set(tilesX, tilesY);
        tracer.bounces = 6;
        tracer.transmissiveBounces = 8;
        tracer.multipleImportanceSampling = true;
        tracer.filterGlossyFactor = 0.55;
        tracer.stableNoise = true;
        tracer.renderDelay = 0;
        tracer.fadeDuration = 0;
        tracer.minSamples = 1;
        tracer.renderScale = 1;
        tracer.setScene(scene, camera);
        const goal = passes * tilesX * tilesY;
        const started = performance.now();
        while (tracer.samples < goal && performance.now() - started < 420000) {
            tracer.renderSample();
            const shown = Math.min(passes, Math.floor(tracer.samples / (tilesX * tilesY)));
            button.textContent = `Render ${shown}/${passes}`;
            await new Promise((resolve) => requestAnimationFrame(resolve));
        }
        button.textContent = 'Netleştiriliyor';
        await new Promise((resolve) => requestAnimationFrame(resolve));
        const href = denoiseRender(renderer.domElement).toDataURL('image/png');
        renderer.setPixelRatio(ratio);
        renderer.setSize(size.x, size.y, false);
        const link = document.createElement('a');
        link.href = href;
        link.download = `${state.name || 'dolap'}-render.png`;
        link.click();
        toast('Render indirildi.');
        try { tracer.dispose(); } catch { /* eski sürüm dispose hatası görüntüyü bozmaz */ }
    } catch (error) {
        console.error(error);
        renderer.render(scene, camera);
        const link = document.createElement('a');
        link.href = renderer.domElement.toDataURL('image/png');
        link.download = `${state.name || 'dolap'}-render.png`;
        link.click();
        toast('Işık izleme açılamadı, mevcut görüntü kaydedildi.');
    } finally {
        if (key) scene.remove(key);
        if (floorShot && room.floor) {
            room.floor.geometry.dispose();
            room.floor.geometry = floorShot.geometry;
            room.floor.position.copy(floorShot.position);
        }
        if (backdrop) {
            scene.remove(backdrop);
            backdrop.geometry.dispose();
            backdrop.material.dispose();
        }
        state.modules.forEach((mod, index) => { mod.open = opens[index]; });
        selectedId = wasSelected;
        showMeasures = measures;
        scene.environment = envMap;
        scene.environmentIntensity = env;
        scene.background = background;
        renderer.toneMappingExposure = exposure;
        resize();
        camera.fov = shot.fov;
        camera.near = shot.near;
        camera.far = shot.far;
        camera.position.copy(shot.pos);
        controls.target.copy(shot.target);
        camera.updateProjectionMatrix();
        controls.update();
        rebuild();
        renderer.setAnimationLoop(tick);
        button.disabled = false;
        button.textContent = label;
        rendering = false;
    }
}

function bindChrome() {
    el('project-name').addEventListener('change', (event) => {
        state.name = event.target.value || 'Dolabım';
        scheduleSave();
    });
    el('btn-new').onclick = () => { location.href = '/dolap'; };
    el('module-search').addEventListener('input', () => renderLibrary());
    el('btn-production').onclick = () => {
        renderProduction();
        el('production-modal').classList.remove('hidden');
    };
    el('production-close').onclick = () => el('production-modal').classList.add('hidden');
    el('btn-undo').onclick = undo;
    el('btn-redo').onclick = redo;
    el('btn-save').onclick = saveDesign;
    el('btn-share').onclick = shareDesign;
    el('btn-saved').onclick = () => { location.href = '/dolap'; };
    el('saved-close').onclick = () => el('saved-modal').classList.add('hidden');
    el('btn-measures').onclick = () => {
        showMeasures = !showMeasures;
        el('btn-measures').classList.toggle('is-on', showMeasures);
        rebuild();
    };
    el('btn-lights').onclick = () => {
        lightsOn = !lightsOn;
        syncLightsButton();
        rebuild();
    };
    el('btn-shot').onclick = () => { takeRender(); };
    el('view-bar').addEventListener('click', (event) => {
        const button = event.target.closest('[data-view]');
        if (!button) return;
        setCameraView(button.dataset.view);
    });
    el('btn-quote').onclick = () => {
        const form = el('quote-form');
        form.name.value = state.customerName || '';
        form.phone.value = state.phone || '';
        form.note.value = state.note || '';
        el('quote-modal').classList.remove('hidden');
    };
    el('saved-search').onclick = () => searchProjects(el('saved-phone').value, el('saved-list'), () => el('saved-modal').classList.add('hidden'));
    el('resume-search').onclick = () => searchProjects(el('resume-phone').value, el('resume-list'), () => el('customer-modal').classList.add('hidden'));
    el('customer-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(event.target);
        const msg = el('customer-msg');
        const left = Number(form.get('leftWall'));
        const front = Number(form.get('frontWall'));
        const right = Number(form.get('rightWall'));
        const roomH = Number(form.get('roomHeight'));
        if (![left, front, right].every((value) => Number.isFinite(value) && value >= 80 && value <= 800)) {
            msg.textContent = 'Duvar genişlikleri 80 ile 800 cm arasında olmalı.';
            return;
        }
        if (!Number.isFinite(roomH) || roomH < 160 || roomH > 400) {
            msg.textContent = 'Yükseklik 160 ile 400 cm arasında olmalı.';
            return;
        }
        state.customerName = String(form.get('customerName') || '').trim();
        state.phone = '';
        state.note = String(form.get('note') || '').trim();
        state.name = String(form.get('projectName') || 'Dolabım').trim() || 'Dolabım';
        state.leftWall = left;
        state.wallWidth = front;
        state.rightWall = right;
        state.roomHeight = roomH;
        state.height = clamp(roomH, H_MIN, H_MAX);
        state.modules = [];
        ensureModuleColors();
        msg.textContent = 'Kaydediliyor…';
        const ok = await createProject();
        if (!ok) {
            if (msg.textContent === 'Kaydediliyor…') msg.textContent = 'Proje kaydı yazılamadı.';
            return;
        }
        el('customer-modal').classList.add('hidden');
        render();
        frameCamera();
    });
    el('quote-close').onclick = () => el('quote-modal').classList.add('hidden');
    el('quote-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(event.target);
        const msg = el('quote-msg');
        msg.textContent = 'Gönderiliyor…';
        try {
            const response = await fetch(window.WARDROBE.quoteUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.WARDROBE.csrf, Accept: 'application/json' },
                body: JSON.stringify({ name: form.get('name'), phone: form.get('phone'), note: form.get('note'), design: state }),
            });
            const data = await response.json();
            msg.textContent = data.message || 'Gönderildi.';
            if (data.ok) toast('Teklif talebi alındı.');
        } catch {
            msg.textContent = 'Bağlantı kurulamadı. Tasarımı kaydedip daha sonra deneyin.';
        }
    });
    el('act-config').onclick = () => {
        if (!selected()) return;
        el('config-modal').classList.add('hidden');
        renderInspector();
        openPhoneSheet('doors');
    };
    el('config-close').onclick = () => el('config-modal').classList.add('hidden');
    el('act-clone').onclick = cloneSelected;
    el('act-delete').onclick = deleteSelected;
    el('open-library').onclick = () => openPhoneSheet('modules');
    el('open-material').onclick = () => openPhoneSheet('material');
    el('open-doors').onclick = () => openPhoneSheet('doors');
    el('open-measures').onclick = () => {
        showMeasures = true;
        el('btn-measures').classList.add('is-on');
        openPhoneSheet('measure');
    };
    el('open-project').onclick = () => openPhoneSheet('project');
    el('close-library').onclick = () => openPhoneSheet('modules');
    el('close-inspector').onclick = () => openPhoneSheet(document.body.dataset.sheet || 'material');
    el('close-project').onclick = () => openPhoneSheet('project');
    el('sheet-new').onclick = () => el('btn-new').click();
    el('sheet-undo').onclick = () => el('btn-undo').click();
    el('sheet-redo').onclick = () => el('btn-redo').click();
    el('sheet-saved').onclick = () => el('btn-saved').click();
    el('sheet-share').onclick = () => el('btn-share').click();
    el('sheet-quote').onclick = () => el('btn-quote').click();
    el('sheet-shot').onclick = () => el('btn-shot').click();
    el('selection-ui').addEventListener('pointerdown', (event) => event.stopPropagation());
}

function applyColorsToAll(source) {
    const fields = ['carcassId', 'doorColorId', 'backId', 'shelfId', 'drawerColorId', 'aluminumId', 'glassId'];
    fields.forEach((field) => {
        if (source[field]) state[field] = source[field];
    });
    state.modules.forEach((item) => {
        fields.forEach((field) => {
            if (source[field]) item[field] = source[field];
        });
    });
}

function catalogWoodId() {
    return materials.find((item) => item.group === 'melamin-kapli-panel' && item.code === 'A402')?.id || '';
}

function solidDoorColor() {
    return catalogWoodId();
}

function preferCatalogWood(mod) {
    const wood = catalogWoodId();
    if (!wood || !mod) return;
    const lake = materials.find((item) => item.group === 'melamin-kapli-panel' && item.code === 'D129')?.id || '';
    const current = mod.carcassId || '';
    if (current && current !== lake) return;
    mod.carcassId = wood;
    mod.doorColorId = wood;
    mod.interiorId = wood;
    mod.backId = wood;
    mod.shelfId = wood;
    mod.drawerColorId = wood;
}

function applyDefaultColors() {
    const oak = materials.find((item) => item.id === catalogWoodId()) || materials[0];
    if (!decorExists(state.carcassId)) state.carcassId = oak?.id || '';
    if (!decorExists(state.doorColorId)) state.doorColorId = oak?.id || '';
    if (!decorExists(state.interiorId)) state.interiorId = oak?.id || '';
    if (!decorExists(state.backId)) state.backId = oak?.id || '';
    if (!decorExists(state.shelfId)) state.shelfId = oak?.id || '';
    if (!ALUMINUM.some((item) => item.id === state.aluminumId)) state.aluminumId = 'al-gumus';
    if (!GLASS.some((item) => item.id === state.glassId)) state.glassId = 'cam-fum';
    if (typeof state.plinth !== 'boolean') state.plinth = true;
    if (typeof state.kulp !== 'boolean') state.kulp = true;
    if (typeof state.led !== 'boolean') state.led = true;
    if (!(Number(state.wallWidth) >= 80)) state.wallWidth = WALL_X;
    if (!(Number(state.leftWall) >= 80)) state.leftWall = 300;
    if (!(Number(state.rightWall) >= 80)) state.rightWall = 300;
    if (!(Number(state.roomHeight) >= 160)) state.roomHeight = 270;
}

function ensureModuleColors() {
    applyDefaultColors();
    state.modules.forEach((mod) => {
        if (!decorExists(mod.carcassId)) mod.carcassId = state.carcassId;
        if (!decorExists(mod.doorColorId)) mod.doorColorId = state.doorColorId;
        if (!decorExists(mod.interiorId)) mod.interiorId = state.interiorId;
        if (!decorExists(mod.backId)) mod.backId = state.backId || state.interiorId;
        if (!decorExists(mod.shelfId)) mod.shelfId = state.shelfId || state.interiorId;
        if (!ALUMINUM.some((item) => item.id === mod.aluminumId)) mod.aluminumId = state.aluminumId || 'al-gumus';
        if (!GLASS.some((item) => item.id === mod.glassId)) mod.glassId = state.glassId || 'cam-fum';
        if (!mod.height) mod.height = state.height;
        if (typeof mod.plinth !== 'boolean') mod.plinth = state.plinth !== false;
        if (typeof mod.kulp !== 'boolean') mod.kulp = state.kulp !== false;
        if (typeof mod.led !== 'boolean') mod.led = state.led !== false;
    });
}

function projectBody() {
    return {
        customerName: state.customerName,
        phone: state.phone,
        projectName: state.name || 'Dolabım',
        note: state.note || '',
        design: state,
    };
}

function scheduleSave() {
    if (!projectToken) return;
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => persist(false), 700);
}

async function persist(manual) {
    if (!projectToken || !state.customerName) {
        if (manual) toast('Önce müşteri bilgisi gerekli.');
        return false;
    }
    try {
        const response = await fetch(`${window.WARDROBE.projectBase}/proje/${projectToken}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.WARDROBE.csrf, Accept: 'application/json' },
            body: JSON.stringify(projectBody()),
        });
        const data = await response.json();
        if (manual) toast(data.message || (data.ok ? 'Proje kaydedildi.' : 'Kayıt yazılamadı.'));
        return Boolean(data.ok);
    } catch {
        if (manual) toast('Kayıt yazılamadı.');
        return false;
    }
}

function startNewProject() {
    state = blankState();
    applyDefaultColors();
    projectToken = null;
    selectedId = null;
    history = [];
    future = [];
    lightsOn = true;
    showMeasures = false;
    el('btn-lights').classList.add('is-on');
    el('btn-lights').textContent = 'Işıkları kapat';
    el('btn-measures').classList.remove('is-on');
    el('measure-layer').classList.add('hidden');
    window.history.replaceState(null, '', location.pathname);
    const form = el('customer-form');
    form.reset();
    form.projectName.value = 'Dolabım';
    form.leftWall.value = '300';
    form.frontWall.value = '300';
    form.rightWall.value = '300';
    form.roomHeight.value = '270';
    el('customer-msg').textContent = '';
    el('customer-modal').classList.remove('hidden');
    searchProjects('', el('resume-list'), () => el('customer-modal').classList.add('hidden'));
    el('saved-modal').classList.add('hidden');
    el('production-modal').classList.add('hidden');
    el('config-modal').classList.add('hidden');
    render();
    frameCamera();
}

async function createProject() {
    try {
        const response = await fetch(window.WARDROBE.projectStoreUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.WARDROBE.csrf, Accept: 'application/json' },
            body: JSON.stringify(projectBody()),
        });
        const data = await response.json().catch(() => null);
        if (!response.ok || !data?.ok || !data.token) {
            const detail = data?.message || (data?.errors ? Object.values(data.errors).flat().join(' ') : `Kayıt yazılamadı (${response.status}).`);
            el('customer-msg').textContent = detail;
            return false;
        }
        projectToken = data.token;
        window.history.replaceState(null, '', `${location.pathname}?proje=${projectToken}`);
        return true;
    } catch (error) {
        el('customer-msg').textContent = error?.message || 'Kayıt yazılamadı.';
        return false;
    }
}

async function openProject(token) {
    try {
        const response = await fetch(`${window.WARDROBE.projectBase}/proje/${token}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) return false;
        const data = await response.json();
        if (!data.design?.modules || data.design.modules.some((mod) => !mod.kind)) return false;
        state = data.design;
        state.customerName = data.customerName || state.customerName;
        state.phone = data.phone || state.phone;
        state.name = data.projectName || state.name;
        state.note = data.note || state.note || '';
        ensureModuleColors();
        await preloadModuleGlbs();
        projectToken = data.token;
        selectedId = null;
        history = [];
        future = [];
        window.history.replaceState(null, '', `${location.pathname}?proje=${projectToken}`);
        render();
        frameCamera();
        return true;
    } catch {
        return false;
    }
}

async function searchProjects(phone, box, onOpen) {
    const digits = String(phone || '').replace(/\D/g, '');
    box.innerHTML = '<p class="hint">Aranıyor…</p>';
    try {
        const query = digits.length >= 10 ? `?phone=${encodeURIComponent(phone)}` : '';
        const response = await fetch(`${window.WARDROBE.projectIndexUrl}${query}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        const projects = data.projects || [];
        box.innerHTML = projects.length
            ? projects.map((item) => `<button type="button" class="choice" data-token="${item.token}"><strong>${item.projectName}</strong><span>${item.customerName} · ${item.phone}${item.updatedAt ? ` · ${new Date(item.updatedAt).toLocaleString('tr-TR')}` : ''}</span></button>`).join('')
            : `<p class="hint">${digits.length >= 10 ? 'Bu telefona kayıtlı proje yok.' : 'Kayıtlı proje yok.'}</p>`;
        box.querySelectorAll('[data-token]').forEach((button) => {
            button.onclick = async () => {
                const ok = await openProject(button.dataset.token);
                if (ok) onOpen();
                else toast('Proje açılamadı.');
            };
        });
    } catch {
        box.innerHTML = '<p class="hint">Arama yapılamadı.</p>';
    }
}

async function loadGlbCatalog() {
    const headers = { Accept: 'application/json' };
    const [base, extra] = await Promise.all([
        fetch('/data/wardrobe-glb-modules.json', { headers }).then((response) => response.json()),
        fetch('/data/wardrobe-y26-modules.json', { headers }).then((response) => (response.ok ? response.json() : { modules: [] })),
    ]);
    const older = Array.isArray(base.modules) ? base.modules : [];
    const newer = Array.isArray(extra.modules) ? extra.modules : [];
    const seen = new Set(older.map((item) => item.id));
    glbCatalog = [...newer.filter((item) => item?.id && !seen.has(item.id)), ...older];
}

async function preloadModuleGlbs() {
    const ids = [...new Set(state.modules.map((mod) => mod.glb).filter(Boolean))];
    await Promise.all(ids.map(async (id) => {
        const item = glbCatalog.find((entry) => entry.id === id);
        if (!item) return;
        try {
            await ensureGlb(item);
        } catch {
            toast(`${item.name} modeli açılamadı.`);
        }
    }));
}

async function loadCadModules() {
    const response = await fetch('/data/wardrobe-cad.json', { headers: { Accept: 'application/json' } });
    const data = await response.json();
    cadModules = Array.isArray(data.modules) ? data.modules : [];
}

async function loadDxfModules() {
    const response = await fetch('/data/wardrobe-modules.json', { headers: { Accept: 'application/json' } });
    const data = await response.json();
    dxfModules = Array.isArray(data.modules) ? data.modules : [];
    if (!dxfById(addInteriorKind) && dxfModules[0]) addInteriorKind = dxfModules[0].id;
}

async function loadMaterials() {
    const response = await fetch(window.WARDROBE.materialsUrl, { headers: { Accept: 'application/json' } });
    const data = await response.json();
    materials = [];
    (data.groups || []).forEach((group) => {
        (group.items || []).forEach((item) => {
            materials.push({ ...item, group: group.slug, groupLabel: group.label });
        });
    });
}

async function boot() {
    init3d();
    bindChrome();
    try {
        await loadMaterials();
    } catch {
        toast('Kastamonu renkleri yüklenemedi.');
    }
    try {
        await loadGlbCatalog();
        await Promise.all(glbCatalog.map(async (item) => {
            try {
                await ensureGlb(item);
            } catch {
                glbFailed.add(item.id);
            }
        }));
    } catch (error) {
        console.warn(error);
    }
    try {
        await loadDxfModules();
    } catch (error) {
        console.warn(error);
    }
    applyDefaultColors();
    try {
        await loadProps();
    } catch (error) {
        console.warn(error);
    }
    const token = new URLSearchParams(location.search).get('proje');
    if (token && await openProject(token)) {
        el('customer-modal').classList.add('hidden');
        return;
    }
    if (loadHash()) {
        ensureModuleColors();
        render();
        frameCamera();
        if (state.customerName) {
            el('customer-modal').classList.add('hidden');
            await createProject();
            return;
        }
        el('customer-form').projectName.value = state.name || 'Dolabım';
    }
    el('customer-modal').classList.add('hidden');
    render();
    frameCamera();
}

boot();
