<?php

namespace App\Support;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class SalesMonthStats
{
    /** Satış tarihi verilen aralıkta olan aktif siparişler. */
    public static function salesQuery(Carbon $start, Carbon $end): Builder
    {
        return Sale::query()
            ->where('isCancelled', false)
            ->whereDate('saleDate', '>=', $start->toDateString())
            ->whereDate('saleDate', '<=', $end->toDateString());
    }

    public static function currentMonthQuery(?Carbon $today = null): Builder
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $queryEnd = $today->greaterThan($monthEnd) ? $monthEnd : $today;

        return self::salesQuery($monthStart, $queryEnd);
    }

    public static function turnover(Builder $query): float
    {
        return (float) (clone $query)->sum('grandTotal');
    }

    public static function count(Builder $query): int
    {
        return (int) (clone $query)->count();
    }

    /** Seçili satışların tahsil edilmemiş kalan tutarı. */
    public static function receivable(Builder $query): float
    {
        return (float) (clone $query)
            ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as receivable')
            ->value('receivable');
    }

    public static function collectedOnSales(Builder $query): float
    {
        return (float) (clone $query)->sum('paidAmount');
    }

    /** @return array{start: Carbon, end: Carbon, label: string} */
    public static function currentMonthRange(?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        return [
            'start' => $monthStart,
            'end' => $monthEnd,
            'label' => $monthStart->locale('tr')->isoFormat('D') . '–' . $monthEnd->locale('tr')->isoFormat('D MMM'),
        ];
    }
}
