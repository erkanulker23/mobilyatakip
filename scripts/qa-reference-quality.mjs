import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/reference-quality/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9345;
const password = process.env.QA_PASSWORD || 'password';
const rows = [];

function note(area, id, status, detail) {
    rows.push({ area, id, status, detail });
    console.log(`${status} ${area} ${id}: ${detail}`);
}
function abs(path) {
    return new URL(path, OUT).pathname;
}
mkdirSync(abs('shots'), { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${abs('chrome-profile')}`,
    '--window-size=1440,900',
    '--use-angle=metal',
    '--enable-precise-memory-info',
    'about:blank',
], { stdio: 'ignore' });

async function target() {
    for (let i = 0; i < 40; i += 1) {
        try {
            const list = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
            const page = list.find((item) => item.type === 'page');
            if (page) return page;
        } catch { /* starting */ }
        await sleep(250);
    }
    throw new Error('Chrome hedefi yok');
}

const page = await target();
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve, reject) => {
    ws.addEventListener('open', resolve);
    ws.addEventListener('error', reject);
});
let seq = 0;
const pending = new Map();
ws.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    if (!data.id || !pending.has(data.id)) return;
    const { resolve, reject } = pending.get(data.id);
    pending.delete(data.id);
    if (data.error) reject(new Error(JSON.stringify(data.error)));
    else resolve(data.result);
});
function send(method, params = {}) {
    const id = ++seq;
    return new Promise((resolve, reject) => {
        pending.set(id, { resolve, reject });
        ws.send(JSON.stringify({ id, method, params }));
    });
}
async function ev(expression) {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    return result.result?.value;
}
async function nav(url) {
    await send('Page.navigate', { url });
    await sleep(400);
    for (let i = 0; i < 40; i += 1) {
        if (await ev('document.readyState') === 'complete') return;
        await sleep(200);
    }
}
async function waitFor(expression, ms = 30000) {
    const start = Date.now();
    while (Date.now() - start < ms) {
        if (await ev(`!!(${expression})`)) return;
        await sleep(200);
    }
    throw new Error(`zaman aşımı: ${expression}`);
}
async function canvasShot(file) {
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.72) || ''`);
    if (!data.startsWith('data:image')) return false;
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
    return true;
}

await send('Page.enable');
await send('Runtime.enable');

try {
    await nav(`${BASE}/login`);
    const needsLogin = await ev(`!!document.querySelector('#email')`);
    if (needsLogin) {
        await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
        await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
        await ev(`document.querySelector('#login-btn').click()`);
        await waitFor(`location.pathname !== '/login'`, 15000);
    }
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`window.__studio && document.querySelector('[data-glb="y26_tek_01"]')`, 30000);
    note('boot', 'studio', 'PASS', await ev('location.pathname'));

    async function alone(id) {
        await ev(`window.__studio.clear()`);
        await ev(`window.__studio.add(${JSON.stringify(id)})`);
        await waitFor(`window.__studio.stats().ids.includes(${JSON.stringify(id)})`, 20000);
        await sleep(600);
    }

    const views = ['front', 'left45', 'right45', 'shelf', 'drawer', 'glass'];
    for (const id of ['y26_tek_01', 'y26_cift_01', 'kose_1']) {
        await alone(id);
        await ev(`window.__studio.setDoor('none')`);
        await sleep(300);
        await ev(`window.__setCameraView('drawer')`);
        await sleep(250);
        const openDrawer = await canvasShot(`shots/${id}-drawer-open.jpg`);
        note('shot', `${id}-drawer-body`, openDrawer ? 'PASS' : 'FAIL', 'kapaksız çekmece kadrajı');
        await ev(`window.__studio.setDoor('solid')`);
        await sleep(400);
        for (const view of views) {
            await ev(`window.__setCameraView(${JSON.stringify(view)})`);
            await sleep(250);
            const ok = await canvasShot(`shots/${id}-${view}.jpg`);
            note('shot', `${id}-${view}`, ok ? 'PASS' : 'FAIL', ok ? 'tuval kaydı' : 'tuval boş');
        }
        await ev(`window.__studio.setDoor('glass')`);
        await ev(`window.__setCameraView('glass')`);
        await sleep(300);
        const glass = await canvasShot(`shots/${id}-glass-door.jpg`);
        note('shot', `${id}-glass-door`, glass ? 'PASS' : 'FAIL', 'cam kapak');
    }

    await ev(`window.__studio.clear()`);
    const heapBefore = await ev(`({ geometries: window.__studio.stats().geometries, heap: performance.memory ? performance.memory.usedJSHeapSize : null })`);
    const cycleStarted = performance.now();
    const cycles = await ev(`(async () => {
        let added = 0;
        let removed = 0;
        for (let i = 0; i < 50; i += 1) {
            await window.__studio.add('y26_tek_01');
            if (window.__studio.stats().count !== 1) return { added, removed, stuck: window.__studio.stats().count };
            added += 1;
            window.__studio.remove();
            if (window.__studio.stats().count !== 0) return { added, removed, stuck: window.__studio.stats().count };
            removed += 1;
        }
        return { added, removed, stuck: 0 };
    })()`);
    const cycleMs = Math.round(performance.now() - cycleStarted);
    note('stability', 'add-delete-50', cycles.added === 50 && cycles.removed === 50 ? 'PASS' : 'FAIL', `${JSON.stringify(cycles)} süre ${cycleMs} ms`);

    await alone('y26_tek_01');
    const paints = await ev(`(() => {
        let n = 0;
        for (let i = 0; i < 100; i += 1) {
            if (window.__studio.paint(i % 2 ? 'a01' : 'a02')) n += 1;
        }
        return { n, count: window.__studio.stats().count };
    })()`);
    note('stability', 'paint-100', paints.n === 100 && paints.count === 1 ? 'PASS' : 'FAIL', JSON.stringify(paints));

    const doors = await ev(`(() => {
        const opens = [];
        for (let i = 0; i < 30; i += 1) opens.push(window.__studio.toggleDoor());
        return { n: opens.length, last: opens.at(-1), count: window.__studio.stats().count };
    })()`);
    note('stability', 'door-30', doors.n === 30 && doors.count === 1 ? 'PASS' : 'FAIL', JSON.stringify(doors));

    const undo = await ev(`(async () => {
        window.__studio.clear();
        await window.__studio.add('y26_tek_01');
        await window.__studio.add('y26_cift_01');
        const before = window.__studio.stats().count;
        window.__studio.undo();
        const mid = window.__studio.stats().count;
        window.__studio.redo();
        return { before, mid, after: window.__studio.stats().count, ids: window.__studio.stats().ids };
    })()`);
    note('stability', 'undo-redo', undo.before === 2 && undo.mid === 1 && undo.after === 2 ? 'PASS' : 'FAIL', JSON.stringify(undo));
    const heapAfter = await ev(`({ geometries: window.__studio.stats().geometries, heap: performance.memory ? performance.memory.usedJSHeapSize : null, count: window.__studio.stats().count })`);
    note('performance', 'geometry-after-cycles', 'MEASURED', `önce ${JSON.stringify(heapBefore)} sonra ${JSON.stringify(heapAfter)}`);

    const beforeReload = await ev(`window.__studio.stats().count`);
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`window.__studio`, 30000);
    await sleep(800);
    const afterReload = await ev(`window.__studio.stats()`);
    const draftOk = afterReload.count === beforeReload && afterReload.ids.includes('y26_tek_01');
    note('stability', 'draft-reload', draftOk ? 'PASS' : 'FAIL', `önce ${beforeReload} sonra ${JSON.stringify(afterReload)}`);

    const saveProbe = await ev(`(async () => {
        const response = await fetch('/dolap/proje', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.WARDROBE.csrf, Accept: 'application/json' },
            body: JSON.stringify({ customerName: 'QA Referans', phone: '05550000000', projectName: 'QA referans kalite', note: '', design: { modules: [] } }),
        });
        const data = await response.json().catch(() => ({}));
        return { status: response.status, ok: data.ok === true, message: data.message || '', id: data.id || null };
    })()`);
    if (saveProbe.ok && saveProbe.id) {
        await ev(`fetch('/dolap/proje/kayit/${saveProbe.id}', { method: 'DELETE', headers: { 'X-CSRF-TOKEN': window.WARDROBE.csrf, Accept: 'application/json' } })`);
    }
    note('stability', 'server-save', saveProbe.status === 200 && saveProbe.ok ? 'PASS' : 'BLOCKED', `HTTP ${saveProbe.status} ${saveProbe.message}`);

    const perf = await ev(`(() => new Promise((resolve) => {
        const samples = [];
        let n = 0;
        const start = performance.now();
        function frame() {
            samples.push(performance.now());
            n += 1;
            if (n < 60) requestAnimationFrame(frame);
            else {
                const dt = samples.slice(1).map((t, i) => t - samples[i]);
                const avg = dt.reduce((s, v) => s + v, 0) / dt.length;
                resolve({
                    frames: dt.length,
                    avgMs: Math.round(avg * 10) / 10,
                    heap: performance.memory ? performance.memory.usedJSHeapSize : null,
                    stats: window.__studio.stats(),
                    elapsedMs: Math.round(performance.now() - start),
                });
            }
        }
        requestAnimationFrame(frame);
    }))()`);
    note('performance', 'raster-60', 'PASS', JSON.stringify(perf));
    writeFileSync(abs('performance.json'), JSON.stringify({ cycleMs, cycles, paints, doors, undo, perf, saveProbe }, null, 2));

    await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 2, mobile: true });
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`window.__studio`, 30000);
    await sleep(500);
    await ev(`window.__studio.clear()`);
    await ev(`window.__studio.add('y26_tek_01')`);
    await waitFor(`window.__studio.stats().count === 1`, 20000);
    await sleep(400);
    const mobile = await send('Page.captureScreenshot', { format: 'jpeg', quality: 60 });
    writeFileSync(abs('shots/mobile-390.jpg'), Buffer.from(mobile.data, 'base64'));
    const covered = await ev(`(() => {
        const canvas = document.querySelector('#viewport canvas')?.getBoundingClientRect();
        const sheet = document.querySelector('#inspector')?.getBoundingClientRect();
        const open = document.querySelector('#inspector')?.classList.contains('is-open');
        return { canvasH: Math.round(canvas?.height || 0), canvasW: Math.round(canvas?.width || 0), sheetOpen: Boolean(open), sheetH: Math.round(sheet?.height || 0) };
    })()`);
    note('ux', 'mobile-scene', covered.canvasH > 200 && !covered.sheetOpen ? 'PASS' : 'FAIL', JSON.stringify(covered));
    await send('Emulation.clearDeviceMetricsOverride');

    await ev(`window.__setCameraView('perspective')`);
    const renderStarted = Date.now();
    await ev(`document.querySelector('#btn-shot')?.click()`);
    await waitFor(`!document.querySelector('#btn-shot')?.disabled`, 120000);
    const renderStats = await ev(`window.__renderStats || null`);
    const renderShot = await ev(`window.__renderImage || ''`);
    if (String(renderShot).startsWith('data:image')) writeFileSync(abs('shots/ultra-tek.png'), Buffer.from(renderShot.split(',')[1], 'base64'));
    note('render', 'ultra-tek', renderStats ? 'MEASURED' : 'FAIL', `${JSON.stringify(renderStats)} duvar ${Date.now() - renderStarted} ms`);
} catch (error) {
    note('run', 'exception', 'FAIL', String(error.stack || error).slice(0, 800));
} finally {
    writeFileSync(abs('results.json'), JSON.stringify(rows, null, 2));
    chrome.kill('SIGKILL');
    ws.close();
}
