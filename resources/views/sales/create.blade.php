@extends('layouts.app')
@section('title', 'Yeni Satış')
@section('content')
<div class="mb-8">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('sales.index') }}" class="hover:text-slate-700">Satışlar</a>
        <span>/</span>
        <span class="text-slate-700">Yeni Satış</span>
    </div>
    <h1 class="page-title">Yeni Satış</h1>
    <p class="page-desc">Doğrudan satış faturası oluşturun</p>
</div>

<div class="card p-6 max-w-4xl">
    <form method="POST" action="{{ route('sales.store') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @if(session('error'))
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">{{ session('error') }}</div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="form-label">Müşteri *</label>
                <select name="customerId" required class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ old('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('customerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Depo *</label>
                <select name="warehouseId" required class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" {{ old('warehouseId') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
                @error('warehouseId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">KDV</label>
                <select name="kdvIncluded" class="form-select">
                    <option value="1" {{ old('kdvIncluded', '1') == '1' ? 'selected' : '' }}>KDV Dahil</option>
                    <option value="0" {{ old('kdvIncluded') === '0' ? 'selected' : '' }}>KDV Hariç</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Birim fiyat KDV dahil mi hariç mi?</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Satış Tarihi *</label>
                <input type="date" name="saleDate" required value="{{ old('saleDate', date('Y-m-d')) }}" class="form-input">
                @error('saleDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Vade Tarihi</label>
                <input type="date" name="dueDate" value="{{ old('dueDate') }}" class="form-input">
                @error('dueDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="border-t border-slate-200 pt-5">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Satış Kalemleri</h3>
            <div id="items" class="space-y-3">
                <div class="item-row grid grid-cols-1 md:grid-cols-[1fr_120px_80px_80px_40px] gap-3 items-end">
                    <div>
                        <label class="form-label">Ürün *</label>
                        <select name="items[0][productId]" required class="form-select item-product" data-row="0">
                            <option value="">Seçiniz</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" data-price="{{ $p->unitPrice }}" data-kdv="{{ $p->kdvRate ?? 18 }}">{{ $p->name }} ({{ number_format($p->unitPrice, 2) }} ₺)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Fiyat *</label>
                        <input type="number" step="0.01" min="0" name="items[0][unitPrice]" required class="form-input item-price" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Adet *</label>
                        <input type="number" name="items[0][quantity]" value="1" required min="1" class="form-input item-qty">
                    </div>
                    <div>
                        <label class="form-label">KDV %</label>
                        <input type="number" step="0.01" min="0" max="100" name="items[0][kdvRate]" value="18" class="form-input item-kdv" placeholder="18">
                    </div>
                    <div><button type="button" onclick="addRow()" class="p-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">+</button></div>
                </div>
            </div>
        </div>
        <div>
            <label class="form-label">Notlar</label>
            <textarea name="notes" rows="2" class="form-input form-textarea">{{ old('notes') }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="submitting" class="btn-primary disabled:opacity-70 disabled:cursor-not-allowed">
                <span x-show="submitting" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" aria-hidden="true"></span>
                <span x-text="submitting ? 'Oluşturuluyor...' : 'Satış Oluştur'">Satış Oluştur</span>
            </button>
            <a href="{{ route('sales.index') }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
<script>
let idx = 1;
function addRow() {
    const t = document.querySelector('.item-row');
    const c = t.cloneNode(true);
    c.querySelectorAll('select, input').forEach(e => {
        if (e.name) e.name = e.name.replace(/\[\d+\]/, '[' + idx + ']');
        if (e.classList.contains('item-price')) e.value = '';
        if (e.classList.contains('item-qty')) e.value = '1';
        if (e.classList.contains('item-kdv')) e.value = '18';
    });
    c.querySelector('.item-product')?.addEventListener('change', function() {
        const o = this.selectedOptions[0];
        if (o) {
            const row = this.closest('.item-row');
            if (o.dataset.price) row.querySelector('.item-price').value = o.dataset.price;
            if (o.dataset.kdv) row.querySelector('.item-kdv').value = o.dataset.kdv;
        }
    });
    document.getElementById('items').appendChild(c);
    idx++;
}
document.querySelectorAll('.item-product').forEach(s => {
    s.addEventListener('change', function() {
        const o = this.selectedOptions[0];
        if (o) {
            const row = this.closest('.item-row');
            if (o.dataset.price) row.querySelector('.item-price').value = o.dataset.price;
            if (o.dataset.kdv) row.querySelector('.item-kdv').value = o.dataset.kdv;
        }
    });
});
</script>
@endsection
