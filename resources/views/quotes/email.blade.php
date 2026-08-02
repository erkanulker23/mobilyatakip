@extends('layouts.app')
@section('title', 'Teklif E-posta Gönder - ' . $quote->quoteNumber)
@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
            <a href="{{ route('quotes.show', $quote) }}" class="hover:text-neutral-900">Teklif {{ $quote->quoteNumber }}</a>
            <span>/</span>
            <span class="text-neutral-700">E-posta Gönder</span>
        </div>
        <h1 class="page-title">Teklif E-posta ile Gönder</h1>
        <p class="page-desc">{{ $quote->customer?->name ?? 'Müşteri' }} için teklif gönderin</p>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('quotes.sendEmail', $quote) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-700">Alıcı E-posta</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $quote->customer?->email) }}"
                        class="form-input mt-1 block w-full rounded-lg border-slate-300"
                        placeholder="ornek@email.com" required>
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn-primary">E-posta Gönder</button>
                <a href="{{ route('quotes.show', $quote) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-neutral-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
            </div>
        </form>
    </div>
</div>
@endsection
