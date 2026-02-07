@extends('layouts.app')
@section('title', 'Servis ' . $serviceTicket->ticketNumber)
@section('content')
@php
    $status = $serviceTicket->status ?? 'acildi';
    $statusClass = $status === 'tamamlandi' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300' : ($status === 'devam_ediyor' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300' : ($status === 'iptal' ? 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' : 'bg-sky-100 dark:bg-sky-900/40 text-sky-800 dark:text-sky-300'));
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1" aria-label="Breadcrumb">
            <a href="{{ route('service-tickets.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Servis Talepleri</a>
            <span>/</span>
            <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $serviceTicket->ticketNumber }}</span>
        </nav>
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="page-title mb-0">{{ $serviceTicket->ticketNumber }}</h1>
            <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
        </div>
        <p class="page-desc">{{ $serviceTicket->issueType ?? 'Servis kaydı' }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('service-tickets.edit', $serviceTicket) }}" class="btn-primary">Düzenle</a>
        <a href="{{ route('service-tickets.print', $serviceTicket) }}" target="_blank" rel="noopener" class="btn-secondary">Yazdır</a>
        @if($serviceTicket->saleId && $serviceTicket->sale)
        <a href="{{ route('sales.show', $serviceTicket->sale) }}" class="btn-secondary">Satış Detayı</a>
        @endif
    </div>
</div>

<div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="card-header">Servis Bilgileri</div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    @if($serviceTicket->sale)
                    <div><dt class="form-label">Satış</dt><dd class="font-medium"><a href="{{ route('sales.show', $serviceTicket->sale) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $serviceTicket->sale->saleNumber ?? '—' }}</a></dd></div>
                    @endif
                    @if($serviceTicket->customer)
                    <div><dt class="form-label">Müşteri</dt><dd class="font-medium"><a href="{{ route('customers.show', $serviceTicket->customer) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $serviceTicket->customer->name ?? '—' }}</a></dd></div>
                    <div><dt class="form-label">Telefon</dt><dd class="text-slate-800 dark:text-slate-200">{{ $serviceTicket->customer->phone ?: '—' }}</dd></div>
                    <div><dt class="form-label">Adres</dt><dd class="text-slate-800 dark:text-slate-200">{{ $serviceTicket->customer->address ?: '—' }}</dd></div>
                    @endif
                    <div><dt class="form-label">Sorun Tipi</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $serviceTicket->issueType ?? '—' }}</dd></div>
                    <div><dt class="form-label">Garanti</dt><dd class="text-slate-800 dark:text-slate-200">{{ $serviceTicket->underWarranty ? 'Evet' : 'Hayır' }}</dd></div>
                    <div><dt class="form-label">Teknisyen</dt><dd class="text-slate-800 dark:text-slate-200">{{ $serviceTicket->assignedUser?->name ?? '—' }}</dd></div>
                    @if($serviceTicket->assignedVehiclePlate)<div><dt class="form-label">Araç</dt><dd class="text-slate-800 dark:text-slate-200">{{ $serviceTicket->assignedVehiclePlate }}</dd></div>@endif
                    @if($serviceTicket->serviceChargeAmount)
                    <div><dt class="form-label">Servis Ücreti</dt><dd class="font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($serviceTicket->serviceChargeAmount, 0, ',', '.') }} ₺</dd></div>
                    @endif
                    <div><dt class="form-label">Açılış</dt><dd class="text-slate-800 dark:text-slate-200">{{ $serviceTicket->openedAt?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                    @if($serviceTicket->closedAt)<div><dt class="form-label">Kapanış</dt><dd class="text-slate-800 dark:text-slate-200">{{ $serviceTicket->closedAt->format('d.m.Y H:i') }}</dd></div>@endif
                </dl>
            </div>
        </div>
        @if($serviceTicket->description)
        <div class="card overflow-hidden">
            <div class="card-header">Açıklama</div>
            <div class="p-5">
                <p class="text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{{ $serviceTicket->description }}</p>
            </div>
        </div>
        @endif
        @php $ticketImages = is_array($serviceTicket->images ?? null) ? $serviceTicket->images : []; @endphp
        @if(count($ticketImages) > 0)
        <div class="card overflow-hidden">
            <div class="card-header">Resimler</div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($ticketImages as $img)
                    <a href="{{ asset($img) }}" target="_blank" rel="noopener" class="block rounded-xl overflow-hidden border border-slate-200 dark:border-slate-600 hover:border-emerald-500 transition-colors aspect-square">
                        <img src="{{ asset($img) }}" alt="Servis" class="w-full h-full object-cover">
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        @if($serviceTicket->notes)
        <div class="card overflow-hidden">
            <div class="card-header">Notlar</div>
            <div class="p-5">
                <p class="text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{{ $serviceTicket->notes }}</p>
            </div>
        </div>
        @endif
        <div class="card overflow-hidden">
            <div class="card-header">İşlem Geçmişi</div>
            <div class="p-5">
                <div class="space-y-4">
                    @forelse($serviceTicket->details->sortBy('actionDate') as $i => $d)
                    <div class="flex gap-4 pb-4 {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-700' : '' }}">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-semibold shrink-0 {{ $i === 0 ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">{{ $i + 1 }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-slate-900 dark:text-white">{{ ucfirst($d->action ?? '—') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $d->actionDate?->format('d.m.Y H:i') ?? '—' }} · {{ $d->user?->name ?? '—' }}</p>
                            @if($d->notes)<p class="text-sm text-slate-600 dark:text-slate-400 mt-2 whitespace-pre-wrap">{{ $d->notes }}</p>@endif
                        </div>
                    </div>
                    @empty
                    <p class="text-slate-500 dark:text-slate-400 text-sm">Henüz işlem kaydı yok.</p>
                    @endforelse
                </div>
            </div>
        </div>
</div>
@endsection
