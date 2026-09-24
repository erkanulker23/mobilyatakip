<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'TV Ünitesi Tasarla',
        'metaDescription' => 'Sürükle-bırak ile profesyonel TV ünitesi tasarlayın. Kastamonu Entegre malzemeleriyle kaplama, ölçü, raf, kapak ve çıta ekleyin.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #e8e8e8;
            --panel: #ffffff;
            --ink: #1a1a1a;
            --muted: #6b7280;
            --line: #e5e7eb;
            --accent: #0058a3;
            --accent-hover: #004a8a;
            --price: #111827;
            --shadow: 0 8px 30px rgba(0,0,0,.08);
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; height: 100%; overflow: hidden;
            font-family: Montserrat, system-ui, sans-serif;
            background: var(--bg); color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        #app { display: grid; grid-template-columns: 1fr min(380px, 100%); height: 100%; }
        @media (max-width: 900px) {
            #app { grid-template-columns: 1fr; grid-template-rows: 1fr min(48vh, 420px); }
        }
        #viewport {
            position: relative; background: linear-gradient(180deg, #f3f3f3 0%, #dedede 100%);
            min-height: 0;
        }
        #canvas-host { position: absolute; inset: 0; }
        #canvas-host canvas { display: block; width: 100% !important; height: 100% !important; }

        .topbar {
            position: absolute; top: 0; left: 0; right: 0; z-index: 20;
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px; pointer-events: none;
        }
        .topbar > * { pointer-events: auto; }
        .top-left, .top-right { display: flex; align-items: center; gap: 8px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            border: 0; border-radius: 999px; padding: 10px 16px;
            font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
            background: #fff; color: var(--ink); box-shadow: var(--shadow);
            transition: transform .15s ease, background .15s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-icon { width: 40px; height: 40px; padding: 0; justify-content: center; border-radius: 50%; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); }
        .price-pill {
            background: #fff; border-radius: 999px; padding: 10px 14px;
            font-weight: 700; font-size: 14px; box-shadow: var(--shadow);
            display: inline-flex; align-items: center; gap: 8px;
        }

        .fab-stack {
            position: absolute; left: 16px; bottom: 72px; z-index: 20;
            display: flex; flex-direction: column; gap: 8px;
        }
        .fab {
            width: 44px; height: 44px; border-radius: 50%; border: 0;
            background: #fff; box-shadow: var(--shadow); cursor: pointer;
            display: grid; place-items: center; color: #374151;
        }
        .fab.active { background: var(--accent); color: #fff; }
        .selection-bar {
            position: absolute; left: 50%; bottom: 72px; transform: translateX(-50%);
            z-index: 20; display: flex; gap: 8px;
        }
        .selection-bar[hidden] { display: none !important; }
        .room-btn {
            position: absolute; left: 50%; bottom: 18px; transform: translateX(-50%);
            z-index: 20; background: #111; color: #fff; border: 0; border-radius: 999px;
            padding: 12px 18px; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
            box-shadow: var(--shadow);
        }
        .hint {
            position: absolute; left: 50%; top: 72px; transform: translateX(-50%);
            z-index: 15; background: rgba(17,17,17,.78); color: #fff;
            padding: 8px 14px; border-radius: 999px; font-size: 12px; font-weight: 500;
            pointer-events: none; opacity: 0; transition: opacity .25s ease;
        }
        .hint.show { opacity: 1; }

        #sidebar {
            background: var(--panel); border-left: 1px solid var(--line);
            display: flex; flex-direction: column; min-height: 0; z-index: 30;
        }
        .side-head {
            padding: 18px 18px 12px; border-bottom: 1px solid var(--line);
            display: flex; align-items: flex-start; gap: 10px;
        }
        .side-head h1 { margin: 0; font-size: 16px; font-weight: 700; line-height: 1.35; flex: 1; }
        .side-body { flex: 1; overflow: auto; padding: 8px 0 24px; }
        .menu-item {
            width: 100%; display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; border: 0; background: transparent; cursor: pointer;
            font: inherit; text-align: left; color: var(--ink);
            border-bottom: 1px solid #f3f4f6;
        }
        .menu-item:hover { background: #f9fafb; }
        .menu-item .ico {
            width: 40px; height: 40px; border-radius: 10px; background: #f3f4f6;
            display: grid; place-items: center; flex-shrink: 0;
        }
        .menu-item .label { flex: 1; font-size: 14px; font-weight: 600; }
        .menu-item .chev { color: #9ca3af; }

        .panel-section { padding: 8px 16px 16px; }
        .section-title {
            font-size: 11px; font-weight: 700; letter-spacing: .06em;
            text-transform: uppercase; color: var(--muted); margin: 12px 2px 8px;
        }
        .product-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .product-card {
            border: 1px solid var(--line); border-radius: 12px; overflow: hidden;
            background: #fff; cursor: pointer; text-align: left; padding: 0;
            font: inherit; transition: border-color .15s, box-shadow .15s;
        }
        .product-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
        .product-card .thumb {
            aspect-ratio: 1.4; background: #f3f4f6; display: grid; place-items: center;
            overflow: hidden;
        }
        .product-card .thumb img, .product-card .thumb canvas, .product-card .thumb .swatch {
            width: 100%; height: 100%; object-fit: cover;
        }
        .product-card .meta { padding: 10px; }
        .product-card .meta strong { display: block; font-size: 12px; font-weight: 700; }
        .product-card .meta span { display: block; margin-top: 2px; font-size: 11px; color: var(--muted); }

        .field { margin-bottom: 12px; }
        .field label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; }
        .field input, .field select {
            width: 100%; border: 1px solid var(--line); border-radius: 10px;
            padding: 10px 12px; font: inherit; font-size: 13px; background: #fff;
        }
        .dim-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip {
            border: 1px solid var(--line); background: #fff; border-radius: 999px;
            padding: 8px 12px; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer;
        }
        .chip.active { border-color: var(--accent); color: var(--accent); background: #eff6ff; }
        .mat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; max-height: 280px; overflow: auto; }
        .mat-swatch {
            aspect-ratio: 1; border-radius: 10px; border: 2px solid transparent;
            overflow: hidden; cursor: pointer; padding: 0; background: #eee;
        }
        .mat-swatch img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .mat-swatch.active { border-color: var(--accent); }
        .mat-swatch span {
            display: block; font-size: 9px; padding: 3px; background: rgba(255,255,255,.9);
            text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .info-box {
            margin: 12px 16px; padding: 12px; border-radius: 12px; background: #f8fafc;
            border: 1px solid var(--line); font-size: 12px; color: #475569; line-height: 1.45;
        }
        .danger {
            width: calc(100% - 32px); margin: 8px 16px 0; padding: 11px;
            border-radius: 12px; border: 1px solid #fecaca; background: #fef2f2;
            color: #b91c1c; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .tabs {
            display: flex; gap: 4px; padding: 0 16px 8px;
        }
        .tab {
            flex: 1; border: 1px solid var(--line); background: #fff; border-radius: 10px;
            padding: 8px; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer;
        }
        .tab.active { background: #111; color: #fff; border-color: #111; }

        #summary-modal {
            position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,.45);
            display: none; align-items: center; justify-content: center; padding: 16px;
        }
        #summary-modal.open { display: flex; }
        .modal-card {
            width: min(480px, 100%); max-height: 85vh; overflow: auto;
            background: #fff; border-radius: 18px; padding: 22px; box-shadow: var(--shadow);
        }
        .modal-card h2 { margin: 0 0 8px; font-size: 18px; }
        .modal-card table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 12px; }
        .modal-card td, .modal-card th { padding: 8px 0; border-bottom: 1px solid var(--line); text-align: left; }
        .modal-actions { display: flex; gap: 8px; margin-top: 16px; }
        .empty-state {
            padding: 28px 20px; text-align: center; color: var(--muted); font-size: 13px; line-height: 1.5;
        }
    </style>
</head>
<body>
<div id="app"
     data-materials-url="{{ $materialsUrl }}"
     data-brand="{{ \App\Support\CompanyBranding::siteName($company) }}"
     data-catalog-url="{{ url('/katalog') }}">
    <div id="viewport">
        <div id="canvas-host"></div>
        <div class="topbar">
            <div class="top-left">
                <a class="btn btn-icon" href="{{ url('/katalog') }}" title="Katalog" aria-label="Menü">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </a>
                <button type="button" class="btn" id="btn-save">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    Kaydet
                </button>
            </div>
            <div class="top-right">
                <div class="price-pill" id="price-display">0 ₺</div>
                <button type="button" class="btn btn-primary" id="btn-summary">Özet →</button>
            </div>
        </div>
        <div class="hint" id="hint">İskelet ekleyerek başlayın</div>
        <div class="fab-stack">
            <button type="button" class="fab active" id="fab-tv" title="TV göster/gizle" aria-label="TV">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </button>
            <button type="button" class="fab" id="fab-night" title="Gece ışığı" aria-label="Gece">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 14.5A8.5 8.5 0 1111.5 3 7 7 0 0021 14.5z"/></svg>
            </button>
            <button type="button" class="fab" id="fab-grid" title="Izgara" aria-label="Izgara">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg>
            </button>
            <button type="button" class="fab" id="fab-measure" title="Ölçüler" aria-label="Ölçü">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20L20 4M8 20v-2M12 20v-3M16 20v-2M4 16h2M4 12h3M4 8h2"/></svg>
            </button>
            <button type="button" class="fab" id="fab-undo" title="Geri al" aria-label="Geri">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 7H5v4"/><path d="M5 11a7 7 0 117 7"/></svg>
            </button>
            <button type="button" class="fab" id="fab-redo" title="İleri al" aria-label="İleri">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 7h4v4"/><path d="M19 11a7 7 0 11-7 7"/></svg>
            </button>
        </div>
        <div class="selection-bar" id="selection-bar" hidden>
            <button type="button" class="fab" id="sel-delete" title="Sil" aria-label="Sil">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
            </button>
            <button type="button" class="fab" id="sel-dup" title="Çoğalt" aria-label="Çoğalt">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M4 16V6a2 2 0 012-2h10"/></svg>
            </button>
        </div>
        <button type="button" class="room-btn" id="btn-room">Odayı özelleştirin</button>
    </div>

    <aside id="sidebar">
        <div class="side-head">
            <button type="button" class="btn btn-icon" id="btn-back" style="display:none" aria-label="Geri">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <h1 id="side-title">Kendi TV ünitenizi oluşturun</h1>
        </div>
        <div class="side-body" id="side-body"></div>
    </aside>
</div>

<div id="summary-modal" role="dialog" aria-modal="true">
    <div class="modal-card">
        <h2>Tasarım özeti</h2>
        <p style="margin:0;color:#6b7280;font-size:13px;">Parça listesi ve tahmini tutar. Malzemeler Kastamonu Entegre kataloğundan seçilir.</p>
        <div id="summary-content"></div>
        <div class="modal-actions">
            <button type="button" class="btn" id="btn-close-summary">Kapat</button>
            <button type="button" class="btn btn-primary" id="btn-copy-summary">Listeyi kopyala</button>
        </div>
    </div>
</div>

<script type="importmap">
{
  "imports": {
    "three": "https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js",
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"
  }
}
</script>
<script type="module" src="{{ asset('js/tv-configurator.js') }}?v=2"></script>
</body>
</html>
