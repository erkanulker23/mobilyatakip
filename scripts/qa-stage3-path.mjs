import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/ultra-realism/stage3/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9362;
const password = process.env.QA_PASSWORD || '';
if (!password) throw new Error('QA_PASSWORD yok');
function abs(path) { return new URL(path, OUT).pathname; }
mkdirSync(abs('.'), { recursive: true });
const chrome = spawn(CHROME, ['--headless=new', `--remote-debugging-port=${PORT}`, `--user-data-dir=${abs('chrome')}`, '--window-size=1440,900', '--use-angle=metal', 'about:blank'], { stdio: 'ignore' });
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
await new Promise((resolve, reject) => { ws.addEventListener('open', resolve); ws.addEventListener('error', reject); });
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
    return new Promise((resolve, reject) => { pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params })); });
}
async function ev(expression) {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    return result.result?.value;
}
async function nav(url) {
    await send('Page.navigate', { url });
    await sleep(700);
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
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.9) || ''`);
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
    console.log('shot', file);
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
    await waitFor(`location.pathname !== '/login'`);
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`document.querySelector('[data-glb="y26_tek_01"]')`);
    await sleep(500);
    await ev(`document.querySelector('[data-glb="y26_tek_01"]')?.click()`);
    await waitFor(`document.querySelector('#inspector-body h2')`);
    await sleep(1000);
    await view('front');
    await shot('y26_tek_01-front.jpg');
    await view('shelf');
    await shot('y26_tek_01-shelf-macro.jpg');
    await ev(`document.querySelector('[data-door="glass"]')?.click()`);
    await sleep(300);
    await ev(`document.querySelector('#mod-doors-all')?.click()`);
    await sleep(700);
    await view('front');
    await shot('y26_tek_01-glass-front.jpg');
    await ev(`(() => { window.__renderHref=''; const orig=HTMLAnchorElement.prototype.click; HTMLAnchorElement.prototype.click=function(){ if(String(this.download||'').includes('render')&&String(this.href||'').startsWith('data:image')) window.__renderHref=this.href; return orig.call(this); }; document.querySelector('#btn-shot')?.click(); return 'go'; })()`);
    const started = Date.now();
    let href = '';
    let saw = false;
    while (Date.now() - started < 420000) {
        href = await ev(`window.__renderHref || ''`);
        const label = await ev(`document.querySelector('#btn-shot')?.textContent || ''`);
        if (/Render \\d|Işık|Netleştir/.test(label)) saw = true;
        if (href.startsWith('data:image')) break;
        if (saw && label === 'Render al') break;
        await sleep(2000);
        if ((Date.now() - started) % 20000 < 2100) console.log('render', label);
    }
    if (!href.startsWith('data:image')) throw new Error('path yok');
    writeFileSync(abs('y26_tek_01-glass-path.png'), Buffer.from(href.split(',')[1], 'base64'));
    console.log('path', href.length);
} finally {
    ws.close();
    chrome.kill();
}
