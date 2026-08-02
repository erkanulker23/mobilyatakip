@php
    $fieldId = $id ?? 'kasaId';
    $fieldName = $name ?? 'kasaId';
    $wrapperId = $wrapperId ?? ($fieldId . 'Wrapper');
    $labelId = $labelId ?? ($fieldId . 'Label');
    $hintId = $hintId ?? ($fieldId . 'RequiredHint');
    $helpId = $helpId ?? ($fieldId . 'Help');
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
     @if(!empty($paymentModeName)) data-payment-mode-name="{{ $paymentModeName }}" @endif>
    <label class="form-label" id="{{ $labelId }}">
        Kasa <span class="text-amber-600 dark:text-amber-400" id="{{ $hintId }}" data-kasa-required-star>*</span>
    </label>
    <select name="{{ $fieldName }}" id="{{ $fieldId }}" class="{{ $selectClass }}" @if(!empty($disabled)) disabled data-keep-disabled="1" @endif>
        <option value="">Seçiniz</option>
        @foreach($kasalar as $k)
        <option value="{{ $k->id }}" data-kasa-type="{{ $k->type }}" {{ (string) $selectedValue === (string) $k->id ? 'selected' : '' }}>
            {{ $k->name }} ({{ \App\Support\PaymentType::kasaTypeLabel($k) }})
        </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400" id="{{ $helpId }}" data-kasa-help></p>
    @error($errorName)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
