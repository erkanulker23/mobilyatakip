@if(isset($sale) && !($sale->isCancelled ?? false))
@php($orderStatus = \App\Support\SaleDelivery::currentStatus($sale))
@if($orderStatus !== \App\Support\SaleDelivery::PENDING)
<span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full {{ \App\Support\SaleDelivery::badgeClass($orderStatus) }}">
    {{ \App\Support\SaleDelivery::label($orderStatus) }}
</span>
@endif
@endif
