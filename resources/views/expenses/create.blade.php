@extends('layouts.app')
@section('title', 'Yeni Gider')
@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="page-title">Yeni Gider</h1>
        <p class="page-desc">Gider kaydı oluşturun — kasa hareketi otomatik işlenir</p>
    </div>
    <a href="{{ route('expenses.index') }}" class="btn-secondary shrink-0">← Gider listesi</a>
</div>

<form method="POST" action="{{ route('expenses.store') }}" id="expenseForm">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="card p-6">
                <h2 class="text-sm font-semibold text-neutral-900 mb-4">Gider bilgileri</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="form-label">Tutar *</label>
                        <div class="relative">
                            <input type="text" inputmode="decimal" name="amount" id="amountInput" required value="{{ old('amount') }}" class="form-input money-input text-lg font-semibold pr-10" placeholder="0" autocomplete="off">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 font-medium">₺</span>
                        </div>
                        @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Tarih *</label>
                        <input type="date" name="expenseDate" required value="{{ old('expenseDate', date('Y-m-d')) }}" class="form-input">
                        @error('expenseDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Kasa</label>
                        <select name="kasaId" class="form-select">
                            <option value="">Seçiniz (opsiyonel)</option>
                            @foreach($kasalar as $k)
                            <option value="{{ $k->id }}" {{ old('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-neutral-500">Kasa seçilirse çıkış hareketi otomatik kaydedilir.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Açıklama *</label>
                        <textarea name="description" rows="3" required class="form-textarea" placeholder="Gider detayı...">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-neutral-900">Kategori</h2>
                    <input type="text" name="category" id="categoryInput" value="{{ old('category') }}" class="form-input text-sm max-w-[180px]" placeholder="Özel kategori">
                </div>
                <div class="flex flex-wrap gap-2" id="categoryList">
                    @foreach($categories as $c)
                    <button type="button"
                        class="category-btn px-3 py-2 rounded-xl border text-sm font-medium transition-all {{ old('category') == $c ? 'border-neutral-900 bg-neutral-900 text-white' : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-400' }}"
                        data-category="{{ $c }}">
                        {{ $c }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="text-sm font-semibold text-neutral-900 mb-4">KDV ayarları</h2>
                <label class="inline-flex items-center gap-2 mb-4 cursor-pointer">
                    <input type="hidden" name="kdvIncluded" value="0">
                    <input type="checkbox" name="kdvIncluded" id="kdvIncluded" value="1" class="rounded border-neutral-300" {{ old('kdvIncluded', true) ? 'checked' : '' }}>
                    <span class="text-sm text-neutral-800">Tutar KDV dahil</span>
                </label>
                <div>
                    <label class="form-label">KDV oranı %</label>
                    <select name="kdvRate" id="kdvRate" class="form-select">
                        @foreach([0, 1, 8, 10, 18, 20] as $rate)
                        <option value="{{ $rate }}" {{ (float) old('kdvRate', 18) === (float) $rate ? 'selected' : '' }}>%{{ $rate }}</option>
                        @endforeach
                    </select>
                    @error('kdvRate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card p-6 bg-neutral-50 border-neutral-200">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-3">Özet</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-neutral-600">Matrah</dt>
                        <dd class="font-medium text-neutral-900" id="previewNet">0 ₺</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-neutral-600">KDV</dt>
                        <dd class="font-medium text-neutral-900" id="previewKdv">0 ₺</dd>
                    </div>
                    <div class="flex justify-between gap-2 pt-2 border-t border-neutral-200">
                        <dt class="font-semibold text-neutral-900">Toplam</dt>
                        <dd class="text-lg font-bold text-neutral-900" id="previewTotal">0 ₺</dd>
                    </div>
                </dl>
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-base">Gideri Kaydet</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function () {
    var amountInput = document.getElementById('amountInput');
    var kdvIncluded = document.getElementById('kdvIncluded');
    var kdvRate = document.getElementById('kdvRate');
    var categoryInput = document.getElementById('categoryInput');

    function parseAmount() {
        if (typeof window.parseMoney === 'function') return window.parseMoney(amountInput.value) || 0;
        var v = (amountInput.value || '').replace(/\./g, '').replace(',', '.');
        return parseFloat(v) || 0;
    }

    function formatMoney(n) {
        if (typeof window.formatMoney === 'function') return window.formatMoney(n, 2) + ' ₺';
        return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + ' ₺';
    }

    function updatePreview() {
        var amount = parseAmount();
        var rate = parseFloat(kdvRate.value) || 0;
        var included = kdvIncluded.checked;
        var net, kdv, total;

        if (included) {
            total = amount;
            net = rate > 0 ? amount / (1 + rate / 100) : amount;
            kdv = total - net;
        } else {
            net = amount;
            kdv = net * (rate / 100);
            total = net + kdv;
        }

        document.getElementById('previewNet').textContent = formatMoney(net);
        document.getElementById('previewKdv').textContent = formatMoney(kdv);
        document.getElementById('previewTotal').textContent = formatMoney(total);
    }

    document.querySelectorAll('.category-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.category-btn').forEach(function (b) {
                b.classList.remove('border-neutral-900', 'bg-neutral-900', 'text-white');
                b.classList.add('border-neutral-200', 'bg-white', 'text-neutral-700');
            });
            this.classList.remove('border-neutral-200', 'bg-white', 'text-neutral-700');
            this.classList.add('border-neutral-900', 'bg-neutral-900', 'text-white');
            categoryInput.value = this.dataset.category;
        });
    });

    categoryInput.addEventListener('input', function () {
        document.querySelectorAll('.category-btn').forEach(function (b) {
            var active = b.dataset.category === categoryInput.value;
            b.classList.toggle('border-neutral-900', active);
            b.classList.toggle('bg-neutral-900', active);
            b.classList.toggle('text-white', active);
            b.classList.toggle('border-neutral-200', !active);
            b.classList.toggle('bg-white', !active);
            b.classList.toggle('text-neutral-700', !active);
        });
    });

    [amountInput, kdvIncluded, kdvRate].forEach(function (el) {
        el.addEventListener('input', updatePreview);
        el.addEventListener('change', updatePreview);
    });

    document.getElementById('expenseForm').addEventListener('submit', function () {
        if (typeof window.parseMoney === 'function') {
            var parsed = window.parseMoney(amountInput.value);
            if (parsed > 0) amountInput.value = String(parsed);
        }
    });

    updatePreview();
})();
</script>
@endpush
@endsection
