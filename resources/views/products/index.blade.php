@extends('layouts.app')
@section('title', 'Ürünler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Ürünler</h1>
        <p class="page-desc">Ürün listesi, stok ve fiyat bilgisi</p>
    </div>
    <a href="{{ route('products.create') }}" class="btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Ürün
    </a>
</div>

<div class="card overflow-hidden" x-data="productsBulk" data-product-ids='{{ json_encode($productIds ?? []) }}'>
    <div class="p-4 border-b border-neutral-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (ad, SKU, açıklama)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Tedarikçi</label>
            <select name="supplierId" class="form-select">
                <option value="">Tümü</option>
                @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}" {{ request('supplierId') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[120px]">
            <label class="form-label">Min Fiyat (₺)</label>
            <input type="number" step="0.01" name="minPrice" value="{{ request('minPrice') }}" placeholder="0" class="form-input">
        </div>
        <div class="min-w-[120px]">
            <label class="form-label">Max Fiyat (₺)</label>
            <input type="number" step="0.01" name="maxPrice" value="{{ request('maxPrice') }}" placeholder="0" class="form-input">
        </div>
        <div class="min-w-[120px]">
            <label class="form-label">Durum</label>
            <select name="isActive" class="form-select">
                <option value="">Tümü</option>
                <option value="1" {{ request('isActive') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('isActive') === '0' ? 'selected' : '' }}>Pasif</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('products.index') }}" class="btn-secondary">Temizle</a>
        </div>
        </form>
    </div>
    <div class="px-5 py-3 border-b border-neutral-100 flex items-center justify-between gap-4 flex-wrap">
        <span class="text-sm text-slate-600" x-show="selected.length > 0" x-text="selected.length + ' ürün seçildi'"></span>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" x-show="selected.length > 0" @click="confirmBulkDelete = true"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
                Seçilenleri sil
            </button>
            <button type="button" @click="confirmBulkDeleteAll = true"
                    class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium text-sm">
                Filtrelenen tümünü sil
            </button>
        </div>
    </div>
    {{-- Toplu silme için ayrı form (tablo içinde tekil silme formları olduğu için form iç içe olmasın) --}}
    <form id="products-bulk-form" method="POST" action="{{ route('products.bulk-destroy') }}" class="hidden">
        @csrf
        <input type="hidden" name="all_filtered" id="products-bulk-all-filtered" value="0">
        <input type="hidden" name="search" value="{{ request('search') }}">
        <input type="hidden" name="supplierId" value="{{ request('supplierId') }}">
        <input type="hidden" name="isActive" value="{{ request('isActive') }}">
        <input type="hidden" name="minPrice" value="{{ request('minPrice') }}">
        <input type="hidden" name="maxPrice" value="{{ request('maxPrice') }}">
        <div id="products-bulk-form-ids"></div>
    </form>
    <div class="overflow-x-auto">
            <table class="min-w-full">
            <thead>
                <tr>
                    <th class="py-3 text-center w-px whitespace-nowrap" style="width: 2.25rem; min-width: 2.25rem;" title="Tümünü seç (bu sayfa)">
                        <input type="checkbox" class="rounded border-slate-300 text-green-600 focus:ring-green-500 w-4 h-4 cursor-pointer"
                               @change="toggleAll($event.target.checked)" :checked="selected.length === items.length && items.length > 0">
                    </th>
                    <th class="table-th">Ürün</th>
                    <th class="table-th">SKU</th>
                    <th class="table-th text-right">Fiyat</th>
                    <th class="table-th">Tedarikçi</th>
                    <th class="table-th text-center w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($products as $p)
                <tr class="hover:bg-slate-50 transition-colors" data-product-id="{{ $p->id }}">
                    <td class="py-4 text-center w-px whitespace-nowrap" style="width: 2.25rem; min-width: 2.25rem;">
                        <input type="checkbox" name="ids[]" value="{{ $p->id }}" class="product-row-check rounded border-slate-300 text-green-600 focus:ring-green-500 w-4 h-4 cursor-pointer"
                               @change="toggleRow('{{ $p->id }}', $event.target.checked)">
                    </td>
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            @php $img = \App\Support\ProductImages::primaryUrl($p); @endphp
                            @if($img)
                            <img src="{{ $img }}" alt="{{ $p->name }}" class="w-12 h-12 object-cover rounded-lg border border-neutral-200 shrink-0">
                            @else
                            <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            @endif
                            <div>
                                <span class="font-medium text-neutral-900">{{ $p->name }}</span>
                                @if($p->externalSource)<span class="ml-1 text-xs text-slate-400" title="XML Feed">●</span>@endif
                                @if(!($p->isActive ?? true))<span class="ml-1 text-xs text-slate-400">(Pasif)</span>@endif
                            </div>
                        </div>
                    </td>
                    <td class="table-td text-slate-600 font-mono text-sm">{{ $p->sku ?? '-' }}</td>
                    <td class="table-td text-right font-medium text-neutral-900">{{ number_format($p->unitPrice ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-slate-600">{{ $p->supplier?->name ?? '-' }}</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('products.show', $p),
                            'edit' => route('products.edit', $p),
                            'destroy' => route('products.destroy', $p),
                        ])
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-neutral-500">Kayıt bulunamadı. <a href="{{ route('xml-feeds.index') }}" class="text-green-600 hover:underline">XML Feed</a> ile ürün çekebilirsiniz.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-neutral-200">{{ $products->links() }}</div>

    {{-- Toplu silme onay modal (seçilenler) --}}
    <div x-show="confirmBulkDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="confirmBulkDelete" x-transition class="fixed inset-0 bg-black/50" @click="confirmBulkDelete = false"></div>
        <div x-show="confirmBulkDelete" x-transition class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-sm w-full p-6 border border-neutral-200 dark:border-slate-700">
            <h2 class="text-base font-semibold text-neutral-900">Toplu silme</h2>
            <p class="mt-2 text-sm text-neutral-500 dark:text-slate-400" x-text="'Seçili ' + selected.length + ' ürün kalıcı olarak silinecek. Emin misiniz?'"></p>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" @click="confirmBulkDelete = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-600 text-neutral-700 dark:text-slate-200 font-medium hover:bg-slate-300 dark:hover:bg-slate-500">İptal</button>
                <button type="button" @click="submitBulkDelete()" class="px-4 py-2 rounded-xl bg-red-600 text-white font-medium hover:bg-red-700">Sil</button>
            </div>
        </div>
    </div>
    {{-- Filtrelenen tümünü sil onay modal --}}
    <div x-show="confirmBulkDeleteAll" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="confirmBulkDeleteAll" x-transition class="fixed inset-0 bg-black/50" @click="confirmBulkDeleteAll = false"></div>
        <div x-show="confirmBulkDeleteAll" x-transition class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-sm w-full p-6 border border-neutral-200 dark:border-slate-700">
            <h2 class="text-base font-semibold text-neutral-900">Filtrelenen tümünü sil</h2>
            <p class="mt-2 text-sm text-neutral-500 dark:text-slate-400">Filtreye uyan tüm ürünler kalıcı olarak silinecek. (Arama, tedarikçi, fiyat vb. filtrelere uyan kayıtlar.) Emin misiniz?</p>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" @click="confirmBulkDeleteAll = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-600 text-neutral-700 dark:text-slate-200 font-medium hover:bg-slate-300 dark:hover:bg-slate-500">İptal</button>
                <button type="button" @click="submitBulkDeleteAll()" class="px-4 py-2 rounded-xl bg-amber-600 text-white font-medium hover:bg-amber-700">Tümünü sil</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function register() {
        Alpine.data('productsBulk', function() {
            var el = this.$el;
            var idsJson = el && el.getAttribute ? el.getAttribute('data-product-ids') : '[]';
            var items = [];
            try { items = JSON.parse(idsJson || '[]'); } catch (e) {}
            return {
                items: items,
                selected: [],
                confirmBulkDelete: false,
                confirmBulkDeleteAll: false,
                toggleAll: function(checked) {
                    this.selected = checked ? this.items.slice() : [];
                    var self = this;
                    this.$nextTick(function() {
                        document.querySelectorAll('.product-row-check').forEach(function(el) { el.checked = checked; });
                    });
                },
                toggleRow: function(id, checked) {
                    if (checked) this.selected.push(id);
                    else this.selected = this.selected.filter(function(x) { return x !== id; });
                },
                submitBulkDelete: function() {
                    this.confirmBulkDelete = false;
                    var sel = this.selected;
                    var form = document.getElementById('products-bulk-form');
                    var container = document.getElementById('products-bulk-form-ids');
                    document.getElementById('products-bulk-all-filtered').value = '0';
                    if (container) {
                        container.innerHTML = '';
                        sel.forEach(function(id) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = 'ids[]';
                            inp.value = id;
                            container.appendChild(inp);
                        });
                    }
                    form.submit();
                },
                submitBulkDeleteAll: function() {
                    this.confirmBulkDeleteAll = false;
                    document.getElementById('products-bulk-all-filtered').value = '1';
                    document.getElementById('products-bulk-form-ids').innerHTML = '';
                    document.getElementById('products-bulk-form').submit();
                }
            };
        });
    }
    if (typeof Alpine !== 'undefined') register();
    else document.addEventListener('alpine:init', register);
})();
</script>
@endsection
