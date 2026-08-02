@php
    $change = (float) ($change ?? 0);
    $isUp = $change > 0;
    $isDown = $change < 0;
    $isFlat = ! $isUp && ! $isDown;
@endphp
<span @class([
    'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums',
    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => $isUp,
    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $isDown,
    'bg-neutral-100 text-neutral-600 dark:bg-slate-700 dark:text-slate-300' => $isFlat,
])>
    @if($isUp)
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
    @elseif($isDown)
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
    @endif
    @if($isFlat)
        —
    @else
        {{ $isUp ? '+' : '' }}{{ number_format($change, $change == (int) $change ? 0 : 1, ',', '.') }}%
    @endif
</span>
