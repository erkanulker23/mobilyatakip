@extends('layouts.print')
@section('title', 'Servis Formu - ' . $serviceTicket->ticketNumber)
@section('content')
@php $company = \App\Models\Company::first(); @endphp
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden print:shadow-none print:border-0">
    <div class="p-6 md:p-8 lg:p-10">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-6 mb-8 pb-6 border-b-2 border-slate-200">
            <div class="flex-1">
                @if($company?->logoUrl)
                <img src="{{ asset($company->logoUrl) }}" alt="Logo" class="h-14 mb-3 object-contain">
                @endif
                <h1 class="text-xl font-bold text-slate-900">{{ $company?->name ?? 'Firma Adı' }}</h1>
                @if($company?->address)<p class="text-sm text-slate-600 mt-1">{{ $company->address }}</p>@endif
                @if($company?->phone)<p class="text-sm text-slate-600">{{ $company->phone }}</p>@endif
            </div>
            <div class="md:text-right">
                <h2 class="text-lg font-semibold text-slate-800">SERVİS FORMU</h2>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $serviceTicket->ticketNumber }}</p>
                <p class="text-sm text-slate-600 mt-2">{{ $serviceTicket->openedAt?->format('d.m.Y H:i') ?? '-' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Müşteri</h3>
                <p class="font-semibold text-slate-900">{{ $serviceTicket->customer?->name ?? '-' }}</p>
                @if($serviceTicket->customer?->phone)<p class="text-sm text-slate-600 mt-1">{{ $serviceTicket->customer->phone }}</p>@endif
                @if($serviceTicket->customer?->email)<p class="text-sm text-slate-600">{{ $serviceTicket->customer->email }}</p>@endif
                @if($serviceTicket->customer?->address)<p class="text-sm text-slate-600">{{ $serviceTicket->customer->address }}</p>@endif
            </div>
            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Servis Bilgileri</h3>
                <p class="text-sm text-slate-600">Satış: <span class="font-medium">{{ $serviceTicket->sale?->saleNumber ?? '-' }}</span></p>
                <p class="text-sm text-slate-600 mt-1">Sorun Tipi: <span class="font-medium">{{ $serviceTicket->issueType ?? '-' }}</span></p>
                <p class="text-sm text-slate-600 mt-1">Durum: <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $serviceTicket->status ?? 'acildi')) }}</span></p>
                <p class="text-sm text-slate-600 mt-1">Garanti: <span class="font-medium">{{ $serviceTicket->underWarranty ? 'Evet' : 'Hayır' }}</span></p>
                <p class="text-sm text-slate-600 mt-1">Teknisyen: <span class="font-medium">{{ $serviceTicket->assignedUser?->name ?? '-' }}</span></p>
                @if($serviceTicket->serviceChargeAmount)
                <p class="text-sm font-bold text-green-600 mt-2">Servis Ücreti: {{ number_format($serviceTicket->serviceChargeAmount, 0, ',', '.') }} ₺</p>
                @endif
            </div>
        </div>

        @if($serviceTicket->description)
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Açıklama</h3>
            <p class="text-slate-700 whitespace-pre-wrap">{{ $serviceTicket->description }}</p>
        </div>
        @endif

        <div class="mb-6">
            <h3 class="text-xs font-semibold text-slate-500 uppercase mb-3">İşlem Geçmişi (Timeline)</h3>
            <div class="space-y-4">
                @forelse($serviceTicket->details->sortBy('actionDate') as $d)
                <div class="flex gap-4 p-4 bg-slate-50 rounded-lg border-l-4 border-green-500">
                    <div class="flex-shrink-0 text-sm text-slate-600">{{ $d->actionDate?->format('d.m.Y H:i') ?? '-' }}</div>
                    <div>
                        <p class="font-medium text-slate-900">{{ ucfirst($d->action ?? '-') }}</p>
                        <p class="text-sm text-slate-600">{{ $d->user?->name ?? '-' }}</p>
                        @if($d->notes)<p class="text-sm text-slate-700 mt-1">{{ $d->notes }}</p>@endif
                    </div>
                </div>
                @empty
                <p class="text-slate-500 text-sm py-4">Henüz işlem kaydı yok.</p>
                @endforelse
            </div>
        </div>

        @if($serviceTicket->notes)
        <div class="pt-6 border-t border-slate-200">
            <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Notlar</h3>
            <p class="text-slate-600 whitespace-pre-wrap">{{ $serviceTicket->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
