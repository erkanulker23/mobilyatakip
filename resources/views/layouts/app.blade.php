<!DOCTYPE html>
<html lang="tr">
<head>
    @php $company = \App\Models\Company::first(); @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', $company?->metaTitle ?? $company?->name ?? $company?->appName ?? 'Mobilya Takip')</title>
    @if($company?->metaDescription)<meta name="description" content="{{ $company->metaDescription }}">@endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3/dist/cdn.min.js"></script>
    <script defer src="{{ asset('js/form-inputs.js') }}"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'] },
                    colors: {
                        primary: { 50: '#ecfdf5', 100: '#d1fae5', 500: '#10b981', 600: '#059669', 700: '#047857' },
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .nav-link { transition: background .15s, color .15s; }
        .nav-link:hover { background: #f1f5f9; color: #0f172a; }
        .nav-link.active { background: rgba(16,185,129,.12); color: #059669; }
        .dark .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        .dark .nav-link.active { background: rgba(16,185,129,.2); color: #34d399; }
        .form-label { display: block; font-size: 0.8125rem; font-weight: 500; color: #64748b; margin-bottom: 0.375rem; letter-spacing: .01em; }
        .form-input, .form-select, .form-textarea { width: 100%; border-radius: 0.5rem; padding: 0.625rem 0.875rem; font-size: 0.9375rem; background: #f8fafc; transition: background .15s, box-shadow .15s; }
        .form-input:hover, .form-select:hover, .form-textarea:hover { background: #f1f5f9; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; background: #fff; box-shadow: 0 0 0 2px rgba(16,185,129,.2); }
        .form-textarea { min-height: 100px; resize: vertical; }
        .card { background: #fff; border-radius: 1rem; border: 1px solid #f1f5f9; }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; font-weight: 600; font-size: 0.9375rem; color: #0f172a; }
        .table-th { padding: 0.75rem 1.25rem; text-align: left; font-size: 0.6875rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
        .table-td { padding: 1rem 1.25rem; font-size: 0.9375rem; color: #475569; }
        .table-td strong, .table-td .font-medium { color: #0f172a; }
        .btn-primary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; background: #10b981; color: #fff; font-weight: 500; font-size: 0.9375rem; border-radius: 0.75rem; transition: background .15s; }
        .btn-primary:hover { background: #059669; }
        .btn-secondary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; background: #f1f5f9; color: #475569; font-weight: 500; font-size: 0.9375rem; border-radius: 0.75rem; transition: background .15s; }
        .btn-secondary:hover { background: #e2e8f0; }
        @media (max-width: 1023px) { .btn-primary, .btn-secondary { min-height: 44px; } }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #0f172a; letter-spacing: -.02em; }
        .page-desc { font-size: 0.9375rem; color: #64748b; margin-top: 0.25rem; }
        .dark .page-title { color: #f1f5f9; }
        .dark .page-desc { color: #94a3b8; }
        .dark .card { background: #1e293b; border-color: #334155; }
        .dark .card-header { border-color: #334155; color: #f1f5f9; }
        .dark .table-th { color: #64748b; }
        .dark .table-td { color: #cbd5e1; }
        .dark .table-td .font-medium, .dark .table-td strong { color: #f1f5f9; }
        .dark .form-label { color: #94a3b8; }
        .dark .form-input, .dark .form-select, .dark .form-textarea { background: #334155; color: #f1f5f9; }
        .dark .form-input:hover, .dark .form-select:hover, .dark .form-textarea:hover { background: #475569; }
        .dark .form-input:focus, .dark .form-select:focus, .dark .form-textarea:focus { background: #1e293b; box-shadow: 0 0 0 2px rgba(16,185,129,.3); }
        .dark .btn-primary { background: #059669; }
        .dark .btn-primary:hover { background: #047857; }
        .dark .btn-secondary { background: #334155; color: #e2e8f0; }
        .dark .btn-secondary:hover { background: #475569; }
        .amount-negative, .text-negative { color: #dc2626 !important; }
        .dark .amount-negative, .dark .text-negative { color: #f87171 !important; }
        [x-cloak] { display: none !important; }
        .safe-area-padding { padding-left: env(safe-area-inset-left, 0); padding-right: env(safe-area-inset-right, 0); padding-top: max(0.875rem, env(safe-area-inset-top)); }
        .safe-area-footer { padding-bottom: max(0.5rem, env(safe-area-inset-bottom)); }
        .main-offset { padding-top: calc(3.5rem + env(safe-area-inset-top, 0px)); }
        .touch-manipulation { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
        .form-items-section-box { border: 2px solid #cbd5e1; border-radius: 1rem; padding: 1.5rem; margin-top: 0.5rem; background: #f8fafc; }
        .dark .form-items-section-box { border-color: #475569; background: #1e293b; }
        .form-item-row { border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1rem; background: #fff; }
        .dark .form-item-row { border-color: #475569; background: #334155; }
        @media (max-width: 1023px) { main { padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right); } }
        @media print { .no-print { display: none !important; } aside { display: none !important; } }
    </style>
    @stack('head')
</head>
<body class="bg-slate-50/80 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen transition-colors" x-data="{ sidebarOpen: false, dark: false }" x-init="dark = localStorage.getItem('theme-dark') === '1'; document.documentElement.classList.toggle('dark', dark)">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[60] focus:px-4 focus:py-2 focus:bg-emerald-600 focus:text-white focus:rounded-xl">İçeriğe atla</a>
    <div class="flex min-h-screen">
        {{-- Üst bar: mobilde hamburger + ikonlar, masaüstünde sadece ikonlar (içerikle çakışmaz) --}}
        <header class="no-print fixed top-0 left-0 right-0 lg:left-60 h-14 z-50 flex items-center justify-between px-4 gap-3 bg-white/95 dark:bg-slate-900/95 border-b border-slate-200 dark:border-slate-800 backdrop-blur supports-[backdrop-filter]:bg-white/80 dark:supports-[backdrop-filter]:bg-slate-900/80 safe-area-padding">
            <div class="flex items-center gap-2 min-w-0">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden flex items-center justify-center w-11 h-11 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors touch-manipulation" aria-label="Menü">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <span class="lg:hidden text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $company?->appName ?? 'Mobilya Takip' }}</span>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                {{-- Bildirim --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex items-center justify-center w-11 h-11 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors touch-manipulation" aria-label="Bildirimler" :aria-expanded="open">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        @if(session('success') || session('error') || session('info'))
                        <span class="absolute top-2 right-2 flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 dark:bg-emerald-600"></span></span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-[min(320px,100vw-2rem)] rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 shadow-xl z-[60] overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/80">
                            <h3 class="font-semibold text-slate-900 dark:text-slate-100">Bildirimler</h3>
                        </div>
                        <div class="max-h-72 overflow-y-auto">
                            @if(session('success'))
                            <div class="px-4 py-3 flex items-start gap-3 border-b border-slate-100 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-900/20">
                                <span class="shrink-0 w-8 h-8 rounded-full bg-emerald-500 dark:bg-emerald-600 flex items-center justify-center text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                                <p class="text-sm text-emerald-800 dark:text-emerald-200">{{ session('success') }}</p>
                            </div>
                            @endif
                            @if(session('error'))
                            <div class="px-4 py-3 flex items-start gap-3 border-b border-slate-100 dark:border-slate-700 bg-red-50 dark:bg-red-900/20">
                                <span class="shrink-0 w-8 h-8 rounded-full bg-red-500 dark:bg-red-600 flex items-center justify-center text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></span>
                                <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                            </div>
                            @endif
                            @if(session('info'))
                            <div class="px-4 py-3 flex items-start gap-3 border-b border-slate-100 dark:border-slate-700 bg-blue-50 dark:bg-blue-900/20">
                                <span class="shrink-0 w-8 h-8 rounded-full bg-blue-500 dark:bg-blue-600 flex items-center justify-center text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
                                <p class="text-sm text-blue-800 dark:text-blue-200">{{ session('info') }}</p>
                            </div>
                            @endif
                            @if(!session('success') && !session('error') && !session('info'))
                            <div class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 text-sm">Yeni bildirim yok</div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- Tema (açık/koyu) --}}
                <button type="button" @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme-dark', dark ? '1' : '0')" class="flex items-center justify-center w-11 h-11 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors touch-manipulation" aria-label="Tema değiştir">
                    <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
            </div>
        </header>
        <div x-show="sidebarOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="sidebarOpen = false" class="fixed inset-0 bg-black/40 z-40 lg:hidden backdrop-blur-sm"></div>
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:static inset-y-0 left-0 w-60 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 flex flex-col shrink-0 z-40 transform transition-transform duration-200 ease-out border-r border-slate-200 dark:border-slate-800 pb-[env(safe-area-inset-bottom)] lg:pb-0">
            <div class="p-5 border-b border-slate-200 dark:border-white/5">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-slate-900 dark:text-white tracking-tight">{{ $company?->appName ?? 'Mobilya Takip' }}</a>
            </div>
            <nav class="flex-1 p-3 overflow-y-auto" aria-label="Ana menü">
                <a href="{{ route('dashboard') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">CRM</p>
                <a href="{{ route('customers.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('customers.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>Müşteriler</a>
                <a href="{{ route('suppliers.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>Tedarikçiler</a>
                <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Ürün & Stok</p>
                <a href="{{ route('products.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('products.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>Ürünler</a>
                <a href="{{ route('xml-feeds.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('xml-feeds.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>XML Ürün Çekme</a>
                <a href="{{ route('warehouses.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>Depolar</a>
                <a href="{{ route('stock.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('stock.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>Stok</a>
                <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Satış & Alış</p>
                <a href="{{ route('quotes.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('quotes.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Teklifler</a>
                <a href="{{ route('sales.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('sales.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>Satışlar</a>
                <a href="{{ route('purchases.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('purchases.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>Alışlar</a>
                <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Ödemeler & Kasa</p>
                <a href="{{ route('customer-payments.create') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('customer-payments.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>Ödeme Al</a>
                <a href="{{ route('supplier-payments.create') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('supplier-payments.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>Ödeme Yap</a>
                <a href="{{ route('kasa.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('kasa.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Kasa</a>
                <a href="{{ route('expenses.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('expenses.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>Giderler</a>
                <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Raporlar</p>
                <a href="{{ route('reports.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('reports.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Raporlar</a>
                <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Sistem</p>
                <a href="{{ route('service-tickets.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('service-tickets.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>Servis Talepleri</a>
                <a href="{{ route('personnel.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('personnel.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>Personel</a>
                @auth
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('settings.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('settings.*') ? 'active' : '' }}"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>Ayarlar</a>
                @endif
                @endauth
            </nav>
            <div class="p-3 border-t border-slate-200 dark:border-white/5">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-500 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Çıkış
                    </button>
                </form>
            </div>
        </aside>
        {{-- Main content (üst bar yüksekliği + safe area; mobilde alt menü için pb) --}}
        <main id="main-content" class="flex-1 overflow-auto pt-14 main-offset pb-24 lg:pb-0" role="main">
            <div class="p-4 sm:p-6 lg:p-10 max-w-[1600px] mx-auto relative">
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
        <nav class="no-print lg:hidden fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around gap-1 px-2 py-2 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 safe-area-footer touch-manipulation" aria-label="Alt menü">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center gap-0.5 min-w-[56px] py-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 {{ request()->routeIs('dashboard') ? 'text-emerald-600 dark:text-emerald-400' : '' }}">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"></path></svg>
                <span class="text-[10px] font-medium">Ana Sayfa</span>
            </a>
            <a href="{{ route('customers.index') }}" class="flex flex-col items-center justify-center gap-0.5 min-w-[56px] py-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 {{ request()->routeIs('customers.*') ? 'text-emerald-600 dark:text-emerald-400' : '' }}">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="text-[10px] font-medium">Müşteriler</span>
            </a>
            <a href="{{ route('quotes.create') }}" class="flex items-center justify-center w-14 h-14 -mt-5 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg shadow-emerald-500/30 transition-transform active:scale-95" aria-label="Yeni teklif">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            </a>
            <a href="{{ route('customer-payments.create') }}" class="flex flex-col items-center justify-center gap-0.5 min-w-[56px] py-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 {{ request()->routeIs('customer-payments.*') ? 'text-emerald-600 dark:text-emerald-400' : '' }}">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="text-[10px] font-medium">Ödeme Al</span>
            </a>
            <button type="button" @click="sidebarOpen = true" class="flex flex-col items-center justify-center gap-0.5 min-w-[56px] py-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 touch-manipulation" aria-label="Menüyü aç">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <span class="text-[10px] font-medium">Menü</span>
            </button>
        </nav>
    </div>
</body>
</html>
