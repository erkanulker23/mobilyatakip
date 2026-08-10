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
}
