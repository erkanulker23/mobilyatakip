@extends('layouts.app')
@section('title', 'Satışlar')

@section('content')
@php
    $activeFilters = request()->hasAny(['search', 'customerId', 'deliveryStatus', 'paymentStatus', 'from', 'to']);
    $filterChip = fn (array $params) => route('sales.index', array_filter(array_merge(request()->only(['search', 'customerId', 'from', 'to', 'paymentStatus', 'deliveryStatus']), $params)));
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
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam sipariş</p>
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
        <form method="GET" id="salesFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
            <div class="sm:col-span-2 xl:col-span-2">
                <label for="salesSearchInput" class="form-label">Ara</label>
                <input type="text" name="search" id="salesSearchInput" placeholder="Sipariş no veya müşteri..." value="{{ request('search') }}" class="form-input w-full" autocomplete="off">
            </div>
            <div>
                <label class="form-label">Müşteri</label>
                <select name="customerId" class="form-select w-full">
                    <option value="">Tümü</option>
                    @foreach($customers ?? [] as $c)
                    <option value="{{ $c->id }}" {{ request('customerId') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
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
            <a href="{{ $filterChip(['deliveryStatus' => \App\Support\SaleDelivery::PENDING, 'paymentStatus' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ request('deliveryStatus') === \App\Support\SaleDelivery::PENDING ? 'bg-neutral-700 text-white dark:bg-neutral-500' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-700' }}">Teslim bekliyor</a>
        </div>
    </div>

    <div class="px-4 sm:px-5 py-3 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-3 flex-wrap">
        <span class="text-sm text-neutral-500">
            @if($sales->total() === 0)
                Kayıt bulunamadı
            @elseif($sales->total() === 1)
                1 sipariş
            @else
                {{ number_format($sales->total(), 0, ',', '.') }} sipariş
                @if($sales->hasPages())
                    · sayfa {{ $sales->currentPage() }}/{{ $sales->lastPage() }}
                @endif
            @endif
            @if($activeFilters)
                <span class="text-neutral-400"> · filtre aktif</span>
            @endif
        </span>
        <div class="flex items-center gap-3" x-show="selected.length > 0">
            <span class="text-sm text-neutral-500" x-text="selected.length + ' seçildi'"></span>
            <button type="button" @click="showBulkDeleteModal = true" class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
                Seçilenleri sil
            </button>
        </div>
    </div>

    <form id="sales-bulk-form" method="POST" action="{{ route('sales.bulk-destroy') }}" class="hidden">
        @csrf
        <div id="sales-bulk-form-ids"></div>
    </form>

    <div class="overflow-x-auto -mx-px">
        <table class="min-w-full sales-index-table">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-neutral-800">
                    <th class="table-th w-10 col-hide-mobile">
                        <input type="checkbox" class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleAll($event.target.checked)" :checked="selected.length === items.length && items.length > 0">
                    </th>
                    <th class="table-th">Sipariş</th>
                    <th class="table-th col-hide-mobile whitespace-nowrap">Tarih / Termin</th>
                    <th class="table-th text-right whitespace-nowrap">Tutar</th>
                    <th class="table-th">Durum</th>
                    <th class="table-th text-right w-36 sm:w-44">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $s)
                @php
                    $saleStatus = \App\Support\CustomerBalance::saleStatus($s);
                    $orderStatus = \App\Support\SaleDelivery::currentStatus($s);
                    $remaining = \App\Support\CustomerBalance::saleRemaining($s);
                    $saleNumberClass = \App\Support\SaleDelivery::numberClassFor($s);
                    $terminMeta = \App\Support\SaleDelivery::terminListMeta($s);
                @endphp
                <tr class="border-b border-neutral-50 dark:border-neutral-800/60 hover:bg-neutral-50/50 dark:hover:bg-neutral-900/40 transition-colors {{ ($s->isCancelled ?? false) ? 'opacity-60 bg-slate-50 dark:bg-slate-800/30' : '' }}">
                    <td class="table-td col-hide-mobile">
                        <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="sale-row-check rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleRow('{{ $s->id }}', $event.target.checked)">
                    </td>
                    <td class="table-td min-w-[10rem]">
                        <a href="{{ route('sales.show', $s) }}" class="font-medium hover:underline {{ $saleNumberClass }}">{{ $s->saleNumber }}</a>
                        @if($s->isCancelled ?? false)
                            <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-md bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-300 font-medium">İptal</span>
                        @endif
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 truncate mt-0.5">{{ $s->customer?->name ?? '—' }}</p>
                        @if($s->personnel)
                            <p class="text-xs text-neutral-400 mt-0.5 col-hide-mobile">{{ $s->personnel->name }}</p>
                        @endif
                        @if($s->needsFinalMeasurement ?? false)
                            <span class="inline-block mt-1">@include('partials.final-measurement-badge', ['sale' => $s])</span>
                        @endif
                        <span class="block mt-1 text-xs text-neutral-400 md:hidden">
                            {{ $s->saleDate?->format('d.m.Y') ?? '—' }}
                            @if($terminMeta['date'])
                                · {{ $terminMeta['prefix'] }} {{ $terminMeta['date']->format('d.m.Y') }}
                                @if($terminMeta['suffix'])
                                    · {{ $terminMeta['suffix'] }}
                                @endif
                            @endif
                        </span>
                    </td>
                    <td class="table-td col-hide-mobile whitespace-nowrap">
                        <p class="text-neutral-900 dark:text-neutral-100">{{ $s->saleDate?->format('d.m.Y') ?? '—' }}</p>
                        @if($terminMeta['empty'])
                            <p class="text-xs text-neutral-400 mt-0.5">{{ $terminMeta['empty'] }}</p>
                        @elseif($terminMeta['date'])
                            <p class="text-xs mt-0.5 {{ $terminMeta['class'] }}">
                                {{ $terminMeta['prefix'] }} {{ $terminMeta['date']->format('d.m.Y') }}
                                @if($terminMeta['suffix'])
                                    · {{ $terminMeta['suffix'] }}
                                @endif
                            </p>
                        @endif
                    </td>
                    <td class="table-td text-right whitespace-nowrap">
                        <p class="font-semibold text-neutral-900 dark:text-neutral-100 tabular-nums">{{ number_format($s->grandTotal ?? 0, 0, ',', '.') }} ₺</p>
                        @if($remaining > 0.005)
                            <p class="text-xs font-medium text-red-600 dark:text-red-400 tabular-nums mt-0.5">{{ number_format($remaining, 0, ',', '.') }} ₺ kalan</p>
                        @elseif($remaining < -0.005)
                            <p class="text-xs font-medium amount-negative tabular-nums mt-0.5">{{ number_format(abs($remaining), 0, ',', '.') }} ₺ fazla</p>
                        @else
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5">Ödendi</p>
                        @endif
                        @if((float) ($s->paidAmount ?? 0) > 0 && $remaining > 0.005)
                            <p class="text-xs text-neutral-400 tabular-nums mt-0.5 col-hide-mobile">{{ number_format($s->paidAmount ?? 0, 0, ',', '.') }} ₺ alındı</p>
                        @endif
                    </td>
                    <td class="table-td min-w-[7rem]">
                        @if(!($s->isCancelled ?? false))
                        <div class="flex flex-col items-start gap-1">
                            @include('partials.payment-status-badge', ['status' => $saleStatus])
                            @include('partials.delivery-status-badge', ['sale' => $s])
                            @if($orderStatus === \App\Support\SaleDelivery::PENDING && !($s->needsFinalMeasurement ?? false))
                                <span class="text-xs text-neutral-400">Teslim bekliyor</span>
                            @endif
                        </div>
                        @else
                            <span class="text-neutral-400">—</span>
                        @endif
                    </td>
                    <td class="table-td">
                        <div class="flex items-center justify-end gap-1 flex-wrap sm:flex-nowrap">
                            @include('partials.action-buttons', [
                                'show' => route('sales.show', $s),
                                'edit' => !($s->isCancelled ?? false) ? route('sales.edit', $s) : null,
                                'print' => route('sales.print', $s),
                                'shipment' => !($s->isCancelled ?? false) ? route('sales.shipment', $s) : null,
                                'destroy' => route('sales.destroy', $s),
                            ])
                            @if(!($s->isCancelled ?? false))
                            <form method="POST" action="{{ route('sales.convert-to-quote', $s) }}" class="inline-flex" onsubmit="return confirm('Bu kayıt teklif olarak devam edecek. Satış listesinden kaldırılır; teklifler bölümünde kalır. Devam?');">
                                @csrf
                                <button type="submit" title="Teklife Dönüştür" aria-label="Teklife dönüştür" class="p-2 rounded-lg bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <p class="text-neutral-500 text-sm">Filtreye uygun satış bulunamadı.</p>
                        @if($activeFilters)
                            <a href="{{ route('sales.index') }}" class="btn-secondary mt-4 text-sm">Filtreleri temizle</a>
                        @else
                            <a href="{{ route('sales.create') }}" class="btn-primary mt-4 text-sm">Satış oluştur</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sales->hasPages())
    <div class="px-4 sm:px-5 py-3 border-t border-neutral-100 dark:border-neutral-800">{{ $sales->links() }}</div>
    @endif

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

<script>
(function () {
    const input = document.getElementById('salesSearchInput');
    const form = document.getElementById('salesFilterForm');
    if (input && form) {
        let timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => form.submit(), 450);
        });
        if (input.value !== '') {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }
    }

    function register() {
        Alpine.data('salesBulk', function () {
            var el = this.$el;
            var idsJson = el && el.getAttribute ? el.getAttribute('data-sale-ids') : '[]';
            var items = [];
            try { items = JSON.parse(idsJson || '[]'); } catch (e) {}
            return {
                items: items,
                selected: [],
                showBulkDeleteModal: false,
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
    if (typeof Alpine !== 'undefined') register();
    else document.addEventListener('alpine:init', register);
})();
</script>
@endsection
