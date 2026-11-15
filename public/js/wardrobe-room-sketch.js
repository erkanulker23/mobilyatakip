/**
 * Meemare dolap tasarlama — gömülü oda planı (plan sayfasından bağımsız).
 */

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

const sketch = {
    walls: [],
    openings: [],
    tool: 'select',
    panX: 0,
    panY: 80,
    zoom: 1.35,
    draft: null,
    drag: null,
    selectedId: null,
    rectDrag: null,
};

let canvas = null;
let ctx = null;
let statusEl = null;
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
    if (kind === 'window') return { width: 120, height: 120, sill: 90 };
    return { width: 90, height: 210, sill: 0 };
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
    if (len < 60) return;
    const defs = openingDefaults(kind);
    const offset = clamp(Math.round(t * len - defs.width / 2), 0, len - defs.width);
    const role = wall.role || wallRole(wall);
    const wallKey = role === 'front' || role === 'left' || role === 'right' ? role : wall.id;
    const idx = sketch.openings.findIndex(
        (o) => o.wallId === wallKey && Math.abs(o.offset - offset) < defs.width * 0.65 && o.kind === kind,
    );
    if (idx >= 0) sketch.openings.splice(idx, 1);
    else {
        sketch.openings.push({
            id: `o${Date.now()}`,
            wallId: wallKey,
            kind,
            offset,
            width: defs.width,
            height: defs.height,
            sill: defs.sill,
        });
    }
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
    const form = document.getElementById('studio-plan-handoff');
    const height = Number(form?.odaboy?.value);
    const ok = hooks.applyWallsCm(data.frontCm, data.leftCm, data.rightCm, Number.isFinite(height) ? height : null);
    if (ok) hooks.scheduleSave?.();
    return ok;
}

function setTool(tool) {
    sketch.tool = tool;
    sketch.draft = null;
    sketch.rectDrag = null;
    document.querySelectorAll('[data-sketch-tool]').forEach((btn) => {
        btn.classList.toggle('is-on', btn.dataset.sketchTool === tool);
    });
    draw();
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
    ctx.fillStyle = highlight ? '#d8e8df' : '#2c2a27';
    ctx.fill();
    ctx.strokeStyle = highlight ? '#1f6b4a' : '#1a1917';
    ctx.lineWidth = highlight ? 2 : 1;
    ctx.stroke();
    if (len > 40 * sketch.zoom) {
        const midX = (a.x + b.x) / 2;
        const midY = (a.y + b.y) / 2;
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

function drawOpenings() {
    sketch.openings.forEach((item) => {
        const wall = sketch.walls.find((w) => (w.role || w.id) === item.wallId || w.id === item.wallId);
        if (!wall) return;
        const len = wallLen(wall);
        const t0 = (Number(item.offset) || 0) / len;
        const t1 = t0 + (Number(item.width) || 90) / len;
        const x1 = wall.x1 + (wall.x2 - wall.x1) * t0;
        const y1 = wall.y1 + (wall.y2 - wall.y1) * t0;
        const x2 = wall.x1 + (wall.x2 - wall.x1) * t1;
        const y2 = wall.y1 + (wall.y2 - wall.y1) * t1;
        const a = worldToScreen(x1, y1);
        const b = worldToScreen(x2, y2);
        ctx.strokeStyle = item.kind === 'door' ? '#3c463f' : '#6d8ea8';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(a.x, a.y);
        ctx.lineTo(b.x, b.y);
        ctx.stroke();
        if (item.kind === 'door') {
            const arcR = Math.hypot(b.x - a.x, b.y - a.y);
            ctx.beginPath();
            ctx.arc(a.x, a.y, arcR, 0, Math.PI / 2, false);
            ctx.stroke();
        }
    });
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
    if (sketch.draft) {
        drawWallSegment(sketch.draft, true);
    }
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
}

function paintStatus() {
    if (!statusEl) return;
    const dims = syncDimensions();
    const area = dims.frontCm && dims.leftCm ? ((dims.frontCm * dims.leftCm) / 10000).toFixed(1) : '—';
    const nWall = sketch.walls.filter((w) => wallLen(w) > 20).length;
    statusEl.innerHTML = `
        <span>${nWall} duvar</span>
        <span>${area} m² (yaklaşık)</span>
        <span>Yakalama ${SNAP} cm</span>
        <span class="room-sketch-zoom">
            <button type="button" data-zoom="-">−</button>
            <span>${Math.round(sketch.zoom * 100)}%</span>
            <button type="button" data-zoom="+">+</button>
        </span>`;
}

function pointerPos(event) {
    const rect = canvas.getBoundingClientRect();
    return {
        x: (event.clientX - rect.left) * (canvas.clientWidth / rect.width),
        y: (event.clientY - rect.top) * (canvas.clientHeight / rect.height),
    };
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

    canvas.addEventListener('pointerdown', (event) => {
        const pt = pointerPos(event);
        const world = screenToWorld(pt.x, pt.y);
        const wx = snap(world.x);
        const wy = snap(world.y);

        if (sketch.tool === 'wall') {
            if (!sketch.draft) {
                sketch.draft = { x1: wx, y1: wy, x2: wx, y2: wy };
            } else {
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
                    sketch.draft = { x1: wx, y1: wy, x2: wx, y2: wy };
                } else sketch.draft = null;
            }
            canvas.setPointerCapture(event.pointerId);
            draw();
            paintStatus();
            syncFormFields();
            return;
        }

        if (sketch.tool === 'door' || sketch.tool === 'window') {
            const hit = hitWall(world.x, world.y);
            if (!hit) return;
            assignRoles(sketch.walls);
            pushOpening(hit.wall, hit.t, sketch.tool);
            draw();
            hooks?.scheduleSave?.();
            return;
        }

        if (sketch.tool === 'rect') {
            sketch.rectDrag = { x0: wx, y0: wy, x1: wx, y1: wy };
            canvas.setPointerCapture(event.pointerId);
            return;
        }

        const hit = hitWall(world.x, world.y);
        if (hit) {
            sketch.selectedId = hit.wall.id;
            sketch.drag = { kind: 'pan', x: pt.x, y: pt.y, panX: sketch.panX, panY: sketch.panY };
            if (event.altKey && wallLen(hit.wall) > SNAP) {
                const idx = sketch.walls.findIndex((w) => w.id === hit.wall.id);
                if (idx >= 0) sketch.walls.splice(idx, 1);
                sketch.selectedId = null;
            }
            canvas.setPointerCapture(event.pointerId);
            draw();
            return;
        }
        sketch.selectedId = null;
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
        if (!sketch.drag) return;
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
            setTool('select');
            draw();
            paintStatus();
        }
        if (sketch.tool === 'wall' && event.detail === 2) sketch.draft = null;
        sketch.drag = null;
        try { canvas.releasePointerCapture(event.pointerId); } catch { /* noop */ }
    });

    canvas.addEventListener('dblclick', () => {
        if (sketch.tool === 'wall') sketch.draft = null;
        draw();
    });
}

function bindToolbar() {
    document.querySelectorAll('[data-sketch-tool]').forEach((btn) => {
        btn.addEventListener('click', () => setTool(btn.dataset.sketchTool));
    });
    document.getElementById('btn-sketch-rect')?.addEventListener('click', () => setTool('rect'));
    document.getElementById('btn-sketch-l')?.addEventListener('click', () => {
        const form = document.getElementById('studio-plan-handoff');
        const front = Number(form?.onduvar?.value) || 300;
        const left = Number(form?.solduvar?.value) || 300;
        const right = Number(form?.sagduvar?.value) || 280;
        sketch.walls = buildLShape(front, left, right);
        sketch.openings = [];
        assignRoles(sketch.walls);
        syncFormFields();
        draw();
        paintStatus();
    });
    document.getElementById('btn-sketch-clear')?.addEventListener('click', () => {
        sketch.walls = [];
        sketch.openings = [];
        sketch.draft = null;
        draw();
        paintStatus();
    });
    document.getElementById('btn-room-redraw')?.addEventListener('click', () => {
        sketch.walls = [];
        sketch.openings = [];
        sketch.draft = null;
        setTool('wall');
        paintStatus();
        hooks?.toast?.('Duvarları tıklayarak çizin. Bitirmek için çift tıklayın.');
    });
    document.getElementById('btn-sketch-apply')?.addEventListener('click', () => {
        if (applyToStudio()) {
            hooks?.toggleRoomPlan?.(false);
            hooks?.toast?.('Oda çizimi 3D sahneye uygulandı.');
        } else hooks?.toast?.('Ön / sol / sağ duvar tanımlayın (L veya dikdörtgen şablon).');
    });
    statusEl?.addEventListener('click', (event) => {
        const zoomBtn = event.target.closest('[data-zoom]');
        if (!zoomBtn) return;
        sketch.zoom = clamp(sketch.zoom * (zoomBtn.dataset.zoom === '+' ? 1.12 : 0.9), 0.45, 4);
        draw();
        paintStatus();
    });
}

export function bindRoomSketch(deps) {
    hooks = deps;
    canvas = document.getElementById('room-sketch-canvas');
    statusEl = document.getElementById('room-sketch-status');
    if (!canvas) return;
    ctx = canvas.getContext('2d');
    if (bound) return;
    bound = true;
    bindCanvas();
    bindToolbar();
    setTool('select');
    window.addEventListener('resize', () => { draw(); paintStatus(); });
    window.addEventListener('keydown', (event) => {
        if (!canvas || !(document.body.classList.contains('show-room-plan') || document.body.classList.contains('studio-plan-first'))) return;
        const key = String(event.key || '').toLowerCase();
        if (key === 'v') setTool('select');
        else if (key === 'w') setTool('wall');
        else if (key === 'd') setTool('door');
        else if (key === 'p') setTool('window');
        else if (key === 'escape') hooks?.toggleRoomPlan?.(false);
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