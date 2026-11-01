import { spawn } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/ultra-realism/stage-v3/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9374;
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
    await sleep(1200);
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
    await sleep(350);
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.92) || ''`);
    if (!data.startsWith('data:image')) throw new Error(`kare yok: ${file}`);
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
    console.log('shot', file, data.length);
}
async function view(name) {
    await ev(`window.__setCameraView(${JSON.stringify(name)})`);
    await sleep(500);
}
async function wood(mode) {
    await ev(`window.__woodMode=${JSON.stringify(mode)}; window.__rebuildCabinet(); 'ok'`);
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
async function trace(file, opts) {
    const fields = {
        __ultraPasses: opts.passes,
        __ultraMax: opts.max || 1200,
        __traceBounces: opts.bounces ?? 4,
        __traceTransmissive: opts.transmissive ?? 5,
        __denoiseMode: opts.denoise || 'off',
        __denoiseSigma: opts.sigma || 1.6,
        __skipDenoise: opts.denoise === 'off',
        __traceLeds: opts.leds || 'soft',
        __traceKey: opts.key === true,
        __traceEnv: opts.env || 'gradient',
        __traceReframe: opts.reframe === true,
        __traceW: opts.w || 0,
        __traceH: opts.h || 0,
        __renderHref: '',
    };
    await ev(`Object.assign(window, ${JSON.stringify(fields)}); 'ok'`);
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
    console.log('login', await ev(`location.href + ' | ' + (document.body && document.body.innerText || '').slice(0, 120)`));
    await waitFor(`document.querySelector('#email')`);
    await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
    await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
    await ev(`document.querySelector('#login-btn').click()`);
    await waitFor(`location.pathname !== '/login'`);
    await nav(`${BASE}/dolap/tasarla`);
    await waitFor(`document.querySelector('[data-glb="y26_cift_01"]') && document.querySelector('script[src*="wardrobe-studio.js?v=178"]')`);
    await ev(`window.__qaShot = true; window.__shirtOnly = true; 'ok'`);
    await ev(`document.querySelector('[data-glb="y26_cift_01"]').click()`);
    await waitFor(`document.querySelector('#mod-doors-all')`);
    await sleep(1500);
    await ev(`window.__rebuildCabinet()`);
    await sleep(800);
    await setDoors(true);

    await wood('mismatch');
    await view('grain');
    await shot('wood-01-mismatch.jpg');
    await wood('catalog');
    await view('grain');
    await shot('wood-02-catalog.jpg');
    await wood('oak');
    await waitFor(`window.__oakReady === true`, 20000);
    await ev(`window.__rebuildCabinet()`);
    await sleep(600);
    await view('grain');
    await shot('wood-03-oak.jpg');
    await view('shelf');
    await shot('wood-04-oak-shelf.jpg');

    await ev(`document.querySelector('[data-door="glass"]')?.click()`);
    await sleep(800);
    await setDoors(false);
    await view('glass');
    console.log('cam', await ev(`JSON.stringify(window.__camDebug||null)`));
    await shot('glass-01-raster.jpg');
    report.glassGradient = await trace('glass-02-path-gradient.png', { passes: 8, max: 1100, env: 'gradient', denoise: 'off', leds: 'soft' });
    await view('glass');
    report.glassHdri = await trace('glass-03-path-hdri.png', { passes: 8, max: 1100, env: 'hdri', denoise: 'off', leds: 'soft' });
    await view('glass');
    report.glassReframe = await trace('glass-04-path-reframe.png', { passes: 6, max: 900, env: 'hdri', denoise: 'off', leds: 'soft', reframe: true });

    await ev(`document.querySelector('[data-door="solid"]')?.click()`);
    await sleep(700);
    await setDoors(true);
    await view('front');
    await shot('noise-00-raster.jpg');
    const noise = { passes: 8, w: 960, h: 600, bounces: 4, transmissive: 5, env: 'gradient' };
    report.noiseScene = await trace('noise-01-led-scene-key.png', { ...noise, leds: 'scene', key: true, denoise: 'off' });
    report.noiseOff = await trace('noise-02-led-off.png', { ...noise, leds: 'off', denoise: 'off' });
    report.noiseSoft = await trace('noise-03-led-soft.png', { ...noise, leds: 'soft', denoise: 'off' });
    report.noiseSigma = await trace('noise-04-denoise-sigma5.png', { ...noise, leds: 'soft', denoise: 'gpu', sigma: 5 });
    report.noiseWeak = await trace('noise-05-denoise-sigma1.png', { ...noise, leds: 'soft', denoise: 'gpu', sigma: 1.6 });

    await view('shirt');
    await shot('shirt-01-front.jpg');
    await view('shirt-side');
    await shot('shirt-02-side.jpg');

    const save = await ev(`(() => { document.querySelector('#btn-save')?.click(); return 'go'; })()`);
    await sleep(400);
    report.save = await ev(`JSON.stringify({ toast: document.querySelector('#toast')?.textContent || '', url: location.search })`);
    console.log('save', save, report.save);
    writeFileSync(abs('stats.json'), JSON.stringify(report, null, 2));
    console.log('DONE');
} catch (error) {
    console.error(error);
    process.exitCode = 1;
} finally {
    try { ws.close(); } catch { /* closed */ }
    chrome.kill();
    await sleep(400);
    rmSync(abs('chrome'), { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
}
