@php
    $netClass = ($netNakit ?? 0) >= 0
        ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-red-600 dark:text-red-400';
    $karClass = ($donemKar ?? 0) >= 0
        ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-red-600 dark:text-red-400';
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
    <div class="card p-5">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider">Satış Hasılatı</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-white mt-1 tabular-nums">{{ number_format($gelir ?? 0, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-500 dark:text-slate-400 mt-1">{{ $salesCount ?? 0 }} sipariş · tahakkuk</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider">Tahsilat</p>
        <p class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400 mt-1 tabular-nums">{{ number_format($tahsilat ?? 0, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-500 dark:text-slate-400 mt-1">
            {{ $payments->count() ?? 0 }} hareket
            @if($tahsilatOrani !== null)· satışın %{{ number_format($tahsilatOrani, 1, ',', '.') }}'i @endif
        </p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider">Toplam Çıkış</p>
        <p class="text-2xl font-semibold text-red-600 dark:text-red-400 mt-1 tabular-nums">− {{ number_format($toplamCikis ?? 0, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-500 dark:text-slate-400 mt-1">Gider {{ number_format($gider ?? 0, 0, ',', '.') }} ₺ · Tedarikçi {{ number_format($tedarikciOdeme ?? 0, 0, ',', '.') }} ₺</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider">Net Nakit Akışı</p>
        <p class="text-2xl font-semibold {{ $netClass }} mt-1 tabular-nums">{{ number_format($netNakit ?? 0, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-500 dark:text-slate-400 mt-1">Tahsilat − gider − tedarikçi ödemesi</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider">Alış Tutarı</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-white mt-1 tabular-nums">{{ number_format($alis ?? 0, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-500 dark:text-slate-400 mt-1">{{ $alisCount ?? 0 }} alış · dönem maliyeti</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-medium text-neutral-500 dark:text-slate-400 uppercase tracking-wider">Dönem Operasyon Sonucu</p>
        <p class="text-2xl font-semibold {{ $karClass }} mt-1 tabular-nums">{{ number_format($donemKar ?? 0, 0, ',', '.') }} ₺</p>
        <p class="text-xs text-neutral-500 dark:text-slate-400 mt-1">Hasılat − alış − gider (tahakkuk bazlı)</p>
    </div>
</div>
