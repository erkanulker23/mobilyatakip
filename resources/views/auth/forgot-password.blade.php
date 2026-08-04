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
    @include('partials.site-meta', ['company' => $company ?? null, 'pageTitle' => 'Şifremi Unuttum'])
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Montserrat', 'system-ui', 'sans-serif'] } } } };</script>
    <style>html, body { font-family: 'Montserrat', system-ui, sans-serif; }</style>
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-100 dark:bg-neutral-950 flex items-center justify-center p-4">
    <div class="w-full max-w-[400px]">
        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200/80 dark:border-neutral-800 shadow-sm p-8">
            <div class="text-center mb-6">
                <h1 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Şifremi Unuttum</h1>
                <p class="text-neutral-500 dark:text-neutral-400 text-sm mt-1.5">E-posta adresinize şifre sıfırlama bağlantısı gönderelim</p>
            </div>

            @if(session('success'))
            <div class="mb-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-500 mb-1.5">E-posta</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 @error('email') border-red-400 @enderror">
                    @error('email')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                    Sıfırlama Bağlantısı Gönder
                </button>
            </form>

            <p class="mt-6 text-center text-sm">
                <a href="{{ route('login') }}" class="text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-200">← Giriş sayfasına dön</a>
            </p>
        </div>
    </div>
</body>
</html>
