@extends('layouts.app')
@section('title', 'Yeni Kasa')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('kasa.index') }}" class="hover:text-slate-700">Kasa</a>
        <span>/</span>
        <span class="text-slate-700">Yeni Kasa</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Yeni Kasa</h1>
    <p class="text-slate-600 mt-1">Yeni kasa veya banka hesabı ekleyin</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('kasa.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Kasa / Hesap Adı *</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="form-input" placeholder="Örn: Ana Kasa, İş Bankası">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Tip</label>
            <select name="type" class="form-select">
                <option value="kasa" {{ old('type', 'kasa') == 'kasa' ? 'selected' : '' }}>Kasa</option>
                <option value="banka" {{ old('type') == 'banka' ? 'selected' : '' }}>Banka</option>
            </select>
        </div>
        <div id="banka-alanlari" class="space-y-5 {{ old('type', 'kasa') == 'banka' ? '' : 'hidden' }}">
            <div>
                <label class="form-label">Banka Adı</label>
                <input type="text" name="bankName" value="{{ old('bankName') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">IBAN</label>
                <input type="text" name="iban" value="{{ old('iban') }}" class="form-input" placeholder="TR00 0000 0000 0000 0000 0000 00">
            </div>
            <div>
                <label class="form-label">Hesap Numarası</label>
                <input type="text" name="accountNumber" value="{{ old('accountNumber') }}" class="form-input">
            </div>
        </div>
        <div>
            <label class="form-label">Açılış Bakiyesi (₺)</label>
            <input type="number" step="0.01" name="openingBalance" value="{{ old('openingBalance', 0) }}" class="form-input">
            @error('openingBalance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('kasa.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
<script>
document.querySelector('select[name="type"]').addEventListener('change', function() {
    const el = document.getElementById('banka-alanlari');
    el.classList.toggle('hidden', this.value !== 'banka');
});
</script>
@endsection
