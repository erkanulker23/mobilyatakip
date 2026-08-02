@if(($paymentType ?? '') === 'havale' && ($kasa ?? null))
<div class="md:col-span-2 rounded-xl border border-sky-200 dark:border-sky-900/50 bg-sky-50/80 dark:bg-sky-950/30 p-4">
    <p class="text-[11px] font-semibold uppercase tracking-wider text-sky-700/80 dark:text-sky-300/80 mb-3">Havale Banka Bilgileri</p>
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        @if($kasa->bankName)
        <div>
            <dt class="form-label mb-0">Banka</dt>
            <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $kasa->bankName }}</dd>
        </div>
        @endif
        @if($kasa->iban)
        <div class="sm:col-span-2">
            <dt class="form-label mb-0">IBAN</dt>
            <dd class="font-mono text-base font-semibold tracking-wide text-slate-900 dark:text-white break-all">{{ $kasa->iban }}</dd>
        </div>
        @elseif($kasa->accountNumber)
        <div>
            <dt class="form-label mb-0">Hesap No</dt>
            <dd class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $kasa->accountNumber }}</dd>
        </div>
        @endif
    </dl>
</div>
@endif
