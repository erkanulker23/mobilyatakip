<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => '90 cm çift kapılı dolap',
        'metaDescription' => 'Meemare 90 cm çift kapılı dolap modeli. Kapak, aksesuar ve malzeme canlı değişir.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #1c1c1c; --muted: #6d6a64; --line: #e6e2da; --bg: #f4f3f0; --panel: #fffcf8; --accent: #1f6b4a; }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; font-family: Montserrat, system-ui, sans-serif; color: var(--ink); background: var(--bg); }
        button { font-family: inherit; cursor: pointer; }
        #app { display: grid; grid-template-columns: 1fr 320px; grid-template-rows: 56px 1fr; height: 100%; }
        header { grid-column: 1 / -1; display: flex; align-items: center; gap: 14px; padding: 0 16px; background: var(--panel); border-bottom: 1px solid var(--line); }
        .brand { text-decoration: none; color: inherit; line-height: 1.15; }
        .brand strong { font-size: 13px; letter-spacing: .08em; }
        .brand span, .sub { color: var(--muted); font-size: 12px; }
        #viewport { position: relative; min-width: 0; }
        #viewport canvas { display: block; width: 100%; height: 100%; }
        aside { background: var(--panel); border-left: 1px solid var(--line); overflow: auto; padding: 16px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .row { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
        .btn, .swatch { border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 8px 10px; font-size: 13px; font-weight: 600; }
        .btn.is-on { background: var(--accent); color: #fff; border-color: var(--accent); }
        .field { margin: 14px 0; }
        .field p { margin: 0 0 6px; font-size: 12px; color: var(--muted); }
        .swatch { display: inline-flex; align-items: center; gap: 6px; }
        .swatch i { width: 14px; height: 14px; border-radius: 4px; display: inline-block; border: 1px solid rgba(0,0,0,.12); }
        .swatch.is-on { border-color: var(--accent); }
        #findings { margin: 8px 0 0; padding-left: 18px; font-size: 12px; line-height: 1.45; color: var(--ink); }
        #findings li { margin: 4px 0; }
        .warn { font-size: 12px; line-height: 1.45; color: #6a3b12; background: #f8f1e4; border-radius: 8px; padding: 10px; }
        @media (max-width: 860px) {
            #app { grid-template-columns: 1fr; grid-template-rows: 56px 58vh auto; }
            aside { border-left: 0; border-top: 1px solid var(--line); }
        }
    </style>
</head>
<body>
<div id="app">
    <header>
        <a class="brand" href="{{ route('wardrobe.index') }}"><strong>MEEMARE</strong><span>Dolap modeli</span></a>
        <div class="sub">90 cm çift kapılı · 250 cm · ön yüz açık gövde</div>
    </header>
    <div id="viewport"></div>
    <aside>
        <h1>CIFT_V1</h1>
        <p class="sub">İlk model. Diğer 24 dolap bu örnek oturmadan açılmıyor.</p>
        <div class="row">
            <button type="button" class="btn" id="btn-doors">Kapakları aç</button>
            <button type="button" class="btn is-on" id="btn-accessories">Aksesuarları gizle</button>
            <button type="button" class="btn" id="btn-spin">360° döndür</button>
        </div>
        <div class="field" data-role="body"><p>Gövde</p></div>
        <div class="field" data-role="door"><p>Kapak</p></div>
        <div class="field" data-role="drawer"><p>Çekmece</p></div>
        <div class="field" data-role="back"><p>Arkalık</p></div>
        <div class="field" data-role="shelf"><p>Raf</p></div>
        <p class="warn">Kıyafet, ayakkabı ve kutu geometrileri kutu prototiptir. Işık bunu düzeltmez. Değiştirilecek modeller sağdaki listede.</p>
        <ul id="findings"></ul>
    </aside>
</div>
<script type="importmap">
{
  "imports": {
    "three": "https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js",
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"
  }
}
</script>
<script>
    window.MODULE_VIEW = { modelUrl: @json($modelUrl) };
</script>
<script type="module" src="{{ asset('js/module-viewer.js') }}?v=2"></script>
</body>
</html>
