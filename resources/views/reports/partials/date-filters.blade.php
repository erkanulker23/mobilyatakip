@if(! ($embedded ?? false))
<form method="get" class="flex flex-wrap gap-4 items-end">
@endif
    @if($showYear ?? true)
    <div class="min-w-[120px]">
        <label class="form-label">Yıl</label>
        <select name="year" class="form-select" data-report-year-select>
            <option value="">Seçin</option>
            @foreach(($years ?? \App\Support\ReportFilters::yearOptions()) as $y)
            <option value="{{ $y }}" @selected((string) ($year ?? '') === (string) $y)>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    @endif
    @if($showMonth ?? false)
    <div class="min-w-[140px]">
        <label class="form-label">Ay</label>
        <select name="month" class="form-select" data-report-month-select>
            <option value="">Özel aralık</option>
            @foreach(\App\Support\ReportFilters::monthOptions() as $m)
            <option value="{{ $m }}" @selected(request()->filled('month') && (int) request('month') === (int) $m)>{{ \App\Support\ReportFilters::monthLabel($m) }}</option>
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
    @if($showSubmit ?? ! ($embedded ?? false))
    <button type="submit" class="btn-primary">{{ $submitLabel ?? 'Filtrele' }}</button>
    @endif
@if(! ($embedded ?? false))
</form>
@endif
