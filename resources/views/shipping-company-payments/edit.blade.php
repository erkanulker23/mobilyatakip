@extends('layouts.app')
@section('title', 'Nakliye Ödemesi Düzenle')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm mb-1">
        <a href="{{ route('shipping-companies.show', $shippingCompanyPayment->shippingCompany) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Nakliye Firması</a>
        <span>/</span>
        <a href="{{ route('shipping-company-payments.show', $shippingCompanyPayment) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Ödeme</a>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-300">Düzenle</span>
    </div>
    <h1 class="page-title">Nakliye Ödemesi Düzenle</h1>
    <p class="page-desc">{{ $shippingCompanyPayment->shippingCompany?->name ?? 'Nakliye' }} · {{ number_format($shippingCompanyPayment->amount ?? 0, 0, ',', '.') }} ₺</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">{{ session('error') }}</div>
@endif

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('shipping-company-payments.update', $shippingCompanyPayment) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Nakliye Firması</label>
            <p class="font-medium text-slate-900 dark:text-white">{{ $shippingCompanyPayment->shippingCompany?->name ?? '—' }}</p>
        </div>
        @if($purchasesWithShipping->isNotEmpty())
        <div>
            <label class="form-label">İlgili Alış (ne için ödendi)</label>
            <select name="purchaseId" class="form-select min-h-[44px]">
                <option value="">Alışa bağlama</option>
                @foreach($purchasesWithShipping as $p)
                <option value="{{ $p->id }}" {{ old('purchaseId', $shippingCompanyPayment->purchaseId) == $p->id ? 'selected' : '' }}>
                    {{ $p->purchaseNumber }} — {{ $p->supplier?->name }} ({{ $p->purchaseDate?->format('d.m.Y') }})
                </option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tutar (₺) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $shippingCompanyPayment->amount) }}" class="form-input min-h-[44px]">
                @error('amount')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Tarih <span class="text-red-500">*</span></label>
                <input type="date" name="paymentDate" required value="{{ old('paymentDate', $shippingCompanyPayment->paymentDate?->format('Y-m-d')) }}" class="form-input min-h-[44px]">
                @error('paymentDate')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Ödeme Tipi</label>
                <select name="paymentType" class="form-select min-h-[44px]">
                    <option value="nakit" {{ old('paymentType', $shippingCompanyPayment->paymentType) == 'nakit' ? 'selected' : '' }}>Nakit</option>
                    <option value="havale" {{ old('paymentType', $shippingCompanyPayment->paymentType) == 'havale' ? 'selected' : '' }}>Havale</option>
                    <option value="kredi_karti" {{ old('paymentType', $shippingCompanyPayment->paymentType) == 'kredi_karti' ? 'selected' : '' }}>Kredi Kartı</option>
                    <option value="cek" {{ old('paymentType', $shippingCompanyPayment->paymentType) == 'cek' ? 'selected' : '' }}>Çek</option>
                    <option value="senet" {{ old('paymentType', $shippingCompanyPayment->paymentType) == 'senet' ? 'selected' : '' }}>Senet</option>
                    <option value="diger" {{ old('paymentType', $shippingCompanyPayment->paymentType) == 'diger' ? 'selected' : '' }}>Diğer</option>
                </select>
            </div>
            <div>
                <label class="form-label">Kasa</label>
                <select name="kasaId" class="form-select min-h-[44px]">
                    <option value="">Seçiniz</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ old('kasaId', $shippingCompanyPayment->kasaId) == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="form-label">Referans / Açıklama</label>
            <input type="text" name="reference" value="{{ old('reference', $shippingCompanyPayment->reference) }}" class="form-input min-h-[44px]">
        </div>
        <div>
            <label class="form-label">Notlar</label>
            <textarea name="notes" rows="2" class="form-input form-textarea">{{ old('notes', $shippingCompanyPayment->notes) }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('shipping-company-payments.show', $shippingCompanyPayment) }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
@endsection
