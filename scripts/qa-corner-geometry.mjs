import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/fixes/', import.meta.url);
const abs = (path) => new URL(path, OUT).pathname;
const password = process.env.QA_PASSWORD || '';
mkdirSync(abs('.'), { recursive: true });
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', [
    '--headless=new', '--remote-debugging-port=9344', '--user-data-dir=/tmp/meemare-corner',
    '--window-size=1440,900', '--disable-gpu-sandbox', 'about:blank',
], { stdio: 'ignore' });
let page;
for (let i = 0; i < 40 && !page; i += 1) {
    try { page = (await (await fetch('http://127.0.0.1:9344/json')).json()).find((item) => item.type === 'page'); } catch { /* */ }
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
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    return result.result?.value;
}
async function shot(file) {
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.7) || ''`);
    if (!data) throw new Error('canvas yok');
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
}
async function nav(url) {
    await send('Page.navigate', { url });
    await sleep(400);
}
async function waitFor(expression, ms = 30000) {
    const start = Date.now();
    while (Date.now() - start < ms) {
        if (await ev(`!!(${expression})`)) return;
        await sleep(200);
    }
    throw new Error(expression);
}
async function create(name) {
    await nav(`${BASE}/dolap`);
    await waitFor(`document.querySelector('#create')`);
    await ev(`(() => { const form=document.querySelector('#create'); form.customerName.value='QA Denetim'; form.projectName.value=${JSON.stringify(name)}; form.frontWall.value='360'; form.leftWall.value='240'; form.rightWall.value='240'; form.roomHeight.value='270'; form.note.value='QA köşe. Müşteri projesi değil.'; form.requestSubmit(); })()`);
    await waitFor(`location.pathname.includes('/tasarla')`);
    await waitFor(`document.querySelector('[data-glb="kose_1"] .card-add')`, 60000);
    await sleep(300);
}
async function view(name) {
    await ev(`document.querySelector('[data-view="${name}"]').click()`);
    await sleep(350);
}
const report = [];
try {
    await nav(`${BASE}/login`);
    await ev(`document.querySelector('#email').value='erkanulker0@gmail.com';document.querySelector('#password').value=${JSON.stringify(password)};document.querySelector('#login-btn').click()`);
    await sleep(700);
    for (const id of ['kose_1', 'kose_2', 'kose_3', 'kose_4', 'kose_5']) {
        await create(`QA köşe ${id}`);
        await ev(`document.querySelector('[data-glb="${id}"] .card-add').click()`);
        await waitFor(`(document.querySelector('#summary')?.innerText || '').includes('Dolap 90 cm')`);
        await sleep(400);
        for (const mode of ['top', 'front', 'perspective']) {
            await view(mode);
            await shot(`${id}-${mode}.jpg`);
        }
        if (id === 'kose_1') {
            await ev(`document.querySelector('#mod-open')?.click()`);
            await sleep(600);
            await view('perspective');
            await shot('kose_1-doors.jpg');
        }
        const doorButton = await ev(`!!document.querySelector('[data-door="solid"]')`);
        report.push({ id, doors: doorButton, sum: await ev(`document.querySelector('#summary')?.innerText || ''`) });
        console.log(id, doorButton);
    }
    await create('QA köşe bağ');
    await ev(`document.querySelector('[data-glb="kose_1"] .card-add').click()`);
    await waitFor(`(document.querySelector('#summary')?.innerText || '').includes('Dolap 90 cm')`);
    await view('front');
    await ev(`(() => {
        const card=document.querySelector('[data-glb="cift_v1"]');
        const canvas=document.querySelector('#viewport canvas');
        const from=card.getBoundingClientRect();
        const to=canvas.getBoundingClientRect();
        const x=to.left+to.width*0.42;
        const y=to.top+to.height*0.55;
        card.dispatchEvent(new PointerEvent('pointerdown',{bubbles:true,button:0,buttons:1,clientX:from.left+16,clientY:from.top+16,pointerId:1,pointerType:'mouse'}));
        window.dispatchEvent(new PointerEvent('pointermove',{bubbles:true,buttons:1,clientX:x,clientY:y,pointerId:1,pointerType:'mouse'}));
        window.dispatchEvent(new PointerEvent('pointerup',{bubbles:true,clientX:x,clientY:y,pointerId:1,pointerType:'mouse'}));
    })()`);
    await sleep(500);
    await view('left');
    await ev(`(() => {
        const card=document.querySelector('[data-glb="tek_v1"]');
        const canvas=document.querySelector('#viewport canvas');
        const from=card.getBoundingClientRect();
        const to=canvas.getBoundingClientRect();
        const x=to.left+to.width*0.5;
        const y=to.top+to.height*0.5;
        card.dispatchEvent(new PointerEvent('pointerdown',{bubbles:true,button:0,buttons:1,clientX:from.left+16,clientY:from.top+16,pointerId:1,pointerType:'mouse'}));
        window.dispatchEvent(new PointerEvent('pointermove',{bubbles:true,buttons:1,clientX:x,clientY:y,pointerId:1,pointerType:'mouse'}));
        window.dispatchEvent(new PointerEvent('pointerup',{bubbles:true,clientX:x,clientY:y,pointerId:1,pointerType:'mouse'}));
    })()`);
    await sleep(500);
    const joined = await ev(`document.querySelector('#summary')?.innerText || ''`);
    const log = await ev(`(window.__wardrobeDropLog || []).filter((row) => row.result || row.wall)`);
    await view('top');
    await shot('kose-joined-top.jpg');
    await view('perspective');
    await shot('kose-joined-perspective.jpg');
    await ev(`document.querySelector('#mod-open')?.click()`);
    await sleep(700);
    await shot('kose-1-open.jpg');
    report.push({ joined, log });
    console.log(joined);
    console.log(JSON.stringify(log));
} catch (error) {
    console.log('FAIL', error);
    report.push({ error: String(error.stack || error) });
} finally {
    writeFileSync(abs('corner-report.json'), JSON.stringify(report, null, 2));
    ws.close();
    chrome.kill();
}
