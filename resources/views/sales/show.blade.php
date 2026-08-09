@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::sale($sale))
@section('content')
@php
    $currentOrderStatus = \App\Support\SaleDelivery::currentStatus($sale);
    $initialDeliveryStatus = old('deliveryStatus', $currentOrderStatus);
    $openServiceTickets = ($sale->serviceTickets ?? collect())
        ->filter(fn ($ticket) => ! \App\Support\ServiceTicketStatus::isClosed($ticket->status))
        ->values();
    $primaryServiceTicket = $openServiceTickets->first();
@endphp
<div x-data='{
    showCustomerEmail: false,
    showStatusModal: @json(old('deliveryStatus') !== null || $errors->has('deliveredAt')),
    showCancelModal: false,
    showPaymentModal: @json(session('open_payment_modal') || (old('redirectToSale') && old('redirectToSale') == $sale->id)),
    deliveryStatus: @json($initialDeliveryStatus),
    openStatusModal() {
        this.deliveryStatus = @json($currentOrderStatus);
        this.showStatusModal = true;
    }
}'>
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 dark:text-neutral-400 text-sm mb-1">
                <a href="{{ route('sales.index') }}" class="hover:text-neutral-900 dark:hover:text-neutral-100">Satışlar</a>
                <span>/</span>
                <span class="text-neutral-700 dark:text-neutral-300">{{ $sale->saleNumber }}</span>
            </div>
            <h1 class="page-title flex flex-wrap items-center gap-2">
                {{ $sale->saleNumber }}
                @if($sale->isCancelled ?? false)
                <span class="text-sm font-normal px-2.5 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">İptal</span>
                @endif
                @if($primaryServiceTicket)
                <a href="{{ $openServiceTickets->count() > 1 ? '#ssh-kayitlari' : route('service-tickets.show', $primaryServiceTicket) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold px-3 py-1 rounded-full bg-orange-100 text-orange-800 ring-1 ring-orange-300/80 hover:bg-orange-200 dark:bg-orange-900/40 dark:text-orange-200 dark:ring-orange-700/60 dark:hover:bg-orange-900/55 transition-colors">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    SSH{{ $openServiceTickets->count() > 1 ? ' (' . $openServiceTickets->count() . ')' : '' }}
                </a>
                @endif
                @include('partials.final-measurement-badge', ['sale' => $sale])
            </h1>
            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1.5 text-sm text-neutral-600 dark:text-neutral-400">
                <span>Satış faturası</span>
                @if($sale->customer)
                <span class="text-neutral-300 dark:text-neutral-600" aria-hidden="true">·</span>
                <span>Müşteri: <a href="{{ route('customers.show', $sale->customer) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">{{ $sale->customer->name }}</a></span>
                @else
                <span class="text-neutral-300 dark:text-neutral-600" aria-hidden="true">·</span>
                <span>Müşteri: —</span>
                @endif
                @if(!($sale->isCancelled ?? false) && \App\Support\SaleDelivery::currentStatus($sale) !== \App\Support\SaleDelivery::PENDING)
                <span class="text-neutral-300 dark:text-neutral-600" aria-hidden="true">·</span>
                <span class="inline-flex flex-wrap items-center gap-1.5">
                    @if($primaryServiceTicket && \App\Support\SaleDelivery::currentStatus($sale) === \App\Support\SaleDelivery::SSH)
                    <a href="{{ route('service-tickets.show', $primaryServiceTicket) }}" class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full {{ \App\Support\SaleDelivery::badgeClass(\App\Support\SaleDelivery::SSH) }} hover:opacity-90 transition-opacity">
                        {{ \App\Support\SaleDelivery::label(\App\Support\SaleDelivery::SSH) }}
                    </a>
                    @else
                    @include('partials.delivery-status-badge', ['sale' => $sale])
                    @endif
                    @if(\App\Support\SaleDelivery::isDelivered($sale) && $sale->deliveredAt)
                    <span>({{ $sale->deliveredAt->format('d.m.Y') }})</span>
                    @endif
                </span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(!($sale->isCancelled ?? false))
            @if($primaryServiceTicket)
            <a href="{{ $openServiceTickets->count() > 1 ? '#ssh-kayitlari' : route('service-tickets.show', $primaryServiceTicket) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-orange-600 text-white rounded-[0.625rem] hover:bg-orange-700 font-medium text-sm transition-colors dark:bg-orange-700 dark:hover:bg-orange-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                {{ $openServiceTickets->count() > 1 ? 'SSH Kayıtları' : 'SSH\'ye Git' }}
            </a>
            @endif
            <a href="{{ route('sales.edit', $sale) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white rounded-[0.625rem] hover:bg-red-700 font-medium text-sm transition-colors dark:bg-red-700 dark:hover:bg-red-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Düzenle
            </a>
            <a href="{{ route('sales.workshop.koltuk', $sale) }}" target="_blank" class="btn-secondary text-sm">
                Koltuk Atölye Fişi Çıkar
            </a>
            <a href="{{ route('sales.workshop.mobilya', $sale) }}" target="_blank" class="btn-secondary text-sm">
                Mobilya Atölyesi Fişi Çıkar
            </a>
            <a href="{{ route('sales.print', $sale) }}" target="_blank" class="btn-secondary text-sm">
                Sipariş Fişi Yaz
            </a>
            <a href="{{ route('sales.shipment', $sale) }}" target="_blank" class="btn-secondary text-sm">
                Sevkiyat Fişi Çıkar
            </a>
            @if($sale->customer)
            <a href="#musteri-ekstresi" class="btn-secondary text-sm">Müşteri Ekstresi</a>
            @endif
            <button type="button" @click="openStatusModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium text-sm">
                Sipariş Durumunu Güncelle
            </button>
            <form method="POST" action="{{ route('sales.convert-to-quote', $sale) }}" class="inline-flex" onsubmit="return confirm('Bu kayıt teklif olarak devam edecek. Satış listesinden kaldırılır; teklifler bölümünde kalır. Devam?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-900 rounded-lg hover:bg-amber-200 font-medium text-sm dark:bg-amber-900/30 dark:text-amber-200 dark:hover:bg-amber-900/50">
                    Teklife Dönüştür
                </button>
            </form>
            @endif
            <button type="button" @click="showCustomerEmail = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Müşteriye Mail Gönder
            </button>
            @if(!($sale->isCancelled ?? false) && $sale->customerId)
            <button type="button" @click="showPaymentModal = true" class="btn-primary text-sm">Ödeme Al</button>
            @endif
            @if(!($sale->isCancelled ?? false))
            <button type="button" @click="showCancelModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 border border-red-300 text-red-700 rounded-[0.625rem] hover:bg-red-50 font-medium text-sm transition-colors dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                Siparişi İptal Et
            </button>
            @endif
            @include('partials.action-buttons', [
                'destroy' => route('sales.destroy', $sale),
            ])
        </div>
    </div>
</div>

@if($sale->isCancelled ?? false)
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800">
    <p class="font-semibold text-red-800 dark:text-red-200">Bu sipariş iptal edilmiş</p>
    <p class="text-sm text-red-700/90 dark:text-red-300/90 mt-1">Kayıt silinmedi; stok iade edildi. Düzenleme ve yeni işlem yapılamaz.</p>
</div>
@endif

@if(!($sale->isCancelled ?? false))
@php $suppliersWithEmail = $sale->getSuppliersWithEmail(); $showPrompt = session('show_supplier_email_prompt') || (!$sale->hasSupplierEmailSent() && $suppliersWithEmail->isNotEmpty()); @endphp
@if($showPrompt && $suppliersWithEmail->isNotEmpty())
<div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800">
    <p class="text-emerald-800 dark:text-emerald-200 font-medium mb-2">Faturada bulunan ürünlerin tedarikçisine sipariş maili gönderilsin mi?</p>
    <p class="text-sm text-emerald-700 dark:text-emerald-300/90 mb-3">Bu satıştaki ürünlerin tedarikçilerine ({{ $suppliersWithEmail->pluck('name')->join(', ') }}) sipariş e-postası gönderebilirsiniz.</p>
    <form method="POST" action="{{ route('sales.send-supplier-email', $sale) }}" class="inline">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">Tedarikçiye Sipariş Maili Gönder</button>
    </form>
</div>
@endif
@endif

@if(!($sale->isCancelled ?? false) && $openServiceTickets->isNotEmpty())
<div id="ssh-kayitlari" class="mb-6 scroll-mt-24">
    @if($openServiceTickets->count() === 1)
        @php $ticket = $openServiceTickets->first(); @endphp
        <a href="{{ route('service-tickets.show', $ticket) }}" class="group block p-4 rounded-xl bg-orange-50 dark:bg-orange-950/35 border-2 border-orange-300 dark:border-orange-700 hover:bg-orange-100/80 dark:hover:bg-orange-950/50 transition-colors">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-200 text-orange-800 dark:bg-orange-900/50 dark:text-orange-200 group-hover:bg-orange-300 dark:group-hover:bg-orange-900/70 transition-colors" aria-hidden="true">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-lg text-orange-950 dark:text-orange-100">Bu siparişte açık SSH kaydı var</p>
                    <p class="text-sm text-orange-900/90 dark:text-orange-200/90 mt-1">
                        <span class="font-medium">{{ $ticket->ticketNumber }}</span>
                        · {{ \App\Support\ServiceTicketStatus::label($ticket->status) }}
                        @if($ticket->dueDate)
                        · Termin {{ $ticket->dueDate->format('d.m.Y') }}
                        @endif
                    </p>
                    <p class="text-sm font-medium text-orange-700 dark:text-orange-300 mt-2 group-hover:underline">SSH kaydına git →</p>
                </div>
            </div>
        </a>
    @else
        <div class="p-4 rounded-xl bg-orange-50 dark:bg-orange-950/35 border-2 border-orange-300 dark:border-orange-700">
            <div class="flex items-start gap-3 mb-4">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-200 text-orange-800 dark:bg-orange-900/50 dark:text-orange-200" aria-hidden="true">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </span>
                <div>
                    <p class="font-semibold text-lg text-orange-950 dark:text-orange-100">Bu siparişte {{ $openServiceTickets->count() }} açık SSH kaydı var</p>
                    <p class="text-sm text-orange-900/90 dark:text-orange-200/90 mt-1">İlgili servis kaydına tıklayarak detayına gidebilirsiniz.</p>
                </div>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach($openServiceTickets as $ticket)
                <a href="{{ route('service-tickets.show', $ticket) }}" class="flex items-center justify-between gap-3 rounded-lg border border-orange-200 dark:border-orange-800 bg-white/70 dark:bg-neutral-900/40 px-3 py-2.5 hover:bg-orange-100 dark:hover:bg-orange-950/40 transition-colors">
                    <span class="min-w-0">
                        <span class="block font-semibold text-orange-900 dark:text-orange-100">{{ $ticket->ticketNumber }}</span>
                        <span class="block text-xs text-orange-800/80 dark:text-orange-200/80 mt-0.5">
                            {{ \App\Support\ServiceTicketStatus::label($ticket->status) }}
                            @if($ticket->dueDate)
                            · Termin {{ $ticket->dueDate->format('d.m.Y') }}
                            @endif
                        </span>
                    </span>
                    <span class="text-sm font-medium text-orange-700 dark:text-orange-300 shrink-0">Git →</span>
                </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endif

@if($sale->needsFinalMeasurement ?? false)
<div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-950 dark:text-amber-100">
    <div class="flex items-start gap-3">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100" aria-hidden="true">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
        </span>
        <div>
            <p class="font-semibold text-lg">Kesin ölçüye gidilecek</p>
            <p class="text-sm text-amber-900/90 dark:text-amber-200/90 mt-1">Bu sipariş için saha ölçüsü alınacaktır. Üretim ve teslimat kesin ölçü sonrası planlanır.</p>
        </div>
    </div>
</div>
@endif

@if(($productionStagesReady ?? false) && ($productionStages ?? collect())->isNotEmpty() && !($sale->isCancelled ?? false))
<div class="mb-6 p-4 rounded-xl bg-sky-50 dark:bg-sky-950/30 border border-sky-200 dark:border-sky-800">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-start gap-3 min-w-0">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300" aria-hidden="true">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
            </span>
            <div class="min-w-0">
                <p class="font-semibold text-sky-900 dark:text-sky-100">Siparişte aşamalar var</p>
                <p class="text-sm text-sky-800/90 dark:text-sky-200/90 mt-0.5">
                    Bu siparişe {{ $productionStages->count() }} aşama eklenmiş. Aşamaları okuyabilirsiniz.
                </p>
            </div>
        </div>
        <a href="#atolye-takibi" class="btn-secondary text-sm shrink-0">Aşamaları Gör</a>
    </div>
</div>
@endif

@include('partials.sale-order-meta-panel', ['sale' => $sale])

<div class="mb-6">
    @include('partials.drawing-files-display', ['entries' => \App\Support\DrawingFiles::entriesForSale($sale)])
</div>

@include('partials.invoice-document', array_merge(
    \App\Support\SaleDocument::invoiceParams($sale),
    ['showOrderSummary' => false]
))

@if($customerLedger)
@include('partials.customer-ledger-panel', [
    'customerLedger' => $customerLedger,
    'highlightRefId' => $sale->id,
    'highlightType' => 'satis',
])
@endif

@php
    use App\Models\SaleActivity;
    use App\Support\SaleDelivery;

    $pt = \App\Support\PaymentType::labels();
    $paymentEntries = collect($sale->payments ?? [])->map(fn($p) => (object)['type' => 'payment', 'sortAt' => $p->paymentDate ? $p->paymentDate->format('Y-m-d') . ' 00:00' : '', 'payment' => $p, 'linked' => true]);
    $unlinkedEntries = collect($unlinkedPayments ?? [])->map(fn($p) => (object)['type' => 'payment', 'sortAt' => $p->paymentDate ? $p->paymentDate->format('Y-m-d') . ' 00:00' : '', 'payment' => $p, 'linked' => false]);
    $activityEntries = collect($sale->activities ?? [])->map(fn($a) => (object)['type' => 'activity', 'sortAt' => $a->createdAt->format('Y-m-d H:i'), 'activity' => $a]);

    $hasDeliveryActivity = collect($sale->activities ?? [])->contains(function ($a) {
        return $a->type === SaleActivity::TYPE_STATUS_CHANGED
            && (($a->metadata['toStatus'] ?? null) === SaleDelivery::DELIVERED);
    });
    $legacyStatusEntries = collect();
    if (SaleDelivery::isDelivered($sale) && $sale->deliveredAt && !$hasDeliveryActivity) {
        $legacyStatusEntries->push((object)[
            'type' => 'activity',
            'sortAt' => $sale->deliveredAt->format('Y-m-d H:i'),
            'activity' => (object)[
                'type' => SaleActivity::TYPE_STATUS_CHANGED,
                'description' => 'Sipariş teslim edildi (' . $sale->deliveredAt->format('d.m.Y') . ')',
                'metadata' => ['toStatus' => SaleDelivery::DELIVERED],
                'createdAt' => $sale->deliveredAt,
            ],
        ]);
    }

    $timeline = $paymentEntries->concat($unlinkedEntries)->concat($activityEntries)->concat($legacyStatusEntries)->sortByDesc('sortAt')->values();
@endphp
@if($timeline->isNotEmpty())
<div class="mt-8 card overflow-hidden">
    <div class="card-header">Zaman çizelgesi</div>
    <div class="p-5 sm:p-6">
    <div class="relative space-y-0">
        @foreach($timeline as $entry)
        <div class="flex gap-4 pb-6 last:pb-0">
            <div class="flex flex-col items-center">
                @if($entry->type === 'payment')
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">💰</span>
                @else
                @php $activity = $entry->activity; @endphp
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full
                    @if($activity->type === 'created') bg-slate-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200
                    @elseif($activity->type === 'status_changed') bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300
                    @elseif($activity->type === 'workshop_completed') bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300
                    @elseif($activity->type === 'supplier_email_sent') bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300
                    @elseif($activity->type === 'supplier_email_read') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                    @elseif($activity->type === 'supplier_email_replied') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                    @elseif($activity->type === 'customer_email_sent') bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300
                    @else bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-neutral-300 @endif">
                    @if($activity->type === 'created') 📋
                    @elseif($activity->type === 'status_changed') 📦
                    @elseif($activity->type === 'workshop_completed') 🏭
                    @elseif($activity->type === 'supplier_email_sent') ✉️
                    @elseif($activity->type === 'supplier_email_read') 👁️
                    @elseif($activity->type === 'supplier_email_replied') ↩️
                    @elseif($activity->type === 'customer_email_sent') 📧
                    @else • @endif
                </span>
                @endif
                @if(!$loop->last)
                <div class="mt-1 w-px flex-1 bg-slate-200 dark:bg-neutral-700 min-h-[24px]"></div>
                @endif
            </div>
            <div class="flex-1 min-w-0 pt-0.5">
                @if($entry->type === 'payment')
                @php $p = $entry->payment; $isLinked = $entry->linked ?? true; @endphp
                <p class="font-medium text-neutral-900 dark:text-neutral-100">
                    <a href="{{ $isLinked ? route('customer-payments.show', $p) : route('customer-payments.edit', $p) }}" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:underline">Tahsilat alındı: {{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</a>
                    @if($pt[$p->paymentType ?? ''] ?? null)
                    <span class="text-slate-600 dark:text-neutral-400 font-normal">({{ $pt[$p->paymentType ?? ''] }})</span>
                    @endif
                    @if(!$isLinked)
                    <span class="ml-1 text-amber-600 dark:text-amber-400 text-sm font-normal">— Faturaya bağlı değil</span>
                    @endif
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">{{ $p->paymentDate?->format('d.m.Y H:i') ?? '—' }}</p>
                @else
                @php $activity = $entry->activity; @endphp
                <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $activity->description }}</p>
                @if($activity->metadata && isset($activity->metadata['suppliers']))
                <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1 break-words">
                    @foreach($activity->metadata['suppliers'] as $s)
                    <span class="inline-block mr-2 mb-1">{{ $s['name'] }} &lt;{{ $s['email'] }}&gt;</span>
                    @endforeach
                </p>
                @endif
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">{{ $activity->createdAt->format('d.m.Y H:i') }}</p>
                @if($activity->type === 'supplier_email_sent')
                <div class="mt-2 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('sales.activity', $sale) }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="supplier_email_read">
                        <button type="submit" class="text-sm px-3 py-1.5 rounded-lg bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:hover:bg-amber-900/50 font-medium">Okundu işaretle</button>
                    </form>
                    <form method="POST" action="{{ route('sales.activity', $sale) }}" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="supplier_email_replied">
                        <button type="submit" class="text-sm px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:hover:bg-emerald-900/50 font-medium">Cevaplandı işaretle</button>
                    </form>
                </div>
                @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>
    </div>
</div>
@endif

@include('partials.sale-workshop-panel', [
    'sale' => $sale,
    'productionStages' => $productionStages ?? collect(),
    'productionStagesReady' => $productionStagesReady ?? false,
    'canAddProductionStage' => $canAddProductionStage ?? false,
    'openDeficienciesCount' => $openDeficienciesCount ?? 0,
])

{{-- Sipariş durumu güncelle --}}
@if(!($sale->isCancelled ?? false))
<div x-show="showStatusModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="sale-status-title">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showStatusModal = false"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-neutral-900 shadow-xl border border-neutral-200 dark:border-neutral-800 overflow-hidden">
        <div class="px-5 pt-5 pb-1">
            <h2 id="sale-status-title" class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Sipariş Durumunu Güncelle</h2>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $sale->saleNumber }}</p>
        </div>
        <form method="POST" action="{{ route('sales.update-status', $sale) }}" class="p-5 space-y-4">
            @csrf
            <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-100 dark:border-neutral-700 text-sm space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-neutral-500 dark:text-neutral-400">Ödeme durumu</span>
                    @include('partials.payment-status-badge', ['sale' => $sale])
                </div>
                @if(\App\Support\SaleDelivery::isDelivered($sale) && $sale->deliveredAt)
                <div class="flex items-center justify-between gap-3">
                    <span class="text-neutral-500 dark:text-neutral-400">Kayıtlı teslim tarihi</span>
                    <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $sale->deliveredAt->format('d.m.Y') }}</span>
                </div>
                @endif
                @if($sale->serviceTickets->isNotEmpty())
                <div class="flex items-start justify-between gap-3">
                    <span class="text-neutral-500 dark:text-neutral-400 shrink-0">SSH kayıtları</span>
                    <div class="text-right space-y-1">
                        @foreach($sale->serviceTickets as $ticket)
                        <div>
                            <a href="{{ route('service-tickets.show', $ticket) }}" class="font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">{{ $ticket->ticketNumber }}</a>
                            <span class="text-neutral-500 dark:text-neutral-400">· {{ \App\Support\ServiceTicketStatus::label($ticket->status) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div>
                <label class="form-label" for="deliveryStatus">Sipariş durumu</label>
                <select id="deliveryStatus" name="deliveryStatus" x-model="deliveryStatus" class="form-select min-h-[44px]">
                    @foreach(\App\Support\SaleDelivery::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('deliveryStatus')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @if($sale->serviceTickets->isEmpty())
                <p class="mt-2 text-xs text-neutral-500">SSH kaydı oluşturmak için <a href="{{ route('service-tickets.create', ['saleId' => $sale->id, 'customerId' => $sale->customerId]) }}" class="text-emerald-600 hover:text-emerald-700">yeni servis kaydı</a> açabilirsiniz.</p>
                @endif
            </div>
            <template x-if="deliveryStatus === 'delivered'">
                <div>
                    <label class="form-label" for="deliveredAt">Teslim tarihi *</label>
                    <input
                        type="date"
                        id="deliveredAt"
                        name="deliveredAt"
                        value="{{ old('deliveredAt', $sale->deliveredAt?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        class="form-input min-h-[44px] w-full"
                        required
                    >
                    @error('deliveredAt')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </template>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" @click="showStatusModal = false" class="btn-secondary min-h-[44px]">İptal</button>
                <button type="submit" class="btn-primary min-h-[44px]">Kaydet</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Sipariş iptal modal --}}
@if(!($sale->isCancelled ?? false))
<div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="cancel-sale-title">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCancelModal = false"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 pt-5 pb-1">
            <h2 id="cancel-sale-title" class="text-lg font-semibold text-red-700 dark:text-red-300">Siparişi İptal Et</h2>
            <p class="mt-1 text-sm text-neutral-600 dark:text-slate-400">{{ $sale->saleNumber }} numaralı sipariş iptal edilecek.</p>
        </div>
        <div class="px-5 py-4 space-y-3 text-sm text-neutral-700 dark:text-slate-300">
            <ul class="list-disc list-inside space-y-1.5 text-neutral-600 dark:text-slate-400">
                <li>Rezerve edilen stok iade edilir.</li>
                <li>Sipariş kaydı silinmez; iptal olarak işaretlenir.</li>
                <li>İptal sonrası düzenleme ve durum güncellemesi yapılamaz.</li>
            </ul>
            @php $paidOnSale = (float) ($sale->paidAmount ?? 0); @endphp
            @if($paidOnSale > 0.005)
            <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200">
                <p class="font-medium">Tahsilat kaydı var</p>
                <p class="mt-1">Bu siparişe bağlı {{ number_format($paidOnSale, 2, ',', '.') }} ₺ tahsilat görünüyor. İptal sonrası tahsilat kayıtları silinmez; gerekirse müşteri tahsilatlarından düzenleyin.</p>
            </div>
            @endif
        </div>
        <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="px-5 pb-5 flex gap-3 justify-end">
            @csrf
            <button type="button" @click="showCancelModal = false" class="btn-secondary min-h-[44px]">Vazgeç</button>
            <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded-[0.625rem] bg-red-600 text-white font-medium text-sm hover:bg-red-700 transition-colors">Evet, Siparişi İptal Et</button>
        </form>
    </div>
</div>
@endif

{{-- Müşteriye mail gönder --}}
<div x-show="showCustomerEmail" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="customer-email-title">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCustomerEmail = false"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 pt-5 pb-1">
            <h2 id="customer-email-title" class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Müşteriye Mail Gönder</h2>
            <p class="mt-1 text-sm text-neutral-500 dark:text-slate-400">{{ $sale->saleNumber }} numaralı satış fişi e-posta ile gönderilecek.</p>
        </div>
        <form method="POST" action="{{ route('sales.send-customer-email', $sale) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="form-label">Alıcı e-posta</label>
                <input type="email" name="email" value="{{ $sale->customer?->email }}" required class="form-input min-h-[44px]" placeholder="ornek@email.com">
                @if(!$sale->customer?->email)
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Müşteri kartında e-posta yok, lütfen bir adres girin.</p>
                @endif
            </div>
            <div>
                <label class="form-label">Ek not (opsiyonel)</label>
                <textarea name="note" rows="3" class="form-input form-textarea" placeholder="Mailde görünecek kısa bir not..."></textarea>
            </div>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" @click="showCustomerEmail = false" class="btn-secondary min-h-[44px]">İptal</button>
                <button type="submit" class="btn-primary min-h-[44px]">Gönder</button>
            </div>
        </form>
    </div>
</div>

{{-- Ödeme al modal --}}
@if($sale->customerId)
@include('partials.sale-payment-modal', compact('sale', 'kasalar', 'saleRemaining', 'suppliers'))
@endif
</div>
@endsection
