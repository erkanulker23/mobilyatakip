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
    @include('partials.site-meta', ['company' => $company ?? null, 'pageTitle' => 'Yeni Şifre'])
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Montserrat', 'system-ui', 'sans-serif'] } } } };</script>
    <style>html, body { font-family: 'Montserrat', system-ui, sans-serif; }</style>
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-100 dark:bg-neutral-950 flex items-center justify-center p-4">
    <div class="w-full max-w-[400px]">
        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200/80 dark:border-neutral-800 shadow-sm p-8">
            <div class="text-center mb-6">
                <h1 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Yeni Şifre Belirle</h1>
                <p class="text-neutral-500 dark:text-neutral-400 text-sm mt-1.5">Hesabınız için yeni bir şifre oluşturun</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-500 mb-1.5">E-posta</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required readonly
                        class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/80 text-neutral-700 dark:text-neutral-300 px-3.5 py-2.5 text-sm">
                    @error('email')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-500 mb-1.5">Yeni şifre</label>
                    <input type="password" id="password" name="password" required autofocus
                        class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 @error('password') border-red-400 @enderror">
                    @error('password')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-neutral-500 mb-1.5">Yeni şifre (tekrar)</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                    Şifreyi Güncelle
                </button>
            </form>

            <p class="mt-6 text-center text-sm">
                <a href="{{ route('login') }}" class="text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-200">← Giriş sayfasına dön</a>
            </p>
        </div>
    </div>
</body>
</html>
