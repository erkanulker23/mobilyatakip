<?php

namespace App\Support;

use App\Models\CustomerPayment;
use App\Models\KasaHareket;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dönem muhasebesi: nakit (ödeme / hareket tarihi) ile sipariş (satış tarihi) metriklerini ayırır.
 *
 * Satış denklemi:
 *   hasılat − siparişe işlenen tahsil ≈ kalan alacak
 *
 * Kasa neti (gerçek kasa):
 *   Σ KasaHareket.amount (virman hariç, ledger kapsamı)
 *   = kasa tahsilatı − gider − personel avansı − tedarikçi − nakliye
 *
 * Kasaya girmeyen kayıtlar (ör. tedarikçiye_ode, kasasız gider) kasa netine dahil değildir.
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
     *     expenses: float,
     *     personnelAdvances: float,
     *     supplierPayments: float,
     *     shippingPayments: float,
     *     cashOutflows: float,
     *     cashNet: float,
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

        // Sadece kasaya giren tahsilatlar (tedarikçiye_ode ve kasasız kayıtlar hariç)
        $kasaPaymentQuery = CustomerPayment::query()
            ->whereDate('paymentDate', '>=', $fromDate)
            ->whereDate('paymentDate', '<=', $toDate)
            ->whereNotNull('kasaId')
            ->where(function ($q) {
                $q->whereNull('paymentType')
                    ->orWhere('paymentType', '!=', 'tedarikciye_ode');
            });

        $cashCollections = (float) (clone $kasaPaymentQuery)->sum('amount');

        $cashOnPeriodSales = $saleIds->isEmpty()
            ? 0.0
            : (float) (clone $kasaPaymentQuery)->whereIn('saleId', $saleIds)->sum('amount');

        $cashUnallocated = (float) (clone $kasaPaymentQuery)->whereNull('saleId')->sum('amount');

        $cashOnPriorSales = max(0, round($cashCollections - $cashOnPeriodSales - $cashUnallocated, 2));

        $outflows = self::kasaOutflowsByType($fromDate, $toDate);
        $cashNet = self::kasaNet($fromDate, $toDate);

        return [
            'saleCount' => (int) (clone $salesQuery)->count(),
            'revenue' => round($revenue, 2),
            'collectedOnSales' => round($collectedOnSales, 2),
            'receivable' => round($receivable, 2),
            'cashCollections' => round($cashCollections, 2),
            'cashOnPeriodSales' => round($cashOnPeriodSales, 2),
            'cashOnPriorSales' => $cashOnPriorSales,
            'cashUnallocated' => round($cashUnallocated, 2),
            'expenses' => $outflows['expenses'],
            'personnelAdvances' => $outflows['personnelAdvances'],
            'supplierPayments' => $outflows['supplierPayments'],
            'shippingPayments' => $outflows['shippingPayments'],
            'cashOutflows' => $outflows['total'],
            'cashNet' => $cashNet,
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

    /** Kasaya gerçekten giren tahsilat (ödeme tarihi). */
    public static function cashCollections(Carbon $from, Carbon $to): float
    {
        return (float) CustomerPayment::query()
            ->whereDate('paymentDate', '>=', $from->toDateString())
            ->whereDate('paymentDate', '<=', $to->toDateString())
            ->whereNotNull('kasaId')
            ->where(function ($q) {
                $q->whereNull('paymentType')
                    ->orWhere('paymentType', '!=', 'tedarikciye_ode');
            })
            ->sum('amount');
    }

    /** Dönem kasa neti: virman hariç ledger hareketleri. */
    public static function kasaNet(string $fromDate, string $toDate): float
    {
        return round((float) self::cashLedgerQuery($fromDate, $toDate)->sum('amount'), 2);
    }

    /**
     * @return array{
     *     expenses: float,
     *     personnelAdvances: float,
     *     supplierPayments: float,
     *     shippingPayments: float,
     *     total: float,
     * }
     */
    public static function kasaOutflowsByType(string $fromDate, string $toDate): array
    {
        $rows = self::cashLedgerQuery($fromDate, $toDate)
            ->where('amount', '<', 0)
            ->select('refType', DB::raw('SUM(amount) as s'))
            ->groupBy('refType')
            ->pluck('s', 'refType');

        $expenses = abs((float) ($rows['expense'] ?? 0));
        $personnelAdvances = abs((float) ($rows['personnel_advance'] ?? 0));
        $supplierPayments = abs((float) ($rows['supplier_payment'] ?? 0));
        $shippingPayments = abs((float) ($rows['shipping_company_payment'] ?? 0));

        return [
            'expenses' => round($expenses, 2),
            'personnelAdvances' => round($personnelAdvances, 2),
            'supplierPayments' => round($supplierPayments, 2),
            'shippingPayments' => round($shippingPayments, 2),
            'total' => round($expenses + $personnelAdvances + $supplierPayments + $shippingPayments, 2),
        ];
    }

    private static function cashLedgerQuery(string $fromDate, string $toDate)
    {
        return KasaHareket::query()
            ->ledger()
            ->whereDate('movementDate', '>=', $fromDate)
            ->whereDate('movementDate', '<=', $toDate)
            ->where(function ($q) {
                $q->whereNull('refType')
                    ->orWhere('refType', '!=', 'kasa_transfer');
            });
    }

    public static function assertSalesIdentity(float $revenue, float $collected, float $receivable, float $tolerance = 0.02): bool
    {
        return abs(($revenue - $collected) - $receivable) <= $tolerance
            || abs(max(0, $revenue - $collected) - $receivable) <= $tolerance;
    }
}
