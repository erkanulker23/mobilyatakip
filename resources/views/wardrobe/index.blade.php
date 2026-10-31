<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => '3D Dolap Tasarla',
        'metaDescription' => 'Meemare 3D Studio ile kendi modüler dolabınızı tasarlayın. Ölçü, raf, çekmece, kapak ve renk canlı olarak değişir.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #17211c;
            --muted: #5e6a64;
            --line: #e4e1da;
            --bg: #f3f1ec;
            --panel: #fffcf8;
            --accent: #1f6b4a;
            --accent-2: #e7f3ec;
            --danger: #9f2d2d;
            --shadow: 0 10px 30px rgba(23, 33, 28, .08);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; overflow: hidden; font-family: Montserrat, system-ui, sans-serif; color: var(--ink); background: var(--bg); }
        button, input, select { font-family: inherit; }
        button { cursor: pointer; }
        #studio { display: grid; grid-template-rows: 58px 1fr 52px; height: 100%; }
        header.bar, footer.bar { display: flex; align-items: center; gap: 10px; padding: 0 14px; background: var(--panel); border-color: var(--line); }
        header.bar { border-bottom: 1px solid var(--line); }
        footer.bar { border-top: 1px solid var(--line); justify-content: space-between; }
        .brand { display: flex; flex-direction: column; line-height: 1.1; min-width: 0; }
        .brand strong { font-size: 13px; letter-spacing: .08em; }
        .brand span { font-size: 11px; color: var(--muted); }
        .name-input { border: 0; background: transparent; font-weight: 600; font-size: 14px; width: 180px; }
        .name-input:focus { outline: none; }
        .grow { flex: 1; }
        .btn, .btn-ghost, .btn-primary { border-radius: 8px; border: 1px solid var(--line); background: #fff; padding: 7px 10px; font-size: 12px; font-weight: 600; }
        .btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn:disabled { opacity: .4; cursor: default; }
        .btn-ghost { background: transparent; }
        .workspace { display: grid; grid-template-columns: 300px 1fr 320px; min-height: 0; }
        aside { background: var(--panel); min-height: 0; display: flex; flex-direction: column; }
        aside.left { border-right: 1px solid var(--line); }
        aside.right { border-left: 1px solid var(--line); }
        .side-head, .side-body { padding: 12px; }
        .side-body { overflow: auto; min-height: 0; }
        .search, .field input, .field select { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; background: #fff; font-size: 13px; }
        .cats { display: flex; gap: 6px; flex-wrap: wrap; margin: 10px 0; }
        .cat { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 4px 8px; font-size: 11px; }
        .cat.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        .card { display: grid; grid-template-columns: 72px 1fr; gap: 8px; border: 1px solid var(--line); border-radius: 12px; padding: 8px; margin-bottom: 8px; background: #fff; }
        .card[draggable="true"] { cursor: grab; }
        .swatch-preview { height: 72px; border-radius: 8px; background: linear-gradient(#efe8dc, #d7c4a8); display: flex; align-items: flex-end; justify-content: center; font-size: 10px; color: #5c5146; }
        .card h3 { margin: 0 0 2px; font-size: 13px; }
        .card p { margin: 0; color: var(--muted); font-size: 11px; line-height: 1.35; }
        .card .meta { margin-top: 4px; font-size: 11px; color: var(--ink); }
        .stage { position: relative; min-width: 0; min-height: 0; background: radial-gradient(circle at 50% 40%, #f7f4ee, #ddd8ce 70%); }
        #viewport { position: absolute; inset: 0; }
        #viewport canvas { display: block; }
        .view-bar { position: absolute; left: 12px; top: 12px; display: flex; flex-wrap: wrap; gap: 6px; max-width: calc(100% - 24px); z-index: 2; }
        .warn { position: absolute; left: 12px; right: 12px; bottom: 12px; background: #fff6f4; color: var(--danger); border: 1px solid #f0c8c4; border-radius: 10px; padding: 8px 10px; font-size: 12px; z-index: 2; }
        .field { margin-bottom: 10px; }
        .field label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 4px; }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .colors { display: flex; flex-wrap: wrap; gap: 6px; }
        .dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #d5d0c8; }
        .dot.is-on { box-shadow: 0 0 0 2px var(--accent); }
        .hint { font-size: 11px; color: var(--muted); margin: 0 0 8px; }
        .stat { font-size: 12px; color: var(--muted); }
        .stat b { color: var(--ink); }
        .overlay { position: fixed; inset: 0; background: rgba(18, 24, 21, .55); z-index: 40; display: flex; align-items: center; justify-content: center; padding: 18px; }
        .sheet { width: min(720px, 100%); background: var(--panel); border-radius: 16px; padding: 22px; box-shadow: var(--shadow); }
        .choices { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .choice { text-align: left; border: 1px solid var(--line); background: #fff; border-radius: 12px; padding: 14px; }
        .choice strong { display: block; margin-bottom: 4px; }
        .choice span { color: var(--muted); font-size: 12px; }
        .hidden { display: none !important; }
        .toast { position: fixed; bottom: 70px; left: 50%; transform: translateX(-50%); background: var(--ink); color: #fff; padding: 10px 14px; border-radius: 999px; font-size: 12px; z-index: 50; }
        .mobile-nav { display: none; }
        .parts { font-size: 12px; border-top: 1px solid var(--line); margin-top: 8px; padding-top: 8px; }
        .parts li { margin: 2px 0; }
        @media (max-width: 980px) {
            .workspace { grid-template-columns: 1fr; }
            aside { position: fixed; left: 0; right: 0; bottom: 52px; height: min(72vh, 640px); z-index: 15; transform: translateY(110%); transition: transform .2s ease; box-shadow: var(--shadow); }
            aside.is-open { transform: none; }
            .mobile-nav { display: flex; gap: 8px; }
            .choices { grid-template-columns: 1fr; }
            .name-input { width: 110px; }
        }
    </style>
</head>
<body>
<div id="studio">
    <header class="bar">
        <a href="{{ route('login') }}" class="brand" style="text-decoration:none;color:inherit">
            <strong>MEEMARE</strong>
            <span>3D Studio</span>
        </a>
        <input id="project-name" class="name-input" maxlength="80" aria-label="Proje adı">
        <div class="grow"></div>
        <button type="button" class="btn" id="btn-undo" disabled>Geri</button>
        <button type="button" class="btn" id="btn-redo" disabled>İleri</button>
        <button type="button" class="btn" id="btn-mode">Kolay mod</button>
        <button type="button" class="btn" id="btn-save">Kaydet</button>
        <button type="button" class="btn" id="btn-share">Paylaş</button>
        <a class="btn" href="{{ url('/plan') }}">Oda planı</a>
        <a class="btn" href="{{ url('/dolap-uretim') }}">Üretim dolabı</a>
        <button type="button" class="btn" id="btn-shot">Görsel al</button>
    </header>
    <div class="workspace">
        <aside class="left" id="library">
            <div class="side-head">
                <input id="module-search" class="search" placeholder="Modül ara" autocomplete="off">
                <div class="cats" id="cats"></div>
            </div>
            <div class="side-body" id="module-list"></div>
        </aside>
        <section class="stage">
            <div id="viewport"></div>
            <div class="view-bar" id="view-bar"></div>
            <div id="warn" class="warn hidden"></div>
        </section>
        <aside class="right" id="inspector">
            <div class="side-body" id="inspector-body"></div>
        </aside>
    </div>
    <footer class="bar">
        <div class="stat" id="summary"></div>
        <div class="mobile-nav">
            <button type="button" class="btn" id="open-library">Modüller</button>
            <button type="button" class="btn" id="open-inspector">Ayarlar</button>
        </div>
        <button type="button" class="btn-primary" id="btn-quote">Fiyat teklifi al</button>
    </footer>
</div>

<div id="welcome" class="overlay">
    <div class="sheet">
        <p class="hint" style="letter-spacing:.12em;text-transform:uppercase">meemare 3D Studio</p>
        <h1 style="margin:0 0 6px;font-size:28px">Kendi dolabını tasarla</h1>
        <p class="hint">Hayalindeki dolabı kendin tasarla. Ölçüyü gir, modülü yerleştir, rengi seç.</p>
        <div class="choices">
            <button type="button" class="choice" id="start-empty"><strong>Sıfırdan dolap tasarla</strong><span>Boş bir dolap ölçüsüyle başla.</span></button>
            <button type="button" class="choice" id="start-template"><strong>Hazır modelden başla</strong><span>Askılık, raf veya çekmece ağırlıklı düzen.</span></button>
            <button type="button" class="choice" id="start-saved"><strong>Kayıtlı tasarımı aç</strong><span>Bu tarayıcıda sakladığın dolaplar.</span></button>
        </div>
        <div id="template-list" class="hidden" style="margin-top:14px"></div>
        <div id="saved-list" class="hidden" style="margin-top:14px"></div>
    </div>
</div>

<div id="intro" class="overlay hidden">
    <div class="sheet">
        <h2 style="margin-top:0" id="intro-title"></h2>
        <p id="intro-body"></p>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn" id="intro-skip">Kapat</button>
            <button type="button" class="btn-primary" id="intro-next">Devam</button>
        </div>
    </div>
</div>

<div id="quote-modal" class="overlay hidden">
    <form class="sheet" id="quote-form">
        <h2 style="margin-top:0">Fiyat teklifi</h2>
        <p class="hint">Bu bir hazır fiyat değildir. Tasarım atölyeye gider, size dönüş yapılır.</p>
        <div class="field"><label>Ad</label><input name="name" required maxlength="120"></div>
        <div class="field"><label>Telefon</label><input name="phone" required maxlength="40"></div>
        <div class="field"><label>Not</label><input name="note" maxlength="2000"></div>
        <div id="quote-msg" class="hint"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn" id="quote-close">Vazgeç</button>
            <button type="submit" class="btn-primary">Gönder</button>
        </div>
    </form>
</div>
<div id="toast" class="toast hidden"></div>

<script type="importmap">
{
  "imports": {
    "three": "https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js",
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"
  }
}
</script>
<script>
    window.WARDROBE = {
        quoteUrl: @json($quoteUrl),
        csrf: @json(csrf_token())
    };
</script>
<script type="module" src="{{ asset('js/wardrobe-studio.js') }}?v=3"></script>
</body>
</html>
