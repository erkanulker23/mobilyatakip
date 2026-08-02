@extends('layouts.app')
@section('title', 'Nakliye Firmaları')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Nakliye Firmaları</h1>
        <p class="page-desc">Nakliye firması listesi ve ödeme takibi</p>
    </div>
    <a href="{{ route('shipping-companies.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
        Yeni Nakliye Firması
    </a>
</div>

<div class="card overflow-hidden">
    <div class="p-4 border-b border-neutral-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[200px] flex-1">
            <label class="form-label">Ara (ad, e-posta, telefon)</label>
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
            <a href="{{ route('shipping-companies.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="table-th">Firma Adı</th>
                    <th class="table-th">Telefon</th>
                    <th class="table-th">E-posta</th>
                    <th class="table-th text-right">Toplam Ödenen</th>
                    <th class="table-th text-center w-40">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shippingCompanies as $sc)
                <tr class="hover:bg-neutral-50/50 transition-colors border-b border-neutral-50" x-data="{ deleteOpen: false }">
                    <td class="table-td">
                        <span class="font-medium text-neutral-900">{{ $sc->name }}</span>
                        @if(!($sc->isActive ?? true))<span class="ml-1 text-xs text-slate-400">(Pasif)</span>@endif
                    </td>
                    <td class="table-td text-neutral-500">{{ $sc->phone ?? '-' }}</td>
                    <td class="table-td text-neutral-500">{{ $sc->email ?? '-' }}</td>
                    <td class="table-td text-right font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($odemeByShipping[$sc->id] ?? 0, 0, ',', '.') }} ₺</td>
                    <td class="table-td">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('shipping-companies.show', $sc) }}" aria-label="Görüntüle" title="Görüntüle" class="action-btn-view p-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </a>
                            <a href="{{ route('shipping-companies.edit', $sc) }}" aria-label="Düzenle" title="Düzenle" class="action-btn-edit p-2 rounded-xl hover:bg-sky-50 dark:hover:bg-sky-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <button type="button" @click="deleteOpen = true" aria-label="Sil" title="Sil" class="action-btn-delete p-2 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                            <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                                <div x-show="deleteOpen" x-transition class="fixed inset-0 bg-black/50" @click="deleteOpen = false"></div>
                                <div x-show="deleteOpen" x-transition class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6 border border-neutral-200 dark:border-slate-700">
                                    <h2 class="text-base font-semibold text-neutral-900">Nakliye firmasını sil</h2>
                                    <p class="mt-2 text-sm text-neutral-500 dark:text-slate-400">Bu nakliye firması ve ödemeleri silinecek. Devam etmek istiyor musunuz?</p>
                                    <div class="mt-6 flex gap-3 justify-end">
                                        <button type="button" @click="deleteOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-600 text-neutral-700 dark:text-slate-200 font-medium hover:bg-slate-300 dark:hover:bg-slate-500">İptal</button>
                                        <form method="POST" action="{{ route('shipping-companies.destroy', $sc) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-delete">Sil</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-neutral-500 dark:text-slate-400">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-neutral-100">{{ $shippingCompanies->links() }}</div>
</div>
@endsection
