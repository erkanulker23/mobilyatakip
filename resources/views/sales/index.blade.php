@extends('layouts.app')
@section('title', 'Satışlar')

@section('content')
@php
    $activeFilters = $activeFilters ?? (
        request()->filled('search')
        || request()->filled('customerId')
        || request()->filled('personnelId')
        || \App\Support\SaleDelivery::isFilterValue(request('deliveryStatus'))
        || in_array(request('paymentStatus'), ['borclu', 'alacakli', 'odendi'], true)
        || request()->filled('from')
        || request()->filled('to')
        || request()->boolean('cancelled')
    );
    $filterChip = fn (array $params) => route('sales.index', array_filter(array_merge(request()->only(['search', 'customerId', 'from', 'to', 'paymentStatus', 'deliveryStatus', 'cancelled']), $params)));
@endphp

<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Satışlar</h1>
        <p class="page-desc mt-1">Siparişler, teslimat durumu ve tahsilat takibi</p>
    </div>
    <a href="{{ route('sales.create') }}" class="btn-primary w-full sm:w-auto justify-center">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
        Satış Oluştur
    </a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">{{ $activeFilters ? 'Filtrelenen sipariş' : 'Toplam sipariş' }}</p>
        <p class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100 mt-1">{{ number_format($stats['total'], 0, ',', '.') }}</p>
    </div>
    <div class="card p-4 {{ $stats['receivable'] > 0 ? 'ring-1 ring-amber-200 dark:ring-amber-800/60' : '' }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Alınacak</p>
        <p class="text-xl sm:text-2xl font-semibold {{ $stats['receivable'] > 0 ? 'text-amber-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1 tabular-nums">₺{{ number_format($stats['receivable'], 0, ',', '.') }}</p>
    </div>
    <a href="{{ $filterChip(['paymentStatus' => 'borclu', 'deliveryStatus' => null]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ request('paymentStatus') === 'borclu' ? 'ring-2 ring-red-300 dark:ring-red-700' : ($stats['withDebt'] > 0 ? 'ring-1 ring-red-200 dark:ring-red-800/60' : '') }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Borçlu sipariş</p>
        <p class="text-2xl font-semibold {{ $stats['withDebt'] > 0 ? 'text-red-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1">{{ number_format($stats['withDebt'], 0, ',', '.') }}</p>
    </a>
    <a href="{{ $filterChip(['deliveryStatus' => \App\Support\SaleDelivery::FINAL_MEASUREMENT, 'paymentStatus' => null]) }}" class="card p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors {{ request('deliveryStatus') === \App\Support\SaleDelivery::FINAL_MEASUREMENT ? 'ring-2 ring-amber-300 dark:ring-amber-700' : ($stats['finalMeasurement'] > 0 ? 'ring-1 ring-amber-200 dark:ring-amber-800/60' : '') }}">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Ölçü bekliyor</p>
        <p class="text-2xl font-semibold {{ $stats['finalMeasurement'] > 0 ? 'text-amber-600' : 'text-neutral-900 dark:text-neutral-100' }} mt-1">{{ number_format($stats['finalMeasurement'], 0, ',', '.') }}</p>
    </a>
</div>

<div class="card overflow-hidden" x-data="salesBulk" data-sale-ids='{{ json_encode($saleIds ?? []) }}'>
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800">
        <form method="GET" action="{{ route('sales.index') }}" id="salesFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
            <div class="sm:col-span-2 xl:col-span-2">
                <label for="salesSearchInput" class="form-label">Ara</label>
                <input type="text" name="search" id="salesSearchInput" placeholder="Sipariş no veya müşteri..." value="{{ request('search') }}" class="form-input w-full" autocomplete="off">
            </div>
            <div>
                <label class="form-label">Müşteri</label>
                <select name="customerId" class="form-select w-full">
                    <option value="">Tümü</option>
                    @foreach($filterCustomers ?? [] as $c)
                    <option value="{{ $c->id }}" {{ request('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-neutral-400">Müşteri adı için üstteki arama kutusunu kullanın.</p>
            </div>
            <div>
                <label class="form-label">Teslim durumu</label>
                <select name="deliveryStatus" class="form-select w-full">
                    <option value="">Tümü</option>
                    @foreach(\App\Support\SaleDelivery::filterOptions() as $value => $label)
                    <option value="{{ $value }}" {{ request('deliveryStatus') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Ödeme durumu</label>
                <select name="paymentStatus" class="form-select w-full">
                    <option value="">Tümü</option>
                    <option value="borclu" {{ request('paymentStatus') === 'borclu' ? 'selected' : '' }}>Borçlu</option>
                    <option value="odendi" {{ request('paymentStatus') === 'odendi' ? 'selected' : '' }}>Ödendi</option>
                </select>
            </div>
            <div>
                <label class="form-label">Başlangıç</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-input w-full">
            </div>
            <div>
                <label class="form-label">Bitiş</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-input w-full">
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:col-span-2 xl:col-span-6">
                <button type="submit" class="btn-primary w-full sm:w-auto justify-center">Filtrele</button>
                <a href="{{ route('sales.index') }}" class="btn-secondary w-full sm:w-auto justify-center">Temizle</a>
            </div>
        </form>

        <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800">
            <span class="text-xs text-neutral-400 self-center mr-1">Hızlı filtre:</span>
            <a href="{{ route('sales.index') }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ !$activeFilters ? 'bg-neutral-900 text-white dark:bg-emerald-600' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Tümü</a>
            <a href="{{ $filterChip(['paymentStatus' => 'borclu']) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request('paymentStatus') === 'borclu' && !request('deliveryStatus') ? 'bg-red-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Borçlu</a>
            <a href="{{ $filterChip(['deliveryStatus' => \App\Support\SaleDelivery::FINAL_MEASUREMENT, 'paymentStatus' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request('deliveryStatus') === \App\Support\SaleDelivery::FINAL_MEASUREMENT ? 'bg-amber-500 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Ölçü bekliyor</a>
            <a href="{{ $filterChip(['deliveryStatus' => \App\Support\SaleDelivery::IN_PRODUCTION, 'paymentStatus' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request('deliveryStatus') === \App\Support\SaleDelivery::IN_PRODUCTION ? 'bg-violet-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Üretimde</a>
            <a href="{{ $filterChip(['deliveryStatus' => \App\Support\SaleDelivery::PENDING, 'paymentStatus' => null, 'cancelled' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request('deliveryStatus') === \App\Support\SaleDelivery::PENDING ? 'bg-neutral-700 text-white dark:bg-neutral-500' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Teslim bekliyor</a>
            <a href="{{ $filterChip(['cancelled' => 1, 'deliveryStatus' => null, 'paymentStatus' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request()->boolean('cancelled') ? 'bg-red-600 text-white' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">İptal edilenler{{ ($stats['cancelled'] ?? 0) > 0 ? ' ('.$stats['cancelled'].')' : '' }}</a>
            <a href="{{ route('sales.delivered') }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700">Teslim edilenler →</a>
        </div>
    </div>

    <form id="sales-bulk-form" method="POST" action="{{ route('sales.bulk-destroy') }}" class="hidden">
        @csrf
        <div id="sales-bulk-form-ids"></div>
    </form>

    @include('sales.partials.index-results', compact('sales', 'saleIds', 'activeFilters'))

    <div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="showBulkDeleteModal" x-transition class="fixed inset-0 bg-black/50" @click="showBulkDeleteModal = false"></div>
        <div x-show="showBulkDeleteModal" x-transition class="relative card max-w-sm w-full p-6">
            <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Toplu satış silme</h2>
            <p class="mt-2 text-sm text-neutral-500">Seçili <span x-text="selected.length"></span> satış silinecek. Ödeme alınmış satış varsa işlem iptal edilir.</p>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" @click="showBulkDeleteModal = false" class="btn-secondary">İptal</button>
                <button type="button" @click="submitBulkDelete()" class="btn-delete">Sil</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ route('assets.js', ['file' => 'list-search.js']) }}"></script>
<script>
(function () {
    function registerSalesBulk() {
        Alpine.data('salesBulk', function () {
            var el = this.$el;
            var idsJson = el && el.getAttribute ? el.getAttribute('data-sale-ids') : '[]';
            var items = [];
            try { items = JSON.parse(idsJson || '[]'); } catch (e) {}

            return {
                items: items,
                selected: [],
                showBulkDeleteModal: false,
                init: function () {
                    var self = this;
                    document.addEventListener('sales-results-updated', function (event) {
                        self.items = event.detail.saleIds || [];
                        self.selected = [];
                    });
                },
                toggleAll: function (checked) {
                    this.selected = checked ? this.items.slice() : [];
                    this.$nextTick(function () {
                        document.querySelectorAll('.sale-row-check').forEach(function (cb) { cb.checked = checked; });
                    });
                },
                toggleRow: function (id, checked) {
                    if (checked) this.selected.push(id);
                    else this.selected = this.selected.filter(function (x) { return x !== id; });
                },
                submitBulkDelete: function () {
                    this.showBulkDeleteModal = false;
                    var sel = this.selected;
                    var container = document.getElementById('sales-bulk-form-ids');
                    if (container) {
                        container.innerHTML = '';
                        sel.forEach(function (id) {
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

    if (typeof Alpine !== 'undefined') registerSalesBulk();
    else document.addEventListener('alpine:init', registerSalesBulk);

    document.addEventListener('DOMContentLoaded', function () {
        window.initListSearch({
            formId: 'salesFilterForm',
            inputId: 'salesSearchInput',
            resultsId: 'salesListResults',
            debounceMs: 650,
            onUpdated: function (resultsEl) {
                if (window.Alpine) {
                    window.Alpine.initTree(resultsEl);
                }
                var card = document.querySelector('[data-sale-ids]');
                var saleIds = [];
                try {
                    saleIds = JSON.parse(resultsEl.getAttribute('data-sale-ids') || '[]');
                } catch (e) {}
                if (card) {
                    card.setAttribute('data-sale-ids', JSON.stringify(saleIds));
                }
                document.dispatchEvent(new CustomEvent('sales-results-updated', { detail: { saleIds: saleIds } }));
            },
        });
    });
})();
</script>
@endpush
@endsection
