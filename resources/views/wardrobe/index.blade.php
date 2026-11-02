<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => '3D Dolap Tasarla',
        'metaDescription' => 'Meemare hazır dolap modüllerini odanın duvarına dizin. Kapak, raf ve renk canlı değişir.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1c1c1c;
            --muted: #6d6a64;
            --line: #e6e2da;
            --bg: #f4f3f0;
            --panel: #fffcf8;
            --accent: #1f6b4a;
            --danger: #9f2d2d;
            --shadow: 0 12px 32px rgba(28, 28, 28, .08);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; height: 100dvh; overflow: hidden; font-family: Montserrat, system-ui, sans-serif; color: var(--ink); background: var(--bg); }
        button, input, select { font-family: inherit; }
        button { cursor: pointer; }
        #studio { display: grid; grid-template-rows: auto minmax(0, 1fr) auto; height: 100dvh; min-height: 0; padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom); }
        header.bar, footer.bar, .series {
            display: flex; align-items: center; gap: 8px; padding: 0 14px; background: var(--panel);
        }
        header.bar { border-bottom: 1px solid var(--line); }
        .series { display: none; }
        footer.bar { border-top: 1px solid var(--line); justify-content: space-between; }
        .brand { display: flex; flex-direction: column; line-height: 1.1; text-decoration: none; color: inherit; }
        .brand strong { font-size: 13px; letter-spacing: .08em; }
        .brand span { font-size: 11px; color: var(--muted); }
        .name-input { border: 0; background: transparent; font-weight: 600; font-size: 14px; width: 160px; }
        .name-input:focus { outline: none; }
        .grow { flex: 1; }
        .btn, .btn-primary { border-radius: 8px; border: 1px solid var(--line); background: #fff; padding: 7px 10px; font-size: 12px; font-weight: 600; }
        .btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn:disabled { opacity: .4; cursor: default; }
        .series button { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .series button.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        .seg { display: flex; gap: 6px; flex-wrap: wrap; }
        .seg button { border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 7px 10px; font-size: 12px; font-weight: 600; }
        .seg button.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        .workspace { display: block; position: relative; min-height: 0; }
        .stage { position: absolute; inset: 0; min-width: 0; min-height: 0; background: #eceae6; }
        aside { background: var(--panel); min-height: 0; display: flex; flex-direction: column; }
        @media (min-width: 901px) {
            aside.left, aside.right {
                position: absolute; top: 0; bottom: 0; width: min(320px, 34vw); z-index: 6;
                transition: transform .2s ease; box-shadow: var(--shadow);
            }
            aside.left { left: 0; border-right: 1px solid var(--line); transform: translateX(-105%); }
            aside.right { right: 0; border-left: 1px solid var(--line); transform: translateX(105%); }
            body.show-library aside.left, body.show-custom aside.right { transform: none; }
            aside .sheet-close { display: block; }
        }
        .side-head { padding: 12px 14px 0; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        .library-search { padding: 10px 12px 0; }
        .library-search input { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; background: #fff; font-size: 13px; }
        .side-body { padding: 12px; overflow: auto; min-height: 0; flex: 1; }
        .side-foot { border-top: 1px solid var(--line); padding: 12px; flex: 0 0 auto; }
        .side-foot .btn { width: 100%; }
        .catalog { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .card { display: flex; flex-direction: column; border: 1px solid var(--line); border-radius: 12px; background: #fff; padding: 6px; text-align: left; cursor: grab; touch-action: none; }
        .card-add { align-self: flex-start; margin-top: 6px; border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 700; cursor: pointer; }
        .card:hover { border-color: #c8c2b6; }
        .card img { width: 100%; height: 168px; object-fit: contain; background: #fff; border-radius: 8px; }
        .card-zoom { position: fixed; z-index: 40; width: min(440px, 48vw); padding: 10px; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 18px 48px rgba(28, 28, 28, .18); pointer-events: none; }
        .card-zoom img { display: block; width: 100%; height: min(72vh, 760px); object-fit: contain; background: #f7f5f2; border-radius: 12px; }
        .card-zoom strong { display: block; margin-top: 8px; font-size: 14px; }
        .card-zoom span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }
        .catalog-switch { display: flex; gap: 6px; margin: 0 0 8px; }
        .catalog-switch button { flex: 1; border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 7px 6px; font-size: 12px; font-weight: 700; }
        .catalog-switch button.is-on { background: #149152; color: #fff; border-color: #149152; }
        .catalog-kicker { margin: 12px 0 6px; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        .card.is-broken { outline: 2px solid #c0392b; }
        .card strong { display: block; margin-top: 6px; font-size: 12px; font-weight: 600; }
        .card span, .set-card span { color: var(--muted); font-size: 11px; }
        .sets { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
        .set-card { display: flex; align-items: center; gap: 10px; text-align: left; border: 1px solid var(--line); border-radius: 12px; background: #fff; padding: 8px; }
        .set-card img { width: 64px; height: 64px; object-fit: contain; background: #f7f6f4; border-radius: 8px; flex: none; }
        .set-card strong { display: block; font-size: 13px; }
        .parts { margin: 0 0 12px; padding-left: 18px; font-size: 12px; }
        .parts li { margin: 0 0 8px; }
        .parts li.is-on { background: #e5f6ec; border: 2px solid #149152; border-radius: 8px; padding: 6px 8px; list-style-position: inside; }
        #viewport { position: absolute; inset: 0; }
        #viewport canvas { display: block; }
        #view-bar { position: absolute; left: 12px; bottom: 12px; z-index: 4; display: flex; flex-wrap: wrap; gap: 6px; max-width: min(420px, calc(100% - 24px)); }
        #view-bar button, #plan-switch button { border: 1px solid var(--line); background: rgba(255,252,248,.94); border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; }
        #plan-switch { position: absolute; top: 12px; right: 12px; z-index: 5; display: flex; gap: 6px; }
        #plan-switch button.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        #plan-board { position: absolute; inset: 0; z-index: 3; background: #f3f1ec; }
        #plan-board.hidden { display: none; }
        #plan-canvas { width: 100%; height: 100%; display: block; touch-action: none; }
        #plan-tools { position: absolute; left: 12px; top: 12px; display: flex; gap: 6px; }
        #plan-tools button { border: 1px solid var(--line); background: rgba(255,252,248,.94); border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; }
        #plan-tools button.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        body.plan-2d #view-bar, body.plan-2d #selection-ui, body.plan-2d #height-handle { display: none; }
        #measure-layer { position: absolute; inset: 0; z-index: 2; pointer-events: none; overflow: hidden; }
        .measure { position: absolute; transform: translate(-50%, -50%); background: #1f6b4a; color: #fff; font-size: 11px; font-weight: 700; line-height: 1.2; padding: 2px 6px; border-radius: 999px; white-space: nowrap; }
        .btn.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        #selection-ui, #height-handle { position: absolute; z-index: 3; }
        #selection-ui { display: flex; flex-direction: column; align-items: center; gap: 6px; transform: translate(-50%, 10px); pointer-events: none; }
        #selection-ui button { pointer-events: auto; }
        #selection-ui .selection-actions { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; max-width: 280px; }
        #selection-name { background: #149152; color: #fff; font-size: 11px; font-weight: 600; line-height: 1.2; padding: 3px 8px; border-radius: 999px; white-space: nowrap; pointer-events: auto; cursor: grab; touch-action: none; }
        #selection-ui button { background: #2b2b2b; color: #fff; border: 0; border-radius: 7px; padding: 6px 9px; font-size: 12px; font-weight: 600; }
        #selection-ui button.is-on { background: #149152; }
        #hold-select { position: absolute; left: 50%; bottom: 18px; transform: translateX(-50%); z-index: 4; background: #1f6b4a; color: #fff; border-radius: 999px; padding: 8px 14px; font-size: 13px; font-weight: 600; pointer-events: none; }
        .picked { display: inline-block; margin: 0 0 6px; background: #149152; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; padding: 3px 8px; border-radius: 999px; }
        #height-handle {
            width: 32px; height: 32px; border-radius: 50%; border: 1px solid #d5d0c6; background: #fff;
            transform: translate(-50%, -50%); font-size: 16px; box-shadow: var(--shadow);
        }
        .warn { position: absolute; left: 12px; right: 12px; bottom: 12px; background: #fff6f4; color: var(--danger); border: 1px solid #f0c8c4; border-radius: 10px; padding: 8px 10px; font-size: 12px; z-index: 2; }
        .field { margin-bottom: 12px; }
        .field label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 4px; }
        .field-key { margin-bottom: 14px; padding: 10px; background: #fff; border: 1px solid var(--line); border-radius: 12px; }
        .field-key > label { font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
        .field input, .field select { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; background: #fff; font-size: 13px; }
        .swatches { display: flex; flex-wrap: wrap; gap: 6px; }
        .swatch { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #d5d0c8; }
        .field-key .swatch { width: 36px; height: 36px; }
        .swatch.is-on { box-shadow: 0 0 0 2px var(--accent); }
        .decors { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; max-height: 168px; overflow: auto; }
        .decor-kicker { grid-column: 1 / -1; margin: 4px 0 0; font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--muted); }
        .decor { display: flex; gap: 6px; align-items: center; border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 4px; text-align: left; }
        .decor.is-on { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
        .decor img { width: 28px; height: 28px; object-fit: cover; border-radius: 4px; background: #eee; }
        .decor b { display: block; font-size: 10px; font-weight: 600; line-height: 1.2; }
        .decor span { display: block; font-size: 10px; color: var(--muted); }
        .hint { font-size: 12px; color: var(--muted); margin: 0 0 8px; }
        .stat { font-size: 12px; color: var(--muted); }
        .stat b { color: var(--ink); }
        h2 { margin: 0 0 8px; font-size: 16px; }
        .overlay { position: fixed; inset: 0; background: rgba(18, 24, 21, .55); z-index: 80; display: flex; align-items: center; justify-content: center; padding: 18px; }
        #config-modal .sheet { width: min(680px, 100%); }
        .sheet { width: min(520px, 100%); max-height: min(86vh, 720px); overflow: auto; background: var(--panel); border-radius: 16px; padding: 22px; box-shadow: var(--shadow); }
        .choice { display: block; width: 100%; text-align: left; border: 1px solid var(--line); background: #fff; border-radius: 12px; padding: 12px; margin-bottom: 8px; }
        .choice span { display: block; color: var(--muted); font-size: 12px; margin-top: 2px; }
        .hidden { display: none !important; }
        .toast { position: fixed; bottom: 64px; left: 50%; transform: translateX(-50%); background: var(--ink); color: #fff; padding: 10px 14px; border-radius: 999px; font-size: 12px; z-index: 50; }
        .check { display: flex; align-items: center; gap: 8px; margin: 0 0 12px; font-size: 13px; font-weight: 600; }
        .check input { width: auto; }
        .mobile-nav { display: none; }
        .sheet-close, #project-sheet { display: none; }
        @media (max-width: 900px) {
            header.bar { min-height: 52px; }
            header.bar .btn:not(#btn-save), header.bar .extra { display: none; }
            .workspace { display: flex; min-height: 0; }
            .stage { position: relative; inset: auto; flex: 1 1 auto; min-height: 0; width: 100%; }
            #viewport { min-height: 0; }
            aside {
                position: fixed; left: 0; right: 0; bottom: calc(72px + env(safe-area-inset-bottom));
                height: min(42dvh, 380px); z-index: 20; transform: translateY(120%);
                transition: transform .2s ease; border-radius: 16px 16px 0 0; box-shadow: var(--shadow);
            }
            aside.is-open { transform: none; }
            .sheet-close { display: block; margin: 8px 12px 0 auto; border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 700; }
            #project-sheet {
                display: none; position: fixed; left: 0; right: 0; bottom: calc(72px + env(safe-area-inset-bottom));
                z-index: 20; background: var(--panel); border-radius: 16px 16px 0 0; box-shadow: var(--shadow); padding: 12px;
            }
            #project-sheet.is-open { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            .mobile-nav { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 6px; width: 100%; }
            .mobile-nav .btn { padding: 8px 4px; font-size: 11px; }
            footer.bar { display: block; padding: 8px 8px calc(8px + env(safe-area-inset-bottom)); }
            footer.bar .stat, footer.bar #btn-quote { display: none; }
            #view-bar { top: auto; bottom: calc(58px + env(safe-area-inset-bottom)); left: 8px; right: 8px; max-width: none; flex-wrap: nowrap; overflow-x: auto; gap: 4px; scrollbar-width: none; -webkit-mask-image: linear-gradient(90deg, #000 calc(100% - 22px), transparent); mask-image: linear-gradient(90deg, #000 calc(100% - 22px), transparent); }
            #view-bar::-webkit-scrollbar { display: none; }
            #view-bar button { padding: 6px 8px; font-size: 11px; }
            #plan-switch { top: auto; left: auto; right: 8px; bottom: calc(116px + env(safe-area-inset-bottom)); }
            body.plan-2d #plan-switch { bottom: calc(58px + env(safe-area-inset-bottom)); }
            .name-input { width: min(42vw, 180px); }
            body[data-sheet="material"] #inspector-body > :not([data-picker]) { display: none; }
            body[data-sheet="doors"] #inspector-body > :not(:has([data-door], [data-swing], #mod-open)) { display: none; }
            body[data-sheet="measure"] #inspector-body > :not(:has(#wall-left, #wall-front, #wall-right, #room-h, #cab-h, #cab-d, #mod-w, #mod-h)) { display: none; }
        }
    </style>
</head>
<body class="show-library">
<div id="studio">
    <header class="bar">
        <a href="{{ route('login') }}" class="brand">
            <strong>MEEMARE</strong>
            <span>Dolap planı</span>
        </a>
        <input id="project-name" class="name-input" maxlength="80" aria-label="Proje adı" value="Dolabım">
        <div class="grow"></div>
        <button type="button" class="btn" id="btn-new">Yeni proje</button>
        <button type="button" class="btn" id="btn-undo" disabled>Geri</button>
        <button type="button" class="btn" id="btn-redo" disabled>İleri</button>
        <button type="button" class="btn" id="btn-saved">Kayıtlar</button>
        <button type="button" class="btn" id="btn-save">Kaydet</button>
        <button type="button" class="btn" id="btn-share">Paylaş</button>
        <a class="btn extra" href="{{ url('/plan') }}">Oda planı</a>
        <button type="button" class="btn" id="btn-measures">Ölçüler</button>
        <button type="button" class="btn is-on" id="btn-lights">Işıkları kapat</button>
        <button type="button" class="btn" id="btn-shot">Render al</button>
    </header>
    <div class="workspace">
        <aside class="left" id="library">
            <button type="button" class="sheet-close" id="close-library">Kapat</button>
            <div class="side-head">Dolap modülleri</div>
            <div class="library-search"><input id="module-search" type="search" placeholder="Modül ara" autocomplete="off"></div>
            <div class="side-body" id="library-body"></div>
        </aside>
        <div id="card-zoom" class="card-zoom hidden" aria-hidden="true">
            <img alt="">
            <strong></strong>
            <span></span>
        </div>
        <section class="stage">
            <div id="viewport"></div>
            <div id="plan-board" class="hidden">
                <canvas id="plan-canvas"></canvas>
                <div id="plan-tools">
                    <button type="button" data-plan-tool="move" class="is-on">Taşı</button>
                    <button type="button" data-plan-tool="door">Kapı</button>
                    <button type="button" data-plan-tool="window">Pencere</button>
                </div>
            </div>
            <div id="plan-switch">
                <button type="button" id="btn-modules">Modüller</button>
                <button type="button" data-plan="2d">2B</button>
                <button type="button" data-plan="3d" class="is-on">3B</button>
                <button type="button" id="btn-guides" class="is-on">Kılavuz</button>
            </div>
            <div id="view-bar">
                <button type="button" data-view="front">Ön</button>
                <button type="button" data-view="left">Sol</button>
                <button type="button" data-view="right">Sağ</button>
                <button type="button" data-view="left45">Sol 45</button>
                <button type="button" data-view="right45">Sağ 45</button>
                <button type="button" data-view="top">Üst</button>
                <button type="button" data-view="perspective">Perspektif</button>
                <button type="button" data-view="shelf">Raf</button>
                <button type="button" data-view="drawer">Çekmece</button>
                <button type="button" data-view="grain">Ahşap</button>
                <button type="button" data-view="glass">Cam</button>
            </div>
            <div id="measure-layer" class="hidden"></div>
            <div id="selection-ui" class="hidden">
                <div id="selection-name"></div>
                <div class="selection-actions">
                    <button type="button" id="act-config">Özelleştir</button>
                    <button type="button" id="act-forward">Öne</button>
                    <button type="button" id="act-back">Arkaya</button>
                    <button type="button" id="act-clone">Klonla</button>
                    <button type="button" id="act-delete">Sil</button>
                </div>
            </div>
            <button type="button" id="height-handle" class="hidden" aria-label="Yükseklik">↕</button>
            <div id="hold-select" class="hidden"></div>
            <div id="warn" class="warn hidden"></div>
        </section>
        <aside class="right" id="inspector">
            <button type="button" class="sheet-close" id="close-inspector">Kapat</button>
            <div class="side-body" id="inspector-body"></div>
            <div class="side-foot">
                <button type="button" class="btn" id="btn-production">Üretim listesi</button>
            </div>
        </aside>
    </div>
    <footer class="bar">
        <div class="stat" id="summary"></div>
        <div class="mobile-nav">
            <button type="button" class="btn" id="open-library">Modül Ekle</button>
            <button type="button" class="btn" id="open-material">Malzeme</button>
            <button type="button" class="btn" id="open-doors">Kapaklar</button>
            <button type="button" class="btn" id="open-measures">Ölçüler</button>
            <button type="button" class="btn" id="open-project">Proje</button>
        </div>
        <button type="button" class="btn-primary" id="btn-quote">Fiyat teklifi al</button>
    </footer>
</div>
<div id="project-sheet">
    <button type="button" class="btn" id="sheet-new">Yeni proje</button>
    <button type="button" class="btn" id="sheet-undo">Geri</button>
    <button type="button" class="btn" id="sheet-redo">İleri</button>
    <button type="button" class="btn" id="sheet-saved">Kayıtlar</button>
    <button type="button" class="btn" id="sheet-share">Paylaş</button>
    <button type="button" class="btn" id="sheet-quote">Fiyat teklifi</button>
    <button type="button" class="btn" id="sheet-shot">Render al</button>
    <button type="button" class="sheet-close" id="close-project">Kapat</button>
</div>

<div id="customer-modal" class="overlay hidden">
    <form class="sheet" id="customer-form">
        <h2>Kayıtlı projeler</h2>
        <p class="hint">Daha önce kaydedilen projeler. Birine basınca açılır.</p>
        <div id="resume-list"></div>
        <div class="field" style="margin-top:10px"><p class="hint">Yalnızca bu hesaba ait projeler listelenir.</p></div>
        <button type="button" class="btn" id="resume-search">Listeyi yenile</button>
        <h2 style="margin-top:18px">Yeni proje</h2>
        <p class="hint">Dolabı tasarlamadan önce müşteri kayda geçer.</p>
        <div class="field"><label>Ad</label><input name="customerName" required maxlength="120" autocomplete="name"></div>
        <div class="field"><label>Proje adı</label><input name="projectName" required maxlength="80" value="Dolabım"></div>
        <div class="field"><label>Sol duvar genişliği (cm)</label><input name="leftWall" type="number" required min="80" max="800" value="300" inputmode="numeric"></div>
        <div class="field"><label>Ön duvar genişliği (cm)</label><input name="frontWall" type="number" required min="80" max="800" value="300" inputmode="numeric"></div>
        <div class="field"><label>Sağ duvar genişliği (cm)</label><input name="rightWall" type="number" required min="80" max="800" value="300" inputmode="numeric"></div>
        <div class="field"><label>Oda yüksekliği (cm)</label><input name="roomHeight" type="number" required min="160" max="320" value="320" inputmode="numeric"></div>
        <p class="hint">Standart oda yüksekliği 320 cm. 160 ile 320 arasında değiştirilebilir. Dolaplar duvara dizilir.</p>
        <div class="field"><label>Not</label><input name="note" maxlength="2000"></div>
        <div id="customer-msg" class="hint"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="submit" class="btn-primary">Dolabı tasarla</button>
        </div>
    </form>
</div>

<div id="saved-modal" class="overlay hidden">
    <div class="sheet">
        <h2>Kayıtlı projeler</h2>
        <div class="field"><label>Telefon</label><input id="saved-phone" maxlength="40" inputmode="tel"></div>
        <button type="button" class="btn" id="saved-search">Ara</button>
        <div id="saved-list" style="margin-top:8px"></div>
        <div style="display:flex;justify-content:flex-end;margin-top:8px">
            <button type="button" class="btn" id="saved-close">Kapat</button>
        </div>
    </div>
</div>

<div id="config-modal" class="overlay hidden">
    <div class="sheet">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px">
            <h2 id="config-title" style="margin:0">Yapılandır</h2>
            <button type="button" class="btn" id="config-close">Kapat</button>
        </div>
        <div id="config-body"></div>
    </div>
</div>

<div id="production-modal" class="overlay hidden">
    <div class="sheet">
        <h2>Üretim listesi</h2>
        <p class="hint" id="production-meta"></p>
        <ol class="parts" id="production"></ol>
        <div style="display:flex;justify-content:flex-end">
            <button type="button" class="btn" id="production-close">Kapat</button>
        </div>
    </div>
</div>

<div id="quote-modal" class="overlay hidden">
    <form class="sheet" id="quote-form">
        <h2>Fiyat teklifi</h2>
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
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/",
    "three/examples/jsm/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/",
    "three-mesh-bvh": "https://cdn.jsdelivr.net/npm/three-mesh-bvh@0.7.8/src/index.js",
    "three-gpu-pathtracer": "https://cdn.jsdelivr.net/npm/three-gpu-pathtracer@0.0.23/src/index.js"
  }
}
</script>
<script>
    window.WARDROBE = {
        quoteUrl: @json($quoteUrl),
        materialsUrl: @json($materialsUrl),
        projectStoreUrl: @json($projectStoreUrl),
        projectIndexUrl: @json($projectIndexUrl),
        projectBase: @json(url('/dolap')),
        modelsUrl: @json(asset('models/wardrobe')),
        photosUrl: @json(asset('images/wardrobe')),
        csrf: @json(csrf_token())
    };
</script>
<script type="module" src="{{ asset('js/wardrobe-studio.js') }}?v=214"></script>
</body>
</html>
