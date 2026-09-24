@extends('layouts.print')
@section('title', 'Teklif ' . $quote->quoteNumber . ' - Yazdır')
@push('print-actions')
    <a href="{{ route('quotes.pdf', $quote) }}"
       class="px-4 py-2.5 bg-slate-800 text-white rounded-xl hover:bg-slate-900 font-semibold text-sm shadow-sm w-full sm:w-auto text-center order-first sm:order-none">
        PDF İndir
    </a>
@endpush
@section('content')
@php
    $quoteIssuedAt = $quote->createdAt;
    $quoteValidUntil = $quoteIssuedAt?->copy()->addDays(3);
@endphp
@include('partials.invoice-document-print', [
    'printVariant' => 'quote',
    'documentTitle' => 'TEKLİF',
    'documentSubtitle' => 'Geçerlilik: ' . ($quoteValidUntil?->format('d.m.Y') ?? '—') . ' · Fatura değildir',
    'documentNotice' => null,
    'documentNumber' => $quote->quoteNumber,
    'documentDate' => $quoteIssuedAt,
    'partyLabel' => 'Müşteri',
    'partyName' => $quote->customer?->name ?? '-',
    'partyAddress' => $quote->customer ? full_address($quote->customer) : null,
    'partyPhone' => $quote->customer?->phone,
    'partyEmail' => $quote->customer?->email,
    'partyTax' => ($quote->customer?->taxNumber ? $quote->customer->taxNumber . ($quote->customer->taxOffice ? ' / ' . $quote->customer->taxOffice : '') : null),
    'metaLabel' => 'Teklif Bilgileri',
    'extraInfo' => '<div class="print-kv-list">'
        . '<div class="print-kv-row"><span class="print-kv-label">Teklif Tarihi</span><span class="print-kv-value">' . e($quoteIssuedAt?->format('d.m.Y') ?? '-') . '</span></div>'
        . '<div class="print-kv-row"><span class="print-kv-label">Son Geçerlilik</span><span class="print-kv-value">' . e($quoteValidUntil?->format('d.m.Y') ?? '-') . '</span></div>'
        . '<div class="print-kv-row"><span class="print-kv-label">Teklifi Hazırlayan</span><span class="print-kv-value">' . e($quote->personnel?->name ?? \App\Support\QuoteCreator::displayNameForQuote($quote) ?? '-') . '</span></div>'
        . ($quote->branch ? '<div class="print-kv-row"><span class="print-kv-label">Şube</span><span class="print-kv-value">' . e($quote->branch->name) . '</span></div>' : '')
        . '</div>',
    'footerNote' => null,
    'termsTitle' => 'TEKLİF VE SİPARİŞ KOŞULLARI',
    'terms' => \App\Support\QuotePrintTerms::items(),
    'items' => $quote->items->map(fn ($i) => ['name' => $i->product?->name ?? $i->productName, 'description' => $i->description, 'unitPrice' => $i->unitPrice, 'quantity' => $i->quantity, 'kdvRate' => $i->kdvRate, 'lineTotal' => $i->lineTotal])->toArray(),
    'showKdv' => true,
    'kdvIncluded' => (bool) ($quote->kdvIncluded ?? true),
    'subtotal' => $quote->subtotal,
    'kdvTotal' => $quote->kdvTotal,
    'discount' => $quote->generalDiscountApplied(),
    'grandTotal' => $quote->grandTotal,
    'notes' => $quote->notes,
])

@include('partials.drawing-files-print', ['drawingFiles' => $quote->drawingFiles ?? []])
@endsection
