@extends('layouts.print')
@section('title', 'Alış ' . $purchase->purchaseNumber . ' - Yazdır')
@section('content')
@include('partials.invoice-document', [
    'documentTitle' => 'ALIŞ FİŞİ',
    'documentNumber' => $purchase->purchaseNumber,
    'documentDate' => $purchase->purchaseDate,
    'partyLabel' => 'Tedarikçi',
    'partyName' => $purchase->supplier?->name ?? '-',
    'partyAddress' => $purchase->supplier?->address,
    'partyPhone' => $purchase->supplier?->phone,
    'partyEmail' => $purchase->supplier?->email,
    'partyTax' => ($purchase->supplier?->taxNumber ? $purchase->supplier->taxNumber . ($purchase->supplier->taxOffice ? ' / ' . $purchase->supplier->taxOffice : '') : null),
    'extraInfo' => '<p class="text-sm text-slate-600">Vade: ' . ($purchase->dueDate?->format('d.m.Y') ?? '-') . '</p>',
    'items' => $purchase->items->map(fn($i) => ['name' => $i->product?->name, 'unitPrice' => $i->unitPrice, 'quantity' => $i->quantity, 'kdvRate' => $i->kdvRate ?? 18, 'lineTotal' => $i->lineTotal])->toArray(),
    'showKdv' => true,
    'subtotal' => $purchase->subtotal,
    'kdvTotal' => $purchase->kdvTotal,
    'grandTotal' => $purchase->grandTotal,
    'notes' => $purchase->notes,
])
@endsection
