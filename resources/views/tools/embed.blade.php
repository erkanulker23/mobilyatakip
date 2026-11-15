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
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; font-family: Montserrat, system-ui, sans-serif; background: #f3f1ec; color: #17211c; }
        .shell { display: grid; grid-template-rows: auto auto 1fr; height: 100%; min-height: 100dvh; }
        .bar {
            display: flex; align-items: center; gap: 12px; padding: 10px 14px;
            background: #fffcf8; border-bottom: 1px solid #e4e1da; flex-wrap: wrap;
        }
        .bar strong { font-size: 15px; }
        .bar .lead { color: #5e6a64; font-size: 12px; flex: 1 1 200px; min-width: 0; }
        .bar a.source { color: #1f6b4a; text-decoration: none; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .nav-row { padding: 8px 14px; background: #f7f5f1; border-bottom: 1px solid #e4e1da; overflow-x: auto; }
        .handoff {
            display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; padding: 8px 14px;
            background: #eef6f1; border-bottom: 1px solid #d4e8dc; font-size: 12px;
        }
        .handoff label { display: flex; flex-direction: column; gap: 3px; color: #3d5247; font-weight: 600; }
        .handoff input { width: 72px; border: 1px solid #cfe0d6; border-radius: 8px; padding: 6px 8px; font-size: 12px; }
        .handoff button {
            border: 0; background: #1f6b4a; color: #fff; border-radius: 999px; padding: 7px 14px;
            font-weight: 700; cursor: pointer; font-family: inherit; font-size: 12px;
        }
        .handoff p { margin: 0; color: #5e6a64; flex: 1 1 220px; }
        iframe { width: 100%; height: 100%; border: 0; background: #fff; min-height: 0; }
        @media (max-width: 640px) { .bar .lead { display: none; } }
    </style>
</head>
<body>
<div class="shell">
    <div class="bar">
        <strong>{{ $tool['title'] }}</strong>
        <span class="lead">{{ $tool['lead'] }}</span>
        <a class="source" href="{{ $tool['source'] }}" target="_blank" rel="noopener">{{ $tool['credit'] }}</a>
        <a class="source" href="{{ $tool['src'] }}" target="_blank" rel="noopener">Yeni sekme</a>
    </div>
    <div class="nav-row">
        @include('partials.meemare-apps-nav', ['activeApp' => $tool['portal']])
    </div>
    @if($tool['portal'] === 'plan')
    <form class="handoff" id="plan-handoff" action="{{ route('wardrobe.design') }}" method="get">
        <p>OpenPlan’da çizdiğiniz odanın ön duvar ve yan duvar ölçülerini (cm) yazıp dolap stüdyosuna aktarın.</p>
        <label>Ön duvar (cm)<input type="number" name="onduvar" min="80" max="800" value="320" required></label>
        <label>Sol yan (cm)<input type="number" name="solduvar" min="80" max="800" value="280"></label>
        <label>Sağ yan (cm)<input type="number" name="sagduvar" min="80" max="800" value="280"></label>
        <button type="submit">3D dolaba aktar</button>
    </form>
    @endif
    <iframe src="{{ $tool['src'] }}" title="{{ $tool['title'] }}" referrerpolicy="no-referrer-when-downgrade" allow="fullscreen; clipboard-read; clipboard-write"></iframe>
</div>
@if($tool['portal'] === 'plan')
    <script src="{{ asset('js/meemare-plan-bridge.js') }}?v=1" defer></script>
@endif
@if($tool['portal'] === 'uretim')
    <script src="{{ asset('js/meemare-cabinet-bridge.js') }}?v=1" defer></script>
@endif
</body>
</html>