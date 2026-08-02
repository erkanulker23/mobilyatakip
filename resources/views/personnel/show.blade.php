@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::personnel($personnel))
@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('personnel.index') }}" class="hover:text-neutral-900 dark:hover:text-white">Personel</a>
                <span>/</span>
                @endif
                <span class="text-neutral-700 dark:text-neutral-300">{{ $personnel->name }}</span>
            </div>
            <h1 class="page-title">{{ $viewingOwnProfile ? 'Siparişlerim' : $personnel->name }}</h1>
            <p class="page-desc">
                @if($viewingOwnProfile)
                Size atanmış tüm siparişler
                @else
                {{ $personnel->title ?? 'Personel detayları' }} · {{ $salesStats->count }} sipariş
                @endif
            </p>
        </div>
        @if(auth()->user()?->isAdmin())
        <a href="{{ route('personnel.edit', $personnel) }}" class="btn-edit">Düzenle</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <div class="card p-6">
        <div class="flex justify-center mb-4">
            @if($personnel->photoUrl)
                <img src="{{ storage_url($personnel->photoUrl) }}" alt="{{ $personnel->name }}" class="h-28 w-28 rounded-full object-cover border border-neutral-200 dark:border-neutral-700">
            @else
                <div class="h-28 w-28 rounded-full bg-slate-100 dark:bg-slate-700 border border-neutral-200 dark:border-slate-600 flex items-center justify-center text-3xl font-semibold text-slate-400">
                    {{ mb_strtoupper(mb_substr($personnel->name, 0, 1)) }}
                </div>
            @endif
        </div>
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">{{ $personnel->name }}</h2>
        <dl class="space-y-3 text-sm">
            <div><dt class="text-neutral-500">E-posta</dt><dd class="font-medium text-neutral-900 dark:text-white">{{ $personnel->email ?: '—' }}</dd></div>
            <div><dt class="text-neutral-500">Telefon</dt><dd class="font-medium text-neutral-900 dark:text-white">{{ $personnel->phone ?: '—' }}</dd></div>
            <div><dt class="text-neutral-500">Unvan</dt><dd class="font-medium text-neutral-900 dark:text-white">{{ $personnel->title ?: '—' }}</dd></div>
            <div><dt class="text-neutral-500">Kategori</dt><dd class="font-medium text-neutral-900 dark:text-white">{{ $personnel->category ?: '—' }}</dd></div>
            @if(auth()->user()?->isAdmin())
            <div class="pt-2 border-t border-neutral-100 dark:border-slate-700">
                <dt class="text-neutral-500">Sistem erişimi</dt>
                <dd class="font-medium mt-1">
                    @if($personnel->hasSystemAccess())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                            Giriş açık · {{ $personnel->user?->role === 'admin' ? 'Yönetici' : 'Personel' }}
                        </span>
                    @elseif($personnel->user)
                        <span class="text-neutral-500">Hesap var, giriş kapalı</span>
                    @else
                        <span class="text-neutral-500">Sistem kullanıcısı değil</span>
                    @endif
                </dd>
            </div>
            @endif
        </dl>
    </div>

    <div class="card p-5 space-y-3 md:col-span-1 lg:col-span-2">
        <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Sipariş Özeti</h2>
        <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-sm">
            <div class="rounded-lg bg-neutral-50 dark:bg-slate-800/60 p-3">
                <dt class="text-neutral-500 text-xs">Toplam sipariş</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums">{{ $salesStats->count }}</dd>
            </div>
            <div class="rounded-lg bg-neutral-50 dark:bg-slate-800/60 p-3">
                <dt class="text-neutral-500 text-xs">Aktif sipariş</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums">{{ $salesStats->activeCount }}</dd>
            </div>
            <div class="rounded-lg bg-neutral-50 dark:bg-slate-800/60 p-3">
                <dt class="text-neutral-500 text-xs">Toplam ciro</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums">₺{{ number_format($salesStats->total, 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-lg bg-red-50 dark:bg-red-950/30 p-3 border border-red-100 dark:border-red-900/40">
                <dt class="text-red-700 dark:text-red-300 text-xs">Alınması gereken ödeme</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-red-700 dark:text-red-300">₺{{ number_format($salesStats->totalReceivable ?? 0, 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-lg bg-neutral-50 dark:bg-slate-800/60 p-3">
                <dt class="text-neutral-500 text-xs">Bu ay</dt>
                <dd class="mt-1 font-semibold tabular-nums">{{ $salesStats->monthCount }} sipariş</dd>
            </div>
        </dl>
    </div>
</div>

@php
    $perf = $monthlyPerformance ?? null;
    $maxCount = max(1, ($perf['thisMonth']['count'] ?? 0), ($perf['lastMonth']['count'] ?? 0));
    $maxTotal = max(1, ($perf['thisMonth']['total'] ?? 0), ($perf['lastMonth']['total'] ?? 0));
@endphp
@if($perf)
<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-slate-700 bg-neutral-50/80 dark:bg-slate-800/40">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Aylık Performans</h2>
        <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">Geçen ay ile bu ay karşılaştırması (iptal edilmeyen siparişler)</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-2xl border border-neutral-200 dark:border-slate-700 p-5 bg-white dark:bg-slate-900/40">
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-slate-400 mb-4">Geçen Ay</p>
                <p class="text-sm font-medium text-neutral-700 dark:text-slate-300 capitalize mb-4">{{ $perf['lastMonth']['label'] }}</p>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs text-neutral-500 dark:text-slate-400 mb-1">Sipariş sayısı</dt>
                        <dd class="flex items-end justify-between gap-3">
                            <span class="text-2xl font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $perf['lastMonth']['count'] }}</span>
                            <div class="flex-1 max-w-[8rem] h-2 rounded-full bg-neutral-100 dark:bg-slate-700 overflow-hidden">
                                <div class="h-full rounded-full bg-neutral-400 dark:bg-slate-500" style="width: {{ min(100, ($perf['lastMonth']['count'] / $maxCount) * 100) }}%"></div>
                            </div>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-500 dark:text-slate-400 mb-1">Satış cirosu</dt>
                        <dd class="text-xl font-semibold tabular-nums text-neutral-900 dark:text-white">₺{{ number_format($perf['lastMonth']['total'], 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-neutral-500 dark:text-slate-400 mb-1">Tahsil edilen</dt>
                        <dd class="text-lg font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">₺{{ number_format($perf['lastMonth']['collected'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-emerald-200 dark:border-emerald-900/50 p-5 bg-emerald-50/40 dark:bg-emerald-950/20 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-200/30 dark:bg-emerald-800/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none" aria-hidden="true"></div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 mb-4">Bu Ay</p>
                <p class="text-sm font-medium text-emerald-900 dark:text-emerald-100 capitalize mb-4">{{ $perf['thisMonth']['label'] }}</p>
                <dl class="space-y-4 relative">
                    <div>
                        <dt class="text-xs text-emerald-800/70 dark:text-emerald-300/80 mb-1">Sipariş sayısı</dt>
                        <dd class="flex items-end justify-between gap-3 flex-wrap">
                            <span class="text-2xl font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $perf['thisMonth']['count'] }}</span>
                            @include('partials.performance-change-badge', ['change' => $perf['countChange']])
                            <div class="w-full h-2 rounded-full bg-emerald-100 dark:bg-emerald-900/40 overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, ($perf['thisMonth']['count'] / $maxCount) * 100) }}%"></div>
                            </div>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-emerald-800/70 dark:text-emerald-300/80 mb-1">Satış cirosu</dt>
                        <dd class="flex items-center justify-between gap-3 flex-wrap">
                            <span class="text-xl font-semibold tabular-nums text-neutral-900 dark:text-white">₺{{ number_format($perf['thisMonth']['total'], 0, ',', '.') }}</span>
                            @include('partials.performance-change-badge', ['change' => $perf['totalChange']])
                        </dd>
                        <div class="mt-2 h-2 rounded-full bg-emerald-100 dark:bg-emerald-900/40 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-600" style="width: {{ min(100, ($perf['thisMonth']['total'] / $maxTotal) * 100) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <dt class="text-xs text-emerald-800/70 dark:text-emerald-300/80 mb-1">Tahsil edilen</dt>
                        <dd class="flex items-center justify-between gap-3 flex-wrap">
                            <span class="text-lg font-semibold tabular-nums text-emerald-700 dark:text-emerald-300">₺{{ number_format($perf['thisMonth']['collected'], 0, ',', '.') }}</span>
                            @include('partials.performance-change-badge', ['change' => $perf['collectedChange']])
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        @php($overallUp = ($perf['totalChange'] ?? 0) >= 0)
        <p class="mt-5 pt-5 border-t border-neutral-200 dark:border-slate-700 text-sm text-neutral-600 dark:text-slate-400">
            @if(($perf['thisMonth']['count'] ?? 0) === 0 && ($perf['lastMonth']['count'] ?? 0) === 0)
                Bu personel için karşılaştırılacak aylık satış verisi henüz yok.
            @elseif($overallUp && ($perf['totalChange'] ?? 0) > 0)
                Bu ay cirosu geçen aya göre <span class="font-semibold text-emerald-600 dark:text-emerald-400">%{{ abs($perf['totalChange']) }} artış</span> gösteriyor.
            @elseif(($perf['totalChange'] ?? 0) < 0)
                Bu ay cirosu geçen aya göre <span class="font-semibold text-red-600 dark:text-red-400">%{{ abs($perf['totalChange']) }} azalış</span> gösteriyor.
            @else
                Bu ay cirosu geçen ay ile aynı seviyede.
            @endif
        </p>
    </div>
</div>
@endif

<div class="card overflow-hidden mb-6 w-full">
    <div class="px-6 py-4 border-b border-amber-200 dark:border-amber-900/40 bg-amber-50 dark:bg-amber-950/30 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Termini 1 Hafta Kalan Müşteriler</h2>
            <p class="text-sm text-neutral-600 dark:text-slate-400 mt-1">Önümüzdeki 7 gün içinde termin tarihi gelen siparişler (gecikenler dahil)</p>
        </div>
        @if($upcomingDueSales->isNotEmpty())
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ $upcomingDueSales->count() }} sipariş</span>
        @endif
    </div>
    <div class="overflow-x-auto">
        @if($upcomingDueSales->isEmpty())
        <div class="px-6 py-10 text-center text-neutral-500 dark:text-slate-400">
            Önümüzdeki 7 gün içinde termin tarihi olan sipariş yok.
        </div>
        @else
        <table class="min-w-full w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">Sipariş No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Telefon</th>
                    <th class="table-th">Termin</th>
                    <th class="table-th">Kalan Süre</th>
                    <th class="table-th">Teslimat</th>
                    <th class="table-th text-right w-24">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @foreach($upcomingDueSales as $sale)
                @php($daysLeft = $sale->dueDate ? (int) now()->startOfDay()->diffInDays($sale->dueDate, false) : null)
                @php($terminClass = $daysLeft !== null && $daysLeft < 0 ? 'text-red-600 dark:text-red-400 font-medium' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-neutral-600 dark:text-slate-300'))
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="table-td">
                        <a href="{{ route('sales.show', $sale) }}" class="font-medium text-neutral-900 dark:text-white hover:text-emerald-600">{{ $sale->saleNumber }}</a>
                    </td>
                    <td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>
                    <td class="table-td text-neutral-600 dark:text-slate-300">{{ $sale->customer?->phone ?? '—' }}</td>
                    <td class="table-td {{ $terminClass }}">{{ $sale->dueDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td {{ $daysLeft !== null && $daysLeft < 0 ? 'text-red-600 dark:text-red-400 font-medium' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-neutral-600 dark:text-slate-300') }}">
                        @if($daysLeft === null)—
                        @elseif($daysLeft < 0){{ abs($daysLeft) }} gün gecikti
                        @elseif($daysLeft === 0)Bugün
                        @else{{ $daysLeft }} gün
                        @endif
                    </td>
                    <td class="table-td">@include('partials.delivery-status-badge', ['sale' => $sale])</td>
                    <td class="table-td text-right">
                        <a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Gör</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<div class="card overflow-hidden mb-6 w-full">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Siparişler</h2>
            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">{{ $personnel->name }} personeline atanmış tüm satışlar</p>
        </div>
        @if(auth()->user()?->isAdmin())
        <a href="{{ route('sales.index', ['personnelId' => $personnel->id]) }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium shrink-0">Satış listesinde filtrele →</a>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">Sipariş No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th">Termin</th>
                    <th class="table-th text-right">Toplam</th>
                    <th class="table-th">Ödeme</th>
                    <th class="table-th">Teslimat</th>
                    <th class="table-th text-right w-24">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @forelse($sales as $sale)
                @php($saleStatus = \App\Support\CustomerBalance::saleStatus($sale))
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40 transition-colors {{ ($sale->isCancelled ?? false) ? 'opacity-60' : '' }}">
                    <td class="table-td">
                        <a href="{{ route('sales.show', $sale) }}" class="font-medium text-neutral-900 dark:text-white hover:text-emerald-600">{{ $sale->saleNumber }}</a>
                        @if($sale->isCancelled ?? false)
                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md bg-red-50 text-red-600 font-medium">İptal</span>
                        @endif
                        @if($sale->needsFinalMeasurement ?? false)
                        <span class="block mt-1">@include('partials.final-measurement-badge', ['sale' => $sale])</span>
                        @endif
                    </td>
                    <td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>
                    <td class="table-td text-neutral-600 dark:text-slate-300">{{ $sale->saleDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td text-neutral-600 dark:text-slate-300">{{ $sale->dueDate?->format('d.m.Y') ?? '—' }}</td>
                    <td class="table-td text-right font-medium tabular-nums">₺{{ number_format($sale->grandTotal ?? 0, 0, ',', '.') }}</td>
                    <td class="table-td">
                        @if(!($sale->isCancelled ?? false))
                        @include('partials.payment-status-badge', ['status' => $saleStatus])
                        @else
                        —
                        @endif
                    </td>
                    <td class="table-td">
                        @if(!($sale->isCancelled ?? false))
                        @include('partials.delivery-status-badge', ['sale' => $sale])
                        @else
                        —
                        @endif
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Gör</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-10 text-center text-neutral-500 dark:text-slate-400">
                        Bu personele atanmış sipariş yok.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($sales->hasPages())
    <div class="px-6 py-3 border-t border-neutral-100 dark:border-slate-700">{{ $sales->links() }}</div>
    @endif
</div>

@if($quotes->isNotEmpty())
<div class="card overflow-hidden w-full">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Teklifler</h2>
                <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">Son {{ $quotes->count() }} teklif</p>
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
                        @foreach($quotes as $q)
                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40">
                            <td class="table-td"><a href="{{ route('quotes.show', $q) }}" class="font-medium text-emerald-600 hover:text-emerald-700">{{ $q->quoteNumber }}</a></td>
                            <td class="table-td">{{ $q->customer?->name ?? '—' }}</td>
                            <td class="table-td text-right font-medium tabular-nums">₺{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }}</td>
                            <td class="table-td text-neutral-600 dark:text-slate-300">{{ $q->createdAt?->format('d.m.Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
@endsection
