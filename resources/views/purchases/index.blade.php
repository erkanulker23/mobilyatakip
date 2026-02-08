@extends('layouts.app')
@section('title', 'Alışlar')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Alışlar</h1>
        <p class="text-slate-600 mt-1">Alış faturaları ve tedarikçi borç takibi</p>
    </div>
    <a href="{{ route('purchases.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Yeni Alış
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (fatura no, tedarikçi)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Tedarikçi</label>
            <select name="supplierId" class="form-select">
                <option value="">Tümü</option>
                @foreach($suppliers ?? [] as $sup)
                <option value="{{ $sup->id }}" {{ request('supplierId') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Başlangıç</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input">
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Bitiş</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-input">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Filtrele</button>
            <a href="{{ route('purchases.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Temizle</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" x-data="purchasesBulk" data-purchase-ids='{{ json_encode($purchaseIds ?? []) }}'>
    <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between gap-4 flex-wrap" x-show="selected.length > 0">
        <span class="text-sm text-slate-600" x-text="selected.length + ' alış seçildi'"></span>
        <button type="button" @click="showBulkDeleteModal = true" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
            Seçilenleri sil
        </button>
    </div>
    <form id="purchases-bulk-form" method="POST" action="{{ route('purchases.bulk-destroy') }}" class="hidden">
        @csrf
        <div id="purchases-bulk-form-ids"></div>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left w-12">
                        <input type="checkbox" class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleAll($event.target.checked)" :checked="selected.length === items.length && items.length > 0">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">No</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tedarikçi</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Toplam</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase w-48">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($purchases as $p)
                <tr class="hover:bg-slate-50 {{ ($p->isCancelled ?? false) ? 'opacity-60 bg-slate-50' : '' }}">
                    <td class="px-4 py-4">
                        <input type="checkbox" name="ids[]" value="{{ $p->id }}" class="purchase-row-check rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleRow('{{ $p->id }}', $event.target.checked)">
                    </td>
                    <td class="px-6 py-4 font-medium text-slate-900">{{ $p->purchaseNumber }} @if($p->isCancelled ?? false)<span class="ml-1 text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-700">İptal</span>@endif</td>
                    <td class="px-6 py-4 text-slate-600">{{ $p->supplier?->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $p->purchaseDate?->format('d.m.Y') ?? '-' }}</td>
                    <td class="px-6 py-4 text-right font-medium">{{ number_format($p->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('purchases.show', $p) }}" aria-label="Görüntüle" title="Görüntüle" class="p-2 rounded-xl hover:bg-emerald-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </a>
                            <a href="{{ route('purchases.edit', $p) }}" aria-label="Düzenle" title="Düzenle" class="p-2 rounded-xl hover:bg-sky-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <a href="{{ route('purchases.print', $p) }}" target="_blank" rel="noopener" aria-label="Yazdır" title="Yazdır" class="p-2 rounded-xl hover:bg-violet-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </a>
                            @if(!($p->isCancelled ?? false))
                            <form method="POST" action="{{ route('purchases.cancel', $p) }}" class="inline" onsubmit="return confirm('Bu alışı iptal etmek istediğinize emin misiniz? Stok girişi geri alınacak.');">
                                @csrf
                                <button type="submit" aria-label="İptal et" title="İptal et" class="p-2 rounded-xl hover:bg-amber-50 transition-colors text-amber-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </form>
                            @endif
                            <span x-data="{ deleteOpen: false }">
                                <button type="button" @click="deleteOpen = true" aria-label="Sil" title="Sil" class="p-2 rounded-xl hover:bg-red-50 transition-colors text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                                    <div x-show="deleteOpen" x-transition class="fixed inset-0 bg-black/50" @click="deleteOpen = false"></div>
                                    <div x-show="deleteOpen" x-transition class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200">
                                        <h2 class="text-base font-semibold text-slate-900">Alışı sil</h2>
                                        <p class="mt-2 text-sm text-slate-500">Bu alış kalıcı olarak silinecek. Emin misiniz?</p>
                                        <div class="mt-6 flex gap-3 justify-end">
                                            <button type="button" @click="deleteOpen = false" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg font-medium">İptal</button>
                                            <form method="POST" action="{{ route('purchases.destroy', $p) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700">Sil</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-3 border-t border-slate-200">{{ $purchases->links() }}</div>

    <div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="showBulkDeleteModal" x-transition class="fixed inset-0 bg-black/50" @click="showBulkDeleteModal = false"></div>
        <div x-show="showBulkDeleteModal" x-transition class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Toplu alış silme</h2>
            <p class="mt-2 text-sm text-slate-500">Seçili <span x-text="selected.length"></span> alış silinecek. Emin misiniz?</p>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" @click="showBulkDeleteModal = false" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg font-medium">İptal</button>
                <button type="button" @click="submitBulkDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700">Sil</button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function register() {
        Alpine.data('purchasesBulk', function() {
            var el = this.$el;
            var idsJson = el && el.getAttribute ? el.getAttribute('data-purchase-ids') : '[]';
            var items = [];
            try { items = JSON.parse(idsJson || '[]'); } catch (e) {}
            return {
                items: items,
                selected: [],
                showBulkDeleteModal: false,
                toggleAll: function(checked) {
                    this.selected = checked ? this.items.slice() : [];
                    this.$nextTick(function() {
                        document.querySelectorAll('.purchase-row-check').forEach(function(cb) { cb.checked = checked; });
                    });
                },
                toggleRow: function(id, checked) {
                    if (checked) this.selected.push(id);
                    else this.selected = this.selected.filter(function(x) { return x !== id; });
                },
                submitBulkDelete: function() {
                    this.showBulkDeleteModal = false;
                    var sel = this.selected;
                    var container = document.getElementById('purchases-bulk-form-ids');
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
                    document.getElementById('purchases-bulk-form').submit();
                }
            };
        });
    }
    if (typeof Alpine !== 'undefined') register();
    else document.addEventListener('alpine:init', register);
})();
</script>
@endsection
