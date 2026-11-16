/**
 * Meemare dolap tasarlama — gömülü oda planı (plan sayfasından bağımsız).
 * Duvar / kapı / pencere aynı araç mantığı: araç seç → yerleştir → Esc/sağ tık ile çık.
 */
import { resizeOpeningSpan } from './wardrobe-layout.js?v=3';

const SNAP = 25;
const WALL_THICK = 12;

function clamp(n, a, b) {
    return Math.min(b, Math.max(a, n));
}

function snap(n) {
    return Math.round(n / SNAP) * SNAP;
}

function wallLen(w) {
    return Math.hypot(w.x2 - w.x1, w.y2 - w.y1);
}

function wallRole(w) {
    const dx = Math.abs(w.x2 - w.x1);
    const dy = Math.abs(w.y2 - w.y1);
    if (dy < 8 && dx >= 40) return 'front';
    if (dx < 8 && dy >= 40) {
        const x = (w.x1 + w.x2) / 2;
        const xs = sketch.walls.filter((item) => Math.abs(item.x2 - item.x1) < 8).map((item) => (item.x1 + item.x2) / 2);
        const minX = Math.min(...xs, x);
        const maxX = Math.max(...xs, x);
        if (Math.abs(x - minX) < 20) return 'left';
        if (Math.abs(x - maxX) < 20) return 'right';
    }
    return w.role || 'other';
}

function assignRoles(walls) {
    walls.forEach((w) => { w.role = wallRole(w); });
}

function buildLShape(front, left, right) {
    const F = snap(front);
    const L = snap(left);
    const R = snap(right);
    return [
        { id: 'front', role: 'front', x1: 0, y1: 0, x2: F, y2: 0 },
        { id: 'left', role: 'left', x1: 0, y1: 0, x2: 0, y2: L },
        { id: 'right', role: 'right', x1: F, y1: 0, x2: F, y2: R },
    ];
}

function buildRect(width, depth) {
    const W = snap(width);
    const D = snap(depth);
    return [
        { id: 'front', role: 'front', x1: 0, y1: 0, x2: W, y2: 0 },
        { id: 'left', role: 'left', x1: 0, y1: 0, x2: 0, y2: D },
        { id: 'right', role: 'right', x1: W, y1: 0, x2: W, y2: D },
        { id: 'back', role: 'other', x1: 0, y1: D, x2: W, y2: D },
    ];
}

/** U: ön + sol + sağ + iki arka kanat (açık orta). */
function buildUShape(front, depth, wing = 120) {
    const F = snap(front);
    const D = snap(depth);
    const W = snap(Math.min(wing, Math.max(80, F * 0.35)));
    return [
        { id: 'front', role: 'front', x1: 0, y1: 0, x2: F, y2: 0 },
        { id: 'left', role: 'left', x1: 0, y1: 0, x2: 0, y2: D },
        { id: 'right', role: 'right', x1: F, y1: 0, x2: F, y2: D },
        { id: 'back-l', role: 'other', x1: 0, y1: D, x2: W, y2: D },
        { id: 'back-r', role: 'other', x1: F - W, y1: D, x2: F, y2: D },
    ];
}

/** T: geniş ön + ortada derin kol. */
function buildTShape(front, depth, stem = 140) {
    const F = snap(front);
    const D = snap(depth);
    const S = snap(Math.min(stem, Math.max(100, F * 0.4)));
    const x0 = snap((F - S) / 2);
    return [
        { id: 'front', role: 'front', x1: 0, y1: 0, x2: F, y2: 0 },
        { id: 'left', role: 'left', x1: 0, y1: 0, x2: 0, y2: snap(D * 0.45) },
        { id: 'right', role: 'right', x1: F, y1: 0, x2: F, y2: snap(D * 0.45) },
        { id: 'shoulder-l', role: 'other', x1: 0, y1: snap(D * 0.45), x2: x0, y2: snap(D * 0.45) },
        { id: 'shoulder-r', role: 'other', x1: x0 + S, y1: snap(D * 0.45), x2: F, y2: snap(D * 0.45) },
        { id: 'stem-l', role: 'other', x1: x0, y1: snap(D * 0.45), x2: x0, y2: D },
        { id: 'stem-r', role: 'other', x1: x0 + S, y1: snap(D * 0.45), x2: x0 + S, y2: D },
        { id: 'back', role: 'other', x1: x0, y1: D, x2: x0 + S, y2: D },
    ];
}

const OPENING_PRESETS = {
    door_single: { kind: 'door', style: 'single', width: 90, height: 210, sill: 0, label: 'Tek 90', sub: '90cm swing' },
    door_double: { kind: 'door', style: 'double', width: 150, height: 210, sill: 0, label: 'Çift 150', sub: '150cm swing' },
    door_sliding: { kind: 'door', style: 'sliding', width: 180, height: 210, sill: 0, label: 'Sürgü 180', sub: '180cm slide' },
    door_french: { kind: 'door', style: 'french', width: 150, height: 210, sill: 0, label: 'French', sub: '150cm glass' },
    door_pocket: { kind: 'door', style: 'pocket', width: 90, height: 210, sill: 0, label: 'Pocket', sub: '90cm recess' },
    door_bifold: { kind: 'door', style: 'bifold', width: 180, height: 210, sill: 0, label: 'Bifold', sub: '180cm fold' },
    door_opening: { kind: 'door', style: 'opening', width: 100, height: 210, sill: 0, label: 'Doorway', sub: '100cm open' },
    door_garage: { kind: 'door', style: 'garage', width: 240, height: 210, sill: 0, label: 'Garage', sub: '240cm overhead' },
    window_std: { kind: 'window', style: 'standard', width: 120, height: 120, sill: 90, label: 'Standard', sub: '120×120cm' },
    window_fixed: { kind: 'window', style: 'fixed', width: 100, height: 100, sill: 90, label: 'Fixed', sub: '100×100cm' },
    window_casement: { kind: 'window', style: 'casement', width: 80, height: 130, sill: 90, label: 'Casement', sub: '80×130cm' },
    window_sliding: { kind: 'window', style: 'sliding', width: 180, height: 120, sill: 90, label: 'Sliding', sub: '180×120cm' },
    window_wide: { kind: 'window', style: 'fixed', width: 180, height: 120, sill: 90, label: 'Wide', sub: '180×120cm' },
    window_bay: { kind: 'window', style: 'bay', width: 200, height: 120, sill: 90, label: 'Bay', sub: '200×120cm' },
};

const sketch = {
    walls: [],
    openings: [],
    tool: 'select',
    openingPreset: 'door_single',
    panX: 0,
    panY: 80,
    zoom: 1.35,
    draft: null,
    drag: null,
    selectedId: null,
    selectedOpeningId: null,
    rectDrag: null,
    hoverOpening: null,
};

let canvas = null;
let ctx = null;
let statusEl = null;
let hintEl = null;
let hooks = null;
let bound = false;

function worldToScreen(x, y) {
    const z = sketch.zoom;
    return { x: sketch.panX + x * z, y: sketch.panY + y * z };
}

function screenToWorld(sx, sy) {
    const z = sketch.zoom;
    return { x: (sx - sketch.panX) / z, y: (sy - sketch.panY) / z };
}

function fmtCm(n) {
    const m = n / 100;
    return m >= 2 ? `${(m).toFixed(2)} m` : `${Math.round(n)} cm`;
}

function openingDefaults(kind) {
    const preset = OPENING_PRESETS[sketch.openingPreset];
    if (preset && preset.kind === kind) {
        return {
            width: preset.width,
            height: preset.height,
            sill: preset.sill,
            style: preset.style,
        };
    }
    if (kind === 'window') return { width: 120, height: 120, sill: 90, style: 'fixed' };
    return { width: 90, height: 210, sill: 0, style: 'single' };
}

function setOpeningPreset(id, { silent = false } = {}) {
    const preset = OPENING_PRESETS[id];
    if (!preset) return;
    sketch.openingPreset = id;
    document.querySelectorAll('[data-opening-preset]').forEach((btn) => {
        btn.classList.toggle('is-on', btn.dataset.openingPreset === id);
    });
    if (preset.kind === 'door' || preset.kind === 'window') {
        setTool(preset.kind, { silent: true });
    }
    if (!silent) hooks?.toast?.(preset.kind === 'door'
        ? `Kapı: ${preset.label} — duvara tıklayın`
        : `Pencere: ${preset.label} — duvara tıklayın`);
    updateHint();
    draw();
}

function hitWall(wx, wy, tol = 22) {
    let best = null;
    sketch.walls.forEach((w) => {
        const dx = w.x2 - w.x1;
        const dy = w.y2 - w.y1;
        const len2 = dx * dx + dy * dy || 1;
        let t = ((wx - w.x1) * dx + (wy - w.y1) * dy) / len2;
        t = clamp(t, 0, 1);
        const px = w.x1 + dx * t;
        const py = w.y1 + dy * t;
        const dist = Math.hypot(wx - px, wy - py);
        if (!best || dist < best.dist) best = { wall: w, t, dist, px, py };
    });
    return best && best.dist < tol / sketch.zoom ? best : null;
}

function hitOpening(wx, wy, tol = 18) {
    let best = null;
    sketch.openings.forEach((item) => {
        const seg = openingSegment(item);
        if (!seg) return;
        const dx = seg.x2 - seg.x1;
        const dy = seg.y2 - seg.y1;
        const len2 = dx * dx + dy * dy || 1;
        let t = ((wx - seg.x1) * dx + (wy - seg.y1) * dy) / len2;
        t = clamp(t, 0, 1);
        const px = seg.x1 + dx * t;
        const py = seg.y1 + dy * t;
        const dist = Math.hypot(wx - px, wy - py);
        if (!best || dist < best.dist) best = { opening: item, dist, px, py };
    });
    return best && best.dist < tol / sketch.zoom ? best : null;
}

function clearSelection() {
    sketch.selectedId = null;
    sketch.selectedOpeningId = null;
    updateSelectionBar();
}

function selectionAnchor() {
    if (sketch.selectedOpeningId) {
        const item = sketch.openings.find((o) => o.id === sketch.selectedOpeningId);
        const seg = item ? openingSegment(item) : null;
        if (seg) {
            return worldToScreen((seg.x1 + seg.x2) / 2, (seg.y1 + seg.y2) / 2);
        }
    }
    if (sketch.selectedId) {
        const wall = sketch.walls.find((w) => w.id === sketch.selectedId);
        if (wall) return worldToScreen((wall.x1 + wall.x2) / 2, (wall.y1 + wall.y2) / 2);
    }
    return null;
}

function selectedOpening() {
    return sketch.openings.find((item) => item.id === sketch.selectedOpeningId) || null;
}

function publishOpenings() {
    hooks?.liveOpenings?.(sketch.openings.map((item) => ({ ...item })));
}

function applyOpeningPatch(item, patch) {
    const wall = sketch.walls.find((entry) => (entry.role || entry.id) === item.wallId || entry.id === item.wallId);
    const len = wall ? wallLen(wall) : 800;
    if (patch.offset != null || patch.width != null) {
        const span = resizeOpeningSpan(
            len,
            patch.offset != null ? patch.offset : item.offset,
            patch.width != null ? patch.width : item.width,
            'move',
            (patch.offset != null ? Number(patch.offset) : Number(item.offset) || 0) + (patch.width != null ? Number(patch.width) : Number(item.width) || 90) / 2,
        );
        item.offset = span.offset;
        item.width = span.width;
    }
    if (patch.height != null) item.height = clamp(Math.round(patch.height), 40, 300);
    if (patch.sill != null) item.sill = clamp(Math.round(patch.sill), 0, 220);
    publishOpenings();
}

function fillOpeningFields() {
    const item = selectedOpening();
    document.querySelectorAll('[data-opening-size]').forEach((node) => {
        node.hidden = !item || (node.dataset.openingSize === 'sill' && item.kind !== 'window');
    });
    if (!item) return;
    const width = document.getElementById('opening-width');
    const height = document.getElementById('opening-height');
    const sill = document.getElementById('opening-sill');
    if (width && document.activeElement !== width) width.value = Math.round(Number(item.width) || 90);
    if (height && document.activeElement !== height) height.value = Math.round(Number(item.height) || (item.kind === 'window' ? 120 : 210));
    if (sill && document.activeElement !== sill) sill.value = Math.round(Number(item.sill) || 0);
}

function updateSelectionBar() {
    const bar = document.getElementById('room-sketch-selbar');
    if (!bar) return;
    const anchor = selectionAnchor();
    const has = Boolean(anchor);
    bar.classList.toggle('is-visible', has);
    bar.setAttribute('aria-hidden', has ? 'false' : 'true');
    fillOpeningFields();
    if (!has) return;
    const stage = document.querySelector('.room-sketch-stage');
    const rect = stage?.getBoundingClientRect();
    const w = bar.offsetWidth || 112;
    const left = clamp(anchor.x - w / 2, 8, (rect?.width || 400) - w - 8);
    const top = clamp(anchor.y - 48, 8, (rect?.height || 400) - 40);
    bar.style.left = `${left}px`;
    bar.style.top = `${top}px`;
}

function deleteSelection() {
    if (sketch.selectedOpeningId) {
        sketch.openings = sketch.openings.filter((o) => o.id !== sketch.selectedOpeningId);
        sketch.selectedOpeningId = null;
        hooks?.scheduleSave?.();
        draw();
        paintStatus();
        updateSelectionBar();
        return true;
    }
    if (sketch.selectedId) {
        const idx = sketch.walls.findIndex((w) => w.id === sketch.selectedId);
        if (idx >= 0) {
            const removed = sketch.walls[idx];
            sketch.walls.splice(idx, 1);
            sketch.openings = sketch.openings.filter(
                (o) => o.wallId !== removed.id && o.wallId !== (removed.role || ''),
            );
            sketch.selectedId = null;
            syncFormFields();
            hooks?.scheduleSave?.();
            draw();
            paintStatus();
            updateSelectionBar();
            return true;
        }
    }
    return false;
}

function duplicateSelection() {
    if (sketch.selectedOpeningId) {
        const src = sketch.openings.find((o) => o.id === sketch.selectedOpeningId);
        if (!src) return;
        const wall = sketch.walls.find((w) => (w.role || w.id) === src.wallId || w.id === src.wallId);
        const len = wall ? wallLen(wall) : 400;
        const copy = {
            ...src,
            id: `o${Date.now()}`,
            offset: clamp((Number(src.offset) || 0) + (Number(src.width) || 90) + 10, 0, Math.max(0, len - (Number(src.width) || 90))),
        };
        sketch.openings.push(copy);
        sketch.selectedOpeningId = copy.id;
        hooks?.scheduleSave?.();
        draw();
        paintStatus();
        updateSelectionBar();
        return;
    }
    if (sketch.selectedId) {
        const wall = sketch.walls.find((w) => w.id === sketch.selectedId);
        if (!wall) return;
        const dx = wall.x2 - wall.x1;
        const dy = wall.y2 - wall.y1;
        const len = Math.hypot(dx, dy) || 1;
        const ox = (-dy / len) * 30;
        const oy = (dx / len) * 30;
        const copy = {
            id: `w${Date.now()}`,
            x1: wall.x1 + ox,
            y1: wall.y1 + oy,
            x2: wall.x2 + ox,
            y2: wall.y2 + oy,
        };
        sketch.walls.push(copy);
        assignRoles(sketch.walls);
        sketch.selectedId = copy.id;
        syncFormFields();
        hooks?.scheduleSave?.();
        draw();
        paintStatus();
        updateSelectionBar();
    }
}

function flipSelection() {
    if (sketch.selectedOpeningId) {
        const item = sketch.openings.find((o) => o.id === sketch.selectedOpeningId);
        if (!item) return;
        const wall = sketch.walls.find((w) => (w.role || w.id) === item.wallId || w.id === item.wallId);
        if (!wall) return;
        const len = wallLen(wall);
        item.offset = Math.max(0, Math.round(len - (Number(item.offset) || 0) - (Number(item.width) || 90)));
        if (item.style === 'single') item.swing = item.swing === 'left' ? 'right' : 'left';
        hooks?.scheduleSave?.();
        draw();
        paintStatus();
        updateSelectionBar();
        return;
    }
    if (sketch.selectedId) {
        const wall = sketch.walls.find((w) => w.id === sketch.selectedId);
        if (!wall) return;
        const { x1, y1, x2, y2 } = wall;
        wall.x1 = x2;
        wall.y1 = y2;
        wall.x2 = x1;
        wall.y2 = y1;
        assignRoles(sketch.walls);
        syncFormFields();
        hooks?.scheduleSave?.();
        draw();
        paintStatus();
        updateSelectionBar();
    }
}

function syncDimensions() {
    assignRoles(sketch.walls);
    const front = sketch.walls.find((w) => w.role === 'front');
    const left = sketch.walls.find((w) => w.role === 'left');
    const right = sketch.walls.find((w) => w.role === 'right');
    const frontCm = front ? Math.round(wallLen(front)) : null;
    const leftCm = left ? Math.round(wallLen(left)) : null;
    const rightCm = right ? Math.round(wallLen(right)) : null;
    return { frontCm, leftCm, rightCm };
}

function pushOpening(wall, t, kind) {
    const len = wallLen(wall);
    if (len < 60) return false;
    const defs = openingDefaults(kind);
    const offset = clamp(Math.round(t * len - defs.width / 2), 0, Math.max(0, len - defs.width));
    const role = wall.role || wallRole(wall);
    const wallKey = role === 'front' || role === 'left' || role === 'right' ? role : wall.id;
    const idx = sketch.openings.findIndex(
        (o) => o.wallId === wallKey && Math.abs(o.offset - offset) < defs.width * 0.65 && o.kind === kind,
    );
    if (idx >= 0) {
        sketch.openings.splice(idx, 1);
        return true;
    }
    const created = {
        id: `o${Date.now()}`,
        wallId: wallKey,
        kind,
        offset,
        width: defs.width,
        height: defs.height,
        sill: defs.sill,
        style: defs.style || (kind === 'window' ? 'fixed' : 'single'),
    };
    sketch.openings.push(created);
    sketch.selectedOpeningId = created.id;
    sketch.selectedId = null;
    return true;
}

function exportToStudio() {
    const dims = syncDimensions();
    const openings = sketch.openings.map((o) => ({ ...o }));
    return {
        walls: sketch.walls.map((w) => ({ ...w })),
        openings,
        ...dims,
    };
}

function loadFromStudio(state) {
    const rs = state?.roomSketch;
    if (rs?.walls?.length) {
        sketch.walls = rs.walls.map((w, i) => ({ ...w, id: w.id || `w${i}` }));
        sketch.openings = (rs.openings || []).map((o) => ({ ...o }));
    } else {
        const front = state?.wallWidth || 300;
        const left = state?.leftWall || 300;
        const right = state?.rightWall || 300;
        sketch.walls = buildLShape(front, left, right);
        sketch.openings = (rs?.openings || []).map((o) => ({ ...o }));
    }
    assignRoles(sketch.walls);
    cancelDraft();
    syncFormFields();
    draw();
    paintStatus();
}

function syncFormFields() {
    const dims = syncDimensions();
    const form = document.getElementById('studio-plan-handoff');
    if (!form) return dims;
    if (dims.frontCm && form.onduvar) form.onduvar.value = dims.frontCm;
    if (dims.leftCm && form.solduvar) form.solduvar.value = dims.leftCm;
    if (dims.rightCm && form.sagduvar) form.sagduvar.value = dims.rightCm;
    return dims;
}

function applyToStudio() {
    if (!hooks) return false;
    const data = exportToStudio();
    syncFormFields();
    const sketchState = hooks.ensureSketch();
    sketchState.walls = data.walls;
    sketchState.openings = data.openings;
    sketchState.openingPreset = sketch.openingPreset;
    const form = document.getElementById('studio-plan-handoff');
    const height = Number(form?.odaboy?.value);
    const ok = hooks.applyWallsCm(data.frontCm, data.leftCm, data.rightCm, Number.isFinite(height) ? height : null);
    if (ok) hooks.scheduleSave?.();
    return ok;
}

function toolHint(tool = sketch.tool) {
    if (tool === 'wall') {
        return sketch.draft
            ? 'İkinci noktayı tıklayın. Esc / sağ tık / «Çizimi bitir» ile iptal.'
            : 'İlk noktayı tıklayın, sonra bitiş noktası. Esc ile çıkın.';
    }
    if (tool === 'door') {
        const p = OPENING_PRESETS[sketch.openingPreset];
        return `Kapı (${p?.label || 'Tek 90'}): duvara tıklayın. Esc = seç.`;
    }
    if (tool === 'window') {
        const p = OPENING_PRESETS[sketch.openingPreset];
        return `Pencere (${p?.label || '120'}): duvara tıklayın. Esc = seç.`;
    }
    if (tool === 'rect') return 'Sürükleyerek dikdörtgen oda çizin.';
    return 'Şablon seçin veya Duvar / Kapı / Pencere aracı. Esc çizimi iptal eder.';
}

function updateHint() {
    if (!hintEl) hintEl = document.getElementById('room-sketch-tool-hint');
    if (hintEl) hintEl.textContent = toolHint();
    if (canvas) {
        canvas.style.cursor = sketch.tool === 'select' ? 'grab' : 'crosshair';
    }
}

function cancelDraft() {
    sketch.draft = null;
    sketch.rectDrag = null;
    sketch.hoverOpening = null;
    sketch.drag = null;
}

/** Çizimden çık: taslak varsa iptal, yoksa Seç aracına dön. */
function finishDrawing({ forceSelect = false } = {}) {
    const hadDraft = Boolean(sketch.draft || sketch.rectDrag);
    cancelDraft();
    if (forceSelect || !hadDraft || sketch.tool === 'wall' || sketch.tool === 'door' || sketch.tool === 'window') {
        if (forceSelect || sketch.tool !== 'select') setTool('select', { silent: true });
    }
    draw();
    paintStatus();
    updateHint();
    return hadDraft;
}

function setTool(tool, { silent = false } = {}) {
    sketch.tool = tool;
    cancelDraft();
    document.querySelectorAll('[data-sketch-tool]').forEach((btn) => {
        btn.classList.toggle('is-on', btn.dataset.sketchTool === tool);
    });
    updateHint();
    draw();
    if (!silent && tool === 'wall') hooks?.toast?.('Duvar: iki tık = bir duvar. Esc veya sağ tık ile çıkın.');
    if (!silent && tool === 'door') hooks?.toast?.('Kapı: duvara tıklayın. Esc ile çıkın.');
    if (!silent && tool === 'window') hooks?.toast?.('Pencere: duvara tıklayın. Esc ile çıkın.');
}

function applyPreset(builder) {
    const form = document.getElementById('studio-plan-handoff');
    const front = Number(form?.onduvar?.value) || 400;
    const left = Number(form?.solduvar?.value) || 300;
    const right = Number(form?.sagduvar?.value) || 300;
    sketch.walls = builder(front, left, right);
    sketch.openings = [];
    assignRoles(sketch.walls);
    cancelDraft();
    setTool('select', { silent: true });
    syncFormFields();
    draw();
    paintStatus();
    hooks?.toast?.('Oda şablonu yerleştirildi. Kapı/pencere ekleyebilirsiniz.');
}

function drawGrid(w, h) {
    const z = sketch.zoom;
    const step = SNAP * z;
    if (step < 14) return;
    ctx.strokeStyle = 'rgba(60,58,54,0.08)';
    ctx.lineWidth = 1;
    const wx0 = screenToWorld(0, 0).x;
    const wy0 = screenToWorld(0, 0).y;
    const wx1 = screenToWorld(w, h).x;
    const wy1 = screenToWorld(w, h).y;
    const startX = Math.floor(wx0 / SNAP) * SNAP;
    const startY = Math.floor(wy0 / SNAP) * SNAP;
    for (let x = startX; x <= wx1; x += SNAP) {
        const s = worldToScreen(x, 0);
        ctx.beginPath();
        ctx.moveTo(s.x, 0);
        ctx.lineTo(s.x, h);
        ctx.stroke();
    }
    for (let y = startY; y <= wy1; y += SNAP) {
        const s = worldToScreen(0, y);
        ctx.beginPath();
        ctx.moveTo(0, s.y);
        ctx.lineTo(w, s.y);
        ctx.stroke();
    }
}

function drawWallSegment(w, highlight) {
    const a = worldToScreen(w.x1, w.y1);
    const b = worldToScreen(w.x2, w.y2);
    const dx = b.x - a.x;
    const dy = b.y - a.y;
    const len = Math.hypot(dx, dy) || 1;
    const nx = (-dy / len) * (WALL_THICK * 0.45);
    const ny = (dx / len) * (WALL_THICK * 0.45);
    ctx.beginPath();
    ctx.moveTo(a.x + nx, a.y + ny);
    ctx.lineTo(b.x + nx, b.y + ny);
    ctx.lineTo(b.x - nx, b.y - ny);
    ctx.lineTo(a.x - nx, a.y - ny);
    ctx.closePath();
    ctx.fillStyle = highlight ? '#9ec5e8' : '#2c2a27';
    ctx.fill();
    ctx.strokeStyle = highlight ? '#2f6fad' : '#1a1917';
    ctx.lineWidth = highlight ? 2 : 1;
    ctx.stroke();
    if (highlight) {
        const midX = (a.x + b.x) / 2;
        const midY = (a.y + b.y) / 2;
        ctx.fillStyle = '#2f6fad';
        ctx.fillRect(midX - 4, midY - 4, 8, 8);
        ctx.fillStyle = '#fff';
        ctx.beginPath();
        ctx.arc(a.x, a.y, 5, 0, Math.PI * 2);
        ctx.arc(b.x, b.y, 5, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#2f6fad';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.arc(a.x, a.y, 5, 0, Math.PI * 2);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(b.x, b.y, 5, 0, Math.PI * 2);
        ctx.stroke();
    }
    if (len > 40 * sketch.zoom) {
        const midX = (a.x + b.x) / 2;
        const midY = (a.y + b.y) / 2 + (highlight ? 16 : 0);
        ctx.save();
        ctx.font = '11px Montserrat, system-ui, sans-serif';
        ctx.fillStyle = '#5e5953';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        const label = fmtCm(wallLen(w));
        const wide = ctx.measureText(label).width + 8;
        ctx.fillStyle = '#f3f1ec';
        ctx.fillRect(midX - wide / 2, midY - 8, wide, 16);
        ctx.fillStyle = '#5e5953';
        ctx.fillText(label, midX, midY);
        ctx.restore();
    }
}

function openingSegment(item) {
    const wall = sketch.walls.find((w) => (w.role || w.id) === item.wallId || w.id === item.wallId);
    if (!wall) return null;
    const len = wallLen(wall);
    if (len < 1) return null;
    const t0 = (Number(item.offset) || 0) / len;
    const t1 = t0 + (Number(item.width) || 90) / len;
    return {
        wall,
        x1: wall.x1 + (wall.x2 - wall.x1) * t0,
        y1: wall.y1 + (wall.y2 - wall.y1) * t0,
        x2: wall.x1 + (wall.x2 - wall.x1) * t1,
        y2: wall.y1 + (wall.y2 - wall.y1) * t1,
        kind: item.kind,
        style: item.style || 'single',
    };
}

function strokeOpening(seg, color, width = 3) {
    if (!seg) return;
    const a = worldToScreen(seg.x1, seg.y1);
    const b = worldToScreen(seg.x2, seg.y2);
    const dx = b.x - a.x;
    const dy = b.y - a.y;
    const len = Math.hypot(dx, dy) || 1;
    const tx = dx / len;
    const ty = dy / len;
    // Odaya doğru normal (plan: +Y genelde içeri)
    let nx = -ty;
    let ny = tx;
    const midX = (seg.x1 + seg.x2) / 2;
    const midY = (seg.y1 + seg.y2) / 2;
    if (midY + ny * 8 < midY) {
        nx = -nx;
        ny = -ny;
    }
    ctx.strokeStyle = color;
    ctx.lineWidth = width;
    ctx.beginPath();
    ctx.moveTo(a.x, a.y);
    ctx.lineTo(b.x, b.y);
    ctx.stroke();
    if (seg.kind === 'door') {
        const style = seg.style || 'single';
        const hinge = a;
        const leafEnd = {
            x: hinge.x + nx * len,
            y: hinge.y + ny * len,
        };
        ctx.beginPath();
        ctx.moveTo(hinge.x, hinge.y);
        ctx.lineTo(leafEnd.x, leafEnd.y);
        ctx.stroke();
        const start = Math.atan2(ty, tx);
        const end = Math.atan2(ny, nx);
        ctx.beginPath();
        ctx.arc(hinge.x, hinge.y, len, start, end, false);
        ctx.stroke();
        if (style === 'double') {
            const hinge2 = b;
            ctx.beginPath();
            ctx.moveTo(hinge2.x, hinge2.y);
            ctx.lineTo(hinge2.x + nx * len * 0.5, hinge2.y + ny * len * 0.5);
            ctx.stroke();
        }
    } else {
        const ox = nx * 3;
        const oy = ny * 3;
        ctx.beginPath();
        ctx.moveTo(a.x + ox, a.y + oy);
        ctx.lineTo(b.x + ox, b.y + oy);
        ctx.moveTo(a.x + ox * 2, a.y + oy * 2);
        ctx.lineTo(b.x + ox * 2, b.y + oy * 2);
        ctx.stroke();
    }
}

function alongWall(wall, wx, wy) {
    const dx = wall.x2 - wall.x1;
    const dy = wall.y2 - wall.y1;
    const len = Math.hypot(dx, dy) || 1;
    const t = clamp(((wx - wall.x1) * dx + (wy - wall.y1) * dy) / (len * len), 0, 1);
    return t * len;
}

function hitOpeningHandle(wx, wy) {
    const item = selectedOpening();
    const seg = item ? openingSegment(item) : null;
    if (!seg) return null;
    const tol = 16 / sketch.zoom;
    const start = Math.hypot(wx - seg.x1, wy - seg.y1);
    const end = Math.hypot(wx - seg.x2, wy - seg.y2);
    if (start <= tol && start <= end) return 'start';
    if (end <= tol) return 'end';
    return null;
}

function drawOpenings() {
    sketch.openings.forEach((item) => {
        const seg = openingSegment(item);
        const selected = item.id === sketch.selectedOpeningId;
        strokeOpening(seg, item.kind === 'door' ? '#3c463f' : '#6d8ea8', selected ? 4 : 3);
        if (!selected || !seg) return;
        [seg.x1, seg.x2].forEach((x, index) => {
            const y = index === 0 ? seg.y1 : seg.y2;
            const p = worldToScreen(x, y);
            ctx.beginPath();
            ctx.fillStyle = '#fff';
            ctx.strokeStyle = '#1f6b4a';
            ctx.lineWidth = 2;
            ctx.arc(p.x, p.y, 7, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();
        });
        const mid = worldToScreen((seg.x1 + seg.x2) / 2, (seg.y1 + seg.y2) / 2);
        const label = `${Math.round(item.width)}×${Math.round(item.height || (item.kind === 'window' ? 120 : 210))}`;
        ctx.font = '11px Montserrat, system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        const wide = ctx.measureText(label).width + 10;
        ctx.fillStyle = '#1c1c1c';
        ctx.fillRect(mid.x - wide / 2, mid.y - 18, wide, 16);
        ctx.fillStyle = '#fff';
        ctx.fillText(label, mid.x, mid.y - 10);
    });
    if (sketch.hoverOpening) {
        const defs = openingDefaults(sketch.hoverOpening.kind);
        const wall = sketch.hoverOpening.wall;
        const len = wallLen(wall);
        const offset = clamp(Math.round(sketch.hoverOpening.t * len - defs.width / 2), 0, Math.max(0, len - defs.width));
        const fake = {
            wallId: wall.id,
            kind: sketch.hoverOpening.kind,
            offset,
            width: defs.width,
        };
        // geçici segment — wallId rol değil id; doğrudan hesapla
        const t0 = offset / len;
        const t1 = t0 + defs.width / len;
        strokeOpening({
            wall,
            kind: sketch.hoverOpening.kind,
            style: defs.style,
            x1: wall.x1 + (wall.x2 - wall.x1) * t0,
            y1: wall.y1 + (wall.y2 - wall.y1) * t0,
            x2: wall.x1 + (wall.x2 - wall.x1) * t1,
            y2: wall.y1 + (wall.y2 - wall.y1) * t1,
        }, sketch.hoverOpening.kind === 'door' ? '#1f6b4a' : '#3a7ca5', 4);
        void fake;
    }
}

function drawFloorFill() {
    if (sketch.walls.length < 3) return;
    const xs = sketch.walls.flatMap((w) => [w.x1, w.x2]);
    const ys = sketch.walls.flatMap((w) => [w.y1, w.y2]);
    const minX = Math.min(...xs);
    const maxX = Math.max(...xs);
    const minY = Math.min(...ys);
    const maxY = Math.max(...ys);
    const p0 = worldToScreen(minX, minY);
    const p1 = worldToScreen(maxX, maxY);
    ctx.fillStyle = 'rgba(212, 195, 168, 0.35)';
    ctx.fillRect(p0.x, p0.y, p1.x - p0.x, p1.y - p0.y);
}

function draw() {
    if (!ctx || !canvas) return;
    const w = canvas.clientWidth;
    const h = canvas.clientHeight;
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.max(1, Math.round(w * ratio));
    canvas.height = Math.max(1, Math.round(h * ratio));
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.fillStyle = '#f3f1ec';
    ctx.fillRect(0, 0, w, h);
    drawGrid(w, h);
    drawFloorFill();
    sketch.walls.forEach((wall) => drawWallSegment(wall, wall.id === sketch.selectedId));
    if (sketch.draft) drawWallSegment(sketch.draft, true);
    if (sketch.rectDrag) {
        const { x0, y0, x1, y1 } = sketch.rectDrag;
        const a = worldToScreen(Math.min(x0, x1), Math.min(y0, y1));
        const b = worldToScreen(Math.max(x0, x1), Math.max(y0, y1));
        ctx.strokeStyle = '#1f6b4a';
        ctx.lineWidth = 2;
        ctx.setLineDash([6, 4]);
        ctx.strokeRect(a.x, a.y, b.x - a.x, b.y - a.y);
        ctx.setLineDash([]);
    }
    drawOpenings();
    updateSelectionBar();
}

function paintStatus() {
    if (!statusEl) return;
    const dims = syncDimensions();
    const area = dims.frontCm && dims.leftCm ? ((dims.frontCm * dims.leftCm) / 10000).toFixed(1) : '—';
    const nWall = sketch.walls.filter((w) => wallLen(w) > 20).length;
    const toolLabel = ({ select: 'Seç', wall: 'Duvar', door: 'Kapı', window: 'Pencere', rect: 'Dikdörtgen' })[sketch.tool] || sketch.tool;
    statusEl.innerHTML = `
        <span><strong>${toolLabel}</strong></span>
        <span>${nWall} duvar · ${sketch.openings.length} açıklık</span>
        <span>${area} m² (yaklaşık)</span>
        <span>Yakalama ${SNAP} cm</span>
        <span class="room-sketch-zoom">
            <button type="button" data-zoom="-">−</button>
            <span>${Math.round(sketch.zoom * 100)}%</span>
            <button type="button" data-zoom="+">+</button>
        </span>`;
    updateHint();
}

function pointerPos(event) {
    const rect = canvas.getBoundingClientRect();
    return {
        x: (event.clientX - rect.left) * (canvas.clientWidth / rect.width),
        y: (event.clientY - rect.top) * (canvas.clientHeight / rect.height),
    };
}

function placePresetCentered(walls) {
    // canvas merkezine kaydır
    const xs = walls.flatMap((w) => [w.x1, w.x2]);
    const ys = walls.flatMap((w) => [w.y1, w.y2]);
    const cx = (Math.min(...xs) + Math.max(...xs)) / 2;
    const cy = (Math.min(...ys) + Math.max(...ys)) / 2;
    const vw = canvas?.clientWidth || 800;
    const vh = canvas?.clientHeight || 500;
    sketch.panX = vw / 2 - cx * sketch.zoom;
    sketch.panY = vh / 2 - cy * sketch.zoom;
}

function bindCanvas() {
    if (!canvas || canvas.dataset.sketchBound) return;
    canvas.dataset.sketchBound = '1';

    canvas.addEventListener('wheel', (event) => {
        event.preventDefault();
        const factor = event.deltaY > 0 ? 0.92 : 1.08;
        sketch.zoom = clamp(sketch.zoom * factor, 0.45, 4);
        draw();
        paintStatus();
    }, { passive: false });

    canvas.addEventListener('contextmenu', (event) => {
        event.preventDefault();
        finishDrawing({ forceSelect: true });
        hooks?.toast?.('Çizim iptal · Seç aracına dönüldü.');
    });

    canvas.addEventListener('pointerdown', (event) => {
        if (event.button === 2) {
            finishDrawing({ forceSelect: true });
            return;
        }
        if (event.button !== 0) return;

        const pt = pointerPos(event);
        const world = screenToWorld(pt.x, pt.y);
        const wx = snap(world.x);
        const wy = snap(world.y);

        // Duvar: iki tık = bir segment, sonra taslak kapanır (sürekli zincir yok).
        if (sketch.tool === 'wall') {
            if (!sketch.draft) {
                sketch.draft = { x1: wx, y1: wy, x2: wx, y2: wy };
                updateHint();
                canvas.setPointerCapture(event.pointerId);
                draw();
                paintStatus();
                return;
            }
            sketch.draft.x2 = wx;
            sketch.draft.y2 = wy;
            if (wallLen(sketch.draft) >= SNAP) {
                sketch.walls.push({
                    id: `w${Date.now()}`,
                    x1: sketch.draft.x1,
                    y1: sketch.draft.y1,
                    x2: sketch.draft.x2,
                    y2: sketch.draft.y2,
                });
                assignRoles(sketch.walls);
                syncFormFields();
            }
            sketch.draft = null;
            updateHint();
            draw();
            paintStatus();
            return;
        }

        // Kapı / pencere: duvara tıkla (duvarla aynı araç döngüsü).
        if (sketch.tool === 'door' || sketch.tool === 'window') {
            const hit = hitWall(world.x, world.y);
            if (!hit) {
                hooks?.toast?.('Önce bir duvarın üzerine tıklayın.');
                return;
            }
            assignRoles(sketch.walls);
            pushOpening(hit.wall, hit.t, sketch.tool);
            sketch.hoverOpening = null;
            draw();
            paintStatus();
            hooks?.scheduleSave?.();
            return;
        }

        if (sketch.tool === 'rect') {
            sketch.rectDrag = { x0: wx, y0: wy, x1: wx, y1: wy };
            canvas.setPointerCapture(event.pointerId);
            return;
        }

        const handle = hitOpeningHandle(world.x, world.y);
        if (handle) {
            const item = selectedOpening();
            sketch.drag = { kind: 'opening', edge: handle, id: item.id };
            canvas.setPointerCapture(event.pointerId);
            draw();
            return;
        }
        const openHit = hitOpening(world.x, world.y);
        if (openHit) {
            sketch.selectedOpeningId = openHit.opening.id;
            sketch.selectedId = null;
            const openingWall = sketch.walls.find((entry) => (entry.role || entry.id) === openHit.opening.wallId || entry.id === openHit.opening.wallId);
            const grab = openingWall ? alongWall(openingWall, world.x, world.y) - (Number(openHit.opening.offset) || 0) : (Number(openHit.opening.width) || 90) / 2;
            sketch.drag = { kind: 'opening', edge: 'move', id: openHit.opening.id, grab };
            canvas.setPointerCapture(event.pointerId);
            draw();
            updateSelectionBar();
            return;
        }
        const hit = hitWall(world.x, world.y);
        if (hit) {
            sketch.selectedId = hit.wall.id;
            sketch.selectedOpeningId = null;
            sketch.drag = { kind: 'pan', x: pt.x, y: pt.y, panX: sketch.panX, panY: sketch.panY };
            if (event.altKey && wallLen(hit.wall) > SNAP) {
                const idx = sketch.walls.findIndex((w) => w.id === hit.wall.id);
                if (idx >= 0) {
                    const removed = sketch.walls[idx];
                    sketch.walls.splice(idx, 1);
                    sketch.openings = sketch.openings.filter(
                        (o) => o.wallId !== removed.id && o.wallId !== (removed.role || ''),
                    );
                }
                clearSelection();
            }
            canvas.setPointerCapture(event.pointerId);
            draw();
            return;
        }
        clearSelection();
        sketch.drag = { kind: 'pan', x: pt.x, y: pt.y, panX: sketch.panX, panY: sketch.panY };
        canvas.setPointerCapture(event.pointerId);
        draw();
    });

    canvas.addEventListener('pointermove', (event) => {
        const pt = pointerPos(event);
        const world = screenToWorld(pt.x, pt.y);
        const wx = snap(world.x);
        const wy = snap(world.y);

        if (sketch.draft) {
            sketch.draft.x2 = wx;
            sketch.draft.y2 = wy;
            draw();
            return;
        }
        if (sketch.rectDrag) {
            sketch.rectDrag.x1 = wx;
            sketch.rectDrag.y1 = wy;
            draw();
            return;
        }
        if (sketch.tool === 'door' || sketch.tool === 'window') {
            const hit = hitWall(world.x, world.y);
            sketch.hoverOpening = hit ? { wall: hit.wall, t: hit.t, kind: sketch.tool } : null;
            draw();
            return;
        }
        if (!sketch.drag) return;
        if (sketch.drag.kind === 'opening') {
            const item = sketch.openings.find((entry) => entry.id === sketch.drag.id);
            const wall = item && sketch.walls.find((entry) => (entry.role || entry.id) === item.wallId || entry.id === item.wallId);
            if (item && wall) {
                const cursor = alongWall(wall, world.x, world.y);
                const at = sketch.drag.edge === 'move' ? cursor - (Number(sketch.drag.grab) || 0) + (Number(item.width) || 0) / 2 : cursor;
                const next = resizeOpeningSpan(wallLen(wall), item.offset, item.width, sketch.drag.edge, at);
                item.offset = next.offset;
                item.width = next.width;
                publishOpenings();
                fillOpeningFields();
            }
            draw();
            updateSelectionBar();
            return;
        }
        if (sketch.drag.kind === 'pan') {
            sketch.panX = sketch.drag.panX + (pt.x - sketch.drag.x);
            sketch.panY = sketch.drag.panY + (pt.y - sketch.drag.y);
            draw();
        }
    });

    canvas.addEventListener('pointerup', (event) => {
        if (sketch.rectDrag) {
            const { x0, y0, x1, y1 } = sketch.rectDrag;
            const minX = Math.min(x0, x1);
            const minY = Math.min(y0, y1);
            const W = Math.abs(x1 - x0);
            const D = Math.abs(y1 - y0);
            if (W >= SNAP * 2 && D >= SNAP * 2) {
                sketch.walls = buildRect(W, D).map((w) => ({
                    ...w,
                    x1: w.x1 + minX,
                    y1: w.y1 + minY,
                    x2: w.x2 + minX,
                    y2: w.y2 + minY,
                }));
                assignRoles(sketch.walls);
                syncFormFields();
            }
            sketch.rectDrag = null;
            setTool('select', { silent: true });
            draw();
            paintStatus();
        }
        sketch.drag = null;
        try { canvas.releasePointerCapture(event.pointerId); } catch { /* noop */ }
    });

    canvas.addEventListener('dblclick', (event) => {
        event.preventDefault();
        finishDrawing({ forceSelect: true });
    });
}

function onToolbarClick(event) {
    const target = event.target.closest('button, [data-sketch-tool], [data-opening-preset]');
    if (!target) return;

    const preset = target.dataset?.openingPreset || target.closest?.('[data-opening-preset]')?.dataset?.openingPreset;
    if (preset) {
        event.preventDefault();
        setOpeningPreset(preset);
        return;
    }

    const tool = target.dataset?.sketchTool || target.closest?.('[data-sketch-tool]')?.dataset?.sketchTool;
    if (tool) {
        event.preventDefault();
        if (tool === 'door' && !String(sketch.openingPreset).startsWith('door_')) {
            sketch.openingPreset = 'door_single';
        }
        if (tool === 'window' && !String(sketch.openingPreset).startsWith('window_')) {
            sketch.openingPreset = 'window_std';
        }
        document.querySelectorAll('[data-opening-preset]').forEach((btn) => {
            btn.classList.toggle('is-on', btn.dataset.openingPreset === sketch.openingPreset);
        });
        setTool(tool);
        return;
    }

    const id = target.id;
    if (id === 'btn-sketch-finish-draw') {
        finishDrawing({ forceSelect: true });
        hooks?.toast?.('Çizim bitti · Seç aracındasınız.');
        return;
    }
    if (id === 'btn-sketch-rect') {
        const form = document.getElementById('studio-plan-handoff');
        const front = Number(form?.onduvar?.value) || 400;
        const depth = Number(form?.solduvar?.value) || 300;
        sketch.walls = buildRect(front, depth);
        sketch.openings = [];
        assignRoles(sketch.walls);
        placePresetCentered(sketch.walls);
        setTool('select', { silent: true });
        syncFormFields();
        draw();
        paintStatus();
        hooks?.toast?.('Dikdörtgen oda yerleştirildi.');
        return;
    }
    if (id === 'btn-sketch-l') {
        applyPreset((front, left, right) => buildLShape(front, left, right));
        placePresetCentered(sketch.walls);
        draw();
        return;
    }
    if (id === 'btn-sketch-u') {
        applyPreset((front, left) => buildUShape(front, left));
        placePresetCentered(sketch.walls);
        draw();
        return;
    }
    if (id === 'btn-sketch-t') {
        applyPreset((front, left) => buildTShape(front, left));
        placePresetCentered(sketch.walls);
        draw();
        return;
    }
    if (id === 'btn-sketch-clear') {
        sketch.walls = [];
        sketch.openings = [];
        cancelDraft();
        setTool('select', { silent: true });
        draw();
        paintStatus();
        return;
    }
    if (id === 'btn-room-redraw') {
        sketch.walls = [];
        sketch.openings = [];
        cancelDraft();
        setTool('wall');
        paintStatus();
        hooks?.toast?.('Duvar: iki tık = bir duvar. Esc / sağ tık / «Çizimi bitir» ile çıkın.');
        return;
    }
    if (id === 'btn-sketch-apply') {
        if (applyToStudio()) {
            hooks?.toggleRoomPlan?.(false);
            hooks?.toast?.('Oda çizimi 3D sahneye uygulandı.');
        } else hooks?.toast?.('Ön / sol / sağ duvar tanımlayın (L veya dikdörtgen şablon).');
    }
}

function bindToolbar() {
    // Delegasyon: plan paneli CSS/DOM ile taşınsa da çalışır.
    document.addEventListener('click', (event) => {
        const sizeInput = event.target.closest?.('[data-opening-size] input');
        if (sizeInput) return;
        const selAct = event.target.closest?.('[data-sketch-sel]');
        if (selAct) {
            event.preventDefault();
            const act = selAct.dataset.sketchSel;
            if (act === 'delete') deleteSelection();
            else if (act === 'copy') duplicateSelection();
            else if (act === 'flip') flipSelection();
            return;
        }
        const inTools = event.target.closest?.('.room-sketch-tools, .room-sketch-head, #room-sketch-status');
        if (!inTools) return;
        if (event.target.closest?.('[data-zoom]')) {
            const zoomBtn = event.target.closest('[data-zoom]');
            sketch.zoom = clamp(sketch.zoom * (zoomBtn.dataset.zoom === '+' ? 1.12 : 0.9), 0.45, 4);
            draw();
            paintStatus();
            return;
        }
        onToolbarClick(event);
    });
}

export function bindRoomSketch(deps) {
    hooks = deps;
    canvas = document.getElementById('room-sketch-canvas');
    statusEl = document.getElementById('room-sketch-status');
    hintEl = document.getElementById('room-sketch-tool-hint');
    if (!canvas) return;
    ctx = canvas.getContext('2d');
    window.__sketchSetTool = (tool) => setTool(tool);
    window.__sketchFinish = () => finishDrawing({ forceSelect: true });
    if (bound) {
        // hooks yenilenebilir; tuval zaten bağlı
        return;
    }
    bound = true;
    bindCanvas();
    bindToolbar();
    ['opening-width', 'opening-height', 'opening-sill'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', (event) => {
            const item = selectedOpening();
            const value = Number(event.target.value);
            if (!item || !Number.isFinite(value)) return;
            if (id === 'opening-width') applyOpeningPatch(item, { width: value });
            if (id === 'opening-height') applyOpeningPatch(item, { height: value });
            if (id === 'opening-sill') applyOpeningPatch(item, { sill: value });
            draw();
        });
    });
    setTool('select', { silent: true });
    window.addEventListener('resize', () => { draw(); paintStatus(); });
    window.addEventListener('keydown', (event) => {
        if (!canvas || !(document.body.classList.contains('show-room-plan') || document.body.classList.contains('studio-plan-first'))) return;
        const tag = String(event.target?.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || event.target?.isContentEditable) return;
        const key = String(event.key || '').toLowerCase();
        if (key === 'escape') {
            event.preventDefault();
            // 1) taslak varsa iptal  2) araçtan Seç’e dön  3) zaten seçteyse planı kapatma (plan-first’te kal)
            if (sketch.draft || sketch.rectDrag) {
                cancelDraft();
                draw();
                paintStatus();
                updateHint();
                hooks?.toast?.('Taslak iptal.');
                return;
            }
            if (sketch.tool !== 'select') {
                setTool('select', { silent: true });
                hooks?.toast?.('Seç aracına dönüldü.');
                return;
            }
            // Plan-first’te Esc paneli kapatmaz; overlay modunda kapatır.
            if (document.body.classList.contains('show-room-plan') && !document.body.classList.contains('studio-plan-first')) {
                hooks?.toggleRoomPlan?.(false);
            }
            return;
        }
        if (key === 'enter') {
            event.preventDefault();
            finishDrawing({ forceSelect: true });
            return;
        }
        if (key === 'v') setTool('select');
        else if (key === 'w') setTool('wall');
        else if (key === 'd') setTool('door');
        else if (key === 'p') setTool('window');
        else if (key === 'delete' || key === 'backspace') {
            if (deleteSelection()) event.preventDefault();
        } else if ((event.metaKey || event.ctrlKey) && key === 'd') {
            event.preventDefault();
            duplicateSelection();
        }
    });
}

export function roomSketchOnOpen(state) {
    if (!canvas) return;
    loadFromStudio(state);
}

export function roomSketchBeforeHandoff(state) {
    syncFormFields();
    const data = exportToStudio();
    if (state) {
        if (!state.roomSketch) state.roomSketch = { walls: [], openings: [] };
        state.roomSketch.walls = data.walls;
        state.roomSketch.openings = data.openings;
    }
    return data;
}
