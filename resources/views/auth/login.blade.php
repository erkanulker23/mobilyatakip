<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Giriş - Mobilya Takip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'] } } } }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-[400px]">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8">
            <div class="text-center mb-8">
                <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Mobilya Takip</h1>
                <p class="text-slate-500 text-sm mt-1.5">Hesabınıza giriş yapın</p>
            </div>
            <form method="POST" action="{{ route('login') }}" class="space-y-5" id="login-form">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-600 mb-1.5">E-posta</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 @error('email') border-red-400 @enderror">
                    @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-600 mb-1.5">Şifre</label>
                    <input type="password" id="password" name="password" required
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="remember" class="text-sm text-slate-600">Beni hatırla</label>
                </div>
                <button type="submit" id="login-btn" class="w-full py-3 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed transition-colors">
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
