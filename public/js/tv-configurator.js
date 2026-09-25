import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { CSS2DRenderer, CSS2DObject } from 'three/addons/renderers/CSS2DRenderer.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

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
    // 1–6 blok · her biri bazalı + bazasız (yüzen)
    { id: 'p1', label: '1 blok · bazalı', w: 60, d: 40, h: 40, baza: true, bazaH: 8, bays: 1, price: 600 },
    { id: 'p2', label: '2 blok · bazalı', w: 120, d: 40, h: 40, baza: true, bazaH: 8, bays: 2, price: 1000 },
    { id: 'p3', label: '3 blok · bazalı', w: 180, d: 40, h: 40, baza: true, bazaH: 8, bays: 3, price: 1400 },
    { id: 'p4', label: '4 blok · bazalı', w: 240, d: 40, h: 40, baza: true, bazaH: 8, bays: 4, price: 1800 },
    { id: 'p5', label: '5 blok · bazalı', w: 300, d: 40, h: 40, baza: true, bazaH: 8, bays: 5, price: 2200 },
    { id: 'p6', label: '6 blok · bazalı', w: 360, d: 40, h: 40, baza: true, bazaH: 8, bays: 6, price: 2600 },
    { id: 'pf1', label: '1 blok · yüzen', w: 60, d: 40, h: 35, floatY: 15, baza: false, led: true, bays: 1, price: 700 },
    { id: 'pf2', label: '2 blok · yüzen', w: 120, d: 40, h: 35, floatY: 15, baza: false, led: true, bays: 2, price: 1100 },
    { id: 'pf3', label: '3 blok · yüzen', w: 180, d: 40, h: 35, floatY: 15, baza: false, led: true, bays: 3, price: 1500 },
    { id: 'pf4', label: '4 blok · yüzen', w: 240, d: 40, h: 35, floatY: 15, baza: false, led: true, bays: 4, price: 1900 },
    { id: 'pf5', label: '5 blok · yüzen', w: 300, d: 40, h: 35, floatY: 15, baza: false, led: true, bays: 5, price: 2300 },
    { id: 'pf6', label: '6 blok · yüzen', w: 360, d: 40, h: 35, floatY: 15, baza: false, led: true, bays: 6, price: 2700 },
  ],
  glb: [
    { id: 'g-low', label: 'Alçak raflı konsol', glbFile: 'low-shelf.glb', w: 100, d: 25, h: 17, price: 0 },
    { id: 'g-float', label: 'Yüzen ceviz konsol', glbFile: 'floating-walnut.glb', w: 140, d: 40, h: 35, price: 0 },
    { id: 'g-four', label: 'Dört kapaklı konsol', glbFile: 'four-door.glb', w: 180, d: 40, h: 45, price: 0 },
    { id: 'g-white', label: 'Beyaz panelli konsol', glbFile: 'white-panelled.glb', w: 160, d: 40, h: 45, price: 0 },
    { id: 'g-draw', label: 'Çekmeceli açık banko', glbFile: 'white-open-drawers.glb', w: 160, d: 40, h: 45, price: 0 },
    { id: 'g-open', label: 'Koyu açık raflı', glbFile: 'open-shelf-dark.glb', w: 140, d: 35, h: 50, price: 0 },
    { id: 'g-wall', label: 'TV panelli duvar', glbFile: 'wall-tv-panel.glb', w: 180, d: 30, h: 160, price: 0 },
    { id: 'g-light', label: 'Işıklı duvar ünitesi', glbFile: 'wall-lighting.glb', w: 200, d: 35, h: 180, price: 0 },
  ],
  wallPanel: [
    { id: 'wp120', label: 'Arka pano 120', w: 120, d: 1.8, h: 200, thicknessMm: 18, price: 1800 },
    { id: 'wp160', label: 'Arka pano 160', w: 160, d: 1.8, h: 200, thicknessMm: 18, price: 2200 },
    { id: 'wp180', label: 'Arka pano 180', w: 180, d: 1.8, h: 220, thicknessMm: 18, price: 2600 },
    { id: 'wp200', label: 'Arka pano 200', w: 200, d: 1.8, h: 240, thicknessMm: 18, price: 3000 },
    { id: 'wp240', label: 'Arka pano 240', w: 240, d: 1.8, h: 220, thicknessMm: 18, price: 3200 },
    { id: 'wp300', label: 'Arka pano 300', w: 300, d: 1.8, h: 240, thicknessMm: 18, price: 3800 },
  ],
  shelf: [
    { id: 'st40', label: 'Kule 40', w: 40, d: 35, h: 220, shelfCount: 9, price: 2200 },
    { id: 'st50', label: 'Kule 50', w: 50, d: 35, h: 220, shelfCount: 9, price: 2400 },
    { id: 'st60', label: 'Kule 60', w: 60, d: 35, h: 220, shelfCount: 9, price: 2800 },
    { id: 'st80', label: 'Kule 80', w: 80, d: 35, h: 220, shelfCount: 9, price: 3200 },
    { id: 's60', label: 'Alçak raf 60', w: 60, d: 35, h: 40, shelfCount: 1, price: 900 },
    { id: 's120', label: 'Alçak raf 120', w: 120, d: 35, h: 40, shelfCount: 1, price: 1400 },
    { id: 's180', label: 'Alçak raf 180', w: 180, d: 35, h: 40, shelfCount: 1, price: 1800 },
  ],
  slat: [
    { id: 'slw40', label: 'Çıta 40', w: 40, d: 3, h: 220, price: 1200 },
    { id: 'slw60', label: 'Çıta 60', w: 60, d: 3, h: 220, price: 1600 },
    { id: 'slw80', label: 'Çıta 80', w: 80, d: 3, h: 220, price: 1900 },
    { id: 'slw120', label: 'Çıta 120', w: 120, d: 3, h: 220, price: 2400 },
    { id: 'slw160', label: 'Çıta 160', w: 160, d: 3, h: 220, price: 2900 },
    { id: 'sl120', label: 'Çıta panel alçak', w: 120, d: 3, h: 40, price: 1100 },
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
  top: [
    { id: 't-oak', label: 'Üst panel · meşe', w: 120, d: 42, h: 2.5, color: '#d4b896', finish: 'matte', materialName: 'Meşe', price: 900 },
    { id: 't-bleach', label: 'Üst panel · ağartılmış', w: 120, d: 42, h: 2.5, color: '#e8dcc8', finish: 'matte', materialName: 'Ağartılmış meşe', price: 950 },
    { id: 't-wenge', label: 'Üst panel · venge', w: 120, d: 42, h: 2.5, color: '#3d2b1f', finish: 'matte', materialName: 'Venge', price: 950 },
    { id: 't-white', label: 'Üst panel · beyaz', w: 120, d: 42, h: 2.5, color: '#f7f7f7', finish: 'matte', materialName: 'Beyaz', price: 800 },
    { id: 't-grey', label: 'Üst panel · gri', w: 120, d: 42, h: 2.5, color: '#6b7280', finish: 'matte', materialName: 'Gri', price: 850 },
    { id: 't-black', label: 'Üst panel · siyah', w: 120, d: 42, h: 2.5, color: '#1a1a1a', finish: 'highgloss', materialName: 'Siyah parlak', price: 980 },
  ],
  interior: [
    { id: 'is-w', label: 'Raf, beyaz', w: 56, d: 32, h: 1.8, color: '#f7f7f7', finish: 'matte', materialName: 'Beyaz', price: 370 },
    { id: 'is-g', label: 'Raf, koyu gri', w: 56, d: 32, h: 1.8, color: '#4b5563', finish: 'matte', materialName: 'Koyu gri', price: 370 },
    { id: 'is-o', label: 'Raf, ağartılmış meşe', w: 56, d: 32, h: 1.8, color: '#e8dcc8', finish: 'matte', materialName: 'Ağartılmış meşe', price: 390 },
    { id: 'is-v', label: 'Raf, venge', w: 56, d: 32, h: 1.8, color: '#3d2b1f', finish: 'matte', materialName: 'Venge', price: 390 },
    { id: 'is-oak', label: 'Raf, meşe', w: 56, d: 32, h: 1.8, color: '#c4a574', finish: 'matte', materialName: 'Meşe', price: 390 },
    { id: 'is-glass', label: 'Cam raf', w: 56, d: 32, h: 1.2, color: '#c5d5e8', finish: 'gloss', materialName: 'Cam', glassShelf: true, price: 520 },
    { id: 'is-tray', label: 'Çekme tepsi', w: 56, d: 32, h: 4, color: '#f7f7f7', finish: 'matte', materialName: 'Beyaz', organizer: 'tray', price: 650 },
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
  { id: 'open', label: 'Açık raf' },
];

const HANDLE_STYLES = [
  { id: 'none', label: 'Kulp yok' },
  { id: 'knob', label: 'Düğme' },
  { id: 'bar', label: 'Çubuk' },
  { id: 'edge', label: 'Gizli kenar' },
];

const HANDLE_POS = [
  { id: 'center', label: 'Orta' },
  { id: 'top', label: 'Üst' },
  { id: 'bottom', label: 'Alt' },
  { id: 'left', label: 'Sol' },
  { id: 'right', label: 'Sağ' },
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
const modelsBase = appEl.dataset.modelsBase || '';
const brandName = appEl.dataset.brand || 'Meemare';

const state = {
  view: 'home',
  catalogKind: null,
  selectedId: null,
  modules: [],
  materials: { groups: [] },
  showTv: false,
  tvInch: 55,
  /** TV konumu cm — manual=true ise otomatik hizalamaz */
  tv: { x: 0, y: 110, z: null, manual: false },
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
  { id: 'black', label: 'Siyah', color: '#1c1c1c', metalness: 0.72, roughness: 0.28 },
  { id: 'gold', label: 'Gold / eskitme', color: '#c4a15a', metalness: 0.92, roughness: 0.32 },
  { id: 'silver', label: 'Gümüş / natural', color: '#c5c8cc', metalness: 0.9, roughness: 0.22 },
  { id: 'bronze', label: 'Bronz profil', color: '#8a6244', metalness: 0.86, roughness: 0.3 },
];

function frameProfile(mod) {
  if (!mod) return ALUMINUM_COLORS[2];
  return ALUMINUM_COLORS.find((c) => c.color === mod.frameColor) || ALUMINUM_COLORS[2];
}

const GLASS_COLORS = [
  { id: 'smoke', label: 'Füme', color: '#4a515a', transmission: 0.42, opacity: 0.62, roughness: 0.04, metalness: 0.18 },
  { id: 'bronze', label: 'Bronz', color: '#8d5a34', transmission: 0.48, opacity: 0.5, roughness: 0.06, metalness: 0.22 },
  { id: 'clear', label: 'Şeffaf', color: '#e7eef6', transmission: 0.94, opacity: 0.08, roughness: 0.02, metalness: 0 },
  { id: 'satin', label: 'Buzlu', color: '#f3f0ea', transmission: 0.12, opacity: 0.72, roughness: 0.72, metalness: 0 },
  { id: 'ref-smoke', label: 'Füme reflekte', color: '#2f353c', transmission: 0.22, opacity: 0.78, roughness: 0.08, metalness: 0.72 },
  { id: 'ref-bronze', label: 'Bronz reflekte', color: '#6a4630', transmission: 0.2, opacity: 0.74, roughness: 0.08, metalness: 0.7 },
];

function glassProfile(mod) {
  if (!mod) return GLASS_COLORS[2];
  return GLASS_COLORS.find((c) => c.id === mod.glassKind)
    || GLASS_COLORS.find((c) => c.color === mod.glassColor)
    || GLASS_COLORS.find((c) => c.id === 'clear');
}

let scene, camera, renderer, labelRenderer, controls, raycaster, pointer;
let roomGroup, modulesGroup, gridHelper, tvMesh;
let drag = null;
let pendingDrag = null;
let catalogDrag = null;
let materialDrag = null;

function boxGeo(w, h, d, radius = 0) {
  const W = w * CM, H = h * CM, D = d * CM;
  const r = Math.min((radius || 0) * CM, Math.min(W, H, D) * 0.35);
  if (r > 0.002) {
    return new RoundedBoxGeometry(W, H, D, 4, r);
  }
  return new THREE.BoxGeometry(W, H, D);
}

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
  resolveAllSolids();
  rebuildModules();
  renderSidebar();
}

function redo() {
  if (!state.future.length) return;
  state.history.push(JSON.stringify(state.modules));
  state.modules = JSON.parse(state.future.pop());
  state.selectedId = null;
  resolveAllSolids();
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
        t.anisotropy = 8;
        t.userData.pooled = true;
        resolve(t);
      },
      undefined,
      () => resolve(null)
    );
  });
  state.textureCache.set(url, tex);
  return tex;
}

const finishTexCache = new Map();
function finishTexture(hex, finish) {
  const key = `${hex || '#f4f1ec'}|${finish || 'matte'}`;
  const cached = finishTexCache.get(key);
  if (cached) return cached;
  const c = document.createElement('canvas');
  c.width = 512;
  c.height = 512;
  const ctx = c.getContext('2d');
  const base = hex || '#f4f1ec';
  ctx.fillStyle = base;
  ctx.fillRect(0, 0, 512, 512);
  let r = 200, g = 196, b = 188;
  const m = String(base).match(/^#([0-9a-f]{6})$/i);
  if (m) {
    r = parseInt(m[1].slice(0, 2), 16);
    g = parseInt(m[1].slice(2, 4), 16);
    b = parseInt(m[1].slice(4, 6), 16);
  }
  const warm = r > b + 12 && g > b + 4 && r < 230;
  if (warm && finish !== 'highgloss') {
    for (let i = 0; i < 48; i++) {
      const y = (i / 48) * 512;
      ctx.strokeStyle = `rgba(${Math.max(0, r - 40)},${Math.max(0, g - 36)},${Math.max(0, b - 28)},${0.08 + (i % 5) * 0.02})`;
      ctx.lineWidth = i % 7 === 0 ? 2 : 1;
      ctx.beginPath();
      ctx.moveTo(0, y);
      ctx.bezierCurveTo(140, y + 6, 300, y - 4, 512, y + 2);
      ctx.stroke();
    }
  } else {
    const img = ctx.getImageData(0, 0, 512, 512);
    const d = img.data;
    for (let i = 0; i < d.length; i += 4) {
      const n = ((i * 17) % 11) - 5;
      d[i] = Math.min(255, Math.max(0, d[i] + n));
      d[i + 1] = Math.min(255, Math.max(0, d[i + 1] + n));
      d[i + 2] = Math.min(255, Math.max(0, d[i + 2] + n));
    }
    ctx.putImageData(img, 0, 0);
  }
  const tex = new THREE.CanvasTexture(c);
  tex.colorSpace = THREE.SRGBColorSpace;
  tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
  tex.anisotropy = 8;
  tex.userData.pooled = true;
  finishTexCache.set(key, tex);
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
  const baseHex = mod.color || '#f4f1ec';
  const proc = !map && !isGlass ? finishTexture(baseHex, mod.finish) : null;
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
    color: (map || proc) ? 0xffffff : new THREE.Color(baseHex),
    map: map || proc || null,
    roughness: finishRoughness(mod.finish),
    metalness: finishMetalness(mod.finish),
  });
}

function initThree() {
  const host = $('#canvas-host');
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0xf3f1ee);
  scene.fog = new THREE.Fog(0xf3f1ee, 22, 48);
  camera = new THREE.PerspectiveCamera(42, 1, 0.02, 80);
  camera.position.set(2.4, 1.5, 3.4);

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.08;
  host.appendChild(renderer.domElement);

  labelRenderer = new CSS2DRenderer();
  labelRenderer.domElement.style.position = 'absolute';
  labelRenderer.domElement.style.inset = '0';
  labelRenderer.domElement.style.pointerEvents = 'none';
  host.appendChild(labelRenderer.domElement);

  controls = new OrbitControls(camera, renderer.domElement);
  controls.target.set(0, 0.55, 0);
  controls.enableDamping = true;
  controls.dampingFactor = 0.18;
  controls.rotateSpeed = 0.9;
  controls.zoomSpeed = 0.8;
  controls.minPolarAngle = 0.08;
  controls.maxPolarAngle = Math.PI * 0.49;
  controls.minDistance = 0.35;
  controls.maxDistance = 10;
  controls.enablePan = true;
  controls.autoRotateSpeed = 1.6;

  // IKEA tarzı yumuşak stüdyo ışığı — önden-soldan, duvar + zemine gölge
  const hemi = new THREE.HemisphereLight(0xf5f7fa, 0xb8aea0, 0.62);
  scene.add(hemi);

  const key = new THREE.DirectionalLight(0xfff6ea, 1.65);
  key.position.set(-2.6, 4.4, 3.8);
  key.target.position.set(0, 0.7, -(FLOOR_D * CM) / 2 + 0.35);
  scene.add(key.target);
  key.castShadow = true;
  key.shadow.mapSize.set(2048, 2048);
  key.shadow.bias = -0.0002;
  key.shadow.normalBias = 0.035;
  key.shadow.radius = 6;
  {
    const sc = key.shadow.camera;
    sc.near = 0.4;
    sc.far = 16;
    sc.left = -4.5;
    sc.right = 4.5;
    sc.top = 4.2;
    sc.bottom = -0.5;
    sc.updateProjectionMatrix();
  }
  scene.add(key);

  // Dolgu — gölge yok, iç hacmi yumuşatır
  const fill = new THREE.DirectionalLight(0xe8eef6, 0.42);
  fill.position.set(3.2, 2.4, 2.6);
  scene.add(fill);

  // Duvar yansıması / rim
  const bounce = new THREE.DirectionalLight(0xffffff, 0.22);
  bounce.position.set(0.4, 1.8, -3.5);
  scene.add(bounce);

  state._hemi = hemi;
  state._dirLight = key;
  state._fillLight = fill;
  state._bounceLight = bounce;

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

  // Drop catalog products / materials onto the scene
  host.addEventListener('dragover', (e) => {
    if (!catalogDrag && !materialDrag) return;
    e.preventDefault();
    const overlay = $('#drop-overlay');
    overlay.classList.add('show');
    const label = overlay.querySelector('span');
    if (label) label.textContent = materialDrag ? 'Kaplamayı parçaya bırakın' : 'Buraya bırakın';
  });
  host.addEventListener('dragleave', () => $('#drop-overlay').classList.remove('show'));
  host.addEventListener('drop', (e) => {
    e.preventDefault();
    $('#drop-overlay').classList.remove('show');
    const matRaw = e.dataTransfer.getData('application/x-tv-material')
      || (materialDrag ? JSON.stringify(materialDrag) : '');
    if (matRaw) {
      try {
        const item = JSON.parse(matRaw);
        applyMaterialAtClient(item, e.clientX, e.clientY);
      } catch (_) {}
      materialDrag = null;
      catalogDrag = null;
      return;
    }
    const raw = e.dataTransfer.getData('application/x-tv-part') || e.dataTransfer.getData('text/plain');
    if (!raw) return;
    try {
      const data = JSON.parse(raw);
      placeFromDrop(data, e.clientX, e.clientY);
    } catch (_) {}
    catalogDrag = null;
    materialDrag = null;
  });

  onResize();
  animate();
}

function buildRoom() {
  while (roomGroup.children.length) roomGroup.remove(roomGroup.children[0]);

  const floorMat = new THREE.MeshStandardMaterial({
    color: new THREE.Color(state.room.floor),
    roughness: 0.82,
    metalness: 0.02,
  });
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
    new THREE.MeshStandardMaterial({
      color: new THREE.Color(state.room.wall),
      roughness: 0.96,
      metalness: 0,
    })
  );
  wall.position.set(0, (WALL_H * CM) / 2, -(FLOOR_D * CM) / 2 + 0.01);
  wall.receiveShadow = true;
  wall.name = 'wall';
  roomGroup.add(wall);

  const sk = new THREE.Mesh(
    new THREE.BoxGeometry(WALL_W * CM, 0.08, 0.02),
    new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.65 })
  );
  sk.position.set(0, 0.04, -(FLOOR_D * CM) / 2 + 0.03);
  sk.castShadow = true;
  sk.receiveShadow = true;
  roomGroup.add(sk);
}

function tvSizeFromInch(inch) {
  const diag = inch * 2.54 * CM;
  const aspect = 16 / 9;
  const h = diag / Math.sqrt(1 + aspect * aspect);
  const w = h * aspect;
  return { w, h, d: 0.045 };
}

function defaultTvPose() {
  const panel = state.modules.find((m) => m.type === 'wallPanel')
    || state.modules.find((m) => m.type === 'back' && m.h >= 80)
    || null;
  const bench = state.modules.find((m) => m.type === 'plinth' || (m.type === 'frame' && !m.parentId && m.h <= 50));
  if (panel) {
    return {
      x: panel.x,
      y: panel.y + panel.h * 0.52,
      z: panel.z + panel.d / 2 + 3,
    };
  }
  if (bench) {
    return {
      x: bench.x,
      y: (bench.y || 0) + bench.h + 55,
      z: bench.z - bench.d / 2 - 2,
    };
  }
  return { x: 0, y: 110, z: -(FLOOR_D / 2) + 8 };
}

function alignTvToWall() {
  const pose = defaultTvPose();
  state.tv.x = pose.x;
  state.tv.y = pose.y;
  state.tv.z = pose.z;
  state.tv.manual = false;
  buildTv();
}

function buildTv() {
  if (tvMesh) {
    scene.remove(tvMesh);
    disposeObject(tvMesh);
    tvMesh = null;
  }
  if (!state.showTv) return;

  const { w, h, d } = tvSizeFromInch(state.tvInch);
  if (!state.tv.manual || state.tv.z == null) {
    const pose = defaultTvPose();
    if (!state.tv.manual) {
      state.tv.x = pose.x;
      state.tv.y = pose.y;
      state.tv.z = pose.z;
    } else if (state.tv.z == null) {
      state.tv.z = pose.z;
    }
  }

  const g = new THREE.Group();
  g.name = 'tv';
  g.userData.isTv = true;
  g.userData.moduleId = '__tv__';

  const x = (state.tv.x || 0) * CM;
  const y = (state.tv.y || 110) * CM;
  const z = (state.tv.z != null ? state.tv.z : -(FLOOR_D / 2) + 8) * CM;

  const screen = new THREE.Mesh(
    new THREE.BoxGeometry(w, h, d),
    new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.35, metalness: 0.4 })
  );
  screen.castShadow = true;
  const glass = new THREE.Mesh(
    new THREE.PlaneGeometry(w * 0.94, h * 0.9),
    new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.2, metalness: 0.55 })
  );
  glass.position.z = d / 2 + 0.002;
  g.add(screen, glass);

  const helper = new THREE.BoxHelper(g, 0x0058a3);
  helper.visible = state.selectedId === '__tv__';
  helper.name = 'selection';
  helper.raycast = () => {};
  g.add(helper);

  g.position.set(x, y, z);
  tvMesh = g;
  scene.add(tvMesh);
}

/** 2+ bölmeli gövdede dikey ara kayıt — açık rafta da 3 göz görünür */
function addBayDividers(group, mod, mat, innerH, yBase, depth) {
  const bays = Math.max(1, mod.doorBays || bayCountForWidth(mod.w));
  if (bays < 2) return;
  const t = 1.6 * CM;
  const W = mod.w * CM;
  for (let i = 1; i < bays; i++) {
    const x = -W / 2 + (W / bays) * i;
    const div = new THREE.Mesh(
      new THREE.BoxGeometry(t, Math.max(innerH - 0.01, t), Math.max(depth - 0.02, t)),
      mat.clone()
    );
    div.position.set(x, yBase + innerH / 2, 0);
    div.castShadow = true;
    div.raycast = () => {};
    group.add(div);
  }
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

function addInteriorLeds(group, W, H, D, shelfCount, shelfT, gap) {
  const night = !!state.night;
  const stripMat = new THREE.MeshBasicMaterial({
    color: 0xfff6e4,
    toneMapped: false,
  });
  const washMat = new THREE.MeshBasicMaterial({
    color: 0xffe7c0,
    transparent: true,
    opacity: night ? 0.22 : 0.14,
    depthWrite: false,
    toneMapped: false,
    side: THREE.DoubleSide,
  });
  for (let i = 0; i <= shelfCount; i++) {
    const yTop = i === shelfCount
      ? H - 0.35 * CM
      : gap * (i + 1) + shelfT * (i + 1);
    const strip = new THREE.Mesh(
      new THREE.BoxGeometry(W * 0.86, 0.35 * CM, 0.9 * CM),
      stripMat
    );
    strip.position.set(0, yTop - shelfT - 0.25 * CM, D * 0.28);
    strip.raycast = () => {};
    group.add(strip);
    const wash = new THREE.Mesh(
      new THREE.PlaneGeometry(W * 0.8, Math.max(gap * 0.85, 4 * CM)),
      washMat
    );
    wash.position.set(0, yTop - shelfT - gap * 0.48, -D * 0.15);
    wash.raycast = () => {};
    group.add(wash);
  }
}

function addLedStrip(group, mod, where) {
  const night = !!state.night;
  if (where === 'under') {
    // Tam en boyunca ince şerit + kenarlara yayılmış soft ışık (ortada tek nokta değil)
    const W = mod.w * CM;
    const D = mod.d * CM;
    const stripMat = new THREE.MeshStandardMaterial({
      color: 0xfff6e8,
      emissive: new THREE.Color(0xffe0a8),
      emissiveIntensity: night ? 1.35 : 0.65,
      roughness: 1,
      metalness: 0,
      transparent: true,
      opacity: 0.9,
    });
    const strip = new THREE.Mesh(
      new THREE.BoxGeometry(W * 0.94, 0.22 * CM, 0.7 * CM),
      stripMat
    );
    // Ön kenara yakın — mobilya altından sızan ince hat
    strip.position.set(0, -0.18 * CM, D * 0.32);
    strip.raycast = () => {};
    group.add(strip);

    // Saydam zemin yıkaması (şık, soft)
    const wash = new THREE.Mesh(
      new THREE.PlaneGeometry(W * 0.92, Math.max(D * 0.5, 18 * CM)),
      new THREE.MeshBasicMaterial({
        color: 0xffe8c4,
        transparent: true,
        opacity: night ? 0.2 : 0.1,
        depthWrite: false,
        side: THREE.DoubleSide,
      })
    );
    wash.rotation.x = -Math.PI / 2;
    wash.position.set(0, -0.12 * CM, D * 0.12);
    wash.raycast = () => {};
    group.add(wash);

    // Baştan sona kesintisiz çizgi — ortada tek leke değil
    const bar = new THREE.Mesh(
      new THREE.BoxGeometry(W * 0.96, 0.35 * CM, Math.min(D * 0.55, 16 * CM)),
      new THREE.MeshBasicMaterial({
        color: 0xffe7c2,
        transparent: true,
        opacity: night ? 0.55 : 0.38,
        depthWrite: false,
      })
    );
    bar.position.set(0, -1.1 * CM, D * 0.08);
    bar.raycast = () => {};
    group.add(bar);

    const n = 2;
    for (let i = 0; i < n; i++) {
      const x = (i === 0 ? -1 : 1) * W * 0.32;
      const light = new THREE.PointLight(0xffe4b8, night ? 0.28 : 0.16, 2.2, 1.2);
      light.position.set(x, -0.9 * CM, D * 0.18);
      group.add(light);
    }
  } else if (where === 'back') {
    const glow = new THREE.MeshStandardMaterial({
      color: 0xffe6b8,
      emissive: new THREE.Color(0xffcc66),
      emissiveIntensity: night ? 1.8 : 0.9,
      roughness: 1,
      metalness: 0,
      transparent: true,
      opacity: 0.85,
    });
    const edge = 0.45 * CM;
    const H = mod.h * CM;
    const W = mod.w * CM;
    const D = Math.max(1.5, mod.d) * CM;
    [[-W / 2 - edge, H / 2, -D / 2 - 0.01], [W / 2 + edge, H / 2, -D / 2 - 0.01]].forEach(([x, y, z]) => {
      const s = new THREE.Mesh(new THREE.BoxGeometry(edge, H * 0.96, edge), glow.clone());
      s.position.set(x, y, z);
      s.raycast = () => {};
      group.add(s);
    });
    const top = new THREE.Mesh(new THREE.BoxGeometry(W * 0.96, edge, edge), glow.clone());
    top.position.set(0, H + edge, -D / 2 - 0.01);
    top.raycast = () => {};
    group.add(top);
  } else if (where === 'shelf') {
    const W = mod.w * CM;
    const glow = new THREE.MeshStandardMaterial({
      color: 0xfff0d8,
      emissive: new THREE.Color(0xffd090),
      emissiveIntensity: night ? 1.2 : 0.55,
      roughness: 1,
      transparent: true,
      opacity: 0.8,
    });
    const strip = new THREE.Mesh(
      new THREE.BoxGeometry(W * 0.9, 0.2 * CM, 0.55 * CM),
      glow
    );
    strip.position.set(0, -0.15 * CM, 0);
    strip.raycast = () => {};
    group.add(strip);
  }
}

const glbCache = new Map();
const gltfLoader = new GLTFLoader();

function loadGlb(file) {
  if (glbCache.has(file)) return glbCache.get(file);
  const job = gltfLoader.loadAsync(`${modelsBase}/${file}`);
  glbCache.set(file, job);
  return job;
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
    const backChild = state.modules.find((m) => m.parentId === mod.id && m.type === 'back');
    if (backChild) {
      const backMat = await makeMaterial(backChild);
      const backD = Math.max(0.8, backChild.d) * CM;
      const backMesh = new THREE.Mesh(
        new THREE.BoxGeometry(W - 0.03, H - 0.03, backD),
        backMat
      );
      backMesh.position.set(0, H / 2, -D / 2 + backD / 2 + 0.002);
      backMesh.castShadow = true;
      group.add(backMesh);
    } else {
      const inner = new THREE.Mesh(
        new THREE.PlaneGeometry(W - 0.04, H - 0.04),
        new THREE.MeshStandardMaterial({ color: 0xfafafa, roughness: 0.95 })
      );
      inner.position.set(0, H / 2, -D / 2 + 0.005);
      group.add(inner);
    }
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
      const backChild = state.modules.find((m) => m.parentId === mod.id && m.type === 'back');
      const backD = backChild ? Math.max(0.8, backChild.d) * CM : 0;
      // Yerleşik arkalık — rafların arkasında net görünür
      if (backChild) {
        const backMat = await makeMaterial(backChild);
        const backMesh = new THREE.Mesh(
          new THREE.BoxGeometry(W - 0.03, H - 0.03, backD),
          backMat
        );
        backMesh.position.set(0, H / 2, -D / 2 + backD / 2 + 0.002);
        backMesh.castShadow = true;
        backMesh.receiveShadow = true;
        group.add(backMesh);
      }
      const shelfCount = Math.max(1, Math.min(20, mod.shelfCount != null
        ? mod.shelfCount
        : Math.max(1, Math.floor(mod.h / 22))));
      // Eşit boşluk: üst + ara + alt aynı (raf kalınlığı düşülerek)
      const shelfT = 1.5 * CM;
      const gap = Math.max(0.4 * CM, (H - shelfCount * shelfT) / (shelfCount + 1));
      for (let i = 0; i < shelfCount; i++) {
        const y = gap * (i + 1) + shelfT * i + shelfT / 2;
        const sh = new THREE.Mesh(
          new THREE.BoxGeometry(W - 0.04, shelfT, D - 0.02 - backD),
          mat.clone()
        );
        sh.position.set(0, y, backD / 2);
        sh.castShadow = true;
        group.add(sh);
      }
      if (mod.led) addInteriorLeds(group, W, H, D, shelfCount, shelfT, gap);
    }
  } else if (mod.type === 'door') {
    await buildDoorMesh(group, mod, mat);
  } else if (mod.type === 'plinth') {
    const bazaH = mod.baza ? Math.min(10, Math.max(6, (mod.bazaH || 8))) : 0;
    const bodyH = Math.max(8, mod.h - bazaH);
    // Kapak / çekmece alabilen banko → içi boş gövde (masif blok değil)
    if (canTakeFronts(mod)) {
      const shell = frameShellGeometry(mod.w, bodyH, mod.d, 1.8);
      for (const part of shell.parts) {
        const mesh = new THREE.Mesh(new THREE.BoxGeometry(...part.s), mat.clone());
        mesh.position.set(part.p[0], part.p[1] + bazaH * CM, part.p[2]);
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        group.add(mesh);
      }
      const backChild = state.modules.find((m) => m.parentId === mod.id && m.type === 'back');
      if (backChild) {
        const backMat = await makeMaterial(backChild);
        const backD = Math.max(0.8, backChild.d) * CM;
        const backMesh = new THREE.Mesh(
          new THREE.BoxGeometry(shell.W - 0.03, shell.H - 0.03, backD),
          backMat
        );
        backMesh.position.set(0, bazaH * CM + shell.H / 2, -shell.D / 2 + backD / 2 + 0.002);
        backMesh.castShadow = true;
        group.add(backMesh);
      }
      addBayDividers(group, mod, mat, shell.H, bazaH * CM, shell.D);
    } else {
      const body = new THREE.Mesh(boxGeo(mod.w, bodyH, mod.d, mod.radius || 0), mat);
      body.position.y = (bazaH + bodyH / 2) * CM;
      body.castShadow = true;
      group.add(body);
    }
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
  } else if (mod.type === 'glb' && mod.glbFile) {
    try {
      const gltf = await loadGlb(mod.glbFile);
      const root = gltf.scene.clone(true);
      root.traverse((o) => {
        if (!o.isMesh) return;
        o.castShadow = true;
        o.receiveShadow = true;
        o.userData.sharedAsset = true;
      });
      const box = new THREE.Box3().setFromObject(root);
      const size = new THREE.Vector3();
      const center = new THREE.Vector3();
      box.getSize(size);
      box.getCenter(center);
      root.position.set(-center.x, -box.min.y, -center.z);
      group.add(root);
      if (size.x > 0.05) {
        mod.w = Math.round(size.x / CM);
        mod.h = Math.round(size.y / CM);
        mod.d = Math.round(size.z / CM);
      }
    } catch (err) {
      console.warn(err);
      const fb = new THREE.Mesh(boxGeo(mod.w || 120, mod.h || 40, mod.d || 40), mat);
      fb.position.y = ((mod.h || 40) * CM) / 2;
      group.add(fb);
    }
    group.userData.originAtBottom = true;
  } else if (mod.type === 'wallPanel') {
    const thick = Math.max(1.5, mod.d) * CM;
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, mod.h * CM, thick),
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
    // İnce paneli seçmek için görünmez pick kutusu
    const pick = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, mod.h * CM, Math.max(8 * CM, thick + 2 * CM)),
      new THREE.MeshBasicMaterial({ transparent: true, opacity: 0, depthWrite: false })
    );
    pick.position.y = (mod.h * CM) / 2;
    pick.name = 'pick-proxy';
    group.add(pick);
    group.userData.originAtBottom = true;
    if (mod.led) addLedStrip(group, mod, 'back');
  } else if (mod.type === 'back') {
    const host = mod.parentId ? state.modules.find((m) => m.id === mod.parentId) : null;
    if (host && (host.type === 'shelf' || host.type === 'frame' || host.type === 'plinth')) {
      // Görünür plaka host mesh'inde; seçim için hayalet kutu
      const ghost = new THREE.Mesh(
        new THREE.BoxGeometry(mod.w * CM, mod.h * CM, Math.max(0.8, mod.d) * CM),
        new THREE.MeshBasicMaterial({ transparent: true, opacity: 0.01, depthWrite: false })
      );
      ghost.position.y = (mod.h * CM) / 2;
      group.add(ghost);
    } else {
      const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(mod.w * CM, mod.h * CM, Math.max(0.8, mod.d) * CM),
        mat
      );
      mesh.position.y = (mod.h * CM) / 2;
      mesh.castShadow = true;
      group.add(mesh);
      const pick = new THREE.Mesh(
        new THREE.BoxGeometry(mod.w * CM, mod.h * CM, Math.max(6 * CM, (mod.d + 2) * CM)),
        new THREE.MeshBasicMaterial({ transparent: true, opacity: 0, depthWrite: false })
      );
      pick.position.y = (mod.h * CM) / 2;
      pick.name = 'pick-proxy';
      group.add(pick);
    }
    group.userData.originAtBottom = true;
    if (mod.led) addLedStrip(group, mod, 'back');
  } else if (mod.type === 'slat') {
    const slatW = mod.slatWidth || 1.6; // cm
    const gapCm = mod.slatGap != null ? mod.slatGap : 2.5; // cm between slats
    const pitch = slatW + gapCm;
    const count = Math.max(3, Math.floor((mod.w + gapCm) / pitch));
    const used = count * slatW + (count - 1) * gapCm;
    const startX = -used / 2;
    const D = Math.max(1.5, mod.d) * CM;
    const H = mod.h * CM;
    // Arkalık (çıta arkası düz pano)
    if (mod.hasBack) {
      const backD = Math.max(0.4, mod.backThicknessCm || 0.8) * CM;
      const backMat = new THREE.MeshStandardMaterial({
        color: new THREE.Color(mod.backColor || '#d4b896'),
        roughness: 0.75,
        metalness: 0.02,
      });
      const backMesh = new THREE.Mesh(
        new THREE.BoxGeometry(mod.w * CM * 0.98, H * 0.98, backD),
        backMat
      );
      backMesh.position.set(0, H / 2, -D / 2 - backD / 2 + 0.001);
      backMesh.castShadow = true;
      backMesh.receiveShadow = true;
      group.add(backMesh);
    }
    for (let i = 0; i < count; i++) {
      const slat = new THREE.Mesh(
        new THREE.BoxGeometry(slatW * CM, H, D),
        mat.clone()
      );
      const x = (startX + slatW / 2 + i * pitch) * CM;
      slat.position.set(x, H / 2, 0);
      slat.castShadow = true;
      group.add(slat);
    }
    if (count >= 2 && state.showMeasure) {
      const el = document.createElement('div');
      el.textContent = gapCm.toFixed(1).replace(/\.0$/, '') + ' cm';
      el.style.cssText = 'color:#0058a3;background:rgba(255,255,255,.92);padding:2px 7px;border-radius:6px;font:700 11px Montserrat,sans-serif;white-space:nowrap;border:1px solid #bfdbfe;';
      const obj = new CSS2DObject(el);
      const midX = (startX + slatW + gapCm / 2) * CM;
      obj.position.set(midX, H * 0.55, D / 2 + 0.04);
      obj.name = 'slat-gap';
      group.add(obj);
    }
    const pick = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, H, Math.max(6 * CM, D + 2 * CM)),
      new THREE.MeshBasicMaterial({ transparent: true, opacity: 0, depthWrite: false })
    );
    pick.position.y = H / 2;
    pick.name = 'pick-proxy';
    group.add(pick);
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

  // Seçim kutusu — pick-proxy şişkinliğini sayma
  const pickProxies = group.children.filter((c) => c.name === 'pick-proxy');
  pickProxies.forEach((c) => group.remove(c));
  const helper = new THREE.BoxHelper(group, 0x0058a3);
  helper.visible = false;
  helper.name = 'selection';
  helper.raycast = () => {};
  group.add(helper);
  pickProxies.forEach((c) => group.add(c));

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
  const radius = mod.radius || 0;

  if (style === 'open') {
    // Açık raf nişi — sadece yan/üst kenar çerçevesi
    const t = 1.6 * CM;
    const rimMat = mat.clone();
    [
      { s: [W, t, D], p: [0, t / 2, 0] },
      { s: [W, t, D], p: [0, H - t / 2, 0] },
      { s: [t, H - 2 * t, D], p: [-W / 2 + t / 2, H / 2, 0] },
      { s: [t, H - 2 * t, D], p: [W / 2 - t / 2, H / 2, 0] },
    ].forEach((part) => {
      const m = new THREE.Mesh(new THREE.BoxGeometry(...part.s), rimMat.clone());
      m.position.set(...part.p);
      m.castShadow = true;
      group.add(m);
    });
    group.userData.originAtBottom = true;
    return;
  }

  if (style === 'drawer') {
    const body = new THREE.Mesh(boxGeo(mod.w, mod.h, Math.max(mod.d, 28), radius), mat);
    body.position.y = H / 2;
    body.castShadow = true;
    group.add(body);
    const face = new THREE.Mesh(
      boxGeo(mod.w, mod.h, 1.8, radius),
      await makeMaterial(mod)
    );
    face.position.set(0, H / 2, Math.max(D, 28 * CM) / 2 + 0.005);
    face.castShadow = true;
    group.add(face);
    addHandle(group, mod, W, H, Math.max(D, 28 * CM) / 2 + 0.02);
  } else if (style === 'glass') {
    const frameColor = mod.frameColor || '#c5c8cc';
    const glassColor = mod.glassColor || '#e7eef6';
    const frame = frameProfile({ frameColor });
    const glass = glassProfile(mod);
    const frameMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(frame.color),
      metalness: frame.metalness,
      roughness: frame.roughness,
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
    const pane = new THREE.Mesh(
      new THREE.BoxGeometry(W - t * 2, H - t * 2, D * 0.35),
      new THREE.MeshPhysicalMaterial({
        color: new THREE.Color(glass.color || glassColor),
        roughness: glass.roughness,
        metalness: glass.metalness,
        transmission: glass.transmission,
        transparent: true,
        opacity: Math.max(glass.opacity, 0.08),
        thickness: glass.id === 'satin' ? 0.08 : 0.03,
        ior: 1.5,
      })
    );
    pane.position.set(0, H / 2, 0);
    group.add(pane);
  } else {
    const panel = new THREE.Mesh(boxGeo(mod.w, Math.max(mod.h, 1), Math.max(mod.d, 1.6), radius), mat);
    panel.position.y = H / 2;
    panel.castShadow = true;
    group.add(panel);
    if (style === 'push' && (mod.handleStyle === 'none' || !mod.handleStyle)) {
      const tip = new THREE.Mesh(
        new THREE.SphereGeometry(0.008, 10, 10),
        new THREE.MeshStandardMaterial({ color: 0x666666, metalness: 0.5, roughness: 0.3 })
      );
      tip.position.set(0, H * 0.15, D / 2 + 0.008);
      group.add(tip);
    } else {
      addHandle(group, mod, W, H, D / 2 + 0.012);
    }
  }
  group.userData.originAtBottom = true;
}

function addHandle(group, mod, W, H, zFront) {
  const style = mod.handleStyle || (mod.doorStyle === 'drawer' ? 'bar' : mod.doorStyle === 'hinge' ? 'bar' : 'none');
  if (style === 'none') return;
  const pos = mod.handlePos || (mod.doorStyle === 'drawer' ? 'top' : 'right');
  const metal = new THREE.MeshStandardMaterial({ color: 0x888888, metalness: 0.85, roughness: 0.22 });
  let hx = 0, hy = H / 2;
  if (pos === 'left') hx = -W * 0.35;
  if (pos === 'right') hx = W * 0.35;
  if (pos === 'top') hy = H * 0.78;
  if (pos === 'bottom') hy = H * 0.22;
  if (pos === 'center') { hx = 0; hy = H / 2; }

  if (style === 'knob') {
    const knob = new THREE.Mesh(new THREE.SphereGeometry(0.012, 12, 12), metal);
    knob.position.set(hx, hy, zFront);
    group.add(knob);
  } else if (style === 'edge') {
    const edge = new THREE.Mesh(new THREE.BoxGeometry(W * 0.9, 0.006, 0.01), metal);
    edge.position.set(0, pos === 'bottom' ? H * 0.08 : H * 0.92, zFront);
    group.add(edge);
  } else {
    // bar
    const len = Math.min(W, H) * 0.35;
    const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.005, 0.005, len, 12), metal);
    if (pos === 'top' || pos === 'bottom' || pos === 'center') {
      bar.rotation.z = Math.PI / 2;
    }
    bar.position.set(hx, hy, zFront);
    group.add(bar);
  }
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
    if (o.geometry && !o.userData.sharedAsset) o.geometry.dispose();
    if (o.material && !o.userData.sharedAsset) {
      const mats = Array.isArray(o.material) ? o.material : [o.material];
      mats.forEach((m) => {
        if (m.map && !m.map.userData?.pooled) m.map.dispose();
        m.dispose();
      });
    }
  });
}

function clearAllLabels() {
  if (labelRenderer?.domElement) {
    labelRenderer.domElement.replaceChildren();
  }
}

let rebuildToken = 0;
async function rebuildModules() {
  const token = ++rebuildToken;
  clearAllLabels();
  while (modulesGroup.children.length) {
    const c = modulesGroup.children[0];
    modulesGroup.remove(c);
    disposeObject(c);
  }
  const snapshot = state.modules.slice();
  for (const mod of snapshot) {
    if (token !== rebuildToken) return;
    const mesh = await createModuleMesh(mod);
    if (token !== rebuildToken) {
      disposeObject(mesh);
      return;
    }
    modulesGroup.add(mesh);
  }
  if (token !== rebuildToken) return;
  updateSelectionVisual();
  if (state.showTv) buildTv();
  scheduleAutoSave();
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
      helper.material.linewidth = selected ? 2 : 1;
      helper.update();
    }
  });
  if (tvMesh) {
    const helper = tvMesh.children.find((c) => c.name === 'selection');
    if (helper) {
      const selected = state.selectedId === '__tv__';
      const hovered = state.hoveredId === '__tv__' && !selected;
      helper.visible = selected || hovered;
      if (helper.visible) {
        helper.material.color.setHex(selected ? 0x0058a3 : 0x38bdf8);
        helper.update();
      }
    }
  }
  const bar = $('#selection-bar');
  if (bar) bar.hidden = !state.selectedId;
  // cursor hover-tag tarafından yönetilir
}

/** Kapak/çekmece kabul eden gövdeler — duvar paneli hariç */
const FRONT_HOST_TYPES = new Set(['frame', 'plinth', 'shelf']);
const HOST_TYPES = new Set(['frame', 'plinth', 'shelf', 'wallPanel']);
const ATTACH_FRONT = new Set(['door']); // çıta serbest; kapak özelleştirmeden
const ATTACH_BACK = new Set(['back']);
const ATTACH_TOP = new Set(['top']);

function isHost(m) {
  return m && HOST_TYPES.has(m.type);
}

function canTakeFronts(m) {
  return m && FRONT_HOST_TYPES.has(m.type);
}

function wallFlushZ(depthCm) {
  // Duvar görsel düzlemi ~ -FLOOR_D/2; parçanın ARKA yüzü odaya 0.8 cm içeride
  // (ince paneller duvarın içine gömülmesin)
  const wallPlane = -FLOOR_D / 2 + 0.8;
  return wallPlane + (depthCm || 2) / 2;
}

/** IKEA/masaüstü planlayıcı: parça rolü — zemin / duvar / bağlı */
function placementRole(m) {
  if (!m || m.parentId) return 'attached';
  if (m.type === 'wallPanel' || m.type === 'slat') return 'wall';
  if (m.type === 'back' && !m.parentId) return 'wall';
  if (m.type === 'plinth' || m.type === 'frame' || m.type === 'shelf' || m.type === 'glb') return 'floor';
  return 'free';
}

function isFloatingBench(m) {
  return m && m.type === 'plinth' && (m.baza === false || m.floatY != null);
}

const WALL_MAGNET_CM = 10;
/** Bırakınca bir kez oturt. Sürüklerken çağrılmaz — parça kaybolmaz, yana fırlamaz. */
function settleDropped(mod) {
  if (!mod || mod.parentId) return;
  const role = placementRole(mod);
  const clampX = () => {
    mod.x = Math.max(-WALL_W / 2 + mod.w / 2, Math.min(WALL_W / 2 - mod.w / 2, mod.x));
  };

  if (role === 'wall') {
    mod.z = wallFlushZ(mod.d);
    mod.y = Math.max(0, Math.min(WALL_H - mod.h, mod.y || 0));
    clampX();
    return;
  }

  if (role !== 'floor') return;

  const flush = wallFlushZ(mod.d);
  const wallPlane = -FLOOR_D / 2 + 0.8;
  if ((mod.z - mod.d / 2) - wallPlane < WALL_MAGNET_CM) mod.z = flush;
  mod.z = Math.max(flush, Math.min(FLOOR_D / 2 - mod.d / 2 - 20, mod.z));
  clampX();

  const stacked = snapOntoSupports(mod);
  if (!stacked) {
    if (isFloatingBench(mod)) mod.y = mod.floatY != null ? mod.floatY : (mod.y || 15);
    else mod.y = 0;
  }

  const a = solidAabb(mod);
  for (const other of solidsExcept(mod.id)) {
    if (isRestingOn(mod, other) || isRestingOn(other, mod)) continue;
    if (Math.abs((mod.y || 0) - (other.y || 0)) > 6) continue;
    const b = solidAabb(other);
    if (!aabbOverlap(a, b, 0.4)) continue;
    const overlap = Math.min(a.maxX, b.maxX) - Math.max(a.minX, b.minX);
    if (overlap < 1) continue;
    mod.x += mod.x >= other.x ? overlap + 0.4 : -(overlap + 0.4);
    clampX();
    break;
  }

  if (isHost(mod)) syncAttachedToHost(mod);
}

/**
 * Zemin mobilyası (alt blok / raf kule):
 * - Zemine oturur (yüzen hariç)
 * - Arka duvara yaklaşınca otomatik yaslanır
 */
function applyFloorPlacement(mod, opts = {}) {
  if (placementRole(mod) !== 'floor') return;

  const resting = solidsExcept(mod.id).some((o) => isRestingOn(mod, o));
  if (!resting) {
    if (isFloatingBench(mod)) {
      mod.y = mod.floatY != null ? mod.floatY : Math.max(mod.y || 0, 12);
    } else {
      mod.y = 0;
    }
  }

  const flush = wallFlushZ(mod.d);
  const rearFace = mod.z - mod.d / 2;
  const wallPlane = -FLOOR_D / 2 + 0.8;
  const gapToWall = rearFace - wallPlane;
  const magnet = opts.dragging ? 5 : WALL_MAGNET_CM;
  if (gapToWall < magnet || Math.abs(mod.z - flush) < magnet) {
    mod.z = flush;
  }

  mod.x = Math.max(-WALL_W / 2 + mod.w / 2, Math.min(WALL_W / 2 - mod.w / 2, mod.x));
  mod.z = Math.max(flush, Math.min(FLOOR_D / 2 - mod.d / 2 - 20, mod.z));
}

/**
 * Duvar paneli / çıta / serbest arkalık:
 * - Her zaman arka duvara kilitli (Z)
 * - Banko üstüne / yan panele kenar manyetiği
 */
function applyWallPlacement(mod) {
  if (placementRole(mod) !== 'wall') return;
  mod.z = wallFlushZ(mod.d);
  mod.x = Math.max(-WALL_W / 2 + mod.w / 2, Math.min(WALL_W / 2 - mod.w / 2, mod.x));
  mod.y = Math.max(0, Math.min(WALL_H - mod.h, mod.y || 0));
}

/** Tüm yerleştirme kurallarını uygula (sürükle / ekle / yükle) */
function applyPlacementRules(mod, opts = {}) {
  if (!mod || mod.parentId) return;
  const role = placementRole(mod);
  if (role === 'floor') {
    applyFloorPlacement(mod, opts);
    snapOntoSupports(mod);
    if (!opts.dragging) snapBesideNeighbors(mod);
    resolveSolidPlacement(mod);
    applyFloorPlacement(mod, opts);
  } else if (role === 'wall') {
    applyWallPlacement(mod, opts);
  }
  if (isHost(mod)) syncAttachedToHost(mod);
  resyncSpanTopsFor(mod.id);
}

/** Gövde / kutu — mobilyacı mantığında hacim kaplar (kapak/arkalık/duvar çıta değil) */
function isSolidBody(m) {
  if (!m || m.parentId) return false;
  if (m.type === 'door' || m.type === 'back' || m.type === 'top' || m.type === 'leg') return false;
  if (m.type === 'wallPanel') return false; // duvar kaplaması — yer planı gövdesi değil
  if (m.type === 'slat') return false; // çıta serbest yerleştirilir
  if (m.type === 'shelf' && (m.interior || m.h <= 4)) return false;
  return ['frame', 'plinth', 'shelf', 'glb'].includes(m.type);
}

function isWallMounted(m) {
  return placementRole(m) === 'wall';
}

function solidAabb(m) {
  return {
    minX: m.x - m.w / 2,
    maxX: m.x + m.w / 2,
    minY: m.y || 0,
    maxY: (m.y || 0) + m.h,
    minZ: m.z - m.d / 2,
    maxZ: m.z + m.d / 2,
  };
}

function aabbOverlap(a, b, eps = 0.8) {
  return a.minX < b.maxX - eps && a.maxX > b.minX + eps
    && a.minY < b.maxY - eps && a.maxY > b.minY + eps
    && a.minZ < b.maxZ - eps && a.maxZ > b.minZ + eps;
}

/** Gövde üst yüzeyi (üst panel dahil) — cm */
function hostSurfaceY(m) {
  if (!m) return 0;
  const tops = childrenOf(m.id).filter((c) => c.type === 'top');
  const topH = tops.reduce((s, t) => Math.max(s, t.h || 0), 0);
  return (m.y || 0) + m.h + topH;
}

/** Üstteki altın üzerine oturuyor mu? (kule banko üstü) */
function isRestingOn(upper, lower) {
  const top = hostSurfaceY(lower);
  if (Math.abs((upper.y || 0) - top) > 4) return false;
  const ua = solidAabb(upper);
  const la = solidAabb(lower);
  const overlapW = Math.min(ua.maxX, la.maxX) - Math.max(ua.minX, la.minX);
  const overlapD = Math.min(ua.maxZ, la.maxZ) - Math.max(ua.minZ, la.minZ);
  return overlapW > Math.min(upper.w, lower.w) * 0.25
    && overlapD > Math.min(upper.d, lower.d) * 0.2;
}

function solidsExcept(id) {
  return state.modules.filter((m) => isSolidBody(m) && m.id !== id);
}

/**
 * Mobilyacı kuralı: gövdeler birbirinin içine giremez.
 * Aynı zeminde → planda ayır (önce X). Üst üste oturma bilinçli yığma.
 */
function resolveSolidPlacement(mod) {
  if (!isSolidBody(mod)) return;

  for (let pass = 0; pass < 8; pass++) {
    let hit = null;
    const a = solidAabb(mod);
    for (const other of solidsExcept(mod.id)) {
      if (isRestingOn(mod, other) || isRestingOn(other, mod)) continue;
      const b = solidAabb(other);
      if (!aabbOverlap(a, b)) continue;
      hit = { other, a, b };
      break;
    }
    if (!hit) break;

    const { other, a: A, b: B } = hit;
    const overlapX = Math.min(A.maxX - B.minX, B.maxX - A.minX);
    const overlapZ = Math.min(A.maxZ - B.minZ, B.maxZ - A.minZ);
    // Alçak bankonun üzerine kule/raf — yana itmek yerine üstüne oturt
    if (other.h <= 80 && mod.h > other.h + 16 && overlapX > 4 && overlapZ > 4
      && (mod.y || 0) <= hostSurfaceY(other) + 2) {
      mod.y = hostSurfaceY(other);
      continue;
    }
    const coplanar = Math.abs((mod.y || 0) - (other.y || 0)) < 4;

    if (coplanar) {
      if (overlapX >= 0.5) {
        const pushRight = mod.x >= other.x;
        mod.x = pushRight
          ? other.x + other.w / 2 + mod.w / 2
          : other.x - other.w / 2 - mod.w / 2;
        if (Math.abs(mod.z - other.z) < 30) mod.z = other.z;
      } else if (overlapZ >= 0.5) {
        const pushFwd = mod.z >= other.z;
        mod.z = pushFwd
          ? other.z + other.d / 2 + mod.d / 2
          : other.z - other.d / 2 - mod.d / 2;
      }
    } else {
      const overlapY = Math.min(A.maxY - B.minY, B.maxY - A.minY);
      if (overlapX <= overlapZ && overlapX <= overlapY) {
        const pushRight = mod.x >= other.x;
        mod.x = pushRight
          ? other.x + other.w / 2 + mod.w / 2
          : other.x - other.w / 2 - mod.w / 2;
      } else if (overlapZ <= overlapY) {
        const pushFwd = mod.z >= other.z;
        mod.z = pushFwd
          ? other.z + other.d / 2 + mod.d / 2
          : other.z - other.d / 2 - mod.d / 2;
      } else if (mod.y + mod.h / 2 >= other.y + other.h / 2) {
        mod.y = other.y + other.h;
      } else {
        mod.y = Math.max(0, other.y - mod.h);
      }
    }

    mod.x = Math.max(-WALL_W / 2 + mod.w / 2, Math.min(WALL_W / 2 - mod.w / 2, mod.x));
    mod.z = Math.max(wallFlushZ(mod.d), Math.min(FLOOR_D / 2 - mod.d / 2 - 20, mod.z));
    mod.y = Math.max(0, mod.y);
  }
}

/** Tüm gövdeleri mobilyacı kurallarına göre ayır (yükleme / toplu düzeltme) */
function resolveAllSolids() {
  state.modules.filter((m) => !m.parentId).forEach((m) => {
    applyPlacementRules(m);
  });
}

/** Yan yana gövdeleri milimetrik hizala (mimari çizgi) */
function snapBesideNeighbors(mod) {
  if (!isSolidBody(mod) || mod.type === 'wallPanel') return;
  for (const o of solidsExcept(mod.id)) {
    if (o.type === 'wallPanel') continue;
    if (Math.abs(mod.z - o.z) > 18) continue;

    // Üst üste oturma: yan itme YAPMA — sadece yüzeye kilitle
    if (isRestingOn(mod, o)) {
      mod.y = hostSurfaceY(o);
      mod.z = o.z;
      const half = o.w / 2 - mod.w / 2;
      if (half >= 0) {
        mod.x = Math.max(o.x - half, Math.min(o.x + half, mod.x));
      }
      continue;
    }
    if (isRestingOn(o, mod)) continue;

    if (Math.abs(mod.y - o.y) > 8) continue;

    // gapL: o'nun sağı ile mod'un solu arası (mod sağdaysa pozitif)
    // gapR: mod'un sağı ile o'nun solu arası (mod soldaysa pozitif)
    const gapL = (mod.x - mod.w / 2) - (o.x + o.w / 2);
    const gapR = (o.x - o.w / 2) - (mod.x + mod.w / 2);
    const overlapping = gapL < 0 && gapR < 0;
    const nearGap = gapL >= 0 ? gapL : (gapR >= 0 ? gapR : 0);
    if (overlapping || nearGap < 6) {
      if (Math.abs(gapL) <= Math.abs(gapR)) {
        mod.x = o.x + o.w / 2 + mod.w / 2;
      } else {
        mod.x = o.x - o.w / 2 - mod.w / 2;
      }
      mod.z = o.z;
      if (Math.abs(mod.y - o.y) < 8) mod.y = o.y;
    }
  }
}

/**
 * Raf / kuleyi alt blok üstüne oturt (plan kesişince).
 * Yerden sürüklerken veya yüzeye yakınken manyetik snap.
 */
function snapOntoSupports(mod) {
  if (!isSolidBody(mod)) return false;
  let best = null;
  let bestArea = 0;
  for (const o of solidsExcept(mod.id)) {
    if (o.type === 'wallPanel') continue;
    // Destek: daha alçak veya aynı yükseklikte banko; kule üzerine kule değil
    const surf = hostSurfaceY(o);
    if (surf < 8) continue;
    if (o.h >= 120 && mod.h >= 120) continue;

    const oa = solidAabb(o);
    const overlapW = Math.min(mod.x + mod.w / 2, oa.maxX) - Math.max(mod.x - mod.w / 2, oa.minX);
    const overlapD = Math.min(mod.z + mod.d / 2, oa.maxZ) - Math.max(mod.z - mod.d / 2, oa.minZ);
    if (overlapW < Math.min(10, Math.min(mod.w, o.w) * 0.18)) continue;
    if (overlapD < Math.min(8, Math.min(mod.d, o.d) * 0.18)) continue;

    const onFloor = (mod.y || 0) < 6;
    const nearSurf = Math.abs((mod.y || 0) - surf) < 28;
    if (!onFloor && !nearSurf && !isRestingOn(mod, o)) continue;

    // Destek yeterince yüksek olsun (ayak/çok alçak değil)
    if (surf < 12) continue;

    const area = overlapW * overlapD;
    if (area > bestArea) {
      bestArea = area;
      best = o;
    }
  }
  if (!best) return false;
  const surf = hostSurfaceY(best);
  mod.y = surf;
  mod.z = best.z;
  const half = best.w / 2 - mod.w / 2;
  if (half >= 0) {
    mod.x = Math.max(best.x - half, Math.min(best.x + half, mod.x));
  } else {
    // Destekten genişse ortala
    mod.x = best.x;
  }
  return true;
}

function sendPanelToWall(mod) {
  if (!mod || (mod.type !== 'wallPanel' && mod.type !== 'back' && mod.type !== 'slat')) return;
  // Arka pano / çıta her zaman arka duvarda — bankonun arkasına gömme
  applyWallPlacement(mod);
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
      return null;
    }
    const thicknessMm = extras.thicknessMm || 18;
    const d = extras.d != null ? extras.d : thicknessMm / 10;
    // Arkalık yüksekliği her zaman host yüksekliği — kalınlık sadece d
    const h = host.h;
    const y = baseY;
    return {
      w: extras.w || host.w,
      h,
      d,
      thicknessMm,
      x: extras.relX != null ? host.x + extras.relX : host.x,
      y,
      z: host.z - host.d / 2 + d / 2 + 0.15, // arkalık gövde içinde (rabbet)
    };
  }

  if (type === 'slat') {
    const d = extras.d || 3;
    const h = extras.h || host.h;
    const y = extras.yOffset != null ? baseY + extras.yOffset : baseY;
    // Raf/kule: çıta arkaya (arkalık gibi); diğerlerinde öne
    const onBack = host.type === 'shelf' || extras.onBack;
    const z = onBack
      ? host.z - host.d / 2 + d / 2 + 0.15
      : host.z + host.d / 2 + d / 2 + 0.3;
    return {
      w: extras.w || host.w,
      h,
      d,
      x: extras.relX != null ? host.x + extras.relX : host.x,
      y,
      z,
      slatWidth: extras.slatWidth || 1.6,
      slatGap: extras.slatGap != null ? extras.slatGap : 2.5,
      onBack: !!onBack,
    };
  }

  if (type === 'door') {
    if (!canTakeFronts(host)) return null;
    const bazaH = (host.type === 'plinth' && host.baza) ? (host.bazaH || 8) : 0;
    const bays = Math.max(1, extras.bays || bayCountForWidth(host.w));
    const bayIndex = extras.bayIndex != null ? extras.bayIndex : 0;
    if (doorStyle === 'drawer') {
      // Her blok (bay) kendi çekmecesi — 4 blok = 4 sütun, tek geniş çekmece değil
      const usable = host.h - bazaH;
      const rows = extras.rows || (usable >= 50 ? 2 : 1);
      const row = extras.drawerRow != null ? extras.drawerRow : 0;
      const rowH = (usable - 3) / rows;
      const yOff = bazaH + 1.5 + row * rowH;
      const drawerW = extras.w || (host.w / bays - 1.2);
      const left = host.x - host.w / 2;
      const cx = extras.x != null
        ? extras.x
        : left + drawerW / 2 + 0.6 + bayIndex * (drawerW + 1.2);
      return {
        w: drawerW,
        h: Math.max(12, rowH - 0.5),
        d: Math.max(18, host.d - 4),
        x: cx,
        y: baseY + yOff,
        z: host.z,
        drawerRow: row,
        rows,
        bayIndex,
        bays,
        yOffset: yOff,
      };
    }
    const doorW = extras.w || (host.w / bays - 1.2);
    const left = host.x - host.w / 2;
    const cx = extras.x != null
      ? extras.x
      : left + doorW / 2 + 0.6 + bayIndex * (doorW + 1.2);
    const frontD = doorStyle === 'open' ? Math.max(2, host.d - 4) : 2;
    return {
      w: doorW,
      h: Math.max(12, extras.h || host.h - bazaH - 2),
      d: frontD,
      x: cx,
      y: baseY + bazaH + 1,
      z: doorStyle === 'open' ? host.z : host.z + host.d / 2 + 1.1,
      bayIndex,
      bays,
    };
  }

  if (type === 'shelf' || type === 'innerShelf') {
    const yOff = extras.yOffset != null ? extras.yOffset : host.h / 2;
    return {
      w: host.w - 3.6,
      h: extras.h != null ? extras.h : 1.8,
      d: extras.d != null ? extras.d : host.d - 2.5,
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
    if (child.type === 'top' && child.spanHosts?.length > 1) {
      layoutSpanTop(child);
      return;
    }
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
      onBack: child.onBack,
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
    if (pose.onBack != null) child.onBack = pose.onBack;
  });
}

/** Yayılı üst panel: sadece hâlâ bitişik olan gövdelere yay; ayrılanları kopar */
function layoutSpanTop(top) {
  if (!top || top.type !== 'top') return;
  const anchor = state.modules.find((m) => m.id === top.parentId);
  const listed = (top.spanHosts || [])
    .map((id) => state.modules.find((m) => m.id === id))
    .filter(Boolean);
  if (!anchor || !listed.length) {
    delete top.spanHosts;
    if (anchor) {
      const pose = attachToHost(anchor, 'top', {});
      if (pose) Object.assign(top, pose);
    }
    return;
  }
  // Anchor’ın bitişik zinciri ∩ spanHosts
  const contigIds = new Set(contiguousHosts(anchor).map((h) => h.id));
  let group = listed.filter((m) => contigIds.has(m.id));
  if (!group.find((m) => m.id === anchor.id)) group = [anchor, ...group];
  group = [...new Map(group.map((m) => [m.id, m])).values()];

  if (group.length <= 1) {
    delete top.spanHosts;
    const pose = attachToHost(anchor, 'top', {});
    if (pose) {
      top.w = pose.w; top.h = pose.h; top.d = pose.d;
      top.x = pose.x; top.y = pose.y; top.z = pose.z;
    }
    return;
  }

  top.spanHosts = group.map((g) => g.id);
  top.parentId = anchor.id;
  const minX = Math.min(...group.map((m) => m.x - m.w / 2));
  const maxX = Math.max(...group.map((m) => m.x + m.w / 2));
  top.w = (maxX - minX) + 2;
  top.d = Math.max(...group.map((m) => m.d)) + 2;
  top.x = (minX + maxX) / 2;
  top.y = Math.max(...group.map((m) => (m.y || 0) + m.h));
  top.z = group.reduce((s, m) => s + m.z, 0) / group.length;
}

/** Bu gövdeyi içeren tüm yayılı üst panelleri güncelle */
function resyncSpanTopsFor(modId) {
  state.modules
    .filter((m) => m.type === 'top' && m.spanHosts?.includes(modId))
    .forEach(layoutSpanTop);
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
    return { finish: 'matte', color: '#d4b896', materialName: 'Ahşap arkalık' };
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
  if ((type === 'door') && !canTakeFronts(host)) {
    showHint('Duvar paneline kapak eklenmez — düz plaka / kaplama kullanın');
    return;
  }
  if (type === 'slat' && host.type === 'wallPanel') {
    showHint('Duvar paneline çıta yerine ayrı çıta duvarı ekleyin');
    return;
  }
  const doorStyle = opts.doorStyle || (type === 'door' ? 'push' : undefined);
  pushHistory();
  if (type === 'back') {
    const kill = new Set(childrenOf(host.id).filter((c) => c.type === 'back').map((b) => b.id));
    if (kill.size) state.modules = state.modules.filter((m) => !kill.has(m.id));
  }
  const pose = attachToHost(host, type, { doorStyle, ...opts });
  if (!pose) return;
  const mat = defaultMaterial(type, doorStyle);
  if (type === 'back' && !opts.color) {
    mat.color = '#d4b896';
    mat.materialName = 'Ahşap arkalık';
  }
  if (opts.finish) mat.finish = opts.finish;
  if (opts.materialName) mat.materialName = opts.materialName;
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
    doorStyle: opts.glassShelf ? 'glass' : doorStyle,
    glassColor: opts.glassShelf || doorStyle === 'glass' ? (opts.color || '#c5d5e8') : undefined,
    organizer: opts.organizer,
    glassShelf: !!opts.glassShelf,
    frameColor: (opts.glassShelf || doorStyle === 'glass') ? '#c5c8cc' : undefined,
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
  // Parça eklenince seçim gövdede kalsın (ince raf/kapak özelleştirmeyi ele geçirmesin)
  state.selectedId = host.id;
  rebuildModules().then(() => {
    state.view = 'customize';
    renderSidebar();
    showHint(type === 'back'
      ? `Arkalık eklendi → ${typeTitle(host.type)}`
      : `${mod.label} → ${typeTitle(host.type)}`);
  });
}

/** BESTÅ: geniş iskelete 60 cm bölmeli ön kapaklar */
function bayCountForWidth(w) {
  return Math.max(1, Math.round(w / BAY_W));
}

function clearHostDoors(host) {
  const kill = new Set(childrenOf(host.id).filter((c) => c.type === 'door').map((d) => d.id));
  if (kill.size) state.modules = state.modules.filter((m) => !kill.has(m.id));
}

function pushDoorOnHost(host, style, extras = {}) {
  const mat = defaultMaterial('door', style);
  const pose = attachToHost(host, 'door', { doorStyle: style, ...extras });
  if (!pose) return;
  state.modules.push({
    id: uid(),
    type: 'door',
    label: style === 'drawer' ? 'Çekmece' : (DOOR_STYLES.find((d) => d.id === style)?.label || 'Kapak'),
    parentId: host.id,
    ...pose,
    finish: mat.finish,
    color: style === 'glass' ? '#c5d5e8' : mat.color,
    materialId: null,
    materialImage: null,
    materialName: mat.materialName,
    materialCode: '',
    doorStyle: style,
    bayIndex: pose.bayIndex,
    bays: pose.bays,
    drawerRow: pose.drawerRow,
    rows: pose.rows,
    yOffset: pose.yOffset,
    frameColor: style === 'glass' ? '#c5c8cc' : undefined,
    glassColor: style === 'glass' ? '#e7eef6' : undefined,
    glassKind: style === 'glass' ? 'clear' : undefined,
  });
}

/** IKEA Görünüm: açık / kapak / çekmece / karışık */
function applyHostLayout(host, layout) {
  if (!host || !canTakeFronts(host)) return;
  pushHistory();
  clearHostDoors(host);
  host.layout = layout;
  host.frontStyle = layout === 'open' ? 'open' : (layout === 'drawers' || layout === 'mix' ? 'drawer' : 'push');

  if (layout === 'open') {
    // kapak yok
  } else if (layout === 'doors') {
    const bays = host.doorBays || bayCountForWidth(host.w);
    host.doorBays = bays;
    for (let i = 0; i < bays; i++) pushDoorOnHost(host, 'push', { bayIndex: i, bays });
  } else if (layout === 'drawers') {
    const bays = host.doorBays || bayCountForWidth(host.w);
    host.doorBays = bays;
    const rows = host.h >= 50 ? 2 : 1;
    for (let i = 0; i < bays; i++) {
      for (let r = 0; r < rows; r++) {
        pushDoorOnHost(host, 'drawer', { bayIndex: i, bays, drawerRow: r, rows });
      }
    }
  } else if (layout === 'mix') {
    // Her blokta alt çekmece, üst açık (IKEA)
    const bays = host.doorBays || bayCountForWidth(host.w);
    host.doorBays = bays;
    for (let i = 0; i < bays; i++) {
      pushDoorOnHost(host, 'drawer', { bayIndex: i, bays, drawerRow: 0, rows: 1 });
    }
  }

  state.selectedId = host.id;
  rebuildModules().then(() => {
    state.view = 'customize';
    renderSidebar();
    const labels = { open: 'Açık raflar', doors: 'Kapaklar', drawers: 'Çekmeceler', mix: 'Çekmece + açık' };
    showHint(labels[layout] || 'Görünüm güncellendi');
  });
}

function toggleHostTop(host) {
  if (!host) return;
  const existing = childrenOf(host.id).filter((c) => c.type === 'top');
  if (existing.length) {
    pushHistory();
    const kill = new Set(existing.map((t) => t.id));
    state.modules = state.modules.filter((m) => !kill.has(m.id));
    rebuildModules().then(() => renderSidebar());
    showHint('Üst panel kaldırıldı');
    return;
  }
  addTopPanel(host, CATALOG.top[0] || {});
}

function applyFrontStyleColor(host, color, finish = 'matte', name = 'Kapak') {
  const doors = childrenOf(host.id).filter((c) => c.type === 'door');
  if (!doors.length) {
    showHint('Önce Görünüm’den kapak seçin');
    return;
  }
  pushHistory();
  doors.forEach((d) => {
    d.color = color;
    d.finish = finish;
    d.materialImage = null;
    d.materialId = null;
    d.materialName = name;
    d.materialCode = '';
  });
  rebuildModules().then(() => renderSidebar());
  showHint('Kapak stili uygulandı');
}

function addFrontsToHost(host, doorStyle, forcedBays = null) {
  if (!host || !canTakeFronts(host)) {
    showHint('Kapak yalnızca iskelet, alt blok veya raf ünitesine eklenir');
    return;
  }
  const style = doorStyle || 'push';
  if (style === 'open') {
    applyHostLayout(host, 'open');
    return;
  }
  if (style === 'drawer') {
    applyHostLayout(host, 'drawers');
    return;
  }
  pushHistory();
  clearHostDoors(host);
  host.frontStyle = style;
  host.layout = 'doors';
  const bays = forcedBays != null ? forcedBays : (host.doorBays || bayCountForWidth(host.w));
  host.doorBays = bays;
  for (let i = 0; i < bays; i++) pushDoorOnHost(host, style, { bayIndex: i, bays });
  state.selectedId = host.id;
  rebuildModules().then(() => {
    state.view = 'customize';
    renderSidebar();
    showHint('Kapaklar eklendi');
  });
}

function addInteriorShelf(host, opts = {}) {
  if (!host) return;
  const existing = childrenOf(host.id).filter((c) => c.type === 'shelf' && c.interior);
  const n = existing.length + 1;
  const shelfH = opts.organizer === 'tray' ? Math.max(3.5, opts.h || 4) : (opts.h || 1.8);
  const gap = (host.h - n * shelfH) / (n + 1);
  existing.forEach((s, i) => {
    s.yOffset = gap * (i + 1) + shelfH * i + shelfH / 2;
    s.h = s.h || shelfH;
  });
  if (existing.length) syncAttachedToHost(host);
  const yOffset = opts.yOffset != null ? opts.yOffset : gap * n + shelfH * (n - 1) + shelfH / 2;
  addAttachedPart('shelf', {
    host,
    label: opts.label || (opts.organizer === 'tray' ? 'Çekme tepsi' : 'İç raf'),
    yOffset,
    led: opts.led !== false && !opts.glassShelf,
    color: opts.color,
    finish: opts.finish,
    materialName: opts.materialName,
    glassShelf: opts.glassShelf,
    organizer: opts.organizer,
    h: shelfH,
    d: opts.d,
  });
}

/** Yan yana aynı hizadaki gövdeler (üst panel yayılımı) */
function contiguousHosts(seed) {
  if (!seed || !canTakeFronts(seed)) return seed ? [seed] : [];
  const pool = state.modules.filter((m) => canTakeFronts(m) && !m.parentId
    && Math.abs((m.y || 0) - (seed.y || 0)) < 3
    && Math.abs((m.z || 0) - (seed.z || 0)) < 8
    && Math.abs(m.h - seed.h) < 8);
  const sorted = [...pool].sort((a, b) => a.x - b.x);
  const run = [seed];
  let left = seed.x - seed.w / 2;
  let right = seed.x + seed.w / 2;
  let grew = true;
  while (grew) {
    grew = false;
    for (const m of sorted) {
      if (run.includes(m)) continue;
      const ml = m.x - m.w / 2;
      const mr = m.x + m.w / 2;
      if (Math.abs(ml - right) < 2) {
        run.push(m); right = mr; grew = true;
      } else if (Math.abs(mr - left) < 2) {
        run.unshift(m); left = ml; grew = true;
      }
    }
  }
  return run;
}

function addTopPanel(host, preset = {}, spanNeighbors = false) {
  if (!host || !canTakeFronts(host)) {
    showHint('Önce alt blok veya raf seçin');
    return;
  }
  const group = spanNeighbors ? contiguousHosts(host) : [host];
  pushHistory();
  // Eski üst panelleri kaldır
  group.forEach((h) => {
    const kill = new Set(childrenOf(h.id).filter((c) => c.type === 'top').map((t) => t.id));
    if (kill.size) state.modules = state.modules.filter((m) => !kill.has(m.id));
  });

  const minX = Math.min(...group.map((m) => m.x - m.w / 2));
  const maxX = Math.max(...group.map((m) => m.x + m.w / 2));
  const anchor = group[0];
  const w = (maxX - minX) + 2;
  const d = Math.max(...group.map((m) => m.d)) + 2;
  const y = Math.max(...group.map((m) => (m.y || 0) + m.h));
  const z = group.reduce((s, m) => s + m.z, 0) / group.length;
  const matColor = preset.color || '#d4b896';
  const finish = preset.finish || 'matte';

  state.modules.push({
    id: uid(),
    type: 'top',
    label: preset.label || 'Üst panel',
    parentId: anchor.id,
    w,
    h: preset.h || 2.5,
    d,
    x: (minX + maxX) / 2,
    y,
    z,
    finish,
    color: matColor,
    materialId: null,
    materialImage: null,
    materialName: preset.materialName || 'Üst kaplama',
    materialCode: '',
    spanHosts: group.map((g) => g.id),
  });
  state.selectedId = anchor.id;
  rebuildModules().then(() => {
    state.view = 'customize';
    renderSidebar();
    showHint(spanNeighbors && group.length > 1
      ? `Üst panel ${group.length} bloğa yayıldı`
      : 'Üst panel eklendi');
  });
}

function addInteriorOrganizer(host, preset = {}) {
  if (!host || !canTakeFronts(host)) {
    showHint('Önce bir gövde (alt blok / raf) seçin');
    return;
  }
  addInteriorShelf(host, {
    label: preset.label || 'İç raf',
    color: preset.color,
    finish: preset.finish,
    materialName: preset.materialName,
    glassShelf: preset.glassShelf,
    organizer: preset.organizer,
    h: preset.h,
    d: preset.d,
    led: !preset.glassShelf,
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
    // Yan kulelere arkalık (görünür renk + 18 mm)
    state.modules.filter((m) => m.type === 'shelf' && m.h >= 200).forEach((tower) => {
      const pose = attachToHost(tower, 'back', { thicknessMm: 18 });
      if (pose) {
        pushMod({
          type: 'back', label: 'Arkalık 18 mm', parentId: tower.id, ...pose,
          thicknessMm: 18, finish: 'matte', color: '#d4b896', materialName: 'Ahşap arkalık',
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
    // Yüzen banko + ortada çıtalı panel + yanlarda banko ÜSTÜNDE kule raflar
    const floatY = 15;
    const benchW = 240;
    const towerW = 40;
    const panelW = 140;
    const benchId = pushMod({
      type: 'plinth', label: 'Yüzen banko', w: benchW, h: 32, d: 38,
      x: 0, y: floatY, z: benchZ, floatY, baza: false, led: true,
      finish: 'matte', color: '#ebe6df', materialName: 'Grej',
    });
    pushMod({
      type: 'wallPanel', label: 'Düz panel', w: panelW, h: 200, d: 1.8, thicknessMm: 18,
      x: 0, y: floatY + 32, z: panelZ, led: true,
      finish: 'matte', color: '#f0ebe4', materialName: 'Panel',
    });
    pushMod({
      type: 'slat', label: 'Dikey çıtalar', w: panelW, h: 200, d: 2.5,
      x: 0, y: floatY + 32, z: panelZ + 2.2,
      slatWidth: 2.2, slatGap: 1.2,
      finish: 'matte', color: '#b8956a', materialName: 'Ahşap çıta',
    });
    // Kuleler bankonun uçlarında üstte — panel ortada TV arkası
    pushMod({
      type: 'shelf', label: 'Sol raf', w: towerW, h: 180, d: 30, led: true,
      x: -benchW / 2 + towerW / 2, y: floatY + 32, z: benchZ,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap',
    });
    pushMod({
      type: 'shelf', label: 'Sağ raf', w: towerW, h: 180, d: 30, led: true,
      x: benchW / 2 - towerW / 2, y: floatY + 32, z: benchZ,
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
    // Banko + yan kuleler yerde, bankonun DIŞINDA kenetlenir (iç içe değil)
    const benchW = 200;
    const towerW = 45;
    const benchId = pushMod({
      type: 'plinth', label: 'Alt blok', w: benchW, h: 40, d: 40,
      x: 0, y: 0, z: benchZ, baza: true, bazaH: 8,
      finish: 'matte', color: '#e8e4df', materialName: 'Krem',
    });
    pushMod({
      type: 'wallPanel', label: 'Orta taş panel', w: 120, h: 220, d: 1.8, thicknessMm: 18,
      x: 0, y: 40, z: panelZ, led: true,
      finish: 'ceramic', color: '#ebe6de', materialName: 'Traverten',
    });
    pushMod({
      type: 'shelf', label: 'Sol kule', w: towerW, h: 220, d: 35, led: true,
      x: -(benchW / 2 + towerW / 2), y: 0, z: benchZ,
      finish: 'matte', color: '#c4a574', materialName: 'Ahşap',
    });
    pushMod({
      type: 'shelf', label: 'Sağ kule', w: towerW, h: 220, d: 35, led: true,
      x: benchW / 2 + towerW / 2, y: 0, z: benchZ,
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
    state.modules.filter((m) => m.type === 'shelf' && m.h >= 200).forEach((tower) => {
      const pose = attachToHost(tower, 'back', { thicknessMm: 18 });
      if (pose) {
        pushMod({
          type: 'back', label: 'Arkalık 18 mm', parentId: tower.id, ...pose,
          thicknessMm: 18, finish: 'matte', color: '#d4b896', materialName: 'Ahşap arkalık',
        });
      }
    });
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
  resolveAllSolids();

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
  state.tv = { x: 0, y: 110, z: null, manual: false };
  localStorage.removeItem('tv-configurator-design');
  buildTv();
  rebuildModules();
  clearAllLabels();
  state.view = 'home';
  renderSidebar();
  $('#fab-tv')?.classList.remove('active');
  showHint('Oda temizlendi');
}

/** Yeni arka pano / çıta mevcut duvar parçalarının sağına — üst üste binmesin */
function nextWallSlot(w, h) {
  const peers = state.modules.filter((m) => !m.parentId && (m.type === 'wallPanel' || m.type === 'slat'));
  const bench = state.modules.find((m) => m.type === 'plinth' || (m.type === 'frame' && m.h <= 50));
  const y = bench ? hostSurfaceY(bench) : Math.max(0, Math.min(40, WALL_H - (h || 200)));
  const z = wallFlushZ(1.8);
  if (!peers.length) return { x: 0, y, z };
  let x = Math.max(...peers.map((m) => m.x + m.w / 2)) + w / 2 + 6;
  const maxX = WALL_W / 2 - w / 2;
  if (x > maxX) {
    const row = peers.filter((m) => Math.abs((m.y || 0) - y) < 30).length;
    x = -WALL_W / 2 + w / 2 + (row % 3) * 8;
  }
  return { x, y, z };
}
function nextFreeSlot(w, d) {
  const wallZ = wallFlushZ(d);
  const solids = state.modules.filter((m) => isSolidBody(m));
  if (!solids.length) {
    return { x: 0, y: 0, z: wallZ };
  }
  const right = Math.max(...solids.map((m) => m.x + m.w / 2));
  const neighbor = solids.find((m) => Math.abs(m.x + m.w / 2 - right) < 0.5) || solids[solids.length - 1];
  // Yeni parça da duvara yaslı hizada
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
    const isWall = preset.type === 'slat' || preset.type === 'wallPanel' || preset.type === 'back';
    pos = {
      x: Math.round((pt.x / CM) / SNAP) * SNAP,
      y: isWall
        ? Math.max(0, Math.round((pt.y / CM) / SNAP) * SNAP)
        : (preset.floatY != null ? preset.floatY : 0),
      z: isWall
        ? wallFlushZ(preset.d || 3)
        : Math.max(wallFlushZ(preset.d || 40), Math.round((pt.z / CM) / SNAP) * SNAP),
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

  // Arkalık / kapak / üst — seçili gövdeye yapışır
  if ((attachable || type === 'back') && host && !pos) {
    addAttachedPart(type, {
      host,
      doorStyle,
      label: p.label,
      d: p.d,
      h: p.h,
      thicknessMm: p.thicknessMm,
      slatWidth: 1.6,
      slatGap: 2.5,
    });
    return;
  }

  const mat = defaultMaterial(type, doorStyle);
  const wallPiece = type === 'wallPanel' || type === 'slat';
  const slot = pos || (wallPiece ? nextWallSlot(p.w, p.h) : nextFreeSlot(p.w, p.d));
  const role = placementRole({ type, parentId: null });
  const floatY = p.floatY != null ? p.floatY : (type === 'wallPanel' ? 40 : 0);
  let z = slot.z;
  let y = slot.y != null ? slot.y : floatY;

  // Rol bazlı varsayılan: zemin → duvar hizası; duvar paneli/çıta → duvar + yükseklik
  if (role === 'wall' || type === 'wallPanel' || type === 'slat') {
    z = wallFlushZ(p.d || 1.8);
    if (!pos) {
      // Banko varsa üstünden başlat; yoksa yerden ~40 cm
      const bench = state.modules.find((m) => m.type === 'plinth' || (m.type === 'frame' && m.h <= 50));
      y = bench ? hostSurfaceY(bench) : (type === 'slat' && p.h < 80 ? 0 : floatY || 40);
    }
  } else if (p.floatY != null) {
    y = p.floatY;
  } else if (type === 'plinth' && p.baza) {
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
    glbFile: p.glbFile || undefined,
    frameColor: doorStyle === 'glass' ? '#c5c8cc' : undefined,
    glassColor: doorStyle === 'glass' ? '#c5d5e8' : undefined,
    slatWidth: type === 'slat' ? 1.6 : undefined,
    slatGap: type === 'slat' ? 2.5 : undefined,
    shelfCount: type === 'shelf'
      ? (p.shelfCount != null ? p.shelfCount : (p.h > 40 ? Math.max(1, Math.floor(p.h / 22)) : 1))
      : undefined,
    doorBays: type === 'plinth' || (type === 'shelf' && p.h > 40)
      ? (p.bays || bayCountForWidth(p.w))
      : undefined,
    layout: type === 'shelf' && p.h > 40 ? 'open' : undefined,
  };
  state.modules.push(mod);
  applyPlacementRules(mod);
  // Raf kulesi: varsayılan açık + arkalık (LED kapalı, boşluklar eşit)
  if (type === 'shelf' && !mod.parentId && mod.h > 40) {
    const pose = attachToHost(mod, 'back', { thicknessMm: 18 });
    if (pose) {
      const bm = defaultMaterial('back');
      state.modules.push({
        id: uid(),
        type: 'back',
        label: 'Arkalık 18 mm',
        parentId: mod.id,
        ...pose,
        thicknessMm: 18,
        d: pose.d,
        finish: bm.finish,
        color: '#d4b896',
        materialId: null,
        materialImage: null,
        materialName: 'Ahşap arkalık',
        materialCode: '',
      });
    }
  }
  // Alt blok: kapakları blok sayısına göre ekle
  if (type === 'plinth' && mod.doorBays) {
    const style = 'push';
    const matDoor = defaultMaterial('door', style);
    for (let i = 0; i < mod.doorBays; i++) {
      const pose = attachToHost(mod, 'door', { doorStyle: style, bayIndex: i, bays: mod.doorBays });
      if (!pose) continue;
      state.modules.push({
        id: uid(),
        type: 'door',
        label: 'Bas-aç',
        parentId: mod.id,
        ...pose,
        finish: matDoor.finish,
        color: matDoor.color,
        materialId: null,
        materialImage: null,
        materialName: matDoor.materialName,
        materialCode: '',
        doorStyle: style,
        bayIndex: i,
        bays: mod.doorBays,
      });
    }
  }
  state.selectedId = mod.id;
  if (type === 'glb') showHint('Hazır model yükleniyor…');
  rebuildModules().then(() => {
    if (type === 'wallPanel') buildTv();
    state.view = 'customize';
    renderSidebar();
    showHint(mod.label + ' eklendi');
  });
}

function deleteSelected() {
  if (state.selectedId === '__tv__') {
    state.showTv = false;
    state.selectedId = null;
    buildTv();
    state.view = 'home';
    renderSidebar();
    $('#fab-tv')?.classList.remove('active');
    hideCtx();
    return;
  }
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
  if (state.selectedId === '__tv__') {
    showHint('TV çoğaltılamaz — inch / konumdan ayarlayın');
    return;
  }
  const mod = selected();
  if (!mod) {
    showHint('Önce bir ürün seçin');
    return;
  }

  // Bağlı parça seçiliyse tüm gövdeyi çoğalt
  const root = mod.parentId
    ? state.modules.find((m) => m.id === mod.parentId) || mod
    : mod;
  const kids = childrenOf(root.id);

  pushHistory();

  const idMap = new Map();
  const newRootId = uid();
  idMap.set(root.id, newRootId);

  const cloneOf = (src, newId, parentId) => {
    const c = {
      ...src,
      id: newId,
      parentId: parentId || undefined,
      materialImage: src.materialImage || null,
    };
    // parentId yoksa silinmiş sayılmasın
    if (!parentId) delete c.parentId;
    return c;
  };

  const copyRoot = cloneOf(root, newRootId, null);
  // Yanına koy — sağ doluysa sola
  const rightX = root.x + root.w / 2 + copyRoot.w / 2;
  const leftX = root.x - root.w / 2 - copyRoot.w / 2;
  const maxX = WALL_W / 2 - copyRoot.w / 2;
  const minX = -WALL_W / 2 + copyRoot.w / 2;
  if (rightX <= maxX + 0.05) {
    copyRoot.x = rightX;
  } else if (leftX >= minX - 0.05) {
    copyRoot.x = leftX;
  } else {
    copyRoot.x = Math.min(maxX, Math.max(minX, root.x));
    copyRoot.z = (root.z || 0) + (root.d || 40) + 4;
  }
  copyRoot.y = root.y || 0;
  if (copyRoot.z == null) copyRoot.z = root.z;
  // Aynı duvar hattı
  if (Math.abs((copyRoot.z || 0) - (root.z || 0)) < 1) copyRoot.z = root.z;

  const copies = [copyRoot];
  kids.forEach((child) => {
    const nid = uid();
    idMap.set(child.id, nid);
    const cc = cloneOf(child, nid, newRootId);
    // Çocuk pozisyonu sync ile host’a göre yenilenecek
    copies.push(cc);
  });

  state.modules.push(...copies);

  if (isSolidBody(copyRoot) || placementRole(copyRoot) === 'wall') {
    applyPlacementRules(copyRoot);
  }
  if (isHost(copyRoot)) syncAttachedToHost(copyRoot);

  state.selectedId = copyRoot.id;
  state.view = 'customize';
  rebuildModules().then(() => {
    renderSidebar();
    updateSelectionVisual();
    showHint(`${copyRoot.label || typeTitle(copyRoot.type)} çoğaltıldı`);
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

function pickModuleAt(clientX, clientY) {
  return pickModule({ clientX, clientY });
}

function pickModule(e) {
  ndcFromEvent(e);
  raycaster.setFromCamera(pointer, camera);
  const targets = [...modulesGroup.children];
  if (tvMesh) targets.push(tvMesh);
  const hits = raycaster.intersectObjects(targets, true);
  // Her kök için en yakın isabet
  const byId = new Map();
  for (const hit of hits) {
    let o = hit.object;
    while (o && !o.userData.moduleId && !o.userData.isTv) o = o.parent;
    if (!o) continue;
    const id = (o.userData.isTv || o.userData.moduleId === '__tv__')
      ? '__tv__'
      : selectionRootId(o.userData.moduleId);
    if (!id || byId.has(id)) continue;
    byId.set(id, hit.distance);
  }
  if (!byId.size) return null;

  const entries = [...byId.entries()].map(([id, dist]) => ({ id, dist }));
  const closest = Math.min(...entries.map((e) => e.dist));
  // Sadece neredeyse aynı mesafedekiler arasından seç (5 cm)
  const EPS = 0.05;
  const contenders = entries.filter((e) => e.dist <= closest + EPS);
  contenders.sort((a, b) => {
    let da = a.dist;
    let db = b.dist;
    const ma = a.id !== '__tv__' ? state.modules.find((m) => m.id === a.id) : null;
    const mb = b.id !== '__tv__' ? state.modules.find((m) => m.id === b.id) : null;
    // Dar / üstteki parçayı hafifçe tercih et (eşit mesafede)
    if (ma) da += (ma.w * ma.d) * 0.00002 - (ma.y || 0) * 0.0001;
    if (mb) db += (mb.w * mb.d) * 0.00002 - (mb.y || 0) * 0.0001;
    // Seçiliye çok hafif bonus — ikinci tıkta Taşı için (başka ürüne tıklamayı engellemez)
    if (a.id === state.selectedId) da -= 0.008;
    if (b.id === state.selectedId) db -= 0.008;
    return da - db;
  });
  return contenders[0].id;
}

/** Kapak, üst, iç raf, arkalık tıklanınca gövde seçilir — ölçü 1.8 cm ince parçaya kaymasın. */
function selectionRootId(id) {
  if (!id || id === '__tv__') return id;
  const m = state.modules.find((x) => x.id === id);
  if (!m?.parentId) return id;
  return m.parentId;
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

function updateHoverTag(e, id) {
  const tag = $('#hover-tag');
  if (!tag) return;
  if (!id || drag || pendingDrag) {
    tag.classList.remove('show', 'move');
    return;
  }
  const canMove = state.selectedId === id
    || (() => {
      const sel = state.modules.find((m) => m.id === state.selectedId);
      const hit = state.modules.find((m) => m.id === id);
      if (!sel || !hit) return false;
      if (sel.parentId === id) return true; // seçili kapak/arkalık → gövde Taşı
      if (hit.parentId === state.selectedId) return true;
      return false;
    })();
  tag.classList.add('show');
  tag.classList.toggle('move', canMove);
  tag.innerHTML = canMove
    ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20"/></svg> Taşı`
    : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg> Seç`;
  if (e) {
    tag.style.left = `${e.clientX}px`;
    tag.style.top = `${e.clientY}px`;
  }
  renderer.domElement.style.cursor = canMove ? 'grab' : 'pointer';
}

function onPointerDown(e) {
  if (e.button === 2) return;
  if (e.button !== 0) return;
  hideCtx();
  const id = pickModule(e);
  pendingDrag = null;
  updateHoverTag(null, null);

  if (id === '__tv__') {
    const already = state.selectedId === '__tv__';
    state.selectedId = '__tv__';
    state.view = 'tv';
    updateSelectionVisual();
    renderSidebar();
    // Sadece seçiliyken taşı
    if (already) {
      controls.enabled = false;
      pendingDrag = { kind: 'tv', x: e.clientX, y: e.clientY };
    }
    return;
  }
  if (id) {
    const raw = state.modules.find((m) => m.id === id);
    // Bağlı kapak/arkalık sürüklenince gövde taşınır
    const moveId = (raw?.parentId && (raw.type === 'door' || raw.type === 'back'))
      ? raw.parentId
      : id;
    const already = state.selectedId === id || state.selectedId === moveId;
    state.selectedId = id;
    state.view = 'customize';
    updateSelectionVisual();
    if (!already) renderSidebar();
    if (!already && state.showMeasure) rebuildModules();
    // İlk basışta tut — ikinci tık beklemeden sürükle
    controls.enabled = false;
    const target = state.modules.find((m) => m.id === moveId);
    pendingDrag = {
      kind: 'mod',
      id: moveId,
      x: e.clientX,
      y: e.clientY,
      snapshot: JSON.stringify(state.modules),
    };
    if (target && placementRole(target) === 'wall') {
      const wallZ = wallFlushZ(target.d) * CM;
      const plane = new THREE.Plane(new THREE.Vector3(0, 0, 1), -wallZ);
      ndcFromEvent(e);
      raycaster.setFromCamera(pointer, camera);
      const hit = new THREE.Vector3();
      if (raycaster.ray.intersectPlane(plane, hit)) {
        pendingDrag.ox = hit.x / CM - target.x;
        pendingDrag.oy = hit.y / CM - target.y;
        pendingDrag.wallZ = wallZ;
      }
    } else if (target && placementRole(target) !== 'wall') {
      const planeY = ((target.y || 0) + Math.min(target.h * 0.35, 18)) * CM;
      const plane = new THREE.Plane(new THREE.Vector3(0, 1, 0), -planeY);
      ndcFromEvent(e);
      raycaster.setFromCamera(pointer, camera);
      const hit = new THREE.Vector3();
      if (raycaster.ray.intersectPlane(plane, hit)) {
        pendingDrag.ox = hit.x / CM - target.x;
        pendingDrag.oz = hit.z / CM - target.z;
        pendingDrag.planeY = planeY;
      }
    }
  } else {
    // Boş alan: orbit serbest, seçimi kaldır
    state.selectedId = null;
    updateSelectionVisual();
    if (state.view === 'customize' || state.view === 'materials') {
      state.view = 'home';
      renderSidebar();
    }
  }
}

let lastHoverPick = 0;
function onPointerMove(e) {
  if (!drag && !pendingDrag) {
    const now = performance.now();
    if (now - lastHoverPick < 50) return;
    lastHoverPick = now;
    const id = pickModule(e);
    if (id !== state.hoveredId) {
      state.hoveredId = id;
      updateSelectionVisual();
    }
    updateHoverTag(e, id);
    return;
  }

  updateHoverTag(null, null);

  // Eşik: 10px — seçili parçayı sürükleyince taşınır
  if (pendingDrag && !drag) {
    const dx = e.clientX - pendingDrag.x;
    const dy = e.clientY - pendingDrag.y;
    if (Math.hypot(dx, dy) < 10) return;
    drag = { ...pendingDrag, moved: false };
    pendingDrag = null;
    renderer.domElement.style.cursor = 'grabbing';
  }

  if (!drag) return;

  ndcFromEvent(e);
  raycaster.setFromCamera(pointer, camera);

  if (drag.kind === 'tv') {
    const wallZ = (state.tv.z != null ? state.tv.z : -(FLOOR_D / 2) + 8) * CM;
    const wallPlane = new THREE.Plane(new THREE.Vector3(0, 0, 1), -wallZ);
    const hit = new THREE.Vector3();
    if (!raycaster.ray.intersectPlane(wallPlane, hit)) return;
    state.tv.x = Math.round((hit.x / CM) / SNAP) * SNAP;
    state.tv.y = Math.max(30, Math.min(WALL_H - 20, Math.round((hit.y / CM) / SNAP) * SNAP));
    state.tv.manual = true;
    if (tvMesh) {
      tvMesh.position.x = state.tv.x * CM;
      tvMesh.position.y = state.tv.y * CM;
      const helper = tvMesh.children.find((c) => c.name === 'selection');
      if (helper) helper.update();
    }
    drag.moved = true;
    return;
  }

  const mod = state.modules.find((m) => m.id === drag.id);
  if (!mod) return;

  // Duvar paneli / çıta / serbest arkalık: duvar düzleminde X+Y
  if (placementRole(mod) === 'wall') {
    const wallZ = drag.wallZ != null ? drag.wallZ : wallFlushZ(mod.d) * CM;
    const wallPlane = new THREE.Plane(new THREE.Vector3(0, 0, 1), -wallZ);
    const hit = new THREE.Vector3();
    if (!raycaster.ray.intersectPlane(wallPlane, hit)) return;
    const ox = drag.ox || 0;
    const oy = drag.oy || 0;
    mod.x = Math.max(-WALL_W / 2 + mod.w / 2, Math.min(WALL_W / 2 - mod.w / 2, hit.x / CM - ox));
    mod.y = Math.max(0, Math.min(WALL_H - mod.h, hit.y / CM - oy));
    applyWallPlacement(mod, { dragging: true });
    drag.moved = true;
    const g = modulesGroup.children.find((c) => c.userData.moduleId === mod.id);
    if (g) placeModule(g, mod);
    return;
  }

  let nx;
  let nz;
  if (drag.planeY != null && drag.ox != null) {
    const plane = new THREE.Plane(new THREE.Vector3(0, 1, 0), -drag.planeY);
    const hit = new THREE.Vector3();
    if (!raycaster.ray.intersectPlane(plane, hit)) return;
    nx = hit.x / CM - drag.ox;
    nz = hit.z / CM - drag.oz;
  } else {
    const floor = roomGroup.children.find((c) => c.name === 'floor');
    const hits = raycaster.intersectObject(floor);
    if (!hits.length) return;
    const p = hits[0].point;
    nx = p.x / CM;
    nz = p.z / CM;
  }
  const minZ = wallFlushZ(mod.d);
  const maxZ = FLOOR_D / 2 - mod.d / 2 - 20;
  mod.x = Math.max(-WALL_W / 2 + mod.w / 2, Math.min(WALL_W / 2 - mod.w / 2, nx));
  mod.z = Math.max(minZ, Math.min(maxZ, nz));

  if (isHost(mod)) syncAttachedToHost(mod);

  const attachable = ATTACH_FRONT.has(mod.type) || ATTACH_BACK.has(mod.type) || ATTACH_TOP.has(mod.type);
  if (!isSolidBody(mod) && attachable && placementRole(mod) !== 'wall') {
    const host = findNearestHost(mod.x, mod.z, 40);
    if (host && (canTakeFronts(host) || (mod.type === 'back' && isHost(host)))) {
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
        onBack: mod.onBack,
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
        if (pose.onBack != null) mod.onBack = pose.onBack;
      }
    }
  }

  drag.moved = true;
  const placeLive = (m) => {
    const g = modulesGroup.children.find((c) => c.userData.moduleId === m.id);
    if (g) placeModule(g, m);
  };
  placeLive(mod);
  if (isHost(mod)) childrenOf(mod.id).forEach(placeLive);
  if (isSolidBody(mod)) {
    state.modules
      .filter((m) => m.type === 'top' && m.spanHosts?.includes(mod.id))
      .forEach(placeLive);
  }
}

function onPointerUp() {
  if (drag?.kind === 'tv' && drag.moved) {
    renderSidebar();
    showHint('TV konumu güncellendi');
  } else if (drag?.moved && drag.snapshot) {
    state.history.push(drag.snapshot);
    if (state.history.length > 40) state.history.shift();
    state.future = [];
    const mod = state.modules.find((m) => m.id === drag.id);
    if (mod && !mod.parentId) settleDropped(mod);
    const placeLive = (m) => {
      const g = modulesGroup.children.find((c) => c.userData.moduleId === m.id);
      if (g) placeModule(g, m);
    };
    if (mod) {
      placeLive(mod);
      if (isHost(mod)) childrenOf(mod.id).forEach(placeLive);
    }
    if (state.showTv && !state.tv.manual) buildTv();
    scheduleAutoSave();
  }
  drag = null;
  pendingDrag = null;
  controls.enabled = true;
  updateHoverTag(null, null);
  renderer.domElement.style.cursor = 'default';
}

function menuItems() {
  return [
    { kind: 'plinth', label: 'Alt Blok', icon: '▄', desc: 'Zemin · arka duvara yaslanır' },
    { kind: 'shelf', label: 'Raf sistemi', icon: '☰', desc: 'Zemin veya banko üstü · duvar hizası' },
    { kind: 'slat', label: 'Çıtalama', icon: '▥', desc: 'Arka duvara dikey çıta' },
    { kind: 'wallPanel', label: 'Arka Pano', icon: '▮', desc: 'Karşı duvara TV paneli' },
    { kind: 'glb', label: 'Hazır model', icon: '◈', desc: 'Fotoğraflı hazır TV ünitesi · taşınır' },
    { kind: 'tv', label: 'TV', icon: '▣', desc: 'Aç / kapat · inch · konum' },
  ];
}

function typeTitle(type) {
  return ({
    frame: 'İskelet', door: 'Kapak', shelf: 'Raf sistemi', back: 'Arkalık',
    slat: 'Çıtalama', top: 'Üst panel', leg: 'Ayak', plinth: 'Alt Blok',
    wallPanel: 'Arka Pano', interior: 'İç düzenleyici', glb: 'Hazır model',
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
  if (kind === 'top') {
    return 'background:linear-gradient(90deg,#d4b896 0%,#e8dcc8 50%,#c4a574 100%)';
  }
  if (kind === 'interior' || (kind === 'shelf' && preset.interior)) {
    return `background:${preset.color || '#f7f7f7'};border:8px solid #e5e7eb;box-sizing:border-box`;
  }
  if (kind === 'plinth') {
    return 'background:#1a1a1a';
  }
  return 'background:linear-gradient(135deg,#fff,#e5e7eb);border:8px solid #f3f4f6;box-sizing:border-box';
}

/** Canvas örnek görselleri — bir kez üretilir */
const thumbCache = new Map();
function presetThumbDataUrl(kind, preset) {
  const key = `${kind}:${preset.id || ''}:${preset.w}:${preset.h}:${preset.d}`;
  const hit = thumbCache.get(key);
  if (hit) return hit;
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
    ctx.fillStyle = '#c5c8cc';
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
  const url = c.toDataURL('image/png');
  thumbCache.set(key, url);
  return url;
}

function renderSidebar() {
  btnBack.style.display = state.view === 'home' ? 'none' : 'inline-flex';

  if (state.view === 'home') {
    sideTitle.textContent = 'Kendi TV ünitenizi oluşturun';
    const hasSaved = !!localStorage.getItem('tv-configurator-design');
    sideBody.innerHTML = `
      <div class="panel-section" style="display:flex;gap:8px;padding-top:0">
        <button type="button" class="chip danger-chip" id="btn-clear-all">Temizle</button>
        ${hasSaved ? `<button type="button" class="chip" id="btn-load-saved">Kayıtlı tasarım</button>` : ''}
      </div>
      <p class="section-title" style="padding:4px 2px 8px">Parça ekle</p>
      <div class="add-grid">
        ${menuItems().map((item) => `
          <button type="button" class="add-tile" data-kind="${item.kind}">
            <strong>${item.label}</strong>
            <span>${item.desc}</span>
          </button>
        `).join('')}
      </div>
      <p class="section-title" style="padding:16px 2px 8px">Hazır duvar</p>
      <div class="preset-list">
        ${WALL_COMPOSITIONS.map((p) => `
          <button type="button" class="preset-row" data-wall="${p.id}">
            <strong>${p.label}</strong>
            <span>${p.desc}</span>
          </button>
        `).join('')}
      </div>
    `;
    sideBody.querySelectorAll('[data-wall]').forEach((btn) => {
      btn.addEventListener('click', () => startWallComposition(btn.dataset.wall));
    });
    sideBody.querySelectorAll('[data-kind]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const kind = btn.dataset.kind;
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
    const tx = Math.round(state.tv.x || 0);
    const ty = Math.round(state.tv.y || 110);
    sideBody.innerHTML = `
      <div class="panel-section">
        <div class="section-title">Göster</div>
        <div class="chip-row">
          <button type="button" class="chip ${state.showTv ? 'active' : ''}" data-tv="1">Açık</button>
          <button type="button" class="chip ${!state.showTv ? 'active' : ''}" data-tv="0">Kapalı</button>
        </div>
        <div class="section-title">Boyut (inch)</div>
        <div class="chip-row">
          ${TV_INCHES.map((n) => `<button type="button" class="chip ${state.tvInch === n ? 'active' : ''}" data-inch="${n}">${n}"</button>`).join('')}
        </div>
        <div class="section-title">Konum (cm)</div>
        <div class="dim-row">
          <div class="field"><label>Yatay X</label><input type="number" id="tv-x" step="1" value="${tx}"></div>
          <div class="field"><label>Yükseklik Y</label><input type="number" id="tv-y" min="30" max="280" step="1" value="${ty}"></div>
        </div>
        <div class="chip-row" style="margin-top:8px">
          <button type="button" class="chip" id="btn-tv-align">Ortaya / panele hizala</button>
        </div>
        <p class="hint-inline">TV’yi sahnede sürükleyerek de taşıyabilirsiniz.</p>
      </div>
    `;
    sideBody.querySelectorAll('[data-tv]').forEach((b) => {
      b.addEventListener('click', () => {
        state.showTv = b.dataset.tv === '1';
        if (state.showTv) state.selectedId = '__tv__';
        buildTv();
        $('#fab-tv')?.classList.toggle('active', state.showTv);
        renderSidebar();
      });
    });
    sideBody.querySelectorAll('[data-inch]').forEach((b) => {
      b.addEventListener('click', () => {
        state.tvInch = Number(b.dataset.inch);
        if (!state.showTv) state.showTv = true;
        state.selectedId = '__tv__';
        buildTv();
        $('#fab-tv')?.classList.add('active');
        renderSidebar();
        showHint(`TV ${state.tvInch}"`);
      });
    });
    const applyTvPos = () => {
      state.tv.x = clampNum($('#tv-x').value, -WALL_W / 2, WALL_W / 2);
      state.tv.y = clampNum($('#tv-y').value, 30, WALL_H - 10);
      state.tv.manual = true;
      state.showTv = true;
      buildTv();
      updateSelectionVisual();
    };
    $('#tv-x')?.addEventListener('change', applyTvPos);
    $('#tv-y')?.addEventListener('change', applyTvPos);
    $('#btn-tv-align')?.addEventListener('click', () => {
      alignTvToWall();
      state.selectedId = '__tv__';
      updateSelectionVisual();
      renderSidebar();
      showHint('TV hizalandı');
    });
    return;
  }

  if (state.view === 'catalog') {
    const kind = state.catalogKind;
    const items = CATALOG[kind] || [];
    const hostSel = selected() && canTakeFronts(selected()) ? selected() : null;
    const needsHost = kind === 'top' || kind === 'interior';
    sideTitle.textContent = typeTitle(kind);
    sideBody.innerHTML = `
      <div class="panel-section">
        ${needsHost ? `
          <div class="info-box" style="margin-bottom:12px">
            ${hostSel
              ? `Seçili: <strong>${hostSel.label || typeTitle(hostSel.type)}</strong> — tıklayınca buna eklenir.`
              : 'Önce sahneden bir <strong>alt blok</strong> veya <strong>raf</strong> seçin.'}
          </div>` : `
          <div class="section-title">Sürükle veya tıkla</div>`}
        <div class="product-grid">
          ${items.map((p) => `
            <button type="button" class="product-card" ${needsHost ? '' : 'draggable="true"'}
              data-type="${kind}" data-preset="${encodeURIComponent(JSON.stringify(p))}">
              <div class="thumb"><img src="${presetThumbDataUrl(kind === 'interior' ? 'shelf' : kind, p)}" alt="${p.label}"></div>
              <div class="meta">
                <strong>${p.label}</strong>
                <span>${kind === 'interior' ? (p.materialName || '') : `${p.w}×${p.d}×${p.h} cm`}</span>
              </div>
            </button>
          `).join('')}
        </div>
      </div>
      ${kind === 'top' && hostSel ? `
        <div class="panel-section">
          <button type="button" class="btn full-btn" id="btn-top-span">Yan yana bloğa yay</button>
          <p class="hint-inline">Komşu aynı yükseklikteki blokların üstünü tek panel yapar.</p>
        </div>` : ''}
    `;
    sideBody.querySelectorAll('.product-card').forEach((btn) => {
      const readPreset = () => JSON.parse(decodeURIComponent(btn.dataset.preset));
      if (!needsHost) {
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
      }
      btn.addEventListener('click', () => {
        const preset = readPreset();
        if (kind === 'top') {
          if (!hostSel) { showHint('Önce alt blok veya raf seçin'); return; }
          addTopPanel(hostSel, preset, false);
          return;
        }
        if (kind === 'interior') {
          if (!hostSel) { showHint('Önce gövde seçin'); return; }
          addInteriorOrganizer(hostSel, preset);
          return;
        }
        addModule(btn.dataset.type, preset);
      });
    });
    $('#btn-top-span')?.addEventListener('click', () => {
      if (!hostSel) return;
      addTopPanel(hostSel, CATALOG.top[0] || {}, true);
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
    sideTitle.textContent = `${typeTitle(mod.type)} · ${Math.round(mod.w)}×${Math.round(mod.d)}×${Math.round(mod.h)}`;
    const fmt = (n) => (Math.round(Number(n) * 10) / 10);

    const doorUI = mod.type === 'door' ? `
      <div class="section-title">Ön tip</div>
      <div class="chip-row">
        ${DOOR_STYLES.map((d) => `<button type="button" class="chip ${mod.doorStyle === d.id ? 'active' : ''}" data-door="${d.id}">${d.label}</button>`).join('')}
      </div>
      ${mod.doorStyle === 'drawer' || mod.doorStyle === 'hinge' || (mod.doorStyle === 'push' && mod.handleStyle && mod.handleStyle !== 'none') || mod.doorStyle === 'push' ? `
      <div class="section-title">Kulp</div>
      <div class="chip-row">
        ${HANDLE_STYLES.map((h) => `<button type="button" class="chip ${(mod.handleStyle || (mod.doorStyle === 'push' ? 'none' : 'bar')) === h.id ? 'active' : ''}" data-handle="${h.id}">${h.label}</button>`).join('')}
      </div>
      <div class="section-title">Kulp yeri</div>
      <div class="chip-row">
        ${HANDLE_POS.map((h) => `<button type="button" class="chip ${(mod.handlePos || 'center') === h.id ? 'active' : ''}" data-handle-pos="${h.id}">${h.label}</button>`).join('')}
      </div>` : ''}
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
      <div class="chip-row" style="margin-top:8px">
        <button type="button" class="chip" id="btn-to-wall">Duvara yasla</button>
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

    const shelfCountDef = mod.type === 'shelf' && !mod.parentId && mod.h > 4
      ? (mod.shelfCount != null ? mod.shelfCount : Math.max(1, Math.floor(mod.h / 22)))
      : null;
    const doors = childrenOf(mod.id).filter((c) => c.type === 'door');
    const hasTop = childrenOf(mod.id).some((c) => c.type === 'top');
    const backChild = childrenOf(mod.id).find((c) => c.type === 'back');
    const hasHostBack = !!backChild;
    const glassDoors = doors.filter((d) => d.doorStyle === 'glass');
    const hasGlassFront = glassDoors.length > 0;
    const frameCur = glassDoors[0]?.frameColor || '#c5c8cc';
    const glassCur = glassDoors[0]?.glassColor || '#c5d5e8';
    const naturalBays = bayCountForWidth(mod.w);
    const bayN = mod.doorBays || naturalBays;
    const layoutCur = mod.layout
      || (doors.length === 0 || mod.frontStyle === 'open' ? 'open'
        : doors.some((d) => d.doorStyle === 'drawer') && doors.every((d) => d.doorStyle === 'drawer') ? 'drawers'
        : doors.some((d) => d.doorStyle === 'drawer') ? 'mix'
        : 'doors');
    const hostAddUI = canTakeFronts(mod) ? `
      ${shelfCountDef != null ? `
      <div class="section-title">Raf sayısı</div>
      <div class="dim-row">
        <div class="field"><label>Adet</label><input type="number" id="shelf-count" min="1" max="20" step="1" value="${shelfCountDef}"></div>
      </div>
      <p class="hint-inline">Boşluklar eşit (standart) · ~${Math.max(4, Math.round((mod.h - shelfCountDef * 1.5) / (shelfCountDef + 1)))} cm ara</p>` : ''}
      ${mod.type === 'shelf' && !mod.parentId && mod.h > 40 ? `
      <p class="hint-inline" style="margin-bottom:10px">Varsayılan: açık raf · arkalıklı · LED kapalı. Görünüm’den kapak / cam seçebilirsiniz.</p>` : ''}
      <div class="section-title">Görünüm</div>
      <div class="layout-grid">
        <button type="button" class="layout-btn ${layoutCur === 'open' ? 'active' : ''}" data-layout="open" title="Açık raflar">
          <span class="layout-ico" aria-hidden="true">
            <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2"/><path d="M6 18h36M6 30h36" stroke="currentColor" stroke-width="2"/></svg>
          </span>
          <span>Açık raflar</span>
        </button>
        <button type="button" class="layout-btn ${layoutCur === 'doors' ? 'active' : ''}" data-layout="doors" title="Kapaklar">
          <span class="layout-ico" aria-hidden="true">
            <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2"/><path d="M24 6v36" stroke="currentColor" stroke-width="2"/><circle cx="20" cy="24" r="1.5" fill="currentColor"/><circle cx="28" cy="24" r="1.5" fill="currentColor"/></svg>
          </span>
          <span>Kapaklar</span>
        </button>
        <button type="button" class="layout-btn ${layoutCur === 'drawers' ? 'active' : ''}" data-layout="drawers" title="Çekmeceler — her blok ayrı">
          <span class="layout-ico" aria-hidden="true">
            <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2"/><path d="M6 18h36M6 30h36M18 12h12M18 24h12" stroke="currentColor" stroke-width="2"/></svg>
          </span>
          <span>Çekmeceler</span>
        </button>
        <button type="button" class="layout-btn ${layoutCur === 'mix' ? 'active' : ''}" data-layout="mix" title="Çekmece ve açık raf">
          <span class="layout-ico" aria-hidden="true">
            <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2"/><path d="M6 28h36M18 34h12M6 14h36" stroke="currentColor" stroke-width="2"/></svg>
          </span>
          <span>Karışık</span>
        </button>
      </div>
      <p class="hint-inline">${bayN} bölme — açık raf, kapak, cam ve çekmece bu sayıya bölünür</p>
      ${layoutCur === 'doors' ? `
      <div class="chip-row" style="margin-top:8px">
        <button type="button" class="chip ${!hasGlassFront && bayN === 1 ? 'active' : ''}" data-front-bays="1">1 kapak</button>
        <button type="button" class="chip ${!hasGlassFront && bayN === 2 ? 'active' : ''}" data-front-bays="2">2 kapak</button>
        ${naturalBays > 2 ? `<button type="button" class="chip ${!hasGlassFront && bayN === naturalBays ? 'active' : ''}" data-front-bays="${naturalBays}">${naturalBays} kapak</button>` : ''}
        <button type="button" class="chip ${hasGlassFront ? 'active' : ''}" data-front="glass" data-front-bays="${naturalBays}">Cam kapak · ${naturalBays}</button>
      </div>` : ''}
      ${hasGlassFront ? `
      <div class="section-title">Çerçeve ve profil</div>
      <div class="color-picks">
        ${ALUMINUM_COLORS.map((c) => `
          <button type="button" class="color-pick ${frameCur === c.color ? 'active' : ''}" data-host-frame="${c.color}">
            <span class="dot" style="background:${c.color}"></span>${c.label}
          </button>
        `).join('')}
      </div>
      <div class="section-title">Cam rengi</div>
      <div class="color-picks">
        ${GLASS_COLORS.map((c) => `
          <button type="button" class="color-pick ${glassCur === c.color || glassDoors[0]?.glassKind === c.id ? 'active' : ''}" data-host-glass="${c.color}" data-glass-kind="${c.id}">
            <span class="dot" style="background:${c.color}"></span>${c.label}
          </button>
        `).join('')}
      </div>` : (layoutCur === 'doors' || layoutCur === 'drawers' || layoutCur === 'mix') ? `
      <div class="section-title">Kapak / çekmece rengi</div>
      <div class="color-picks">
        ${[
          { c: '#f7f7f7', f: 'matte', n: 'Beyaz' },
          { c: '#111111', f: 'highgloss', n: 'Siyah' },
          { c: '#e8e4df', f: 'matte', n: 'Krem' },
          { c: '#c4a574', f: 'matte', n: 'Meşe' },
          { c: '#6b7280', f: 'matte', n: 'Gri' },
        ].map((s) => `
          <button type="button" class="color-pick" data-front-color="${s.c}" data-front-finish="${s.f}" data-front-name="${s.n}">
            <span class="dot" style="background:${s.c}"></span>${s.n}
          </button>
        `).join('')}
        <button type="button" class="chip" id="btn-front-mat">Katalog</button>
      </div>` : ''}
      <div class="section-title">Üst panel</div>
      <div class="color-picks">
        <button type="button" class="color-pick ${!hasTop ? 'active' : ''}" data-top="0"><span class="dot" style="background:#f3f4f6"></span>Yok</button>
        ${(CATALOG.top || []).map((t) => `
          <button type="button" class="color-pick" data-top-preset="${encodeURIComponent(JSON.stringify(t))}">
            <span class="dot" style="background:${t.color}"></span>${(t.materialName || t.label).replace('Üst panel · ', '')}
          </button>
        `).join('')}
        <button type="button" class="chip" data-top-span="1" title="Yan yana bloklara yay">Yay</button>
      </div>
      <div class="section-title">İç düzenleyiciler</div>
      <div class="chip-row">
        ${(CATALOG.interior || []).slice(0, 6).map((p) => `
          <button type="button" class="mat-swatch" data-interior="${encodeURIComponent(JSON.stringify(p))}" title="${p.label}" style="background:${p.color}"></button>
        `).join('')}
        <button type="button" class="chip" data-add-part="shelf">+ Raf</button>
      </div>
      <div class="section-title">Arkalık</div>
      <p class="hint-inline">Gövdenin arkasına plaka ekler — kablo gizleme ve sağlamlık için</p>
      <button type="button" class="feature-btn ${hasHostBack ? 'active' : ''}" id="btn-toggle-back">
        <span class="feature-ico" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="28" height="28"><rect x="8" y="6" width="6" height="36" fill="currentColor" opacity=".35"/><rect x="18" y="6" width="22" height="36" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M22 14h14M22 24h14M22 34h10" stroke="currentColor" stroke-width="2"/></svg>
        </span>
        <span class="feature-text">
          <strong>${hasHostBack ? 'Arkalık var' : 'Arkalık ekle'}</strong>
          <small>${hasHostBack ? `${backChild.thicknessMm || Math.round((backChild.d || 1.8) * 10)} mm · kaldırmak için tekrar tıkla` : '18 mm plaka ekle'}</small>
        </span>
      </button>
      ${hasHostBack ? `
      <div class="chip-row" style="margin-top:8px">
        ${BACK_THICKNESS_MM.map((mm) => `
          <button type="button" class="chip ${(backChild.thicknessMm || Math.round((backChild.d || 1.8) * 10)) === mm ? 'active' : ''}" data-host-back-thick="${mm}">${mm} mm</button>
        `).join('')}
      </div>` : ''}
      <div class="chip-row" style="margin-top:10px">
        ${mod.type === 'shelf' ? '<button type="button" class="chip" data-add-part="slat">Çıta arkalık</button>' : ''}
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
      <p class="hint-inline">Rengi Kaplama / Renk bölümünden seçin.</p>
    ` : '';

    const slatUI = mod.type === 'slat' ? `
      <div class="section-title">Çıta</div>
      <div class="dim-row">
        <div class="field"><label>Eni (cm)</label><input type="number" id="slat-w" min="0.8" max="6" step="0.1" value="${mod.slatWidth || 1.6}"></div>
        <div class="field"><label>Boşluk (cm)</label><input type="number" id="slat-gap" min="0.5" max="12" step="0.1" value="${mod.slatGap != null ? mod.slatGap : 2.5}"></div>
      </div>
      <div class="section-title">Arkalık</div>
      <div class="chip-row">
        <button type="button" class="chip ${!mod.hasBack ? 'active' : ''}" data-slat-back="0">Yok</button>
        <button type="button" class="chip ${mod.hasBack ? 'active' : ''}" data-slat-back="1">Var</button>
      </div>
      ${mod.hasBack ? `
      <div class="dim-row" style="margin-top:8px">
        <div class="field"><label>Arkalık (cm)</label><input type="number" id="slat-back-d" min="0.4" max="3" step="0.1" value="${mod.backThicknessCm || 0.8}"></div>
      </div>
      <div class="section-title">Arkalık rengi</div>
      <div class="chip-row">
        ${['#f7f7f7','#e8e4df','#d4b896','#c4a574','#6b7280','#1a1a1a','#f2ebe3'].map((c) => `
          <button type="button" class="mat-swatch ${(mod.backColor || '#d4b896') === c ? 'active' : ''}" data-slat-back-color="${c}" style="background:${c}"></button>
        `).join('')}
      </div>` : ''}
      <div class="chip-row" style="margin-top:8px"><button type="button" class="chip" id="btn-to-wall">Duvara yasla</button></div>
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

    const radiusUI = ['plinth', 'door', 'frame', 'wallPanel'].includes(mod.type) ? `
      <div class="section-title">Ovallik</div>
      <div class="chip-row">
        ${[0, 0.5, 1, 1.5, 2].map((r) => `
          <button type="button" class="chip ${(mod.radius || 0) === r ? 'active' : ''}" data-radius="${r}">${r === 0 ? 'Keskin' : r + ' cm'}</button>
        `).join('')}
      </div>
    ` : '';

    const hasGear = !!(wallPanelUI || plinthUI || hostAddUI || doorUI || glassUI || backUI || slatUI || shelfLedUI || radiusUI);
    const openCoat = ['back', 'wallPanel', 'door', 'slat'].includes(mod.type);

    sideBody.innerHTML = `
      <div class="side-tabs" role="tablist">
        <button type="button" class="side-tab active" data-pane="front">Düzen</button>
        <button type="button" class="side-tab" data-pane="size">Ölçü</button>
        <button type="button" class="side-tab" data-pane="color">Renk</button>
      </div>
      <div class="pane active" data-pane="front">
        <div class="acc" style="margin:0 0 10px">
          <div class="acc-body">
            ${hostAddUI || doorUI || glassUI || '<p class="hint-inline">Bu parçanın ön ayarı yok. Ölçü veya renk sekmesine geçin.</p>'}
          </div>
        </div>
      </div>
      <div class="pane" data-pane="size">
        <div class="acc" style="margin:0">
          <div class="acc-body">
            <div class="dim-row">
              <div class="field"><label>En</label><input type="number" id="dim-w" min="10" max="500" step="1" value="${fmt(mod.w)}"></div>
              <div class="field"><label>Derinlik</label><input type="number" id="dim-d" min="0.5" max="80" step="0.1" value="${fmt(mod.d)}"></div>
              <div class="field"><label>Yükseklik</label><input type="number" id="dim-h" min="1" max="300" step="1" value="${fmt(mod.h)}"></div>
            </div>
            ${floatUI}
            ${wallPanelUI}${plinthUI}${slatUI}${shelfLedUI}${radiusUI}${backUI}
          </div>
        </div>
      </div>
      <div class="pane" data-pane="color">
        <div class="acc" style="margin:0" id="acc-coat" open>
          <div class="acc-body">
            <div class="section-title">Yüzey</div>
            <div class="chip-row">
              ${FINISHES.map((f) => `<button type="button" class="chip ${mod.finish === f.id ? 'active' : ''}" data-finish="${f.id}">${f.label}</button>`).join('')}
            </div>
            <div class="section-title">Renk</div>
            <div class="chip-row">
              ${['#f7f7f7','#111111','#e8e4df','#c4a574','#d4b896','#6b7280','#f2ebe3','#1e3a5f'].map((c) => `
                <button type="button" class="mat-swatch ${mod.color === c && !mod.materialImage ? 'active' : ''}" data-color="${c}" style="background:${c}"></button>
              `).join('')}
            </div>
            <div class="mat-name">${mod.materialName || 'Varsayılan'}${mod.materialCode ? ' · ' + mod.materialCode : ''}</div>
            <p class="hint-inline">Kataloğu açmak için aşağıya bakın. Bir parçanın üstüne de sürükleyebilirsiniz.</p>
            <div id="mat-browser"></div>
          </div>
        </div>
      </div>
      <div class="side-dock">
        <button type="button" class="btn full-btn" id="btn-dup">Çoğalt</button>
        <button type="button" class="danger" id="btn-del">Sil</button>
      </div>
    `;
    sideBody.querySelectorAll('.side-tab').forEach((tab) => {
      tab.addEventListener('click', () => {
        sideBody.querySelectorAll('.side-tab').forEach((t) => t.classList.toggle('active', t === tab));
        sideBody.querySelectorAll('.pane').forEach((p) => p.classList.toggle('active', p.dataset.pane === tab.dataset.pane));
        if (tab.dataset.pane === 'color') openMats();
      });
    });

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
      if (isSolidBody(mod)) {
        resolveAllSolids();
      }
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
        // Yükseklik asla kalınlığa bağlanmaz
        if (mod.type === 'back' && mod.parentId) {
          const host = state.modules.find((m) => m.id === mod.parentId);
          if (host) {
            mod.h = host.h;
            syncAttachedToHost(host);
          }
        }
        if (mod.type === 'wallPanel') sendPanelToWall(mod);
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

    sideBody.querySelectorAll('[data-layout]').forEach((btn) => {
      btn.addEventListener('click', () => applyHostLayout(mod, btn.dataset.layout));
    });
    sideBody.querySelectorAll('[data-front]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const n = btn.dataset.frontBays ? Number(btn.dataset.frontBays) : null;
        if (n) mod.doorBays = n;
        addFrontsToHost(mod, btn.dataset.front, n);
      });
    });
    sideBody.querySelectorAll('[data-front-bays]').forEach((btn) => {
      if (btn.dataset.front) return;
      btn.addEventListener('click', () => {
        const n = Number(btn.dataset.frontBays);
        mod.doorBays = n;
        addFrontsToHost(mod, 'push', n);
      });
    });
    sideBody.querySelectorAll('[data-front-color]').forEach((btn) => {
      btn.addEventListener('click', () => {
        applyFrontStyleColor(mod, btn.dataset.frontColor, btn.dataset.frontFinish, btn.dataset.frontName);
      });
    });
    sideBody.querySelectorAll('[data-host-frame]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const doorsGlass = childrenOf(mod.id).filter((c) => c.type === 'door' && c.doorStyle === 'glass');
        if (!doorsGlass.length) return;
        pushHistory();
        doorsGlass.forEach((d) => { d.frameColor = btn.dataset.hostFrame; });
        rebuildModules().then(() => renderSidebar());
        showHint(`${btn.textContent.trim() || 'Çerçeve'} uygulandı`);
      });
    });
    sideBody.querySelectorAll('[data-host-glass]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const doorsGlass = childrenOf(mod.id).filter((c) => c.type === 'door' && c.doorStyle === 'glass');
        if (!doorsGlass.length) return;
        pushHistory();
        doorsGlass.forEach((d) => {
          d.glassColor = btn.dataset.hostGlass;
          d.glassKind = btn.dataset.glassKind || null;
        });
        rebuildModules().then(() => renderSidebar());
        showHint(`${btn.textContent.trim() || 'Cam'} uygulandı`);
      });
    });
    $('#btn-front-mat')?.addEventListener('click', () => {
      const door = childrenOf(mod.id).find((c) => c.type === 'door');
      if (!door) {
        showHint('Önce Görünüm’den kapak seçin');
        return;
      }
      state.selectedId = door.id;
      state.view = 'materials';
      renderSidebar();
    });
    sideBody.querySelectorAll('[data-top]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const want = btn.dataset.top === '1';
        const has = childrenOf(mod.id).some((c) => c.type === 'top');
        if (want && !has) toggleHostTop(mod);
        else if (!want && has) toggleHostTop(mod);
      });
    });
    sideBody.querySelectorAll('[data-top-preset]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const preset = JSON.parse(decodeURIComponent(btn.dataset.topPreset));
        addTopPanel(mod, preset, false);
      });
    });
    sideBody.querySelectorAll('[data-top-span]').forEach((btn) => {
      btn.addEventListener('click', () => {
        addTopPanel(mod, CATALOG.top[0] || {}, true);
      });
    });
    sideBody.querySelectorAll('[data-interior]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const preset = JSON.parse(decodeURIComponent(btn.dataset.interior));
        addInteriorOrganizer(mod, preset);
      });
    });
    $('#shelf-count')?.addEventListener('change', () => {
      pushHistory();
      mod.shelfCount = clampNum($('#shelf-count').value, 1, 20);
      rebuildModules().then(() => renderSidebar());
    });
    sideBody.querySelectorAll('[data-add-part]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const t = btn.dataset.addPart;
        if (t === 'shelf') addInteriorShelf(mod);
        else if (t === 'back') addAttachedPart('back', { host: mod, thicknessMm: 18, label: 'Arkalık 18 mm' });
        else if (t === 'slat') addAttachedPart('slat', {
          host: mod, onBack: true, slatWidth: 2, slatGap: 1.5, label: 'Çıta arkalık',
        });
        else addAttachedPart(t, { host: mod });
      });
    });
    $('#btn-toggle-back')?.addEventListener('click', () => {
      const existing = childrenOf(mod.id).filter((c) => c.type === 'back');
      if (existing.length) {
        pushHistory();
        const kill = new Set(existing.map((b) => b.id));
        state.modules = state.modules.filter((m) => !kill.has(m.id));
        rebuildModules().then(() => renderSidebar());
        showHint('Arkalık kaldırıldı');
      } else {
        addAttachedPart('back', { host: mod, thicknessMm: 18, label: 'Arkalık 18 mm' });
      }
    });
    sideBody.querySelectorAll('[data-host-back-thick]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const back = childrenOf(mod.id).find((c) => c.type === 'back');
        if (!back) return;
        pushHistory();
        const mm = Number(btn.dataset.hostBackThick);
        back.thicknessMm = mm;
        back.d = mm / 10;
        back.label = `Arkalık ${mm} mm`;
        syncAttachedToHost(mod);
        rebuildModules().then(() => renderSidebar());
        showHint(`Arkalık ${mm} mm`);
      });
    });

    sideBody.querySelectorAll('[data-radius]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.radius = Number(btn.dataset.radius);
        rebuildModules().then(() => renderSidebar());
      });
    });
    sideBody.querySelectorAll('[data-handle]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.handleStyle = btn.dataset.handle;
        rebuildModules().then(() => renderSidebar());
      });
    });
    sideBody.querySelectorAll('[data-handle-pos]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.handlePos = btn.dataset.handlePos;
        rebuildModules().then(() => renderSidebar());
      });
    });

    const applySlat = () => {
      if (mod.type !== 'slat') return;
      pushHistory();
      mod.slatWidth = clampNum($('#slat-w')?.value, 0.8, 6);
      mod.slatGap = clampNum($('#slat-gap')?.value, 0.5, 12);
      if ($('#slat-back-d')) {
        mod.backThicknessCm = clampNum($('#slat-back-d').value, 0.4, 3);
      }
      rebuildModules().then(() => renderSidebar());
    };
    $('#slat-w')?.addEventListener('change', applySlat);
    $('#slat-gap')?.addEventListener('change', applySlat);
    $('#slat-back-d')?.addEventListener('change', applySlat);
    sideBody.querySelectorAll('[data-slat-back]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.hasBack = btn.dataset.slatBack === '1';
        if (mod.hasBack) {
          mod.backThicknessCm = mod.backThicknessCm || 0.8;
          mod.backColor = mod.backColor || '#d4b896';
        }
        rebuildModules().then(() => renderSidebar());
        showHint(mod.hasBack ? 'Çıta arkalığı eklendi' : 'Çıta arkalığı kaldırıldı');
      });
    });
    sideBody.querySelectorAll('[data-slat-back-color]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.backColor = btn.dataset.slatBackColor;
        rebuildModules().then(() => renderSidebar());
      });
    });

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
        if (mod.doorStyle === 'open') {
          mod.handleStyle = 'none';
        }
        if ((mod.doorStyle === 'hinge' || mod.doorStyle === 'push' || mod.doorStyle === 'glass') && mod.d > 8) mod.d = 2;
        if (mod.doorStyle === 'glass') {
          mod.frameColor = mod.frameColor || '#c5c8cc';
          mod.glassColor = mod.glassColor || '#c5d5e8';
        }
        if (mod.doorStyle === 'drawer') {
          mod.handleStyle = mod.handleStyle || 'bar';
          mod.handlePos = mod.handlePos || 'top';
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
    const openMats = () => {
      const host = $('#mat-browser');
      if (!host || host.dataset.ready === '1') return;
      host.dataset.ready = '1';
      mountMaterialBrowser(host, mod, { limit: 16 });
    };
    $('#btn-del').addEventListener('click', deleteSelected);
    $('#btn-dup').addEventListener('click', duplicateSelected);
  }
}

/** Kastamonu malzemesini parçaya uygula */
async function applyCatalogMaterial(mod, item) {
  if (!mod || !item || mod.id === '__tv__') return false;
  pushHistory();
  mod.materialId = item.id;
  mod.materialImage = item.image;
  mod.materialName = item.name;
  mod.materialCode = item.code || '';
  if (item.finish_hint) mod.finish = item.finish_hint;
  await rebuildModules();
  showHint(`${item.code || item.name} · ${typeTitle(mod.type)}`);
  return true;
}

/** Malzemeyi sahne koordinatındaki parçaya uygula (sürükle-bırak) */
async function applyMaterialAtClient(item, clientX, clientY) {
  const id = pickModuleAt(clientX, clientY);
  if (!id || id === '__tv__') {
    // Parça bulunamadıysa seçiliye uygula
    const sel = selected();
    if (!sel) {
      showHint('Kaplamayı bir parçanın üzerine bırakın');
      return;
    }
    await applyCatalogMaterial(sel, item);
    if (state.view === 'customize' || state.view === 'materials') renderSidebar();
    return;
  }
  const mod = state.modules.find((m) => m.id === id);
  if (!mod) {
    showHint('Kaplamayı bir parçanın üzerine bırakın');
    return;
  }
  state.selectedId = id;
  await applyCatalogMaterial(mod, item);
  state.view = 'customize';
  renderSidebar();
  updateSelectionVisual();
}

function wireMaterialDrag(btn, item) {
  btn.draggable = true;
  btn.addEventListener('dragstart', (e) => {
    materialDrag = {
      id: item.id,
      image: item.image,
      name: item.name,
      code: item.code,
      finish_hint: item.finish_hint,
    };
    e.dataTransfer.setData('application/x-tv-material', JSON.stringify(materialDrag));
    e.dataTransfer.setData('text/plain', item.name || 'malzeme');
    e.dataTransfer.effectAllowed = 'copy';
    const overlay = $('#drop-overlay');
    const label = overlay?.querySelector('span');
    if (label) label.textContent = 'Kaplamayı parçaya bırakın';
  });
  btn.addEventListener('dragend', () => {
    materialDrag = null;
    $('#drop-overlay')?.classList.remove('show');
  });
}

/** Kaplama tarayıcısı — özelleştir paneli veya tam ekran */
function mountMaterialBrowser(hostEl, mod, opts = {}) {
  if (!hostEl || !mod) return;
  const groups = state.materials.groups || [];
  if (!groups.length) {
    hostEl.innerHTML = `<div class="empty-state">Malzemeler yükleniyor…</div>`;
    return;
  }
  let active = opts.preferredGroup || groups[0]?.slug;
  const limit = opts.limit || 120;

  hostEl.innerHTML = `
    <div class="tabs" id="mat-tabs">
      ${groups.map((g) => `<button type="button" class="tab ${g.slug === active ? 'active' : ''}" data-g="${g.slug}">${g.label.replace(' Panel', '').replace('Kaplı ', '')}</button>`).join('')}
    </div>
    <div id="mat-list"></div>
  `;

  const paint = () => {
    const g = groups.find((x) => x.slug === active);
    const list = hostEl.querySelector('#mat-list');
    if (!g) {
      list.innerHTML = '<div class="empty-state">Malzeme yok</div>';
      return;
    }
    list.innerHTML = `
      <div class="mat-grid">
        ${g.items.slice(0, limit).map((item) => `
          <button type="button" class="mat-swatch ${mod.materialId === item.id ? 'active' : ''}" data-mat-id="${item.id}" title="${item.code || ''} ${item.name} — sürükle veya tıkla" draggable="true">
            <img src="${item.image}" alt="${item.name}" loading="lazy" draggable="false">
          </button>
        `).join('')}
      </div>
    `;
    list.querySelectorAll('[data-mat-id]').forEach((btn) => {
      const item = g.items.find((i) => String(i.id) === String(btn.dataset.matId));
      if (!item) return;
      wireMaterialDrag(btn, item);
      btn.addEventListener('click', async () => {
        await applyCatalogMaterial(mod, item);
        if (state.view === 'materials') renderMaterialsPanel(mod, active);
        else renderSidebar();
      });
    });
  };

  hostEl.querySelectorAll('#mat-tabs .tab').forEach((t) => {
    t.addEventListener('click', () => {
      active = t.dataset.g;
      hostEl.querySelectorAll('#mat-tabs .tab').forEach((x) => x.classList.toggle('active', x === t));
      paint();
    });
  });
  paint();
}

function renderMaterialsPanel(mod, preferredGroup) {
  const groups = state.materials.groups || [];
  if (!groups.length) {
    sideBody.innerHTML = `<div class="empty-state">Malzemeler yükleniyor…</div>`;
    return;
  }
  sideBody.innerHTML = `
    ${mod.materialName ? `<div class="applied-banner">${mod.materialCode ? mod.materialCode + ' · ' : ''}${mod.materialName} uygulandı</div>` : ''}
    <div class="panel-section">
      <div class="section-title">Parça: ${typeTitle(mod.type)}</div>
      <div class="chip-row" style="margin-bottom:10px">
        ${FINISHES.map((f) => `<button type="button" class="chip ${mod.finish === f.id ? 'active' : ''}" data-finish="${f.id}">${f.label}</button>`).join('')}
      </div>
      <p class="hint-inline">Tıkla → bu parçaya · Sürükle → başka parçaya bırak</p>
      <div id="mat-browser"></div>
    </div>
  `;
  sideBody.querySelectorAll('[data-finish]').forEach((btn) => {
    btn.addEventListener('click', () => {
      pushHistory();
      mod.finish = btn.dataset.finish;
      rebuildModules().then(() => renderMaterialsPanel(mod, preferredGroup));
    });
  });
  mountMaterialBrowser($('#mat-browser'), mod, { preferredGroup, limit: 120 });
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
  persistDesign();
  showHint('Tasarım kaydedildi');
}

function persistDesign() {
  try {
    localStorage.setItem('tv-configurator-design', JSON.stringify({
      version: 4,
      brand: brandName,
      room: state.room,
      modules: state.modules,
      showTv: state.showTv,
      tvInch: state.tvInch,
      tv: state.tv,
      selectedId: state.selectedId,
      savedAt: new Date().toISOString(),
    }));
  } catch (_) { /* quota */ }
}

let autoSaveTimer = null;
function scheduleAutoSave() {
  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(() => {
    if (!state.modules.length && !state.showTv) return;
    persistDesign();
  }, 500);
}

function loadDesign(force = false) {
  try {
    const raw = localStorage.getItem('tv-configurator-design');
    if (!raw) {
      clearAllLabels();
      if (force) showHint('Kayıtlı tasarım yok');
      return false;
    }
    const data = JSON.parse(raw);
    if (data.modules) {
      state.modules = data.modules.map((m) => ({ ...m }));
      if (data.room) state.room = data.room;
      if (typeof data.showTv === 'boolean') state.showTv = data.showTv;
      if (data.tvInch) state.tvInch = data.tvInch;
      if (data.tv) state.tv = { ...state.tv, ...data.tv };
      buildRoom();
      buildTv();
      resolveAllSolids();
      rebuildModules().then(() => {
        $('#fab-tv')?.classList.toggle('active', state.showTv);
        state.selectedId = data.selectedId && state.modules.some((m) => m.id === data.selectedId)
          ? data.selectedId
          : (state.modules[0]?.id || null);
        state.view = state.selectedId ? 'customize' : 'home';
        renderSidebar();
        updateSelectionVisual();
        if (force) showHint('Kayıtlı tasarım yüklendi');
        else showHint('Tasarımınız geri yüklendi', 2200);
      });
      return true;
    }
    clearAllLabels();
    return false;
  } catch (_) {
    clearAllLabels();
    return false;
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
    if (!state.showTv) {
      state.showTv = true;
      alignTvToWall();
      $('#fab-tv').classList.add('active');
    }
    state.selectedId = '__tv__';
    state.view = 'tv';
    updateSelectionVisual();
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
    scene.fog = new THREE.Fog(state.night ? 0x1f2937 : 0xe8e8e8, state.night ? 8 : 10, 22);
    if (state._hemi) state._hemi.intensity = state.night ? 0.22 : 0.62;
    if (state._dirLight) state._dirLight.intensity = state.night ? 0.35 : 1.65;
    if (state._fillLight) state._fillLight.intensity = state.night ? 0.12 : 0.42;
    if (state._bounceLight) state._bounceLight.intensity = state.night ? 0.08 : 0.22;
    renderer.toneMappingExposure = state.night ? 0.85 : 1.08;
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
  await loadMaterials();
  const restored = loadDesign(false);
  if (!restored) {
    renderSidebar();
    showHint('Hazır ünite seçin veya parça ekleyin · Otomatik kaydedilir', 3200);
  }
}

boot();
