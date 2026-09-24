import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { CSS2DRenderer, CSS2DObject } from 'three/addons/renderers/CSS2DRenderer.js';

const CM = 0.01;
const SNAP = 1;
const WALL_W = 500; // cm — 420 cm ünitelere yer
const WALL_H = 300;
const FLOOR_D = 320;
const GAP = 3; // cm between independent modules

/** Arkalık / panel kalınlıkları (mm → cm) */
const BACK_THICKNESS_MM = [8, 10, 12, 16, 18, 22, 25];

const CATALOG = {
  frame: [
    { id: 'f60x40', label: 'İskelet', w: 60, d: 40, h: 38, price: 2000 },
    { id: 'f120x40', label: 'İskelet geniş', w: 120, d: 40, h: 38, price: 3200 },
    { id: 'f60x64', label: 'İskelet yüksek', w: 60, d: 40, h: 64, price: 2800 },
    { id: 'f180x40', label: 'TV banko', w: 180, d: 40, h: 38, price: 4500 },
    { id: 'f60x200', label: 'Kule iskelet', w: 60, d: 35, h: 200, price: 4800 },
    { id: 'f40x200', label: 'Dar kule', w: 40, d: 35, h: 200, price: 3600 },
  ],
  plinth: [
    { id: 'p120', label: 'Alt blok 120', w: 120, d: 40, h: 40, baza: true, price: 900 },
    { id: 'p180', label: 'Alt blok 180', w: 180, d: 40, h: 40, baza: true, price: 1200 },
    { id: 'p240', label: 'Alt blok 240', w: 240, d: 40, h: 40, baza: true, price: 1600 },
    { id: 'p420', label: 'Alt blok 420', w: 420, d: 40, h: 40, baza: true, price: 2800 },
    { id: 'p60', label: 'Alt blok dar', w: 60, d: 40, h: 40, baza: true, price: 600 },
    { id: 'pf180', label: 'Yüzen banko 180', w: 180, d: 40, h: 35, floatY: 18, baza: false, led: true, price: 1400 },
    { id: 'pf240', label: 'Yüzen banko 240', w: 240, d: 40, h: 35, floatY: 18, baza: false, led: true, price: 1800 },
    { id: 'pf420', label: 'Yüzen banko 420', w: 420, d: 40, h: 38, floatY: 15, baza: false, led: true, price: 3200 },
  ],
  wallPanel: [
    { id: 'wp160', label: 'Duvar paneli 160', w: 160, d: 1.8, h: 200, thicknessMm: 18, price: 2200 },
    { id: 'wp180', label: 'Duvar paneli 180', w: 180, d: 1.8, h: 220, thicknessMm: 18, price: 2600 },
    { id: 'wp240', label: 'Duvar paneli 240', w: 240, d: 1.8, h: 220, thicknessMm: 18, price: 3200 },
    { id: 'wp120', label: 'Duvar paneli 120', w: 120, d: 1.8, h: 200, thicknessMm: 18, price: 1800 },
    { id: 'wp200', label: 'Duvar paneli 200', w: 200, d: 1.8, h: 240, thicknessMm: 18, price: 3000 },
  ],
  shelf: [
    { id: 's120', label: 'Açık raf ünitesi', w: 120, d: 35, h: 40, price: 1400 },
    { id: 's60', label: 'Raf ünitesi', w: 60, d: 35, h: 40, price: 900 },
    { id: 's180', label: 'Geniş raf', w: 180, d: 35, h: 30, price: 1800 },
    { id: 'st50', label: 'Kule raf 50', w: 50, d: 35, h: 220, price: 2400 },
    { id: 'st40', label: 'Kule raf 40', w: 40, d: 35, h: 220, price: 2200 },
    { id: 'st60', label: 'Kule raf 60', w: 60, d: 35, h: 220, price: 2800 },
  ],
  door: [
    { id: 'd-hinge', label: 'Menteşeli kapak', w: 60, d: 2, h: 38, price: 900, doorStyle: 'hinge' },
    { id: 'd-push', label: 'Bas-aç kapak', w: 60, d: 2, h: 38, price: 950, doorStyle: 'push' },
    { id: 'd-drawer', label: 'Çekmece', w: 60, d: 40, h: 20, price: 1100, doorStyle: 'drawer' },
    { id: 'd-glass', label: 'Cam kapaklı', w: 60, d: 2, h: 64, price: 1600, doorStyle: 'glass' },
    { id: 'd-glass-w', label: 'Cam kapaklı geniş', w: 120, d: 2, h: 64, price: 2400, doorStyle: 'glass' },
  ],
  back: [
    { id: 'b8', label: 'Arkalık 8 mm', w: 60, d: 0.8, h: 64, thicknessMm: 8, price: 400 },
    { id: 'b10', label: 'Arkalık 10 mm', w: 60, d: 1.0, h: 64, thicknessMm: 10, price: 450 },
    { id: 'b12', label: 'Arkalık 12 mm', w: 120, d: 1.2, h: 64, thicknessMm: 12, price: 500 },
    { id: 'b18', label: 'Arkalık 18 mm', w: 180, d: 1.8, h: 64, thicknessMm: 18, price: 700 },
  ],
  slat: [
    { id: 'sl120', label: 'Çıta panel', w: 120, d: 3, h: 40, price: 1100 },
    { id: 'sl180', label: 'Çıta panel geniş', w: 180, d: 3, h: 40, price: 1500 },
    { id: 'slw60', label: 'Çıta duvar 60', w: 60, d: 3, h: 220, price: 1600 },
    { id: 'slw80', label: 'Çıta duvar 80', w: 80, d: 3, h: 220, price: 1900 },
    { id: 'slw40', label: 'Çıta duvar 40', w: 40, d: 3, h: 220, price: 1200 },
  ],
  top: [
    { id: 't120', label: 'Üst panel', w: 120, d: 42, h: 2.5, price: 800 },
    { id: 't180', label: 'Üst panel geniş', w: 180, d: 42, h: 2.5, price: 1100 },
    { id: 't240', label: 'Üst panel 240', w: 240, d: 42, h: 2.5, price: 1400 },
  ],
  leg: [
    { id: 'l60', label: 'Ayak seti', w: 60, d: 40, h: 10, price: 350 },
    { id: 'l120', label: 'Ayak seti geniş', w: 120, d: 40, h: 10, price: 450 },
    { id: 'l180', label: 'Ayak seti 180', w: 180, d: 40, h: 10, price: 550 },
  ],
};

const PRICES = {
  frame: 1500, door: 900, shelf: 900, back: 400, slat: 650, top: 800, leg: 250, plinth: 700, wallPanel: 2000,
};

const FINISHES = [
  { id: 'matte', label: 'Mat' },
  { id: 'gloss', label: 'Parlak' },
  { id: 'highgloss', label: 'High Gloss' },
  { id: 'ceramic', label: 'Seramik' },
];

const DOOR_STYLES = [
  { id: 'hinge', label: 'Menteşeli' },
  { id: 'push', label: 'Bas-aç' },
  { id: 'drawer', label: 'Çekmece' },
  { id: 'glass', label: 'Cam kapak' },
];

const TV_INCHES = [32, 43, 50, 55, 65, 75];

/** BESTÅ tarzı TV banko hazır başlangıçlar (iskeletler yan yana) */
const TV_BENCH_PRESETS = [
  { id: 'tv120', label: 'TV banko 120 cm', bays: 2, bayW: 60, d: 40, h: 38, inch: 43 },
  { id: 'tv180', label: 'TV banko 180 cm', bays: 3, bayW: 60, d: 40, h: 38, inch: 55 },
  { id: 'tv240', label: 'TV banko 240 cm', bays: 4, bayW: 60, d: 40, h: 38, inch: 65 },
];

/**
 * Referans görsellerdeki gibi tam TV duvarı kompozisyonları.
 * base + feature panel + çıta + kule raf + LED + TV.
 */
const WALL_COMPOSITIONS = [
  {
    id: 'pro420',
    label: '420×260 Profesyonel',
    desc: 'Raflı · bas-aç banko · panel',
    inch: 75,
    build: 'pro420',
  },
  {
    id: 'lux-marble',
    label: 'Mermer TV duvarı',
    desc: 'Yüzen banko · mermer panel · çıta · kule',
    inch: 65,
    build: 'marble',
  },
  {
    id: 'lux-wood',
    label: 'Ahşap çıta duvarı',
    desc: 'Banko · çıta panel · yan raflar',
    inch: 55,
    build: 'wood',
  },
  {
    id: 'lux-sym',
    label: 'Simetrik TV duvarı',
    desc: 'Orta panel · iki yan kule · banko',
    inch: 65,
    build: 'sym',
  },
  {
    id: 'lux-float',
    label: 'Minimal yüzen',
    desc: 'Yüzen banko · LED · düz panel',
    inch: 55,
    build: 'float',
  },
];

const BAY_W = 60; // BESTÅ standart iskelet eni cm

const appEl = document.getElementById('app');
const materialsUrl = appEl.dataset.materialsUrl;
const brandName = appEl.dataset.brand || 'Meemare';

const state = {
  view: 'home',
  catalogKind: null,
  selectedId: null,
  modules: [],
  materials: { groups: [] },
  showTv: false,
  tvInch: 55,
  night: false,
  showGrid: false,
  showMeasure: false,
  autoRotate: false,
  room: { wall: '#f2f2f2', floor: '#d8c3a5' },
  history: [],
  future: [],
  textureCache: new Map(),
  hoveredId: null,
};

const ALUMINUM_COLORS = [
  { id: 'silver', label: 'Gümüş', color: '#c0c4c8' },
  { id: 'black', label: 'Siyah', color: '#1a1a1a' },
  { id: 'bronze', label: 'Bronz', color: '#8b7355' },
  { id: 'gold', label: 'Şampanya', color: '#d4af87' },
  { id: 'white', label: 'Beyaz', color: '#f5f5f5' },
];

const GLASS_COLORS = [
  { id: 'clear', label: 'Şeffaf', color: '#c5d5e8' },
  { id: 'smoke', label: 'Füme', color: '#64748b' },
  { id: 'bronze-g', label: 'Bronz cam', color: '#b45309' },
  { id: 'green', label: 'Yeşil', color: '#86efac' },
  { id: 'grey', label: 'Gri', color: '#94a3b8' },
  { id: 'mirror', label: 'Ayna', color: '#e2e8f0' },
];

let scene, camera, renderer, labelRenderer, controls, raycaster, pointer;
let roomGroup, modulesGroup, gridHelper, tvMesh;
let drag = null;
let catalogDrag = null;

const $ = (sel) => document.querySelector(sel);
const sideBody = $('#side-body');
const sideTitle = $('#side-title');
const btnBack = $('#btn-back');
const hintEl = $('#hint');
const ctxMenu = $('#ctx-menu');

function uid() {
  return 'm_' + Math.random().toString(36).slice(2, 10);
}

function showHint(text, ms = 2400) {
  hintEl.textContent = text;
  hintEl.classList.add('show');
  clearTimeout(showHint._t);
  showHint._t = setTimeout(() => hintEl.classList.remove('show'), ms);
}

function pushHistory() {
  state.history.push(JSON.stringify(state.modules));
  if (state.history.length > 40) state.history.shift();
  state.future = [];
}

function undo() {
  if (!state.history.length) return;
  state.future.push(JSON.stringify(state.modules));
  state.modules = JSON.parse(state.history.pop());
  state.selectedId = null;
  rebuildModules();
  renderSidebar();
}

function redo() {
  if (!state.future.length) return;
  state.history.push(JSON.stringify(state.modules));
  state.modules = JSON.parse(state.future.pop());
  state.selectedId = null;
  rebuildModules();
  renderSidebar();
}

function selected() {
  return state.modules.find((m) => m.id === state.selectedId) || null;
}

function updatePrice() {
  // Fiyatlar şimdilik gizli
}

function finishRoughness(finish) {
  if (finish === 'matte') return 0.85;
  if (finish === 'gloss') return 0.28;
  if (finish === 'highgloss') return 0.06;
  if (finish === 'ceramic') return 0.32;
  return 0.5;
}

function finishMetalness(finish) {
  if (finish === 'highgloss') return 0.18;
  if (finish === 'ceramic') return 0.04;
  return 0.02;
}

async function loadTexture(url) {
  if (!url) return null;
  if (state.textureCache.has(url)) return state.textureCache.get(url);
  const loader = new THREE.TextureLoader();
  const tex = await new Promise((resolve) => {
    loader.load(
      url,
      (t) => {
        t.colorSpace = THREE.SRGBColorSpace;
        t.wrapS = t.wrapT = THREE.RepeatWrapping;
        resolve(t);
      },
      undefined,
      () => resolve(null)
    );
  });
  state.textureCache.set(url, tex);
  return tex;
}

async function makeMaterial(mod, opts = {}) {
  const src = mod.materialImage ? await loadTexture(mod.materialImage) : null;
  const map = src ? src.clone() : null;
  if (map) {
    map.needsUpdate = true;
    map.repeat.set(
      Math.max(0.8, (mod.w || 60) / 80),
      Math.max(0.8, (mod.h || 40) / 80)
    );
  }
  const isGlass = opts.glass || mod.doorStyle === 'glass';
  if (isGlass) {
    return new THREE.MeshPhysicalMaterial({
      color: map ? 0xffffff : new THREE.Color(mod.color || '#c5d5e8'),
      map: map || null,
      roughness: 0.05,
      metalness: 0.05,
      transmission: map ? 0.15 : 0.55,
      transparent: true,
      opacity: map ? 0.92 : 0.45,
      thickness: 0.02,
    });
  }
  return new THREE.MeshStandardMaterial({
    color: map ? 0xffffff : new THREE.Color(mod.color || '#f5f5f5'),
    map: map || null,
    roughness: finishRoughness(mod.finish),
    metalness: finishMetalness(mod.finish),
  });
}

function initThree() {
  const host = $('#canvas-host');
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0xe8e8e8);
  scene.fog = new THREE.Fog(0xe8e8e8, 10, 22);

  camera = new THREE.PerspectiveCamera(42, 1, 0.05, 50);
  camera.position.set(2.4, 1.5, 3.4);

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  host.appendChild(renderer.domElement);

  labelRenderer = new CSS2DRenderer();
  labelRenderer.domElement.style.position = 'absolute';
  labelRenderer.domElement.style.inset = '0';
  labelRenderer.domElement.style.pointerEvents = 'none';
  host.appendChild(labelRenderer.domElement);

  controls = new OrbitControls(camera, renderer.domElement);
  controls.target.set(0, 0.55, 0);
  controls.enableDamping = true;
  controls.minPolarAngle = 0.08;
  controls.maxPolarAngle = Math.PI * 0.49;
  controls.minDistance = 1;
  controls.maxDistance = 10;
  controls.enablePan = true;
  controls.autoRotateSpeed = 1.6;

  const hemi = new THREE.HemisphereLight(0xffffff, 0xb0b0b0, 1.05);
  scene.add(hemi);
  const dir = new THREE.DirectionalLight(0xffffff, 1.35);
  dir.position.set(3, 5, 2);
  dir.castShadow = true;
  dir.shadow.mapSize.set(2048, 2048);
  dir.shadow.camera.near = 0.5;
  dir.shadow.camera.far = 20;
  dir.shadow.camera.left = -5;
  dir.shadow.camera.right = 5;
  dir.shadow.camera.top = 5;
  dir.shadow.camera.bottom = -5;
  scene.add(dir);
  state._dirLight = dir;

  roomGroup = new THREE.Group();
  scene.add(roomGroup);
  modulesGroup = new THREE.Group();
  scene.add(modulesGroup);

  buildRoom();
  buildTv();

  gridHelper = new THREE.GridHelper(6, 60, 0xbbbbbb, 0xdddddd);
  gridHelper.position.y = 0.001;
  gridHelper.visible = false;
  scene.add(gridHelper);

  raycaster = new THREE.Raycaster();
  pointer = new THREE.Vector2();

  const canvas = renderer.domElement;
  canvas.addEventListener('pointerdown', onPointerDown);
  canvas.addEventListener('pointermove', onPointerMove);
  canvas.addEventListener('contextmenu', onContextMenu);
  window.addEventListener('pointerup', onPointerUp);
  window.addEventListener('resize', onResize);
  document.addEventListener('click', (e) => {
    if (!ctxMenu?.contains(e.target)) hideCtx();
  });

  // Drop catalog products onto the scene
  host.addEventListener('dragover', (e) => {
    if (!catalogDrag) return;
    e.preventDefault();
    $('#drop-overlay').classList.add('show');
  });
  host.addEventListener('dragleave', () => $('#drop-overlay').classList.remove('show'));
  host.addEventListener('drop', (e) => {
    e.preventDefault();
    $('#drop-overlay').classList.remove('show');
    const raw = e.dataTransfer.getData('application/x-tv-part') || e.dataTransfer.getData('text/plain');
    if (!raw) return;
    try {
      const data = JSON.parse(raw);
      placeFromDrop(data, e.clientX, e.clientY);
    } catch (_) {}
    catalogDrag = null;
  });

  onResize();
  animate();
}

function buildRoom() {
  while (roomGroup.children.length) roomGroup.remove(roomGroup.children[0]);

  const floorMat = new THREE.MeshStandardMaterial({ color: new THREE.Color(state.room.floor), roughness: 0.7 });
  const c = document.createElement('canvas');
  c.width = 512; c.height = 512;
  const ctx = c.getContext('2d');
  ctx.fillStyle = state.room.floor;
  ctx.fillRect(0, 0, 512, 512);
  for (let i = 0; i < 32; i++) {
    ctx.fillStyle = i % 2 ? 'rgba(0,0,0,0.04)' : 'rgba(255,255,255,0.05)';
    ctx.fillRect(0, i * 16, 512, 16);
  }
  const floorMap = new THREE.CanvasTexture(c);
  floorMap.wrapS = floorMap.wrapT = THREE.RepeatWrapping;
  floorMap.repeat.set(4, 4);
  floorMap.colorSpace = THREE.SRGBColorSpace;
  floorMat.map = floorMap;

  const floor = new THREE.Mesh(new THREE.PlaneGeometry(WALL_W * CM, FLOOR_D * CM), floorMat);
  floor.rotation.x = -Math.PI / 2;
  floor.receiveShadow = true;
  floor.name = 'floor';
  roomGroup.add(floor);

  const wall = new THREE.Mesh(
    new THREE.PlaneGeometry(WALL_W * CM, WALL_H * CM),
    new THREE.MeshStandardMaterial({ color: new THREE.Color(state.room.wall), roughness: 0.92 })
  );
  wall.position.set(0, (WALL_H * CM) / 2, -(FLOOR_D * CM) / 2 + 0.01);
  wall.receiveShadow = true;
  wall.name = 'wall';
  roomGroup.add(wall);

  const sk = new THREE.Mesh(
    new THREE.BoxGeometry(WALL_W * CM, 0.08, 0.02),
    new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.6 })
  );
  sk.position.set(0, 0.04, -(FLOOR_D * CM) / 2 + 0.03);
  roomGroup.add(sk);
}

function tvSizeFromInch(inch) {
  const diag = inch * 2.54 * CM;
  const aspect = 16 / 9;
  const h = diag / Math.sqrt(1 + aspect * aspect);
  const w = h * aspect;
  return { w, h, d: 0.045 };
}

function buildTv() {
  if (tvMesh) {
    scene.remove(tvMesh);
    tvMesh = null;
  }
  if (!state.showTv) return;

  const { w, h, d } = tvSizeFromInch(state.tvInch);
  const g = new THREE.Group();
  g.name = 'tv';

  // TV'yi duvar paneli / özellik paneli / banko üstüne ortala
  const panel = state.modules.find((m) => m.type === 'wallPanel')
    || state.modules.find((m) => m.type === 'back' && m.h >= 80)
    || null;
  const bench = state.modules.find((m) => m.type === 'plinth' || (m.type === 'frame' && !m.parentId && m.h <= 50));
  let x = 0;
  let yCm;
  let zCm;
  if (panel) {
    x = panel.x;
    yCm = panel.y + panel.h * 0.52;
    zCm = panel.z + panel.d / 2 + 3;
  } else if (bench) {
    x = bench.x;
    yCm = (bench.y || 0) + bench.h + 55;
    zCm = bench.z - bench.d / 2 - 2;
  } else {
    yCm = 105;
    zCm = -(FLOOR_D / 2) + 8;
  }
  const y = yCm * CM;
  const z = zCm * CM;
  const screen = new THREE.Mesh(
    new THREE.BoxGeometry(w, h, d),
    new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.35, metalness: 0.4 })
  );
  screen.position.set(x * CM, y, z);
  const glass = new THREE.Mesh(
    new THREE.PlaneGeometry(w * 0.94, h * 0.9),
    new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.2, metalness: 0.55 })
  );
  glass.position.set(x * CM, y, z + d / 2 + 0.002);
  g.add(screen, glass);
  tvMesh = g;
  scene.add(tvMesh);
}

function frameShellGeometry(w, h, d, thick = 1.8) {
  const t = thick * CM;
  const W = w * CM, H = h * CM, D = d * CM;
  return {
    parts: [
      { s: [W, t, D], p: [0, t / 2, 0] },
      { s: [W, t, D], p: [0, H - t / 2, 0] },
      { s: [t, H - 2 * t, D], p: [-W / 2 + t / 2, H / 2, 0] },
      { s: [t, H - 2 * t, D], p: [W / 2 - t / 2, H / 2, 0] },
    ],
    W, H, D, t,
  };
}

function addLedStrip(group, mod, where) {
  const glow = new THREE.MeshStandardMaterial({
    color: 0xffe6b8,
    emissive: new THREE.Color(0xffcc66),
    emissiveIntensity: state.night ? 2.2 : 1.1,
    roughness: 1,
    metalness: 0,
  });
  if (where === 'under') {
    const strip = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM * 0.92, 0.4 * CM, 1.2 * CM),
      glow
    );
    strip.position.set(0, -0.3 * CM, 0);
    group.add(strip);
    const light = new THREE.PointLight(0xffddaa, 0.55, 1.8);
    light.position.set(0, -0.02, 0.05);
    group.add(light);
  } else if (where === 'back') {
    // Yan + üst halo
    const edge = 0.5 * CM;
    const H = mod.h * CM;
    const W = mod.w * CM;
    const D = Math.max(1.5, mod.d) * CM;
    [[-W / 2 - edge, H / 2, -D / 2 - 0.01], [W / 2 + edge, H / 2, -D / 2 - 0.01]].forEach(([x, y, z]) => {
      const s = new THREE.Mesh(new THREE.BoxGeometry(edge, H * 0.96, edge), glow.clone());
      s.position.set(x, y, z);
      group.add(s);
    });
    const top = new THREE.Mesh(new THREE.BoxGeometry(W * 0.96, edge, edge), glow.clone());
    top.position.set(0, H + edge, -D / 2 - 0.01);
    group.add(top);
  } else if (where === 'shelf') {
    const strip = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM * 0.9, 0.3 * CM, 0.8 * CM),
      glow
    );
    strip.position.set(0, -0.2 * CM, 0);
    group.add(strip);
  }
}

async function createModuleMesh(mod) {
  const group = new THREE.Group();
  group.userData.moduleId = mod.id;
  const mat = await makeMaterial(mod);

  if (mod.type === 'frame') {
    const { parts, W, H, D } = frameShellGeometry(mod.w, mod.h, mod.d);
    for (const part of parts) {
      const mesh = new THREE.Mesh(new THREE.BoxGeometry(...part.s), mat.clone());
      mesh.position.set(...part.p);
      mesh.castShadow = true;
      mesh.receiveShadow = true;
      group.add(mesh);
    }
    const inner = new THREE.Mesh(
      new THREE.PlaneGeometry(W - 0.04, H - 0.04),
      new THREE.MeshStandardMaterial({ color: 0xfafafa, roughness: 0.95 })
    );
    inner.position.set(0, H / 2, -D / 2 + 0.005);
    group.add(inner);
  } else if (mod.type === 'shelf') {
    if (mod.interior || mod.parentId || mod.h <= 4) {
      // İç raf paneli
      const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(mod.w * CM, Math.max(1.5, mod.h) * CM, mod.d * CM),
        mat
      );
      mesh.position.y = (Math.max(1.5, mod.h) * CM) / 2;
      mesh.castShadow = true;
      group.add(mesh);
      if (mod.led) addLedStrip(group, mod, 'shelf');
      group.userData.originAtBottom = true;
    } else {
      // Bağımsız açık raf ünitesi
      const { parts, W, H, D } = frameShellGeometry(mod.w, mod.h, mod.d, 1.6);
      for (const part of parts) {
        const mesh = new THREE.Mesh(new THREE.BoxGeometry(...part.s), mat.clone());
        mesh.position.set(...part.p);
        mesh.castShadow = true;
        group.add(mesh);
      }
      const shelfCount = Math.max(1, Math.floor(mod.h / 22));
      for (let i = 1; i <= shelfCount; i++) {
        const y = (H / (shelfCount + 1)) * i;
        const sh = new THREE.Mesh(
          new THREE.BoxGeometry(W - 0.04, 1.5 * CM, D - 0.02),
          mat.clone()
        );
        sh.position.set(0, y, 0);
        sh.castShadow = true;
        group.add(sh);
        if (mod.led) {
          const glow = new THREE.Mesh(
            new THREE.BoxGeometry(W - 0.06, 0.25 * CM, 0.6 * CM),
            new THREE.MeshStandardMaterial({
              color: 0xffe6b8, emissive: 0xffcc66, emissiveIntensity: 1.2, roughness: 1,
            })
          );
          glow.position.set(0, y - 0.8 * CM, 0);
          group.add(glow);
        }
      }
    }
  } else if (mod.type === 'door') {
    await buildDoorMesh(group, mod, mat);
  } else if (mod.type === 'plinth') {
    const bazaH = mod.baza ? Math.min(10, Math.max(6, (mod.bazaH || 8))) : 0;
    const bodyH = Math.max(8, mod.h - bazaH);
    const body = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, bodyH * CM, mod.d * CM),
      mat
    );
    body.position.y = (bazaH + bodyH / 2) * CM;
    body.castShadow = true;
    group.add(body);
    if (bazaH > 0) {
      const bazaMat = new THREE.MeshStandardMaterial({
        color: new THREE.Color(mod.bazaColor || '#2a2a2a'),
        roughness: 0.7,
      });
      const baza = new THREE.Mesh(
        new THREE.BoxGeometry((mod.w - 4) * CM, bazaH * CM, (mod.d - 2) * CM),
        bazaMat
      );
      baza.position.y = (bazaH / 2) * CM;
      baza.castShadow = true;
      group.add(baza);
    }
    group.userData.originAtBottom = true;
    if (mod.led && !mod.baza) addLedStrip(group, mod, 'under');
  } else if (mod.type === 'wallPanel') {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, mod.h * CM, Math.max(1.5, mod.d) * CM),
      mat
    );
    mesh.position.y = (mod.h * CM) / 2;
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    group.add(mesh);
    if (mod.metalStrips) {
      const n = Math.max(2, Math.floor(mod.w / 40));
      const metal = new THREE.MeshStandardMaterial({ color: 0xc9a66b, metalness: 0.9, roughness: 0.25 });
      for (let i = 1; i < n; i++) {
        const strip = new THREE.Mesh(
          new THREE.BoxGeometry(0.4 * CM, mod.h * CM * 0.98, (mod.d + 0.4) * CM),
          metal.clone()
        );
        strip.position.set((-mod.w / 2 + (mod.w / n) * i) * CM, mod.h * CM / 2, 0);
        group.add(strip);
      }
    }
    group.userData.originAtBottom = true;
    if (mod.led) addLedStrip(group, mod, 'back');
  } else if (mod.type === 'back') {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, mod.h * CM, Math.max(0.6, mod.d) * CM),
      mat
    );
    mesh.position.y = (mod.h * CM) / 2;
    mesh.castShadow = true;
    group.add(mesh);
    group.userData.originAtBottom = true;
    if (mod.led) addLedStrip(group, mod, 'back');
  } else if (mod.type === 'slat') {
    const slatW = mod.slatWidth || 1.6; // cm
    const gapCm = mod.slatGap != null ? mod.slatGap : 2.5; // cm between slats
    const pitch = slatW + gapCm;
    const count = Math.max(3, Math.floor((mod.w + gapCm) / pitch));
    const used = count * slatW + (count - 1) * gapCm;
    const startX = -used / 2;
    for (let i = 0; i < count; i++) {
      const slat = new THREE.Mesh(
        new THREE.BoxGeometry(slatW * CM, mod.h * CM, Math.max(1.5, mod.d) * CM),
        mat.clone()
      );
      const x = (startX + slatW / 2 + i * pitch) * CM;
      slat.position.set(x, mod.h * CM / 2, 0);
      slat.castShadow = true;
      group.add(slat);
    }
    // Gap label between first two slats (yalnızca ölçü modunda)
    if (count >= 2 && state.showMeasure) {
      const el = document.createElement('div');
      el.textContent = gapCm.toFixed(1).replace(/\.0$/, '') + ' cm';
      el.style.cssText = 'color:#0058a3;background:rgba(255,255,255,.92);padding:2px 7px;border-radius:6px;font:700 11px Montserrat,sans-serif;white-space:nowrap;border:1px solid #bfdbfe;';
      const obj = new CSS2DObject(el);
      const midX = (startX + slatW + gapCm / 2) * CM;
      obj.position.set(midX, mod.h * CM * 0.55, mod.d * CM / 2 + 0.04);
      obj.name = 'slat-gap';
      group.add(obj);
    }
    group.userData.originAtBottom = true;
    group.userData.slatMeta = { count, slatW, gapCm };
  } else if (mod.type === 'top') {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, Math.max(1.8, mod.h) * CM, mod.d * CM),
      mat
    );
    mesh.position.y = (Math.max(1.8, mod.h) * CM) / 2;
    mesh.castShadow = true;
    group.add(mesh);
    group.userData.originAtBottom = true;
  } else if (mod.type === 'leg') {
    const positions = [
      [-mod.w * CM / 2 + 0.03, 0, -mod.d * CM / 2 + 0.03],
      [mod.w * CM / 2 - 0.03, 0, -mod.d * CM / 2 + 0.03],
      [-mod.w * CM / 2 + 0.03, 0, mod.d * CM / 2 - 0.03],
      [mod.w * CM / 2 - 0.03, 0, mod.d * CM / 2 - 0.03],
    ];
    for (const p of positions) {
      const leg = new THREE.Mesh(
        new THREE.CylinderGeometry(0.012, 0.014, mod.h * CM, 12),
        mat.clone()
      );
      leg.position.set(p[0], mod.h * CM / 2, p[2]);
      leg.castShadow = true;
      group.add(leg);
    }
    group.userData.originAtBottom = true;
  }

  const helper = new THREE.BoxHelper(group, 0x0058a3);
  helper.visible = false;
  helper.name = 'selection';
  group.add(helper);

  if (state.showMeasure && mod.id === state.selectedId) {
    addMeasureLabels(group, mod);
  }

  placeModule(group, mod);
  return group;
}

async function buildDoorMesh(group, mod, mat) {
  const style = mod.doorStyle || 'hinge';
  const W = mod.w * CM;
  const H = mod.h * CM;
  const D = Math.max(1.6, mod.d) * CM;

  if (style === 'drawer') {
    const body = new THREE.Mesh(new THREE.BoxGeometry(W, H, Math.max(D, 28 * CM)), mat);
    body.position.y = H / 2;
    body.castShadow = true;
    group.add(body);
    const face = new THREE.Mesh(
      new THREE.BoxGeometry(W, H, 1.8 * CM),
      await makeMaterial(mod)
    );
    face.position.set(0, H / 2, Math.max(D, 28 * CM) / 2 + 0.005);
    face.castShadow = true;
    group.add(face);
    const handle = new THREE.Mesh(
      new THREE.BoxGeometry(W * 0.35, 0.008, 0.012),
      new THREE.MeshStandardMaterial({ color: 0x888888, metalness: 0.85, roughness: 0.2 })
    );
    handle.position.set(0, H / 2, Math.max(D, 28 * CM) / 2 + 0.02);
    group.add(handle);
  } else if (style === 'glass') {
    const frameColor = mod.frameColor || '#c0c4c8';
    const glassColor = mod.glassColor || '#c5d5e8';
    const frameMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(frameColor),
      metalness: 0.85,
      roughness: 0.25,
    });
    const t = 2.2 * CM;
    const parts = [
      { s: [W, t, D], p: [0, t / 2, 0] },
      { s: [W, t, D], p: [0, H - t / 2, 0] },
      { s: [t, H - 2 * t, D], p: [-W / 2 + t / 2, H / 2, 0] },
      { s: [t, H - 2 * t, D], p: [W / 2 - t / 2, H / 2, 0] },
    ];
    for (const part of parts) {
      const m = new THREE.Mesh(new THREE.BoxGeometry(...part.s), frameMat.clone());
      m.position.set(...part.p);
      m.castShadow = true;
      group.add(m);
    }
    const glass = new THREE.Mesh(
      new THREE.BoxGeometry(W - t * 2, H - t * 2, D * 0.4),
      new THREE.MeshPhysicalMaterial({
        color: new THREE.Color(glassColor),
        roughness: 0.05,
        metalness: 0.05,
        transmission: glassColor === '#e2e8f0' ? 0.05 : 0.65,
        transparent: true,
        opacity: glassColor === '#e2e8f0' ? 0.95 : 0.4,
        thickness: 0.02,
      })
    );
    glass.position.set(0, H / 2, 0);
    group.add(glass);
  } else {
    // hinge or push front panel
    const panel = new THREE.Mesh(new THREE.BoxGeometry(W, H, D), mat);
    panel.position.y = H / 2;
    panel.castShadow = true;
    group.add(panel);
    if (style === 'hinge') {
      const handle = new THREE.Mesh(
        new THREE.CylinderGeometry(0.005, 0.005, 0.12, 12),
        new THREE.MeshStandardMaterial({ color: 0x888888, metalness: 0.8, roughness: 0.25 })
      );
      handle.rotation.z = Math.PI / 2;
      handle.position.set(W * 0.35, H / 2, D / 2 + 0.01);
      group.add(handle);
    } else {
      // push-open: no handle, small tip mark
      const tip = new THREE.Mesh(
        new THREE.SphereGeometry(0.008, 10, 10),
        new THREE.MeshStandardMaterial({ color: 0x666666, metalness: 0.5, roughness: 0.3 })
      );
      tip.position.set(0, H * 0.15, D / 2 + 0.008);
      group.add(tip);
    }
  }
  group.userData.originAtBottom = true;
}

function addMeasureLabels(group, mod) {
  const mk = (text, x, y, z) => {
    const el = document.createElement('div');
    el.textContent = text;
    el.style.cssText = 'color:#111;background:rgba(255,255,255,.9);padding:2px 6px;border-radius:6px;font:600 11px Montserrat,sans-serif;white-space:nowrap;';
    const obj = new CSS2DObject(el);
    obj.position.set(x, y, z);
    obj.name = 'measure';
    group.add(obj);
  };
  const W = mod.w * CM, H = mod.h * CM, D = Math.max(mod.d, 2) * CM;
  mk(mod.w.toFixed(0), 0, H + 0.05, D / 2);
  mk(mod.h.toFixed(0), W / 2 + 0.05, H / 2, 0);
  mk(mod.d.toFixed(0), 0, 0.04, -D / 2 - 0.04);
}

function placeModule(group, mod) {
  // All modules: position.y is bottom of piece in cm
  group.position.set((mod.x || 0) * CM, (mod.y || 0) * CM, (mod.z || 0) * CM);
}

function disposeObject(obj) {
  obj.traverse((o) => {
    if (o.isCSS2DObject && o.element?.parentNode) {
      o.element.parentNode.removeChild(o.element);
    }
    if (o.geometry) o.geometry.dispose();
    if (o.material) {
      if (Array.isArray(o.material)) o.material.forEach((m) => m.dispose());
      else o.material.dispose();
    }
  });
}

function clearAllLabels() {
  if (labelRenderer?.domElement) {
    labelRenderer.domElement.replaceChildren();
  }
}

async function rebuildModules() {
  clearAllLabels();
  while (modulesGroup.children.length) {
    const c = modulesGroup.children[0];
    modulesGroup.remove(c);
    disposeObject(c);
  }
  for (const mod of state.modules) {
    modulesGroup.add(await createModuleMesh(mod));
  }
  updateSelectionVisual();
  if (state.showTv) buildTv();
}

function updateSelectionVisual() {
  modulesGroup.children.forEach((g) => {
    const helper = g.children.find((c) => c.name === 'selection');
    if (!helper) return;
    const id = g.userData.moduleId;
    const selected = id === state.selectedId;
    const hovered = id === state.hoveredId && !selected;
    helper.visible = selected || hovered;
    if (helper.visible) {
      helper.material.color.setHex(selected ? 0x0058a3 : 0x38bdf8);
      helper.update();
    }
  });
  const bar = $('#selection-bar');
  if (bar) bar.hidden = !state.selectedId;
  renderer.domElement.style.cursor = state.hoveredId || state.selectedId ? 'pointer' : 'default';
}

/** Kapak/çekmece kabul eden gövdeler — duvar paneli hariç */
const FRONT_HOST_TYPES = new Set(['frame', 'plinth', 'shelf']);
const HOST_TYPES = new Set(['frame', 'plinth', 'shelf', 'wallPanel']);
const ATTACH_FRONT = new Set(['door', 'slat']);
const ATTACH_BACK = new Set(['back']);
const ATTACH_TOP = new Set(['top']);

function isHost(m) {
  return m && HOST_TYPES.has(m.type);
}

function canTakeFronts(m) {
  return m && FRONT_HOST_TYPES.has(m.type);
}

function wallFlushZ(depthCm) {
  return -(FLOOR_D / 2) + (depthCm || 2) / 2 + 0.4;
}

function sendPanelToWall(mod) {
  if (!mod || (mod.type !== 'wallPanel' && mod.type !== 'back' && mod.type !== 'slat')) return;
  mod.z = wallFlushZ(mod.d);
  if (mod.type === 'wallPanel' || mod.type === 'slat') {
    // Duvar paneli bankonun arkasında kalır
    const bench = state.modules.find((m) => m.type === 'plinth' || (m.type === 'frame' && m.h <= 50));
    if (bench && mod.z >= bench.z - bench.d / 2) {
      mod.z = Math.min(mod.z, bench.z - bench.d / 2 - mod.d / 2 - 1);
      mod.z = Math.max(mod.z, wallFlushZ(mod.d));
    }
  }
}

function childrenOf(hostId) {
  return state.modules.filter((m) => m.parentId === hostId);
}

function findNearestHost(x, z, maxDist = 55) {
  let best = null;
  let bestD = maxDist;
  for (const m of state.modules) {
    if (!isHost(m)) continue;
    const dx = Math.abs(x - m.x);
    const dz = Math.abs(z - m.z);
    const xOverlap = dx < (m.w / 2 + 25);
    const dist = Math.sqrt(dx * dx + dz * dz);
    if (xOverlap && dist < bestD) {
      bestD = dist;
      best = m;
    }
  }
  return best;
}

/** Pose a part flush to host — bayIndex / yOffset / relX korunur */
function attachToHost(host, type, extras = {}) {
  const doorStyle = extras.doorStyle || 'push';
  const baseY = host.y || 0;

  if (type === 'back') {
    if (host.type === 'wallPanel') {
      return null; // duvar paneli zaten plaka
    }
    const thicknessMm = extras.thicknessMm || 18;
    const d = extras.d != null ? extras.d : thicknessMm / 10; // mm → cm
    const h = extras.h || host.h;
    const y = extras.yOffset != null ? baseY + extras.yOffset : baseY;
    return {
      w: extras.w || host.w,
      h,
      d,
      thicknessMm,
      x: extras.relX != null ? host.x + extras.relX : host.x,
      y,
      z: host.z - host.d / 2 - d / 2 - 0.2,
    };
  }

  if (type === 'slat') {
    const d = extras.d || 3;
    const h = extras.h || host.h;
    const y = extras.yOffset != null ? baseY + extras.yOffset : baseY;
    return {
      w: extras.w || host.w,
      h,
      d,
      x: extras.relX != null ? host.x + extras.relX : host.x,
      y,
      z: host.z + host.d / 2 + d / 2 + 0.3,
      slatWidth: extras.slatWidth || 1.6,
      slatGap: extras.slatGap != null ? extras.slatGap : 2.5,
    };
  }

  if (type === 'door') {
    if (!canTakeFronts(host)) return null;
    const bazaH = (host.type === 'plinth' && host.baza) ? (host.bazaH || 8) : 0;
    const bays = Math.max(1, extras.bays || bayCountForWidth(host.w));
    const bayIndex = extras.bayIndex != null ? extras.bayIndex : 0;
    if (doorStyle === 'drawer') {
      const usable = host.h - bazaH;
      const rows = extras.rows || (usable >= 50 ? 2 : 1);
      const row = extras.drawerRow != null ? extras.drawerRow : 0;
      const rowH = (usable - 3) / rows;
      const yOff = bazaH + 1.5 + row * rowH;
      return {
        w: Math.max(20, host.w - 2),
        h: Math.max(12, rowH - 0.5),
        d: Math.max(18, host.d - 4),
        x: host.x,
        y: baseY + yOff,
        z: host.z,
        drawerRow: row,
        rows,
        yOffset: yOff,
      };
    }
    const doorW = extras.w || (host.w / bays - 1.2);
    const left = host.x - host.w / 2;
    const cx = extras.x != null
      ? extras.x
      : left + doorW / 2 + 0.6 + bayIndex * (doorW + 1.2);
    return {
      w: doorW,
      h: Math.max(12, extras.h || host.h - bazaH - 2),
      d: 2,
      x: cx,
      y: baseY + bazaH + 1,
      z: host.z + host.d / 2 + 1.1,
      bayIndex,
      bays,
    };
  }

  if (type === 'shelf' || type === 'innerShelf') {
    const yOff = extras.yOffset != null ? extras.yOffset : host.h / 2;
    return {
      w: host.w - 3.6,
      h: 1.8,
      d: host.d - 2.5,
      x: host.x,
      y: baseY + yOff,
      z: host.z,
      interior: true,
      yOffset: yOff,
    };
  }

  if (type === 'leg') {
    const h = extras.h || 10;
    return {
      w: host.w, h, d: host.d,
      x: host.x, y: 0, z: host.z,
    };
  }

  if (type === 'top') {
    return {
      w: host.w + 2,
      h: 2.5,
      d: host.d + 2,
      x: host.x,
      y: baseY + host.h,
      z: host.z,
    };
  }
  return null;
}

function syncAttachedToHost(host) {
  childrenOf(host.id).forEach((child) => {
    const pose = attachToHost(host, child.type, {
      doorStyle: child.doorStyle,
      d: child.d,
      h: child.h,
      w: child.w,
      bayIndex: child.bayIndex,
      bays: child.bays,
      drawerRow: child.drawerRow,
      rows: child.rows,
      yOffset: child.yOffset,
      relX: child.relX,
      slatWidth: child.slatWidth,
      slatGap: child.slatGap,
      thicknessMm: child.thicknessMm,
      x: child.bayIndex != null ? undefined : child.x,
    });
    if (!pose) return;
    child.w = pose.w;
    child.h = pose.h;
    child.d = pose.d;
    child.x = pose.x;
    child.y = pose.y;
    child.z = pose.z;
    if (pose.bayIndex != null) child.bayIndex = pose.bayIndex;
    if (pose.bays != null) child.bays = pose.bays;
    if (pose.drawerRow != null) child.drawerRow = pose.drawerRow;
    if (pose.yOffset != null) child.yOffset = pose.yOffset;
    if (pose.slatWidth != null) child.slatWidth = pose.slatWidth;
    if (pose.slatGap != null) child.slatGap = pose.slatGap;
    if (pose.thicknessMm != null) child.thicknessMm = pose.thicknessMm;
  });
}

/** Bench'teki yan yana hostları ayak yüksekliğine yükselt */
function raiseBenchHosts(sourceHost, legH) {
  const band = 12;
  state.modules.forEach((m) => {
    if (!isHost(m) || m.type === 'wallPanel') return;
    if (Math.abs(m.z - sourceHost.z) > band) return;
    // Aynı yatay bandodaki banko parçaları
    if (m.h > 80 && m.id !== sourceHost.id) return;
    m.y = legH;
    m.floatY = legH;
    syncAttachedToHost(m);
  });
}

function defaultMaterial(type, doorStyle) {
  if (type === 'door' && doorStyle === 'glass') {
    return { finish: 'gloss', color: '#c5d5e8', materialName: 'Cam' };
  }
  if (type === 'door') {
    return { finish: 'matte', color: '#e8e4df', materialName: 'Krem Mat' };
  }
  if (type === 'wallPanel') {
    return { finish: 'ceramic', color: '#f0ebe3', materialName: 'Mermer / Taş' };
  }
  if (type === 'plinth') {
    return { finish: 'matte', color: '#e8e4df', materialName: 'Krem Mat' };
  }
  if (type === 'slat') {
    return { finish: 'matte', color: '#c4a574', materialName: 'Ahşap çıta' };
  }
  if (type === 'top') {
    return { finish: 'ceramic', color: '#e8e4df', materialName: 'Seramik' };
  }
  if (type === 'back') {
    return { finish: 'ceramic', color: '#f5f0ea', materialName: 'Panel' };
  }
  return { finish: 'matte', color: '#f7f7f7', materialName: 'Beyaz' };
}

function addAttachedPart(type, opts = {}) {
  const host = opts.host
    || (selected() && isHost(selected()) ? selected() : null)
    || state.modules.filter((m) => isHost(m)).at(-1);
  if (!host) {
    showHint('Önce iskelet, alt blok veya raf seçin');
    return;
  }
  if ((type === 'door' || type === 'slat') && !canTakeFronts(host)) {
    showHint('Duvar paneline kapak eklenmez — düz plaka / kaplama kullanın');
    return;
  }
  const doorStyle = opts.doorStyle || (type === 'door' ? 'push' : undefined);
  const pose = attachToHost(host, type, { doorStyle, ...opts });
  if (!pose) return;
  const mat = defaultMaterial(type, doorStyle);
  pushHistory();
  const mod = {
    id: uid(),
    type,
    label: opts.label || (type === 'door'
      ? (DOOR_STYLES.find((d) => d.id === doorStyle)?.label || 'Kapak')
      : type === 'back'
        ? `Arkalık ${opts.thicknessMm || pose.thicknessMm || 18} mm`
        : typeTitle(type)),
    parentId: host.id,
    ...pose,
    finish: mat.finish,
    color: opts.color || mat.color,
    materialId: null,
    materialImage: null,
    materialName: mat.materialName,
    materialCode: '',
    doorStyle,
    frameColor: doorStyle === 'glass' ? '#c0c4c8' : undefined,
    glassColor: doorStyle === 'glass' ? '#c5d5e8' : undefined,
    led: opts.led,
    thicknessMm: pose.thicknessMm || opts.thicknessMm,
    bayIndex: pose.bayIndex,
    bays: pose.bays,
    drawerRow: pose.drawerRow,
    yOffset: pose.yOffset,
    relX: opts.relX,
  };
  state.modules.push(mod);
  if (type === 'leg') {
    raiseBenchHosts(host, mod.h);
  }
  state.selectedId = mod.id;
  rebuildModules().then(() => {
    state.view = 'customize';
    renderSidebar();
    showHint(`${mod.label} → ${typeTitle(host.type)}`);
  });
}

/** BESTÅ: geniş iskelete 60 cm bölmeli ön kapaklar */
function bayCountForWidth(w) {
  return Math.max(1, Math.round(w / BAY_W));
}

function addFrontsToHost(host, doorStyle) {
  if (!host || !canTakeFronts(host)) {
    showHint('Kapak yalnızca iskelet, alt blok veya raf ünitesine eklenir');
    return;
  }
  const oldDoors = childrenOf(host.id).filter((c) => c.type === 'door');
  pushHistory();
  if (oldDoors.length) {
    const kill = new Set(oldDoors.map((d) => d.id));
    state.modules = state.modules.filter((m) => !kill.has(m.id));
  }

  const style = doorStyle || 'push';
  const mat = defaultMaterial('door', style);

  if (style === 'drawer') {
    const rows = host.h >= 50 ? 2 : 1;
    for (let r = 0; r < rows; r++) {
      const pose = attachToHost(host, 'door', {
        doorStyle: 'drawer',
        drawerRow: r,
        rows,
      });
      state.modules.push({
        id: uid(),
        type: 'door',
        label: 'Çekmece',
        parentId: host.id,
        ...pose,
        finish: mat.finish,
        color: mat.color,
        materialId: null,
        materialImage: null,
        materialName: mat.materialName,
        materialCode: '',
        doorStyle: 'drawer',
        drawerRow: r,
        rows,
        yOffset: pose.yOffset,
      });
    }
  } else {
    const bays = bayCountForWidth(host.w);
    for (let i = 0; i < bays; i++) {
      const pose = attachToHost(host, 'door', {
        doorStyle: style,
        bayIndex: i,
        bays,
      });
      state.modules.push({
        id: uid(),
        type: 'door',
        label: DOOR_STYLES.find((d) => d.id === style)?.label || 'Kapak',
        parentId: host.id,
        ...pose,
        finish: mat.finish,
        color: style === 'glass' ? '#c5d5e8' : mat.color,
        materialId: null,
        materialImage: null,
        materialName: mat.materialName,
        materialCode: '',
        doorStyle: style,
        bayIndex: i,
        bays,
        frameColor: style === 'glass' ? '#c0c4c8' : undefined,
        glassColor: style === 'glass' ? '#c5d5e8' : undefined,
      });
    }
  }

  state.selectedId = host.id;
  rebuildModules().then(() => {
    state.view = 'customize';
    renderSidebar();
    showHint('Ön paneller eklendi — kaplama seçebilirsiniz');
  });
}

function addInteriorShelf(host) {
  if (!host) return;
  const existing = childrenOf(host.id).filter((c) => c.type === 'shelf' && c.interior);
  const n = existing.length + 1;
  const slots = Math.max(n + 1, 3);
  const yOffset = (host.h / slots) * n;
  // Mevcut rafları yeniden dağıt
  existing.forEach((s, i) => {
    s.yOffset = (host.h / (n + 1)) * (i + 1);
  });
  if (existing.length) syncAttachedToHost(host);
  addAttachedPart('shelf', {
    host,
    label: 'İç raf',
    yOffset: (host.h / (n + 1)) * n,
    led: true,
  });
}

/** Yan yana iskeletlerden TV bankosu kur (BESTÅ akışı) */
function startTvBench(presetId) {
  const preset = TV_BENCH_PRESETS.find((p) => p.id === presetId) || TV_BENCH_PRESETS[1];
  pushHistory();
  state.modules = [];
  const totalW = preset.bays * preset.bayW;
  const startX = -totalW / 2 + preset.bayW / 2;
  const z = -(FLOOR_D / 2) + preset.d / 2 + 2;
  let firstId = null;
  for (let i = 0; i < preset.bays; i++) {
    const id = uid();
    if (!firstId) firstId = id;
    state.modules.push({
      id,
      type: 'frame',
      label: 'İskelet',
      w: preset.bayW,
      h: preset.h,
      d: preset.d,
      x: startX + i * preset.bayW,
      y: 0,
      z,
      finish: 'matte',
      color: '#f7f7f7',
      materialId: null,
      materialImage: null,
      materialName: 'Beyaz',
      materialCode: '',
    });
  }
  state.showTv = true;
  state.tvInch = preset.inch;
  buildTv();
  state.selectedId = firstId;
  rebuildModules().then(() => {
    state.view = 'customize';
    renderSidebar();
    $('#fab-tv')?.classList.add('active');
    showHint(`${preset.label} hazır — iskelete kapak / çekmece ekleyin`);
  });
}

function pushMod(partial) {
  const id = uid();
  state.modules.push({ id, materialId: null, materialImage: null, materialCode: '', ...partial });
  return id;
}

/** Referans görsellerdeki gibi hazır TV duvarı */
function startWallComposition(compId) {
  const comp = WALL_COMPOSITIONS.find((c) => c.id === compId) || WALL_COMPOSITIONS[0];
  pushHistory();
  state.modules = [];
  const panelZ = wallFlushZ(1.8);
  const benchZ = panelZ + 20;

  if (comp.build === 'pro420') {
    // 420 × 260 profesyonel: bazalı banko + bas-aç + yan raflar + düz panel
    const benchH = 40;
    const upperH = 220; // 40+220 = 260
    const totalW = 420;
    const towerW = 55;
    const panelW = 250;
    const benchId = pushMod({
      type: 'plinth', label: 'Alt blok 420', w: totalW, h: benchH, d: 40,
      x: 0, y: 0, z: benchZ, baza: true, bazaH: 8, led: false,
      finish: 'matte', color: '#e8e4df', materialName: 'Krem Mat',
    });
    const panelId = pushMod({
      type: 'wallPanel', label: 'Duvar paneli', w: panelW, h: upperH, d: 1.8, thicknessMm: 18,
      x: 0, y: benchH, z: panelZ, led: true,
      finish: 'ceramic', color: '#f0ebe3', materialName: 'Mermer / Taş',
    });
    pushMod({
      type: 'shelf', label: 'Sol raf kule', w: towerW, h: upperH, d: 35, led: true,
      x: -totalW / 2 + towerW / 2, y: benchH, z: benchZ - 3,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap',
    });
    pushMod({
      type: 'shelf', label: 'Sağ raf kule', w: towerW, h: upperH, d: 35, led: true,
      x: totalW / 2 - towerW / 2, y: benchH, z: benchZ - 3,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap',
    });
    // Üst kaplama banko üstü
    pushMod({
      type: 'top', label: 'Üst panel', w: totalW + 2, h: 2.5, d: 42,
      x: 0, y: benchH, z: benchZ,
      finish: 'matte', color: '#d4b896', materialName: 'Ahşap üst',
    });
    const bench = state.modules.find((m) => m.id === benchId);
    const bays = 7;
    for (let i = 0; i < bays; i++) {
      const pose = attachToHost(bench, 'door', { doorStyle: 'push', bayIndex: i, bays });
      pushMod({
        type: 'door', label: 'Bas-aç', parentId: benchId, ...pose,
        doorStyle: 'push', bayIndex: i, bays,
        finish: 'matte', color: '#e8e4df', materialName: 'Krem Mat',
      });
    }
    // Yan kulelere arkalık 8 mm
    state.modules.filter((m) => m.type === 'shelf' && m.h >= 200).forEach((tower) => {
      const pose = attachToHost(tower, 'back', { thicknessMm: 8 });
      if (pose) {
        pushMod({
          type: 'back', label: 'Arkalık 8 mm', parentId: tower.id, ...pose,
          thicknessMm: 8, finish: 'matte', color: '#f5f5f5', materialName: 'Beyaz arkalık',
        });
      }
    });
    state.selectedId = panelId;
  } else if (comp.build === 'marble') {
    // Yüzen banko 240 + bas-aç · mermer panel · sağ çıta · sol kule
    const floatY = 18;
    const benchId = pushMod({
      type: 'plinth', label: 'Yüzen banko', w: 240, h: 35, d: 40,
      x: 0, y: floatY, z: benchZ, floatY, baza: false, led: true,
      finish: 'matte', color: '#e8e4df', materialName: 'Krem Mat',
    });
    const panelId = pushMod({
      type: 'wallPanel', label: 'Mermer panel', w: 140, h: 200, d: 1.8, thicknessMm: 18,
      x: 10, y: floatY + 35, z: panelZ, led: true, metalStrips: true,
      finish: 'ceramic', color: '#f2ebe3', materialName: 'Mermer',
    });
    pushMod({
      type: 'slat', label: 'Çıta duvar', w: 55, h: 200, d: 3,
      x: 10 + 70 + 27.5, y: floatY + 35, z: panelZ + 2,
      slatWidth: 2, slatGap: 1.5,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap çıta',
    });
    pushMod({
      type: 'shelf', label: 'Kule raf', w: 40, h: 200, d: 35, led: true,
      x: 10 - 70 - 20, y: floatY + 35, z: benchZ - 2,
      finish: 'matte', color: '#d4b896', materialName: 'Ahşap',
    });
    pushMod({
      type: 'frame', label: 'Cam kule', w: 40, h: 200, d: 35,
      x: 10 - 70 - 20 - 42, y: floatY + 35, z: benchZ - 2,
      finish: 'matte', color: '#f7f7f7', materialName: 'Beyaz',
    });
    const bench = state.modules.find((m) => m.id === benchId);
    const bays = 4;
    for (let i = 0; i < bays; i++) {
      const pose = attachToHost(bench, 'door', { doorStyle: 'push', bayIndex: i, bays });
      pushMod({
        type: 'door', label: 'Bas-aç', parentId: benchId, ...pose,
        doorStyle: 'push', bayIndex: i, bays,
        finish: 'matte', color: '#e8e4df', materialName: 'Krem Mat',
      });
    }
    state.selectedId = panelId;
  } else if (comp.build === 'wood') {
    const floatY = 15;
    const benchId = pushMod({
      type: 'plinth', label: 'Yüzen banko', w: 220, h: 32, d: 38,
      x: 0, y: floatY, z: benchZ, floatY, baza: false, led: true,
      finish: 'matte', color: '#ebe6df', materialName: 'Grej',
    });
    pushMod({
      type: 'wallPanel', label: 'Düz panel', w: 160, h: 210, d: 1.8, thicknessMm: 18,
      x: 0, y: floatY + 32, z: panelZ, led: true,
      finish: 'matte', color: '#f0ebe4', materialName: 'Panel',
    });
    pushMod({
      type: 'slat', label: 'Dikey çıtalar', w: 160, h: 210, d: 2.5,
      x: 0, y: floatY + 32, z: panelZ + 2.2,
      slatWidth: 2.2, slatGap: 1.2,
      finish: 'matte', color: '#b8956a', materialName: 'Ahşap çıta',
    });
    pushMod({
      type: 'shelf', label: 'Sol raf', w: 35, h: 180, d: 30, led: true,
      x: -100, y: floatY + 32, z: benchZ - 4,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap',
    });
    pushMod({
      type: 'shelf', label: 'Sağ raf', w: 35, h: 180, d: 30, led: true,
      x: 100, y: floatY + 32, z: benchZ - 4,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap',
    });
    const bench = state.modules.find((m) => m.id === benchId);
    const bays = 4;
    for (let i = 0; i < bays; i++) {
      const pose = attachToHost(bench, 'door', { doorStyle: 'push', bayIndex: i, bays });
      pushMod({
        type: 'door', label: 'Bas-aç', parentId: benchId, ...pose,
        doorStyle: 'push', bayIndex: i, bays,
        finish: 'matte', color: '#ebe6df', materialName: 'Grej',
      });
    }
    state.selectedId = benchId;
  } else if (comp.build === 'sym') {
    const benchId = pushMod({
      type: 'plinth', label: 'Alt blok', w: 200, h: 40, d: 40,
      x: 0, y: 0, z: benchZ, baza: true, bazaH: 8,
      finish: 'matte', color: '#e8e4df', materialName: 'Krem',
    });
    pushMod({
      type: 'wallPanel', label: 'Orta taş panel', w: 120, h: 220, d: 1.8, thicknessMm: 18,
      x: 0, y: 40, z: panelZ, led: true,
      finish: 'ceramic', color: '#ebe6de', materialName: 'Traverten',
    });
    pushMod({
      type: 'shelf', label: 'Sol kule', w: 45, h: 220, d: 35, led: true,
      x: -82.5, y: 0, z: benchZ - 2,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap',
    });
    pushMod({
      type: 'shelf', label: 'Sağ kule', w: 45, h: 220, d: 35, led: true,
      x: 82.5, y: 0, z: benchZ - 2,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap',
    });
    const bench = state.modules.find((m) => m.id === benchId);
    const bays = 3;
    for (let i = 0; i < bays; i++) {
      const pose = attachToHost(bench, 'door', { doorStyle: 'push', bayIndex: i, bays });
      pushMod({
        type: 'door', label: 'Bas-aç', parentId: benchId, ...pose,
        doorStyle: 'push', bayIndex: i, bays,
        finish: 'matte', color: '#e8e4df', materialName: 'Krem',
      });
    }
    state.selectedId = benchId;
  } else {
    // float minimal
    const floatY = 20;
    const benchId = pushMod({
      type: 'plinth', label: 'Yüzen banko', w: 200, h: 32, d: 38,
      x: 0, y: floatY, z: benchZ, floatY, baza: false, led: true,
      finish: 'matte', color: '#f5f0ea', materialName: 'Krem',
    });
    pushMod({
      type: 'wallPanel', label: 'Düz panel', w: 180, h: 160, d: 1.8, thicknessMm: 18,
      x: 0, y: floatY + 32 + 10, z: panelZ, led: true,
      finish: 'matte', color: '#f0ebe4', materialName: 'Panel',
    });
    pushMod({
      type: 'top', label: 'Üst kaplama', w: 202, h: 2.5, d: 40,
      x: 0, y: floatY + 32, z: benchZ,
      finish: 'matte', color: '#d4b896', materialName: 'Ahşap üst',
    });
    const bench = state.modules.find((m) => m.id === benchId);
    const bays = 3;
    for (let i = 0; i < bays; i++) {
      const pose = attachToHost(bench, 'door', { doorStyle: 'push', bayIndex: i, bays });
      pushMod({
        type: 'door', label: 'Bas-aç', parentId: benchId, ...pose,
        doorStyle: 'push', bayIndex: i, bays,
        finish: 'matte', color: '#f5f0ea', materialName: 'Krem',
      });
    }
    state.selectedId = benchId;
  }

  // Tüm panelleri duvara yasla
  state.modules.filter((m) => m.type === 'wallPanel').forEach(sendPanelToWall);

  state.showTv = true;
  state.tvInch = comp.inch;
  buildTv();
  rebuildModules().then(() => {
    state.view = 'home';
    renderSidebar();
    $('#fab-tv')?.classList.add('active');
    // Kamerayı üniteye bakacak şekilde ayarla
    const span = Math.max(...state.modules.map((m) => m.w), 200);
    camera.position.set(span * CM * 0.55, 1.6, 4.2);
    controls.target.set(0, 1.1, 0);
    controls.update();
    showHint(`${comp.label} hazır`, 3200);
  });
}

function clearDesign() {
  pushHistory();
  state.modules = [];
  state.selectedId = null;
  state.hoveredId = null;
  state.showTv = false;
  localStorage.removeItem('tv-configurator-design');
  buildTv();
  rebuildModules();
  clearAllLabels();
  state.view = 'home';
  renderSidebar();
  $('#fab-tv')?.classList.remove('active');
  showHint('Oda temizlendi');
}

/** Place next independent module to the RIGHT of existing ones. */
function nextFreeSlot(w, d) {
  const wallZ = -(FLOOR_D / 2) + d / 2 + 2;
  if (!state.modules.length) {
    return { x: 0, y: 0, z: wallZ };
  }
  const right = Math.max(...state.modules.map((m) => m.x + m.w / 2));
  return { x: right + w / 2 + GAP, y: 0, z: wallZ };
}

function floorPointFromClient(clientX, clientY) {
  const rect = renderer.domElement.getBoundingClientRect();
  pointer.x = ((clientX - rect.left) / rect.width) * 2 - 1;
  pointer.y = -((clientY - rect.top) / rect.height) * 2 + 1;
  raycaster.setFromCamera(pointer, camera);
  const floor = roomGroup.children.find((c) => c.name === 'floor');
  const hits = raycaster.intersectObject(floor);
  if (!hits.length) return null;
  return hits[0].point;
}

function placeFromDrop(data, clientX, clientY) {
  const preset = { ...data };
  const pt = floorPointFromClient(clientX, clientY);
  const attachable = ATTACH_FRONT.has(preset.type) || ATTACH_BACK.has(preset.type) || ATTACH_TOP.has(preset.type);

  // Prefer selected host, else nearest under drop
  let host = selected() && isHost(selected()) ? selected() : null;
  if (!host && pt) {
    host = findNearestHost(pt.x / CM, pt.z / CM);
  }
  if (attachable && host) {
    addAttachedPart(preset.type, {
      host,
      doorStyle: preset.doorStyle,
      label: preset.label,
      d: preset.d,
      h: preset.h,
      slatWidth: 1.6,
      slatGap: 2.5,
    });
    return;
  }

  let pos;
  if (pt) {
    pos = {
      x: Math.round((pt.x / CM) / SNAP) * SNAP,
      y: 0,
      z: Math.max(-(FLOOR_D / 2) + preset.d / 2 + 1, Math.round((pt.z / CM) / SNAP) * SNAP),
    };
  } else {
    pos = nextFreeSlot(preset.w, preset.d);
  }
  addModule(preset.type, preset, pos);
}

function addModule(type, preset = null, pos = null) {
  const list = CATALOG[type];
  const p = preset || (list && list[0]) || { w: 60, d: 40, h: 38, label: typeTitle(type) };
  const doorStyle = p.doorStyle || (type === 'door' ? 'hinge' : undefined);
  const attachable = ATTACH_FRONT.has(type) || ATTACH_BACK.has(type) || ATTACH_TOP.has(type);
  const host = selected() && isHost(selected()) ? selected() : null;

  // Click-add attachable while host selected → attach
  if (attachable && host && !pos) {
    addAttachedPart(type, {
      host,
      doorStyle,
      label: p.label,
      d: p.d,
      h: p.h,
      slatWidth: 1.6,
      slatGap: 2.5,
    });
    return;
  }

  const mat = defaultMaterial(type, doorStyle);
  const slot = pos || nextFreeSlot(p.w, p.d);
  const floatY = p.floatY != null ? p.floatY : (type === 'wallPanel' ? 40 : 0);
  let z = slot.z;
  let y = slot.y != null ? slot.y : floatY;
  if (type === 'wallPanel' || (type === 'slat' && p.h >= 150)) {
    z = wallFlushZ(p.d || 1.8);
    if (!pos) y = floatY;
  } else if (p.floatY != null) {
    y = p.floatY;
  }
  if (type === 'plinth' && p.baza) {
    y = 0;
  }

  pushHistory();
  const mod = {
    id: uid(),
    type,
    label: p.label || typeTitle(type),
    w: p.w,
    h: p.h,
    d: p.thicknessMm ? p.thicknessMm / 10 : p.d,
    x: slot.x,
    y,
    z,
    floatY: p.floatY,
    baza: type === 'plinth' ? (p.baza !== false && !p.floatY) : undefined,
    bazaH: type === 'plinth' ? (p.bazaH || 8) : undefined,
    thicknessMm: p.thicknessMm || (type === 'wallPanel' || type === 'back' ? Math.round((p.d || 1.8) * 10) : undefined),
    led: !!p.led,
    metalStrips: !!p.metalStrips,
    finish: mat.finish,
    color: mat.color,
    materialId: null,
    materialImage: null,
    materialName: mat.materialName,
    materialCode: '',
    doorStyle,
    priceHint: p.price,
    frameColor: doorStyle === 'glass' ? '#c0c4c8' : undefined,
    glassColor: doorStyle === 'glass' ? '#c5d5e8' : undefined,
    slatWidth: type === 'slat' ? 1.6 : undefined,
    slatGap: type === 'slat' ? 2.5 : undefined,
  };
  state.modules.push(mod);
  state.selectedId = mod.id;
  rebuildModules().then(() => {
    if (type === 'wallPanel') buildTv();
    state.view = 'customize';
    renderSidebar();
    showHint(mod.label + ' eklendi');
  });
}

function deleteSelected() {
  const mod = selected();
  if (!mod) return;
  pushHistory();
  const kill = new Set([mod.id]);
  childrenOf(mod.id).forEach((c) => kill.add(c.id));
  state.modules = state.modules.filter((m) => !kill.has(m.id));
  state.selectedId = null;
  state.view = 'home';
  rebuildModules();
  renderSidebar();
  hideCtx();
}

function duplicateSelected() {
  const mod = selected();
  if (!mod) return;
  pushHistory();
  const copy = {
    ...mod,
    id: uid(),
    x: mod.x + mod.w + GAP,
  };
  state.modules.push(copy);
  state.selectedId = copy.id;
  rebuildModules().then(() => {
    renderSidebar();
  });
  hideCtx();
}

function hideCtx() {
  ctxMenu?.classList.remove('open');
}

function openCtx(clientX, clientY, moduleId) {
  if (!ctxMenu) return;
  state.selectedId = moduleId;
  updateSelectionVisual();
  const host = $('#viewport');
  const rect = host.getBoundingClientRect();
  ctxMenu.classList.add('open');
  const mw = ctxMenu.offsetWidth || 180;
  const mh = ctxMenu.offsetHeight || 160;
  let left = clientX - rect.left;
  let top = clientY - rect.top;
  left = Math.min(left, rect.width - mw - 8);
  top = Math.min(top, rect.height - mh - 8);
  ctxMenu.style.left = Math.max(8, left) + 'px';
  ctxMenu.style.top = Math.max(8, top) + 'px';
}

function onResize() {
  const host = $('#canvas-host');
  const w = host.clientWidth;
  const h = host.clientHeight;
  camera.aspect = w / Math.max(1, h);
  camera.updateProjectionMatrix();
  renderer.setSize(w, h, false);
  labelRenderer.setSize(w, h);
}

function animate() {
  requestAnimationFrame(animate);
  controls.autoRotate = state.autoRotate;
  controls.update();
  renderer.render(scene, camera);
  labelRenderer.render(scene, camera);
}

function ndcFromEvent(e) {
  const rect = renderer.domElement.getBoundingClientRect();
  pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
  pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
}

function pickModule(e) {
  ndcFromEvent(e);
  raycaster.setFromCamera(pointer, camera);
  const hits = raycaster.intersectObjects(modulesGroup.children, true);
  for (const hit of hits) {
    let o = hit.object;
    while (o && !o.userData.moduleId) o = o.parent;
    if (o?.userData.moduleId) return o.userData.moduleId;
  }
  return null;
}

function onContextMenu(e) {
  e.preventDefault();
  const id = pickModule(e);
  if (!id) {
    hideCtx();
    return;
  }
  openCtx(e.clientX, e.clientY, id);
}

function onPointerDown(e) {
  if (e.button === 2) return; // right-click handled by contextmenu
  if (e.button !== 0) return;
  hideCtx();
  const id = pickModule(e);
  if (id) {
    const changed = state.selectedId !== id;
    state.selectedId = id;
    state.view = 'customize';
    updateSelectionVisual();
    renderSidebar();
    if (changed && state.showMeasure) rebuildModules();
    const mod = selected();
    if (mod) {
      controls.enabled = false;
      drag = { id: mod.id, moved: false, snapshot: JSON.stringify(state.modules) };
    }
  } else {
    state.selectedId = null;
    updateSelectionVisual();
    if (state.view === 'customize' || state.view === 'materials') {
      state.view = 'home';
      renderSidebar();
    }
  }
}

function onPointerMove(e) {
  // Hover highlight (sürükleme yokken)
  if (!drag) {
    const id = pickModule(e);
    if (id !== state.hoveredId) {
      state.hoveredId = id;
      updateSelectionVisual();
    }
    return;
  }
  ndcFromEvent(e);
  raycaster.setFromCamera(pointer, camera);
  const floor = roomGroup.children.find((c) => c.name === 'floor');
  const hits = raycaster.intersectObject(floor);
  if (!hits.length) return;
  const p = hits[0].point;
  const mod = state.modules.find((m) => m.id === drag.id);
  if (!mod) return;
  const nx = Math.round((p.x / CM) / SNAP) * SNAP;
  const nz = Math.round((p.z / CM) / SNAP) * SNAP;
  const minZ = wallFlushZ(mod.d);
  const maxZ = FLOOR_D / 2 - mod.d / 2 - 20;
  mod.x = Math.max(-WALL_W / 2 + mod.w / 2, Math.min(WALL_W / 2 - mod.w / 2, nx));
  mod.z = Math.max(minZ, Math.min(maxZ, nz));

  // Duvar paneli duvara yakınsa otomatik yasla
  if (mod.type === 'wallPanel' || (mod.type === 'slat' && mod.h >= 120)) {
    if (mod.z < minZ + 8) sendPanelToWall(mod);
  }

  // Snap attachable parts onto nearest host while dragging
  const attachable = ATTACH_FRONT.has(mod.type) || ATTACH_BACK.has(mod.type) || ATTACH_TOP.has(mod.type);
  if (attachable && mod.type !== 'wallPanel') {
    const host = findNearestHost(mod.x, mod.z, 40);
    if (host && canTakeFronts(host)) {
      const pose = attachToHost(host, mod.type, {
        doorStyle: mod.doorStyle,
        d: mod.d,
        h: mod.h,
        bayIndex: mod.bayIndex,
        bays: mod.bays,
        drawerRow: mod.drawerRow,
        rows: mod.rows,
        yOffset: mod.yOffset,
        slatWidth: mod.slatWidth,
        slatGap: mod.slatGap,
        thicknessMm: mod.thicknessMm,
      });
      if (pose) {
        mod.w = pose.w; mod.h = pose.h; mod.d = pose.d;
        mod.x = pose.x; mod.y = pose.y; mod.z = pose.z;
        mod.parentId = host.id;
        if (pose.bayIndex != null) mod.bayIndex = pose.bayIndex;
        if (pose.yOffset != null) mod.yOffset = pose.yOffset;
        if (pose.slatWidth != null) mod.slatWidth = pose.slatWidth;
        if (pose.slatGap != null) mod.slatGap = pose.slatGap;
        if (pose.thicknessMm != null) mod.thicknessMm = pose.thicknessMm;
      }
    }
  } else if (isHost(mod) && mod.type !== 'wallPanel') {
    for (const o of state.modules) {
      if (o.id === mod.id || !isHost(o) || o.type === 'wallPanel') continue;
      const gapL = Math.abs((mod.x - mod.w / 2) - (o.x + o.w / 2));
      const gapR = Math.abs((mod.x + mod.w / 2) - (o.x - o.w / 2));
      if (gapL < 4) mod.x = o.x + o.w / 2 + mod.w / 2;
      if (gapR < 4) mod.x = o.x - o.w / 2 - mod.w / 2;
      if (Math.abs(mod.z - o.z) < 3) mod.z = o.z;
    }
    syncAttachedToHost(mod);
  }

  drag.moved = true;
  const placeLive = (m) => {
    const g = modulesGroup.children.find((c) => c.userData.moduleId === m.id);
    if (g) placeModule(g, m);
  };
  placeLive(mod);
  if (isHost(mod)) childrenOf(mod.id).forEach(placeLive);
}

function onPointerUp() {
  if (drag?.moved && drag.snapshot) {
    state.history.push(drag.snapshot);
    if (state.history.length > 40) state.history.shift();
    state.future = [];
    if (state.showTv) buildTv();
  }
  drag = null;
  controls.enabled = true;
}

function menuItems() {
  return [
    { kind: 'wallPanel', label: 'Duvar paneli', icon: '▮', desc: 'TV arkası özellik paneli' },
    { kind: 'frame', label: 'İskeletler', icon: '□', desc: 'BESTÅ tarzı gövde — yan yana' },
    { kind: 'plinth', label: 'Alt blok / Banko', icon: '▄', desc: 'Yüzen veya yerde banko' },
    { kind: 'shelf', label: 'Raf / Kule', icon: '☰', desc: 'Açık raf · yan kule' },
    { kind: 'door', label: 'Kapaklar', icon: '▣', desc: 'Seçili bloğa yapışır' },
    { kind: 'back', label: 'Arkalık', icon: '▦', desc: '8–25 mm kalınlık seçin' },
    { kind: 'slat', label: 'Çıtalar', icon: '▥', desc: 'Duvar çıta · boşluk cm' },
    { kind: 'top', label: 'Üst paneller', icon: '▬', desc: 'Tezgah / üst' },
    { kind: 'leg', label: 'Ayaklar', icon: '⊓', desc: 'Metal / ahşap ayak' },
    { kind: 'tv', label: 'TV', icon: '▣', desc: 'İsteğe bağlı · inch' },
    { kind: 'materials', label: 'Malzeme & kaplama', icon: '◆', desc: 'Kastamonu renkleri' },
  ];
}

function typeTitle(type) {
  return ({
    frame: 'İskelet', door: 'Kapak', shelf: 'Raf', back: 'Arka panel',
    slat: 'Çıta', top: 'Üst panel', leg: 'Ayak', plinth: 'Alt blok',
    wallPanel: 'Duvar paneli',
  })[type] || 'Parça';
}

function clampNum(v, min, max) {
  const n = Number(v);
  if (Number.isNaN(n)) return min;
  return Math.min(max, Math.max(min, n));
}

function thumbStyle(kind, preset) {
  if (kind === 'door' && preset.doorStyle === 'glass') {
    return 'background:linear-gradient(135deg,#dbeafe,#93c5fd);border:6px solid #e5e7eb;box-sizing:border-box';
  }
  if (kind === 'door' && preset.doorStyle === 'drawer') {
    return 'background:linear-gradient(#fff 40%,#e5e7eb 40%,#e5e7eb 45%,#fff 45%,#fff 70%,#e5e7eb 70%,#e5e7eb 75%,#fff 75%)';
  }
  if (kind === 'slat') {
    return 'background:repeating-linear-gradient(90deg,#c4a574 0 6px,#b8925f 6px 10px)';
  }
  if (kind === 'plinth') {
    return 'background:#1a1a1a';
  }
  return 'background:linear-gradient(135deg,#fff,#e5e7eb);border:8px solid #f3f4f6;box-sizing:border-box';
}

/** Canvas örnek görselleri (iskelet / kapak / raf vb.) */
function presetThumbDataUrl(kind, preset) {
  const c = document.createElement('canvas');
  c.width = 320;
  c.height = 220;
  const ctx = c.getContext('2d');
  ctx.fillStyle = '#eef1f4';
  ctx.fillRect(0, 0, 320, 220);

  const maxW = 200, maxH = 140;
  const scale = Math.min(maxW / preset.w, maxH / Math.max(preset.h, 1));
  const rw = preset.w * scale;
  const rh = Math.max(preset.h * scale, 8);
  const rd = Math.min(preset.d * scale * 0.35, 28);
  const x = (320 - rw - rd) / 2;
  const y = (220 - rh - rd / 2) / 2 + 8;

  if (kind === 'frame' || kind === 'shelf') {
    // isometric open carcass
    ctx.fillStyle = '#f8fafc';
    ctx.strokeStyle = '#94a3b8';
    ctx.lineWidth = 2;
    // front face
    ctx.fillRect(x, y + rd / 2, rw, rh);
    ctx.strokeRect(x, y + rd / 2, rw, rh);
    // top
    ctx.beginPath();
    ctx.moveTo(x, y + rd / 2);
    ctx.lineTo(x + rd, y);
    ctx.lineTo(x + rw + rd, y);
    ctx.lineTo(x + rw, y + rd / 2);
    ctx.closePath();
    ctx.fillStyle = '#e2e8f0';
    ctx.fill();
    ctx.stroke();
    // side
    ctx.beginPath();
    ctx.moveTo(x + rw, y + rd / 2);
    ctx.lineTo(x + rw + rd, y);
    ctx.lineTo(x + rw + rd, y + rh);
    ctx.lineTo(x + rw, y + rh + rd / 2);
    ctx.closePath();
    ctx.fillStyle = '#cbd5e1';
    ctx.fill();
    ctx.stroke();
    // inner opening
    const m = Math.max(6, rw * 0.08);
    ctx.fillStyle = '#f1f5f9';
    ctx.fillRect(x + m, y + rd / 2 + m, rw - m * 2, rh - m * 2);
    ctx.strokeRect(x + m, y + rd / 2 + m, rw - m * 2, rh - m * 2);
    if (kind === 'shelf') {
      ctx.strokeStyle = '#94a3b8';
      ctx.beginPath();
      ctx.moveTo(x + m, y + rd / 2 + rh / 2);
      ctx.lineTo(x + rw - m, y + rd / 2 + rh / 2);
      ctx.stroke();
    }
  } else if (kind === 'wallPanel' || kind === 'back') {
    ctx.fillStyle = '#f2ebe3';
    ctx.fillRect(x, y, rw, rh);
    ctx.strokeStyle = '#c9a66b';
    ctx.lineWidth = 1;
    for (let i = 1; i < 4; i++) {
      const lx = x + (rw / 4) * i;
      ctx.beginPath();
      ctx.moveTo(lx, y);
      ctx.lineTo(lx, y + rh);
      ctx.stroke();
    }
    ctx.strokeStyle = '#94a3b8';
    ctx.strokeRect(x, y, rw, rh);
  } else if (kind === 'plinth') {
    ctx.fillStyle = '#1a1a1a';
    ctx.fillRect(x, y + rh * 0.55, rw + rd, rh * 0.45);
  } else if (kind === 'door' && preset.doorStyle === 'glass') {
    ctx.fillStyle = '#c0c4c8';
    ctx.fillRect(x, y, rw, rh);
    ctx.fillStyle = 'rgba(147,197,253,0.65)';
    ctx.fillRect(x + 8, y + 8, rw - 16, rh - 16);
    ctx.strokeStyle = '#64748b';
    ctx.strokeRect(x, y, rw, rh);
  } else if (kind === 'door' && preset.doorStyle === 'drawer') {
    ctx.fillStyle = '#f8fafc';
    ctx.strokeStyle = '#94a3b8';
    ctx.fillRect(x, y, rw, rh);
    ctx.strokeRect(x, y, rw, rh);
    ctx.beginPath();
    ctx.moveTo(x, y + rh / 3);
    ctx.lineTo(x + rw, y + rh / 3);
    ctx.moveTo(x, y + (2 * rh) / 3);
    ctx.lineTo(x + rw, y + (2 * rh) / 3);
    ctx.stroke();
  } else if (kind === 'slat') {
    const n = 8;
    const gap = rw / n;
    for (let i = 0; i < n; i++) {
      ctx.fillStyle = i % 2 ? '#c4a574' : '#b8925f';
      ctx.fillRect(x + i * gap, y, gap * 0.7, rh);
    }
  } else if (kind === 'top') {
    ctx.fillStyle = '#e8e4df';
    ctx.fillRect(x, y + rh * 0.6, rw + rd, rh * 0.25);
  } else if (kind === 'leg') {
    ctx.fillStyle = '#333';
    [[0, 0], [rw, 0], [0, rh], [rw, rh]].forEach(([dx, dy]) => {
      ctx.beginPath();
      ctx.ellipse(x + dx, y + dy, 6, 6, 0, 0, Math.PI * 2);
      ctx.fill();
    });
  } else {
    ctx.fillStyle = '#f8fafc';
    ctx.strokeStyle = '#94a3b8';
    ctx.fillRect(x, y, rw, rh);
    ctx.strokeRect(x, y, rw, rh);
  }

  ctx.fillStyle = '#64748b';
  ctx.font = '600 13px Montserrat,sans-serif';
  ctx.textAlign = 'center';
  ctx.fillText(`${preset.w}×${preset.d}×${preset.h}`, 160, 208);
  return c.toDataURL('image/png');
}

function renderSidebar() {
  btnBack.style.display = state.view === 'home' ? 'none' : 'inline-flex';

  if (state.view === 'home') {
    sideTitle.textContent = 'Kendi TV ünitenizi oluşturun';
    const hasSaved = !!localStorage.getItem('tv-configurator-design');
    sideBody.innerHTML = `
      <div class="panel-section" style="display:flex;gap:8px;flex-wrap:wrap">
        <button type="button" class="chip danger-chip" id="btn-clear-all" style="border-color:#fecaca;color:#b91c1c;background:#fef2f2">Temizle</button>
        ${hasSaved ? `<button type="button" class="chip" id="btn-load-saved">Kayıtlıyı yükle</button>` : ''}
      </div>
      <div class="panel-section">
        <div class="section-title">Hazır TV duvarı · tek tık</div>
        <div class="product-grid">
          ${WALL_COMPOSITIONS.map((p) => `
            <button type="button" class="product-card" data-wall="${p.id}">
              <div class="thumb"><img src="${presetThumbDataUrl('wallPanel', { w: 180, d: 3, h: 200 })}" alt=""></div>
              <div class="meta">
                <strong>${p.label}</strong>
                <span>${p.desc}</span>
              </div>
            </button>
          `).join('')}
        </div>
      </div>
      <div class="panel-section">
        <div class="section-title">Basit TV bankosu</div>
        <div class="product-grid">
          ${TV_BENCH_PRESETS.map((p) => `
            <button type="button" class="product-card" data-bench="${p.id}">
              <div class="thumb"><img src="${presetThumbDataUrl('frame', { w: p.bays * p.bayW, d: p.d, h: p.h })}" alt=""></div>
              <div class="meta">
                <strong>${p.label}</strong>
                <span>${p.bays}×${p.bayW} cm · TV ${p.inch}"</span>
              </div>
            </button>
          `).join('')}
        </div>
      </div>
      <div class="info-box">Oda boş açılır. Hazır ünite seçin veya parçaları ekleyin. Temizle her şeyi siler.</div>
    ` + menuItems().map((item) => `
      <button type="button" class="menu-item" data-kind="${item.kind}">
        <span class="ico">${item.icon}</span>
        <span class="label">${item.label}<small>${item.desc}</small></span>
        <span class="chev">›</span>
      </button>
    `).join('');
    sideBody.querySelectorAll('[data-wall]').forEach((btn) => {
      btn.addEventListener('click', () => startWallComposition(btn.dataset.wall));
    });
    sideBody.querySelectorAll('[data-bench]').forEach((btn) => {
      btn.addEventListener('click', () => startTvBench(btn.dataset.bench));
    });
    sideBody.querySelectorAll('[data-kind]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const kind = btn.dataset.kind;
        if (kind === 'materials') {
          if (!selected()) {
            showHint('Önce sahneden bir parça seçin');
            return;
          }
          state.view = 'materials';
          renderSidebar();
          return;
        }
        if (kind === 'tv') {
          state.view = 'tv';
          renderSidebar();
          return;
        }
        state.view = 'catalog';
        state.catalogKind = kind;
        renderSidebar();
      });
    });
    $('#btn-clear-all')?.addEventListener('click', clearDesign);
    $('#btn-load-saved')?.addEventListener('click', () => {
      loadDesign(true);
    });
    return;
  }

  if (state.view === 'tv') {
    sideTitle.textContent = 'TV';
    sideBody.innerHTML = `
      <div class="panel-section">
        <div class="field">
          <label>TV göster</label>
          <div class="chip-row">
            <button type="button" class="chip ${state.showTv ? 'active' : ''}" data-tv="1">Açık</button>
            <button type="button" class="chip ${!state.showTv ? 'active' : ''}" data-tv="0">Kapalı</button>
          </div>
        </div>
        <div class="section-title">Ekran boyutu (inch)</div>
        <div class="chip-row">
          ${TV_INCHES.map((n) => `<button type="button" class="chip ${state.tvInch === n ? 'active' : ''}" data-inch="${n}">${n}"</button>`).join('')}
        </div>
      </div>
      <div class="info-box">TV isteğe bağlıdır. Varsayılan kapalıdır; açıp inch seçebilirsiniz.</div>
    `;
    sideBody.querySelectorAll('[data-tv]').forEach((b) => {
      b.addEventListener('click', () => {
        state.showTv = b.dataset.tv === '1';
        buildTv();
        $('#fab-tv').classList.toggle('active', state.showTv);
        renderSidebar();
      });
    });
    sideBody.querySelectorAll('[data-inch]').forEach((b) => {
      b.addEventListener('click', () => {
        state.tvInch = Number(b.dataset.inch);
        if (!state.showTv) state.showTv = true;
        buildTv();
        $('#fab-tv').classList.add('active');
        renderSidebar();
        showHint(`TV ${state.tvInch}" ayarlandı`);
      });
    });
    return;
  }

  if (state.view === 'catalog') {
    const kind = state.catalogKind;
    const items = CATALOG[kind] || [];
    sideTitle.textContent = typeTitle(kind) + (kind === 'door' ? 'lar' : kind === 'frame' ? 'ler' : '');
    sideBody.innerHTML = `
      <div class="panel-section">
        <div class="section-title">Sürükle veya tıkla — bağımsız ürün</div>
        <div class="product-grid">
          ${items.map((p) => `
            <button type="button" class="product-card" draggable="true"
              data-type="${kind}" data-preset="${encodeURIComponent(JSON.stringify(p))}">
              <div class="thumb"><img src="${presetThumbDataUrl(kind, p)}" alt="${p.label}"></div>
              <div class="meta">
                <strong>${p.label}</strong>
                <span>${p.w}×${p.d}×${p.h} cm</span>
                ${p.doorStyle ? `<span>${DOOR_STYLES.find((d) => d.id === p.doorStyle)?.label || ''}</span>` : ''}
              </div>
            </button>
          `).join('')}
        </div>
      </div>
      <div class="info-box">${['door','back','slat','top'].includes(kind) ? 'Seçili iskelet/alt blok/rafa yapışır. Sürükleyip bloğun üzerine de bırakabilirsiniz.' : 'Yan yana eklenir. Kartı sürükleyip sahneye bırakın.'}</div>
    `;
    sideBody.querySelectorAll('.product-card').forEach((btn) => {
      const readPreset = () => JSON.parse(decodeURIComponent(btn.dataset.preset));
      btn.addEventListener('dragstart', (e) => {
        const preset = readPreset();
        const payload = { type: btn.dataset.type, ...preset };
        catalogDrag = payload;
        e.dataTransfer.setData('application/x-tv-part', JSON.stringify(payload));
        e.dataTransfer.setData('text/plain', JSON.stringify(payload));
        e.dataTransfer.effectAllowed = 'copy';
      });
      btn.addEventListener('dragend', () => {
        catalogDrag = null;
        $('#drop-overlay').classList.remove('show');
      });
      btn.addEventListener('click', () => {
        addModule(btn.dataset.type, readPreset());
      });
    });
    return;
  }

  if (state.view === 'materials') {
    sideTitle.textContent = 'Malzeme & kaplama';
    const mod = selected();
    if (!mod) {
      sideBody.innerHTML = `<div class="empty-state">Kaplama için sahneden bir parça seçin.</div>`;
      return;
    }
    renderMaterialsPanel(mod);
    return;
  }

  if (state.view === 'room') {
    sideTitle.textContent = 'Odayı özelleştirin';
    sideBody.innerHTML = `
      <div class="panel-section">
        <div class="field"><label>Duvar rengi</label><input type="color" id="wall-color" value="${state.room.wall}"></div>
        <div class="field"><label>Zemin tonu</label><input type="color" id="floor-color" value="${state.room.floor}"></div>
        <div class="chip-row" style="margin-top:8px">
          <button type="button" class="chip" data-wall="#f2f2f2" data-floor="#d8c3a5">Açık</button>
          <button type="button" class="chip" data-wall="#e8eef5" data-floor="#cbb89a">Soğuk</button>
          <button type="button" class="chip" data-wall="#1f2937" data-floor="#6b4f3a">Koyu</button>
        </div>
      </div>
    `;
    $('#wall-color').addEventListener('input', (e) => { state.room.wall = e.target.value; buildRoom(); });
    $('#floor-color').addEventListener('input', (e) => { state.room.floor = e.target.value; buildRoom(); });
    sideBody.querySelectorAll('.chip').forEach((c) => {
      c.addEventListener('click', () => {
        state.room.wall = c.dataset.wall;
        state.room.floor = c.dataset.floor;
        renderSidebar();
        buildRoom();
      });
    });
    return;
  }

  if (state.view === 'customize') {
    const mod = selected();
    if (!mod) {
      state.view = 'home';
      renderSidebar();
      return;
    }
    sideTitle.textContent = typeTitle(mod.type);
    const fmt = (n) => (Math.round(Number(n) * 10) / 10);

    const doorUI = mod.type === 'door' ? `
      <div class="section-title">Kapak tipi</div>
      <div class="chip-row">
        ${DOOR_STYLES.map((d) => `<button type="button" class="chip ${mod.doorStyle === d.id ? 'active' : ''}" data-door="${d.id}">${d.label}</button>`).join('')}
      </div>
    ` : '';

    const glassUI = mod.type === 'door' && mod.doorStyle === 'glass' ? `
      <div class="section-title">Alüminyum / Cam</div>
      <div class="chip-row">
        ${ALUMINUM_COLORS.map((c) => `
          <button type="button" class="mat-swatch ${mod.frameColor === c.color ? 'active' : ''}" data-frame="${c.color}" title="${c.label}" style="background:${c.color}"></button>
        `).join('')}
      </div>
      <div class="chip-row" style="margin-top:6px">
        ${GLASS_COLORS.map((c) => `
          <button type="button" class="mat-swatch ${mod.glassColor === c.color ? 'active' : ''}" data-glass="${c.color}" title="${c.label}" style="background:${c.color}"></button>
        `).join('')}
      </div>
    ` : '';

    const wallPanelUI = mod.type === 'wallPanel' ? `
      <div class="section-title">Kalınlık (mm)</div>
      <div class="chip-row">
        ${BACK_THICKNESS_MM.map((mm) => `
          <button type="button" class="chip ${(mod.thicknessMm || 18) === mm ? 'active' : ''}" data-thick="${mm}">${mm} mm</button>
        `).join('')}
      </div>
      <div class="section-title">Konum</div>
      <div class="chip-row">
        <button type="button" class="chip" id="btn-to-wall">Duvara yasla (arkaya)</button>
      </div>
      <div class="section-title">LED</div>
      <div class="chip-row">
        <button type="button" class="chip ${mod.led ? 'active' : ''}" data-led="1">Açık</button>
        <button type="button" class="chip ${!mod.led ? 'active' : ''}" data-led="0">Kapalı</button>
      </div>
    ` : '';

    const plinthUI = mod.type === 'plinth' ? `
      <div class="section-title">Baza</div>
      <div class="chip-row">
        <button type="button" class="chip ${mod.baza ? 'active' : ''}" data-baza="1">Bazalı</button>
        <button type="button" class="chip ${!mod.baza ? 'active' : ''}" data-baza="0">Bazasız (yüzen)</button>
      </div>
      ${!mod.baza ? `
        <div class="dim-row">
          <div class="field"><label>Yerden (cm)</label><input type="number" id="dim-y" min="0" max="80" step="1" value="${fmt(mod.y || 0)}"></div>
        </div>
        <div class="section-title">LED alt ışık</div>
        <div class="chip-row">
          <button type="button" class="chip ${mod.led ? 'active' : ''}" data-led="1">Açık</button>
          <button type="button" class="chip ${!mod.led ? 'active' : ''}" data-led="0">Kapalı</button>
        </div>
      ` : `
        <div class="dim-row">
          <div class="field"><label>Baza yüksekliği (cm)</label><input type="number" id="baza-h" min="6" max="12" step="1" value="${mod.bazaH || 8}"></div>
        </div>
      `}
    ` : '';

    const hostAddUI = canTakeFronts(mod) ? `
      <div class="section-title">Kapak ekle</div>
      <div class="chip-row">
        <button type="button" class="chip" data-front="push">Bas-aç</button>
        <button type="button" class="chip" data-front="drawer">Çekmece</button>
        <button type="button" class="chip" data-front="hinge">Menteşeli</button>
        <button type="button" class="chip" data-front="glass">Cam</button>
      </div>
      <div class="section-title">Donanım</div>
      <div class="chip-row">
        <button type="button" class="chip" data-add-part="shelf">İç raf</button>
        <button type="button" class="chip" data-add-part="back">Arkalık</button>
        <button type="button" class="chip" data-add-part="top">Üst panel</button>
        <button type="button" class="chip" data-add-part="leg">Ayak</button>
      </div>
    ` : '';

    const backUI = mod.type === 'back' ? `
      <div class="section-title">Arkalık kalınlığı (mm)</div>
      <div class="chip-row">
        ${BACK_THICKNESS_MM.map((mm) => `
          <button type="button" class="chip ${(mod.thicknessMm || Math.round(mod.d * 10)) === mm ? 'active' : ''}" data-thick="${mm}">${mm} mm</button>
        `).join('')}
      </div>
    ` : '';

    const slatUI = mod.type === 'slat' ? `
      <div class="section-title">Çıta</div>
      <div class="dim-row">
        <div class="field"><label>Eni (cm)</label><input type="number" id="slat-w" min="0.8" max="6" step="0.1" value="${mod.slatWidth || 1.6}"></div>
        <div class="field"><label>Boşluk (cm)</label><input type="number" id="slat-gap" min="0.5" max="12" step="0.1" value="${mod.slatGap != null ? mod.slatGap : 2.5}"></div>
      </div>
      <div class="chip-row"><button type="button" class="chip" id="btn-to-wall">Duvara yasla</button></div>
    ` : '';

    const shelfLedUI = mod.type === 'shelf' || mod.type === 'frame' ? `
      <div class="section-title">LED</div>
      <div class="chip-row">
        <button type="button" class="chip ${mod.led ? 'active' : ''}" data-led="1">Açık</button>
        <button type="button" class="chip ${!mod.led ? 'active' : ''}" data-led="0">Kapalı</button>
      </div>
    ` : '';

    const floatUI = (mod.type === 'frame' || mod.type === 'wallPanel') && !mod.parentId ? `
      <div class="dim-row">
        <div class="field"><label>Yerden Y (cm)</label><input type="number" id="dim-y" min="0" max="120" step="1" value="${fmt(mod.y || 0)}"></div>
      </div>
    ` : '';

    sideBody.innerHTML = `
      <div class="panel-section">
        <div class="section-title">Ebat (cm)</div>
        <div class="dim-row">
          <div class="field"><label>En</label><input type="number" id="dim-w" min="10" max="500" step="1" value="${fmt(mod.w)}"></div>
          <div class="field"><label>Derinlik</label><input type="number" id="dim-d" min="0.5" max="80" step="0.1" value="${fmt(mod.d)}"></div>
          <div class="field"><label>Yükseklik</label><input type="number" id="dim-h" min="1" max="300" step="1" value="${fmt(mod.h)}"></div>
        </div>
        ${floatUI}
        ${wallPanelUI}
        ${plinthUI}
        ${hostAddUI}
        ${doorUI}
        ${glassUI}
        ${backUI}
        ${slatUI}
        ${shelfLedUI}
        <div class="section-title">Yüzey</div>
        <div class="chip-row">
          ${FINISHES.map((f) => `<button type="button" class="chip ${mod.finish === f.id ? 'active' : ''}" data-finish="${f.id}">${f.label}</button>`).join('')}
        </div>
        <div class="section-title">Kaplama</div>
        <div style="font-size:13px;font-weight:600;margin-bottom:8px">${mod.materialName || 'Varsayılan'}${mod.materialCode ? ' · ' + mod.materialCode : ''}</div>
        <button type="button" class="btn" id="btn-pick-mat" style="width:100%;justify-content:center;border-radius:12px;box-shadow:none;border:1px solid #e5e7eb">Katalogdan seç</button>
        <div class="chip-row" style="margin-top:10px">
          ${['#f7f7f7','#111111','#e8e4df','#c4a574','#6b7280','#f2ebe3'].map((c) => `
            <button type="button" class="mat-swatch ${mod.color === c && !mod.materialImage ? 'active' : ''}" data-color="${c}" style="background:${c}"></button>
          `).join('')}
        </div>
      </div>
      <button type="button" class="btn" id="btn-dup" style="width:calc(100% - 32px);margin:8px 16px;justify-content:center;border-radius:12px;box-shadow:none;border:1px solid #e5e7eb">Çoğalt</button>
      <button type="button" class="danger" id="btn-del">Sil</button>
    `;

    const applyDims = () => {
      pushHistory();
      mod.w = clampNum($('#dim-w').value, 10, 500);
      mod.d = clampNum($('#dim-d').value, 0.5, 80);
      mod.h = clampNum($('#dim-h').value, 1, 300);
      if ($('#dim-y')) {
        mod.y = clampNum($('#dim-y').value, 0, 120);
        mod.floatY = mod.y;
      }
      if ($('#baza-h')) mod.bazaH = clampNum($('#baza-h').value, 6, 12);
      if (isHost(mod)) syncAttachedToHost(mod);
      rebuildModules().then(() => buildTv());
    };
    ['dim-w', 'dim-d', 'dim-h', 'dim-y', 'baza-h'].forEach((id) => $(`#${id}`)?.addEventListener('change', applyDims));

    sideBody.querySelectorAll('[data-led]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.led = btn.dataset.led === '1';
        rebuildModules();
        renderSidebar();
      });
    });
    sideBody.querySelectorAll('[data-baza]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        const on = btn.dataset.baza === '1';
        mod.baza = on;
        if (on) {
          mod.y = 0;
          mod.floatY = 0;
          mod.led = false;
          mod.bazaH = mod.bazaH || 8;
        } else {
          mod.y = mod.floatY || 15;
          mod.floatY = mod.y;
          mod.led = true;
        }
        syncAttachedToHost(mod);
        rebuildModules().then(() => renderSidebar());
      });
    });
    sideBody.querySelectorAll('[data-thick]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        const mm = Number(btn.dataset.thick);
        mod.thicknessMm = mm;
        mod.d = mm / 10;
        if (mod.type === 'wallPanel') sendPanelToWall(mod);
        if (mod.parentId) {
          const host = state.modules.find((m) => m.id === mod.parentId);
          if (host) syncAttachedToHost(host);
        }
        rebuildModules().then(() => renderSidebar());
      });
    });
    $('#btn-to-wall')?.addEventListener('click', () => {
      pushHistory();
      sendPanelToWall(mod);
      rebuildModules().then(() => {
        buildTv();
        showHint('Panel duvara yaslandı');
      });
    });

    sideBody.querySelectorAll('[data-front]').forEach((btn) => {
      btn.addEventListener('click', () => addFrontsToHost(mod, btn.dataset.front));
    });
    sideBody.querySelectorAll('[data-add-part]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const t = btn.dataset.addPart;
        if (t === 'shelf') addInteriorShelf(mod);
        else if (t === 'back') addAttachedPart('back', { host: mod, thicknessMm: 18, label: 'Arkalık 18 mm' });
        else addAttachedPart(t, { host: mod });
      });
    });

    const applySlat = () => {
      if (mod.type !== 'slat') return;
      pushHistory();
      mod.slatWidth = clampNum($('#slat-w')?.value, 0.8, 6);
      mod.slatGap = clampNum($('#slat-gap')?.value, 0.5, 12);
      rebuildModules().then(() => renderSidebar());
    };
    $('#slat-w')?.addEventListener('change', applySlat);
    $('#slat-gap')?.addEventListener('change', applySlat);

    sideBody.querySelectorAll('[data-finish]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.finish = btn.dataset.finish;
        rebuildModules().then(() => renderSidebar());
      });
    });
    sideBody.querySelectorAll('[data-door]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.doorStyle = btn.dataset.door;
        mod.label = DOOR_STYLES.find((d) => d.id === mod.doorStyle)?.label || mod.label;
        if (mod.doorStyle === 'drawer' && mod.d < 20) mod.d = 40;
        if ((mod.doorStyle === 'hinge' || mod.doorStyle === 'push' || mod.doorStyle === 'glass') && mod.d > 8) mod.d = 2;
        if (mod.doorStyle === 'glass') {
          mod.frameColor = mod.frameColor || '#c0c4c8';
          mod.glassColor = mod.glassColor || '#c5d5e8';
        }
        if (mod.parentId) {
          const host = state.modules.find((m) => m.id === mod.parentId);
          if (host) {
            const pose = attachToHost(host, 'door', {
              doorStyle: mod.doorStyle, h: mod.h, d: mod.d,
              bayIndex: mod.bayIndex, bays: mod.bays,
              drawerRow: mod.drawerRow, rows: mod.rows,
            });
            if (pose) {
              mod.w = pose.w; mod.h = pose.h; mod.d = pose.d;
              mod.x = pose.x; mod.y = pose.y; mod.z = pose.z;
              if (pose.yOffset != null) mod.yOffset = pose.yOffset;
            }
          }
        }
        rebuildModules().then(() => renderSidebar());
      });
    });
    sideBody.querySelectorAll('[data-frame]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.frameColor = btn.dataset.frame;
        rebuildModules().then(() => renderSidebar());
      });
    });
    sideBody.querySelectorAll('[data-glass]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.glassColor = btn.dataset.glass;
        rebuildModules().then(() => renderSidebar());
      });
    });
    sideBody.querySelectorAll('[data-color]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.color = btn.dataset.color;
        mod.materialImage = null;
        mod.materialId = null;
        mod.materialName = 'Özel renk';
        mod.materialCode = '';
        rebuildModules().then(() => renderSidebar());
      });
    });
    $('#btn-pick-mat').addEventListener('click', () => {
      state.view = 'materials';
      renderSidebar();
    });
    $('#btn-del').addEventListener('click', deleteSelected);
    $('#btn-dup').addEventListener('click', duplicateSelected);
  }
}

function renderMaterialsPanel(mod, preferredGroup) {
  const groups = state.materials.groups || [];
  if (!groups.length) {
    sideBody.innerHTML = `<div class="empty-state">Malzemeler yükleniyor…</div>`;
    return;
  }
  const activeSlug = preferredGroup || groups[0]?.slug;
  sideBody.innerHTML = `
    ${mod.materialName ? `<div class="applied-banner">${mod.materialCode ? mod.materialCode + ' · ' : ''}${mod.materialName} uygulandı</div>` : ''}
    <div class="tabs">
      ${groups.map((g) => `<button type="button" class="tab ${g.slug === activeSlug ? 'active' : ''}" data-g="${g.slug}">${g.label.replace(' Panel', '').replace('Kaplı ', '')}</button>`).join('')}
    </div>
    <div class="panel-section">
      <div class="section-title">Parça: ${typeTitle(mod.type)}</div>
      <div class="chip-row" style="margin-bottom:10px">
        ${FINISHES.map((f) => `<button type="button" class="chip ${mod.finish === f.id ? 'active' : ''}" data-finish="${f.id}">${f.label}</button>`).join('')}
      </div>
      <div id="mat-list"></div>
    </div>
  `;
  let active = activeSlug;
  const paint = () => {
    const g = groups.find((x) => x.slug === active);
    const list = $('#mat-list');
    if (!g) { list.innerHTML = '<div class="empty-state">Malzeme yok</div>'; return; }
    list.innerHTML = `
      <div class="mat-grid">
        ${g.items.slice(0, 120).map((item) => `
          <button type="button" class="mat-swatch ${mod.materialId === item.id ? 'active' : ''}" data-id="${item.id}" title="${item.code} ${item.name}">
            <img src="${item.image}" alt="${item.name}" loading="lazy">
          </button>
        `).join('')}
      </div>
      <div class="info-box" style="margin:12px 0 0">Tıklayınca seçili parçaya hemen uygulanır.</div>
    `;
    list.querySelectorAll('[data-id]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const item = g.items.find((i) => i.id === btn.dataset.id);
        if (!item) return;
        pushHistory();
        mod.materialId = item.id;
        mod.materialImage = item.image;
        mod.materialName = item.name;
        mod.materialCode = item.code;
        mod.finish = item.finish_hint || mod.finish;
        await rebuildModules();
        showHint(`${item.code} uygulandı`);
        renderMaterialsPanel(mod, active);
      });
    });
  };
  sideBody.querySelectorAll('[data-finish]').forEach((btn) => {
    btn.addEventListener('click', () => {
      pushHistory();
      mod.finish = btn.dataset.finish;
      rebuildModules().then(() => renderMaterialsPanel(mod, active));
    });
  });
  sideBody.querySelectorAll('.tab').forEach((t) => {
    t.addEventListener('click', () => {
      active = t.dataset.g;
      sideBody.querySelectorAll('.tab').forEach((x) => x.classList.toggle('active', x === t));
      paint();
    });
  });
  paint();
}

function openSummary() {
  const modal = $('#summary-modal');
  const content = $('#summary-content');
  const rows = state.modules.map((m) => `
    <tr>
      <td>${typeTitle(m.type)}${m.doorStyle ? ' · ' + (DOOR_STYLES.find((d) => d.id === m.doorStyle)?.label || '') : ''} · ${m.materialName || m.label || ''}<br>
      <span style="color:#9ca3af;font-size:11px">${m.w}×${m.d}×${m.h} cm · ${FINISHES.find((f) => f.id === m.finish)?.label || ''} ${m.materialCode || ''}</span></td>
    </tr>
  `).join('');
  content.innerHTML = state.modules.length ? `
    <table>
      <thead><tr><th>Parça listesi</th></tr></thead>
      <tbody>${rows}</tbody>
    </table>
  ` : `<div class="empty-state">Henüz parça yok.</div>`;
  modal.classList.add('open');
}

function saveDesign() {
  localStorage.setItem('tv-configurator-design', JSON.stringify({
    version: 2,
    brand: brandName,
    room: state.room,
    modules: state.modules,
    showTv: state.showTv,
    tvInch: state.tvInch,
    savedAt: new Date().toISOString(),
  }));
  showHint('Tasarım kaydedildi');
}

function loadDesign(force = false) {
  try {
    const raw = localStorage.getItem('tv-configurator-design');
    if (!raw) {
      clearAllLabels();
      if (force) showHint('Kayıtlı tasarım yok');
      return;
    }
    const data = JSON.parse(raw);
    if (data.modules) {
      state.modules = data.modules.map((m) => ({ ...m }));
      if (data.room) state.room = data.room;
      if (typeof data.showTv === 'boolean') state.showTv = data.showTv;
      if (data.tvInch) state.tvInch = data.tvInch;
      buildRoom();
      buildTv();
      rebuildModules().then(() => {
        $('#fab-tv')?.classList.toggle('active', state.showTv);
        state.view = 'home';
        renderSidebar();
        showHint('Kayıtlı tasarım yüklendi');
      });
    } else {
      clearAllLabels();
    }
  } catch (_) {
    clearAllLabels();
  }
}

async function loadMaterials() {
  try {
    const res = await fetch(materialsUrl, { headers: { Accept: 'application/json' } });
    state.materials = await res.json();
  } catch (e) {
    console.warn(e);
    state.materials = { groups: [] };
  }
}

function wireUi() {
  btnBack.addEventListener('click', () => {
    state.view = 'home';
    renderSidebar();
  });
  $('#btn-save').addEventListener('click', saveDesign);
  $('#btn-summary').addEventListener('click', openSummary);
  $('#btn-close-summary').addEventListener('click', () => $('#summary-modal').classList.remove('open'));
  $('#summary-modal').addEventListener('click', (e) => {
    if (e.target.id === 'summary-modal') e.currentTarget.classList.remove('open');
  });
  $('#btn-copy-summary').addEventListener('click', async () => {
    const text = state.modules.map((m) =>
      `${typeTitle(m.type)} | ${m.w}x${m.d}x${m.h}cm | ${m.materialName || ''} ${m.materialCode || ''} | ${FINISHES.find((f) => f.id === m.finish)?.label || ''}`
    ).join('\n');
    await navigator.clipboard.writeText(`${brandName} TV Ünitesi\n${text || '(boş)'}`);
    showHint('Özet panoya kopyalandı');
  });
  $('#btn-room').addEventListener('click', () => {
    state.view = 'room';
    renderSidebar();
  });
  $('#fab-tv').addEventListener('click', () => {
    state.view = 'tv';
    renderSidebar();
  });
  $('#fab-orbit').addEventListener('click', () => {
    state.autoRotate = !state.autoRotate;
    $('#fab-orbit').classList.toggle('active', state.autoRotate);
    showHint(state.autoRotate ? '360° dönüş açık' : 'Otomatik dönüş kapalı');
  });
  $('#fab-night').addEventListener('click', () => {
    state.night = !state.night;
    $('#fab-night').classList.toggle('active', state.night);
    scene.background = new THREE.Color(state.night ? 0x1f2937 : 0xe8e8e8);
    state._dirLight.intensity = state.night ? 0.45 : 1.35;
  });
  $('#fab-grid').addEventListener('click', () => {
    state.showGrid = !state.showGrid;
    gridHelper.visible = state.showGrid;
    $('#fab-grid').classList.toggle('active', state.showGrid);
  });
  $('#fab-measure').addEventListener('click', () => {
    state.showMeasure = !state.showMeasure;
    $('#fab-measure').classList.toggle('active', state.showMeasure);
    rebuildModules();
  });
  $('#fab-undo').addEventListener('click', undo);
  $('#fab-redo').addEventListener('click', redo);
  $('#sel-delete')?.addEventListener('click', deleteSelected);
  $('#sel-dup')?.addEventListener('click', duplicateSelected);

  ctxMenu?.querySelectorAll('[data-act]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const act = btn.dataset.act;
      if (act === 'customize') {
        state.view = 'customize';
        renderSidebar();
      } else if (act === 'material') {
        state.view = 'materials';
        renderSidebar();
      } else if (act === 'dup') {
        duplicateSelected();
      } else if (act === 'del') {
        deleteSelected();
      }
      hideCtx();
    });
  });
}

async function boot() {
  initThree();
  clearAllLabels();
  wireUi();
  renderSidebar();
  await loadMaterials();
  // Oda boş açılır — kayıtlı tasarım "Kayıtlıyı yükle" ile gelir
  clearAllLabels();
  showHint('Hazır ünite seçin veya parça ekleyin · Temizle ile sıfırlayın', 3200);
}

boot();
