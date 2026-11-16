const frame = document.getElementById('plan-frame');
const wait = document.getElementById('plan-wait');
const errorBox = document.getElementById('plan-error');
const quoteOpen = document.getElementById('quote-open');
const quoteModal = document.getElementById('quote-modal');
const quoteForm = document.getElementById('quote-form');
const quoteMsg = document.getElementById('quote-msg');

const params = new URLSearchParams(location.search);
const token = params.get('proje') || '';
const recordId = params.get('kayit') || '';

let project = null;
let lastSignature = '';
let seenRevision = 0;
let saveTimer = 0;

function clamp(value, fallback, min, max) {
    const n = Number(value);
    if (!Number.isFinite(n)) return fallback;
    return Math.min(max, Math.max(min, Math.round(n)));
}

function slug(value, fallback) {
    const clean = String(value || '').replace(/[^A-Za-z0-9_-]/g, '').slice(0, 40);
    return clean || fallback;
}

function showError(text) {
    wait.hidden = true;
    frame.hidden = true;
    errorBox.style.display = 'grid';
    document.getElementById('plan-error-text').textContent = text;
}

function catalogIdOf(mod) {
    return mod?.glb || mod?.cad || mod?.dxf || mod?.product || (mod?.kind === 'corner' ? 'kose_1' : '');
}

function designPayload(design, name) {
    const modules = (design?.modules || []).map((mod) => {
        const catalogId = catalogIdOf(mod);
        if (!catalogId) return null;
        return {
            catalogId,
            name: mod.name || '',
            wall: mod.wall || 'front',
            offset: Number(mod.offset) || 0,
            width: Number(mod.width) || 60,
            depth: Number(mod.glbDepth || mod.depth) || 60,
            height: Number(mod.height) || 250,
            x: Number.isFinite(Number(mod.planX)) ? Number(mod.planX) : undefined,
            y: Number.isFinite(Number(mod.planY)) ? Number(mod.planY) : undefined,
            rotation: Number(mod.planRotation) || 0,
            color: mod.planColor || '',
            finishImage: mod.planFinish || '',
            backColor: mod.planBack || '',
            drawerColor: mod.planDrawer || '',
            shelfColor: mod.planShelf || '',
            backImage: mod.planBackImage || '',
            drawerImage: mod.planDrawerImage || '',
            shelfImage: mod.planShelfImage || '',
            ...(mod.planDoorStyle === 'glass' || mod.planDoorStyle === 'wood' ? { doorStyle: mod.planDoorStyle } : (mod.door === 'glass' ? { doorStyle: 'glass' } : {})),
            ...(mod.aluminumId || mod.planAluminum ? { aluminumId: mod.planAluminum || mod.aluminumId } : {}),
            ...(mod.glassId || mod.planGlass ? { glassId: mod.planGlass || mod.glassId } : {}),
            led: mod.planLed === true || mod.led === true,
            plinth: mod.planPlinth !== false && mod.plinth !== false,
            plinthHidden: mod.planPlinthHidden === true || mod.plinthHidden === true,
            ...(Number.isInteger(mod.planShelfCount) ? { shelfCount: mod.planShelfCount } : {}),
            ...(Number.isInteger(mod.planDrawerCount) ? { drawerCount: mod.planDrawerCount } : {}),
            ...(mod.planCornerSide === 'left' || mod.planCornerSide === 'right' ? { cornerSide: mod.planCornerSide } : {}),
        };
    }).filter(Boolean);
    return {
        source: 'meemare',
        action: 'openDesign',
        name: name || design?.name || 'Dolabım',
        walls: {
            front: Number(design?.wallWidth) || 300,
            left: Number(design?.leftWall) || 300,
            right: Number(design?.rightWall) || 300,
            height: Number(design?.roomHeight) || 280,
        },
        modules,
    };
}

function signatureOf(data) {
    const walls = data?.walls || {};
    return JSON.stringify({
        f: Math.round(Number(walls.front) || 0),
        l: Math.round(Number(walls.left) || 0),
        r: Math.round(Number(walls.right) || 0),
        h: Math.round(Number(walls.height) || 0),
        m: (data?.modules || []).map((mod) => ({
            id: mod.catalogId || '',
            x: Math.round(Number(mod.x) || 0),
            y: Math.round(Number(mod.y) || 0),
            r: Math.round(Number(mod.rotation) || 0),
            w: Math.round(Number(mod.width) || 0),
            d: Math.round(Number(mod.depth) || 0),
            h: Math.round(Number(mod.height) || 0),
            c: mod.color || '',
            f: mod.finishImage || '',
            b: mod.backColor || '',
            dr: mod.drawerColor || '',
            s: mod.shelfColor || '',
            bi: mod.backImage || '',
            di: mod.drawerImage || '',
            si: mod.shelfImage || '',
            door: mod.doorStyle || '',
            al: mod.aluminumId || '',
            gl: mod.glassId || '',
            led: mod.led === true,
            plinth: mod.plinth !== false,
            hidden: mod.plinthHidden === true,
            shelves: Number.isInteger(mod.shelfCount) ? mod.shelfCount : null,
            drawers: Number.isInteger(mod.drawerCount) ? mod.drawerCount : null,
            corner: mod.cornerSide || '',
        })),
    });
}

function moduleFromPlan(item, index, roomWidth) {
    const catalogId = slug(item.catalogId, '');
    if (!catalogId) return null;
    const corner = catalogId.startsWith('kose');
    const width = clamp(item.width, 60, 30, 200);
    const front = clamp(roomWidth, 300, 80, 800);
    return {
        id: slug(`m${index}${catalogId}`, `m${index}`),
        kind: corner ? 'corner' : 'wardrobe',
        wall: 'front',
        offset: 0,
        width: Math.min(width, front),
        height: clamp(item.height, 250, 40, 320),
        depth: clamp(item.depth, 60, 30, 80),
        glb: catalogId,
        glbWidth: width,
        glbDepth: clamp(item.depth, 60, 20, 120),
        glbHeight: clamp(item.height, 250, 40, 320),
        product: corner ? 'kose-raf' : 'tek-raf',
        name: String(item.name || '').replace(/[<>]/g, '').slice(0, 120),
        door: 'none',
        cornerSide: corner && Number(item.x) < front / 2 ? 'left' : 'right',
        planX: Number(item.x) || 0,
        planY: Number(item.y) || 0,
        planRotation: Number(item.rotation) || 0,
        planColor: item.color || '',
        planFinish: item.finishImage || '',
        planBack: item.backColor || '',
        planDrawer: item.drawerColor || '',
        planShelf: item.shelfColor || '',
        planBackImage: item.backImage || '',
        planDrawerImage: item.drawerImage || '',
        planShelfImage: item.shelfImage || '',
        ...(item.doorStyle === 'glass' || item.doorStyle === 'wood' ? { door: item.doorStyle === 'glass' ? 'glass' : 'solid', planDoorStyle: item.doorStyle } : {}),
        ...(item.aluminumId ? { aluminumId: item.aluminumId, planAluminum: item.aluminumId } : {}),
        ...(item.glassId ? { glassId: item.glassId, planGlass: item.glassId } : {}),
        led: item.led === true,
        planLed: item.led === true,
        plinth: item.plinth !== false,
        planPlinth: item.plinth !== false,
        plinthHidden: item.plinthHidden === true,
        planPlinthHidden: item.plinthHidden === true,
        ...(Number.isInteger(item.shelfCount) ? { planShelfCount: item.shelfCount } : {}),
        ...(Number.isInteger(item.drawerCount) ? { planDrawerCount: item.drawerCount } : {}),
        ...(item.cornerSide === 'left' || item.cornerSide === 'right' ? { planCornerSide: item.cornerSide } : {}),
    };
}

function applyDesign(data) {
    const walls = data.walls || {};
    const front = clamp(walls.front, project.design.wallWidth || 300, 80, 800);
    project.design.wallWidth = front;
    project.design.leftWall = clamp(walls.left, front, 80, 800);
    project.design.rightWall = clamp(walls.right, front, 80, 800);
    project.design.roomHeight = clamp(walls.height, project.design.roomHeight || 280, 160, 320);
    if (data.name) project.design.name = String(data.name).replace(/[<>]/g, '').slice(0, 80);
    project.design.modules = (data.modules || []).map((item, index) => moduleFromPlan(item, index, front)).filter(Boolean);
    project.design.placementRules = 1;
    if (Array.isArray(data.wallSegments) && data.wallSegments.length) {
        project.design.roomSketch = {
            walls: data.wallSegments.map((segment) => ({
                id: String(segment.id || ''),
                x1: Number(segment.x1) || 0,
                y1: Number(segment.y1) || 0,
                x2: Number(segment.x2) || 0,
                y2: Number(segment.y2) || 0,
                thickness: Number(segment.thickness) || 12,
            })),
            openings: (Array.isArray(data.openings) ? data.openings : []).map((opening) => {
                const wall = data.wallSegments.find((segment) => segment.id === opening.wallId);
                const length = wall ? Math.hypot((Number(wall.x2) || 0) - (Number(wall.x1) || 0), (Number(wall.y2) || 0) - (Number(wall.y1) || 0)) : 0;
                const width = Number(opening.width) || 0;
                const center = (Number(opening.position) || 0) * length;
                return { wallId: String(opening.wallId || ''), offset: Math.max(0, center - width / 2), width };
            }).filter((opening) => opening.width >= 20),
        };
    } else {
        project.design.roomSketch = project.design.roomSketch || { walls: [], openings: [] };
    }
}

async function persist() {
    if (!project?.token || !project.customerName) return;
    const response = await fetch(`${window.WARDROBE.projectBase}/proje/${project.token}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.WARDROBE.csrf,
            Accept: 'application/json',
        },
        body: JSON.stringify({
            customerName: project.customerName,
            phone: project.phone || '',
            projectName: project.projectName || project.design.name || 'Dolabım',
            note: project.note || '',
            design: project.design,
        }),
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok || !body.ok) {
        const detail = body.errors ? Object.values(body.errors).flat()[0] : body.message;
        const toast = document.getElementById('plan-toast');
        if (!toast) return;
        toast.textContent = detail || 'Kayıt yazılamadı.';
        toast.style.display = 'block';
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 2400);
    }
}

function scheduleSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => { persist().catch(() => {}); }, 700);
}

function postToPlan() {
    if (!frame.contentWindow) return;
    const message = project
        ? designPayload(project.design, project.projectName)
        : { source: 'meemare', action: 'openEditor' };
    if (project) lastSignature = signatureOf(message);
    frame.contentWindow.postMessage(message, '*');
}

function openFrame() {
    const src = String(window.WARDROBE.openplanSrc || '').trim();
    if (!src) {
        showError('Plan editörü bulunamadı.');
        return;
    }
    const base = src.endsWith('/') ? src : `${src}/`;
    const design = project ? '&design=1' : '';
    frame.hidden = false;
    wait.hidden = true;
    if (project) quoteOpen.hidden = false;
    frame.src = `${base}?embed=1${design}&v=310`;
    frame.addEventListener('load', () => {
        postToPlan();
        setTimeout(postToPlan, 400);
        setTimeout(postToPlan, 1200);
    }, { once: true });
}

window.addEventListener('message', (event) => {
    const data = event.data;
    if (!data || data.source !== 'openplan3d' || data.action !== 'designChanged') return;
    if (frame.contentWindow && event.source !== frame.contentWindow) return;
    if (!project) return;
    if (Number(data.revision) && Number(data.revision) < seenRevision) return;
    if (Number(data.revision)) seenRevision = Number(data.revision);
    if (signatureOf(data) === lastSignature) return;
    lastSignature = signatureOf(data);
    applyDesign(data);
    scheduleSave();
});

async function loadProject(key) {
    const path = String(key).includes('-')
        ? `${window.WARDROBE.projectBase}/proje/kayit/${key}`
        : `${window.WARDROBE.projectBase}/proje/${key}`;
    const response = await fetch(path, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error('Proje bulunamadı.');
    const data = await response.json();
    if (!data?.design || !Array.isArray(data.design.modules)) throw new Error('Proje tasarımı okunamadı.');
    project = data;
    if (quoteForm.name) quoteForm.name.value = data.customerName || '';
    if (quoteForm.phone) quoteForm.phone.value = data.phone || '';
}

quoteOpen?.addEventListener('click', () => { quoteModal.classList.add('is-on'); });
document.getElementById('quote-close')?.addEventListener('click', () => quoteModal.classList.remove('is-on'));
quoteForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!project?.design) return;
    quoteMsg.textContent = '';
    const body = {
        name: quoteForm.name.value,
        phone: quoteForm.phone.value,
        note: quoteForm.note.value,
        design: project.design,
    };
    try {
        const response = await fetch(window.WARDROBE.quoteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.WARDROBE.csrf,
                Accept: 'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await response.json().catch(() => ({}));
        quoteMsg.textContent = data.message || (response.ok ? 'Teklif gönderildi.' : 'Teklif gönderilemedi.');
        if (response.ok && data.ok) quoteForm.reset();
    } catch {
        quoteMsg.textContent = 'Teklif gönderilemedi.';
    }
});

const key = recordId || token;
if (!window.WARDROBE.openplanSrc) {
    showError('Plan editörü bulunamadı.');
} else if (key) {
    loadProject(key).then(openFrame).catch((error) => showError(error.message || 'Proje bulunamadı.'));
} else {
    openFrame();
}
