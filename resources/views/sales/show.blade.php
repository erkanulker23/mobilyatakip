@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::sale($sale))
@section('content')
@php
    $currentOrderStatus = \App\Support\SaleDelivery::currentStatus($sale);
    $initialDeliveryStatus = old('deliveryStatus', $currentOrderStatus);
@endphp
<div x-data="{
    showCustomerEmail: false,
    showStatusModal: @json(old('deliveryStatus') !== null || $errors->has('deliveredAt')),
    showPaymentModal: @json(session('open_payment_modal') || (old('redirectToSale') && old('redirectToSale') == $sale->id)),
    deliveryStatus: @json($initialDeliveryStatus),
    openStatusModal() {
        this.deliveryStatus = @json($currentOrderStatus);
        this.showStatusModal = true;
    }
}">
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                <a href="{{ route('sales.index') }}" class="hover:text-neutral-900">Satışlar</a>
                <span>/</span>
                <span class="text-neutral-700">{{ $sale->saleNumber }}</span>
            </div>
            <h1 class="page-title">{{ $sale->saleNumber }} @if($sale->isCancelled ?? false)<span class="ml-2 text-sm font-normal px-2 py-1 rounded-full bg-red-100 text-red-700">İptal</span>@endif @include('partials.final-measurement-badge', ['sale' => $sale, 'class' => 'align-middle'])</h1>
            <p class="page-desc">
                Satış faturası @if($sale->customer)· Müşteri: <a href="{{ route('customers.show', $sale->customer) }}" class="font-medium text-emerald-600 hover:text-emerald-700">{{ $sale->customer->name }}</a>@else· Müşteri: —@endif
                @if(!($sale->isCancelled ?? false))
                · @include('partials.payment-status-badge', ['sale' => $sale])
                @if(\App\Support\SaleDelivery::currentStatus($sale) !== \App\Support\SaleDelivery::PENDING)
                · @include('partials.delivery-status-badge', ['sale' => $sale])
                @if(\App\Support\SaleDelivery::isDelivered($sale))
                <span class="text-neutral-500 text-sm">({{ $sale->deliveredAt->format('d.m.Y') }})</span>
                @endif
                <span class="text-neutral-500 text-sm">{{ \App\Support\CustomerBalance::saleStatus($sale)['description'] }}</span>
                @endif
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(!($sale->isCancelled ?? false))
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
            <button type="button" @click="openStatusModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium text-sm">
                Sipariş Durumunu Güncelle
            </button>
            @endif
            <button type="button" @click="showCustomerEmail = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Müşteriye Mail Gönder
            </button>
            @if(!($sale->isCancelled ?? false) && $sale->customerId)
            <button type="button" @click="showPaymentModal = true" class="btn-primary text-sm">Ödeme Al</button>
            @endif
            @if(!($sale->isCancelled ?? false))
            <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="inline" onsubmit="return confirm('Bu satışı iptal etmek istediğinize emin misiniz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 font-medium text-sm">İptal Et</button>
            </form>
            @endif
            @include('partials.action-buttons', [
                'edit' => !($sale->isCancelled ?? false) ? route('sales.edit', $sale) : null,
                'destroy' => route('sales.destroy', $sale),
            ])
        </div>
    </div>
</div>

@if(session('show_sale_actions'))
<div class="mb-6 p-4 rounded-xl bg-neutral-50 border border-neutral-200">
    <p class="text-neutral-900 font-medium mb-3">Sipariş oluşturuldu. Hemen paylaşabilirsiniz:</p>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('sales.workshop.koltuk', $sale) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-100 text-sm font-medium">Koltuk Atölye Fişi Çıkar</a>
        <a href="{{ route('sales.workshop.mobilya', $sale) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-100 text-sm font-medium">Mobilya Atölyesi Fişi Çıkar</a>
        <a href="{{ route('sales.print', $sale) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-100 text-sm font-medium">Sipariş Fişi Yaz</a>
        <a href="{{ route('sales.shipment', $sale) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-100 text-sm font-medium">Sevkiyat Fişi Çıkar</a>
        <button type="button" @click="openStatusModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">Sipariş Durumunu Güncelle</button>
        <button type="button" @click="showCustomerEmail = true" class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-900 text-white rounded-lg hover:bg-neutral-800 text-sm font-medium">Müşteriye Mail Gönder</button>
    </div>
</div>
@endif

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

@if($sale->needsFinalMeasurement ?? false)
<div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-950">
    <div class="flex items-start gap-3">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-200 text-amber-900" aria-hidden="true">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
        </span>
        <div>
            <p class="font-semibold text-lg">Kesin ölçüye gidilecek</p>
            <p class="text-sm text-amber-900/90 mt-1">Bu sipariş için saha ölçüsü alınacaktır. Üretim ve teslimat kesin ölçü sonrası planlanır.</p>
        </div>
    </div>
</div>
@endif

@include('partials.invoice-document', \App\Support\SaleDocument::invoiceParams($sale))

<div class="mt-6">
    @include('partials.drawing-files-display', ['drawingFiles' => $sale->drawingFiles ?? []])
</div>

@php
    $pt = \App\Support\PaymentType::labels();
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
                    @if($activity->type === 'created') bg-slate-200 text-neutral-700
                    @elseif($activity->type === 'supplier_email_sent') bg-blue-100 text-blue-700
                    @elseif($activity->type === 'supplier_email_read') bg-amber-100 text-amber-700
                    @elseif($activity->type === 'supplier_email_replied') bg-emerald-100 text-emerald-700
                    @elseif($activity->type === 'customer_email_sent') bg-indigo-100 text-indigo-700
                    @else bg-slate-100 text-slate-600 @endif">
                    @if($activity->type === 'created') 📋
                    @elseif($activity->type === 'supplier_email_sent') ✉️
                    @elseif($activity->type === 'supplier_email_read') 👁️
                    @elseif($activity->type === 'supplier_email_replied') ↩️
                    @elseif($activity->type === 'customer_email_sent') 📧
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
                <p class="font-medium text-neutral-900">
                    <a href="{{ $isLinked ? route('customer-payments.show', $p) : route('customer-payments.edit', $p) }}" class="text-emerald-600 hover:text-emerald-700 hover:underline">Tahsilat alındı: {{ number_format($p->amount ?? 0, 0, ',', '.') }} ₺</a>
                    @if($pt[$p->paymentType ?? ''] ?? null)
                    <span class="text-slate-600 font-normal">({{ $pt[$p->paymentType ?? ''] }})</span>
                    @endif
                    @if(!$isLinked)
                    <span class="ml-1 text-amber-600 text-sm font-normal">— Faturaya bağlı değil</span>
                    @endif
                </p>
                <p class="text-xs text-neutral-500 mt-1">{{ $p->paymentDate?->format('d.m.Y H:i') ?? '—' }}</p>
                @else
                @php $activity = $entry->activity; @endphp
                <p class="font-medium text-neutral-900">{{ $activity->description }}</p>
                @if($activity->metadata && isset($activity->metadata['suppliers']))
                <p class="text-sm page-desc">
                    @foreach($activity->metadata['suppliers'] as $s)
                    <span class="inline-block mr-2">{{ $s['name'] }} &lt;{{ $s['email'] }}&gt;</span>
                    @endforeach
                </p>
                @endif
                <p class="text-xs text-neutral-500 mt-1">{{ $activity->createdAt->format('d.m.Y H:i') }}</p>
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
                    <span class="text-neutral-500">Ödeme durumu</span>
                    @include('partials.payment-status-badge', ['sale' => $sale])
                </div>
                @if(\App\Support\SaleDelivery::isDelivered($sale))
                <div class="flex items-center justify-between gap-3">
                    <span class="text-neutral-500">Kayıtlı teslim tarihi</span>
                    <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $sale->deliveredAt->format('d.m.Y') }}</span>
                </div>
                @endif
                @if($sale->serviceTickets->isNotEmpty())
                <div class="flex items-start justify-between gap-3">
                    <span class="text-neutral-500 shrink-0">SSH kayıtları</span>
                    <div class="text-right space-y-1">
                        @foreach($sale->serviceTickets as $ticket)
                        <div>
                            <a href="{{ route('service-tickets.show', $ticket) }}" class="font-medium text-emerald-600 hover:text-emerald-700">{{ $ticket->ticketNumber }}</a>
                            <span class="text-neutral-500">· {{ \App\Support\ServiceTicketStatus::label($ticket->status) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div>
                <label class="form-label" for="deliveryStatus">Sipariş durumu</label>
                <select id="deliveryStatus" name="deliveryStatus" x-model="deliveryStatus" class="form-select min-h-[44px]">
                    <option value="pending">Teslim bekliyor</option>
                    <option value="delivered">Teslim edildi</option>
                    <option value="ssh">SSH var</option>
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

{{-- Müşteriye mail gönder --}}
<div x-show="showCustomerEmail" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="customer-email-title">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCustomerEmail = false"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
        <div class="px-5 pt-5 pb-1">
            <h2 id="customer-email-title" class="text-lg font-semibold text-neutral-900">Müşteriye Mail Gönder</h2>
            <p class="mt-1 text-sm text-neutral-500 dark:text-slate-400">{{ $sale->saleNumber }} numaralı satış fişi e-posta ile gönderilecek.</p>
        </div>
        <form method="POST" action="{{ route('sales.send-customer-email', $sale) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="form-label">Alıcı e-posta</label>
                <input type="email" name="email" value="{{ $sale->customer?->email }}" required class="form-input min-h-[44px]" placeholder="ornek@email.com">
                @if(!$sale->customer?->email)
                <p class="mt-1 text-xs text-amber-600">Müşteri kartında e-posta yok, lütfen bir adres girin.</p>
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
@include('partials.sale-payment-modal', compact('sale', 'kasalar', 'saleRemaining'))
@endif
</div>
@endsection
