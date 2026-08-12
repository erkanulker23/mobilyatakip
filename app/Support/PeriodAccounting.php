<?php

namespace App\Support;

use App\Models\CustomerPayment;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Dönem muhasebesi: nakit (ödeme tarihi) ile sipariş (satış tarihi) metriklerini ayırır.
 *
 * Doğru denklem (sipariş bazlı):
 *   hasılat − siparişe işlenen tahsil = kalan alacak
 *
 * Nakit tahsilat (ödeme tarihi) eski siparişlere ait ödemeleri de içerir;
 * bu yüzden "hasılat − nakit tahsilat ≠ kalan alacak" olabilir — bu hata değildir.
 */
final class PeriodAccounting
{
    /**
     * @return array{
     *     saleCount: int,
     *     revenue: float,
     *     collectedOnSales: float,
     *     receivable: float,
     *     cashCollections: float,
     *     cashOnPeriodSales: float,
     *     cashOnPriorSales: float,
     *     cashUnallocated: float,
     * }
     */
    public static function forRange(Carbon $from, Carbon $to): array
    {
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $salesQuery = Sale::query()
            ->where('isCancelled', false)
            ->whereDate('saleDate', '>=', $fromDate)
            ->whereDate('saleDate', '<=', $toDate);

        $saleIds = (clone $salesQuery)->pluck('id');

        $revenue = (float) (clone $salesQuery)->sum('grandTotal');
        $collectedOnSales = (float) (clone $salesQuery)->sum('paidAmount');
        $receivable = (float) (clone $salesQuery)
            ->selectRaw('COALESCE(SUM(GREATEST(grandTotal - COALESCE(paidAmount, 0), 0)), 0) as receivable')
            ->value('receivable');

        $cashCollections = (float) CustomerPayment::query()
            ->whereDate('paymentDate', '>=', $fromDate)
            ->whereDate('paymentDate', '<=', $toDate)
            ->sum('amount');

        $cashOnPeriodSales = $saleIds->isEmpty()
            ? 0.0
            : (float) CustomerPayment::query()
                ->whereDate('paymentDate', '>=', $fromDate)
                ->whereDate('paymentDate', '<=', $toDate)
                ->whereIn('saleId', $saleIds)
                ->sum('amount');

        $cashUnallocated = (float) CustomerPayment::query()
            ->whereDate('paymentDate', '>=', $fromDate)
            ->whereDate('paymentDate', '<=', $toDate)
            ->whereNull('saleId')
            ->sum('amount');

        $cashOnPriorSales = max(0, round($cashCollections - $cashOnPeriodSales - $cashUnallocated, 2));

        return [
            'saleCount' => (int) (clone $salesQuery)->count(),
            'revenue' => round($revenue, 2),
            'collectedOnSales' => round($collectedOnSales, 2),
            'receivable' => round($receivable, 2),
            'cashCollections' => round($cashCollections, 2),
            'cashOnPeriodSales' => round($cashOnPeriodSales, 2),
            'cashOnPriorSales' => $cashOnPriorSales,
            'cashUnallocated' => round($cashUnallocated, 2),
        ];
    }

    /** Sipariş listesinden özet (rapor satırlarıyla birebir). */
    public static function fromSalesCollection(Collection $sales): object
    {
        $grandTotal = (float) $sales->sum('grandTotal');
        $paidAmount = (float) $sales->sum('paidAmount');
        $receivable = (float) $sales->sum(fn (Sale $s) => max(0, CustomerBalance::saleRemaining($s)));
        $netRemaining = (float) $sales->sum(fn (Sale $s) => CustomerBalance::saleRemaining($s));

        return (object) [
            'count' => $sales->count(),
            'grandTotal' => round($grandTotal, 2),
            'paidAmount' => round($paidAmount, 2),
            'remaining' => round($receivable, 2),
            'netRemaining' => round($netRemaining, 2),
        ];
    }

    public static function cashCollections(Carbon $from, Carbon $to): float
    {
        return (float) CustomerPayment::query()
            ->whereDate('paymentDate', '>=', $from->toDateString())
            ->whereDate('paymentDate', '<=', $to->toDateString())
            ->sum('amount');
    }

    public static function assertSalesIdentity(float $revenue, float $collected, float $receivable, float $tolerance = 0.02): bool
    {
        return abs(($revenue - $collected) - $receivable) <= $tolerance
            || abs(max(0, $revenue - $collected) - $receivable) <= $tolerance;
    }
}
