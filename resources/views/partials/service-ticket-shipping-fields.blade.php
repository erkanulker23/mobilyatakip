@php
    $ticket = $serviceTicket ?? null;
    $selectedCompanyId = old('shippingCompanyId', $ticket?->shippingCompanyId ?? '');
    $selectedVehicleId = old('shippingVehicleId', $ticket?->shippingVehicleId ?? '');
@endphp
<div class="rounded-xl border border-neutral-200 dark:border-slate-700 p-4 space-y-4">
    <h3 class="text-sm font-semibold text-neutral-900 dark:text-slate-100">Sevkiyatçı Bilgileri</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="form-label" for="shippingCompanyId">Nakliye Firması</label>
            <select name="shippingCompanyId" id="shippingCompanyId" class="form-select">
                <option value="">— Firma seçin —</option>
                @foreach($shippingCompanies as $company)
                <option value="{{ $company->id }}" {{ (string) $selectedCompanyId === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('shippingCompanyId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label" for="shippingVehicleId">Araç</label>
            <select name="shippingVehicleId" id="shippingVehicleId" class="form-select">
                <option value="">— Araç seçin veya manuel girin —</option>
            </select>
            @error('shippingVehicleId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">Firmaya kayıtlı araçlardan seçebilir veya alttaki alanları manuel doldurabilirsiniz.</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="form-label">Araç Plakası</label>
            <input type="text" name="assignedVehiclePlate" id="assignedVehiclePlate" value="{{ old('assignedVehiclePlate', $ticket?->assignedVehiclePlate ?? '') }}" class="form-input" placeholder="34 ABC 123">
        </div>
        <div>
            <label class="form-label">Sevkiyatçı / Sürücü Adı</label>
            <input type="text" name="assignedDriverName" id="assignedDriverName" value="{{ old('assignedDriverName', $ticket?->assignedDriverName ?? '') }}" class="form-input" placeholder="Ad Soyad">
        </div>
    </div>
    <div>
        <label class="form-label">Sevkiyatçı Telefonu</label>
        <input type="tel" name="assignedDriverPhone" id="assignedDriverPhone" value="{{ old('assignedDriverPhone', $ticket?->assignedDriverPhone ?? '') }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel" autocomplete="tel">
        @error('assignedDriverPhone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
<script>
(function () {
    const vehiclesByCompany = @json($vehiclesByCompany);
    const companySelect = document.getElementById('shippingCompanyId');
    const vehicleSelect = document.getElementById('shippingVehicleId');
    const plateInput = document.getElementById('assignedVehiclePlate');
    const driverInput = document.getElementById('assignedDriverName');
    const phoneInput = document.getElementById('assignedDriverPhone');
    const initialCompanyId = @json((string) $selectedCompanyId);
    const initialVehicleId = @json((string) $selectedVehicleId);

    function fillVehicleFields(vehicle) {
        if (!vehicle) return;
        if (plateInput) plateInput.value = vehicle.vehiclePlate || '';
        if (driverInput) driverInput.value = vehicle.driverName || '';
        if (phoneInput) phoneInput.value = vehicle.driverPhone || '';
    }

    function populateVehicles(companyId, keepVehicleId) {
        if (!vehicleSelect) return;
        vehicleSelect.innerHTML = '<option value="">— Araç seçin veya manuel girin —</option>';
        if (!companyId || !vehiclesByCompany[companyId]) return;
        vehiclesByCompany[companyId].forEach(function (vehicle) {
            const option = document.createElement('option');
            option.value = vehicle.id;
            option.textContent = vehicle.label;
            if (keepVehicleId && vehicle.id === keepVehicleId) {
                option.selected = true;
            }
            vehicleSelect.appendChild(option);
        });
    }

    companySelect?.addEventListener('change', function () {
        populateVehicles(this.value, '');
        vehicleSelect.value = '';
    });

    vehicleSelect?.addEventListener('change', function () {
        const companyId = companySelect?.value;
        if (!companyId || !this.value) return;
        const vehicle = (vehiclesByCompany[companyId] || []).find(function (v) { return v.id === this.value; }.bind(this));
        if (vehicle) fillVehicleFields(vehicle);
    });

    if (initialCompanyId) {
        populateVehicles(initialCompanyId, initialVehicleId);
    }
})();
</script>
