@extends('layouts.app')
@section('title', 'Ödeme Al')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <span class="text-slate-700">Ödeme Al (Tahsilat)</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Ödeme Al</h1>
    <p class="text-slate-600 mt-1">Müşteriden tahsilat kaydı oluşturun</p>
</div>

@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">{{ session('error') }}</div>
@endif
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('customer-payments.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Müşteri *</label>
            <select name="customerId" required class="form-select" id="customerSelect" onchange="window.location.href='{{ route('customer-payments.create') }}?customerId='+this.value">
                <option value="">Seçiniz</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customerId', request('customerId')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('customerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @if($openSales->isNotEmpty())
        <div>
            <label class="form-label">İlgili Fatura (Opsiyonel)</label>
            <select name="saleId" class="form-select">
                <option value="">Faturaya bağlama</option>
                @foreach($openSales as $s)
                @php $kalan = (float)$s->grandTotal - (float)($s->paidAmount ?? 0); @endphp
                <option value="{{ $s->id }}" {{ old('saleId') == $s->id ? 'selected' : '' }}>{{ $s->saleNumber }} — Kalan {{ number_format($kalan, 2, ',', '.') }} ₺</option>
                @endforeach
            </select>
            <p class="mt-1 text-sm text-slate-500">Seçilirse tahsilat faturaya bağlanır ve satış ödenen tutarı güncellenir.</p>
        </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Tutar (₺) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount') }}" class="form-input" placeholder="0.00">
                @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Tarih *</label>
                <input type="date" name="paymentDate" required value="{{ old('paymentDate', date('Y-m-d')) }}" class="form-input">
                @error('paymentDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Ödeme Tipi</label>
                <select name="paymentType" class="form-select">
                    <option value="nakit" {{ old('paymentType') == 'nakit' ? 'selected' : '' }}>Nakit</option>
                    <option value="havale" {{ old('paymentType') == 'havale' ? 'selected' : '' }}>Havale</option>
                    <option value="kredi_karti" {{ old('paymentType') == 'kredi_karti' ? 'selected' : '' }}>Kredi Kartı</option>
                    <option value="cek" {{ old('paymentType') == 'cek' ? 'selected' : '' }}>Çek</option>
                    <option value="senet" {{ old('paymentType') == 'senet' ? 'selected' : '' }}>Senet</option>
                    <option value="diger" {{ old('paymentType') == 'diger' ? 'selected' : '' }}>Diğer</option>
                </select>
            </div>
            <div>
                <label class="form-label">Kasa</label>
                <select name="kasaId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($kasalar as $k)
                    <option value="{{ $k->id }}" {{ old('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="form-label">Referans / Açıklama</label>
            <input type="text" name="reference" value="{{ old('reference') }}" class="form-input" placeholder="Havale dekont no, çek no vb.">
            @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Tahsilat Kaydet</button>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
