@php
    use App\Support\ServiceTicketStatus;

    $showCustomerNames = $showCustomerNames ?? !($hideCommercialData ?? false);
    $problems = ServiceTicketStatus::normalizeProblems($ticket->reportedProblems ?? []);
    if ($problems === [] && $ticket->issueType) {
        $problems = [['description' => $ticket->issueType, 'status' => 'bekliyor']];
    }
    $status = $ticket->status ?? 'acildi';
    $isCancelled = $status === 'iptal';
    $isWaiting = in_array($status, ['parca_bekleniyor', 'sevkiyatci_bekleniyor'], true);

    $dueHint = null;
    $dueClass = '';
    if ($ticket->dueDate && ! ServiceTicketStatus::isClosed($status)) {
        $daysLeft = (int) now()->startOfDay()->diffInDays($ticket->dueDate, false);
        if ($daysLeft < 0) {
            $dueHint = abs($daysLeft) . 'g gecikmiş';
            $dueClass = 'text-red-600 dark:text-red-400';
        } elseif ($daysLeft <= 3) {
            $dueHint = $daysLeft === 0 ? 'bugün' : $daysLeft . 'g kaldı';
            $dueClass = 'text-amber-600 dark:text-amber-400';
        }
    }
@endphp
<tr class="hover:bg-neutral-50/80 dark:hover:bg-neutral-900/40 transition-colors {{ $isCancelled ? 'opacity-60 bg-neutral-50/80 dark:bg-neutral-900/30' : '' }} {{ $isWaiting ? 'bg-amber-50/30 dark:bg-amber-950/10' : '' }}">
    <td class="table-td">
        <a href="{{ route('service-tickets.show', $ticket) }}" class="font-semibold text-neutral-900 dark:text-neutral-100 hover:text-emerald-600 dark:hover:text-emerald-400">{{ $ticket->ticketNumber }}</a>
        @if($dueHint)
        <span class="block text-[11px] mt-0.5 {{ $dueClass }}">{{ $dueHint }}</span>
        @endif
    </td>
    <td class="table-td text-neutral-600 dark:text-neutral-400">
        @if($ticket->sale)
            @if(empty($hideCommercialData))
            <a href="{{ route('sales.show', $ticket->sale) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline">{{ $ticket->sale->saleNumber }}</a>
            @else
            {{ $ticket->sale->saleNumber }}
            @endif
        @else
        —
        @endif
    </td>
    @if($showCustomerNames)
    <td class="table-td text-neutral-600 dark:text-neutral-400">
        @if($ticket->customer)
            @if(empty($hideCommercialData))
            <a href="{{ route('customers.show', $ticket->customer) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline">{{ $ticket->customer->name }}</a>
            @else
            {{ $ticket->customer->name }}
            @endif
        @else
        —
        @endif
    </td>
    @endif
    <td class="table-td text-neutral-600 dark:text-neutral-400 max-w-[14rem]">
        <span class="block truncate" title="{{ $problems[0]['description'] ?? '' }}">{{ Str::limit($problems[0]['description'] ?? '—', 36) }}</span>
        <span class="text-xs text-neutral-500">{{ ServiceTicketStatus::problemSummary($problems) }}</span>
    </td>
    <td class="table-td text-neutral-600 dark:text-neutral-400">{{ $ticket->assignedDriverName ?: '—' }}</td>
    <td class="table-td text-neutral-600 dark:text-neutral-400">{{ $ticket->openingDetail?->user?->name ?? '—' }}</td>
    <td class="table-td text-neutral-600 dark:text-neutral-400">{{ $ticket->closedByUserName() ?? '—' }}</td>
    <td class="table-td">
        <form method="POST" action="{{ route('service-tickets.update-status', $ticket) }}" class="inline-flex flex-col gap-1">
            @csrf
            @method('PATCH')
            <span class="badge {{ ServiceTicketStatus::badgeClass($status) }} self-start">{{ ServiceTicketStatus::label($status) }}</span>
            <select name="status" class="form-select text-xs py-1.5 px-2 min-w-[10rem] max-w-[15rem]" title="Durumu değiştir" onchange="this.form.submit()" aria-label="Durum değiştir">
                @foreach(ServiceTicketStatus::STATUSES as $value => $label)
                <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </td>
    <td class="table-td text-neutral-600 dark:text-neutral-400 whitespace-nowrap">
        <span class="block">{{ $ticket->createdAt?->format('d.m.Y') ?? '—' }}</span>
        @if($ticket->dueDate)
        <span class="text-[11px] text-neutral-400">Termin {{ $ticket->dueDate->format('d.m.Y') }}</span>
        @endif
    </td>
    <td class="table-td">
        @include('partials.action-buttons', [
            'show' => route('service-tickets.show', $ticket),
            'edit' => route('service-tickets.edit', $ticket),
            'print' => empty($hideCommercialData) ? route('service-tickets.print', $ticket) : null,
            'destroy' => empty($hideCommercialData) ? route('service-tickets.destroy', $ticket) : null,
        ])
    </td>
</tr>
