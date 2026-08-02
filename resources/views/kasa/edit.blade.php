@extends('layouts.app')
@section('title', 'Düzenle: ' . $kasa->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('kasa.index') }}" class="hover:text-neutral-900">Kasa</a>
        <span>/</span>
        <a href="{{ route('kasa.show', $kasa) }}" class="hover:text-neutral-900">{{ $kasa->name }}</a>
        <span>/</span>
        <span class="text-neutral-700">Düzenle</span>
    </div>
    <h1 class="page-title">Kasa Düzenle</h1>
    <p class="page-desc">{{ $kasa->name }}</p>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('kasa.update', $kasa) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Kasa / Hesap Adı *</label>
            <input type="text" name="name" required value="{{ old('name', $kasa->name) }}" class="form-input">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Tip</label>
            <select name="type" class="form-select" id="typeSelect">
                <option value="kasa" {{ old('type', $kasa->type) == 'kasa' ? 'selected' : '' }}>Kasa</option>
                <option value="banka" {{ old('type', $kasa->type) == 'banka' ? 'selected' : '' }}>Banka</option>
            </select>
        </div>
        <div id="banka-alanlari" class="space-y-5 {{ old('type', $kasa->type) == 'banka' ? '' : 'hidden' }}">
            <div>
                <label class="form-label">Banka Adı</label>
                <input type="text" name="bankName" value="{{ old('bankName', $kasa->bankName) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">IBAN</label>
                <input type="text" name="iban" value="{{ old('iban', $kasa->iban) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Hesap Numarası</label>
                <input type="text" name="accountNumber" value="{{ old('accountNumber', $kasa->accountNumber) }}" class="form-input">
            </div>
        </div>
        <div>
            <label class="form-label">Açılış Bakiyesi (₺)</label>
            <input type="text" inputmode="decimal" name="openingBalance" value="{{ old('openingBalance', ($kasa->openingBalance ?? 0) != 0 ? money($kasa->openingBalance) : '') }}" class="form-input money-input" placeholder="0" autocomplete="off">
            <p class="mt-1 text-xs text-neutral-500">Boş bırakılırsa 0 ₺ kabul edilir. Hareketlerden bağımsızdır; silinen tahsilat/virman bu alanı etkilemez.</p>
            @error('openingBalance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="isActive" value="1" {{ old('isActive', $kasa->isActive ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <label class="form-label mb-0">Aktif</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Güncelle</button>
            <a href="{{ route('kasa.show', $kasa) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
<script>
document.getElementById('typeSelect').addEventListener('change', function() {
    document.getElementById('banka-alanlari').classList.toggle('hidden', this.value !== 'banka');
});
</script>
@endsection
