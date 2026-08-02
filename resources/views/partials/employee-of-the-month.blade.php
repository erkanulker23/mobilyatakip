@php
    $person = $employeeOfTheMonth;
    $monthLabel = $employeeOfTheMonthLabel ?? now()->locale('tr')->isoFormat('MMMM YYYY');
    $initial = mb_strtoupper(mb_substr($person->name ?? '?', 0, 1));
@endphp

@push('head')
<style>
    .eotm-card {
        background:
            radial-gradient(ellipse 120% 80% at 50% -20%, rgba(251, 191, 36, 0.45), transparent 55%),
            linear-gradient(135deg, #1c1917 0%, #422006 38%, #78350f 68%, #92400e 100%);
    }
    .dark .eotm-card {
        background:
            radial-gradient(ellipse 120% 80% at 50% -20%, rgba(251, 191, 36, 0.25), transparent 55%),
            linear-gradient(135deg, #0c0a09 0%, #292524 40%, #451a03 100%);
    }
    .eotm-shimmer {
        background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.18) 50%, transparent 60%);
        background-size: 200% 100%;
        animation: eotm-shimmer 4s ease-in-out infinite;
    }
    @keyframes eotm-shimmer {
        0%, 100% { background-position: 200% 0; }
        50% { background-position: -200% 0; }
    }
    .eotm-photo-ring {
        box-shadow:
            0 0 0 4px rgba(251, 191, 36, 0.85),
            0 0 0 8px rgba(255, 255, 255, 0.12),
            0 20px 40px rgba(0, 0, 0, 0.35);
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
        background: #fde68a;
        box-shadow: 0 0 8px #fbbf24;
        animation: eotm-float 5s ease-in-out infinite;
        opacity: 0.7;
    }
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
    class="eotm-card relative overflow-hidden rounded-3xl border border-amber-500/30 shadow-2xl shadow-amber-950/20 mb-8"
    aria-label="Ayın elemanı"
>
    <div class="eotm-shimmer absolute inset-0 pointer-events-none" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="top:12%; left:8%; animation-delay:0s" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="top:22%; right:12%; animation-delay:1.2s; width:6px; height:6px" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="bottom:18%; left:18%; animation-delay:2.4s" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="bottom:28%; right:22%; animation-delay:0.8s" aria-hidden="true"></div>
    <div class="eotm-sparkle" style="top:45%; left:42%; animation-delay:1.8s; width:3px; height:3px" aria-hidden="true"></div>

    <div class="relative px-6 py-8 sm:px-10 sm:py-10">
        <div class="flex flex-col lg:flex-row lg:items-center gap-8 lg:gap-10">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 lg:flex-1">
                <div class="relative shrink-0">
                    <div class="eotm-pulse absolute -inset-3 rounded-full bg-amber-400/20 blur-md" aria-hidden="true"></div>
                    @if($person->photoUrl)
                        <img
                            src="{{ storage_url($person->photoUrl) }}"
                            alt="{{ $person->name }}"
                            class="relative h-32 w-32 sm:h-40 sm:w-40 rounded-full object-cover eotm-photo-ring"
                        >
                    @else
                        <div class="relative h-32 w-32 sm:h-40 sm:w-40 rounded-full eotm-photo-ring bg-gradient-to-br from-amber-200 to-amber-500 flex items-center justify-center text-4xl sm:text-5xl font-bold text-amber-950">
                            {{ $initial }}
                        </div>
                    @endif
                    <span class="absolute -bottom-1 -right-1 flex h-11 w-11 items-center justify-center rounded-full bg-amber-400 text-amber-950 shadow-lg ring-4 ring-amber-950/40" aria-hidden="true">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 7.1-1.01L12 2z"/></svg>
                    </span>
                </div>

                <div class="text-center sm:text-left min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full bg-amber-400/15 border border-amber-300/30 px-3 py-1 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-200">Ayın Elemanı</span>
                        <span class="h-1 w-1 rounded-full bg-amber-300" aria-hidden="true"></span>
                        <span class="text-[11px] font-medium text-amber-100/90 capitalize">{{ $monthLabel }}</span>
                    </div>
                    <h2 class="brand-logo text-3xl sm:text-4xl lg:text-[2.75rem] leading-tight text-white tracking-tight">
                        {{ $person->name }}
                    </h2>
                    @if($person->title)
                        <p class="mt-2 text-base sm:text-lg text-amber-100/85 font-medium">{{ $person->title }}</p>
                    @endif
                    <p class="mt-3 text-sm text-amber-100/70 max-w-md">
                        Bu ay en yüksek satış performansı ile öne çıkan ekip üyemiz. Tebrikler!
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row lg:flex-col xl:flex-row gap-3 lg:min-w-[16rem] shrink-0">
                <div class="flex-1 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/10 px-5 py-4 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-200/80">Satış Adedi</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-white">{{ (int) ($person->sales_count ?? 0) }}</p>
                </div>
                <div class="flex-1 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/10 px-5 py-4 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-200/80">Satış Cirosu</p>
                    <p class="mt-1 text-2xl sm:text-3xl font-bold tabular-nums text-white">₺{{ number_format((float) ($person->sales_total ?? 0), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-5 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-amber-100/60">Performans bu ay tamamlanan satışlara göre hesaplanır.</p>
            <a
                href="{{ route('personnel.show', $person->id) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-semibold text-amber-950 hover:bg-amber-300 transition-colors shadow-lg shadow-amber-950/20"
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

    var colors = ['#fbbf24', '#f59e0b', '#fde68a', '#ffffff', '#34d399', '#fcd34d'];
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
