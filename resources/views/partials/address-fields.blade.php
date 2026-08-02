@php
    $address = $address ?? null;
    $cityId = $cityId ?? null;
    $districtId = $districtId ?? null;
    $addressName = $addressName ?? 'address';
    $cityName = $cityName ?? 'cityId';
    $districtName = $districtName ?? 'districtId';
    $addressLabel = $addressLabel ?? 'Adres';
    $addressPlaceholder = $addressPlaceholder ?? 'Mahalle, sokak, bina no...';
    $selectedCityId = old($cityName, $cityId);
    $selectedDistrictId = old($districtName, $districtId);
@endphp

<div
    class="turkey-address-fields space-y-4"
    data-city-id="{{ $selectedCityId }}"
    data-district-id="{{ $selectedDistrictId }}"
>
    <div>
        <label class="form-label">{{ $addressLabel }}</label>
        <textarea
            name="{{ $addressName }}"
            rows="3"
            class="form-input form-textarea"
            placeholder="{{ $addressPlaceholder }}"
        >{{ old($addressName, $address) }}</textarea>
        @error($addressName)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="form-label">İl</label>
            <select name="{{ $cityName }}" class="form-select turkey-city-select">
                <option value="">İl seçiniz</option>
            </select>
            @error($cityName)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">İlçe</label>
            <select name="{{ $districtName }}" class="form-select turkey-district-select">
                <option value="">Önce il seçiniz</option>
            </select>
            @error($districtName)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
