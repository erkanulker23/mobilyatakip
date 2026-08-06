@extends('layouts.app')
@section('title', 'Düzenle: ' . $serviceTicket->ticketNumber)
@section('content')
@php
    use App\Support\ServiceTicketStatus;

    $problems = ServiceTicketStatus::normalizeProblems(old('problems', $serviceTicket->reportedProblems ?? []));
    if ($problems === [] && $serviceTicket->issueType) {
        $problems = [['description' => $serviceTicket->issueType, 'status' => 'bekliyor']];
    }
    $ticketStatus = old('status', $serviceTicket->status ?? 'acildi');
    $isClosed = ServiceTicketStatus::isClosed($ticketStatus);
    $oldStages = old('newStages', ['']);
    $stageHistory = $serviceTicket->details->sortByDesc('actionDate');
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

@if(!empty($hideCommercialData))
@include('service-tickets.partials.edit-workshop', ['serviceTicket' => $serviceTicket])
@else
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
            <label class="form-label">Açılacak Servis Tarihi</label>
            <input type="date" name="dueDate" value="{{ old('dueDate', $serviceTicket->dueDate?->format('Y-m-d')) }}" class="form-input">
            @error('dueDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Kayıt Durumu</label>
            <select name="status" class="form-select" id="ticketStatusSelect">
                @foreach(ServiceTicketStatus::STATUSES as $value => $label)
                <option value="{{ $value }}" {{ $ticketStatus == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-neutral-500">Hızlı kapatma için aşağıdaki “SSH kapat” seçeneğini de kullanabilirsiniz.</p>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
            <div class="px-4 py-3 bg-neutral-50 dark:bg-neutral-900/60 border-b border-neutral-200 dark:border-neutral-700">
                <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">İşlem Aşamaları</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Servis sürecinde yapılan adımları ekleyin; geçmiş kayıtlar aşağıda listelenir.</p>
            </div>
            <div class="p-4 space-y-4">
                @if($stageHistory->isNotEmpty())
                <div class="space-y-3 max-h-56 overflow-y-auto">
                    @foreach($stageHistory as $detail)
                    <div class="flex gap-3 text-sm">
                        <span class="shrink-0 w-2 h-2 mt-2 rounded-full bg-neutral-300 dark:bg-neutral-600"></span>
                        <div class="min-w-0">
                            <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ ServiceTicketStatus::detailActionLabel($detail->action) }}</p>
                            <p class="text-xs text-neutral-500">{{ $detail->actionDate?->format('d.m.Y H:i') ?? '—' }} · {{ $detail->user?->name ?? '—' }}</p>
                            @if($detail->notes)
                            <p class="text-neutral-600 dark:text-neutral-400 mt-1 whitespace-pre-wrap">{{ $detail->notes }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-neutral-500">Henüz aşama kaydı yok.</p>
                @endif

                <div>
                    <label class="form-label">Yeni aşama ekle</label>
                    <div id="newStagesList" class="space-y-2">
                        @foreach($oldStages as $i => $stageText)
                        <div class="stage-row flex gap-2">
                            <input type="text" name="newStages[]" value="{{ $stageText }}" class="form-input flex-1" placeholder="Örn: Servis ekibi yönlendirildi, Parça değişimi yapıldı">
                            @if($i > 0)
                            <button type="button" class="remove-stage px-3 py-2 text-sm rounded-lg border border-neutral-200 text-neutral-600 hover:bg-neutral-50 shrink-0">Sil</button>
                            @else
                            <span class="w-[52px] shrink-0"></span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <button type="button" id="addStageBtn" class="mt-2 text-sm font-medium text-neutral-700 hover:text-neutral-900">+ Aşama satırı ekle</button>
                </div>
            </div>
        </div>

        @if($isClosed)
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900/40 p-4">
            <p class="text-sm text-neutral-700 dark:text-neutral-300">
                Bu SSH kaydı <strong>{{ ServiceTicketStatus::label($ticketStatus) }}</strong> durumunda.
                @if($serviceTicket->closedAt)
                Kapanış: {{ $serviceTicket->closedAt->format('d.m.Y H:i') }}
                @endif
            </p>
            @if($ticketStatus === 'tamamlandi')
            <label class="mt-3 flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="reopenTicket" value="1" {{ old('reopenTicket') ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-green-600 focus:ring-green-500">
                <span class="text-sm text-neutral-700 dark:text-neutral-300">Kaydı tekrar aç (Devam Ediyor)</span>
            </label>
            @endif
        </div>
        @else
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/20 p-4">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="closeTicket" value="1" {{ old('closeTicket') ? 'checked' : '' }} class="mt-1 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500" id="closeTicketCheckbox">
                <span>
                    <span class="font-medium text-emerald-900 dark:text-emerald-200">SSH kaydını kapat</span>
                    <span class="block text-sm text-emerald-800/80 dark:text-emerald-300/80 mt-0.5">Kayıt tamamlandı olarak işaretlenir, kapanış tarihi kaydedilir ve bağlı sipariş durumu güncellenir.</span>
                </span>
            </label>
        </div>
        @endif

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
@endif
<script>
@if(empty($hideCommercialData))
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

document.getElementById('addStageBtn')?.addEventListener('click', function() {
    const list = document.getElementById('newStagesList');
    if (!list) return;
    const row = document.createElement('div');
    row.className = 'stage-row flex gap-2';
    row.innerHTML =
        '<input type="text" name="newStages[]" class="form-input flex-1" placeholder="Örn: Servis ekibi yönlendirildi, Parça değişimi yapıldı">' +
        '<button type="button" class="remove-stage px-3 py-2 text-sm rounded-lg border border-neutral-200 text-neutral-600 hover:bg-neutral-50 shrink-0">Sil</button>';
    list.appendChild(row);
});

document.getElementById('newStagesList')?.addEventListener('click', function(e) {
    if (!e.target.classList.contains('remove-stage')) return;
    const rows = document.querySelectorAll('#newStagesList .stage-row');
    if (rows.length <= 1) return;
    e.target.closest('.stage-row')?.remove();
});

document.getElementById('closeTicketCheckbox')?.addEventListener('change', function() {
    const statusSelect = document.getElementById('ticketStatusSelect');
    if (!statusSelect) return;
    if (this.checked) {
        statusSelect.value = 'tamamlandi';
    }
});
@endif
</script>
@endsection
