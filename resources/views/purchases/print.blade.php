@extends('layouts.print')
@section('title', 'Alış ' . $purchase->purchaseNumber . ' - Yazdır')
@section('content')
@include('partials.invoice-document-print', [
    'documentTitle' => 'ALIŞ FİŞİ',
    'documentNumber' => $purchase->purchaseNumber,
    'documentDate' => $purchase->purchaseDate,
    'partyLabel' => 'Tedarikçi',
    'partyName' => $purchase->supplier?->name ?? '-',
    'partyAddress' => $purchase->supplier ? full_address($purchase->supplier) : null,
    'partyPhone' => $purchase->supplier?->phone,
    'partyEmail' => $purchase->supplier?->email,
    'partyTax' => ($purchase->supplier?->taxNumber ? $purchase->supplier->taxNumber . ($purchase->supplier->taxOffice ? ' / ' . $purchase->supplier->taxOffice : '') : null),
    'extraInfo' => '<div class="print-kv-list">'
        . '<div class="print-kv-row"><span class="print-kv-label">Vade</span><span class="print-kv-value">' . e($purchase->dueDate?->format('d.m.Y') ?? '-') . '</span></div>'
        . ((isset($purchase->supplierDiscountRate) && $purchase->supplierDiscountRate != null && $purchase->supplierDiscountRate > 0)
            ? '<div class="print-kv-row"><span class="print-kv-label">Tedarikçi İskonto</span><span class="print-kv-value">%' . number_format($purchase->supplierDiscountRate, 1, ',', '.') . '</span></div>'
            : '')
        . '</div>',
    'footerNote' => 'Alış fişi — tedarikçi cari kaydı için düzenlenmiştir.',
    'items' => $purchase->items->map(fn($i) => ['name' => $i->product?->name, 'unitPrice' => $i->unitPrice, 'listPrice' => $i->listPrice, 'quantity' => $i->quantity, 'kdvRate' => $i->kdvRate ?? 18, 'lineTotal' => $i->lineTotal])->toArray(),
    'showListPrice' => $purchase->items->contains(fn($i) => $i->listPrice !== null),
    'showKdv' => true,
    'subtotal' => $purchase->subtotal,
    'kdvTotal' => $purchase->kdvTotal,
    'grandTotal' => $purchase->grandTotal,
    'notes' => $purchase->notes,
])
@endsection
