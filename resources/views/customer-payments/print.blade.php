@extends('layouts.print')
@section('title', 'Tahsilat Makbuzu - ' . ($customerPayment->paymentDate?->format('d.m.Y') ?? ''))
@section('content')
@php
    $pt = \App\Support\PaymentType::labels();
    $makbuzNo = 'TAHS-' . ($customerPayment->paymentDate?->format('Ymd') ?? date('Ymd')) . '-' . strtoupper(substr($customerPayment->id, 0, 8));
@endphp
<div class="print-document print-document--fit card overflow-hidden print:shadow-none print:border-0">
    <div class="print-fit-target">
        <div class="print-doc-inner">
            @include('partials.print-brand-header', [
                'documentTitle' => 'Tahsilat Makbuzu',
                'documentNumber' => $makbuzNo,
                'documentDate' => $customerPayment->paymentDate,
            ])

            <div class="print-meta-grid print-section-lg">
                <div class="print-card">
                    <p class="print-label">Müşteri</p>
                    <p class="print-party-name">{{ $customerPayment->customer?->name ?? '-' }}</p>
                    @if($customerPayment->customer?->address)<p class="print-muted mt-1">{{ $customerPayment->customer->address }}</p>@endif
                    @if($customerPayment->customer?->phone)<p class="print-muted">{{ $customerPayment->customer->phone }}</p>@endif
                    @if($customerPayment->customer?->email)<p class="print-muted">{{ $customerPayment->customer->email }}</p>@endif
                </div>
                <div class="print-card print-card--meta">
                    <p class="print-label">Tahsilat Bilgileri</p>
                    <p class="print-muted">Ödeme Tipi: <span class="font-medium">{{ $pt[$customerPayment->paymentType ?? ''] ?? ucfirst($customerPayment->paymentType ?? '-') }}</span></p>
                    @if($customerPayment->kasa)<p class="print-muted mt-1">Kasa: <span class="font-medium">{{ $customerPayment->kasa->name }}</span></p>@endif
                    @if($customerPayment->paymentType === 'tedarikciye_ode' && $customerPayment->supplier)
                    <p class="print-muted mt-1">Tedarikçi: <span class="font-medium">{{ $customerPayment->supplier->name }}</span></p>
                    @endif
                    @if(($customerPayment->paymentType ?? '') === 'havale' && $customerPayment->kasa)
                        @if($customerPayment->kasa->bankName)<p class="print-muted mt-1">Banka: <span class="font-medium">{{ $customerPayment->kasa->bankName }}</span></p>@endif
                        @if($customerPayment->kasa->iban)<p class="print-muted mt-1">IBAN: <span class="font-mono font-medium">{{ $customerPayment->kasa->iban }}</span></p>@endif
                    @endif
                    @if($customerPayment->sale)<p class="print-muted mt-1">İlgili Fatura: <span class="font-medium">{{ $customerPayment->sale->saleNumber }}</span></p>@endif
                    @if(!empty($customerPayment->reference))<p class="print-muted mt-1">Referans: <span class="font-medium">{{ $customerPayment->reference }}</span></p>@endif
                </div>
            </div>

            <div class="print-highlight-box print-section">
                <p class="print-label">Tahsil Edilen Tutar</p>
                <p class="print-highlight-amount">{{ number_format($customerPayment->amount ?? 0, 0, ',', '.') }} ₺</p>
            </div>

            @if(!empty($customerPayment->notes))
            <div class="print-notes-block print-section">
                <p class="print-label">Notlar</p>
                <p class="whitespace-pre-wrap">{{ $customerPayment->notes }}</p>
            </div>
            @endif

            <div class="print-footer-note">
                <p>Bu belge tahsilat makbuzu olup {{ now()->format('d.m.Y H:i') }} tarihinde düzenlenmiştir.</p>
            </div>
        </div>
    </div>
</div>
@endsection
