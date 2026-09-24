import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { CSS2DRenderer, CSS2DObject } from 'three/addons/renderers/CSS2DRenderer.js';

const CM = 0.01; // 1 unit = 1 meter; sizes stored in cm
const SNAP = 1; // cm snap
const WALL_W = 400;
const WALL_H = 280;
const FLOOR_D = 300;

const FRAME_PRESETS = [
  { id: 'f60x20', label: 'İskelet', w: 60, d: 20, h: 38, price: 1500 },
  { id: 'f60x40', label: 'İskelet', w: 60, d: 40, h: 38, price: 2000 },
  { id: 'f120x40', label: 'İskelet geniş', w: 120, d: 40, h: 38, price: 3200 },
  { id: 'f60x64', label: 'İskelet yüksek', w: 60, d: 40, h: 64, price: 2800 },
  { id: 'f180x40', label: 'TV banko', w: 180, d: 40, h: 38, price: 4500 },
];

const PRICES = {
  frame: 1500,
  door: 900,
  shelf: 350,
  back: 400,
  slat: 650,
  top: 800,
  leg: 250,
  plinth: 300,
};

const FINISHES = [
  { id: 'matte', label: 'Mat' },
  { id: 'gloss', label: 'Parlak' },
  { id: 'highgloss', label: 'High Gloss' },
  { id: 'ceramic', label: 'Seramik görünüm' },
];

const appEl = document.getElementById('app');
const materialsUrl = appEl.dataset.materialsUrl;
const brandName = appEl.dataset.brand || 'Meemare';

const state = {
  view: 'home', // home | catalog | customize | room | materials
  catalogKind: null,
  selectedId: null,
  modules: [],
  materials: { groups: [] },
  showTv: true,
  night: false,
  showGrid: false,
  showMeasure: true,
  room: { wall: '#f2f2f2', floor: '#d8c3a5' },
  history: [],
  future: [],
  textureCache: new Map(),
};

let scene, camera, renderer, labelRenderer, controls, raycaster, pointer;
let roomGroup, modulesGroup, gridHelper, tvMesh;
let drag = null;
let animId = 0;

const $ = (sel) => document.querySelector(sel);
const sideBody = $('#side-body');
const sideTitle = $('#side-title');
const btnBack = $('#btn-back');
const hintEl = $('#hint');
const priceEl = $('#price-display');

function uid() {
  return 'm_' + Math.random().toString(36).slice(2, 10);
}

function showHint(text, ms = 2200) {
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
  updatePrice();
}

function redo() {
  if (!state.future.length) return;
  state.history.push(JSON.stringify(state.modules));
  state.modules = JSON.parse(state.future.pop());
  state.selectedId = null;
  rebuildModules();
  renderSidebar();
  updatePrice();
}

function selected() {
  return state.modules.find((m) => m.id === state.selectedId) || null;
}

function formatPrice(n) {
  return new Intl.NumberFormat('tr-TR').format(Math.round(n)) + ' ₺';
}

function estimatePrice(m) {
  const base = PRICES[m.type] || 500;
  const volume = (m.w * m.h * m.d) / (60 * 38 * 40);
  return Math.max(200, Math.round(base * Math.max(0.6, volume)));
}

function updatePrice() {
  const total = state.modules.reduce((s, m) => s + estimatePrice(m), 0);
  priceEl.textContent = formatPrice(total);
}

function finishRoughness(finish) {
  if (finish === 'matte') return 0.82;
  if (finish === 'gloss') return 0.28;
  if (finish === 'highgloss') return 0.08;
  if (finish === 'ceramic') return 0.35;
  return 0.5;
}

function finishMetalness(finish) {
  if (finish === 'highgloss') return 0.15;
  if (finish === 'ceramic') return 0.05;
  return 0.02;
}

async function loadTexture(url) {
  if (!url) return null;
  if (state.textureCache.has(url)) return state.textureCache.get(url);
  const loader = new THREE.TextureLoader();
  loader.setCrossOrigin('anonymous');
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

async function makeMaterial(mod) {
  const map = mod.materialImage ? await loadTexture(mod.materialImage) : null;
  const mat = new THREE.MeshStandardMaterial({
    color: map ? 0xffffff : new THREE.Color(mod.color || '#f5f5f5'),
    map: map || null,
    roughness: finishRoughness(mod.finish),
    metalness: finishMetalness(mod.finish),
  });
  if (map && (mod.type === 'frame' || mod.type === 'door' || mod.type === 'top' || mod.type === 'back')) {
    map.repeat.set(Math.max(1, mod.w / 60), Math.max(1, mod.h / 60));
  }
  return mat;
}

function initThree() {
  const host = $('#canvas-host');
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0xe8e8e8);
  scene.fog = new THREE.Fog(0xe8e8e8, 8, 18);

  camera = new THREE.PerspectiveCamera(42, 1, 0.05, 50);
  camera.position.set(2.2, 1.6, 3.2);

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
  controls.target.set(0, 0.6, 0);
  controls.enableDamping = true;
  controls.maxPolarAngle = Math.PI * 0.49;
  controls.minDistance = 1.2;
  controls.maxDistance = 8;

  const hemi = new THREE.HemisphereLight(0xffffff, 0xb0b0b0, 1.05);
  scene.add(hemi);
  const dir = new THREE.DirectionalLight(0xffffff, 1.35);
  dir.position.set(3, 5, 2);
  dir.castShadow = true;
  dir.shadow.mapSize.set(2048, 2048);
  dir.shadow.camera.near = 0.5;
  dir.shadow.camera.far = 20;
  dir.shadow.camera.left = -4;
  dir.shadow.camera.right = 4;
  dir.shadow.camera.top = 4;
  dir.shadow.camera.bottom = -4;
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

  renderer.domElement.addEventListener('pointerdown', onPointerDown);
  renderer.domElement.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);
  window.addEventListener('resize', onResize);
  onResize();
  animate();
}

function buildRoom() {
  while (roomGroup.children.length) roomGroup.remove(roomGroup.children[0]);

  const floorMat = new THREE.MeshStandardMaterial({
    color: new THREE.Color(state.room.floor),
    roughness: 0.7,
    metalness: 0.05,
  });
  // faux wood stripes via canvas texture
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

  const wallMat = new THREE.MeshStandardMaterial({
    color: new THREE.Color(state.room.wall),
    roughness: 0.92,
  });
  const wall = new THREE.Mesh(new THREE.PlaneGeometry(WALL_W * CM, WALL_H * CM), wallMat);
  wall.position.set(0, (WALL_H * CM) / 2, -(FLOOR_D * CM) / 2 + 0.01);
  wall.receiveShadow = true;
  wall.name = 'wall';
  roomGroup.add(wall);

  // skirting
  const sk = new THREE.Mesh(
    new THREE.BoxGeometry(WALL_W * CM, 0.08, 0.02),
    new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.6 })
  );
  sk.position.set(0, 0.04, -(FLOOR_D * CM) / 2 + 0.03);
  roomGroup.add(sk);
}

function buildTv() {
  if (tvMesh) scene.remove(tvMesh);
  const g = new THREE.Group();
  const screen = new THREE.Mesh(
    new THREE.BoxGeometry(1.2, 0.68, 0.04),
    new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.35, metalness: 0.4 })
  );
  screen.position.set(0, 1.15, -(FLOOR_D * CM) / 2 + 0.08);
  const glass = new THREE.Mesh(
    new THREE.PlaneGeometry(1.12, 0.6),
    new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.2, metalness: 0.6 })
  );
  glass.position.set(0, 1.15, -(FLOOR_D * CM) / 2 + 0.102);
  g.add(screen, glass);
  g.visible = state.showTv;
  tvMesh = g;
  scene.add(tvMesh);
}

function frameShellGeometry(w, h, d, thick = 1.8) {
  // hollow cabinet: outer box minus inner (visual via edges + panels)
  const group = new THREE.Group();
  const t = thick * CM;
  const W = w * CM, H = h * CM, D = d * CM;

  const parts = [
    { s: [W, H, t], p: [0, H / 2, -D / 2 + t / 2] }, // back of carcass thin? we'll add real back separately
    { s: [W, t, D], p: [0, t / 2, 0] }, // bottom
    { s: [W, t, D], p: [0, H - t / 2, 0] }, // top
    { s: [t, H - 2 * t, D], p: [-W / 2 + t / 2, H / 2, 0] }, // left
    { s: [t, H - 2 * t, D], p: [W / 2 - t / 2, H / 2, 0] }, // right
  ];
  // skip fake back in carcass - use open back so arka panel shows
  parts.shift();
  return { parts, W, H, D, t };
}

async function createModuleMesh(mod) {
  const group = new THREE.Group();
  group.userData.moduleId = mod.id;
  const mat = await makeMaterial(mod);
  const edgeMat = new THREE.LineBasicMaterial({ color: 0x9ca3af });

  if (mod.type === 'frame') {
    const { parts, W, H, D } = frameShellGeometry(mod.w, mod.h, mod.d);
    for (const part of parts) {
      const mesh = new THREE.Mesh(new THREE.BoxGeometry(...part.s), mat.clone());
      mesh.position.set(...part.p);
      mesh.castShadow = true;
      mesh.receiveShadow = true;
      group.add(mesh);
    }
    // subtle inner back plane (very pale)
    const inner = new THREE.Mesh(
      new THREE.PlaneGeometry(W - 0.04, H - 0.04),
      new THREE.MeshStandardMaterial({ color: 0xfafafa, roughness: 0.95 })
    );
    inner.position.set(0, H / 2, -D / 2 + 0.005);
    group.add(inner);
  } else if (mod.type === 'door') {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, mod.h * CM, Math.max(1.6, mod.d) * CM),
      mat
    );
    mesh.castShadow = true;
    group.add(mesh);
    // handle
    const handle = new THREE.Mesh(
      new THREE.CylinderGeometry(0.005, 0.005, 0.12, 12),
      new THREE.MeshStandardMaterial({ color: 0x888888, metalness: 0.8, roughness: 0.25 })
    );
    handle.rotation.z = Math.PI / 2;
    handle.position.set(mod.w * CM * 0.35, 0, mod.d * CM * 0.6);
    group.add(handle);
  } else if (mod.type === 'shelf') {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, Math.max(1.5, mod.h) * CM, mod.d * CM),
      mat
    );
    mesh.castShadow = true;
    group.add(mesh);
  } else if (mod.type === 'back') {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, mod.h * CM, Math.max(0.6, mod.d) * CM),
      mat
    );
    mesh.castShadow = true;
    group.add(mesh);
  } else if (mod.type === 'slat') {
    const count = Math.max(3, Math.floor(mod.w / 4));
    const gap = (mod.w * CM) / count;
    for (let i = 0; i < count; i++) {
      const slat = new THREE.Mesh(
        new THREE.BoxGeometry(1.2 * CM, mod.h * CM, Math.max(1.2, mod.d) * CM),
        mat.clone()
      );
      slat.position.x = -mod.w * CM / 2 + gap / 2 + i * gap;
      slat.castShadow = true;
      group.add(slat);
    }
  } else if (mod.type === 'top') {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, Math.max(1.8, mod.h) * CM, mod.d * CM),
      mat
    );
    mesh.castShadow = true;
    group.add(mesh);
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
  } else if (mod.type === 'plinth') {
    const mesh = new THREE.Mesh(
      new THREE.BoxGeometry(mod.w * CM, mod.h * CM, mod.d * CM),
      mat
    );
    mesh.castShadow = true;
    group.add(mesh);
  }

  // selection outline box
  const box = new THREE.Box3().setFromObject(group);
  const size = new THREE.Vector3();
  box.getSize(size);
  const helper = new THREE.BoxHelper(group, 0x0058a3);
  helper.visible = false;
  helper.name = 'selection';
  group.add(helper);

  if (state.showMeasure) {
    addMeasureLabels(group, mod);
  }

  // position: x,z on floor; y is bottom of module
  placeModule(group, mod);
  return group;
}

function addMeasureLabels(group, mod) {
  const mk = (text, x, y, z) => {
    const el = document.createElement('div');
    el.className = 'measure-label';
    el.textContent = text;
    el.style.cssText = 'color:#111;background:rgba(255,255,255,.85);padding:2px 6px;border-radius:6px;font:600 11px Montserrat,sans-serif;white-space:nowrap;';
    const obj = new CSS2DObject(el);
    obj.position.set(x, y, z);
    obj.name = 'measure';
    group.add(obj);
  };
  const W = mod.w * CM, H = mod.h * CM, D = mod.d * CM;
  mk(mod.w.toFixed(0), 0, H + 0.05, D / 2 + 0.02);
  mk(mod.h.toFixed(0), W / 2 + 0.05, H / 2, 0);
  mk(mod.d.toFixed(0), 0, 0.05, -D / 2 - 0.05);
}

function placeModule(group, mod) {
  const y = (mod.y || 0) * CM;
  group.position.set((mod.x || 0) * CM, y, (mod.z || 0) * CM);
}

async function rebuildModules() {
  while (modulesGroup.children.length) {
    const c = modulesGroup.children[0];
    modulesGroup.remove(c);
    c.traverse((o) => {
      if (o.geometry) o.geometry.dispose();
      if (o.material) {
        if (Array.isArray(o.material)) o.material.forEach((m) => m.dispose());
        else o.material.dispose();
      }
    });
  }
  for (const mod of state.modules) {
    const mesh = await createModuleMesh(mod);
    modulesGroup.add(mesh);
  }
  updateSelectionVisual();
}

function updateSelectionVisual() {
  modulesGroup.children.forEach((g) => {
    const helper = g.children.find((c) => c.name === 'selection');
    if (helper) helper.visible = g.userData.moduleId === state.selectedId;
  });
  const bar = $('#selection-bar');
  if (bar) bar.hidden = !state.selectedId;
}

function nextFramePosition(preset) {
  // place along wall, stack side by side
  const frames = state.modules.filter((m) => m.type === 'frame');
  let x = -90;
  if (frames.length) {
    const right = Math.max(...frames.map((f) => f.x + f.w / 2));
    x = right + preset.w / 2 + 0.5;
  }
  const z = -(FLOOR_D / 2) + preset.d / 2 + 2;
  return { x, y: 0, z };
}

function addFrame(preset) {
  pushHistory();
  const pos = nextFramePosition(preset);
  const mod = {
    id: uid(),
    type: 'frame',
    label: preset.label,
    w: preset.w,
    h: preset.h,
    d: preset.d,
    x: pos.x,
    y: 0,
    z: pos.z,
    finish: 'matte',
    color: '#f7f7f7',
    materialId: null,
    materialImage: null,
    materialName: 'Beyaz',
  };
  state.modules.push(mod);
  state.selectedId = mod.id;
  rebuildModules().then(() => {
    renderSidebar();
    updatePrice();
    showHint('İskelet eklendi — özelleştirmek için seçili bırakın');
    state.view = 'customize';
    renderSidebar();
  });
}

function addChildPart(type) {
  const frame = selected() && selected().type === 'frame'
    ? selected()
    : state.modules.filter((m) => m.type === 'frame').at(-1);
  if (!frame) {
    showHint('Önce bir iskelet ekleyin');
    state.view = 'catalog';
    state.catalogKind = 'frame';
    renderSidebar();
    return;
  }
  pushHistory();
  let mod;
  if (type === 'door') {
    mod = {
      id: uid(), type: 'door', label: 'Kapak', parentId: frame.id,
      w: frame.w - 2, h: frame.h - 2, d: 1.8,
      x: frame.x, y: frame.y + 1, z: frame.z + frame.d / 2 + 0.5,
      finish: 'highgloss', color: '#111111', materialId: null, materialImage: null, materialName: 'Siyah High Gloss',
    };
  } else if (type === 'shelf') {
    mod = {
      id: uid(), type: 'shelf', label: 'Raf', parentId: frame.id,
      w: frame.w - 3.6, h: 1.8, d: frame.d - 2,
      x: frame.x, y: frame.y + frame.h / 2, z: frame.z,
      finish: 'matte', color: '#f5f5f5', materialId: null, materialImage: null, materialName: 'Beyaz',
    };
  } else if (type === 'back') {
    mod = {
      id: uid(), type: 'back', label: 'Arka panel', parentId: frame.id,
      w: frame.w - 1, h: frame.h - 1, d: 0.8,
      x: frame.x, y: frame.y + 0.5, z: frame.z - frame.d / 2 + 0.5,
      finish: 'matte', color: '#eeeeee', materialId: null, materialImage: null, materialName: 'Beyaz',
    };
  } else if (type === 'slat') {
    mod = {
      id: uid(), type: 'slat', label: 'Çıta panel', parentId: frame.id,
      w: frame.w, h: frame.h, d: 2,
      x: frame.x, y: frame.y, z: frame.z + frame.d / 2 + 1.2,
      finish: 'matte', color: '#c4a574', materialId: null, materialImage: null, materialName: 'Ahşap çıta',
    };
  } else if (type === 'top') {
    mod = {
      id: uid(), type: 'top', label: 'Üst panel', parentId: frame.id,
      w: frame.w + 2, h: 2, d: frame.d + 2,
      x: frame.x, y: frame.y + frame.h, z: frame.z,
      finish: 'ceramic', color: '#e8e4df', materialId: null, materialImage: null, materialName: 'Seramik',
    };
  } else if (type === 'leg') {
    mod = {
      id: uid(), type: 'leg', label: 'Ayaklar', parentId: frame.id,
      w: frame.w, h: 10, d: frame.d,
      x: frame.x, y: Math.max(0, frame.y - 10), z: frame.z,
      finish: 'matte', color: '#222222', materialId: null, materialImage: null, materialName: 'Siyah metal',
    };
    frame.y = 10;
  } else if (type === 'plinth') {
    mod = {
      id: uid(), type: 'plinth', label: 'Alt blok / baza', parentId: frame.id,
      w: frame.w, h: 8, d: frame.d,
      x: frame.x, y: 0, z: frame.z,
      finish: 'matte', color: '#111111', materialId: null, materialImage: null, materialName: 'Siyah baza',
    };
    frame.y = 8;
  }
  state.modules.push(mod);
  // update parent frame y if needed already done
  state.selectedId = mod.id;
  rebuildModules().then(() => {
    state.view = 'customize';
    renderSidebar();
    updatePrice();
    showHint(mod.label + ' eklendi');
  });
}

function deleteSelected() {
  const mod = selected();
  if (!mod) return;
  pushHistory();
  state.modules = state.modules.filter((m) => m.id !== mod.id && m.parentId !== mod.id);
  state.selectedId = null;
  state.view = 'home';
  rebuildModules();
  renderSidebar();
  updatePrice();
}

function duplicateSelected() {
  const mod = selected();
  if (!mod || mod.type !== 'frame') {
    showHint('Çoğaltmak için bir iskelet seçin');
    return;
  }
  pushHistory();
  const copy = {
    ...mod,
    id: uid(),
    x: mod.x + mod.w + 1,
  };
  state.modules.push(copy);
  state.selectedId = copy.id;
  rebuildModules().then(() => {
    renderSidebar();
    updatePrice();
  });
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
  animId = requestAnimationFrame(animate);
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

function onPointerDown(e) {
  if (e.button !== 0) return;
  const id = pickModule(e);
  if (id) {
    state.selectedId = id;
    state.view = 'customize';
    updateSelectionVisual();
    renderSidebar();
    const mod = selected();
    if (mod && (mod.type === 'frame' || mod.type === 'plinth' || mod.type === 'top' || mod.type === 'slat')) {
      controls.enabled = false;
      drag = { id: mod.id, moved: false, snapshot: JSON.stringify(state.modules) };
    }
  } else {
    state.selectedId = null;
    updateSelectionVisual();
    if (state.view === 'customize') {
      state.view = 'home';
      renderSidebar();
    }
  }
}

function onPointerMove(e) {
  if (!drag) return;
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
  // keep near wall for frames
  const minZ = -(FLOOR_D / 2) + mod.d / 2 + 1;
  const maxZ = FLOOR_D / 2 - mod.d / 2 - 20;
  mod.x = Math.max(-WALL_W / 2 + mod.w / 2, Math.min(WALL_W / 2 - mod.w / 2, nx));
  mod.z = Math.max(minZ, Math.min(maxZ, nz));
  // snap to neighboring frames
  if (mod.type === 'frame') {
    const others = state.modules.filter((m) => m.type === 'frame' && m.id !== mod.id);
    for (const o of others) {
      const gapL = Math.abs((mod.x - mod.w / 2) - (o.x + o.w / 2));
      const gapR = Math.abs((mod.x + mod.w / 2) - (o.x - o.w / 2));
      if (gapL < 3) mod.x = o.x + o.w / 2 + mod.w / 2;
      if (gapR < 3) mod.x = o.x - o.w / 2 - mod.w / 2;
      if (Math.abs(mod.z - o.z) < 3) mod.z = o.z;
      if (Math.abs(mod.y - o.y) < 2 && Math.abs(mod.x - o.x) < 3) {
        /* stack align */
      }
    }
  }
  drag.moved = true;
  const g = modulesGroup.children.find((c) => c.userData.moduleId === mod.id);
  if (g) placeModule(g, mod);
  // sync children absolute positions roughly
  state.modules.filter((m) => m.parentId === mod.id).forEach((child) => {
    // keep relative z/x based on type
    if (child.type === 'door') {
      child.x = mod.x;
      child.z = mod.z + mod.d / 2 + 0.5;
      child.y = mod.y + 1;
    } else if (child.type === 'shelf') {
      child.x = mod.x;
      child.z = mod.z;
    } else if (child.type === 'back') {
      child.x = mod.x;
      child.z = mod.z - mod.d / 2 + 0.5;
      child.y = mod.y + 0.5;
    } else if (child.type === 'slat') {
      child.x = mod.x;
      child.z = mod.z + mod.d / 2 + 1.2;
      child.y = mod.y;
    } else if (child.type === 'top') {
      child.x = mod.x;
      child.y = mod.y + mod.h;
      child.z = mod.z;
    } else if (child.type === 'leg') {
      child.x = mod.x;
      child.z = mod.z;
      child.y = Math.max(0, mod.y - child.h);
    } else if (child.type === 'plinth') {
      child.x = mod.x;
      child.z = mod.z;
    }
    const cg = modulesGroup.children.find((c) => c.userData.moduleId === child.id);
    if (cg) placeModule(cg, child);
  });
}

function onPointerUp() {
  if (drag?.moved && drag.snapshot) {
    state.history.push(drag.snapshot);
    if (state.history.length > 40) state.history.shift();
    state.future = [];
    updatePrice();
  }
  drag = null;
  controls.enabled = true;
}

function menuItems() {
  return [
    { kind: 'frame', label: 'İskeletler', icon: '□', desc: 'Alt / yan bloklar' },
    { kind: 'door', label: 'Ön paneller / Kapaklar', icon: '▣', desc: 'Kapak ve sürgü' },
    { kind: 'shelf', label: 'Raflar', icon: '☰', desc: 'İç raf ekle' },
    { kind: 'back', label: 'Arka paneller', icon: '▦', desc: 'Arka kaplama' },
    { kind: 'slat', label: 'Çıtalar', icon: '▥', desc: 'Dekoratif çıta' },
    { kind: 'top', label: 'Üst paneller', icon: '▬', desc: 'Tezgah / üst' },
    { kind: 'plinth', label: 'Alt blok / Baza', icon: '▄', desc: 'Yükseltme bloğu' },
    { kind: 'leg', label: 'Ayaklar', icon: '⊓', desc: 'Metal / ahşap ayak' },
    { kind: 'materials', label: 'Malzeme & kaplama', icon: '◆', desc: 'Kastamonu renkleri' },
  ];
}

function renderSidebar() {
  btnBack.style.display = state.view === 'home' ? 'none' : 'inline-flex';

  if (state.view === 'home') {
    sideTitle.textContent = 'Kendi TV ünitenizi oluşturun';
    sideBody.innerHTML = menuItems().map((item) => `
      <button type="button" class="menu-item" data-kind="${item.kind}">
        <span class="ico">${item.icon}</span>
        <span class="label">${item.label}<br><span style="font-weight:500;color:#9ca3af;font-size:11px">${item.desc}</span></span>
        <span class="chev">›</span>
      </button>
    `).join('') + `
      <div class="info-box">İskelet ekleyin, sürükleyerek konumlandırın. Kapak, raf, çıta ve arka paneli seçili iskelete ekleyin. Kaplamalar Kastamonu Entegre kataloğundan seçilir.</div>
      ${state.modules.length ? `<button type="button" class="danger" id="btn-clear-all">Tasarımı temizle</button>` : ''}
    `;
    sideBody.querySelectorAll('[data-kind]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const kind = btn.dataset.kind;
        if (kind === 'materials') {
          state.view = 'materials';
          renderSidebar();
          return;
        }
        if (kind === 'frame') {
          state.view = 'catalog';
          state.catalogKind = 'frame';
          renderSidebar();
          return;
        }
        addChildPart(kind);
      });
    });
    $('#btn-clear-all')?.addEventListener('click', () => {
      pushHistory();
      state.modules = [];
      state.selectedId = null;
      rebuildModules();
      updatePrice();
      renderSidebar();
    });
    return;
  }

  if (state.view === 'catalog' && state.catalogKind === 'frame') {
    sideTitle.textContent = 'İskeletler';
    sideBody.innerHTML = `
      <div class="panel-section">
        <div class="section-title">Hazır ebatlar</div>
        <div class="product-grid">
          ${FRAME_PRESETS.map((p) => `
            <button type="button" class="product-card" data-preset="${p.id}">
              <div class="thumb"><div class="swatch" style="background:linear-gradient(135deg,#fff,#e5e7eb);border:8px solid #f3f4f6;box-sizing:border-box"></div></div>
              <div class="meta">
                <strong>${p.w}×${p.d}×${p.h} cm</strong>
                <span>${formatPrice(p.price)}</span>
              </div>
            </button>
          `).join('')}
        </div>
      </div>
      <div class="info-box">Bu kombinasyonu monte etmek için gereken bağlantı parçaları sipariş özetinde listelenir.</div>
    `;
    sideBody.querySelectorAll('[data-preset]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const preset = FRAME_PRESETS.find((p) => p.id === btn.dataset.preset);
        if (preset) addFrame(preset);
      });
    });
    return;
  }

  if (state.view === 'materials') {
    sideTitle.textContent = 'Malzeme & kaplama';
    const mod = selected();
    if (!mod) {
      sideBody.innerHTML = `<div class="empty-state">Kaplama uygulamak için sahneden bir parça seçin (iskelet, kapak, çıta…).</div>`;
      return;
    }
    renderMaterialsPanel(mod);
    return;
  }

  if (state.view === 'room') {
    sideTitle.textContent = 'Odayı özelleştirin';
    sideBody.innerHTML = `
      <div class="panel-section">
        <div class="field">
          <label>Duvar rengi</label>
          <input type="color" id="wall-color" value="${state.room.wall}">
        </div>
        <div class="field">
          <label>Zemin tonu</label>
          <input type="color" id="floor-color" value="${state.room.floor}">
        </div>
        <div class="chip-row" style="margin-top:8px">
          <button type="button" class="chip" data-wall="#f2f2f2" data-floor="#d8c3a5">Açık</button>
          <button type="button" class="chip" data-wall="#e8eef5" data-floor="#cbb89a">Soğuk</button>
          <button type="button" class="chip" data-wall="#1f2937" data-floor="#6b4f3a">Koyu</button>
        </div>
      </div>
    `;
    const applyRoom = () => { buildRoom(); };
    $('#wall-color').addEventListener('input', (e) => { state.room.wall = e.target.value; applyRoom(); });
    $('#floor-color').addEventListener('input', (e) => { state.room.floor = e.target.value; applyRoom(); });
    sideBody.querySelectorAll('.chip').forEach((c) => {
      c.addEventListener('click', () => {
        state.room.wall = c.dataset.wall;
        state.room.floor = c.dataset.floor;
        renderSidebar();
        applyRoom();
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
    sideTitle.textContent = typeTitle(mod.type) + ' özelleştir';
    sideBody.innerHTML = `
      <div class="panel-section">
        <div class="section-title">Ebat (cm)</div>
        <div class="dim-row">
          <div class="field"><label>En</label><input type="number" id="dim-w" min="10" max="300" step="1" value="${mod.w}"></div>
          <div class="field"><label>Boy</label><input type="number" id="dim-d" min="5" max="80" step="1" value="${mod.d}"></div>
          <div class="field"><label>Yükseklik</label><input type="number" id="dim-h" min="1" max="250" step="1" value="${mod.h}"></div>
        </div>

        <div class="section-title">Yüzey</div>
        <div class="chip-row" id="finish-chips">
          ${FINISHES.map((f) => `<button type="button" class="chip ${mod.finish === f.id ? 'active' : ''}" data-finish="${f.id}">${f.label}</button>`).join('')}
        </div>

        <div class="section-title">Kaplama</div>
        <div class="field">
          <label>Seçili</label>
          <div style="font-size:13px;font-weight:600">${mod.materialName || 'Varsayılan'} ${mod.materialCode ? '· ' + mod.materialCode : ''}</div>
        </div>
        <button type="button" class="btn" id="btn-pick-mat" style="width:100%;justify-content:center;border-radius:12px;box-shadow:none;border:1px solid #e5e7eb">Katalogdan malzeme seç</button>

        <div class="section-title" style="margin-top:16px">Hızlı renk</div>
        <div class="chip-row">
          ${['#f7f7f7','#111111','#e8e4df','#c4a574','#6b7280','#1e3a5f'].map((c) => `
            <button type="button" class="mat-swatch ${mod.color === c && !mod.materialImage ? 'active' : ''}" data-color="${c}" style="background:${c}"></button>
          `).join('')}
        </div>
      </div>
      <div class="info-box">Tahmini: <strong>${formatPrice(estimatePrice(mod))}</strong></div>
      <button type="button" class="btn" id="btn-dup" style="width:calc(100% - 32px);margin:8px 16px;justify-content:center;border-radius:12px;box-shadow:none;border:1px solid #e5e7eb">Çoğalt</button>
      <button type="button" class="danger" id="btn-del">Seçili parçayı sil</button>
    `;

    const applyDims = () => {
      pushHistory();
      mod.w = clampNum($('#dim-w').value, 10, 300);
      mod.d = clampNum($('#dim-d').value, 1, 80);
      mod.h = clampNum($('#dim-h').value, 1, 250);
      if (mod.type === 'frame') {
        state.modules.filter((m) => m.parentId === mod.id).forEach((child) => {
          if (child.type === 'door') {
            child.w = Math.max(10, mod.w - 2);
            child.h = Math.max(10, mod.h - 2);
            child.x = mod.x;
            child.z = mod.z + mod.d / 2 + 0.5;
            child.y = mod.y + 1;
          } else if (child.type === 'shelf') {
            child.w = Math.max(8, mod.w - 3.6);
            child.d = Math.max(5, mod.d - 2);
            child.x = mod.x;
            child.z = mod.z;
          } else if (child.type === 'back') {
            child.w = Math.max(8, mod.w - 1);
            child.h = Math.max(8, mod.h - 1);
            child.x = mod.x;
            child.z = mod.z - mod.d / 2 + 0.5;
            child.y = mod.y + 0.5;
          } else if (child.type === 'slat') {
            child.w = mod.w;
            child.h = mod.h;
            child.x = mod.x;
            child.z = mod.z + mod.d / 2 + 1.2;
            child.y = mod.y;
          } else if (child.type === 'top') {
            child.w = mod.w + 2;
            child.d = mod.d + 2;
            child.x = mod.x;
            child.y = mod.y + mod.h;
            child.z = mod.z;
          } else if (child.type === 'plinth' || child.type === 'leg') {
            child.w = mod.w;
            child.d = mod.d;
            child.x = mod.x;
            child.z = mod.z;
          }
        });
      }
      rebuildModules().then(updatePrice);
    };
    ['dim-w', 'dim-d', 'dim-h'].forEach((id) => {
      $(`#${id}`).addEventListener('change', applyDims);
    });
    sideBody.querySelectorAll('[data-finish]').forEach((btn) => {
      btn.addEventListener('click', () => {
        pushHistory();
        mod.finish = btn.dataset.finish;
        rebuildModules().then(() => { renderSidebar(); updatePrice(); });
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
        rebuildModules().then(() => { renderSidebar(); updatePrice(); });
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

function typeTitle(type) {
  return ({
    frame: 'İskelet', door: 'Kapak', shelf: 'Raf', back: 'Arka panel',
    slat: 'Çıta', top: 'Üst panel', leg: 'Ayak', plinth: 'Alt blok',
  })[type] || 'Parça';
}

function clampNum(v, min, max) {
  const n = Number(v);
  if (Number.isNaN(n)) return min;
  return Math.min(max, Math.max(min, n));
}

function renderMaterialsPanel(mod, preferredGroup) {
  const groups = state.materials.groups || [];
  sideBody.innerHTML = `
    <div class="tabs" id="mat-tabs">
      ${groups.map((g, i) => {
        const activeSlug = preferredGroup || groups[0]?.slug;
        return `<button type="button" class="tab ${g.slug === activeSlug ? 'active' : ''}" data-g="${g.slug}">${g.label.replace(' Panel', '').replace('Kaplı ', '')}</button>`;
      }).join('')}
    </div>
    <div class="panel-section">
      <div class="section-title">Uygulanacak parça: ${typeTitle(mod.type)}</div>
      <div class="chip-row" style="margin-bottom:10px" id="finish-chips-mat">
        ${FINISHES.map((f) => `<button type="button" class="chip ${mod.finish === f.id ? 'active' : ''}" data-finish="${f.id}">${f.label}</button>`).join('')}
      </div>
      <div id="mat-list"></div>
    </div>
  `;
  let active = preferredGroup || groups[0]?.slug;
  const paint = () => {
    const g = groups.find((x) => x.slug === active);
    const list = $('#mat-list');
    if (!g) { list.innerHTML = '<div class="empty-state">Malzeme yüklenemedi</div>'; return; }
    list.innerHTML = `
      <div class="mat-grid">
        ${g.items.slice(0, 120).map((item) => `
          <button type="button" class="mat-swatch ${mod.materialId === item.id ? 'active' : ''}" data-id="${item.id}" title="${item.code} ${item.name}">
            <img src="${item.image}" alt="${item.name}" loading="lazy">
          </button>
        `).join('')}
      </div>
      <div class="info-box" style="margin:12px 0 0">${g.label} · tıklayınca seçili parçaya uygulanır. High Gloss için Glossmax Pro; mat için Boyalı/Melamin önerilir.</div>
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
        updatePrice();
        showHint(`${item.code} · ${item.name} uygulandı`);
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
      <td>${typeTitle(m.type)} · ${m.materialName || m.label || ''}<br><span style="color:#9ca3af;font-size:11px">${m.w}×${m.d}×${m.h} cm · ${FINISHES.find((f) => f.id === m.finish)?.label || ''} ${m.materialCode || ''}</span></td>
      <td style="text-align:right;font-weight:600">${formatPrice(estimatePrice(m))}</td>
    </tr>
  `).join('');
  const total = state.modules.reduce((s, m) => s + estimatePrice(m), 0);
  content.innerHTML = state.modules.length ? `
    <table>
      <thead><tr><th>Parça</th><th style="text-align:right">Tutar</th></tr></thead>
      <tbody>${rows}</tbody>
      <tfoot><tr><th>Toplam (tahmini)</th><th style="text-align:right">${formatPrice(total)}</th></tr></tfoot>
    </table>
  ` : `<div class="empty-state">Henüz parça yok.</div>`;
  modal.classList.add('open');
}

function saveDesign() {
  const payload = {
    version: 1,
    brand: brandName,
    room: state.room,
    modules: state.modules,
    savedAt: new Date().toISOString(),
  };
  localStorage.setItem('tv-configurator-design', JSON.stringify(payload));
  showHint('Tasarım tarayıcıya kaydedildi');
}

function loadDesign() {
  try {
    const raw = localStorage.getItem('tv-configurator-design');
    if (!raw) return;
    const data = JSON.parse(raw);
    if (data.modules) {
      state.modules = data.modules;
      if (data.room) state.room = data.room;
      rebuildModules().then(() => {
        updatePrice();
        showHint('Kayıtlı tasarım yüklendi');
      });
    }
  } catch (_) {}
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
      `${typeTitle(m.type)} | ${m.w}x${m.d}x${m.h}cm | ${m.materialName || ''} ${m.materialCode || ''} | ${FINISHES.find((f) => f.id === m.finish)?.label} | ${formatPrice(estimatePrice(m))}`
    ).join('\n');
    const total = state.modules.reduce((s, m) => s + estimatePrice(m), 0);
    await navigator.clipboard.writeText(`${brandName} TV Ünitesi\n${text}\nToplam: ${formatPrice(total)}`);
    showHint('Özet panoya kopyalandı');
  });
  $('#btn-room').addEventListener('click', () => {
    state.view = 'room';
    renderSidebar();
  });
  $('#fab-tv').addEventListener('click', () => {
    state.showTv = !state.showTv;
    tvMesh.visible = state.showTv;
    $('#fab-tv').classList.toggle('active', state.showTv);
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
  $('#fab-measure').classList.add('active');
  $('#sel-delete')?.addEventListener('click', deleteSelected);
  $('#sel-dup')?.addEventListener('click', duplicateSelected);
}

async function boot() {
  initThree();
  wireUi();
  renderSidebar();
  await loadMaterials();
  loadDesign();
  showHint('İskelet ekleyerek tasarımına başla', 3200);
}

boot();
