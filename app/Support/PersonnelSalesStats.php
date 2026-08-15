<?php

namespace App\Support;

use App\Models\CustomerPayment;
use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class PersonnelSalesStats
{
    /** Bu dönemde personele bağlı siparişler için alınan tahsilatlar (ödeme tarihine göre). */
    public static function collectedInPeriod(Personnel $personnel, Carbon $start, Carbon $end): float
    {
        return (float) CustomerPayment::query()
            ->whereHas('sale', fn ($q) => $q
                ->where('personnelId', $personnel->id)
                ->where('isCancelled', false))
            ->whereBetween('paymentDate', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }

    public static function receivableTotal(Builder|Relation $activeSalesQuery): float
    {
        return (float) (clone $activeSalesQuery)
            ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as receivable')
            ->value('receivable');
    }

    /** Satış cirosu × prim oranı (%). */
    public static function commissionFromTurnover(float $turnover, float|string|null $ratePercent): float
    {
        $rate = (float) ($ratePercent ?? 0);
        if ($rate <= 0 || $turnover <= 0) {
            return 0.0;
        }

        return round($turnover * $rate / 100, 2);
    }
}
