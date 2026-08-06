@php
    use App\Support\ServiceTicketStatus;
    $oldStages = old('newStages', ['']);
    $stageHistory = $serviceTicket->details->sortByDesc('actionDate');
    $problems = ServiceTicketStatus::normalizeProblems($serviceTicket->reportedProblems ?? []);
    if ($problems === [] && $serviceTicket->issueType) {
        $problems = [['description' => $serviceTicket->issueType, 'status' => 'bekliyor']];
    }
@endphp
<div class="card p-6 max-w-3xl">
    <form method="POST" action="{{ route('service-tickets.update', $serviceTicket) }}" class="space-y-5">
        @csrf @method('PUT')

        @if($serviceTicket->sale)
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-4 text-sm">
            <span class="text-neutral-500">İlgili sipariş:</span>
            <span class="font-medium text-neutral-900 dark:text-white ml-1">{{ $serviceTicket->sale->saleNumber }}</span>
        </div>
        @endif

        @if($problems !== [])
        <div>
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-white mb-2">Bildirilen sorunlar</h2>
            <ul class="space-y-2">
                @foreach($problems as $problem)
                <li class="text-sm text-neutral-700 dark:text-neutral-300 flex items-start gap-2">
                    <span class="badge badge-amber shrink-0">{{ ServiceTicketStatus::problemLabel($problem['status'] ?? 'bekliyor') }}</span>
                    <span>{{ $problem['description'] }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
            <div class="px-4 py-3 bg-neutral-50 dark:bg-neutral-900/60 border-b border-neutral-200 dark:border-neutral-700">
                <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">İşlem Aşamaları</h2>
            </div>
            <div class="p-4 space-y-4">
                @if($stageHistory->isNotEmpty())
                <div class="space-y-3 max-h-56 overflow-y-auto">
                    @foreach($stageHistory as $detail)
                    <div class="flex gap-3 text-sm">
                        <span class="shrink-0 w-2 h-2 mt-2 rounded-full bg-neutral-300 dark:bg-neutral-600"></span>
                        <div class="min-w-0">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ ServiceTicketStatus::detailActionLabel($detail->action) }}</p>
                            <p class="text-xs text-neutral-500">{{ $detail->actionDate?->format('d.m.Y H:i') ?? '—' }} · {{ $detail->user?->name ?? '—' }}</p>
                            @if($detail->notes)
                            <p class="text-neutral-600 dark:text-neutral-400 mt-1 whitespace-pre-wrap">{{ $detail->notes }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-neutral-500">Henüz aşama kaydı yok.</p>
                @endif

                <div>
                    <label class="form-label">Yeni aşama ekle</label>
                    <div id="newStagesList" class="space-y-2">
                        @foreach($oldStages as $i => $stageText)
                        <div class="stage-row flex gap-2">
                            <input type="text" name="newStages[]" value="{{ $stageText }}" class="form-input flex-1" placeholder="Örn: Parça değişimi yapıldı">
                            @if($i > 0)
                            <button type="button" class="remove-stage px-3 py-2 text-sm rounded-lg border border-neutral-200 text-neutral-600 hover:bg-neutral-50 shrink-0">Sil</button>
                            @else
                            <span class="w-[52px] shrink-0"></span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <button type="button" id="addStageBtn" class="mt-2 text-sm font-medium text-neutral-700 hover:text-neutral-900">+ Aşama satırı ekle</button>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('service-tickets.show', $serviceTicket) }}" class="btn-secondary">Geri</a>
        </div>
    </form>
</div>
<script>
document.getElementById('addStageBtn')?.addEventListener('click', function () {
    var list = document.getElementById('newStagesList');
    var row = document.createElement('div');
    row.className = 'stage-row flex gap-2';
    row.innerHTML = '<input type="text" name="newStages[]" class="form-input flex-1" placeholder="Yeni aşama">' +
        '<button type="button" class="remove-stage px-3 py-2 text-sm rounded-lg border border-neutral-200 text-neutral-600 hover:bg-neutral-50 shrink-0">Sil</button>';
    list.appendChild(row);
});
document.getElementById('newStagesList')?.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-stage')) {
        e.target.closest('.stage-row')?.remove();
    }
});
</script>
