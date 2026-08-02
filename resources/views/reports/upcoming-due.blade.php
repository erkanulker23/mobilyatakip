@extends('layouts.app')
@section('title', 'Termin Yaklaşanlar')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Termin Tarihi Yaklaşanlar</h1>
        <p class="page-desc">Önümüzdeki {{ $days }} gün içinde termin tarihi gelen sipariş ve SSH formları</p>
    </div>
    @include('reports.partials.toolbar', ['printRoute' => 'reports.upcoming-due.print'])
</div>

<div class="card p-6 mb-6">
    <form method="get" class="flex flex-wrap gap-4 items-end">
        <div class="min-w-[160px]">
            <label class="form-label">Gün penceresi</label>
            <select name="days" class="form-select">
                @foreach([7, 14, 21, 30, 60, 90] as $d)
                <option value="{{ $d }}" {{ (int) $days === $d ? 'selected' : '' }}>{{ $d }} gün</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filtrele</button>
    </form>
</div>

@include('reports.partials.upcoming-due-content')
@endsection
