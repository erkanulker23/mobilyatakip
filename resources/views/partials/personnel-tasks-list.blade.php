@if($personnel->hasSystemAccess() && ($personnelTasks ?? collect())->isNotEmpty())
@php
    $openTasks = $personnelTasks->where('isCompleted', false);
    $doneTasks = $personnelTasks->where('isCompleted', true);
    $tasksCalendarUrl = route('tasks.index', ['personnelId' => $personnel->id]);
@endphp
<div class="card overflow-hidden mb-6 w-full">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-slate-700 bg-neutral-50/80 dark:bg-slate-800/40 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Yapılacaklar Listesi</h2>
            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-1">
                {{ $personnel->name }} — {{ $openTasks->count() }} bekleyen, {{ $doneTasks->count() }} tamamlanan görev
            </p>
        </div>
        <a href="{{ $tasksCalendarUrl }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 shrink-0">
            Takvimde aç →
        </a>
    </div>
    <div class="p-6 space-y-6">
        @if($openTasks->isNotEmpty())
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-slate-400 mb-3">Bekleyen görevler</h3>
            <div class="space-y-2">
                @foreach($openTasks as $task)
                @php $color = \App\Support\UserTaskColor::classes($task->color); @endphp
                @php
                    $isOverdue = $task->dueDate && $task->dueDate->isPast() && ! $task->dueDate->isToday();
                @endphp
                <div class="rounded-xl border p-4 {{ $color['bg'] }} {{ $color['border'] }}">
                    <div class="flex items-start gap-3">
                        <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 {{ $color['dot'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium {{ $color['text'] }}">{{ $task->title }}</p>
                            @if($task->notes)
                            <p class="text-xs text-neutral-600 dark:text-slate-400 mt-1 whitespace-pre-wrap">{{ $task->notes }}</p>
                            @endif
                            @if($task->dueDate)
                            <p class="text-xs mt-2 {{ $isOverdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-neutral-500 dark:text-slate-400' }}">
                                @if($isOverdue)
                                    Gecikti · {{ $task->dueDate->format('d.m.Y') }}
                                @elseif($task->dueDate->isToday())
                                    Bugün
                                @else
                                    {{ $task->dueDate->locale('tr')->isoFormat('D MMMM YYYY') }}
                                @endif
                            </p>
                            @else
                            <p class="text-xs text-neutral-400 mt-2">Tarih yok</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($doneTasks->isNotEmpty())
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-slate-400 mb-3">Tamamlanan görevler</h3>
            <div class="space-y-2">
                @foreach($doneTasks->take(10) as $task)
                @php $color = \App\Support\UserTaskColor::classes($task->color); @endphp
                <div class="rounded-xl border border-neutral-200 dark:border-slate-700 p-3 bg-white/60 dark:bg-slate-900/30 opacity-75">
                    <div class="flex items-start gap-3">
                        <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $color['dot'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-neutral-600 dark:text-slate-400 line-through">{{ $task->title }}</p>
                            @if($task->completedAt)
                            <p class="text-[11px] text-neutral-400 mt-1">Tamamlandı · {{ $task->completedAt->format('d.m.Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
                @if($doneTasks->count() > 10)
                <p class="text-xs text-neutral-500 text-center pt-1">+ {{ $doneTasks->count() - 10 }} tamamlanan görev daha</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endif
