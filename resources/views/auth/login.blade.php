@php
    $brand = \App\Support\CompanyBranding::siteName($company ?? null);
    $logoUrl = (($company?->logoUrl && $company->logoDisplayUrl()) ? $company->logoDisplayUrl() : null);
    $heroSrc = asset('images/wardrobe/set-giyinme.jpg');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Giriş',
        'metaDescription' => 'Meemare üye girişi — sipariş takibi, malzeme kataloğu ve 3D dolap stüdyosu.',
        'robots' => 'noindex, nofollow',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #f2f6f3;
            --ink-dim: rgba(242, 246, 243, .72);
            --ink-soft: rgba(242, 246, 243, .48);
            --line: rgba(242, 246, 243, .16);
            --accent: #3db87a;
            --accent-deep: #1a7a52;
            --field: rgba(10, 16, 13, .55);
            --field-border: rgba(242, 246, 243, .18);
            --danger: #ff8d7a;
            --ok: #8fe0b4;
            --ease: cubic-bezier(.22, 1, .36, 1);
            --display: "Syne", "Avenir Next", sans-serif;
            --ui: "Figtree", "Segoe UI", sans-serif;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; min-height: 100%;
            color: var(--ink);
            font-family: var(--ui);
            -webkit-font-smoothing: antialiased;
            background: #0a100d;
        }
        button, input { font-family: inherit; }
        a { color: inherit; }

        .login {
            position: relative;
            min-height: 100dvh;
            isolation: isolate;
            overflow: hidden;
            display: grid;
            align-items: end;
        }
        @media (min-width: 900px) {
            .login { align-items: stretch; grid-template-columns: 1.15fr 0.85fr; }
        }

        .login__media {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
        .login__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: 58% 40%;
            transform: scale(1.04);
            animation: login-drift 18s var(--ease) infinite alternate;
        }
        @keyframes login-drift {
            from { transform: scale(1.04) translate3d(0, 0, 0); }
            to { transform: scale(1.08) translate3d(-1.5%, -1%, 0); }
        }

        .login__veil {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(7, 12, 10, .78) 0%, rgba(7, 12, 10, .42) 42%, rgba(7, 12, 10, .72) 100%),
                linear-gradient(180deg, rgba(7, 12, 10, .35) 0%, rgba(7, 12, 10, .18) 35%, rgba(7, 12, 10, .88) 100%);
        }
        @media (min-width: 900px) {
            .login__veil {
                background:
                    linear-gradient(105deg, rgba(7, 12, 10, .82) 0%, rgba(7, 12, 10, .28) 48%, rgba(7, 12, 10, .78) 100%),
                    linear-gradient(180deg, rgba(7, 12, 10, .2) 0%, transparent 40%, rgba(7, 12, 10, .55) 100%);
            }
        }

        .login__grain {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            opacity: .18;
            mix-blend-mode: soft-light;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.55'/%3E%3C/svg%3E");
        }

        .login__copy,
        .login__auth {
            position: relative;
            z-index: 3;
        }

        .login__copy {
            padding: clamp(1.25rem, 4vw, 2.5rem);
            padding-bottom: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 2rem;
            min-height: 42dvh;
        }
        @media (min-width: 900px) {
            .login__copy {
                min-height: 100dvh;
                padding: clamp(2rem, 5vw, 3.5rem);
                justify-content: flex-start;
                gap: clamp(3rem, 10vh, 6rem);
            }
        }

        .login__brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            text-decoration: none;
            width: fit-content;
            animation: login-rise .8s var(--ease) both;
        }
        .login__brand img {
            height: clamp(28px, 4vw, 36px);
            width: auto;
            display: block;
            filter: brightness(1.05);
        }
        .login__brand-word {
            font-family: var(--display);
            font-weight: 800;
            font-size: clamp(1.85rem, 5.5vw, 3.4rem);
            letter-spacing: -.04em;
            line-height: .95;
            text-transform: uppercase;
        }

        .login__intro {
            max-width: 18ch;
            animation: login-rise .9s var(--ease) .08s both;
        }
        .login__intro h1 {
            margin: 0;
            font-family: var(--display);
            font-weight: 700;
            font-size: clamp(2rem, 6vw, 3.75rem);
            letter-spacing: -.045em;
            line-height: .98;
        }
        .login__intro p {
            margin: 1rem 0 0;
            max-width: 28ch;
            font-size: clamp(1rem, 2.1vw, 1.2rem);
            line-height: 1.45;
            color: var(--ink-dim);
            font-weight: 500;
        }

        .login__auth {
            padding: 0 clamp(1.1rem, 4vw, 2.5rem) calc(1.4rem + env(safe-area-inset-bottom));
            animation: login-rise .9s var(--ease) .16s both;
        }
        @media (min-width: 900px) {
            .login__auth {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: clamp(2rem, 5vw, 3.5rem);
                background: linear-gradient(180deg, rgba(8, 14, 11, .28), rgba(8, 14, 11, .55));
                backdrop-filter: blur(18px) saturate(1.15);
                -webkit-backdrop-filter: blur(18px) saturate(1.15);
                border-left: 1px solid var(--line);
            }
        }

        .login__panel {
            width: min(100%, 420px);
            margin: 0 auto;
            padding: 1.25rem 1.2rem 1.35rem;
            border-radius: 28px 28px 22px 22px;
            background: rgba(12, 18, 15, .66);
            border: 1px solid var(--line);
            box-shadow: 0 24px 80px rgba(0, 0, 0, .35);
            backdrop-filter: blur(22px) saturate(1.2);
            -webkit-backdrop-filter: blur(22px) saturate(1.2);
        }
        @media (min-width: 900px) {
            .login__panel {
                margin: 0;
                width: min(100%, 400px);
                padding: 1.75rem 1.6rem 1.7rem;
                border-radius: 28px;
            }
        }

        .login__panel h2 {
            margin: 0;
            font-family: var(--display);
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -.03em;
        }
        .login__panel .lead {
            margin: .4rem 0 1.25rem;
            color: var(--ink-dim);
            font-size: .95rem;
            line-height: 1.45;
        }

        .flash {
            margin: 0 0 1rem;
            padding: .75rem .9rem;
            border-radius: 14px;
            font-size: .9rem;
            font-weight: 600;
            line-height: 1.35;
        }
        .flash-ok {
            background: rgba(61, 184, 122, .14);
            color: var(--ok);
            border: 1px solid rgba(61, 184, 122, .28);
        }
        .flash-err {
            background: rgba(255, 141, 122, .12);
            color: var(--danger);
            border: 1px solid rgba(255, 141, 122, .28);
        }

        .field { margin-bottom: 1rem; }
        .field label {
            display: block;
            margin-bottom: .4rem;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--ink-soft);
        }
        .field-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .4rem;
        }
        .field-row label { margin-bottom: 0; }
        .field input[type="email"],
        .field input[type="password"] {
            width: 100%;
            min-height: 48px;
            border-radius: 14px;
            border: 1px solid var(--field-border);
            background: var(--field);
            color: var(--ink);
            padding: .85rem 1rem;
            font-size: 1rem;
            outline: none;
            transition: border-color .2s var(--ease), box-shadow .2s var(--ease), background .2s var(--ease);
        }
        .field input::placeholder { color: rgba(242, 246, 243, .35); }
        .field input:focus {
            border-color: rgba(61, 184, 122, .65);
            box-shadow: 0 0 0 4px rgba(61, 184, 122, .16);
            background: rgba(10, 16, 13, .72);
        }
        .field input.is-error {
            border-color: rgba(255, 141, 122, .7);
        }
        .err {
            margin: .4rem 0 0;
            color: var(--danger);
            font-size: .85rem;
            font-weight: 600;
        }

        .link-quiet {
            font-size: .85rem;
            font-weight: 600;
            color: var(--ink-dim);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: color .2s var(--ease), border-color .2s var(--ease);
        }
        .link-quiet:hover {
            color: var(--ink);
            border-bottom-color: rgba(242, 246, 243, .35);
        }

        .remember {
            display: flex;
            align-items: center;
            gap: .55rem;
            margin: .25rem 0 1.15rem;
            font-size: .92rem;
            font-weight: 500;
            color: var(--ink-dim);
        }
        .remember input {
            width: 1.05rem;
            height: 1.05rem;
            accent-color: var(--accent-deep);
        }

        .btn-login {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-deep) 100%);
            color: #07110c;
            font-family: var(--display);
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -.01em;
            cursor: pointer;
            box-shadow: 0 14px 36px rgba(26, 122, 82, .35);
            transition: transform .2s var(--ease), filter .2s var(--ease), box-shadow .2s var(--ease);
        }
        .btn-login:hover { filter: brightness(1.05); box-shadow: 0 18px 40px rgba(26, 122, 82, .42); }
        .btn-login:active { transform: scale(.985); }
        .btn-login:disabled {
            opacity: .65;
            cursor: default;
            transform: none;
            box-shadow: none;
        }

        .login__guest {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem 1rem;
            margin-top: 1.15rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }
        .login__guest a {
            font-size: .88rem;
            font-weight: 600;
            color: var(--ink-dim);
            text-decoration: none;
            transition: color .2s var(--ease);
        }
        .login__guest a:hover { color: var(--ink); }

        @keyframes login-rise {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .login__media img,
            .login__brand,
            .login__intro,
            .login__auth { animation: none !important; }
        }
    </style>
</head>
<body>
<main class="login">
    <div class="login__media" aria-hidden="true">
        <img src="{{ $heroSrc }}" alt="" width="887" height="903" decoding="async" fetchpriority="high">
    </div>
    <div class="login__veil" aria-hidden="true"></div>
    <div class="login__grain" aria-hidden="true"></div>

    <section class="login__copy" aria-label="Karşılama">
        <a class="login__brand" href="{{ url('/') }}">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brand }}">
            @else
                <span class="login__brand-word">{{ $brand }}</span>
            @endif
        </a>
        <div class="login__intro">
            <h1>Dolabını yerinde kur</h1>
            <p>Ölçü, malzeme ve üretim — tek stüdyoda.</p>
        </div>
    </section>

    <section class="login__auth" aria-label="Üye girişi">
        <div class="login__panel">
            <h2>Üye girişi</h2>
            <p class="lead">E-posta ve şifrenizle stüdyoya geçin.</p>

            @if(session('success'))
                <div class="flash flash-ok">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="flash flash-err">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="login-form">
                @csrf
                <div class="field">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="ornek@email.com"
                           class="@error('email') is-error @enderror"
                           autocomplete="username">
                    @error('email')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <div class="field-row">
                        <label for="password">Şifre</label>
                        <a class="link-quiet" href="{{ route('password.request') }}">Şifremi unuttum</a>
                    </div>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <div class="remember">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Beni hatırla</label>
                </div>
                <button type="submit" class="btn-login" id="login-btn">Giriş Yap</button>
            </form>

            <nav class="login__guest" aria-label="Misafir erişim">
                <a href="{{ url('/takip') }}">Sipariş takip</a>
                <a href="{{ url('/katalog') }}">Katalog</a>
                <a href="{{ url('/dolap') }}">3D dolap</a>
            </nav>
        </div>
    </section>
</main>
<script>
    document.getElementById('login-form')?.addEventListener('submit', function () {
        var btn = document.getElementById('login-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Giriş yapılıyor…'; }
    });
</script>
</body>
</html>
