@props(['maxWidth' => 'max-w-2xl'])
<div {{ $attributes->merge(['class' => 'card p-6 ' . $maxWidth]) }}>
    {{ $slot }}
</div>
