@extends('layouts.app')
@section('title', 'Atölye - ' . $sale->saleNumber)
@section('content')
@php
    use App\Support\SaleDelivery;

    $orderStatus = $orderStatus ?? SaleDelivery::currentStatus($sale);
    $status = $orderStatus;
    $canAddProductionStage = $canAddProductionStage ?? ! ($sale->isCancelled ?? false);
    $canEditProduction = $canEditProduction ?? ($status === SaleDelivery::IN_PRODUCTION);
    $termin = SaleDelivery::terminListMeta($sale);
    $backUrl = $backUrl ?? route('workshop.index');
@endphp
<div class="mb-6">
    <nav class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ $backUrl }}" class="hover:text-neutral-900 dark:hover:text-white">{{ str_contains($backUrl, 'termin-yaklasan') ? 'Termin Raporu' : 'Atölye' }}</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-neutral-300">{{ $sale->saleNumber }}</span>
    </nav>
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <h1 class="page-title">{{ $sale->saleNumber }}</h1>
            <p class="page-desc">{{ SaleDelivery::label($status) }}@if($showCustomerNames && $sale->customer?->name) · {{ $sale->customer->name }}@endif</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.workshop.mobilya', $sale) }}" target="_blank" class="btn-secondary text-sm">Atölye Fişi</a>
            <a href="{{ route('sales.workshop.koltuk', $sale) }}" target="_blank" class="btn-secondary text-sm">Koltuk Fişi</a>
            @if($canEditProduction)
            <form method="POST" action="{{ route('workshop.complete-production', $sale) }}" onsubmit="return confirm('Sipariş atölyeden çıktı olarak işaretlenecek. Onaylıyor musunuz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-[0.625rem] hover:bg-emerald-700 font-medium text-sm transition-colors">
                    Üretim Bitti — Atölyeden Çıktı
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

@if(! $canAddProductionStage)
<div class="mb-6 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900/40 dark:text-neutral-300">
    Bu sipariş iptal edilmiş; yeni not eklenemez.
</div>
@elseif(! $canEditProduction)
<div class="mb-6 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900/40 dark:text-neutral-300">
    Bu sipariş henüz <strong>üretimde değil</strong>. Yine de not ekleyebilirsiniz; üretim tamamlama işlemi sipariş üretime alındığında kullanılabilir.
</div>
@endif

@if(empty($productionStagesReady))
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
    Not ekleme henüz aktif değil. Sistem yöneticisinin <code class="text-xs">php artisan migrate --force</code> çalıştırması gerekiyor.
</div>
@elseif($canAddProductionStage)
<div class="card p-6 mb-6 border-amber-200/80 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/20">
    @include('partials.sale-production-stage-form', ['sale' => $sale, 'formId' => 'workshopNoteForm', 'compact' => true])
</div>
@endif

@if($canAddProductionStage && ($openDeficienciesCount ?? 0) > 0)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
    Bu siparişte <strong>{{ $openDeficienciesCount }}</strong> açık eksiklik/yanlış parça kaydı var.
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-4">Sipariş Kalemleri</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800">
                            <th class="table-th">Ürün</th>
                            <th class="table-th">Kod</th>
                            <th class="table-th">Adet</th>
                            <th class="table-th">Kalem Açıklaması</th>
                            <th class="table-th">Ürün Detayı</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse($sale->items as $item)
                        @php
                            $itemName = $item->productName ?? $item->product?->name ?? 'Ürün';
                            $itemDetail = trim((string) ($item->product?->description ?? ''));
                        @endphp
                        <tr>
                            <td class="table-td font-medium">{{ $itemName }}</td>
                            <td class="table-td text-sm text-neutral-500">{{ $item->product?->sku ?: '—' }}</td>
                            <td class="table-td">{{ $item->quantity }}</td>
                            <td class="table-td text-sm text-neutral-600 dark:text-neutral-400 whitespace-pre-wrap">{{ $item->description ?: '—' }}</td>
                            <td class="table-td text-sm text-neutral-600 dark:text-neutral-400 whitespace-pre-wrap">{{ $itemDetail !== '' ? $itemDetail : '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="table-td text-neutral-500">Kalem yok</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-4">Notlar</h2>
            @if($sale->productionStages->isEmpty())
            <p class="text-neutral-500 text-sm">Henüz not eklenmemiş.</p>
            @else
            <div class="space-y-4">
                @foreach($sale->productionStages as $stage)
                @include('workshop.partials.production-stage-item', ['stage' => $stage])
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-4">Sipariş Bilgileri</h2>
            <dl class="space-y-3 text-sm">
                @if($showCustomerNames)
                <div><dt class="text-neutral-500">Müşteri</dt><dd class="font-medium">{{ $sale->customer?->name ?? '—' }}</dd></div>
                @if($sale->customer)
                <div><dt class="text-neutral-500">İl / İlçe</dt><dd>{{ trim(($sale->customer->city?->name ?? '') . ' / ' . ($sale->customer->district?->name ?? ''), ' /') ?: '—' }}</dd></div>
                @if($sale->customer->full_address ?? $sale->customer->address ?? null)
                <div><dt class="text-neutral-500">Adres</dt><dd class="whitespace-pre-wrap">{{ $sale->customer->full_address ?? $sale->customer->address }}</dd></div>
                @endif
                @endif
                @endif
                <div><dt class="text-neutral-500">Durum</dt><dd><span class="badge badge-blue">{{ SaleDelivery::label($orderStatus) }}</span></dd></div>
                <div><dt class="text-neutral-500">Sipariş No</dt><dd class="font-medium">{{ $sale->saleNumber }}</dd></div>
                <div><dt class="text-neutral-500">Termin</dt>
                    <dd class="font-medium">
                        @if($sale->dueDate)
                        {{ $sale->dueDate->format('d.m.Y') }}
                        @if($termin['suffix'] ?? null)
                        <span class="block text-xs {{ $termin['class'] ?? 'text-neutral-500' }}">{{ $termin['suffix'] }}</span>
                        @endif
                        @else — @endif
                    </dd>
                </div>
                @if($showSalesPersonnel)
                <div><dt class="text-neutral-500">Satış Temsilcisi</dt><dd class="font-medium">{{ $sale->personnel?->name ?? '—' }}</dd></div>
                @endif
                <div><dt class="text-neutral-500">Satış Tarihi</dt><dd class="font-medium">{{ $sale->saleDate?->format('d.m.Y') ?? '—' }}</dd></div>
                @if($sale->notes && ($showCustomerNames || empty($hideCommercialData)))
                <div><dt class="text-neutral-500">Sipariş Notları</dt><dd class="font-medium whitespace-pre-wrap">{{ $sale->notes }}</dd></div>
                @endif
            </dl>
        </div>

        @if($canEditProduction)
        <div class="card p-6 border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/40 dark:bg-emerald-950/20">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-1">Üretim Tamamlandı</h2>
            <p class="text-xs text-neutral-600 dark:text-neutral-400 mb-4">Sipariş atölyeden çıktığında durum <strong>Teslim bekliyor</strong> olur.</p>
            <form method="POST" action="{{ route('workshop.complete-production', $sale) }}" onsubmit="return confirm('Sipariş atölyeden çıktı olarak işaretlenecek. Emin misiniz?');">
                @csrf
                <button type="submit" class="btn-primary w-full justify-center bg-emerald-600 hover:bg-emerald-700 border-emerald-600">Atölyeden Çıktı — Üretim Bitti</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
