<div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="sale-payment-title">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPaymentModal = false"></div>
    <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 shadow-xl border border-neutral-200 dark:border-slate-700 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="px-5 pt-5 pb-1">
            <h2 id="sale-payment-title" class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Ödeme Al</h2>
            <p class="mt-1 text-sm text-neutral-500 dark:text-slate-400">{{ $sale->saleNumber }} numaralı sipariş için tahsilat kaydı</p>
        </div>
        <form method="POST" action="{{ route('customer-payments.store') }}" class="p-5 space-y-4" id="salePaymentForm">
            @csrf
            <input type="hidden" name="customerId" value="{{ $sale->customerId }}">
            <input type="hidden" name="saleId" value="{{ $sale->id }}">
            <input type="hidden" name="redirectToSale" value="{{ $sale->id }}">

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-neutral-200 dark:border-slate-600">
                <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-slate-400 mb-1">Müşteri</p>
                <p class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $sale->customer?->name ?? '—' }}</p>
                @if($sale->customer?->phone)
                <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">{{ $sale->customer->phone }}</p>
                @endif
            </div>

            <div class="p-4 rounded-xl border @php $salePaymentStatus = \App\Support\CustomerBalance::saleStatus($sale); @endphp {{ $salePaymentStatus['key'] === 'borclu' ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800' : ($salePaymentStatus['key'] === 'alacakli' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800') }}">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <span class="text-sm font-medium text-neutral-700 dark:text-slate-200">Sipariş durumu</span>
                    @include('partials.payment-status-badge', ['status' => $salePaymentStatus])
                </div>
                <div class="flex justify-between gap-4 text-sm">
                    <span class="text-slate-600 dark:text-slate-300">Genel Toplam</span>
                    <span class="font-medium">{{ number_format($sale->grandTotal, 0, ',', '.') }} ₺</span>
                </div>
                <div class="flex justify-between gap-4 text-sm mt-1">
                    <span class="text-slate-600 dark:text-slate-300">Ödenen</span>
                    <span class="font-medium">{{ number_format($sale->paidAmount ?? 0, 0, ',', '.') }} ₺</span>
                </div>
                <div class="flex justify-between gap-4 mt-2 pt-2 border-t border-current/10">
                    <span class="font-semibold text-neutral-700 dark:text-slate-200">{{ $salePaymentStatus['key'] === 'alacakli' ? 'Fazla ödeme' : 'Kalan' }}</span>
                    <span class="font-bold text-lg">{{ number_format(abs($saleRemaining), 0, ',', '.') }} ₺</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Tutar (₺) <span class="text-red-500">*</span></label>
                    <input type="text" inputmode="decimal" name="amount" id="salePaymentAmount" required
                        value="{{ old('redirectToSale') == $sale->id ? old('amount') : ($saleRemaining > 0 ? money($saleRemaining) : '') }}"
                        class="form-input min-h-[44px] money-input" placeholder="0" autocomplete="off" @if($saleRemaining <= 0) disabled @endif>
                    @if($saleRemaining <= 0)
                    <p class="mt-1 text-xs text-emerald-600">Bu sipariş tamamen ödenmiş.</p>
                    @endif
                </div>
                <div>
                    <label class="form-label">Tahsilat Tarihi <span class="text-red-500">*</span></label>
                    <input type="date" name="paymentDate" required value="{{ old('redirectToSale') == $sale->id ? old('paymentDate') : date('Y-m-d') }}" class="form-input min-h-[44px]" max="{{ date('Y-m-d') }}" @if($saleRemaining <= 0) disabled @endif>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Ödeme Tipi</label>
                    <select name="paymentType" class="form-select min-h-[44px]" id="salePaymentType" @if($saleRemaining <= 0) disabled @endif>
                        @php $oldPt = old('redirectToSale') == $sale->id ? old('paymentType') : 'nakit'; @endphp
                        @foreach(\App\Support\PaymentType::SELECTABLE as $value => $label)
                        <option value="{{ $value }}" {{ $oldPt == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    @include('partials.payment-kasa-field', [
                        'kasalar' => $kasalar,
                        'paymentTypeId' => 'salePaymentType',
                        'amountId' => 'salePaymentAmount',
                        'selected' => old('redirectToSale') == $sale->id ? old('kasaId') : '',
                        'disabled' => $saleRemaining <= 0,
                    ])
                </div>
            </div>

            <div>
                <label class="form-label">Referans / Açıklama</label>
                <input type="text" name="reference" value="{{ old('redirectToSale') == $sale->id ? old('reference') : '' }}" class="form-input min-h-[44px]" placeholder="Havale dekont no, çek no vb." @if($saleRemaining <= 0) disabled @endif>
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" @click="showPaymentModal = false" class="btn-secondary min-h-[44px]">İptal</button>
                @if($saleRemaining > 0)
                <button type="submit" class="btn-primary min-h-[44px] justify-center">Tahsilat Kaydet</button>
                @endif
            </div>
        </form>
    </div>
</div>
