import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import { mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/ultra-realism/reference/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9366;
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
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.92) || ''`);
    if (!data.startsWith('data:image')) throw new Error(`kare yok: ${file}`);
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
    console.log('shot', file, data.length);
}
async function view(name) {
    await ev(`document.querySelector('[data-view="${name}"]')?.click()`);
    await sleep(500);
}
async function setDoors(wantOpen) {
    const label = await ev(`document.querySelector('#mod-doors-all')?.textContent || ''`);
    const isOpen = String(label).includes('kapat');
    console.log('doors', label, 'want', wantOpen);
    if (wantOpen !== isOpen) {
        await ev(`document.querySelector('#mod-doors-all').click()`);
        await sleep(800);
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
    await waitFor(`document.querySelector('[data-glb="y26_cift_01"]') && document.querySelector('script[src*="wardrobe-studio.js?v=165"]')`);
    await sleep(400);
    await ev(`document.querySelector('[data-glb="y26_cift_01"]').click()`);
    await waitFor(`document.querySelector('#inspector-body h2')`);
    await sleep(1200);
    const fps = await ev(`new Promise((resolve) => { let n=0; const t0=performance.now(); const step=()=>{ n+=1; if(performance.now()-t0<1000) requestAnimationFrame(step); else resolve(String(n)); }; requestAnimationFrame(step); })`);
    console.log('design fps', fps);
    await setDoors(false);
    await view('front');
    await shot('01-front-closed.jpg');
    await setDoors(true);
    await view('front');
    await shot('02-front-open.jpg');
    await view('right45');
    await shot('03-right45.jpg');
    await view('left45');
    await shot('04-left45.jpg');
    await view('shelf');
    await shot('05-shelf-macro.jpg');
    const drawerLabel = await ev(`document.querySelector('#mod-drawers')?.textContent || ''`);
    if (String(drawerLabel).includes('aç')) {
        await ev(`document.querySelector('#mod-drawers').click()`);
        await sleep(800);
    }
    await view('drawer');
    await shot('06-drawer-macro.jpg');
    await view('grain');
    await shot('07-wood-macro.jpg');
    await ev(`document.querySelector('[data-door="glass"]')?.click()`);
    await sleep(600);
    await setDoors(false);
    await view('glass');
    await shot('08-glass-close.jpg');
    await setDoors(true);
    await view('front');
    await shot('09-accessories-open.jpg');
    await ev(`window.__ultraPasses = 32; window.__ultraMax = 1280; 'ok'`);
    await ev(`(() => { window.__renderHref=''; const orig=HTMLAnchorElement.prototype.click; HTMLAnchorElement.prototype.click=function(){ if(String(this.download||'').includes('render')&&String(this.href||'').startsWith('data:image')) window.__renderHref=this.href; return orig.call(this); }; document.querySelector('#btn-shot')?.click(); return 'go'; })()`);
    const started = Date.now();
    let href = '';
    while (Date.now() - started < 420000) {
        href = await ev(`window.__renderHref || ''`);
        const label = await ev(`document.querySelector('#btn-shot')?.textContent || ''`);
        if (href.startsWith('data:image')) break;
        await sleep(2000);
        if ((Date.now() - started) % 10000 < 2100) console.log('render', label);
    }
    if (!href.startsWith('data:image')) throw new Error('path yok');
    writeFileSync(abs('10-path.png'), Buffer.from(href.split(',')[1], 'base64'));
    const stats = await ev(`JSON.stringify(window.__renderStats || {})`);
    writeFileSync(abs('render-stats.json'), stats || '{}');
    console.log('stats', stats);
    const left = createHash('sha256').update(readFileSync(abs('04-left45.jpg'))).digest('hex');
    const right = createHash('sha256').update(readFileSync(abs('03-right45.jpg'))).digest('hex');
    console.log('45', left === right ? 'SAME' : 'DIFFERENT', left.slice(0, 12), right.slice(0, 12));
    const colors = await ev(`(() => { const search=document.querySelector('[data-search="shelf"]'); if(search){ search.value='Ceviz'; search.dispatchEvent(new Event('input',{bubbles:true})); } const button=[...document.querySelectorAll('[data-picker="shelf"] [data-decor]')].find((el)=>/ceviz/i.test(el.textContent||'')); button?.click(); return 'clicked'; })()`);
    await sleep(400);
    const labels = await ev(`JSON.stringify({door:document.querySelector('[data-picker="door"] label')?.textContent||'', back:document.querySelector('[data-picker="back"] label')?.textContent||'', shelf:document.querySelector('[data-picker="shelf"] label')?.textContent||'', drawer:document.querySelector('[data-picker="drawer"] label')?.textContent||''})`);
    console.log('colors', colors, labels);
    await ev(`document.querySelector('#btn-save')?.click()`);
    await sleep(300);
    const save = await ev(`JSON.stringify({toast:document.querySelector('#toast')?.textContent||document.body.innerText.slice(0,0), url:location.search})`);
    console.log('save', save);
    console.log('design fps', fps);
} finally {
    ws.close();
    chrome.kill();
    rmSync(abs('chrome'), { recursive: true, force: true });
}
