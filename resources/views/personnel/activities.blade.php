@extends('layouts.app')
@section('title', $personnel->name . ' — Log Hareketleri')

@section('content')
@php
    $hasFilters = request()->hasAny(['search', 'entity', 'action']);
@endphp

<div class="mb-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400 mb-1" aria-label="Breadcrumb">
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('personnel.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Personel</a>
                <span>/</span>
                @endif
                <a href="{{ route('personnel.show', $personnel) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ $personnel->name }}</a>
                <span>/</span>
                <span class="text-neutral-700 dark:text-neutral-300 font-medium">Log Hareketleri</span>
            </nav>
            <h1 class="page-title">Log Hareketleri</h1>
            <p class="page-desc mt-1">
                {{ $personnel->name }} kullanıcısının sistemde yaptığı işlemler
                @if($personnel->user?->lastLoginAt)
                    · Son giriş {{ $personnel->user->lastLoginAt->format('d.m.Y H:i') }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ route('personnel.show', $personnel) }}" class="btn-secondary">Personele Dön</a>
            @if(auth()->user()?->isAdmin())
            <a href="{{ route('personnel.edit', $personnel) }}" class="btn-edit">Düzenle</a>
            @endif
        </div>
    </div>
</div>

@if(! $personnel->userId)
<div class="card p-10 text-center">
    <p class="text-neutral-600 dark:text-neutral-300 font-medium">Sistem kullanıcısı değil</p>
    <p class="text-sm text-neutral-500 mt-1">Bu personelin sisteme giriş hesabı olmadığı için log kaydı bulunmaz.</p>
    @if(auth()->user()?->isAdmin())
    <a href="{{ route('personnel.edit', $personnel) }}" class="btn-primary mt-4 text-sm inline-flex">Erişim Ayarla</a>
    @endif
</div>
@else
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Toplam kayıt</p>
        <p class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100 mt-1">{{ number_format($logs->total(), 0, ',', '.') }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Kullanıcı</p>
        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mt-2 truncate">{{ $personnel->user->email ?? '—' }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Rol</p>
        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mt-2">{{ $personnel->user->role === 'admin' ? 'Yönetici' : 'Personel' }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Son giriş</p>
        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mt-2">{{ $personnel->user->lastLoginAt?->format('d.m.Y H:i') ?? '—' }}</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100 dark:border-neutral-800">
        <form method="GET" action="{{ route('personnel.activities', $personnel) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div class="sm:col-span-2">
                <label for="activitySearchInput" class="form-label">Ara</label>
                <input type="search" name="search" id="activitySearchInput" value="{{ request('search') }}" placeholder="Sipariş no, görev, müşteri adı..." class="form-input w-full" autocomplete="off">
            </div>
            <div>
                <label class="form-label">Kayıt tipi</label>
                <select name="entity" class="form-select w-full">
                    <option value="">Tümü</option>
                    @foreach($entityOptions as $value => $label)
                    <option value="{{ $value }}" {{ request('entity') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">İşlem</label>
                <select name="action" class="form-select w-full">
                    <option value="">Tümü</option>
                    @foreach($actionOptions as $value => $label)
                    <option value="{{ $value }}" {{ request('action') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:col-span-2 lg:col-span-4">
                <button type="submit" class="btn-primary w-full sm:w-auto justify-center">Filtrele</button>
                <a href="{{ route('personnel.activities', $personnel) }}" class="btn-secondary w-full sm:w-auto justify-center">Temizle</a>
            </div>
        </form>
    </div>

    <div class="px-4 sm:px-5 py-3 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-3 text-sm text-neutral-500">
        <span>
            @if($logs->total() === 0)
                Kayıt bulunamadı
            @elseif($logs->total() === 1)
                1 hareket
            @else
                {{ number_format($logs->total(), 0, ',', '.') }} hareket
                @if($logs->hasPages())
                    · sayfa {{ $logs->currentPage() }}/{{ $logs->lastPage() }}
                @endif
            @endif
        </span>
        @if($hasFilters)
            <span class="text-xs text-neutral-400">Filtre uygulanıyor</span>
        @endif
    </div>

    <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
        @forelse($logs as $log)
        @php
            $activity = \App\Support\ActivityMessage::from($log);
            $toneClasses = match($activity['tone']) {
                'success' => 'border-l-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20',
                'danger' => 'border-l-red-500 bg-red-50/40 dark:bg-red-950/20',
                'info' => 'border-l-blue-500 bg-blue-50/40 dark:bg-blue-950/20',
                'warning' => 'border-l-amber-500 bg-amber-50/40 dark:bg-amber-950/20',
                default => 'border-l-neutral-300 dark:border-l-neutral-600',
            };
            $dotClasses = match($activity['tone']) {
                'success' => 'bg-emerald-500',
                'danger' => 'bg-red-500',
                'info' => 'bg-blue-500',
                'warning' => 'bg-amber-500',
                default => 'bg-neutral-400',
            };
        @endphp
        <div class="px-4 sm:px-5 py-4 border-l-4 {{ $toneClasses }}">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0 flex-1">
                    <span class="shrink-0 mt-1.5 w-2 h-2 rounded-full {{ $dotClasses }}"></span>
                    <div class="min-w-0">
                        <p class="text-sm text-neutral-800 dark:text-neutral-200 leading-relaxed">
                            {{ $activity['text'] }}
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400">
                            <span>{{ $activity['time']->format('d.m.Y H:i') }}</span>
                            <span>{{ $activity['timeAgo'] }}</span>
                            @if($log->ipAddress)
                            <span>IP {{ $log->ipAddress }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @if($activity['url'])
                <a href="{{ $activity['url'] }}" class="btn-secondary text-sm shrink-0 self-start">Kayda Git</a>
                @endif
            </div>
        </div>
        @empty
        <div class="px-6 py-16 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-neutral-600 dark:text-neutral-300 font-medium">Henüz log kaydı yok</p>
            <p class="text-sm text-neutral-500 mt-1">
                {{ $hasFilters ? 'Filtreleri değiştirmeyi deneyin.' : 'Bu kullanıcı sisteme girdikten sonra yaptığı işlemler burada listelenir.' }}
            </p>
        </div>
        @endforelse
    </div>

    @if($logs->hasPages())
    <div class="px-4 sm:px-5 py-3 border-t border-neutral-100 dark:border-neutral-800">{{ $logs->links() }}</div>
    @endif
</div>
@endif
@endsection
