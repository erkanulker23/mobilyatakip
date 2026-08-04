@extends('layouts.app')
@section('title', 'Yeni Gider')
@section('content')
<div
    x-data="{
        amount: @js(old('amount') !== null && old('amount') !== '' ? money(money_parse(old('amount'))) : ''),
        expenseDate: @js(old('expenseDate', date('Y-m-d'))),
        description: @js(old('description', '')),
        category: @js(old('category', '')),
        kasaId: @js(old('kasaId', '')),
        kasaNames: @js($kasalar->pluck('name', 'id')),
        parseAmount(raw) {
            if (typeof window.parseMoney === 'function') {
                const v = window.parseMoney(raw);
                return isNaN(v) ? 0 : v;
            }
            const t = String(raw || '').replace(/\./g, '').replace(',', '.');
            return parseFloat(t) || 0;
        },
        get amountNumber() { return this.parseAmount(this.amount); },
        get amountFormatted() {
            if (this.amountNumber <= 0) return '—';
            return (typeof window.fmtMoney === 'function' ? window.fmtMoney(this.amountNumber) : this.amountNumber.toLocaleString('tr-TR')) + ' ₺';
        },
        get kasaLabel() {
            if (!this.kasaId) return 'Kasa seçilmedi';
            return this.kasaNames[this.kasaId] || 'Seçili kasa';
        },
        get categoryLabel() {
            return this.category.trim() !== '' ? this.category : 'Kategori seçilmedi';
        },
        get canSubmit() {
            return this.amountNumber > 0 && this.description.trim() !== '' && this.expenseDate !== '';
        },
        selectCategory(name) {
            this.category = this.category === name ? '' : name;
        },
        syncAmountFromInput(e) {
            this.amount = e.target.value;
        }
    }"
    x-init="
        const amountEl = document.getElementById('expenseAmount');
        if (amountEl) {
            amountEl.addEventListener('input', (e) => syncAmountFromInput(e));
            amountEl.addEventListener('change', (e) => syncAmountFromInput(e));
        }
    "
>
    {{-- Üst başlık --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm text-neutral-500 dark:text-slate-400 mb-2" aria-label="Breadcrumb">
            <a href="{{ route('expenses.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Giderler</a>
            <span>/</span>
            <span class="text-neutral-700 dark:text-slate-300 font-medium">Yeni gider</span>
        </nav>
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <h1 class="page-title">Yeni Gider Kaydı</h1>
                <p class="page-desc max-w-2xl">Şirket harcamasını kaydedin. Kasa seçerseniz otomatik çıkış hareketi oluşur; seçmezseniz yalnızca gider defterine yazılır.</p>
            </div>
            <a href="{{ route('expenses.index') }}" class="btn-secondary shrink-0">← Gider listesi</a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Form --}}
        <div class="xl:col-span-2">
            <form method="POST" action="{{ route('expenses.store') }}" id="expenseForm" class="space-y-5">
                @csrf

                {{-- 1. Tutar --}}
                <div class="card p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 text-sm font-bold">1</span>
                        <div>
                            <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Ne kadar harcandı?</h2>
                            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-0.5">Gider tutarını girin. <span class="whitespace-nowrap">23000</span> veya <span class="whitespace-nowrap">23.000</span> yazabilirsiniz.</p>
                        </div>
                    </div>
                    <div class="max-w-md">
                        <label class="form-label" for="expenseAmount">Tutar (₺) *</label>
                        <div class="relative">
                            <input
                                type="text"
                                inputmode="decimal"
                                name="amount"
                                id="expenseAmount"
                                required
                                x-model="amount"
                                class="form-input money-input text-2xl font-bold tabular-nums pr-12 min-h-[52px]"
                                placeholder="0"
                                autocomplete="off"
                            >
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-neutral-400 font-semibold text-lg">₺</span>
                        </div>
                        @error('amount')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- 2. Tarih & Kasa --}}
                <div class="card p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 text-sm font-bold">2</span>
                        <div>
                            <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Ne zaman ve hangi kasadan?</h2>
                            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-0.5">Gider tarihi zorunlu. Kasa opsiyonel — seçilirse kasadan düşülür.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="form-label" for="expenseDate">Gider tarihi *</label>
                            <input type="date" name="expenseDate" id="expenseDate" required x-model="expenseDate" max="{{ date('Y-m-d') }}" class="form-input min-h-[44px]">
                            @error('expenseDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="expenseKasa">Ödeme yapılan kasa</label>
                            <select name="kasaId" id="expenseKasa" x-model="kasaId" class="form-select min-h-[44px]">
                                <option value="">Kasa kullanılmadı</option>
                                @foreach($kasalar as $k)
                                <option value="{{ $k->id }}" {{ old('kasaId') == $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-neutral-500 dark:text-slate-400">Nakit/banka ödemesi yaptıysanız ilgili kasayı seçin.</p>
                        </div>
                    </div>
                </div>

                {{-- 3. Açıklama --}}
                <div class="card p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300 text-sm font-bold">3</span>
                        <div>
                            <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Ne için harcandı?</h2>
                            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-0.5">Fatura, fiş veya kısa açıklama yazın — raporlarda bu metin görünür.</p>
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="expenseDescription">Açıklama *</label>
                        <textarea
                            name="description"
                            id="expenseDescription"
                            rows="3"
                            required
                            x-model="description"
                            class="form-textarea"
                            placeholder="Örn: Ocak ayı kira ödemesi, Atölye elektrik faturası, Kırtasiye alımı..."
                        >{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- 4. Kategori --}}
                <div class="card p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300 text-sm font-bold">4</span>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">Kategori</h2>
                            <p class="text-sm text-neutral-500 dark:text-slate-400 mt-0.5">Hazır kategorilerden birini seçin veya kendi kategorinizi yazın. (Opsiyonel)</p>
                        </div>
                    </div>

                    <input type="hidden" name="category" :value="category">

                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($categories as $c)
                        <button
                            type="button"
                            @click="selectCategory(@js($c))"
                            class="px-3.5 py-2 rounded-xl border text-sm font-medium transition-all"
                            :class="category === @js($c)
                                ? 'border-violet-600 bg-violet-600 text-white shadow-sm dark:border-violet-500 dark:bg-violet-600'
                                : 'border-neutral-200 bg-white text-neutral-700 hover:border-violet-300 hover:bg-violet-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:border-violet-700'"
                        >
                            {{ $c }}
                        </button>
                        @endforeach
                    </div>

                    <div class="max-w-sm">
                        <label class="form-label text-xs">veya özel kategori yazın</label>
                        <input
                            type="text"
                            x-model="category"
                            class="form-input text-sm"
                            placeholder="Örn: Yemek, Yakıt..."
                            maxlength="100"
                        >
                    </div>
                    <p x-show="category" x-cloak class="mt-3 text-sm text-violet-700 dark:text-violet-300">
                        Seçili kategori: <strong x-text="category"></strong>
                        <button type="button" @click="category = ''" class="ml-2 text-xs underline opacity-80 hover:opacity-100">Temizle</button>
                    </p>
                </div>

                {{-- Mobil kaydet --}}
                <div class="xl:hidden flex gap-3">
                    <button type="submit" class="btn-primary flex-1 py-3">Gideri Kaydet</button>
                    <a href="{{ route('expenses.index') }}" class="btn-secondary py-3">İptal</a>
                </div>
            </form>
        </div>

        {{-- Özet paneli --}}
        <div class="xl:col-span-1">
            <div class="card p-6 xl:sticky xl:top-4 space-y-5">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-neutral-500 dark:text-slate-400">Kayıt özeti</h2>
                    <p class="text-xs text-neutral-400 dark:text-slate-500 mt-1">Kaydetmeden önce kontrol edin</p>
                </div>

                <div class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900/50 p-4 text-center">
                    <p class="text-xs font-medium uppercase tracking-wide text-red-600/80 dark:text-red-400/80">Gider tutarı</p>
                    <p class="text-3xl font-bold text-red-700 dark:text-red-300 tabular-nums mt-1" x-text="amountFormatted">—</p>
                </div>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3 py-2 border-b border-neutral-100 dark:border-neutral-800">
                        <dt class="text-neutral-500 shrink-0">Tarih</dt>
                        <dd class="font-medium text-neutral-900 dark:text-neutral-100 text-right" x-text="expenseDate ? new Date(expenseDate + 'T12:00:00').toLocaleDateString('tr-TR') : '—'">—</dd>
                    </div>
                    <div class="flex justify-between gap-3 py-2 border-b border-neutral-100 dark:border-neutral-800">
                        <dt class="text-neutral-500 shrink-0">Kategori</dt>
                        <dd class="font-medium text-neutral-900 dark:text-neutral-100 text-right truncate" x-text="categoryLabel">—</dd>
                    </div>
                    <div class="flex justify-between gap-3 py-2 border-b border-neutral-100 dark:border-neutral-800">
                        <dt class="text-neutral-500 shrink-0">Kasa</dt>
                        <dd class="font-medium text-right" :class="kasaId ? 'text-emerald-700 dark:text-emerald-400' : 'text-neutral-400'" x-text="kasaLabel">—</dd>
                    </div>
                    <div class="pt-1">
                        <dt class="text-neutral-500 mb-1">Açıklama</dt>
                        <dd class="text-neutral-800 dark:text-neutral-200 text-sm leading-relaxed whitespace-pre-wrap break-words" x-text="description.trim() || 'Henüz yazılmadı'">—</dd>
                    </div>
                </dl>

                <div class="rounded-lg bg-neutral-50 dark:bg-neutral-900/60 border border-neutral-200 dark:border-neutral-700 p-3 text-xs text-neutral-600 dark:text-neutral-400 leading-relaxed">
                    <template x-if="kasaId">
                        <p><strong class="text-neutral-800 dark:text-neutral-200">Kasa seçildi:</strong> Kayıt sonrası seçilen kasadan <span x-text="amountFormatted"></span> çıkış hareketi oluşturulur.</p>
                    </template>
                    <template x-if="!kasaId">
                        <p><strong class="text-neutral-800 dark:text-neutral-200">Kasa seçilmedi:</strong> Gider yalnızca gider listesine eklenir; kasa bakiyesi değişmez.</p>
                    </template>
                </div>

                <div class="hidden xl:flex flex-col gap-2 pt-1">
                    <button type="submit" form="expenseForm" class="btn-primary w-full py-3 text-base">
                        Gideri Kaydet
                    </button>
                    <a href="{{ route('expenses.index') }}" class="btn-secondary w-full justify-center py-2.5">Vazgeç</a>
                </div>

                <p x-show="!canSubmit" x-cloak class="text-xs text-amber-700 dark:text-amber-400 text-center">
                    Kaydetmek için tutar, tarih ve açıklama doldurulmalı.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
