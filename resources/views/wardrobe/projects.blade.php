<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Dolap projeleri',
        'metaDescription' => 'Kayıtlı dolap projeleri ve yeni 3D tasarım.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.meemare-shell-styles')
    <style>
        :root {
            --ink: #1c1c1c; --muted: #6d6a64; --line: #e6e2da; --bg: #eceae6;
            --panel: #fffcf8; --accent: #1f6b4a; --danger: #9f2d2d;
            --shadow: 0 10px 30px rgba(28, 28, 28, .08);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Montserrat, system-ui, sans-serif; color: var(--ink); background: var(--bg); }
        button, input { font-family: inherit; }
        button { cursor: pointer; }
        .hub { min-height: 100dvh; display: flex; flex-direction: column; }
        .hub-top {
            padding: 14px 18px 12px; background: var(--panel); border-bottom: 1px solid var(--line);
            display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px 16px;
        }
        .hub-top h1 { margin: 0; font-size: 22px; letter-spacing: -.02em; }
        .hub-top p { margin: 4px 0 0; font-size: 13px; color: var(--muted); max-width: 52ch; }
        .hub-top__actions { margin-left: auto; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .hub-nav { padding: 8px 18px; background: #faf8f5; border-bottom: 1px solid var(--line); }
        .hub-main {
            flex: 1; width: min(1180px, 100%); margin: 0 auto; padding: 18px 16px 40px;
            display: grid; gap: 18px; align-items: start;
        }
        @media (min-width: 960px) {
            .hub-main { grid-template-columns: minmax(0, 1fr) 340px; }
        }
        .panel {
            background: var(--panel); border: 1px solid var(--line); border-radius: 16px;
            box-shadow: var(--shadow); overflow: hidden;
        }
        .panel-head {
            padding: 14px 16px; border-bottom: 1px solid var(--line);
            display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
        }
        .panel-head h2 { margin: 0; font-size: 15px; font-weight: 700; }
        .panel-head .grow { flex: 1; min-width: 120px; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .search {
            flex: 1; min-width: 180px; border: 1px solid var(--line); border-radius: 999px;
            padding: 8px 14px; background: #fff; font-size: 13px;
        }
        .btn {
            border: 1px solid var(--line); background: #fff; border-radius: 999px;
            padding: 8px 14px; font-size: 12px; font-weight: 600;
        }
        .btn:hover { border-color: #c8c2b6; }
        .btn.primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn.ghost { background: transparent; }
        .btn.danger { color: var(--danger); border-color: #f0d4d2; }
        .btn.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        .grid { padding: 14px 16px 16px; display: grid; gap: 12px; }
        @media (min-width: 600px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1100px) { .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .card {
            border: 1px solid var(--line); border-radius: 14px; background: #fff;
            padding: 14px; display: flex; flex-direction: column; gap: 10px; min-height: 168px;
            transition: border-color .15s, box-shadow .15s;
        }
        .card:hover { border-color: #c5dece; box-shadow: 0 8px 22px rgba(31, 107, 74, .1); }
        .card__top { display: flex; gap: 8px; align-items: flex-start; }
        .card__thumb {
            width: 44px; height: 44px; border-radius: 12px; flex: 0 0 auto;
            background: linear-gradient(145deg, #e8e4dc, #f7f5f0);
            display: grid; place-items: center; font-size: 18px;
        }
        .card__title { margin: 0; font-size: 15px; font-weight: 700; line-height: 1.25; }
        .card__sub { margin: 2px 0 0; font-size: 12px; color: var(--muted); }
        .badges { display: flex; flex-wrap: wrap; gap: 6px; }
        .badge {
            font-size: 10px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
            padding: 4px 8px; border-radius: 999px; background: #eef6f1; color: #1f5f44;
        }
        .badge.muted { background: #f0eeea; color: #6d6a64; text-transform: none; font-weight: 600; }
        .card__meta { font-size: 11px; color: var(--muted); line-height: 1.4; }
        .card__actions { margin-top: auto; display: flex; gap: 8px; }
        .card__actions .btn.primary { flex: 1; text-align: center; }
        .empty {
            grid-column: 1 / -1; text-align: center; padding: 36px 20px; color: var(--muted);
            border: 1px dashed #d5d0c6; border-radius: 14px; background: #faf8f5;
        }
        .empty strong { display: block; color: var(--ink); font-size: 15px; margin-bottom: 6px; }
        .composer { padding: 16px; display: flex; flex-direction: column; gap: 12px; }
        #create { display: flex; flex-direction: column; gap: 12px; }
        .composer h2 { margin: 0; font-size: 16px; }
        .composer .lead { margin: 0; font-size: 12px; color: var(--muted); line-height: 1.45; }
        label { display: block; font-size: 11px; font-weight: 600; color: var(--muted); margin-bottom: 4px; }
        .field input { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px; background: #fff; font-size: 13px; }
        .field { margin: 0; }
        .preset-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .preset {
            text-align: left; border: 1px solid var(--line); background: #fff; border-radius: 12px;
            padding: 10px; font-size: 12px; line-height: 1.35;
        }
        .preset b { display: block; font-size: 13px; margin-bottom: 4px; }
        .preset span { color: var(--muted); font-size: 11px; }
        .preset.is-on { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); background: #f4fbf7; }
        .steps {
            display: flex; gap: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; color: var(--muted);
        }
        .steps span { padding: 4px 8px; border-radius: 999px; background: #f0eeea; }
        .steps span.is-on { background: var(--accent); color: #fff; }
        .msg { min-height: 18px; font-size: 12px; color: var(--danger); }
        .loading { padding: 24px; text-align: center; color: var(--muted); font-size: 13px; }
        .filter-chip { font-size: 11px; }
    </style>
</head>
<body>
<div class="hub">
    <header class="hub-top">
        <div>
            <h1>Projeler</h1>
            <p>Kayıtlı dolapları açın veya yeni müşteri için plan çizimiyle 3D tasarım başlatın.</p>
        </div>
        <div class="hub-top__actions">
            <a href="{{ $designUrl }}" class="btn">Boş 3D sahnede devam</a>
            <button type="button" class="btn primary" id="jump-create">+ Yeni proje</button>
        </div>
    </header>
    <div class="hub-nav">@include('partials.meemare-apps-nav', ['activeApp' => 'projeler'])</div>

    <main class="hub-main">
        <section class="panel" aria-labelledby="list-heading">
            <div class="panel-head">
                <h2 id="list-heading">Kayıtlı projeler</h2>
                <p class="grow" style="margin:0;font-size:12px;color:var(--muted)">Son 20 kayıt</p>
                <div class="toolbar">
                    <input id="q" class="search" type="search" placeholder="Proje veya müşteri ara…" autocomplete="off">
                    <button type="button" class="btn ghost filter-chip is-on" data-filter="all">Tümü</button>
                    <button type="button" class="btn ghost filter-chip" data-filter="active">Modüllü</button>
                    <button type="button" class="btn" id="refresh" title="Listeyi yenile">Yenile</button>
                </div>
            </div>
            <div class="grid" id="list">
                <div class="loading" id="loading">Projeler yükleniyor…</div>
            </div>
        </section>

        <aside class="panel" id="create-panel" aria-labelledby="create-heading">
            <div class="panel-head" style="border:0;padding-bottom:0">
                <h2 id="create-heading">Yeni proje</h2>
            </div>
            <div class="composer">
                <div class="steps" aria-hidden="true">
                    <span class="is-on">1 Müşteri</span>
                    <span>2 Plan çiz</span>
                    <span>3 Modül</span>
                </div>
                <p class="lead">Ölçü seçimi yok — önce <strong>duvar, kapı ve pencere</strong> çizersiniz (plan sayfası ile aynı editör), sonra modül eklersiniz.</p>
                <form id="create">
                    <div class="field">
                        <label for="customerName">Müşteri adı</label>
                        <input id="customerName" name="customerName" required maxlength="120" autocomplete="name" placeholder="Örn. Ayşe Yılmaz">
                    </div>
                    <div class="field">
                        <label for="projectName">Proje adı</label>
                        <input id="projectName" name="projectName" required maxlength="80" value="Dolabım" placeholder="Örn. L giyinme odası">
                    </div>
                    <div class="field">
                        <label for="note">Not (isteğe bağlı)</label>
                        <input id="note" name="note" maxlength="2000" placeholder="Teslim, ölçü notu…">
                    </div>
                    <p class="msg" id="msg"></p>
                    <button class="btn primary" type="submit" style="width:100%;padding:12px 16px;font-size:13px">Proje oluştur → plan çiz</button>
                </form>
            </div>
        </aside>
    </main>
</div>
<script>
const storeUrl = @json($projectStoreUrl);
const indexUrl = @json($projectIndexUrl);
const designUrl = @json($designUrl);
const csrf = document.querySelector('meta[name="csrf-token"]').content;

const DEFAULT_ROOM = { front: 300, left: 300, right: 300, height: 320 };

let allProjects = [];
let filterMode = 'all';

function formatWhen(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    const now = new Date();
    const sameDay = date.toDateString() === now.toDateString();
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    if (sameDay) return `Bugün ${date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}`;
    if (date.toDateString() === yesterday.toDateString()) return `Dün ${date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}`;
    return date.toLocaleString('tr-TR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function roomLabel(room) {
    const f = room?.frontCm || 0;
    const l = room?.leftCm || 0;
    const r = room?.rightCm || 0;
    if (!f && !l && !r) return 'Oda ölçüsü henüz yok';
    return `Ön ${f} · Sol ${l} · Sağ ${r} cm`;
}

function filteredProjects() {
    const q = (document.getElementById('q').value || '').trim().toLocaleLowerCase('tr-TR');
    return allProjects.filter((item) => {
        if (filterMode === 'active' && !(item.moduleCount > 0)) return false;
        if (!q) return true;
        const hay = `${item.projectName || ''} ${item.customerName || ''}`.toLocaleLowerCase('tr-TR');
        return hay.includes(q);
    });
}

function renderList() {
    const box = document.getElementById('list');
    const items = filteredProjects();
    if (!items.length) {
        box.innerHTML = `
            <div class="empty">
                <strong>${allProjects.length ? 'Arama sonucu yok' : 'Henüz proje yok'}</strong>
                ${allProjects.length ? 'Filtreyi temizleyin veya yeni proje oluşturun.' : 'Sağdaki formdan ilk müşteri projenizi başlatın.'}
            </div>`;
        return;
    }
    box.innerHTML = '';
    items.forEach((item) => {
        const card = document.createElement('article');
        card.className = 'card';
        const customer = item.customerName ? item.customerName : 'Müşteri belirtilmedi';
        const modText = item.moduleCount > 0 ? `${item.moduleCount} modül` : 'Modül eklenmedi';
        card.innerHTML = `
            <div class="card__top">
                <div class="card__thumb" aria-hidden="true">🚪</div>
                <div>
                    <h3 class="card__title">${escapeHtml(item.projectName || 'Proje')}</h3>
                    <p class="card__sub">${escapeHtml(customer)}</p>
                </div>
            </div>
            <div class="badges">
                <span class="badge">${escapeHtml(modText)}</span>
                <span class="badge muted">${escapeHtml(formatWhen(item.updatedAt))}</span>
            </div>
            <p class="card__meta">${escapeHtml(roomLabel(item.room))}</p>
            <div class="card__actions">
                <button type="button" class="btn primary" data-open>Aç</button>
                <button type="button" class="btn danger" data-del>Sil</button>
            </div>`;
        card.querySelector('[data-open]').onclick = () => {
            location.href = `${designUrl}?kayit=${item.id}`;
        };
        card.querySelector('[data-del]').onclick = () => removeProject(item);
        box.append(card);
    });
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

async function load() {
    const box = document.getElementById('list');
    document.getElementById('loading')?.remove();
    box.innerHTML = '<div class="loading">Projeler yükleniyor…</div>';
    try {
        const response = await fetch(indexUrl, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        allProjects = data.projects || [];
        renderList();
    } catch {
        box.innerHTML = '<div class="empty"><strong>Liste alınamadı</strong>Bağlantıyı kontrol edip yenileyin.</div>';
    }
}

async function removeProject(item) {
    if (!confirm(`「${item.projectName}」 silinsin mi?`)) return;
    const result = await fetch(`/dolap/proje/kayit/${item.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
    });
    if (result.ok) load();
}

document.querySelectorAll('[data-filter]').forEach((btn) => {
    btn.addEventListener('click', () => {
        filterMode = btn.dataset.filter;
        document.querySelectorAll('[data-filter]').forEach((el) => el.classList.toggle('is-on', el === btn));
        renderList();
    });
});

document.getElementById('q').addEventListener('input', () => renderList());
document.getElementById('refresh').onclick = () => load();
document.getElementById('jump-create').onclick = () => {
    document.getElementById('create-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('customerName').focus();
};

document.getElementById('create').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = new FormData(event.target);
    const msg = document.getElementById('msg');
    const room = DEFAULT_ROOM;
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
                name,
                customerName: String(form.get('customerName') || '').trim(),
                phone: '',
                note: String(form.get('note') || ''),
                series: 'duz',
                height: Math.min(270, Math.max(160, room.height - 50)),
                depth: 60,
                carcassId: '',
                doorColorId: '',
                interiorId: '',
                backId: '',
                shelfId: '',
                aluminumId: 'al-gumus',
                glassId: 'cam-fum',
                plinth: true,
                plinthHidden: false,
                kulp: true,
                led: true,
                wallWidth: room.front,
                leftWall: room.left,
                rightWall: room.right,
                roomHeight: room.height,
                modules: [],
                roomSketch: { walls: [], openings: [] },
            },
        }),
    });
    const data = await response.json().catch(() => null);
    if (response.status === 429) { msg.textContent = 'Çok sık denendi. Bir dakika sonra tekrar dene.'; return; }
    if (!response.ok || !data?.token) { msg.textContent = data?.message || 'Proje kaydı yazılamadı.'; return; }
    location.href = `${designUrl}?proje=${data.token}&plan=1`;
});

load();
</script>
</body>
</html>