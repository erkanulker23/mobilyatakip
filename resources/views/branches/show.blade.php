@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::branch($branch))
@section('content')
@php
    $thisMonth = $monthlyPerformance['thisMonth'] ?? null;
    $lastMonth = $monthlyPerformance['lastMonth'] ?? null;
    $overdueDueCount = $upcomingDueSales->filter(fn ($sale) => $sale->dueDate && $sale->dueDate->lt(now()->startOfDay()))->count();
    $collectedTotal = max(0, (float) ($salesStats->total ?? 0) - (float) ($salesStats->receivable ?? 0));
    $collectionRate = ($salesStats->total ?? 0) > 0
        ? min(100, round(($collectedTotal / $salesStats->total) * 100))
        : 0;
@endphp

<div class="mb-5">
    <div class="flex items-start justify-between flex-wrap gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                <a href="{{ route('branches.index') }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Şubeler</a>
                <span>/</span>
                <span class="text-neutral-700 dark:text-neutral-300 truncate">{{ $branch->name }}</span>
            </div>
            <h1 class="page-title">{{ $branch->name }}</h1>
            <p class="page-desc">
                @if($branch->code)
                    Kod: {{ $branch->code }} ·
                @endif
                Personel, satış, teklif ve SSH özeti
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($branch->phone)
            <a href="tel:{{ preg_replace('/\s+/', '', $branch->phone) }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                Ara
            </a>
            @endif
            <a href="{{ route('reports.branches', ['branchId' => $branch->id, 'period' => 'this_month']) }}" class="btn-secondary">Rapor</a>
            <a href="{{ route('sales.index', ['branchId' => $branch->id]) }}" class="btn-secondary">Satışlar</a>
            <a href="{{ route('branches.edit', $branch) }}" class="btn-edit">Düzenle</a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
@endif

{{-- Hero KPI --}}
<div class="card overflow-hidden mb-5">
    <div class="p-5 sm:p-6 flex flex-col lg:flex-row gap-6 lg:items-center">
        <div class="flex items-center gap-4 min-w-0 lg:w-[20rem] shrink-0">
            <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60 flex items-center justify-center text-2xl font-bold text-emerald-700 dark:text-emerald-300 shrink-0">
                {{ mb_strtoupper(mb_substr($branch->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-lg font-semibold text-neutral-900 dark:text-white truncate">{{ $branch->name }}</p>
                <p class="text-sm text-neutral-500 truncate">{{ $branch->code ? 'Kod: '.$branch->code : 'Şube' }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @if($branch->isActive)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-neutral-100 text-neutral-600 dark:bg-slate-700 dark:text-slate-300">Pasif</span>
                    @endif
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-sky-50 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">{{ $activePersonnelCount }} aktif personel</span>
                </div>
            </div>
        </div>

        <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-3 min-w-0">
            <div class="rounded-xl border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/50 dark:bg-emerald-950/20 p-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700/80 dark:text-emerald-300/80">Bu ay ciro</p>
                <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-emerald-700 dark:text-emerald-300">₺{{ number_format($thisMonth['total'] ?? 0, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-emerald-700/70 dark:text-emerald-400/70">{{ $thisMonth['count'] ?? 0 }} satış · {{ $thisMonth['label'] ?? '' }}</p>
            </div>
            <div class="rounded-xl border border-amber-200/80 dark:border-amber-800/50 bg-amber-50/50 dark:bg-amber-950/20 p-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-300">Bu ay alınacak</p>
                <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-300">₺{{ number_format($thisMonth['receivable'] ?? 0, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-amber-800/70 dark:text-amber-300/70">Bu ayki satışlardan</p>
            </div>
            <a href="#personel" class="rounded-xl border border-neutral-200 dark:border-slate-700 bg-neutral-50/70 dark:bg-slate-900/40 p-3.5 hover:bg-neutral-100/80 dark:hover:bg-slate-800/50 transition-colors">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Personel</p>
                <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-neutral-900 dark:text-white">{{ $branch->personnel_count }}</p>
                <p class="mt-1 text-xs text-neutral-500">{{ $activePersonnelCount }} aktif</p>
            </a>
            <a href="#termin" class="rounded-xl border border-red-200/80 dark:border-red-800/50 bg-red-50/40 dark:bg-red-950/20 p-3.5 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700 dark:text-red-300">Yakın termin</p>
                <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-red-700 dark:text-red-300">{{ $upcomingDueSales->count() }}</p>
                <p class="mt-1 text-xs text-red-700/70 dark:text-red-300/70">
                    @if($overdueDueCount > 0)
                        {{ $overdueDueCount }} gecikmiş
                    @else
                        7 gün içinde
                    @endif
                </p>
            </a>
        </div>
    </div>

    <div class="px-5 sm:px-6 py-3 border-t border-neutral-100 dark:border-slate-800 flex flex-wrap gap-x-5 gap-y-2 text-sm">
        @if($branch->phone)
            <a href="tel:{{ preg_replace('/\s+/', '', $branch->phone) }}" class="inline-flex items-center gap-1.5 text-neutral-700 dark:text-neutral-300 hover:text-emerald-600">
                <span class="text-neutral-400">Tel</span>
                <span class="font-medium">{{ $branch->phone }}</span>
            </a>
        @endif
        @if($branch->full_address)
            <span class="inline-flex items-start gap-1.5 text-neutral-700 dark:text-neutral-300 min-w-0">
                <span class="text-neutral-400 shrink-0">Adres</span>
                <span class="font-medium">{{ $branch->full_address }}</span>
            </span>
        @endif
        <span class="inline-flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400">
            <span class="text-neutral-400">Toplam ciro</span>
            <span class="font-medium tabular-nums text-neutral-800 dark:text-neutral-200">₺{{ number_format($salesStats->total, 0, ',', '.') }}</span>
            <span class="text-neutral-400">· %{{ $collectionRate }} tahsil</span>
        </span>
    </div>
</div>

{{-- Bölüm menüsü --}}
<nav class="mb-6 -mx-1 px-1 overflow-x-auto">
    <div class="inline-flex min-w-full sm:min-w-0 items-center gap-1 p-1 rounded-xl bg-neutral-100/80 dark:bg-slate-900/60 border border-neutral-200/80 dark:border-slate-800">
        <a href="#ozet" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">Özet</a>
        <a href="#personel" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">
            Personel
            @if($branch->personnel_count > 0)
            <span class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200">{{ $branch->personnel_count }}</span>
            @endif
        </a>
        <a href="#termin" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">
            Termin
            @if($upcomingDueSales->isNotEmpty())
            <span class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] font-semibold {{ $overdueDueCount > 0 ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200' }}">{{ $upcomingDueSales->count() }}</span>
            @endif
        </a>
        <a href="#satislar" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">Satışlar</a>
        <a href="#teklifler" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">Teklifler</a>
        <a href="#ssh" class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-white dark:hover:bg-slate-800 transition-colors">SSH</a>
    </div>
</nav>

{{-- Özet kartlar --}}
<div id="ozet" class="scroll-mt-24 grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <a href="{{ route('sales.index', ['branchId' => $branch->id]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Satış</p>
        <p class="mt-2 text-2xl font-bold tabular-nums text-neutral-900 dark:text-white">{{ $branch->sales_count }}</p>
        <p class="mt-1 text-xs text-neutral-500">{{ $salesStats->activeCount }} aktif · ₺{{ number_format($salesStats->total, 0, ',', '.') }}</p>
    </a>
    <a href="{{ route('quotes.index', ['branchId' => $branch->id]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Teklif</p>
        <p class="mt-2 text-2xl font-bold tabular-nums text-neutral-900 dark:text-white">{{ $branch->quotes_count }}</p>
        <p class="mt-1 text-xs text-neutral-500">Tüm teklifler</p>
    </a>
    <a href="{{ route('service-tickets.index', ['branchId' => $branch->id]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">SSH</p>
        <p class="mt-2 text-2xl font-bold tabular-nums text-neutral-900 dark:text-white">{{ $branch->service_tickets_count }}</p>
        <p class="mt-1 text-xs text-neutral-500">Servis kayıtları</p>
    </a>
    <div class="card p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Alınacak</p>
        <p class="mt-2 text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-300">₺{{ number_format($salesStats->receivable, 0, ',', '.') }}</p>
        <p class="mt-1 text-xs text-neutral-500">Tüm dönem kalan</p>
    </div>
</div>

{{-- Aylık karşılaştırma --}}
@if($monthlyPerformance)
<div class="card overflow-hidden mb-6">
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Aylık Performans</h2>
            <p class="text-sm text-neutral-500 mt-1">Geçen ay ↔ bu ay satış karşılaştırması</p>
        </div>
        @if(($monthlyPerformance['totalChange'] ?? 0) != 0)
            @include('partials.performance-change-badge', ['change' => $monthlyPerformance['totalChange']])
        @endif
    </div>
    <div class="p-5 sm:p-6 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wider text-neutral-500 border-b border-neutral-100 dark:border-slate-800">
                    <th class="py-2 pr-4 font-semibold">Metrik</th>
                    <th class="py-2 pr-4 font-semibold">{{ $lastMonth['label'] ?? 'Geçen ay' }}</th>
                    <th class="py-2 pr-4 font-semibold text-emerald-700 dark:text-emerald-300">{{ $thisMonth['label'] ?? 'Bu ay' }}</th>
                    <th class="py-2 font-semibold">Değişim</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-800">
                <tr>
                    <td class="py-3 pr-4 text-neutral-600 dark:text-slate-400">Satış adedi</td>
                    <td class="py-3 pr-4 tabular-nums font-medium">{{ $lastMonth['count'] ?? 0 }}</td>
                    <td class="py-3 pr-4 tabular-nums font-semibold">{{ $thisMonth['count'] ?? 0 }}</td>
                    <td class="py-3">@include('partials.performance-change-badge', ['change' => $monthlyPerformance['countChange']])</td>
                </tr>
                <tr>
                    <td class="py-3 pr-4 text-neutral-600 dark:text-slate-400">Ciro</td>
                    <td class="py-3 pr-4 tabular-nums font-medium">₺{{ number_format($lastMonth['total'] ?? 0, 0, ',', '.') }}</td>
                    <td class="py-3 pr-4 tabular-nums font-semibold text-emerald-700 dark:text-emerald-300">₺{{ number_format($thisMonth['total'] ?? 0, 0, ',', '.') }}</td>
                    <td class="py-3">@include('partials.performance-change-badge', ['change' => $monthlyPerformance['totalChange']])</td>
                </tr>
                <tr>
                    <td class="py-3 pr-4 text-neutral-600 dark:text-slate-400">Alınacak</td>
                    <td class="py-3 pr-4 tabular-nums font-medium">₺{{ number_format($lastMonth['receivable'] ?? 0, 0, ',', '.') }}</td>
                    <td class="py-3 pr-4 tabular-nums font-semibold text-amber-700 dark:text-amber-300">₺{{ number_format($thisMonth['receivable'] ?? 0, 0, ',', '.') }}</td>
                    <td class="py-3 text-neutral-400">—</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Personel --}}
<div id="personel" class="card overflow-hidden mb-6 scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Görevli Personel</h2>
            <p class="text-sm text-neutral-500 mt-1">{{ $activePersonnelCount }} aktif · {{ $branch->personnel_count }} toplam</p>
        </div>
        <a href="{{ route('personnel.index', ['branchId' => $branch->id]) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 shrink-0">Tümü →</a>
    </div>
    <div class="divide-y divide-neutral-100 dark:divide-slate-800">
        @forelse($branchPersonnel as $person)
        <a href="{{ route('personnel.show', $person) }}" class="flex items-center gap-3 px-5 sm:px-6 py-3 hover:bg-neutral-50 dark:hover:bg-slate-800/40 transition-colors {{ $person->isActive ? '' : 'opacity-60' }}">
            @if($person->photoUrl)
                <img src="{{ storage_url($person->photoUrl) }}" alt="" class="h-10 w-10 rounded-xl object-cover border border-neutral-200 dark:border-slate-700 shrink-0">
            @else
                <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-sm font-semibold text-slate-500 shrink-0">
                    {{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <p class="font-medium text-neutral-900 dark:text-white truncate">{{ $person->name }}</p>
                <p class="text-xs text-neutral-500 truncate">{{ $person->title ?: \App\Support\PersonnelCategory::label($person->category) }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($person->phone)
                <span class="hidden sm:inline text-xs text-neutral-500">{{ $person->phone }}</span>
                @endif
                @if(! $person->isActive)
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-500 dark:bg-neutral-800">Pasif</span>
                @endif
            </div>
        </a>
        @empty
        <p class="px-6 py-10 text-sm text-neutral-500 text-center">Bu şubeye henüz personel atanmamış.</p>
        @endforelse
    </div>
</div>

{{-- Termin --}}
<div id="termin" class="card overflow-hidden mb-6 scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b {{ $overdueDueCount > 0 ? 'border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-950/25' : 'border-amber-200 dark:border-amber-900/40 bg-amber-50 dark:bg-amber-950/25' }} flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Yaklaşan Terminler</h2>
            <p class="text-sm text-neutral-600 dark:text-slate-400 mt-1">Önümüzdeki 7 gün · gecikenler dahil</p>
        </div>
        @if($upcomingDueSales->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @if($overdueDueCount > 0)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">{{ $overdueDueCount }} gecikmiş</span>
            @endif
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ $upcomingDueSales->count() }} sipariş</span>
        </div>
        @endif
    </div>
    <div class="overflow-x-auto">
        @if($upcomingDueSales->isEmpty())
        <div class="px-6 py-10 text-center text-sm text-neutral-500">Önümüzdeki 7 gün içinde termin yok.</div>
        @else
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">Sipariş</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Termin</th>
                    <th class="table-th">Kalan</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th text-right w-20">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @foreach($upcomingDueSales as $sale)
                @php($daysLeft = $sale->dueDate ? (int) now()->startOfDay()->diffInDays($sale->dueDate, false) : null)
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40 {{ $daysLeft !== null && $daysLeft < 0 ? 'bg-red-50/40 dark:bg-red-950/10' : '' }}">
                    <td class="table-td font-medium">
                        <a href="{{ route('sales.show', $sale) }}" class="hover:text-emerald-600">{{ $sale->saleNumber }}</a>
                    </td>
                    <td class="table-td">
                        {{ $sale->customer?->name ?? '—' }}
                        @if($sale->customer?->phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $sale->customer->phone) }}" class="block text-xs text-neutral-500 hover:text-emerald-600">{{ $sale->customer->phone }}</a>
                        @endif
                    </td>
                    <td class="table-td {{ $daysLeft !== null && $daysLeft < 0 ? 'text-red-600 font-medium' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600 font-medium' : '') }}">{{ $sale->dueDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td {{ $daysLeft !== null && $daysLeft < 0 ? 'text-red-600 font-medium' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600 font-medium' : 'text-neutral-600') }}">
                        @if($daysLeft === null)—
                        @elseif($daysLeft < 0){{ abs($daysLeft) }} gün gecikti
                        @elseif($daysLeft === 0)Bugün
                        @else{{ $daysLeft }} gün
                        @endif
                    </td>
                    <td class="table-td text-right tabular-nums font-medium">₺{{ number_format($sale->grandTotal ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td text-right"><a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Gör</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Son satışlar --}}
<div id="satislar" class="card overflow-hidden mb-6 scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Son Satışlar</h2>
            <p class="text-sm text-neutral-500 mt-1">Son {{ $recentSales->count() }} kayıt</p>
        </div>
        <a href="{{ route('sales.index', ['branchId' => $branch->id]) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 shrink-0">Tümü →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">Sipariş</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th">Ödeme</th>
                    <th class="table-th text-right w-20">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @forelse($recentSales as $sale)
                @php($saleStatus = \App\Support\CustomerBalance::saleStatus($sale))
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40 {{ ($sale->isCancelled ?? false) ? 'opacity-60' : '' }}">
                    <td class="table-td font-medium">
                        <a href="{{ route('sales.show', $sale) }}" class="hover:text-emerald-600">{{ $sale->saleNumber }}</a>
                        @if($sale->isCancelled ?? false)
                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md bg-red-50 text-red-600 font-medium">İptal</span>
                        @endif
                    </td>
                    <td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>
                    <td class="table-td text-neutral-600">{{ $sale->saleDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td text-right tabular-nums font-medium">₺{{ number_format($sale->grandTotal ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td">
                        @if(!($sale->isCancelled ?? false))
                        @include('partials.payment-status-badge', ['status' => $saleStatus])
                        @else—
                        @endif
                    </td>
                    <td class="table-td text-right"><a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Gör</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-neutral-500">Bu şubeye henüz satış bağlanmamış.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Teklifler --}}
<div id="teklifler" class="card overflow-hidden mb-6 scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Son Teklifler</h2>
            <p class="text-sm text-neutral-500 mt-1">Son {{ $recentQuotes->count() }} kayıt</p>
        </div>
        <a href="{{ route('quotes.index', ['branchId' => $branch->id]) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 shrink-0">Tümü →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th text-right">Tutar</th>
                    <th class="table-th">Tarih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @forelse($recentQuotes as $quote)
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40">
                    <td class="table-td"><a href="{{ route('quotes.show', $quote) }}" class="font-medium text-emerald-600 hover:text-emerald-700">{{ $quote->quoteNumber }}</a></td>
                    <td class="table-td">{{ $quote->customer?->name ?? '—' }}</td>
                    <td class="table-td text-right tabular-nums font-medium">₺{{ number_format($quote->grandTotal ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td text-neutral-600">{{ $quote->createdAt?->format('d.m.Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-neutral-500">Bu şubeye henüz teklif bağlanmamış.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- SSH --}}
<div id="ssh" class="card overflow-hidden scroll-mt-24">
    <div class="px-5 sm:px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Son SSH Kayıtları</h2>
            <p class="text-sm text-neutral-500 mt-1">Son {{ $recentTickets->count() }} kayıt</p>
        </div>
        <a href="{{ route('service-tickets.index', ['branchId' => $branch->id]) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 shrink-0">Tümü →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Durum</th>
                    <th class="table-th">Açılış</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @forelse($recentTickets as $ticket)
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40">
                    <td class="table-td"><a href="{{ route('service-tickets.show', $ticket) }}" class="font-medium text-emerald-600 hover:text-emerald-700">{{ $ticket->ticketNumber }}</a></td>
                    <td class="table-td">{{ $ticket->customer?->name ?? '—' }}</td>
                    <td class="table-td text-sm">{{ \App\Support\ServiceTicketStatus::label($ticket->status) }}</td>
                    <td class="table-td text-neutral-600">{{ $ticket->openedAt?->format('d.m.Y') ?? $ticket->createdAt?->format('d.m.Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-neutral-500">Bu şubeye henüz SSH bağlanmamış.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
