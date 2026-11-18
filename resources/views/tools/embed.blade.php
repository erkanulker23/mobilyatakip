<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => $tool['title'],
        'metaDescription' => $tool['lead'],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    @include('partials.meemare-shell-styles')
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; font-family: Montserrat, system-ui, sans-serif; background: #f3f1ec; color: #17211c; }
        .shell { display: grid; grid-template-rows: auto auto auto auto 1fr; height: 100%; min-height: 100dvh; }
        .bar a.source { color: #1f6b4a; text-decoration: none; font-size: 12px; font-weight: 600; white-space: nowrap; }
        iframe { width: 100%; height: 100%; border: 0; background: #fff; min-height: 0; }
        @media (max-width: 640px) { .bar .lead { display: none; } }
    </style>
</head>
<body>
<div class="shell">
    <div class="bar meemare-tool-bar">
        <strong>{{ $tool['title'] }}</strong>
        <span class="lead">{{ $tool['lead'] }}</span>
        <a class="source" href="{{ $tool['source'] }}" target="_blank" rel="noopener">{{ $tool['credit'] }}</a>
        <a class="source" href="{{ $tool['src'] }}" target="_blank" rel="noopener">Yeni sekme</a>
    </div>
    <div class="meemare-nav-row">@include('partials.meemare-apps-nav', ['activeApp' => $tool['portal']])</div>
    @include('partials.meemare-workflow', ['workflowStep' => $tool['portal']])
    @if($tool['portal'] === 'plan')
    <form class="meemare-handoff-row" id="plan-handoff">
        <p style="margin:0;flex:1">Bu sayfa bağımsızdır. Duvarları çizin; ölçüleri kaydedin. Açık dolap projenize döndüğünüzde otomatik uygulanır.</p>
        <label style="display:flex;flex-direction:column;gap:3px;font-weight:600;color:#3d5247">OpenPlan JSON
            <input id="openplan-json-file" type="file" accept="application/json,.json" style="width:auto;font-size:11px">
        </label>
        <label>Ön (cm)<input type="number" name="onduvar" min="80" max="800" value="320" required></label>
        <label>Sol (cm)<input type="number" name="solduvar" min="80" max="800" value="280"></label>
        <label>Sağ (cm)<input type="number" name="sagduvar" min="80" max="800" value="280"></label>
        <button type="submit">Ölçüleri kaydet</button>
    </form>
    @endif
    @if($tool['portal'] === 'uretim')
    <div class="meemare-handoff-row">
        <p style="margin:0;flex:1;color:#5e6a64;font-size:12px">Tek dolap gövdesi, DXF ve kapak detayı burada. Oda ve Y26 modül dizimi için <strong>3D tasarla</strong>.</p>
        <a class="btn-apply" href="{{ route('wardrobe.design') }}" style="text-decoration:none;display:inline-block">Modül stüdyosu</a>
    </div>
    @endif
    <iframe src="{{ $tool['src'] }}" title="{{ $tool['title'] }}" referrerpolicy="no-referrer-when-downgrade" allow="fullscreen; clipboard-read; clipboard-write"></iframe>
</div>
@if($tool['portal'] === 'plan')
    <script src="{{ asset('js/meemare-plan-bridge.js') }}?v=6" defer></script>
    <script src="{{ asset('js/openplan-import.js') }}?v=2" defer></script>
@endif
@if($tool['portal'] === 'uretim')
    <script src="{{ asset('js/meemare-cabinet-bridge.js') }}?v=2" defer></script>
@endif
</body>
</html>