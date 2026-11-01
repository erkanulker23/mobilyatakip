import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/fixes/', import.meta.url);
const abs = (path) => new URL(path, OUT).pathname;
const password = process.env.QA_PASSWORD || '';
mkdirSync(abs('.'), { recursive: true });
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', [
    '--headless=new', '--remote-debugging-port=9340', `--user-data-dir=${abs('chrome')}`,
    '--window-size=1440,900', '--disable-gpu-sandbox', 'about:blank',
], { stdio: 'ignore' });
let page;
for (let i = 0; i < 40 && !page; i += 1) {
    try { page = (await (await fetch('http://127.0.0.1:9340/json')).json()).find((item) => item.type === 'page'); } catch { /* */ }
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
    if (data.error) job.reject(new Error(JSON.stringify(data.error)));
    else job.resolve(data.result);
});
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++seq;
    pending.set(id, { resolve, reject });
    ws.send(JSON.stringify({ id, method, params }));
});
async function ev(expression) {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    return result.result?.value;
}
async function nav(url) {
    await send('Page.navigate', { url });
    await sleep(500);
    for (let i = 0; i < 40; i += 1) {
        if (await ev('document.readyState') === 'complete') return;
        await sleep(200);
    }
}
async function waitFor(expression, ms = 40000) {
    const start = Date.now();
    while (Date.now() - start < ms) {
        if (await ev(`!!(${expression})`)) return;
        await sleep(250);
    }
    throw new Error(expression);
}
async function shot(file) {
    const data = await ev(`document.querySelector('#viewport canvas')?.toDataURL('image/jpeg', 0.72) || ''`);
    writeFileSync(abs(file), Buffer.from(data.split(',')[1], 'base64'));
}
async function create(name, front) {
    await nav(`${BASE}/dolap`);
    await waitFor(`document.querySelector('#create')`);
    await ev(`(() => { const form=document.querySelector('#create'); form.customerName.value='QA Denetim'; form.projectName.value=${JSON.stringify(name)}; form.frontWall.value='${front}'; form.leftWall.value='300'; form.rightWall.value='300'; form.note.value='QA test verisi. Müşteri projesi değil.'; form.requestSubmit(); })()`);
    await waitFor(`location.pathname.includes('/tasarla')`, 20000);
    await waitFor(`document.querySelector('[data-glb]')`, 60000);
    await sleep(400);
}
async function drag(id, xRatio) {
    return ev(`(() => {
        const card=document.querySelector('[data-glb="${id}"]');
        const canvas=document.querySelector('#viewport canvas');
        const from=card.getBoundingClientRect();
        const to=canvas.getBoundingClientRect();
        const x=to.left+to.width*${xRatio};
        const y=to.top+to.height*0.5;
        card.dispatchEvent(new PointerEvent('pointerdown',{bubbles:true,button:0,buttons:1,clientX:from.left+20,clientY:from.top+30,pointerId:1,pointerType:'mouse'}));
        window.dispatchEvent(new PointerEvent('pointermove',{bubbles:true,button:0,buttons:1,clientX:x,clientY:y,pointerId:1,pointerType:'mouse'}));
        window.dispatchEvent(new PointerEvent('pointerup',{bubbles:true,button:0,buttons:0,clientX:x,clientY:y,pointerId:1,pointerType:'mouse'}));
        return 'bırakıldı';
    })()`);
}
const report = [];
try {
    await nav(`${BASE}/login`);
    await ev(`document.querySelector('#email').value='erkanulker0@gmail.com';document.querySelector('#password').value=${JSON.stringify(password)};document.querySelector('#login-btn').click()`);
    await sleep(800);
    await create('QA yerleşim 315', 315);
    let placed315 = 0;
    for (const id of ['cift_v1', 'tek_v2', 'cift_v4', 'cift_v5']) {
        await drag(id, 0.22);
        placed315 += 1;
        await waitFor(`(window.__wardrobeDropLog || []).filter((row) => row.result || row.phase === 'error').length >= ${placed315}`, 8000).catch(() => {});
    }
    const sum = await ev(`document.querySelector('#summary')?.innerText || ''`);
    const names = await ev(`document.querySelector('#production')?.innerText || ''`);
    const log = await ev(`window.__wardrobeDropLog || []`);
    const ok315 = sum.includes('Dolap 315 cm') && (names.match(/cm/g) || []).length >= 4;
    report.push({ test: '315 sürükle', ok: ok315, sum });
    console.log(ok315 ? 'OK' : 'FAIL', '315', sum);
    console.log(JSON.stringify(log));
    await ev(`document.querySelector('[data-view="front"]').click()`);
    await sleep(400);
    await shot('315-front.jpg');
    await ev(`document.querySelector('#btn-save').click()`);
    await sleep(1200);
    const token = await ev(`new URL(location.href).searchParams.get('proje')`);
    await nav(`${BASE}/dolap/tasarla?proje=${token}`);
    await waitFor(`document.querySelector('#summary')`, 20000);
    await sleep(900);
    const reloaded = await ev(`document.querySelector('#production')?.innerText || ''`);
    const same = reloaded === names;
    report.push({ test: '315 kayıt', ok: same && reloaded.includes('Çift kapılı V5'), token });
    console.log(same ? 'OK' : 'FAIL', 'kayıt', token);
    writeFileSync(abs('315-before.txt'), names);
    writeFileSync(abs('315-after.txt'), reloaded);
    writeFileSync(abs('drop-log.json'), JSON.stringify(log, null, 2));

    await create('QA yerleşim 225', 225);
    let placed225 = 0;
    for (const id of ['tek_v1', 'cift_v1', 'cift_v2']) {
        await drag(id, 0.22);
        placed225 += 1;
        await waitFor(`(window.__wardrobeDropLog || []).filter((row) => row.result || row.phase === 'error').length >= ${placed225}`, 8000).catch(() => {});
    }
    const sum225 = await ev(`document.querySelector('#summary')?.innerText || ''`);
    report.push({ test: '225 sürükle', ok: sum225.includes('Dolap 225 cm'), sum: sum225 });
    console.log(sum225.includes('Dolap 225 cm') ? 'OK' : 'FAIL', '225', sum225);

    await create('QA yerleşim 270', 270);
    const spots270 = [0.3, 0.5, 0.72];
    let placed270 = 0;
    for (const id of ['cift_v1', 'cift_v2', 'cift_v3']) {
        await drag(id, spots270[placed270]);
        placed270 += 1;
        await waitFor(`(window.__wardrobeDropLog || []).filter((row) => row.result || row.phase === 'error').length >= ${placed270}`, 8000).catch(() => {});
    }
    const sum270 = await ev(`document.querySelector('#summary')?.innerText || ''`);
    report.push({ test: '270 sürükle', ok: sum270.includes('Dolap 270 cm'), sum: sum270 });
    console.log(sum270.includes('Dolap 270 cm') ? 'OK' : 'FAIL', '270', sum270);
    console.log(JSON.stringify(await ev(`window.__wardrobeDropLog || []`)));
    await ev(`document.querySelector('[data-glb="cift_v4"] .card-add').click()`);
    await sleep(700);
    const afterAdd = await ev(`document.querySelector('#toast')?.textContent || ''`);
    const sumAdd = await ev(`document.querySelector('#summary')?.innerText || ''`);
    report.push({ test: '270 Ekle düğmesi sığmama', ok: sumAdd.includes('Dolap 270 cm'), sum: sumAdd, toast: afterAdd });
    console.log('ekle', sumAdd, afterAdd);
} catch (error) {
    report.push({ test: 'tur', ok: false, error: String(error.stack || error) });
    console.log('FAIL', error);
} finally {
    writeFileSync(abs('placement-report.json'), JSON.stringify(report, null, 2));
    ws.close();
    chrome.kill();
}
