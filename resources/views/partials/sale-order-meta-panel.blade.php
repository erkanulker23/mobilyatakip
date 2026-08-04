@php
    $metaRows = \App\Support\SaleDocument::orderMetaRows($sale);
    $iconMap = [
        'saleDate' => ['class' => 'date', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'dueDate' => ['class' => 'termin', 'path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        'personnel' => ['class' => 'person', 'path' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        'payment' => ['class' => 'payment', 'path' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
        'delivery' => ['class' => 'delivery', 'path' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        'measurement' => ['class' => 'measure', 'path' => 'M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4'],
    ];
@endphp

@if(!empty($metaRows))
@include('partials.sale-order-meta-styles')

<section class="sale-order-meta mb-6" aria-label="Sipariş özeti">
    <div class="sale-order-meta__card">
        <div class="sale-order-meta__header">
            <p class="sale-order-meta__title">Sipariş Özeti</p>
            <p class="sale-order-meta__subtitle">{{ $sale->saleNumber }}@if($sale->customer) · {{ $sale->customer->name }}@endif</p>
        </div>
        <dl class="sale-order-meta__grid">
            @foreach($metaRows as $row)
            @php $icon = $iconMap[$row['key'] ?? ''] ?? ['class' => 'date', 'path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z']; @endphp
            <div class="sale-order-meta__item">
                <span class="sale-order-meta__icon sale-order-meta__icon--{{ $icon['class'] }}" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['path'] }}"></path>
                    </svg>
                </span>
                <div class="sale-order-meta__body">
                    <dt class="sale-order-meta__label">{{ $row['label'] }}</dt>
                    <dd class="sale-order-meta__value @if(!empty($row['statusKey']) || !empty($row['deliveryKey'])) sale-order-meta__value--badge @endif">
                        @if(!empty($row['statusKey']))
                            @include('partials.payment-status-badge', ['status' => ['key' => $row['statusKey'], 'label' => $row['value']]])
                        @elseif(!empty($row['deliveryKey']))
                            @include('partials.delivery-status-badge', ['sale' => $sale])
                        @else
                            {{ $row['value'] }}
                        @endif
                    </dd>
                </div>
            </div>
            @endforeach
        </dl>
    </div>
</section>
@endif
