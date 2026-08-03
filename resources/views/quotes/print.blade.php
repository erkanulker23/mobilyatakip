@extends('layouts.print')
@section('title', 'Teklif ' . $quote->quoteNumber . ' - Yazdır')
@section('content')
@php
    $quoteIssuedAt = $quote->createdAt;
    $quoteValidUntil = $quoteIssuedAt?->copy()->addDays(3);
@endphp
@include('partials.invoice-document-print', [
    'documentTitle' => 'TEKLİFTİR',
    'documentSubtitle' => 'Teklif süresi 3 gündür.',
    'documentNotice' => '<strong>TEKLİFTİR — FATURA DEĞİLDİR.</strong> Bu belge bilgilendirme amaçlıdır. Teklif süresi 3 gündür.',
    'documentNumber' => $quote->quoteNumber,
    'documentDate' => $quoteIssuedAt,
    'partyLabel' => 'Müşteri',
    'partyName' => $quote->customer?->name ?? '-',
    'partyAddress' => $quote->customer ? full_address($quote->customer) : null,
    'partyPhone' => $quote->customer?->phone,
    'partyEmail' => $quote->customer?->email,
    'partyTax' => ($quote->customer?->taxNumber ? $quote->customer->taxNumber . ($quote->customer->taxOffice ? ' / ' . $quote->customer->taxOffice : '') : null),
    'extraInfo' => '<div class="print-kv-list">'
        . '<div class="print-kv-row"><span class="print-kv-label">Teklif Tarihi</span><span class="print-kv-value">' . e($quoteIssuedAt?->format('d.m.Y') ?? '-') . '</span></div>'
        . '<div class="print-kv-row"><span class="print-kv-label">Son Geçerlilik</span><span class="print-kv-value">' . e($quoteValidUntil?->format('d.m.Y') ?? '-') . '</span></div>'
        . '<div class="print-kv-row"><span class="print-kv-label">Personel</span><span class="print-kv-value">' . e($quote->personnel?->name ?? '-') . '</span></div>'
        . '</div>',
    'footerNote' => 'Teklif belgesi — fatura yerine geçmez. Geçerlilik süresi 3 gündür.',
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
