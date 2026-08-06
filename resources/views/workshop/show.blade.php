@extends('layouts.app')
@section('title', 'Atölye - ' . $sale->saleNumber)
@section('content')
@php
    use App\Support\SaleDelivery;
    use App\Models\SaleProductionStage;

    $status = SaleDelivery::currentStatus($sale);
    $termin = SaleDelivery::terminListMeta($sale);
    $backUrl = $backUrl ?? route('workshop.index');
@endphp
<div class="mb-6">
    <nav class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ $backUrl }}" class="hover:text-neutral-900 dark:hover:text-white">Atölye</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-neutral-300">{{ $sale->saleNumber }}</span>
    </nav>
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <h1 class="page-title">{{ $sale->saleNumber }}</h1>
            <p class="page-desc">{{ SaleDelivery::label($status) }}@if(empty($hideCommercialData) && $sale->customer?->name) · {{ $sale->customer->name }}@endif</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.workshop.mobilya', $sale) }}" target="_blank" class="btn-secondary text-sm">Atölye Fişi</a>
            <a href="{{ route('sales.workshop.koltuk', $sale) }}" target="_blank" class="btn-secondary text-sm">Koltuk Fişi</a>
        </div>
    </div>
</div>

@if(empty($productionStagesReady))
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
    Üretim aşaması kayıtları henüz aktif değil. Aşama ve eksiklik eklemek için sistem yöneticisinin migration çalıştırması gerekiyor.
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-4">Sipariş Kalemleri</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800">
                            <th class="table-th">Ürün</th>
                            <th class="table-th">Adet</th>
                            <th class="table-th">Açıklama</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse($sale->items as $item)
                        <tr>
                            <td class="table-td font-medium">{{ $item->productName ?? $item->product?->name ?? '—' }}</td>
                            <td class="table-td">{{ $item->quantity }}</td>
                            <td class="table-td text-sm text-neutral-600 dark:text-neutral-400">{{ $item->description ?: '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="table-td text-neutral-500">Kalem yok</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-4">Aşama & Eksiklik Geçmişi</h2>
            @if($sale->productionStages->isEmpty())
            <p class="text-neutral-500 text-sm">Henüz kayıt eklenmemiş.</p>
            @else
            <div class="space-y-4">
                @foreach($sale->productionStages as $stage)
                <div class="border rounded-xl p-4 {{ $stage->isCompleted ? 'border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 opacity-80' : 'border-neutral-100 dark:border-neutral-800' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge {{ $stage->type === SaleProductionStage::TYPE_DEFICIENCY ? 'badge-amber' : 'badge-blue' }}">
                                {{ SaleProductionStage::typeLabel($stage->type) }}
                            </span>
                            @if($stage->isCompleted)
                            <span class="badge badge-green">Yapıldı</span>
                            @endif
                            <span class="text-xs text-neutral-500">{{ $stage->actionDate?->format('d.m.Y H:i') }}</span>
                            @if($stage->user)
                            <span class="text-xs text-neutral-500">· {{ $stage->user->name }}</span>
                            @endif
                        </div>
                        @if(! $stage->isCompleted)
                        <form method="POST" action="{{ route('workshop.complete-stage', $stage) }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Yapıldı</button>
                        </form>
                        @endif
                    </div>
                    <p class="text-sm text-neutral-800 dark:text-neutral-200 whitespace-pre-wrap {{ $stage->isCompleted ? 'line-through opacity-70' : '' }}">{{ $stage->notes }}</p>
                    @if($stage->isCompleted && $stage->completedAt)
                    <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-2">
                        {{ $stage->completedByUser?->name ?? 'Atölye' }} · {{ $stage->completedAt->format('d.m.Y H:i') }}
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-4">Sipariş Bilgileri</h2>
            <dl class="space-y-3 text-sm">
                @if(empty($hideCommercialData))
                <div><dt class="text-neutral-500">Müşteri</dt><dd class="font-medium">{{ $sale->customer?->name ?? '—' }}</dd></div>
                @endif
                <div><dt class="text-neutral-500">Sipariş No</dt><dd class="font-medium">{{ $sale->saleNumber }}</dd></div>
                <div><dt class="text-neutral-500">Termin</dt>
                    <dd class="font-medium">
                        @if($sale->dueDate)
                        {{ $sale->dueDate->format('d.m.Y') }}
                        @if($termin['suffix'] ?? null)
                        <span class="block text-xs {{ $termin['class'] ?? 'text-neutral-500' }}">{{ $termin['suffix'] }}</span>
                        @endif
                        @else — @endif
                    </dd>
                </div>
                @if(empty($hideCommercialData))
                <div><dt class="text-neutral-500">Satış Temsilcisi</dt><dd class="font-medium">{{ $sale->personnel?->name ?? '—' }}</dd></div>
                @endif
                <div><dt class="text-neutral-500">Satış Tarihi</dt><dd class="font-medium">{{ $sale->saleDate?->format('d.m.Y') ?? '—' }}</dd></div>
                @if($sale->notes && empty($hideCommercialData))
                <div><dt class="text-neutral-500">Notlar</dt><dd class="font-medium whitespace-pre-wrap">{{ $sale->notes }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-1">Kayıt Ekle</h2>
            <p class="text-xs text-neutral-500 mb-4">Üretim aşaması veya eksik/yanlış parça bildirimi</p>
            @if(!empty($productionStagesReady))
            <form method="POST" action="{{ route('workshop.store-stage', $sale) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Tür *</label>
                    <select name="type" id="stageType" required class="form-select">
                        @foreach(SaleProductionStage::typeOptions() as $value => $label)
                        <option value="{{ $value }}" {{ old('type', SaleProductionStage::TYPE_STAGE) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Açıklama *</label>
                    <textarea name="notes" rows="4" required class="form-input form-textarea" placeholder="Örn: Kesim tamamlandı">{{ old('notes') }}</textarea>
                    <div class="mt-2 flex flex-wrap gap-2" id="quickNotes">
                        <button type="button" data-note="Kesim tamamlandı" class="text-xs px-2 py-1 rounded-md border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800">Kesim tamamlandı</button>
                        <button type="button" data-note="Montaj tamamlandı" class="text-xs px-2 py-1 rounded-md border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800">Montaj tamamlandı</button>
                        <button type="button" data-note="Parça eksik geldi" class="text-xs px-2 py-1 rounded-md border border-amber-200 text-amber-800 dark:border-amber-800 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/30">Parça eksik</button>
                        <button type="button" data-note="Yanlış parça/ölçü geldi" class="text-xs px-2 py-1 rounded-md border border-amber-200 text-amber-800 dark:border-amber-800 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/30">Yanlış parça</button>
                    </div>
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Kaydet</button>
            </form>
            @else
            <p class="text-sm text-neutral-500">Migration tamamlanana kadar kayıt eklenemez.</p>
            @endif
        </div>
    </div>
</div>
<script>
document.querySelectorAll('#quickNotes button').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var notes = document.querySelector('textarea[name="notes"]');
        var type = document.getElementById('stageType');
        notes.value = this.dataset.note;
        if (this.dataset.note.includes('eksik') || this.dataset.note.includes('Yanlış')) {
            type.value = 'eksiklik';
        } else {
            type.value = 'asama';
        }
    });
});
</script>
@endsection
