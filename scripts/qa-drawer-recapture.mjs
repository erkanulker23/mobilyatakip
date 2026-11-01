import { spawn } from 'node:child_process';
import { rmSync, writeFileSync, mkdirSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/ultra-realism/reference-v2/', import.meta.url);
const password = process.env.QA_PASSWORD || '';
if (!password) throw new Error('QA_PASSWORD yok');
function abs(path) { return new URL(path, OUT).pathname; }
mkdirSync(abs('.'), { recursive: true });
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', ['--headless=new', '--remote-debugging-port=9368', `--user-data-dir=${abs('chrome2')}`, '--window-size=1440,900', '--use-angle=metal', 'about:blank'], { stdio: 'ignore' });
async function target() {
    for (let i = 0; i < 40; i += 1) {
        try {
            const list = await (await fetch('http://127.0.0.1:9368/json')).json();
            const page = list.find((item) => item.type === 'page');
            if (page) return page;
        } catch { /* starting */ }
        await sleep(250);
    }
    throw new Error('Chrome hedefi yok');
}
const page = await target();
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { ws.addEventListener('open', resolve); ws.addEventListener('error', reject); });
let seq = 0;
const pending = new Map();
ws.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    if (!data.id || !pending.has(data.id)) return;
    const done = pending.get(data.id);
    pending.delete(data.id);
    if (data.error) done.reject(new Error(JSON.stringify(data.error)));
    else done.resolve(data.result);
});
function send(method, params = {}) {
    const id = ++seq;
    return new Promise((resolve, reject) => { pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params })); });
}
async function ev(expression) {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    return result.result?.value;
}
try {
    await send('Page.enable');
    await send('Page.navigate', { url: `${BASE}/login` });
    await sleep(800);
    for (let i = 0; i < 40 && !await ev(`!!document.querySelector('#email')`); i += 1) await sleep(200);
    await ev(`document.querySelector('#email').value='erkanulker0@gmail.com'; document.querySelector('#password').value=${JSON.stringify(password)}; document.querySelector('#login-btn').click()`);
    for (let i = 0; i < 40 && await ev(`location.pathname`) === '/login'; i += 1) await sleep(200);
    await send('Page.navigate', { url: `${BASE}/dolap/tasarla` });
    for (let i = 0; i < 50 && !await ev(`!!document.querySelector('script[src*="v=170"]') && !!document.querySelector('[data-glb="y26_cift_01"]')`); i += 1) await sleep(200);
    await ev(`document.querySelector('[data-glb="y26_cift_01"]').click()`);
    for (let i = 0; i < 40 && !await ev(`!!document.querySelector('#mod-drawers')`); i += 1) await sleep(200);
    await sleep(800);
    const label = await ev(`document.querySelector('#mod-drawers').textContent`);
    if (String(label).includes('aç')) await ev(`document.querySelector('#mod-drawers').click()`);
    await sleep(900);
    await sleep(600);
    await ev(`document.querySelector('[data-view="drawer"]').click()`);
    await sleep(500);
    console.log('cam', await ev(`JSON.stringify(window.__camDebug || null)`));
    const data = await ev(`document.querySelector('#viewport canvas').toDataURL('image/jpeg', 0.92)`);
    writeFileSync(abs('04-drawer-open.jpg'), Buffer.from(String(data).split(',')[1], 'base64'));
    console.log('drawer', String(data).length, label);
} catch (error) {
    console.error(error);
    process.exitCode = 1;
} finally {
    try { ws.close(); } catch { /* closed */ }
    chrome.kill();
    await sleep(400);
    rmSync(abs('chrome2'), { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
}
