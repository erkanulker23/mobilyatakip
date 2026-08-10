<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportFilters
{
    /** @return array{from: Carbon, to: Carbon, year: ?int, month: ?int} */
    public static function range(Request $request, ?Carbon $defaultFrom = null, ?Carbon $defaultTo = null): array
    {
        $defaultFrom ??= now()->startOfMonth();
        $defaultTo ??= now()->endOfDay();

        $period = $request->input('period');
        if ($period === 'this_month') {
            return self::compactRange(now()->startOfMonth()->startOfDay(), now()->endOfDay());
        }
        if ($period === 'last_month') {
            $anchor = now()->subMonth();

            return self::compactRange($anchor->copy()->startOfMonth()->startOfDay(), $anchor->copy()->endOfMonth()->endOfDay());
        }
        if ($period === 'this_year') {
            return self::compactRange(now()->copy()->startOfYear()->startOfDay(), now()->endOfDay(), now()->year);
        }

        $year = $request->filled('year') ? (int) $request->year : null;
        $month = $request->filled('month') ? max(1, min(12, (int) $request->month)) : null;

        if ($month) {
            $year ??= (int) now()->year;
            $anchor = Carbon::create($year, $month, 1);

            return self::compactRange($anchor->copy()->startOfMonth()->startOfDay(), $anchor->copy()->endOfMonth()->endOfDay(), $year, $month);
        }

        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from')
                ? Carbon::parse($request->from)->startOfDay()
                : ($year ? Carbon::create($year, 1, 1)->startOfDay() : $defaultFrom->copy()->startOfDay());
            $to = $request->filled('to')
                ? Carbon::parse($request->to)->endOfDay()
                : ($year ? Carbon::create($year, 12, 31)->endOfDay() : $defaultTo->copy()->endOfDay());

            return self::compactRange($from, $to, $year, $month);
        }

        if ($year) {
            return self::compactRange(
                Carbon::create($year, 1, 1)->startOfDay(),
                Carbon::create($year, 12, 31)->endOfDay(),
                $year,
            );
        }

        return self::compactRange($defaultFrom->copy()->startOfDay(), $defaultTo->copy()->endOfDay());
    }

    /** @return array{from: Carbon, to: Carbon, year: ?int, month: ?int} */
    private static function compactRange(Carbon $from, Carbon $to, ?int $year = null, ?int $month = null): array
    {
        if ($year === null && $month === null && $from->year === $to->year && $from->isStartOfDay() && $from->day === 1 && $from->month === 1
            && $to->month === 12 && $to->day === 31) {
            $year = $from->year;
        }

        if ($year === null && $month === null && $from->day === 1 && $from->month === $to->month && $from->year === $to->year
            && $to->day === $from->daysInMonth) {
            $year = $from->year;
            $month = $from->month;
        }

        return compact('from', 'to', 'year', 'month');
    }

    /** @return list<int> */
    public static function monthOptions(): array
    {
        return range(1, 12);
    }

    public static function monthLabel(int $month): string
    {
        return Carbon::createFromDate(2000, $month, 1)->locale('tr')->translatedFormat('F');
    }

    /** @return list<int> */
    public static function yearOptions(?int $startYear = null): array
    {
        $end = (int) now()->year;
        $startYear ??= $end - 5;

        return range($end, min($startYear, $end));
    }

    public static function periodLabel(Carbon $from, Carbon $to, ?int $year = null, ?int $month = null): string
    {
        if ($year && $from->format('Y-m-d') === Carbon::create($year, 1, 1)->format('Y-m-d')
            && $to->format('Y-m-d') === Carbon::create($year, 12, 31)->format('Y-m-d')) {
            return (string) $year;
        }

        if ($from->day === 1 && $from->month === $to->month && $from->year === $to->year
            && $to->day === $from->daysInMonth) {
            return $from->locale('tr')->translatedFormat('F Y');
        }

        if ($month && $year) {
            return self::monthLabel($month) . ' ' . $year;
        }

        return $from->format('d.m.Y') . ' – ' . $to->format('d.m.Y');
    }
}
