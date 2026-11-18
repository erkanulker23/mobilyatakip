/**
 * MEEMARE-E2E-MASTER-QA-001 — yerel tarayıcı otomasyonu (Chrome CDP).
 * Canlıya push yok. Gerçek müşteri verisi kullanmaz.
 */
import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync, copyFileSync, existsSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const BASE = process.env.QA_BASE || 'http://127.0.0.1:8765';
const EMAIL = process.env.QA_EMAIL || 'e2e-qa@meemare.test';
const PASSWORD = process.env.QA_PASSWORD || 'E2eQa!2026';
const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT = path.join(ROOT, 'test-results', 'master-e2e');
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = Number(process.env.QA_CDP_PORT || 9355);

const rows = [];
function note(area, id, status, detail) {
    const row = { area, id, status, detail, at: new Date().toISOString() };
    rows.push(row);
    console.log(`${status.padEnd(12)} ${area} ${id}: ${detail}`);
}

for (const dir of ['screenshots', 'wall-drawing', 'module-placement', 'save-load', 'corner', 'project-creation']) {
    mkdirSync(path.join(OUT, dir), { recursive: true });
}

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${path.join(OUT, 'chrome-profile-e2e')}`,
    '--window-size=1440,900',
    '--disable-gpu-sandbox',
    'about:blank',
], { stdio: 'ignore' });

async function target() {
    for (let i = 0; i < 50; i += 1) {
        try {
            const list = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
            const page = list.find((item) => item.type === 'page' && item.webSocketDebuggerUrl);
            if (page) return page;
        } catch { /* starting */ }
        await sleep(200);
    }
    throw new Error('Chrome CDP hedefi yok');
}

const page = await target();
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((resolve, reject) => {
    ws.addEventListener('open', resolve);
    ws.addEventListener('error', reject);
});
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
    return new Promise((resolve, reject) => {
        pending.set(id, { resolve, reject });
        ws.send(JSON.stringify({ id, method, params }));
    });
}
async function ev(expression) {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) {
        throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
    }
    return result.result?.value;
}
async function nav(url) {
    await send('Page.enable');
    await send('Page.navigate', { url });
    await sleep(400);
    for (let i = 0; i < 60; i += 1) {
        const ready = await ev('document.readyState');
        if (ready === 'complete') return;
        await sleep(150);
    }
}
async function shot(rel) {
    const result = await send('Page.captureScreenshot', { format: 'png', fromSurface: true });
    const dest = path.join(OUT, rel);
    writeFileSync(dest, Buffer.from(result.data, 'base64'));
    return dest;
}
async function waitFor(expr, ms = 15000) {
    const end = Date.now() + ms;
    while (Date.now() < end) {
        try {
            if (await ev(expr)) return true;
        } catch { /* retry */ }
        await sleep(250);
    }
    return false;
}

try {
    await send('Page.enable');
    await send('Runtime.enable');

    // --- Login (reuse session if chrome profile already authenticated) ---
    await nav(`${BASE}/login`);
    await sleep(500);
    let pathname = await ev('location.pathname');
    if (pathname === '/login') {
        const loginOk = await ev(`(() => {
            const email = document.querySelector('input[type="email"], input[name="email"]');
            const pass = document.querySelector('input[type="password"], input[name="password"]');
            const btn = document.querySelector('button[type="submit"]');
            if (!email || !pass || !btn) return false;
            email.value = ${JSON.stringify(EMAIL)};
            pass.value = ${JSON.stringify(PASSWORD)};
            email.dispatchEvent(new Event('input', { bubbles: true }));
            pass.dispatchEvent(new Event('input', { bubbles: true }));
            btn.click();
            return true;
        })()`);
        if (!loginOk) throw new Error('Login formu yok');
        await waitFor(`location.pathname !== '/login'`, 12000);
        pathname = await ev('location.pathname');
    }
    note('auth', 'login', locationCheck(pathname), pathname);

    // --- Create project ---
    await nav(`${BASE}/dolap`);
    await waitFor(`!!document.getElementById('create')`, 8000);
    const created = await ev(`(async () => {
        const form = document.getElementById('create');
        if (!form) return { ok: false, reason: 'no-form' };
        form.customerName.value = 'E2E Matrix Müşteri';
        form.projectName.value = 'E2E-MATRIX-' + Date.now().toString(36);
        form.note.value = 'master-e2e wall/module matrix';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const body = {
            customerName: form.customerName.value,
            projectName: form.projectName.value,
            note: form.note.value,
            phone: '',
            design: {
                name: form.projectName.value,
                customerName: form.customerName.value,
                wallWidth: 300, leftWall: 300, rightWall: 300, roomHeight: 320,
                modules: [],
            },
        };
        const res = await fetch(${JSON.stringify(BASE)} + '/dolap/proje', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        return { ok: res.ok && data.ok, status: res.status, token: data.token, id: data.id, name: form.projectName.value };
    })()`);
    note('project', 'create', created.ok ? 'PASS' : 'FAIL', JSON.stringify(created));
    if (!created.ok) throw new Error('Proje oluşmadı');

    // Open design skipping plan-first for placement matrix
    await nav(`${BASE}/dolap/tasarla?proje=${created.token}`);
    await waitFor(`typeof window.__setCameraView === 'function' || !!document.getElementById('viewport')`, 20000);
    await sleep(1500);
    // Close plan-first if open
    await ev(`(() => {
        if (document.body.classList.contains('studio-plan-first')) {
            document.getElementById('btn-plan-skip')?.click();
        }
        return document.body.classList.contains('studio-plan-first');
    })()`);
    await sleep(800);
    await shot('project-creation/S1b-design-opened.png');

    // --- Wall width matrix + 45cm modules ---
    const widths = [180, 225, 270, 315, 413];
    for (const width of widths) {
        const result = await ev(`(async () => {
            const W = ${width};
            window.__studio?.clear?.();
            window.MeemareStudioApplyWalls?.({ frontCm: W, leftCm: 300, rightCm: 300, heightCm: 280 });
            await new Promise((r) => setTimeout(r, 350));
            // Prefer first 45cm Y26 single if catalog exposed via data attributes
            const card = [...document.querySelectorAll('#library-body [data-glb], #library-body .card, #library-body article, #library-body > *')]
                .find((node) => /45\\s*[x×]/i.test(node.innerText || '') && /tek|y26/i.test(node.innerText || ''));
            const addButtons = card
                ? [card.querySelector('button')]
                : [...document.querySelectorAll('#library-body button')].filter((b) => b.textContent.trim() === 'Ekle');
            const addBtn = addButtons.find(Boolean);
            if (!addBtn) return { ok: false, reason: 'no-add-buttons', width: W };
            const maxN = Math.ceil(W / 45) + 1;
            for (let i = 0; i < maxN; i += 1) {
                const before = window.__moduleCount?.() ?? 0;
                const beforeRun = window.__wallRun?.('front') ?? 0;
                addBtn.click();
                await new Promise((r) => setTimeout(r, 650));
                const after = window.__moduleCount?.() ?? before;
                const run = window.__wallRun?.('front') ?? beforeRun;
                if (after === before || run >= W - 0.5) break;
            }
            await new Promise((r) => setTimeout(r, 300));
            const state = window.__exportState?.() || null;
            const mods = state?.modules || [];
            const frontMods = mods.filter((m) => m.kind !== 'corner' && (m.wall || 'front') === 'front');
            const run = frontMods.reduce((s, m) => s + Math.max(30, Number(m.width) || 45), 0);
            const wall = Number(state?.wallWidth) || W;
            const expected = Math.floor(W / 45);
            return {
                ok: wall === W && frontMods.length === expected && Math.abs(run - expected * 45) < 1,
                width: W,
                wall,
                expected,
                moduleCount: mods.length,
                frontCount: frontMods.length,
                run,
                leftover: Math.max(0, wall - run),
                ids: mods.map((m) => m.glb || m.product || m.name),
            };
        })()`);
        const pass = result.ok && result.run <= result.wall + 0.5 && result.frontCount > 0;
        note('placement', `front-${width}`, pass ? 'PASS' : 'FAIL', JSON.stringify(result));
        await shot(`module-placement/front-${width}.png`);
    }

    // --- Camera focus check (QA-UX-002) ---
    {
        await ev(`(async () => {
            window.__studio?.clear?.();
            window.MeemareStudioApplyWalls?.({ frontCm: 300, leftCm: 300, rightCm: 300, heightCm: 280 });
            await new Promise((r) => setTimeout(r, 300));
            const card = [...document.querySelectorAll('#library-body [data-glb]')]
                .find((node) => /45\\s*[x×]/i.test(node.innerText || '') && /tek|y26/i.test(node.innerText || ''))
                || document.querySelector('#library-body [data-glb]');
            card?.querySelector('button')?.click();
            await new Promise((r) => setTimeout(r, 900));
            window.__studio?.focusSelected?.();
            await new Promise((r) => setTimeout(r, 400));
        })()`);
        const framing = await ev(`(() => {
            const summary = document.getElementById('summary')?.innerText || '';
            const sel = document.getElementById('selection-bar-name')?.textContent
                || document.getElementById('selection-name')?.textContent || '';
            const modules = window.__moduleCount?.() ?? 0;
            const run = window.__wallRun?.('front') ?? 0;
            return {
                hasCanvas: !!document.querySelector('#viewport canvas'),
                summary,
                selected: sel,
                modules,
                run,
                camDebug: window.__camDebug || null,
            };
        })()`);
        const distOk = Number(framing.camDebug?.dist) >= 260;
        const camOk = framing.hasCanvas && framing.modules === 1 && framing.run === 45 && !!framing.selected && distOk;
        note('camera', 'focus-after-add', camOk ? 'PASS' : 'FAIL', JSON.stringify(framing));
        await shot('module-placement/ux002-focus-after-add.png');
        // Room-context frame for visual audit (module still selected)
        await ev(`window.__setCameraView?.('perspective')`);
        await sleep(400);
        await shot('module-placement/ux002-room-context.png');
        await ev(`window.__studio?.focusSelected?.()`);
        await sleep(400);
        await shot('module-placement/ux002-focus-after-add-b.png');
    }

    // --- Save / reload ---
    const saveLoad = await ev(`(async () => {
        const before = window.__exportState?.();
        if (!before) {
            // try reading from studio global if exposed later
            document.getElementById('btn-save')?.click();
            await new Promise((r) => setTimeout(r, 800));
            return { ok: false, reason: 'no-export-hook' };
        }
        document.getElementById('btn-save')?.click();
        await new Promise((r) => setTimeout(r, 1000));
        const token = new URLSearchParams(location.search).get('proje');
        return {
            ok: true,
            token,
            moduleCount: before.modules?.length || 0,
            wallWidth: before.wallWidth,
            carcassId: before.modules?.[0]?.carcassId || before.carcassId,
            snapshot: {
                modules: (before.modules || []).map((m) => ({
                    id: m.id, glb: m.glb, width: m.width, wall: m.wall, offset: m.offset,
                    door: m.door, carcassId: m.carcassId, doorColorId: m.doorColorId,
                })),
                wallWidth: before.wallWidth,
                leftWall: before.leftWall,
                rightWall: before.rightWall,
            },
        };
    })()`);
    note('save', 'snapshot', saveLoad.ok ? 'PASS' : 'FAIL', JSON.stringify({ ...saveLoad, snapshot: undefined }));
    if (saveLoad.ok && saveLoad.token) {
        writeFileSync(path.join(OUT, 'save-load', 'before.json'), JSON.stringify(saveLoad.snapshot, null, 2));
        await nav(`${BASE}/dolap/tasarla?proje=${saveLoad.token}`);
        await waitFor(`!!document.getElementById('viewport')`, 20000);
        await sleep(2000);
        await ev(`document.body.classList.contains('studio-plan-first') && document.getElementById('btn-plan-skip')?.click()`);
        await sleep(1000);
        const after = await ev(`(() => {
            const s = window.__exportState?.();
            if (!s) return { ok: false, reason: 'no-state' };
            return {
                ok: true,
                moduleCount: s.modules?.length || 0,
                wallWidth: s.wallWidth,
                modules: (s.modules || []).map((m) => ({
                    id: m.id, glb: m.glb, width: m.width, wall: m.wall, offset: m.offset,
                    door: m.door, carcassId: m.carcassId, doorColorId: m.doorColorId,
                })),
            };
        })()`);
        writeFileSync(path.join(OUT, 'save-load', 'after.json'), JSON.stringify(after, null, 2));
        const match = after.ok
            && after.moduleCount === saveLoad.moduleCount
            && Number(after.wallWidth) === Number(saveLoad.wallWidth);
        note('save', 'reload-match', match ? 'PASS' : 'FAIL', JSON.stringify({ before: saveLoad.moduleCount, after: after.moduleCount, wall: after.wallWidth }));
        await shot('save-load/after-reload.png');
    }

    // --- Corner: apply L walls + add corner if available ---
    const corner = await ev(`(async () => {
        window.__studio?.clear?.();
        window.MeemareStudioApplyWalls?.({ frontCm: 320, leftCm: 280, rightCm: 280, heightCm: 280 });
        await new Promise((r) => setTimeout(r, 300));
        const cornerBtn = [...document.querySelectorAll('#library-body button')].find((b) => {
            const card = b.closest('article, .card, div');
            const text = (card?.innerText || b.innerText || '').toLowerCase();
            return text.includes('köşe') || text.includes('kose');
        });
        if (!cornerBtn) return { ok: false, reason: 'no-corner-button' };
        cornerBtn.click();
        await new Promise((r) => setTimeout(r, 1000));
        const s = window.__exportState?.();
        const cornerMods = (s?.modules || []).filter((m) => m.kind === 'corner' || /kose/i.test(m.glb || ''));
        return {
            ok: cornerMods.length > 0,
            count: s?.modules?.length || 0,
            corner: cornerMods.map((m) => ({ kind: m.kind, glb: m.glb, name: m.name })),
        };
    })()`);
    note('corner', 'add-corner', corner.ok ? 'PASS' : 'FAIL', JSON.stringify(corner));
    await shot('corner/l-corner-attempt.png');

} catch (error) {
    note('runner', 'fatal', 'FAIL', String(error?.stack || error));
} finally {
    writeFileSync(path.join(OUT, 'functional-log.json'), JSON.stringify(rows, null, 2));
    const summary = rows.reduce((acc, row) => {
        acc[row.status] = (acc[row.status] || 0) + 1;
        return acc;
    }, {});
    writeFileSync(path.join(OUT, 'summary.json'), JSON.stringify({ summary, rows: rows.length, at: new Date().toISOString() }, null, 2));
    console.log('SUMMARY', summary);
    try { chrome.kill('SIGKILL'); } catch { /* */ }
    process.exit(rows.some((r) => r.status === 'FAIL') ? 1 : 0);
}

function locationCheck(pathname) {
    return pathname && pathname !== '/login' ? 'PASS' : 'FAIL';
}
