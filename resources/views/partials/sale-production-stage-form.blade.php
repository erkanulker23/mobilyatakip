@php
    use App\Models\SaleProductionStage;

    $formId = $formId ?? 'workshopNoteForm';
    $defaultNoteType = $defaultNoteType ?? old('type', SaleProductionStage::TYPE_DEFICIENCY);
    $compact = $compact ?? false;
@endphp
@if(! $compact)
<div class="mt-6 pt-6 border-t border-neutral-200 dark:border-neutral-800">
    <h3 class="font-semibold text-neutral-900 dark:text-white mb-1">Aşama / Eksiklik Ekle</h3>
    <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">Üretim durumunu veya eksik parçaları buradan kaydedin.</p>
@else
<div>
    <h2 class="font-semibold text-neutral-900 dark:text-white mb-1">Atölye Notu / Eksiklik Bildir</h2>
    <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">Eksik, yanlış veya hatalı gelen parçaları veya üretim durumunu buradan kaydedin.</p>
@endif
    <form method="POST" action="{{ route('workshop.store-stage', $sale) }}" id="{{ $formId }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Tür *</label>
                <select name="type" id="{{ $formId }}Type" required class="form-select">
                    @foreach(SaleProductionStage::typeOptions() as $value => $label)
                    <option value="{{ $value }}" {{ $defaultNoteType === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($sale->items->isNotEmpty())
            <div>
                <label class="form-label">İlgili Ürün</label>
                <select name="saleItemId" id="{{ $formId }}Item" class="form-select">
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
            <textarea name="notes" id="{{ $formId }}Notes" rows="4" required class="form-input form-textarea" placeholder="Örn: Kesim tamamlandı veya sol panel parçaları eksik geldi">{{ old('notes') }}</textarea>
            <div class="mt-3 flex flex-wrap gap-2" id="{{ $formId }}QuickNotes">
                <span class="text-xs text-neutral-500 w-full">Hızlı not:</span>
                <button type="button" data-type="eksiklik" data-note="Sol panel parçaları eksik geldi" class="text-xs px-2.5 py-1.5 rounded-md border border-amber-200 text-amber-900 dark:border-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/40">Sol panel eksik</button>
                <button type="button" data-type="eksiklik" data-note="Sağ panel parçaları eksik geldi" class="text-xs px-2.5 py-1.5 rounded-md border border-amber-200 text-amber-900 dark:border-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/40">Sağ panel eksik</button>
                <button type="button" data-type="eksiklik" data-note="Yanlış parça/ölçü geldi" class="text-xs px-2.5 py-1.5 rounded-md border border-amber-200 text-amber-900 dark:border-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/40">Yanlış parça</button>
                <button type="button" data-type="eksiklik" data-note="Montaj parçası eksik geldi" class="text-xs px-2.5 py-1.5 rounded-md border border-amber-200 text-amber-900 dark:border-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/40">Montaj parçası eksik</button>
                <button type="button" data-type="asama" data-note="Kesim tamamlandı" class="text-xs px-2.5 py-1.5 rounded-md border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800">Kesim tamamlandı</button>
                <button type="button" data-type="asama" data-note="Montaj tamamlandı" class="text-xs px-2.5 py-1.5 rounded-md border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800">Montaj tamamlandı</button>
            </div>
            @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('saleItemId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary">{{ $compact ? 'Notu Kaydet' : 'Kaydet' }}</button>
    </form>
</div>
<script>
(function () {
    var form = document.getElementById(@json($formId));
    if (!form) return;

    var notes = document.getElementById(@json($formId . 'Notes'));
    var type = document.getElementById(@json($formId . 'Type'));
    var itemSelect = document.getElementById(@json($formId . 'Item'));
    var quickNotes = document.getElementById(@json($formId . 'QuickNotes'));

    function prefixWithProduct(text) {
        if (!itemSelect || !itemSelect.value) return text;
        var option = itemSelect.options[itemSelect.selectedIndex];
        var name = option && option.dataset.name ? option.dataset.name : '';
        if (!name) return text;
        return name + ': ' + text;
    }

    if (quickNotes) {
        quickNotes.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (notes) notes.value = prefixWithProduct(this.dataset.note || '');
                if (type && this.dataset.type) type.value = this.dataset.type;
            });
        });
    }
})();
</script>
