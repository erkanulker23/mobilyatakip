@php
    $cards = $branchReports ?? [];
@endphp
@if($cards !== [])
<section class="report-section mb-8" data-category="satis">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Şube Raporları</h2>
        <p class="text-sm text-neutral-500 dark:text-slate-400">Sipariş ve SSH kayıtlarını şubeye göre karşılaştırın</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($cards as $branch)
        @php
            $searchText = strtolower(($branch['label'] ?? '') . ' ' . ($branch['desc'] ?? '') . ' ' . ($branch['keywords'] ?? ''));
        @endphp
        <div class="report-card sales-stage-card card p-5 flex flex-col min-h-[168px]" data-search="{{ $searchText }}">
            <div class="flex items-start justify-between gap-3 mb-3">
                <span class="w-11 h-11 rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </span>
                <div class="text-right">
                    <p class="text-2xl font-semibold tabular-nums text-neutral-900 dark:text-white">{{ number_format($branch['salesCount'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-neutral-400">sipariş · bu ay</p>
                </div>
            </div>
            <h3 class="font-semibold text-neutral-900 dark:text-white">{{ $branch['label'] }}</h3>
            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1 flex-1 leading-relaxed">
                {{ $branch['sshCount'] ?? 0 }} SSH
                @if(($branch['openSsh'] ?? 0) > 0)
                    <span class="text-amber-600 dark:text-amber-400">· {{ $branch['openSsh'] }} açık</span>
                @endif
            </p>
            <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-slate-700 flex flex-wrap gap-2">
                <a href="{{ $branch['url'] }}" class="btn-secondary text-sm py-2">Listele</a>
                <a href="{{ $branch['printUrl'] }}" target="_blank" rel="noopener" class="btn-secondary text-sm py-2">Yazdır</a>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
