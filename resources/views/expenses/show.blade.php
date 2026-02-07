@extends('layouts.app')
@section('title', 'Gider Detay')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
            <a href="{{ route('expenses.index') }}" class="hover:text-slate-700">Giderler</a>
            <span>/</span>
            <span class="text-slate-700">{{ $expense->expenseDate?->format('d.m.Y') }} - {{ Str::limit($expense->description, 30) }}</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Gider Detay</h1>
        <p class="text-slate-600 mt-1">{{ number_format($expense->amount, 0, ',', '.') }} ₺</p>
    </div>
    <div class="flex items-center gap-2">
        @include('partials.action-buttons', [
            'edit' => route('expenses.edit', $expense),
            'destroy' => route('expenses.destroy', $expense),
        ])
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <dl class="space-y-4">
        <div><dt class="text-sm text-slate-500">Tarih</dt><dd class="font-medium">{{ $expense->expenseDate?->format('d.m.Y') }}</dd></div>
        <div><dt class="text-sm text-slate-500">Tutar</dt><dd class="font-bold text-lg text-slate-900">{{ number_format($expense->amount, 0, ',', '.') }} ₺</dd></div>
        @if($expense->kdvRate !== null && (float)$expense->kdvRate > 0)
        <div><dt class="text-sm text-slate-500">KDV ({{ number_format($expense->kdvRate, 0) }}%)</dt><dd class="font-medium">{{ number_format($expense->kdvAmount ?? 0, 0, ',', '.') }} ₺ {{ $expense->kdvIncluded ? '(dahil)' : '(hariç)' }}</dd></div>
        @endif
        <div><dt class="text-sm text-slate-500">Kategori</dt><dd class="font-medium">{{ $expense->category ?: '—' }}</dd></div>
        <div><dt class="text-sm text-slate-500">Açıklama</dt><dd class="text-slate-700 whitespace-pre-wrap">{{ $expense->description }}</dd></div>
        <div><dt class="text-sm text-slate-500">Kasa</dt><dd class="font-medium">{{ $expense->kasa?->name ?? '—' }}</dd></div>
        @if($expense->createdByUser)
        <div><dt class="text-sm text-slate-500">Kaydeden</dt><dd class="font-medium">{{ $expense->createdByUser->name ?? $expense->createdByUser->email ?? '—' }}</dd></div>
        @endif
    </dl>
</div>
@endsection
