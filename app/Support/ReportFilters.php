<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportFilters
{
    /** @return array{from: Carbon, to: Carbon, year: ?int} */
    public static function range(Request $request, ?Carbon $defaultFrom = null, ?Carbon $defaultTo = null): array
    {
        $defaultFrom ??= now()->startOfMonth();
        $defaultTo ??= now()->endOfDay();
        $year = $request->filled('year') ? (int) $request->year : null;

        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from')
                ? Carbon::parse($request->from)->startOfDay()
                : ($year ? Carbon::create($year, 1, 1)->startOfDay() : $defaultFrom->copy()->startOfDay());
            $to = $request->filled('to')
                ? Carbon::parse($request->to)->endOfDay()
                : ($year ? Carbon::create($year, 12, 31)->endOfDay() : $defaultTo->copy()->endOfDay());
        } elseif ($year) {
            $from = Carbon::create($year, 1, 1)->startOfDay();
            $to = Carbon::create($year, 12, 31)->endOfDay();
        } else {
            $from = $defaultFrom->copy()->startOfDay();
            $to = $defaultTo->copy()->endOfDay();
        }

        if (! $year && $from->year === $to->year && $from->isStartOfDay() && $from->day === 1 && $from->month === 1
            && $to->month === 12 && $to->day === 31) {
            $year = $from->year;
        }

        return compact('from', 'to', 'year');
    }

    /** @return list<int> */
    public static function yearOptions(?int $startYear = null): array
    {
        $end = (int) now()->year;
        $startYear ??= $end - 5;

        return range($end, min($startYear, $end));
    }

    public static function periodLabel(Carbon $from, Carbon $to, ?int $year = null): string
    {
        if ($year && $from->format('Y-m-d') === Carbon::create($year, 1, 1)->format('Y-m-d')
            && $to->format('Y-m-d') === Carbon::create($year, 12, 31)->format('Y-m-d')) {
            return (string) $year;
        }

        return $from->format('d.m.Y') . ' – ' . $to->format('d.m.Y');
    }
}
