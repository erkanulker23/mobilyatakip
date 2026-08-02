@props([
    'colspan' => 1,
    'message' => 'Kayıt bulunamadı.',
    'actionUrl' => null,
    'actionLabel' => null,
])
<tr>
    <td colspan="{{ $colspan }}" class="px-6 py-16 text-center">
        <p class="text-neutral-500 text-sm">{{ $message }}</p>
        @if($actionUrl && $actionLabel)
            <a href="{{ $actionUrl }}" class="btn-primary mt-4 inline-flex">{{ $actionLabel }}</a>
        @endif
    </td>
</tr>
