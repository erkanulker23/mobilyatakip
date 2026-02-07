@extends('layouts.app')
@section('title', 'Gider Düzenle')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Gider Düzenle</h1>
        <p class="text-slate-600 mt-1">{{ $expense->expenseDate?->format('d.m.Y') }} - {{ number_format($expense->amount, 2, ',', '.') }} ₺</p>
    </div>
    <a href="{{ route('expenses.show', $expense) }}" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <div>
            <label class="form-label">Tutar *</label>
            <input type="number" step="0.01" name="amount" required value="{{ old('amount', $expense->amount) }}" class="form-input">
            @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Tarih *</label>
            <input type="date" name="expenseDate" required value="{{ old('expenseDate', $expense->expenseDate?->format('Y-m-d')) }}" class="form-input">
            @error('expenseDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Açıklama *</label>
            <textarea name="description" rows="3" required class="form-textarea">{{ old('description', $expense->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Kategori</label>
            <select name="category" class="form-select">
                <option value="">Seçiniz</option>
                @foreach($categories as $c)
                <option value="{{ $c }}" {{ old('category', $expense->category) == $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Kasa</label>
            <select name="kasaId" class="form-select">
                <option value="">Seçiniz</option>
                @foreach($kasalar as $k)
                <option value="{{ $k->id }}" {{ old('kasaId', $expense->kasaId) == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">Güncelle</button>
            <a href="{{ route('expenses.show', $expense) }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
@endsection
