@php
    use App\Models\SaleProductionStage;
    use App\Support\SaleDelivery;
    use App\Support\SaleProductionStageSchema;

    $productionStages = $productionStages ?? collect();
    $productionStagesReady = $productionStagesReady ?? SaleProductionStageSchema::isReady();
    $canEditProduction = $canEditProduction ?? false;
    $openDeficienciesCount = $openDeficienciesCount ?? 0;
    $showPanel = $productionStagesReady
        && ($productionStages->isNotEmpty()
            || $sale->workshopCompletedAt
            || SaleDelivery::currentStatus($sale) === SaleDelivery::IN_PRODUCTION
            || $canEditProduction);
@endphp

@if($showPanel)
<div class="mt-6 card overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-800 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Atölye Takibi</h2>
            <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Üretim aşamaları ve eksiklik kayıtları</p>
        </div>
        @if(SaleDelivery::currentStatus($sale) === SaleDelivery::IN_PRODUCTION && auth()->user()?->isWorkshop())
        <a href="{{ route('workshop.show', $sale) }}" class="btn-secondary text-sm">Atölye Detayı</a>
        @endif
    </div>
    <div class="p-6">
        @if($sale->workshopCompletedAt)
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-100">
            Atölyeden çıkış: <strong>{{ $sale->workshopCompletedAt->format('d.m.Y H:i') }}</strong>
        </div>
        @endif

        @if($canEditProduction && $openDeficienciesCount > 0)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            Bu siparişte <strong>{{ $openDeficienciesCount }}</strong> açık eksiklik kaydı var.
        </div>
        @endif

        @if($productionStages->isEmpty())
        <p class="text-sm text-neutral-500">Henüz atölye kaydı yok.</p>
        @else
        <div class="space-y-3">
            @foreach($productionStages as $stage)
            @include('workshop.partials.production-stage-item', ['stage' => $stage, 'showActions' => $canEditProduction])
            @endforeach
        </div>
        @endif

        @if($canEditProduction)
        @include('partials.sale-production-stage-form', ['sale' => $sale, 'formId' => 'saleWorkshopNoteForm'])
        @elseif(SaleDelivery::currentStatus($sale) !== SaleDelivery::IN_PRODUCTION && !($sale->isCancelled ?? false))
        <p class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-800 text-sm text-neutral-500">
            Aşama eklemek için sipariş durumunun <strong>Üretimde</strong> olması gerekir.
        </p>
        @endif
    </div>
</div>
@endif
