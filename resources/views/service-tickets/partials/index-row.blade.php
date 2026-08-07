@php
    use App\Support\ServiceTicketStatus;

    $problems = ServiceTicketStatus::normalizeProblems($ticket->reportedProblems ?? []);
    if ($problems === [] && $ticket->issueType) {
        $problems = [['description' => $ticket->issueType, 'status' => 'bekliyor']];
    }
    $status = $ticket->status ?? 'acildi';
    $isCancelled = $status === 'iptal';
@endphp
<tr class="hover:bg-slate-50 dark:hover:bg-neutral-900/40 transition-colors {{ $isCancelled ? 'opacity-60 bg-slate-50/80 dark:bg-neutral-900/30' : '' }}">
    <td class="table-td">
        <a href="{{ route('service-tickets.show', $ticket) }}" class="font-medium text-neutral-900 dark:text-neutral-100 hover:underline">{{ $ticket->ticketNumber }}</a>
    </td>
    <td class="table-td text-slate-600 dark:text-neutral-400">{{ $ticket->sale?->saleNumber ?? '—' }}</td>
    @if($showCustomerNames)
    <td class="table-td text-slate-600 dark:text-neutral-400">{{ $ticket->customer?->name ?? '—' }}</td>
    @endif
    <td class="table-td text-slate-600 dark:text-neutral-400">
        <span class="block">{{ Str::limit($problems[0]['description'] ?? '—', 28) }}</span>
        <span class="text-xs text-neutral-500 dark:text-neutral-500">{{ ServiceTicketStatus::problemSummary($problems) }}</span>
    </td>
    <td class="table-td text-slate-600 dark:text-neutral-400">{{ $ticket->assignedDriverName ?: '—' }}</td>
    <td class="table-td text-slate-600 dark:text-neutral-400">{{ $ticket->openingDetail?->user?->name ?? '—' }}</td>
    <td class="table-td text-slate-600 dark:text-neutral-400">{{ $ticket->closedByUserName() ?? '—' }}</td>
    <td class="table-td">
        <form method="POST" action="{{ route('service-tickets.update-status', $ticket) }}" class="inline">
            @csrf
            @method('PATCH')
            <select name="status" class="form-select text-xs py-1.5 px-2 min-w-[8.5rem] max-w-[10rem]" onchange="this.form.submit()">
                @foreach(ServiceTicketStatus::STATUSES as $value => $label)
                <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </td>
    <td class="table-td text-slate-600 dark:text-neutral-400">{{ $ticket->createdAt?->format('d.m.Y') ?? '—' }}</td>
    <td class="table-td">
        @include('partials.action-buttons', [
            'show' => route('service-tickets.show', $ticket),
            'edit' => route('service-tickets.edit', $ticket),
            'print' => empty($hideCommercialData) ? route('service-tickets.print', $ticket) : null,
            'destroy' => empty($hideCommercialData) ? route('service-tickets.destroy', $ticket) : null,
        ])
    </td>
</tr>
