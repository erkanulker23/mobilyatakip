<!DOCTYPE html>
<html lang="tr" class="font-sans">
<head>
    <script>
        (function () {
            try {
                if (localStorage.getItem('theme-dark') === '1') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    @php $company = \App\Models\Company::first(); @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turkey-cities-url" content="{{ route('api.turkey.cities') }}">
    <meta name="turkey-districts-url" content="{{ route('api.turkey.districts') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    @php
        $pageTitle = trim($__env->yieldContent('title'));
        $siteName = $company?->appName ?? $company?->name ?? 'Mobilya Takip';
        $documentTitle = $pageTitle !== '' ? $pageTitle . ' | ' . $siteName : ($company?->metaTitle ?? $siteName);
        $metaDescription = trim($__env->yieldContent('meta_description'));
        if ($metaDescription === '') {
            $metaDescription = $company?->metaDescription ?? '';
        }
        $canonicalUrl = trim($__env->yieldContent('canonical'));
        if ($canonicalUrl === '') {
            $canonicalUrl = url()->current();
        }
        $robotsContent = trim($__env->yieldContent('robots'));
        if ($robotsContent === '') {
            $robotsContent = 'noindex, nofollow';
        }
        $metaDescriptionPlain = $metaDescription !== '' ? \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', strip_tags($metaDescription)) ?? '', 160) : '';
    @endphp
    <title>{{ $documentTitle }}</title>
    @if($metaDescriptionPlain !== '')
    <meta name="description" content="{{ $metaDescriptionPlain }}">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="robots" content="{{ $robotsContent }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $documentTitle }}">
    @if($metaDescriptionPlain !== '')
    <meta property="og:description" content="{{ $metaDescriptionPlain }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($company?->logoDisplayUrl())
    <meta property="og:image" content="{{ $company->logoDisplayUrl() }}">
    @endif
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $documentTitle }}">
    @if($metaDescriptionPlain !== '')
    <meta name="twitter:description" content="{{ $metaDescriptionPlain }}">
    @endif
    @stack('structured_data')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3/dist/cdn.min.js"></script>
    <script defer src="{{ route('assets.js', ['file' => 'money.js']) }}?v={{ @filemtime(public_path('js/money.js')) ?: 1 }}"></script>
    <script defer src="{{ route('assets.js', ['file' => 'payment-kasa.js']) }}?v={{ @filemtime(public_path('js/payment-kasa.js')) ?: 1 }}"></script>
    <script defer src="{{ route('assets.js', ['file' => 'turkey-address.js']) }}?v={{ @filemtime(public_path('js/turkey-address.js')) ?: 1 }}"></script>
    <script defer src="{{ route('assets.js', ['file' => 'form-inputs.js']) }}?v={{ @filemtime(public_path('js/form-inputs.js')) ?: 1 }}"></script>
    <script defer src="{{ route('assets.js', ['file' => 'image-upload-compress.js']) }}?v={{ @filemtime(public_path('js/image-upload-compress.js')) ?: 1 }}"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                        mono: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                        display: ['Cormorant Garamond', 'Georgia', 'serif'],
                    },
                    colors: {
                        primary: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' },
                    }
                }
            }
        }
    </script>
    <style>
        html, body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        button, input, select, textarea, table, th, td, nav, label, a, p, span, div, dl, dt, dd {
            font-family: inherit;
        }
        .brand-logo, .print-brand-name { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; letter-spacing: 0.04em; }
        .brand-logo img { max-height: 2.25rem; width: auto; max-width: 11rem; object-fit: contain; object-position: center center; }
        .nav-link { transition: background .15s, color .15s, border-color .15s; border-left: 3px solid transparent; border-radius: 0; }
        .nav-link:hover { background: #f5f5f5; color: #171717; }
        .nav-link.active { background: #fafafa; color: #171717; border-left-color: #171717; font-weight: 600; }
        .dark .nav-link:hover { background: rgba(255,255,255,.05); color: #f5f5f5; }
        .dark .nav-link.active { background: rgba(255,255,255,.06); color: #fafafa; border-left-color: #fafafa; }
        .form-label { display: block; font-size: 0.8125rem; font-weight: 500; color: #737373; margin-bottom: 0.375rem; letter-spacing: .01em; }
        .form-input, .form-select, .form-textarea { width: 100%; border-radius: 0.625rem; padding: 0.625rem 0.875rem; font-size: 0.9375rem; background: #fafafa; border: 1px solid #e5e5e5; transition: background .15s, box-shadow .15s, border-color .15s; }
        .form-input:hover, .form-select:hover, .form-textarea:hover { background: #f5f5f5; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; background: #fff; border-color: #d4d4d4; box-shadow: 0 0 0 3px rgba(0,0,0,.04); }
        .form-textarea { min-height: 100px; resize: vertical; }
        .card { background: #fff; border-radius: 1rem; border: 1px solid #f0f0f0; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f0f0f0; font-weight: 600; font-size: 0.9375rem; color: #171717; }
        .table-th { padding: 0.875rem 1.25rem; text-align: left; font-size: 0.6875rem; font-weight: 600; color: #a3a3a3; text-transform: uppercase; letter-spacing: .08em; }
        .table-td { padding: 1rem 1.25rem; font-size: 0.9375rem; color: #525252; }
        .table-td strong, .table-td .font-medium.text-neutral-900 { color: #171717; }
        .btn-primary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.125rem; background: #171717; color: #fff; font-weight: 500; font-size: 0.9375rem; border-radius: 0.625rem; transition: background .15s; }
        .btn-primary:hover { background: #262626; }
        .btn-secondary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.125rem; background: #fff; color: #525252; font-weight: 500; font-size: 0.9375rem; border-radius: 0.625rem; border: 1px solid #e5e5e5; transition: background .15s, border-color .15s; }
        .btn-secondary:hover { background: #fafafa; border-color: #d4d4d4; }
        .btn-view { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.125rem; background: #171717; color: #fff; font-weight: 500; font-size: 0.9375rem; border-radius: 0.625rem; transition: background .15s; }
        .btn-view:hover { background: #262626; }
        .btn-edit { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.125rem; background: #525252; color: #fff; font-weight: 500; font-size: 0.9375rem; border-radius: 0.625rem; transition: background .15s; }
        .btn-edit:hover { background: #404040; }
        .btn-print { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.125rem; background: #525252; color: #fff; font-weight: 500; font-size: 0.9375rem; border-radius: 0.625rem; transition: background .15s; }
        .btn-print:hover { background: #404040; }
        .btn-delete { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.125rem; background: #dc2626; color: #fff; font-weight: 500; font-size: 0.9375rem; border-radius: 0.625rem; transition: background .15s; }
        .btn-delete:hover { background: #b91c1c; }
        @media (max-width: 1023px) { .btn-primary, .btn-secondary { min-height: 44px; } }
        .page-title { font-size: 1.75rem; font-weight: 600; color: #171717; letter-spacing: -.025em; }
        .page-desc { font-size: 0.9375rem; color: #737373; margin-top: 0.25rem; }
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .badge-dark { background: #171717; color: #fff; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-amber { background: #fef3c7; color: #b45309; }
        .badge-red { background: #fee2e2; color: #b91c1c; }
        .dark .badge-blue { background: rgba(29, 78, 216, 0.25); color: #93c5fd; }
        .dark .badge-green { background: rgba(21, 128, 61, 0.3); color: #86efac; }
        .dark .badge-amber { background: rgba(180, 83, 9, 0.3); color: #fcd34d; }
        .dark .badge-red { background: rgba(185, 28, 28, 0.3); color: #fca5a5; }
        .dark .badge-dark { background: #404040; color: #f5f5f5; }
        .dark .bg-emerald-50 { background-color: rgba(6, 78, 59, 0.35); }
        .dark .bg-emerald-100 { background-color: rgba(6, 78, 59, 0.4); }
        .dark .text-emerald-700, .dark .text-emerald-800 { color: #6ee7b7; }
        .dark .bg-sky-50 { background-color: rgba(12, 74, 110, 0.35); }
        .dark .text-sky-700 { color: #7dd3fc; }
        .dark .bg-amber-50, .dark .bg-amber-100 { background-color: rgba(146, 64, 14, 0.35); }
        .dark .text-amber-600, .dark .text-amber-700, .dark .text-amber-800 { color: #fcd34d; }
        .dark .bg-green-100 { background-color: rgba(21, 128, 61, 0.35); }
        .dark .text-green-800 { color: #86efac; }
        .dark .bg-red-50 { background-color: rgba(127, 29, 29, 0.35); }
        .dark .text-red-600, .dark .text-red-700 { color: #fca5a5; }
        .dark .bg-indigo-100 { background-color: rgba(67, 56, 202, 0.35); }
        .dark .text-indigo-700, .dark .text-indigo-800 { color: #a5b4fc; }
        .dark .text-emerald-600 { color: #34d399; }
        .global-search { width: 100%; max-width: 32rem; border-radius: 9999px; background: #f5f5f5; border: 1px solid #e5e5e5; padding: 0.625rem 1rem 0.625rem 2.75rem; font-size: 0.875rem; color: #171717; transition: background .15s, border-color .15s, box-shadow .15s; }
        .global-search:focus { outline: none; background: #fff; border-color: #d4d4d4; box-shadow: 0 0 0 3px rgba(0,0,0,.04); }
        .global-search::placeholder { color: #a3a3a3; }
        .dark .global-search { background: #262626; border-color: #404040; color: #f5f5f5; }
        .dark .global-search:focus { background: #171717; border-color: #525252; box-shadow: 0 0 0 2px rgba(255,255,255,.06); }
        .dark .global-search::placeholder { color: #737373; }
        .dark .page-title { color: #f5f5f5; }
        .dark .page-desc { color: #a3a3a3; }
        .dark .card { background: #171717; border-color: #262626; }
        .dark .card-header { border-color: #262626; color: #f5f5f5; }
        .dark .table-th { color: #737373; }
        .dark .table-td { color: #d4d4d4; }
        .dark .table-td .font-medium.text-neutral-900, .dark .table-td strong { color: #f5f5f5; }
        .dark .form-label { color: #a3a3a3; }
        .dark .form-input, .dark .form-select, .dark .form-textarea { background: #262626; border-color: #404040; color: #f5f5f5; }
        .dark .form-input:hover, .dark .form-select:hover, .dark .form-textarea:hover { background: #404040; }
        .dark .form-input:focus, .dark .form-select:focus, .dark .form-textarea:focus { background: #171717; border-color: #525252; box-shadow: 0 0 0 2px rgba(255,255,255,.06); }
        .dark .btn-primary { background: #059669; }
        .dark .btn-primary:hover { background: #047857; }
        .dark .btn-secondary { background: #262626; color: #e5e5e5; border-color: #404040; }
        .dark .btn-secondary:hover { background: #404040; border-color: #525252; }
        .dark .btn-view { background: #059669; }
        .dark .btn-view:hover { background: #047857; }
        .dark .btn-edit { background: #0369a1; }
        .dark .btn-edit:hover { background: #075985; }
        .dark .btn-print { background: #6d28d9; }
        .dark .btn-print:hover { background: #5b21b6; }
        .dark .btn-delete { background: #b91c1c; }
        .dark .btn-delete:hover { background: #991b1b; }
        .action-btn-view { color: #525252 !important; }
        .action-btn-view:hover { color: #171717 !important; background: #f5f5f5 !important; }
        .dark .action-btn-view { color: #a3a3a3 !important; }
        .dark .action-btn-view:hover { background: rgba(255,255,255,.08) !important; color: #f5f5f5 !important; }
        .action-btn-edit { color: #737373 !important; }
        .action-btn-edit:hover { color: #171717 !important; background: #f5f5f5 !important; }
        .dark .action-btn-edit { color: #a3a3a3 !important; }
        .dark .action-btn-edit:hover { background: rgba(255,255,255,.08) !important; color: #f5f5f5 !important; }
        .action-btn-print { color: #737373 !important; }
        .action-btn-print:hover { color: #171717 !important; background: #f5f5f5 !important; }
        .dark .action-btn-print { color: #a3a3a3 !important; }
        .dark .action-btn-print:hover { background: rgba(255,255,255,.08) !important; color: #f5f5f5 !important; }
        .action-btn-delete { color: #dc2626 !important; }
        .action-btn-delete:hover { color: #b91c1c !important; background: #fef2f2 !important; }
        .dark .action-btn-delete { color: #f87171 !important; }
        .dark .action-btn-delete:hover { background: rgba(220,38,38,.15) !important; }
        .amount-negative, .text-negative { color: #dc2626 !important; }
        .dark .amount-negative, .dark .text-negative { color: #f87171 !important; }
        [x-cloak] { display: none !important; }
        .safe-area-padding { padding-left: env(safe-area-inset-left, 0); padding-right: env(safe-area-inset-right, 0); padding-top: max(0.875rem, env(safe-area-inset-top)); }
        .safe-area-footer { padding-bottom: max(0.5rem, env(safe-area-inset-bottom)); }
        .main-offset { padding-top: calc(4rem + env(safe-area-inset-top, 0px)); }
        .touch-manipulation { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
        .form-items-section-box { border: 2px solid #cbd5e1; border-radius: 0.75rem; padding: 1rem; margin-top: 0.5rem; background: #f8fafc; }
        .dark .form-items-section-box { border-color: #404040; background: #171717; }
        .form-item-row { border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1rem; background: #fff; }
        .dark .form-item-row { border-color: #404040; background: #262626; }
        /* Sayfalarda eksik dark: sınıfları için yedek stiller — menü ile aynı nötr palet */
        .dark { color-scheme: dark; }
        .dark .text-slate-900, .dark .text-neutral-900 { color: #f5f5f5; }
        .dark .text-slate-800 { color: #e5e5e5; }
        .dark .text-neutral-700 { color: #d4d4d4; }
        .dark .text-slate-600, .dark .text-neutral-500 { color: #a3a3a3; }
        .dark .text-slate-300, .dark .text-slate-400, .dark .dark\:text-slate-300, .dark .dark\:text-slate-400 { color: #a3a3a3; }
        .dark .bg-white, .dark .dark\:bg-white { background-color: #171717; }
        .dark .bg-slate-50, .dark .dark\:bg-slate-50 { background-color: #171717; }
        .dark .bg-slate-100, .dark .dark\:bg-slate-100 { background-color: #262626; }
        .dark .bg-slate-200, .dark .dark\:bg-slate-200 { background-color: #404040; color: #e5e5e5; }
        .dark .dark\:bg-slate-700 { background-color: #262626; }
        .dark .dark\:bg-slate-800, .dark .dark\:bg-slate-900 { background-color: #171717; }
        .dark .border-neutral-100, .dark .border-neutral-50, .dark .border-slate-50, .dark .border-slate-100 { border-color: #262626; }
        .dark .border-neutral-200, .dark .dark\:border-slate-600, .dark .dark\:border-slate-700, .dark .dark\:border-slate-800 { border-color: #404040; }
        .dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]), .dark .divide-neutral-100 > :not([hidden]) ~ :not([hidden]) { border-color: #262626; }
        .dark .hover\:bg-slate-50:hover, .dark .hover\:bg-neutral-50:hover { background-color: rgba(255,255,255,.05); }
        .dark .hover\:bg-slate-50\/50:hover, .dark .hover\:bg-neutral-50\/50:hover { background-color: rgba(255,255,255,.04); }
        .dark .hover\:bg-slate-300:hover, .dark .dark\:hover\:bg-slate-700:hover, .dark .dark\:hover\:bg-slate-800:hover { background-color: #262626; }
        .dark .dark\:hover\:bg-slate-700\/50:hover { background-color: rgba(255,255,255,.06); }
        /* Tom Select (satış, alış, teklif formları) */
        .dark .ts-wrapper .ts-control { background: #262626; border-color: #404040; color: #f5f5f5; }
        .dark .ts-wrapper .ts-control input { color: #f5f5f5; }
        .dark .ts-wrapper .ts-control input::placeholder { color: #737373; }
        .dark .ts-dropdown { background: #171717; border-color: #404040; color: #f5f5f5; }
        .dark .ts-dropdown .option { color: #d4d4d4; }
        .dark .ts-dropdown .option.active, .dark .ts-dropdown .option:hover { background: #262626; color: #f5f5f5; }
        .dark .ts-wrapper.multi .ts-control > div { background: #404040; color: #f5f5f5; border-color: #525252; }
        .ts-dropdown.sale-product-dropdown { min-width: min(440px, calc(100vw - 2rem)); max-width: 560px; }
        .ts-dropdown.sale-product-dropdown .option { padding: 8px 12px; border-bottom: 1px solid rgba(0,0,0,.04); }
        .ts-dropdown.sale-product-dropdown .option:last-child { border-bottom: 0; }
        .dark .ts-dropdown.sale-product-dropdown .option { border-bottom-color: rgba(255,255,255,.06); }
        @media (max-width: 1023px) { main { padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right); } }
        @media print { .no-print { display: none !important; } aside { display: none !important; } }
    </style>
    @stack('head')
</head>
<body class="font-sans antialiased bg-neutral-50 dark:bg-neutral-950 text-neutral-800 dark:text-neutral-200 min-h-screen transition-colors" x-data="{ sidebarOpen: false, dark: false }" x-init="dark = document.documentElement.classList.contains('dark')" @keydown.window.meta.k.prevent="$refs.globalSearch?.focus()" @keydown.window.ctrl.k.prevent="$refs.globalSearch?.focus()">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[60] focus:px-4 focus:py-2 focus:bg-neutral-900 focus:text-white focus:rounded-xl">İçeriğe atla</a>
    <div class="flex min-h-screen">
        {{-- Üst bar --}}
        <header class="no-print fixed top-0 left-0 right-0 lg:left-64 h-16 z-50 flex items-center px-4 lg:px-8 gap-4 bg-white/95 dark:bg-neutral-900/95 border-b border-neutral-200 dark:border-neutral-800 backdrop-blur supports-[backdrop-filter]:bg-white/80 dark:supports-[backdrop-filter]:bg-neutral-900/80 safe-area-padding">
            <div class="flex items-center gap-2 min-w-0 lg:hidden shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="flex items-center justify-center w-11 h-11 rounded-xl text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors touch-manipulation" aria-label="Menü">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                @if($company?->logoUrl)
                <a href="{{ route('dashboard') }}" class="min-w-0 shrink">
                    <img src="{{ $company->logoDisplayUrl() }}" alt="{{ $company->appName ?? $company->name ?? 'Logo' }}" class="h-8 w-auto max-w-[7.5rem] object-contain">
                </a>
                @endif
            </div>
            {{-- Global arama --}}
            <div class="flex-1 flex justify-center min-w-0">
                <form action="{{ route('customers.index') }}" method="GET" class="relative w-full max-w-lg hidden sm:block">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="search" name="search" x-ref="globalSearch" placeholder="Sipariş, müşteri veya ürün ara..." class="global-search" autocomplete="off">
                    <kbd class="hidden md:inline-flex absolute right-3 top-1/2 -translate-y-1/2 items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-medium text-neutral-400 bg-white border border-neutral-200 rounded">⌘K</kbd>
                </form>
            </div>
            <div class="flex items-center gap-1 shrink-0 ml-auto">
                {{-- Bildirim --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex items-center justify-center w-11 h-11 rounded-xl text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors touch-manipulation" aria-label="Bildirimler" :aria-expanded="open">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        @if(session('success') || session('error') || session('info') || ($recentActivities ?? collect())->isNotEmpty())
                        <span class="absolute top-2 right-2 flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 dark:bg-emerald-600"></span></span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-[min(320px,100vw-2rem)] rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 shadow-xl z-[60] overflow-hidden">
                        <div class="px-4 py-3 border-b border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900/80">
                            <h3 class="font-semibold text-neutral-900">Bildirimler</h3>
                        </div>
                        <div class="max-h-72 overflow-y-auto">
                            @if(session('success'))
                            <div class="px-4 py-3 flex items-start gap-3 border-b border-neutral-100 dark:border-neutral-800 bg-emerald-50 dark:bg-emerald-900/20">
                                <span class="shrink-0 w-8 h-8 rounded-full bg-emerald-500 dark:bg-emerald-600 flex items-center justify-center text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                                <p class="text-sm text-emerald-800 dark:text-emerald-200">{{ session('success') }}</p>
                            </div>
                            @endif
                            @if(session('error'))
                            <div class="px-4 py-3 flex items-start gap-3 border-b border-neutral-100 dark:border-neutral-800 bg-red-50 dark:bg-red-900/20">
                                <span class="shrink-0 w-8 h-8 rounded-full bg-red-500 dark:bg-red-600 flex items-center justify-center text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></span>
                                <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                            </div>
                            @endif
                            @if(session('info'))
                            <div class="px-4 py-3 flex items-start gap-3 border-b border-neutral-100 dark:border-neutral-800 bg-blue-50 dark:bg-blue-900/20">
                                <span class="shrink-0 w-8 h-8 rounded-full bg-blue-500 dark:bg-blue-600 flex items-center justify-center text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
                                <p class="text-sm text-blue-800 dark:text-blue-200">{{ session('info') }}</p>
                            </div>
                            @endif
                            @forelse($recentActivities ?? [] as $activity)
                            @php
                                $toneClasses = match($activity['tone']) {
                                    'success' => 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-100 dark:border-emerald-900/40',
                                    'danger' => 'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-900/40',
                                    'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-900/40',
                                    'warning' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-100 dark:border-amber-900/40',
                                    default => 'bg-white dark:bg-neutral-900 border-neutral-100 dark:border-neutral-800',
                                };
                                $dotClasses = match($activity['tone']) {
                                    'success' => 'bg-emerald-500',
                                    'danger' => 'bg-red-500',
                                    'info' => 'bg-blue-500',
                                    'warning' => 'bg-amber-500',
                                    default => 'bg-neutral-400',
                                };
                            @endphp
                            @if($activity['url'])
                            <a href="{{ $activity['url'] }}" class="block px-4 py-3 border-b {{ $toneClasses }} hover:bg-neutral-50 dark:hover:bg-neutral-800/80 transition-colors">
                            @else
                            <div class="px-4 py-3 border-b {{ $toneClasses }}">
                            @endif
                                <div class="flex items-start gap-3">
                                    <span class="shrink-0 mt-1.5 w-2 h-2 rounded-full {{ $dotClasses }}"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-neutral-800 dark:text-neutral-200 leading-snug">
                                            <span class="font-semibold text-neutral-900 dark:text-white">{{ $activity['user'] }}</span>
                                            <span> {{ $activity['text'] ?? $activity['message'] }}</span>
                                        </p>
                                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">{{ $activity['timeAgo'] }}</p>
                                    </div>
                                </div>
                            @if($activity['url'])
                            </a>
                            @else
                            </div>
                            @endif
                            @empty
                            @if(!session('success') && !session('error') && !session('info'))
                            <div class="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400 text-sm">Yeni bildirim yok</div>
                            @endif
                            @endforelse
                        </div>
                    </div>
                </div>
                {{-- Tema (açık/koyu) --}}
                <button type="button" @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme-dark', dark ? '1' : '0')" class="flex items-center justify-center w-11 h-11 rounded-xl text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors touch-manipulation" aria-label="Tema değiştir">
                    <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
            </div>
        </header>
        <div x-show="sidebarOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="sidebarOpen = false" class="fixed inset-0 bg-black/40 z-40 lg:hidden backdrop-blur-sm"></div>
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:static inset-y-0 left-0 w-64 bg-white dark:bg-neutral-900 text-neutral-600 dark:text-neutral-400 flex flex-col shrink-0 z-40 transform transition-transform duration-200 ease-out border-r border-neutral-200 dark:border-neutral-800 pb-[env(safe-area-inset-bottom)] lg:pb-0">
            <div class="h-16 flex items-center justify-center px-4 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
                <a href="{{ route('dashboard') }}" class="brand-logo flex items-center justify-center w-full min-w-0" title="{{ $company?->appName ?? $company?->name ?? 'Mobilya Takip' }}">
                    @if($company?->logoUrl)
                        <img src="{{ $company->logoDisplayUrl() }}" alt="{{ $company->appName ?? $company->name ?? 'Logo' }}">
                    @else
                        <span class="text-xl text-neutral-900 dark:text-white uppercase truncate text-center">{{ $company?->appName ?? $company?->name ?? 'Mobilya Takip' }}</span>
                    @endif
                </a>
            </div>
            <nav class="flex-1 px-3 py-4 overflow-y-auto" aria-label="Ana menü">
                <a href="{{ route('dashboard') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                <p class="px-3 pt-5 pb-1 text-[10px] font-medium text-neutral-400 uppercase tracking-widest">Öncelik</p>
                <a href="{{ route('customers.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('customers.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>Müşteriler</a>
                <a href="{{ route('sales.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('sales.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>Satışlar</a>
                <a href="{{ route('customer-payments.create') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('customer-payments.create') && !request('list') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>Ödeme Al</a>
                <a href="{{ route('service-tickets.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('service-tickets.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>SSH'lar</a>
                <a href="{{ route('quotes.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('quotes.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Teklifler</a>
                <p class="px-3 pt-5 pb-1 text-[10px] font-medium text-neutral-400 uppercase tracking-widest">CRM</p>
                <a href="{{ route('suppliers.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>Tedarikçiler</a>
                <a href="{{ route('shipping-companies.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('shipping-companies.*') || request()->routeIs('shipping-company-payments.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>Nakliye Firmaları</a>
                <p class="px-3 pt-5 pb-1 text-[10px] font-medium text-neutral-400 uppercase tracking-widest">Ürün & Stok</p>
                <a href="{{ route('products.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('products.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>Ürünler</a>
                <a href="{{ route('xml-feeds.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('xml-feeds.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>XML Ürün Çekme</a>
                <a href="{{ route('warehouses.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>Depolar</a>
                <a href="{{ route('stock.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('stock.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>Stok</a>
                <p class="px-3 pt-5 pb-1 text-[10px] font-medium text-neutral-400 uppercase tracking-widest">Satış & Alış</p>
                <a href="{{ route('purchases.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('purchases.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>Alışlar</a>
                <p class="px-3 pt-5 pb-1 text-[10px] font-medium text-neutral-400 uppercase tracking-widest">Ödemeler & Kasa</p>
                <a href="{{ route('customer-payments.create') }}?list=1" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ (request()->routeIs('customer-payments.create') && request('list')) || request()->routeIs('customer-payments.show') || request()->routeIs('customer-payments.edit') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>Tahsilat Kayıtları</a>
                <a href="{{ route('supplier-payments.create') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('supplier-payments.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>Tedarikçi Ödeme Yap</a>
                <a href="{{ route('shipping-company-payments.create') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('shipping-company-payments.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>Nakliye Ödemesi Yap</a>
                <a href="{{ route('kasa.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('kasa.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75m15.75 0h.75.75v-.75c0-.414-.336-.75-.75-.75h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"></path></svg>Kasa</a>
                <a href="{{ route('expenses.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('expenses.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>Giderler</a>
                <p class="px-3 pt-5 pb-1 text-[10px] font-medium text-neutral-400 uppercase tracking-widest">Raporlar</p>
                <a href="{{ route('reports.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('reports.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Raporlar</a>
                <p class="px-3 pt-5 pb-1 text-[10px] font-medium text-neutral-400 uppercase tracking-widest">Sistem</p>
                @php $linkedPersonnel = auth()->user()?->personnel; @endphp
                @if($linkedPersonnel && !auth()->user()?->isAdmin())
                <a href="{{ route('personnel.show', $linkedPersonnel) }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('personnel.show') && request()->route('personnel')?->id === $linkedPersonnel->id ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>Siparişlerim</a>
                @else
                <a href="{{ route('personnel.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('personnel.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>Personel</a>
                @endif
                @auth
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('settings.index') }}" class="nav-link flex items-center gap-3 px-3 py-2 text-sm {{ request()->routeIs('settings.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>Ayarlar</a>
                @endif
                @endauth
            </nav>
            @auth
            @php
                $user = auth()->user();
            @endphp
            <div class="p-4 border-t border-neutral-200 dark:border-neutral-800">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors {{ request()->routeIs('profile.*') ? 'bg-neutral-100 dark:bg-neutral-800' : '' }}">
                    @if($user->photoDisplayUrl())
                        <img src="{{ $user->photoDisplayUrl() }}" alt="" class="w-9 h-9 rounded-full object-cover shrink-0 border border-neutral-200 dark:border-neutral-600">
                    @else
                        <div class="w-9 h-9 rounded-full bg-neutral-900 dark:bg-neutral-700 text-white flex items-center justify-center text-xs font-semibold shrink-0">{{ $user->initials() }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">{{ $user->name ?? 'Kullanıcı' }}</p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">{{ $user->email }}</p>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="nav-link w-full flex items-center gap-3 px-3 py-2 text-sm text-neutral-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Çıkış
                    </button>
                </form>
            </div>
            @endauth
        </aside>
        {{-- Main content (üst bar yüksekliği + safe area; mobilde alt menü için pb) --}}
        <main id="main-content" class="flex-1 overflow-auto pt-16 main-offset pb-24 lg:pb-0" role="main">
            <div class="p-4 sm:p-6 lg:p-8 max-w-[1600px] mx-auto relative">
                {{-- Toast bildirimler (üst barın hemen altında) --}}
                @if(session('success'))
                    <div class="no-print fixed top-16 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-sm z-[100] py-3 px-4 rounded-xl bg-emerald-500 dark:bg-emerald-600 text-white text-sm font-medium shadow-lg flex items-center justify-between gap-3 border border-emerald-600/20" role="alert" aria-live="polite" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <span>{{ session('success') }}</span>
                        <button type="button" @click="show = false" class="shrink-0 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg hover:bg-white/20 touch-manipulation" aria-label="Kapat">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="no-print fixed top-16 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-sm z-[100] py-3 px-4 rounded-xl bg-red-500 dark:bg-red-600 text-white text-sm font-medium shadow-lg flex items-center justify-between gap-3 border border-red-600/20" role="alert" aria-live="polite" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <span>{{ session('error') }}</span>
                        <button type="button" @click="show = false" class="shrink-0 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg hover:bg-white/20 touch-manipulation" aria-label="Kapat">&times;</button>
                    </div>
                @endif
                @if(session('info'))
                    <div class="no-print fixed top-16 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-sm z-[100] py-3 px-4 rounded-xl bg-blue-500 dark:bg-blue-600 text-white text-sm font-medium shadow-lg flex items-center justify-between gap-3 border border-blue-600/20" role="alert" aria-live="polite" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <span>{{ session('info') }}</span>
                        <button type="button" @click="show = false" class="shrink-0 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg hover:bg-white/20 touch-manipulation" aria-label="Kapat">&times;</button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        {{-- Sabit alt menü (mobil / tablet): Teklif Ekle, Menü vb. --}}
        <nav class="no-print lg:hidden fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around gap-1 px-2 py-2 bg-white dark:bg-neutral-900 border-t border-neutral-200 dark:border-neutral-800 safe-area-footer touch-manipulation" aria-label="Alt menü">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center gap-0.5 min-w-[56px] py-2 rounded-xl text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ request()->routeIs('dashboard') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"></path></svg>
                <span class="text-[10px] font-medium">Ana Sayfa</span>
            </a>
            <a href="{{ route('customers.index') }}" class="flex flex-col items-center justify-center gap-0.5 min-w-[56px] py-2 rounded-xl text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ request()->routeIs('customers.*') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="text-[10px] font-medium">Müşteriler</span>
            </a>
            <a href="{{ route('quotes.index') }}" class="flex items-center justify-center w-14 h-14 -mt-5 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white shadow-lg shadow-neutral-900/20 transition-transform active:scale-95 {{ request()->routeIs('quotes.*') ? 'ring-2 ring-blue-500 ring-offset-2' : '' }}" aria-label="Teklifler">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </a>
            <a href="{{ route('customer-payments.create') }}" class="flex flex-col items-center justify-center gap-0.5 min-w-[56px] py-2 rounded-xl text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ request()->routeIs('customer-payments.create') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="text-[10px] font-medium">Ödeme Al</span>
            </a>
            <button type="button" @click="sidebarOpen = true" class="flex flex-col items-center justify-center gap-0.5 min-w-[56px] py-2 rounded-xl text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 touch-manipulation" aria-label="Menüyü aç">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <span class="text-[10px] font-medium">Menü</span>
            </button>
        </nav>
    </div>
    @stack('scripts')
</body>
</html>
