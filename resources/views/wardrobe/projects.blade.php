@php
    $brand = \App\Support\CompanyBranding::siteName($company ?? null);
    $logoUrl = (($company?->logoUrl && $company->logoDisplayUrl()) ? $company->logoDisplayUrl() : null);
    $heroSrc = asset('images/wardrobe/set-giyinme.jpg');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Dolap projeleri',
        'metaDescription' => 'Kayıtlı dolap projeleri ve yeni 3D tasarım.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #f2f6f3;
            --ink-dim: rgba(242, 246, 243, .72);
            --ink-soft: rgba(242, 246, 243, .48);
            --line: rgba(242, 246, 243, .14);
            --accent: #3db87a;
            --accent-deep: #1a7a52;
            --panel: rgba(12, 18, 15, .72);
            --panel-solid: #101814;
            --field: rgba(10, 16, 13, .55);
            --danger: #ff8d7a;
            --ease: cubic-bezier(.22, 1, .36, 1);
            --display: "Syne", "Avenir Next", sans-serif;
            --ui: "Figtree", "Segoe UI", sans-serif;
            --shadow: 0 22px 60px rgba(0, 0, 0, .35);
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; min-height: 100%;
            color: var(--ink);
            font-family: var(--ui);
            background: #0a100d;
            -webkit-font-smoothing: antialiased;
        }
        button, input { font-family: inherit; }
        a { color: inherit; text-decoration: none; }
        button { cursor: pointer; }

        .hub {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            isolation: isolate;
        }

        .hub-hero {
            position: relative;
            overflow: hidden;
            min-height: clamp(220px, 38vw, 340px);
            display: grid;
            align-items: end;
        }
        .hub-hero__media {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
        }
        .hub-hero__media img {
            width: 100%; height: 100%; object-fit: cover; object-position: 55% 35%;
            transform: scale(1.05);
            animation: hub-drift 20s var(--ease) infinite alternate;
        }
        .hub-hero__veil {
            position: absolute; inset: 0; z-index: 1; pointer-events: none;
            background:
                linear-gradient(105deg, rgba(7,12,10,.78) 0%, rgba(7,12,10,.28) 52%, rgba(7,12,10,.62) 100%),
                linear-gradient(180deg, rgba(7,12,10,.18) 0%, rgba(7,12,10,.72) 100%);
        }
        .hub-hero__inner {
            position: relative; z-index: 2;
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 1.1rem clamp(1rem, 3vw, 1.5rem) 1.5rem;
            display: grid;
            gap: 1.25rem;
        }
        @media (min-width: 860px) {
            .hub-hero__inner {
                grid-template-columns: 1fr auto;
                align-items: end;
                gap: 2rem;
                padding-bottom: 2rem;
            }
        }

        .hub-brand {
            display: inline-flex; align-items: center; gap: .7rem;
            margin-bottom: 1.1rem; width: fit-content;
        }
        .hub-brand img { height: 28px; width: auto; display: block; }
        .hub-brand span {
            font-family: var(--display);
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -.03em;
            text-transform: uppercase;
        }
        .hub-copy h1 {
            margin: 0;
            font-family: var(--display);
            font-size: clamp(2rem, 5.5vw, 3.4rem);
            font-weight: 700;
            letter-spacing: -.045em;
            line-height: .98;
            max-width: 12ch;
        }
        .hub-copy p {
            margin: .75rem 0 0;
            max-width: 36ch;
            color: var(--ink-dim);
            font-size: 1.02rem;
            line-height: 1.45;
            font-weight: 500;
        }
        .hub-cta {
            display: flex; flex-wrap: wrap; gap: .65rem;
            justify-content: flex-start;
        }
        @media (min-width: 860px) {
            .hub-cta { justify-content: flex-end; padding-bottom: .2rem; }
        }

        .hub-nav {
            position: sticky; top: 0; z-index: 20;
            padding: .65rem clamp(1rem, 3vw, 1.5rem);
            background: rgba(10, 16, 13, .82);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(16px) saturate(1.15);
            -webkit-backdrop-filter: blur(16px) saturate(1.15);
        }
        .hub-nav .meemare-apps {
            width: min(1180px, 100%);
            margin: 0 auto;
        }
        .hub-nav .meemare-apps a,
        .hub-nav .meemare-apps button.meemare-apps__exit {
            background: rgba(255,255,255,.04);
            border-color: var(--line);
            color: var(--ink);
        }
        .hub-nav .meemare-apps a:hover,
        .hub-nav .meemare-apps button.meemare-apps__exit:hover {
            background: rgba(255,255,255,.08);
            border-color: rgba(242,246,243,.28);
        }
        .hub-nav .meemare-apps a.is-on,
        .hub-nav .meemare-apps a.is-accent {
            background: var(--accent-deep);
            border-color: var(--accent-deep);
            color: #fff;
        }
        .hub-nav .meemare-apps .meemare-apps__sep { background: var(--line); }
        .hub-nav .meemare-apps__menu {
            background: #121a16;
            border-color: var(--line);
            box-shadow: var(--shadow);
        }
        .hub-nav .meemare-apps__menu a,
        .hub-nav .meemare-apps__menu button.meemare-apps__exit {
            color: var(--ink);
            background: transparent;
            border: 0;
        }
        .hub-nav .meemare-apps__menu a.is-on,
        .hub-nav .meemare-apps__menu a.is-accent {
            background: var(--accent-deep);
            color: #fff;
        }

        .hub-main {
            flex: 1;
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 1.25rem clamp(1rem, 3vw, 1.5rem) 2.5rem;
            display: grid;
            gap: 1.1rem;
            align-items: start;
        }
        @media (min-width: 960px) {
            .hub-main { grid-template-columns: minmax(0, 1fr) 360px; gap: 1.25rem; }
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
            backdrop-filter: blur(18px) saturate(1.15);
            -webkit-backdrop-filter: blur(18px) saturate(1.15);
        }
        .panel-head {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid var(--line);
            display: flex; flex-wrap: wrap; align-items: center; gap: .75rem;
        }
        .panel-head h2 {
            margin: 0;
            font-family: var(--display);
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -.02em;
        }
        .panel-head .grow { flex: 1; min-width: 100px; margin: 0; font-size: .85rem; color: var(--ink-soft); }
        .toolbar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
        .search {
            flex: 1; min-width: 160px;
            border: 1px solid var(--line); border-radius: 999px;
            padding: .7rem 1rem; background: var(--field); color: var(--ink); font-size: .92rem;
            outline: none;
        }
        .search:focus {
            border-color: rgba(61,184,122,.55);
            box-shadow: 0 0 0 3px rgba(61,184,122,.14);
        }
        .search::placeholder { color: rgba(242,246,243,.35); }

        .btn {
            border: 1px solid var(--line); background: rgba(255,255,255,.05);
            border-radius: 999px; padding: .7rem 1rem;
            font-size: .85rem; font-weight: 700; color: var(--ink);
            transition: background .18s var(--ease), border-color .18s var(--ease), transform .18s var(--ease);
        }
        .btn:hover { background: rgba(255,255,255,.09); border-color: rgba(242,246,243,.28); }
        .btn:active { transform: scale(.98); }
        .btn.primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-deep));
            border-color: transparent; color: #07110c;
            box-shadow: 0 12px 30px rgba(26,122,82,.28);
            font-family: var(--display);
        }
        .btn.primary:hover { filter: brightness(1.05); }
        .btn.ghost { background: transparent; }
        .btn.danger { color: var(--danger); border-color: rgba(255,141,122,.28); }
        .btn.is-on { background: #fff; color: #0a100d; border-color: #fff; }
        .btn.lg { padding: .85rem 1.2rem; font-size: .95rem; }

        .grid { padding: 1rem 1.1rem 1.15rem; display: grid; gap: .85rem; }
        @media (min-width: 600px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1100px) { .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

        .card {
            border: 1px solid var(--line); border-radius: 18px;
            background: rgba(255,255,255,.035);
            padding: 1rem; display: flex; flex-direction: column; gap: .7rem; min-height: 168px;
            transition: border-color .18s var(--ease), background .18s var(--ease), transform .18s var(--ease);
        }
        .card:hover {
            border-color: rgba(61,184,122,.4);
            background: rgba(61,184,122,.06);
            transform: translateY(-1px);
        }
        .card__top { display: flex; gap: .7rem; align-items: flex-start; }
        .card__thumb {
            width: 44px; height: 44px; border-radius: 14px; flex: 0 0 auto;
            background:
                linear-gradient(145deg, rgba(61,184,122,.25), rgba(255,255,255,.06)),
                url('{{ $heroSrc }}') center/cover;
            border: 1px solid var(--line);
        }
        .card__title {
            margin: 0; font-family: var(--display); font-size: 1.05rem;
            font-weight: 700; letter-spacing: -.02em; line-height: 1.2;
        }
        .card__sub { margin: .15rem 0 0; font-size: .85rem; color: var(--ink-dim); }
        .badges { display: flex; flex-wrap: wrap; gap: .4rem; }
        .badge {
            font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
            padding: .3rem .55rem; border-radius: 999px;
            background: rgba(61,184,122,.14); color: var(--accent);
        }
        .badge.muted {
            background: rgba(255,255,255,.06); color: var(--ink-soft);
            text-transform: none; font-weight: 600; letter-spacing: 0;
        }
        .card__meta { font-size: .78rem; color: var(--ink-soft); line-height: 1.4; margin: 0; }
        .card__actions { margin-top: auto; display: flex; gap: .5rem; }
        .card__actions .btn.primary { flex: 1; text-align: center; }

        .empty {
            grid-column: 1 / -1; text-align: center; padding: 2.4rem 1.2rem;
            color: var(--ink-dim); border: 1px dashed rgba(242,246,243,.18);
            border-radius: 18px; background: rgba(255,255,255,.03);
        }
        .empty strong {
            display: block; color: var(--ink); font-family: var(--display);
            font-size: 1.1rem; margin-bottom: .4rem;
        }

        .composer { padding: 1.1rem 1.15rem 1.25rem; display: flex; flex-direction: column; gap: .9rem; }
        #create { display: flex; flex-direction: column; gap: .85rem; }
        .composer h2 {
            margin: 0; font-family: var(--display); font-size: 1.25rem;
            letter-spacing: -.02em;
        }
        .composer .lead {
            margin: 0; font-size: .9rem; color: var(--ink-dim); line-height: 1.45;
        }
        label {
            display: block; font-size: .72rem; font-weight: 700; letter-spacing: .06em;
            text-transform: uppercase; color: var(--ink-soft); margin-bottom: .35rem;
        }
        .field input {
            width: 100%; border: 1px solid var(--line); border-radius: 14px;
            padding: .85rem 1rem; background: var(--field); color: var(--ink);
            font-size: .98rem; outline: none; min-height: 46px;
        }
        .field input:focus {
            border-color: rgba(61,184,122,.55);
            box-shadow: 0 0 0 3px rgba(61,184,122,.14);
        }
        .field input::placeholder { color: rgba(242,246,243,.32); }
        .field { margin: 0; }
        .steps {
            display: flex; flex-wrap: wrap; gap: .4rem;
            font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
            color: var(--ink-soft);
        }
        .steps span {
            padding: .35rem .6rem; border-radius: 999px;
            background: rgba(255,255,255,.06); border: 1px solid var(--line);
        }
        .steps span.is-on {
            background: rgba(61,184,122,.16); color: var(--accent); border-color: rgba(61,184,122,.35);
        }
        .msg { min-height: 18px; font-size: .85rem; color: var(--danger); margin: 0; }
        .loading { padding: 1.6rem; text-align: center; color: var(--ink-dim); font-size: .92rem; }
        .filter-chip { font-size: .78rem; }

        @keyframes hub-drift {
            from { transform: scale(1.05) translate3d(0,0,0); }
            to { transform: scale(1.09) translate3d(-1.2%, -1%, 0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .hub-hero__media img { animation: none; }
        }
    </style>
</head>
<body>
<div class="hub">
    <header class="hub-hero" aria-label="Dolap stüdyosu">
        <div class="hub-hero__media" aria-hidden="true">
            <img src="{{ $heroSrc }}" alt="" width="887" height="903" decoding="async" fetchpriority="high">
        </div>
        <div class="hub-hero__veil" aria-hidden="true"></div>
        <div class="hub-hero__inner">
            <div class="hub-copy">
                <a class="hub-brand" href="{{ route('wardrobe.index') }}">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brand }}">
                    @else
                        <span>{{ $brand }}</span>
                    @endif
                </a>
                <h1>Dolap projeleri</h1>
                <p>Kayıtlı tasarımları açın veya yeni müşteri için plan çizimiyle 3D stüdyoya geçin.</p>
            </div>
            <div class="hub-cta">
                <a href="{{ $designUrl }}?plan=1" class="btn">Boş planda devam</a>
                <button type="button" class="btn primary lg" id="jump-create">+ Yeni proje</button>
            </div>
        </div>
    </header>

    <div class="hub-nav">@include('partials.meemare-apps-nav', ['activeApp' => 'projeler'])</div>

    <main class="hub-main">
        <section class="panel" aria-labelledby="list-heading">
            <div class="panel-head">
                <h2 id="list-heading">Kayıtlı projeler</h2>
                <p class="grow">Son 20 kayıt</p>
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
                <p class="lead">Önce <strong>duvar, kapı ve pencere</strong> çizersiniz; sonra modül eklersiniz.</p>
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
                    <button class="btn primary" type="submit" style="width:100%;padding:12px 16px;font-size:15px">Proje oluştur → plan çiz</button>
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
                <div class="card__thumb" aria-hidden="true"></div>
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
