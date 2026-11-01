<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Dolap projeleri',
        'metaDescription' => 'Kayıtlı dolap projeleri ve yeni oda.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #1c1c1c; --muted: #6d6a64; --line: #e6e2da; --bg: #f4f3f0; --panel: #fffcf8; --accent: #1f6b4a; --danger: #9f2d2d; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Montserrat, system-ui, sans-serif; color: var(--ink); background: var(--bg); }
        button, input { font-family: inherit; }
        button { cursor: pointer; }
        header { display: flex; align-items: center; gap: 12px; height: 56px; padding: 0 16px; background: var(--panel); border-bottom: 1px solid var(--line); }
        header a { color: inherit; text-decoration: none; }
        header strong { letter-spacing: .08em; }
        header span { color: var(--muted); font-size: 13px; margin-left: 8px; }
        main { display: grid; grid-template-columns: 1fr; gap: 16px; max-width: 1180px; margin: 0 auto; padding: 20px 16px 40px; }
        section.form { max-width: 440px; }
        section { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; padding: 16px; }
        h1 { margin: 0 0 6px; font-size: 20px; }
        h2 { margin: 0 0 12px; font-size: 16px; }
        p.hint { color: var(--muted); font-size: 13px; margin: 0 0 12px; }
        .row { display: flex; gap: 8px; margin-bottom: 12px; }
        input { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; background: #fff; }
        .btn { border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 8px 12px; }
        .btn.primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn.danger { color: var(--danger); }
        #list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .project { display: flex; flex-direction: column; gap: 12px; min-height: 156px; padding: 14px; border: 1px solid var(--line); border-radius: 14px; background: #fff; }
        .project button.open { flex: 1; text-align: left; border: 0; background: transparent; padding: 0; }
        .project strong { display: block; font-size: 15px; margin-bottom: 6px; }
        .project span { display: block; color: var(--muted); font-size: 12px; line-height: 1.45; }
        .project .btn.danger { align-self: flex-start; }
        @media (min-width: 900px) { #list { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 4px; }
        .field { margin-bottom: 10px; }
        canvas { width: 100%; height: 240px; background: #f7f6f4; border: 1px solid var(--line); border-radius: 10px; touch-action: none; }
        .msg { min-height: 18px; color: var(--danger); font-size: 13px; }
        @media (max-width: 800px) { section.form { max-width: none; } }
    </style>
</head>
<body>
<header>
    <a href="{{ route('wardrobe.index') }}"><strong>MEEMARE</strong><span>Dolap projeleri</span></a>
</header>
<main>
    <section>
        <h1>Kayıtlı projeler</h1>
        <p class="hint">Bir karta basınca proje açılır. Yeni oda alttaki formdan başlar.</p>
        <div class="row">
            <input id="phone" inputmode="tel" placeholder="Telefonla ara" aria-label="Telefon">
            <button type="button" class="btn" id="search">Ara</button>
        </div>
        <div id="list"></div>
    </section>
    <section class="form">
        <h2>Yeni proje</h2>
        <form id="create">
            <div class="field"><label>Ad</label><input name="customerName" required maxlength="120"></div>
            <div class="field"><label>Proje adı</label><input name="projectName" required maxlength="80" value="Dolabım"></div>
            <div class="grid">
                <div class="field"><label>Sol duvar (cm)</label><input name="leftWall" type="number" min="80" max="800" value="300" required></div>
                <div class="field"><label>Ön duvar (cm)</label><input name="frontWall" type="number" min="80" max="800" value="300" required></div>
                <div class="field"><label>Sağ duvar (cm)</label><input name="rightWall" type="number" min="80" max="800" value="300" required></div>
                <div class="field"><label>Yükseklik (cm)</label><input name="roomHeight" type="number" min="160" max="400" value="270" required></div>
            </div>
            <p class="hint">Kapı veya pencereyi duvarın üzerine bırak. Bir daha basınca kalkar.</p>
            <div class="row">
                <button type="button" class="btn primary" id="tool-door">Kapı</button>
                <button type="button" class="btn" id="tool-window">Pencere</button>
            </div>
            <canvas id="sketch" width="680" height="440"></canvas>
            <div class="field" style="margin-top:10px"><label>Not</label><input name="note" maxlength="2000"></div>
            <p class="msg" id="msg"></p>
            <button class="btn primary" type="submit">Dolabı tasarla</button>
        </form>
    </section>
</main>
<script>
const storeUrl = @json($projectStoreUrl);
const indexUrl = @json($projectIndexUrl);
const designUrl = @json($designUrl);
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const openings = [];
let tool = 'door';
const canvas = document.getElementById('sketch');
const ctx = canvas.getContext('2d');

function walls() {
    const form = document.getElementById('create');
    const left = Number(form.leftWall.value) || 300;
    const front = Number(form.frontWall.value) || 300;
    const right = Number(form.rightWall.value) || 300;
    const pad = 46;
    const span = Math.max(left, right, 80);
    const scale = Math.min((canvas.width - pad * 2) / front, (canvas.height - pad * 2) / span);
    const x0 = (canvas.width - front * scale) / 2;
    const z0 = pad;
    return {
        scale,
        left: { id: 'left', x1: x0, z1: z0, x2: x0, z2: z0 + left * scale, length: left },
        front: { id: 'front', x1: x0, z1: z0, x2: x0 + front * scale, z2: z0, length: front },
        right: { id: 'right', x1: x0 + front * scale, z1: z0, x2: x0 + front * scale, z2: z0 + right * scale, length: right },
    };
}

function draw() {
    const room = walls();
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.lineWidth = 8;
    ctx.lineCap = 'butt';
    ctx.strokeStyle = '#1c1c1c';
    Object.values(room).filter((wall) => wall.id).forEach((wall) => {
        ctx.beginPath();
        ctx.moveTo(wall.x1, wall.z1);
        ctx.lineTo(wall.x2, wall.z2);
        ctx.stroke();
    });
    openings.forEach((item) => {
        const wall = room[item.wallId];
        if (!wall) return;
        const t = item.offset / wall.length;
        const w = item.width / wall.length;
        const horizontal = wall.z1 === wall.z2;
        const x = wall.x1 + (wall.x2 - wall.x1) * t;
        const z = wall.z1 + (wall.z2 - wall.z1) * t;
        const len = (horizontal ? wall.x2 - wall.x1 : wall.z2 - wall.z1) * w;
        ctx.strokeStyle = item.kind === 'door' ? '#1f6b4a' : '#3d6f9f';
        ctx.lineWidth = 10;
        ctx.beginPath();
        if (horizontal) { ctx.moveTo(x, z); ctx.lineTo(x + len, z); }
        else { ctx.moveTo(x, z); ctx.lineTo(x, z + len); }
        ctx.stroke();
    });
}

function nearest(event) {
    const rect = canvas.getBoundingClientRect();
    const x = (event.clientX - rect.left) * (canvas.width / rect.width);
    const z = (event.clientY - rect.top) * (canvas.height / rect.height);
    const room = walls();
    let best = null;
    Object.values(room).filter((wall) => wall.id).forEach((wall) => {
        const dx = wall.x2 - wall.x1;
        const dz = wall.z2 - wall.z1;
        const len2 = dx * dx + dz * dz || 1;
        let t = ((x - wall.x1) * dx + (z - wall.z1) * dz) / len2;
        t = Math.max(0, Math.min(1, t));
        const px = wall.x1 + dx * t;
        const pz = wall.z1 + dz * t;
        const dist = Math.hypot(x - px, z - pz);
        if (!best || dist < best.dist) best = { wall, t, dist };
    });
    return best && best.dist < 28 ? best : null;
}

canvas.addEventListener('pointerdown', (event) => {
    const hit = nearest(event);
    if (!hit) return;
    const width = tool === 'door' ? 90 : 120;
    const offset = Math.max(0, Math.min(hit.wall.length - width, hit.t * hit.wall.length - width / 2));
    const existing = openings.findIndex((item) => item.wallId === hit.wall.id && Math.abs(item.offset - offset) < item.width);
    if (existing >= 0) openings.splice(existing, 1);
    else openings.push({ id: `o${Date.now()}`, wallId: hit.wall.id, kind: tool, offset: Math.round(offset), width, height: tool === 'door' ? 210 : 120, sill: tool === 'door' ? 0 : 90 });
    draw();
});
document.getElementById('tool-door').onclick = () => { tool = 'door'; document.getElementById('tool-door').className = 'btn primary'; document.getElementById('tool-window').className = 'btn'; };
document.getElementById('tool-window').onclick = () => { tool = 'window'; document.getElementById('tool-window').className = 'btn primary'; document.getElementById('tool-door').className = 'btn'; };
document.getElementById('create').addEventListener('input', draw);
draw();

async function load(phone) {
    const box = document.getElementById('list');
    box.textContent = 'Aranıyor…';
    const digits = String(phone || '').replace(/\D/g, '');
    const query = digits.length >= 10 ? `?phone=${encodeURIComponent(phone)}` : '';
    const response = await fetch(`${indexUrl}${query}`, { headers: { Accept: 'application/json' } });
    const data = await response.json();
    const projects = data.projects || [];
    box.innerHTML = projects.length ? '' : '<p class="hint">Kayıtlı proje yok.</p>';
    projects.forEach((item) => {
        const row = document.createElement('div');
        row.className = 'project';
        const open = document.createElement('button');
        open.type = 'button';
        open.className = 'open';
        const name = document.createElement('strong');
        name.textContent = item.projectName || 'Proje';
        const who = document.createElement('span');
        who.textContent = item.customerName || '';
        const when = document.createElement('span');
        when.textContent = item.updatedAt ? new Date(item.updatedAt).toLocaleString('tr-TR') : '';
        open.append(name, who, when);
        if (item.phone) {
            const phone = document.createElement('span');
            phone.textContent = item.phone;
            open.insertBefore(phone, when);
        }
        open.onclick = () => { location.href = `${designUrl}?proje=${item.token}`; };
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn danger';
        remove.textContent = 'Sil';
        remove.onclick = async () => {
            if (!confirm(`${item.projectName} silinsin mi?`)) return;
            const result = await fetch(`/dolap/proje/${item.token}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
            if (result.ok) load(document.getElementById('phone').value);
        };
        row.append(open, remove);
        box.append(row);
    });
}
document.getElementById('search').onclick = () => load(document.getElementById('phone').value);
document.getElementById('create').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.target);
    const msg = document.getElementById('msg');
    const left = Number(form.get('leftWall'));
    const front = Number(form.get('frontWall'));
    const right = Number(form.get('rightWall'));
    const roomH = Number(form.get('roomHeight'));
    const name = String(form.get('projectName') || 'Dolabım');
    msg.textContent = 'Kaydediliyor…';
    const response = await fetch(storeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        body: JSON.stringify({
            customerName: String(form.get('customerName') || '').trim(),
            phone: '',
            projectName: name,
            note: String(form.get('note') || ''),
            design: {
                name, customerName: String(form.get('customerName') || '').trim(), phone: '', note: String(form.get('note') || ''),
                series: 'duz', height: Math.min(270, Math.max(160, roomH)), depth: 60,
                carcassId: '', doorColorId: '', interiorId: '', backId: '', shelfId: '',
                aluminumId: 'al-gumus', glassId: 'cam-fum', plinth: true, plinthHidden: false, kulp: true, led: true,
                wallWidth: front, leftWall: left, rightWall: right, roomHeight: roomH, modules: [],
                roomSketch: { openings: openings.slice() },
            },
        }),
    });
    const data = await response.json().catch(() => null);
    if (response.status === 429) { msg.textContent = 'Çok sık denendi. Bir dakika sonra tekrar dene.'; return; }
    if (!response.ok || !data?.token) { msg.textContent = data?.message || 'Proje kaydı yazılamadı.'; return; }
    location.href = `${designUrl}?proje=${data.token}`;
});
load('');
</script>
</body>
</html>
