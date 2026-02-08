@extends('layouts.app')
@section('title', $personnel->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                <a href="{{ route('personnel.index') }}" class="hover:text-slate-700">Personel</a>
                <span>/</span>
                <span class="text-slate-700">{{ $personnel->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $personnel->name }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $personnel->title ?? 'Personel detayları' }}</p>
        </div>
        <a href="{{ route('personnel.edit', $personnel) }}" class="btn-edit">Düzenle</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">İletişim Bilgileri</h2>
            <dl class="space-y-3">
                <div><dt class="text-sm text-slate-500">E-posta</dt><dd class="font-medium">{{ $personnel->email ?: '-' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Telefon</dt><dd class="font-medium">{{ $personnel->phone ?: '-' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Unvan</dt><dd class="font-medium">{{ $personnel->title ?: '-' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Kategori</dt><dd class="font-medium">{{ $personnel->category ?: '-' }}</dd></div>
            </dl>
        </div>
    </div>
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Teklifler</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $personnel->quotes->count() }} teklif</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Müşteri</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Tutar</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($personnel->quotes->take(10) as $q)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4"><a href="{{ route('quotes.show', $q) }}" class="font-medium text-green-600 hover:text-green-700">{{ $q->quoteNumber }}</a></td>
                            <td class="px-6 py-4 text-slate-600">{{ $q->customer?->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-right font-medium">{{ number_format($q->grandTotal ?? 0, 0, ',', '.') }} ₺</td>
                            <td class="px-6 py-4 text-slate-600">{{ $q->createdAt?->format('d.m.Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Henüz teklif yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
