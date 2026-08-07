@php
    use App\Support\SaleDelivery;

    $termin = SaleDelivery::terminListMeta($sale);
    $orderStatus = SaleDelivery::currentStatus($sale);
    $canOpen = ! SaleDelivery::isDelivered($sale) || auth()->user()?->isAdmin();
    $inProduction = $orderStatus === SaleDelivery::IN_PRODUCTION;
    $stages = ! empty($productionStagesReady) ? $sale->productionStages : collect();
@endphp
<article class="border-b border-neutral-100 dark:border-neutral-800 last:border-b-0">
    <div class="p-4 sm:p-6 space-y-5">
        {{-- Başlık --}}
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $sale->saleNumber }}</h2>
                    <span class="badge {{ $inProduction ? 'badge-blue' : 'badge-neutral' }}">{{ SaleDelivery::label($orderStatus) }}</span>
                    @if(($sale->open_deficiencies_count ?? 0) > 0)
                    <span class="badge badge-amber">{{ $sale->open_deficiencies_count }} açık eksiklik</span>
                    @endif
                    @if($sale->workshopCompletedAt)
                    <span class="badge badge-green">Atölyeden çıktı</span>
                    @endif
                </div>
                <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-x-6 gap-y-1 text-sm">
                    @if($showCustomerNames && $sale->customer?->name)
                    <div><dt class="text-neutral-500 inline">Müşteri: </dt><dd class="inline font-medium text-neutral-800 dark:text-neutral-200">{{ $sale->customer->name }}</dd></div>
                    @endif
                    @if($showSalesPersonnel && $sale->personnel?->name)
                    <div><dt class="text-neutral-500 inline">Satış temsilcisi: </dt><dd class="inline font-medium">{{ $sale->personnel->name }}</dd></div>
                    @endif
                    <div>
                        <dt class="text-neutral-500 inline">Termin: </dt>
                        <dd class="inline font-medium {{ $termin['class'] ?? '' }}">
                            @if($sale->dueDate)
                            {{ $sale->dueDate->format('d.m.Y') }}
                            @if($termin['suffix'] ?? null)
                            <span class="text-xs">({{ $termin['suffix'] }})</span>
                            @endif
                            @else — @endif
                        </dd>
                    </div>
                    @if($sale->workshopCompletedAt)
                    <div><dt class="text-neutral-500 inline">Atölye bitiş: </dt><dd class="inline font-medium text-emerald-700 dark:text-emerald-300">{{ $sale->workshopCompletedAt->format('d.m.Y H:i') }}</dd></div>
                    @elseif($sale->saleDate)
                    <div><dt class="text-neutral-500 inline">Sipariş: </dt><dd class="inline font-medium">{{ $sale->saleDate->format('d.m.Y') }}</dd></div>
                    @endif
                </dl>
                @if($showCustomerNames && ($sale->customer?->full_address ?? $sale->customer?->address ?? null))
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400 whitespace-pre-wrap">{{ $sale->customer->full_address ?? $sale->customer->address }}</p>
                @endif
                @if($sale->notes && ($showCustomerNames || empty($hideCommercialData)))
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400"><span class="font-medium text-neutral-700 dark:text-neutral-300">Sipariş notu:</span> {{ $sale->notes }}</p>
                @endif
            </div>
            @if($canOpen)
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('workshop.show', $sale) }}" class="btn-view text-sm py-2.5">
                    {{ $inProduction ? 'Not ekle / düzenle' : 'Tam detay' }}
                </a>
            </div>
            @endif
        </div>

        {{-- Ürünler --}}
        @if($sale->items->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-white mb-2">Sipariş Kalemleri</h3>
            <div class="overflow-x-auto rounded-xl border border-neutral-100 dark:border-neutral-800">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-neutral-50 dark:bg-neutral-900/50 border-b border-neutral-100 dark:border-neutral-800">
                            <th class="table-th">Ürün</th>
                            <th class="table-th">Kod</th>
                            <th class="table-th">Adet</th>
                            <th class="table-th">Açıklama</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @foreach($sale->items as $item)
                        @php
                            $itemName = $item->productName ?? $item->product?->name ?? 'Ürün';
                        @endphp
                        <tr>
                            <td class="table-td font-medium">{{ $itemName }}</td>
                            <td class="table-td text-neutral-500">{{ $item->product?->sku ?: '—' }}</td>
                            <td class="table-td">{{ $item->quantity }}</td>
                            <td class="table-td text-neutral-600 dark:text-neutral-400 whitespace-pre-wrap">{{ $item->description ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Atölye kayıtları --}}
        <div>
            <div class="flex items-center justify-between gap-2 mb-2">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">Atölye İşlem Geçmişi</h3>
                @if($stages->isNotEmpty())
                <span class="text-xs text-neutral-500">{{ $stages->count() }} kayıt</span>
                @endif
            </div>
            @if($stages->isEmpty())
            <p class="text-sm text-neutral-500 rounded-xl border border-dashed border-neutral-200 dark:border-neutral-700 px-4 py-6 text-center">Henüz atölye kaydı yok.</p>
            @else
            <div class="space-y-3">
                @foreach($stages as $stage)
                    @include('workshop.partials.production-stage-item', ['stage' => $stage, 'showActions' => false])
                @endforeach
            </div>
            @endif
        </div>
    </div>
</article>
