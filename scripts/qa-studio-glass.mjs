import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/', import.meta.url);
const abs = (path) => new URL(path, OUT).pathname;
const password = process.env.QA_PASSWORD || '';
mkdirSync(abs('renders'), { recursive: true });
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', [
    '--headless=new', '--remote-debugging-port=9337', `--user-data-dir=${abs('chrome-profile-4')}`,
    '--window-size=1440,900', '--disable-gpu-sandbox', 'about:blank',
], { stdio: 'ignore' });
let page;
for (let i = 0; i < 40 && !page; i += 1) {
    try { page = (await (await fetch('http://127.0.0.1:9337/json')).json()).find((item) => item.type === 'page'); } catch { /* */ }
    if (!page) await sleep(250);
}
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve) => ws.addEventListener('open', resolve));
let seq = 0; const pending = new Map();
ws.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    if (!data.id || !pending.has(data.id)) return;
    const job = pending.get(data.id); pending.delete(data.id);
    if (data.error) job.reject(new Error(JSON.stringify(data.error))); else job.resolve(data.result);
});
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++seq; pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params }));
});
async function ev(expression) {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    return result.result?.value;
}
async function nav(url) {
    await send('Page.navigate', { url });
    await sleep(500);
    for (let i = 0; i < 40; i += 1) { if (await ev('document.readyState') === 'complete') return; await sleep(200); }
}
async function waitFor(expression, ms = 40000) {
    const start = Date.now();
    while (Date.now() - start < ms) { if (await ev(`!!(${expression})`)) return; await sleep(250); }
    throw new Error(expression);
}
try {
    await nav(`${BASE}/login`);
    await ev(`document.querySelector('#email').value='erkanulker0@gmail.com';document.querySelector('#password').value=${JSON.stringify(password)};document.querySelector('#login-btn').click()`);
    await sleep(800);
    await nav(`${BASE}/dolap`);
    await waitFor(`document.querySelector('#create')`);
    await ev(`(() => { const form=document.querySelector('#create'); form.customerName.value='QA Denetim'; form.projectName.value='QA Denetim 315c'; form.frontWall.value='315'; form.note.value='QA test verisi. Müşteri projesi değil.'; form.requestSubmit(); })()`);
    await waitFor(`document.querySelector('[data-glb]')`, 60000);
    for (const id of ['cift_v1', 'tek_v2', 'cift_v4', 'cift_v5']) {
        await ev(`(() => { const card=document.querySelector('[data-glb="${id}"]'); const canvas=document.querySelector('#viewport canvas'); const from=card.getBoundingClientRect(); const to=canvas.getBoundingClientRect(); const x=to.left+to.width*0.22; const y=to.top+to.height*0.5; card.dispatchEvent(new PointerEvent('pointerdown',{bubbles:true,button:0,buttons:1,clientX:from.left+16,clientY:from.top+16,pointerId:1,pointerType:'mouse'})); window.dispatchEvent(new PointerEvent('pointermove',{bubbles:true,button:0,buttons:1,clientX:x,clientY:y,pointerId:1,pointerType:'mouse'})); window.dispatchEvent(new PointerEvent('pointerup',{bubbles:true,button:0,buttons:0,clientX:x,clientY:y,pointerId:1,pointerType:'mouse'})); })()`);
        await sleep(700);
    }
    const sum = await ev(`document.querySelector('#summary')?.innerText||''`);
    const names = await ev(`document.querySelector('#production')?.innerText||''`);
    console.log('315', sum.includes('Dolap 315 cm') ? 'OK' : 'FAIL', sum);
    writeFileSync(abs('followup-315c.txt'), `${sum}\n${names}`);
    await nav(`${BASE}/dolap`);
    await waitFor(`document.querySelector('#create')`);
    await ev(`(() => { const form=document.querySelector('#create'); form.customerName.value='QA Denetim'; form.projectName.value='QA Denetim füme 3'; form.frontWall.value='120'; form.note.value='QA test verisi. Müşteri projesi değil.'; form.requestSubmit(); })()`);
    await waitFor(`document.querySelector('[data-glb]')`, 60000);
    await ev(`document.querySelector('[data-glb="tek_v1"]').click()`);
    await sleep(800);
    await ev(`document.querySelector('[data-door="glass"]').click()`);
    await sleep(300);
    await ev(`document.querySelector('[data-glass="cam-fum"]').click()`);
    await sleep(300);
    await ev(`(() => { const orig=HTMLCanvasElement.prototype.toDataURL; HTMLCanvasElement.prototype.toDataURL=function(...args){ const url=orig.apply(this,args); if(String(args[0]).includes('png')&&url.length>50000) window.__shot=url; return url; }; })()`);
    await ev(`document.querySelector('#btn-shot').click()`);
    let label = '';
    for (let i = 0; i < 100; i += 1) {
        await sleep(3000);
        label = await ev(`document.querySelector('#btn-shot')?.textContent||''`);
        console.log('render', label);
        if (label === 'Render al') break;
    }
    const shot = await ev(`window.__shot || ''`);
    if (shot.startsWith('data:image')) {
        writeFileSync(abs('renders/fume-path-3.png'), Buffer.from(shot.split(',')[1], 'base64'));
        console.log('OK path kaydı', shot.length);
    } else console.log('FAIL path kaydı yok');
} catch (error) {
    console.log('FAIL', error.stack || error);
} finally {
    ws.close();
    chrome.kill();
}
