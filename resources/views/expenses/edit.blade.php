@extends('layouts.app')
@section('title', 'Gider Düzenle')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Gider Düzenle</h1>
        <p class="text-slate-600 mt-1">{{ $expense->expenseDate?->format('d.m.Y') }} - {{ number_format($expense->amount, 0, ',', '.') }} ₺</p>
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
        <div class="flex items-center gap-4">
            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="kdvIncluded" value="0">
                <input type="checkbox" name="kdvIncluded" value="1" {{ old('kdvIncluded', $expense->kdvIncluded ?? true) ? 'checked' : '' }}>
                <span class="form-label mb-0">KDV dahil</span>
            </label>
            <div class="flex-1 max-w-[120px]">
                <label class="form-label">KDV oranı %</label>
                <input type="number" step="0.01" min="0" max="100" name="kdvRate" value="{{ old('kdvRate', $expense->kdvRate ?? 18) }}" class="form-input">
                @error('kdvRate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
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
            <input type="hidden" name="category" id="categoryInput" value="{{ old('category', $expense->category) }}">
            <div class="flex flex-wrap gap-2 mt-2" id="categoryList">
                @foreach($categories as $c)
                <button type="button"
                    class="category-btn px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all duration-200 {{ old('category', $expense->category) == $c ? 'border-primary-600 bg-primary-50 text-primary-700 ring-2 ring-primary-200' : 'border-slate-200 bg-white text-slate-700 hover:border-primary-400 hover:bg-primary-50/50' }}"
                    data-category="{{ $c }}">
                    {{ $c }}
                </button>
                @endforeach
            </div>
        </div>
        <script>
            document.querySelectorAll('.category-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.category-btn').forEach(function(b) {
                        b.classList.remove('border-primary-600', 'bg-primary-50', 'text-primary-700', 'ring-2', 'ring-primary-200');
                        b.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
                    });
                    this.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
                    this.classList.add('border-primary-600', 'bg-primary-50', 'text-primary-700', 'ring-2', 'ring-primary-200');
                    document.getElementById('categoryInput').value = this.dataset.category;
                });
            });
        </script>
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
