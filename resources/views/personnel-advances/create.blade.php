@extends('layouts.app')
@section('title', 'Personel Avans')
@section('content')
<div class="mb-6">
    <a href="{{ route('expenses.index') }}" class="text-sm text-neutral-500 hover:text-neutral-900">Giderler</a>
    <h1 class="page-title mt-1">Personel Avans</h1>
    <p class="page-desc">Personele verilen para kasadan çıkar ve carisine borç olarak yazılır. Gider listesine karışmaz.</p>
</div>

<form method="POST" action="{{ route('personnel-advances.store') }}" class="card p-6 max-w-xl space-y-4">
    @csrf
    <div>
        <label class="form-label">Personel</label>
        <select name="personnelId" class="form-select" required>
            <option value="">Seçin</option>
            @foreach($personnel as $p)
            <option value="{{ $p->id }}" {{ old('personnelId') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Tutar</label>
        <input type="text" name="amount" value="{{ old('amount') }}" class="form-input" inputmode="decimal" placeholder="0" required>
    </div>
    <div>
        <label class="form-label">Tarih</label>
        <input type="date" name="advanceDate" value="{{ old('advanceDate', date('Y-m-d')) }}" class="form-input" required>
    </div>
    <div>
        <label class="form-label">Cari etkisi</label>
        <div class="flex gap-4 text-sm">
            <label class="inline-flex items-center gap-2">
                <input type="radio" name="period" value="gunluk" {{ old('period', 'gunluk') === 'gunluk' ? 'checked' : '' }}>
                Günlük
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="radio" name="period" value="aylik" {{ old('period') === 'aylik' ? 'checked' : '' }}>
                Aylık
            </label>
        </div>
        <p class="text-xs text-neutral-500 mt-1">Günlük, o tarihte görünür. Aylık, ait olduğu ayın cari bakiyesine yazılır.</p>
    </div>
    <div>
        <label class="form-label">Kasa</label>
        <select name="kasaId" class="form-select" required>
            <option value="">Seçin</option>
            @foreach($kasalar as $k)
            <option value="{{ $k->id }}" {{ old('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Not</label>
        <input type="text" name="note" value="{{ old('note') }}" class="form-input" maxlength="500" placeholder="İsteğe bağlı">
    </div>
    <button type="submit" class="btn-primary">Avansı kaydet</button>
</form>
@endsection
