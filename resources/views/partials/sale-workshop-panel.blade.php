@php
    use App\Models\SaleProductionStage;
    use App\Support\SaleDelivery;
    use App\Support\SaleProductionStageSchema;

    $productionStages = $productionStages ?? collect();
    $showPanel = SaleProductionStageSchema::isReady()
        && ($productionStages->isNotEmpty()
            || $sale->workshopCompletedAt
            || SaleDelivery::currentStatus($sale) === SaleDelivery::IN_PRODUCTION);
@endphp

@if($showPanel)
<div class="mt-6 card overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-800 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Atölye Takibi</h2>
            <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Üretim aşamaları ve eksiklik kayıtları</p>
        </div>
        @if(SaleDelivery::currentStatus($sale) === SaleDelivery::IN_PRODUCTION)
        <a href="{{ route('workshop.show', $sale) }}" class="btn-secondary text-sm">Atölye Detayı</a>
        @endif
    </div>
    <div class="p-6">
        @if($sale->workshopCompletedAt)
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-100">
            Atölyeden çıkış: <strong>{{ $sale->workshopCompletedAt->format('d.m.Y H:i') }}</strong>
        </div>
        @endif

        @if($productionStages->isEmpty())
        <p class="text-sm text-neutral-500">Henüz atölye kaydı yok.</p>
        @else
        <div class="space-y-3">
            @foreach($productionStages as $stage)
            <div class="border rounded-xl p-4 {{ $stage->isCompleted ? 'border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/20 dark:bg-emerald-950/10' : 'border-neutral-100 dark:border-neutral-800' }}">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="badge {{ $stage->type === SaleProductionStage::TYPE_DEFICIENCY ? 'badge-amber' : 'badge-blue' }}">
                        {{ SaleProductionStage::typeLabel($stage->type) }}
                    </span>
                    @if($stage->isCompleted)
                    <span class="badge badge-green">Giderildi</span>
                    @else
                    <span class="badge badge-red">Açık</span>
                    @endif
                    <span class="text-xs text-neutral-500">{{ $stage->actionDate?->format('d.m.Y H:i') }}</span>
                    @if($stage->user)
                    <span class="text-xs text-neutral-500">· {{ $stage->user->name }}</span>
                    @endif
                </div>
                <p class="text-sm text-neutral-800 dark:text-neutral-200 whitespace-pre-wrap">{{ $stage->notes }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endif
