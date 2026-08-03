@php
    $linkType = old('linkType', $linkType ?? '');
    $purchaseId = old('purchaseId', $selectedPurchaseId ?? ($preselectedPurchaseId ?? ''));
    $saleIds = old('saleIds', $selectedSaleIds ?? ($preselectedSaleIds ?? []));
    $serviceTicketIds = old('serviceTicketIds', $selectedServiceTicketIds ?? ($preselectedServiceTicketIds ?? []));
    if (! is_array($saleIds)) {
        $saleIds = array_filter([(string) $saleIds]);
    }
    if (! is_array($serviceTicketIds)) {
        $serviceTicketIds = array_filter([(string) $serviceTicketIds]);
    }
    $paymentFor = old('paymentFor', $selectedPaymentFor ?? '');
    $requireLinkType = $requireLinkType ?? true;
@endphp

<div class="space-y-4" id="shipping-payment-links">
    <div>
        <label for="payment-link-type" class="form-label">Ödeme türü @if($requireLinkType)<span class="text-red-500">*</span>@endif</label>
        <select id="payment-link-type" name="linkType" class="form-select min-h-[44px]" @if($requireLinkType) required @endif>
            <option value="" disabled {{ $linkType === '' ? 'selected' : '' }}>Seçiniz</option>
            <option value="sale" {{ $linkType === 'sale' ? 'selected' : '' }}>Ürün teslimatı ödemesi</option>
            <option value="service_ticket" {{ $linkType === 'service_ticket' ? 'selected' : '' }}>SSH ödemesi</option>
            <option value="purchase" {{ $linkType === 'purchase' ? 'selected' : '' }}>Alış nakliyesi</option>
            <option value="manual" {{ $linkType === 'manual' ? 'selected' : '' }}>Manuel açıklama</option>
        </select>
        <p class="mt-1 text-xs text-neutral-500">Ürün teslimatında birden fazla sipariş, SSH ödemesinde birden fazla SSH kaydı seçilebilir (isteğe bağlı).</p>
        @error('linkType')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div id="payment-link-sale-wrap" class="{{ $linkType === 'sale' ? '' : 'hidden' }}">
        <label for="payment-sale-id" class="form-label">Siparişler <span class="text-neutral-400 text-xs font-normal">(isteğe bağlı, çoklu)</span></label>
        <select id="payment-sale-id" multiple @if($linkType === 'sale') name="saleIds[]" @endif class="form-select min-h-[44px]">
            @foreach($sales as $s)
            <option value="{{ $s->id }}" {{ in_array((string) $s->id, array_map('strval', $saleIds), true) ? 'selected' : '' }}>
                {{ $s->saleNumber }} — {{ $s->customer?->name ?? 'Müşteri' }} ({{ $s->saleDate?->format('d.m.Y') }}) · {{ number_format((float) ($s->grandTotal ?? 0), 0, ',', '.') }} ₺
            </option>
            @endforeach
        </select>
        @error('saleIds')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        @error('saleIds.*')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div id="payment-link-ssh-wrap" class="{{ $linkType === 'service_ticket' ? '' : 'hidden' }}">
        <label for="payment-service-ticket-id" class="form-label">SSH kayıtları <span class="text-neutral-400 text-xs font-normal">(isteğe bağlı, çoklu)</span></label>
        <select id="payment-service-ticket-id" multiple @if($linkType === 'service_ticket') name="serviceTicketIds[]" @endif class="form-select min-h-[44px]">
            @foreach($serviceTickets as $t)
            <option value="{{ $t->id }}" {{ in_array((string) $t->id, array_map('strval', $serviceTicketIds), true) ? 'selected' : '' }}>
                {{ $t->ticketNumber }} — {{ $t->customer?->name ?? 'Müşteri' }}
                @if($t->sale?->saleNumber) ({{ $t->sale->saleNumber }}) @endif
                · {{ $t->openedAt?->format('d.m.Y') ?? '—' }}
            </option>
            @endforeach
        </select>
        @if($serviceTickets->isEmpty())
        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Kayıtlı SSH bulunamadı. Ödemeyi kayıt seçmeden de oluşturabilirsiniz.</p>
        @endif
        @error('serviceTicketIds')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        @error('serviceTicketIds.*')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div id="payment-link-purchase-wrap" class="{{ $linkType === 'purchase' ? '' : 'hidden' }}">
        <label for="payment-purchase-id" class="form-label">Alış faturası <span class="text-red-500">*</span></label>
        <select id="payment-purchase-id" name="purchaseId" class="form-select min-h-[44px]" @if($linkType === 'purchase') required @endif>
            <option value="">Seçiniz</option>
            @foreach($purchasesWithShipping as $p)
            <option value="{{ $p->id }}" {{ (string) $purchaseId === (string) $p->id ? 'selected' : '' }}>
                {{ $p->purchaseNumber }} — {{ $p->supplier?->name }} ({{ $p->purchaseDate?->format('d.m.Y') }})
            </option>
            @endforeach
        </select>
        @if($purchasesWithShipping->isEmpty())
        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Bu firmaya bağlı alış kaydı yok.</p>
        @endif
        @error('purchaseId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div id="payment-link-manual-wrap" class="{{ $linkType === 'manual' ? '' : 'hidden' }}">
        <label for="payment-for-manual" class="form-label">Manuel açıklama <span class="text-red-500">*</span></label>
        <input id="payment-for-manual" type="text" name="paymentFor" value="{{ $paymentFor }}" class="form-input min-h-[44px]" placeholder="Örn: İstanbul sevkiyatı, depo transferi..." @if($linkType === 'manual') required @endif>
        @error('paymentFor')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
</div>

@once
@push('scripts')
<script src="{{ asset('js/shipping-payment-links.js') }}?v=2"></script>
@endpush
@endonce
