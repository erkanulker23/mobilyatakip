@php
    $status = $status ?? null;
    if (!$status && isset($sale)) {
        $status = \App\Support\CustomerBalance::saleStatus($sale);
    }
@endphp
@if($status)
<span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full {{ \App\Support\CustomerBalance::badgeClass($status['key']) }}">
    {{ $status['label'] }}
</span>
@endif
