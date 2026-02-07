@extends('layouts.app')
@section('title', 'Müşteriler')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <h1 class="page-title">Müşteriler</h1>
        <p class="page-desc">Müşteri listesi ve cari yönetimi</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('customers.excel.export') }}" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Excel İndir
        </a>
        <form action="{{ route('customers.excel.import') }}" method="POST" enctype="multipart/form-data" class="inline-flex items-center gap-2">
            @csrf
            <label class="btn-secondary cursor-pointer mb-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 16m4-4v12"></path></svg>
                Excel Yükle
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden" onchange="this.form.submit()">
            </label>
        </form>
        <a href="{{ route('customers.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
            Yeni Müşteri
        </a>
    </div>
</div>

<div class="card p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[200px] flex-1">
            <label class="form-label">Ara (ad, e-posta, telefon, adres, vergi no)</label>
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
            <a href="{{ route('customers.index') }}" class="btn-secondary">Temizle</a>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="table-th">Ad</th>
                    <th class="table-th">E-posta</th>
                    <th class="table-th">Telefon</th>
                    <th class="table-th">Adres</th>
                    <th class="table-th text-right w-40">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                    <td class="table-td">
                        <span class="font-medium text-slate-900">{{ $c->name }}</span>
                        @if(!($c->isActive ?? true))<span class="ml-1 text-xs text-slate-400">(Pasif)</span>@endif
                    </td>
                    <td class="table-td">{{ $c->email ?? '-' }}</td>
                    <td class="table-td">{{ $c->phone ?? '-' }}</td>
                    <td class="table-td text-slate-500">{{ Str::limit($c->address, 40) ?: '-' }}</td>
                    <td class="table-td">
                        @include('partials.action-buttons', [
                            'show' => route('customers.show', $c),
                            'edit' => route('customers.edit', $c),
                            'print' => route('customers.print', $c),
                            'destroy' => route('customers.destroy', $c),
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center">
                        <p class="text-slate-500 text-sm">Kayıt bulunamadı.</p>
                        <a href="{{ route('customers.create') }}" class="btn-primary mt-4">Yeni müşteri ekle</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-100 text-sm text-slate-500">{{ $customers->links() }}</div>
</div>
@endsection
