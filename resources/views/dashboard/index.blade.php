@extends('layouts.app')
@section('title', 'Kontrol Paneli')

@section('content')
@php
    $urgentCount = ($urgentDueSales ?? collect())->count();
    $collectedPct = $monthlySales > 0 ? min(100, round(($monthlyCollected / $monthlySales) * 100)) : 0;
@endphp

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="page-title">Kontrol Paneli</h1>
        <p class="page-desc mt-1">Bugün odaklanmanız gereken işler ve özet rakamlar</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('sales.create') }}" class="btn-primary text-sm">Yeni Satış</a>
        <a href="{{ route('quotes.create') }}" class="btn-secondary text-sm">Yeni Teklif</a>
    </div>
</div>

{{-- Özet kartlar --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Bu ay ciro</p>
        <p class="text-xl sm:text-2xl font-semibold text-neutral-900 dark:text-neutral-100 mt-1 tabular-nums">₺{{ number_format($monthlySales, 0, ',', '.') }}</p>
        <p class="text-xs text-neutral-400 mt-1">{{ $monthlySalesCount }} satış</p>
    </div>
    <div class="card p-4 {{ $monthlyReceivable > 0 ? 'ring-1 ring-amber-200 dark:ring-amber-800/60' : '' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Alınacak</p>
        <p class="text-xl sm:text-2xl font-semibold {{ $monthlyReceivable > 0 ? 'text-amber-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1 tabular-nums">₺{{ number_format($monthlyReceivable, 0, ',', '.') }}</p>
        <p class="text-xs text-neutral-400 mt-1">Bu ayki satışlardan</p>
    </div>
    <a href="#is-listesi" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ ($urgentCount > 0 || $upcomingSalesCount > 0) ? 'ring-1 ring-red-200 dark:ring-red-800/60' : '' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Termin (14 gün)</p>
        <p class="text-xl sm:text-2xl font-semibold {{ $urgentCount > 0 ? 'text-red-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1">{{ $upcomingSalesCount }}</p>
        @if($urgentCount > 0)
            <p class="text-xs text-red-600 font-medium mt-1">{{ $urgentCount }} acil (≤{{ $terminAlertDays }} gün)</p>
        @else
            <p class="text-xs text-neutral-400 mt-1">Yaklaşan sipariş</p>
        @endif
    </a>
    <a href="{{ route('sales.index', ['deliveryStatus' => \App\Support\SaleDelivery::FINAL_MEASUREMENT]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ $finalMeasurementCount > 0 ? 'ring-1 ring-amber-200 dark:ring-amber-800/60' : '' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Ölçü bekliyor</p>
        <p class="text-xl sm:text-2xl font-semibold {{ $finalMeasurementCount > 0 ? 'text-amber-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1">{{ $finalMeasurementCount }}</p>
        <p class="text-xs text-neutral-400 mt-1">Kesin ölçü listesi</p>
    </a>
    <a href="{{ route('stock.low') }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ $stats['lowStockCount'] > 0 ? 'ring-1 ring-red-200 dark:ring-red-800/60' : '' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Kritik stok</p>
        <p class="text-xl sm:text-2xl font-semibold {{ $stats['lowStockCount'] > 0 ? 'text-red-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1">{{ $stats['lowStockCount'] }}</p>
        <p class="text-xs text-neutral-400 mt-1">Stok uyarıları</p>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Sol: iş listesi + son siparişler --}}
    <div class="lg:col-span-2 space-y-5">
        <div id="is-listesi" class="card overflow-hidden scroll-mt-24" x-data="{ tab: '{{ $defaultWorkTab }}' }">
            <div class="card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <span>Yapılacak işler</span>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" @click="tab = 'termin'" :class="tab === 'termin' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                        Termin
                        @if($upcomingSalesCount > 0)
                            <span class="ml-1 opacity-80">({{ $upcomingSalesCount }})</span>
                        @endif
                    </button>
                    <button type="button" @click="tab = 'olcu'" :class="tab === 'olcu' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                        Ölçü
                        @if($finalMeasurementCount > 0)
                            <span class="ml-1 opacity-80">({{ $finalMeasurementCount }})</span>
                        @endif
                    </button>
                    <button type="button" @click="tab = 'ssh'" :class="tab === 'ssh' ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                        SSH
                        @if($upcomingSshCount > 0)
                            <span class="ml-1 opacity-80">({{ $upcomingSshCount }})</span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- Termin --}}
            <div x-show="tab === 'termin'" x-cloak class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @if($upcomingSales->isEmpty())
                    <div class="p-10 text-center text-sm text-neutral-500">Önümüzdeki 14 gün içinde termin tarihi olan sipariş yok.</div>
                @else
                    @foreach($upcomingSales as $s)
                    @php
                        $daysLeft = (int) now()->startOfDay()->diffInDays($s->dueDate, false);
                        if ($daysLeft < 0) {
                            $daysLabel = abs($daysLeft) . ' gün gecikti';
                            $daysClass = 'text-red-600 bg-red-50 dark:bg-red-950/40';
                        } elseif ($daysLeft === 0) {
                            $daysLabel = 'Bugün';
                            $daysClass = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
                        } elseif ($daysLeft <= $terminAlertDays) {
                            $daysLabel = $daysLeft . ' gün';
                            $daysClass = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
                        } else {
                            $daysLabel = $daysLeft . ' gün';
                            $daysClass = 'text-neutral-600 bg-neutral-100 dark:bg-neutral-800';
                        }
                    @endphp
                    <a href="{{ route('sales.show', $s) }}" class="flex items-center gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $s->saleNumber }}</p>
                            <p class="text-sm text-neutral-500 truncate">{{ $s->customer?->name ?? '—' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $s->dueDate?->format('d.m.Y') }}</p>
                            <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-md {{ $daysClass }}">{{ $daysLabel }}</span>
                        </div>
                    </a>
                    @endforeach
                    @if($upcomingSalesCount > $upcomingSales->count())
                        <div class="p-3 text-center border-t border-neutral-100 dark:border-neutral-800">
                            <a href="{{ route('sales.index') }}" class="text-sm text-neutral-600 hover:text-neutral-900 dark:hover:text-neutral-100">+{{ $upcomingSalesCount - $upcomingSales->count() }} sipariş daha →</a>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Ölçü --}}
            <div x-show="tab === 'olcu'" x-cloak class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @if($finalMeasurementSales->isEmpty())
                    <div class="p-10 text-center text-sm text-neutral-500">Kesin ölçü bekleyen sipariş yok.</div>
                @else
                    @foreach($finalMeasurementSales as $s)
                    <a href="{{ route('sales.show', $s) }}" class="flex items-center gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-amber-700 dark:text-amber-400">{{ $s->saleNumber }}</p>
                            <p class="text-sm text-neutral-500 truncate">{{ $s->customer?->name ?? '—' }}</p>
                            @if($s->customer?->phone)
                                <p class="text-xs text-neutral-400 mt-0.5">{{ $s->customer->phone }}</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0 text-sm text-neutral-500">
                            <p>{{ $s->saleDate?->format('d.m.Y') ?? '—' }}</p>
                            @if($s->personnel)
                                <p class="text-xs mt-1">{{ $s->personnel->name }}</p>
                            @endif
                        </div>
                    </a>
                    @endforeach
                    @if($finalMeasurementCount > $finalMeasurementSales->count())
                        <div class="p-3 text-center border-t border-neutral-100 dark:border-neutral-800">
                            <a href="{{ route('sales.index', ['deliveryStatus' => \App\Support\SaleDelivery::FINAL_MEASUREMENT]) }}" class="text-sm text-neutral-600 hover:text-neutral-900 dark:hover:text-neutral-100">+{{ $finalMeasurementCount - $finalMeasurementSales->count() }} kayıt daha →</a>
                        </div>
                    @endif
                @endif
            </div>

            {{-- SSH --}}
            <div x-show="tab === 'ssh'" x-cloak class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @if($upcomingServiceTickets->isEmpty())
                    <div class="p-10 text-center text-sm text-neutral-500">Önümüzdeki 14 gün içinde termin tarihi olan açık SSH kaydı yok.</div>
                @else
                    @foreach($upcomingServiceTickets as $ticket)
                    @php
                        $daysLeft = (int) now()->startOfDay()->diffInDays($ticket->dueDate, false);
                        if ($daysLeft < 0) {
                            $daysLabel = abs($daysLeft) . ' gün gecikti';
                            $daysClass = 'text-red-600 bg-red-50 dark:bg-red-950/40';
                        } elseif ($daysLeft === 0) {
                            $daysLabel = 'Bugün';
                            $daysClass = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
                        } elseif ($daysLeft <= $terminAlertDays) {
                            $daysLabel = $daysLeft . ' gün';
                            $daysClass = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
                        } else {
                            $daysLabel = $daysLeft . ' gün';
                            $daysClass = 'text-neutral-600 bg-neutral-100 dark:bg-neutral-800';
                        }
                        $status = $ticket->status ?? 'acildi';
                    @endphp
                    <a href="{{ route('service-tickets.show', $ticket) }}" class="flex items-center gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $ticket->ticketNumber }}</p>
                            <p class="text-sm text-neutral-500 truncate">{{ $ticket->customer?->name ?? '—' }}</p>
                            <span class="inline-block mt-1 text-xs badge {{ $status === 'devam_ediyor' ? 'badge-amber' : 'badge-blue' }}">{{ \App\Support\ServiceTicketStatus::label($status) }}</span>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $ticket->dueDate?->format('d.m.Y') }}</p>
                            <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-md {{ $daysClass }}">{{ $daysLabel }}</span>
                        </div>
                    </a>
                    @endforeach
                    @if($upcomingSshCount > $upcomingServiceTickets->count())
                        <div class="p-3 text-center border-t border-neutral-100 dark:border-neutral-800">
                            <a href="{{ route('service-tickets.index') }}" class="text-sm text-neutral-600 hover:text-neutral-900 dark:hover:text-neutral-100">+{{ $upcomingSshCount - $upcomingServiceTickets->count() }} kayıt daha →</a>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Son siparişler --}}
        <div class="card overflow-hidden">
            <div class="card-header flex items-center justify-between">
                <span>Son siparişler</span>
                <a href="{{ route('sales.index') }}" class="text-sm font-normal text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors">Tümü →</a>
            </div>
            @if($recentSales->isEmpty())
                <div class="p-10 text-center text-sm text-neutral-500">Henüz satış kaydı yok.</div>
            @else
                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach($recentSales as $s)
                    @php
                        $status = 'Bekliyor';
                        $badgeClass = 'badge-blue';
                        if ($s->isCancelled) { $status = 'İptal'; $badgeClass = 'badge-red'; }
                        elseif ((float)$s->paidAmount >= (float)$s->grandTotal) { $status = 'Ödendi'; $badgeClass = 'badge-green'; }
                        elseif ((float)$s->paidAmount > 0) { $status = 'Kısmi'; $badgeClass = 'badge-amber'; }
                    @endphp
                    <a href="{{ route('sales.show', $s) }}" class="flex items-center gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">#{{ $s->saleNumber }}</p>
                            <p class="text-sm text-neutral-500 truncate">{{ $s->customer?->name ?? '—' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-medium tabular-nums">₺{{ number_format($s->grandTotal, 0, ',', '.') }}</p>
                            <span class="badge {{ $badgeClass }} mt-1">{{ $status }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Sağ: finans + personel --}}
    <div class="space-y-5">
        <div class="card p-5">
            <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Bu ay finansal özet</p>
            @if($monthlySales > 0)
                <div class="h-2 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden mb-4">
                    <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $collectedPct }}%;"></div>
                </div>
            @endif
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-neutral-500">Alınmış</dt>
                    <dd class="font-semibold text-emerald-600 tabular-nums">₺{{ number_format($monthlyCollected, 0, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-neutral-500">Alınacak</dt>
                    <dd class="font-semibold {{ $monthlyReceivable > 0 ? 'text-amber-600' : 'text-neutral-400' }} tabular-nums">₺{{ number_format($monthlyReceivable, 0, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3 pt-3 border-t border-neutral-100 dark:border-neutral-800">
                    <dt class="text-neutral-500">Geçen aya göre</dt>
                    <dd class="font-medium {{ $monthlyChange >= 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $monthlyChange >= 0 ? '+' : '' }}{{ $monthlyChange }}%
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-neutral-500">Aktif müşteri</dt>
                    <dd class="font-medium">{{ $totalCustomers }}</dd>
                </div>
            </dl>
        </div>

        @if($employeeOfTheMonth)
        <div class="card p-5">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide mb-3">Ayın elemanı · {{ $employeeOfTheMonthLabel }}</p>
            <a href="{{ route('personnel.show', $employeeOfTheMonth->id) }}" class="flex items-center gap-3 group">
                @if($employeeOfTheMonth->photoUrl)
                    <img src="{{ storage_url($employeeOfTheMonth->photoUrl) }}" alt="{{ $employeeOfTheMonth->name }}" class="h-12 w-12 rounded-full object-cover border border-neutral-200 dark:border-neutral-700 shrink-0">
                @else
                    <div class="h-12 w-12 rounded-full bg-neutral-100 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 flex items-center justify-center text-lg font-semibold text-neutral-500 shrink-0">
                        {{ mb_strtoupper(mb_substr($employeeOfTheMonth->name, 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-neutral-900 dark:text-neutral-100 group-hover:underline truncate">{{ $employeeOfTheMonth->name }}</p>
                    @if($employeeOfTheMonth->title)
                        <p class="text-xs text-neutral-500 truncate">{{ $employeeOfTheMonth->title }}</p>
                    @endif
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">{{ $employeeOfTheMonth->sales_count }} satış · ₺{{ number_format((float) $employeeOfTheMonth->sales_total, 0, ',', '.') }}</p>
                </div>
            </a>
            @if($topPersonnel->count() > 1)
                <ul class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 space-y-2">
                    @foreach($topPersonnel->skip(1) as $index => $person)
                    <li>
                        <a href="{{ route('personnel.show', $person->id) }}" class="flex items-center justify-between gap-2 text-sm text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100">
                            <span class="truncate">{{ $index + 2 }}. {{ $person->name }}</span>
                            <span class="shrink-0 tabular-nums">{{ $person->sales_count }} satış</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
        @endif

        <div class="card p-5">
            <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Kayıt özeti</p>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-neutral-50 dark:bg-neutral-900/50 p-3">
                    <p class="text-xs text-neutral-500">Satış</p>
                    <p class="text-xl font-semibold mt-0.5">{{ $stats['salesCount'] }}</p>
                </div>
                <div class="rounded-xl bg-neutral-50 dark:bg-neutral-900/50 p-3">
                    <p class="text-xs text-neutral-500">Teklif</p>
                    <p class="text-xl font-semibold mt-0.5">{{ $stats['quotesCount'] }}</p>
                </div>
                <div class="rounded-xl bg-neutral-50 dark:bg-neutral-900/50 p-3">
                    <p class="text-xs text-neutral-500">Alış</p>
                    <p class="text-xl font-semibold mt-0.5">{{ $stats['purchasesCount'] }}</p>
                </div>
                <div class="rounded-xl bg-neutral-50 dark:bg-neutral-900/50 p-3">
                    <p class="text-xs text-neutral-500">Müşteri</p>
                    <p class="text-xl font-semibold mt-0.5">{{ $totalCustomers }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<details class="mt-8 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/50 dark:bg-amber-950/20">
    <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-amber-900 dark:text-amber-200 select-none">
        Proje başlangıç tarihi hakkında bilgi (02.08.2026)
    </summary>
    <p class="px-4 pb-4 text-sm text-amber-800/90 dark:text-amber-300/90 leading-relaxed">
        Bu proje 02.08.2026 tarihinde işleme alınmıştır. Bu tarihten önceki siparişlerde dikkatli olun; satış fişlerinde gerekli düzenlemeleri yapın. Kasa defterleri bu dönem için doğru olmayabilir.
    </p>
</details>
@endsection
