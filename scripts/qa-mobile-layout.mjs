import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/fixes/', import.meta.url);
const abs = (path) => new URL(path, OUT).pathname;
const password = process.env.QA_PASSWORD || '';
mkdirSync(abs('.'), { recursive: true });
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', [
    '--headless=new', '--remote-debugging-port=9342', '--user-data-dir=/tmp/meemare-mobile',
    '--disable-gpu-sandbox', 'about:blank',
], { stdio: 'ignore' });
let page;
for (let i = 0; i < 40 && !page; i += 1) {
    try { page = (await (await fetch('http://127.0.0.1:9342/json')).json()).find((item) => item.type === 'page'); } catch { /* */ }
    if (!page) await sleep(250);
}
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve) => ws.addEventListener('open', resolve));
let seq = 0;
const pending = new Map();
ws.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    if (!data.id || !pending.has(data.id)) return;
    const job = pending.get(data.id);
    pending.delete(data.id);
    job(data.result);
});
const send = (method, params = {}) => new Promise((resolve) => {
    const id = ++seq;
    pending.set(id, resolve);
    ws.send(JSON.stringify({ id, method, params }));
});
async function ev(expression) {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text || 'js');
    return result.result?.value;
}
async function shot(file) {
    const data = await send('Page.captureScreenshot', { format: 'jpeg', quality: 60 });
    writeFileSync(abs(file), Buffer.from(data.data, 'base64'));
}
const sizes = [[375, 812], [390, 844], [430, 932], [768, 1024], [1366, 768]];
const report = [];
try {
    await send('Page.navigate', { url: `${BASE}/login` });
    await sleep(700);
    await ev(`document.querySelector('#email').value='erkanulker0@gmail.com';document.querySelector('#password').value=${JSON.stringify(password)};document.querySelector('#login-btn').click()`);
    await sleep(800);
    for (const [width, height] of sizes) {
        const mobile = width <= 900;
        await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile });
        await send('Page.navigate', { url: `${BASE}/dolap/tasarla?proje=Kw9Yfsvdop0PY4UTvSehlAhK` });
        for (let i = 0; i < 40; i += 1) {
            if (await ev(`document.querySelector('#viewport canvas') && document.querySelector('#summary')`)) break;
            await sleep(250);
        }
        await sleep(600);
        const box = await ev(`(() => { const canvas=document.querySelector('#viewport canvas'); const rect=canvas.getBoundingClientRect(); const stage=document.querySelector('.stage').getBoundingClientRect(); return { canvas:[Math.round(rect.width), Math.round(rect.height)], stage:[Math.round(stage.width), Math.round(stage.height)], nav:[...document.querySelectorAll('.mobile-nav .btn')].map(b=>b.textContent), save:getComputedStyle(document.querySelector('#btn-save')).display, panels:getComputedStyle(document.querySelector('#library')).position }; })()`);
        const visible = box.canvas[0] > 200 && box.canvas[1] > 200 && box.stage[1] > 200;
        report.push({ size: `${width}x${height}`, visible, ...box });
        console.log(visible ? 'OK' : 'FAIL', `${width}x${height}`, JSON.stringify(box));
        await shot(`viewport-${width}x${height}.jpg`);
    }
    await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    await ev(`document.querySelector('#open-library').click()`);
    await sleep(300);
    const opened = await ev(`document.querySelector('#library').classList.contains('is-open') && document.querySelector('#viewport canvas').getBoundingClientRect().height`);
    await shot('mobile-sheet-open.jpg');
    await ev(`document.querySelector('#close-library').click()`);
    await sleep(300);
    const closed = await ev(`!document.querySelector('#library').classList.contains('is-open') && document.querySelector('#viewport canvas').getBoundingClientRect().height > 200`);
    await ev(`document.querySelector('[data-view="left"]').click()`);
    await sleep(250);
    await ev(`document.querySelector('#open-material').click()`);
    await sleep(200);
    const material = await ev(`document.body.dataset.sheet === 'material' && document.querySelector('#inspector').classList.contains('is-open')`);
    await ev(`document.querySelector('#close-inspector').click()`);
    report.push({ sheet: true, opened: opened > 200, closed, material });
    console.log('sheet', opened, closed, material);
} catch (error) {
    console.log('FAIL', error);
    report.push({ error: String(error) });
} finally {
    writeFileSync(abs('mobile-report.json'), JSON.stringify(report, null, 2));
    ws.close();
    chrome.kill();
}
