@php
    /** @var \App\Models\ShippingCompanyPayment $payment */
    $linkedSales = $payment->relationLoaded('sales')
        ? $payment->sales
        : ($payment->sales()->with('customer')->get());
    if ($linkedSales->isEmpty() && $payment->sale) {
        $linkedSales = collect([$payment->sale]);
    }

    $linkedTickets = $payment->relationLoaded('serviceTickets')
        ? $payment->serviceTickets
        : ($payment->serviceTickets()->with('customer')->get());
    if ($linkedTickets->isEmpty() && $payment->serviceTicket) {
        $linkedTickets = collect([$payment->serviceTicket]);
    }
@endphp

@if($payment->purchase)
    <a href="{{ route('purchases.show', $payment->purchase) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">Alış: {{ $payment->purchase->purchaseNumber }}</a>
@elseif($linkedSales->isNotEmpty())
    <div class="space-y-1">
        @foreach($linkedSales as $sale)
        <div>
            <a href="{{ route('sales.show', $sale) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">Sipariş: {{ $sale->saleNumber }}</a>
            @if($sale->customer?->name)
            <span class="text-neutral-500 dark:text-slate-400 text-xs"> · {{ $sale->customer->name }}</span>
            @endif
        </div>
        @endforeach
    </div>
@elseif($linkedTickets->isNotEmpty())
    <div class="space-y-1">
        @foreach($linkedTickets as $ticket)
        <div>
            <a href="{{ route('service-tickets.show', $ticket) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">SSH: {{ $ticket->ticketNumber }}</a>
            @if($ticket->customer?->name)
            <span class="text-neutral-500 dark:text-slate-400 text-xs"> · {{ $ticket->customer->name }}</span>
            @endif
        </div>
        @endforeach
    </div>
@elseif($payment->paymentFor)
    {{ $payment->paymentFor }}
@elseif($payment->reference)
    {{ $payment->reference }}
@else
    —
@endif
