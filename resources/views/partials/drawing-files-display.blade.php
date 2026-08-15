@php
    $entries = $entries ?? \App\Support\DrawingFiles::existingEntries(
        \App\Support\DrawingFiles::entries($drawingFiles ?? [])
    );
    $lightboxImages = [];
    foreach ($entries as $entry) {
        if (! \App\Support\DrawingFiles::isImage($entry)) {
            continue;
        }
        $url = \App\Support\DrawingFiles::url($entry['path']);
        if (! $url) {
            continue;
        }
        $lightboxImages[] = [
            'url' => $url,
            'name' => $entry['name'] ?? 'Görsel',
        ];
    }
@endphp
@if(count($entries) > 0)
<div
    class="card overflow-hidden"
    x-data="{
        open: false,
        index: 0,
        images: @js($lightboxImages),
        openAt(i) {
            if (!this.images.length) return;
            this.index = i;
            this.open = true;
        },
        close() { this.open = false; },
        prev() {
            if (!this.images.length) return;
            this.index = (this.index - 1 + this.images.length) % this.images.length;
        },
        next() {
            if (!this.images.length) return;
            this.index = (this.index + 1) % this.images.length;
        },
        current() { return this.images[this.index] || null; }
    }"
    @keydown.escape.window="if (open) close()"
>
    <div class="card-header flex items-center justify-between">
        <span>Çizim Dosyaları</span>
        <span class="text-xs font-normal text-neutral-500">{{ count($entries) }} dosya</span>
    </div>
    <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @php $imageIndex = 0; @endphp
            @foreach($entries as $entry)
            @php
                $fileUrl = \App\Support\DrawingFiles::url($entry['path']);
                $kindLabel = \App\Support\DrawingFiles::kindLabel($entry);
                $isImage = \App\Support\DrawingFiles::isImage($entry);
                $isPdf = \App\Support\DrawingFiles::isPdf($entry);
                $isDwg = \App\Support\DrawingFiles::isDwg($entry);
                $previewClass = match (true) {
                    $isImage => 'bg-neutral-50 dark:bg-slate-800',
                    $isPdf => 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100/80',
                    $isDwg => 'bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100/80',
                    default => 'bg-neutral-50 dark:bg-slate-800 hover:bg-neutral-100/80',
                };
                $labelClass = match (true) {
                    $isPdf => 'text-red-700 dark:text-red-300',
                    $isDwg => 'text-sky-700 dark:text-sky-300',
                    default => 'text-neutral-700 dark:text-neutral-300',
                };
                $thisImageIndex = $isImage && $fileUrl ? $imageIndex++ : null;
            @endphp
            <div class="rounded-xl border border-neutral-200 dark:border-slate-700 overflow-hidden">
                @if($isImage && $fileUrl)
                <button
                    type="button"
                    @click="openAt({{ $thisImageIndex }})"
                    class="block w-full aspect-video {{ $previewClass }} cursor-zoom-in focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-inset"
                    aria-label="{{ $entry['name'] }} görselini büyüt"
                >
                    <img src="{{ $fileUrl }}" alt="{{ $entry['name'] }}" class="w-full h-full object-contain p-2 pointer-events-none">
                </button>
                @elseif($fileUrl)
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" download class="flex aspect-video items-center justify-center {{ $previewClass }} transition-colors">
                    <div class="text-center px-4">
                        @if($isPdf)
                        <svg class="w-10 h-10 mx-auto text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        @elseif($isDwg)
                        <svg class="w-10 h-10 mx-auto text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        @else
                        <svg class="w-10 h-10 mx-auto text-neutral-500 dark:text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        @endif
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide {{ $labelClass }}">{{ $kindLabel }}</p>
                    </div>
                </a>
                @else
                <div class="flex aspect-video items-center justify-center {{ $previewClass }}">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $labelClass }}">{{ $kindLabel }}</p>
                </div>
                @endif
                <div class="p-3 border-t border-neutral-100 dark:border-slate-700">
                    @if($isImage && $fileUrl)
                    <button type="button" @click="openAt({{ $thisImageIndex }})" class="text-left text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline break-all">{{ $entry['name'] }}</button>
                    @elseif($fileUrl)
                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" download class="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline break-all">{{ $entry['name'] }}</a>
                    @else
                    <p class="text-sm font-medium break-all">{{ $entry['name'] }}</p>
                    @endif
                    <p class="text-[11px] text-neutral-500 mt-1">{{ $kindLabel }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-8"
            role="dialog"
            aria-modal="true"
            aria-label="Görsel önizleme"
            @keydown.left.window="if (open) prev()"
            @keydown.right.window="if (open) next()"
        >
            <div class="absolute inset-0 bg-black/80" @click="close()" aria-hidden="true"></div>

            <div
                class="relative z-10 flex w-full max-w-6xl max-h-full flex-col gap-3"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
            >
                <div class="flex items-center justify-between gap-3 text-white">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium" x-text="current()?.name || ''"></p>
                        <p class="text-xs text-white/70" x-show="images.length > 1" x-text="(index + 1) + ' / ' + images.length"></p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            x-show="current()?.url"
                            :href="current()?.url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-colors"
                            title="Yeni sekmede aç"
                            aria-label="Yeni sekmede aç"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                        <button
                            type="button"
                            @click="close()"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-colors"
                            aria-label="Kapat"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="relative flex items-center justify-center min-h-0">
                    <button
                        type="button"
                        x-show="images.length > 1"
                        @click="prev()"
                        class="absolute left-0 sm:-left-2 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 transition-colors"
                        aria-label="Önceki görsel"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>

                    <img
                        :src="current()?.url"
                        :alt="current()?.name || 'Görsel'"
                        class="max-h-[75vh] max-w-full object-contain rounded-lg shadow-2xl bg-black/20"
                    >

                    <button
                        type="button"
                        x-show="images.length > 1"
                        @click="next()"
                        class="absolute right-0 sm:-right-2 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 transition-colors"
                        aria-label="Sonraki görsel"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endif
