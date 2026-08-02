@php
    $years = $years ?? \App\Support\ReportFilters::yearOptions();
    $showYear = $showYear ?? true;
@endphp
<form method="get" class="flex flex-wrap gap-4 items-end">
    @if($showYear)
    <div class="min-w-[120px]">
        <label class="form-label">Yıl</label>
        <select name="year" class="form-select" onchange="this.form.querySelector('[name=from]').value=''; this.form.querySelector('[name=to]').value='';">
            <option value="">Tümü / Özel</option>
            @foreach($years as $y)
            <option value="{{ $y }}" {{ (string) ($year ?? '') === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="min-w-[140px]">
        <label class="form-label">Başlangıç</label>
        <input type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="form-input">
    </div>
    <div class="min-w-[140px]">
        <label class="form-label">Bitiş</label>
        <input type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="form-input">
    </div>
    @foreach($extraFilters ?? [] as $filter)
    <div class="{{ $filter['class'] ?? 'min-w-[140px]' }}">
        <label class="form-label">{{ $filter['label'] }}</label>
        {!! $filter['html'] !!}
    </div>
    @endforeach
    <button type="submit" class="btn-primary">{{ $submitLabel ?? 'Filtrele' }}</button>
</form>
