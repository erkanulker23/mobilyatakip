@extends('layouts.app')
@section('title', 'Teklif ' . $quote->quoteNumber)
@section('content')
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                <a href="{{ route('quotes.index') }}" class="hover:text-slate-700">Teklifler</a>
                <span>/</span>
                <span class="text-slate-700">{{ $quote->quoteNumber }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $quote->quoteNumber }}</h1>
            <p class="text-slate-600 mt-1">Teklif detayları @if($quote->customer)· Müşteri: <a href="{{ route('customers.show', $quote->customer) }}" class="font-medium text-green-600 hover:text-green-700">{{ $quote->customer->name }}</a>@else· Müşteri: —@endif</p>
        </div>
        @if(session('error'))
        <div class="w-full p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">{{ session('error') }}</div>
        @endif
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('quotes.edit', $quote) }}" class="btn-edit">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Düzenle
            </a>
            @if(!$quote->convertedSaleId && ($quote->status ?? '') == 'taslak')
            <form method="POST" action="{{ route('quotes.convert', $quote) }}" class="inline-flex" onsubmit="return confirm('Bu teklifi satışa dönüştürmek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Satışa Dönüştür</button>
            </form>
            @endif
            @if($quote->convertedSaleId)
            <a href="{{ route('sales.show', $quote->convertedSale) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 font-medium">Satış #{{ $quote->convertedSale?->saleNumber ?? '' }}</a>
            @endif
            <a href="{{ route('quotes.print', $quote) }}" target="_blank" class="btn-print">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Yazdır
            </a>
            <a href="{{ route('quotes.email', $quote) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 font-medium">E-posta Gönder</a>
        </div>
    </div>
</div>

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
    'extraInfo' => '<p class="text-sm text-slate-600">Geçerlilik: ' . ($quote->validUntil?->format('d.m.Y') ?? '-') . '</p><p class="text-sm text-slate-600">Personel: ' . e($quote->personnel?->name ?? '-') . '</p><p class="text-sm mt-2"><span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . (($quote->status ?? '') === 'taslak' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800') . '">' . e(ucfirst($quote->status ?? '-')) . '</span></p>',
    'items' => $quote->items->map(fn($i) => ['name' => $i->product?->name, 'unitPrice' => $i->unitPrice, 'quantity' => $i->quantity, 'kdvRate' => $i->kdvRate, 'lineTotal' => $i->lineTotal])->toArray(),
    'showKdv' => true,
    'subtotal' => $quote->subtotal,
    'kdvTotal' => $quote->kdvTotal,
    'discount' => ($quote->generalDiscountPercent ?? 0) > 0 ? $quote->subtotal * ($quote->generalDiscountPercent / 100) : ($quote->generalDiscountAmount ?? 0),
    'grandTotal' => $quote->grandTotal,
    'notes' => $quote->notes,
])
@endsection
