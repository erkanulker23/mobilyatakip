<?php

namespace App\Support;

use Carbon\Carbon;

class MonthPeriod
{
    /**
     * Ay içi 7 günlük dönem: 1–7, 8–14, 15–21, 22–ay sonu.
     *
     * @return array{start: Carbon, end: Carbon, queryEnd: Carbon, label: string, period: int}
     */
    public static function current(?Carbon $date = null): array
    {
        $today = ($date ?? Carbon::today())->copy()->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $lastDay = $monthEnd->day;
        $day = $today->day;

        if ($day <= 7) {
            $start = $monthStart->copy();
            $end = $monthStart->copy()->day(min(7, $lastDay));
            $period = 1;
        } elseif ($day <= 14) {
            $start = $monthStart->copy()->day(min(8, $lastDay));
            $end = $monthStart->copy()->day(min(14, $lastDay));
            $period = 2;
        } elseif ($day <= 21) {
            $start = $monthStart->copy()->day(min(15, $lastDay));
            $end = $monthStart->copy()->day(min(21, $lastDay));
            $period = 3;
        } else {
            $start = $monthStart->copy()->day(min(22, $lastDay));
            $end = $monthEnd->copy();
            $period = 4;
        }

        $queryEnd = $end->greaterThan($today) ? $today : $end;

        return [
            'start' => $start,
            'end' => $end,
            'queryEnd' => $queryEnd,
            'label' => self::formatLabel($start, $end),
            'period' => $period,
        ];
    }

    public static function formatLabel(Carbon $start, Carbon $end): string
    {
        $start = $start->copy()->locale('tr');
        $end = $end->copy()->locale('tr');

        if ($start->isSameMonth($end)) {
            return $start->isoFormat('D') . '–' . $end->isoFormat('D MMM');
        }

        return $start->isoFormat('D MMM') . ' – ' . $end->isoFormat('D MMM');
    }
}
