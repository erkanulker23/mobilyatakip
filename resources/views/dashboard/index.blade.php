@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="mb-8">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-desc">İstatistiklerinize genel bakış</p>
</div>

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
        <p class="text-sm text-neutral-500">Aylık Satış</p>
        <p class="text-3xl font-semibold text-neutral-900 mt-1">
            @if($monthlySales > 0)
                ₺{{ number_format($monthlySales, 0, ',', '.') }}
            @else
                <span class="text-neutral-400">— TL</span>
            @endif
        </p>
        <div class="flex items-center gap-1.5 mt-2">
            @if($monthlyChange >= 0)
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                <span class="text-sm text-green-600 font-medium">{{ abs($monthlyChange) }}%</span>
            @else
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                <span class="text-sm text-red-500 font-medium">{{ abs($monthlyChange) }}%</span>
            @endif
            <span class="text-sm text-neutral-400">geçen aya göre</span>
        </div>
        <div class="mt-6 pt-4 border-t border-neutral-100 space-y-2">
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
    <div class="card overflow-hidden">
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
