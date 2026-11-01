import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9334;
const password = process.env.QA_PASSWORD || '';
if (!password) throw new Error('QA_PASSWORD yok');

const log = [];
function note(step, ok, detail) {
    const row = { step, ok, detail, at: new Date().toISOString() };
    log.push(row);
    console.log(`${ok ? 'OK' : 'FAIL'} ${step}: ${detail}`);
}

function abs(path) {
    return new URL(path, OUT).pathname;
}

mkdirSync(abs('module-renders'), { recursive: true });
mkdirSync(abs('pages'), { recursive: true });
mkdirSync(abs('renders'), { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${abs('chrome-profile')}`,
    '--window-size=1440,900',
    '--disable-gpu-sandbox',
    '--enable-precise-memory-info',
    'about:blank',
], { stdio: 'ignore' });

async function target() {
    for (let i = 0; i < 40; i += 1) {
        try {
            const list = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
            const page = list.find((item) => item.type === 'page');
            if (page) return page;
        } catch { /* chrome still starting */ }
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
    if (result.exceptionDetails) {
        throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    }
    return result.result?.value;
}
async function nav(url) {
    await send('Page.navigate', { url });
    await sleep(600);
    for (let i = 0; i < 40; i += 1) {
        const ready = await ev('document.readyState');
        if (ready === 'complete') return;
        await sleep(200);
    }
}
async function shot(file) {
    const dir = abs(file).split('/').slice(0, -1).join('/');
    mkdirSync(dir, { recursive: true });
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.72) || ''`);
    if (!data.startsWith('data:image')) throw new Error(`kanvas yok: ${file}`);
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
}
async function pageShot(file) {
    const result = await send('Page.captureScreenshot', { format: 'jpeg', quality: 60 });
    writeFileSync(abs(file), Buffer.from(result.data, 'base64'));
}
async function waitFor(expression, ms = 40000) {
    const start = Date.now();
    while (Date.now() - start < ms) {
        const value = await ev(`!!(${expression})`);
        if (value) return;
        await sleep(250);
    }
    throw new Error(`zaman aşımı: ${expression}`);
}

await send('Page.enable');
await send('Runtime.enable');
await send('Browser.setDownloadBehavior', {
    behavior: 'allow',
    downloadPath: abs('renders'),
    eventsEnabled: true,
});

try {
    await nav(`${BASE}/login`);
    await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
    await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
    await ev(`document.querySelector('#login-btn').click()`);
    await sleep(800);
    await waitFor(`location.pathname !== '/login'`, 15000);
    note('giriş', true, await ev('location.pathname'));

    async function createProject(name, front) {
        await nav(`${BASE}/dolap`);
        await waitFor(`document.querySelector('#create')`);
        await ev(`(() => {
            const form = document.querySelector('#create');
            form.customerName.value = 'QA Denetim';
            form.projectName.value = ${JSON.stringify(name)};
            form.frontWall.value = '${front}';
            form.leftWall.value = '300';
            form.rightWall.value = '300';
            form.note.value = 'QA test verisi. Müşteri projesi değil.';
            form.requestSubmit();
        })()`);
        await waitFor(`location.pathname.includes('/tasarla')`, 20000);
        await waitFor(`document.querySelector('[data-glb]') && document.querySelector('#viewport canvas')`, 60000);
        await sleep(400);
    }

    async function summary() {
        return ev(`document.querySelector('#summary')?.innerText || ''`);
    }
    async function production() {
        return ev(`document.querySelector('#production')?.innerText || ''`);
    }
    async function dragCard(id, xRatio, yRatio) {
        return ev(`(() => {
            const card = document.querySelector('[data-glb="${id}"]');
            const canvas = document.querySelector('#viewport canvas');
            if (!card || !canvas) return 'kart yok';
            const from = card.getBoundingClientRect();
            const to = canvas.getBoundingClientRect();
            const x = to.left + to.width * ${xRatio};
            const y = to.top + to.height * ${yRatio};
            const down = new PointerEvent('pointerdown', { bubbles: true, button: 0, buttons: 1, clientX: from.left + 12, clientY: from.top + 12, pointerId: 1, pointerType: 'mouse' });
            card.dispatchEvent(down);
            window.dispatchEvent(new PointerEvent('pointermove', { bubbles: true, button: 0, buttons: 1, clientX: x, clientY: y, pointerId: 1, pointerType: 'mouse' }));
            window.dispatchEvent(new PointerEvent('pointerup', { bubbles: true, button: 0, buttons: 0, clientX: x, clientY: y, pointerId: 1, pointerType: 'mouse' }));
            return 'bırakıldı';
        })()`);
    }
    async function view(name) {
        await ev(`document.querySelector('[data-view="${name}"]')?.click()`);
        await sleep(350);
    }

    await createProject('QA Denetim görsel', 450);
    const ids = await ev(`[...document.querySelectorAll('[data-glb]')].map((node) => node.dataset.glb)`);
    note('katalog kartı', ids.length === 25, `${ids.length} kart`);
    const visual = [];
    for (const id of ids) {
        const row = { id, shots: [], door: false, drawers: false, error: '' };
        try {
            if ((await ev(`document.querySelectorAll('#production b').length`)) > 0) {
                await ev(`document.querySelector('#act-delete')?.click()`);
                await sleep(300);
            }
            await ev(`document.querySelector('[data-glb="${id}"]').click()`);
            await waitFor(`document.querySelectorAll('#production b').length > 0`, 15000);
            await sleep(500);
            const hasDoor = await ev(`!!document.querySelector('[data-door="solid"]')`);
            if (hasDoor) {
                await ev(`document.querySelector('[data-door="solid"]').click()`);
                await sleep(400);
            }
            await view('front');
            await shot(`module-renders/${id}/${id}-front.jpg`);
            await view('right45');
            await shot(`module-renders/${id}/${id}-right45.jpg`);
            await view('left45');
            await shot(`module-renders/${id}/${id}-left45.jpg`);
            const opener = await ev(`document.querySelector('#mod-open')?.textContent || ''`);
            if (opener) {
                await ev(`document.querySelector('#mod-open').click()`);
                await sleep(900);
                row.door = true;
            }
            await view('front');
            await shot(`module-renders/${id}/${id}-open.jpg`);
            if (opener) {
                await ev(`document.querySelector('#mod-open')?.click()`);
                await sleep(700);
            }
            await view('front');
            await shot(`module-renders/${id}/${id}-closed.jpg`);
            row.drawers = await ev(`!!document.querySelector('#mod-drawers')`);
            row.shots = ['front', 'right45', 'left45', 'open', 'closed'];
            if (row.drawers) {
                await ev(`document.querySelector('#mod-drawers').click()`);
                await sleep(700);
                await shot(`module-renders/${id}/${id}-drawers.jpg`);
                await ev(`document.querySelector('#mod-drawers').click()`);
            }
        } catch (error) {
            row.error = String(error.message || error);
        }
        visual.push(row);
        console.log(id, row.error || 'görüntüler yazıldı');
    }
    writeFileSync(abs('visual.json'), JSON.stringify(visual, null, 2));
    const failed = visual.filter((row) => row.error);
    note('25 modül görüntüsü', failed.length === 0, `${visual.length - failed.length}/${visual.length}`);

    async function runLayout(name, front, drops) {
        await createProject(name, front);
        const placed = [];
        for (const drop of drops) {
            placed.push(await dragCard(drop.id, drop.x, drop.y));
            await sleep(600);
        }
        await view('perspective');
        await pageShot(`pages/${name.replaceAll(' ', '-')}.jpg`);
        const before = await production();
        const text = await summary();
        note(name, text.includes(`Dolap ${front} cm`), `${placed.join(', ')} | ${text}`);
        return { before, text };
    }

    await runLayout('QA Denetim 270', 270, [
        { id: 'cift_v1', x: 0.3, y: 0.55 },
        { id: 'cift_v2', x: 0.5, y: 0.55 },
        { id: 'cift_v3', x: 0.72, y: 0.55 },
    ]);
    const layout270 = await production();
    await ev(`document.querySelector('#btn-save').click()`);
    await sleep(1200);
    const saved = await ev(`document.querySelector('#toast')?.textContent || ''`);
    const token = await ev(`new URL(location.href).searchParams.get('proje')`);
    await nav(`${BASE}/dolap/tasarla?proje=${token}`);
    await waitFor(`document.querySelector('#production')`);
    await sleep(800);
    const reloaded = await production();
    note('kayıt ve yenileme', layout270 === reloaded, saved || 'toast yok');
    await nav(`${BASE}/logout`);
    await nav(`${BASE}/login`);
    await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
    await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
    await ev(`document.querySelector('#login-btn').click()`);
    await sleep(800);
    await nav(`${BASE}/dolap/tasarla?proje=${token}`);
    await waitFor(`document.querySelector('#summary')`, 20000);
    await sleep(800);
    const relogin = await production();
    note('çıkış sonrası aynı proje', layout270 === relogin, token);

    await runLayout('QA Denetim 225', 225, [
        { id: 'tek_v1', x: 0.28, y: 0.55 },
        { id: 'cift_v1', x: 0.52, y: 0.55 },
        { id: 'cift_v2', x: 0.74, y: 0.55 },
    ]);
    await runLayout('QA Denetim 315', 315, [
        { id: 'cift_v1', x: 0.22, y: 0.55 },
        { id: 'tek_v2', x: 0.42, y: 0.55 },
        { id: 'cift_v4', x: 0.6, y: 0.55 },
        { id: 'cift_v5', x: 0.8, y: 0.55 },
    ]);

    await createProject('QA Denetim köşe', 360);
    await dragCard('kose_1', 0.25, 0.55);
    await sleep(500);
    await dragCard('cift_v1', 0.55, 0.55);
    await sleep(500);
    await dragCard('tek_v1', 0.18, 0.7);
    await sleep(500);
    await view('top');
    await shot('pages/kose-top.jpg');
    await view('perspective');
    await pageShot('pages/kose-perspective.jpg');
    note('köşe yerleşimi', (await summary()).includes('Dolap'), await summary());

    await createProject('QA Denetim füme', 120);
    await ev(`document.querySelector('[data-glb="tek_v1"]').click()`);
    await sleep(700);
    await ev(`document.querySelector('[data-door="glass"]')?.click()`);
    await sleep(300);
    await ev(`document.querySelector('[data-glass="cam-fum"]')?.click()`);
    await sleep(400);
    await view('front');
    await shot('pages/fume-raster.jpg');
    await ev(`document.querySelector('[data-glass="cam-seffaf"]')?.click()`);
    await sleep(300);
    await shot('pages/seffaf-raster.jpg');
    await ev(`document.querySelector('[data-glass="cam-bronz"]')?.click()`);
    await sleep(300);
    await shot('pages/bronz-raster.jpg');
    await ev(`document.querySelector('[data-glass="cam-fum"]')?.click()`);
    await sleep(200);
    const renderStarted = Date.now();
    await ev(`document.querySelector('#btn-shot').click()`);
    let renderLabel = '';
    for (let i = 0; i < 120; i += 1) {
        await sleep(3000);
        renderLabel = await ev(`document.querySelector('#btn-shot')?.textContent || ''`);
        console.log('render', renderLabel);
        if (renderLabel === 'Render al') break;
    }
    note('füme path trace', renderLabel === 'Render al', `${renderLabel}, ${Math.round((Date.now() - renderStarted) / 1000)} sn`);

    await createProject('QA Denetim performans', 800);
    const perf = [];
    for (const count of [1, 5, 10, 17]) {
        const t0 = Date.now();
        let stuck = 0;
        while ((await ev(`document.querySelectorAll('#production b').length`)) < count) {
            const before = await ev(`document.querySelectorAll('#production b').length`);
            await ev(`document.querySelector('[data-glb="tek_6"]').click()`);
            await sleep(700);
            const after = await ev(`document.querySelectorAll('#production b').length`);
            if (after === before) {
                stuck += 1;
                if (stuck > 2) break;
            }
        }
        const fps = await ev(`new Promise((resolve) => {
            let frames = 0;
            const start = performance.now();
            const loop = (now) => {
                frames += 1;
                if (now - start < 1000) requestAnimationFrame(loop);
                else resolve(frames);
            };
            requestAnimationFrame(loop);
        })`);
        const heap = await ev(`performance.memory ? Math.round(performance.memory.usedJSHeapSize / 1048576) : null`);
        perf.push({ count: await ev(`document.querySelectorAll('#production b').length`), ms: Date.now() - t0, fps, heapMb: heap });
    }
    writeFileSync(abs('perf.json'), JSON.stringify(perf, null, 2));
    note('performans', true, JSON.stringify(perf));

    await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 2, mobile: true });
    await sleep(500);
    await pageShot('pages/mobile-390.jpg');
    await send('Emulation.clearDeviceMetricsOverride');
    note('mobil 390', true, 'pages/mobile-390.jpg');
} catch (error) {
    note('tur', false, String(error.stack || error));
    try { await pageShot('pages/failure.jpg'); } catch { /* sayfa yok */ }
} finally {
    writeFileSync(abs('qa-log.json'), JSON.stringify(log, null, 2));
    ws.close();
    chrome.kill();
}
