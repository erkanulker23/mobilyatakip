@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::personnel($personnel))
@section('content')
@php
    use App\Support\SaleDelivery;
    use App\Support\ServiceTicketStatus;
@endphp
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
            <h1 class="page-title">{{ $viewingOwnProfile ? 'Atölyem' : $personnel->name . ' — Atölye' }}</h1>
            <p class="page-desc">Üretimdeki siparişler, termin takibi ve SSH kayıtları</p>
        </div>
        @if(auth()->user()?->isAdmin())
        <a href="{{ route('personnel.edit', $personnel) }}" class="btn-edit">Düzenle</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="card p-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Üretimde</p>
        <p class="mt-2 text-3xl font-bold text-neutral-900 dark:text-white">{{ $workshopStats->productionCount }}</p>
        <p class="mt-1 text-xs text-neutral-500">Atölyedeki sipariş</p>
    </div>
    <div class="card p-5 border-amber-200/80 dark:border-amber-900/40 bg-amber-50/40 dark:bg-amber-950/20">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-700/80 dark:text-amber-300/80">Termin Yaklaşan</p>
        <p class="mt-2 text-3xl font-bold text-amber-800 dark:text-amber-200">{{ $workshopStats->upcomingTerminCount }}</p>
        <p class="mt-1 text-xs text-amber-700/70 dark:text-amber-400/70">
            @if($workshopStats->overdueCount > 0)
            {{ $workshopStats->overdueCount }} gecikmiş
            @else
            14 gün içinde
            @endif
        </p>
    </div>
    <div class="card p-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Açık SSH</p>
        <p class="mt-2 text-3xl font-bold text-neutral-900 dark:text-white">{{ $workshopStats->openSshCount }}</p>
        <p class="mt-1 text-xs text-neutral-500">Servis kaydı</p>
    </div>
    <div class="card p-5 border-red-200/80 dark:border-red-900/40 bg-red-50/40 dark:bg-red-950/20">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700/80 dark:text-red-300/80">Açık Eksiklik</p>
        <p class="mt-2 text-3xl font-bold text-red-800 dark:text-red-200">{{ $workshopStats->openDeficienciesCount }}</p>
        <p class="mt-1 text-xs text-red-700/70 dark:text-red-400/70">Çözülmemiş parça sorunu</p>
    </div>
</div>

<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Üretimdeki Siparişler</h2>
            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">Aşama ekleyin, eksik/yanlış parça bildirin veya yapıldı işaretleyin</p>
        </div>
        <a href="{{ route('reports.upcoming-due') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Termin raporu →</a>
    </div>
    <div class="overflow-x-auto">
        @if($productionSales->isEmpty())
        <div class="px-6 py-10 text-center text-neutral-500">Şu an üretimde sipariş yok.</div>
        @else
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-slate-700">
                    <th class="table-th">Sipariş No</th>
                    @if(empty($hideCommercialData))<th class="table-th">Müşteri</th>@endif
                    <th class="table-th">Termin</th>
                    <th class="table-th">Kayıt</th>
                    <th class="table-th">Eksiklik</th>
                    <th class="table-th text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                @foreach($productionSales as $sale)
                @php
                    $termin = SaleDelivery::terminListMeta($sale);
                    $daysLeft = $sale->dueDate ? (int) now()->startOfDay()->diffInDays($sale->dueDate, false) : null;
                @endphp
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-slate-800/40">
                    <td class="table-td font-medium text-neutral-900 dark:text-white">{{ $sale->saleNumber }}</td>
                    @if(empty($hideCommercialData))<td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>@endif
                    <td class="table-td">
                        @if($sale->dueDate)
                        <span class="{{ $termin['class'] ?? '' }}">{{ $sale->dueDate->format('d.m.Y') }}</span>
                        @if($termin['suffix'] ?? null)
                        <span class="block text-xs text-neutral-500">{{ $termin['suffix'] }}</span>
                        @endif
                        @else — @endif
                    </td>
                    <td class="table-td">
                        @if($sale->production_stages_count > 0)
                        <span class="badge badge-blue">{{ $sale->production_stages_count }}</span>
                        @if($sale->open_stages_count > 0)
                        <span class="text-xs text-neutral-500 block mt-1">{{ $sale->open_stages_count }} açık</span>
                        @endif
                        @else
                        <span class="text-neutral-400 text-sm">—</span>
                        @endif
                    </td>
                    <td class="table-td">
                        @if($sale->open_deficiencies_count > 0)
                        <span class="badge badge-amber">{{ $sale->open_deficiencies_count }} açık</span>
                        @else
                        <span class="text-neutral-400 text-sm">—</span>
                        @endif
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('workshop.show', $sale) }}" class="btn-view text-sm py-2 px-3">Düzenle</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-amber-200 dark:border-amber-900/40 bg-amber-50 dark:bg-amber-950/30">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Termin Tarihi Yaklaşanlar</h2>
            <p class="text-sm text-neutral-600 dark:text-slate-400 mt-1">14 gün içinde termin gelen üretim siparişleri</p>
        </div>
        <div class="overflow-x-auto">
            @if($upcomingDueSales->isEmpty())
            <div class="px-6 py-8 text-center text-neutral-500 text-sm">Yaklaşan termin yok.</div>
            @else
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-slate-700">
                        <th class="table-th">Sipariş</th>
                        @if(empty($hideCommercialData))<th class="table-th">Müşteri</th>@endif
                        <th class="table-th">Termin</th>
                        <th class="table-th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                    @foreach($upcomingDueSales as $sale)
                    @php $daysLeft = $sale->dueDate ? (int) now()->startOfDay()->diffInDays($sale->dueDate, false) : null; @endphp
                    <tr>
                        <td class="table-td font-medium">{{ $sale->saleNumber }}</td>
                        @if(empty($hideCommercialData))<td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>@endif
                        <td class="table-td {{ $daysLeft !== null && $daysLeft < 0 ? 'text-red-600 font-medium' : ($daysLeft !== null && $daysLeft <= 3 ? 'text-amber-600 font-medium' : '') }}">
                            {{ $sale->dueDate?->format('d.m.Y') }}
                            @if($daysLeft !== null && $daysLeft < 0)
                            <span class="block text-xs">({{ abs($daysLeft) }} gün gecikti)</span>
                            @elseif($daysLeft === 0)
                            <span class="block text-xs">Bugün</span>
                            @elseif($daysLeft !== null)
                            <span class="block text-xs">({{ $daysLeft }} gün)</span>
                            @endif
                        </td>
                        <td class="table-td text-right">
                            <a href="{{ route('workshop.show', $sale) }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Aç</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">SSH Kayıtları</h2>
            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">Açık servis formları</p>
        </div>
        <div class="overflow-x-auto">
            @if($openServiceTickets->isEmpty())
            <div class="px-6 py-8 text-center text-neutral-500 text-sm">Açık SSH kaydı yok.</div>
            @else
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-slate-700">
                        <th class="table-th">No</th>
                        @if(empty($hideCommercialData))<th class="table-th">Müşteri</th>@endif
                        <th class="table-th">Sipariş</th>
                        <th class="table-th">Durum</th>
                        <th class="table-th">Termin</th>
                        <th class="table-th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-slate-700">
                    @foreach($openServiceTickets as $ticket)
                    <tr>
                        <td class="table-td font-medium">{{ $ticket->ticketNumber }}</td>
                        @if(empty($hideCommercialData))<td class="table-td">{{ $ticket->customer?->name ?? '—' }}</td>@endif
                        <td class="table-td">{{ $ticket->sale?->saleNumber ?? '—' }}</td>
                        <td class="table-td">
                            <span class="badge badge-amber">{{ ServiceTicketStatus::label($ticket->status ?? 'acildi') }}</span>
                        </td>
                        <td class="table-td">{{ $ticket->dueDate?->format('d.m.Y') ?? '—' }}</td>
                        <td class="table-td text-right">
                            <a href="{{ route('service-tickets.show', $ticket) }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Gör</a>
                            <a href="{{ route('service-tickets.edit', $ticket) }}" class="text-sm text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white font-medium ml-2">Düzenle</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
        <div class="px-6 py-3 border-t border-neutral-100 dark:border-slate-700">
            <a href="{{ route('service-tickets.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Tüm SSH kayıtları →</a>
        </div>
    </div>
</div>

@include('partials.personnel-tasks-list')
@endsection
