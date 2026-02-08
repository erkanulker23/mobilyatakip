@extends('layouts.app')
@section('title', 'Satış ' . $sale->saleNumber)
@section('content')
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                <a href="{{ route('sales.index') }}" class="hover:text-slate-700">Satışlar</a>
                <span>/</span>
                <span class="text-slate-700">{{ $sale->saleNumber }}</span>
            </div>
            <h1 class="page-title">{{ $sale->saleNumber }} @if($sale->isCancelled ?? false)<span class="ml-2 text-sm font-normal px-2 py-1 rounded-full bg-red-100 text-red-700">İptal</span>@endif</h1>
            <p class="text-slate-600 mt-1">
                Satış faturası @if($sale->customer)· Müşteri: <a href="{{ route('customers.show', $sale->customer) }}" class="font-medium text-emerald-600 hover:text-emerald-700">{{ $sale->customer->name }}</a>@else· Müşteri: —@endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if(!($sale->isCancelled ?? false))
            <a href="{{ route('customer-payments.create') }}?customerId={{ $sale->customerId ?? '' }}&saleId={{ $sale->id ?? '' }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Müşteri Ödeme Al</a>
            <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="inline" onsubmit="return confirm('Bu satışı iptal etmek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 font-medium">İptal Et</button>
            </form>
            @endif
            @if(!($sale->isCancelled ?? false))
            <a href="{{ route('sales.efatura.xml', $sale) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 font-medium">E-Fatura XML İndir</a>
            <form method="POST" action="{{ route('sales.efatura.send', $sale) }}" class="inline" onsubmit="return confirm('Bu faturayı e-fatura olarak GİB/entegratöre göndermek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">E-Fatura Gönder</button>
            </form>
            @endif
            @if($sale->efaturaStatus ?? null)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                @if($sale->efaturaStatus === 'accepted' || $sale->efaturaStatus === 'sent') bg-emerald-100 text-emerald-800
                @elseif($sale->efaturaStatus === 'rejected') bg-red-100 text-red-800
                @else bg-slate-100 text-slate-700 @endif">
                E-Fatura: {{ $sale->efaturaStatus === 'sent' ? 'Gönderildi' : ($sale->efaturaStatus === 'accepted' ? 'Kabul' : ($sale->efaturaStatus === 'rejected' ? 'Red' : $sale->efaturaStatus)) }}
                @if($sale->efaturaSentAt) ({{ $sale->efaturaSentAt->format('d.m.Y H:i') }})@endif
            </span>
            @endif
            @include('partials.action-buttons', [
                'print' => route('sales.print', $sale),
                'destroy' => route('sales.destroy', $sale),
            ])
        </div>
    </div>
</div>

@if(!($sale->isCancelled ?? false))
@php $suppliersWithEmail = $sale->getSuppliersWithEmail(); $showPrompt = session('show_supplier_email_prompt') || (!$sale->hasSupplierEmailSent() && $suppliersWithEmail->isNotEmpty()); @endphp
@if($showPrompt && $suppliersWithEmail->isNotEmpty())
<div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
    <p class="text-emerald-800 font-medium mb-2">Faturada bulunan ürünlerin tedarikçisine sipariş maili gönderilsin mi?</p>
    <p class="text-sm text-emerald-700 mb-3">Bu satıştaki ürünlerin tedarikçilerine ({{ $suppliersWithEmail->pluck('name')->join(', ') }}) sipariş e-postası gönderebilirsiniz.</p>
    <form method="POST" action="{{ route('sales.send-supplier-email', $sale) }}" class="inline">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">Tedarikçiye Sipariş Maili Gönder</button>
    </form>
</div>
@endif
@endif

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

@php
    $pt = ['nakit' => 'Nakit', 'havale' => 'Havale', 'kredi_karti' => 'Kredi Kartı', 'cek' => 'Çek', 'senet' => 'Senet', 'diger' => 'Diğer'];
    $paymentEntries = collect($sale->payments ?? [])->map(fn($p) => (object)['type' => 'payment', 'sortAt' => $p->paymentDate ? $p->paymentDate->format('Y-m-d') . ' 00:00' : '', 'payment' => $p, 'linked' => true]);
    $unlinkedEntries = collect($unlinkedPayments ?? [])->map(fn($p) => (object)['type' => 'payment', 'sortAt' => $p->paymentDate ? $p->paymentDate->format('Y-m-d') . ' 00:00' : '', 'payment' => $p, 'linked' => false]);
    $activityEntries = collect($sale->activities ?? [])->map(fn($a) => (object)['type' => 'activity', 'sortAt' => $a->createdAt->format('Y-m-d H:i'), 'activity' => $a]);
    $timeline = $paymentEntries->concat($unlinkedEntries)->concat($activityEntries)->sortByDesc('sortAt')->values();
@endphp
@if($timeline->isNotEmpty())
<div class="mt-8 card p-6">
    <h2 class="text-lg font-semibold text-slate-900 mb-4">Zaman çizelgesi</h2>
    <div class="relative space-y-0">
        @foreach($timeline as $entry)
        <div class="flex gap-4 pb-6 last:pb-0">
            <div class="flex flex-col items-center">
                @if($entry->type === 'payment')
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">💰</span>
                @else
                @php $activity = $entry->activity; @endphp
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full
                    @if($activity->type === 'created') bg-slate-200 text-slate-700
                    @elseif($activity->type === 'supplier_email_sent') bg-blue-100 text-blue-700
                    @elseif($activity->type === 'supplier_email_read') bg-amber-100 text-amber-700
                    @elseif($activity->type === 'supplier_email_replied') bg-emerald-100 text-emerald-700
                    @else bg-slate-100 text-slate-600 @endif">
                    @if($activity->type === 'created') 📋
                    @elseif($activity->type === 'supplier_email_sent') ✉️
                    @elseif($activity->type === 'supplier_email_read') 👁️
                    @elseif($activity->type === 'supplier_email_replied') ↩️
                    @else • @endif
                </span>
                @endif
                @if(!$loop->last)
                <div class="mt-1 w-px flex-1 bg-slate-200 min-h-[24px]"></div>
                @endif
            </div>
            <div class="flex-1 min-w-0 pt-0.5">
                @if($entry->type === 'payment')
                @php $p = $entry->payment; $isLinked = $entry->linked ?? true; @endphp
                <p class="font-medium text-slate-900">
                    <a href="{{ $isLinked ? route('customer-payments.show', $p) : route('customer-payments.edit', $p) }}" class="text-emerald-600 hover:text-emerald-700 hover:underline">Tahsilat alındı: {{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</a>
                    @if($pt[$p->paymentType ?? ''] ?? null)
                    <span class="text-slate-600 font-normal">({{ $pt[$p->paymentType ?? ''] }})</span>
                    @endif
                    @if(!$isLinked)
                    <span class="ml-1 text-amber-600 text-sm font-normal">— Faturaya bağlı değil</span>
                    @endif
                </p>
                <p class="text-xs text-slate-500 mt-1">{{ $p->paymentDate?->format('d.m.Y H:i') ?? '—' }}</p>
                @else
                @php $activity = $entry->activity; @endphp
                <p class="font-medium text-slate-900">{{ $activity->description }}</p>
                @if($activity->metadata && isset($activity->metadata['suppliers']))
                <p class="text-sm text-slate-600 mt-1">
                    @foreach($activity->metadata['suppliers'] as $s)
                    <span class="inline-block mr-2">{{ $s['name'] }} &lt;{{ $s['email'] }}&gt;</span>
                    @endforeach
                </p>
                @endif
                <p class="text-xs text-slate-500 mt-1">{{ $activity->createdAt->format('d.m.Y H:i') }}</p>
                @if($activity->type === 'supplier_email_sent')
                <div class="mt-2 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('sales.activity', $sale) }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="supplier_email_read">
                        <button type="submit" class="text-sm px-3 py-1.5 rounded-lg bg-amber-100 text-amber-800 hover:bg-amber-200 font-medium">Okundu işaretle</button>
                    </form>
                    <form method="POST" action="{{ route('sales.activity', $sale) }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="supplier_email_replied">
                        <button type="submit" class="text-sm px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 hover:bg-emerald-200 font-medium">Cevaplandı işaretle</button>
                    </form>
                </div>
                @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
