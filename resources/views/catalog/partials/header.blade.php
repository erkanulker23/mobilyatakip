<header class="border-b border-neutral-200 dark:border-neutral-800 bg-white/90 dark:bg-neutral-900/90 backdrop-blur sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 py-3.5 flex items-center justify-between gap-3">
        <a href="{{ route('catalog.index') }}" class="flex items-center gap-3 min-w-0">
            @if($company?->logoUrl && $company->logoDisplayUrl())
                <img src="{{ $company->logoDisplayUrl() }}" alt="{{ \App\Support\CompanyBranding::siteName($company) }}" class="h-9 w-auto max-w-[10rem] object-contain">
            @else
                <span class="font-semibold text-neutral-900 dark:text-neutral-100 truncate">{{ \App\Support\CompanyBranding::siteName($company) }}</span>
            @endif
        </a>
        <div class="flex items-center gap-3 shrink-0">
            <span class="hidden sm:inline text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ $subtitle ?? 'Malzeme Kataloğu' }}</span>
            <a href="{{ url('/takip') }}" class="text-xs font-medium text-neutral-500 hover:text-emerald-600 dark:hover:text-emerald-400">Takip</a>
            <a href="{{ route('login') }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Giriş</a>
        </div>
    </div>
</header>
