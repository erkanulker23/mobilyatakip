@extends('layouts.app')
@section('title', 'Tedarikçi Ödemesi Düzenle')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('suppliers.show', $supplierPayment->supplier) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Tedarikçi</a>
        <span>/</span>
        <a href="{{ route('supplier-payments.show', $supplierPayment) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Ödeme</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tedarikçi Ödemesi Düzenle</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $supplierPayment->supplier?->name ?? 'Tedarikçi' }} · {{ number_format($supplierPayment->amount ?? 0, 0, ',', '.') }} ₺</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">{{ session('error') }}</div>
@endif
<div class="bg-white dark:bg-slate-800 p-6 max-w-2xl rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
    <form method="POST" action="{{ route('supplier-payments.update', $supplierPayment) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <div>
            <label class="form-label">Tedarikçi</label>
            <p class="font-medium text-slate-900 dark:text-white">{{ $supplierPayment->supplier?->name ?? '—' }}</p>
        </div>
        @if($openPurchases->isNotEmpty())
        <div>
            <label class="form-label">İlgili Alış (Opsiyonel)</label>
            <select name="purchaseId" class="form-select">
                <option value="">Alışa bağlama</option>
                @foreach($openPurchases as $p)
                @php $kalan = (float)$p->grandTotal - (float)($p->paidAmount ?? 0); @endphp
                <option value="{{ $p->id }}" {{ old('purchaseId', $supplierPayment->purchaseId) == $p->id ? 'selected' : '' }}>{{ $p->purchaseNumber }} — Kalan {{ number_format($kalan, 0, ',', '.') }} ₺</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tutar (₺) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $supplierPayment->amount) }}" class="form-input" placeholder="0.00">
                @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Tarih *</label>
                <input type="date" name="paymentDate" required value="{{ old('paymentDate', $supplierPayment->paymentDate?->format('Y-m-d')) }}" class="form-input">
                @error('paymentDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Ödeme Tipi</label>
                <select name="paymentType" class="form-select">
                    <option value="nakit" {{ old('paymentType', $supplierPayment->paymentType) == 'nakit' ? 'selected' : '' }}>Nakit</option>
                    <option value="havale" {{ old('paymentType', $supplierPayment->paymentType) == 'havale' ? 'selected' : '' }}>Havale</option>
                    <option value="kredi_karti" {{ old('paymentType', $supplierPayment->paymentType) == 'kredi_karti' ? 'selected' : '' }}>Kredi Kartı</option>
                    <option value="cek" {{ old('paymentType', $supplierPayment->paymentType) == 'cek' ? 'selected' : '' }}>Çek</option>
                    <option value="senet" {{ old('paymentType', $supplierPayment->paymentType) == 'senet' ? 'selected' : '' }}>Senet</option>
                    <option value="diger" {{ old('paymentType', $supplierPayment->paymentType) == 'diger' ? 'selected' : '' }}>Diğer</option>
                </select>
            </div>
            <div>
                <label class="form-label">Kasa</label>
                <select name="kasaId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ old('kasaId', $supplierPayment->kasaId) == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Nakit, havale, kredi kartı ödemeleri kasaya çıkış olarak yansır.</p>
            </div>
        </div>
        <div>
            <label class="form-label">Referans / Açıklama</label>
            <input type="text" name="reference" value="{{ old('reference', $supplierPayment->reference) }}" class="form-input" placeholder="Havale dekont no, çek no vb.">
        </div>
        <div>
            <label class="form-label">Notlar</label>
            <textarea name="notes" rows="2" class="form-input">{{ old('notes', $supplierPayment->notes) }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('supplier-payments.show', $supplierPayment) }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
@endsection
