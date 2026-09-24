<!DOCTYPE html>
<html lang="tr">
<head>
    <script>
        (function () {
            try {
                if (localStorage.getItem('theme-dark') === '1') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Giriş',
        'metaDescription' => 'Üye girişi, sipariş takibi, malzeme kataloğu ve 3D TV ünitesi tasarımcısı.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="{{ route('assets.js', ['file' => 'form-inputs.js']) }}"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Montserrat', 'system-ui', 'sans-serif'],
                        display: ['Cormorant Garamond', 'Georgia', 'serif'],
                    },
                },
            },
        }
    </script>
    <style>
        :root {
            --ink: #141816;
            --muted: #5c6560;
            --line: rgba(20, 24, 22, 0.1);
            --accent: #1f6b4a;
            --accent-soft: #e8f3ed;
            --panel: #f7f5f1;
            --wood: #2a332e;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; min-height: 100%;
            font-family: Montserrat, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: var(--ink);
            background: var(--wood);
        }
        .gate {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
        }
        @media (max-width: 960px) {
            .gate { grid-template-columns: 1fr; }
        }

        .hero {
            position: relative;
            overflow: hidden;
            padding: clamp(1.5rem, 4vw, 3rem);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 52vh;
            background:
                radial-gradient(120% 80% at 10% 0%, rgba(61, 110, 84, 0.45), transparent 55%),
                radial-gradient(90% 70% at 90% 100%, rgba(20, 24, 22, 0.55), transparent 50%),
                linear-gradient(145deg, #1c2621 0%, #2f3d35 42%, #1a221e 100%);
            color: #f4f1ea;
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background-image:
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: linear-gradient(180deg, rgba(0,0,0,.55), transparent 85%);
            pointer-events: none;
        }
        .hero::after {
            content: '';
            position: absolute;
            right: -12%;
            bottom: -18%;
            width: min(70vw, 520px);
            height: min(70vw, 520px);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(232, 243, 237, 0.12), transparent 68%);
            pointer-events: none;
            animation: drift 12s ease-in-out infinite alternate;
        }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(-4%, -3%) scale(1.06); }
        }
        @keyframes rise {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .rise { animation: rise .7s ease both; }
        .rise-2 { animation-delay: .12s; }
        .rise-3 { animation-delay: .22s; }
        .rise-4 { animation-delay: .32s; }

        .hero-top, .hero-main, .hero-portals { position: relative; z-index: 1; }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: inherit;
        }
        .brand-lockup img {
            height: clamp(2.75rem, 6vw, 3.75rem);
            width: auto;
            max-width: 13rem;
            object-fit: contain;
            filter: drop-shadow(0 8px 24px rgba(0,0,0,.25));
        }
        .brand-word {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(2rem, 4.5vw, 3rem);
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            line-height: 1;
        }
        .hero-kicker {
            margin: 0 0 0.75rem;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(244, 241, 234, 0.55);
        }
        .hero-title {
            margin: 0;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(2.4rem, 5.5vw, 3.6rem);
            font-weight: 500;
            line-height: 1.05;
            letter-spacing: -0.01em;
            max-width: 12ch;
        }
        .hero-lead {
            margin: 1rem 0 0;
            max-width: 32ch;
            font-size: 0.95rem;
            line-height: 1.55;
            color: rgba(244, 241, 234, 0.72);
            font-weight: 400;
        }

        .portals {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-top: clamp(1.75rem, 4vw, 2.5rem);
        }
        @media (max-width: 640px) {
            .portals { grid-template-columns: 1fr; }
        }
        .portal {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            padding: 1.05rem 1.1rem 1.15rem;
            border: 1px solid rgba(255,255,255,0.14);
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(10px);
            text-decoration: none;
            color: #f4f1ea;
            transition: background .2s ease, border-color .2s ease, transform .2s ease;
        }
        .portal:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.28);
            transform: translateY(-2px);
        }
        .portal-ico {
            width: 2rem; height: 2rem;
            display: grid; place-items: center;
            color: #b7d4c4;
        }
        .portal strong {
            font-size: 0.92rem;
            font-weight: 600;
            letter-spacing: -0.01em;
        }
        .portal span {
            font-size: 0.75rem;
            line-height: 1.4;
            color: rgba(244, 241, 234, 0.58);
            font-weight: 500;
        }

        .panel {
            background: var(--panel);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1.5rem, 4vw, 3rem);
        }
        .panel-inner {
            width: 100%;
            max-width: 380px;
        }
        .panel-head {
            margin-bottom: 1.75rem;
        }
        .panel-head h2 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--ink);
        }
        .panel-head p {
            margin: 0.4rem 0 0;
            font-size: 0.875rem;
            color: var(--muted);
            line-height: 1.45;
        }
        .field { margin-bottom: 1.1rem; }
        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.45rem;
        }
        .field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 0.45rem;
            letter-spacing: 0.02em;
        }
        .field-row label { margin-bottom: 0; }
        .field input[type="email"],
        .field input[type="password"] {
            width: 100%;
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 12px;
            padding: 0.8rem 0.95rem;
            font: inherit;
            font-size: 0.9rem;
            color: var(--ink);
            transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.15);
        }
        .field input.is-error { border-color: #dc2626; }
        .err {
            margin: 0.4rem 0 0;
            font-size: 0.8rem;
            color: #dc2626;
        }
        .flash {
            margin-bottom: 1rem;
            padding: 0.75rem 0.9rem;
            border-radius: 12px;
            font-size: 0.85rem;
        }
        .flash-ok { background: #e8f3ed; color: #14532d; }
        .flash-err { background: #fff7ed; color: #9a3412; }
        .link-quiet {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--accent);
            text-decoration: none;
        }
        .link-quiet:hover { text-decoration: underline; }
        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.25rem 0 1.25rem;
            font-size: 0.85rem;
            color: var(--muted);
        }
        .remember input {
            width: 1rem; height: 1rem;
            accent-color: var(--accent);
        }
        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 0.95rem 1rem;
            background: var(--accent);
            color: #fff;
            font: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s ease, transform .15s ease;
        }
        .btn-login:hover { background: #185a3e; transform: translateY(-1px); }
        .btn-login:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .panel-note {
            margin: 1.5rem 0 0;
            padding-top: 1.25rem;
            border-top: 1px solid var(--line);
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.5;
            text-align: center;
        }
        @media (max-width: 960px) {
            .hero { min-height: auto; padding-bottom: 2rem; }
            .hero-title { max-width: none; }
        }
    </style>
</head>
@php
    $brand = \App\Support\CompanyBranding::siteName($company);
    $logoUrl = ($company?->logoUrl && $company->logoDisplayUrl()) ? $company->logoDisplayUrl() : null;
@endphp
<body>
<div class="gate">
    <section class="hero" aria-label="Karşılama">
        <div class="hero-top rise">
            <a class="brand-lockup" href="{{ url('/') }}">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $brand }}">
                @else
                    <span class="brand-word">{{ $brand }}</span>
                @endif
            </a>
        </div>

        <div class="hero-main rise rise-2">
            <p class="hero-kicker">Mobilya · Tasarım · Takip</p>
            <h1 class="hero-title">Her şey yerli yerinde</h1>
            <p class="hero-lead">Siparişinizi izleyin, malzemeleri inceleyin veya TV ünitenizi 3D tasarlayın. Üyeler sağdan giriş yapar.</p>
        </div>

        <nav class="hero-portals rise rise-3" aria-label="Hızlı erişim">
            <div class="portals">
                <a class="portal" href="{{ url('/takip') }}">
                    <span class="portal-ico" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/><path d="M11 8v3l2 1"/></svg>
                    </span>
                    <strong>Takip kodu sorgula</strong>
                    <span>Sipariş veya SSH kodunuzla durumu görün</span>
                </a>
                <a class="portal" href="{{ url('/katalog') }}">
                    <span class="portal-ico" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="7" height="7" rx="1"/><rect x="14" y="4" width="7" height="7" rx="1"/><rect x="3" y="13" width="7" height="7" rx="1"/><rect x="14" y="13" width="7" height="7" rx="1"/></svg>
                    </span>
                    <strong>Kataloga göz at</strong>
                    <span>Kastamonu Entegre renk ve kaplamalar</span>
                </a>
                <a class="portal" href="{{ url('/tasarla') }}">
                    <span class="portal-ico" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 18V6l8-3 8 3v12l-8 3-8-3z"/><path d="M12 3v18M4 6l8 3 8-3"/></svg>
                    </span>
                    <strong>3D tasarımcıya git</strong>
                    <span>TV ünitesini sürükle-bırak ile kurun</span>
                </a>
            </div>
        </nav>
    </section>

    <aside class="panel" aria-label="Üye girişi">
        <div class="panel-inner rise rise-4">
            <div class="panel-head">
                <h2>Üye girişi</h2>
                <p>Panele erişmek için e-posta ve şifrenizle giriş yapın.</p>
            </div>

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

            <p class="panel-note">Misafir olarak takip, katalog ve 3D tasarımı soldaki bağlantılardan kullanabilirsiniz.</p>
        </div>
    </aside>
</div>
<script>
    document.getElementById('login-form')?.addEventListener('submit', function () {
        var btn = document.getElementById('login-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Giriş yapılıyor...'; }
    });
</script>
</body>
</html>
