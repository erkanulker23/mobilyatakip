@php
    /** @var \App\Models\SupplierPayment $payment */
    $customerPayment = $payment->customerPayment;
@endphp
@if($customerPayment?->sale)
    @php $coverage = \App\Support\CustomerPaymentSaleCoverage::label($customerPayment); @endphp
    <div class="space-y-0.5">
        @if($customerPayment->customer)
            <div class="text-xs text-neutral-500 dark:text-slate-400">{{ $customerPayment->customer->name }}</div>
        @endif
        <div>
            <a href="{{ route('sales.show', $customerPayment->sale) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $customerPayment->sale->saleNumber }}</a>
            @if($coverage)
                <span class="text-xs text-neutral-500 dark:text-slate-400">· {{ $coverage }}</span>
            @endif
        </div>
    </div>
@elseif($payment->purchaseId && $payment->purchase)
    <a href="{{ route('purchases.show', $payment->purchase) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ $payment->purchase->purchaseNumber }}</a>
@else
    {{ $payment->reference ?? '—' }}
@endif
