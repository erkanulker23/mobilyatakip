@php
    use App\Models\SaleProductionStage;

    $productLabel = $stage->productLabel();
    $showActions = $showActions ?? false;
@endphp
<div class="border rounded-xl p-4 {{ $stage->isCompleted ? 'border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 opacity-90' : 'border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-900/30' }}">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
        <div class="flex flex-wrap items-center gap-2 min-w-0">
            <span class="badge {{ $stage->type === SaleProductionStage::TYPE_DEFICIENCY ? 'badge-amber' : 'badge-blue' }}">
                {{ SaleProductionStage::typeLabel($stage->type) }}
            </span>
            @if($productLabel)
            <span class="badge badge-neutral">{{ $productLabel }}</span>
            @endif
            @if($stage->isCompleted)
            <span class="badge badge-green">Giderildi</span>
            @endif
            <span class="text-xs text-neutral-500">{{ $stage->actionDate?->format('d.m.Y H:i') ?? '—' }}</span>
            @if($stage->user)
            <span class="text-xs text-neutral-500">· {{ $stage->user->name }}</span>
            @endif
        </div>
        @if($showActions && ! $stage->isCompleted)
        <form method="POST" action="{{ route('workshop.complete-stage', $stage) }}" class="shrink-0">
            @csrf
            <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Giderildi</button>
        </form>
        @endif
    </div>
    <p class="text-sm text-neutral-800 dark:text-neutral-200 whitespace-pre-wrap {{ $stage->isCompleted ? 'line-through opacity-70' : '' }}">{{ $stage->notes }}</p>
    @if($stage->isCompleted && $stage->completedAt)
    <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-2">
        {{ $stage->completedByUser?->name ?? 'Atölye' }} · {{ $stage->completedAt->format('d.m.Y H:i') }}
    </p>
    @endif
</div>
