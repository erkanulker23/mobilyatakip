@php
    $activeApp = $activeApp ?? '';
    $homeUrl = auth()->check()
        ? (auth()->user()->isWorkshop() && ! auth()->user()->isAdmin()
            ? route('workshop.dashboard')
            : route('dashboard'))
        : route('login');
    $homeLabel = auth()->check() ? 'Kontrol paneli' : 'Giriş';
@endphp
<style>
    .meemare-apps {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        font-family: Montserrat, system-ui, sans-serif;
    }
    .meemare-apps a {
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
    }
    .meemare-apps a:hover { border-color: #c8c2b6; background: #fff; }
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
    @media (max-width: 720px) {
        .meemare-apps { gap: 4px; }
        .meemare-apps a { font-size: 11px; padding: 5px 8px; }
        .meemare-apps .meemare-apps__hide-sm { display: none; }
    }
</style>
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
            <button type="submit" style="border:1px solid #e4e1da;background:#fff;border-radius:999px;padding:6px 11px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit">Çıkış</button>
        </form>
    @endauth
</nav>