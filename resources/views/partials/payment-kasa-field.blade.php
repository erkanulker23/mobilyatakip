@php
    $fieldId = $id ?? 'kasaId';
    $fieldName = $name ?? 'kasaId';
    $wrapperId = $wrapperId ?? ($fieldId . 'Wrapper');
    $labelId = $labelId ?? ($fieldId . 'Label');
    $hintId = $hintId ?? ($fieldId . 'RequiredHint');
    $helpId = $helpId ?? ($fieldId . 'Help');
    $bankInfoId = $bankInfoId ?? ($fieldId . 'BankInfo');
    $selectedValue = $selected ?? old($fieldName);
    $wrapperClass = $wrapperClass ?? '';
    $selectClass = $selectClass ?? 'form-select min-h-[44px]';
    $errorName = $errorName ?? $fieldName;
@endphp
<div id="{{ $wrapperId }}"
     class="payment-kasa-field {{ $wrapperClass }}"
     data-payment-type-id="{{ $paymentTypeId }}"
     data-amount-id="{{ $amountId ?? '' }}"
     data-kasa-id="{{ $fieldId }}"
     data-hint-id="{{ $hintId }}"
     data-label-id="{{ $labelId }}"
     data-bank-info-id="{{ $bankInfoId }}"
     @if(!empty($paymentModeName)) data-payment-mode-name="{{ $paymentModeName }}" @endif>
    <label class="form-label" id="{{ $labelId }}">
        Kasa <span class="text-amber-600 dark:text-amber-400" id="{{ $hintId }}" data-kasa-required-star>*</span>
    </label>
    <select name="{{ $fieldName }}" id="{{ $fieldId }}" class="{{ $selectClass }}" @if(!empty($disabled)) disabled data-keep-disabled="1" @endif>
        <option value="">Seçiniz</option>
        @foreach($kasalar as $k)
        <option value="{{ $k->id }}"
            data-kasa-type="{{ $k->type }}"
            data-bank-name="{{ $k->bankName ?? '' }}"
            data-iban="{{ $k->iban ?? '' }}"
            data-account-number="{{ $k->accountNumber ?? '' }}"
            {{ (string) $selectedValue === (string) $k->id ? 'selected' : '' }}>
            {{ $k->name }}@if($k->bankName) — {{ $k->bankName }}@endif ({{ \App\Support\PaymentType::kasaTypeLabel($k) }})
        </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400" id="{{ $helpId }}" data-kasa-help></p>
    <div id="{{ $bankInfoId }}" class="hidden mt-3 rounded-xl border border-sky-200 dark:border-sky-900/50 bg-sky-50/80 dark:bg-sky-950/30 p-3 text-sm" data-bank-info-panel aria-live="polite"></div>
    @error($errorName)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
