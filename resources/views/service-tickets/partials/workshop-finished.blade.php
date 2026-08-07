@php
    use App\Support\ServiceTicketStatus;

    $isClosed = ServiceTicketStatus::isClosed($serviceTicket->status ?? '');
    $workshopFinished = $serviceTicket->isWorkshopFinished();
    $workshopFinishedDetail = $serviceTicket->workshopFinishedDetail;
@endphp

@if(! $isClosed)
<div class="card overflow-hidden border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20">
    <div class="card-header bg-emerald-50/80 dark:bg-emerald-950/30">Atölye İşlemi</div>
    <div class="p-5 space-y-4">
        @if($workshopFinished)
        <div class="rounded-xl border border-emerald-200 bg-white/80 px-4 py-3 text-sm dark:border-emerald-800 dark:bg-neutral-900/40">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="font-semibold text-emerald-900 dark:text-emerald-200">Atölyede iş bitti</p>
                    <p class="text-xs text-neutral-500 mt-1">
                        {{ $workshopFinishedDetail?->actionDate?->format('d.m.Y H:i') ?? '—' }}
                        · {{ $workshopFinishedDetail?->user?->name ?? '—' }}
                    </p>
                    @if($workshopFinishedDetail?->notes && $workshopFinishedDetail->notes !== ServiceTicketStatus::WORKSHOP_FINISHED_NOTE)
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-2 whitespace-pre-wrap">{{ $workshopFinishedDetail->notes }}</p>
                    @endif
                </div>
            </div>
        </div>
        @else
        <p class="text-sm text-neutral-600 dark:text-neutral-400">
            Ürün onarımı veya üretimi atölyede tamamlandıysa aşağıdan kaydedin. Servis ekibi bu bilgiyi işlem geçmişinde görür.
        </p>
        <form method="POST" action="{{ route('service-tickets.workshop-finished', $serviceTicket) }}" class="space-y-3">
            @csrf
            <div>
                <label class="form-label" for="workshopFinishedNotes">Not (isteğe bağlı)</label>
                <textarea id="workshopFinishedNotes" name="notes" rows="2" class="form-input form-textarea" placeholder="Örn: Panel değişimi tamamlandı, sevkiyata hazır">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-[0.625rem] hover:bg-emerald-700 font-medium text-sm transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Atölyede İş Bitti
            </button>
        </form>
        @endif
    </div>
</div>
@elseif($workshopFinished)
<div class="card overflow-hidden">
    <div class="card-header">Atölye İşlemi</div>
    <div class="p-5">
        <div class="flex items-center gap-2 text-sm">
            <span class="badge badge-green">Atölyede iş bitti</span>
            <span class="text-neutral-500">
                {{ $workshopFinishedDetail?->actionDate?->format('d.m.Y H:i') ?? '—' }}
                · {{ $workshopFinishedDetail?->user?->name ?? '—' }}
            </span>
        </div>
    </div>
</div>
@endif
