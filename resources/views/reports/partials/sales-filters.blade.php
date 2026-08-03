@php
    $selectedPersonnelId = $filters['personnelId'] ?? request('personnelId');
    $selectedOdeme = $filters['odeme'] ?? request('odeme');
    $showOdemeFilter = $showOdemeFilter ?? true;
@endphp
<div class="min-w-[180px]">
    <label class="form-label">Satışı yapan personel</label>
    <select name="personnelId" class="form-select">
        <option value="">Tüm personel</option>
        <option value="none" {{ $selectedPersonnelId === 'none' ? 'selected' : '' }}>Personel atanmamış</option>
        @foreach($personnelOptions as $person)
        <option value="{{ $person->id }}" {{ (string) $selectedPersonnelId === (string) $person->id ? 'selected' : '' }}>{{ $person->name }}</option>
        @endforeach
    </select>
</div>
@if($showOdemeFilter)
<div class="min-w-[160px]">
    <label class="form-label">Ödeme durumu</label>
    <select name="odeme" class="form-select">
        <option value="">Tümü</option>
        <option value="borclu" {{ $selectedOdeme === 'borclu' ? 'selected' : '' }}>Borçlular</option>
        <option value="borcsuz" {{ $selectedOdeme === 'borcsuz' ? 'selected' : '' }}>Borçsuzlar</option>
    </select>
</div>
@endif
