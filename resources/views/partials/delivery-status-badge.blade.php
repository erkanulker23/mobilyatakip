@if(isset($sale) && \App\Support\SaleDelivery::isDelivered($sale))
<span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full {{ \App\Support\SaleDelivery::badgeClass() }}">
    Teslim edildi
</span>
@endif
