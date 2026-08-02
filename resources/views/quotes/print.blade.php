@extends('layouts.print')
@section('title', 'Teklif ' . $quote->quoteNumber . ' - Yazdır')
@section('content')
@php
    $quoteIssuedAt = $quote->createdAt;
    $quoteValidUntil = $quoteIssuedAt?->copy()->addDays(3);
@endphp
@include('partials.invoice-document', [
    'documentTitle' => 'TEKLİFTİR',
    'documentSubtitle' => 'Teklif süresi 3 gündür.',
    'documentNotice' => '<span class="font-bold uppercase tracking-wider text-neutral-900">Tekliftir.</span> Bu belge fatura değildir. <strong>Teklif süresi 3 gündür.</strong>',
    'documentNumber' => $quote->quoteNumber,
    'documentDate' => $quoteIssuedAt,
    'partyLabel' => 'Müşteri',
    'partyName' => $quote->customer?->name ?? '-',
    'partyAddress' => $quote->customer ? full_address($quote->customer) : null,
    'partyPhone' => $quote->customer?->phone,
    'partyEmail' => $quote->customer?->email,
    'partyTax' => ($quote->customer?->taxNumber ? $quote->customer->taxNumber . ($quote->customer->taxOffice ? ' / ' . $quote->customer->taxOffice : '') : null),
    'extraInfo' => '<p class="text-sm text-slate-600">Teklif tarihi: ' . ($quoteIssuedAt?->format('d.m.Y') ?? '-') . '</p>'
        . '<p class="text-sm font-semibold text-slate-900 mt-1">Son geçerlilik: ' . ($quoteValidUntil?->format('d.m.Y') ?? '-') . '</p>'
        . '<p class="text-sm text-slate-600 mt-1">Personel: ' . e($quote->personnel?->name ?? '-') . '</p>',
    'items' => $quote->items->map(fn($i) => ['name' => $i->product?->name, 'description' => $i->description, 'unitPrice' => $i->unitPrice, 'quantity' => $i->quantity, 'kdvRate' => $i->kdvRate, 'lineTotal' => $i->lineTotal])->toArray(),
    'showKdv' => true,
    'subtotal' => $quote->subtotal,
    'kdvTotal' => $quote->kdvTotal,
    'discount' => ($quote->generalDiscountPercent ?? 0) > 0 ? $quote->subtotal * ($quote->generalDiscountPercent / 100) : ($quote->generalDiscountAmount ?? 0),
    'grandTotal' => $quote->grandTotal,
    'notes' => $quote->notes,
])
@endsection
