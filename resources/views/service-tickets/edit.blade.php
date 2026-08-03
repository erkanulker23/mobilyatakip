@extends('layouts.app')
@section('title', 'Düzenle: ' . $serviceTicket->ticketNumber)
@section('content')
@php
    $problems = \App\Support\ServiceTicketStatus::normalizeProblems(old('problems', $serviceTicket->reportedProblems ?? []));
    if ($problems === [] && $serviceTicket->issueType) {
        $problems = [['description' => $serviceTicket->issueType, 'status' => 'bekliyor']];
    }
@endphp
<div class="mb-6">
    <div class="flex items-center gap-2 text-neutral-500 text-sm mb-1">
        <a href="{{ route('service-tickets.index') }}" class="hover:text-neutral-900">Servis Kayıtları</a>
        <span>/</span>
        <a href="{{ route('service-tickets.show', $serviceTicket) }}" class="hover:text-neutral-900">{{ $serviceTicket->ticketNumber }}</a>
        <span>/</span>
        <span class="text-neutral-700">Düzenle</span>
    </div>
    <h1 class="page-title">Servis Kaydı Düzenle</h1>
    <p class="page-desc">{{ $serviceTicket->ticketNumber }}</p>
</div>

<div class="card p-6 max-w-3xl">
    <form method="POST" action="{{ route('service-tickets.update', $serviceTicket) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label class="form-label">İlgili Sipariş</label>
            <select name="saleId" class="form-select" id="saleSelect">
                <option value="">— Sipariş yok —</option>
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
            <label class="form-label">Müşteri Problemleri *</label>
            <div id="editProblemsList" class="space-y-3">
                @foreach($problems as $i => $problem)
                <div class="problem-row grid grid-cols-1 md:grid-cols-[1fr_160px_auto] gap-2 items-start">
                    <input type="text" name="problems[{{ $i }}][description]" value="{{ $problem['description'] }}" required class="form-input" placeholder="Problem açıklaması">
                    <select name="problems[{{ $i }}][status]" class="form-select">
                        @foreach(\App\Support\ServiceTicketStatus::PROBLEM_STATUSES as $value => $label)
                        <option value="{{ $value }}" {{ ($problem['status'] ?? 'bekliyor') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($i > 0)
                    <button type="button" class="remove-problem px-3 py-2 text-sm rounded-lg border border-neutral-200 text-neutral-600 hover:bg-neutral-50">Sil</button>
                    @else
                    <span></span>
                    @endif
                </div>
                @endforeach
            </div>
            <button type="button" id="addEditProblemBtn" class="mt-2 text-sm font-medium text-neutral-700 hover:text-neutral-900">+ Problem ekle</button>
            @error('problems')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Genel Açıklama</label>
            <textarea name="description" rows="3" class="form-input form-textarea">{{ old('description', $serviceTicket->description) }}</textarea>
        </div>
        <div>
            <label class="form-label">Termin Tarihi</label>
            <input type="date" name="dueDate" value="{{ old('dueDate', $serviceTicket->dueDate?->format('Y-m-d')) }}" class="form-input">
            @error('dueDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Kayıt Durumu</label>
            <select name="status" class="form-select">
                @foreach(\App\Support\ServiceTicketStatus::STATUSES as $value => $label)
                <option value="{{ $value }}" {{ old('status', $serviceTicket->status) == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
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
                <input type="text" inputmode="decimal" name="serviceChargeAmount" value="{{ old('serviceChargeAmount', $serviceTicket->serviceChargeAmount ? money($serviceTicket->serviceChargeAmount) : '') }}" class="form-input money-input" placeholder="0" autocomplete="off">
            </div>
        </div>
        @include('partials.service-ticket-shipping-fields', ['serviceTicket' => $serviceTicket])
        @if(!empty($serviceTicket->images))
        <div>
            <label class="form-label">Mevcut Resimler</label>
            <div class="flex flex-wrap gap-3">
                @foreach($serviceTicket->images as $image)
                <label class="relative block w-24 cursor-pointer group">
                    <img src="{{ $image }}" alt="SSH resmi" class="w-24 h-24 object-cover rounded-lg border border-neutral-200">
                    <span class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/50 opacity-0 group-hover:opacity-100 transition">
                        <input type="checkbox" name="removeImages[]" value="{{ $image }}" class="rounded border-white text-red-600 focus:ring-red-500">
                        <span class="sr-only">Sil</span>
                    </span>
                </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-neutral-500">Silmek istediğiniz resimlerin üzerine gelip işaretleyin.</p>
        </div>
        @endif
        <div>
            <label class="form-label">Yeni Resimler</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-input py-2">
            @error('images')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
            <button type="submit" class="btn-primary">Güncelle</button>
            <a href="{{ route('service-tickets.show', $serviceTicket) }}" class="btn-secondary">İptal</a>
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

let editProblemIndex = {{ count($problems) }};
const problemStatusOptions = @json(\App\Support\ServiceTicketStatus::PROBLEM_STATUSES);
document.getElementById('addEditProblemBtn')?.addEventListener('click', function() {
    const list = document.getElementById('editProblemsList');
    if (!list) return;
    const row = document.createElement('div');
    row.className = 'problem-row grid grid-cols-1 md:grid-cols-[1fr_160px_auto] gap-2 items-start';
    let optionsHtml = '';
    Object.entries(problemStatusOptions).forEach(function(entry) {
        optionsHtml += '<option value="' + entry[0] + '">' + entry[1] + '</option>';
    });
    row.innerHTML =
        '<input type="text" name="problems[' + editProblemIndex + '][description]" required class="form-input" placeholder="Problem açıklaması">' +
        '<select name="problems[' + editProblemIndex + '][status]" class="form-select">' + optionsHtml + '</select>' +
        '<button type="button" class="remove-problem px-3 py-2 text-sm rounded-lg border border-neutral-200 text-neutral-600 hover:bg-neutral-50">Sil</button>';
    editProblemIndex++;
    list.appendChild(row);
});

document.getElementById('editProblemsList')?.addEventListener('click', function(e) {
    if (!e.target.classList.contains('remove-problem')) return;
    const rows = document.querySelectorAll('#editProblemsList .problem-row');
    if (rows.length <= 1) return;
    e.target.closest('.problem-row')?.remove();
});
</script>
@endsection
