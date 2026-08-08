@php
    $author = $stage->user;
    $avatarUrl = $author?->avatarDisplayUrl();
    $initials = $author?->initials() ?? 'K';
    $canModify = auth()->check() && (
        auth()->user()?->isAdmin()
        || ((string) auth()->id() === (string) $stage->userId && $stage->userId)
    );
@endphp
<div class="border rounded-xl p-4 border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-900/30" x-data="{ editing: false }">
    <div class="flex gap-3">
        <div class="shrink-0">
            @if($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="{{ $author?->name ?? 'Kullanıcı' }}" class="h-10 w-10 rounded-full object-cover border border-neutral-200 dark:border-neutral-700">
            @else
            <div class="h-10 w-10 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center text-xs font-semibold text-neutral-700 dark:text-neutral-200">
                {{ $initials }}
            </div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $author?->name ?? 'Kullanıcı' }}</p>
                    <p class="text-xs text-neutral-500">{{ $stage->actionDate?->format('d.m.Y H:i') ?? '—' }}</p>
                </div>
                @if($canModify)
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="editing = true" x-show="!editing" class="text-xs font-medium px-2.5 py-1.5 rounded-lg text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">
                        Düzenle
                    </button>
                    <form method="POST" action="{{ route('workshop.destroy-stage', $stage) }}" x-show="!editing" class="inline" onsubmit="return confirm('Bu notu silmek istediğinize emin misiniz?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">
                            Sil
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <p x-show="!editing" class="text-sm text-neutral-800 dark:text-neutral-200 whitespace-pre-wrap">{{ $stage->notes }}</p>

            @if($canModify)
            <form x-show="editing" x-cloak method="POST" action="{{ route('workshop.update-stage', $stage) }}" class="space-y-2">
                @csrf
                @method('PUT')
                <textarea name="notes" rows="3" required class="form-input form-textarea text-sm">{{ old('notes', $stage->notes) }}</textarea>
                @error('notes')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary text-sm px-3 py-1.5">Kaydet</button>
                    <button type="button" @click="editing = false" class="btn-secondary text-sm px-3 py-1.5">İptal</button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
