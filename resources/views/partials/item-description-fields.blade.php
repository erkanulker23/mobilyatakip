<div class="mt-2 item-desc-block">
    <div class="flex items-center justify-between gap-2 mb-1.5">
        <label class="form-label mb-0">Açıklama (madde madde)</label>
        <button type="button" class="item-desc-add inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500 transition-colors touch-manipulation shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Madde Ekle
        </button>
    </div>
    <div class="item-desc-lines space-y-1.5">
        <div class="item-desc-line flex gap-2 items-start">
            <span class="text-neutral-400 text-sm leading-10 shrink-0 select-none" aria-hidden="true">•</span>
            <input type="text" name="items[__IDX__][descriptionLines][]" class="form-input item-desc-line-input flex-1 text-sm min-h-[40px]" placeholder="Renk, ölçü, kumaş vb.">
            <button type="button" class="item-desc-remove shrink-0 w-10 h-10 flex items-center justify-center text-neutral-400 hover:text-red-600 rounded-lg hover:bg-red-50 touch-manipulation" title="Maddeyi sil" aria-label="Maddeyi sil">&times;</button>
        </div>
    </div>
</div>
