@php
    $activeApp = $activeApp ?? '';
    $compact = (bool) ($compact ?? false);
    $homeUrl = auth()->check()
        ? (auth()->user()->isWorkshop() && ! auth()->user()->isAdmin()
            ? route('workshop.dashboard')
            : route('dashboard'))
        : route('login');
    $homeLabel = auth()->check() ? 'Kontrol paneli' : 'Giriş';
    $labels = [
        'projeler' => 'Dolap projeleri',
        'tasarla' => '3D tasarla',
        'plan' => 'Oda planı',
        'uretim' => 'Üretim dolabı',
    ];
    $activeLabel = $labels[$activeApp] ?? 'Uygulamalar';
@endphp
<style>
    .meemare-apps {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        font-family: Montserrat, system-ui, sans-serif;
    }
    .meemare-apps a, .meemare-apps button.meemare-apps__exit {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 11px;
        border-radius: 999px;
        border: 1px solid #e4e1da;
        background: rgba(255, 252, 248, .95);
        color: #1c1c1c;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        transition: background .15s, border-color .15s, color .15s;
        cursor: pointer;
        font-family: inherit;
    }
    .meemare-apps a:hover, .meemare-apps button.meemare-apps__exit:hover {
        border-color: #c8c2b6;
        background: #fff;
    }
    .meemare-apps a.is-on {
        background: #1c1c1c;
        border-color: #1c1c1c;
        color: #fff;
    }
    .meemare-apps a.is-accent {
        background: #1f6b4a;
        border-color: #1f6b4a;
        color: #fff;
    }
    .meemare-apps .meemare-apps__sep {
        width: 1px;
        height: 20px;
        background: #e4e1da;
        margin: 0 2px;
    }
    .meemare-apps--compact {
        position: relative;
        flex-wrap: nowrap;
        gap: 0;
    }
    .meemare-apps--compact > summary {
        list-style: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid #e4e1da;
        background: #fff;
        color: #1c1c1c;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        user-select: none;
    }
    .meemare-apps--compact > summary::-webkit-details-marker { display: none; }
    .meemare-apps--compact > summary::after {
        content: '';
        width: 0; height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid #6d6a64;
        margin-left: 2px;
    }
    .meemare-apps--compact[open] > summary {
        border-color: #1c1c1c;
        background: #1c1c1c;
        color: #fff;
    }
    .meemare-apps--compact[open] > summary::after { border-top-color: #fff; }
    .meemare-apps__menu {
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        z-index: 40;
        min-width: 196px;
        padding: 6px;
        border-radius: 12px;
        border: 1px solid #e4e1da;
        background: #fffcf8;
        box-shadow: 0 14px 36px rgba(28, 28, 28, .14);
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .meemare-apps__menu a,
    .meemare-apps__menu button.meemare-apps__exit {
        width: 100%;
        justify-content: flex-start;
        border-radius: 8px;
        border: 0;
        background: transparent;
        padding: 9px 10px;
        font-size: 12px;
    }
    .meemare-apps__menu a.is-on,
    .meemare-apps__menu a.is-accent {
        background: #1f6b4a;
        color: #fff;
    }
    .meemare-apps__menu .meemare-apps__sep {
        width: auto;
        height: 1px;
        margin: 4px 6px;
    }
    @media (max-width: 720px) {
        .meemare-apps:not(.meemare-apps--compact) { gap: 4px; }
        .meemare-apps:not(.meemare-apps--compact) a { font-size: 11px; padding: 5px 8px; }
        .meemare-apps:not(.meemare-apps--compact) .meemare-apps__hide-sm { display: none; }
    }
</style>
@if ($compact)
<details class="meemare-apps meemare-apps--compact" aria-label="Meemare tasarım uygulamaları">
    <summary title="Uygulamalar">{{ $activeLabel }}</summary>
    <div class="meemare-apps__menu">
        <a href="{{ route('wardrobe.index') }}" @class(['is-on' => $activeApp === 'projeler'])>Dolap projeleri</a>
        <a href="{{ route('wardrobe.design') }}" @class(['is-on' => $activeApp === 'tasarla', 'is-accent' => $activeApp === 'tasarla'])>3D tasarla</a>
        <a href="{{ route('tools.plan') }}" @class(['is-on' => $activeApp === 'plan'])>Oda planı</a>
        <a href="{{ route('tools.cabinet') }}" @class(['is-on' => $activeApp === 'uretim'])>Üretim dolabı</a>
        <span class="meemare-apps__sep" aria-hidden="true"></span>
        <a href="{{ $homeUrl }}">{{ $homeLabel }}</a>
        @auth
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="meemare-apps__exit">Çıkış</button>
            </form>
        @endauth
    </div>
</details>
@else
<nav class="meemare-apps" aria-label="Meemare tasarım uygulamaları">
    <a href="{{ route('wardrobe.index') }}" @class(['is-on' => $activeApp === 'projeler'])>Dolap projeleri</a>
    <a href="{{ route('wardrobe.design') }}" @class(['is-on' => $activeApp === 'tasarla', 'is-accent' => $activeApp === 'tasarla'])>3D tasarla</a>
    <a href="{{ route('tools.plan') }}" @class(['is-on' => $activeApp === 'plan'])>Oda planı</a>
    <a href="{{ route('tools.cabinet') }}" @class(['is-on' => $activeApp === 'uretim'])>Üretim dolabı</a>
    <span class="meemare-apps__sep meemare-apps__hide-sm" aria-hidden="true"></span>
    <a href="{{ $homeUrl }}" class="meemare-apps__hide-sm">{{ $homeLabel }}</a>
    @auth
        <form method="POST" action="{{ route('logout') }}" style="margin:0;display:inline">
            @csrf
            <button type="submit" class="meemare-apps__exit">Çıkış</button>
        </form>
    @endauth
</nav>
@endif
