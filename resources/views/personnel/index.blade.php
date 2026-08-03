@extends('layouts.app')
@section('title', 'Personel')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Personel</h1>
        <p class="page-desc">Personel listesi ve yönetimi</p>
    </div>
    <a href="{{ route('personnel.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
        Yeni Personel
    </a>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="min-w-[200px] flex-1">
                <label class="form-label">Ara (ad, e-posta, telefon, unvan)</label>
                <input type="text" name="search" placeholder="Ara..." value="{{ request('search') }}" class="form-input">
            </div>
            <div class="min-w-[140px]">
                <label class="form-label">Durum</label>
                <select name="isActive" class="form-select">
                    <option value="">Tümü</option>
                    <option value="1" {{ request('isActive') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('isActive') === '0' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Filtrele</button>
                <a href="{{ route('personnel.index') }}" class="btn-secondary">Temizle</a>
            </div>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-neutral-100">
                    <th class="table-th">Ad</th>
                    <th class="table-th">E-posta</th>
                    <th class="table-th">Telefon</th>
                    <th class="table-th">Unvan</th>
                    <th class="table-th">Son giriş</th>
                    <th class="table-th text-right w-40">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse($personnel as $p)
                <tr class="border-b border-neutral-50 hover:bg-neutral-50/50 transition-colors">
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            @if($p->photoUrl)
                                <img src="{{ storage_url($p->photoUrl) }}" alt="{{ $p->name }}" class="h-9 w-9 rounded-full object-cover border border-neutral-200 shrink-0">
                            @else
                                <div class="h-9 w-9 rounded-full bg-neutral-100 border border-neutral-200 flex items-center justify-center text-sm font-semibold text-neutral-400 shrink-0">
                                    {{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="font-medium text-neutral-900">{{ $p->name }}</span>
                            @if(!($p->isActive ?? true))<span class="text-xs text-neutral-400">(Pasif)</span>@endif
                        </div>
                    </td>
                    <td class="table-td text-neutral-500">{{ $p->email ?? '—' }}</td>
                    <td class="table-td text-neutral-500">{{ $p->phone ?? '—' }}</td>
                    <td class="table-td text-neutral-500">{{ $p->title ?? '—' }}</td>
                    <td class="table-td text-neutral-500 whitespace-nowrap">
                        @if($p->user?->lastLoginAt)
                            <span title="{{ $p->user->lastLoginAt->format('d.m.Y H:i') }}">{{ $p->user->lastLoginAt->locale('tr')->diffForHumans() }}</span>
                            <span class="block text-xs text-neutral-400">{{ $p->user->lastLoginAt->format('d.m.Y H:i') }}</span>
                        @elseif($p->userId)
                            <span class="text-neutral-400">Henüz giriş yok</span>
                        @else
                            <span class="text-neutral-400">Sistem hesabı yok</span>
                        @endif
                    </td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('personnel.show', $p),
                            'edit' => route('personnel.edit', $p),
                            'destroy' => route('personnel.destroy', $p),
                        ])
                    </td>
                </tr>
                @empty
                <x-data-table-empty :colspan="6" message="Kayıt bulunamadı." :action-url="route('personnel.create')" action-label="Yeni Personel" />
                @endforelse
            </tbody>
        </table>
    </div>
    @if($personnel->hasPages())
    <div class="px-6 py-3 border-t border-neutral-100">{{ $personnel->links() }}</div>
    @endif
</div>
@endsection
