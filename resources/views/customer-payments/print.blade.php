@extends('layouts.print')
@section('title', 'Tahsilat Makbuzu - ' . ($customerPayment->paymentDate?->format('d.m.Y') ?? ''))
@section('content')
@php
    $company = \App\Models\Company::first();
    $makbuzNo = 'TAHS-' . ($customerPayment->paymentDate?->format('Ymd') ?? date('Ymd')) . '-' . strtoupper(substr($customerPayment->id, 0, 8));
    $pt = ['nakit' => 'Nakit', 'havale' => 'Havale', 'kredi_karti' => 'Kredi Kartı', 'cek' => 'Çek', 'senet' => 'Senet', 'diger' => 'Diğer'];
@endphp
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
                <h2 class="text-lg font-semibold text-slate-800">TAHSİLAT MAKBUZU</h2>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $makbuzNo }}</p>
                <p class="text-sm text-slate-600 mt-2">{{ $customerPayment->paymentDate?->format('d.m.Y') ?? '-' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Müşteri</h3>
                <p class="font-semibold text-slate-900">{{ $customerPayment->customer?->name ?? '-' }}</p>
                @if($customerPayment->customer?->address)<p class="text-sm text-slate-600 mt-1">{{ $customerPayment->customer->address }}</p>@endif
                @if($customerPayment->customer?->phone)<p class="text-sm text-slate-600">{{ $customerPayment->customer->phone }}</p>@endif
                @if($customerPayment->customer?->email)<p class="text-sm text-slate-600">{{ $customerPayment->customer->email }}</p>@endif
            </div>
            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Tahsilat Bilgileri</h3>
                <p class="text-sm text-slate-600">Ödeme Tipi: <span class="font-medium">{{ $pt[$customerPayment->paymentType ?? ''] ?? ucfirst($customerPayment->paymentType ?? '-') }}</span></p>
                @if($customerPayment->kasa)<p class="text-sm text-slate-600 mt-1">Kasa: <span class="font-medium">{{ $customerPayment->kasa->name }}</span></p>@endif
                @if($customerPayment->sale)<p class="text-sm text-slate-600 mt-1">İlgili Fatura: <span class="font-medium">{{ $customerPayment->sale->saleNumber }}</span></p>@endif
                @if(!empty($customerPayment->reference))<p class="text-sm text-slate-600 mt-1">Referans: <span class="font-medium">{{ $customerPayment->reference }}</span></p>@endif
            </div>
        </div>

        <div class="p-6 bg-emerald-50 rounded-xl border-2 border-emerald-200 mb-6">
            <p class="text-sm font-semibold text-slate-600 uppercase mb-1">Tahsil Edilen Tutar</p>
            <p class="text-3xl font-bold text-emerald-700">{{ number_format($customerPayment->amount ?? 0, 2, ',', '.') }} ₺</p>
        </div>

        @if(!empty($customerPayment->notes))
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Notlar</h3>
            <p class="text-slate-700 whitespace-pre-wrap">{{ $customerPayment->notes }}</p>
        </div>
        @endif

        <div class="pt-6 mt-6 border-t border-slate-200 text-sm text-slate-500">
            <p>Bu belge tahsilat makbuzu olup {{ now()->format('d.m.Y H:i') }} tarihinde düzenlenmiştir.</p>
        </div>
    </div>
</div>
@endsection
