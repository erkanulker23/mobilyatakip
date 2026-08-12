@extends('layouts.app')
@section('title', 'Raporlar')
@section('content')
@php
    $upcomingTotal = $upcomingSalesCount + $upcomingTicketsCount;
    $reports = [
        [
            'category' => 'operasyon',
            'title' => 'Termin Tarihi Yaklaşanlar',
            'desc' => 'Önümüzdeki 14 gün içinde teslim veya SSH termin tarihi gelen kayıtlar.',
            'route' => 'reports.upcoming-due',
            'icon' => 'clock',
            'tone' => 'amber',
            'stat' => $upcomingTotal > 0 ? $upcomingTotal . ' kayıt' : 'Kayıt yok',
            'statHint' => $overdueSalesCount > 0 ? $overdueSalesCount . ' gecikmiş sipariş' : 'Gecikme yok',
            'keywords' => 'termin teslimat ssh servis yaklaşan gecikme',
        ],
        [
            'category' => 'satis',
            'title' => 'Satış Raporu',
            'desc' => 'Tarih aralığına göre satış listesi, ciro, tahsilat ve kalan tutarlar.',
            'route' => 'reports.sales',
            'icon' => 'document',
            'tone' => 'blue',
            'stat' => '₺' . number_format($monthlySales, 0, ',', '.'),
            'statHint' => $monthlySalesCount . ' satış · bu ay',
            'keywords' => 'satış fatura ciro sipariş liste',
        ],
        [
            'category' => 'satis',
            'title' => 'Şube Raporu',
            'desc' => 'Şube bazında sipariş cirosu, tahsilat ve SSH (açık / tamamlanan) özeti.',
            'route' => 'reports.branches',
            'icon' => 'building',
            'tone' => 'teal',
            'stat' => count($branchReports ?? []) . ' şube',
            'statHint' => 'Sipariş ve SSH karşılaştırması',
            'keywords' => 'şube sipariş ssh servis ciro tahsilat',
        ],
        [
            'category' => 'finans',
            'title' => 'Gelir – Gider Raporu',
            'desc' => 'Seçilen dönemde satış geliri, tahsilat, gider ve tedarikçi ödemeleri.',
            'route' => 'reports.income-expense',
            'icon' => 'chart',
            'tone' => 'emerald',
            'stat' => '₺' . number_format($incomeExpense['tahsilat'], 0, ',', '.'),
            'statHint' => 'Bu ay tahsilat',
            'keywords' => 'gelir gider nakit akış tahsilat expense',
        ],
        [
            'category' => 'finans',
            'title' => 'KDV Raporu',
            'desc' => 'Satış, alış ve gider kalemlerine göre KDV özeti ve oran dağılımı.',
            'route' => 'reports.kdv',
            'icon' => 'calculator',
            'tone' => 'violet',
            'stat' => 'Oran bazlı',
            'statHint' => 'Dönemsel KDV özeti',
            'keywords' => 'kdv vergi matrah oran',
            'hidden' => ! \Illuminate\Support\Facades\Route::has('reports.kdv'),
        ],
        [
            'category' => 'cari',
            'title' => 'Müşteri Cari Hesap Özeti',
            'desc' => 'Müşteri bazında borç, tahsilat ve bakiye durumu; detay ekstre.',
            'route' => 'reports.customer-ledger',
            'icon' => 'users',
            'tone' => 'sky',
            'stat' => '₺' . number_format($customerReceivable, 0, ',', '.'),
            'statHint' => 'Toplam alacak (tahmini)',
            'keywords' => 'müşteri cari borç alacak tahsilat ekstre',
        ],
        [
            'category' => 'cari',
            'title' => 'Tedarikçi Cari Hesap Özeti',
            'desc' => 'Tedarikçi alışları, ödemeler ve güncel bakiye özeti.',
            'route' => 'reports.supplier-ledger',
            'icon' => 'building',
            'tone' => 'orange',
            'stat' => '₺' . number_format($supplierPayable, 0, ',', '.'),
            'statHint' => 'Toplam borç (tahmini)',
            'keywords' => 'tedarikçi cari alış ödeme borç',
        ],
    ];
    $reports = collect($reports)->reject(fn ($r) => ! empty($r['hidden']))->values();
    $categories = [
        'operasyon' => ['label' => 'Operasyon', 'desc' => 'Teslimat ve termin takibi'],
        'satis' => ['label' => 'Satış', 'desc' => 'Ciro ve sipariş analizi'],
        'finans' => ['label' => 'Finans', 'desc' => 'Gelir, gider ve vergi'],
        'cari' => ['label' => 'Cari Hesaplar', 'desc' => 'Müşteri ve tedarikçi bakiyeleri'],
    ];
@endphp

<div class="mb-8">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <h1 class="page-title">Raporlar</h1>
            <p class="page-desc">Mali raporlar, satış analizi ve operasyon takibi — {{ $monthLabel }}</p>
        </div>
        <div class="relative w-full lg:max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="search" id="reportSearch" placeholder="Rapor ara (termin, KDV, cari…)" class="form-input pl-10 min-h-[44px]" autocomplete="off">
        </div>
    </div>
</div>

{{-- Özet kartlar --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <a href="{{ route('reports.sales') }}" class="card p-5 hover:shadow-md hover:border-neutral-300 dark:hover:border-slate-600 transition-all group">
        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Bu Ay Satış</p>
        <p class="text-2xl font-semibold text-neutral-900 dark:text-white mt-1 tabular-nums group-hover:text-emerald-600 transition-colors">₺{{ number_format($monthlySales, 0, ',', '.') }}</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $monthlySalesCount }} sipariş</p>
    </a>
    <a href="{{ route('reports.income-expense') }}" class="card p-5 hover:shadow-md hover:border-neutral-300 dark:hover:border-slate-600 transition-all group">
        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Bu Ay Tahsilat</p>
        <p class="text-2xl font-semibold text-emerald-600 mt-1 tabular-nums">₺{{ number_format($incomeExpense['tahsilat'], 0, ',', '.') }}</p>
        <p class="text-xs text-neutral-400 mt-1">Net nakit: <span class="{{ $monthlyNetCash >= 0 ? 'text-emerald-600' : 'text-red-500' }} font-medium">₺{{ number_format($monthlyNetCash, 0, ',', '.') }}</span></p>
    </a>
    <a href="{{ route('reports.upcoming-due') }}" class="card p-5 hover:shadow-md hover:border-neutral-300 dark:hover:border-slate-600 transition-all group {{ $upcomingTotal > 0 ? 'ring-1 ring-amber-200 dark:ring-amber-800/50' : '' }}">
        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Yaklaşan Termin</p>
        <p class="text-2xl font-semibold {{ $upcomingTotal > 0 ? 'text-amber-600' : 'text-neutral-900 dark:text-white' }} mt-1 tabular-nums">{{ $upcomingTotal }}</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $upcomingSalesCount }} sipariş · {{ $upcomingTicketsCount }} SSH</p>
    </a>
    <a href="{{ route('reports.customer-ledger', ['tip' => 'borclu']) }}" class="card p-5 hover:shadow-md hover:border-neutral-300 dark:hover:border-slate-600 transition-all group">
        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Müşteri Alacağı</p>
        <p class="text-2xl font-semibold text-neutral-900 dark:text-white mt-1 tabular-nums group-hover:text-sky-600 transition-colors">₺{{ number_format($customerReceivable, 0, ',', '.') }}</p>
        <p class="text-xs text-neutral-400 mt-1">Tedarikçi borcu: ₺{{ number_format($supplierPayable, 0, ',', '.') }}</p>
    </a>
</div>

@if($overdueSalesCount > 0)
<div class="mb-6 p-4 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800/50 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-start gap-3">
        <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-900/40 shrink-0">
            <svg class="w-5 h-5 text-amber-700 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </span>
        <div>
            <p class="font-medium text-amber-900 dark:text-amber-200">{{ $overdueSalesCount }} siparişin termin tarihi geçmiş</p>
            <p class="text-sm text-amber-800/80 dark:text-amber-300/80 mt-0.5">Termin raporundan gecikmiş siparişleri inceleyebilirsiniz.</p>
        </div>
    </div>
    <a href="{{ route('reports.upcoming-due') }}" class="btn-secondary text-sm shrink-0 bg-white dark:bg-slate-800">Termin Raporu →</a>
</div>
@endif

@include('reports.partials.sales-stage-cards', ['salesStageReports' => $salesStageReports ?? []])
@include('reports.partials.branch-cards', ['branchReports' => $branchReports ?? []])

<div id="reportSections" class="space-y-8">
    @foreach($categories as $catKey => $cat)
    @php $catReports = $reports->where('category', $catKey); @endphp
    @if($catReports->isNotEmpty())
    <section class="report-section" data-category="{{ $catKey }}">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $cat['label'] }}</h2>
            <p class="text-sm text-neutral-500 dark:text-slate-400">{{ $cat['desc'] }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($catReports as $report)
            <a href="{{ route($report['route']) }}"
               class="report-card group card p-5 hover:shadow-md hover:border-neutral-300 dark:hover:border-slate-600 transition-all flex flex-col min-h-[168px]"
               data-search="{{ strtolower($report['title'] . ' ' . $report['desc'] . ' ' . ($report['keywords'] ?? '')) }}">
                <div class="flex items-start justify-between gap-3 mb-3">
                    @php
                        $tone = $report['tone'];
                        $iconBg = match($tone) {
                            'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
                            'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                            'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
                            'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400',
                            'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400',
                            'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
                            'teal' => 'bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
                            default => 'bg-neutral-100 text-neutral-600',
                        };
                    @endphp
                    <span class="w-11 h-11 rounded-xl {{ $iconBg }} flex items-center justify-center shrink-0">
                        @if($report['icon'] === 'clock')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        @elseif($report['icon'] === 'document')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        @elseif($report['icon'] === 'chart')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        @elseif($report['icon'] === 'calculator')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        @elseif($report['icon'] === 'users')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        @endif
                    </span>
                    <span class="text-neutral-300 dark:text-slate-600 group-hover:text-neutral-500 dark:group-hover:text-slate-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                    </span>
                </div>
                <h3 class="font-semibold text-neutral-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $report['title'] }}</h3>
                <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1 flex-1 leading-relaxed">{{ $report['desc'] }}</p>
                <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-slate-700 flex items-end justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-neutral-900 dark:text-white tabular-nums">{{ $report['stat'] }}</p>
                        <p class="text-xs text-neutral-400 mt-0.5">{{ $report['statHint'] }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif
    @endforeach
</div>

<div id="reportEmpty" class="hidden card p-12 text-center">
    <p class="text-neutral-500 dark:text-slate-400">Aramanızla eşleşen rapor bulunamadı.</p>
    <button type="button" id="reportSearchClear" class="mt-3 text-sm font-medium text-emerald-600 hover:text-emerald-700">Aramayı temizle</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('reportSearch');
    const empty = document.getElementById('reportEmpty');
    const sections = document.getElementById('reportSections');
    const clearBtn = document.getElementById('reportSearchClear');

    function filterReports() {
        const q = (input?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        document.querySelectorAll('.report-section').forEach(function(section) {
            let sectionVisible = 0;
            section.querySelectorAll('.report-card, .sales-stage-card').forEach(function(card) {
                const haystack = card.getAttribute('data-search') || '';
                const match = !q || haystack.includes(q);
                card.classList.toggle('hidden', !match);
                if (match) sectionVisible++;
            });
            section.classList.toggle('hidden', sectionVisible === 0);
            visibleCount += sectionVisible;
        });

        if (empty && sections) {
            empty.classList.toggle('hidden', visibleCount > 0);
            sections.classList.toggle('hidden', visibleCount === 0);
        }
    }

    input?.addEventListener('input', filterReports);
    clearBtn?.addEventListener('click', function() {
        if (input) input.value = '';
        filterReports();
        input?.focus();
    });
});
</script>
@endsection
