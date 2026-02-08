@extends('layouts.app')
@section('title', 'Tedarikçi Ödemesi')
@section('content')
@php
    $pt = ['nakit' => 'Nakit', 'havale' => 'Havale', 'kredi_karti' => 'Kredi Kartı', 'cek' => 'Çek', 'senet' => 'Senet', 'diger' => 'Diğer'];
@endphp
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1" aria-label="Breadcrumb">
                <a href="{{ route('suppliers.show', $supplierPayment->supplier) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Tedarikçi</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium">Ödeme · {{ number_format($supplierPayment->amount ?? 0, 0, ',', '.') }} ₺</span>
            </nav>
            <h1 class="page-title">Tedarikçi Ödemesi</h1>
            <p class="page-desc">
                {{ $supplierPayment->supplier?->name ?? 'Tedarikçi' }} · {{ number_format($supplierPayment->amount ?? 0, 0, ',', '.') }} ₺ · {{ $supplierPayment->paymentDate?->format('d.m.Y') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('supplier-payments.edit', $supplierPayment) }}" class="btn-edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Düzenle
            </a>
            <form method="POST" action="{{ route('supplier-payments.destroy', $supplierPayment) }}" class="inline" onsubmit="return confirm('Bu ödemeyi silmek istediğinize emin misiniz? Kasa hareketi ve fatura ödenen tutarı geri alınacaktır.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Sil
                </button>
            </form>
            <a href="{{ route('suppliers.show', $supplierPayment->supplier) }}" class="btn-secondary">Tedarikçiye Dön</a>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">Ödeme Detayı</div>
    <div class="p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <dt class="form-label">Tarih</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $supplierPayment->paymentDate?->format('d.m.Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="form-label">Tedarikçi</dt>
                <dd class="font-medium">
                    <a href="{{ route('suppliers.show', $supplierPayment->supplier) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $supplierPayment->supplier?->name ?? '—' }}</a>
                </dd>
            </div>
            <div>
                <dt class="form-label">Ödeme Tipi</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $pt[$supplierPayment->paymentType ?? ''] ?? ucfirst($supplierPayment->paymentType ?? '—') }}</dd>
            </div>
            <div>
                <dt class="form-label">Tutar</dt>
                <dd class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($supplierPayment->amount ?? 0, 0, ',', '.') }} ₺</dd>
            </div>
            @if($supplierPayment->kasa)
            <div>
                <dt class="form-label">Kasa</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $supplierPayment->kasa->name ?? '—' }}</dd>
            </div>
            @endif
            @if($supplierPayment->purchase)
            <div>
                <dt class="form-label">İlgili Alış</dt>
                <dd class="font-medium">
                    <a href="{{ route('purchases.show', $supplierPayment->purchase) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $supplierPayment->purchase->purchaseNumber ?? '—' }}</a>
                </dd>
            </div>
            @endif
            @if(!empty($supplierPayment->reference))
            <div>
                <dt class="form-label">Referans</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $supplierPayment->reference }}</dd>
            </div>
            @endif
        </dl>
        @if(!empty($supplierPayment->notes))
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700">
            <dt class="form-label">Notlar</dt>
            <dd class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $supplierPayment->notes }}</dd>
        </div>
        @endif
    </div>
</div>
@endsection
