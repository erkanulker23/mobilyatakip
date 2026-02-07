@extends('layouts.app')
@section('title', 'Düzenle: ' . $serviceTicket->ticketNumber)
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('service-tickets.index') }}" class="hover:text-slate-700">Servis Kayıtları</a>
        <span>/</span>
        <a href="{{ route('service-tickets.show', $serviceTicket) }}" class="hover:text-slate-700">{{ $serviceTicket->ticketNumber }}</a>
        <span>/</span>
        <span class="text-slate-700">Düzenle</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Servis Kaydı Düzenle</h1>
    <p class="text-slate-600 mt-1">{{ $serviceTicket->ticketNumber }} - {{ $serviceTicket->issueType }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('service-tickets.update', $serviceTicket) }}" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">Satış *</label>
            <select name="saleId" required class="form-select" id="saleSelect">
                @foreach($sales as $s)
                <option value="{{ $s->id }}" data-customer="{{ $s->customerId }}" {{ old('saleId', $serviceTicket->saleId) == $s->id ? 'selected' : '' }}>{{ $s->saleNumber }} - {{ $s->customer?->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Müşteri *</label>
            <select name="customerId" required class="form-select" id="customerSelect">
                @foreach(\App\Models\Customer::orderBy('name')->get() as $c)
                <option value="{{ $c->id }}" {{ old('customerId', $serviceTicket->customerId) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Sorun Tipi *</label>
            <input type="text" name="issueType" required value="{{ old('issueType', $serviceTicket->issueType) }}" class="form-input">
        </div>
        <div>
            <label class="form-label">Açıklama</label>
            <textarea name="description" rows="3" class="form-input form-textarea">{{ old('description', $serviceTicket->description) }}</textarea>
        </div>
        <div>
            <label class="form-label">Durum</label>
            <select name="status" class="form-select">
                <option value="acildi" {{ old('status', $serviceTicket->status) == 'acildi' ? 'selected' : '' }}>Açıldı</option>
                <option value="devam_ediyor" {{ old('status', $serviceTicket->status) == 'devam_ediyor' ? 'selected' : '' }}>Devam Ediyor</option>
                <option value="tamamlandi" {{ old('status', $serviceTicket->status) == 'tamamlandi' ? 'selected' : '' }}>Tamamlandı</option>
                <option value="iptal" {{ old('status', $serviceTicket->status) == 'iptal' ? 'selected' : '' }}>İptal</option>
            </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Atanan Teknisyen</label>
                <select name="assignedUserId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ old('assignedUserId', $serviceTicket->assignedUserId) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Servis Ücreti (₺)</label>
                <input type="number" step="0.01" min="0" name="serviceChargeAmount" value="{{ old('serviceChargeAmount', $serviceTicket->serviceChargeAmount) }}" class="form-input">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Araç Plakası</label>
                <input type="text" name="assignedVehiclePlate" value="{{ old('assignedVehiclePlate', $serviceTicket->assignedVehiclePlate) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Sürücü Adı</label>
                <input type="text" name="assignedDriverName" value="{{ old('assignedDriverName', $serviceTicket->assignedDriverName) }}" class="form-input">
            </div>
        </div>
        <div>
            <label class="form-label">Sürücü Telefonu</label>
            <input type="text" name="assignedDriverPhone" value="{{ old('assignedDriverPhone', $serviceTicket->assignedDriverPhone) }}" class="form-input">
        </div>
        <div>
            <label class="form-label">Notlar</label>
            <textarea name="notes" rows="3" class="form-input form-textarea">{{ old('notes', $serviceTicket->notes) }}</textarea>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="underWarranty" value="1" {{ old('underWarranty', $serviceTicket->underWarranty) ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <label class="form-label mb-0">Garanti kapsamında</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Güncelle</button>
            <a href="{{ route('service-tickets.show', $serviceTicket) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
<script>
document.getElementById('saleSelect')?.addEventListener('change', function() {
    const opt = this.selectedOptions[0];
    const customerId = opt?.dataset?.customer;
    const customerSelect = document.getElementById('customerSelect');
    if (customerId && customerSelect) customerSelect.value = customerId;
});
</script>
@endsection
