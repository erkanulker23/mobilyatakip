@extends('layouts.app')
@section('title', 'Satış ' . $sale->saleNumber)
@section('content')
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                <a href="{{ route('sales.index') }}" class="hover:text-slate-700">Satışlar</a>
                <span>/</span>
                <span class="text-slate-700">{{ $sale->saleNumber }}</span>
            </div>
            <h1 class="page-title">{{ $sale->saleNumber }} @if($sale->isCancelled ?? false)<span class="ml-2 text-sm font-normal px-2 py-1 rounded-full bg-red-100 text-red-700">İptal</span>@endif</h1>
            <p class="text-slate-600 mt-1">
                Satış faturası @if($sale->customer)· Müşteri: <a href="{{ route('customers.show', $sale->customer) }}" class="font-medium text-emerald-600 hover:text-emerald-700">{{ $sale->customer->name }}</a>@else· Müşteri: —@endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if(!($sale->isCancelled ?? false))
            <a href="{{ route('customer-payments.create') }}?customerId={{ $sale->customerId ?? '' }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Ödeme Al</a>
            <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="inline" onsubmit="return confirm('Bu satışı iptal etmek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 font-medium">İptal Et</button>
            </form>
            @endif
            @include('partials.action-buttons', [
                'print' => route('sales.print', $sale),
                'destroy' => route('sales.destroy', $sale),
            ])
        </div>
    </div>
</div>

@include('partials.invoice-document', [
    'documentTitle' => 'SATIŞ FİŞİ',
    'documentNumber' => $sale->saleNumber,
    'documentDate' => $sale->saleDate,
    'partyLabel' => 'Müşteri',
    'partyName' => $sale->customer?->name ?? '-',
    'partyAddress' => $sale->customer?->address,
    'partyPhone' => $sale->customer?->phone,
    'partyEmail' => $sale->customer?->email,
    'partyTax' => ($sale->customer?->taxNumber ? $sale->customer->taxNumber . ($sale->customer->taxOffice ? ' / ' . $sale->customer->taxOffice : '') : null),
    'extraInfo' => '<p class="text-sm text-slate-600">Vade: ' . ($sale->dueDate?->format('d.m.Y') ?? '-') . '</p>',
    'items' => collect($sale->items ?? [])->map(fn($i) => ['name' => $i->product?->name ?? '-', 'unitPrice' => $i->unitPrice ?? 0, 'quantity' => $i->quantity ?? 0, 'kdvRate' => $i->kdvRate ?? 18, 'lineTotal' => $i->lineTotal ?? 0])->toArray(),
    'showKdv' => true,
    'subtotal' => $sale->subtotal,
    'kdvTotal' => $sale->kdvTotal,
    'grandTotal' => $sale->grandTotal,
    'paidAmount' => $sale->paidAmount ?? 0,
    'notes' => $sale->notes,
])
@endsection
