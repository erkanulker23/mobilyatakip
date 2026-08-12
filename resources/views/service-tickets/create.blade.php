@extends('layouts.app')
@section('title', 'Yeni Servis Kaydı')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 dark:text-slate-400 text-sm mb-1">
        <a href="{{ route('service-tickets.index') }}" class="hover:text-neutral-900 dark:hover:text-slate-300">Servis Kayıtları</a>
        <span>/</span>
        <span class="text-neutral-700 dark:text-slate-300">Yeni Kayıt</span>
    </div>
    <h1 class="page-title">Yeni Servis Kaydı</h1>
    <p class="page-desc">Servis / garanti takibi için yeni kayıt oluşturun. Önce müşteri seçin; ardından ilgili siparişi bağlayabilir veya yeni sipariş oluşturabilirsiniz.</p>
</div>

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">
    <p class="font-medium mb-1">Kayıt oluşturulamadı:</p>
    <ul class="list-disc list-inside text-sm space-y-0.5">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">{{ session('error') }}</div>
@endif

<div class="card p-6 max-w-4xl dark:border-slate-700">
    <form method="POST" action="{{ route('service-tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <label class="form-label">Müşteri <span class="text-red-500">*</span></label>
            <select name="customerId" required class="form-select" id="customerSelect">
                <option value="">Seçiniz</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" data-address="{{ e($c->full_address) }}" data-phone="{{ e($c->phone ?? '') }}" data-email="{{ e($c->email ?? '') }}" {{ (string) old('customerId', $selectedCustomerId ?? '') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('customerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @include('partials.branch-select', [
            'branches' => $branches ?? collect(),
            'id' => 'branchSelect',
            'hint' => 'Sipariş seçilirse şube otomatik gelir; isterseniz değiştirebilirsiniz.',
        ])
        {{-- Müşteri adres bilgileri --}}
        <div id="customerInfoCard" class="hidden rounded-lg border border-neutral-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/50 p-4">
            <h3 class="text-sm font-semibold text-neutral-700 dark:text-slate-300 mb-2">Müşteri Adres Bilgileri</h3>
            <div class="space-y-1 text-sm text-neutral-500">
                <p id="customerAddress"><span class="font-medium text-neutral-500 dark:text-neutral-500">Adres:</span> <span class="value">-</span></p>
                <p id="customerPhone"><span class="font-medium text-neutral-500 dark:text-neutral-500">Telefon:</span> <span class="value">-</span></p>
                <p id="customerEmail"><span class="font-medium text-neutral-500 dark:text-neutral-500">E-posta:</span> <span class="value">-</span></p>
            </div>
        </div>
        <div id="saleSection" class="hidden space-y-3">
            <div>
                <label class="form-label">İlgili Sipariş (Opsiyonel)</label>
                <select name="saleId" class="form-select" id="saleSelect">
                    <option value="">— Sipariş seçmeyin —</option>
                </select>
                @error('saleId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <p id="saleEmptyHint" class="mt-1 text-xs text-amber-600 hidden">Bu müşteriye ait sipariş bulunamadı. Yeni sipariş oluşturabilirsiniz.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="#" id="newSaleLink" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border border-neutral-200 dark:border-slate-600 text-neutral-700 dark:text-slate-200 hover:bg-neutral-50/50 transition-colors border-b border-neutral-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Yeni sipariş oluştur
                </a>
                <button type="button" id="refreshSalesBtn" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                    Siparişleri yenile
                </button>
            </div>
            <p class="text-xs text-neutral-500 dark:text-slate-400">Müşterinin mevcut siparişlerinden birini seçebilir veya yeni sipariş oluşturup bu sayfaya dönebilirsiniz.</p>
        </div>
        <div>
            <label class="form-label">Müşteri Problemleri <span class="text-red-500">*</span></label>
            <p class="text-xs text-neutral-500 dark:text-slate-400 mb-2">Müşterinin bildirdiği her sorunu ayrı satır olarak ekleyin. Sevkiyatçı formunda listelenir.</p>
            <div id="problemsList" class="space-y-2">
                @php $oldProblems = old('problems', ['']); @endphp
                @foreach($oldProblems as $i => $problemText)
                <div class="problem-row flex gap-2">
                    <input type="text" name="problems[]" value="{{ $problemText }}" class="form-input flex-1" placeholder="Örn: Kırık ayak, Montaj hatası" {{ $i === 0 ? 'required' : '' }}>
                    @if($i > 0)
                    <button type="button" class="remove-problem px-3 py-2 text-sm rounded-lg border border-neutral-200 text-neutral-600 hover:bg-neutral-50">Sil</button>
                    @endif
                </div>
                @endforeach
            </div>
            <button type="button" id="addProblemBtn" class="mt-2 text-sm font-medium text-neutral-700 hover:text-neutral-900">+ Problem ekle</button>
            @error('problems')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('problems.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Açıklama</label>
            <textarea name="description" rows="4" class="form-input form-textarea" placeholder="Sorun detayı">{{ old('description') }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Notlar</label>
            <textarea name="notes" rows="3" class="form-input form-textarea" placeholder="İç notlar (opsiyonel)">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Açılacak Servis Tarihi</label>
            <input type="date" name="dueDate" value="{{ old('dueDate') }}" class="form-input">
            @error('dueDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">Servisin açılacağı planlanan tarih. Kontrol panelinde yaklaşan SSH olarak listelenir.</p>
        </div>
        <div>
            <label class="form-label">Resimler</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-input py-2">
            <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">Sorunla ilgili fotoğraflar ekleyebilirsiniz. Birden fazla resim seçebilirsiniz.</p>
            @error('images')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @include('partials.service-ticket-shipping-fields', ['serviceTicket' => null])
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="underWarranty" value="1" id="underWarranty" {{ old('underWarranty') ? 'checked' : '' }} class="rounded border-slate-300 dark:border-slate-500 text-green-600 focus:ring-green-500">
                <label class="form-label mb-0" for="underWarranty">Garanti kapsamında</label>
            </div>
            <div id="serviceChargeWrapper" class="{{ old('underWarranty') ? 'hidden' : '' }}">
                <label class="form-label">Servis Ücreti (₺) *</label>
                <input type="text" inputmode="decimal" name="serviceChargeAmount" id="serviceChargeAmount" value="{{ old('serviceChargeAmount') }}" class="form-input money-input" placeholder="0" autocomplete="off">
                <p class="mt-1 text-xs text-neutral-500 dark:text-slate-400">Garanti kapsamında değilse servis ücreti girilmelidir.</p>
                @error('serviceChargeAmount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Kaydet</button>
            <a href="{{ route('service-tickets.index') }}" class="btn-secondary text-sm">İptal</a>
        </div>
    </form>
</div>
<script>
const selectedSaleId = @json(old('saleId', $selectedSaleId ?? ''));

document.getElementById('customerSelect')?.addEventListener('change', function() {
    updateCustomerInfo(this);
    loadCustomerSales(this.value, '');
});
document.getElementById('saleSelect')?.addEventListener('change', function() {
    applySaleBranch(this);
});
document.getElementById('refreshSalesBtn')?.addEventListener('click', function() {
    const customerId = document.getElementById('customerSelect')?.value;
    const currentSaleId = document.getElementById('saleSelect')?.value || selectedSaleId || '';
    loadCustomerSales(customerId, currentSaleId, true);
});

function updateCustomerInfo(select) {
    const card = document.getElementById('customerInfoCard');
    const opt = select?.selectedOptions[0];
    if (!opt || !opt.value) {
        card?.classList.add('hidden');
        return;
    }
    const address = opt.dataset.address || '-';
    const phone = opt.dataset.phone || '-';
    const email = opt.dataset.email || '-';
    const addrEl = document.querySelector('#customerAddress .value');
    const phoneEl = document.querySelector('#customerPhone .value');
    const emailEl = document.querySelector('#customerEmail .value');
    if (addrEl) addrEl.textContent = address;
    if (phoneEl) phoneEl.textContent = phone;
    if (emailEl) emailEl.textContent = email;
    card?.classList.remove('hidden');
}

function renderSalesOptions(sales, preferredSaleId) {
    const saleSection = document.getElementById('saleSection');
    const saleSelect = document.getElementById('saleSelect');
    const emptyHint = document.getElementById('saleEmptyHint');
    if (!saleSelect || !saleSection) return;

    saleSelect.innerHTML = '<option value="">— Sipariş seçmeyin —</option>';
    sales.forEach(function(s) {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.label;
        if (s.branchId) opt.dataset.branchId = s.branchId;
        if (preferredSaleId && String(preferredSaleId) === String(s.id)) {
            opt.selected = true;
        }
        saleSelect.appendChild(opt);
    });

    if (emptyHint) {
        emptyHint.classList.toggle('hidden', sales.length > 0);
    }
    applySaleBranch(saleSelect);
}

function applySaleBranch(saleSelect) {
    const branchSelect = document.getElementById('branchSelect');
    if (!branchSelect || !saleSelect) return;
    const opt = saleSelect.selectedOptions[0];
    const branchId = opt?.dataset?.branchId || '';
    if (branchId) {
        branchSelect.value = branchId;
    }
}

async function loadCustomerSales(customerId, preferredSaleId, forceFetch) {
    const saleSection = document.getElementById('saleSection');
    const newSaleLink = document.getElementById('newSaleLink');
    if (!saleSection) return;

    if (!customerId) {
        saleSection.classList.add('hidden');
        renderSalesOptions([], '');
        return;
    }

    saleSection.classList.remove('hidden');
    if (newSaleLink) {
        newSaleLink.href = '{{ route('sales.create') }}?customerId=' + encodeURIComponent(customerId) + '&returnTo=service-tickets/create';
    }

    try {
        const res = await fetch('{{ url('api/customers') }}/' + encodeURIComponent(customerId) + '/sales', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error('fetch failed');
        const sales = await res.json();
        renderSalesOptions(sales, preferredSaleId || selectedSaleId || '');
    } catch (e) {
        if (forceFetch) {
            alert('Siparişler yüklenemedi. Lütfen tekrar deneyin.');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customerSelect');
    if (customerSelect?.value) {
        updateCustomerInfo(customerSelect);
        loadCustomerSales(customerSelect.value, selectedSaleId || '');
    }

    const underWarranty = document.getElementById('underWarranty');
    const serviceChargeWrapper = document.getElementById('serviceChargeWrapper');
    const serviceChargeAmount = document.getElementById('serviceChargeAmount');
    function toggleServiceCharge() {
        const checked = underWarranty?.checked;
        if (serviceChargeWrapper) serviceChargeWrapper.classList.toggle('hidden', !!checked);
        if (serviceChargeAmount) serviceChargeAmount.required = !checked;
    }
    underWarranty?.addEventListener('change', toggleServiceCharge);
    toggleServiceCharge();
});

document.getElementById('addProblemBtn')?.addEventListener('click', function() {
    const list = document.getElementById('problemsList');
    if (!list) return;
    const row = document.createElement('div');
    row.className = 'problem-row flex gap-2';
    row.innerHTML = '<input type="text" name="problems[]" class="form-input flex-1" placeholder="Problem açıklaması">' +
        '<button type="button" class="remove-problem px-3 py-2 text-sm rounded-lg border border-neutral-200 text-neutral-600 hover:bg-neutral-50">Sil</button>';
    list.appendChild(row);
});

document.getElementById('problemsList')?.addEventListener('click', function(e) {
    if (!e.target.classList.contains('remove-problem')) return;
    const rows = document.querySelectorAll('#problemsList .problem-row');
    if (rows.length <= 1) return;
    e.target.closest('.problem-row')?.remove();
});
</script>
@endsection
