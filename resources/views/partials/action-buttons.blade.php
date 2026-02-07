{{--
  Kullanım: @include('partials.action-buttons', [
    'show' => route('...', $item),
    'edit' => route('...', $item),
    'destroy' => route('...', $item),
    'print' => route('...', $item),
  ])
  İstenmeyen buton için ilgili key'i kaldırın veya null yapın.
--}}
<div class="flex items-center justify-end gap-1" x-data="{ deleteOpen: false }">
  @if(!empty($show))
  <a href="{{ $show }}" aria-label="Detay görüntüle" title="Detay" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-slate-900">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
  </a>
  @endif
  @if(!empty($edit))
  <a href="{{ $edit }}" aria-label="Düzenle" title="Düzenle" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-sky-600 dark:hover:text-sky-400 transition-colors focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-slate-900">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
  </a>
  @endif
  @if(!empty($print))
  <a href="{{ $print }}" target="_blank" rel="noopener" aria-label="Yazdır" title="Yazdır" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-700 dark:hover:text-slate-300 transition-colors focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-slate-900">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
  </a>
  @endif
  @if(!empty($destroy))
  <button type="button" @click="deleteOpen = true" aria-label="Kaydı sil" title="Sil" class="p-2 rounded-xl text-slate-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 dark:hover:text-red-400 transition-colors focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-slate-900">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
  </button>
  {{-- Silme onay modal --}}
  <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
    <div x-show="deleteOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50" @click="deleteOpen = false"></div>
    <div x-show="deleteOpen" x-transition class="relative card max-w-sm w-full p-6" @keydown.escape.window="deleteOpen = false">
      <h2 id="delete-modal-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Kaydı sil</h2>
      <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Bu kaydı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
      <div class="mt-6 flex gap-3 justify-end">
        <button type="button" @click="deleteOpen = false" class="btn-secondary">İptal</button>
        <form method="POST" action="{{ $destroy }}" class="inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">Sil</button>
        </form>
      </div>
    </div>
  </div>
  @endif
</div>
