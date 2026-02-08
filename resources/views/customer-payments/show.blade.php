@extends('layouts.app')
@section('title', 'Tahsilat Makbuzu')
@section('content')
@php
    $makbuzNo = 'TAHS-' . ($customerPayment->paymentDate?->format('Ymd') ?? date('Ymd')) . '-' . strtoupper(substr($customerPayment->id, 0, 8));
    $pt = ['nakit' => 'Nakit', 'havale' => 'Havale', 'kredi_karti' => 'Kredi Kartı', 'cek' => 'Çek', 'senet' => 'Senet', 'diger' => 'Diğer'];
@endphp
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1" aria-label="Breadcrumb">
                <a href="{{ route('customers.show', $customerPayment->customer) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Müşteri</a>
                <span>/</span>
                <a href="{{ route('customers.show', $customerPayment->customer) }}#tahsilatlar" class="hover:text-emerald-600 dark:hover:text-emerald-400">Tahsilatlar</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $makbuzNo }}</span>
            </nav>
            <h1 class="page-title">Tahsilat Makbuzu</h1>
            <p class="page-desc">
                {{ $makbuzNo }} · {{ $customerPayment->customer?->name ?? 'Müşteri' }} · {{ number_format($customerPayment->amount ?? 0, 0, ',', '.') }} ₺
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('customer-payments.edit', $customerPayment) }}" class="btn-edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Düzenle
            </a>
            <a href="{{ route('customer-payments.print', $customerPayment) }}" target="_blank" rel="noopener" class="btn-print">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Yazdır / PDF
            </a>
            <a href="{{ route('customers.show', $customerPayment->customer) }}#tahsilatlar" class="btn-secondary">Müşteriye Dön</a>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">Tahsilat Detayı</div>
    <div class="p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <dt class="form-label">Makbuz No</dt>
                <dd class="font-semibold text-slate-900 dark:text-white">{{ $makbuzNo }}</dd>
            </div>
            <div>
                <dt class="form-label">Tarih</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $customerPayment->paymentDate?->format('d.m.Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="form-label">Müşteri</dt>
                <dd class="font-medium">
                    <a href="{{ route('customers.show', $customerPayment->customer) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $customerPayment->customer?->name ?? '—' }}</a>
                </dd>
            </div>
            <div>
                <dt class="form-label">Ödeme Tipi</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $pt[$customerPayment->paymentType ?? ''] ?? ucfirst($customerPayment->paymentType ?? '—') }}</dd>
            </div>
            <div>
                <dt class="form-label">Tutar</dt>
                <dd class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($customerPayment->amount ?? 0, 0, ',', '.') }} ₺</dd>
            </div>
            @if($customerPayment->kasa)
            <div>
                <dt class="form-label">Kasa</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $customerPayment->kasa->name ?? '—' }}</dd>
            </div>
            @endif
            @if($customerPayment->sale)
            <div>
                <dt class="form-label">İlgili Fatura</dt>
                <dd class="font-medium">
                    <a href="{{ route('sales.show', $customerPayment->sale) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $customerPayment->sale->saleNumber ?? '—' }}</a>
                </dd>
            </div>
            @endif
            @if(!empty($customerPayment->reference))
            <div>
                <dt class="form-label">Referans</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $customerPayment->reference }}</dd>
            </div>
            @endif
        </dl>
        @if(!empty($customerPayment->notes))
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700">
            <dt class="form-label">Notlar</dt>
            <dd class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $customerPayment->notes }}</dd>
        </div>
        @endif
    </div>
</div>
@endsection
