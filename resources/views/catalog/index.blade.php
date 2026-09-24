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
        'pageTitle' => 'Malzeme Kataloğu',
        'metaDescription' => 'Mobilya malzemeleri, renk ve dekor örnekleri. Firmalara göre panel ve yüzey seçeneklerini inceleyin.',
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
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-semibold text-neutral-900 dark:text-neutral-100 tracking-tight">Malzeme &amp; renk kataloğu</h1>
            <p class="mt-2 text-sm sm:text-base text-neutral-500 dark:text-neutral-400 max-w-2xl">
                Firmalara göre panel, dekor ve renk örneklerini inceleyin. İstediğiniz malzemeyi seçip kodunu siparişinizde kullanabilirsiniz.
            </p>
        </div>

        @if(count($manufacturers) === 0)
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-8 text-center text-neutral-500">
                Henüz katalog eklenmedi.
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($manufacturers as $m)
                    <a href="{{ route('catalog.manufacturer', $m['slug']) }}"
                       class="group rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 hover:border-emerald-500/60 hover:shadow-md transition-all">
                        <div class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-2">Firma</div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                            {{ $m['name'] }}
                        </h2>
                        @if(!empty($m['categories']))
                            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                {{ count($m['categories']) }} kategori
                                · {{ collect($m['categories'])->pluck('name')->take(2)->implode(', ') }}{{ count($m['categories']) > 2 ? '…' : '' }}
                            </p>
                        @endif
                        <span class="mt-4 inline-flex items-center text-sm font-medium text-emerald-600 dark:text-emerald-400">
                            İncele
                            <svg class="ml-1 w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </main>

    @include('catalog.partials.footer')
</body>
</html>
