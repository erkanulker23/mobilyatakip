@extends('layouts.app')
@section('title', 'Satışlar')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <h1 class="page-title">Satışlar</h1>
        <p class="page-desc">Satış faturaları ve tahsilat takibi</p>
    </div>
    <a href="{{ route('sales.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
        Satış Oluştur
    </a>
</div>

<div class="card p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[180px] flex-1">
            <label class="form-label">Ara (fatura no, müşteri)</label>
            <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Müşteri</label>
            <select name="customerId" class="form-select">
                <option value="">Tümü</option>
                @foreach($customers ?? [] as $c)
                <option value="{{ $c->id }}" {{ request('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
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
            <button type="submit" class="btn-primary">Filtrele</button>
            <a href="{{ route('sales.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
</div>

<div class="card overflow-hidden" x-data="salesBulk" data-sale-ids='{{ json_encode($saleIds ?? []) }}'>
    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between gap-4 flex-wrap" x-show="selected.length > 0">
        <span class="text-sm text-slate-500" x-text="selected.length + ' satış seçildi'"></span>
        <button type="button" @click="showBulkDeleteModal = true" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
            Seçilenleri sil
        </button>
    </div>
    <form id="sales-bulk-form" method="POST" action="{{ route('sales.bulk-destroy') }}" class="hidden">
        @csrf
        <div id="sales-bulk-form-ids"></div>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="table-th w-12">
                        <input type="checkbox" class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleAll($event.target.checked)" :checked="selected.length === items.length && items.length > 0">
                    </th>
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th text-right">Toplam</th>
                    <th class="table-th text-right">Ödenen</th>
                    <th class="table-th text-right">Kalan</th>
                    <th class="table-th text-right w-40">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $s)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors {{ ($s->isCancelled ?? false) ? 'opacity-60 bg-slate-50' : '' }}">
                    <td class="table-td">
                        <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="sale-row-check rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleRow('{{ $s->id }}', $event.target.checked)">
                    </td>
                    <td class="table-td"><span class="font-medium text-slate-900">{{ $s->saleNumber }}</span> @if($s->isCancelled ?? false)<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md bg-red-50 text-red-600 font-medium">İptal</span>@endif</td>
                    <td class="table-td">{{ $s->customer?->name ?? '-' }}</td>
                    <td class="table-td">{{ $s->saleDate?->format('d.m.Y') ?? '-' }}</td>
                    <td class="table-td text-right font-medium text-slate-900">{{ number_format($s->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right text-emerald-600">{{ number_format($s->paidAmount ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="table-td text-right {{ (($s->grandTotal ?? 0) - ($s->paidAmount ?? 0)) > 0 ? 'text-red-600 dark:text-red-400 font-medium' : (((($s->grandTotal ?? 0) - ($s->paidAmount ?? 0)) < 0 ? 'amount-negative' : 'text-slate-500 dark:text-slate-400')) }}">{{ number_format(($s->grandTotal ?? 0) - ($s->paidAmount ?? 0), 0, ',', '.') }} ₺</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('sales.show', $s),
                            'print' => route('sales.print', $s),
                            'destroy' => route('sales.destroy', $s),
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                        <div class="max-w-sm mx-auto">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto">
                                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <p class="mt-4 text-slate-500 text-sm">Filtreye uygun satış bulunamadı.</p>
                            <p class="mt-1 text-sm text-slate-400">Yeni satış eklemek için aşağıdaki butonu kullanın.</p>
                            <a href="{{ route('sales.create') }}" class="btn-primary mt-4">Satış oluştur</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-100 text-sm text-slate-500">{{ $sales->links() }}</div>

    <div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="showBulkDeleteModal" x-transition class="fixed inset-0 bg-black/50" @click="showBulkDeleteModal = false"></div>
        <div x-show="showBulkDeleteModal" x-transition class="relative card max-w-sm w-full p-6">
            <h2 class="text-base font-semibold text-slate-900">Toplu satış silme</h2>
            <p class="mt-2 text-sm text-slate-500">Seçili <span x-text="selected.length"></span> satış silinecek. Ödeme alınmış satış varsa işlem iptal edilir.</p>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" @click="showBulkDeleteModal = false" class="btn-secondary">İptal</button>
                <button type="button" @click="submitBulkDelete()" class="btn-delete">Sil</button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function register() {
        Alpine.data('salesBulk', function() {
            var el = this.$el;
            var idsJson = el && el.getAttribute ? el.getAttribute('data-sale-ids') : '[]';
            var items = [];
            try { items = JSON.parse(idsJson || '[]'); } catch (e) {}
            return {
                items: items,
                selected: [],
                showBulkDeleteModal: false,
                toggleAll: function(checked) {
                    this.selected = checked ? this.items.slice() : [];
                    var self = this;
                    this.$nextTick(function() {
                        document.querySelectorAll('.sale-row-check').forEach(function(cb) { cb.checked = checked; });
                    });
                },
                toggleRow: function(id, checked) {
                    if (checked) this.selected.push(id);
                    else this.selected = this.selected.filter(function(x) { return x !== id; });
                },
                submitBulkDelete: function() {
                    this.showBulkDeleteModal = false;
                    var sel = this.selected;
                    var container = document.getElementById('sales-bulk-form-ids');
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
                    document.getElementById('sales-bulk-form').submit();
                }
            };
        });
    }
    if (typeof Alpine !== 'undefined') register();
    else document.addEventListener('alpine:init', register);
})();
</script>
@endsection
