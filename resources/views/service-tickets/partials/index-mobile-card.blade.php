@php
    use App\Support\ServiceTicketStatus;

    $showCustomerNames = $showCustomerNames ?? !($hideCommercialData ?? false);
    $problems = ServiceTicketStatus::normalizeProblems($ticket->reportedProblems ?? []);
    if ($problems === [] && $ticket->issueType) {
        $problems = [['description' => $ticket->issueType, 'status' => 'bekliyor']];
    }
    $status = $ticket->status ?? 'acildi';
    $isCancelled = $status === 'iptal';
    $dueDate = $ticket->dueDate;
    $dueHint = null;
    $dueClass = 'text-neutral-500';
    if ($dueDate && ! ServiceTicketStatus::isClosed($status)) {
        $daysLeft = (int) now()->startOfDay()->diffInDays($dueDate, false);
        if ($daysLeft < 0) {
            $dueHint = abs($daysLeft) . ' gün gecikti';
            $dueClass = 'text-red-600 dark:text-red-400 font-medium';
        } elseif ($daysLeft === 0) {
            $dueHint = 'Termin bugün';
            $dueClass = 'text-amber-600 dark:text-amber-400 font-medium';
        } elseif ($daysLeft <= 3) {
            $dueHint = $daysLeft . ' gün kaldı';
            $dueClass = 'text-amber-600 dark:text-amber-400';
        }
    }
@endphp
<article class="p-4 {{ $isCancelled ? 'opacity-60 bg-neutral-50 dark:bg-neutral-900/40' : '' }}">
    <div class="flex items-start justify-between gap-3 mb-3">
        <div class="min-w-0">
            <a href="{{ route('service-tickets.show', $ticket) }}" class="font-semibold text-neutral-900 dark:text-neutral-100 hover:text-emerald-600 dark:hover:text-emerald-400">
                {{ $ticket->ticketNumber }}
            </a>
            @if($showCustomerNames && $ticket->customer)
            <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-0.5 truncate">{{ $ticket->customer->name }}</p>
            @endif
            @if($ticket->sale)
            <p class="text-xs text-neutral-500 mt-0.5">{{ $ticket->sale->saleNumber }}</p>
            @endif
        </div>
        <span class="badge {{ ServiceTicketStatus::badgeClass($status) }} shrink-0">{{ ServiceTicketStatus::label($status) }}</span>
    </div>

    <p class="text-sm text-neutral-700 dark:text-neutral-300 line-clamp-2">{{ $problems[0]['description'] ?? '—' }}</p>
    <p class="text-xs text-neutral-500 mt-1">{{ ServiceTicketStatus::problemSummary($problems) }}</p>

    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400">
        <span>{{ $ticket->createdAt?->format('d.m.Y') ?? '—' }}</span>
        @if($ticket->assignedDriverName)
        <span>Sevkiyat: {{ $ticket->assignedDriverName }}</span>
        @endif
        @if($dueHint)
        <span class="{{ $dueClass }}">{{ $dueHint }}</span>
        @endif
    </div>

    <div class="mt-3 flex items-center justify-between gap-2">
        <form method="POST" action="{{ route('service-tickets.update-status', $ticket) }}" class="flex-1 min-w-0">
            @csrf
            @method('PATCH')
            <select name="status" class="form-select text-xs py-2 w-full" onchange="this.form.submit()">
                @foreach(ServiceTicketStatus::STATUSES as $value => $label)
                <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('service-tickets.show', $ticket) }}" class="btn-secondary text-xs px-3 py-2 shrink-0">Detay</a>
    </div>
</article>
