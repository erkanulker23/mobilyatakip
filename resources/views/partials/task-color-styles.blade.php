@php $taskColorTheme = \App\Support\UserTaskColor::THEME; @endphp
@push('head')
<style>
@foreach($taskColorTheme as $key => $t)
.task-color-card[data-task-color="{{ $key }}"] {
    background-color: {{ $t['bg'] }};
    border-color: {{ $t['border'] }};
}
.task-color-card[data-task-color="{{ $key }}"] .task-color-title {
    color: {{ $t['text'] }};
}
.task-color-chip[data-task-color="{{ $key }}"] {
    background-color: {{ $t['bg'] }};
    border-color: {{ $t['border'] }};
    color: {{ $t['text'] }};
}
.task-color-dot[data-task-color="{{ $key }}"] {
    background-color: {{ $t['dot'] }};
}
.dark .task-color-card[data-task-color="{{ $key }}"] {
    background-color: {{ $t['bgDark'] }};
    border-color: {{ $t['borderDark'] }};
}
.dark .task-color-card[data-task-color="{{ $key }}"] .task-color-title {
    color: {{ $t['textDark'] }};
}
.dark .task-color-chip[data-task-color="{{ $key }}"] {
    background-color: {{ $t['bgDark'] }};
    border-color: {{ $t['borderDark'] }};
    color: {{ $t['textDark'] }};
}
@endforeach
.task-color-dot {
    display: block;
    border-radius: 9999px;
    flex-shrink: 0;
    width: 1rem;
    height: 1rem;
}
.task-color-dot--sm {
    width: 0.5rem;
    height: 0.5rem;
}
</style>
@endpush
