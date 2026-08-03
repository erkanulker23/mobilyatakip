@extends('layouts.app')
@section('title', 'Kontrol Paneli')
@section('content')
<div class="space-y-4 mb-6">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-800/60 p-4 sm:p-5">
        <div class="flex gap-3 sm:gap-4">
            <div class="shrink-0 mt-0.5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-300 mb-1">Dikkat</p>
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Bu proje <span class="whitespace-nowrap">02.08.2026</span> tarihinde işleme alınmıştır.</p>
                <p class="mt-1.5 text-sm text-amber-800/90 dark:text-amber-300/90 leading-relaxed">
                    Bu tarihten önceki siparişlerde dikkatli olun; satış fişlerinde gerekli düzenlemeleri yapın. Kasa defterleri bu dönem için doğru olmayabilir.
                </p>
            </div>
        </div>
    </div>

    @if(($urgentDueSales ?? collect())->isNotEmpty())
    <div class="rounded-2xl border border-red-200 bg-red-50 dark:bg-red-950/30 dark:border-red-800/60 p-4 sm:p-5">
        <div class="flex gap-3 sm:gap-4">
            <div class="shrink-0 mt-0.5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-red-800 dark:text-red-300 mb-1">Dikkat</p>
                <p class="text-sm font-semibold text-red-900 dark:text-red-200">
                    Termin tarihi yakınlaşan siparişleriniz bulunmaktadır.
                </p>
                <p class="mt-1.5 text-sm text-red-800/90 dark:text-red-300/90 leading-relaxed">
                    {{ $urgentDueSales->count() }} siparişin termin tarihi {{ $terminAlertDays }} gün veya daha kısa süre içinde.
                    @php
                        $overdueCount = $urgentDueSales->filter(fn ($s) => $s->dueDate && (int) now()->startOfDay()->diffInDays($s->dueDate, false) < 0)->count();
                    @endphp
                    @if($overdueCount > 0)
                        <span class="font-medium">{{ $overdueCount }} tanesi gecikmiş durumda.</span>
                    @endif
                </p>
                <ul class="mt-3 space-y-1.5 text-sm text-red-900/90 dark:text-red-200/90">
                    @foreach($urgentDueSales->take(5) as $sale)
                    @php
                        $daysLeft = (int) now()->startOfDay()->diffInDays($sale->dueDate, false);
                        $daysText = $daysLeft < 0
                            ? abs($daysLeft) . ' gün gecikti'
                            : ($daysLeft === 0 ? 'bugün' : $daysLeft . ' gün kaldı');
                    @endphp
                    <li class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                        <a href="{{ route('sales.show', $sale) }}" class="font-medium hover:underline">{{ $sale->saleNumber }}</a>
                        <span class="text-red-700/80 dark:text-red-300/80">·</span>
                        <span>{{ $sale->customer?->name ?? '—' }}</span>
                        <span class="text-red-700/80 dark:text-red-300/80">·</span>
                        <span class="font-medium">{{ $sale->dueDate?->format('d.m.Y') }} ({{ $daysText }})</span>
                    </li>
                    @endforeach
                </ul>
                @if($urgentDueSales->count() > 5)
                <p class="mt-2 text-xs text-red-700/80 dark:text-red-300/80">+{{ $urgentDueSales->count() - 5 }} sipariş daha</p>
                @endif
                <a href="#termin-yaklasan" class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-red-800 dark:text-red-200 hover:underline">
                    Termin listesine git →
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="mb-8">
    <h1 class="page-title">Kontrol Paneli</h1>
    <p class="page-desc">İstatistiklerinize genel bakış</p>
</div>

@if($employeeOfTheMonth)
    @include('partials.employee-of-the-month')
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
    {{-- Son 3 Gün --}}
    <div class="card p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="text-sm text-neutral-500">Son 3 Gün</p>
                <p class="text-3xl font-semibold text-neutral-900 mt-1">{{ $last3Days->sum('count') }} <span class="text-lg font-normal text-neutral-500">Sipariş</span></p>
            </div>
            <a href="{{ route('sales.index') }}" class="p-2 text-neutral-400 hover:text-neutral-600 transition-colors" aria-label="Satışları gör">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
        </div>
        <div class="flex items-end justify-between gap-3 h-24 pt-2">
            @php $maxCount = max(1, $last3Days->max('count')); @endphp
            @foreach($last3Days as $day)
            <div class="flex-1 flex flex-col items-center gap-2">
                <div class="w-full bg-neutral-100 rounded-md overflow-hidden flex items-end" style="height: 4rem;">
                    <div class="w-full bg-neutral-900 rounded-md transition-all" style="height: {{ max(4, ($day['count'] / $maxCount) * 100) }}%;"></div>
                </div>
                <span class="text-xs text-neutral-500 capitalize">{{ $day['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Aylık Satış --}}
    <div class="card p-6">
        <p class="text-sm text-neutral-500 mb-4">Aylık Satış Cirosu</p>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="rounded-xl bg-neutral-50 p-4">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Bu Ay</p>
                <p class="text-2xl font-semibold text-neutral-900 mt-1 tabular-nums">
                    ₺{{ number_format($monthlySales, 0, ',', '.') }}
                </p>
                <p class="text-xs text-neutral-400 mt-1">
                    {{ $monthlySalesCount > 0 ? $monthlySalesCount . ' satış' : 'Henüz satış yok' }}
                </p>
            </div>
            <div class="rounded-xl bg-neutral-50 p-4">
                <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Geçen Ay</p>
                <p class="text-2xl font-semibold text-neutral-900 mt-1 tabular-nums">
                    ₺{{ number_format($lastMonthSales, 0, ',', '.') }}
                </p>
                <p class="text-xs text-neutral-400 mt-1">
                    {{ $lastMonthSalesCount > 0 ? $lastMonthSalesCount . ' satış' : 'Satış yok' }}
                </p>
            </div>
        </div>
        @if($monthlySales > 0)
        @php
            $collectedPct = min(100, round(($monthlyCollected / $monthlySales) * 100));
        @endphp
        <div class="mt-4 h-2 rounded-full bg-neutral-100 overflow-hidden">
            <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $collectedPct }}%;"></div>
        </div>
        @endif
        <div class="mt-4 space-y-2">
            <div class="flex items-center justify-between text-sm">
                <span class="text-neutral-500">Alınmış</span>
                <span class="font-semibold text-emerald-600">₺{{ number_format($monthlyCollected, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-neutral-500">Alınacak</span>
                <span class="font-semibold {{ $monthlyReceivable > 0 ? 'text-amber-600' : 'text-neutral-400' }}">₺{{ number_format($monthlyReceivable, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="flex items-center gap-1.5 mt-4 pt-4 border-t border-neutral-100">
            @if($monthlyChange >= 0)
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                <span class="text-sm text-green-600 font-medium">{{ abs($monthlyChange) }}%</span>
            @else
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                <span class="text-sm text-red-500 font-medium">{{ abs($monthlyChange) }}%</span>
            @endif
            <span class="text-sm text-neutral-400">geçen aya göre satış cirosu</span>
        </div>
        <div class="mt-4 pt-4 border-t border-neutral-100 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-neutral-500">Ort. Sipariş Değeri</span>
                <span class="font-medium text-neutral-900">{{ $avgOrderValue > 0 ? '₺' . number_format($avgOrderValue, 0, ',', '.') : '—' }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-neutral-500">Toplam Müşteri</span>
                <span class="font-medium text-neutral-900">{{ $totalCustomers }}</span>
            </div>
        </div>
    </div>

    {{-- Özet Kartlar --}}
    <div class="card p-6">
        <p class="text-sm text-neutral-500 mb-4">Genel Durum</p>
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-neutral-50 rounded-xl p-4">
                <p class="text-xs text-neutral-500">Teklif</p>
                <p class="text-2xl font-semibold text-neutral-900 mt-1">{{ $stats['quotesCount'] }}</p>
            </div>
            <div class="bg-neutral-50 rounded-xl p-4">
                <p class="text-xs text-neutral-500">Alış</p>
                <p class="text-2xl font-semibold text-neutral-900 mt-1">{{ $stats['purchasesCount'] }}</p>
            </div>
            <div class="bg-neutral-50 rounded-xl p-4">
                <p class="text-xs text-neutral-500">Satış</p>
                <p class="text-2xl font-semibold text-neutral-900 mt-1">{{ $stats['salesCount'] }}</p>
            </div>
            <a href="{{ route('stock.low') }}" class="bg-neutral-50 rounded-xl p-4 hover:bg-red-50 transition-colors {{ $stats['lowStockCount'] > 0 ? 'ring-1 ring-red-200' : '' }}">
                <p class="text-xs text-neutral-500">Kritik Stok</p>
                <p class="text-2xl font-semibold {{ $stats['lowStockCount'] > 0 ? 'text-red-600' : 'text-neutral-900' }} mt-1">{{ $stats['lowStockCount'] }}</p>
            </a>
        </div>
        <div class="mt-4 flex gap-2">
            <a href="{{ route('sales.create') }}" class="btn-primary flex-1 justify-center text-sm">Yeni Satış</a>
            <a href="{{ route('quotes.create') }}" class="btn-secondary flex-1 justify-center text-sm">Yeni Teklif</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    {{-- Son Siparişler --}}
    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <span>Son Siparişler</span>
            <a href="{{ route('sales.index') }}" class="text-sm font-normal text-neutral-500 hover:text-neutral-900 transition-colors">Tümünü Gör →</a>
        </div>
        <div class="overflow-x-auto">
            @if($recentSales->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-neutral-500 text-sm">Henüz satış kaydı yok.</p>
                    <a href="{{ route('sales.create') }}" class="btn-primary mt-4 text-sm">İlk satışı oluştur</a>
                </div>
            @else
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-neutral-100">
                            <th class="table-th">Sipariş No</th>
                            <th class="table-th">Müşteri</th>
                            <th class="table-th">Tutar</th>
                            <th class="table-th">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSales as $s)
                        @php
                            $status = 'Bekliyor';
                            $badgeClass = 'badge-blue';
                            if ($s->isCancelled) { $status = 'İptal'; $badgeClass = 'badge-red'; }
                            elseif ((float)$s->paidAmount >= (float)$s->grandTotal) { $status = 'Ödendi'; $badgeClass = 'badge-green'; }
                            elseif ((float)$s->paidAmount > 0) { $status = 'Kısmi Ödeme'; $badgeClass = 'badge-amber'; }
                        @endphp
                        <tr class="border-b border-neutral-50 hover:bg-neutral-50/50 transition-colors">
                            <td class="table-td"><a href="{{ route('sales.show', $s) }}" class="font-medium text-neutral-900 hover:underline">#{{ $s->saleNumber }}</a></td>
                            <td class="table-td">{{ $s->customer?->name ?? '—' }}</td>
                            <td class="table-td font-medium text-neutral-900">₺{{ number_format($s->grandTotal, 2, ',', '.') }}</td>
                            <td class="table-td"><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Termin süresi yaklaşan siparişler --}}
    <div id="termin-yaklasan" class="card overflow-hidden scroll-mt-24">
        <div class="card-header flex items-center justify-between">
            <span>Termin Süresi Yaklaşan Siparişler</span>
            <a href="{{ route('sales.index') }}" class="text-sm font-normal text-neutral-500 hover:text-neutral-900 transition-colors">Tümünü Gör →</a>
        </div>
        <div class="overflow-x-auto">
            @if($upcomingSales->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-neutral-500 text-sm">Önümüzdeki 14 gün içinde termin tarihi olan sipariş yok.</p>
                </div>
            @else
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-neutral-100">
                            <th class="table-th">Sipariş No</th>
                            <th class="table-th">Müşteri</th>
                            <th class="table-th">Termin</th>
                            <th class="table-th">Kalan Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingSales as $s)
                        @php
                            $daysLeft = (int) now()->startOfDay()->diffInDays($s->dueDate, false);
                            if ($daysLeft < 0) {
                                $daysLabel = abs($daysLeft) . ' gün gecikti';
                                $daysClass = 'text-red-600 font-medium';
                            } elseif ($daysLeft === 0) {
                                $daysLabel = 'Bugün';
                                $daysClass = 'text-amber-600 font-medium';
                            } else {
                                $daysLabel = $daysLeft . ' gün';
                                $daysClass = $daysLeft <= 3 ? 'text-amber-600 font-medium' : 'text-neutral-500';
                            }
                        @endphp
                        <tr class="border-b border-neutral-50 hover:bg-neutral-50/50 transition-colors">
                            <td class="table-td"><a href="{{ route('sales.show', $s) }}" class="font-medium text-neutral-900 hover:underline">#{{ $s->saleNumber }}</a></td>
                            <td class="table-td">{{ $s->customer?->name ?? '—' }}</td>
                            <td class="table-td text-neutral-600">{{ $s->dueDate?->format('d.m.Y') }}</td>
                            <td class="table-td {{ $daysClass }}">{{ $daysLabel }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Kesin ölçüye gidilecek müşteriler --}}
    <div id="kesin-olcu" class="card overflow-hidden scroll-mt-24">
        <div class="card-header flex items-center justify-between gap-3 flex-wrap">
            <span class="inline-flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                </span>
                Kesin Ölçüye Gidilecek Müşteriler
            </span>
            <a href="{{ route('sales.index', ['deliveryStatus' => \App\Support\SaleDelivery::FINAL_MEASUREMENT]) }}" class="text-sm font-normal text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors">Tümünü Gör →</a>
        </div>
        <div class="overflow-x-auto">
            @if(($finalMeasurementSales ?? collect())->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-neutral-500 text-sm">Kesin ölçü bekleyen sipariş yok.</p>
                </div>
            @else
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-slate-700">
                            <th class="table-th">Sipariş No</th>
                            <th class="table-th">Müşteri</th>
                            <th class="table-th col-hide-mobile">Telefon</th>
                            <th class="table-th col-hide-mobile">Adres</th>
                            <th class="table-th col-hide-mobile">Satışı Yapan</th>
                            <th class="table-th">Sipariş Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($finalMeasurementSales as $s)
                        <tr class="border-b border-neutral-50 dark:border-slate-700/50 hover:bg-neutral-50/50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="table-td min-w-[8.5rem]">
                                <a href="{{ route('sales.show', $s) }}" class="font-medium text-amber-700 dark:text-amber-400 hover:underline">{{ $s->saleNumber }}</a>
                                <span class="block mt-1 md:hidden text-xs text-neutral-500">{{ $s->customer?->name ?? '—' }}</span>
                                @if($s->customer?->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $s->customer->phone) }}" class="block mt-0.5 text-xs text-neutral-500 md:hidden">{{ $s->customer->phone }}</a>
                                @endif
                            </td>
                            <td class="table-td col-hide-mobile">
                                @if($s->customer)
                                <a href="{{ route('customers.show', $s->customer) }}" class="font-medium text-neutral-900 dark:text-neutral-100 hover:underline">{{ $s->customer->name }}</a>
                                @else
                                —
                                @endif
                            </td>
                            <td class="table-td col-hide-mobile cell-phone">
                                @if($s->customer?->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $s->customer->phone) }}" class="text-neutral-700 dark:text-slate-300 hover:underline">{{ $s->customer->phone }}</a>
                                @else
                                —
                                @endif
                            </td>
                            <td class="table-td text-neutral-600 dark:text-slate-400 max-w-xs truncate col-hide-mobile">{{ $s->customer?->full_address ?: '—' }}</td>
                            <td class="table-td text-neutral-600 col-hide-mobile">{{ $s->personnel?->name ?? '—' }}</td>
                            <td class="table-td whitespace-nowrap">{{ $s->saleDate?->format('d.m.Y') ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- SSH süresi yaklaşan formlar --}}
    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <span>SSH Süresi Yaklaşan Formlar</span>
            <a href="{{ route('service-tickets.index') }}" class="text-sm font-normal text-neutral-500 hover:text-neutral-900 transition-colors">Tümünü Gör →</a>
        </div>
        <div class="overflow-x-auto">
            @if($upcomingServiceTickets->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-neutral-500 text-sm">Önümüzdeki 14 gün içinde termin tarihi olan açık SSH kaydı yok.</p>
                </div>
            @else
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-neutral-100">
                            <th class="table-th">SSH No</th>
                            <th class="table-th">Müşteri</th>
                            <th class="table-th">Termin</th>
                            <th class="table-th">Kalan Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingServiceTickets as $ticket)
                        @php
                            $daysLeft = (int) now()->startOfDay()->diffInDays($ticket->dueDate, false);
                            if ($daysLeft < 0) {
                                $daysLabel = abs($daysLeft) . ' gün gecikti';
                                $daysClass = 'text-red-600 font-medium';
                            } elseif ($daysLeft === 0) {
                                $daysLabel = 'Bugün';
                                $daysClass = 'text-amber-600 font-medium';
                            } else {
                                $daysLabel = $daysLeft . ' gün';
                                $daysClass = $daysLeft <= 3 ? 'text-amber-600 font-medium' : 'text-neutral-500';
                            }
                            $status = $ticket->status ?? 'acildi';
                            $statusClass = $status === 'devam_ediyor' ? 'badge-amber' : 'badge-blue';
                        @endphp
                        <tr class="border-b border-neutral-50 hover:bg-neutral-50/50 transition-colors">
                            <td class="table-td">
                                <a href="{{ route('service-tickets.show', $ticket) }}" class="font-medium text-neutral-900 hover:underline">{{ $ticket->ticketNumber }}</a>
                                <span class="badge {{ $statusClass }} ml-2">{{ \App\Support\ServiceTicketStatus::label($status) }}</span>
                            </td>
                            <td class="table-td">{{ $ticket->customer?->name ?? '—' }}</td>
                            <td class="table-td text-neutral-600">{{ $ticket->dueDate?->format('d.m.Y') }}</td>
                            <td class="table-td {{ $daysClass }}">{{ $daysLabel }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- En Çok Satış Yapan Personel --}}
    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <span>En Çok Satış Yapan Personel</span>
            <span class="text-sm font-normal text-neutral-500">Bu ay</span>
        </div>
        <div class="p-4">
            @if($topPersonnel->isEmpty())
                <div class="py-10 text-center">
                    <p class="text-neutral-500 text-sm">Bu ay personel atanmış satış yok.</p>
                    <a href="{{ route('personnel.index') }}" class="text-sm text-neutral-600 hover:text-neutral-900 mt-2 inline-block">Personel listesi →</a>
                </div>
            @else
                <ul class="space-y-3">
                    @foreach($topPersonnel as $index => $person)
                    <li>
                        <a href="{{ route('personnel.show', $person->id) }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-neutral-50 transition-colors group">
                            <span class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold shrink-0 {{ $index === 0 ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600' }}">
                                {{ $index + 1 }}
                            </span>
                            @if($person->photoUrl)
                                <img src="{{ storage_url($person->photoUrl) }}" alt="{{ $person->name }}" class="h-10 w-10 rounded-full object-cover border border-neutral-200 shrink-0">
                            @else
                                <div class="h-10 w-10 rounded-full bg-neutral-100 border border-neutral-200 flex items-center justify-center text-sm font-semibold text-neutral-500 shrink-0">
                                    {{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-neutral-900 truncate group-hover:underline">{{ $person->name }}</p>
                                @if($person->title)
                                    <p class="text-xs text-neutral-500 truncate">{{ $person->title }}</p>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold text-neutral-900">{{ $person->sales_count }} satış</p>
                                <p class="text-xs text-neutral-500">₺{{ number_format((float) $person->sales_total, 0, ',', '.') }}</p>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
