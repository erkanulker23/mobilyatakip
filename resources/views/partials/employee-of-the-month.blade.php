@php
    $person = $employeeOfTheMonth;
    $monthLabel = $employeeOfTheMonthLabel ?? now()->locale('tr')->isoFormat('MMMM YYYY');
    $initial = mb_strtoupper(mb_substr($person->name ?? '?', 0, 1));
    $salesCount = (int) ($person->sales_count ?? 0);
    $salesTotal = (float) ($person->sales_total ?? 0);
@endphp

<section
    id="employee-of-the-month"
    class="card relative overflow-hidden mb-6 border-emerald-200/70 dark:border-emerald-900/40 bg-gradient-to-r from-emerald-50/80 via-white to-amber-50/40 dark:from-emerald-950/20 dark:via-neutral-900 dark:to-amber-950/10"
    aria-label="Ayın elemanı"
>
    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-emerald-500 via-emerald-400 to-amber-400" aria-hidden="true"></div>

    <div class="relative flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5 px-4 py-4 sm:px-5 sm:py-4">
        <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
            <div class="relative shrink-0">
                @if($person->photoUrl)
                    <img
                        src="{{ storage_url($person->photoUrl) }}"
                        alt="{{ $person->name }}"
                        class="h-14 w-14 rounded-full object-cover ring-2 ring-white dark:ring-neutral-800 shadow-sm"
                    >
                @else
                    <div class="h-14 w-14 rounded-full bg-emerald-100 dark:bg-emerald-900/40 ring-2 ring-white dark:ring-neutral-800 flex items-center justify-center text-lg font-semibold text-emerald-700 dark:text-emerald-300">
                        {{ $initial }}
                    </div>
                @endif
                <span class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-amber-950 ring-2 ring-white dark:ring-neutral-900" aria-hidden="true">
                    <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 7.1-1.01L12 2z"/></svg>
                </span>
            </div>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-0.5">
                    <span class="inline-flex items-center rounded-md bg-emerald-600/10 dark:bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                        Ayın Elemanı
                    </span>
                    <span class="text-[11px] text-neutral-500 dark:text-neutral-400 capitalize">{{ $monthLabel }}</span>
                </div>
                <h2 class="text-lg sm:text-xl font-semibold text-neutral-900 dark:text-white truncate">
                    {{ $person->name }}
                </h2>
                @if($person->title)
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 truncate">{{ $person->title }}</p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:gap-3 sm:shrink-0 pl-[4.25rem] sm:pl-0">
            <div class="inline-flex items-center gap-2 rounded-lg bg-white/80 dark:bg-neutral-800/80 border border-neutral-200/80 dark:border-neutral-700 px-3 py-2">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-neutral-400">Adet</span>
                <span class="text-base font-semibold tabular-nums text-neutral-900 dark:text-white">{{ $salesCount }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-lg bg-emerald-600/10 dark:bg-emerald-500/15 border border-emerald-200/80 dark:border-emerald-800/50 px-3 py-2">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600/80 dark:text-emerald-400/80">Ciro</span>
                <span class="text-base font-semibold tabular-nums text-emerald-700 dark:text-emerald-300">₺{{ number_format($salesTotal, 0, ',', '.') }}</span>
            </div>
            <a
                href="{{ route('personnel.show', $person->id) }}"
                class="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 whitespace-nowrap"
            >
                Profili gör
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </div>
</section>
