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
    @include('partials.meemare-shell-styles')
    <style>
        :root { --ink: #1c1c1c; --muted: #6d6a64; --line: #e6e2da; --bg: #f4f3f0; --panel: #fffcf8; --accent: #1f6b4a; --danger: #9f2d2d; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Montserrat, system-ui, sans-serif; color: var(--ink); background: var(--bg); }
        button, input { font-family: inherit; }
        button { cursor: pointer; }
        .page-shell { min-height: 100dvh; display: grid; grid-template-rows: auto auto auto 1fr; }
        main { display: grid; grid-template-columns: 1fr; gap: 16px; max-width: 1180px; margin: 0 auto; padding: 20px 16px 40px; width: 100%; }
        @media (min-width: 900px) { main { grid-template-columns: 1.2fr 1fr; align-items: start; } }
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
        .msg { min-height: 18px; color: var(--danger); font-size: 13px; }
        .is-hidden { display: none; }
        .mode .btn.is-on { background: var(--accent); color: #fff; border-color: var(--accent); }
        .plan-cta { margin: 0 0 12px; padding: 12px; border: 1px dashed #cfe0d6; border-radius: 10px; background: #f7faf8; font-size: 13px; }
        .plan-cta a { color: var(--accent); font-weight: 700; }
    </style>
</head>
<body>
<div class="page-shell">
    <div class="meemare-tool-bar">
        <strong>Dolap projeleri</strong>
        <span class="lead">Önce proje oluşturun; duvar çizimi proje içinden veya ayrı plan sayfasından yapılır.</span>
    </div>
    <div class="meemare-nav-row">@include('partials.meemare-apps-nav', ['activeApp' => 'projeler'])</div>
    @include('partials.meemare-workflow', ['workflowStep' => 'projeler'])
<main>
    <section>
        <h1>Kayıtlı projeler</h1>
        <p class="hint">Bir karta basınca proje açılır. Yeni oda alttaki formdan başlar.</p>
        <div class="row">
            <button type="button" class="btn" id="search">Listeyi yenile</button>
        </div>
        <div id="list"></div>
    </section>
    <section class="form">
        <h2>Yeni proje</h2>
        <form id="create">
            <div class="field"><label>Müşteri adı</label><input name="customerName" required maxlength="120" autocomplete="name"></div>
            <div class="field"><label>Proje adı</label><input name="projectName" required maxlength="80" value="Dolabım"></div>
            <div class="field"><label>Not (isteğe bağlı)</label><input name="note" maxlength="2000"></div>
            <p class="hint">Proje oluşturulunca 3D dolap ekranı açılır; oradan <strong>Duvar çiz</strong> ile OpenPlan kullanılır.</p>
            <p class="msg" id="msg"></p>
            <button class="btn primary" type="submit">Proje oluştur</button>
        </form>
    </section>
</main>
</div>
<script>
const storeUrl = @json($projectStoreUrl);
const indexUrl = @json($projectIndexUrl);
const designUrl = @json($designUrl);
const csrf = document.querySelector('meta[name="csrf-token"]').content;

async function load() {
    const box = document.getElementById('list');
    box.textContent = 'Aranıyor…';
    const response = await fetch(indexUrl, { headers: { Accept: 'application/json' } });
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
        const when = document.createElement('span');
        when.textContent = item.updatedAt ? new Date(item.updatedAt).toLocaleString('tr-TR') : '';
        open.append(name, when);
        open.onclick = () => { location.href = `${designUrl}?kayit=${item.id}`; };
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn danger';
        remove.textContent = 'Sil';
        remove.onclick = async () => {
            if (!confirm(`${item.projectName} silinsin mi?`)) return;
            const result = await fetch(`/dolap/proje/kayit/${item.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
            if (result.ok) load();
        };
        row.append(open, remove);
        box.append(row);
    });
}
document.getElementById('search').onclick = () => load();
document.getElementById('create').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.target);
    const msg = document.getElementById('msg');
    const left = 300;
    const front = 300;
    const right = 300;
    const roomH = 320;
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
                roomSketch: { openings: [] },
            },
        }),
    });
    const data = await response.json().catch(() => null);
    if (response.status === 429) { msg.textContent = 'Çok sık denendi. Bir dakika sonra tekrar dene.'; return; }
    if (!response.ok || !data?.token) { msg.textContent = data?.message || 'Proje kaydı yazılamadı.'; return; }
    location.href = `${designUrl}?proje=${data.token}`;
});
load();
</script>
</body>
</html>
