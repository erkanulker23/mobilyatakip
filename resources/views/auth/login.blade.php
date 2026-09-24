<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Giriş',
        'metaDescription' => 'Üye girişi, sipariş takibi, malzeme kataloğu ve 3D TV ünitesi tasarımcısı.',
    ])
    @include('partials.public-gate-head-bootstrap')
    <script defer src="{{ route('assets.js', ['file' => 'form-inputs.js']) }}"></script>
    @include('partials.public-gate-styles')
</head>
<body>
<div class="gate">
    <section class="hero" aria-label="Karşılama">
        <div class="hero-top rise">
            @include('partials.public-gate-brand', ['homeUrl' => url('/')])
        </div>

        <div class="hero-main rise rise-2">
            <p class="hero-kicker">Mobilya · Tasarım · Takip</p>
            <h1 class="hero-title">Her şey yerli yerinde</h1>
            <p class="hero-lead">Siparişinizi izleyin, malzemeleri inceleyin veya TV ünitenizi 3D tasarlayın. Üyeler sağdan giriş yapar.</p>
        </div>

        @include('partials.public-gate-portals')
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
