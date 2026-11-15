<div class="hero-top-links">
    @auth
        <a href="{{ route('wardrobe.design') }}">3D dolap</a>
        <a href="{{ route('tools.plan') }}">Oda planı</a>
        <a href="{{ route('tools.cabinet') }}">Üretim</a>
        <a href="{{ auth()->user()->isWorkshop() && ! auth()->user()->isAdmin() ? route('workshop.dashboard') : route('dashboard') }}" @class(['is-accent' => ($activeTop ?? '') === 'panel'])>Panel</a>
    @else
        <a href="{{ route('login') }}" @class(['is-accent' => ($activeTop ?? '') === 'login'])>Giriş</a>
    @endauth
</div>
