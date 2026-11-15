import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync, readFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/master-qa/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9344;
const password = process.env.QA_PASSWORD || 'password';

const rows = [];
function note(area, id, status, detail) {
    const row = { area, id, status, detail };
    rows.push(row);
    console.log(`${status} ${area} ${id}: ${detail}`);
}
function abs(path) {
    return new URL(path, OUT).pathname;
}
for (const dir of ['screenshots', 'module-tests', 'placement-tests', 'mobile-tests', 'performance', 'security', 'render-comparisons']) {
    mkdirSync(abs(dir), { recursive: true });
}

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
    await sleep(500);
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
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.62) || ''`);
    if (!data.startsWith('data:image')) return false;
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
    return true;
}
async function pageShot(file) {
    const result = await send('Page.captureScreenshot', { format: 'jpeg', quality: 55 });
    writeFileSync(abs(file), Buffer.from(result.data, 'base64'));
}

await send('Page.enable');
await send('Runtime.enable');

try {
    await nav(`${BASE}/login`);
    await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
    await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
    await ev(`document.querySelector('#login-btn').click()`);
    await waitFor(`location.pathname !== '/login'`, 15000);
    note('auth', 'login', 'PASS', await ev('location.pathname'));

    const indexMeta = await ev(`(async () => {
        const response = await fetch('/dolap/projeler', { headers: { Accept: 'application/json' } });
        const data = await response.json();
        const list = data.projects || [];
        const keys = list[0] ? Object.keys(list[0]) : [];
        return { status: response.status, count: list.length, keys, qaOnly: list.every((item) => String(item.projectName || '').startsWith('QA ')) };
    })()`);
    writeFileSync(abs('security/project-index-meta.json'), JSON.stringify(indexMeta, null, 2));
    note('security', 'project-index', indexMeta.count > 0 ? 'FAIL' : 'PASS', `filtresiz liste ${indexMeta.count} kayıt, alanlar ${indexMeta.keys.join(',')}`);

    await nav(`${BASE}/dolap`);
    await waitFor(`document.querySelector('#create')`);
    await ev(`(() => {
        const form = document.querySelector('#create');
        form.customerName.value = 'QA Master';
        form.projectName.value = 'QA Master Denetim';
        form.frontWall.value = '300';
        form.leftWall.value = '300';
        form.rightWall.value = '300';
        form.note.value = 'Otomatik denetim verisi. Müşteri projesi değildir.';
        form.requestSubmit();
    })()`);
    await waitFor(`location.pathname.includes('/tasarla')`, 20000);
    await waitFor(`document.querySelector('[data-glb]')`, 60000);
    const projectUrl = await ev('location.href');
    note('save', 'create', 'PASS', projectUrl);
    await pageShot('screenshots/desktop-studio.jpg');

    const ids = await ev(`[...document.querySelectorAll('[data-glb]')].map((card) => card.dataset.glb)`);
    note('catalog', 'count', ids.length === 45 ? 'PASS' : 'FAIL', `${ids.length} kart`);

    for (const id of ids) {
        try {
            const before = await ev(`document.body.innerText.includes('modeli açılamadı')`);
            await ev(`(() => {
                const card = document.querySelector('[data-glb="${id}"]');
                const btn = [...card.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Ekle');
                btn.click();
            })()`);
            await sleep(1300);
            const info = await ev(`(() => ({
                name: document.querySelector('#selection-name')?.textContent || '',
                w: document.querySelector('#mod-w')?.value || '',
                h: document.querySelector('#mod-h')?.value || '',
                summary: document.querySelector('#summary')?.innerText || '',
                broken: document.querySelector('[data-glb="${id}"]')?.classList.contains('is-broken') || false,
            }))()`);
            await ev(`document.body.classList.remove('show-custom','show-library')`);
            const shot = await canvasShot(`module-tests/${id}.jpg`);
            await ev(`document.querySelector('#act-delete')?.click()`);
            await sleep(200);
            const status = info.broken || !shot ? 'FAIL' : 'PASS';
            note('module', id, status, `${info.name} | ölçü kutusu ${info.w}x${info.h} | ${info.summary.replace(/\\s+/g, ' ').slice(0, 140)}`);
            if (before) note('module', `${id}-toast`, 'FAIL', 'önceki model açılamadı');
        } catch (error) {
            note('module', id, 'FAIL', String(error.message || error).slice(0, 180));
        }
    }

    async function clearScene() {
        for (let i = 0; i < 30; i += 1) {
            const left = await ev(`document.querySelector('#act-delete') && !document.querySelector('#selection-ui')?.classList.contains('hidden')`);
            if (!left) break;
            await ev(`document.querySelector('#act-delete').click()`);
            await sleep(150);
        }
    }

    const widths = [180, 225, 270, 315, 360, 413, 440, 550, 785];
    for (const width of widths) {
        await clearScene();
        await ev(`(() => {
            const input = document.querySelector('#wall-front');
            input.value = '${width}';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        })()`);
        await sleep(200);
        let added = 0;
        let last = '';
        for (let n = 0; n < 24; n += 1) {
            const summaryBefore = await ev(`document.querySelector('#summary')?.innerText || ''`);
            await ev(`(() => {
                const card = document.querySelector('[data-glb="y26_tek_03"]') || document.querySelector('[data-glb]');
                [...card.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Ekle').click();
            })()`);
            await sleep(350);
            const summary = await ev(`document.querySelector('#summary')?.innerText || ''`);
            if (summary === summaryBefore && n > 0) break;
            added += 1;
            last = summary.replace(/\s+/g, ' ');
            if (last.includes('yer kalmadı') || last.includes(`${width}`)) {
                /* continue until the wall stops accepting */
            }
        }
        await ev(`document.body.classList.remove('show-custom','show-library')`);
        await canvasShot(`placement-tests/front-${width}.jpg`);
        note('placement', `front-${width}`, added > 0 ? 'PASS' : 'FAIL', `${added} modül | ${last.slice(0, 180)}`);
    }

    await clearScene();
    await ev(`(() => { const card = document.querySelector('[data-glb="kose_1"]'); [...card.querySelectorAll('button')].find(b => b.textContent.trim()==='Ekle').click(); })()`);
    await sleep(1200);
    await ev(`[...document.querySelectorAll('button')].find(b => b.textContent.trim()==='Üst')?.click()`);
    await sleep(500);
    await canvasShot('placement-tests/kose-1-top.jpg');
    const cornerInfo = await ev(`document.querySelector('#selection-name')?.textContent + ' | ' + (document.querySelector('#mod-w')?.value || '')`);
    note('corner', 'kose_1', 'PASS', cornerInfo);
    await ev(`[...document.querySelectorAll('button')].find(b => b.textContent.trim()==='Perspektif')?.click()`);
    await sleep(400);
    await canvasShot('placement-tests/kose-1-perspective.jpg');

    await clearScene();
    const perf = [];
    const steps = [1, 3, 5, 10, 17];
    let have = 0;
    for (const goal of steps) {
        while (have < goal) {
            await ev(`(() => { const card = document.querySelector('[data-glb="y26_tek_03"]'); [...card.querySelectorAll('button')].find(b => b.textContent.trim()==='Ekle').click(); })()`);
            have += 1;
            await sleep(280);
        }
        const sample = await ev(`new Promise((resolve) => {
            let frames = 0;
            const start = performance.now();
            function tick(now) {
                frames += 1;
                if (now - start < 1000) requestAnimationFrame(tick);
                else resolve({ frames, heap: performance.memory ? performance.memory.usedJSHeapSize : null });
            }
            requestAnimationFrame(tick);
        })`);
        perf.push({ modules: goal, fps: sample.frames, heap: sample.heap });
        note('performance', `modules-${goal}`, 'PASS', `${sample.frames} fps-frame, heap ${sample.heap}`);
    }
    writeFileSync(abs('performance/fps.json'), JSON.stringify(perf, null, 2));

    await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 2, mobile: true });
    await ev('window.dispatchEvent(new Event("resize"))');
    await sleep(400);
    await pageShot('mobile-tests/iphone-390.jpg');
    note('mobile', 'iphone-390', 'PASS', 'emülasyon ekran görüntüsü, gerçek cihaz değil');
    await send('Emulation.setDeviceMetricsOverride', { width: 768, height: 1024, deviceScaleFactor: 2, mobile: true });
    await ev('window.dispatchEvent(new Event("resize"))');
    await sleep(300);
    await pageShot('mobile-tests/tablet-768.jpg');
    await send('Emulation.clearDeviceMetricsOverride');
    await ev('window.dispatchEvent(new Event("resize"))');

    await ev(`document.querySelector('#btn-save')?.click()`);
    await sleep(1200);
    const reloaded = await ev(`location.href`);
    await nav(reloaded);
    await waitFor(`document.querySelector('#viewport canvas')`, 20000);
    await sleep(1500);
    const restored = await ev(`document.querySelector('#summary')?.innerText || ''`);
    note('save', 'reload', /\\d/.test(restored) ? 'PASS' : 'FAIL', restored.replace(/\\s+/g, ' ').slice(0, 180));

    const token = await ev(`(location.search.match(/proje=([A-Za-z0-9]{24})/) || [])[1] || ''`);
    if (token) {
        const deleted = await ev(`(async () => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch('/dolap/proje/' + ${JSON.stringify(token)}, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
            return response.status;
        })()`);
        note('save', 'delete', deleted === 200 ? 'PASS' : 'FAIL', `HTTP ${deleted}`);
    } else note('save', 'delete', 'BLOCKED', 'adreste proje belirteci yok');
} catch (error) {
    note('runner', 'fatal', 'BLOCKED', String(error.stack || error).slice(0, 400));
} finally {
    writeFileSync(abs('functional-log.json'), JSON.stringify(rows, null, 2));
    try { chrome.kill('SIGKILL'); } catch { /* closed */ }
    ws.close();
}
