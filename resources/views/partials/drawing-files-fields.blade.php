@php
    $entries = \App\Support\DrawingFiles::existingEntries(
        \App\Support\DrawingFiles::entries($drawingFiles ?? [])
    );
    $removePaths = old('remove_drawing_files', []);
    if (! is_array($removePaths)) {
        $removePaths = [];
    }
    $lightboxImages = [];
    $fileCards = [];
    $imageIndex = 0;
    foreach ($entries as $entry) {
        $filePath = $entry['path'];
        $fileUrl = \App\Support\DrawingFiles::url($filePath);
        $isImage = \App\Support\DrawingFiles::isImage($entry);
        $isPdf = \App\Support\DrawingFiles::isPdf($entry);
        $lightboxIndex = null;
        if ($isImage && $fileUrl) {
            $lightboxImages[] = [
                'url' => $fileUrl,
                'name' => $entry['name'] ?? 'Görsel',
            ];
            $lightboxIndex = $imageIndex++;
        }
        $fileCards[] = [
            'path' => $filePath,
            'url' => $fileUrl,
            'name' => $entry['name'] ?? basename($filePath),
            'kindLabel' => \App\Support\DrawingFiles::kindLabel($entry),
            'isImage' => $isImage,
            'isPdf' => $isPdf,
            'isDwg' => \App\Support\DrawingFiles::isDwg($entry),
            'lightboxIndex' => $lightboxIndex,
        ];
    }
@endphp
<div
    class="sale-form-section"
    x-data="{
        open: false,
        pdfOpen: false,
        pdfUrl: '',
        pdfName: '',
        index: 0,
        images: @js($lightboxImages),
        files: @js($fileCards),
        removedPaths: @js(array_values($removePaths)),
        isRemoved(path) {
            return this.removedPaths.includes(path);
        },
        isRemovedAt(i) {
            const f = this.files[i];
            return f ? this.isRemoved(f.path) : false;
        },
        toggleRemoveAt(i) {
            const f = this.files[i];
            if (f) this.toggleRemove(f.path);
        },
        toggleRemove(path) {
            if (this.isRemoved(path)) {
                this.removedPaths = this.removedPaths.filter(p => p !== path);
            } else {
                this.removedPaths.push(path);
            }
        },
        previewAt(i) {
            const f = this.files[i];
            if (!f || !f.url) return;
            if (f.isImage && f.lightboxIndex !== null) {
                this.openAt(f.lightboxIndex);
            } else if (f.isPdf) {
                this.openPdf(f.url, f.name || 'PDF');
            } else {
                window.open(f.url, '_blank', 'noopener');
            }
        },
        openAt(i) {
            if (!this.images.length) return;
            this.index = i;
            this.open = true;
        },
        openPdf(url, name) {
            this.pdfUrl = url;
            this.pdfName = name || 'PDF';
            this.pdfOpen = true;
        },
        closeLightbox() { this.open = false; },
        closePdf() { this.pdfOpen = false; this.pdfUrl = ''; },
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
    @keydown.escape.window="if (open) closeLightbox(); if (pdfOpen) closePdf();"
>
    <div class="sale-form-section-head">
        <h2 class="sale-form-section-title">Çizim Dosyaları</h2>
        <span class="text-xs text-neutral-500">{{ count($fileCards) }} kayıtlı dosya</span>
    </div>
    <div class="sale-form-section-body space-y-5">
        @if(count($fileCards) > 0)
        <p class="text-xs text-neutral-500 dark:text-neutral-400">Önizlemek için görsele veya PDF alanına tıklayın. Kaldırdığınız dosyalar <strong>Değişiklikleri Kaydet</strong> ile silinir.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($fileCards as $fileIndex => $card)
            @php
                $previewClass = match (true) {
                    $card['isImage'] => 'bg-neutral-50 dark:bg-slate-800',
                    $card['isPdf'] => 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100/80',
                    $card['isDwg'] => 'bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100/80',
                    default => 'bg-neutral-50 dark:bg-slate-800',
                };
            @endphp
            <div
                class="rounded-xl border border-neutral-200 dark:border-slate-700 overflow-hidden transition-opacity"
                x-bind:class="isRemovedAt({{ $fileIndex }}) ? 'opacity-50 ring-2 ring-red-300 dark:ring-red-800' : ''"
            >
                @if($card['isImage'] && $card['url'])
                <button
                    type="button"
                    @click="previewAt({{ $fileIndex }})"
                    class="block w-full aspect-video {{ $previewClass }} cursor-zoom-in focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-inset"
                    aria-label="{{ $card['name'] }} önizle"
                >
                    <img src="{{ $card['url'] }}" alt="{{ $card['name'] }}" class="w-full h-full object-contain p-2 pointer-events-none">
                </button>
                @elseif($card['isPdf'] && $card['url'])
                <button
                    type="button"
                    @click="previewAt({{ $fileIndex }})"
                    class="flex w-full aspect-video items-center justify-center {{ $previewClass }} transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-inset"
                    aria-label="{{ $card['name'] }} PDF önizle"
                >
                    <div class="text-center px-4">
                        <svg class="w-10 h-10 mx-auto text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">PDF · Önizle</p>
                    </div>
                </button>
                @elseif($card['url'])
                <a href="{{ $card['url'] }}" target="_blank" rel="noopener" class="flex aspect-video items-center justify-center {{ $previewClass }} transition-colors">
                    <div class="text-center px-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">{{ $card['kindLabel'] }}</p>
                        <p class="mt-1 text-[11px] text-neutral-500">İndir / aç</p>
                    </div>
                </a>
                @else
                <div class="flex aspect-video items-center justify-center {{ $previewClass }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ $card['kindLabel'] }}</p>
                </div>
                @endif

                <div class="p-3 border-t border-neutral-100 dark:border-slate-700 space-y-2">
                    <p class="text-sm font-medium text-neutral-900 dark:text-white break-all">{{ $card['name'] }}</p>
                    <p class="text-[11px] text-neutral-500">{{ $card['kindLabel'] }}</p>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        @if($card['url'] && ! $card['isImage'] && ! $card['isPdf'])
                        <a href="{{ $card['url'] }}" target="_blank" rel="noopener" class="text-xs font-medium text-emerald-600 hover:underline">Aç</a>
                        @endif
                        @if($card['url'])
                        <button type="button" @click="previewAt({{ $fileIndex }})" class="text-xs font-medium text-emerald-600 hover:underline">Önizle</button>
                        @endif
                        <button
                            type="button"
                            @click="toggleRemoveAt({{ $fileIndex }})"
                            class="text-xs font-semibold"
                            x-bind:class="isRemovedAt({{ $fileIndex }}) ? 'text-neutral-600 hover:text-neutral-800' : 'text-red-600 hover:text-red-700'"
                            x-text="isRemovedAt({{ $fileIndex }}) ? 'Geri al' : 'Kaldır'"
                        ></button>
                    </div>
                    <input
                        type="checkbox"
                        name="remove_drawing_files[]"
                        value="{{ $card['path'] }}"
                        class="sr-only"
                        x-bind:checked="isRemovedAt({{ $fileIndex }})"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                    <p x-show="isRemovedAt({{ $fileIndex }})" x-cloak class="text-[11px] text-red-600 dark:text-red-400">Kaydedince silinecek</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div>
            <label class="form-label">Yeni dosya ekle</label>
            <input
                type="file"
                name="drawing_files[]"
                multiple
                accept=".pdf,.dwg,image/jpeg,image/png,image/gif,image/webp,application/pdf"
                class="form-input py-2"
                id="drawingFilesInput"
            >
            <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">PDF, DWG, JPG, PNG, WEBP · dosya başına en fazla 10 MB · birden fazla dosya seçebilirsiniz</p>
            @error('drawing_files')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('drawing_files.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <div id="newDrawingFilesPreview" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 hidden"></div>
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
            <div class="absolute inset-0 bg-black/80" @click="closeLightbox()" aria-hidden="true"></div>
            <div class="relative z-10 flex w-full max-w-6xl max-h-full flex-col gap-3" @click.stop>
                <div class="flex items-center justify-between gap-3 text-white">
                    <p class="truncate text-sm font-medium" x-text="current()?.name || ''"></p>
                    <button type="button" @click="closeLightbox()" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 hover:bg-white/20" aria-label="Kapat">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="relative flex items-center justify-center min-h-0">
                    <img :src="current()?.url" :alt="current()?.name || 'Görsel'" class="max-h-[75vh] max-w-full object-contain rounded-lg shadow-2xl">
                </div>
            </div>
        </div>

        <div
            x-show="pdfOpen"
            x-cloak
            class="fixed inset-0 z-[80] flex flex-col p-3 sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-label="PDF önizleme"
        >
            <div class="absolute inset-0 bg-black/80" @click="closePdf()" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto flex w-full max-w-5xl flex-1 flex-col gap-2 min-h-0" @click.stop>
                <div class="flex items-center justify-between gap-3 text-white shrink-0">
                    <p class="truncate text-sm font-medium" x-text="pdfName"></p>
                    <div class="flex items-center gap-2 shrink-0">
                        <a :href="pdfUrl" target="_blank" rel="noopener" class="text-xs font-medium text-white/90 hover:text-white underline">Yeni sekmede aç</a>
                        <button type="button" @click="closePdf()" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 hover:bg-white/20" aria-label="Kapat">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>
                <iframe :src="pdfUrl" class="flex-1 w-full min-h-[60vh] rounded-lg bg-white border-0 shadow-2xl" title="PDF önizleme"></iframe>
            </div>
        </div>
    </template>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('drawingFilesInput');
    var preview = document.getElementById('newDrawingFilesPreview');
    if (!input || !preview) return;

    input.addEventListener('change', function () {
        preview.innerHTML = '';
        var files = input.files ? Array.from(input.files) : [];
        if (!files.length) {
            preview.classList.add('hidden');
            return;
        }
        preview.classList.remove('hidden');

        files.forEach(function (file) {
            var card = document.createElement('div');
            card.className = 'rounded-xl border border-dashed border-emerald-300 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20 overflow-hidden';

            var body = document.createElement('div');
            body.className = 'aspect-video flex items-center justify-center bg-white/80 dark:bg-neutral-900/50 p-2';

            var name = file.name || 'Dosya';
            var isImage = /^image\//.test(file.type);
            var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(name);

            if (isImage) {
                var img = document.createElement('img');
                img.className = 'max-h-full max-w-full object-contain';
                img.alt = name;
                var reader = new FileReader();
                reader.onload = function (e) { img.src = e.target.result; };
                reader.readAsDataURL(file);
                body.appendChild(img);
            } else if (isPdf) {
                body.innerHTML = '<div class="text-center"><p class="text-xs font-bold uppercase text-red-700 dark:text-red-300">PDF</p><p class="text-[11px] text-neutral-500 mt-1">Kayıt sonrası önizlenebilir</p></div>';
            } else {
                body.innerHTML = '<div class="text-center"><p class="text-xs font-bold uppercase text-neutral-600">Dosya</p></div>';
            }

            var foot = document.createElement('div');
            foot.className = 'p-2 border-t border-emerald-200/80 dark:border-emerald-900/50';
            foot.innerHTML = '<p class="text-xs font-medium break-all text-neutral-800 dark:text-neutral-200"></p><p class="text-[10px] text-emerald-700 dark:text-emerald-400 mt-0.5">Kaydedilince eklenecek</p>';
            foot.querySelector('p').textContent = name;

            card.appendChild(body);
            card.appendChild(foot);
            preview.appendChild(card);
        });
    });
});
</script>
@endpush
@endonce
