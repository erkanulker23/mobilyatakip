@php
    $isTaskAdmin = auth()->user()?->isAdmin() ?? false;
    $currentUserId = (string) auth()->id();
    $taskColorPalette = \App\Support\UserTaskColor::PALETTE;
    $taskColorMapJson = collect($taskColorPalette)->map(function ($c) {
        return [
            'bg' => $c['bg'],
            'border' => $c['border'],
            'text' => $c['text'],
            'dot' => $c['dot'],
        ];
    })->all();
@endphp

<div class="card overflow-hidden mb-8" id="yapilacaklar" x-data="dashboardTasks()" x-init="init()">
    <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <span class="text-base font-semibold">Yapılacaklar</span>
            <p class="text-xs font-normal text-neutral-500 mt-0.5">Kişisel görev listeniz ve takvim</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($isTaskAdmin)
            <select x-model="filterUserId" @change="loadTasks()" class="form-select text-sm min-h-[36px] py-1.5 max-w-[180px]">
                <option value="">Tüm kullanıcılar</option>
                @foreach($taskUsers ?? [] as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            @endif
            <button type="button" @click="showForm = !showForm" class="btn-primary text-sm min-h-[36px] py-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Görev Ekle
            </button>
        </div>
    </div>

    {{-- Yeni görev formu --}}
    <div x-show="showForm" x-cloak class="px-5 py-4 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/80 dark:bg-neutral-900/40">
        <form @submit.prevent="createTask()" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-4">
                <label class="form-label">Görev *</label>
                <input type="text" x-model="form.title" required maxlength="255" class="form-input" placeholder="Ne yapılacak?">
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Tarih</label>
                <input type="date" x-model="form.dueDate" class="form-input">
            </div>
            <div class="md:col-span-3">
                <label class="form-label">Renk</label>
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @foreach($taskColorPalette as $key => $color)
                    <button type="button"
                        @click="form.color = @json($key)"
                        :class="form.color === @json($key) ? 'ring-2 ring-offset-1 {{ $color['ring'] }}' : ''"
                        class="w-7 h-7 rounded-full {{ $color['dot'] }} border-2 border-white dark:border-neutral-900 shadow-sm"
                        title="{{ $color['label'] }}"></button>
                    @endforeach
                </div>
            </div>
            @if($isTaskAdmin)
            <div class="md:col-span-2">
                <label class="form-label">Kullanıcı</label>
                <select x-model="form.userId" class="form-select text-sm">
                    <option value="">Kendim</option>
                    @foreach($taskUsers ?? [] as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="md:col-span-1 flex gap-2">
                <button type="submit" :disabled="saving" class="btn-primary text-sm w-full justify-center">Ekle</button>
            </div>
        </form>
        <p x-show="formError" x-text="formError" class="mt-2 text-sm text-red-600"></p>
    </div>

    <div class="p-5 grid grid-cols-1 xl:grid-cols-5 gap-6">
        {{-- Takvim --}}
        <div class="xl:col-span-3">
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
                        class="min-h-[72px] sm:min-h-[80px] rounded-xl border text-left p-1.5 transition-colors relative"
                        :class="{
                            'border-transparent bg-transparent opacity-30 cursor-default': !cell.inMonth,
                            'border-neutral-100 dark:border-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-900/50': cell.inMonth && selectedDate !== cell.date,
                            'border-neutral-900 dark:border-neutral-100 bg-neutral-50 dark:bg-neutral-900 ring-1 ring-neutral-900/10': cell.inMonth && selectedDate === cell.date,
                            'bg-emerald-50/50 dark:bg-emerald-950/20': cell.inMonth && cell.isToday && selectedDate !== cell.date,
                        }">
                        <span class="text-xs font-medium"
                            :class="cell.isToday ? 'text-emerald-700 dark:text-emerald-400' : 'text-neutral-700 dark:text-neutral-300'"
                            x-text="cell.day"></span>
                        <div class="mt-1 space-y-0.5 overflow-hidden max-h-[40px]">
                            <template x-for="task in cell.tasks.slice(0, 3)" :key="task.id">
                                <div class="text-[10px] leading-tight truncate px-1 py-0.5 rounded border"
                                    :class="colorClasses(task.color).bg + ' ' + colorClasses(task.color).border + ' ' + colorClasses(task.color).text + (task.isCompleted ? ' opacity-50 line-through' : '')"
                                    x-text="task.title"></div>
                            </template>
                            <p x-show="cell.tasks.length > 3" class="text-[10px] text-neutral-400 px-1" x-text="'+' + (cell.tasks.length - 3)"></p>
                        </div>
                    </button>
                </template>
            </div>
            <p x-show="loading" class="text-sm text-neutral-500 py-8 text-center">Yükleniyor...</p>
        </div>

        {{-- Seçili gün listesi --}}
        <div class="xl:col-span-2 space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3" x-text="selectedDateLabel"></h3>
                <div class="space-y-2 min-h-[120px]">
                    <template x-if="selectedDayTasks.length === 0 && !loading">
                        <p class="text-sm text-neutral-500 py-6 text-center rounded-xl border border-dashed border-neutral-200 dark:border-neutral-700">Bu gün için görev yok.</p>
                    </template>
                    <template x-for="task in selectedDayTasks" :key="task.id">
                        <div class="rounded-xl border p-3"
                            :class="colorClasses(task.color).bg + ' ' + colorClasses(task.color).border">
                            <div class="flex items-start gap-2">
                                <input type="checkbox" :checked="task.isCompleted" @change="toggleComplete(task)" class="mt-1 rounded border-neutral-300">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium" :class="[colorClasses(task.color).text, task.isCompleted ? 'line-through opacity-60' : '']" x-text="task.title"></p>
                                    @if($isTaskAdmin)
                                    <p class="text-[11px] text-neutral-500 mt-0.5" x-show="task.userName" x-text="task.userName"></p>
                                    @endif
                                    <p x-show="task.notes" class="text-xs text-neutral-500 mt-1" x-text="task.notes"></p>
                                </div>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <div class="relative" x-data="{ open: false }">
                                        <button type="button" @click="open = !open" class="p-1 rounded hover:bg-black/5" title="Renk">
                                            <span class="block w-4 h-4 rounded-full" :class="colorClasses(task.color).dot"></span>
                                        </button>
                                        <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 z-10 mt-1 p-2 bg-white dark:bg-neutral-900 rounded-xl shadow-lg border border-neutral-200 dark:border-neutral-700 flex flex-wrap gap-1 w-[140px]">
                                            @foreach($taskColorPalette as $key => $color)
                                            <button type="button" @click="updateColor(task, @json($key)); open = false" class="w-6 h-6 rounded-full {{ $color['dot'] }}"></button>
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
            </div>

            <div x-show="undatedTasks.length > 0">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Tarihsiz görevler</h3>
                <div class="space-y-2">
                    <template x-for="task in undatedTasks" :key="'u-' + task.id">
                        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 p-3 bg-white dark:bg-neutral-900/40">
                            <div class="flex items-start gap-2">
                                <input type="checkbox" :checked="task.isCompleted" @change="toggleComplete(task)" class="mt-1 rounded">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="colorClasses(task.color).dot"></span>
                                        <p class="text-sm font-medium text-neutral-800 dark:text-neutral-200" :class="task.isCompleted ? 'line-through opacity-60' : ''" x-text="task.title"></p>
                                    </div>
                                    @if($isTaskAdmin)
                                    <p class="text-[11px] text-neutral-500 mt-0.5 ml-4" x-text="task.userName"></p>
                                    @endif
                                </div>
                                <button type="button" @click="deleteTask(task)" class="p-1 text-neutral-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
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
    const today = @json(now()->format('Y-m-d'));
    const currentUserId = @json($currentUserId);

    return {
        tasks: [],
        loading: false,
        saving: false,
        showForm: false,
        formError: '',
        filterUserId: '',
        currentMonth: today.slice(0, 7),
        selectedDate: today,
        weekdayLabels: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'],
        form: { title: '', dueDate: today, color: 'emerald', userId: '' },

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
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        },

        colorClasses(color) {
            return colorMap[color] || colorMap.emerald;
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
                if (this.filterUserId) url.searchParams.set('userId', this.filterUserId);
                const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
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

        async createTask() {
            this.saving = true;
            this.formError = '';
            try {
                const body = {
                    title: this.form.title,
                    dueDate: this.form.dueDate || null,
                    color: this.form.color,
                };
                if (this.form.userId) body.userId = this.form.userId;
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
                this.form.title = '';
                this.form.dueDate = this.selectedDate || today;
                this.showForm = false;
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
                if (idx !== -1) this.tasks[idx] = data.task;
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
