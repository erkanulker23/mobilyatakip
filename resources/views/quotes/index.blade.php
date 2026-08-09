@extends('layouts.app')
@section('title', 'Teklifler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Teklifler</h1>
        <p class="page-desc">Teklif listesi ve satışa dönüştürme</p>
    </div>
    <a href="{{ route('quotes.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
        Teklif Oluştur
    </a>
</div>

<div class="card overflow-hidden" x-data="quotesBulk" data-quote-ids='{{ json_encode($quoteIds ?? []) }}'>
    <div class="p-4 border-b border-neutral-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="min-w-[180px] flex-1">
                <label class="form-label">Ara (no, müşteri)</label>
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
            <div class="min-w-[160px]">
                <label class="form-label">Personel</label>
                <select name="personnelId" class="form-select">
                    <option value="">Tümü</option>
                    @foreach($personnel ?? [] as $p)
                    <option value="{{ $p->id }}" {{ request('personnelId') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[140px]">
                <label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <option value="">Tümü</option>
                    <option value="taslak" {{ request('status') === 'taslak' ? 'selected' : '' }}>Taslak</option>
                    <option value="onaylandi" {{ request('status') === 'onaylandi' ? 'selected' : '' }}>Onaylandı</option>
                    <option value="reddedildi" {{ request('status') === 'reddedildi' ? 'selected' : '' }}>Reddedildi</option>
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
                <a href="{{ route('quotes.index') }}" class="btn-secondary">Temizle</a>
            </div>
        </form>
    </div>
    <div class="px-5 py-3 border-b border-neutral-100 flex items-center justify-between gap-4 flex-wrap" x-show="selected.length > 0">
        <span class="text-sm text-neutral-500" x-text="selected.length + ' teklif seçildi'"></span>
        <button type="button" @click="showBulkDeleteModal = true" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
            Seçilenleri sil
        </button>
    </div>
    <form id="quotes-bulk-form" method="POST" action="{{ route('quotes.bulk-destroy') }}" class="hidden">
        @csrf
        <div id="quotes-bulk-form-ids"></div>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100">
                    <th class="table-th w-12">
                        <input type="checkbox" class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleAll($event.target.checked)" :checked="selected.length === items.length && items.length > 0">
                    </th>
                    <th class="table-th">No</th>
                    <th class="table-th">Müşteri</th>
                        <th class="table-th">Oluşturan</th>
                    <th class="table-th">Tarih</th>
                    <th class="table-th text-right">Toplam</th>
                    <th class="table-th">Durum</th>
                    <th class="table-th text-right w-48">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotes as $q)
                <tr class="border-b border-neutral-50 hover:bg-neutral-50/50 transition-colors {{ $q->convertedSaleId ? 'opacity-80' : '' }}">
                    <td class="table-td">
                        @if(!$q->convertedSaleId)
                        <input type="checkbox" name="ids[]" value="{{ $q->id }}" class="quote-row-check rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleRow('{{ $q->id }}', $event.target.checked)">
                        @endif
                    </td>
                    <td class="table-td">
                        <span class="font-medium text-neutral-900">{{ $q->quoteNumber }}</span>
                    </td>
                    <td class="table-td">{{ $q->customer?->name ?? '-' }}</td>
                    <td class="table-td text-neutral-600">{{ \App\Support\QuoteCreator::displayNameForQuote($q, $creatorFallbackMap ?? []) ?? '—' }}</td>
                    <td class="table-td">{{ $q->createdAt?->format('d.m.Y') ?? '-' }}</td>
                    <td class="table-td text-right font-medium text-neutral-900">{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="table-td">
                        @if($q->convertedSaleId)
                        <a href="{{ route('sales.show', $q->convertedSale) }}" class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300">
                            Siparişe dönüştürüldü
                        </a>
                        @else
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $q->status === 'taslak' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : ($q->status === 'onaylandi' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-neutral-400') }}">{{ ucfirst($q->status ?? '-') }}</span>
                        @endif
                    </td>
                    <td class="table-td">
                        <div class="flex items-center justify-end gap-1">
                            @include('partials.action-buttons', [
                                'show' => route('quotes.show', $q),
                                'edit' => !$q->convertedSaleId ? route('quotes.edit', $q) : null,
                                'print' => route('quotes.print', $q),
                                'destroy' => !$q->convertedSaleId ? route('quotes.destroy', $q) : null,
                            ])
                            <form method="POST" action="{{ route('quotes.duplicate', $q) }}" class="inline-flex" onsubmit="return confirm('Bu tekliften yeni bir teklif oluşturulsun mu?');">
                                @csrf
                                <button type="submit" title="Teklif Çoğalt" aria-label="Teklif Çoğalt" class="p-2 rounded-lg bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                            </form>
                            @if(!$q->convertedSaleId && ($q->status ?? '') == 'taslak')
                            <form method="POST" action="{{ route('quotes.convert', $q) }}" class="inline-flex ml-1" onsubmit="return confirm('Bu teklifi satışa dönüştürmek istediğinize emin misiniz?');">
                                @csrf
                                <button type="submit" title="Satışa Dönüştür" class="p-2 rounded-lg bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                        <div class="max-w-sm mx-auto">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-neutral-800 flex items-center justify-center mx-auto">
                                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <p class="mt-4 text-neutral-500 text-sm">Filtreye uygun teklif bulunamadı.</p>
                            <p class="mt-1 text-sm text-slate-400">Yeni teklif eklemek için aşağıdaki butonu kullanın.</p>
                            <a href="{{ route('quotes.create') }}" class="btn-primary mt-4">Teklif oluştur</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-100 text-sm text-neutral-500">{{ $quotes->links() }}</div>

    <div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div x-show="showBulkDeleteModal" x-transition class="fixed inset-0 bg-black/50" @click="showBulkDeleteModal = false"></div>
        <div x-show="showBulkDeleteModal" x-transition class="relative card max-w-sm w-full p-6">
            <h2 class="text-base font-semibold text-slate-900 dark:text-neutral-100">Toplu teklif silme</h2>
            <p class="mt-2 text-sm text-neutral-500">Seçili <span x-text="selected.length"></span> teklif silinecek. Satışa dönüştürülmüş teklifler silinemez.</p>
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
        Alpine.data('quotesBulk', function() {
            var el = this.$el;
            var idsJson = el && el.getAttribute ? el.getAttribute('data-quote-ids') : '[]';
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
                        document.querySelectorAll('.quote-row-check').forEach(function(cb) { cb.checked = checked; });
                    });
                },
                toggleRow: function(id, checked) {
                    if (checked) this.selected.push(id);
                    else this.selected = this.selected.filter(function(x) { return x !== id; });
                },
                submitBulkDelete: function() {
                    this.showBulkDeleteModal = false;
                    var sel = this.selected;
                    var container = document.getElementById('quotes-bulk-form-ids');
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
                    document.getElementById('quotes-bulk-form').submit();
                }
            };
        });
    }
    if (typeof Alpine !== 'undefined') register();
    else document.addEventListener('alpine:init', register);
})();
</script>
@endsection
