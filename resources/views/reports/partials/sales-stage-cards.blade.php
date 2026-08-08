@php
    $cards = $salesStageReports ?? [];
@endphp
@if($cards !== [])
<section class="report-section mb-8" data-category="operasyon">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Sipariş Aşama Raporları</h2>
        <p class="text-sm text-neutral-500 dark:text-slate-400">Güncel siparişleri aşamaya göre listele ve yazdır</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($cards as $stage)
        @php
            $tone = $stage['tone'] ?? 'neutral';
            $iconBg = match($tone) {
                'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
                'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400',
                'teal' => 'bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
                'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400',
                'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
                'red' => 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                default => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300',
            };
            $searchText = strtolower(($stage['label'] ?? '') . ' ' . ($stage['desc'] ?? '') . ' ' . ($stage['keywords'] ?? ''));
        @endphp
        <div class="report-card sales-stage-card card p-5 flex flex-col min-h-[168px] {{ ($stage['count'] ?? 0) > 0 ? '' : 'opacity-90' }}"
             data-search="{{ $searchText }}">
            <div class="flex items-start justify-between gap-3 mb-3">
                <span class="w-11 h-11 rounded-xl {{ $iconBg }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </span>
                <span class="text-2xl font-semibold tabular-nums text-neutral-900 dark:text-white">{{ number_format($stage['count'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <h3 class="font-semibold text-neutral-900 dark:text-white">{{ $stage['label'] }}</h3>
            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1 flex-1 leading-relaxed">{{ $stage['desc'] }}</p>
            <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-slate-700 flex flex-wrap gap-2">
                <a href="{{ $stage['listUrl'] }}" class="btn-secondary text-sm py-2">Listele</a>
                <a href="{{ $stage['printUrl'] }}" target="_blank" rel="noopener" class="btn-secondary text-sm py-2">Yazdır</a>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
