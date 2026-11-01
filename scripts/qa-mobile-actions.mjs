import { spawn } from 'node:child_process';
import { setTimeout as sleep } from 'node:timers/promises';

const password = process.env.QA_PASSWORD || '';
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', [
    '--headless=new', '--remote-debugging-port=9343', '--user-data-dir=/tmp/meemare-mobile2',
    '--disable-gpu-sandbox', 'about:blank',
], { stdio: 'ignore' });
let page;
for (let i = 0; i < 40 && !page; i += 1) {
    try { page = (await (await fetch('http://127.0.0.1:9343/json')).json()).find((item) => item.type === 'page'); } catch { /* */ }
    if (!page) await sleep(250);
}
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve) => ws.addEventListener('open', resolve));
let seq = 0;
const pending = new Map();
ws.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    if (!data.id || !pending.has(data.id)) return;
    pending.get(data.id)(data.result);
    pending.delete(data.id);
});
const send = (method, params = {}) => new Promise((resolve) => {
    const id = ++seq;
    pending.set(id, resolve);
    ws.send(JSON.stringify({ id, method, params }));
});
async function ev(expression) {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    return result.result?.value;
}
await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
await send('Page.navigate', { url: 'http://127.0.0.1:8765/login' });
await sleep(700);
await ev(`document.querySelector('#email').value='erkanulker0@gmail.com';document.querySelector('#password').value=${JSON.stringify(password)};document.querySelector('#login-btn').click()`);
await sleep(800);
await send('Page.navigate', { url: 'http://127.0.0.1:8765/dolap/tasarla?proje=Kw9Yfsvdop0PY4UTvSehlAhK' });
for (let i = 0; i < 40; i += 1) {
    if (await ev(`document.querySelector('[data-picker="carcass"] [data-decor]')`)) break;
    await sleep(250);
}
await sleep(400);
const before = await ev(`document.querySelector('[data-picker="carcass"] label').textContent`);
await ev(`document.querySelector('#open-material').click()`);
await sleep(200);
await ev(`document.querySelector('[data-picker="carcass"] [data-decor]:not(.is-on)').click()`);
await sleep(300);
const after = await ev(`document.querySelector('[data-picker="carcass"] label').textContent`);
await ev(`document.querySelector('#btn-save').click()`);
await sleep(800);
const toast = await ev(`document.querySelector('#toast')?.textContent || ''`);
const zoom = await ev(`(() => { const canvas=document.querySelector('#viewport canvas'); const rect=canvas.getBoundingClientRect(); const beforeZoom=canvas.width; canvas.dispatchEvent(new WheelEvent('wheel',{bubbles:true,deltaY:-120,clientX:rect.left+rect.width/2,clientY:rect.top+rect.height/2})); return beforeZoom; })()`);
console.log(JSON.stringify({ before, after, changed: before !== after, toast, zoom }));
ws.close();
chrome.kill();
