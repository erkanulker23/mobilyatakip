@php
    $years = $years ?? \App\Support\ReportFilters::yearOptions();
    $showYear = $showYear ?? true;
    $showMonth = $showMonth ?? false;
    $embedded = $embedded ?? false;
    $showSubmit = $showSubmit ?? ! $embedded;
    $selectedMonth = $month ?? request('month') ?? ($from ?? null)?->month;
@endphp
@if(! $embedded)
<form method="get" class="flex flex-wrap gap-4 items-end">
@endif
    @if($showYear)
    <div class="min-w-[120px]">
        <label class="form-label">Yıl</label>
        <select name="year" class="form-select" data-report-year-select>
            <option value="">Seçin</option>
            @foreach($years as $y)
            <option value="{{ $y }}" {{ (string) ($year ?? '') === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    @endif
    @if($showMonth)
    <div class="min-w-[140px]">
        <label class="form-label">Ay</label>
        <select name="month" class="form-select" data-report-month-select>
            <option value="">Özel aralık</option>
            @foreach(\App\Support\ReportFilters::monthOptions() as $m)
            <option value="{{ $m }}" {{ (string) ($selectedMonth ?? '') === (string) $m ? 'selected' : '' }}>{{ \App\Support\ReportFilters::monthLabel($m) }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="min-w-[140px]">
        <label class="form-label">Başlangıç</label>
        <input type="date" name="from" value="{{ ($dateFrom ?? $from)?->format('Y-m-d') }}" class="form-input" data-report-from>
    </div>
    <div class="min-w-[140px]">
        <label class="form-label">Bitiş</label>
        <input type="date" name="to" value="{{ ($dateTo ?? $to)?->format('Y-m-d') }}" class="form-input" data-report-to>
    </div>
    @foreach($extraFilters ?? [] as $filter)
    <div class="{{ $filter['class'] ?? 'min-w-[140px]' }}">
        <label class="form-label">{{ $filter['label'] }}</label>
        {!! $filter['html'] !!}
    </div>
    @endforeach
    @if($showSubmit)
    <button type="submit" class="btn-primary">{{ $submitLabel ?? 'Filtrele' }}</button>
    @endif
@if(! $embedded)
</form>
@endif
