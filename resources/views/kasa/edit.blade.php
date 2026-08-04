@extends('layouts.app')
@section('title', 'Düzenle: ' . $kasa->name)
@section('content')
@php
    $selectedType = old('type', $kasa->type ?? \App\Support\KasaType::KASA);
    $showBankFields = \App\Support\KasaType::showsBankFields($selectedType);
@endphp
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
            <label class="form-label">Tip *</label>
            <select name="type" class="form-select" id="typeSelect">
                @foreach(\App\Support\KasaType::labels() as $value => $label)
                <option value="{{ $value }}" {{ $selectedType === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div id="banka-alanlari" class="space-y-5 {{ $showBankFields ? '' : 'hidden' }}">
            <div>
                <label class="form-label" id="bankNameLabel">{{ $selectedType === \App\Support\KasaType::KREDI_KARTI ? 'Kart / Banka Adı' : 'Banka Adı' }}</label>
                <input type="text" name="bankName" value="{{ old('bankName', $kasa->bankName) }}" class="form-input" placeholder="{{ $selectedType === \App\Support\KasaType::KREDI_KARTI ? 'Örn: Garanti Bonus, İş Bankası POS' : 'Örn: Ziraat Bankası' }}">
            </div>
            <div id="iban-field">
                <label class="form-label">IBAN</label>
                <input type="text" name="iban" value="{{ old('iban', $kasa->iban) }}" class="form-input" placeholder="TR00 0000 0000 0000 0000 0000 00">
            </div>
            <div>
                <label class="form-label" id="accountNumberLabel">{{ $selectedType === \App\Support\KasaType::KREDI_KARTI ? 'Kart / Hesap No' : 'Hesap Numarası' }}</label>
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
(function () {
    const typeSelect = document.getElementById('typeSelect');
    const bankBlock = document.getElementById('banka-alanlari');
    const bankTypes = @json([\App\Support\KasaType::BANKA, \App\Support\KasaType::KREDI_KARTI]);

    function syncTypeFields() {
        const type = typeSelect.value;
        const showBank = bankTypes.includes(type);
        bankBlock.classList.toggle('hidden', !showBank);

        const isCard = type === @json(\App\Support\KasaType::KREDI_KARTI);
        document.getElementById('bankNameLabel').textContent = isCard ? 'Kart / Banka Adı' : 'Banka Adı';
        document.getElementById('accountNumberLabel').textContent = isCard ? 'Kart / Hesap No' : 'Hesap Numarası';
        document.getElementById('iban-field').classList.toggle('hidden', isCard);
    }

    typeSelect.addEventListener('change', syncTypeFields);
    syncTypeFields();
})();
</script>
@endsection
