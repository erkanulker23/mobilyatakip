<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('meta')
    @include('partials.public-gate-head-bootstrap')
    @include('partials.public-gate-styles')
    @stack('head')
</head>
<body class="pub-page">
    <header class="hero hero--page" aria-label="@yield('hero_aria', 'Sayfa')">
        <div class="hero-top rise">
            <div class="hero-top-row">
                @include('partials.public-gate-brand', ['homeUrl' => route('login')])
                @include('partials.public-gate-top-links')
            </div>
        </div>

        <div class="hero-main rise rise-2">
            @yield('hero')
        </div>

        @include('partials.public-gate-portals', [
            'activePortal' => trim($__env->yieldContent('active_portal')) ?: null,
        ])
    </header>

    <main class="pub-main">
        <div class="pub-wrap @yield('wrap_class', 'pub-wrap--lg')">
            @yield('content')
        </div>
    </main>

    <footer class="pub-footer">
        {{ \App\Support\CompanyBranding::siteName($company) }}
        @hasSection('footer_extra')
            · @yield('footer_extra')
        @endif
    </footer>
    @stack('scripts')
</body>
</html>
