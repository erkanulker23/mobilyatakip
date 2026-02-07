@extends('layouts.print')
@section('title', 'Satış ' . $sale->saleNumber . ' - Yazdır')
@section('content')
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
    'extraInfo' => $sale->dueDate ? ('<p class="text-sm text-slate-600">Vade: ' . $sale->dueDate->format('d.m.Y') . '</p>') : '',
    'items' => collect($sale->items ?? [])->map(fn($i) => ['name' => $i->productName ?? $i->product?->name ?? '-', 'unitPrice' => $i->unitPrice ?? 0, 'quantity' => $i->quantity ?? 0, 'kdvRate' => $i->kdvRate ?? 18, 'lineTotal' => $i->lineTotal ?? 0])->toArray(),
    'showKdv' => true,
    'subtotal' => $sale->subtotal,
    'kdvTotal' => $sale->kdvTotal,
    'grandTotal' => $sale->grandTotal,
    'paidAmount' => $sale->paidAmount ?? 0,
    'notes' => $sale->notes,
])
@endsection
