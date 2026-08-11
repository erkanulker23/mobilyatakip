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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => 'Sipariş Takibi',
        'robots' => 'noindex, nofollow',
        'metaDescription' => 'Sipariş veya SSH takip kodunuzla siparişinizin hangi aşamada olduğunu öğrenin.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Montserrat', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        html, body {
            font-family: 'Montserrat', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .dark { color-scheme: dark; }
        .stage-line { height: 2px; flex: 1; min-width: 12px; }
    </style>
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-100 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 transition-colors">
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-neutral-200 dark:border-neutral-800 bg-white/90 dark:bg-neutral-900/90 backdrop-blur">
            <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    @if($company?->logoUrl && $company->logoDisplayUrl())
                        <img src="{{ $company->logoDisplayUrl() }}" alt="{{ \App\Support\CompanyBranding::siteName($company) }}" class="h-10 w-auto max-w-[10rem] object-contain">
                    @else
                        <span class="font-semibold text-neutral-900 dark:text-neutral-100 truncate">{{ \App\Support\CompanyBranding::siteName($company) }}</span>
                    @endif
                </div>
                <span class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 shrink-0">Sipariş Takibi</span>
            </div>
        </header>

        <main class="flex-1 px-4 py-8 sm:py-12">
            <div class="max-w-3xl mx-auto space-y-6">
                <div class="text-center mb-2">
                    <h1 class="text-2xl sm:text-3xl font-semibold text-neutral-900 dark:text-neutral-100 tracking-tight">Takip kodu sorgula</h1>
                    <p class="mt-2 text-sm sm:text-base text-neutral-500 dark:text-neutral-400">Sipariş numaranızı (SAT-…) veya SSH kodunuzu girerek sürecinizi görüntüleyin.</p>
                </div>

                <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 shadow-sm p-5 sm:p-6">
                    <form method="POST" action="{{ url('/takip') }}" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <label for="code" class="sr-only">Takip kodu</label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            value="{{ old('code', $code) }}"
                            placeholder="Örn: SAT-2026-00042 veya SSH-2026-00003"
                            autocomplete="off"
                            autofocus
                            class="flex-1 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 px-4 py-3 text-sm sm:text-base focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 @error('code') border-red-400 @enderror"
                        >
                        <button type="submit" class="shrink-0 inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-3 text-sm sm:text-base transition-colors">
                            Sorgula
                        </button>
                    </form>
                    @error('code')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-3 text-xs text-neutral-500 dark:text-neutral-400">Takip linki örneği: <span class="font-mono text-neutral-600 dark:text-neutral-300">{{ url('/takip') }}/SAT-2026-00042</span></p>
                </div>

                @if($notFound)
                <div class="rounded-2xl border border-amber-200 dark:border-amber-900/40 bg-amber-50 dark:bg-amber-900/20 px-5 py-4 text-amber-900 dark:text-amber-200">
                    <p class="font-medium">Kayıt bulunamadı</p>
                    <p class="text-sm mt-1 opacity-90">“{{ $code }}” koduna ait sipariş veya SSH kaydı yok. Kodu kontrol edip tekrar deneyin.</p>
                </div>
                @endif

                @if($result)
                    @include('tracking.partials.result', ['result' => $result])
                @endif
            </div>
        </main>

        <footer class="py-6 text-center text-xs text-neutral-400 dark:text-neutral-500">
            {{ \App\Support\CompanyBranding::siteName($company) }} · Sipariş takip
        </footer>
    </div>
</body>
</html>
