@php
    $person = $employeeOfTheMonth;
    $monthLabel = $employeeOfTheMonthLabel ?? now()->locale('tr')->isoFormat('MMMM YYYY');
    $initial = mb_strtoupper(mb_substr($person->name ?? '?', 0, 1));
    $salesCount = (int) ($person->sales_count ?? 0);
    $salesTotal = (float) ($person->sales_total ?? 0);
@endphp

@push('head')
<style>
    .eotm-card {
        background:
            radial-gradient(ellipse 90% 70% at 0% 0%, rgba(167, 243, 208, 0.95), transparent 52%),
            radial-gradient(ellipse 80% 65% at 100% 100%, rgba(186, 230, 253, 0.9), transparent 48%),
            linear-gradient(135deg, #ecfdf5 0%, #d1fae5 38%, #e0f2fe 72%, #bae6fd 100%);
        border-color: rgba(16, 185, 129, 0.28);
        box-shadow: 0 20px 50px rgba(16, 185, 129, 0.12), 0 4px 16px rgba(14, 165, 233, 0.08);
    }
    .dark .eotm-card {
        background:
            radial-gradient(ellipse 90% 70% at 0% 0%, rgba(6, 95, 70, 0.55), transparent 52%),
            radial-gradient(ellipse 80% 65% at 100% 100%, rgba(12, 74, 110, 0.5), transparent 48%),
            linear-gradient(135deg, #064e3b 0%, #065f46 42%, #0e7490 100%);
        border-color: rgba(52, 211, 153, 0.25);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
    }
    .eotm-shimmer {
        background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.35) 50%, transparent 60%);
        background-size: 200% 100%;
        animation: eotm-shimmer 4s ease-in-out infinite;
    }
    .dark .eotm-shimmer {
        background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.12) 50%, transparent 60%);
    }
    @keyframes eotm-shimmer {
        0%, 100% { background-position: 200% 0; }
        50% { background-position: -200% 0; }
    }
    .eotm-photo-ring {
        box-shadow:
            0 0 0 4px rgba(52, 211, 153, 0.75),
            0 0 0 8px rgba(255, 255, 255, 0.65),
            0 16px 32px rgba(6, 95, 70, 0.18);
    }
    .dark .eotm-photo-ring {
        box-shadow:
            0 0 0 4px rgba(52, 211, 153, 0.85),
            0 0 0 8px rgba(255, 255, 255, 0.1),
            0 16px 32px rgba(0, 0, 0, 0.35);
    }
    .eotm-pulse {
        animation: eotm-pulse 2.8s ease-in-out infinite;
    }
    @keyframes eotm-pulse {
        0%, 100% { transform: scale(1); opacity: 0.55; }
        50% { transform: scale(1.06); opacity: 0.85; }
    }
    .eotm-sparkle {
        position: absolute;
        width: 4px;
        height: 4px;
        border-radius: 9999px;
        background: #6ee7b7;
        box-shadow: 0 0 8px #34d399;
        animation: eotm-float 5s ease-in-out infinite;
        opacity: 0.75;
    }
    .eotm-sparkle:nth-child(3) { background: #7dd3fc; box-shadow: 0 0 8px #38bdf8; }
    .eotm-sparkle:nth-child(5) { background: #a7f3d0; box-shadow: 0 0 8px #6ee7b7; }
    @keyframes eotm-float {
        0%, 100% { transform: translateY(0) scale(1); opacity: 0.4; }
        50% { transform: translateY(-12px) scale(1.2); opacity: 1; }
    }
    @media (prefers-reduced-motion: reduce) {
        .eotm-shimmer, .eotm-pulse, .eotm-sparkle { animation: none !important; }
    }
</style>
@endpush

<section
    id="employee-of-the-month"
    class="eotm-card relative overflow-hidden rounded-3xl border mb-8"
    aria-label="Ayın elemanı"
>
    <div class="eotm-shimmer absolute inset-0 pointer-events-none" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="top:12%; left:8%; animation-delay:0s" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="top:22%; right:12%; animation-delay:1.2s; width:6px; height:6px" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="bottom:18%; left:18%; animation-delay:2.4s" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="bottom:28%; right:22%; animation-delay:0.8s" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="top:45%; left:42%; animation-delay:1.8s; width:3px; height:3px" aria-hidden="true"></div>

    <div class="relative px-6 py-7 sm:px-8 sm:py-8">
        <div class="flex flex-col lg:flex-row lg:items-center gap-6 lg:gap-8">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 lg:flex-1 min-w-0">
                <div class="relative shrink-0">
                    <div class="eotm-pulse absolute -inset-3 rounded-full bg-emerald-400/25 blur-md" aria-hidden="true"></div>
                    @if($person->photoUrl)
                        <img
                            src="{{ storage_url($person->photoUrl) }}"
                            alt="{{ $person->name }}"
                            class="relative h-28 w-28 sm:h-32 sm:w-32 rounded-full object-cover eotm-photo-ring"
                        >
                    @else
                        <div class="relative h-28 w-28 sm:h-32 sm:w-32 rounded-full eotm-photo-ring bg-gradient-to-br from-emerald-200 to-teal-400 flex items-center justify-center text-3xl sm:text-4xl font-bold text-emerald-950">
                            {{ $initial }}
                        </div>
                    @endif
                    <span class="absolute -bottom-1 -right-1 flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-400 text-white shadow-lg ring-4 ring-white/80 dark:ring-emerald-950/40" aria-hidden="true">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 7.1-1.01L12 2z"/></svg>
                    </span>
                </div>

                <div class="text-center sm:text-left min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/55 dark:bg-white/10 border border-emerald-200/70 dark:border-emerald-400/25 px-3 py-1 mb-2 backdrop-blur-sm">
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-800 dark:text-emerald-200">Ayın Elemanı</span>
                        <span class="h-1 w-1 rounded-full bg-teal-400" aria-hidden="true"></span>
                        <span class="text-[11px] font-medium text-emerald-700/90 dark:text-emerald-100/90 capitalize">{{ $monthLabel }}</span>
                    </div>
                    <h2 class="brand-logo text-2xl sm:text-3xl lg:text-4xl leading-tight text-emerald-950 dark:text-white tracking-tight">
                        {{ $person->name }}
                    </h2>
                    @if($person->title)
                        <p class="mt-1.5 text-base sm:text-lg text-emerald-800/90 dark:text-emerald-100/85 font-medium">{{ $person->title }}</p>
                    @endif
                    <p class="mt-2 text-sm text-emerald-700/80 dark:text-emerald-100/70 max-w-md hidden sm:block">
                        Bu ay en yüksek satış performansı ile öne çıkan ekip üyemiz. Tebrikler!
                    </p>
                </div>
            </div>

            <div class="flex flex-row gap-3 lg:shrink-0 w-full sm:w-auto">
                <div class="flex-1 sm:flex-none sm:min-w-[7.5rem] rounded-2xl bg-white/65 dark:bg-white/10 backdrop-blur-sm border border-emerald-200/60 dark:border-white/10 px-4 py-3 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-700/80 dark:text-emerald-200/80">Adet</p>
                    <p class="mt-0.5 text-2xl sm:text-3xl font-bold tabular-nums text-emerald-950 dark:text-white">{{ $salesCount }}</p>
                </div>
                <div class="flex-1 sm:flex-none sm:min-w-[9rem] rounded-2xl bg-white/65 dark:bg-white/10 backdrop-blur-sm border border-sky-200/60 dark:border-white/10 px-4 py-3 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-teal-700/80 dark:text-sky-200/80">Ciro</p>
                    <p class="mt-0.5 text-xl sm:text-2xl font-bold tabular-nums text-emerald-950 dark:text-white">₺{{ number_format($salesTotal, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="mt-5 pt-4 border-t border-emerald-200/50 dark:border-white/10 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-emerald-700/70 dark:text-emerald-100/60 hidden sm:block">Performans bu ay tamamlanan satışlara göre hesaplanır.</p>
            <a
                href="{{ route('personnel.show', $person->id) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors shadow-lg shadow-emerald-900/15 ml-auto sm:ml-0"
            >
                Profili gör
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
            </a>
        </div>
    </div>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
(function () {
    var card = document.getElementById('employee-of-the-month');
    if (!card || !window.confetti) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var colors = ['#34d399', '#6ee7b7', '#a7f3d0', '#38bdf8', '#7dd3fc', '#ffffff'];
    var fired = false;

    function burst() {
        confetti({
            particleCount: 90,
            spread: 70,
            origin: { y: 0.55, x: 0.5 },
            colors: colors,
            ticks: 200,
            gravity: 0.9,
            scalar: 1.05,
        });
        confetti({
            particleCount: 40,
            angle: 60,
            spread: 55,
            origin: { x: 0, y: 0.6 },
            colors: colors,
        });
        confetti({
            particleCount: 40,
            angle: 120,
            spread: 55,
            origin: { x: 1, y: 0.6 },
            colors: colors,
        });
    }

    function playCelebration() {
        if (fired) return;
        fired = true;
        burst();
        window.setTimeout(burst, 700);
        window.setTimeout(function () {
            confetti({
                particleCount: 30,
                spread: 100,
                origin: { y: 0.35 },
                colors: colors,
                shapes: ['star'],
                scalar: 0.9,
            });
        }, 1400);
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    playCelebration();
                    observer.disconnect();
                }
            });
        }, { threshold: 0.35 });
        observer.observe(card);
    } else {
        playCelebration();
    }
})();
</script>
@endpush
