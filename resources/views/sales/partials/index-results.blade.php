@php
    $listContext = $listContext ?? null;
    $isDeliveredList = $listContext === 'delivered';
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
@endphp
<div id="salesListResults" class="transition-opacity duration-150" data-sale-ids='@json($saleIds ?? [])'>
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

    <div class="overflow-x-auto -mx-px">
        <table class="min-w-full sales-index-table">
            <thead>
                <tr class="border-b border-neutral-100 dark:border-neutral-800">
                    <th class="table-th w-10 col-hide-mobile">
                        <input type="checkbox" class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                               @change="toggleAll($event.target.checked)" :checked="selected.length === items.length && items.length > 0">
                    </th>
                    <th class="table-th">Sipariş</th>
                    <th class="table-th col-hide-mobile whitespace-nowrap">{{ $isDeliveredList ? 'Teslim / Sipariş' : 'Tarih / Termin' }}</th>
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
                            @if($isDeliveredList && $s->deliveredAt)
                                Teslim {{ $s->deliveredAt->format('d.m.Y') }}
                            @else
                                {{ $s->saleDate?->format('d.m.Y') ?? '—' }}
                            @endif
                            @if(!$isDeliveredList && $terminMeta['date'])
                                · {{ $terminMeta['prefix'] }} {{ $terminMeta['date']->format('d.m.Y') }}
                                @if($terminMeta['suffix'])
                                    · {{ $terminMeta['suffix'] }}
                                @endif
                            @endif
                        </span>
                    </td>
                    <td class="table-td col-hide-mobile whitespace-nowrap">
                        @if($isDeliveredList)
                            <p class="font-medium text-indigo-700 dark:text-indigo-300">{{ $s->deliveredAt?->format('d.m.Y') ?? '—' }}</p>
                            <p class="text-xs text-neutral-500 mt-0.5">Sipariş {{ $s->saleDate?->format('d.m.Y') ?? '—' }}</p>
                        @else
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
                        <p class="text-neutral-500 text-sm">{{ $isDeliveredList ? 'Filtreye uygun teslim edilmiş sipariş bulunamadı.' : 'Filtreye uygun satış bulunamadı.' }}</p>
                        @if($activeFilters)
                            <a href="{{ $isDeliveredList ? route('sales.delivered') : route('sales.index') }}" class="btn-secondary mt-4 text-sm">Filtreleri temizle</a>
                        @elseif(!$isDeliveredList)
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
</div>
