@extends('layouts.print')
@section('title', 'Teklif ' . $quote->quoteNumber . ' - Yazdır')
@section('content')
@include('partials.invoice-document', [
    'documentTitle' => 'TEKLİF',
    'documentNumber' => $quote->quoteNumber,
    'documentDate' => $quote->createdAt,
    'partyLabel' => 'Müşteri',
    'partyName' => $quote->customer?->name ?? '-',
    'partyAddress' => $quote->customer?->address,
    'partyPhone' => $quote->customer?->phone,
    'partyEmail' => $quote->customer?->email,
    'partyTax' => ($quote->customer?->taxNumber ? $quote->customer->taxNumber . ($quote->customer->taxOffice ? ' / ' . $quote->customer->taxOffice : '') : null),
    'extraInfo' => '<p class="text-sm text-slate-600">Geçerlilik: ' . ($quote->validUntil?->format('d.m.Y') ?? '-') . '</p><p class="text-sm text-slate-600">Personel: ' . ($quote->personnel?->name ?? '-') . '</p><p class="text-sm mt-2"><span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . (($quote->status ?? '') === 'taslak' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800') . '">' . ucfirst($quote->status ?? '-') . '</span></p>',
    'items' => $quote->items->map(fn($i) => ['name' => $i->product?->name, 'unitPrice' => $i->unitPrice, 'quantity' => $i->quantity, 'kdvRate' => $i->kdvRate, 'lineTotal' => $i->lineTotal])->toArray(),
    'showKdv' => true,
    'subtotal' => $quote->subtotal,
    'kdvTotal' => $quote->kdvTotal,
    'discount' => ($quote->generalDiscountPercent ?? 0) > 0 ? $quote->subtotal * ($quote->generalDiscountPercent / 100) : ($quote->generalDiscountAmount ?? 0),
    'grandTotal' => $quote->grandTotal,
    'notes' => $quote->notes,
])
@endsection
