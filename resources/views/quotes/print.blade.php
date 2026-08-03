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
    'extraInfo' => '<p>Teklif tarihi: ' . ($quoteIssuedAt?->format('d.m.Y') ?? '-') . '</p>'
        . '<p><strong>Son geçerlilik:</strong> ' . ($quoteValidUntil?->format('d.m.Y') ?? '-') . '</p>'
        . '<p>Personel: ' . e($quote->personnel?->name ?? '-') . '</p>',
    'items' => $quote->items->map(fn($i) => ['name' => $i->product?->name ?? $i->productName, 'description' => $i->description, 'unitPrice' => $i->unitPrice, 'quantity' => $i->quantity, 'kdvRate' => $i->kdvRate, 'lineTotal' => $i->lineTotal])->toArray(),
    'showKdv' => true,
    'kdvIncluded' => (bool) ($quote->kdvIncluded ?? true),
    'subtotal' => $quote->subtotal,
    'kdvTotal' => $quote->kdvTotal,
    'discount' => ($quote->generalDiscountPercent ?? 0) > 0
        ? round($quote->items->sum(fn ($i) => (float) $i->lineTotal) * (($quote->generalDiscountPercent ?? 0) / 100), 2)
        : ($quote->generalDiscountAmount ?? 0),
    'grandTotal' => $quote->grandTotal,
    'notes' => $quote->notes,
])
@endsection
