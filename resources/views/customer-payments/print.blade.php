@extends('layouts.print')
@section('title', 'Tahsilat Makbuzu - ' . ($customerPayment->paymentDate?->format('d.m.Y') ?? ''))
@section('content')
@php
    $company = \App\Models\Company::first();
    $makbuzNo = 'TAHS-' . ($customerPayment->paymentDate?->format('Ymd') ?? date('Ymd')) . '-' . strtoupper(substr($customerPayment->id, 0, 8));
    $pt = \App\Support\PaymentType::labels();
@endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-doc-inner p-4 md:p-6 lg:p-8">
        @include('partials.print-brand-header', [
            'documentTitle' => 'Tahsilat Makbuzu',
            'documentNumber' => $makbuzNo,
            'documentDate' => $customerPayment->paymentDate,
        ])

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-xs font-semibold text-neutral-500 uppercase mb-2">Müşteri</h3>
                <p class="font-semibold text-slate-900">{{ $customerPayment->customer?->name ?? '-' }}</p>
                @if($customerPayment->customer?->address)<p class="text-sm text-slate-600 mt-1">{{ $customerPayment->customer->address }}</p>@endif
                @if($customerPayment->customer?->phone)<p class="text-sm text-slate-600">{{ $customerPayment->customer->phone }}</p>@endif
                @if($customerPayment->customer?->email)<p class="text-sm text-slate-600">{{ $customerPayment->customer->email }}</p>@endif
            </div>
            <div>
                <h3 class="text-xs font-semibold text-neutral-500 uppercase mb-2">Tahsilat Bilgileri</h3>
                <p class="text-sm text-slate-600">Ödeme Tipi: <span class="font-medium">{{ $pt[$customerPayment->paymentType ?? ''] ?? ucfirst($customerPayment->paymentType ?? '-') }}</span></p>
                @if($customerPayment->kasa)<p class="text-sm text-slate-600 mt-1">Kasa: <span class="font-medium">{{ $customerPayment->kasa->name }}</span></p>@endif
                @if($customerPayment->sale)<p class="text-sm text-slate-600 mt-1">İlgili Fatura: <span class="font-medium">{{ $customerPayment->sale->saleNumber }}</span></p>@endif
                @if(!empty($customerPayment->reference))<p class="text-sm text-slate-600 mt-1">Referans: <span class="font-medium">{{ $customerPayment->reference }}</span></p>@endif
            </div>
        </div>

        <div class="print-section p-4 border-2 border-neutral-900 mb-6">
            <p class="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider mb-1">Tahsil Edilen Tutar</p>
            <p class="text-2xl font-bold text-neutral-900">{{ number_format($customerPayment->amount ?? 0, 0, ',', '.') }} ₺</p>
        </div>

        @if(!empty($customerPayment->notes))
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-neutral-500 uppercase mb-2">Notlar</h3>
            <p class="text-slate-700 whitespace-pre-wrap">{{ $customerPayment->notes }}</p>
        </div>
        @endif

        <div class="pt-6 mt-6 border-t border-neutral-200 text-sm text-neutral-500">
            <p>Bu belge tahsilat makbuzu olup {{ now()->format('d.m.Y H:i') }} tarihinde düzenlenmiştir.</p>
        </div>
    </div>
</div>
@endsection
