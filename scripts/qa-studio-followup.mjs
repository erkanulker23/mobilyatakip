import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';

const BASE = 'http://127.0.0.1:8765';
const OUT = new URL('../test-results/', import.meta.url);
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const password = process.env.QA_PASSWORD || '';
if (!password) throw new Error('QA_PASSWORD yok');
const abs = (path) => new URL(path, OUT).pathname;
mkdirSync(abs('pages'), { recursive: true });
mkdirSync(abs('renders'), { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new', '--remote-debugging-port=9335',
    `--user-data-dir=${abs('chrome-profile-2')}`,
    '--window-size=1440,900', '--disable-gpu-sandbox',
    'about:blank',
], { stdio: 'ignore' });
const log = [];
const note = (step, ok, detail) => {
    log.push({ step, ok, detail });
    console.log(`${ok ? 'OK' : 'FAIL'} ${step}: ${detail}`);
};
let page;
for (let i = 0; i < 40 && !page; i += 1) {
    try {
        const list = await (await fetch('http://127.0.0.1:9335/json')).json();
        page = list.find((item) => item.type === 'page');
    } catch { /* starting */ }
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
async function drag(id) {
    return ev(`(() => {
        const card = document.querySelector('[data-glb="${id}"]');
        const canvas = document.querySelector('#viewport canvas');
        const from = card.getBoundingClientRect();
        const to = canvas.getBoundingClientRect();
        const x = to.left + to.width * 0.22;
        const y = to.top + to.height * 0.5;
        card.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true, button: 0, buttons: 1, clientX: from.left + 16, clientY: from.top + 16, pointerId: 1, pointerType: 'mouse' }));
        window.dispatchEvent(new PointerEvent('pointermove', { bubbles: true, button: 0, buttons: 1, clientX: x, clientY: y, pointerId: 1, pointerType: 'mouse' }));
        window.dispatchEvent(new PointerEvent('pointerup', { bubbles: true, button: 0, buttons: 0, clientX: x, clientY: y, pointerId: 1, pointerType: 'mouse' }));
        return 'bırakıldı';
    })()`);
}
async function create(name, front) {
    await nav(`${BASE}/dolap`);
    await waitFor(`document.querySelector('#create')`);
    await ev(`(() => {
        const form = document.querySelector('#create');
        form.customerName.value = 'QA Denetim';
        form.projectName.value = ${JSON.stringify(name)};
        form.frontWall.value = '${front}';
        form.leftWall.value = '300';
        form.rightWall.value = '300';
        form.note.value = 'QA test verisi. Müşteri projesi değil.';
        form.requestSubmit();
    })()`);
    await waitFor(`location.pathname.includes('/tasarla')`, 20000);
    await waitFor(`document.querySelector('[data-glb]')`, 60000);
    await sleep(400);
}

try {
    await send('Page.enable');
    await send('Browser.setDownloadBehavior', { behavior: 'allow', downloadPath: abs('renders'), eventsEnabled: true });
    await nav(`${BASE}/login`);
    await ev(`document.querySelector('#email').value = 'erkanulker0@gmail.com'`);
    await ev(`document.querySelector('#password').value = ${JSON.stringify(password)}`);
    await ev(`document.querySelector('#login-btn').click()`);
    await sleep(700);
    await waitFor(`location.pathname !== '/login'`);

    await create('QA Denetim 225b', 225);
    for (const id of ['tek_v1', 'cift_v1', 'cift_v2']) {
        await drag(id);
        await sleep(500);
    }
    const sum225 = await ev(`document.querySelector('#summary')?.innerText || ''`);
    const order225 = await ev(`document.querySelector('#production')?.innerText || ''`);
    note('225 sol tarafa bırakma', sum225.includes('Dolap 225 cm'), sum225);

    await create('QA Denetim 315b', 315);
    for (const id of ['cift_v1', 'tek_v2', 'cift_v4', 'cift_v5']) {
        await drag(id);
        await sleep(500);
    }
    const sum315 = await ev(`document.querySelector('#summary')?.innerText || ''`);
    note('315 sol tarafa bırakma', sum315.includes('Dolap 315 cm'), sum315);
    const before = await ev(`document.querySelector('#production')?.innerText || ''`);
    await ev(`document.querySelector('#viewport canvas').dispatchEvent(new PointerEvent('pointerdown', { bubbles: true, button: 0, buttons: 1, clientX: document.querySelector('#viewport canvas').getBoundingClientRect().left + 280, clientY: document.querySelector('#viewport canvas').getBoundingClientRect().top + 320, pointerId: 7, pointerType: 'mouse' }))`);
    await sleep(200);
    const selected = await ev(`document.querySelector('#selection-name')?.textContent || document.querySelector('#inspector-body h2')?.textContent || ''`);
    note('tek tıkla seçim', Boolean(selected), selected || 'seçilmedi');

    const picked = await ev(`(() => {
        const button = [...document.querySelectorAll('[data-picker="door"] [data-decor]')].find((item) => item.textContent.includes('Antrasit'));
        if (!button) return '';
        button.click();
        return button.textContent.trim();
    })()`);
    await sleep(300);
    await ev(`document.querySelector('#apply-all')?.click()`);
    await sleep(400);
    const after = await ev(`document.querySelector('#production')?.innerText || ''`);
    const hits = (after.match(/Antrasit/g) || []).length;
    note('tümüne uygula', hits >= 4, `${picked} · Antrasit ${hits} kez`);

    await create('QA Denetim füme 2', 120);
    await ev(`document.querySelector('[data-glb="tek_v1"]').click()`);
    await sleep(600);
    await ev(`document.querySelector('[data-door="glass"]').click()`);
    await sleep(250);
    await ev(`document.querySelector('[data-glass="cam-fum"]').click()`);
    await sleep(300);
    await ev(`document.querySelector('[data-view="front"]').click()`);
    await sleep(300);
    const raster = await ev(`document.querySelector('#viewport canvas').toDataURL('image/jpeg', 0.8)`);
    writeFileSync(abs('pages/fume-raster-2.jpg'), Buffer.from(raster.split(',')[1], 'base64'));
    await ev(`document.querySelector('#btn-shot').click()`);
    let label = '';
    for (let i = 0; i < 100; i += 1) {
        await sleep(3000);
        label = await ev(`document.querySelector('#btn-shot')?.textContent || ''`);
        console.log('render', label);
        if (label === 'Render al') break;
    }
    note('füme path trace 2', label === 'Render al', label);
    writeFileSync(abs('followup-225.txt'), order225);
    writeFileSync(abs('followup-315.txt'), before);
} catch (error) {
    note('takip', false, String(error.stack || error));
} finally {
    writeFileSync(abs('followup-log.json'), JSON.stringify(log, null, 2));
    ws.close();
    chrome.kill();
}
