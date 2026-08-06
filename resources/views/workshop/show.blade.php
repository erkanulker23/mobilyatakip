@extends('layouts.app')
@section('title', 'Atölye - ' . $sale->saleNumber)
@section('content')
@php
    use App\Support\SaleDelivery;
    use App\Models\SaleProductionStage;

    $status = SaleDelivery::currentStatus($sale);
    $termin = SaleDelivery::terminListMeta($sale);
    $backUrl = $backUrl ?? route('workshop.index');
    $defaultNoteType = !empty($hideCommercialData) ? SaleProductionStage::TYPE_DEFICIENCY : old('type', SaleProductionStage::TYPE_DEFICIENCY);
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
            <p class="page-desc">{{ SaleDelivery::label($status) }}@if($showCustomerNames && $sale->customer?->name) · {{ $sale->customer->name }}@endif</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.workshop.mobilya', $sale) }}" target="_blank" class="btn-secondary text-sm">Atölye Fişi</a>
            <a href="{{ route('sales.workshop.koltuk', $sale) }}" target="_blank" class="btn-secondary text-sm">Koltuk Fişi</a>
            @if($status === SaleDelivery::IN_PRODUCTION)
            <form method="POST" action="{{ route('workshop.complete-production', $sale) }}" onsubmit="return confirm('Sipariş atölyeden çıktı olarak işaretlenecek. Onaylıyor musunuz?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-[0.625rem] hover:bg-emerald-700 font-medium text-sm transition-colors">
                    Üretim Bitti — Atölyeden Çıktı
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

@if(empty($productionStagesReady))
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
    Not ekleme henüz aktif değil. Sistem yöneticisinin <code class="text-xs">php artisan migrate --force</code> çalıştırması gerekiyor.
</div>
@elseif($status === SaleDelivery::IN_PRODUCTION)
<div class="card p-6 mb-6 border-amber-200/80 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/20">
    <h2 class="font-semibold text-neutral-900 dark:text-white mb-1">Atölye Notu / Eksiklik Bildir</h2>
    <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">Eksik, yanlış veya hatalı gelen parçaları veya üretim durumunu buradan kaydedin.</p>
    <form method="POST" action="{{ route('workshop.store-stage', $sale) }}" id="workshopNoteForm" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Tür *</label>
                <select name="type" id="stageType" required class="form-select">
                    @foreach(SaleProductionStage::typeOptions() as $value => $label)
                    <option value="{{ $value }}" {{ $defaultNoteType === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($sale->items->isNotEmpty())
            <div>
                <label class="form-label">İlgili Ürün</label>
                <select name="saleItemId" id="saleItemId" class="form-select">
                    <option value="">Genel sipariş notu</option>
                    @foreach($sale->items as $item)
                    <option value="{{ $item->id }}" data-name="{{ $item->productName ?? $item->product?->name ?? 'Ürün' }}" {{ old('saleItemId') === $item->id ? 'selected' : '' }}>
                        {{ $item->productName ?? $item->product?->name ?? 'Ürün' }} ({{ $item->quantity }} adet)
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
        <div>
            <label class="form-label">Not *</label>
            <textarea name="notes" id="stageNotes" rows="4" required class="form-input form-textarea" placeholder="Örn: Ürünün sol panel parçaları eksik geldi">{{ old('notes') }}</textarea>
            <div class="mt-3 flex flex-wrap gap-2" id="quickNotes">
                <span class="text-xs text-neutral-500 w-full">Hızlı not:</span>
                <button type="button" data-type="eksiklik" data-note="Sol panel parçaları eksik geldi" class="text-xs px-2.5 py-1.5 rounded-md border border-amber-200 text-amber-900 dark:border-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/40">Sol panel eksik</button>
                <button type="button" data-type="eksiklik" data-note="Sağ panel parçaları eksik geldi" class="text-xs px-2.5 py-1.5 rounded-md border border-amber-200 text-amber-900 dark:border-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/40">Sağ panel eksik</button>
                <button type="button" data-type="eksiklik" data-note="Yanlış parça/ölçü geldi" class="text-xs px-2.5 py-1.5 rounded-md border border-amber-200 text-amber-900 dark:border-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/40">Yanlış parça</button>
                <button type="button" data-type="eksiklik" data-note="Montaj parçası eksik geldi" class="text-xs px-2.5 py-1.5 rounded-md border border-amber-200 text-amber-900 dark:border-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/40">Montaj parçası eksik</button>
                <button type="button" data-type="asama" data-note="Kesim tamamlandı" class="text-xs px-2.5 py-1.5 rounded-md border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800">Kesim tamamlandı</button>
                <button type="button" data-type="asama" data-note="Montaj tamamlandı" class="text-xs px-2.5 py-1.5 rounded-md border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800">Montaj tamamlandı</button>
            </div>
            @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('saleItemId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary">Notu Kaydet</button>
    </form>
</div>
@endif

@if($status === SaleDelivery::IN_PRODUCTION && ($openDeficienciesCount ?? 0) > 0)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
    Bu siparişte <strong>{{ $openDeficienciesCount }}</strong> açık eksiklik/yanlış parça kaydı var.
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
                            @if(!empty($productionStagesReady) && $status === SaleDelivery::IN_PRODUCTION)
                            <th class="table-th text-right">Not</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse($sale->items as $item)
                        @php $itemName = $item->productName ?? $item->product?->name ?? 'Ürün'; @endphp
                        <tr>
                            <td class="table-td font-medium">{{ $itemName }}</td>
                            <td class="table-td">{{ $item->quantity }}</td>
                            <td class="table-td text-sm text-neutral-600 dark:text-neutral-400">{{ $item->description ?: '—' }}</td>
                            @if(!empty($productionStagesReady) && $status === SaleDelivery::IN_PRODUCTION)
                            <td class="table-td text-right">
                                <button type="button" class="item-note-btn text-sm font-medium text-amber-700 hover:text-amber-800 dark:text-amber-300 dark:hover:text-amber-200" data-item-id="{{ $item->id }}" data-item-name="{{ $itemName }}">
                                    Eksiklik bildir
                                </button>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="4" class="table-td text-neutral-500">Kalem yok</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-4">Atölye Notları</h2>
            @if($sale->productionStages->isEmpty())
            <p class="text-neutral-500 text-sm">Henüz not eklenmemiş.</p>
            @else
            <div class="space-y-4">
                @foreach($sale->productionStages as $stage)
                @php $productLabel = $stage->productLabel(); @endphp
                <div class="border rounded-xl p-4 {{ $stage->isCompleted ? 'border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 opacity-80' : 'border-neutral-100 dark:border-neutral-800' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge {{ $stage->type === SaleProductionStage::TYPE_DEFICIENCY ? 'badge-amber' : 'badge-blue' }}">
                                {{ SaleProductionStage::typeLabel($stage->type) }}
                            </span>
                            @if($productLabel)
                            <span class="badge badge-neutral">{{ $productLabel }}</span>
                            @endif
                            @if($stage->isCompleted)
                            <span class="badge badge-green">Giderildi</span>
                            @endif
                            <span class="text-xs text-neutral-500">{{ $stage->actionDate?->format('d.m.Y H:i') }}</span>
                            @if($stage->user)
                            <span class="text-xs text-neutral-500">· {{ $stage->user->name }}</span>
                            @endif
                        </div>
                        @if(! $stage->isCompleted)
                        <form method="POST" action="{{ route('workshop.complete-stage', $stage) }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Giderildi</button>
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
                @if($showCustomerNames)
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

        @if($status === SaleDelivery::IN_PRODUCTION)
        <div class="card p-6 border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/40 dark:bg-emerald-950/20">
            <h2 class="font-semibold text-neutral-900 dark:text-white mb-1">Üretim Tamamlandı</h2>
            <p class="text-xs text-neutral-600 dark:text-neutral-400 mb-4">Sipariş atölyeden çıktığında durum <strong>Teslim bekliyor</strong> olur.</p>
            <form method="POST" action="{{ route('workshop.complete-production', $sale) }}" onsubmit="return confirm('Sipariş atölyeden çıktı olarak işaretlenecek. Emin misiniz?');">
                @csrf
                <button type="submit" class="btn-primary w-full justify-center bg-emerald-600 hover:bg-emerald-700 border-emerald-600">Atölyeden Çıktı — Üretim Bitti</button>
            </form>
        </div>
        @endif
    </div>
</div>
<script>
(function () {
    var form = document.getElementById('workshopNoteForm');
    if (!form) return;

    var notes = document.getElementById('stageNotes');
    var type = document.getElementById('stageType');
    var itemSelect = document.getElementById('saleItemId');

    function prefixWithProduct(text) {
        if (!itemSelect || !itemSelect.value) return text;
        var option = itemSelect.options[itemSelect.selectedIndex];
        var name = option && option.dataset.name ? option.dataset.name : '';
        if (!name) return text;
        return name + ': ' + text;
    }

    document.querySelectorAll('#quickNotes button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            notes.value = prefixWithProduct(this.dataset.note || '');
            if (this.dataset.type) type.value = this.dataset.type;
        });
    });

    document.querySelectorAll('.item-note-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (itemSelect) itemSelect.value = this.dataset.itemId || '';
            type.value = 'eksiklik';
            notes.value = (this.dataset.itemName || 'Ürün') + ': Sol panel parçaları eksik geldi';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            notes.focus();
        });
    });
})();
</script>
@endsection
