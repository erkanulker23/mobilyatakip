@php
    $entries = \App\Support\DrawingFiles::existingEntries(
        \App\Support\DrawingFiles::entries($drawingFiles ?? [])
    );
@endphp
<div class="sale-form-section">
    <div class="sale-form-section-head">
        <h2 class="sale-form-section-title">Çizim Dosyaları</h2>
        <span class="text-xs text-neutral-500">{{ count($entries) }} dosya</span>
    </div>
    <div class="sale-form-section-body space-y-4">
        @if(count($entries) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($entries as $entry)
            @php $fileUrl = \App\Support\DrawingFiles::url($entry['path']); @endphp
            <div class="rounded-xl border border-neutral-200 dark:border-slate-700 p-3 flex gap-3 items-start">
                @if(\App\Support\DrawingFiles::isImage($entry) && $fileUrl)
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="shrink-0">
                    <img src="{{ $fileUrl }}" alt="" class="w-16 h-16 object-cover rounded-lg border border-neutral-200">
                </a>
                @else
                <div class="w-16 h-16 shrink-0 rounded-lg border border-neutral-200 flex items-center justify-center
                    @if(\App\Support\DrawingFiles::isDwg($entry)) bg-sky-50 dark:bg-sky-900/20
                    @elseif(\App\Support\DrawingFiles::isPdf($entry)) bg-red-50 dark:bg-red-900/20
                    @else bg-neutral-50 dark:bg-neutral-800 @endif">
                    <span class="text-[10px] font-bold
                        @if(\App\Support\DrawingFiles::isDwg($entry)) text-sky-700 dark:text-sky-300
                        @elseif(\App\Support\DrawingFiles::isPdf($entry)) text-red-700 dark:text-red-300
                        @else text-neutral-600 dark:text-neutral-300 @endif">{{ \App\Support\DrawingFiles::kindLabel($entry) }}</span>
                </div>
                @endif
                <div class="min-w-0 flex-1">
                    @if($fileUrl)
                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="text-sm font-medium text-neutral-900 dark:text-white hover:text-emerald-600 break-all">{{ $entry['name'] }}</a>
                    @else
                    <p class="text-sm font-medium text-neutral-900 dark:text-white break-all">{{ $entry['name'] }}</p>
                    @endif
                    <label class="inline-flex items-center gap-1.5 mt-2 text-xs text-red-600 cursor-pointer">
                        <input type="checkbox" name="remove_drawing_files[]" value="{{ $entry['path'] }}" class="rounded border-neutral-300 text-red-600 focus:ring-red-500">
                        Dosyayı kaldır
                    </label>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div>
            <label class="form-label">Yeni dosya ekle</label>
            <input type="file" name="drawing_files[]" multiple accept=".pdf,.dwg,image/jpeg,image/png,image/gif,image/webp,application/pdf" class="form-input py-2">
            <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">PDF, DWG, JPG, PNG, WEBP · dosya başına en fazla 10 MB · birden fazla dosya seçebilirsiniz</p>
            @error('drawing_files')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('drawing_files.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
