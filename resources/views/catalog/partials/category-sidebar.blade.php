<aside class="lg:col-span-3 mb-8 lg:mb-0">
    <div class="lg:sticky lg:top-20 space-y-4">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Filtre</h2>

            <form method="GET" action="{{ route('catalog.category', [$manufacturer['slug'], $category['slug']]) }}" class="space-y-4" id="catalog-filter">
                <input type="hidden" name="gorunum" value="{{ $viewMode }}">
                @if(($selectedTone ?? '') !== '')
                    <input type="hidden" name="ton" value="{{ $selectedTone }}">
                @endif
                @if($selectedBrand !== '')
                    <input type="hidden" name="marka" value="{{ $selectedBrand }}">
                @endif
                @if(($selectedSeries ?? '') !== '')
                    <input type="hidden" name="seri" value="{{ $selectedSeries }}">
                @endif

                <div>
                    <label for="q" class="block text-xs font-medium text-neutral-500 mb-1.5">Ürün ara</label>
                    <div class="relative">
                        <input type="search" name="q" id="q" value="{{ $q }}" placeholder="Ad veya kod"
                               class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-sm pl-3 pr-9 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                        <button type="submit" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-emerald-600" aria-label="Ara">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-medium text-neutral-500 mb-2">Ton</div>
                    <div class="flex flex-wrap gap-1.5">
                        <a href="{{ request()->fullUrlWithQuery(['ton' => null]) }}"
                           class="px-3 py-1.5 rounded-full text-xs font-medium border {{ ($selectedTone ?? '') === '' ? 'bg-emerald-600 text-white border-emerald-600' : 'border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 hover:border-emerald-400' }}">
                            Tümü
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['ton' => 'acik']) }}"
                           class="px-3 py-1.5 rounded-full text-xs font-medium border {{ ($selectedTone ?? '') === 'acik' ? 'bg-emerald-600 text-white border-emerald-600' : 'border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 hover:border-emerald-400' }}">
                            Açık modeller
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['ton' => 'koyu']) }}"
                           class="px-3 py-1.5 rounded-full text-xs font-medium border {{ ($selectedTone ?? '') === 'koyu' ? 'bg-emerald-600 text-white border-emerald-600' : 'border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 hover:border-emerald-400' }}">
                            Koyu modeller
                        </a>
                    </div>
                    <p class="mt-1.5 text-[11px] text-neutral-400 leading-snug">Desen adına göre otomatik sınıflama; ahşap desenler “Tümü”nde kalır.</p>
                </div>

                @if(count($brands) > 0)
                    <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800">
                        <div class="text-xs font-medium text-neutral-500 mb-2">Marka</div>
                        <ul class="space-y-0.5 text-sm">
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
                    <div class="pt-3 border-t border-neutral-100 dark:border-neutral-800">
                        <div class="text-xs font-medium text-neutral-500 mb-2">Seri</div>
                        <ul class="space-y-0.5 text-sm max-h-36 overflow-y-auto pr-1">
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

                <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-300 cursor-pointer pt-1">
                    <input type="checkbox" name="yeni" value="1" @checked($onlyNew)
                           class="rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500"
                           onchange="this.form.submit()">
                    Sadece yeniler
                </label>

                @if($q !== '' || $selectedBrand !== '' || ($selectedSeries ?? '') !== '' || $onlyNew || ($selectedTone ?? '') !== '')
                    <a href="{{ route('catalog.category', [$manufacturer['slug'], $category['slug']]) }}"
                       class="block text-center text-sm font-medium text-emerald-600 hover:text-emerald-700 py-2 border border-emerald-200 dark:border-emerald-900 rounded-lg">
                        Filtreleri temizle
                    </a>
                @endif
            </form>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/80 dark:bg-neutral-900/80 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-1">Üretici</div>
            <a href="{{ route('catalog.manufacturer', $manufacturer['slug']) }}"
               class="text-sm font-semibold text-neutral-800 dark:text-neutral-100 hover:text-emerald-600">
                {{ $manufacturer['name'] }}
            </a>
            <div class="mt-3 text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-2">Diğer ürün grupları</div>
            <nav class="max-h-[min(420px,50vh)] overflow-y-auto -mx-1 px-1 space-y-0.5" aria-label="Katalog kategorileri">
                @foreach($siblingCategories as $sib)
                    @php $isActive = ($sib['slug'] ?? '') === ($category['slug'] ?? ''); @endphp
                    @if(!empty($sib['available']) || $isActive)
                        <a href="{{ route('catalog.category', [$manufacturer['slug'], $sib['slug']]) }}"
                           class="block rounded-lg px-2.5 py-2 text-sm leading-snug {{ $isActive ? 'bg-emerald-600/10 text-emerald-800 dark:text-emerald-300 font-semibold border-l-2 border-emerald-500' : 'text-neutral-600 dark:text-neutral-400 hover:bg-white dark:hover:bg-neutral-800 hover:text-emerald-700' }}">
                            <span class="line-clamp-2">{{ $sib['name'] }}</span>
                            @if(!empty($sib['is_new']))
                                <span class="mt-1 inline-block text-[10px] font-bold uppercase tracking-wide bg-keas-800 text-white px-1.5 py-0.5 rounded-sm">Yeni</span>
                            @endif
                        </a>
                    @else
                        <span class="block rounded-lg px-2.5 py-2 text-sm leading-snug text-neutral-400 dark:text-neutral-600 cursor-default" title="Yakında">
                            <span class="line-clamp-2">{{ $sib['name'] }}</span>
                        </span>
                    @endif
                @endforeach
            </nav>
        </div>
    </div>
</aside>
