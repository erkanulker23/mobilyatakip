import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/reference-quality/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9346;
const password = process.env.QA_PASSWORD || 'password';

function abs(path) {
    return new URL(path, OUT).pathname;
}
mkdirSync(abs('shots'), { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${abs('chrome-profile-rerender')}`,
    '--window-size=1440,900',
    '--use-angle=metal',
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

await send('Page.enable');
await send('Runtime.enable');

try {
    await nav(`${BASE}/login`);
    if (await ev(`!!document.querySelector('#email')`)) {
        await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
        await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
        await ev(`document.querySelector('#login-btn').click()`);
        await waitFor(`location.pathname !== '/login'`, 15000);
    }
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`window.__studio && document.querySelector('[data-glb="y26_tek_01"]')`, 30000);
    const script = await ev(`document.querySelector('script[src*="wardrobe-studio"]')?.src || ''`);
    await ev(`window.__studio.clear()`);
    await ev(`window.__studio.add('y26_tek_01')`);
    await waitFor(`window.__studio.stats().ids.includes('y26_tek_01')`, 20000);
    await ev(`window.__studio.setDoor('none')`);
    await sleep(400);
    await ev(`window.__setCameraView('left45')`);
    await sleep(300);
    const raster = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.8) || ''`);
    writeFileSync(abs('shots/tek-side-after.jpg'), Buffer.from(raster.split(',')[1], 'base64'));
    await ev(`window.__setCameraView('left45')`);
    await sleep(200);
    const started = Date.now();
    await ev(`document.querySelector('#btn-shot')?.click()`);
    await waitFor(`!document.querySelector('#btn-shot')?.disabled`, 180000);
    const stats = await ev(`window.__renderStats`);
    const image = await ev(`window.__renderImage || ''`);
    if (image.startsWith('data:image')) {
        const ext = image.includes('image/jpeg') ? 'jpg' : 'png';
        writeFileSync(abs(`shots/ultra-tek-after.${ext}`), Buffer.from(image.split(',')[1], 'base64'));
    }
    await ev(`document.querySelector('#mod-drawers')?.click()`);
    await sleep(400);
    await ev(`window.__setCameraView('drawer')`);
    await sleep(300);
    const drawer = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.8) || ''`);
    if (drawer.startsWith('data:image')) {
        writeFileSync(abs('shots/tek-drawer-hollow.jpg'), Buffer.from(drawer.split(',')[1], 'base64'));
    }
    writeFileSync(abs('rerender.json'), JSON.stringify({
        script,
        ms: Date.now() - started,
        stats,
        imageBytes: image.length,
    }, null, 2));
    console.log(JSON.stringify({ script, ms: Date.now() - started, stats }, null, 2));
} finally {
    chrome.kill('SIGTERM');
    ws.close();
}
