<footer class="border-t border-neutral-200 dark:border-neutral-800 mt-auto">
    <div class="max-w-6xl mx-auto px-4 py-6 text-center text-xs text-neutral-500 dark:text-neutral-400">
        {{ \App\Support\CompanyBranding::siteName($company) }} · Malzeme kataloğu
        <span class="mx-2">·</span>
        <a href="{{ url('/takip') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Sipariş takibi</a>
    </div>
</footer>
