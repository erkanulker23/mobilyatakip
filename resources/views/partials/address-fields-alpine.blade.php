<div class="space-y-4" x-data="TurkeyAddress.initAlpine({{ $model ?? 'quickCustomer' }})" x-init="initPicker()">
    <div>
        <label class="form-label">Adres</label>
        <textarea x-model="{{ $model ?? 'quickCustomer' }}.address" rows="2" class="form-input form-textarea" placeholder="Mahalle, sokak, bina no..."></textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label">İl</label>
            <select x-model="cityId" @change="onCityChange()" class="form-select">
                <option value="">İl seçiniz</option>
                <template x-for="city in cities" :key="city.id">
                    <option :value="String(city.id)" x-text="city.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label class="form-label">İlçe</label>
            <select x-model="districtId" @change="onDistrictChange()" class="form-select" :disabled="!cityId">
                <option value="" x-text="cityId ? 'İlçe seçiniz' : 'Önce il seçiniz'"></option>
                <template x-for="district in districts" :key="district.id">
                    <option :value="String(district.id)" x-text="district.name"></option>
                </template>
            </select>
        </div>
    </div>
</div>
