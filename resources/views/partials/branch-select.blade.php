@php
    $field = $name ?? 'branchId';
    $selected = old($field, $selectedBranchId ?? null);
    $inputClass = $class ?? 'form-select';
    $emptyLabel = $emptyLabel ?? 'Şube seçilmedi';
    $hint = $hint ?? null;
@endphp
<div>
    <label class="form-label" @if(!empty($id)) for="{{ $id }}" @endif>Şube</label>
    <select name="{{ $field }}" @if(!empty($id)) id="{{ $id }}" @endif data-personnel-branch-target class="{{ $inputClass }}">
        <option value="">{{ $emptyLabel }}</option>
        @foreach($branches ?? [] as $branch)
        <option value="{{ $branch->id }}" {{ (string) $selected === (string) $branch->id ? 'selected' : '' }}>
            {{ $branch->displayName() }}
        </option>
        @endforeach
    </select>
    @error($field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @if(($branches ?? collect())->isEmpty())
        <p class="mt-1 text-xs text-neutral-500">Henüz şube yok. <a href="{{ route('branches.create') }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">Şube ekle</a></p>
    @elseif($hint)
        <p class="mt-1 text-xs text-neutral-500">{{ $hint }}</p>
    @endif
</div>
