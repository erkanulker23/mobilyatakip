@php
    $linkType = old('linkType', $linkType ?? '');
    $purchaseId = old('purchaseId', $selectedPurchaseId ?? '');
    $saleId = old('saleId', $selectedSaleId ?? '');
    $serviceTicketId = old('serviceTicketId', $selectedServiceTicketId ?? '');
    $paymentFor = old('paymentFor', $selectedPaymentFor ?? '');
@endphp

<div class="space-y-4" x-data="{ linkType: @json($linkType) }">
    <div>
        <label for="payment-link-type" class="form-label">Ne için ödendi?</label>
        <select id="payment-link-type" name="linkType" x-model="linkType" class="form-select min-h-[44px]">
            <option value="">Genel ödeme</option>
            <option value="purchase">Alış nakliyesi</option>
            <option value="sale">Satış sevkiyatı</option>
            <option value="service_ticket">SSH (servis)</option>
            <option value="manual">Manuel açıklama</option>
        </select>
        <p class="mt-1 text-xs text-neutral-500">Ödemenin hangi işlem için yapıldığını seçin veya manuel yazın</p>
        @error('linkType')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div x-show="linkType === 'purchase'" x-cloak>
        <label for="payment-purchase-id" class="form-label">Alış faturası</label>
        <select id="payment-purchase-id" name="purchaseId" class="form-select min-h-[44px]" :disabled="linkType !== 'purchase'">
            <option value="">Seçiniz</option>
            @foreach($purchasesWithShipping as $p)
            <option value="{{ $p->id }}" {{ (string) $purchaseId === (string) $p->id ? 'selected' : '' }}>
                {{ $p->purchaseNumber }} — {{ $p->supplier?->name }} ({{ $p->purchaseDate?->format('d.m.Y') }})
            </option>
            @endforeach
        </select>
        @if($purchasesWithShipping->isEmpty())
        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Bu firmaya bağlı alış kaydı yok. Satış, SSH veya manuel seçebilirsiniz.</p>
        @endif
        @error('purchaseId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div x-show="linkType === 'sale'" x-cloak>
        <label for="payment-sale-id" class="form-label">Satış</label>
        <select id="payment-sale-id" name="saleId" class="form-select min-h-[44px]" :disabled="linkType !== 'sale'">
            <option value="">Satış seçiniz</option>
            @foreach($sales as $s)
            <option value="{{ $s->id }}" {{ (string) $saleId === (string) $s->id ? 'selected' : '' }}>
                {{ $s->saleNumber }} — {{ $s->customer?->name ?? 'Müşteri' }} ({{ $s->saleDate?->format('d.m.Y') }}) · {{ number_format((float) ($s->grandTotal ?? 0), 0, ',', '.') }} ₺
            </option>
            @endforeach
        </select>
        @error('saleId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div x-show="linkType === 'service_ticket'" x-cloak>
        <label for="payment-service-ticket-id" class="form-label">SSH kaydı</label>
        <select id="payment-service-ticket-id" name="serviceTicketId" class="form-select min-h-[44px]" :disabled="linkType !== 'service_ticket'">
            <option value="">SSH seçiniz</option>
            @foreach($serviceTickets as $t)
            <option value="{{ $t->id }}" {{ (string) $serviceTicketId === (string) $t->id ? 'selected' : '' }}>
                {{ $t->ticketNumber }} — {{ $t->customer?->name ?? 'Müşteri' }}
                @if($t->sale?->saleNumber) ({{ $t->sale->saleNumber }}) @endif
                · {{ $t->openedAt?->format('d.m.Y') ?? '—' }}
            </option>
            @endforeach
        </select>
        @error('serviceTicketId')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div x-show="linkType === 'manual'" x-cloak>
        <label for="payment-for-manual" class="form-label">Manuel açıklama</label>
        <input id="payment-for-manual" type="text" name="paymentFor" value="{{ $paymentFor }}" class="form-input min-h-[44px]" placeholder="Örn: İstanbul sevkiyatı, depo transferi..." :disabled="linkType !== 'manual'">
        @error('paymentFor')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    ['payment-sale-id', 'payment-service-ticket-id'].forEach(function (id) {
        const el = document.getElementById(id);
        if (!el || typeof TomSelect === 'undefined') return;
        new TomSelect(el, {
            maxOptions: 200,
            placeholder: el.id === 'payment-sale-id' ? 'Satış ara veya seçin...' : 'SSH ara veya seçin...',
            searchField: ['text'],
            allowEmptyOption: true,
        });
    });
});
</script>
