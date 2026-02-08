@extends('layouts.app')
@section('title', 'Nakliye Ödemesi')
@section('content')
@php
    $pt = ['nakit' => 'Nakit', 'havale' => 'Havale', 'kredi_karti' => 'Kredi Kartı', 'cek' => 'Çek', 'senet' => 'Senet', 'diger' => 'Diğer'];
@endphp
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1" aria-label="Breadcrumb">
                <a href="{{ route('shipping-companies.show', $shippingCompanyPayment->shippingCompany) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Nakliye Firması</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Ödeme · {{ number_format($shippingCompanyPayment->amount ?? 0, 0, ',', '.') }} ₺</span>
            </nav>
            <h1 class="page-title">Nakliye Ödemesi</h1>
            <p class="page-desc">
                {{ $shippingCompanyPayment->shippingCompany?->name ?? 'Nakliye' }} · {{ number_format($shippingCompanyPayment->amount ?? 0, 0, ',', '.') }} ₺ · {{ $shippingCompanyPayment->paymentDate?->format('d.m.Y') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('shipping-company-payments.edit', $shippingCompanyPayment) }}" class="btn-edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Düzenle
            </a>
            <form method="POST" action="{{ route('shipping-company-payments.destroy', $shippingCompanyPayment) }}" class="inline" onsubmit="return confirm('Bu nakliye ödemesini silmek istediğinize emin misiniz?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-delete">Sil</button>
            </form>
            <a href="{{ route('shipping-companies.show', $shippingCompanyPayment->shippingCompany) }}" class="btn-secondary">Nakliye Firmasına Dön</a>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">Ödeme Detayı</div>
    <div class="p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <dt class="form-label">Tarih</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $shippingCompanyPayment->paymentDate?->format('d.m.Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="form-label">Nakliye Firması</dt>
                <dd class="font-medium">
                    <a href="{{ route('shipping-companies.show', $shippingCompanyPayment->shippingCompany) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $shippingCompanyPayment->shippingCompany?->name ?? '—' }}</a>
                </dd>
            </div>
            <div>
                <dt class="form-label">Ödeme Tipi</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $pt[$shippingCompanyPayment->paymentType ?? ''] ?? ucfirst($shippingCompanyPayment->paymentType ?? '—') }}</dd>
            </div>
            <div>
                <dt class="form-label">Tutar</dt>
                <dd class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($shippingCompanyPayment->amount ?? 0, 0, ',', '.') }} ₺</dd>
            </div>
            @if($shippingCompanyPayment->kasa)
            <div>
                <dt class="form-label">Kasa</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $shippingCompanyPayment->kasa->name ?? '—' }}</dd>
            </div>
            @endif
            @if($shippingCompanyPayment->purchase)
            <div>
                <dt class="form-label">İlgili Alış (ne için ödendi)</dt>
                <dd class="font-medium">
                    <a href="{{ route('purchases.show', $shippingCompanyPayment->purchase) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $shippingCompanyPayment->purchase->purchaseNumber ?? '—' }}</a>
                    <span class="text-slate-500 text-sm">({{ $shippingCompanyPayment->purchase->supplier?->name ?? '' }})</span>
                </dd>
            </div>
            @endif
            @if(!empty($shippingCompanyPayment->reference))
            <div>
                <dt class="form-label">Referans</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $shippingCompanyPayment->reference }}</dd>
            </div>
            @endif
        </dl>
        @if(!empty($shippingCompanyPayment->notes))
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700">
            <dt class="form-label">Notlar</dt>
            <dd class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $shippingCompanyPayment->notes }}</dd>
        </div>
        @endif
    </div>
</div>
@endsection
