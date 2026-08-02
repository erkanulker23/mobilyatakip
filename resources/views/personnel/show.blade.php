@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::personnel($personnel))
@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
                <a href="{{ route('personnel.index') }}" class="hover:text-neutral-900">Personel</a>
                <span>/</span>
                <span class="text-neutral-700">{{ $personnel->name }}</span>
            </div>
            <h1 class="page-title">{{ $personnel->name }}</h1>
            <p class="page-desc">{{ $personnel->title ?? 'Personel detayları' }}</p>
        </div>
        <a href="{{ route('personnel.edit', $personnel) }}" class="btn-edit">Düzenle</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-6">
        <div class="card p-6">
            <div class="flex justify-center mb-4">
                @if($personnel->photoUrl)
                    <img src="{{ storage_url($personnel->photoUrl) }}" alt="{{ $personnel->name }}" class="h-28 w-28 rounded-full object-cover border border-neutral-200 dark:border-neutral-700">
                @else
                    <div class="h-28 w-28 rounded-full bg-slate-100 dark:bg-slate-700 border border-neutral-200 dark:border-slate-600 flex items-center justify-center text-3xl font-semibold text-slate-400">
                        {{ mb_strtoupper(mb_substr($personnel->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">İletişim Bilgileri</h2>
            <dl class="space-y-3">
                <div><dt class="text-sm text-neutral-500">E-posta</dt><dd class="font-medium">{{ $personnel->email ?: '-' }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Telefon</dt><dd class="font-medium">{{ $personnel->phone ?: '-' }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Unvan</dt><dd class="font-medium">{{ $personnel->title ?: '-' }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Kategori</dt><dd class="font-medium">{{ $personnel->category ?: '-' }}</dd></div>
            </dl>
        </div>
    </div>
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-lg font-semibold text-slate-900">Teklifler</h2>
                <p class="text-sm text-neutral-500 mt-1">{{ $personnel->quotes->count() }} teklif</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="table-th">No</th>
                            <th class="table-th">Müşteri</th>
                            <th class="table-th text-right">Tutar</th>
                            <th class="table-th">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($personnel->quotes->take(10) as $q)
                        <tr class="hover:bg-slate-50">
                            <td class="table-td"><a href="{{ route('quotes.show', $q) }}" class="font-medium text-green-600 hover:text-green-700">{{ $q->quoteNumber }}</a></td>
                            <td class="px-6 py-4 text-slate-600">{{ $q->customer?->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-right font-medium">{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="px-6 py-4 text-slate-600">{{ $q->createdAt?->format('d.m.Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-neutral-500">Henüz teklif yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
