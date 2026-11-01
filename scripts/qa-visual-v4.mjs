import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/visual-quality-v4/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9371;
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
    if (data.method === 'Runtime.consoleAPICalled') {
        const text = (data.params?.args || []).map((item) => item.value || item.description || '').join(' ');
        if (text) console.log('page', text.slice(0, 500));
    }
    if (data.method === 'Runtime.exceptionThrown') {
        console.log('exception', data.params?.exceptionDetails?.text, data.params?.exceptionDetails?.exception?.description);
    }
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
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.92) || ''`);
    if (!data.startsWith('data:image')) throw new Error(`kare yok: ${file}`);
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
    console.log('shot', file, data.length);
}
async function view(name) {
    await ev(`document.querySelector('[data-view="${name}"]')?.click()`);
    await sleep(700);
}
async function setDoors(wantOpen) {
    const label = await ev(`document.querySelector('#mod-doors-all')?.textContent || ''`);
    const isOpen = String(label).includes('kapat');
    if (wantOpen !== isOpen) {
        await ev(`document.querySelector('#mod-doors-all').click()`);
        await sleep(900);
    }
}
await send('Page.enable');
await send('Runtime.enable');
try {
    await nav(`${BASE}/login`);
    await waitFor(`document.querySelector('#email')`);
    await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
    await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
    await ev(`document.querySelector('#login-btn').click()`);
    await waitFor(`location.pathname !== '/login'`);
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`document.querySelector('[data-glb="y26_cift_01"]') && document.querySelector('script[src*="wardrobe-studio.js?v=172"]')`);
    await sleep(500);
    await ev(`window.__qaShot = true`);
    await ev(`document.querySelector('[data-glb="y26_cift_01"]').click()`);
    await waitFor(`document.querySelector('#inspector-body h2')`);
    await waitFor(`window.__woodReady === true`, 20000);
    await sleep(1500);
    const probe = await ev(`JSON.stringify({doors: document.querySelector('#mod-doors-all')?.textContent || '', wood: window.__woodReady, props: document.body.innerText.includes('y26')})`);
    console.log('probe', probe);
    await setDoors(false);
    await view('front');
    await shot('01-front.jpg');
    await view('right45');
    await shot('02-right45.jpg');
    await view('left45');
    await shot('03-left45.jpg');
    await view('grain');
    await shot('04-wood-macro.jpg');
    await view('shelf');
    await shot('05-shelf-macro.jpg');
    const drawerLabel = await ev(`document.querySelector('#mod-drawers')?.textContent || ''`);
    if (String(drawerLabel).includes('aç')) {
        await ev(`document.querySelector('#mod-drawers').click()`);
        await sleep(900);
    }
    await view('drawer');
    await shot('06-drawer-macro.jpg');
    await setDoors(true);
    await view('front');
    await shot('08-accessories.jpg');
    await setDoors(false);
    await view('front');
    await ev(`window.__ultraPasses = 12; window.__ultraMax = 1280; window.__traceBudgetMs = 90000; 'ok'`);
    await ev(`(() => { window.__renderHref=''; const orig=HTMLAnchorElement.prototype.click; HTMLAnchorElement.prototype.click=function(){ if(String(this.download||'').includes('render')&&String(this.href||'').startsWith('data:image')) window.__renderHref=this.href; return orig.call(this); }; document.querySelector('#btn-shot')?.click(); return 'go'; })()`);
    let started = Date.now();
    let href = '';
    while (Date.now() - started < 120000) {
        href = await ev(`window.__renderHref || ''`);
        const label = await ev(`document.querySelector('#btn-shot')?.textContent || ''`);
        if (href.startsWith('data:image')) break;
        await sleep(2000);
        if ((Date.now() - started) % 10000 < 2100) console.log('render', label);
    }
    if (!href.startsWith('data:image')) throw new Error('path yok');
    writeFileSync(abs('09-path.png'), Buffer.from(href.split(',')[1], 'base64'));
    const stats = await ev(`JSON.stringify(window.__renderStats || {})`);
    writeFileSync(abs('render-stats.json'), stats || '{}');
    console.log('stats', stats);
    await ev(`document.querySelector('[data-door="glass"]')?.click()`);
    await sleep(800);
    await setDoors(false);
    await view('glass');
    await shot('07-glass-macro.jpg');
    await ev(`window.__renderHref=''; window.__ultraPasses = 8; window.__traceBudgetMs = 70000; 'ok'`);
    await ev(`document.querySelector('#btn-shot')?.click()`);
    const glassStarted = Date.now();
    let glassHref = '';
    while (Date.now() - glassStarted < 90000) {
        glassHref = await ev(`window.__renderHref || ''`);
        if (glassHref.startsWith('data:image')) break;
        await sleep(2000);
        if ((Date.now() - glassStarted) % 10000 < 2100) console.log('glass-render', await ev(`document.querySelector('#btn-shot')?.textContent || ''`));
    }
    if (!glassHref.startsWith('data:image')) throw new Error('cam path yok');
    writeFileSync(abs('07-glass-path.png'), Buffer.from(glassHref.split(',')[1], 'base64'));
    const glassStats = await ev(`JSON.stringify(window.__renderStats || {})`);
    writeFileSync(abs('glass-render-stats.json'), glassStats || '{}');
    console.log('glass-stats', glassStats);
} finally {
    ws.close();
    chrome.kill();
}
