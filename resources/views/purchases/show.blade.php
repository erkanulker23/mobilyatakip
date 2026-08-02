@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::purchase($purchase))
@section('content')
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                <a href="{{ route('purchases.index') }}" class="hover:text-neutral-900">Alışlar</a>
                <span>/</span>
                <span class="text-neutral-700">{{ $purchase->purchaseNumber }}</span>
            </div>
            <h1 class="page-title">{{ $purchase->purchaseNumber }} @if($purchase->isCancelled ?? false)<span class="ml-2 text-sm font-normal px-2 py-1 rounded-full bg-red-100 text-red-700">İptal</span>@endif</h1>
            <p class="page-desc">Alış faturası @if($purchase->supplier)· Tedarikçi: <a href="{{ route('suppliers.show', $purchase->supplier) }}" class="font-medium text-green-600 hover:text-green-700">{{ $purchase->supplier->name }}</a>@else· Tedarikçi: —@endif @if($purchase->warehouse)· Depo: {{ $purchase->warehouse->name }}@endif</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if(!($purchase->isCancelled ?? false))
            <a href="{{ route('supplier-payments.create', ['supplierId' => $purchase->supplierId]) }}" class="btn-primary">Tedarikçi Ödeme Yap</a>
            @if($purchase->shippingCompanyId)
            <a href="{{ route('shipping-company-payments.create', ['shippingCompanyId' => $purchase->shippingCompanyId, 'linkType' => 'purchase', 'purchaseId' => $purchase->id]) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 font-medium">Nakliye Ödemesi Yap</a>
            @endif
            <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" class="inline" onsubmit="return confirm('Bu alışı iptal etmek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 font-medium">İptal Et</button>
            </form>
            @endif
            @if(!($purchase->isCancelled ?? false))
            <a href="{{ route('purchases.efatura.xml', $purchase) }}" class="btn-secondary">E-Fatura XML İndir</a>
            <form method="POST" action="{{ route('purchases.efatura.send', $purchase) }}" class="inline" onsubmit="return confirm('Bu faturayı e-fatura olarak göndermek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">E-Fatura Gönder</button>
            </form>
            @endif
            @if($purchase->efaturaStatus ?? null)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                @if($purchase->efaturaStatus === 'accepted' || $purchase->efaturaStatus === 'sent') bg-emerald-100 text-emerald-800
                @elseif($purchase->efaturaStatus === 'rejected') bg-red-100 text-red-800
                @else bg-slate-100 text-neutral-700 @endif">
                E-Fatura: {{ $purchase->efaturaStatus === 'sent' ? 'Gönderildi' : ($purchase->efaturaStatus === 'accepted' ? 'Kabul' : ($purchase->efaturaStatus === 'rejected' ? 'Red' : $purchase->efaturaStatus)) }}
                @if($purchase->efaturaSentAt) ({{ $purchase->efaturaSentAt->format('d.m.Y H:i') }})@endif
            </span>
            @endif
            @include('partials.action-buttons', [
                'edit' => route('purchases.edit', $purchase),
                'print' => route('purchases.print', $purchase),
            ])
        </div>
    </div>
</div>

@php
    $nakliyeInfo = null;
    if ($purchase->shippingCompany || $purchase->vehiclePlate || $purchase->driverName || $purchase->driverPhone) {
        $parts = [];
        if ($purchase->shippingCompany) $parts[] = $purchase->shippingCompany->name;
        if ($purchase->vehiclePlate) $parts[] = 'Plaka: ' . $purchase->vehiclePlate;
        if ($purchase->driverName) $parts[] = 'Şoför: ' . $purchase->driverName . ($purchase->driverPhone ? ' (' . $purchase->driverPhone . ')' : '');
        elseif ($purchase->driverPhone) $parts[] = 'Tel: ' . $purchase->driverPhone;
        $nakliyeInfo = implode(' · ', $parts);
    }
@endphp
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
    'extraInfo' => '<p class="text-sm text-slate-600">Vade: ' . ($purchase->dueDate?->format('d.m.Y') ?? '-') . '</p>' . (isset($purchase->supplierDiscountRate) && $purchase->supplierDiscountRate != null && $purchase->supplierDiscountRate > 0 ? '<p class="text-sm page-desc">Tedarikçi iskonto: %' . number_format($purchase->supplierDiscountRate, 1, ',', '.') . '</p>' : '') . ($nakliyeInfo ? '<p class="text-sm page-desc"><strong>Nakliye:</strong> ' . e($nakliyeInfo) . '</p>' : ''),
    'items' => $purchase->items->map(fn($i) => ['name' => $i->product?->name, 'unitPrice' => $i->unitPrice, 'listPrice' => $i->listPrice, 'quantity' => $i->quantity, 'kdvRate' => $i->kdvRate ?? 18, 'lineTotal' => $i->lineTotal])->toArray(),
    'showListPrice' => $purchase->items->contains(fn($i) => $i->listPrice !== null),
    'showKdv' => true,
    'subtotal' => $purchase->subtotal,
    'kdvTotal' => $purchase->kdvTotal,
    'grandTotal' => $purchase->grandTotal,
    'notes' => $purchase->notes,
])
@endsection
