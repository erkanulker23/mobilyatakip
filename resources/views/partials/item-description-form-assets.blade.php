<template id="item-desc-line-template">
    <div class="item-desc-line flex gap-2 items-start">
        <span class="text-neutral-400 text-sm leading-10 shrink-0 select-none" aria-hidden="true">•</span>
        <input type="text" class="form-input item-desc-line-input flex-1 text-sm min-h-[40px]" placeholder="Renk, ölçü, kumaş vb.">
        <button type="button" class="item-desc-remove shrink-0 w-10 h-10 flex items-center justify-center text-neutral-400 hover:text-red-600 rounded-lg hover:bg-red-50 touch-manipulation" title="Maddeyi sil" aria-label="Maddeyi sil">&times;</button>
    </div>
</template>
@once
@push('scripts')
<script src="{{ asset('js/item-description-lines.js') }}?v=1"></script>
@endpush
@endonce
