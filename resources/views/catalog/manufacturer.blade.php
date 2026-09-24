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
        'pageTitle' => $manufacturer['name'].' · Malzeme Kataloğu',
        'metaDescription' => $manufacturer['name'].' malzeme ve renk kategorileri.',
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
    </style>
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-100 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 transition-colors">
    @include('catalog.partials.header', ['subtitle' => 'Malzeme Kataloğu'])

    <main class="max-w-6xl mx-auto px-4 py-8 sm:py-12">
        <nav class="text-sm text-neutral-500 dark:text-neutral-400 mb-6 flex flex-wrap items-center gap-1.5">
            <a href="{{ route('catalog.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Katalog</a>
            <span aria-hidden="true">/</span>
            <span class="text-neutral-800 dark:text-neutral-200">{{ $manufacturer['name'] }}</span>
        </nav>

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-semibold text-neutral-900 dark:text-neutral-100 tracking-tight">{{ $manufacturer['name'] }}</h1>
            <p class="mt-2 text-sm sm:text-base text-neutral-500 dark:text-neutral-400">Ürün kategorilerini seçerek renk ve dekor örneklerini görüntüleyin.</p>
        </div>

        @php
            $featured = collect($categories)->firstWhere('slug', 'glossmax-pro');
            $mainCats = collect($categories)->reject(fn ($c) => ($c['slug'] ?? '') === 'glossmax-pro')->values();
        @endphp

        @if($featured)
            <div class="mb-8">
                <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-3">PDF katalog</div>
                <a href="{{ route('catalog.category', [$manufacturer['slug'], $featured['slug']]) }}"
                   class="group flex flex-col sm:flex-row overflow-hidden rounded-2xl border border-emerald-200/80 dark:border-emerald-900 bg-white dark:bg-neutral-900 hover:shadow-md transition-all">
                    <div class="sm:w-56 aspect-[16/9] sm:aspect-auto bg-neutral-200 dark:bg-neutral-800 shrink-0">
                        @php
                            $hero = public_path('catalog/'.$manufacturer['slug'].'/'.$featured['slug'].'/hero.webp');
                            $heroUrl = file_exists($hero) ? asset('catalog/'.$manufacturer['slug'].'/'.$featured['slug'].'/hero.webp') : null;
                        @endphp
                        @if($heroUrl)
                            <img src="{{ $heroUrl }}" alt="{{ $featured['name'] }}" class="w-full h-full object-cover min-h-[8rem] group-hover:scale-[1.02] transition-transform duration-500">
                        @endif
                    </div>
                    <div class="p-5 sm:p-6 flex flex-col justify-center">
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">{{ $featured['name'] }}</h2>
                        <p class="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">Resmi katalogdaki 12 high gloss renk — Yalın ve Doğal seriler. Teknik özellikler ve PDF indirme.</p>
                        <span class="mt-3 inline-flex items-center text-sm font-medium text-emerald-600 dark:text-emerald-400">
                            Koleksiyonu aç
                            <svg class="ml-1 w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </a>
            </div>
        @endif

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($mainCats as $cat)
                <a href="{{ route('catalog.category', [$manufacturer['slug'], $cat['slug']]) }}"
                   class="group rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 overflow-hidden hover:border-emerald-500/60 hover:shadow-md transition-all">
                    <div class="aspect-[16/9] bg-neutral-200 dark:bg-neutral-800">
                        @php
                            $hero = public_path('catalog/'.$manufacturer['slug'].'/'.$cat['slug'].'/hero.webp');
                            $heroUrl = file_exists($hero) ? asset('catalog/'.$manufacturer['slug'].'/'.$cat['slug'].'/hero.webp') : null;
                        @endphp
                        @if($heroUrl)
                            <img src="{{ $heroUrl }}" alt="{{ $cat['name'] }}" class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-neutral-400 text-sm">{{ $cat['name'] }}</div>
                        @endif
                    </div>
                    <div class="p-5">
                        <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">{{ $cat['name'] }}</h2>
                        <span class="mt-2 inline-flex items-center text-sm font-medium text-emerald-600 dark:text-emerald-400">
                            Renkleri gör
                            <svg class="ml-1 w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </main>

    @include('catalog.partials.footer')
</body>
</html>
