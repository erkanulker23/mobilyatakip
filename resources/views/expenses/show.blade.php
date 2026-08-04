@extends('layouts.app')
@include('partials.page-seo', \App\Support\PageSeo::expense($expense))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
            <a href="{{ route('expenses.index') }}" class="hover:text-neutral-900">Giderler</a>
            <span>/</span>
            <span class="text-neutral-700">{{ $expense->expenseDate?->format('d.m.Y') }} - {{ Str::limit($expense->description, 30) }}</span>
        </div>
        <h1 class="page-title">Gider Detay</h1>
        <p class="page-desc">{{ number_format($expense->amount, 0, ',', '.') }} ₺</p>
    </div>
    <div class="flex items-center gap-2">
        @include('partials.action-buttons', [
            'edit' => route('expenses.edit', $expense),
            'destroy' => route('expenses.destroy', $expense),
        ])
    </div>
</div>

<div class="card p-6 max-w-2xl">
    <dl class="space-y-4">
        <div><dt class="text-sm text-neutral-500">Tarih</dt><dd class="font-medium">{{ $expense->expenseDate?->format('d.m.Y') }}</dd></div>
        <div><dt class="text-sm text-neutral-500">Tutar</dt><dd class="font-bold text-lg text-slate-900">{{ number_format($expense->amount, 0, ',', '.') }} ₺</dd></div>
        <div><dt class="text-sm text-neutral-500">Kategori</dt><dd class="font-medium">{{ $expense->category ?: '—' }}</dd></div>
        <div><dt class="text-sm text-neutral-500">Açıklama</dt><dd class="text-neutral-700 whitespace-pre-wrap">{{ $expense->description }}</dd></div>
        <div><dt class="text-sm text-neutral-500">Kasa</dt><dd class="font-medium">{{ $expense->kasa?->name ?? '—' }}</dd></div>
        @if($expense->createdByUser)
        <div><dt class="text-sm text-neutral-500">Kaydeden</dt><dd class="font-medium">{{ $expense->createdByUser->name ?? $expense->createdByUser->email ?? '—' }}</dd></div>
        @endif
    </dl>
</div>
@endsection
