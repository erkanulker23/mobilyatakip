@php
    $personalTasksView = $personalTasksView ?? false;
    $personalPersonnelId = $personalPersonnelId ?? null;
    $currentUserId = (string) auth()->id();
    $initialPersonnelFilter = $personalTasksView && $personalPersonnelId
        ? (string) $personalPersonnelId
        : request('personnelId', '');
    $taskColorPalette = \App\Support\UserTaskColor::PALETTE;
    $taskColorMapJson = collect(\App\Support\UserTaskColor::allowedKeys())->mapWithKeys(function ($key) {
        $c = \App\Support\UserTaskColor::classes($key);

        return [$key => [
            'label' => $c['label'],
            'hint' => $c['hint'] ?? '',
            'bg' => $c['bg'],
            'border' => $c['border'],
            'text' => $c['text'],
            'dot' => $c['dot'],
        ]];
    })->all();
    $taskPersonnelOptions = collect($taskPersonnel ?? [])->map(fn ($p) => [
        'id' => (string) $p->id,
        'name' => $p->name,
        'title' => $p->title,
        'photoUrl' => $p->photoUrl ? storage_url($p->photoUrl) : null,
        'label' => $p->name . ($p->title ? ' — ' . $p->title : ''),
    ])->values()->all();
@endphp

@include('partials.task-color-styles')

<div class="card overflow-hidden mb-8" id="yapilacaklar" x-data="dashboardTasks()" x-init="init()">
    <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <span class="text-base font-semibold">{{ $personalTasksView ? 'Yapılacaklarım' : 'Yapılacaklar' }}</span>
            <p class="text-xs font-normal text-neutral-500 mt-0.5">
                @if($personalTasksView)
                    Size atanmış görevlerinizi görüntüleyin ve tamamlayın
                @else
                    Tüm personelin görevlerini görüntüleyebilir, atayabilir ve düzenleyebilirsiniz
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @unless($personalTasksView)
            <select x-model="filterPersonnelId" @change="loadTasks()" class="form-select text-sm min-h-[36px] py-1.5 max-w-[220px]">
                <option value="">Tüm personel</option>
                @foreach($taskPersonnel ?? [] as $person)
                <option value="{{ $person->id }}">{{ $person->name }}@if($person->title) — {{ $person->title }}@endif</option>
                @endforeach
            </select>
            @endunless
            <button type="button" @click="openCreateModal()" class="btn-primary text-sm min-h-[36px] py-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Görev Ekle
            </button>
        </div>
    </div>

    <div class="p-5 space-y-8">
        {{-- Takvim --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <button type="button" @click="prevMonth()" class="icon-btn" aria-label="Önceki ay">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <div class="text-center">
                    <p class="font-semibold text-neutral-900 dark:text-neutral-100" x-text="monthLabel"></p>
                    <button type="button" @click="goToday()" class="text-xs text-emerald-600 hover:text-emerald-700 mt-0.5">Bugüne git</button>
                </div>
                <button type="button" @click="nextMonth()" class="icon-btn" aria-label="Sonraki ay">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 mb-1">
                <template x-for="label in weekdayLabels" :key="label">
                    <div class="text-center text-[11px] font-semibold uppercase text-neutral-400 py-1" x-text="label"></div>
                </template>
            </div>

            <div class="grid grid-cols-7 gap-1" x-show="!loading">
                <template x-for="cell in calendarCells" :key="cell.key">
                    <button type="button"
                        @click="cell.inMonth && selectDate(cell.date)"
                        :disabled="!cell.inMonth"
                        class="min-h-[72px] sm:min-h-[88px] rounded-xl border text-left p-1.5 transition-colors relative"
                        :class="{
                            'border-transparent bg-transparent opacity-30 cursor-default': !cell.inMonth,
                            'border-neutral-100 dark:border-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-900/50': cell.inMonth && selectedDate !== cell.date,
                            'border-neutral-900 dark:border-neutral-100 bg-neutral-50 dark:bg-neutral-900 ring-1 ring-neutral-900/10': cell.inMonth && selectedDate === cell.date,
                            'bg-emerald-50/50 dark:bg-emerald-950/20': cell.inMonth && cell.isToday && selectedDate !== cell.date,
                        }">
                        <span class="text-xs font-medium"
                            :class="cell.isToday ? 'text-emerald-700 dark:text-emerald-400' : 'text-neutral-700 dark:text-neutral-300'"
                            x-text="cell.day"></span>
                        <div class="mt-1 space-y-0.5 overflow-hidden max-h-[44px]">
                            <template x-for="task in cell.tasks.slice(0, 3)" :key="task.id">
                                <div class="task-color-chip text-[10px] leading-tight truncate px-1 py-0.5 rounded border"
                                    :data-task-color="normalizeTaskColor(task.color)"
                                    :class="task.isCompleted ? 'opacity-50 line-through' : ''"
                                    :title="taskCompletionTitle(task)">
                                    <span x-show="task.assigneeName" class="font-semibold" x-text="(task.assigneeName || '').split(' ')[0] + ': '"></span><span x-text="task.title"></span>
                                </div>
                            </template>
                            <p x-show="cell.tasks.length > 3" class="text-[10px] text-neutral-400 px-1" x-text="'+' + (cell.tasks.length - 3)"></p>
                        </div>
                    </button>
                </template>
            </div>
            <p x-show="loading" class="text-sm text-neutral-500 py-8 text-center">Yükleniyor...</p>
        </div>

        {{-- Seçili gün --}}
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/30 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100" x-text="selectedDateLabel"></h3>
                <button type="button" @click="openCreateModal()" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">+ Bu güne görev ekle</button>
            </div>
            <div class="flex gap-2 overflow-x-auto pb-1 min-h-[52px]">
                <template x-if="selectedDayTasks.length === 0 && !loading">
                    <p class="text-sm text-neutral-500 py-2">Bu gün için görev yok.</p>
                </template>
                <template x-for="task in selectedDayTasks" :key="'day-' + task.id">
                    <div class="task-color-card shrink-0 w-[min(100%,280px)] rounded-xl border p-3"
                        :data-task-color="normalizeTaskColor(task.color)">
                        <div class="flex items-start gap-2">
                            <input type="checkbox" :checked="task.isCompleted" @change="toggleComplete(task)" class="mt-1 rounded border-neutral-300">
                            <div class="min-w-0 flex-1">
                                <p class="task-color-title text-sm font-medium truncate" :class="task.isCompleted ? 'line-through opacity-60' : ''" x-text="task.title"></p>
                                <p class="text-[10px] font-semibold uppercase tracking-wide mt-1 opacity-70" x-text="priorityLabel(task.color)"></p>
                                <p class="text-[11px] text-neutral-500 mt-0.5 truncate" x-show="task.assigneeName && !task.isCompleted" x-text="task.assigneeName"></p>
                                <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-1" x-show="task.isCompleted" x-text="taskCompletionLabel(task)"></p>
                            </div>
                            <button type="button" @click="openEditTask(task)" class="p-1 rounded hover:bg-black/5 text-neutral-400 hover:text-neutral-700 shrink-0" title="Düzenle">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Personel satırları --}}
        <div>
            <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">{{ $personalTasksView ? 'Görevlerim' : 'Personel Görevleri' }}</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">{{ $personalTasksView ? 'Açık ve tamamlanan görevleriniz' : 'Her personelin açık görevleri ayrı satırda listelenir' }}</p>
                </div>
            </div>

            <div class="space-y-4" x-show="!loading">
                <template x-for="group in personnelTaskGroups" :key="group.id">
                    <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 overflow-hidden bg-white dark:bg-neutral-900/40">
                        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/80 dark:bg-neutral-900/60">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full shrink-0 overflow-hidden border border-neutral-200 dark:border-neutral-700 flex items-center justify-center bg-neutral-200 dark:bg-neutral-700">
                                    <img x-show="group.photoUrl" :src="group.photoUrl" :alt="group.name" class="w-full h-full object-cover">
                                    <span x-show="!group.photoUrl" class="text-sm font-semibold text-neutral-700 dark:text-neutral-200" x-text="personInitials(group.name)"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-neutral-900 dark:text-neutral-100 truncate" x-text="group.name"></p>
                                    <p class="text-xs text-neutral-500 truncate" x-show="group.title" x-text="group.title"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300" x-text="group.openTasks.length + ' açık'"></span>
                                <span class="text-xs px-2 py-1 rounded-full bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400" x-show="group.completedCount > 0" x-text="group.completedCount + ' tamam'"></span>
                                <button type="button" @click="openCreateModalForPersonnel(group.id)" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 whitespace-nowrap">+ Görev</button>
                            </div>
                        </div>

                        <div class="p-4">
                            <template x-if="group.openTasks.length === 0 && group.completedTasks.length === 0">
                                <p class="text-sm text-neutral-500 text-center py-4">Açık görev yok.</p>
                            </template>
                            <div class="space-y-2" x-show="group.openTasks.length > 0">
                                <template x-for="task in group.openTasks" :key="'person-' + group.id + '-' + task.id">
                                    <div class="task-color-card rounded-xl border p-3"
                                        :data-task-color="normalizeTaskColor(task.color)">
                                        <div class="flex items-start gap-3">
                                            <input type="checkbox" :checked="task.isCompleted" @change="toggleComplete(task)" class="mt-1 rounded border-neutral-300 shrink-0">
                                            <span class="task-color-dot task-color-dot--sm mt-1.5" :data-task-color="normalizeTaskColor(task.color)"></span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                    <p class="task-color-title text-sm font-medium" :class="task.isCompleted ? 'line-through opacity-60' : ''" x-text="task.title"></p>
                                                    <span class="text-[10px] font-semibold uppercase tracking-wide opacity-70 shrink-0" x-text="priorityLabel(task.color)"></span>
                                                </div>
                                                <p x-show="task.notes" class="text-xs text-neutral-500 mt-1 truncate" x-text="task.notes"></p>
                                                <p class="text-[11px] mt-1.5 font-medium"
                                                    :class="taskDueClass(task)"
                                                    x-text="taskDueLabel(task)"></p>
                                            </div>
                                            <div class="flex items-center gap-0.5 shrink-0">
                                                <button type="button" @click="openEditTask(task)" class="p-1 rounded hover:bg-black/5 text-neutral-400 hover:text-neutral-700" title="Düzenle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </button>
                                                <div class="relative" x-data="{ open: false }">
                                                    <button type="button" @click="open = !open" class="inline-flex items-center gap-1 px-1.5 py-1 rounded hover:bg-black/5 text-neutral-500" :title="priorityLabel(task.color)">
                                                        <span class="task-color-dot task-color-dot--sm" :data-task-color="normalizeTaskColor(task.color)"></span>
                                                        <span class="text-[10px] font-semibold max-w-[4.5rem] truncate" x-text="priorityLabel(task.color)"></span>
                                                    </button>
                                                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 z-10 mt-1 p-1.5 bg-white dark:bg-neutral-900 rounded-xl shadow-lg border border-neutral-200 dark:border-neutral-700 w-[9.5rem]">
                                                        @foreach($taskColorPalette as $key => $color)
                                                        <button type="button" @click='updateColor(task, @json($key)); open = false' class="flex w-full items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800 text-left">
                                                            <span class="w-3 h-3 rounded-full shrink-0 {{ $color['dot'] }}"></span>
                                                            <span class="text-xs font-medium text-neutral-800 dark:text-neutral-200">{{ $color['label'] }}</span>
                                                        </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <button type="button" @click="deleteTask(task)" class="p-1 rounded hover:bg-red-50 text-neutral-400 hover:text-red-600" title="Sil">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="space-y-2" x-show="group.completedTasks.length > 0" :class="group.openTasks.length > 0 ? 'mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800' : ''">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400 mb-2">Tamamlanan</p>
                                <template x-for="task in group.completedTasks" :key="'done-' + group.id + '-' + task.id">
                                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-3 bg-neutral-50/80 dark:bg-neutral-900/50 opacity-80">
                                        <div class="flex items-start gap-3">
                                            <input type="checkbox" :checked="true" @change="toggleComplete(task)" class="mt-1 rounded border-neutral-300 shrink-0">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400 line-through" x-text="task.title"></p>
                                                <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-1" x-text="taskCompletionLabel(task)"></p>
                                            </div>
                                            <button type="button" @click="openEditTask(task)" class="p-1 rounded hover:bg-black/5 text-neutral-400 hover:text-neutral-700 shrink-0" title="Düzenle">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Görev ekleme --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="task-create-title">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeCreateModal()"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-neutral-900 shadow-xl border border-neutral-200 dark:border-neutral-800 p-5 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 id="task-create-title" class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Yeni Görev</h3>
            <form @submit.prevent="createTask()" class="space-y-4">
                <div>
                    <label class="form-label">Görev *</label>
                    <input type="text" x-model="form.title" required maxlength="255" class="form-input" placeholder="Ne yapılacak?" autofocus>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Tarih</label>
                        <input type="date" x-model="form.dueDate" class="form-input">
                    </div>
                    @unless($personalTasksView)
                    <div>
                        <label class="form-label">Personel</label>
                        <select x-model="form.personnelId" class="form-select">
                            <option value="">Atanmadı</option>
                            <template x-for="person in personnelOptions" :key="person.id">
                                <option :value="person.id" x-text="person.label"></option>
                            </template>
                        </select>
                    </div>
                    @endunless
                </div>
                <div>
                    <label class="form-label">Öncelik</label>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        @foreach($taskColorPalette as $key => $color)
                        <button type="button"
                            @click='form.color = @json($key)'
                            :class='form.color === @json($key) ? "ring-2 {{ $color['ring'] }} border-neutral-900/20 dark:border-neutral-100/30 bg-neutral-50 dark:bg-neutral-800/80" : "border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900"'
                            class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border text-left transition-shadow">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0 {{ $color['dot'] }}"></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $color['label'] }}</span>
                                @if(!empty($color['hint']))
                                <span class="block text-[11px] text-neutral-500 dark:text-neutral-400 truncate">{{ $color['hint'] }}</span>
                                @endif
                            </span>
                        </button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="form-label">Not</label>
                    <textarea x-model="form.notes" rows="3" maxlength="2000" class="form-input" placeholder="İsteğe bağlı"></textarea>
                </div>
                <p x-show="formError" x-text="formError" class="text-sm text-red-600"></p>
                <div class="flex gap-2 justify-end pt-1">
                    <button type="button" @click="closeCreateModal()" class="btn-secondary">İptal</button>
                    <button type="submit" :disabled="saving" class="btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Görev düzenleme --}}
    <div x-show="editingTaskId" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="cancelEdit()"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-neutral-900 shadow-xl border border-neutral-200 dark:border-neutral-800 p-5 space-y-4">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Görevi Düzenle</h3>
            <div>
                <label class="form-label">Görev *</label>
                <input type="text" x-model="editForm.title" maxlength="255" class="form-input">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Tarih</label>
                    <input type="date" x-model="editForm.dueDate" class="form-input">
                </div>
                @unless($personalTasksView)
                <div>
                    <label class="form-label">Personel</label>
                    <select x-model="editForm.personnelId" class="form-select">
                        <option value="">Atanmadı</option>
                        <template x-for="person in personnelOptions" :key="person.id">
                            <option :value="person.id" x-text="person.label"></option>
                        </template>
                    </select>
                </div>
                @endunless
            </div>
            <div>
                <label class="form-label">Öncelik</label>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    @foreach($taskColorPalette as $key => $color)
                    <button type="button"
                        @click='editForm.color = @json($key)'
                        :class='editForm.color === @json($key) ? "ring-2 {{ $color['ring'] }} border-neutral-900/20 dark:border-neutral-100/30 bg-neutral-50 dark:bg-neutral-800/80" : "border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900"'
                        class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border text-left transition-shadow">
                        <span class="w-3.5 h-3.5 rounded-full shrink-0 {{ $color['dot'] }}"></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $color['label'] }}</span>
                            @if(!empty($color['hint']))
                            <span class="block text-[11px] text-neutral-500 dark:text-neutral-400 truncate">{{ $color['hint'] }}</span>
                            @endif
                        </span>
                    </button>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="form-label">Not</label>
                <textarea x-model="editForm.notes" rows="3" maxlength="2000" class="form-input" placeholder="İsteğe bağlı"></textarea>
            </div>
            <div class="flex gap-2 justify-end pt-1">
                <button type="button" @click="cancelEdit()" class="btn-secondary">İptal</button>
                <button type="button" @click="saveEditTask()" :disabled="saving" class="btn-primary">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardTasks() {
    const colorMap = @json($taskColorMapJson);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const apiIndex = @json(route('api.user-tasks.index'));
    const apiStore = @json(route('api.user-tasks.store'));
    const apiUpdateBase = @json(url('/api/user-tasks'));
    const currentUserId = @json($currentUserId);
    const personnelOptions = @json($taskPersonnelOptions);
    const personalTasksView = @json($personalTasksView);
    const defaultPersonnelId = @json($personalTasksView && $personalPersonnelId ? (string) $personalPersonnelId : '');

    function localDateStr(date) {
        const d = date || new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    const today = localDateStr();

    return {
        tasks: [],
        loading: false,
        saving: false,
        showCreateModal: false,
        formError: '',
        filterPersonnelId: @json($initialPersonnelFilter),
        personalTasksView,
        defaultPersonnelId,
        currentMonth: today.slice(0, 7),
        selectedDate: today,
        editingTaskId: null,
        editingTask: null,
        editForm: { title: '', notes: '', dueDate: '', personnelId: '', color: 'blue' },
        personnelOptions,
        weekdayLabels: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'],
        form: { title: '', dueDate: today, color: 'blue', personnelId: '', notes: '' },

        get monthLabel() {
            const [y, m] = this.currentMonth.split('-').map(Number);
            const d = new Date(y, m - 1, 1);
            return d.toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' });
        },
        get selectedDateLabel() {
            if (!this.selectedDate) return 'Görevler';
            const d = new Date(this.selectedDate + 'T12:00:00');
            return d.toLocaleDateString('tr-TR', { weekday: 'long', day: 'numeric', month: 'long' });
        },
        get undatedTasks() {
            return this.tasks.filter(t => !t.dueDate && !t.isCompleted);
        },
        get personnelTaskGroups() {
            const sortOpenTasks = (list) => [...list].sort((a, b) => {
                const aOver = this.isOverdue(a.dueDate);
                const bOver = this.isOverdue(b.dueDate);
                if (aOver !== bOver) return aOver ? -1 : 1;
                if (!a.dueDate && !b.dueDate) return 0;
                if (!a.dueDate) return 1;
                if (!b.dueDate) return -1;
                return a.dueDate.localeCompare(b.dueDate);
            });

            const sortCompletedTasks = (list) => [...list]
                .filter(t => t.isCompleted)
                .sort((a, b) => (b.completedAt || '').localeCompare(a.completedAt || ''))
                .slice(0, 10);

            let people = this.personnelOptions;
            if (this.filterPersonnelId) {
                people = people.filter(p => p.id === this.filterPersonnelId);
            }

            const groups = people.map(p => ({
                ...p,
                openTasks: sortOpenTasks(this.tasks.filter(t => t.personnelId === p.id && !t.isCompleted)),
                completedTasks: sortCompletedTasks(this.tasks.filter(t => t.personnelId === p.id)),
                completedCount: this.tasks.filter(t => t.personnelId === p.id && t.isCompleted).length,
            }));

            if (!this.filterPersonnelId && !this.personalTasksView) {
                groups.push({
                    id: '__unassigned__',
                    name: 'Atanmamış',
                    title: 'Henüz personele atanmamış görevler',
                    photoUrl: null,
                    label: 'Atanmamış',
                    openTasks: sortOpenTasks(this.tasks.filter(t => !t.personnelId && !t.isCompleted)),
                    completedTasks: sortCompletedTasks(this.tasks.filter(t => !t.personnelId)),
                    completedCount: this.tasks.filter(t => !t.personnelId && t.isCompleted).length,
                });
            }

            if (this.personalTasksView && groups.length === 0) {
                groups.push({
                    id: '__mine__',
                    name: 'Görevlerim',
                    title: 'Size atanmış görevler',
                    photoUrl: null,
                    label: 'Görevlerim',
                    openTasks: sortOpenTasks(this.tasks.filter(t => !t.isCompleted)),
                    completedTasks: sortCompletedTasks(this.tasks.filter(t => t.isCompleted)),
                    completedCount: this.tasks.filter(t => t.isCompleted).length,
                });
            }

            return groups;
        },
        get selectedDayTasks() {
            return this.tasks.filter(t => t.dueDate === this.selectedDate);
        },
        get calendarCells() {
            const [y, m] = this.currentMonth.split('-').map(Number);
            const first = new Date(y, m - 1, 1);
            let startOffset = first.getDay() - 1;
            if (startOffset < 0) startOffset = 6;
            const cells = [];
            const start = new Date(first);
            start.setDate(start.getDate() - startOffset);
            for (let i = 0; i < 42; i++) {
                const d = new Date(start);
                d.setDate(start.getDate() + i);
                const dateStr = this.formatDateLocal(d);
                const inMonth = d.getMonth() === m - 1;
                cells.push({
                    key: dateStr,
                    date: dateStr,
                    day: d.getDate(),
                    inMonth,
                    isToday: dateStr === today,
                    tasks: inMonth ? this.tasks.filter(t => t.dueDate === dateStr) : [],
                });
            }
            return cells;
        },

        formatDateLocal(d) {
            return localDateStr(d);
        },

        formatTaskDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T12:00:00');
            return d.toLocaleDateString('tr-TR', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatCompletionDate(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return '';
            return d.toLocaleString('tr-TR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        taskCompletionLabel(task) {
            if (!task?.isCompleted) return '';
            const when = this.formatCompletionDate(task.completedAt);
            const who = task.completedByName;
            if (who && when) return who + ' tarafından tamamlandı · ' + when;
            if (who) return who + ' tarafından tamamlandı';
            if (when) return 'Tamamlayan bilinmiyor · ' + when;
            return 'Tamamlandı';
        },

        taskCompletionTitle(task) {
            let title = task.assigneeName ? task.assigneeName + ': ' + task.title : task.title;
            if (task.isCompleted) {
                const completion = this.taskCompletionLabel(task);
                if (completion) title += ' — ' + completion;
            }
            return title;
        },

        colorClasses(color) {
            return colorMap[color] || colorMap.blue;
        },

        normalizeTaskColor(color) {
            return Object.prototype.hasOwnProperty.call(colorMap, color) ? color : 'blue';
        },

        priorityLabel(color) {
            return this.colorClasses(this.normalizeTaskColor(color)).label || 'Normal';
        },

        personInitials(name) {
            if (!name) return '?';
            return name.trim().split(/\s+/).slice(0, 2).map(w => w[0]?.toUpperCase() || '').join('');
        },

        isOverdue(dateStr) {
            return !!dateStr && dateStr < today;
        },

        taskDueLabel(task) {
            if (!task.dueDate) return 'Tarih yok';
            if (task.dueDate === today) return 'Bugün';
            if (this.isOverdue(task.dueDate)) return 'Gecikti · ' + this.formatTaskDate(task.dueDate);
            return this.formatTaskDate(task.dueDate);
        },

        taskDueClass(task) {
            if (!task.dueDate) return 'text-neutral-400';
            if (this.isOverdue(task.dueDate)) return 'text-red-600 dark:text-red-400';
            if (task.dueDate === today) return 'text-emerald-600 dark:text-emerald-400';
            return 'text-neutral-500';
        },

        init() {
            this.loadTasks();
        },

        async loadTasks() {
            this.loading = true;
            this.formError = '';
            try {
                const url = new URL(apiIndex, window.location.origin);
                url.searchParams.set('month', this.currentMonth);
                if (this.filterPersonnelId) url.searchParams.set('personnelId', this.filterPersonnelId);
                const res = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Yüklenemedi');
                this.tasks = data.tasks || [];
            } catch (e) {
                this.formError = e.message || 'Görevler yüklenemedi';
            }
            this.loading = false;
        },

        selectDate(date) {
            this.selectedDate = date;
        },

        prevMonth() {
            const [y, m] = this.currentMonth.split('-').map(Number);
            const d = new Date(y, m - 2, 1);
            this.currentMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            this.loadTasks();
        },

        nextMonth() {
            const [y, m] = this.currentMonth.split('-').map(Number);
            const d = new Date(y, m, 1);
            this.currentMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            this.loadTasks();
        },

        goToday() {
            this.currentMonth = today.slice(0, 7);
            this.selectedDate = today;
            this.loadTasks();
        },

        openCreateModal() {
            this.formError = '';
            this.form = {
                title: '',
                dueDate: this.selectedDate || today,
                color: 'blue',
                personnelId: this.filterPersonnelId || this.defaultPersonnelId || '',
                notes: '',
            };
            this.showCreateModal = true;
        },

        openCreateModalForPersonnel(personnelId) {
            this.formError = '';
            this.form = {
                title: '',
                dueDate: this.selectedDate || today,
                color: 'blue',
                personnelId: personnelId === '__unassigned__' ? '' : personnelId,
                notes: '',
            };
            this.showCreateModal = true;
        },

        closeCreateModal() {
            this.showCreateModal = false;
            this.formError = '';
        },

        async createTask() {
            this.saving = true;
            this.formError = '';
            try {
                const title = (this.form.title || '').trim();
                if (!title) {
                    this.formError = 'Görev başlığı zorunludur.';
                    this.saving = false;
                    return;
                }
                const body = {
                    title,
                    dueDate: this.form.dueDate || null,
                    color: this.form.color,
                    notes: this.form.notes || null,
                };
                if (this.form.personnelId) body.personnelId = this.form.personnelId;
                const res = await fetch(apiStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Kaydedilemedi');
                if (data.task?.dueDate) {
                    this.currentMonth = data.task.dueDate.slice(0, 7);
                    this.selectedDate = data.task.dueDate;
                }
                await this.loadTasks();
                this.closeCreateModal();
            } catch (e) {
                this.formError = e.message || 'Görev eklenemedi';
            }
            this.saving = false;
        },

        async toggleComplete(task) {
            await this.patchTask(task, { isCompleted: !task.isCompleted });
        },

        async updateColor(task, color) {
            await this.patchTask(task, { color });
        },

        openEditTask(task) {
            this.editingTaskId = task.id;
            this.editingTask = task;
            this.editForm = {
                title: task.title || '',
                notes: task.notes || '',
                dueDate: task.dueDate || '',
                personnelId: task.personnelId || '',
                color: task.color || 'blue',
            };
        },

        cancelEdit() {
            this.editingTaskId = null;
            this.editingTask = null;
            this.editForm = { title: '', notes: '', dueDate: '', personnelId: '', color: 'blue' };
        },

        async saveEditTask() {
            if (!this.editingTask) return;
            const title = (this.editForm.title || '').trim();
            if (!title) {
                alert('Görev başlığı zorunludur.');
                return;
            }
            await this.patchTask(this.editingTask, {
                title,
                notes: this.editForm.notes || null,
                dueDate: this.editForm.dueDate || null,
                personnelId: this.editForm.personnelId || null,
                color: this.editForm.color,
            });
            this.cancelEdit();
        },

        async patchTask(task, payload) {
            try {
                const res = await fetch(apiUpdateBase + '/' + task.id, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Güncellenemedi');
                const idx = this.tasks.findIndex(t => t.id === task.id);
                if (idx !== -1) {
                    this.tasks = this.tasks.map((t, i) => (i === idx ? data.task : t));
                }
            } catch (e) {
                alert(e.message || 'İşlem başarısız');
                this.loadTasks();
            }
        },

        async deleteTask(task) {
            if (!confirm('Bu görevi silmek istediğinize emin misiniz?')) return;
            try {
                const res = await fetch(apiUpdateBase + '/' + task.id, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) {
                    const data = await res.json();
                    throw new Error(data.message || 'Silinemedi');
                }
                this.tasks = this.tasks.filter(t => t.id !== task.id);
            } catch (e) {
                alert(e.message || 'Silinemedi');
            }
        },
    };
}
</script>
