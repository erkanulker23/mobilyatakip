@php
    use App\Support\ServiceTicketStatus;

    $problems = ServiceTicketStatus::normalizeProblems($ticket->reportedProblems ?? []);
    if ($problems === [] && $ticket->issueType) {
        $problems = [['description' => $ticket->issueType, 'status' => 'duzeltildi']];
    }
    $closedAt = $ticket->closedAt ?? $ticket->updatedAt;
@endphp
<article class="group relative flex flex-col rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/90 via-white to-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-emerald-800/40 dark:from-emerald-950/25 dark:via-neutral-900 dark:to-neutral-900">
    <div class="absolute left-0 top-4 bottom-4 w-1 rounded-r-full bg-emerald-500/80" aria-hidden="true"></div>

    <div class="flex items-start justify-between gap-3 pl-3">
        <div class="flex items-start gap-3 min-w-0">
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </span>
            <div class="min-w-0">
                <a href="{{ route('service-tickets.show', $ticket) }}" class="font-semibold text-neutral-900 dark:text-neutral-100 hover:text-emerald-700 dark:hover:text-emerald-300 truncate block">
                    {{ $ticket->ticketNumber }}
                </a>
                @if($showCustomerNames && $ticket->customer)
                <p class="mt-0.5 text-sm text-neutral-600 dark:text-neutral-400 truncate">{{ $ticket->customer->name }}</p>
                @endif
            </div>
        </div>
        <span class="badge badge-green shrink-0">Tamamlandı</span>
    </div>

    <div class="mt-4 pl-3 space-y-2 text-sm">
        <p class="text-neutral-700 dark:text-neutral-300 line-clamp-2">
            {{ $problems[0]['description'] ?? '—' }}
        </p>
        <p class="text-xs text-emerald-700/80 dark:text-emerald-400/80">
            {{ ServiceTicketStatus::problemSummary($problems) }}
        </p>
    </div>

    <dl class="mt-4 pl-3 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
        <div>
            <dt class="text-neutral-500 dark:text-neutral-500">Açan</dt>
            <dd class="font-medium text-neutral-800 dark:text-neutral-200 mt-0.5">{{ $ticket->openingDetail?->user?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-neutral-500 dark:text-neutral-500">Kapatan</dt>
            <dd class="font-medium text-neutral-800 dark:text-neutral-200 mt-0.5">{{ $ticket->closedByUserName() ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-neutral-500 dark:text-neutral-500">Satış</dt>
            <dd class="font-medium text-neutral-800 dark:text-neutral-200 mt-0.5">{{ $ticket->sale?->saleNumber ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-neutral-500 dark:text-neutral-500">Sevkiyatçı</dt>
            <dd class="font-medium text-neutral-800 dark:text-neutral-200 mt-0.5 truncate">{{ $ticket->assignedDriverName ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-neutral-500 dark:text-neutral-500">Açılış</dt>
            <dd class="font-medium text-neutral-800 dark:text-neutral-200 mt-0.5">{{ $ticket->createdAt?->format('d.m.Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-neutral-500 dark:text-neutral-500">Kapanış</dt>
            <dd class="font-medium text-emerald-700 dark:text-emerald-300 mt-0.5">{{ $closedAt?->format('d.m.Y') ?? '—' }}</dd>
        </div>
    </dl>

    <div class="mt-4 pl-3 pt-3 border-t border-emerald-100 dark:border-emerald-900/40 flex items-center justify-end gap-1">
        @include('partials.action-buttons', [
            'show' => route('service-tickets.show', $ticket),
            'edit' => route('service-tickets.edit', $ticket),
            'print' => empty($hideCommercialData) ? route('service-tickets.print', $ticket) : null,
        ])
    </div>
</article>
