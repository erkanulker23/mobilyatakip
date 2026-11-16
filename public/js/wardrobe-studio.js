import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { PointerLockControls } from 'three/addons/controls/PointerLockControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { bindRoomSketch, roomSketchOnOpen, roomSketchBeforeHandoff } from './wardrobe-room-sketch.js?v=260';
import { layoutModules, doorCountForWidth, resizeOpeningSpan, wallOffsetMin as sharedWallOffsetMin, wallOffsetMax as sharedWallOffsetMax } from './wardrobe-layout.js?v=3';

const T = 1.8;
const BACK = 0.8;
const DOOR_T = 1.8;
const H_MIN = 160;
const H_MAX = 270;
const ROOM_H_MIN = 160;
const ROOM_H_MAX = 320;
const ROOM_H_STD = 320;
const PASS_RATIO = 0.82;
const SNAP_PICK_CM = 26;
const SNAP_WIZARD_CM = 40;
/** Telefon sheet davranışı; tablet/masaüstü sağ çekmece kullanır. */
const PHONE_MQ = '(max-width: 699px)';
function isPhone() {
    return window.matchMedia(PHONE_MQ).matches;
}
const ALUMINUM = [
    { id: 'al-inox', name: 'Inox', color: '#c8ced6', roughness: 0.24 },
    { id: 'al-sampanya', name: 'Şampanya', color: '#e8d0a8', roughness: 0.28 },
    { id: 'al-bronz', name: 'Bronz', color: '#a86b38', roughness: 0.3 },
    { id: 'al-gumus', name: 'Gümüş', color: '#eceff2', roughness: 0.3 },
    { id: 'al-beyaz', name: 'Beyaz', color: '#f8f8f6', roughness: 0.38 },
    { id: 'al-antrasit', name: 'Antrasit', color: '#5e656d', roughness: 0.34 },
    { id: 'al-siyah', name: 'Mat siyah', color: '#1a1a1a', roughness: 0.48 },
    { id: 'al-gold', name: 'Gold', color: '#d4b15a', roughness: 0.28 },
];
/** Kapaklar sekmesinde öne çıkan çerçeve renkleri. */
const ALUM_FOCUS = ['al-inox', 'al-sampanya', 'al-bronz'];
/** WebGL’de tint + iç görünürlük dengesi (çok yüksek transmission rengi siler, çok düşük opak yapar). */
const GLASS = [
    { id: 'cam-fum', name: 'Füme', color: '#6e6964', transmission: 0.48, roughness: 0.1, opacity: 0.46 },
    { id: 'cam-bronz', name: 'Bronz', color: '#b87333', transmission: 0.42, roughness: 0.11, opacity: 0.48 },
    { id: 'cam-siyah', name: 'Siyah', color: '#141414', transmission: 0.16, roughness: 0.15, opacity: 0.7 },
    { id: 'cam-seffaf', name: 'Şeffaf', color: '#eef3f6', transmission: 0.9, roughness: 0.04, opacity: 0.22 },
    { id: 'cam-gri', name: 'Gri', color: '#8a9298', transmission: 0.55, roughness: 0.09, opacity: 0.4 },
    { id: 'cam-antrasit', name: 'Antrasit', color: '#3a4046', transmission: 0.32, roughness: 0.12, opacity: 0.55 },
    { id: 'cam-mavi', name: 'Mavi', color: '#6a8eab', transmission: 0.55, roughness: 0.09, opacity: 0.4 },
    { id: 'cam-yesil', name: 'Yeşil', color: '#6d8f6b', transmission: 0.55, roughness: 0.09, opacity: 0.4 },
];
/** Kapaklar sekmesinde öne çıkan cam renkleri. */
const GLASS_FOCUS = ['cam-fum', 'cam-bronz', 'cam-siyah'];
const PLINTH = 8;
const WALL_X = 520;
const WALL_Z = 400;
/** Oda duvar kalınlığı (cm). İç yüzey oda sınırında; kalınlık dışarı doğru. */
const ROOM_WALL_T = 8;
const ROOM_H = 280;
const SAVE_KEY = 'meemare-wardrobe-saves';
const ROOM_HANDOFF_KEY = 'meemare-room-handoff';
const CABINET_HANDOFF_KEY = 'meemare-cabinet-handoff';

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
const onClick = (id, handler) => {
    const node = el(id);
    if (node) node.addEventListener('click', handler);
};
const on = (id, type, handler) => {
    const node = el(id);
    if (node) node.addEventListener(type, handler);
};
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
let allCabinetsSelected = false;
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
let snapHints = null;
let catalogBound = false;
const glbTemplates = new Map();
const glbFailed = new Set();
let glbCatalog = [];
let showMeasures = false;
let planMode = '3d';
let panelOpacity = 1;
let sceneFocus = 'room';
let guidesOn = true;
/** Inspector sekmesi — her render’da Kapaklar’a sıfırlanmasın. */
let inspectorActiveTab = 'kapak';
let inspectorBoundId = null;
const INSPECTOR_TABS = new Set(['kapak', 'ic', 'govde', 'baza', 'overlay', 'donanim']);

const FLOOR_STYLES = [
    { id: 'parquet', name: 'Parke', color: '#a67c52', roughness: 0.72 },
    { id: 'tile', name: 'Fayans', color: '#e8e6e2', roughness: 0.38 },
    { id: 'plain', name: 'Düz', color: '#c4b29a', roughness: 0.58 },
];
let perspCamera = null;
let orthoCamera = null;
let walkLock = null;
let walkMode = false;
/** Space basılıyken sol sürükleme = ortamı kaydır (pan). */
let spacePanHeld = false;
let walkSprint = false;
const walkKeys = { forward: false, back: false, left: false, right: false };
const WALK_EYE_CM = 158;
const WALK_SPEED = 320;
const WALK_SPRINT = 1.65;
let guideLines = null;
let customFor = null;
let lightsOn = true;
let hideAllDoors = false;
let addDoor = 'solid';
let addInteriorKind = 'tek-aski';
let addWidth = 60;
let dxfModules = [];
let cadModules = [];
const moduleJsonCache = new Map();
let moduleDrag = null;
let dragId = null;
let dragGhost = null;
const hiddenDoorIds = new Set();
const measureNodes = [];
const measureMat = new THREE.LineBasicMaterial({ color: 0x1f6b4a });

function applyWallsCm(front, left, right, height) {
    let changed = false;
    if (front !== null && front >= 80) {
        state.wallWidth = clamp(Math.round(front), 80, 800);
        changed = true;
    }
    if (left !== null && left >= 80) {
        state.leftWall = clamp(Math.round(left), 80, 800);
        changed = true;
    }
    if (right !== null && right >= 80) {
        state.rightWall = clamp(Math.round(right), 80, 800);
        changed = true;
    }
    if (height !== null && height >= ROOM_H_MIN) {
        state.roomHeight = clamp(Math.round(height), ROOM_H_MIN, ROOM_H_MAX);
        changed = true;
    }
    if (!changed) return false;
    ['wall-left', 'wall-front', 'wall-right', 'room-h'].forEach((id) => {
        const node = el(id);
        if (!node) return;
        if (id === 'wall-left') node.value = state.leftWall;
        if (id === 'wall-front') node.value = state.wallWidth;
        if (id === 'wall-right') node.value = state.rightWall;
        if (id === 'room-h') node.value = state.roomHeight;
    });
    refreshPlan();
    return true;
}

function applyRoomSketchExtras(extras) {
    if (!extras) return;
    const sketch = ensureSketch();
    if (Array.isArray(extras.sketchWalls) && extras.sketchWalls.length) {
        sketch.walls = extras.sketchWalls.map((w, i) => ({ ...w, id: w.id || `w${i}` }));
    }
    if (Array.isArray(extras.openings)) {
        sketch.openings = extras.openings.map((item, i) => ({
            id: item.id || `o${i}`,
            wallId: item.wallId || item.wall || 'front',
            kind: item.kind === 'window' ? 'window' : 'door',
            style: item.style || item.type || (item.kind === 'window' ? 'fixed' : 'single'),
            offset: Math.round(Number(item.offset) || 0),
            width: Math.round(Number(item.width) || (item.kind === 'window' ? 120 : 90)),
            height: Math.round(Number(item.height) || (item.kind === 'window' ? 120 : 210)),
            sill: Math.round(Number(item.sill ?? item.sillHeight) || (item.kind === 'window' ? 90 : 0)),
        }));
    }
}

window.MeemareStudioApplyWalls = (walls, extras) => {
    if (!walls) return false;
    const ok = applyWallsCm(
        Number(walls.frontCm ?? walls.front ?? walls.onduvar),
        Number(walls.leftCm ?? walls.left ?? walls.solduvar),
        Number(walls.rightCm ?? walls.right ?? walls.sagduvar),
        Number(walls.heightCm ?? walls.height ?? walls.roomHeight),
    );
    if (ok) {
        applyRoomSketchExtras(extras || walls);
        fitRoom?.();
        toggleRoomPlan(false);
        if (document.body.classList.contains('studio-plan-first')) {
            closePlanStudioStep(false);
        } else {
            toast(extras?.openings?.length
                ? `Oda + ${extras.openings.length} kapı/pencere uygulandı.`
                : 'Oda ölçüsü uygulandı. Modülleri kataloğdan sürükleyin.');
        }
    }
    return ok;
};

function toggleRoomPlan(open) {
    const on = Boolean(open);
    document.body.classList.toggle('show-room-plan', on);
    if (!on) return;
    // /plan ile aynı: yerel OpenPlan varsa onu aç (kapı/pencere kataloğu + seçim araçları).
    setRoomPlanEditorMode(preferredRoomPlanMode());
    const form = el('studio-plan-handoff');
    if (form) {
        if (form.onduvar) form.onduvar.value = state.wallWidth;
        if (form.solduvar) form.solduvar.value = state.leftWall;
        if (form.sagduvar) form.sagduvar.value = state.rightWall;
        if (form.odaboy) form.odaboy.value = state.roomHeight;
    }
    requestAnimationFrame(() => {
        if (roomPlanEditorMode === 'sketch') roomSketchOnOpen(state);
    });
}

function syncViewModeBar() {
    document.querySelectorAll('#plan-switch [data-plan]').forEach((button) => {
        button.classList.toggle('is-on', button.dataset.plan === planMode);
    });
}

function syncInspectorChrome(mod) {
    const title = el('inspector-title');
    const sub = el('inspector-sub');
    if (!title) return;
    if (mod) {
        title.textContent = mod.name;
        if (sub) sub.textContent = 'Kapak, iç ve donanım sekmeleri';
    } else if (sceneFocus === 'floor') {
        title.textContent = 'Zemin';
        if (sub) sub.textContent = 'Parke / fayans seçin';
    } else if (sceneFocus === 'wall') {
        title.textContent = 'Duvar';
        if (sub) sub.textContent = 'Renk ve opaklık';
    } else {
        title.textContent = 'Oda';
        if (sub) sub.textContent = 'Duvar/zemine tıklayın · ölçü düzenleyin';
    }
}

function applyRoomQuery(params) {
    if (!params) return false;
    const read = (keys) => {
        for (const key of keys) {
            const raw = params.get(key);
            if (raw === null || raw === '') continue;
            const value = Number(raw);
            if (Number.isFinite(value)) return value;
        }
        return null;
    };
    const front = read(['onduvar', 'wall', 'on']);
    const left = read(['solduvar', 'left']);
    const right = read(['sagduvar', 'right']);
    const height = read(['odaboy', 'roomHeight', 'tavan']);
    const changed = applyWallsCm(front, left, right, height);
    if (changed) toast('Oda planı ölçüleri yüklendi.');
    return changed;
}

function applyHandoffFromStorage() {
    try {
        const raw = localStorage.getItem(ROOM_HANDOFF_KEY);
        if (!raw) return false;
        const data = JSON.parse(raw);
        const w = data?.walls || {};
        const ok = applyWallsCm(
            Number(w.frontCm ?? w.front),
            Number(w.leftCm ?? w.left),
            Number(w.rightCm ?? w.right),
            Number(w.heightCm ?? w.height),
        );
        if (ok) {
            applyRoomSketchExtras({
                openings: data.openings,
                sketchWalls: data.sketchWalls,
            });
            fitRoom?.();
            toggleRoomPlan(false);
            toast(Array.isArray(data.openings) && data.openings.length
                ? `Plan aktarıldı · ${data.openings.length} kapı/pencere`
                : 'Plan sayfasından oda ölçüleri aktarıldı.');
        }
        localStorage.removeItem(ROOM_HANDOFF_KEY);
        return ok;
    } catch {
        return false;
    }
}

function meemareBundle() {
    return {
        format: 'meemare-wardrobe-bundle',
        version: 1,
        exportedAt: new Date().toISOString(),
        projectToken,
        room: {
            frontCm: state.wallWidth,
            leftCm: state.leftWall,
            rightCm: state.rightWall,
            heightCm: state.roomHeight,
            walls: (state.roomSketch?.walls || []).map((w) => ({
                id: w.id,
                role: w.role,
                x1: w.x1,
                y1: w.y1,
                x2: w.x2,
                y2: w.y2,
            })),
            openings: (state.roomSketch?.openings || []).map((item) => ({
                wall: item.wallId,
                kind: item.kind,
                offset: item.offset,
                width: item.width,
            })),
        },
        customerName: state.customerName,
        phone: state.phone,
        projectName: state.name || 'Dolabım',
        note: state.note || '',
        design: state,
    };
}

function downloadMeemareBundle() {
    const payload = meemareBundle();
    const blob = new Blob([`${JSON.stringify(payload, null, 2)}\n`], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    const slug = (state.name || 'dolap').replace(/[^\w\-]+/g, '_').slice(0, 40);
    anchor.href = url;
    anchor.download = `${slug || 'meemare'}_${Date.now()}.meemare.json`;
    anchor.click();
    URL.revokeObjectURL(url);
    toast('Proje dosyası indirildi.');
}

function openCabinetStudio() {
    const mod = selected();
    const widthMm = mod ? Math.round((Number(mod.width) || 45) * 10) : Math.round((state.wallWidth || 300) * 10);
    const heightMm = Math.round((mod ? moduleHeight(mod) : state.height || 250) * 10);
    const depthMm = Math.round((mod?.glbDepth || state.depth || 60) * 10);
    const doors = mod ? doorCount(mod) : 2;
    const params = new URLSearchParams({
        genislik: String(widthMm),
        yukseklik: String(heightMm),
        derinlik: String(depthMm),
        kapak: String(doors),
    });
    try {
        localStorage.setItem(CABINET_HANDOFF_KEY, JSON.stringify({ savedAt: Date.now(), module: mod, bundle: meemareBundle() }));
    } catch {
        /* devam */
    }
    window.open(`/dolap-uretim?${params}`, '_blank', 'noopener');
}

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
        aluminumId: 'al-inox',
        glassId: 'cam-fum',
        plinth: true,
        plinthHidden: false,
        kulp: true,
        led: true,
        wallWidth: 300,
        leftWall: 300,
        rightWall: 300,
        roomHeight: ROOM_H_STD,
        wallColor: '#f7f6f4',
        floorStyle: 'parquet',
        panelOpacity: 1,
        modules: [],
        roomSketch: { walls: [], openings: [] },
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
    return doorCountForWidth(mod?.width);
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
    if (mod.door === 'none') return 'Kapaksız';
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
        door: 'none',
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

/** Cam/alüminyum kapak çekmece yüzünü örtmesin — ölçülen veya katalog yüksekliği. */
function doorBaseClearance(parent, mod) {
    const plinth = plinthOf(mod);
    if (mod?.door !== 'glass' && mod?.door !== 'mixed') return plinth;
    if (parent) {
        parent.updateMatrixWorld(true);
        let top = 0;
        parent.traverse((child) => {
            if (child.name !== 'drawer-slide') return;
            const box = new THREE.Box3().setFromObject(child);
            if (!box.isEmpty()) top = Math.max(top, box.max.y);
        });
        if (top > plinth + 1) return top + 1.2;
    }
    const n = glbDrawerCount(mod) || moduleDrawerCount(mod);
    if (n > 0) return plinth + n * (Number(mod.drawerHeight) || 16) + 2;
    const drawers = mod?.glb ? cornerRecipe(mod.glb)?.drawers : null;
    if (drawers?.length) return Math.max(plinth, Math.max(...drawers) + 10);
    return plinth;
}

function moduleHeight(mod) {
    if (mod?.glb) {
        const raw = Number(mod.height) || Number(mod.glbHeight) || state.height;
        return clamp(raw, H_MIN, H_MAX);
    }
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
    return Number.isFinite(height) && height >= ROOM_H_MIN ? Math.min(ROOM_H_MAX, height) : ROOM_H_STD;
}

function roomDepthLimit() {
    return Math.max(sideLimit('left'), sideLimit('right'));
}

function alongSize(mod) {
    if (mod.kind === 'end') return 2;
    return Math.max(30, Number(mod.width) || 45);
}

function depthNudge(mod) {
    return clamp(Number(mod?.depthOffset) || 0, 0, 120);
}

function wallName(mod) {
    if (mod.kind === 'corner') return 'front';
    if (mod.wall === 'left' || mod.wall === 'right') return mod.wall;
    return 'front';
}

function sideCornerWall(mod) {
    if (!mod || mod.kind === 'corner' || mod.kind === 'end') return '';
    const wall = wallName(mod);
    if (wall !== 'left' && wall !== 'right') return '';
    const cornerTaken = state.modules.some((item) => item.kind === 'corner' && (wall === 'left' ? item.cornerSide === 'left' : item.cornerSide !== 'left'));
    if (cornerTaken) return '';
    const mates = state.modules.filter((item) => item !== mod && item.kind !== 'corner' && item.kind !== 'end' && wallName(item) === wall);
    const start = mates.reduce((min, item) => Math.min(min, Number(item.offset) || 0), Number(mod.offset) || 0);
    return Math.abs((Number(mod.offset) || 0) - start) < 8 ? wall : '';
}

function cornerModelId(mod) {
    if (mod?.kind === 'corner' && mod.glb) return mod.glb;
    if (cabinetHasDrawers(mod)) return 'kose_3';
    if (/ask/i.test(`${mod?.name || ''} ${mod?.spec || ''}`)) return 'kose_2';
    return 'kose_1';
}

function layout(modules) {
    return layoutModules(modules, {
        front: wallLimit(),
        left: sideLimit('left'),
        right: sideLimit('right'),
        depth: Number(state.depth) || 60,
        openings: state.roomSketch?.openings || [],
        dragId,
        owned: state.modules,
        magneticSnap: (mod, at) => magneticSnapOffset(mod, at),
    });
}


function snapshot() {
    history.push(JSON.stringify(state));
    if (history.length > 40) history.shift();
    future = [];
}

function commit(fn, opts = {}) {
    snapshot();
    fn();
    if (opts.sceneOnly) {
        rebuild();
        fitRoom();
        renderSummary();
        renderProduction();
        updateHud();
        syncInspectorLive();
        if (planMode === '2d') drawPlan();
    } else {
        render();
    }
    scheduleSave();
}

/** 3D güncelle, inspector DOM’unu silme (sekme/scroll kaybı olmasın). */
function commitScene(fn) {
    commit(fn, { sceneOnly: true });
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
        const size = 128;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(image, 0, 0, size, size);
        const src = ctx.getImageData(0, 0, size, size).data;
        const rough = new Uint8Array(size * size);
        const norm = new Uint8Array(size * size * 4);
        const ao = new Uint8Array(size * size);
        const height = new Float32Array(size * size);
        const heightAt = (x, y) => {
            const i = ((y * size) + x) * 4;
            return src[i] * 0.25 + src[i + 1] * 0.65 + src[i + 2] * 0.1;
        };
        for (let y = 0; y < size; y += 1) {
            for (let x = 0; x < size; x += 1) height[(y * size) + x] = heightAt(x, y);
        }
        const blur = new Float32Array(size * size);
        const radius = 2;
        for (let y = 0; y < size; y += 1) {
            for (let x = 0; x < size; x += 1) {
                let sum = 0;
                let count = 0;
                for (let oy = -radius; oy <= radius; oy += 2) {
                    for (let ox = -radius; ox <= radius; ox += 2) {
                        sum += height[(((y + oy + size) % size) * size) + ((x + ox + size) % size)];
                        count += 1;
                    }
                }
                blur[(y * size) + x] = sum / count;
            }
        }
        for (let y = 0; y < size; y += 1) {
            for (let x = 0; x < size; x += 1) {
                const i = (y * size) + x;
                const fine = height[i] - blur[i];
                rough[i] = Math.round(Math.min(215, Math.max(165, 188 + fine * 0.08)));
                const dx = (height[i] - blur[i]) - (height[(y * size) + ((x + size - 1) % size)] - blur[(y * size) + ((x + size - 1) % size)]);
                const dy = (height[i] - blur[i]) - (height[(((y + size - 1) % size) * size) + x] - blur[(((y + size - 1) % size) * size) + x]);
                const nx = -dx * 0.035;
                const ny = -dy * 0.035;
                const nz = 1;
                const len = Math.hypot(nx, ny, nz) || 1;
                const o = i * 4;
                norm[o] = (nx / len * 0.5 + 0.5) * 255;
                norm[o + 1] = (ny / len * 0.5 + 0.5) * 255;
                norm[o + 2] = (nz / len * 0.5 + 0.5) * 255;
                norm[o + 3] = 255;
                ao[i] = Math.round(Math.min(255, Math.max(214, 242 + fine * 0.12)));
            }
        }
        const roughness = new THREE.DataTexture(rough, size, size);
        roughness.colorSpace = THREE.NoColorSpace;
        roughness.wrapS = roughness.wrapT = THREE.RepeatWrapping;
        roughness.needsUpdate = true;
        const normal = new THREE.DataTexture(norm, size, size);
        normal.colorSpace = THREE.NoColorSpace;
        normal.wrapS = normal.wrapT = THREE.RepeatWrapping;
        normal.needsUpdate = true;
        const ambient = new THREE.DataTexture(ao, size, size);
        ambient.colorSpace = THREE.NoColorSpace;
        ambient.wrapS = ambient.wrapT = THREE.RepeatWrapping;
        ambient.needsUpdate = true;
        const pack = { roughness, normal, ao: ambient };
        surfaceMapCache.set(url, pack);
        matCache.forEach((mat) => {
            if (mat.userData.externalPbr || mat.userData.decorUrl !== url || mat.userData.finish === 'highgloss') return;
            mat.roughnessMap = roughness;
            mat.roughness = mat.userData.finish === 'gloss' ? 0.72 : 1;
            mat.normalMap = normal;
            mat.normalScale = new THREE.Vector2(0.08, 0.08);
            mat.aoMap = ambient;
            mat.aoMapIntensity = 0.22;
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
    const opa = opts.glass || opts.metal ? 1 : (state.panelOpacity ?? panelOpacity ?? 1);
    const id = `${key}|${item ? item.id : source}|${opts.glass ? 1 : 0}|${opts.metal ? 1 : 0}|${opts.transmission ?? ''}|${opts.opacity ?? ''}|${opts.attenuation ?? ''}|${opts.representative ? woodStudyMode() : 'plain'}|o${Math.round(opa * 100)}`;
    if (matCache.has(id)) return matCache.get(id);
    // Cam: WebGL’de tint görünsün diye transmission düşürülür; thickness 0 pathtracer
    // (three-gpu-pathtracer 0.0.23) siyah pane sorununu önler.
    const mat = opts.glass
        ? (() => {
            const transmission = THREE.MathUtils.clamp(opts.transmission ?? 0.88, 0.1, 0.92);
            const opacity = THREE.MathUtils.clamp(opts.opacity ?? (0.2 + transmission * 0.55), 0.2, 0.9);
            const glassMat = new THREE.MeshPhysicalMaterial({
                color: source,
                roughness: opts.roughness ?? 0.06,
                metalness: 0,
                specularIntensity: 1,
                transmission,
                thickness: 0,
                transparent: true,
                opacity,
                depthWrite: opacity > 0.7,
                ior: 1.5,
                attenuationColor: new THREE.Color(source),
                attenuationDistance: transmission > 0.7 ? Infinity : 0.8,
                envMapIntensity: 1.2,
                clearcoat: 0.22,
                clearcoatRoughness: 0.06,
                side: THREE.DoubleSide,
            });
            glassMat.userData.glass = true;
            return glassMat;
        })()
        : item && !flat
        ? new THREE.MeshStandardMaterial({
            color: '#ffffff',
            map: textureFor(item.image),
            roughness: item.finish_hint === 'highgloss' ? 0.22 : item.finish_hint === 'gloss' ? 0.42 : 0.62,
            metalness: item.finish_hint === 'highgloss' ? 0.04 : 0,
            specularIntensity: 0.28,
            envMapIntensity: 0.16,
            side: THREE.DoubleSide,
        })
        : new THREE.MeshStandardMaterial({
            color: item ? (PLAIN_CODE_HEX[item.code] || '#d0d2cf') : source,
            map: null,
            roughness: flat ? 0.82 : (opts.metal ? 0.48 : 0.62),
            metalness: opts.metal ? 0.55 : 0,
            specularIntensity: opts.metal ? 0.32 : 0.14,
            envMapIntensity: opts.metal ? 0.08 : 0.1,
            side: THREE.DoubleSide,
        });
    if (flat && item.image) {
        mat.userData.flatUrl = item.image;
        const known = flatColorCache.get(item.image);
        if (known) mat.color.copy(known);
        else {
            if (PLAIN_CODE_HEX[item.code]) mat.color.set(PLAIN_CODE_HEX[item.code]);
            loadFlatColor(item.image);
        }
    } else if (item?.image && opts.representative && item.finish_hint !== 'highgloss') {
        mat.userData.decorUrl = item.image;
        mat.userData.finish = item.finish_hint || 'matte';
        applyRepresentativeWood(mat, item.image);
    } else if (item?.image) {
        mat.userData.decorUrl = item.image;
        mat.userData.finish = item.finish_hint || 'matte';
    }
    if (!opts.glass && !opts.metal && opa < 0.99) {
        mat.transparent = true;
        mat.opacity = opa;
        mat.depthWrite = opa > 0.55;
    }
    matCache.set(id, mat);
    return mat;
}

let woodNormalTex = null;
let woodRoughTex = null;

function applyWoodPbr(mat) {
    if (!woodNormalTex) {
        const loader = new THREE.TextureLoader();
        const normal = loader.load('/textures/wardrobe/wood094-normal.jpg');
        normal.colorSpace = THREE.NoColorSpace;
        normal.wrapS = normal.wrapT = THREE.RepeatWrapping;
        normal.repeat.set(0.4, 0.4);
        normal.center.set(0.5, 0.5);
        normal.rotation = Math.PI / 2;
        const rough = loader.load('/textures/wardrobe/wood094-roughness.jpg');
        rough.colorSpace = THREE.NoColorSpace;
        rough.wrapS = rough.wrapT = THREE.RepeatWrapping;
        rough.repeat.copy(normal.repeat);
        rough.center.copy(normal.center);
        rough.rotation = normal.rotation;
        woodNormalTex = normal;
        woodRoughTex = rough;
    }
    mat.userData.externalPbr = true;
    mat.normalMap = woodNormalTex;
    mat.normalScale = new THREE.Vector2(0.32, 0.32);
    mat.roughnessMap = woodRoughTex;
    mat.roughness = 1;
    mat.aoMap = null;
    mat.needsUpdate = true;
}

let woodScan = null;

function ensureWoodScan() {
    if (woodScan) return woodScan;
    const loader = new THREE.TextureLoader();
    const load = (url, color) => {
        const tex = loader.load(url, () => {
            woodScan.ready += 1;
            window.__woodReady = woodScan.ready >= 3;
        });
        tex.colorSpace = color ? THREE.SRGBColorSpace : THREE.NoColorSpace;
        tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
        tex.repeat.set(1, 1);
        tex.anisotropy = 8;
        return tex;
    };
    woodScan = {
        ready: 0,
        color: load('/textures/wardrobe/wood094-color.jpg', true),
        normal: load('/textures/wardrobe/wood094-normal-gl.jpg', false),
        rough: load('/textures/wardrobe/wood094-roughness-gl.jpg', false),
    };
    window.__woodReady = false;
    return woodScan;
}

const woodTintCache = new Map();
const woodTintUsers = new Set();

function tintMaterial(mat, url, scale = 1) {
    mat.userData.tintUrl = url;
    mat.userData.tintScale = scale;
    woodTintUsers.add(mat);
    const apply = (color) => {
        if (!color || mat.userData.tintUrl !== url) return;
        mat.color.copy(color).multiplyScalar(mat.userData.tintScale || 1);
        mat.needsUpdate = true;
    };
    if (woodTintCache.get(url)) {
        apply(woodTintCache.get(url));
        return;
    }
    if (woodTintCache.has(url)) return;
    woodTintCache.set(url, null);
    const image = new Image();
    image.onload = () => {
        const size = 24;
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
        woodTintCache.set(url, color);
        woodTintUsers.forEach((item) => {
            if (item.userData.tintUrl === url) item.color.copy(color).multiplyScalar(item.userData.tintScale || 1);
        });
    };
    image.src = url;
    apply(null);
}

function woodStudyMode() {
    const mode = window.__woodMode;
    if (mode === 'catalog' || mode === 'mismatch' || mode === 'wood094') return mode;
    return 'oak';
}

let oakMaps = null;

function ensureOakMaps() {
    if (oakMaps) return oakMaps;
    const loader = new THREE.TextureLoader();
    const repeat = 1 / 1.83;
    const load = (url, color) => {
        const tex = loader.load(url, () => {
            oakMaps.ready += 1;
            window.__oakReady = oakMaps.ready >= 3;
        });
        tex.colorSpace = color ? THREE.SRGBColorSpace : THREE.NoColorSpace;
        tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
        tex.repeat.set(repeat, repeat);
        tex.anisotropy = 8;
        return tex;
    };
    oakMaps = {
        ready: 0,
        color: load('/textures/wardrobe/oak-veneer-color.jpg', true),
        normal: load('/textures/wardrobe/oak-veneer-normal.jpg', false),
        rough: load('/textures/wardrobe/oak-veneer-roughness.jpg', false),
    };
    window.__oakReady = false;
    return oakMaps;
}

function applyRepresentativeWood(mat, imageUrl) {
    const mode = woodStudyMode();
    mat.userData.externalPbr = true;
    mat.userData.woodMode = mode;
    mat.userData.matchedWood = false;
    mat.userData.representativeWood = false;
    if (mode === 'catalog') {
        mat.normalMap = null;
        mat.roughnessMap = null;
        mat.aoMap = null;
        mat.roughness = 0.72;
        mat.metalness = 0;
        mat.needsUpdate = true;
        return;
    }
    if (mode === 'mismatch') {
        applyWoodPbr(mat);
        return;
    }
    if (mode === 'oak') {
        const oak = ensureOakMaps();
        mat.map = oak.color;
        mat.color.set('#ffffff');
        mat.normalMap = oak.normal;
        mat.normalScale = new THREE.Vector2(0.12, 0.12);
        mat.roughnessMap = oak.rough;
        mat.roughness = 0.82;
        mat.userData.matchedWood = true;
        mat.metalness = 0;
        mat.envMapIntensity = 0.2;
        mat.aoMap = null;
        mat.userData.decorNote = imageUrl || '';
        mat.needsUpdate = true;
        return;
    }
    const scan = ensureWoodScan();
    mat.map = scan.color;
    mat.normalMap = scan.normal;
    mat.normalScale = new THREE.Vector2(0.16, 0.16);
    mat.roughnessMap = scan.rough;
    mat.roughness = 0.78;
    mat.metalness = 0;
    mat.envMapIntensity = 0.24;
    mat.aoMap = null;
    mat.color.set('#ffffff');
    mat.userData.representativeWood = true;
    tintMaterial(mat, imageUrl, 1);
    mat.needsUpdate = true;
}

function beveledBoard(parent, w, h, d, x, y, z, mat, uvOpts = null) {
    const box = new THREE.Box3(new THREE.Vector3(-w / 2, -h / 2, -d / 2), new THREE.Vector3(w / 2, h / 2, d / 2));
    const geometry = chamferBoxGeometry(box, Math.min(0.12, d * 0.22, w * 0.04, h * 0.04)) || new THREE.BoxGeometry(w, h, d);
    assignPanelUv(geometry, uvOpts || { tile: 100 });
    const mesh = new THREE.Mesh(geometry, mat);
    mesh.position.set(x, y, z);
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    parent.add(mesh);
    return mesh;
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
    geometry.setAttribute('uv2', uv.clone());
}

function assignPanelUv(geometry, opts = {}) {
    const position = geometry.attributes.position;
    const normal = geometry.attributes.normal;
    if (!position || !normal) return;
    const tile = Number(opts.tile) > 0 ? Number(opts.tile) : 1;
    const along = opts.along === 'height';
    const uv = new THREE.BufferAttribute(new Float32Array(position.count * 2), 2);
    for (let i = 0; i < position.count; i += 1) {
        const x = position.getX(i);
        const y = position.getY(i);
        const z = position.getZ(i);
        const nx = Math.abs(normal.getX(i));
        const ny = Math.abs(normal.getY(i));
        const nz = Math.abs(normal.getZ(i));
        const vertical = nx >= ny || nz >= ny;
        let u;
        let v;
        if (along && vertical && nx >= nz) {
            u = y / tile;
            v = z / tile;
        } else if (along && vertical) {
            u = y / tile;
            v = x / tile;
        } else if (nx >= ny && nx >= nz) {
            u = z / tile;
            v = y / tile;
        } else if (ny >= nx && ny >= nz) {
            u = x / tile;
            v = z / tile;
        } else {
            u = x / tile;
            v = y / tile;
        }
        uv.setXY(i, u, v);
    }
    geometry.setAttribute('uv', uv);
    geometry.setAttribute('uv2', uv.clone());
}

function assignDecorUv(geometry) {
    const position = geometry.attributes.position;
    const normal = geometry.attributes.normal;
    if (!position || !normal) return;
    geometry.computeBoundingBox();
    const size = geometry.boundingBox.getSize(new THREE.Vector3());
    const uv = new THREE.BufferAttribute(new Float32Array(position.count * 2), 2);
    for (let i = 0; i < position.count; i += 1) {
        const x = position.getX(i);
        const y = position.getY(i);
        const z = position.getZ(i);
        const nx = Math.abs(normal.getX(i));
        const ny = Math.abs(normal.getY(i));
        const nz = Math.abs(normal.getZ(i));
        let u;
        let v;
        if (ny >= nx && ny >= nz) {
            if (size.x >= size.z) {
                u = z;
                v = x;
            } else {
                u = x;
                v = z;
            }
        } else if (nx >= nz) {
            u = z;
            v = y;
        } else {
            u = x;
            v = y;
        }
        uv.setXY(i, u, v);
    }
    geometry.setAttribute('uv', uv);
    geometry.setAttribute('uv2', uv.clone());
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
    // GLB içindeki kapak/kulp mesh’leri prosedürel kapakla çakışıp tabanda saçılma yapabiliyor.
    if (/^kapak|door_?leaf|kulp_?kapak|handle_door/.test(n)) return true;
    return /aski|kiyafet|ayakkabi|valiz|tekstil|canta|bavul|gomlek|pantolon|elbise/.test(n);
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
    // Indexed BufferGeometry'yi üçgen çorbası sanmak parçalı/patlak mesh üretir (dolap altı saçılma).
    const source = geometry?.index ? geometry.toNonIndexed() : geometry;
    const pos = source?.attributes?.position;
    const nor = source?.attributes?.normal;
    const uv = source?.attributes?.uv;
    if (!pos || !nor || pos.count < 3) return { taken: null, left: geometry };
    const taken = [];
    const left = [];
    for (let i = 0; i < pos.count; i += 3) {
        if (i + 2 >= pos.count) break;
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
        next.computeVertexNormals();
        next.computeBoundingBox();
        return next;
    };
    return { taken: build(taken), left: build(left) };
}

function edgeBandMaterial(sourceId, opts = {}) {
    const base = material('edge-band', sourceId, opts);
    const next = base.clone();
    next.metalness = 0;
    next.aoMap = null;
    next.map = null;
    next.roughness = 0.82;
    next.roughnessMap = null;
    next.normalMap = null;
    const tintUrl = base.userData.tintUrl;
    if (opts.representative && woodStudyMode() === 'oak') {
        next.color.set('#947455');
        next.roughness = 0.58;
    } else if (tintUrl) tintMaterial(next, tintUrl, 0.9);
    else {
        next.userData.flatDark = 0.96;
        if (next.userData.flatUrl) {
            const known = flatColorCache.get(next.userData.flatUrl);
            if (known) next.color.copy(known).multiplyScalar(0.96);
        } else next.color.multiplyScalar(0.96);
    }
    return next;
}

function addRailSockets(mesh) {
    if (!mesh.geometry || !mesh.parent) return;
    if (!mesh.geometry.boundingBox) mesh.geometry.computeBoundingBox();
    const box = mesh.geometry.boundingBox;
    if (!box) return;
    const size = box.getSize(new THREE.Vector3());
    const center = box.getCenter(new THREE.Vector3());
    const axis = size.x >= size.y && size.x >= size.z ? 'x' : size.z >= size.y ? 'z' : 'y';
    const geo = new THREE.CylinderGeometry(0.018, 0.018, 0.012, 18);
    if (axis === 'x') geo.rotateZ(Math.PI / 2);
    if (axis === 'z') geo.rotateX(Math.PI / 2);
    [-1, 1].forEach((sign) => {
        const cap = new THREE.Mesh(geo, mesh.material);
        cap.name = 'ASILIK_BURC';
        cap.castShadow = true;
        cap.receiveShadow = true;
        cap.position.copy(center);
        cap.position[axis] += sign * (size[axis] / 2);
        mesh.parent.add(cap);
    });
}

function paintGlb(root, mod) {
    const drawerId = drawerOf(mod);
    // Tüm Y26 gövdelerinde Kastamonu katalog dokusu (A402 vb.); düz gri değil.
    const woodOpts = { representative: true };
    const added = [];
    root.traverse((child) => {
        if (!child.isMesh) return;
        const role = glbPartRole(child.name);
        const alum = aluminumOf(mod);
        const source = role === 'metal'
            ? material(`glb-metal-${alum.id}`, alum.color, { metal: true })
            : role === 'drawer'
                ? material('glb-drawer', drawerId, woodOpts)
                : role === 'shelf'
                    ? material('glb-shelf', shelfOf(mod), woodOpts)
                    : role === 'back'
                        ? material('glb-back', backOf(mod), woodOpts)
                        : material('glb-body', carcassOf(mod), woodOpts);
        const next = role === 'back' ? source.clone() : source;
        next.vertexColors = false;
        if (role !== 'metal' && !next.userData?.glass) {
            next.transparent = false;
            next.opacity = 1;
            next.depthWrite = true;
        }
        if (role === 'back') next.roughness = Math.min(0.92, (next.roughness || 0.62) + 0.12);
        child.castShadow = true;
        child.receiveShadow = true;
        child.material = next;
        if (woodStudyMode() === 'oak' && role !== 'metal' && child.geometry?.attributes?.position) {
            child.geometry = child.geometry.clone();
            assignDecorUv(child.geometry);
        }
        const name = String(child.name || '').toLowerCase();
        if (role === 'drawer' && /_on_/.test(name)) {
            const face = next.clone();
            face.roughness = 0.94;
            face.roughnessMap = null;
            face.normalMap = null;
            face.aoMap = null;
            face.metalness = 0;
            face.envMapIntensity = 0.05;
            face.specularIntensity = 0.06;
            face.userData.externalPbr = true;
            child.material = face;
        }
        if (role === 'metal' && /askilik|boru|rail/.test(name)) {
            child.material = alumFrameMaterial(alum);
            if (mod.glb === 'y26_cift_01') addRailSockets(child);
        }
        // Kenar bandı / extractFaces: indexed GLB’de tabanda patlak mesh üretiyordu — kapalı.
    });
    void added;
}

function moduleDrawerCount(mod) {
    if (mod?.glb) return 0;
    const cad = cadById(mod.cad);
    if (cad) return mod.drawerCount ?? cad.drawers ?? 0;
    return mod.drawers || 0;
}

function cabinetHasDrawers(mod) {
    if (mod?.glb) return glbDrawerCount(mod) > 0;
    return moduleDrawerCount(mod) > 0;
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

function addRoundTube(parent, length, radius, x, y, z, mat, axis = 'x') {
    const geo = new THREE.CylinderGeometry(radius, radius, Math.max(radius * 2, length), 24);
    if (axis === 'x') geo.rotateZ(Math.PI / 2);
    if (axis === 'z') geo.rotateX(Math.PI / 2);
    const mesh = new THREE.Mesh(geo, mat);
    mesh.name = 'ASKILIK_BORU';
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    mesh.position.set(x, y, z);
    parent.add(mesh);
    return mesh;
}

function addShelfBoard(parent, w, d, y, mat) {
    panel(parent, w - 2 * T - 0.4, 1.8, d - BACK - 2, w / 2, y, BACK + (d - BACK) / 2, mat);
}

function addRailBar(parent, w, d, y) {
    addRoundTube(parent, Math.max(8, w - 2 * T - 4), 1.25, w / 2, y, d * 0.46, alumFrameMaterial(aluminumOf(null)));
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
    addRoundTube(parent, span, 1.25, x, y, d * 0.46, alumFrameMaterial(aluminumOf(null)));
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
                new THREE.CylinderGeometry(piece.kind === 'pants' ? 0.45 : 1.25, piece.kind === 'pants' ? 0.45 : 1.25, span, 24),
                piece.kind === 'pants' ? metal : alumFrameMaterial(aluminumOf(mod))
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
        ? new THREE.MeshStandardMaterial({ color: '#fff4d2', emissive: '#ffe7b0', emissiveIntensity: 3.2, roughness: 0.42 })
        : new THREE.MeshStandardMaterial({ color: '#d9d3c8', emissive: '#000000', emissiveIntensity: 0, roughness: 0.55 });
    clothCache.set(key, mat);
    return mat;
}

const alumFrameCache = new Map();

function alumFrameMaterial(alum) {
    const key = `v4|${alum?.id || 'al-inox'}|${alum?.color || ''}`;
    if (alumFrameCache.has(key)) return alumFrameCache.get(key);
    // MeshPhysical clearcoat/anisotropy paints metal black in three-gpu-pathtracer 0.0.23.
    const mat = new THREE.MeshStandardMaterial({
        color: alum?.color || '#c8ced6',
        metalness: alum?.id === 'al-beyaz' ? 0.35 : alum?.id === 'al-siyah' ? 0.55 : 0.82,
        roughness: alum?.roughness ?? 0.28,
        envMapIntensity: 1.25,
    });
    alumFrameCache.set(key, mat);
    return mat;
}

function glassMaterial(glass) {
    const item = glass || glassOf(null);
    return material(`glass-v3-${item.id}`, item.color, {
        glass: true,
        transmission: item.transmission,
        roughness: item.roughness,
        opacity: item.opacity,
        attenuation: item.attenuation,
    });
}

function orderedFinishes(list, focusIds) {
    const focus = focusIds
        .map((id) => list.find((item) => item.id === id))
        .filter(Boolean);
    const rest = list.filter((item) => !focusIds.includes(item.id));
    return [...focus, ...rest];
}

/** Alüminyum/cam seçimi: kapak yoksa cam kapak aç, 3D’yi anında yenile. */
function applyModuleAluminum(mod, id) {
    if (!mod || !ALUMINUM.some((item) => item.id === id)) return;
    const needGlassDoor = mod.door !== 'glass' && mod.door !== 'mixed';
    const apply = () => {
        mod.aluminumId = id;
        state.aluminumId = id;
        if (needGlassDoor) {
            mod.door = 'glass';
            if (!GLASS.some((item) => item.id === mod.glassId)) {
                mod.glassId = state.glassId || 'cam-fum';
            }
        }
    };
    if (needGlassDoor) commit(apply);
    else commitScene(apply);
}

function applyModuleGlass(mod, id) {
    if (!mod || !GLASS.some((item) => item.id === id)) return;
    const needGlassDoor = mod.door !== 'glass' && mod.door !== 'mixed';
    const apply = () => {
        mod.glassId = id;
        state.glassId = id;
        if (needGlassDoor) {
            mod.door = 'glass';
            if (!ALUMINUM.some((item) => item.id === mod.aluminumId)) {
                mod.aluminumId = state.aluminumId || 'al-inox';
            }
        }
    };
    if (needGlassDoor) commit(apply);
    else commitScene(apply);
}

function ledStyleOf(mod) {
    if (!mod || mod.led === false || mod.kind === 'end' || mod.ledStyle === 'off') return 'off';
    return 'line';
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

function addLedLine(parent, size, at, glowSize, glowAt, channel, strip) {
    const rail = new THREE.Mesh(new THREE.BoxGeometry(size.x, size.y, size.z), channel);
    rail.name = 'LED_PROFIL';
    rail.position.set(at.x, at.y, at.z);
    rail.castShadow = false;
    parent.add(rail);
    const glow = new THREE.Mesh(new THREE.BoxGeometry(glowSize.x, glowSize.y, glowSize.z), strip);
    glow.name = 'LED_GLOW';
    glow.position.set(glowAt.x, glowAt.y, glowAt.z);
    glow.castShadow = false;
    parent.add(glow);
}

function addCabinetLeds(parent, w, h, d, mod, shelves) {
    if (ledStyleOf(mod) === 'off') return;
    const strip = ledMaterial(lightsOn === true);
    const channel = alumFrameMaterial(aluminumOf(mod));
    const floor = plinthOf(mod) + 6;
    const top = h - T - 4;
    const height = Math.max(24, top - floor);
    const y = floor + height / 2;
    const rear = BACK + 0.9;
    [T + 0.45, w - T - 0.45].forEach((x) => {
        const inward = x < w / 2 ? 0.28 : -0.28;
        addLedLine(
            parent,
            { x: 0.8, y: height, z: 0.6 },
            { x, y, z: rear },
            { x: 0.22, y: height - 1.2, z: 0.18 },
            { x: x + inward, y, z: rear + 0.35 },
            channel,
            strip
        );
    });
    const width = Math.max(8, w - 2 * T - 3);
    (shelves || []).forEach((shelfY) => {
        if (shelfY < floor || shelfY > h - 1) return;
        addLedLine(
            parent,
            { x: width, y: 0.28, z: 0.45 },
            { x: w / 2, y: shelfY + 1.05, z: rear },
            { x: width - 1.2, y: 0.14, z: 0.12 },
            { x: w / 2, y: shelfY + 1.05, z: rear + 0.28 },
            channel,
            strip
        );
    });
}

let propTemplates = null;

function placeModel(parent, template, opts) {
    if (!template) return false;
    const obj = template.clone(true);
    obj.rotation.set(opts.rx || 0, opts.yaw || 0, 0);
    if (opts.name) {
        obj.name = opts.name;
        obj.traverse((child) => {
            if (child.isMesh) child.name = opts.name;
        });
    }
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

function foldPile() {
    return;
}

function foldPileUnused(parent, x, y, z, width, seed) {
    const depth = Math.min(18, Math.max(10, width * 0.75));
    for (let i = 0; i < 3; i += 1) {
        panel(parent, width, 2.2, depth, x + (i % 2) * 0.4, y + 1.4 + i * 2.4, z, clothMaterial(CLOTH[(seed + i * 2) % CLOTH.length]));
    }
}

function hangSpan() {
    return;
}

function hangSpanUnused(parent, x0, x1, y, z, keys, maxH) {
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

function hangPants() {
    return;
}

function hangPantsUnused(parent, w, d, pants) {
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
        shirt: 'library/gomlek.glb',
        blazer: 'library/ceket.glb',
        tee: 'library/tisort.glb',
        shoes: 'library/ayakkabi.glb',
        bag: 'library/canta.glb',
        box: 'library/kutu.glb',
    };
    const loaded = {};
    await Promise.all(Object.entries(files).map(async ([key, file]) => {
        try {
            const gltf = await loader.loadAsync(`${base}/${file}`);
            gltf.scene.traverse((child) => {
                if (!child.isMesh) return;
                child.castShadow = true;
                child.receiveShadow = true;
                const src = child.material;
                if (src?.isMeshPhysicalMaterial) {
                    child.material = new THREE.MeshStandardMaterial({
                        color: src.color.clone(),
                        roughness: src.roughness,
                        metalness: src.metalness,
                        map: src.map || null,
                        normalMap: src.normalMap || null,
                        side: THREE.DoubleSide,
                    });
                }
            });
            loaded[key] = gltf.scene;
        } catch (error) {
            console.warn('Dolap aksesuarı yüklenemedi', file, error);
        }
    }));
    propTemplates = loaded;
    if (state.modules.some((item) => item.glb === 'y26_cift_01')) render();
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
        const bar = 2.6;
        [panel(leaf, w, bar, 1.7, 0, h / 2 - bar / 2, 0.72, frame),
            panel(leaf, w, bar, 1.7, 0, -h / 2 + bar / 2, 0.72, frame),
            panel(leaf, bar, h, 1.7, -w / 2 + bar / 2, 0, 0.72, frame),
            panel(leaf, bar, h, 1.7, w / 2 - bar / 2, 0, 0.72, frame)].forEach((piece) => {
            if (piece) piece.name = 'CAM_CERCEVE';
        });
        const rim = 0.18;
        const paneW = Math.max(1, w - bar * 2 - rim * 2);
        const paneH = Math.max(1, h - bar * 2 - rim * 2);
        const pane = panel(leaf, paneW, paneH, 0.6, 0, 0, DOOR_T * 0.55, glassMaterial(glass));
        if (pane) pane.name = 'CAM';
        if (pane) pane.castShadow = false;
        const edgeMat = material(`glass-edge-${glass.id}`, glass.color, {
            glass: true,
            transmission: Math.min(0.35, (glass.transmission ?? 0.4) * 0.7),
            roughness: Math.max(0.12, glass.roughness ?? 0.12),
        });
        const openingW = w - bar * 2;
        const openingH = h - bar * 2;
        const z = DOOR_T * 0.55;
        [
            [openingW, rim, 0.22, 0, openingH / 2 - rim / 2],
            [openingW, rim, 0.22, 0, -openingH / 2 + rim / 2],
            [rim, openingH - rim * 2, 0.22, -openingW / 2 + rim / 2, 0],
            [rim, openingH - rim * 2, 0.22, openingW / 2 - rim / 2, 0],
        ].forEach(([rw, rh, rd, x, y]) => {
            const piece = panel(leaf, rw, rh, rd, x, y, z, edgeMat);
            if (piece) {
                piece.name = 'CAM_KENAR';
                piece.castShadow = false;
            }
        });
    } else {
        const doorOpts = mod.glb === 'y26_cift_01' ? { representative: true } : {};
        const doorUv = mod.glb === 'y26_cift_01' ? { tile: 200, along: 'height' } : null;
        const board = beveledBoard(leaf, w - 0.35, h - 0.35, DOOR_T, 0, 0, DOOR_T / 2, material('door', doorOf(mod), doorOpts), doorUv);
        if (board) board.name = 'KAPAK';
    }
    const hingeMat = alumFrameMaterial(aluminumOf(mod));
    const cupGeo = new THREE.CylinderGeometry(1.75, 1.75, 1.15, 18);
    cupGeo.rotateX(Math.PI / 2);
    const armGeo = new THREE.BoxGeometry(1.15, 0.55, 4.4);
    const yOff = Math.min(h * 0.38, Math.max(10, h / 2 - 11));
    [-1, 1].forEach((sign) => {
        const x = hinge === 'right' ? w / 2 - 2.6 : -w / 2 + 2.6;
        const cup = new THREE.Mesh(cupGeo, hingeMat);
        cup.name = 'MENTESE';
        cup.position.set(x, sign * yOff, -0.4);
        const arm = new THREE.Mesh(armGeo, hingeMat);
        arm.name = 'MENTESE';
        arm.position.set(x + (hinge === 'right' ? 1.5 : -1.5), sign * yOff, -1.9);
        leaf.add(cup, arm);
    });
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
    const base = doorBaseClearance(parent, mod);
    const doorH = h - base - 0.6;
    if (doorH < 24) return;
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
    const base = doorBaseClearance(parent, mod);
    const doorH = h - base - 0.6;
    if (doorH < 24) return;
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
    if (!selectionLineMat) {
        selectionLineMat = new THREE.LineBasicMaterial({ color: 0x1f6b4a, depthTest: true, transparent: true, opacity: 0.55 });
    }
    const box = footprint(mod);
    if (mod.kind === 'corner') return cornerSelection(mod, box);
    // İnce hat çerçevesi — kalın yeşil çubuklar/saçılma yok.
    const w = box.w;
    const h = box.h;
    const d = box.d;
    const positions = new Float32Array([
        0, 0, 0, w, 0, 0, w, 0, 0, w, 0, d, w, 0, d, 0, 0, d, 0, 0, d, 0, 0, 0,
        0, h, 0, w, h, 0, w, h, 0, w, h, d, w, h, d, 0, h, d, 0, h, d, 0, h, 0,
        0, 0, 0, 0, h, 0, w, 0, 0, w, h, 0, 0, 0, d, 0, h, d, w, 0, d, w, h, d,
    ]);
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const line = new THREE.LineSegments(geometry, selectionLineMat);
    line.renderOrder = 20;
    line.name = 'selection-frame';
    return line;
}

let selectionLineMat = null;

function cornerSelection(mod, box) {
    if (!selectionLineMat) {
        selectionLineMat = new THREE.LineBasicMaterial({ color: 0x149152, depthTest: false, transparent: true, opacity: 0.95 });
    }
    const w = box.w;
    const d = box.d;
    const h = box.h;
    const mirrored = sideCornerWall(mod) === 'left' || (mod.kind === 'corner' && mod.cornerSide === 'right');
    const pts = mirrored
        ? [[w, 0], [0, 0], [0, d / 2], [w / 2, d / 2], [w / 2, d], [w, d]]
        : [[0, 0], [w, 0], [w, d / 2], [w / 2, d / 2], [w / 2, d], [0, d]];
    const positions = [];
    const edge = (x1, y1, z1, x2, y2, z2) => positions.push(x1, y1, z1, x2, y2, z2);
    pts.forEach((point, index) => {
        const next = pts[(index + 1) % pts.length];
        edge(point[0], 0, point[1], next[0], 0, next[1]);
        edge(point[0], h, point[1], next[0], h, next[1]);
        edge(point[0], 0, point[1], point[0], h, point[1]);
    });
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    const line = new THREE.LineSegments(geometry, selectionLineMat);
    line.renderOrder = 20;
    const group = new THREE.Group();
    group.add(line);
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
    if (!(backZ == null || backZ < box.min.z + depth * 0.5)) {
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
    const seated = geometryBounds(root);
    const dx = Math.abs(seated.min.x) > 0.001 ? -seated.min.x : 0;
    const dy = seated.min.y < -0.001 ? -seated.min.y : 0;
    const dz = Math.abs(seated.min.z) > 0.001 ? -seated.min.z : 0;
    if (dx || dy || dz) {
        rewritePositions(root, (x, y, z, nx, ny, nz) => ({
            x: x + dx,
            y: y + dy,
            z: z + dz,
            nx,
            ny,
            nz,
        }));
    }
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
    quad([x1, yi0, zi0], [x1, yi1, zi0], [x1, yi1, zi1], [x1, yi0, zi1], [1, 0, 0]);
    quad([x0, yi0, zi1], [x0, yi1, zi1], [x0, yi1, zi0], [x0, yi0, zi0], [-1, 0, 0]);
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

function fitReferenceInterior(root, glbId) {
    const meshes = [];
    root.traverse((child) => {
        if (child.isMesh) meshes.push(child);
    });
    const bounds = (mesh) => {
        if (!mesh.geometry.boundingBox) mesh.geometry.computeBoundingBox();
        return mesh.geometry.boundingBox;
    };
    const nameOf = (mesh) => String(mesh.name || '').toLowerCase();
    const dividers = meshes.filter((mesh) => /dikme/.test(nameOf(mesh)));
    const drawers = meshes.filter((mesh) => /cekmece/.test(nameOf(mesh)));
    const shelves = meshes.filter((mesh) => /raf/.test(nameOf(mesh)));
    const replaceBox = (mesh, box) => {
        const w = box.max.x - box.min.x;
        const h = box.max.y - box.min.y;
        const d = box.max.z - box.min.z;
        if (w < 0.004 || h < 0.004 || d < 0.004) return;
        const geometry = new THREE.BoxGeometry(w, h, d);
        geometry.translate((box.min.x + box.max.x) / 2, (box.min.y + box.max.y) / 2, (box.min.z + box.max.z) / 2);
        geometry.computeBoundingBox();
        mesh.geometry = geometry;
    };
    dividers.forEach((mesh) => {
        const box = bounds(mesh).clone();
        let y0 = box.min.y;
        let y1 = box.max.y;
        drawers.forEach((drawer) => {
            const other = bounds(drawer);
            const overlapX = other.max.x > box.min.x + 0.004 && other.min.x < box.max.x - 0.004;
            if (!overlapX || other.max.y <= y0 || other.min.y >= y1) return;
            y0 = Math.max(y0, other.max.y + 0.002);
        });
        shelves.forEach((shelf) => {
            const other = bounds(shelf);
            const crosses = other.min.x < box.min.x - 0.01 && other.max.x > box.max.x + 0.01;
            if (!crosses) return;
            const mid = (y0 + y1) / 2;
            if (other.max.y <= mid && other.max.y > y0) y0 = other.max.y;
            if (other.min.y >= mid && other.min.y < y1) y1 = other.min.y;
        });
        if (y1 - y0 < 0.08) return;
        if (Math.abs(y0 - box.min.y) < 0.001 && Math.abs(y1 - box.max.y) < 0.001) return;
        box.min.y = y0;
        box.max.y = y1;
        replaceBox(mesh, box);
    });
    meshes.filter((mesh) => /askilik|boru/.test(nameOf(mesh))).forEach((mesh) => {
        const box = bounds(mesh);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        let axis = 'x';
        let length = size.x;
        if (size.y > length) { axis = 'y'; length = size.y; }
        if (size.z > length) { axis = 'z'; length = size.z; }
        let a0 = axis === 'x' ? box.min.x : axis === 'y' ? box.min.y : box.min.z;
        let a1 = axis === 'x' ? box.max.x : axis === 'y' ? box.max.y : box.max.z;
        dividers.forEach((divider) => {
            const other = bounds(divider);
            const d0 = axis === 'x' ? other.min.x : axis === 'y' ? other.min.y : other.min.z;
            const d1 = axis === 'x' ? other.max.x : axis === 'y' ? other.max.y : other.max.z;
            if (d1 <= a0 + 0.002 || d0 >= a1 - 0.002) return;
            const keepLeft = (d0 - a0) >= (a1 - d1);
            if (keepLeft) a1 = d0 - 0.012;
            else a0 = d1 + 0.012;
        });
        length = a1 - a0;
        if (length < 0.12) return;
        const geometry = new THREE.CylinderGeometry(0.0125, 0.0125, length, 28);
        if (axis === 'x') geometry.rotateZ(Math.PI / 2);
        if (axis === 'z') geometry.rotateX(Math.PI / 2);
        geometry.translate(
            axis === 'x' ? (a0 + a1) / 2 : center.x,
            axis === 'y' ? (a0 + a1) / 2 : center.y,
            axis === 'z' ? (a0 + a1) / 2 : center.z
        );
        geometry.computeBoundingBox();
        mesh.geometry = geometry;
    });
    if (glbId !== 'y26_cift_01') return;
    const divider = dividers[0];
    const side = meshes.find((mesh) => /govde_sag/.test(nameOf(mesh)));
    const spanning = shelves.filter((mesh) => {
        const other = bounds(mesh);
        return divider && other.min.x < bounds(divider).min.x - 0.01 && other.max.x > bounds(divider).max.x + 0.01;
    }).sort((a, b) => bounds(a).min.y - bounds(b).min.y);
    if (!divider || !side || spanning.length < 2) return;
    const div = bounds(divider);
    const right = bounds(side);
    const lower = bounds(spanning[0]);
    const upper = bounds(spanning[spanning.length - 1]);
    const x0 = div.max.x + 0.001;
    const x1 = right.min.x - 0.001;
    const y0 = lower.max.y;
    const y1 = upper.min.y;
    const thick = 0.018;
    const count = 3;
    const clear = (y1 - y0 - count * thick) / (count + 1);
    if (x1 - x0 < 0.12 || clear < 0.28) return;
    for (let i = 0; i < count; i += 1) {
        const y = y0 + clear * (i + 1) + thick * i;
        const geometry = new THREE.BoxGeometry(x1 - x0, thick, lower.max.z - lower.min.z);
        geometry.translate((x0 + x1) / 2, y + thick / 2, (lower.min.z + lower.max.z) / 2);
        geometry.computeBoundingBox();
        const shelf = new THREE.Mesh(geometry);
        shelf.name = `RAF_SAG_${i + 1}`;
        shelf.castShadow = true;
        shelf.receiveShadow = true;
        root.add(shelf);
    }
}

function bakeGlb(template, mirror, widthMeters, glbId) {
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
    // Bozuk vm_v4 aktarımı: tek mesh + absürt bbox → stüdyoda taban saçılması.
    // Parametrik gövde beklenen kutuya yakın olmalı; değilse bake’i reddetme yerine
    // bbox’u katalog hedefine oturt (son çare).
    {
        const box = geometryBounds(clone);
        const size = box.getSize(new THREE.Vector3());
        const catalog = glbCatalog.find((entry) => entry.id === glbId);
        const tw = catalog?.width || widthMeters || 0.45;
        const th = catalog?.height || 2.5;
        const td = catalog?.depth || 0.6;
        if (size.y > 0.05 && size.x > 0.05 && size.z > 0.05
            && (size.y < th * 0.55 || size.z > td * 1.45 || size.y < size.z * 0.75)) {
            const sx = tw / size.x;
            const sy = th / size.y;
            const sz = td / size.z;
            rewritePositions(clone, (x, y, z, nx, ny, nz) => ({
                x: (x - box.min.x) * sx,
                y: (y - box.min.y) * sy,
                z: (z - box.min.z) * sz,
                nx,
                ny,
                nz,
            }));
        }
    }
    fitReferenceInterior(clone, glbId);
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
            const part = String(child.name || '').toLowerCase();
            if (/askilik|boru/.test(part)) return;
            const bevel = /raf|cekmece_on|_on_/.test(part) ? 0.0012 : 0.0008;
            const beveled = chamferBoxGeometry(child.geometry.boundingBox, bevel);
            if (beveled) child.geometry = beveled;
        } else if (!child.geometry.getAttribute('normal')) {
            const flat = child.geometry.index ? child.geometry.toNonIndexed() : child.geometry;
            flat.computeVertexNormals();
            child.geometry = flat;
        }
        assignPanelUv(child.geometry, glbId === 'y26_cift_01' ? { tile: 2, along: 'height' } : {});
        child.geometry.computeBoundingBox();
        child.geometry.computeBoundingSphere();
    });
    return clone;
}

function alignCornerReturn(model, targetMeters) {
    const meshes = [];
    model.traverse((child) => {
        if (!child.isMesh || !child.geometry) return;
        child.geometry.computeBoundingBox();
        const box = child.geometry.boundingBox;
        if (!box) return;
        meshes.push({
            mesh: child,
            box,
            size: box.getSize(new THREE.Vector3()),
            center: box.getCenter(new THREE.Vector3()),
        });
    });
    let full = 0;
    meshes.forEach((part) => {
        full = Math.max(full, part.box.max.z);
    });
    const stiles = meshes.filter((part) => part.size.x < 0.04 && part.size.y > 0.8 && part.size.z > 0.2);
    if (stiles.length < 2 || full < 0.2) return;
    stiles.sort((a, b) => a.box.max.z - b.box.max.z);
    const shallow = stiles[0];
    const deep = stiles[stiles.length - 1];
    const short = shallow.box.max.z;
    if (short > full * 0.8) return;
    const target = Math.min(Math.max(targetMeters, 0.3), full - 0.012);
    if (Math.abs(target - short) < 0.006) return;
    const split = (shallow.center.x + deep.center.x) / 2;
    const toward = shallow.center.x >= deep.center.x ? 1 : -1;
    meshes.forEach((part) => {
        if ((part.center.x - split) * toward < 0.01) return;
        if (part.box.max.z > short + 0.02 || part.box.max.z < short * 0.5) return;
        const gap = short - part.box.max.z;
        const newMax = target - gap;
        const minZ = part.box.min.z;
        const span = part.box.max.z - minZ;
        if (span < 0.001 || newMax <= minZ + 0.002) return;
        const scale = (newMax - minZ) / span;
        const position = part.mesh.geometry.attributes.position;
        for (let i = 0; i < position.count; i += 1) {
            const z = position.getZ(i);
            position.setZ(i, minZ + (z - minZ) * scale);
        }
        position.needsUpdate = true;
        part.mesh.geometry.computeVertexNormals();
        part.mesh.geometry.computeBoundingBox();
        part.mesh.geometry.computeBoundingSphere();
    });
    shallow.mesh.geometry.computeBoundingBox();
    window.__cornerFront = {
        shortCm: Math.round(short * 1000) / 10,
        targetCm: Math.round(target * 1000) / 10,
        frontCm: Math.round(shallow.mesh.geometry.boundingBox.max.z * 1000) / 10,
    };
}

function placeGlb(template, mod) {
    const side = mod.kind === 'corner' && mod.cornerSide === 'right' ? 'right' : 'left';
    const key = `${mod.glb}:${side}`;
    if (!bakedGlb.has(key)) bakedGlb.set(key, bakeGlb(template, side === 'right', mod.glbWidth || mod.width / 100, mod.glb));
    const wrap = new THREE.Group();
    const nativeW = (mod.glbWidth || mod.width / 100) * 100;
    const nativeH = mod.glbHeight || moduleHeight(mod);
    const sx = 100 * (mod.width / nativeW);
    const sy = 100 * (moduleHeight(mod) / nativeH);
    const returnCm = mod.returnDepth || mod.width;
    const sz = mod.kind === 'corner' ? 100 * (returnCm / nativeW) : 100;
    wrap.scale.set(sx, sy, sz);
    const model = bakedGlb.get(key).clone(true);
    model.traverse((child) => {
        if (child.isMesh && child.geometry) child.geometry = child.geometry.clone();
    });
    if (mod.kind === 'corner') alignCornerReturn(model, (Number(state.depth) || 60) / 100);
    paintGlb(model, mod);
    mountGlbDrawers(model, mod);
    roundGlbRails(model, mod);
    wrap.add(model);
    dressGlb(wrap, model, mod);
    addGlbLeds(wrap, model, mod);
    return wrap;
}

function roundGlbRails(model, mod) {
    const alum = alumFrameMaterial(aluminumOf(mod));
    const rails = [];
    model.traverse((child) => {
        if (!child.isMesh || !child.geometry) return;
        const name = String(child.name || '').toLowerCase();
        if (!/askilik|boru|rail/.test(name) || /burc|soket|raf/.test(name)) return;
        rails.push(child);
    });
    rails.forEach((mesh) => {
        if (!mesh.geometry.boundingBox) mesh.geometry.computeBoundingBox();
        const bounds = mesh.geometry.boundingBox;
        if (!bounds) return;
        const size = bounds.getSize(new THREE.Vector3());
        const center = bounds.getCenter(new THREE.Vector3());
        const length = Math.max(size.x, size.y, size.z);
        if (length < 0.08) return;
        mesh.visible = false;
        const alongZ = size.z >= size.x && size.z >= size.y;
        const tube = new THREE.Mesh(new THREE.CylinderGeometry(0.0125, 0.0125, length, 24), alum);
        tube.name = 'ASKILIK_BORU';
        tube.castShadow = true;
        tube.receiveShadow = true;
        if (alongZ) tube.rotateX(Math.PI / 2);
        else tube.rotateZ(Math.PI / 2);
        tube.position.copy(center).applyMatrix4(mesh.matrix);
        mesh.parent.add(tube);
    });
}

function dressGlb(wrap, model, mod) {
    dressGlbInterior(wrap, model, mod);
}

function dressGlbRemoved(wrap, model, mod) {
    if (mod?.glb !== 'y26_cift_01' || mod?.props === false || !propTemplates) return;
    const shelves = [];
    let rail = null;
    model.updateMatrixWorld(true);
    model.traverse((child) => {
        if (!child.isMesh || !child.geometry) return;
        const name = String(child.name || '').toLowerCase();
        if (!child.geometry.boundingBox) child.geometry.computeBoundingBox();
        const box = child.geometry.boundingBox;
        if (!box) return;
        const center = box.getCenter(new THREE.Vector3());
        const size = box.getSize(new THREE.Vector3());
        if (/raf_sag/.test(name)) shelves.push({ x: center.x, y: center.y, z: center.z, w: size.x, h: size.y, d: size.z });
        if (/^raf_1$|raf_1/.test(name) && !/sag/.test(name)) {
            shelves.base = { x: center.x + size.x * 0.28, y: center.y, z: center.z, w: size.x * 0.28, h: size.y, d: size.z };
        }
        if (/askilik/.test(name)) rail = { x: center.x, y: center.y, z: center.z, w: size.x };
    });
    shelves.sort((a, b) => a.y - b.y);
    if (rail && propTemplates.shirt) {
        const spots = window.__shirtOnly ? [0] : [-0.08];
        spots.forEach((offset) => {
            placeModel(wrap, propTemplates.shirt, {
                name: 'GOMLEK',
                x: rail.x + offset,
                y: rail.y + 0.012,
                z: rail.z,
                maxW: 0.56,
                maxH: 0.82,
                maxD: 0.16,
                anchor: 'top',
                yaw: 0.08,
            });
        });
    }
    if (window.__shirtOnly) return;
    if (rail && propTemplates.blazer) {
        placeModel(wrap, propTemplates.blazer, {
            x: rail.x + 0.16,
            y: rail.y + 0.012,
            z: rail.z + 0.02,
            maxW: 0.24,
            maxH: 0.78,
            maxD: 0.2,
            anchor: 'top',
            yaw: 0.2,
        });
    }
    if (shelves[0] && propTemplates.tee) {
        placeModel(wrap, propTemplates.tee, {
            x: shelves[0].x,
            y: shelves[0].y + shelves[0].h / 2,
            z: shelves[0].z,
            maxW: shelves[0].w * 0.72,
            maxH: 0.07,
            maxD: shelves[0].d * 0.55,
            anchor: 'bottom',
        });
    }
    if (shelves[1] && propTemplates.box) {
        placeModel(wrap, propTemplates.box, {
            x: shelves[1].x,
            y: shelves[1].y + shelves[1].h / 2,
            z: shelves[1].z,
            maxW: shelves[1].w * 0.62,
            maxH: 0.14,
            maxD: shelves[1].d * 0.45,
            anchor: 'bottom',
            yaw: 0.15,
        });
    }
    if (shelves[2] && propTemplates.bag) {
        placeModel(wrap, propTemplates.bag, {
            x: shelves[2].x,
            y: shelves[2].y + shelves[2].h / 2,
            z: shelves[2].z,
            maxW: shelves[2].w * 0.7,
            maxH: 0.22,
            maxD: shelves[2].d * 0.4,
            anchor: 'bottom',
        });
    }
    const shoeShelf = shelves.base;
    if (shoeShelf && propTemplates.shoes) {
        placeModel(wrap, propTemplates.shoes, {
            x: shoeShelf.x,
            y: shoeShelf.y + shoeShelf.h / 2,
            z: shoeShelf.z + shoeShelf.d * 0.08,
            maxW: 0.28,
            maxH: 0.11,
            maxD: 0.2,
            anchor: 'bottom',
            yaw: 0.35,
        });
    }
}

function dressGlbInterior(wrap, model, mod) {
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
    if (ledStyleOf(mod) === 'off') return;
    const shelves = [];
    const box = new THREE.Box3();
    let backFace = null;
    let leftFace = null;
    let rightFace = null;
    let sized = false;
    model.updateMatrixWorld(true);
    model.traverse((child) => {
        if (!child.isMesh || !child.geometry) return;
        const name = String(child.name || '').toLowerCase();
        if (!child.geometry.boundingBox) child.geometry.computeBoundingBox();
        const bounds = child.geometry.boundingBox;
        if (!bounds) return;
        if (/govde|arkalik|baza/.test(name) && !/bant|_alt/.test(name)) {
            box.union(bounds);
            sized = true;
        }
        if (/arkalik/.test(name) && !/bant/.test(name)) backFace = bounds.max.z;
        if (/govde_sol|left_outer/.test(name)) leftFace = bounds.max.x;
        if (/govde_sag|right_outer/.test(name)) rightFace = bounds.min.x;
        if (/raf|shelf/.test(name) && !/bant|_alt|arkalik/.test(name)) {
            const center = bounds.getCenter(new THREE.Vector3());
            const size = bounds.getSize(new THREE.Vector3());
            if (size.y < 0.05) shelves.push({ x: center.x, y: center.y, w: Math.max(size.x, size.z) });
        }
    });
    if (!sized || backFace == null) return;
    const host = new THREE.Group();
    host.name = 'dolap-led';
    const strip = ledMaterial(lightsOn === true);
    const channel = alumFrameMaterial(aluminumOf(mod));
    const rear = backFace + 0.008;
    const height = Math.max(0.4, box.max.y - box.min.y - 0.18);
    const y = (box.min.y + box.max.y) / 2;
    [[leftFace, 1], [rightFace, -1]].forEach(([face, inward]) => {
        if (face == null) return;
        addLedLine(
            host,
            { x: 0.008, y: height, z: 0.006 },
            { x: face + inward * 0.006, y, z: rear },
            { x: 0.005, y: height - 0.02, z: 0.005 },
            { x: face + inward * 0.012, y, z: rear + 0.008 },
            channel,
            strip
        );
    });
    shelves.forEach((shelf) => {
        const width = Math.max(0.08, shelf.w - 0.03);
        const lineY = shelf.y + 0.01;
        addLedLine(
            host,
            { x: width, y: 0.003, z: 0.004 },
            { x: shelf.x, y: lineY, z: rear },
            { x: width - 0.01, y: 0.0016, z: 0.0016 },
            { x: shelf.x, y: lineY, z: rear + 0.003 },
            channel,
            strip
        );
    });
    if (host.children.length) wrap.add(host);
}

function remapGeometryAxis(mesh, axis, oldMin, oldMax, newMin, newMax) {
    const span = oldMax - oldMin;
    if (span < 0.001) return;
    const geometry = mesh.geometry.clone();
    const position = geometry.attributes.position;
    const scale = (newMax - newMin) / span;
    const get = axis === 'x' ? 'getX' : 'getY';
    const set = axis === 'x' ? 'setX' : 'setY';
    for (let i = 0; i < position.count; i += 1) {
        position[set](i, newMin + (position[get](i) - oldMin) * scale);
    }
    position.needsUpdate = true;
    geometry.computeBoundingBox();
    geometry.computeBoundingSphere();
    mesh.geometry = geometry;
}

function shiftTreeY(root, dy) {
    root.traverse((child) => {
        if (!child.isMesh || !child.geometry?.attributes?.position) return;
        const geometry = child.geometry.clone();
        const position = geometry.attributes.position;
        for (let i = 0; i < position.count; i += 1) position.setY(i, position.getY(i) + dy);
        position.needsUpdate = true;
        geometry.computeBoundingBox();
        geometry.computeBoundingSphere();
        child.geometry = geometry;
    });
}

function packDrawerFronts(model, meshes) {
    const reveal = 0.002;
    const minFront = 0.14;
    const fronts = meshes.filter((mesh) => /_on_/.test(String(mesh.name || '').toLowerCase()));
    fronts.forEach((mesh) => {
        if (!mesh.geometry.boundingBox) mesh.geometry.computeBoundingBox();
    });
    const boards = [];
    const sides = [];
    model.traverse((child) => {
        if (!child.isMesh || !child.geometry) return;
        const name = String(child.name || '').toLowerCase();
        if (/bant|_alt/.test(name)) return;
        if (!child.geometry.boundingBox) child.geometry.computeBoundingBox();
        const box = child.geometry.boundingBox;
        if (!box) return;
        if (/govde_alt|govde_ust|raf/.test(name)) boards.push({ mesh: child, box });
        if (/govde_sol|govde_sag/.test(name)) sides.push(box);
    });
    const overlapsX = (box, other) => Math.min(box.max.x, other.max.x) - Math.max(box.min.x, other.min.x) > 0.04;
    const columns = [];
    fronts.forEach((mesh) => {
        const box = mesh.geometry.boundingBox;
        const column = columns.find((entry) => overlapsX(entry[0].geometry.boundingBox, box));
        if (column) column.push(mesh);
        else columns.push([mesh]);
    });
    columns.forEach((column) => {
        column.sort((a, b) => a.geometry.boundingBox.min.y - b.geometry.boundingBox.min.y);
        const first = column[0].geometry.boundingBox;
        const last = column[column.length - 1].geometry.boundingBox;
        const floor = boards.filter((entry) => entry.box.max.y <= first.min.y + 0.02 && overlapsX(entry.box, first))
            .sort((a, b) => b.box.max.y - a.box.max.y)[0];
        const above = boards.filter((entry) => entry.box.min.y >= (floor ? floor.box.max.y : 0) + 0.05 && overlapsX(entry.box, first))
            .sort((a, b) => a.box.min.y - b.box.min.y);
        const ceiling = above[0];
        const next = above[1];
        let start = floor ? floor.box.max.y + reveal : first.min.y;
        let end = ceiling ? ceiling.box.min.y - reveal : last.max.y;
        let slot = (end - start - reveal * (column.length - 1)) / column.length;
        if (ceiling && slot < minFront) {
            const current = Math.max(minFront, (last.max.y - first.min.y - reveal * (column.length - 1)) / column.length);
            slot = current;
            end = start + column.length * slot + reveal * (column.length - 1);
            const dy = (end + reveal) - ceiling.box.min.y;
            const height = ceiling.box.max.y - ceiling.box.min.y;
            const blocked = next && ceiling.box.min.y + dy + height > next.box.min.y - reveal;
            if (dy > 0 && !blocked) shiftTreeY(ceiling.mesh, dy);
        }
        if (slot < 0.08 || end <= start) return;
        const left = sides.filter((box) => box.max.x <= first.min.x + 0.015).sort((a, b) => b.max.x - a.max.x)[0];
        const right = sides.filter((box) => box.min.x >= first.max.x - 0.015).sort((a, b) => a.min.x - b.min.x)[0];
        column.forEach((front, index) => {
            const old = front.geometry.boundingBox.clone();
            const newMin = start + index * (slot + reveal);
            const newMax = newMin + slot;
            const suffix = String(front.name || '').match(/_(\d+)$/)?.[1];
            const applyY = (mesh) => {
                remapGeometryAxis(mesh, 'y', old.min.y, old.max.y, newMin, newMax);
                mesh.children.forEach((child) => {
                    if (child.isMesh) remapGeometryAxis(child, 'y', old.min.y, old.max.y, newMin, newMax);
                });
            };
            applyY(front);
            if (left && right) {
                const face = front.geometry.boundingBox;
                remapGeometryAxis(front, 'x', face.min.x, face.max.x, left.max.x + reveal, right.min.x - reveal);
            }
            if (!suffix) return;
            meshes.forEach((mesh) => {
                if (mesh === front) return;
                const meshSuffix = String(mesh.name || '').match(/_(\d+)$/)?.[1];
                if (meshSuffix !== suffix) return;
                if (!mesh.geometry.boundingBox) mesh.geometry.computeBoundingBox();
                applyY(mesh);
            });
        });
    });
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
    const shell = material('drawer-box', carcassOf(mod), mod.glb === 'y26_cift_01' ? { representative: true } : {}).clone();
    shell.roughness = Math.max(0.72, shell.roughness || 0.72);
    shell.metalness = 0;
    const midY = (y0 + y1) / 2;
    const midZ = (z0 + z1) / 2;
    const sideGeo = new THREE.BoxGeometry(sideT, height, depth);
    if (mod.glb === 'y26_cift_01') {
        assignPanelUv(sideGeo, { tile: 2, along: 'height' });
        if (shell.userData.tintUrl) tintMaterial(shell, shell.userData.tintUrl, 1);
    }
    const left = new THREE.Mesh(sideGeo, shell);
    left.name = 'CEKMECE_YAN_SOL';
    left.position.set(x0 + sideT / 2, midY, midZ);
    const right = new THREE.Mesh(sideGeo, shell);
    right.name = 'CEKMECE_YAN_SAG';
    right.position.set(x1 - sideT / 2, midY, midZ);
    const backGeo = new THREE.BoxGeometry(innerW, Math.max(0.03, height - bottomT), backT);
    const bottomGeo = new THREE.BoxGeometry(innerW, bottomT, Math.max(0.04, depth - backT));
    if (mod.glb === 'y26_cift_01') {
        assignPanelUv(backGeo, { tile: 2, along: 'height' });
        assignPanelUv(bottomGeo, { tile: 2, along: 'height' });
    }
    const back = new THREE.Mesh(backGeo, shell);
    back.name = 'CEKMECE_ARKA';
    back.position.set((x0 + x1) / 2, y0 + bottomT + (height - bottomT) / 2, z0 + backT / 2);
    const bottom = new THREE.Mesh(bottomGeo, shell);
    bottom.name = 'CEKMECE_TABAN';
    bottom.position.set((x0 + x1) / 2, y0 + bottomT / 2, z0 + backT + (depth - backT) / 2);
    const alum = aluminumOf(mod);
    const railMat = alumFrameMaterial(alum);
    const railLen = Math.max(0.08, depth - 0.04);
    const barT = 0.007;
    const barH = 0.004;
    const railGeo = new THREE.BoxGeometry(barT, barH, railLen);
    const railPair = (x, side) => {
        const lower = new THREE.Mesh(railGeo, railMat);
        lower.name = `CEKMECE_RAY_${side}`;
        lower.position.set(x, y0 + 0.006, midZ);
        const upper = new THREE.Mesh(railGeo, railMat);
        upper.name = `CEKMECE_RAY_${side}_UST`;
        upper.position.set(x, y0 + 0.014, midZ);
        return [lower, upper];
    };
    const rails = [
        ...railPair(x0 - barT / 2 - 0.001, 'SOL'),
        ...railPair(x1 + barT / 2 + 0.001, 'SAG'),
    ];
    [left, right, back, bottom, ...rails].forEach((mesh) => {
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        group.add(mesh);
    });
    kutu.removeFromParent();
}

function mountGlbDrawers(model, mod) {
    // Minimal: çekmece gruplarını kaydırılabilir yap; ekstra ray/kulp/hollow yok (taban saçılması).
    const meshes = [];
    model.traverse((child) => {
        if (child.isMesh && /cekmece/.test(String(child.name || '').toLocaleLowerCase('tr'))) meshes.push(child);
    });
    if (!meshes.length) return;
    // packDrawerFronts / hollow: tabanda saçılmış metal mesh üretiyordu — skip.
    const groups = new Map();
    meshes.forEach((mesh) => {
        const match = String(mesh.name || '').match(/_(\d+)$/);
        const key = match ? match[1] : mesh.uuid;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(mesh);
    });
    groups.forEach((parts) => {
        if (!parts.some((mesh) => /_on_/.test(String(mesh.name || '').toLowerCase()))) return;
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
        group.userData.slide = mod;
        group.userData.rest = 0.02;
        group.userData.travel = Math.min(0.42, Math.max(0.2, size.z * 0.7 || 0.3));
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
    const outer = mod.width || 90;
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

function addCornerGlbDoors(group, mod, preview) {
    const w = mod.width || 90;
    const d = mod.returnDepth || w;
    const h = moduleHeight(mod);
    const base = doorBaseClearance(group, mod);
    const doorH = h - base - T - 0.6;
    if (doorH < 20) return;
    const y = base + doorH / 2;
    const mirrored = mod.cornerSide === 'right';
    const style = mod.door === 'glass' ? 'glass' : 'solid';
    const leafW = Math.max(12, w / 2 - T);
    const leafD = Math.max(12, d / 2 - T);
    const face = Math.max(30, Number(state.depth) || 60);
    const along = new THREE.Group();
    along.position.set(mirrored ? leafW / 2 + T : w - leafW / 2, y, face + 0.15);
    addCornerLeaf(along, leafW, doorH, style, mod, mirrored ? 'right' : 'left');
    along.userData.kind = 'corner-door';
    along.userData.sign = 1;
    along.userData.mod = mod;
    group.add(along);
    const side = new THREE.Group();
    side.position.set(w / 2, y, d - leafD / 2);
    side.rotation.y = mirrored ? -Math.PI / 2 : Math.PI / 2;
    addCornerLeaf(side, leafD, doorH, style, mod, 'left');
    side.userData.kind = 'corner-door';
    side.userData.sign = mirrored ? -1 : 1;
    side.userData.mod = mod;
    group.add(side);
    if (!preview) doorPivots.push(along, side);
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
        const frame = alumFrameMaterial(alum);
        const bar = 2.6;
        panel(parent, w, bar, 1.7, 0, h / 2 - bar / 2, 0.72, frame);
        panel(parent, w, bar, 1.7, 0, -h / 2 + bar / 2, 0.72, frame);
        panel(parent, bar, h, 1.7, -w / 2 + bar / 2, 0, 0.72, frame);
        panel(parent, bar, h, 1.7, w / 2 - bar / 2, 0, 0.72, frame);
        const pane = panel(parent, Math.max(1, w - bar * 2), Math.max(1, h - bar * 2), 0.6, 0, 0, DOOR_T * 0.55, glassMaterial(glassOf(mod)));
        if (pane) pane.castShadow = false;
    } else {
        beveledBoard(parent, w - 0.35, h - 0.35, DOOR_T, 0, 0, DOOR_T / 2, material('door', doorOf(mod)));
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
        const cornerId = mod.kind === 'corner' ? cornerModelId(mod) : '';
        const cornerTemplate = cornerId ? glbTemplates.get(cornerId) : null;
        if (cornerId && !cornerTemplate && !glbFailed.has(cornerId) && !preview) {
            const item = glbCatalog.find((entry) => entry.id === cornerId);
            if (item) ensureGlb(item).then(() => rebuild()).catch(() => {});
        }
        if (cornerTemplate && mod.kind === 'corner') {
            const stand = {
                ...mod,
                glb: cornerId,
                kind: 'corner',
                cornerSide: mod.cornerSide === 'right' ? 'right' : 'left',
                glbWidth: 0.9,
                returnDepth: mod.width || 90,
            };
            group.add(placeGlb(cornerTemplate, stand));
            if (mod.door && mod.door !== 'none' && (preview || !doorsAreHidden(mod))) addCornerGlbDoors(group, stand, preview);
        } else if (mod.kind === 'corner') {
            group.add(buildCornerCabinet(mod, preview));
        } else {
            const template = glbTemplates.get(mod.glb);
            if (!template) {
                const box = footprint(mod);
                if (!glbFailed.has(mod.glb) && !preview) {
                    const item = glbCatalog.find((entry) => entry.id === mod.glb);
                    if (item) ensureGlb(item).then(() => rebuild()).catch(() => {});
                }
                const failed = glbFailed.has(mod.glb);
                const missing = new THREE.Mesh(
                    new THREE.BoxGeometry(box.w, box.h, box.d),
                    new THREE.MeshBasicMaterial({
                        color: failed ? '#a89984' : '#d2c6b4',
                        wireframe: !failed,
                        transparent: true,
                        opacity: failed ? 0.35 : 0.5,
                    })
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
    if (!preview && !window.__qaShot && (mod.id === selectedId || (allCabinetsSelected && mod.kind !== 'end'))) group.add(selectionFrame(mod));
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
    const spots = layout(state.modules);
    if (!spots) return;
    doorPivots.length = 0;
    slides.length = 0;
    bakedGlb.clear();
    clearMeasures();
    clearGroup(cabinetRoot);
    spots.forEach((spot) => {
        const group = buildModule(spot.mod, false);
        group.position.set(spot.x, 0, spot.z);
        group.rotation.y = spot.rot;
        cabinetRoot.add(group);
    });
    snapDoors();
    syncGuides(spots);
}

function shiftCabinets() {
    if (!cabinetRoot) return false;
    const spots = layout(state.modules);
    if (!spots) return false;
    const groups = new Map();
    for (const child of cabinetRoot.children) {
        const id = child.userData?.moduleId;
        if (!id || groups.has(id)) return false;
        groups.set(id, child);
    }
    if (groups.size !== spots.length) return false;
    for (const spot of spots) {
        const group = groups.get(spot.mod.id);
        if (!group) return false;
        group.position.set(spot.x, 0, spot.z);
        group.rotation.y = spot.rot;
    }
    syncGuides(spots);
    return true;
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

function applyFloorStyle() {
    if (!room.floor?.material) return;
    const style = FLOOR_STYLES.find((item) => item.id === (state.floorStyle || 'parquet')) || FLOOR_STYLES[0];
    room.floor.material.color.set(style.color);
    room.floor.material.roughness = style.roughness;
}

function paintQuickRail(mod) {
    const rail = el('quick-rail');
    if (!rail) return;
    rail.querySelectorAll('[data-qr-door]').forEach((btn) => {
        btn.classList.toggle('is-on', mod && btn.dataset.qrDoor === mod.door);
    });
    const woodHost = el('qr-wood-sw');
    const glassHost = el('qr-glass-sw');
    const alumHost = el('qr-alum-sw');
    if (woodHost && mod) {
        const picks = materials.filter((m) => m.group === 'melamin-kapli-panel').slice(0, 4);
        const fallback = [mod.carcassId, mod.doorColorId, state.carcassId, state.doorColorId].filter(Boolean);
        const woodIds = picks.length ? picks.map((item) => item.id) : [...new Set(fallback)].slice(0, 4);
        woodHost.innerHTML = woodIds.map((id) => {
            const item = picks.find((row) => row.id === id) || materials.find((row) => row.id === id);
            const title = item?.name || item?.code || id;
            return `<button type="button" data-qr-wood="${id}" title="${title}"></button>`;
        }).join('');
        woodHost.style.display = woodIds.length ? 'flex' : 'none';
        woodHost.querySelectorAll('[data-qr-wood]').forEach((btn) => {
            const item = materials.find((row) => row.id === btn.dataset.qrWood);
            if (item?.image) btn.style.background = `url(${item.image}) center/cover`;
            else btn.style.background = '#c4a574';
        });
    }
    if (glassHost) {
        glassHost.innerHTML = mod && mod.door !== 'none'
            ? GLASS.slice(0, 4).map((item) => `<button type="button" data-qr-glass="${item.id}" title="${item.name}" style="background:${item.color}"></button>`).join('')
            : '';
        glassHost.style.display = mod?.door === 'glass' || mod?.door === 'mixed' ? 'flex' : 'none';
    }
    if (alumHost) {
        alumHost.innerHTML = mod && mod.door !== 'none'
            ? ALUMINUM.slice(0, 4).map((item) => `<button type="button" data-qr-alum="${item.id}" title="${item.name}" style="background:${item.color}"></button>`).join('')
            : '';
        alumHost.style.display = mod?.door !== 'none' ? 'flex' : 'none';
    }
    rail.querySelectorAll('[data-qr-door]').forEach((b) => { b.style.display = mod?.kind === 'end' ? 'none' : ''; });
}

function syncSelectionBar(mod) {
    const bar = el('selection-bar');
    if (!bar) return;
    const show = Boolean(mod) && planMode !== '2d';
    bar.classList.toggle('hidden', !show);
    const name = el('selection-bar-name');
    if (name) name.textContent = mod ? String(mod.name || 'Dolap') : '';
}

function openInspectorForSelection() {
    const mod = selected();
    if (!mod) return;
    sceneFocus = 'module';
    customFor = mod.id;
    el('inspector')?.classList.add('is-open');
    document.body.classList.add('show-custom');
    if (isPhone()) {
        document.body.classList.remove('show-library');
        el('library')?.classList.remove('is-open');
        el('project-sheet')?.classList.remove('is-open');
        // material/doors filtreleri sekmeleri bozuyordu — serbest inspector.
        document.body.dataset.sheet = 'inspector';
    }
}

function handleModuleQuickAction(event, scope) {
    const mod = selected();
    if (!mod) return;
    const root = scope || document;
    const doorBtn = event.target.closest('[data-qr-door], [data-sb-door]');
    if (doorBtn) {
        const type = doorBtn.dataset.qrDoor || doorBtn.dataset.sbDoor;
        commit(() => { mod.door = type; mod.open = 0; });
        toast(type === 'glass' ? 'Cam kapak' : type === 'none' ? 'Kapaksız' : 'Ahşap kapak');
        return;
    }
    const woodBtn = event.target.closest('[data-qr-wood]');
    if (woodBtn) {
        const id = woodBtn.dataset.qrWood;
        commit(() => {
            mod.doorColorId = id;
            mod.carcassId = id;
            paintColor(mod, 'doorColorId', id);
        });
        return;
    }
    const glassBtn = event.target.closest('[data-qr-glass]');
    if (glassBtn) {
        applyModuleGlass(mod, glassBtn.dataset.qrGlass);
        return;
    }
    const alumBtn = event.target.closest('[data-qr-alum]');
    if (alumBtn) {
        applyModuleAluminum(mod, alumBtn.dataset.qrAlum);
        return;
    }
    const action = event.target.closest('[data-qr]')?.dataset.qr
        || event.target.closest('[data-sb]')?.dataset.sb;
    if (action === 'delete') deleteSelected();
    if (action === 'clone') cloneSelected();
    if (action === 'open') commit(() => { mod.open = mod.open > 0.5 ? 0 : 1; });
    if (action === 'panel') {
        openInspectorForSelection();
        if (!INSPECTOR_TABS.has(inspectorActiveTab)) inspectorActiveTab = 'kapak';
        renderInspector();
    }
}

function updateHud() {
    const handle = el('height-handle');
    const rail = el('quick-rail');
    const mod = selected();
    const group = mod ? cabinetRoot.getObjectByName(`mod-${mod.id}`) : null;
    syncSelectionBar(mod);
    // Yan şişman quick-rail kapalı — Ahşap/Cam/swatch kalabalığı yok; alttaki ince çubuk yeter.
    rail?.classList.add('hidden');
    if (!group) {
        handle?.classList.add('hidden');
        return;
    }
    const box = new THREE.Box3().setFromObject(group);
    const center = new THREE.Vector3();
    box.getCenter(center);
    const top = project(new THREE.Vector3(center.x, box.max.y + 8, center.z));
    handle.classList.toggle('hidden', top.behind);
    if (!top.behind) placeHud(handle, top.x, top.y);
}

function orbitCamera(delta) {
    if (!camera || !controls) return;
    const offset = camera.position.clone().sub(controls.target);
    const radius = Math.max(120, Math.hypot(offset.x, offset.z));
    const angle = Math.atan2(offset.x, offset.z) + delta;
    camera.position.set(
        controls.target.x + Math.sin(angle) * radius,
        offset.y,
        controls.target.z + Math.cos(angle) * radius,
    );
    controls.update();
}

function bindQuickRail() {
    const rail = el('quick-rail');
    const bar = el('selection-bar');
    const bind = (node) => {
        if (!node || node.dataset.bound) return;
        node.dataset.bound = '1';
        node.addEventListener('click', (event) => {
            event.stopPropagation();
            handleModuleQuickAction(event, el('inspector-body'));
        });
        node.addEventListener('pointerdown', (event) => event.stopPropagation());
    };
    bind(rail);
    bind(bar);
}

function placeHud(node, x, y) {
    const stage = node.offsetParent || node.parentElement;
    if (!stage) return;
    node.style.left = `${x}px`;
    node.style.top = `${y}px`;
    const bounds = stage.getBoundingClientRect();
    const box = node.getBoundingClientRect();
    let limit = bounds.bottom - 8;
    [el('view-bar'), el('stage-toolbar')].forEach((bar) => {
        if (!bar || getComputedStyle(bar).display === 'none') return;
        limit = Math.min(limit, bar.getBoundingClientRect().top - 8);
    });
    if (box.bottom > limit) y -= box.bottom - limit;
    if (box.top < bounds.top + 8) y += bounds.top + 8 - box.top;
    if (box.left < bounds.left + 8) x += bounds.left + 8 - box.left;
    if (box.right > bounds.right - 8) x -= box.right - (bounds.right - 8);
    node.style.left = `${x}px`;
    node.style.top = `${y}px`;
}

function frameCamera() {
    if (planMode === '2d') framePlan();
    else setCameraView('perspective');
}

function focusSelectedModule() {
    if (planMode === '2d') {
        framePlan();
        return;
    }
    const mod = selected();
    const group = mod ? cabinetRoot?.getObjectByName(`mod-${mod.id}`) : null;
    if (!group || !camera || !controls) {
        setCameraView('perspective');
        return;
    }
    group.updateWorldMatrix(true, true);
    const box = new THREE.Box3().setFromObject(group);
    if (box.isEmpty()) {
        setCameraView('perspective');
        return;
    }
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3());
    // Tam gövde + oda payı: yükseklik FOV’una göre mesafe (QA-UX-002 — panel içine zoom yok).
    const fov = 34;
    camera.fov = fov;
    const half = THREE.MathUtils.degToRad(fov * 0.5);
    const fitH = Math.max(size.y, Number(mod.height) || 250, 180) * 1.22;
    const fitW = Math.max(size.x, size.z, alongSize(mod), 90) * 1.45;
    const vp = el('viewport');
    const aspect = Math.max(0.75, camera.aspect || ((vp?.clientWidth || 600) / Math.max(vp?.clientHeight || 400, 1)));
    const dist = Math.max(
        (fitH * 0.5) / Math.tan(half),
        (fitW * 0.5) / Math.tan(half) / aspect,
        280,
    );
    const wall = wallLimit();
    const openRight = wall - (center.x + size.x * 0.5);
    const openLeft = center.x - size.x * 0.5;
    const yawSign = openRight >= openLeft ? 1 : -1;
    const nearCorner = openRight < 55 || openLeft < 55;
    const yaw = yawSign * (nearCorner ? 0.18 : 0.4);
    const aimY = clamp(center.y, roomHeightLimit() * 0.28, roomHeightLimit() * 0.48);
    controls.target.set(center.x, aimY, Math.max(center.z, box.max.z - size.z * 0.35));
    // Oda içinde kal: yan duvarın dışına taşan kadraj duvar yüzeyi gösterir (QA-UX-002).
    let x = center.x + Math.sin(yaw) * dist;
    x = clamp(x, 28, Math.max(40, wall - 28));
    const dx = x - center.x;
    const forward = Math.sqrt(Math.max(dist * dist - dx * dx, (Math.max(size.z, 60) + 140) ** 2));
    const z = controls.target.z + forward;
    camera.position.set(
        x,
        clamp(aimY + dist * 0.06, 120, roomHeightLimit() * 0.72),
        z,
    );
    camera.near = 1;
    camera.far = Math.max(5000, dist * 6);
    camera.updateProjectionMatrix();
    controls.minPolarAngle = 0.08;
    controls.maxPolarAngle = Math.PI / 2 - 0.02;
    controls.update();
    window.__camDebug = {
        size: [Math.round(size.x), Math.round(size.y), Math.round(size.z)],
        dist: Math.round(dist),
        forward: Math.round(forward),
        open: [Math.round(openLeft), Math.round(openRight)],
        cam: camera.position.toArray().map((n) => Math.round(n)),
        aim: controls.target.toArray().map((n) => Math.round(n)),
    };
    resize();
}

function focusPart(pattern) {
    const meshes = [];
    cabinetRoot?.traverse((obj) => {
        if (!obj.isMesh) return;
        const name = String(obj.name || '').toLowerCase();
        if (!pattern.test(name) || /bant|_alt|led|kulp|ray/.test(name)) return;
        meshes.push(obj);
    });
    if (!meshes.length) return null;
    meshes.sort((a, b) => a.getWorldPosition(new THREE.Vector3()).y - b.getWorldPosition(new THREE.Vector3()).y);
    return new THREE.Box3().setFromObject(meshes[Math.floor(meshes.length / 2)]);
}

function clearReflectCard() {
    const old = scene?.getObjectByName('STUDIO_YANSIMA');
    if (!old) return;
    scene.remove(old);
    old.geometry?.dispose();
    old.material?.dispose();
}

function placeReflectCard(center, frontZ) {
    clearReflectCard();
    const card = new THREE.Mesh(
        new THREE.PlaneGeometry(70, 42),
        new THREE.MeshStandardMaterial({
            color: '#f7f4ef',
            emissive: '#000000',
            emissiveIntensity: 0,
            roughness: 0.86,
            metalness: 0,
        })
    );
    card.name = 'STUDIO_YANSIMA';
    card.position.set(center.x - 32, center.y + 8, frontZ + 24);
    card.lookAt(center.x, center.y, center.z);
    scene.add(card);
}

function setCameraView(mode) {
    if (!camera || !controls) return;
    if (mode === 'walkthrough') {
        setWalkMode(true);
        return;
    }
    if (walkMode) setWalkMode(false);
    clearReflectCard();
    if (mode === 'shelf' || mode === 'drawer' || mode === 'grain' || mode === 'glass' || mode === 'shirt' || mode === 'shirt-side') {
        const part = mode === 'shelf'
            ? (focusPart(/raf_sag/) || focusPart(/raf/))
            : mode === 'shirt' || mode === 'shirt-side'
                ? focusPart(/gomlek/)
                : focusPart(mode === 'drawer' ? /cekmece_on|_on_/ : mode === 'glass' ? /^cam$/ : /kapak/);
        if (part) {
            const center = part.getCenter(new THREE.Vector3());
            const size = part.getSize(new THREE.Vector3());
            if (mode === 'glass') {
                const root = new THREE.Box3().setFromObject(cabinetRoot);
                const rootCenter = root.getCenter(new THREE.Vector3());
                const rootSize = root.getSize(new THREE.Vector3());
                const fov = 32;
                camera.fov = fov;
                controls.target.set(rootCenter.x + rootSize.x * 0.06, rootCenter.y, root.max.z - 0.4);
                camera.position.set(rootCenter.x - rootSize.x * 0.2, rootCenter.y + 8, root.max.z + 52);
                placeReflectCard(rootCenter, root.max.z);
                window.__camDebug = {
                    cam: camera.position.toArray(),
                    aim: controls.target.toArray(),
                    root: [root.min.toArray(), root.max.toArray()],
                    part: center.toArray(),
                };
            } else if (mode === 'drawer') {
                const root = new THREE.Box3().setFromObject(cabinetRoot);
                const aim = part.getCenter(new THREE.Vector3());
                camera.fov = 26;
                controls.target.set(aim.x, aim.y, aim.z - 8);
                camera.position.set(Math.max(root.min.x - 6, aim.x - 42), aim.y + 18, root.max.z + 22);
                cabinetRoot.traverse((obj) => {
                    if (obj.material === selectionEdgeMat || obj.material === selectionWashMat) obj.visible = false;
                });
            } else if (mode === 'shirt' || mode === 'shirt-side') {
                camera.fov = 26;
                controls.target.copy(center);
                const side = mode === 'shirt-side' ? size.x * 1.15 + 18 : size.x * 0.35;
                camera.position.set(center.x + side, center.y + size.y * 0.08, center.z + Math.max(36, size.z + 28));
            } else if (mode === 'shelf') {
                camera.fov = 26;
                controls.target.copy(center);
                camera.position.set(center.x + size.x * 0.12, center.y - 8, center.z + 26);
            } else {
                camera.fov = 20;
                controls.target.copy(center);
                camera.position.set(center.x, center.y, center.z + 18);
            }
            camera.near = 0.4;
            camera.far = 4000;
            camera.updateProjectionMatrix();
            controls.update();
            aimCabinetShadow(center, size);
            return;
        }
    }
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
    if (mode === 'perspective') {
        const room = openRight;
        yaw = (room > 70 ? 1 : (openRight >= openLeft ? 1 : -1)) * Math.abs(yaw);
    }
    const margin = mode === 'left45' || mode === 'right45' ? 6 : 28;
    let x = center.x + Math.sin(yaw) * dist * (mode === 'top' ? 0 : 1);
    x = Math.min(wall - margin, Math.max(margin, x));
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
    updateWalk(dt);
    if (!walkMode) controls.update();
    updateHud();
    updateMeasures();
    renderer.render(scene, camera);
}

function openPhoneSheet(name) {
    const phone = isPhone();
    if (!phone) {
        if (name === 'modules') showModuleLibrary();
        else {
            document.body.classList.add('show-custom');
            el('inspector')?.classList.add('is-open');
        }
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
    if (camera?.isOrthographicCamera) {
        const aspect = width / Math.max(1, height);
        const halfH = Math.max(Math.abs(camera.top), 80);
        camera.left = -halfH * aspect;
        camera.right = halfH * aspect;
    } else if (camera) {
        camera.aspect = width / Math.max(1, height);
    }
    camera?.updateProjectionMatrix();
    renderer?.setSize(width, height, true);
    if (planMode === '2d') drawPlan();
}

let planTool = 'move';
let planPointer = null;

function ensureSketch() {
    if (!state.roomSketch) state.roomSketch = { walls: [], openings: [] };
    if (!Array.isArray(state.roomSketch.openings)) state.roomSketch.openings = [];
    if (!Array.isArray(state.roomSketch.walls)) state.roomSketch.walls = [];
    return state.roomSketch;
}

function planChrome() {
    const canvas = el('plan-canvas');
    const rect = canvas?.getBoundingClientRect();
    let top = 56;
    let bottom = 36;
    if (!rect || rect.height < 1) return { top, bottom };
    [el('plan-tools'), el('plan-switch')].forEach((node) => {
        if (!node || getComputedStyle(node).display === 'none') return;
        const box = node.getBoundingClientRect();
        const overlap = Math.min(box.right, rect.right) - Math.max(box.left, rect.left);
        if (overlap < 8) return;
        const mid = (box.top + box.bottom) / 2;
        if (mid < rect.top + rect.height * 0.5) top = Math.max(top, box.bottom - rect.top + 58);
        else bottom = Math.max(bottom, rect.bottom - box.top + 18);
    });
    return { top, bottom };
}

function planMetrics() {
    const canvas = el('plan-canvas');
    const left = sideLimit('left');
    const front = wallLimit();
    const right = sideLimit('right');
    const chrome = planChrome();
    const padX = 96;
    const width = canvas?.clientWidth || 600;
    const height = canvas?.clientHeight || 400;
    const span = Math.max(left, right, 80);
    const scale = Math.min((width - padX * 2) / front, (height - chrome.top - chrome.bottom) / span);
    const x0 = (width - front * scale) / 2;
    const z0 = chrome.top;
    return {
        scale, x0, z0, width, height,
        left: { id: 'left', x1: x0, z1: z0, x2: x0, z2: z0 + left * scale, length: left },
        front: { id: 'front', x1: x0, z1: z0, x2: x0 + front * scale, z2: z0, length: front },
        right: { id: 'right', x1: x0 + front * scale, z1: z0, x2: x0 + front * scale, z2: z0 + right * scale, length: right },
    };
}

function planPoint(event) {
    const canvas = el('plan-canvas');
    const rect = canvas.getBoundingClientRect();
    return {
        x: (event.clientX - rect.left) * (canvas.clientWidth / rect.width),
        z: (event.clientY - rect.top) * (canvas.clientHeight / rect.height),
    };
}

function modulePlanRect(mod, room) {
    const along = alongSize(mod);
    const depth = mod.kind === 'corner' ? along : Math.max(30, Number(mod.glbDepth) || Number(state.depth) || 60);
    const nudge = depthNudge(mod);
    const at = Math.max(0, Number(mod.offset) || 0);
    if (mod.kind === 'corner') {
        const x = mod.cornerSide === 'left' ? 0 : wallLimit() - along;
        return { x: room.x0 + x * room.scale, z: room.z0 + nudge * room.scale, w: along * room.scale, d: along * room.scale };
    }
    const wall = wallName(mod);
    if (wall === 'left') return { x: room.x0 + nudge * room.scale, z: room.z0 + at * room.scale, w: depth * room.scale, d: along * room.scale };
    if (wall === 'right') return { x: room.x0 + (wallLimit() - depth - nudge) * room.scale, z: room.z0 + at * room.scale, w: depth * room.scale, d: along * room.scale };
    return { x: room.x0 + at * room.scale, z: room.z0 + nudge * room.scale, w: along * room.scale, d: depth * room.scale };
}

function planWrap(ctx, text, width, lines) {
    const words = String(text || 'Dolap').split(/\s+/).filter(Boolean);
    const out = [];
    let line = '';
    words.forEach((word) => {
        const next = line ? `${line} ${word}` : word;
        if (ctx.measureText(next).width <= width) line = next;
        else {
            if (line) out.push(line);
            line = ctx.measureText(word).width <= width ? word : planEllipsis(ctx, word, width);
        }
    });
    if (line) out.push(line);
    if (out.length > lines) {
        out.length = lines;
        out[lines - 1] = planEllipsis(ctx, out[lines - 1], width);
    }
    return out;
}

function planEllipsis(ctx, text, width) {
    const value = String(text || '');
    if (width < 8) return '';
    if (ctx.measureText(value).width <= width) return value;
    let cut = value;
    while (cut.length > 1 && ctx.measureText(`${cut}…`).width > width) cut = cut.slice(0, -1);
    return `${cut}…`;
}

function planDimension(ctx, x1, z1, x2, z2, label) {
    const dx = x2 - x1;
    const dz = z2 - z1;
    const len = Math.hypot(dx, dz) || 1;
    const px = (-dz / len) * 4.5;
    const pz = (dx / len) * 4.5;
    ctx.save();
    ctx.strokeStyle = '#8d877e';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(x1, z1);
    ctx.lineTo(x2, z2);
    ctx.moveTo(x1 - px, z1 - pz);
    ctx.lineTo(x1 + px, z1 + pz);
    ctx.moveTo(x2 - px, z2 - pz);
    ctx.lineTo(x2 + px, z2 + pz);
    ctx.stroke();
    ctx.font = '11px Montserrat, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    const midX = (x1 + x2) / 2;
    const midZ = (z1 + z2) / 2;
    const wide = ctx.measureText(label).width + 10;
    ctx.translate(midX, midZ);
    if (Math.abs(dz) > Math.abs(dx)) ctx.rotate(-Math.PI / 2);
    ctx.fillStyle = '#f3f1ec';
    ctx.fillRect(-wide / 2, -7, wide, 14);
    ctx.fillStyle = '#5e5953';
    ctx.fillText(label, 0, 0);
    ctx.restore();
}

function drawPlanModule(ctx, mod, room) {
    const rect = modulePlanRect(mod, room);
    const x = rect.x;
    const z = rect.z;
    const w = Math.max(2, rect.w);
    const d = Math.max(2, rect.d);
    const selected = mod.id === selectedId;
    const wall = wallName(mod);
    const corner = mod.kind === 'corner';
    const face = corner
        ? Math.min(d - 1, Math.max(8, ((Math.max(30, Number(state.depth) || 60) / Math.max(1, alongSize(mod))) * d)))
        : d;
    const mid = w / 2;
    ctx.fillStyle = selected ? 'rgba(31,107,74,0.13)' : '#fbfaf6';
    if (corner) {
        ctx.beginPath();
        if (mod.cornerSide !== 'right') {
            ctx.moveTo(x, z);
            ctx.lineTo(x + w, z);
            ctx.lineTo(x + w, z + face);
            ctx.lineTo(x + mid, z + face);
            ctx.lineTo(x + mid, z + d);
            ctx.lineTo(x, z + d);
        } else {
            ctx.moveTo(x, z);
            ctx.lineTo(x + w, z);
            ctx.lineTo(x + w, z + d);
            ctx.lineTo(x + mid, z + d);
            ctx.lineTo(x + mid, z + face);
            ctx.lineTo(x, z + face);
        }
        ctx.closePath();
        ctx.fill();
        ctx.strokeStyle = selected ? '#1f6b4a' : '#3a3834';
        ctx.lineWidth = selected ? 1.6 : 1;
        ctx.stroke();
    } else {
        ctx.fillRect(x, z, w, d);
        ctx.strokeStyle = selected ? '#1f6b4a' : '#3a3834';
        ctx.lineWidth = selected ? 1.6 : 1;
        ctx.strokeRect(x + 0.5, z + 0.5, Math.max(0, w - 1), Math.max(0, d - 1));
    }
    const inset = Math.min(5, w * 0.14, d * 0.14);
    if (!corner && w > 22 && d > 22) {
        ctx.strokeStyle = selected ? 'rgba(31,107,74,0.28)' : 'rgba(58,56,52,0.16)';
        ctx.lineWidth = 1;
        ctx.strokeRect(x + inset, z + inset, w - inset * 2, d - inset * 2);
    }
    ctx.strokeStyle = selected ? '#1f6b4a' : '#242220';
    ctx.lineWidth = 2.25;
    ctx.beginPath();
    if (corner) {
        if (mod.cornerSide !== 'right') {
            ctx.moveTo(x + mid, z + face);
            ctx.lineTo(x + w, z + face);
            ctx.moveTo(x, z + d);
            ctx.lineTo(x + mid, z + d);
        } else {
            ctx.moveTo(x, z + face);
            ctx.lineTo(x + mid, z + face);
            ctx.moveTo(x + mid, z + d);
            ctx.lineTo(x + w, z + d);
        }
    } else if (wall === 'left') {
        ctx.moveTo(x + w, z);
        ctx.lineTo(x + w, z + d);
    } else if (wall === 'right') {
        ctx.moveTo(x, z);
        ctx.lineTo(x, z + d);
    } else {
        ctx.moveTo(x, z + d);
        ctx.lineTo(x + w, z + d);
    }
    ctx.stroke();
    ctx.save();
    ctx.beginPath();
    ctx.rect(x + 5, z + 4, Math.max(0, w - 10), Math.max(0, d - 8));
    ctx.clip();
    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    const along = Math.round(alongSize(mod));
    const depth = Math.round(mod.kind === 'corner' ? along : (Number(mod.glbDepth) || Number(state.depth) || 60));
    if (d >= 34 && w >= 36) {
        ctx.fillStyle = selected ? '#1f6b4a' : '#2c2a28';
        ctx.font = '600 11px Montserrat, sans-serif';
        const roomForName = d >= 48 ? 2 : 1;
        planWrap(ctx, mod.name || 'Dolap', w - 14, roomForName).forEach((line, index) => {
            ctx.fillText(line, x + 7, z + 6 + index * 13);
        });
        if (d >= 48) {
            ctx.fillStyle = selected ? '#1f6b4a' : '#6f6a63';
            ctx.font = '10px Montserrat, sans-serif';
            ctx.fillText(`${along} × ${depth}`, x + 7, z + 6 + roomForName * 13);
        }
    } else if (Math.max(w, d) > 24) {
        ctx.fillStyle = selected ? '#1f6b4a' : '#3a3834';
        ctx.font = '10px Montserrat, sans-serif';
        ctx.fillText(planEllipsis(ctx, String(along), Math.max(12, Math.min(w, d) - 8)), x + 4, z + 4);
    }
    ctx.restore();
}

function drawPlan() {
    const canvas = el('plan-canvas');
    if (!canvas || planMode !== '2d') return;
    const room = planMetrics();
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.max(1, Math.round(room.width * ratio));
    canvas.height = Math.max(1, Math.round(room.height * ratio));
    const ctx = canvas.getContext('2d');
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.clearRect(0, 0, room.width, room.height);
    ctx.fillStyle = '#f3f1ec';
    ctx.fillRect(0, 0, room.width, room.height);
    const x0 = room.left.x1;
    const z0 = room.front.z1;
    const x1 = room.right.x1;
    const zL = room.left.z2;
    const zR = room.right.z2;
    const span = Math.max(zL, zR);
    const step = 50 * room.scale;
    if (step >= 16) {
        ctx.fillStyle = 'rgba(42,40,38,0.045)';
        for (let x = x0; x <= x1 + 0.5; x += step) {
            for (let z = z0; z <= span + 0.5; z += step) ctx.fillRect(x, z, 1.25, 1.25);
        }
    }
    const thickness = 13;
    const openings = ensureSketch().openings;
    ctx.beginPath();
    ctx.moveTo(x0 - thickness, z0 - thickness);
    ctx.lineTo(x1 + thickness, z0 - thickness);
    ctx.lineTo(x1 + thickness, zR);
    ctx.lineTo(x1, zR);
    ctx.lineTo(x1, z0);
    ctx.lineTo(x0, z0);
    ctx.lineTo(x0, zL);
    ctx.lineTo(x0 - thickness, zL);
    ctx.closePath();
    openings.forEach((item) => {
        const wall = room[item.wallId];
        if (!wall) return;
        const at = Math.max(0, Number(item.offset) || 0) * room.scale;
        const width = Math.max(20, Number(item.width) || 0) * room.scale;
        if (item.wallId === 'front') ctx.rect(x0 + at, z0 - thickness, width, thickness);
        else if (item.wallId === 'left') ctx.rect(x0 - thickness, z0 + at, thickness, width);
        else ctx.rect(x1, z0 + at, thickness, width);
    });
    ctx.fillStyle = '#e5e0d7';
    ctx.fill('evenodd');
    ctx.strokeStyle = '#2c2a27';
    ctx.lineWidth = 1;
    ctx.lineJoin = 'miter';
    ctx.stroke();
    openings.forEach((item) => {
        const wall = room[item.wallId];
        if (!wall || item.kind !== 'window') return;
        const at = Math.max(0, Number(item.offset) || 0) * room.scale;
        const width = Math.max(20, Number(item.width) || 0) * room.scale;
        ctx.strokeStyle = '#6d8ea8';
        ctx.lineWidth = 1;
        ctx.beginPath();
        if (item.wallId === 'front') {
            ctx.moveTo(x0 + at, z0 - thickness * 0.35);
            ctx.lineTo(x0 + at + width, z0 - thickness * 0.35);
            ctx.moveTo(x0 + at, z0 - thickness * 0.7);
            ctx.lineTo(x0 + at + width, z0 - thickness * 0.7);
        } else {
            const x = item.wallId === 'left' ? x0 - thickness * 0.35 : x1 + thickness * 0.35;
            const x2 = item.wallId === 'left' ? x0 - thickness * 0.7 : x1 + thickness * 0.7;
            ctx.moveTo(x, z0 + at);
            ctx.lineTo(x, z0 + at + width);
            ctx.moveTo(x2, z0 + at);
            ctx.lineTo(x2, z0 + at + width);
        }
        ctx.stroke();
    });
    state.modules.forEach((mod) => drawPlanModule(ctx, mod, room));
    openings.forEach((item) => {
        if (item.kind !== 'door') return;
        const wall = room[item.wallId];
        if (!wall) return;
        const at = Math.max(0, Number(item.offset) || 0) * room.scale;
        const width = Math.max(20, Number(item.width) || 0) * room.scale;
        ctx.strokeStyle = '#3c463f';
        ctx.lineWidth = 1;
        ctx.beginPath();
        if (item.wallId === 'front') {
            ctx.moveTo(x0 + at, z0);
            ctx.lineTo(x0 + at, z0 + width);
            ctx.moveTo(x0 + at, z0);
            ctx.arc(x0 + at, z0, width, 0, Math.PI / 2, false);
        } else if (item.wallId === 'left') {
            ctx.moveTo(x0, z0 + at);
            ctx.lineTo(x0 + width, z0 + at);
            ctx.moveTo(x0, z0 + at);
            ctx.arc(x0, z0 + at, width, Math.PI / 2, 0, true);
        } else {
            ctx.moveTo(x1, z0 + at);
            ctx.lineTo(x1 - width, z0 + at);
            ctx.moveTo(x1, z0 + at);
            ctx.arc(x1, z0 + at, width, Math.PI / 2, Math.PI, false);
        }
        ctx.stroke();
    });
    const selectedOpening = ensureSketch().openings.find((item) => item.id === planOpeningId);
    if (selectedOpening) {
        ['start', 'end'].forEach((edge) => {
            const point = planOpeningPoint(selectedOpening, room, edge);
            if (!point) return;
            ctx.beginPath();
            ctx.fillStyle = '#fff';
            ctx.strokeStyle = '#1f6b4a';
            ctx.lineWidth = 2;
            ctx.arc(point.x, point.z, 7, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();
        });
    }
    const gap = 18;
    ctx.strokeStyle = '#b7b1a8';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(x0, z0 - thickness);
    ctx.lineTo(x0, z0 - thickness - gap);
    ctx.moveTo(x1, z0 - thickness);
    ctx.lineTo(x1, z0 - thickness - gap);
    ctx.moveTo(x0 - thickness, z0);
    ctx.lineTo(x0 - thickness - gap, z0);
    ctx.moveTo(x0 - thickness, zL);
    ctx.lineTo(x0 - thickness - gap, zL);
    ctx.moveTo(x1 + thickness, z0);
    ctx.lineTo(x1 + thickness + gap, z0);
    ctx.moveTo(x1 + thickness, zR);
    ctx.lineTo(x1 + thickness + gap, zR);
    ctx.stroke();
    planDimension(ctx, x0, z0 - thickness - gap, x1, z0 - thickness - gap, `${room.front.length} cm`);
    planDimension(ctx, x0 - thickness - gap, z0, x0 - thickness - gap, zL, `${room.left.length} cm`);
    planDimension(ctx, x1 + thickness + gap, z0, x1 + thickness + gap, zR, `${room.right.length} cm`);
    [
        [room.left.x2, room.left.z2],
        [room.front.x2, room.front.z2],
        [room.right.x2, room.right.z2],
    ].forEach(([x, z]) => {
        ctx.beginPath();
        ctx.fillStyle = '#fbfaf6';
        ctx.strokeStyle = '#1f6b4a';
        ctx.lineWidth = 1.5;
        ctx.arc(x, z, 5.5, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();
        ctx.beginPath();
        ctx.fillStyle = '#1f6b4a';
        ctx.arc(x, z, 2, 0, Math.PI * 2);
        ctx.fill();
    });
}

function planGrip(point, room) {
    const grips = [
        { id: 'left', x: room.left.x2, z: room.left.z2 },
        { id: 'front', x: room.front.x2, z: room.front.z2 },
        { id: 'right', x: room.right.x2, z: room.right.z2 },
    ];
    return grips.find((grip) => Math.hypot(point.x - grip.x, point.z - grip.z) < 16) || null;
}

function planWallPick(point, room, mod) {
    const along = alongSize(mod);
    const scale = room.scale || 1;
    const choices = [
        { wall: 'front', dist: Math.abs(point.z - room.front.z1), offset: (point.x - room.x0) / scale - along / 2 },
        { wall: 'left', dist: Math.abs(point.x - room.left.x1), offset: (point.z - room.z0) / scale - along / 2 },
        { wall: 'right', dist: Math.abs(point.x - room.right.x1), offset: (point.z - room.z0) / scale - along / 2 },
    ].sort((a, b) => a.dist - b.dist);
    const current = choices.find((item) => item.wall === wallName(mod)) || choices[0];
    return choices[0].wall !== current.wall && choices[0].dist + 18 < current.dist ? choices[0] : current;
}

function planWallHit(point, room) {
    let best = null;
    ['left', 'front', 'right'].forEach((id) => {
        const wall = room[id];
        const dx = wall.x2 - wall.x1;
        const dz = wall.z2 - wall.z1;
        const len2 = dx * dx + dz * dz || 1;
        let t = ((point.x - wall.x1) * dx + (point.z - wall.z1) * dz) / len2;
        t = Math.max(0, Math.min(1, t));
        const px = wall.x1 + dx * t;
        const pz = wall.z1 + dz * t;
        const dist = Math.hypot(point.x - px, point.z - pz);
        if (!best || dist < best.dist) best = { wall, t, dist };
    });
    return best && best.dist < 28 ? best : null;
}

function planModuleHit(point, room) {
    const hits = state.modules.map((mod) => ({ mod, rect: modulePlanRect(mod, room) })).reverse();
    return hits.find(({ rect }) => point.x >= rect.x && point.x <= rect.x + rect.w && point.z >= rect.z && point.z <= rect.z + rect.d)?.mod || null;
}

function paintPlan() {
    renderSummary();
    drawPlan();
}

function refreshPlan() {
    rebuild();
    fitRoom();
    renderSummary();
    drawPlan();
}

function planOpeningDefaults(kind) {
    const preset = state.roomSketch?.openingPreset;
    if (kind === 'window') {
        if (preset === 'window_wide') return { width: 180, height: 120, sill: 90, style: 'fixed' };
        return { width: 120, height: 120, sill: 90, style: 'fixed' };
    }
    if (preset === 'door_double') return { width: 150, height: 210, sill: 0, style: 'double' };
    if (preset === 'door_sliding') return { width: 180, height: 210, sill: 0, style: 'sliding' };
    return { width: 90, height: 210, sill: 0, style: 'single' };
}

function toggleOpening(wall, t) {
    const defs = planOpeningDefaults(planTool);
    const width = defs.width;
    const offset = Math.round(Math.max(0, Math.min(wall.length - width, t * wall.length - width / 2)));
    const sketch = ensureSketch();
    const index = sketch.openings.findIndex((item) => item.wallId === wall.id && Math.abs((Number(item.offset) || 0) - offset) < Math.max(width, Number(item.width) || 0) * 0.7);
    if (index >= 0) sketch.openings.splice(index, 1);
    else {
        sketch.openings.push({
            id: `o${Date.now()}`,
            wallId: wall.id,
            kind: planTool,
            offset,
            width,
            height: defs.height,
            sill: defs.sill,
            style: defs.style,
        });
    }
}

let planOpeningId = null;

function planOpeningPoint(item, room, edge) {
    const wall = room[item.wallId];
    if (!wall) return null;
    const along = (Number(item.offset) || 0) + (edge === 'end' ? Number(item.width) || 0 : edge === 'mid' ? (Number(item.width) || 0) / 2 : 0);
    const t = wall.length > 0 ? along / wall.length : 0;
    return {
        x: wall.x1 + (wall.x2 - wall.x1) * t,
        z: wall.z1 + (wall.z2 - wall.z1) * t,
    };
}

function planAlongCm(point, wall) {
    const dx = wall.x2 - wall.x1;
    const dz = wall.z2 - wall.z1;
    const len2 = dx * dx + dz * dz || 1;
    const t = Math.max(0, Math.min(1, ((point.x - wall.x1) * dx + (point.z - wall.z1) * dz) / len2));
    return t * wall.length;
}

function planOpeningHit(point, room) {
    let best = null;
    ensureSketch().openings.forEach((item) => {
        const start = planOpeningPoint(item, room, 'start');
        const end = planOpeningPoint(item, room, 'end');
        const mid = planOpeningPoint(item, room, 'mid');
        if (!start || !end || !mid) return;
        const startDist = Math.hypot(point.x - start.x, point.z - start.z);
        const endDist = Math.hypot(point.x - end.x, point.z - end.z);
        const midDist = Math.hypot(point.x - mid.x, point.z - mid.z);
        const edge = startDist <= 14 && startDist <= endDist ? 'start' : (endDist <= 14 ? 'end' : (midDist <= 18 ? 'move' : null));
        const dist = edge === 'start' ? startDist : (edge === 'end' ? endDist : midDist);
        if (edge && (!best || dist < best.dist)) best = { item, edge, dist };
    });
    return best;
}

function syncPlanOpeningEditor() {
    const form = el('plan-opening-size');
    const item = ensureSketch().openings.find((entry) => entry.id === planOpeningId);
    if (!form) return;
    form.classList.toggle('hidden', !item);
    if (!item) return;
    const width = el('plan-open-w');
    const height = el('plan-open-h');
    const sill = el('plan-open-sill');
    if (width && document.activeElement !== width) width.value = Math.round(Number(item.width) || 90);
    if (height && document.activeElement !== height) height.value = Math.round(Number(item.height) || (item.kind === 'window' ? 120 : 210));
    if (sill && document.activeElement !== sill) sill.value = Math.round(Number(item.sill) || 0);
    form.querySelector('[data-plan-sill]')?.toggleAttribute('hidden', item.kind !== 'window');
}

function patchPlanOpening(item, patch) {
    const room = planMetrics();
    const wall = room[item.wallId];
    const len = wall?.length || 800;
    if (patch.width != null || patch.offset != null) {
        const width = patch.width != null ? patch.width : item.width;
        const offset = patch.offset != null ? patch.offset : item.offset;
        const span = resizeOpeningSpan(len, offset, width, 'move', (Number(offset) || 0) + (Number(width) || 90) / 2);
        item.offset = span.offset;
        item.width = span.width;
    }
    if (patch.height != null) item.height = clamp(Math.round(patch.height), 40, 300);
    if (patch.sill != null) item.sill = clamp(Math.round(patch.sill), 0, 220);
    fitRoom();
    drawPlan();
    syncPlanOpeningEditor();
    scheduleSave();
}

function bindPlanBoard() {
    const canvas = el('plan-canvas');
    if (!canvas || canvas.dataset.bound) return;
    canvas.dataset.bound = '1';
    canvas.addEventListener('pointerdown', (event) => {
        const point = planPoint(event);
        const room = planMetrics();
        const grip = planGrip(point, room);
        if (grip) {
            snapshot();
            planPointer = { kind: 'grip', id: grip.id, x: point.x, z: point.z, cm: grip.id === 'front' ? wallLimit() : sideLimit(grip.id), scale: room.scale };
            canvas.setPointerCapture(event.pointerId);
            return;
        }
        const openingHit = planOpeningHit(point, room);
        if (openingHit && planTool !== 'door' && planTool !== 'window') {
            snapshot();
            planOpeningId = openingHit.item.id;
            const openingWall = room[openingHit.item.wallId];
            const grab = openingWall ? planAlongCm(point, openingWall) - (Number(openingHit.item.offset) || 0) : 0;
            planPointer = { kind: 'opening', edge: openingHit.edge, id: openingHit.item.id, grab };
            canvas.setPointerCapture(event.pointerId);
            drawPlan();
            syncPlanOpeningEditor();
            return;
        }
        if (openingHit && (planTool === 'door' || planTool === 'window') && openingHit.item.kind === planTool) {
            snapshot();
            planOpeningId = openingHit.item.id;
            const openingWall = room[openingHit.item.wallId];
            const grab = openingWall ? planAlongCm(point, openingWall) - (Number(openingHit.item.offset) || 0) : 0;
            planPointer = { kind: 'opening', edge: openingHit.edge, id: openingHit.item.id, grab };
            canvas.setPointerCapture(event.pointerId);
            drawPlan();
            syncPlanOpeningEditor();
            return;
        }
        planOpeningId = null;
        syncPlanOpeningEditor();
        if (planTool === 'door' || planTool === 'window') {
            const hit = planWallHit(point, room);
            if (!hit) return;
            snapshot();
            toggleOpening(hit.wall, hit.t);
            refreshPlan();
            scheduleSave();
            return;
        }
        const mod = planModuleHit(point, room);
        if (!mod || mod.kind === 'corner' || mod.kind === 'end') {
            if (mod) {
                selectedId = mod.id;
                drawPlan();
            }
            return;
        }
        const wall = room[wallName(mod)];
        const alongPx = wall.z1 === wall.z2 ? point.x - wall.x1 : point.z - wall.z1;
        snapshot();
        selectedId = mod.id;
        moduleDrag = { id: mod.id, x: event.clientX, y: event.clientY, active: true, grab: alongPx / room.scale - (Number(mod.offset) || 0) };
        dragId = mod.id;
        planPointer = { kind: 'module', id: mod.id };
        canvas.setPointerCapture(event.pointerId);
        drawPlan();
    });
    canvas.addEventListener('pointermove', (event) => {
        if (!planPointer) return;
        const point = planPoint(event);
        const room = planMetrics();
        if (planPointer.kind === 'opening') {
            const item = ensureSketch().openings.find((entry) => entry.id === planPointer.id);
            const wall = item ? room[item.wallId] : null;
            if (item && wall) {
                const cursor = planAlongCm(point, wall);
                const at = planPointer.edge === 'move' ? cursor - (Number(planPointer.grab) || 0) + (Number(item.width) || 0) / 2 : cursor;
                const next = resizeOpeningSpan(wall.length, item.offset, item.width, planPointer.edge, at);
                item.offset = next.offset;
                item.width = next.width;
                fitRoom();
                drawPlan();
                syncPlanOpeningEditor();
            }
            return;
        }
        if (planPointer.kind === 'grip') {
            const delta = planPointer.id === 'front' ? point.x - planPointer.x : point.z - planPointer.z;
            const next = clamp(Math.round(planPointer.cm + delta / planPointer.scale), 80, 800);
            if (planPointer.id === 'front') state.wallWidth = next;
            else if (planPointer.id === 'left') state.leftWall = next;
            else state.rightWall = next;
            paintPlan();
            return;
        }
        const mod = state.modules.find((item) => item.id === planPointer.id);
        if (!mod) return;
        const pick = planWallPick(point, room, mod);
        if (pick.wall !== wallName(mod)) {
            mod.wall = pick.wall;
            if (moduleDrag) {
                moduleDrag.anchor = null;
                moduleDrag.origin = null;
                moduleDrag.grab = alongSize(mod) / 2;
            }
        }
        const wall = room[wallName(mod)];
        if (!wall) return;
        const alongPx = wall.z1 === wall.z2 ? point.x - wall.x1 : point.z - wall.z1;
        slideModule(mod, alongPx / room.scale - (moduleDrag?.grab || 0));
        paintPlan();
    });
    canvas.addEventListener('pointerup', () => {
        const dragged = planPointer?.kind === 'module' ? state.modules.find((item) => item.id === planPointer.id) : null;
        if (dragged) settleOverlaps(dragged);
        planPointer = null;
        moduleDrag = null;
        dragId = null;
        if (planMode === '2d') refreshPlan();
        scheduleSave();
    });
    ['plan-open-w', 'plan-open-h', 'plan-open-sill'].forEach((id) => {
        el(id)?.addEventListener('input', (event) => {
            const item = ensureSketch().openings.find((entry) => entry.id === planOpeningId);
            const value = Number(event.target.value);
            if (!item || !Number.isFinite(value)) return;
            if (id === 'plan-open-w') patchPlanOpening(item, { width: value });
            if (id === 'plan-open-h') patchPlanOpening(item, { height: value });
            if (id === 'plan-open-sill') patchPlanOpening(item, { sill: value });
        });
    });
    el('plan-open-delete')?.addEventListener('click', () => {
        if (!planOpeningId) return;
        commit(() => {
            const sketch = ensureSketch();
            sketch.openings = sketch.openings.filter((item) => item.id !== planOpeningId);
            planOpeningId = null;
        });
        syncPlanOpeningEditor();
        fitRoom();
        drawPlan();
    });
    el('plan-tools')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-plan-tool]');
        if (!button) return;
        planTool = button.dataset.planTool;
        el('plan-tools').querySelectorAll('button').forEach((item) => item.classList.toggle('is-on', item === button));
    });
}

function framePlan() {
    const width = wallLimit();
    const depth = Math.max(roomDepthLimit(), 160);
    const cx = width / 2;
    const cz = depth / 2;
    const aspect = (el('viewport').clientWidth || 600) / Math.max(1, el('viewport').clientHeight || 400);
    const halfH = Math.max(width, depth) * 0.62;
    orthoCamera.position.set(cx, 1200, cz);
    orthoCamera.up.set(0, 0, -1);
    orthoCamera.lookAt(cx, 0, cz);
    orthoCamera.left = -halfH * aspect;
    orthoCamera.right = halfH * aspect;
    orthoCamera.top = halfH;
    orthoCamera.bottom = -halfH;
    orthoCamera.zoom = 1;
    orthoCamera.updateProjectionMatrix();
    controls.target.set(cx, 0, cz);
    controls.update();
}

function setPlanMode(mode) {
    if (!perspCamera || !orthoCamera || !controls) return;
    planMode = mode === '2d' ? '2d' : '3d';
    toggleRoomPlan(false);
    document.body.classList.toggle('plan-2d', planMode === '2d');
    document.body.classList.remove('plan-room');
    el('plan-board')?.classList.toggle('hidden', planMode !== '2d');
    syncViewModeBar();
    if (planMode === '2d') {
        bindPlanBoard();
        drawPlan();
        return;
    }
    camera = perspCamera;
    controls.object = perspCamera;
    controls.enableRotate = true;
    setSpacePan(false);
    controls.mouseButtons.LEFT = THREE.MOUSE.ROTATE;
    controls.minPolarAngle = 0.08;
    controls.maxPolarAngle = Math.PI / 2 - 0.02;
    setCameraView('perspective');
    resize();
}

function revealCustomizer(mod) {
    if (!mod || customFor === mod.id) return;
    customFor = mod.id;
    if (isPhone()) return;
    document.body.classList.add('show-custom');
    document.body.classList.remove('show-library');
    requestAnimationFrame(resize);
}

function closeModuleLibrary() {
    el('library')?.classList.remove('is-open');
    document.body.classList.remove('show-library');
    if (isPhone()) document.body.dataset.sheet = '';
}

function showModuleLibrary() {
    toggleRoomPlan(false);
    customFor = selectedId;
    document.body.classList.add('show-library');
    document.body.classList.remove('show-custom');
    el('library')?.classList.add('is-open');
    if (isPhone()) {
        document.body.dataset.sheet = 'modules';
        el('inspector')?.classList.remove('is-open');
        el('project-sheet')?.classList.remove('is-open');
    }
    requestAnimationFrame(resize);
}

function syncGuides(spots) {
    if (!guideLines) return;
    guideLines.visible = guidesOn;
    if (!guidesOn) return;
    const width = wallLimit();
    const depth = roomDepthLimit();
    const pts = [];
    const add = (x1, z1, x2, z2) => pts.push(x1, 0.6, z1, x2, 0.6, z2);
    for (let x = 0; x <= width + 0.1; x += 50) add(x, 0, x, depth);
    for (let z = 0; z <= depth + 0.1; z += 50) add(0, z, width, z);
    add(0, 0, width, 0);
    add(0, 0, 0, depth);
    add(width, 0, width, depth);
    (spots || []).forEach((spot) => {
        const along = alongSize(spot.mod);
        const boxDepth = spot.mod.kind === 'corner' ? spot.mod.width : (spot.mod.glbDepth || state.depth || 60);
        if (Math.abs(spot.rot) < 0.01) {
            add(spot.x, spot.z, spot.x, spot.z + boxDepth);
            add(spot.x + along, spot.z, spot.x + along, spot.z + boxDepth);
            add(spot.x, spot.z + boxDepth, spot.x + along, spot.z + boxDepth);
        }
    });
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(pts, 3));
    guideLines.geometry.dispose();
    guideLines.geometry = geometry;
}

function nudgeDepth(step) {
    const mod = selected();
    if (!mod || mod.kind === 'end') return;
    commit(() => {
        mod.depthOffset = clamp(depthNudge(mod) + step, 0, 120);
    });
}

function init3d() {
    const viewport = el('viewport');
    scene = new THREE.Scene();
    scene.background = new THREE.Color('#e7e6e3');
    camera = new THREE.PerspectiveCamera(42, 1, 2, 4000);
    perspCamera = camera;
    orthoCamera = new THREE.OrthographicCamera(-200, 200, 200, -200, 1, 4000);
    renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 0.98;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    viewport.appendChild(renderer.domElement);

    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environmentIntensity = 0.46;

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
    applyFloorStyle();
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
    guideLines = new THREE.LineSegments(
        new THREE.BufferGeometry(),
        new THREE.LineBasicMaterial({ color: 0x7f978c, transparent: true, opacity: 0.55 })
    );
    guideLines.raycast = () => {};
    scene.add(guideLines);
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.maxPolarAngle = Math.PI * 0.49;
    controls.minDistance = 80;
    controls.maxDistance = 1100;
    controls.enablePan = true;
    controls.screenSpacePanning = true;
    controls.mouseButtons.LEFT = THREE.MOUSE.ROTATE;
    controls.mouseButtons.MIDDLE = THREE.MOUSE.DOLLY;
    controls.mouseButtons.RIGHT = THREE.MOUSE.PAN;
    controls.touches.ONE = THREE.TOUCH.ROTATE;
    controls.touches.TWO = THREE.TOUCH.DOLLY_PAN;
    frameCamera();
    clock = new THREE.Clock();
    resize();
    window.addEventListener('resize', resize);
    renderer.setAnimationLoop(tick);
    el('hold-select')?.classList.add('hidden');
    bindSpacePan(renderer.domElement);

    const clickSlop = 8;
    renderer.domElement.addEventListener('pointerdown', (event) => {
        if (walkMode) {
            if (!walkLock?.isLocked && event.button === 0) walkLock?.lock();
            return;
        }
        // Space + sürükle: OrbitControls pan — modül seçimi/sürüklemesi yok.
        if (spacePanHeld) {
            pointerPress = null;
            pointerDown = null;
            moduleDrag = null;
            controls.enabled = true;
            return;
        }
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
        if (selectedId !== id || allCabinetsSelected) {
            allCabinetsSelected = false;
            selectedId = id;
            sceneFocus = 'module';
            openInspectorForSelection();
            render();
        }
    }, true);
    window.addEventListener('pointermove', (event) => {
        if (walkMode) return;
        if (planMode === '2d') return;
        if (spacePanHeld) return;
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
            const dragged = state.modules.find((item) => item.id === moduleDrag.id);
            if (dragged) moduleDrag.origin = Number(dragged.offset) || 0;
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
        const dragged = moduleDrag ? state.modules.find((item) => item.id === moduleDrag.id) : null;
        if (dragged) settleOverlaps(dragged);
        if (moduleDrag) moduleDrag.anchor = null;
        moduleDrag = null;
        dragId = null;
        pointerDown = null;
        controls.enabled = true;
        clearDragGhost();
        clearSnapHints();
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
        if (press?.moved) return;
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        if (press && Math.hypot(event.clientX - press.x, event.clientY - press.y) > clickSlop) return;
        if (!press?.id) {
            const wallId = wallUnder(event);
            if (wallId) {
                selectedId = null;
                sceneFocus = 'wall';
                allCabinetsSelected = false;
                customFor = null;
                el('inspector')?.classList.add('is-open');
                if (isPhone()) {
                    document.body.classList.add('show-custom');
                    document.body.classList.remove('show-library');
                }
                render();
                return;
            }
            if (floorUnder(event)) {
                selectedId = null;
                sceneFocus = 'floor';
                allCabinetsSelected = false;
                customFor = null;
                el('inspector')?.classList.add('is-open');
                if (isPhone()) {
                    document.body.classList.add('show-custom');
                    document.body.classList.remove('show-library');
                }
                render();
            } else if (selectedId) {
                selectedId = null;
                sceneFocus = 'room';
                render();
            }
            return;
        }
        if (selectedId === press.id && !allCabinetsSelected) return;
        allCabinetsSelected = false;
        selectedId = press.id;
        sceneFocus = 'module';
        openInspectorForSelection();
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
    if (!host) return;
    if (!glbCatalog.length) {
        host.innerHTML = '<p class="hint">Modüller yükleniyor…</p>';
        return;
    }
    const query = foldText(el('module-search')?.value || '');
    const matches = (item) => !query || foldText(`${item.name} ${item.spec} ${item.width} ${item.category || ''}`).includes(query);
    const labels = { 'y26-tek': 'Yeni tek kapılı', 'y26-cift': 'Yeni çift kapılı', tek: 'Tek Kapılı', cift: 'Çift Kapılı', kose: 'Köşe' };
    const body = ['y26-tek', 'y26-cift', 'tek', 'cift', 'kose'].map((key) => {
        const cards = glbCatalog.filter((item) => item.category === key && matches(item)).map((item) => `
            <div class="card${glbFailed.has(item.id) ? ' is-broken' : ''}" data-glb="${item.id}">
                <img src="${item.previewPath}" alt="" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='/images/wardrobe/glb-preview/tek_v1.png'">
                <strong>${item.name}</strong>
                <span>${String(item.spec || '').startsWith('Y26') ? `${item.spec} · ` : ''}${Math.round(item.width * 100)} × ${Math.round(item.height * 100)} × ${Math.round(item.depth * 100)} cm</span>
                <button type="button" class="card-add">Ekle</button>
            </div>`).join('');
        return cards ? `<p class="catalog-kicker">${labels[key]}</p><div class="catalog">${cards}</div>` : '';
    }).join('');
    host.innerHTML = body || '<p class="hint">Eşleşen modül yok.</p>';
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

function colorChoicesHtml(source, showDrawer) {
    const allOn = allCabinetsSelected ? ' is-on' : '';
    return `
        ${decorPicker(source.carcassId, 'carcass', 'Gövde rengi', true)}
        ${decorPicker(source.doorColorId, 'door', 'Kapak rengi', true)}
        ${decorPicker(backOf(source), 'back', 'Arkalık rengi', true)}
        ${decorPicker(shelfOf(source), 'shelf', 'Raf rengi', true)}
        ${showDrawer ? decorPicker(drawerOf(source), 'drawer', 'Çekmece rengi', true) : ''}
        <div class="field" data-picker="scope">
            <button type="button" class="btn${allOn}" id="select-all-cabinets" style="width:100%">Tüm dolapları seç</button>
            <button type="button" class="btn" id="apply-all" style="width:100%;margin-top:8px">Seçimi tüm dolaplara uygula</button>
        </div>`;
}

function selectAllCabinets() {
    const cabinets = state.modules.filter((item) => item.kind !== 'end');
    if (!cabinets.length) {
        toast('Sahnede dolap yok.');
        return;
    }
    allCabinetsSelected = !allCabinetsSelected;
    if (allCabinetsSelected && !cabinets.some((item) => item.id === selectedId)) selectedId = cabinets[0].id;
    render();
}

function paintColor(mod, field, id) {
    mod[field] = id;
    if (!allCabinetsSelected) return;
    state[field] = id;
    state.modules.forEach((item) => {
        if (item.kind === 'end') return;
        item[field] = id;
    });
}

function bindColorScope(root, source) {
    const selectAll = root.querySelector('#select-all-cabinets');
    if (selectAll) selectAll.onclick = () => selectAllCabinets();
    const applyAll = root.querySelector('#apply-all');
    if (applyAll) applyAll.onclick = () => commit(() => applyColorsToAll(source));
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
        button.onclick = () => onPick(button.getAttribute(`data-${attr}`) || button.dataset[attr]);
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
    const options = [['line', 'İnce çizgi LED'], ['off', 'LED kapalı']];
    return `<div class="field field-key"><label>LED</label><div class="seg">${options.map(([id, name]) => `<button type="button" data-led="${id}" class="${id === style ? 'is-on' : ''}">${name}</button>`).join('')}</div></div>`;
}

function doorTools(mod) {
    if (!mod || mod.kind === 'end' || mod.door === 'none') return allDoorButton();
    const one = hiddenDoorIds.has(mod.id) ? 'Kapakları göster' : 'Kapakları gösterme';
    const all = hideAllDoors ? 'Tüm kapakları göster' : 'Tüm dolapların kapaklarını gösterme';
    return `<button type="button" class="btn" id="mod-hide-door" style="width:100%;margin-top:8px">${one}</button><button type="button" class="btn" id="mod-hide-all" style="width:100%;margin-top:8px">${all}</button>${allDoorButton()}`;
}

async function loadModuleJson(glbId) {
    if (!glbId) return null;
    if (moduleJsonCache.has(glbId)) return moduleJsonCache.get(glbId);
    try {
        const response = await fetch(`/catalog/y26/${glbId}/module.json`, { cache: 'force-cache' });
        if (!response.ok) return null;
        const json = await response.json();
        moduleJsonCache.set(glbId, json);
        return json;
    } catch {
        return null;
    }
}

function inspectorTabBar(active = 'kapak') {
    const tabs = [
        ['kapak', 'Kapaklar'],
        ['ic', 'İç'],
        ['govde', 'Ölçü'],
        ['baza', 'Baza'],
        ['overlay', 'Konum'],
        ['donanim', 'Donanım'],
    ];
    const current = INSPECTOR_TABS.has(active) ? active : 'kapak';
    return `<div class="seg insp-tabs" role="tablist">${tabs.map(([id, label]) => `<button type="button" role="tab" data-insp-tab="${id}" aria-selected="${current === id ? 'true' : 'false'}" class="${current === id ? 'is-on' : ''}">${label}</button>`).join('')}</div>`;
}

function paintInspectorTabs(root, active = inspectorActiveTab) {
    const current = INSPECTOR_TABS.has(active) ? active : 'kapak';
    inspectorActiveTab = current;
    root.querySelectorAll('[data-insp-tab]').forEach((button) => {
        const on = button.dataset.inspTab === current;
        button.classList.toggle('is-on', on);
        button.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    root.querySelectorAll('.insp-pane').forEach((pane) => {
        pane.hidden = pane.dataset.inspPane !== current;
    });
}

function bindInspectorTabs(root) {
    const scrollTabsIntoView = () => {
        const tabs = root.querySelector?.('.insp-tabs') || root.parentElement?.querySelector?.('.insp-tabs');
        const host = el('inspector-body');
        if (!tabs || !host) return;
        host.scrollTop = Math.max(0, tabs.offsetTop - 4);
    };
    root.querySelectorAll('[data-insp-tab]').forEach((button) => {
        button.onclick = () => {
            inspectorActiveTab = button.dataset.inspTab || 'kapak';
            paintInspectorTabs(root, inspectorActiveTab);
            scrollTabsIntoView();
        };
    });
    paintInspectorTabs(root, inspectorActiveTab);
    requestAnimationFrame(scrollTabsIntoView);
}

function syncInspectorLive() {
    const mod = selected();
    const root = el('inspector-body');
    if (!mod || !root || root.dataset.boundId !== String(mod.id)) return;
    root.querySelectorAll('[data-door]').forEach((button) => {
        button.classList.toggle('is-on', button.dataset.door === mod.door);
    });
    const swing = swingOf(mod);
    root.querySelectorAll('[data-swing]').forEach((button) => {
        button.classList.toggle('is-on', button.dataset.swing === swing);
    });
    root.querySelectorAll('[data-led]').forEach((button) => {
        button.classList.toggle('is-on', button.dataset.led === ledStyleOf(mod));
    });
    const opener = root.querySelector('#mod-open');
    if (opener) opener.textContent = mod.open > 0.5 ? 'Kapağı kapat' : 'Kapağı aç';
    const drawers = root.querySelector('#mod-drawers');
    if (drawers) drawers.textContent = mod.drawersOpen > 0.5 ? 'Çekmeceleri kapat' : 'Çekmeceleri aç';
    const plinth = root.querySelector('#mod-plinth');
    if (plinth) plinth.checked = mod.plinth !== false;
    const plinthHidden = root.querySelector('#mod-plinth-hidden');
    if (plinthHidden) {
        plinthHidden.checked = Boolean(mod.plinthHidden);
        plinthHidden.disabled = mod.plinth === false;
    }
    const kulp = root.querySelector('#mod-kulp');
    if (kulp) kulp.checked = mod.kulp !== false;
    const markDecor = (attr, id) => {
        const picker = root.querySelector(`[data-picker="${attr}"]`);
        if (!picker) return;
        const label = picker.querySelector('label');
        if (label && !picker.querySelector('.swatches')) {
            const title = label.textContent.split(':')[0];
            label.textContent = `${title}: ${decorLabel(id)}`;
        }
        picker.querySelectorAll('[data-decor]').forEach((button) => {
            button.classList.toggle('is-on', button.dataset.decor === id);
        });
    };
    markDecor('carcass', mod.carcassId);
    markDecor('door', mod.doorColorId);
    markDecor('back', backOf(mod));
    markDecor('shelf', shelfOf(mod));
    markDecor('drawer', drawerOf(mod));
    root.querySelectorAll('[data-alum]').forEach((button) => {
        button.classList.toggle('is-on', button.dataset.alum === aluminumOf(mod).id);
    });
    root.querySelectorAll('[data-glass]').forEach((button) => {
        button.classList.toggle('is-on', button.dataset.glass === glassOf(mod).id);
    });
    const alumLabel = root.querySelector('[data-picker="alum"] label');
    if (alumLabel) alumLabel.textContent = `Alüminyum kapak: ${aluminumOf(mod).name}`;
    const glassLabel = root.querySelector('[data-picker="glass"] label');
    if (glassLabel) glassLabel.textContent = `Cam rengi: ${glassOf(mod).name}`;
    paintInspectorTabs(root, inspectorActiveTab);
}

function hingeScheduleHtml(mod) {
    if (!mod || mod.kind === 'end' || mod.door === 'none') {
        return '<p class="hint">Kapak yok — menteşe tablosu üretilmez.</p>';
    }
    const hMm = Math.round(moduleHeight(mod) * 10);
    const leaves = doorCount(mod);
    const rows = [];
    for (let i = 0; i < leaves; i++) {
        const hinge = hingeFor(mod, i, leaves);
        const y1 = Math.round(hMm * 0.12);
        const y2 = Math.round(hMm * 0.5);
        const y3 = Math.max(y1 + 80, Math.round(hMm * 0.88));
        rows.push(`<tr><td>${leaves > 1 ? `Kapak ${i + 1}` : 'Kapak'}</td><td>${hinge === 'right' ? 'Sağ menteşe' : 'Sol menteşe'}</td><td>${y1} · ${y2} · ${y3}</td></tr>`);
    }
    return `<table class="parts-table"><thead><tr><th>Parça</th><th>Yön</th><th>Y (mm)</th></tr></thead><tbody>${rows.join('')}</tbody></table>`;
}

function modulePartsHtml(parts) {
    const rows = (parts || []).slice(0, 36).map((part) => {
        const w = part.width_mm ?? part.width ?? '—';
        const h = part.height_mm ?? part.height ?? '—';
        const d = part.depth_mm ?? part.depth ?? '—';
        const layer = part.layer || 'PARÇA';
        const name = part.name || '—';
        return `<tr><td>${layer}</td><td>${name}</td><td>${w}×${h}×${d}</td></tr>`;
    }).join('');
    return `<div class="parts-panel" id="module-parts"><h3>Üretim parçaları</h3><table class="parts-table"><thead><tr><th>Katman</th><th>Parça</th><th>mm (G×Y×D)</th></tr></thead><tbody>${rows}</tbody></table><button type="button" class="btn" id="mod-cabinet" style="width:100%;margin-top:8px">Cabinet Studio’da aç</button></div>`;
}

function hydrateModuleParts(mod) {
    if (!mod?.glb) return;
    const token = mod.id;
    loadModuleJson(mod.glb).then((json) => {
        const host = el('module-parts');
        if (!host || selected()?.id !== token) return;
        if (!json?.parts?.length) {
            host.innerHTML = '<p class="hint">Bu modül için parça listesi yok.</p>';
            return;
        }
        host.outerHTML = modulePartsHtml(json.parts);
        el('mod-cabinet')?.addEventListener('click', openCabinetStudio);
    });
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
        ? `<div class="field field-key"><label>Çekmece</label><button type="button" class="btn" id="mod-drawers" style="width:100%">${mod.drawersOpen > 0.5 ? 'Çekmeceleri kapat' : 'Çekmeceleri aç'}</button></div>`
        : drawers > 0
        ? `<div class="field field-key"><label>Çekmece</label>
            <div class="field"><label>Adet</label><input id="mod-drawer-count" type="number" min="1" max="8" value="${drawers}"></div>
            <button type="button" class="btn" id="mod-drawers" style="width:100%">${mod.drawersOpen > 0.5 ? 'Çekmeceleri kapat' : 'Çekmeceleri aç'}</button>
        </div>`
        : '';
    const listed = glbCatalog.find((entry) => entry.id === mod.glb);
    const boardNote = listed?.boardMm
        ? `<details class="insp-tech"><summary>Panel kalınlıkları</summary><p>Raf, yan panel ve çekmece önü ${listed.boardMm} mm. Arkalık ${listed.backMm} mm. Çekmece kutusu: yan ve arka 16 mm, taban 8 mm. Dekor görseli temsili dijital dokudur.</p></details>`
        : '';
    const swingBlock = swingPicker(mod);
    const doorToolsBlock = swingBlock ? '' : doorTools(mod);
    return `
        <div class="insp-mod-head">
            <p class="picked">Seçili dolap</p>
            <h2>${mod.name}</h2>
            <p class="hint">${mod.width} × ${moduleHeight(mod)} cm · siyah çubuk: kapak / klon / sil${allCabinetsSelected ? ' · tüm dolaplar seçili' : ''}</p>
        </div>
        ${inspectorTabBar(inspectorActiveTab)}
        <div class="insp-pane" data-insp-pane="kapak"${inspectorActiveTab === 'kapak' ? '' : ' hidden'}>
            ${mod.kind === 'end' ? '' : `<div class="field field-key"><label>Kapak tipi</label><div class="seg seg-compact"><button type="button" data-door="solid" class="${mod.door === 'solid' ? 'is-on' : ''}">Ahşap</button><button type="button" data-door="glass" class="${mod.door === 'glass' ? 'is-on' : ''}">Cam</button><button type="button" data-door="none" class="${mod.door === 'none' ? 'is-on' : ''}">Yok</button>${doorCount(mod) > 1 ? `<button type="button" data-door="mixed" class="${mod.door === 'mixed' ? 'is-on' : ''}">Karışık</button>` : ''}</div></div>`}
            ${swingBlock || doorToolsBlock}
            ${swatchPicker(orderedFinishes(ALUMINUM, ALUM_FOCUS), aluminumOf(mod).id, 'alum', 'Alüminyum kapak', true)}
            ${swatchPicker(orderedFinishes(GLASS, GLASS_FOCUS), glassOf(mod).id, 'glass', 'Cam rengi')}
        </div>
        <div class="insp-pane" data-insp-pane="ic"${inspectorActiveTab === 'ic' ? '' : ' hidden'}>
            ${colorChoicesHtml(mod, cabinetHasDrawers(mod))}
            ${shelfField}
            ${drawerField}
            ${ledPicker(mod)}
        </div>
        <div class="insp-pane" data-insp-pane="govde"${inspectorActiveTab === 'govde' ? '' : ' hidden'}>
            ${mod.kind === 'end' ? '' : `<div class="field"><label>Genişlik (cm)</label><input id="mod-w" type="number" min="30" max="200" value="${mod.width}"></div>`}
            ${mod.kind === 'end' ? '' : `<div class="field"><label>Yükseklik (cm)</label><input id="mod-h" type="number" min="${mod.dxf === 'ust' ? 40 : H_MIN}" max="${H_MAX}" value="${moduleHeight(mod)}"></div>`}
            ${mod.glb ? '<div id="module-parts" class="parts-panel"><p class="hint">Parça listesi yükleniyor…</p></div>' : ''}
            ${boardNote}
        </div>
        <div class="insp-pane" data-insp-pane="baza"${inspectorActiveTab === 'baza' ? '' : ' hidden'}>
            <label class="check"><input id="mod-plinth" type="checkbox" ${mod.plinth === false ? '' : 'checked'}> Baza (toe kick)</label>
            <label class="check"><input id="mod-plinth-hidden" type="checkbox" ${mod.plinthHidden ? 'checked' : ''} ${mod.plinth === false ? 'disabled' : ''}> Gizli baza</label>
        </div>
        <div class="insp-pane" data-insp-pane="overlay"${inspectorActiveTab === 'overlay' ? '' : ' hidden'}>
            ${mod.kind === 'end' ? '' : `<div class="field"><label>Öne / arkaya (cm)</label><input id="mod-depth" type="number" min="0" max="120" value="${depthNudge(mod)}"></div><p class="hint">Kapak bindirme ve derinlik hizası için modülü öne veya arkaya kaydırın.</p>`}
            ${mod.kind !== 'end' && !mod.glb ? `<button type="button" class="btn" id="mod-upper" style="width:100%;margin-bottom:12px">${upperHeight(mod) ? 'Üst dolabı kaldır' : 'Üst dolap ekle'}</button>` : ''}
            <div style="display:flex;gap:8px;margin-top:8px">
                <button type="button" class="btn" id="mod-left">Sola kaydır</button>
                <button type="button" class="btn" id="mod-right">Sağa kaydır</button>
            </div>
        </div>
        <div class="insp-pane" data-insp-pane="donanim"${inspectorActiveTab === 'donanim' ? '' : ' hidden'}>
            <label class="check"><input id="mod-kulp" type="checkbox" ${mod.kulp === false ? '' : 'checked'}> Kulp / tutamak</label>
            <h3 style="font-size:13px;margin:12px 0 6px">Menteşe tablosu</h3>
            ${hingeScheduleHtml(mod)}
        </div>`;
}

function bindModuleConfig(root, mod) {
    bindInspectorTabs(root);
    root.querySelectorAll('[data-door]').forEach((button) => {
        button.onclick = () => {
            if (mod.door === button.dataset.door) return;
            // Kapak tipi UI’yi değiştirir (açılış seçenekleri) → full refresh, sekme korunur.
            commit(() => {
                mod.door = button.dataset.door;
                if (mod.door === 'glass' || mod.door === 'mixed') {
                    if (!GLASS.some((item) => item.id === mod.glassId)) {
                        mod.glassId = /bronz/i.test(mod.name || '') ? 'cam-bronz' : 'cam-fum';
                    }
                    if (!ALUMINUM.some((item) => item.id === mod.aluminumId)) {
                        mod.aluminumId = /bronz/i.test(mod.name || '') ? 'al-bronz' : 'al-inox';
                    }
                }
            });
        };
    });
    root.querySelectorAll('[data-swing]').forEach((button) => {
        button.onclick = () => commitScene(() => { mod.swing = button.dataset.swing; });
    });
    root.querySelectorAll('[data-interior]').forEach((button) => {
        button.onclick = () => commitScene(() => applyInterior(mod, button.dataset.interior));
    });
    const widthInput = root.querySelector('#mod-w');
    if (widthInput) widthInput.addEventListener('change', (event) => commitScene(() => {
        mod.width = clamp(Number(event.target.value), 30, 200);
    }));
    const depthInput = root.querySelector('#mod-depth');
    if (depthInput) depthInput.addEventListener('change', (event) => commitScene(() => {
        mod.depthOffset = clamp(Number(event.target.value), 0, 120);
    }));
    const shelfInput = root.querySelector('#mod-shelves');
    if (shelfInput) shelfInput.addEventListener('change', (event) => commitScene(() => {
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
    if (drawerCount) drawerCount.addEventListener('change', (event) => commitScene(() => {
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
    if (drawerHeight) drawerHeight.addEventListener('change', (event) => commitScene(() => {
        mod.drawerHeight = clamp(Number(event.target.value), 12, 40);
        mod.layout = 'custom';
        mod.product = 'custom';
    }));
    bindDecor(root, 'drawer', drawerOf(mod), (id) => commitScene(() => paintColor(mod, 'drawerColorId', id)));
    root.querySelector('#mod-plinth')?.addEventListener('change', (event) => commitScene(() => {
        mod.plinth = event.target.checked;
        if (!mod.plinth) mod.plinthHidden = false;
    }));
    const hiddenKick = root.querySelector('#mod-plinth-hidden');
    if (hiddenKick) hiddenKick.addEventListener('change', (event) => commitScene(() => { mod.plinthHidden = event.target.checked; }));
    root.querySelector('#mod-kulp')?.addEventListener('change', (event) => commitScene(() => { mod.kulp = event.target.checked; }));
    bindSwatch(root, 'led', (id) => commitScene(() => {
        mod.ledStyle = id === 'off' ? 'off' : id;
        mod.led = id !== 'off';
        if (mod.led) lightsOn = true;
        syncLightsButton();
    }));
    const heightInput = root.querySelector('#mod-h');
    if (heightInput) heightInput.addEventListener('change', (event) => commitScene(() => {
        mod.height = clamp(Number(event.target.value), mod.dxf === 'ust' ? 40 : H_MIN, H_MAX);
    }));
    bindSwatch(root, 'alum', (id) => applyModuleAluminum(mod, id));
    bindSwatch(root, 'glass', (id) => applyModuleGlass(mod, id));
    bindDecor(root, 'carcass', mod.carcassId, (id) => commitScene(() => paintColor(mod, 'carcassId', id)));
    bindDecor(root, 'door', mod.doorColorId, (id) => commitScene(() => paintColor(mod, 'doorColorId', id)));
    bindDecor(root, 'back', backOf(mod), (id) => commitScene(() => paintColor(mod, 'backId', id)));
    bindDecor(root, 'shelf', shelfOf(mod), (id) => commitScene(() => paintColor(mod, 'shelfId', id)));
    bindColorScope(root, mod);
    const opener = root.querySelector('#mod-open');
    if (opener) opener.onclick = () => commitScene(() => { mod.open = mod.open > 0.5 ? 0 : 1; });
    const hideOne = root.querySelector('#mod-hide-door');
    if (hideOne) hideOne.onclick = () => {
        if (hiddenDoorIds.has(mod.id)) hiddenDoorIds.delete(mod.id);
        else hiddenDoorIds.add(mod.id);
        rebuild();
        renderInspector();
    };
    const hideAll = root.querySelector('#mod-hide-all');
    if (hideAll) hideAll.onclick = () => {
        hideAllDoors = !hideAllDoors;
        rebuild();
        renderInspector();
    };
    const upper = root.querySelector('#mod-upper');
    if (upper) upper.onclick = () => {
        if (upperHeight(mod)) {
            commitScene(() => { mod.upper = 0; });
            return;
        }
        const fit = Math.floor(roomHeightLimit() - moduleHeight(mod));
        if (fit < 30) {
            toast('Tavan payı üst dolap için yetmiyor.');
            return;
        }
        commitScene(() => { mod.upper = Math.min(48, fit); });
    };
    const drawers = root.querySelector('#mod-drawers');
    if (drawers) drawers.onclick = () => commitScene(() => {
        mod.drawersOpen = mod.drawersOpen > 0.5 ? 0 : 1;
        if (mod.drawersOpen > 0.5) mod.open = 1;
    });
    const allDoors = root.querySelector('#mod-doors-all');
    if (allDoors) allDoors.onclick = () => commitScene(() => {
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
    syncInspectorChrome(mod);
    renderProduction();
    const config = el('config-modal');
    const scrollY = body?.scrollTop || 0;
    if (!mod) {
        inspectorBoundId = null;
        customFor = null;
        config.classList.add('hidden');
        body.innerHTML = `
            <div class="room-panel">
            <p class="hint">Zemine veya duvara tıklayın · L oda ölçüleri aşağıda.</p>
            <div class="row-actions" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                <button type="button" class="btn" id="btn-clear-modules" ${state.modules.length ? '' : 'disabled'}>Dolapları temizle</button>
                <button type="button" class="btn" id="btn-open-room-sketch">Odayı çiz</button>
            </div>
            <div class="walls-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div class="field"><label>Sol (cm)</label><input id="wall-left" type="number" min="80" max="800" value="${sideLimit('left')}"></div>
            <div class="field"><label>Sağ (cm)</label><input id="wall-right" type="number" min="80" max="800" value="${sideLimit('right')}"></div>
            <div class="field" style="grid-column:1/-1"><label>Ön duvar (cm)</label><input id="wall-front" type="number" min="80" max="800" value="${wallLimit()}"></div>
            <div class="field" style="grid-column:1/-1"><label>Tavan (cm)</label><input id="room-h" type="number" min="${ROOM_H_MIN}" max="${ROOM_H_MAX}" value="${roomHeightLimit()}"></div>
            </div>
            <details open>
                <summary>Görünüm</summary>
                <div class="field"><label>Zemin</label><div class="seg">${FLOOR_STYLES.map((item) => `<button type="button" data-floor-style="${item.id}" class="${(state.floorStyle || 'parquet') === item.id ? 'is-on' : ''}">${item.name}</button>`).join('')}</div></div>
                <div class="field"><label>Duvar rengi</label><div class="seg">${['#f7f6f4', '#e8e4dc', '#d4cfc6', '#c5d4c9', '#ffffff', '#3d4a42'].map((hex) => `<button type="button" data-wall-color="${hex}" class="${(state.wallColor || '#f7f6f4').toLowerCase() === hex ? 'is-on' : ''}" style="width:28px;height:28px;background:${hex};border:1px solid #ccc;border-radius:6px" title="${hex}"></button>`).join('')}</div></div>
                <div class="field opacity-field"><label>Panel opaklığı</label><input id="panel-opacity" type="range" min="25" max="100" value="${Math.round((state.panelOpacity ?? 1) * 100)}"><span id="panel-opacity-val">${Math.round((state.panelOpacity ?? 1) * 100)}%</span></div>
            </details>
            <details>
                <summary>Yeni modül varsayılanları</summary>
                ${colorChoicesHtml(state, state.modules.some((item) => cabinetHasDrawers(item)))}
                <label class="check"><input id="cab-plinth" type="checkbox" ${state.plinth === false ? '' : 'checked'}> Baza</label>
                <label class="check"><input id="cab-kulp" type="checkbox" ${state.kulp === false ? '' : 'checked'}> Kulp</label>
                <label class="check"><input id="cab-led" type="checkbox" ${state.led === false ? '' : 'checked'}> LED</label>
                <div class="field"><label>Yükseklik (cm)</label><input id="cab-h" type="number" min="${H_MIN}" max="${H_MAX}" value="${state.height}"></div>
                <div class="field"><label>Derinlik (cm)</label><input id="cab-d" type="number" min="40" max="70" value="${state.depth}"></div>
                ${allDoorButton()}
            </details>
            </div>`;
        body.querySelector('#btn-clear-modules')?.addEventListener('click', () => clearAllModules());
        body.querySelector('#btn-open-room-sketch')?.addEventListener('click', () => toggleRoomPlan(true));
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
            state.roomHeight = clamp(Number(event.target.value), ROOM_H_MIN, ROOM_H_MAX);
        }));
        body.querySelectorAll('[data-floor-style]').forEach((button) => {
            button.addEventListener('click', () => commit(() => {
                state.floorStyle = button.dataset.floorStyle;
                applyFloorStyle();
            }));
        });
        body.querySelectorAll('[data-wall-color]').forEach((button) => {
            button.addEventListener('click', () => commit(() => {
                state.wallColor = button.dataset.wallColor;
                fitRoom();
                rebuild();
            }));
        });
        const opacitySlider = body.querySelector('#panel-opacity');
        if (opacitySlider) {
            opacitySlider.addEventListener('input', (event) => {
                const value = clamp(Number(event.target.value), 25, 100);
                state.panelOpacity = value / 100;
                panelOpacity = state.panelOpacity;
                const label = body.querySelector('#panel-opacity-val');
                if (label) label.textContent = `${value}%`;
                matCache.clear();
                rebuild();
            });
        }
        body.querySelector('#cab-plinth').addEventListener('change', (event) => commit(() => {
            state.plinth = event.target.checked;
            if (!state.plinth) state.plinthHidden = false;
            state.modules.forEach((item) => {
                item.plinth = state.plinth;
                if (!state.plinth) item.plinthHidden = false;
            });
        }));
        const plinthHidden = body.querySelector('#cab-plinth-hidden');
        if (plinthHidden) plinthHidden.addEventListener('change', (event) => commit(() => {
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
        bindSwatch(body, 'alum', (id) => {
            if (!ALUMINUM.some((item) => item.id === id)) return;
            commit(() => {
                state.aluminumId = id;
                state.modules.forEach((item) => {
                    if (item.kind === 'end') return;
                    item.aluminumId = id;
                    if (item.door !== 'glass' && item.door !== 'mixed') item.door = 'glass';
                });
            });
        });
        bindSwatch(body, 'glass', (id) => {
            if (!GLASS.some((item) => item.id === id)) return;
            commit(() => {
                state.glassId = id;
                state.modules.forEach((item) => {
                    if (item.kind === 'end') return;
                    item.glassId = id;
                    if (item.door !== 'glass' && item.door !== 'mixed') item.door = 'glass';
                });
            });
        });
        bindDecor(body, 'carcass', state.carcassId, (id) => commit(() => { state.carcassId = id; }));
        bindDecor(body, 'door', state.doorColorId, (id) => commit(() => { state.doorColorId = id; }));
        bindDecor(body, 'back', state.backId, (id) => commit(() => { state.backId = id; }));
        bindDecor(body, 'shelf', state.shelfId, (id) => commit(() => { state.shelfId = id; }));
        bindDecor(body, 'drawer', state.drawerColorId || solidDoorColor(), (id) => commit(() => { state.drawerColorId = id; }));
        bindColorScope(body, state);
        body.dataset.boundId = '';
        return;
    }
    config.classList.add('hidden');
    if (!isPhone()) {
        el('inspector')?.classList.add('is-open');
        document.body.classList.add('show-custom');
    }
    revealCustomizer(mod);
    if (inspectorBoundId !== mod.id) {
        inspectorBoundId = mod.id;
        inspectorActiveTab = 'kapak';
    }
    body.innerHTML = moduleConfigHtml(mod);
    body.dataset.boundId = String(mod.id);
    bindModuleConfig(body, mod);
    hydrateModuleParts(mod);
    requestAnimationFrame(() => {
        body.scrollTop = scrollY;
        paintInspectorTabs(body, inspectorActiveTab);
    });
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

function openingBlocks(wallId, margin = 10) {
    return roomOpenings(wallId).map((item) => ({
        start: Math.max(0, (Number(item.offset) || 0) - margin),
        end: (Number(item.offset) || 0) + (Number(item.width) || 0) + margin,
    }));
}

function hitsOpening(wallId, at, along) {
    const blocks = openingBlocks(wallId);
    if (!blocks.length) return false;
    const end = at + along;
    return blocks.some((block) => at < block.end - 0.5 && end > block.start + 0.5);
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
    if (object) scene.add(object);
}

function openingFrameMat() {
    if (!room.openingFrameMat) {
        room.openingFrameMat = new THREE.MeshStandardMaterial({ color: '#f0eeea', roughness: 0.88, metalness: 0.02 });
    }
    return room.openingFrameMat;
}

function openingDoorMat() {
    if (!room.openingDoorMat) {
        room.openingDoorMat = new THREE.MeshStandardMaterial({ color: '#8a6f4e', roughness: 0.62, metalness: 0.04 });
    }
    return room.openingDoorMat;
}

function openingGlassMat() {
    if (!room.openingGlassMat) {
        room.openingGlassMat = new THREE.MeshPhysicalMaterial({
            color: '#c5d8e8',
            roughness: 0.08,
            metalness: 0,
            transmission: 0.86,
            thickness: 0,
            transparent: false,
            opacity: 1,
            depthWrite: true,
            ior: 1.5,
            attenuationDistance: Infinity,
            envMapIntensity: 0.6,
        });
    }
    return room.openingGlassMat;
}

function addBoxMesh(parent, w, h, d, x, y, z, mat, rotY = 0) {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat);
    mesh.position.set(x, y, z);
    mesh.rotation.y = rotY;
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    parent.add(mesh);
    return mesh;
}

/** OpenPlan tarzı oda kapısı / penceresi — çerçeve + kanat / cam. */
function buildRoomOpeningProp(item, wallId, roomW) {
    const style = String(item.style || (item.kind === 'window' ? 'fixed' : 'single'));
    const openW = Math.max(40, Number(item.width) || (item.kind === 'window' ? 120 : 90));
    const openH = Math.max(60, Number(item.height) || (item.kind === 'window' ? 120 : 210));
    const sill = item.kind === 'window' ? Math.max(0, Number(item.sill) || 90) : 0;
    const along = Math.max(0, Number(item.offset) || 0);
    const frameT = 4;
    const wallT = ROOM_WALL_T;
    const group = new THREE.Group();
    group.name = `opening-${item.id || wallId}`;
    const frame = openingFrameMat();
    const wood = openingDoorMat();
    const glass = openingGlassMat();

    // Yerel: X = duvar boyunca, Y = yükseklik, Z = odaya doğru (+)
    const wallYaw = wallId === 'front' ? 0 : wallId === 'left' ? Math.PI / 2 : -Math.PI / 2;
    const origin = wallId === 'front'
        ? new THREE.Vector3(along, sill, 0)
        : wallId === 'left'
            ? new THREE.Vector3(0, sill, along)
            : new THREE.Vector3(roomW, sill, along);
    const host = new THREE.Group();
    host.position.copy(origin);
    host.rotation.y = wallYaw;
    group.add(host);

    const put = (w, h, d, x, y, z, mat) => addBoxMesh(host, w, h, d, x, y, z, mat, 0);

    put(frameT, openH, wallT + 2, frameT / 2, openH / 2, 0, frame);
    put(frameT, openH, wallT + 2, openW - frameT / 2, openH / 2, 0, frame);
    put(Math.max(2, openW - frameT * 2), frameT, wallT + 2, openW / 2, openH - frameT / 2, 0, frame);

    if (item.kind === 'window') {
        put(Math.max(2, openW - frameT * 2), frameT, wallT + 2, openW / 2, frameT / 2, 0, frame);
        put(Math.max(2, openW - frameT * 2), Math.max(2, openH - frameT * 2), 1.2, openW / 2, openH / 2, 1.5, glass);
        put(2, Math.max(2, openH - frameT * 2), 1.4, openW / 2, openH / 2, 1.6, frame);
        return group;
    }

    const leafH = Math.max(40, openH - frameT - 2);
    const leafD = 3.2;
    const openAngle = 0.45;
    if (style === 'double') {
        const leafW = Math.max(20, (openW - frameT * 2 - 2) / 2);
        [[frameT + 1, 1], [openW - frameT - 1, -1]].forEach(([hingeX, sign]) => {
            const pivot = new THREE.Group();
            pivot.position.set(hingeX, 1, 1.2);
            pivot.rotation.y = sign * openAngle;
            const leaf = new THREE.Mesh(new THREE.BoxGeometry(leafW, leafH, leafD), wood);
            leaf.position.set(sign * leafW / 2, leafH / 2, leafD / 2);
            leaf.castShadow = true;
            pivot.add(leaf);
            host.add(pivot);
        });
    } else if (style === 'sliding') {
        const leafW = openW * 0.55;
        put(leafW, leafH, leafD, openW * 0.28, leafH / 2 + 1, 6, wood);
        put(Math.max(2, openW - 4), 1.5, 2, openW / 2, openH - 3, 5, frame);
    } else {
        const leafW = Math.max(30, openW - frameT * 2 - 2);
        const pivot = new THREE.Group();
        pivot.position.set(frameT + 1, 1, 1.2);
        pivot.rotation.y = openAngle;
        const leaf = new THREE.Mesh(new THREE.BoxGeometry(leafW, leafH, leafD), wood);
        leaf.position.set(leafW / 2, leafH / 2, leafD / 2);
        leaf.castShadow = true;
        pivot.add(leaf);
        const knob = new THREE.Mesh(new THREE.BoxGeometry(1.2, 8, 1.4), frame);
        knob.position.set(leafW - 6, leafH * 0.48, leafD + 0.8);
        pivot.add(knob);
        host.add(pivot);
    }
    return group;
}

function fitSketchExtraWalls() {
    const extras = (state.roomSketch?.walls || []).filter((wall) => wall && wall.role === 'other');
    const height = roomHeightLimit();
    const wallT = ROOM_WALL_T;
    const group = new THREE.Group();
    group.name = 'room-extra-walls';
    extras.forEach((wall) => {
        const x1 = Number(wall.x1);
        const y1 = Number(wall.y1);
        const x2 = Number(wall.x2);
        const y2 = Number(wall.y2);
        if (![x1, y1, x2, y2].every(Number.isFinite)) return;
        const len = Math.hypot(x2 - x1, y2 - y1);
        if (len < 8) return;
        const mesh = new THREE.Mesh(new THREE.BoxGeometry(len, height, wallT), room.wallMat);
        mesh.position.set((x1 + x2) / 2, height / 2, (y1 + y2) / 2);
        mesh.rotation.y = Math.atan2(y2 - y1, x2 - x1);
        mesh.receiveShadow = true;
        group.add(mesh);
    });
    replaceWall('extras', group);
}

function fitRoomOpenings() {
    const root = new THREE.Group();
    root.name = 'room-openings';
    const roomW = wallLimit();
    ['front', 'left', 'right'].forEach((wallId) => {
        roomOpenings(wallId).forEach((item) => {
            root.add(buildRoomOpeningProp(item, wallId, roomW));
        });
    });
    replaceWall('openings', root);
}

/**
 * Ön duvarı yan duvar kalınlığı kadar uzat — dış köşe boşluğu (basamak) kalmasın.
 * Yan duvarlar z=0’dan başlar; ön duvar x=-T…width+T span eder.
 */
function withOuterCornerReturns(pieces, span, height, wallT = ROOM_WALL_T) {
    return [
        { along: -wallT, length: wallT, y: 0, h: height },
        ...pieces,
        { along: span, length: wallT, y: 0, h: height },
    ];
}

function syncSketchWalls() {
    const sketch = state.roomSketch;
    if (!sketch || !Array.isArray(sketch.walls) || !sketch.walls.length) return;
    const front = sketch.walls.find((wall) => wall.role === 'front');
    const left = sketch.walls.find((wall) => wall.role === 'left');
    const right = sketch.walls.find((wall) => wall.role === 'right');
    if (!front || !left || !right) return;
    const straight = (wall, horizontal) => (horizontal
        ? Math.abs(Number(wall.y1) - Number(wall.y2)) < 1
        : Math.abs(Number(wall.x1) - Number(wall.x2)) < 1);
    if (!straight(front, true) || !straight(left, false) || !straight(right, false)) return;
    if (Math.abs(Number(front.x1)) > 2 || Math.abs(Number(front.y1)) > 2 || Math.abs(Number(left.x1)) > 2) return;
    const before = JSON.stringify(sketch.walls);
    const oldFront = Math.abs(Number(front.x2) - Number(front.x1));
    const oldLeft = Math.abs(Number(left.y2) - Number(left.y1));
    const oldRight = Math.abs(Number(right.y2) - Number(right.y1));
    const newFront = wallLimit();
    const newLeft = sideLimit('left');
    const newRight = sideLimit('right');
    const dx = newFront - oldFront;
    const near = (a, b) => Math.abs(Number(a) - Number(b)) < 1.5;
    sketch.walls.forEach((wall) => {
        if (wall.role !== 'other') return;
        ['1', '2'].forEach((end) => {
            const xKey = `x${end}`;
            const yKey = `y${end}`;
            if (near(wall[xKey], oldFront)) wall[xKey] = Number(wall[xKey]) + dx;
            if (Number(wall[xKey]) < oldFront / 2 && near(wall[yKey], oldLeft)) wall[yKey] = newLeft;
            else if (Number(wall[xKey]) >= oldFront / 2 && near(wall[yKey], oldRight)) wall[yKey] = newRight;
        });
    });
    front.x1 = 0;
    front.y1 = 0;
    front.x2 = newFront;
    front.y2 = 0;
    left.x1 = 0;
    left.y1 = 0;
    left.x2 = 0;
    left.y2 = newLeft;
    right.x1 = newFront;
    right.y1 = 0;
    right.x2 = newFront;
    right.y2 = newRight;
    if (JSON.stringify(sketch.walls) !== before) scheduleSave();
}

function fitRoom() {
    syncSketchWalls();
    const width = wallLimit();
    const height = roomHeightLimit();
    const left = sideLimit('left');
    const right = sideLimit('right');
    const depth = roomDepthLimit();
    const wallT = ROOM_WALL_T;
    const wallHex = state.wallColor || '#f7f6f4';
    if (!room.wallMat) room.wallMat = new THREE.MeshStandardMaterial({ color: wallHex, roughness: 0.9 });
    else room.wallMat.color.set(wallHex);
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
    // Ön duvar: iç yüz z=0, dış yüz z=-wallT; uçlar yan duvar dışına taşar → temiz dış köşe.
    replaceWall('back', build(withOuterCornerReturns(wallPieces(width, height, roomOpenings('front')), width, height, wallT), {
        geometry: (piece) => new THREE.BoxGeometry(piece.length, piece.h, wallT),
        position: (piece) => new THREE.Vector3(piece.along + piece.length / 2, piece.y + piece.h / 2, -wallT / 2),
    }));
    // Sol / sağ: iç yüz x=0 / x=width; z=0’dan derinliğe — ön duvarın iç yüzüne butt join.
    replaceWall('left', build(wallPieces(left, height, roomOpenings('left')), {
        geometry: (piece) => new THREE.BoxGeometry(wallT, piece.h, piece.length),
        position: (piece) => new THREE.Vector3(-wallT / 2, piece.y + piece.h / 2, piece.along + piece.length / 2),
    }));
    replaceWall('right', build(wallPieces(right, height, roomOpenings('right')), {
        geometry: (piece) => new THREE.BoxGeometry(wallT, piece.h, piece.length),
        position: (piece) => new THREE.Vector3(width + wallT / 2, piece.y + piece.h / 2, piece.along + piece.length / 2),
    }));
    fitRoomOpenings();
    fitSketchExtraWalls();
    if (room.floor) {
        room.floor.geometry.dispose();
        room.floor.geometry = new THREE.BoxGeometry(width, 2, depth + 40);
        room.floor.position.set(width / 2, -1, (depth + 40) / 2 - 8);
        applyFloorStyle();
    }
}

function render() {
    renderSeries();
    renderLibrary();
    renderInspector();
    renderSummary();
    rebuild();
    fitRoom();
    if (planMode === '2d') drawPlan();
}

function addSet(setId) {
    const set = SETS.find((item) => item.id === setId);
    const mods = set.parts.map((id) => {
        const mod = createModule(id);
        preferCatalogWood(mod, { force: true });
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
    closeModuleLibrary();
}

function syncLightsButton() {
    const button = el('btn-lights');
    if (!button) return;
    button.classList.toggle('is-on', lightsOn);
    button.textContent = lightsOn ? 'Işıkları kapat' : 'Işıkları aç';
}

function pickRaycastPoint(event) {
    if (!renderer) return null;
    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
    raycaster.setFromCamera(pointer, camera);
    return true;
}

function floorUnder(event) {
    if (!renderer || !room.floor) return false;
    if (!pickRaycastPoint(event)) return false;
    return raycaster.intersectObject(room.floor, true).length > 0;
}

function wallUnder(event) {
    if (!renderer || !room.back) return null;
    if (!pickRaycastPoint(event)) return null;
    const hits = [
        ['front', room.back],
        ['left', room.left],
        ['right', room.right],
    ].flatMap(([id, mesh]) => {
        if (!mesh) return [];
        const hit = raycaster.intersectObject(mesh, true)[0];
        return hit ? [{ id, distance: hit.distance }] : [];
    });
    if (!hits.length) return null;
    hits.sort((a, b) => a.distance - b.distance);
    return hits[0].id;
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

function clearSnapHints() {
    if (!snapHints) return;
    scene?.remove(snapHints);
    snapHints.traverse((child) => {
        if (child.geometry) child.geometry.dispose();
        if (child.material) child.material.dispose();
    });
    snapHints = null;
}

function placeSnapHint(mod, at, color = 0x3b82f6, opacity = 0.32) {
    if (!scene) return;
    if (!snapHints) {
        snapHints = new THREE.Group();
        snapHints.name = 'snap-hints';
        scene.add(snapHints);
    }
    const along = alongSize(mod);
    const depth = Math.max(30, Number(mod.glbDepth) || Number(state.depth) || 60);
    const front = wallLimit();
    const wall = wallName(mod);
    const height = Math.max(40, moduleHeight(mod) * 0.35);
    const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(1, 1, 1),
        new THREE.MeshBasicMaterial({ color, transparent: true, opacity, depthWrite: false })
    );
    mesh.scale.set(wall === 'front' ? along : depth, height, wall === 'front' ? depth : along);
    const nudge = depthNudge(mod);
    if (wall === 'front') mesh.position.set(at + along / 2, height / 2 + 2, nudge + depth / 2);
    else if (wall === 'left') mesh.position.set(nudge + depth / 2, height / 2 + 2, at + along / 2);
    else mesh.position.set(front - nudge - depth / 2, height / 2 + 2, at + along / 2);
    snapHints.add(mesh);
}

function updateSnapHints(mod) {
    clearSnapHints();
    if (!mod || (dragId !== mod.id && state.modules.includes(mod))) return;
    const raw = Number(moduleDrag?.rawOffset ?? mod.offset) || 0;
    const locked = Number(mod.offset) || 0;
    const slots = snapSlotsFor(mod);
    const snapped = slots.find((s) => Math.abs(s - locked) < 0.45);
    if (snapped != null) placeSnapHint(mod, snapped, 0x1f6b4a, 0.42);
    const home = Number(moduleDrag?.origin ?? locked);
    const dir = raw > home + 0.5 ? 1 : raw < home - 0.5 ? -1 : 0;
    const candidates = slots
        .filter((s) => Math.abs(s - locked) > 0.5)
        .filter((s) => Math.abs(s - raw) < SNAP_WIZARD_CM)
        .sort((a, b) => {
            if (dir > 0) return a - b;
            if (dir < 0) return b - a;
            return Math.abs(a - raw) - Math.abs(b - raw);
        });
    candidates.slice(0, 2).forEach((s) => placeSnapHint(mod, s, 0x3b82f6, 0.28));
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
    const raw = Math.max(0, Math.min(Number(pick.offset) || 0, Math.max(0, limit - along)));
    const at = magneticSnapOffset(mod, raw);
    pick.offset = at;
    dropGuide.scale.set(pick.wall === 'front' ? along : depth, 8, pick.wall === 'front' ? depth : along);
    if (pick.wall === 'front') dropGuide.position.set(at + along / 2, 4, depth / 2);
    else if (pick.wall === 'left') dropGuide.position.set(depth / 2, 4, at + along / 2);
    else dropGuide.position.set(front - depth / 2, 4, at + along / 2);
    dropGuide.material.color.set(ok ? 0x1f6b4a : 0x9f2d2d);
    dropGuide.visible = true;
}

function nearerSide(pick) {
    return Number(pick?.x) < wallLimit() / 2 ? 'left' : 'right';
}

function dragTarget(event, mod) {
    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
    raycaster.setFromCamera(pointer, camera);
    const along = alongSize(mod);
    const front = wallLimit();
    const hits = [];
    [['front', room.back], ['left', room.left], ['right', room.right]].forEach(([wall, mesh]) => {
        if (!mesh) return;
        const hit = raycaster.intersectObject(mesh, true)[0];
        if (!hit) return;
        const point = hit.point;
        hits.push({
            wall,
            offset: wall === 'front' ? point.x - along / 2 : point.z - along / 2,
            distance: hit.distance,
            x: point.x,
            y: point.y,
            z: point.z,
        });
    });
    const floorMeshHit = room.floor ? raycaster.intersectObject(room.floor, true)[0] : null;
    hits.sort((a, b) => a.distance - b.distance);
    if (hits.length && (!floorMeshHit || hits[0].distance <= floorMeshHit.distance)) return hits[0];
    if (!floorMeshHit && !raycaster.ray.intersectPlane(floorPlane, floorHit)) return null;
    const point = floorMeshHit ? floorMeshHit.point : floorHit;
    const choices = [
        { wall: 'front', dist: Math.abs(point.z), offset: point.x - along / 2 },
        { wall: 'left', dist: Math.abs(point.x), offset: point.z - along / 2 },
        { wall: 'right', dist: Math.abs(point.x - front), offset: point.z - along / 2 },
    ];
    const pick = choices.sort((a, b) => a.dist - b.dist)[0];
    return { ...pick, distance: floorMeshHit?.distance || 0, x: point.x, y: point.y, z: point.z };
}

function applyWallDrop(mod, pick) {
    const previous = { wall: mod.wall, offset: mod.offset, cornerSide: mod.cornerSide };
    const fitsWall = (wall, offset) => {
        mod.wall = wall;
        mod.offset = offset;
        const modules = state.modules.includes(mod) ? state.modules : [...state.modules, mod];
        return Boolean(layout(modules));
    };
    if (fitsWall(pick.wall, pick.offset)) return true;
    const side = pick.wall === 'front' ? nearerSide(pick) : '';
    if (side && fitsWall(side, Math.max(0, Number(pick.z) - alongSize(mod) / 2))) return true;
    Object.assign(mod, previous);
    return false;
}

function wallFullMessage(wall) {
    const name = wall === 'left' ? 'Sol duvarda' : wall === 'right' ? 'Sağ duvarda' : 'Ön duvarda';
    if (roomOpenings(wall).length) return `${name} yer yok — kapı/pencere boşluğu veya dolu sıra.`;
    return `${name} bu dolap için yer kalmadı.`;
}

function initStudioLayout() {
    const mobile = isPhone();
    document.body.classList.remove('show-library', 'show-custom');
    el('library')?.classList.remove('is-open');
    if (mobile) {
        el('inspector')?.classList.remove('is-open');
    } else {
        document.body.classList.add('show-custom');
        el('inspector')?.classList.add('is-open');
    }
}

let roomPlanEditorMode = 'openplan';

function hasLocalOpenPlan() {
    const src = String(window.WARDROBE?.openplanSrc || '').trim();
    return Boolean(src) && !/openplan3d\.com/i.test(src);
}

function preferredRoomPlanMode() {
    return hasLocalOpenPlan() ? 'openplan' : 'sketch';
}

function openPlanEmbedUrl() {
    const src = String(window.WARDROBE?.openplanSrc || '').trim().replace(/\/index\.html$/i, '');
    if (!src) return '';
    const base = src.endsWith('/') ? src : `${src}/`;
    // php built-in: /openplan/editor dosya gibi 404 verir → ana sayfa + embed=1.
    return `${base}?embed=1`;
}

function ensureOpenPlanFrame(frame) {
    if (!frame) return;
    if (!hasLocalOpenPlan()) return;
    const target = openPlanEmbedUrl();
    if (!target) return;
    if (frame.dataset.loaded === '1' && /embed=1/.test(frame.src || '')) return;
    frame.src = target;
    frame.dataset.loaded = '1';
    frame.addEventListener('load', () => {
        try {
            frame.contentWindow?.focus();
            frame.contentWindow?.postMessage({ source: 'meemare', action: 'openEditor' }, '*');
        } catch {
            /* cross-origin */
        }
    }, { once: true });
}

function openPlanFrameFullscreen() {
    const node = el('room-plan-panel') || el('studio-plan-first');
    if (!node) return;
    const req = node.requestFullscreen || node.webkitRequestFullscreen;
    if (req) void req.call(node);
}

function isTypingTarget(target) {
    if (!target) return false;
    const tag = String(target.tagName || '').toLowerCase();
    return tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable;
}

function syncSpacePanControls() {
    if (!controls) return;
    if (spacePanHeld && !walkMode && planMode !== '2d') {
        controls.enabled = true;
        controls.enablePan = true;
        controls.mouseButtons.LEFT = THREE.MOUSE.PAN;
        if (renderer?.domElement) renderer.domElement.style.cursor = 'grab';
        document.body.classList.add('space-pan');
    } else {
        controls.mouseButtons.LEFT = THREE.MOUSE.ROTATE;
        if (renderer?.domElement && !walkMode) renderer.domElement.style.cursor = '';
        document.body.classList.remove('space-pan');
    }
}

function setSpacePan(on) {
    const next = Boolean(on) && !walkMode && planMode !== '2d'
        && !document.body.classList.contains('show-room-plan')
        && !document.body.classList.contains('studio-plan-first');
    if (spacePanHeld === next) {
        if (next) syncSpacePanControls();
        return;
    }
    spacePanHeld = next;
    if (spacePanHeld) {
        // Modül sürüklerken Space’e basılırsa sürüklemeyi bırak, ortama pan geç.
        moduleDrag = null;
        dragId = null;
        pointerPress = null;
        pointerDown = null;
        clearDragGhost?.();
        clearSnapHints?.();
    }
    syncSpacePanControls();
}

function bindSpacePan(canvas) {
    if (window.__meemareSpacePan) return;
    window.__meemareSpacePan = true;
    window.addEventListener('keydown', (event) => {
        if (event.code !== 'Space' && event.key !== ' ') return;
        if (event.repeat) {
            event.preventDefault();
            return;
        }
        if (isTypingTarget(event.target)) return;
        if (walkMode || planMode === '2d') return;
        if (document.body.classList.contains('show-room-plan') || document.body.classList.contains('studio-plan-first')) return;
        event.preventDefault();
        setSpacePan(true);
    });
    window.addEventListener('keyup', (event) => {
        if (event.code !== 'Space' && event.key !== ' ') return;
        if (!spacePanHeld) return;
        event.preventDefault();
        setSpacePan(false);
        if (canvas) canvas.style.cursor = '';
    });
    window.addEventListener('blur', () => setSpacePan(false));
    canvas?.addEventListener('pointerdown', () => {
        if (spacePanHeld && canvas) canvas.style.cursor = 'grabbing';
    });
    canvas?.addEventListener('pointerup', () => {
        if (spacePanHeld && canvas) canvas.style.cursor = 'grab';
    });
}

function bindWalkKeys() {
    if (window.__meemareWalkKeys) return;
    window.__meemareWalkKeys = true;
    const setKey = (event, on) => {
        if (!walkMode) return;
        if (isTypingTarget(event.target)) return;
        const map = {
            KeyW: 'forward',
            ArrowUp: 'forward',
            KeyS: 'back',
            ArrowDown: 'back',
            KeyA: 'left',
            ArrowLeft: 'left',
            KeyD: 'right',
            ArrowRight: 'right',
        };
        const key = map[event.code];
        if (key) walkKeys[key] = on;
        if (event.code === 'ShiftLeft' || event.code === 'ShiftRight') walkSprint = on;
    };
    window.addEventListener('keydown', (e) => setKey(e, true));
    window.addEventListener('keyup', (e) => setKey(e, false));
}

function clampWalkCamera() {
    if (!camera) return;
    const w = wallLimit();
    const d = roomDepthLimit();
    const pad = 14;
    camera.position.x = clamp(camera.position.x, pad, Math.max(pad + 1, w - pad));
    camera.position.z = clamp(camera.position.z, pad, Math.max(pad + 1, d - pad));
    camera.position.y = WALK_EYE_CM;
}

function placeWalkCamera() {
    if (!camera) return;
    const w = wallLimit();
    const d = roomDepthLimit();
    camera.position.set(w * 0.48, WALK_EYE_CM, d * 0.55);
    camera.lookAt(w * 0.5, WALK_EYE_CM, d * 0.15);
    camera.rotation.order = 'YXZ';
}

function setWalkMode(on) {
    const next = Boolean(on);
    if (walkMode === next) return;
    walkMode = next;
    document.body.classList.toggle('walk-mode', walkMode);
    document.querySelectorAll('#view-bar [data-view]').forEach((btn) => {
        btn.classList.toggle('is-on', walkMode ? btn.dataset.view === 'walkthrough' : btn.dataset.view === 'perspective');
    });
    if (!walkMode) {
        walkLock?.unlock();
        document.body.classList.remove('walk-locked');
        Object.keys(walkKeys).forEach((k) => { walkKeys[k] = false; });
        walkSprint = false;
        if (controls) controls.enabled = true;
        setCameraView('perspective');
        return;
    }
    setSpacePan(false);
    if (planMode === '2d') setPlanMode('3d');
    placeWalkCamera();
    if (controls) controls.enabled = false;
    if (!walkLock && camera && renderer) {
        walkLock = new PointerLockControls(camera, renderer.domElement);
        walkLock.addEventListener('lock', () => {
            document.body.classList.add('walk-locked');
            toast('WASD ile gezin · Shift koş · Esc çık');
        });
        walkLock.addEventListener('unlock', () => document.body.classList.remove('walk-locked'));
    }
    toast('Tuvalde tıklayın — oda içi gezinti (plan sayfası gibi).');
}

function updateWalk(dt) {
    if (!walkMode || !walkLock?.isLocked) return;
    const speed = (walkSprint ? WALK_SPEED * WALK_SPRINT : WALK_SPEED) * dt;
    if (walkKeys.forward) walkLock.moveForward(speed);
    if (walkKeys.back) walkLock.moveForward(-speed);
    if (walkKeys.left) walkLock.moveRight(-speed);
    if (walkKeys.right) walkLock.moveRight(speed);
    clampWalkCamera();
}

function setRoomPlanEditorMode(mode) {
    const local = hasLocalOpenPlan();
    roomPlanEditorMode = mode === 'openplan' && local ? 'openplan' : 'sketch';
    el('room-plan-openplan')?.classList.toggle('hidden', roomPlanEditorMode !== 'openplan');
    el('room-plan-sketch-wrap')?.classList.toggle('hidden', roomPlanEditorMode !== 'sketch');
    document.body.classList.toggle('room-plan-openplan-mode', roomPlanEditorMode === 'openplan');
    el('room-plan-mode')?.classList.toggle('hidden', !local);
    document.querySelectorAll('[data-room-plan-mode]').forEach((btn) => {
        btn.classList.toggle('is-on', btn.dataset.roomPlanMode === roomPlanEditorMode);
        if (btn.dataset.roomPlanMode === 'openplan') btn.classList.toggle('hidden', !local);
    });
    if (roomPlanEditorMode === 'openplan') ensureOpenPlanFrame(el('openplan-frame'));
    else requestAnimationFrame(() => roomSketchOnOpen(state));
}

function applyRoomPlanFromHeader() {
    if (roomPlanEditorMode === 'openplan' && requestOpenPlanRoomExport()) {
        setTimeout(() => {
            if (applyHandoffFromStorage()) return;
            const form = el('studio-plan-handoff');
            if (!form) return;
            const front = Number(form.onduvar?.value);
            const left = Number(form.solduvar?.value);
            const right = Number(form.sagduvar?.value);
            const height = Number(form.odaboy?.value || state.roomHeight);
            window.MeemareStudioApplyWalls(
                { frontCm: front, leftCm: left, rightCm: right, heightCm: height },
                { openings: state.roomSketch?.openings, sketchWalls: state.roomSketch?.walls },
            );
        }, 450);
        return;
    }
    roomSketchBeforeHandoff(state);
    const form = el('studio-plan-handoff');
    const front = Number(form?.onduvar?.value);
    const left = Number(form?.solduvar?.value);
    const right = Number(form?.sagduvar?.value);
    const height = Number(form?.odaboy?.value || state.roomHeight);
    if (!window.MeemareStudioApplyWalls(
        { frontCm: front, leftCm: left, rightCm: right, heightCm: height },
        { openings: state.roomSketch?.openings, sketchWalls: state.roomSketch?.walls },
    )) {
        toast('Önce oda ölçüsünü tanımlayın.');
    }
}

function closePlanStudioStep(applyHandoff = true) {
    if (!document.body.classList.contains('studio-plan-first')) return;
    if (applyHandoff) {
        roomSketchBeforeHandoff(state);
        applyHandoffFromStorage();
    }
    document.body.classList.remove('studio-plan-first');
    toggleRoomPlan(false);
    initStudioLayout();
    showModuleLibrary();
    render();
    frameCamera();
    toast(state.modules?.length
        ? '3D düzenleme. Odayı değiştirmek için Oda.'
        : 'Soldan modül ekleyin.');
}

function requestOpenPlanRoomExport() {
    const frame = el('openplan-frame');
    if (!frame?.contentWindow || roomPlanEditorMode !== 'openplan') return false;
    try {
        frame.contentWindow.postMessage({ source: 'meemare', action: 'exportRoom' }, '*');
        return true;
    } catch {
        return false;
    }
}

function finishPlanFirstStep() {
    roomSketchBeforeHandoff(state);
    const handoff = el('studio-plan-handoff');
    const form = el('studio-plan-first-handoff') || handoff;
    if (handoff && form && form !== handoff) {
        ['onduvar', 'solduvar', 'sagduvar', 'odaboy'].forEach((name) => {
            if (form[name] && handoff[name]) form[name].value = handoff[name].value;
        });
    }
    // OpenPlan açıksa önce kapı/pencere dahil export iste (meemare-plan-bridge uygular).
    if (requestOpenPlanRoomExport()) {
        let settled = false;
        const markSettled = () => { settled = true; };
        const onMsg = (event) => {
            const data = event.data;
            if (!data || data.source !== 'openplan3d' || !data.export) return;
            markSettled();
            window.removeEventListener('message', onMsg);
        };
        window.addEventListener('message', onMsg);
        setTimeout(() => {
            window.removeEventListener('message', onMsg);
            if (settled || applyHandoffFromStorage()) return;
            if (!form) {
                closePlanStudioStep(false);
                return;
            }
            const front = Number(form.onduvar?.value || handoff?.onduvar?.value);
            const left = Number(form.solduvar?.value || handoff?.solduvar?.value);
            const right = Number(form.sagduvar?.value || handoff?.sagduvar?.value);
            const height = Number(form.odaboy?.value || handoff?.odaboy?.value || state.roomHeight);
            if (!window.MeemareStudioApplyWalls(
                { frontCm: front, leftCm: left, rightCm: right, heightCm: height },
                { openings: state.roomSketch?.openings, sketchWalls: state.roomSketch?.walls },
            )) {
                closePlanStudioStep(false);
            }
        }, 450);
        return;
    }
    if (applyHandoffFromStorage()) return;
    if (!form) {
        closePlanStudioStep(false);
        return;
    }
    const front = Number(form.onduvar?.value || handoff?.onduvar?.value);
    const left = Number(form.solduvar?.value || handoff?.solduvar?.value);
    const right = Number(form.sagduvar?.value || handoff?.sagduvar?.value);
    const height = Number(form.odaboy?.value || handoff?.odaboy?.value || state.roomHeight);
    if (!window.MeemareStudioApplyWalls(
        { frontCm: front, leftCm: left, rightCm: right, heightCm: height },
        { openings: state.roomSketch?.openings, sketchWalls: state.roomSketch?.walls },
    )) {
        closePlanStudioStep(false);
    }
}

window.MeemareStudioClosePlan = () => closePlanStudioStep(true);

function fillRoomFormsFromState() {
    [el('studio-plan-handoff'), el('studio-plan-first-handoff')].forEach((form) => {
        if (!form) return;
        if (form.onduvar) form.onduvar.value = state.wallWidth;
        if (form.solduvar) form.solduvar.value = state.leftWall;
        if (form.sagduvar) form.sagduvar.value = state.rightWall;
        if (form.odaboy) form.odaboy.value = state.roomHeight;
    });
}

function openRoomEditor() {
    fillRoomFormsFromState();
    document.body.classList.add('studio-plan-first', 'show-room-plan');
    setRoomPlanEditorMode('sketch');
    requestAnimationFrame(() => {
        toast('Kapı veya pencereyi seçin, boyutunu değiştirin. Bitince 3D düzenlemeye geçin.');
    });
}

function maybeOpenPlanStudio(params) {
    if (!params || params.get('plan') !== '1') return;
    openRoomEditor();
    const next = new URLSearchParams(params);
    next.delete('plan');
    const qs = next.toString();
    history.replaceState(null, '', `${location.pathname}${qs ? `?${qs}` : ''}`);
}

function maybeOpenRoomSketcher(params) {
    if (!params || params.get('oda') !== '1') return;
    toggleRoomPlan(true);
    const next = new URLSearchParams(params);
    next.delete('oda');
    const qs = next.toString();
    history.replaceState(null, '', `${location.pathname}${qs ? `?${qs}` : ''}`);
}

function wallSpan(mod) {
    const wall = wallName(mod);
    return wall === 'front' ? wallLimit() : sideLimit(wall);
}

function wallLimits() {
    return { front: wallLimit(), left: sideLimit('left'), right: sideLimit('right') };
}

function wallOffsetMax(mod) {
    return sharedWallOffsetMax(mod, state.modules, wallLimits());
}

function wallOffsetMin(mod) {
    return sharedWallOffsetMin(mod, state.modules);
}

function fitsModuleSlot(mod, at) {
    const along = alongSize(mod);
    const wall = wallName(mod);
    const min = wallOffsetMin(mod);
    const max = wallOffsetMax(mod);
    if (at < min - 0.05 || at + along > max + along + 0.05) return false;
    if (hitsOpening(wall, at, along)) return false;
    return !rowMates(mod).some((other) => {
        const o0 = Number(other.offset) || 0;
        const o1 = o0 + alongSize(other);
        return at < o1 - 0.35 && at + along > o0 + 0.35;
    });
}

function snapSlotsFor(mod) {
    const along = alongSize(mod);
    const min = wallOffsetMin(mod);
    const max = wallOffsetMax(mod);
    const edges = new Set([min, max]);
    rowMates(mod).forEach((mate) => {
        const o = Number(mate.offset) || 0;
        const w = alongSize(mate);
        edges.add(o);
        edges.add(o + w);
        edges.add(o - along);
        edges.add(o + w);
    });
    return [...edges]
        .map((s) => Math.round(s * 10) / 10)
        .filter((s) => fitsModuleSlot(mod, s))
        .sort((a, b) => a - b);
}

function magneticSnapOffset(mod, rawAt) {
    const along = alongSize(mod);
    const at = clamp(Number(rawAt) || 0, wallOffsetMin(mod), wallOffsetMax(mod));
    const placing = !state.modules.some((item) => item.id === mod.id);
    const dragging = dragId === mod.id;
    if (!placing && !dragging) return at;
    const slots = snapSlotsFor(mod);
    if (!slots.length) return at;
    const nearest = slots.reduce((best, s) => (!best || Math.abs(s - at) < Math.abs(best - at) ? s : best), slots[0]);
    if (Math.abs(nearest - at) <= SNAP_PICK_CM) return nearest;
    const home = Number(moduleDrag?.origin ?? mod.offset) || nearest;
    const dir = at > home + 0.8 ? 1 : at < home - 0.8 ? -1 : 0;
    if (dir > 0) {
        const ahead = slots.filter((s) => s >= at - 6).sort((a, b) => a - b);
        const target = ahead.find((s) => s >= at - 6) ?? ahead[0];
        if (target != null && Math.abs(target - at) < SNAP_WIZARD_CM) return target;
    } else if (dir < 0) {
        const back = slots.filter((s) => s <= at + 6).sort((a, b) => b - a);
        const target = back.find((s) => s <= at + 6) ?? back[0];
        if (target != null && Math.abs(target - at) < SNAP_WIZARD_CM) return target;
    }
    return at;
}

function rowMates(mod) {
    return state.modules
        .filter((item) => item !== mod && item.kind !== 'corner' && item.kind !== 'end' && wallName(item) === wallName(mod))
        .sort((a, b) => (Number(a.offset) || 0) - (Number(b.offset) || 0));
}

function slideModule(mod, rawOffset) {
    if (!mod || mod.kind === 'corner' || mod.kind === 'end') return;
    const along = alongSize(mod);
    const at = clamp(Number(rawOffset) || 0, wallOffsetMin(mod), wallOffsetMax(mod));
    const home = Number(mod.offset) || 0;
    const hold = moduleDrag?.anchor;
    if (hold && hold.id === mod.id) {
        const caught = hold.dir > 0 ? at >= hold.at - 0.5 : at <= hold.at + 0.5;
        const backed = hold.dir > 0 ? at < hold.at - along * (1 - PASS_RATIO) : at > hold.at + along * (1 - PASS_RATIO);
        if (!caught && !backed) {
            mod.offset = hold.at;
            return;
        }
        moduleDrag.anchor = null;
    }
    if (Math.abs(at - home) < 1) {
        mod.offset = magneticSnapOffset(mod, at);
        if (moduleDrag) moduleDrag.rawOffset = at;
        return;
    }
    if (moduleDrag && moduleDrag.origin == null) moduleDrag.origin = home;
    const mates = rowMates(mod);
    if (at > home + 0.5) {
        const next = mates.find((item) => {
            const start = Number(item.offset) || 0;
            const end = start + alongSize(item);
            return end > home + 0.5 && start >= home - along;
        });
        if (next) {
            const nextAlong = alongSize(next);
            const line = (Number(next.offset) || 0) + nextAlong * PASS_RATIO;
            if (at + along < line) {
                mod.offset = magneticSnapOffset(mod, at);
                if (moduleDrag) moduleDrag.rawOffset = at;
                return;
            }
            next.offset = home;
            mod.offset = home + nextAlong;
            if (moduleDrag) {
                moduleDrag.anchor = { id: mod.id, at: mod.offset, dir: 1 };
                moduleDrag.origin = mod.offset;
            }
            return;
        }
    } else if (at < home - 0.5) {
        const prev = [...mates].reverse().find((item) => {
            const start = Number(item.offset) || 0;
            const end = start + alongSize(item);
            return start < home + 0.5 && end > home - along;
        });
        if (prev) {
            const prevAlong = alongSize(prev);
            const prevStart = Number(prev.offset) || 0;
            const line = prevStart + prevAlong * (1 - PASS_RATIO);
            if (at > line) {
                mod.offset = magneticSnapOffset(mod, at);
                if (moduleDrag) moduleDrag.rawOffset = at;
                return;
            }
            prev.offset = prevStart + along;
            mod.offset = prevStart;
            if (moduleDrag) {
                moduleDrag.anchor = { id: mod.id, at: mod.offset, dir: -1 };
                moduleDrag.origin = mod.offset;
            }
            return;
        }
    }
    mod.offset = magneticSnapOffset(mod, at);
    if (moduleDrag) moduleDrag.rawOffset = at;
}

function settleOverlaps(mod) {
    if (!mod || mod.kind === 'corner' || mod.kind === 'end') return;
    const along = alongSize(mod);
    const min = wallOffsetMin(mod);
    const max = wallOffsetMax(mod);
    for (let pass = 0; pass < 4; pass += 1) {
        let at = clamp(Number(mod.offset) || 0, min, max);
        const hit = rowMates(mod).find((other) => {
            const o0 = Number(other.offset) || 0;
            const o1 = o0 + alongSize(other);
            return at < o1 - 0.4 && at + along > o0 + 0.4;
        });
        if (!hit) {
            mod.offset = at;
            return;
        }
        const o0 = Number(hit.offset) || 0;
        const o1 = o0 + alongSize(hit);
        const origin = moduleDrag?.origin;
        at = origin != null && origin >= o0 - 0.5 ? o1 : o0 - along;
        mod.offset = clamp(at, min, max);
    }
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
        if (!layout(state.modules)) {
            Object.assign(mod, previous);
            return;
        }
    } else {
        const remembered = state.modules.map((item) => ({ id: item.id, wall: item.wall, offset: item.offset, cornerSide: item.cornerSide }));
        mod.wall = pick.wall === 'left' || pick.wall === 'right' ? pick.wall : 'front';
        slideModule(mod, pick.offset);
        if (!layout(state.modules)) {
            remembered.forEach((row) => {
                const item = state.modules.find((entry) => entry.id === row.id);
                if (item) Object.assign(item, row);
            });
            Object.assign(mod, previous);
            return;
        }
    }
    selectedId = id;
    if (!shiftCabinets()) rebuild();
    showDragGhost(mod);
    updateSnapHints(mod);
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
    preferCatalogWood(mod, { force: true });
    mod.kind = corner ? 'corner' : 'wardrobe';
    mod.width = recipe?.kind === 'corner' ? recipe.width : width;
    mod.door = 'none';
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
    mod.door = 'none';
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
    focusSelectedModule();
    closeModuleLibrary();
    if (!isPhone()) {
        document.body.classList.add('show-custom');
        el('inspector')?.classList.add('is-open');
    }
}

function liftStilesAboveDrawers(root) {
    root.updateMatrixWorld(true);
    const meshes = [];
    root.traverse((child) => { if (child.isMesh) meshes.push(child); });
    const drawers = meshes.filter((mesh) => /KUTU/.test(mesh.name || ''));
    const stiles = meshes.filter((mesh) => /DIKME/.test(mesh.name || ''));
    if (!drawers.length || !stiles.length) return 0;
    const worldBox = (mesh) => new THREE.Box3().setFromObject(mesh);
    let lifted = 0;
    stiles.forEach((stile) => {
        const stileBox = worldBox(stile);
        let top = null;
        drawers.forEach((drawer) => {
            const drawerBox = worldBox(drawer);
            const overlapX = Math.min(stileBox.max.x, drawerBox.max.x) - Math.max(stileBox.min.x, drawerBox.min.x);
            const overlapY = Math.min(stileBox.max.y, drawerBox.max.y) - Math.max(stileBox.min.y, drawerBox.min.y);
            const overlapZ = Math.min(stileBox.max.z, drawerBox.max.z) - Math.max(stileBox.min.z, drawerBox.min.z);
            if (overlapX > 0.004 && overlapY > 0.004 && overlapZ > 0.004) {
                top = Math.max(top ?? drawerBox.max.y, drawerBox.max.y);
            }
        });
        if (top == null || top >= stileBox.max.y - 0.02) return;
        const newMin = top + 0.004;
        const worldMin = stileBox.min.y;
        const worldSpan = stileBox.max.y - worldMin;
        const keep = stileBox.max.y - newMin;
        if (worldSpan < 0.05 || keep < 0.05) return;
        const inverse = stile.matrixWorld.clone().invert();
        const position = stile.geometry.attributes.position;
        const vertex = new THREE.Vector3();
        for (let index = 0; index < position.count; index += 1) {
            vertex.fromBufferAttribute(position, index).applyMatrix4(stile.matrixWorld);
            const t = (vertex.y - worldMin) / worldSpan;
            vertex.y = newMin + t * keep;
            vertex.applyMatrix4(inverse);
            position.setXYZ(index, vertex.x, vertex.y, vertex.z);
        }
        position.needsUpdate = true;
        stile.geometry.computeBoundingBox();
        stile.geometry.computeBoundingSphere();
        lifted += 1;
    });
    return lifted;
}

async function ensureGlb(item) {
    if (glbTemplates.has(item.id)) return glbTemplates.get(item.id);
    if (glbFailed.has(item.id)) throw new Error(item.id);
    const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
    const loader = new GLTFLoader();
    try {
        // Parametrik GLB geri yüklendi; eski bozuk vm_v4 cache’ini kır.
        const path = String(item.modelPath || '');
        const url = path.includes('?') ? `${path}&v=param3` : `${path}?v=param3`;
        const gltf = await loader.loadAsync(url);
        liftStilesAboveDrawers(gltf.scene);
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
        const ok = applyWallDrop(probe, pick);
        showDropGuide(probe, { ...pick, wall: probe.wall, offset: probe.offset }, ok);
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
    if (!applyWallDrop(mod, pick)) {
        toast(wallFullMessage(pick.wall));
        return false;
    }
    mod.offset = magneticSnapOffset(mod, Number(mod.offset) || 0);
    const modules = state.modules.includes(mod) ? state.modules : [...state.modules, mod];
    if (!layout(modules)) {
        toast(wallFullMessage(pick.wall));
        return false;
    }
    return true;
}

async function addGlbModule(id, dropEvent) {
    const item = glbCatalog.find((entry) => entry.id === id);
    if (!item) return;
    toggleRoomPlan(false);
    if (planMode === '2d') setPlanMode('3d');
    noteDrop({ phase: 'load', id, cached: glbTemplates.has(item.id) });
    try {
        await ensureGlb(item);
        const mod = moduleFromCatalogItem(item);
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
            toast(wallFullMessage(mod.wall));
            return;
        }
        noteDrop({ id: mod.glb || mod.name, result: 'accepted', wall: mod.wall, offset: Math.round(Number(mod.offset) || 0), count: state.modules.length + 1 });
        commit(() => {
            state.modules.push(mod);
            selectedId = mod.id;
        });
        focusSelectedModule();
        snapDoors();
        closeModuleLibrary();
        if (!isPhone()) {
            document.body.classList.add('show-custom');
            el('inspector')?.classList.add('is-open');
        } else {
            document.body.classList.remove('show-custom');
        }
    } catch (error) {
        noteDrop({ phase: 'error', id, message: String(error?.stack || error).slice(0, 500) });
        toast('Bu modülün modeli açılamadı. Sahne duruyor. Başka bir modül seçebilirsiniz.');
    }
}

function addCadModule(id, dropEvent) {
    const item = cadById(id);
    if (!item) return;
    const mod = createModule(item.width > 70 ? 'cift-raf' : 'tek-raf');
    preferCatalogWood(mod, { force: true });
    mod.cad = item.id;
    mod.name = item.name;
    mod.width = item.width;
    mod.height = item.height < 160 ? item.height : state.height;
    mod.door = 'none';
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
        toast(wallFullMessage(mod.wall));
        return;
    }
    noteDrop({ id: mod.glb || mod.name, result: 'accepted', wall: mod.wall, offset: Math.round(Number(mod.offset) || 0), count: state.modules.length + 1 });
    commit(() => {
        state.modules.push(mod);
        selectedId = mod.id;
    });
    focusSelectedModule();
    closeModuleLibrary();
    if (!isPhone()) {
        document.body.classList.add('show-custom');
        el('inspector')?.classList.add('is-open');
    }
}

function addProduct(productId, dropEvent) {
    const product = productById(productId);
    if (product.kind === 'corner' && state.modules.some((mod) => mod.kind === 'corner')) {
        toast('Köşe modülü bir kez konur.');
        return;
    }
    const mod = createModule(productId);
    preferCatalogWood(mod, { force: true });
    if (dropEvent && !placeDropped(mod, dropEvent)) return;
    if (!layout([...state.modules, mod])) {
        toast('Bu modül duvarın dışında kalıyor.');
        return;
    }
    commit(() => {
        state.modules.push(mod);
        selectedId = mod.id;
    });
    focusSelectedModule();
    closeModuleLibrary();
    if (isPhone()) document.body.classList.remove('show-custom');
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

function clearAllModules() {
    if (!state.modules.length) {
        toast('Sahnede dolap yok.');
        return;
    }
    if (!confirm('Tüm dolaplar kaldırılsın mı? Oda ölçüsü kalır.')) return;
    commit(() => {
        state.modules = [];
        selectedId = null;
        sceneFocus = 'room';
    });
    frameCamera();
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

let studioHdrPromise = null;

function studioHdri() {
    if (!studioHdrPromise) {
        studioHdrPromise = import('three/addons/loaders/RGBELoader.js').then(({ RGBELoader }) => new Promise((resolve, reject) => {
            new RGBELoader().load('/textures/wardrobe/studio_small_09_1k.hdr', (tex) => {
                tex.mapping = THREE.EquirectangularReflectionMapping;
                resolve(tex);
            }, undefined, reject);
        }));
    }
    return studioHdrPromise;
}

function studioReflectEnvironment() {
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = 512;
    const ctx = canvas.getContext('2d');
    const sky = ctx.createLinearGradient(0, 0, 0, 512);
    sky.addColorStop(0, '#fbf9f6');
    sky.addColorStop(0.42, '#e4ddd4');
    sky.addColorStop(1, '#b7b0a6');
    ctx.fillStyle = sky;
    ctx.fillRect(0, 0, 1024, 512);
    ctx.fillStyle = '#fffdf8';
    ctx.fillRect(70, 36, 190, 150);
    ctx.fillStyle = '#f4f0e8';
    ctx.fillRect(760, 64, 150, 230);
    ctx.fillStyle = '#d9d3c8';
    ctx.fillRect(400, 350, 280, 100);
    const tex = new THREE.CanvasTexture(canvas);
    tex.mapping = THREE.EquirectangularReflectionMapping;
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.needsUpdate = true;
    return tex;
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
    const wasAllCabinets = allCabinetsSelected;
    const measures = showMeasures;
        const exposure = renderer.toneMappingExposure;
        const envMap = scene.environment;
        const env = scene.environmentIntensity;
        const background = scene.background;
        const shot = {
            pos: camera.position.clone(),
            target: controls.target.clone(),
            fov: camera.fov,
            near: camera.near,
            far: camera.far,
            aspect: camera.aspect,
        };
    let key = null;
    let floorShot = null;
    let backdrop = null;
    let ledMat = null;
    let ledSaved = null;
    button.disabled = true;
    try {
        showMeasures = false;
        selectedId = null;
        allCabinetsSelected = false;
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
        const keepFrame = window.__traceReframe !== true;
        const maxEdge = Number(window.__ultraMax) > 400 ? Number(window.__ultraMax) : 1600;
        const forceW = Number(window.__traceW);
        const forceH = Number(window.__traceH);
        let capW;
        let capH;
        if (forceW > 200 && forceH > 200) {
            capW = Math.round(forceW);
            capH = Math.round(forceH);
        } else if (keepFrame) {
            const aspect = Math.max(0.25, shot.aspect || 1);
            if (aspect >= 1) {
                capW = Math.min(maxEdge, 1400);
                capH = Math.max(2, Math.round(capW / aspect));
            } else {
                capH = Math.min(maxEdge, 1400);
                capW = Math.max(2, Math.round(capH * aspect));
            }
        } else {
            const aspect = THREE.MathUtils.clamp(subjectSize.x / Math.max(subjectSize.y, 1), 0.82, 1.7);
            capW = Math.min(maxEdge, aspect >= 1 ? 1600 : Math.round(1280 * aspect));
            capH = aspect >= 1 ? Math.round(capW / aspect) : Math.min(maxEdge, 1280);
        }
        renderer.setPixelRatio(1);
        renderer.setSize(capW, capH, false);
        backdrop = renderBackdrop(subject);
        camera.aspect = capW / capH;
        if (keepFrame) {
            camera.fov = shot.fov;
            camera.near = shot.near;
            camera.far = shot.far;
            camera.position.copy(shot.pos);
            camera.up.set(0, 1, 0);
            camera.lookAt(shot.target);
            camera.updateProjectionMatrix();
            camera.updateMatrixWorld(true);
            controls.target.copy(shot.target);
        } else {
            frameRenderCamera(camera, subject);
            controls.target.copy(subject.getCenter(new THREE.Vector3()));
        }
        if (window.__traceKey === true) key = studioKeyLight(camera, subject);
        scene.background = new THREE.Color('#f4f1ec');
        renderer.toneMappingExposure = 1;
        prepareTraceTextures();
        renderer.setAnimationLoop(null);
        button.textContent = 'Işık ayarlanıyor';
        toast('Dolaba göre kadraj ve ışık kuruldu. Render birkaç dakika sürebilir.');
        const { WebGLPathTracer, GradientEquirectTexture } = await import('three-gpu-pathtracer');
        let glassGuard = 0;
        let meshCount = 0;
        let triangles = 0;
        const materialIds = new Set();
        scene.traverse((obj) => {
            if (!obj.isMesh) return;
            meshCount += 1;
            const geo = obj.geometry;
            if (geo?.index) triangles += geo.index.count / 3;
            else if (geo?.attributes?.position) triangles += geo.attributes.position.count / 3;
            const list = Array.isArray(obj.material) ? obj.material : [obj.material];
            list.forEach((mat) => {
                if (!mat) return;
                materialIds.add(mat.uuid);
                if ((mat.transmission || 0) > 0 && (mat.thickness !== 0 || mat.attenuationDistance !== Infinity)) {
                    mat.thickness = 0;
                    mat.attenuationDistance = Infinity;
                    mat.needsUpdate = true;
                    glassGuard += 1;
                }
            });
        });
        const ledMode = window.__traceLeds || 'soft';
        scene.traverse((obj) => {
            if (obj.isMesh && obj.name === 'LED_GLOW' && obj.material) ledMat = obj.material;
        });
        if (ledMat) ledSaved = { intensity: ledMat.emissiveIntensity, color: ledMat.emissive.clone() };
        if (ledMat && ledMode === 'off') {
            ledMat.emissive.set('#000000');
            ledMat.emissiveIntensity = 0;
        } else if (ledMat && ledMode === 'soft') {
            ledMat.emissive.set('#ffe6c2');
            ledMat.emissiveIntensity = 0.42;
        }
        let envTex;
        if (window.__traceEnv === 'hdri') envTex = await studioHdri();
        else if (window.__traceEnv === 'room') envTex = studioReflectEnvironment();
        else {
            const gradient = new GradientEquirectTexture();
            gradient.topColor.set('#fbfaf8');
            gradient.bottomColor.set('#d5cfc6');
            gradient.update();
            envTex = gradient;
        }
        scene.environment = envTex;
        scene.environmentIntensity = window.__traceEnv === 'hdri' ? 1 : window.__traceEnv === 'room' ? 0.9 : 0.7;
        const tracer = new WebGLPathTracer(renderer);
        const tilesX = 2;
        const tilesY = 2;
        const passes = Number(window.__ultraPasses) > 4 ? Number(window.__ultraPasses) : 18;
        tracer.tiles.set(tilesX, tilesY);
        tracer.bounces = Number(window.__traceBounces) > 0 ? Number(window.__traceBounces) : 4;
        tracer.transmissiveBounces = Number(window.__traceTransmissive) > 0 ? Number(window.__traceTransmissive) : 5;
        tracer.multipleImportanceSampling = true;
        tracer.filterGlossyFactor = 0.55;
        tracer.stableNoise = true;
        tracer.renderDelay = 0;
        tracer.fadeDuration = 0;
        tracer.minSamples = 1;
        tracer.renderScale = 1;
        const bvhStarted = performance.now();
        tracer.setScene(scene, camera);
        const bvhMs = Math.round(performance.now() - bvhStarted);
        const goal = passes * tilesX * tilesY;
        const budget = Number(window.__traceBudgetMs) > 10000 ? Number(window.__traceBudgetMs) : 150000;
        const started = performance.now();
        while (tracer.samples < goal && performance.now() - started < budget) {
            tracer.renderSample();
            const shown = Math.min(passes, Math.floor(tracer.samples / (tilesX * tilesY)));
            button.textContent = `Render ${shown}/${passes}`;
            await new Promise((resolve) => requestAnimationFrame(resolve));
        }
        button.textContent = 'Netleştiriliyor';
        await new Promise((resolve) => requestAnimationFrame(resolve));
        const denoiseMode = window.__denoiseMode || (window.__skipDenoise ? 'off' : 'gpu');
        if (denoiseMode === 'cpu') {
            denoiseRender(renderer.domElement);
        } else if (denoiseMode === 'gpu') {
            try {
                const { DenoiseMaterial } = await import('three-gpu-pathtracer');
                const { FullScreenQuad } = await import('three/addons/postprocessing/Pass.js');
                const denoise = new DenoiseMaterial();
                denoise.uniforms.map.value = tracer.target.texture;
                denoise.uniforms.sigma.value = Number(window.__denoiseSigma) > 0 ? Number(window.__denoiseSigma) : 2.4;
                denoise.uniforms.kSigma.value = 1.8;
                denoise.uniforms.threshold.value = 0.055;
                const quad = new FullScreenQuad(denoise);
                quad.render(renderer);
                quad.dispose();
                denoise.dispose();
            } catch (error) {
                console.error(error);
                denoiseRender(renderer.domElement);
            }
        }
        const href = renderer.domElement.toDataURL('image/png');
        window.__renderImage = href;
        const elapsed = Math.round(performance.now() - started);
        window.__renderStats = {
            ms: elapsed,
            samples: tracer.samples,
            passes,
            bounces: tracer.bounces,
            transmissiveBounces: tracer.transmissiveBounces,
            denoised: denoiseMode,
            denoiseSigma: !window.__skipDenoise ? (Number(window.__denoiseSigma) > 0 ? Number(window.__denoiseSigma) : 1.6) : 0,
            ledMode,
            keepFrame,
            env: window.__traceEnv || 'gradient',
            width: capW,
            height: capH,
            bvhMs,
            meshes: meshCount,
            triangles: Math.round(triangles),
            materials: materialIds.size,
            glassGuard,
            msPerSample: tracer.samples ? Math.round(elapsed / tracer.samples) : null,
            heap: performance.memory ? performance.memory.usedJSHeapSize : null,
        };
        renderer.setPixelRatio(ratio);
        renderer.setSize(size.x, size.y, false);
        const link = document.createElement('a');
        link.href = href;
        link.download = `${state.name || 'dolap'}-render.png`;
        link.click();
        toast(`Render indirildi. ${(elapsed / 1000).toFixed(0)} sn, ${capW}×${capH}, ${passes} geçiş.`);
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
        if (ledMat && ledSaved) {
            ledMat.emissive.copy(ledSaved.color);
            ledMat.emissiveIntensity = ledSaved.intensity;
        }
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
        allCabinetsSelected = wasAllCabinets;
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

function syncWallInputsFromState(form) {
    if (!form) return;
    if (form.onduvar) form.onduvar.value = state.wallWidth;
    if (form.solduvar) form.solduvar.value = state.leftWall;
    if (form.sagduvar) form.sagduvar.value = state.rightWall;
    if (form.odaboy) form.odaboy.value = state.roomHeight;
}

function bindStudioHandoffForm(formId, { sketchHandoff = false } = {}) {
    const form = el(formId);
    if (!form || form.dataset.bound) return;
    form.dataset.bound = '1';
    const apply = () => {
        if (sketchHandoff) roomSketchBeforeHandoff(state);
        const front = Number(form.onduvar?.value);
        const left = Number(form.solduvar?.value);
        const right = Number(form.sagduvar?.value);
        const height = Number(form.odaboy?.value);
        window.MeemareStudioApplyWalls({ frontCm: front, leftCm: left, rightCm: right, heightCm: height });
        ['wall-left', 'wall-front', 'wall-right', 'room-h'].forEach((id) => {
            const node = el(id);
            if (!node) return;
            if (id === 'wall-left') node.value = state.leftWall;
            if (id === 'wall-front') node.value = state.wallWidth;
            if (id === 'wall-right') node.value = state.rightWall;
            if (id === 'room-h') node.value = state.roomHeight;
        });
    };
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        apply();
    });
}

function bindStudioHandoff() {
    bindStudioHandoffForm('studio-plan-handoff', { sketchHandoff: true });
    bindStudioHandoffForm('studio-plan-first-handoff');
    const form = el('studio-plan-handoff');
    if (!form) return;
    el('studio-plan-json')?.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;
        try {
            const data = JSON.parse(await file.text());
            const floor = data?.floors?.[0];
            const walls = floor?.walls || data?.walls || [];
            const len = (w) => Math.hypot((w.end?.x ?? 0) - (w.start?.x ?? 0), (w.end?.y ?? 0) - (w.start?.y ?? 0));
            const lengths = walls.map(len).filter((n) => n > 40).sort((a, b) => b - a);
            if (!lengths.length) {
                toast('JSON içinde duvar bulunamadı.');
                return;
            }
            const front = Math.round(Math.max(lengths[0], lengths[1] || lengths[0]));
            const side = Math.round(lengths.find((n) => Math.abs(n - front) > 20) || lengths[1] || front);
            const height = Math.round(walls[0]?.height || state.roomHeight || 280);
            if (form.onduvar) form.onduvar.value = front;
            if (form.solduvar) form.solduvar.value = side;
            if (form.sagduvar) form.sagduvar.value = side;
            if (form.odaboy) form.odaboy.value = height;
            window.MeemareStudioApplyWalls({ frontCm: front, leftCm: side, rightCm: side, heightCm: height });
        } catch {
            toast('Geçersiz OpenPlan JSON.');
        }
        event.target.value = '';
    });
}

function bindChrome() {
    bindRoomSketch({
        ensureSketch,
        applyWallsCm,
        scheduleSave,
        toast,
        toggleRoomPlan,
        liveOpenings: (openings) => {
            const sketch = ensureSketch();
            sketch.openings = openings.map((item) => ({ ...item }));
            fitRoom();
            if (planMode === '2d') drawPlan();
            scheduleSave();
        },
    });
    bindQuickRail();
    bindStudioHandoff();
    onClick('btn-room-plan', () => openRoomEditor());
    onClick('btn-close-room-plan', () => toggleRoomPlan(false));
    onClick('room-plan-backdrop', () => toggleRoomPlan(false));
    onClick('btn-plan-to-3d', () => finishPlanFirstStep());
    onClick('btn-plan-skip', () => closePlanStudioStep(false));
    onClick('btn-plan-fullscreen', () => openPlanFrameFullscreen());
    onClick('btn-plan-toggle-handoff', () => {
        el('studio-plan-first-handoff')?.classList.toggle('is-collapsed');
    });
    onClick('btn-room-measures', () => {
        el('studio-plan-handoff')?.classList.toggle('is-open');
    });
    onClick('btn-apply-plan-header', () => applyRoomPlanFromHeader());
    onClick('btn-walk', () => setWalkMode(!walkMode));
    bindWalkKeys();
    document.getElementById('room-plan-mode')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-room-plan-mode]');
        if (!button) return;
        setRoomPlanEditorMode(button.dataset.roomPlanMode);
    });
    onClick('cam-orbit-left', () => orbitCamera(-0.22));
    onClick('cam-orbit-right', () => orbitCamera(0.22));
    on('project-name', 'change', (event) => {
        state.name = event.target.value || 'Dolabım';
        scheduleSave();
    });
    onClick('btn-new', () => { location.href = '/dolap'; });
    on('module-search', 'input', () => renderLibrary());
    onClick('btn-production', () => {
        renderProduction();
        el('production-modal')?.classList.remove('hidden');
    });
    onClick('production-close', () => el('production-modal')?.classList.add('hidden'));
    onClick('btn-cabinet', openCabinetStudio);
    onClick('btn-apply-room-handoff', () => {
        if (applyHandoffFromStorage()) return;
        toast('Kayıtlı plan ölçüsü yok. Önce plan sayfasında ölçü kaydedin veya ölçü girin.');
    });
    onClick('btn-demo-dressing', () => { void applyDressingRoomDemo(); });
    onClick('btn-export-json', downloadMeemareBundle);
    onClick('production-cabinet', openCabinetStudio);
    onClick('btn-undo', undo);
    onClick('btn-redo', redo);
    onClick('btn-save', saveDesign);
    onClick('btn-share', shareDesign);
    onClick('btn-saved', () => { location.href = '/dolap'; });
    onClick('saved-close', () => el('saved-modal')?.classList.add('hidden'));
    onClick('btn-measures', () => {
        showMeasures = !showMeasures;
        el('btn-measures')?.classList.toggle('is-on', showMeasures);
        rebuild();
    });
    onClick('btn-lights', () => {
        lightsOn = !lightsOn;
        syncLightsButton();
        rebuild();
    });
    onClick('btn-shot', () => { takeRender(); });
    on('view-bar', 'click', (event) => {
        const button = event.target.closest('[data-view]');
        if (!button) return;
        if (planMode === '2d') setPlanMode('3d');
        setCameraView(button.dataset.view);
    });
    on('plan-switch', 'click', (event) => {
        const button = event.target.closest('[data-plan]');
        if (button && (button.dataset.plan === '2d' || button.dataset.plan === '3d')) setPlanMode(button.dataset.plan);
    });
    onClick('btn-modules', () => showModuleLibrary());
    onClick('open-library-desk', () => showModuleLibrary());
    onClick('open-library-bar', () => showModuleLibrary());
    onClick('btn-guides', () => {
        guidesOn = !guidesOn;
        el('btn-guides')?.classList.toggle('is-on', guidesOn);
        syncGuides(layout(state.modules) || []);
    });
    onClick('act-forward', () => nudgeDepth(10));
    onClick('act-back', () => nudgeDepth(-10));
    onClick('btn-quote', () => {
        const form = el('quote-form');
        if (!form) return;
        form.name.value = state.customerName || '';
        form.phone.value = state.phone || '';
        form.note.value = state.note || '';
        el('quote-modal')?.classList.remove('hidden');
    });
    onClick('saved-search', () => searchProjects(el('saved-phone')?.value, el('saved-list'), () => el('saved-modal')?.classList.add('hidden')));
    onClick('resume-search', () => searchProjects('', el('resume-list'), () => el('customer-modal')?.classList.add('hidden')));
    on('customer-form', 'submit', async (event) => {
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
        if (!Number.isFinite(roomH) || roomH < ROOM_H_MIN || roomH > ROOM_H_MAX) {
            msg.textContent = `Oda yüksekliği ${ROOM_H_MIN} ile ${ROOM_H_MAX} cm arasında olmalı.`;
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
    onClick('quote-close', () => el('quote-modal')?.classList.add('hidden'));
    on('quote-form', 'submit', async (event) => {
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
    onClick('act-config', () => {
        if (!selected()) return;
        el('config-modal')?.classList.add('hidden');
        renderInspector();
        openPhoneSheet('doors');
    });
    onClick('config-close', () => el('config-modal')?.classList.add('hidden'));
    onClick('act-clone', cloneSelected);
    onClick('act-delete', deleteSelected);
    onClick('open-library', () => showModuleLibrary());
    onClick('open-material', () => {
        if (selected()) {
            inspectorActiveTab = 'ic';
            renderInspector();
        }
        openPhoneSheet('material');
    });
    onClick('open-doors', () => {
        if (selected()) {
            inspectorActiveTab = 'kapak';
            renderInspector();
        }
        openPhoneSheet('doors');
    });
    onClick('open-measures', () => {
        showMeasures = true;
        el('btn-measures')?.classList.add('is-on');
        if (selected()) {
            inspectorActiveTab = 'govde';
            renderInspector();
        }
        openPhoneSheet('measure');
    });
    onClick('open-project', () => openPhoneSheet('project'));
    onClick('close-library', () => {
        closeModuleLibrary();
        requestAnimationFrame(resize);
    });
    onClick('close-inspector', () => {
        if (isPhone()) openPhoneSheet(document.body.dataset.sheet || 'material');
        else {
            document.body.classList.remove('show-custom');
            customFor = selectedId;
            requestAnimationFrame(resize);
        }
    });
    onClick('close-project', () => openPhoneSheet('project'));
    onClick('sheet-new', () => { location.href = '/dolap'; });
    onClick('sheet-undo', () => el('btn-undo')?.click());
    onClick('sheet-redo', () => el('btn-redo')?.click());
    onClick('sheet-saved', () => { location.href = '/dolap'; });
    onClick('sheet-share', () => el('btn-share')?.click());
    onClick('sheet-quote', () => el('btn-quote')?.click());
    onClick('sheet-shot', () => el('btn-shot')?.click());
    on('selection-ui', 'pointerdown', (event) => event.stopPropagation());
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

/** Düz melamin kodları — texture yüklenene kadar anlık üretim rengi. */
const PLAIN_CODE_HEX = {
    D123: '#cfd1ce', // Açık Gri
    D141: '#aeb2b5', // Kül Gri
    D152: '#f2f2f0', // Opak Beyaz
    D129: '#f7f7f5', // Lake Beyaz
    D106: '#6e7276', // Koyu Gri
};

/** Düz üretim panelleri (gri/beyaz) — yalnızca üretim listesi / lake senaryosu. */
function productionPanelId() {
    const codes = ['D123', 'D141', 'D152', 'D129'];
    for (const code of codes) {
        const hit = materials.find((item) => item.group === 'melamin-kapli-panel' && item.code === code);
        if (hit?.id) return hit.id;
    }
    const plain = materials.find((item) => item.group === 'melamin-kapli-panel' && isPlainDecor(item));
    return plain?.id || materials[0]?.id || '';
}

/** Katalog varsayılanı: Kastamonu Naturel Meşe (A402), yoksa başka ahşap desen. */
function catalogWoodId() {
    const a402 = materials.find((item) => item.group === 'melamin-kapli-panel' && item.code === 'A402');
    if (a402?.id) return a402.id;
    const wood = materials.find((item) => item.group === 'melamin-kapli-panel' && !isPlainDecor(item) && /^A/.test(item.code || ''));
    return wood?.id || productionPanelId();
}

function isProductionPlainId(id) {
    const item = decor(id);
    return Boolean(item && PLAIN_CODE_HEX[item.code]);
}

function solidDoorColor() {
    return catalogWoodId();
}

function defaultDoorForCatalogItem(item) {
    const id = String(item?.id || '');
    const name = String(item?.name || '');
    if (/vitrin|cam kapak|gözlük|ayna/i.test(name)) return 'glass';
    if (/_(06|07)$/.test(id)) return 'glass';
    return 'solid';
}

function moduleFromCatalogItem(item, opts = {}) {
    const isTek = item.category === 'tek' || item.category === 'y26-tek';
    const mod = createModule(isTek ? 'tek-raf' : 'cift-raf');
    mod.glb = item.id;
    mod.glbWidth = item.width;
    mod.glbDepth = Math.round(item.depth * 100);
    mod.glbHeight = Math.round(item.height * 100);
    mod.name = item.name;
    mod.spec = item.spec;
    mod.width = Math.round(item.width * 100);
    mod.height = mod.glbHeight;
    mod.door = opts.door ?? defaultDoorForCatalogItem(item);
    if (mod.door === 'glass' || mod.door === 'mixed') {
        if (/bronz/i.test(String(item.name || ''))) {
            mod.glassId = 'cam-bronz';
            mod.aluminumId = 'al-bronz';
        } else {
            mod.glassId = 'cam-fum';
            mod.aluminumId = 'al-inox';
        }
    } else {
        mod.glassId = mod.glassId || state.glassId || 'cam-fum';
        mod.aluminumId = mod.aluminumId || state.aluminumId || 'al-inox';
    }
    preferCatalogWood(mod, { force: true });
    mod.open = opts.open ?? 0;
    mod.kind = item.category === 'kose' ? 'corner' : 'wardrobe';
    mod.wall = opts.wall === 'left' || opts.wall === 'right' ? opts.wall : 'front';
    mod.offset = Number.isFinite(opts.offset) ? opts.offset : 10000;
    if (mod.kind === 'corner') {
        mod.cornerSide = opts.cornerSide === 'right' ? 'right' : 'left';
        mod.wall = 'front';
    }
    return mod;
}

async function applyDressingRoomDemo() {
    if (!glbCatalog.length) {
        try {
            await loadGlbCatalog();
            renderLibrary();
        } catch {
            toast('Modül kataloğu yüklenemedi.');
            return false;
        }
    }
    const plan = [
        { id: 'kose_2', cornerSide: 'left', door: 'solid' },
        { id: 'y26_cift_01', wall: 'front', door: 'solid' },
        { id: 'y26_cift_06', wall: 'front', door: 'glass' },
        { id: 'y26_tek_01', wall: 'left', door: 'solid' },
        { id: 'y26_tek_06', wall: 'left', door: 'glass' },
    ];
    const missing = plan.filter((entry) => !glbCatalog.some((item) => item.id === entry.id));
    if (missing.length) {
        toast(`Katalogda eksik modül: ${missing.map((m) => m.id).join(', ')}`);
        return false;
    }
    toast('Örnek L giyinme odası yükleniyor…');
    await Promise.all(plan.map(async (entry) => {
        const item = glbCatalog.find((row) => row.id === entry.id);
        try {
            await ensureGlb(item);
        } catch {
            glbFailed.add(entry.id);
        }
    }));
    applyWallsCm(320, 300, 280, 280);
    const modules = plan.map((entry) => {
        const item = glbCatalog.find((row) => row.id === entry.id);
        return moduleFromCatalogItem(item, entry);
    });
    if (!layout(modules)) {
        applyWallsCm(380, 340, 300, 280);
        if (!layout(modules)) {
            toast('Örnek yerleşim sığmadı; duvar ölçülerini artırın.');
            return false;
        }
    }
    commit(() => {
        state.modules = modules;
        state.name = 'L giyinme odası';
        selectedId = modules.find((m) => m.kind !== 'corner')?.id || modules[0]?.id || null;
        const nameInput = el('project-name');
        if (nameInput) nameInput.value = state.name;
    });
    frameCamera();
    snapDoors();
    const doors = modules.reduce((sum, mod) => sum + (mod.door === 'none' ? 0 : doorCount(mod)), 0);
    toast(`Hazır: köşe + ${modules.length - 1} modül, ${doors} kapak (Kastamonu ahşap gövde).`);
    return true;
}

function preferCatalogWood(mod, { force = false } = {}) {
    const wood = catalogWoodId();
    if (!wood || !mod) return;
    const plainIds = new Set(
        materials
            .filter((item) => item.group === 'melamin-kapli-panel' && PLAIN_CODE_HEX[item.code])
            .map((item) => item.id),
    );
    const legacy = new Set([wood, productionPanelId(), ...plainIds, ''].filter(Boolean));
    const apply = (field) => {
        const cur = mod[field] || '';
        if (!force && cur && !legacy.has(cur) && cur !== state.carcassId && cur !== state.doorColorId) return;
        mod[field] = wood;
    };
    ['carcassId', 'doorColorId', 'interiorId', 'backId', 'shelfId', 'drawerColorId'].forEach(apply);
}

function applyDefaultColors() {
    const wood = catalogWoodId();
    const fallback = materials.find((item) => item.id === wood) || materials[0];
    const useWood = (cur) => !decorExists(cur) || !cur || isProductionPlainId(cur);
    if (useWood(state.carcassId)) state.carcassId = fallback?.id || '';
    if (useWood(state.doorColorId)) state.doorColorId = fallback?.id || '';
    if (useWood(state.interiorId)) state.interiorId = fallback?.id || '';
    if (useWood(state.backId)) state.backId = fallback?.id || '';
    if (useWood(state.shelfId)) state.shelfId = fallback?.id || '';
    if (useWood(state.drawerColorId)) state.drawerColorId = fallback?.id || '';
    // Yeni eklemede şeffaf/yarı saydam panel olmasın
    state.panelOpacity = 1;
    panelOpacity = 1;
    if (!ALUMINUM.some((item) => item.id === state.aluminumId)) state.aluminumId = 'al-inox';
    if (!GLASS.some((item) => item.id === state.glassId)) state.glassId = 'cam-fum';
    if (typeof state.plinth !== 'boolean') state.plinth = true;
    if (typeof state.kulp !== 'boolean') state.kulp = true;
    if (typeof state.led !== 'boolean') state.led = true;
    if (!(Number(state.wallWidth) >= 80)) state.wallWidth = WALL_X;
    if (!(Number(state.leftWall) >= 80)) state.leftWall = 300;
    if (!(Number(state.rightWall) >= 80)) state.rightWall = 300;
    if (!(Number(state.roomHeight) >= ROOM_H_MIN)) state.roomHeight = ROOM_H_STD;
    state.roomHeight = Math.min(ROOM_H_MAX, Number(state.roomHeight) || ROOM_H_STD);
    if (!state.roomSketch || !Array.isArray(state.roomSketch.openings)) state.roomSketch = { openings: [] };
}

function ensureModuleColors() {
    applyDefaultColors();
    state.modules.forEach((mod) => {
        if (!decorExists(mod.carcassId) || isProductionPlainId(mod.carcassId)) mod.carcassId = state.carcassId;
        if (!decorExists(mod.doorColorId) || isProductionPlainId(mod.doorColorId)) mod.doorColorId = state.doorColorId;
        if (!decorExists(mod.interiorId) || isProductionPlainId(mod.interiorId)) mod.interiorId = state.interiorId;
        if (!decorExists(mod.backId) || isProductionPlainId(mod.backId)) mod.backId = state.backId || state.interiorId;
        if (!decorExists(mod.shelfId) || isProductionPlainId(mod.shelfId)) mod.shelfId = state.shelfId || state.interiorId;
        if (!decorExists(mod.drawerColorId) || isProductionPlainId(mod.drawerColorId)) {
            mod.drawerColorId = state.drawerColorId || state.doorColorId;
        }
        if (!ALUMINUM.some((item) => item.id === mod.aluminumId)) mod.aluminumId = state.aluminumId || 'al-inox';
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

function writeDraft() {
    try {
        if (projectToken) return;
        if (!state.modules.length) {
            sessionStorage.removeItem('meemare-wardrobe-draft');
            return;
        }
        sessionStorage.setItem('meemare-wardrobe-draft', JSON.stringify({ savedAt: Date.now(), state }));
    } catch {
        /* taslak yazılamazsa sahne bellekte kalır */
    }
}

function scheduleSave() {
    writeDraft();
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
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) {
            const first = data.errors ? Object.values(data.errors).flat()[0] : '';
            toast(first || data.message || 'Kayıt yazılamadı. Tasarım ekranda duruyor.');
            return false;
        }
        const saveBtn = el('btn-save');
        if (manual) toast(data.message || 'Proje kaydedildi.');
        else if (saveBtn) {
            saveBtn.textContent = 'Kaydedildi';
            saveBtn.classList.add('is-on');
            clearTimeout(saveBtn.__savedTimer);
            saveBtn.__savedTimer = setTimeout(() => {
                saveBtn.textContent = 'Kaydet';
                saveBtn.classList.remove('is-on');
            }, 2200);
        }
        return true;
    } catch {
        if (manual) toast('Kayıt yazılamadı. Tasarım ekranda duruyor. Tekrar deneyin.');
        return false;
    }
}

function startNewProject() {
    try { sessionStorage.removeItem('meemare-wardrobe-draft'); } catch { /* boş */ }
    state = blankState();
    applyDefaultColors();
    projectToken = null;
    selectedId = null;
    allCabinetsSelected = false;
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
    form.roomHeight.value = String(ROOM_H_STD);
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

async function openProject(key) {
    const path = String(key).includes('-')
        ? `${window.WARDROBE.projectBase}/proje/kayit/${key}`
        : `${window.WARDROBE.projectBase}/proje/${key}`;
    try {
        const response = await fetch(path, { headers: { Accept: 'application/json' } });
        if (!response.ok) return false;
        const data = await response.json();
        if (!data.design?.modules || data.design.modules.some((mod) => !mod.kind)) return false;
        state = data.design;
        state.customerName = data.customerName || state.customerName;
        state.phone = data.phone || state.phone;
        state.name = data.projectName || state.name;
        state.note = data.note || state.note || '';
        ensureSketch();
        if (!state.floorStyle) state.floorStyle = 'parquet';
        ensureModuleColors();
        await preloadModuleGlbs();
        projectToken = data.token;
        selectedId = null;
        allCabinetsSelected = false;
        history = [];
        future = [];
        window.history.replaceState(null, '', `${location.pathname}?proje=${projectToken}`);
        render();
        focusSelectedModule();
        return true;
    } catch {
        return false;
    }
}

async function searchProjects(_phone, box, onOpen) {
    box.innerHTML = '<p class="hint">Aranıyor…</p>';
    try {
        const response = await fetch(window.WARDROBE.projectIndexUrl, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        const projects = data.projects || [];
        box.innerHTML = projects.length
            ? projects.map((item) => `<button type="button" class="choice" data-id="${item.id}"><strong>${item.projectName}</strong><span>${item.updatedAt ? new Date(item.updatedAt).toLocaleString('tr-TR') : ''}</span></button>`).join('')
            : '<p class="hint">Kayıtlı proje yok.</p>';
        box.querySelectorAll('[data-id]').forEach((button) => {
            button.onclick = async () => {
                const ok = await openProject(button.dataset.id);
                if (ok) onOpen();
                else toast('Proje açılamadı.');
            };
        });
    } catch {
        box.innerHTML = '<p class="hint">Liste alınamadı.</p>';
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
    initStudioLayout();
    bindChrome();
    renderLibrary();
    try {
        await loadMaterials();
    } catch {
        toast('Kastamonu renkleri yüklenemedi.');
    }
    try {
        await loadGlbCatalog();
        renderLibrary();
        void Promise.all(glbCatalog.map(async (item) => {
            try {
                await ensureGlb(item);
            } catch {
                glbFailed.add(item.id);
            }
        })).then(() => renderLibrary());
    } catch (error) {
        console.warn(error);
        renderLibrary();
    }
    if (!window.__woodMode) window.__woodMode = 'catalog';
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
    const params = new URLSearchParams(location.search);
    applyRoomQuery(params);
    applyHandoffFromStorage();
    const recordId = params.get('kayit');
    const token = params.get('proje');
    if ((recordId && await openProject(recordId)) || (token && await openProject(token))) {
        el('customer-modal').classList.add('hidden');
        panelOpacity = state.panelOpacity ?? 1;
        syncViewModeBar();
        render();
        focusSelectedModule();
        openRoomEditor();
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
    try {
        const draft = JSON.parse(sessionStorage.getItem('meemare-wardrobe-draft') || 'null');
        if (draft?.state?.modules?.length) {
            state = draft.state;
            ensureModuleColors();
            render();
            frameCamera();
            el('customer-modal').classList.add('hidden');
            toggleRoomPlan(false);
            return;
        }
    } catch {
        /* bozuk taslak yok sayılır */
    }
    el('customer-modal').classList.add('hidden');
    panelOpacity = state.panelOpacity ?? 1;
    toggleRoomPlan(false);
    if (params.get('demo') === 'giyinme' && !state.modules.length) {
        await applyDressingRoomDemo();
    }
    syncViewModeBar();
    render();
    frameCamera();
    if (!state.modules.length) {
        showModuleLibrary();
    }
    maybeOpenPlanStudio(params);
    maybeOpenRoomSketcher(params);
}

window.__setCameraView = (mode) => setCameraView(mode);
window.__moduleCount = () => state.modules.length;
window.__wallRun = (wall = 'front') => state.modules
    .filter((mod) => mod.kind !== 'corner' && wallName(mod) === wall)
    .reduce((sum, mod) => sum + alongSize(mod), 0);
window.__exportState = () => JSON.parse(JSON.stringify(state));
window.__studio = {
    dressingDemo: () => applyDressingRoomDemo(),
    add: (id) => addGlbModule(id),
    select: (id) => {
        const mod = state.modules.find((item) => item.id === id || item.glb === id);
        if (!mod) return false;
        selectedId = mod.id;
        allCabinetsSelected = false;
        sceneFocus = 'cabinet';
        openInspectorForSelection();
        renderInspector();
        focusSelectedModule();
        updateHud();
        return true;
    },
    remove: () => deleteSelected(),
    clear: () => {
        commit(() => {
            state.modules = [];
            selectedId = null;
            sceneFocus = 'room';
        });
        frameCamera();
    },
    applyWalls: (front, left, right, height) => applyWallsCm(front, left, right, height),
    focusSelected: () => focusSelectedModule(),
    exportState: () => window.__exportState(),
    undo: () => undo(),
    redo: () => redo(),
    paint: (colorId) => {
        const mod = selected();
        if (!mod) return false;
        commit(() => {
            mod.doorColorId = colorId;
            mod.carcassId = colorId;
        });
        return true;
    },
    setDoor: (kind) => {
        const mod = selected();
        if (!mod) return false;
        commit(() => {
            mod.door = kind;
            mod.open = 0;
        });
        return true;
    },
    shift: (id, offset) => {
        const mod = state.modules.find((item) => item.glb === id || item.id === id);
        if (!mod) return null;
        commit(() => { mod.offset = offset; });
        return true;
    },
    fronts: () => {
        const rows = [];
        cabinetRoot.updateMatrixWorld(true);
        cabinetRoot.traverse((child) => {
            if (!child.isMesh) return;
            const box = new THREE.Box3().setFromObject(child);
            if (box.max.z < 55) return;
            rows.push({
                name: child.name || child.parent?.name || '',
                maxZ: Math.round(box.max.z * 10) / 10,
                minX: Math.round(box.min.x * 10) / 10,
                maxX: Math.round(box.max.x * 10) / 10,
            });
        });
        rows.sort((a, b) => b.maxZ - a.maxZ);
        return rows.slice(0, 24);
    },
    lowMeshes: () => {
        const rows = [];
        cabinetRoot.updateMatrixWorld(true);
        cabinetRoot.traverse((child) => {
            if (!child.isMesh) return;
            const box = new THREE.Box3().setFromObject(child);
            const size = box.getSize(new THREE.Vector3());
            const mat = Array.isArray(child.material) ? child.material[0] : child.material;
            rows.push({
                name: child.name || '(anon)',
                parent: child.parent?.name || '',
                role: glbPartRole(child.name),
                color: mat?.color?.getHexString?.() || '',
                metal: mat?.metalness ?? null,
                opa: mat?.opacity ?? null,
                tr: Boolean(mat?.transparent),
                dw: mat?.depthWrite !== false,
                yMin: Math.round(box.min.y * 10) / 10,
                yMax: Math.round(box.max.y * 10) / 10,
                h: Math.round(size.y * 10) / 10,
                w: Math.round(size.x * 10) / 10,
                d: Math.round(size.z * 10) / 10,
                tris: child.geometry?.index?.count
                    ? child.geometry.index.count / 3
                    : (child.geometry?.attributes?.position?.count || 0) / 3,
            });
        });
        return rows.sort((a, b) => a.yMin - b.yMin).slice(0, 60);
    },
    toggleDoor: () => {
        const mod = selected();
        if (!mod) return false;
        commit(() => {
            if (mod.door === 'none') mod.door = 'solid';
            mod.open = mod.open ? 0 : 1;
        });
        return mod.open;
    },
    stats: () => ({
        count: state.modules.length,
        ids: state.modules.map((mod) => mod.glb || mod.kind),
        places: state.modules.map((mod) => ({ id: mod.glb || mod.name, wall: mod.wall, offset: Math.round(Number(mod.offset) || 0) })),
        openings: (state.roomSketch?.openings || []).map((item) => ({ wall: item.wallId, kind: item.kind, offset: item.offset, width: item.width })),
        calls: renderer?.info?.render?.calls ?? null,
        triangles: renderer?.info?.render?.triangles ?? null,
        geometries: renderer?.info?.memory?.geometries ?? null,
        textures: renderer?.info?.memory?.textures ?? null,
    }),
    summary: () => el('summary')?.textContent || '',
    setOpenings: (items) => {
        commit(() => {
            const sketch = ensureSketch();
            sketch.openings = Array.isArray(items) ? items.map((item) => ({ ...item })) : [];
        });
        fitRoom();
        if (planMode === '2d') drawPlan();
        return ensureSketch().openings.length;
    },
    extras: () => {
        const group = room.extras;
        if (!group) return [];
        return group.children.map((mesh) => ({
            name: mesh.name || group.name,
            x: Math.round(mesh.position.x),
            y: Math.round(mesh.position.y),
            z: Math.round(mesh.position.z),
            rotY: Math.round(mesh.rotation.y * 1000) / 1000,
            w: Math.round(mesh.geometry?.parameters?.width || 0),
            h: Math.round(mesh.geometry?.parameters?.height || 0),
            d: Math.round(mesh.geometry?.parameters?.depth || 0),
        }));
    },
    frameExtras: () => {
        const group = room.extras;
        if (!group?.children?.length || !camera || !controls) return false;
        const box = new THREE.Box3().setFromObject(group);
        const center = box.getCenter(new THREE.Vector3());
        controls.target.copy(center);
        camera.position.set(center.x, Math.max(center.y + 220, 260), center.z + 80);
        camera.lookAt(center);
        controls.update();
        return true;
    },
    measure: async (id) => {
        const item = glbCatalog.find((entry) => entry.id === id);
        if (!item) return null;
        await ensureGlb(item);
        const tpl = glbTemplates.get(id);
        if (!tpl) return null;
        const clone = tpl.clone(true);
        clone.updateMatrixWorld(true);
        const rows = [];
        clone.traverse((child) => {
            if (!child.isMesh || !child.geometry) return;
            const geometry = child.geometry.clone();
            geometry.applyMatrix4(child.matrixWorld);
            geometry.computeBoundingBox();
            const box = geometry.boundingBox;
            const size = box.getSize(new THREE.Vector3());
            geometry.dispose();
            rows.push({
                name: child.name || '',
                x: Math.round(size.x * 1000) / 1000,
                y: Math.round(size.y * 1000) / 1000,
                z: Math.round(size.z * 1000) / 1000,
                min: [box.min.x, box.min.y, box.min.z].map((n) => Math.round(n * 1000) / 1000),
                max: [box.max.x, box.max.y, box.max.z].map((n) => Math.round(n * 1000) / 1000),
            });
        });
        const root = new THREE.Box3().setFromObject(clone);
        const rootSize = root.getSize(new THREE.Vector3());
        return {
            id,
            name: item.name,
            category: item.category,
            catalog: {
                width: item.width,
                height: item.height,
                depth: item.depth,
                shelves: item.shelves,
                drawers: item.drawers,
            },
            root: [rootSize.x, rootSize.y, rootSize.z].map((n) => Math.round(n * 1000) / 1000),
            parts: rows,
        };
    },
    rawGlb: (id) => {
        const tpl = glbTemplates.get(id || state.modules[0]?.glb);
        if (!tpl) return null;
        const rows = [];
        const clone = tpl.clone(true);
        clone.updateMatrixWorld(true);
        clone.traverse((child) => {
            if (!child.isMesh) return;
            const g = child.geometry.clone();
            g.applyMatrix4(child.matrixWorld);
            g.computeBoundingBox();
            const box = g.boundingBox;
            const size = box.getSize(new THREE.Vector3());
            rows.push({
                name: child.name || '(anon)',
                drop: glbAccessoryName(child.name),
                x: Math.round(size.x * 1000) / 1000,
                y: Math.round(size.y * 1000) / 1000,
                z: Math.round(size.z * 1000) / 1000,
                min: [Math.round(box.min.x * 1000) / 1000, Math.round(box.min.y * 1000) / 1000, Math.round(box.min.z * 1000) / 1000],
                max: [Math.round(box.max.x * 1000) / 1000, Math.round(box.max.y * 1000) / 1000, Math.round(box.max.z * 1000) / 1000],
                tris: child.geometry?.index?.count ? child.geometry.index.count / 3 : (child.geometry?.attributes?.position?.count || 0) / 3,
            });
        });
        return rows.sort((a, b) => b.tris - a.tris).slice(0, 80);
    },
};
window.__rebuildCabinet = () => {
    matCache.clear();
    rebuild();
    snapDoors();
};

boot();
