@php
    use App\Models\SaleProductionStage;

    $formId = $formId ?? 'workshopNoteForm';
    $compact = $compact ?? false;
@endphp
@if(! $compact)
<div class="mt-6 pt-6 border-t border-neutral-200 dark:border-neutral-800">
@else
<div>
@endif
    <form method="POST" action="{{ route('workshop.store-stage', $sale) }}" id="{{ $formId }}" class="space-y-3">
        @csrf
        <input type="hidden" name="type" value="{{ SaleProductionStage::TYPE_STAGE }}">
        <div>
            <label class="form-label" for="{{ $formId }}Notes">Not</label>
            <textarea name="notes" id="{{ $formId }}Notes" rows="3" required class="form-input form-textarea" placeholder="Sipariş hakkında not yazın...">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary">Not Ekle</button>
    </form>
</div>
