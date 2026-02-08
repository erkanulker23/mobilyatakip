@extends('layouts.app')
@section('title', 'Tedarikçiler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Tedarikçiler</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Tedarikçi listesi ve borç takibi</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('suppliers.excel.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Excel İndir
        </a>
        <form action="{{ route('suppliers.excel.import') }}" method="POST" enctype="multipart/form-data" class="inline">
            @csrf
            <label class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 font-medium cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 16m4-4v12"></path></svg>
                Excel Yükle
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden" onchange="this.form.submit()">
            </label>
        </form>
        <a href="{{ route('suppliers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Yeni Tedarikçi
        </a>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[200px] flex-1">
            <label class="form-label">Ara (kod, ad, e-posta, telefon, vergi no)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[140px]">
            <label class="form-label">Durum</label>
            <select name="isActive" class="form-select">
                <option value="">Tümü</option>
                <option value="1" {{ request('isActive') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('isActive') === '0' ? 'selected' : '' }}>Pasif</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
            <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden" x-data="suppliersBulk" data-supplier-ids='{{ json_encode($supplierIds ?? []) }}'>
    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-600 flex items-center justify-between gap-4 flex-wrap" x-show="selected.length > 0">
        <span class="text-sm text-slate-600 dark:text-slate-400" x-text="selected.length + ' tedarikçi seçildi'"></span>
        <button type="button" @click="showBulkDeleteModal = true"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
            Seçilenleri sil
        </button>
    </div>
    {{-- Toplu silme için ayrı form (tablo içinde tekil silme formları olduğu için form iç içe olmasın) --}}
    <form id="suppliers-bulk-form" method="POST" action="{{ route('suppliers.bulk-destroy') }}" class="hidden">
        @csrf
        <input type="hidden" name="delete_products" id="suppliers-bulk-delete-products" value="0">
        <div id="suppliers-bulk-form-ids"></div>
    </form>
    <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-600">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 text-left w-12">
                        <input type="checkbox" class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleAll($event.target.checked)" :checked="selected.length === items.length && items.length > 0">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase">Kod</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase">Ad</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase">Telefon</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase">Bakiye</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase w-40">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                @forelse($suppliers as $s)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50" x-data="{ deleteOpen: false }">
                    <td class="px-4 py-4">
                        <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="supplier-row-check rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleRow('{{ $s->id }}', $event.target.checked)">
                    </td>
                    <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-mono text-sm">{{ $s->code ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="font-medium text-slate-900 dark:text-slate-100">{{ $s->name }}</span>
                        @if(!($s->isActive ?? true))<span class="ml-1 text-xs text-slate-400">(Pasif)</span>@endif
                    </td>
                    <td class="px-6 py-4 text-slate-600 dark:text-slate-400">{{ $s->phone ?? '-' }}</td>
                    @php
                        $borc = $borcBySupplier[$s->id] ?? 0;
                        $alacak = $alacakBySupplier[$s->id] ?? 0;
                        $bakiye = $borc - $alacak;
                    @endphp
                    <td class="px-6 py-4 text-right font-medium {{ $bakiye > 0 ? 'text-red-600 dark:text-red-400' : ($bakiye < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400') }}">{{ number_format($bakiye, 0, ',', '.') }} ₺</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('suppliers.show', $s) }}" aria-label="Görüntüle" title="Görüntüle" class="action-btn-view p-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </a>
                            <a href="{{ route('suppliers.edit', $s) }}" aria-label="Düzenle" title="Düzenle" class="action-btn-edit p-2 rounded-xl hover:bg-sky-50 dark:hover:bg-sky-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <a href="{{ route('suppliers.print', $s) }}" target="_blank" rel="noopener" aria-label="Yazdır" title="Yazdır" class="action-btn-print p-2 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </a>
                            <button type="button" @click="deleteOpen = true" aria-label="Sil" title="Sil" class="action-btn-delete p-2 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                            {{-- Tekil silme: tedarikçiye ait ürünler silinsin mi? --}}
                            <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                                <div x-show="deleteOpen" x-transition class="fixed inset-0 bg-black/50" @click="deleteOpen = false"></div>
                                <div x-show="deleteOpen" x-transition class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700">
                                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Tedarikçiyi sil</h2>
                                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Tedarikçiye ait tüm ürünler de kalıcı olarak silinsin mi?</p>
                                    <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-end">
                                        <button type="button" @click="deleteOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 font-medium hover:bg-slate-300 dark:hover:bg-slate-500">İptal</button>
                                        <form method="POST" action="{{ route('suppliers.destroy', $s) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="delete_products" value="0">
                                            <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-600 text-white font-medium hover:bg-slate-700">Sadece tedarikçiyi sil</button>
                                        </form>
                                        <form method="POST" action="{{ route('suppliers.destroy', $s) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="delete_products" value="1">
                                            <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-red-600 text-white font-medium hover:bg-red-700">Tedarikçi ve ürünlerini sil</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-600">{{ $suppliers->links() }}</div>

    {{-- Toplu silme onay: ürünler de silinsin mi? --}}
    <div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="showBulkDeleteModal" x-transition class="fixed inset-0 bg-black/50" @click="showBulkDeleteModal = false"></div>
        <div x-show="showBulkDeleteModal" x-transition class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Toplu tedarikçi silme</h2>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Seçili <span x-text="selected.length"></span> tedarikçi silinecek. Tedarikçilere ait tüm ürünler de kalıcı olarak silinsin mi?</p>
            <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-end">
                <button type="button" @click="showBulkDeleteModal = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 font-medium hover:bg-slate-300 dark:hover:bg-slate-500">İptal</button>
                <button type="button" @click="bulkDeleteProducts = false; submitBulkDelete()" class="px-4 py-2 rounded-xl bg-slate-600 text-white font-medium hover:bg-slate-700">Sadece tedarikçileri sil</button>
                <button type="button" @click="bulkDeleteProducts = true; submitBulkDelete()" class="px-4 py-2 rounded-xl bg-red-600 text-white font-medium hover:bg-red-700">Tedarikçileri ve ürünlerini sil</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function register() {
        Alpine.data('suppliersBulk', function() {
            var el = this.$el;
            var idsJson = el && el.getAttribute ? el.getAttribute('data-supplier-ids') : '[]';
            var items = [];
            try { items = JSON.parse(idsJson || '[]'); } catch (e) {}
            return {
                items: items,
                selected: [],
                showBulkDeleteModal: false,
                bulkDeleteProducts: false,
                toggleAll: function(checked) {
                    this.selected = checked ? this.items.slice() : [];
                    var self = this;
                    this.$nextTick(function() {
                        document.querySelectorAll('.supplier-row-check').forEach(function(el) { el.checked = checked; });
                    });
                },
                toggleRow: function(id, checked) {
                    if (checked) this.selected.push(id);
                    else this.selected = this.selected.filter(function(x) { return x !== id; });
                },
                submitBulkDelete: function() {
                    this.showBulkDeleteModal = false;
                    var sel = this.selected;
                    var deleteProductsInput = document.getElementById('suppliers-bulk-delete-products');
                    if (deleteProductsInput) deleteProductsInput.value = this.bulkDeleteProducts ? '1' : '0';
                    var container = document.getElementById('suppliers-bulk-form-ids');
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
                    document.getElementById('suppliers-bulk-form').submit();
                }
            };
        });
    }
    if (typeof Alpine !== 'undefined') register();
    else document.addEventListener('alpine:init', register);
})();
</script>
@endsection
