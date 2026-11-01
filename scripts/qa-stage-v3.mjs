import { spawn } from 'node:child_process';
import { copyFileSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/ultra-realism/stage-v3/', import.meta.url);
const PREV = new URL('../test-results/ultra-realism/reference-v2/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9372;
const password = process.env.QA_PASSWORD || '';
if (!password) throw new Error('QA_PASSWORD yok');
function abs(path) { return new URL(path, OUT).pathname; }
function prev(path) { return new URL(path, PREV).pathname; }
mkdirSync(abs('.'), { recursive: true });
for (const [from, to] of [
    ['06-wood.jpg', 'wood-v2-before.jpg'],
    ['07-glass.jpg', 'glass-v2-raster-before.jpg'],
    ['07-glass-path.png', 'glass-v2-path-before.png'],
    ['10-compare-bounces4-denoise.png', 'noise-v2-denoise-before.png'],
    ['08-accessories.jpg', 'shirt-v2-before.jpg'],
]) {
    try { copyFileSync(prev(from), abs(to)); } catch (error) { console.warn('kopya yok', from, error.message); }
}
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
async function setDoors(wantOpen) {
    const label = await ev(`document.querySelector('#mod-doors-all')?.textContent || ''`);
    const isOpen = String(label).includes('kapat');
    if (wantOpen !== isOpen) {
        await ev(`document.querySelector('#mod-doors-all').click()`);
        await sleep(700);
    }
}
async function trace(file, opts) {
    const led = opts.led || 'soft';
    const env = opts.env || 'gradient';
    const frame = opts.frame ? 'true' : 'false';
    const skip = opts.skip !== false;
    const sigma = opts.sigma || 0;
    await ev(`window.__ultraPasses=${opts.passes}; window.__ultraMax=${opts.max || 800}; window.__traceBounces=${opts.bounces || 4}; window.__traceTransmissive=${opts.transmissive || 5}; window.__skipDenoise=${skip}; window.__denoiseSigma=${sigma}; window.__traceLeds=${JSON.stringify(led)}; window.__traceEnv=${JSON.stringify(env)}; window.__frameRender=${frame}; window.__renderHref=''; 'ok'`);
    await ev(`(() => { if (!window.__shotHook) { const orig = HTMLAnchorElement.prototype.click; window.__shotHook = orig; HTMLAnchorElement.prototype.click = function(){ if (String(this.download||'').includes('render') && String(this.href||'').startsWith('data:image')) window.__renderHref=this.href; return orig.call(this); }; } document.querySelector('#btn-shot')?.click(); return 'go'; })()`);
    const started = Date.now();
    let href = '';
    while (Date.now() - started < 240000) {
        href = await ev(`window.__renderHref || ''`);
        const label = await ev(`document.querySelector('#btn-shot')?.textContent || ''`);
        if (href.startsWith('data:image')) break;
        await sleep(1500);
        if ((Date.now() - started) % 9000 < 1600) console.log(file, label);
    }
    if (!href.startsWith('data:image')) throw new Error(`path yok ${file}`);
    writeFileSync(abs(file), Buffer.from(href.split(',')[1], 'base64'));
    const stats = JSON.parse(await ev(`JSON.stringify(window.__renderStats||{})`) || '{}');
    console.log('trace', file, JSON.stringify(stats));
    return stats;
}
const report = {};
await send('Page.enable');
await send('Runtime.enable');
try {
    await nav(`${BASE}/login`);
    await waitFor(`document.querySelector('#email')`);
    await ev(`document.querySelector('#email').value='erkanulker0@gmail.com'; document.querySelector('#password').value=${JSON.stringify(password)}; document.querySelector('#login-btn').click()`);
    await waitFor(`location.pathname !== '/login'`);
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`document.querySelector('script[src*="v=173"]') && document.querySelector('[data-glb="y26_cift_01"]')`);
    await ev(`document.querySelector('[data-glb="y26_cift_01"]').click()`);
    await waitFor(`document.querySelector('#mod-drawers')`);
    await sleep(1200);
    await setDoors(false);
    for (const mode of ['catalog', 'mismatch', 'matched']) {
        await ev(`window.__woodMode=${JSON.stringify(mode)}; window.__rebuildCabinet(); 'ok'`);
        if (mode === 'matched') await waitFor(`window.__oakReady === true`, 15000).catch(() => {});
        await sleep(900);
        await ev(`window.__setCameraView('grain')`);
        await sleep(400);
        await shot(`wood-${mode}.jpg`);
    }
    await setDoors(true);
    await ev(`window.__setCameraView('shelf')`);
    await sleep(400);
    await shot('wood-edge-matched.jpg');
    await ev(`document.querySelector('[data-door="glass"]').click()`);
    await sleep(800);
    await setDoors(false);
    await ev(`window.__setCameraView('glass')`);
    await sleep(500);
    await shot('glass-raster.jpg');
    report.glassReframe = await trace('glass-path-reframe.png', { passes: 6, max: 780, frame: true, skip: true, led: 'soft' });
    await ev(`window.__setCameraView('glass')`);
    await sleep(300);
    report.glassKeep = await trace('glass-path-macro.png', { passes: 6, max: 780, frame: false, skip: true, led: 'soft', env: 'room' });
    await ev(`document.querySelector('[data-door="solid"]').click()`);
    await sleep(700);
    await setDoors(true);
    await ev(`window.__setCameraView('front')`);
    await sleep(400);
    await shot('led-design-raster.jpg');
    report.noiseRaster = await trace('noise-led-raster.png', { passes: 6, max: 780, skip: true, led: 'raster' });
    await ev(`window.__setCameraView('front')`);
    report.noiseRasterDenoise = await trace('noise-led-raster-denoise.png', { passes: 6, max: 780, skip: false, sigma: 1.6, led: 'raster' });
    await ev(`window.__setCameraView('front')`);
    report.noiseOff = await trace('noise-led-off.png', { passes: 6, max: 780, skip: true, led: 'off' });
    await ev(`window.__setCameraView('front')`);
    report.noiseSoft = await trace('noise-led-soft.png', { passes: 6, max: 780, skip: true, led: 'soft' });
    await ev(`window.__setCameraView('front')`);
    report.noiseSoftWide = await trace('noise-led-soft-denoise4.png', { passes: 6, max: 780, skip: false, sigma: 4, led: 'soft' });
    await ev(`window.__shirtOnly=true; window.__rebuildCabinet(); 'ok'`);
    await sleep(800);
    await setDoors(true);
    await ev(`window.__setCameraView('shirt')`);
    await sleep(500);
    await shot('shirt-three-quarter.jpg');
    await ev(`window.__setCameraView('shirt-side')`);
    await sleep(400);
    await shot('shirt-side.jpg');
    report.shirt = await trace('shirt-path.png', { passes: 6, max: 780, skip: true, led: 'soft', env: 'room' });
    writeFileSync(abs('stats.json'), JSON.stringify(report, null, 2));
    console.log('DONE');
} catch (error) {
    console.error(error);
    process.exitCode = 1;
} finally {
    try { ws.close(); } catch { /* closed */ }
    chrome.kill();
    await sleep(400);
    try { rmSync(abs('chrome'), { recursive: true, force: true, maxRetries: 5, retryDelay: 200 }); } catch { /* profile */ }
}
