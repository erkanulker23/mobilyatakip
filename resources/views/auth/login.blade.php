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
    @include('partials.site-meta', ['company' => $company, 'pageTitle' => 'Giriş'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="{{ route('assets.js', ['file' => 'form-inputs.js']) }}"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                        mono: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        html, body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        button, input, select, textarea, label { font-family: inherit; }
        .dark { color-scheme: dark; }
    </style>
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-100 dark:bg-neutral-950 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-[400px]">
        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200/80 dark:border-neutral-800 shadow-sm p-8">
            <div class="text-center mb-8">
                @if($company?->logoUrl && $company->logoDisplayUrl())
                    <div class="flex justify-center mb-3">
                        <img src="{{ $company->logoDisplayUrl() }}" alt="{{ \App\Support\CompanyBranding::siteName($company) }}" class="h-16 w-auto max-w-[12rem] object-contain mx-auto">
                    </div>
                @else
                    <h1 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100 tracking-tight">{{ \App\Support\CompanyBranding::siteName($company) }}</h1>
                @endif
                <p class="text-neutral-500 dark:text-neutral-400 text-sm mt-1.5">Hesabınıza giriş yapın</p>
            </div>
            @if(session('success'))
            <div class="mb-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 text-sm">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('login') }}" class="space-y-5" id="login-form">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-500 mb-1.5">E-posta</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 @error('email') border-red-400 @enderror">
                    @error('email')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1.5">
                        <label for="password" class="block text-sm font-medium text-neutral-500">Şifre</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Şifremi unuttum</a>
                    </div>
                    <input type="password" id="password" name="password" required
                        class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember" class="rounded border-neutral-300 dark:border-neutral-600 text-emerald-600 focus:ring-emerald-500">
                    <label for="remember" class="text-sm text-neutral-500">Beni hatırla</label>
                </div>
                <button type="submit" id="login-btn" class="w-full py-3 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 disabled:opacity-70 disabled:cursor-not-allowed transition-colors">
                    Giriş Yap
                </button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('login-form')?.addEventListener('submit', function() {
            var btn = document.getElementById('login-btn');
            if (btn) { btn.disabled = true; btn.textContent = 'Giriş yapılıyor...'; }
        });
    </script>
</body>
</html>
