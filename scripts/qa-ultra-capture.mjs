import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const phase = process.argv[2] === 'after' ? 'after' : 'before';
const BASE = 'http://127.0.0.1:8765';
const OUT = new URL(`../test-results/ultra-realism/${phase}/`, import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = phase === 'after' ? 9356 : 9355;
const password = process.env.QA_PASSWORD || '';
if (!password) throw new Error('QA_PASSWORD yok');

function abs(path) {
    return new URL(path, OUT).pathname;
}
mkdirSync(abs('.'), { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${abs('chrome')}`,
    '--window-size=1440,900',
    '--disable-gpu-sandbox',
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
    if (result.exceptionDetails) {
        throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    }
    return result.result?.value;
}
async function nav(url) {
    await send('Page.navigate', { url });
    await sleep(700);
    for (let i = 0; i < 40; i += 1) {
        if (await ev('document.readyState') === 'complete') return;
        await sleep(200);
    }
}
async function waitFor(expression, ms = 40000) {
    const start = Date.now();
    while (Date.now() - start < ms) {
        if (await ev(`!!(${expression})`)) return;
        await sleep(250);
    }
    throw new Error(`zaman aşımı: ${expression}`);
}
async function shot(file) {
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.86) || ''`);
    if (!data.startsWith('data:image')) throw new Error(`kanvas yok: ${file}`);
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
    console.log('shot', file, data.length);
}
async function view(name) {
    await ev(`document.querySelector('[data-view="${name}"]')?.click()`);
    await sleep(450);
}

await send('Page.enable');
await send('Runtime.enable');

try {
    await nav(`${BASE}/login`);
    await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
    await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
    await ev(`document.querySelector('#login-btn').click()`);
    await waitFor(`location.pathname !== '/login'`, 15000);
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`document.querySelector('[data-glb="y26_tek_01"]') && document.querySelector('#viewport canvas')`, 60000);
    await sleep(800);
    const token = await ev(`new URLSearchParams(location.search).get('proje') || ''`);
    if (token) throw new Error('kayıtlı proje açıldı, görüntü alınmadı');

    async function place(id) {
        if ((await ev(`document.querySelectorAll('#production b').length`)) > 0) {
            await ev(`document.querySelector('#act-delete')?.click()`);
            await sleep(400);
        }
        await ev(`document.querySelector('[data-glb="${id}"]')?.click()`);
        await waitFor(`document.querySelector('#inspector-body h2')`, 20000);
        await sleep(900);
    }

    await place('y26_tek_01');
    for (const name of ['front', 'right45', 'left45', 'top']) {
        await view(name);
        await shot(`y26_tek_01-${name}.jpg`);
    }
    await ev(`document.querySelector('#mod-drawers')?.click()`);
    await sleep(700);
    await view('right45');
    await shot('y26_tek_01-drawers-right45.jpg');
    await ev(`document.querySelector('[data-door="glass"]')?.click()`);
    await sleep(500);
    await ev(`document.querySelector('#mod-doors-all')?.click()`);
    await sleep(700);
    await view('front');
    await shot('y26_tek_01-glass-front.jpg');

    await place('y26_cift_01');
    await ev(`document.querySelector('[data-door="solid"]')?.click()`);
    await sleep(400);
    for (const name of ['front', 'right45']) {
        await view(name);
        await shot(`y26_cift_01-${name}.jpg`);
    }
    console.log('DONE', phase);
} finally {
    ws.close();
    chrome.kill();
}
