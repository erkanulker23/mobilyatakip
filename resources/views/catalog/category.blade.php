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
        'pageTitle' => $category['name'].' · '.$manufacturer['name'],
        'metaDescription' => ($category['description'] ?? ($manufacturer['name'].' '.$category['name'].' renk ve dekor örnekleri.')),
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
                    colors: {
                        keas: {
                            50: '#f0f7fa',
                            600: '#0b5f7a',
                            700: '#084a60',
                            800: '#1a2b4a',
                        }
                    }
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
        .catalog-hero {
            background-size: cover;
            background-position: center;
        }
        .swatch-card:hover .swatch-img {
            transform: scale(1.04);
        }
        .swatch-img {
            transition: transform 0.45s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-up {
            animation: fadeUp 0.35s ease both;
        }
    </style>
</head>
<body class="font-sans antialiased min-h-screen bg-white dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 transition-colors">
    @include('catalog.partials.header', ['subtitle' => $manufacturer['name']])

    {{-- Hero --}}
    <section class="relative">
        @php
            $hero = !empty($category['hero_image']) ? $assetBase.'/'.$category['hero_image'] : null;
        @endphp
        <div class="catalog-hero relative min-h-[220px] sm:min-h-[280px] lg:min-h-[320px] bg-neutral-800"
             @if($hero) style="background-image: linear-gradient(to bottom, rgba(15,23,42,.35), rgba(15,23,42,.55)), url('{{ $hero }}');" @endif>
            <div class="max-w-6xl mx-auto px-4 pt-6 pb-16 sm:pb-20 relative z-10">
                <nav class="text-sm text-white/80 mb-8 flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('catalog.index') }}" class="hover:text-white">Katalog</a>
                    <span aria-hidden="true">›</span>
                    <a href="{{ route('catalog.manufacturer', $manufacturer['slug']) }}" class="hover:text-white">{{ $manufacturer['name'] }}</a>
                    <span aria-hidden="true">›</span>
                    <span class="text-white">{{ $category['name'] }}</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-semibold text-white tracking-tight drop-shadow">{{ $category['name'] }}</h1>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="bg-neutral-700 dark:bg-neutral-800 -mt-10 relative z-20">
            <div class="max-w-6xl mx-auto px-4">
                <div class="flex gap-0 overflow-x-auto text-sm font-semibold tracking-wide">
                    <a href="{{ request()->fullUrlWithQuery(['sekme' => 'urunler']) }}"
                       class="px-5 py-3.5 whitespace-nowrap {{ $tab === 'urunler' ? 'text-white border-b-2 border-emerald-400' : 'text-white/60 hover:text-white' }}">ÜRÜNLER</a>
                    <a href="{{ request()->fullUrlWithQuery(['sekme' => 'ozellikler']) }}"
                       class="px-5 py-3.5 whitespace-nowrap {{ $tab === 'ozellikler' ? 'text-white border-b-2 border-emerald-400' : 'text-white/60 hover:text-white' }}">GENEL ÖZELLİKLER</a>
                    <a href="{{ request()->fullUrlWithQuery(['sekme' => 'dokumanlar']) }}"
                       class="px-5 py-3.5 whitespace-nowrap {{ $tab === 'dokumanlar' ? 'text-white border-b-2 border-emerald-400' : 'text-white/60 hover:text-white' }}">DOKÜMANLAR</a>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if($tab === 'ozellikler')
            <div class="max-w-4xl space-y-10">
                @forelse($features as $feature)
                    <section>
                        <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100 mb-4">{{ $feature['title'] ?? '' }}</h2>
                        <div class="space-y-6">
                            @foreach($feature['blocks'] ?? [] as $block)
                                <div>
                                    @if(!empty($block['heading']))
                                        <h3 class="text-sm font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400 mb-2">{{ $block['heading'] }}</h3>
                                    @endif
                                    <p class="text-sm sm:text-base text-neutral-600 dark:text-neutral-400 leading-relaxed">{{ $block['text'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @empty
                    @if(!empty($category['description']))
                        <p class="text-sm sm:text-base text-neutral-600 dark:text-neutral-400 leading-relaxed">{{ $category['description'] }}</p>
                    @else
                        <p class="text-neutral-500">Bu kategori için özellik bilgisi henüz eklenmedi.</p>
                    @endif
                @endforelse
            </div>
        @elseif($tab === 'dokumanlar')
            @if(count($documents) === 0)
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 p-10 text-center text-neutral-500">
                    Bu kategori için doküman henüz eklenmedi.
                </div>
            @else
                <div class="grid sm:grid-cols-2 gap-3 max-w-3xl">
                    @foreach($documents as $doc)
                        <a href="{{ $assetBase.'/'.$doc['file'] }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 px-4 py-3.5 hover:border-emerald-500/50 hover:shadow-sm transition-all">
                            <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $doc['title'] ?? 'Doküman' }}</span>
                            <span class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400 shrink-0">İndir</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @else
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            {{-- Sidebar filtre --}}
            <aside class="lg:col-span-3 mb-8 lg:mb-0">
                <div class="lg:sticky lg:top-20 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 p-5">
                    <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Filtre</h2>

                    <form method="GET" action="{{ route('catalog.category', [$manufacturer['slug'], $category['slug']]) }}" class="space-y-5" id="catalog-filter">
                        <input type="hidden" name="gorunum" value="{{ $viewMode }}">
                        <input type="hidden" name="sekme" value="urunler">

                        <div>
                            <label for="q" class="sr-only">Ürün ara</label>
                            <div class="relative">
                                <input type="search" name="q" id="q" value="{{ $q }}" placeholder="Ürün ara"
                                       class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-sm pl-3 pr-9 py-2.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                                <button type="submit" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-emerald-600" aria-label="Ara">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-2">Kategoriler</div>
                            <ul class="space-y-1 text-sm">
                                @foreach($siblingCategories as $sib)
                                    @php $isActive = ($sib['slug'] ?? '') === ($category['slug'] ?? ''); @endphp
                                    <li>
                                        @if(!empty($sib['available']) || $isActive)
                                            <a href="{{ route('catalog.category', [$manufacturer['slug'], $sib['slug']]) }}"
                                               class="flex items-center justify-between gap-2 py-1.5 {{ $isActive ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-neutral-600 dark:text-neutral-300 hover:text-emerald-600' }}">
                                                <span>{{ $sib['name'] }}</span>
                                                @if(!empty($sib['is_new']))
                                                    <span class="text-[10px] font-bold uppercase tracking-wide bg-keas-800 text-white px-1.5 py-0.5 rounded-sm">Yeni</span>
                                                @endif
                                            </a>
                                        @else
                                            <span class="flex items-center justify-between gap-2 py-1.5 text-neutral-400 dark:text-neutral-600 cursor-default" title="Yakında eklenecek">
                                                <span>{{ $sib['name'] }}</span>
                                                @if(!empty($sib['is_new']))
                                                    <span class="text-[10px] font-bold uppercase tracking-wide bg-keas-800/60 text-white px-1.5 py-0.5 rounded-sm">Yeni</span>
                                                @endif
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @if(count($brands) > 0)
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-2">Markalar</div>
                                <ul class="space-y-1 text-sm">
                                    <li>
                                        <a href="{{ request()->fullUrlWithQuery(['marka' => null]) }}"
                                           class="block py-1 {{ $selectedBrand === '' ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-neutral-600 dark:text-neutral-300 hover:text-emerald-600' }}">
                                            Tümü
                                        </a>
                                    </li>
                                    @foreach($brands as $b)
                                        <li>
                                            <a href="{{ request()->fullUrlWithQuery(['marka' => $b]) }}"
                                               class="block py-1 {{ $selectedBrand === $b ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-neutral-600 dark:text-neutral-300 hover:text-emerald-600' }}">
                                                {{ $b }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(count($seriesList ?? []) > 0)
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-2">Seriler</div>
                                <ul class="space-y-1 text-sm">
                                    <li>
                                        <a href="{{ request()->fullUrlWithQuery(['seri' => null]) }}"
                                           class="block py-1 {{ ($selectedSeries ?? '') === '' ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-neutral-600 dark:text-neutral-300 hover:text-emerald-600' }}">
                                            Tümü
                                        </a>
                                    </li>
                                    @foreach($seriesList as $s)
                                        <li>
                                            <a href="{{ request()->fullUrlWithQuery(['seri' => $s]) }}"
                                               class="block py-1 {{ ($selectedSeries ?? '') === $s ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-neutral-600 dark:text-neutral-300 hover:text-emerald-600' }}">
                                                {{ $s }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-300 cursor-pointer">
                            <input type="checkbox" name="yeni" value="1" @checked($onlyNew)
                                   class="rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500"
                                   onchange="this.form.submit()">
                            Sadece yeniler
                        </label>

                        @if($q !== '' || $selectedBrand !== '' || ($selectedSeries ?? '') !== '' || $onlyNew)
                            <a href="{{ route('catalog.category', [$manufacturer['slug'], $category['slug']]) }}"
                               class="block text-center text-sm font-medium text-emerald-600 hover:text-emerald-700 py-2 border border-emerald-200 dark:border-emerald-900 rounded-lg">
                                Filtreleri temizle
                            </a>
                        @endif
                    </form>
                </div>
            </aside>

            {{-- Product grid --}}
            <div class="lg:col-span-9">
                @if(!empty($specs) || !empty($category['catalog_pdf']))
                    <div class="mb-5 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900/80 p-4 sm:p-5">
                        @if(!empty($specs))
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
                                @foreach($specs as $spec)
                                    <div>
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-neutral-400">{{ $spec['label'] ?? '' }}</div>
                                        <div class="mt-0.5 text-sm font-medium text-neutral-800 dark:text-neutral-100">{{ $spec['value'] ?? '' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($category['catalog_pdf']))
                            <a href="{{ $assetBase.'/'.$category['catalog_pdf'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Resmi Glossmax Pro kataloğunu indir (PDF)
                            </a>
                        @endif
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $filteredCount }}</span> ürün listelendi
                        @if($filteredCount !== $totalCount)
                            <span class="text-neutral-400">({{ $totalCount }} toplam)</span>
                        @endif
                    </p>
                    <div class="flex items-center gap-1 text-sm">
                        <a href="{{ request()->fullUrlWithQuery(['gorunum' => 'dekor']) }}"
                           class="px-3 py-1.5 rounded-lg {{ $viewMode === 'dekor' ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 font-medium' : 'text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800' }}">
                            Dekor Görünümü
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['gorunum' => 'ic-mekan']) }}"
                           class="px-3 py-1.5 rounded-lg {{ $viewMode === 'ic-mekan' ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 font-medium' : 'text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800' }}">
                            İç Mekan Görünümü
                        </a>
                    </div>
                </div>

                @if(count($products) === 0)
                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 p-10 text-center text-neutral-500">
                        Aramanıza uygun ürün bulunamadı.
                    </div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                        @foreach($products as $i => $product)
                            @php
                                $img = null;
                                if ($viewMode === 'ic-mekan' && !empty($product['image_interior'])) {
                                    $img = $assetBase.'/'.$product['image_interior'];
                                } elseif (!empty($product['image'])) {
                                    $img = $assetBase.'/'.$product['image'];
                                } elseif (!empty($product['image_interior'])) {
                                    $img = $assetBase.'/'.$product['image_interior'];
                                }
                            @endphp
                            <article class="swatch-card fade-up group rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 overflow-hidden hover:shadow-lg hover:border-neutral-300 dark:hover:border-neutral-700 transition-all"
                                     style="animation-delay: {{ min($i * 20, 400) }}ms"
                                     data-code="{{ $product['code'] }}"
                                     data-name="{{ $product['name'] }}"
                                     data-brand="{{ $product['brand'] }}"
                                     data-img="{{ $img }}">
                                <button type="button" class="block w-full text-left open-swatch" aria-label="{{ $product['name'] }} detay">
                                    <div class="relative aspect-square bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                                        @if(!empty($product['is_new']))
                                            <span class="absolute top-2 left-2 z-10 text-[10px] font-bold uppercase tracking-wide bg-keas-800 text-white px-1.5 py-0.5">Yeni</span>
                                        @endif
                                        @if($img)
                                            <img src="{{ $img }}" alt="{{ $product['name'] }}" loading="lazy"
                                                 class="swatch-img w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-neutral-400 text-xs">Görsel yok</div>
                                        @endif
                                    </div>
                                    <div class="p-3 sm:p-3.5">
                                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 leading-snug">{{ $product['name'] }}</h3>
                                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $product['line'] ?? ($product['code'].' - '.$product['brand']) }}</p>
                                        @if(!empty($product['series']))
                                            <p class="mt-1 text-[11px] font-medium text-emerald-700/80 dark:text-emerald-400/80">{{ $product['series'] }}</p>
                                        @endif
                                        <p class="mt-1 text-[11px] text-neutral-400">{{ $manufacturer['name'] }}</p>
                                    </div>
                                </button>
                            </article>
                        @endforeach
                    </div>
                @endif

                @if(!empty($category['description']))
                    <div class="mt-10 pt-8 border-t border-neutral-200 dark:border-neutral-800">
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-2">{{ $category['name'] }}</h2>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed max-w-3xl">{{ $category['description'] }}</p>
                        @if(!empty($category['source_url']))
                            <p class="mt-3 text-xs text-neutral-400">
                                Kaynak:
                                <a href="{{ $category['source_url'] }}" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:underline">{{ $manufacturer['name'] }} ürün sayfası</a>
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @endif
    </main>

    {{-- Lightbox --}}
    @if($tab === 'urunler')
    <div id="swatch-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/70 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="swatch-modal-title">
        <div class="relative w-full max-w-lg bg-white dark:bg-neutral-900 rounded-2xl overflow-hidden shadow-2xl">
            <button type="button" id="swatch-modal-close" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-black/50 text-white hover:bg-black/70 flex items-center justify-center" aria-label="Kapat">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="aspect-square bg-neutral-100 dark:bg-neutral-800">
                <img id="swatch-modal-img" src="" alt="" class="w-full h-full object-cover">
            </div>
            <div class="p-5">
                <h3 id="swatch-modal-title" class="text-lg font-semibold text-neutral-900 dark:text-neutral-100"></h3>
                <p id="swatch-modal-meta" class="mt-1 text-sm text-neutral-500"></p>
                <p class="mt-2 text-xs text-neutral-400">{{ $manufacturer['name'] }} · {{ $category['name'] }}</p>
                <button type="button" id="swatch-copy-code" class="mt-4 w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                    Kodu kopyala
                </button>
            </div>
        </div>
    </div>
    @endif

    @include('catalog.partials.footer')

    @if($tab === 'urunler')
    <script>
        (function () {
            var modal = document.getElementById('swatch-modal');
            var img = document.getElementById('swatch-modal-img');
            var title = document.getElementById('swatch-modal-title');
            var meta = document.getElementById('swatch-modal-meta');
            var copyBtn = document.getElementById('swatch-copy-code');
            var currentCode = '';

            function openModal(card) {
                currentCode = card.getAttribute('data-code') || '';
                title.textContent = card.getAttribute('data-name') || '';
                meta.textContent = (card.getAttribute('data-code') || '') + ' · ' + (card.getAttribute('data-brand') || '');
                img.src = card.getAttribute('data-img') || '';
                img.alt = title.textContent;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
                img.src = '';
            }

            document.querySelectorAll('.open-swatch').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var card = btn.closest('.swatch-card');
                    if (card) openModal(card);
                });
            });

            document.getElementById('swatch-modal-close')?.addEventListener('click', closeModal);
            modal?.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeModal();
            });

            copyBtn?.addEventListener('click', function () {
                if (!currentCode) return;
                var label = copyBtn.textContent;
                navigator.clipboard.writeText(currentCode).then(function () {
                    copyBtn.textContent = 'Kopyalandı: ' + currentCode;
                    setTimeout(function () { copyBtn.textContent = label; }, 1600);
                }).catch(function () {
                    copyBtn.textContent = currentCode;
                });
            });
        })();
    </script>
    @endif
</body>
</html>
