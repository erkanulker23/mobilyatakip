@extends('layouts.app')
@section('title', 'Yeni Servis Kaydı')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
        <a href="{{ route('service-tickets.index') }}" class="hover:text-slate-700">Servis Kayıtları</a>
        <span>/</span>
        <span class="text-slate-700">Yeni Kayıt</span>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Yeni Servis Kaydı</h1>
    <p class="text-slate-600 mt-1">Servis / garanti takibi için yeni kayıt oluşturun</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('service-tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Satış *</label>
            <select name="saleId" required class="form-select" id="saleSelect">
                <option value="">Seçiniz</option>
                @foreach($sales as $s)
                <option value="{{ $s->id }}" data-customer="{{ $s->customerId }}">{{ $s->saleNumber }} - {{ $s->customer?->name }}</option>
                @endforeach
            </select>
            @error('saleId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Müşteri *</label>
            <select name="customerId" required class="form-select" id="customerSelect">
                <option value="">Önce satış seçin veya manuel seçin</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" data-address="{{ e($c->address ?? '') }}" data-phone="{{ e($c->phone ?? '') }}" data-email="{{ e($c->email ?? '') }}">{{ $c->name }}</option>
                @endforeach
            </select>
            @error('customerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        {{-- Müşteri adres bilgileri --}}
        <div id="customerInfoCard" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-2">Müşteri Adres Bilgileri</h3>
            <div class="space-y-1 text-sm text-slate-600">
                <p id="customerAddress"><span class="font-medium text-slate-500">Adres:</span> <span class="value">-</span></p>
                <p id="customerPhone"><span class="font-medium text-slate-500">Telefon:</span> <span class="value">-</span></p>
                <p id="customerEmail"><span class="font-medium text-slate-500">E-posta:</span> <span class="value">-</span></p>
            </div>
        </div>
        <div>
            <label class="form-label">Sorun Tipi *</label>
            <input type="text" name="issueType" required value="{{ old('issueType') }}" class="form-input" placeholder="Örn: Montaj hatası, Kırık parça">
            @error('issueType')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Açıklama</label>
            <textarea name="description" rows="3" class="form-input form-textarea" placeholder="Sorun detayı">{{ old('description') }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Resimler</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-input py-2">
            <p class="mt-1 text-xs text-slate-500">Sorunla ilgili fotoğraflar ekleyebilirsiniz. Birden fazla resim seçebilirsiniz.</p>
            @error('images')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="space-y-3">
            <div>
                <label class="form-label">Atanan Teknisyen</label>
                <select name="assignedUserId" class="form-select">
                    <option value="">Seçiniz</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ old('assignedUserId') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="underWarranty" value="1" {{ old('underWarranty') ? 'checked' : '' }} class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                <label class="form-label mb-0">Garanti kapsamında</label>
            </div>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">Kaydet</button>
            <a href="{{ route('service-tickets.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">İptal</a>
        </div>
    </form>
</div>
<script>
document.getElementById('saleSelect').addEventListener('change', function() {
    const opt = this.selectedOptions[0];
    const customerId = opt?.dataset?.customer;
    const customerSelect = document.getElementById('customerSelect');
    if (customerId && customerSelect) {
        customerSelect.value = customerId;
        updateCustomerInfo(customerSelect);
    }
});
document.getElementById('customerSelect').addEventListener('change', function() {
    updateCustomerInfo(this);
});
function updateCustomerInfo(select) {
    const card = document.getElementById('customerInfoCard');
    const opt = select?.selectedOptions[0];
    if (!opt || !opt.value) {
        card.classList.add('hidden');
        return;
    }
    const address = opt.dataset.address || '-';
    const phone = opt.dataset.phone || '-';
    const email = opt.dataset.email || '-';
    document.querySelector('#customerAddress .value').textContent = address;
    document.querySelector('#customerPhone .value').textContent = phone;
    document.querySelector('#customerEmail .value').textContent = email;
    card.classList.remove('hidden');
}
// Sayfa yüklendiğinde müşteri seçiliyse bilgileri göster
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customerSelect');
    if (customerSelect?.value) updateCustomerInfo(customerSelect);
});
</script>
@endsection
