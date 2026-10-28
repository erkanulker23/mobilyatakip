<?php

namespace App\Support;

use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\KasaHareket;
use App\Models\PersonnelAdvance;
use App\Models\Sale;
use App\Models\ShippingCompanyPayment;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Dönem muhasebesi: nakit (ödeme / hareket tarihi) ile sipariş (satış tarihi) metriklerini ayırır.
 *
 * Satış denklemi:
 *   hasılat − siparişe işlenen tahsil ≈ kalan alacak
 *
 * Kasa (virman hariç ledger):
 *   kasa girişi = tahsilat + para ekleme + diğer giriş
 *   kasa çıkışı = gider + avans + tedarikçi + nakliye + diğer çıkış
 *   kasa neti   = Σ amount = giriş − çıkış
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
     *     cashDeposits: float,
     *     cashInflowsOther: float,
     *     cashInflows: float,
     *     cashOnPeriodSales: float,
     *     cashOnPriorSales: float,
     *     cashUnallocated: float,
     *     expenses: float,
     *     personnelAdvances: float,
     *     supplierPayments: float,
     *     shippingPayments: float,
     *     cashOutflowsOther: float,
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

        // Sipariş tahsil kırılımı (CustomerPayment · ödeme tarihi)
        $kasaPaymentQuery = CustomerPayment::query()
            ->whereDate('paymentDate', '>=', $fromDate)
            ->whereDate('paymentDate', '<=', $toDate)
            ->whereNotNull('kasaId')
            ->where(function ($q) {
                $q->whereNull('paymentType')
                    ->orWhere('paymentType', '!=', 'tedarikciye_ode');
            });

        $paymentCollections = (float) (clone $kasaPaymentQuery)->sum('amount');

        $cashOnPeriodSales = $saleIds->isEmpty()
            ? 0.0
            : (float) (clone $kasaPaymentQuery)->whereIn('saleId', $saleIds)->sum('amount');

        $cashUnallocated = (float) (clone $kasaPaymentQuery)->whereNull('saleId')->sum('amount');

        $cashOnPriorSales = max(0, round($paymentCollections - $cashOnPeriodSales - $cashUnallocated, 2));

        $ledger = self::ledgerBreakdown($fromDate, $toDate);

        return [
            'saleCount' => (int) (clone $salesQuery)->count(),
            'revenue' => round($revenue, 2),
            'collectedOnSales' => round($collectedOnSales, 2),
            'receivable' => round($receivable, 2),
            // Kasa kartları: gerçek ledger (formül her zaman tutar)
            'cashCollections' => $ledger['cashCollections'],
            'cashDeposits' => $ledger['cashDeposits'],
            'cashInflowsOther' => $ledger['cashInflowsOther'],
            'cashInflows' => $ledger['cashInflows'],
            'cashOnPeriodSales' => round($cashOnPeriodSales, 2),
            'cashOnPriorSales' => $cashOnPriorSales,
            'cashUnallocated' => round($cashUnallocated, 2),
            'expenses' => $ledger['expenses'],
            'personnelAdvances' => $ledger['personnelAdvances'],
            'supplierPayments' => $ledger['supplierPayments'],
            'shippingPayments' => $ledger['shippingPayments'],
            'cashOutflowsOther' => $ledger['cashOutflowsOther'],
            'cashOutflows' => $ledger['cashOutflows'],
            'cashNet' => $ledger['cashNet'],
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

    /** Kasaya gerçekten giren tahsilat (ledger · customer_payment). */
    public static function cashCollections(Carbon $from, Carbon $to): float
    {
        return self::ledgerBreakdown($from->toDateString(), $to->toDateString())['cashCollections'];
    }

    /** Dönem kasa neti: virman hariç ledger hareketleri. */
    public static function kasaNet(string $fromDate, string $toDate): float
    {
        return self::ledgerBreakdown($fromDate, $toDate)['cashNet'];
    }

    /**
     * @return array{
     *     expenses: float,
     *     personnelAdvances: float,
     *     supplierPayments: float,
     *     shippingPayments: float,
     *     cashOutflowsOther: float,
     *     total: float,
     * }
     */
    public static function kasaOutflowsByType(string $fromDate, string $toDate): array
    {
        $ledger = self::ledgerBreakdown($fromDate, $toDate);

        return [
            'expenses' => $ledger['expenses'],
            'personnelAdvances' => $ledger['personnelAdvances'],
            'supplierPayments' => $ledger['supplierPayments'],
            'shippingPayments' => $ledger['shippingPayments'],
            'cashOutflowsOther' => $ledger['cashOutflowsOther'],
            'total' => $ledger['cashOutflows'],
        ];
    }

    /**
     * Virman hariç kasa defteri kırılımı — tüm giriş/çıkış kalemleri.
     *
     * @return array{
     *     cashCollections: float,
     *     cashDeposits: float,
     *     cashInflowsOther: float,
     *     cashInflows: float,
     *     expenses: float,
     *     personnelAdvances: float,
     *     supplierPayments: float,
     *     shippingPayments: float,
     *     cashOutflowsOther: float,
     *     cashOutflows: float,
     *     cashNet: float,
     * }
     */
    public static function ledgerBreakdown(string $fromDate, string $toDate): array
    {
        // Tür bazında netleme yapma: aynı günde hem eksi hem artı satır varsa
        // çıkan para, netin içinde kayboluyordu. Her eksi satır çıkıştır.
        $row = self::cashLedgerQuery($fromDate, $toDate)
            ->selectRaw("
                SUM(CASE WHEN refType = 'customer_payment' AND amount > 0 THEN amount ELSE 0 END) as cashCollections,
                SUM(CASE WHEN refType = 'kasa_deposit' AND amount > 0 THEN amount ELSE 0 END) as cashDeposits,
                SUM(CASE WHEN amount > 0 AND (refType IS NULL OR refType NOT IN ('customer_payment', 'kasa_deposit')) THEN amount ELSE 0 END) as cashInflowsOther,
                SUM(CASE WHEN refType = 'expense' AND amount < 0 THEN -amount ELSE 0 END) as expenses,
                SUM(CASE WHEN refType = 'personnel_advance' AND amount < 0 THEN -amount ELSE 0 END) as personnelAdvances,
                SUM(CASE WHEN refType = 'supplier_payment' AND amount < 0 THEN -amount ELSE 0 END) as supplierPayments,
                SUM(CASE WHEN refType = 'shipping_company_payment' AND amount < 0 THEN -amount ELSE 0 END) as shippingPayments,
                SUM(CASE WHEN amount < 0 AND (refType IS NULL OR refType NOT IN ('expense', 'personnel_advance', 'supplier_payment', 'shipping_company_payment')) THEN -amount ELSE 0 END) as cashOutflowsOther,
                SUM(amount) as ledgerSum
            ")
            ->first();

        $cashCollections = round((float) ($row->cashCollections ?? 0), 2);
        $cashDeposits = round((float) ($row->cashDeposits ?? 0), 2);
        $cashInflowsOther = round((float) ($row->cashInflowsOther ?? 0), 2);
        // Gider, tedarikçi, nakliye ve avans: kasası seçilmiş kaydın kendi tarihi.
        // Kasa satırı eksik veya türü boş olsa da panelde görünür.
        $recorded = self::recordedOutflowTotals($fromDate, $toDate);
        $expenses = $recorded['expenses'];
        $personnelAdvances = $recorded['personnelAdvances'];
        $supplierPayments = $recorded['supplierPayments'];
        $shippingPayments = $recorded['shippingPayments'];
        $cashOutflowsOther = self::otherOutflowTotal($fromDate, $toDate, $recorded['ids']);

        $cashInflows = round($cashCollections + $cashDeposits + $cashInflowsOther, 2);
        $cashOutflows = round(
            $expenses + $personnelAdvances + $supplierPayments + $shippingPayments + $cashOutflowsOther,
            2
        );
        $cashNet = round($cashInflows - $cashOutflows, 2);

        return [
            'cashCollections' => round($cashCollections, 2),
            'cashDeposits' => round($cashDeposits, 2),
            'cashInflowsOther' => round($cashInflowsOther, 2),
            'cashInflows' => $cashInflows,
            'expenses' => round($expenses, 2),
            'personnelAdvances' => round($personnelAdvances, 2),
            'supplierPayments' => round($supplierPayments, 2),
            'shippingPayments' => round($shippingPayments, 2),
            'cashOutflowsOther' => round($cashOutflowsOther, 2),
            'cashOutflows' => $cashOutflows,
            'cashNet' => $cashNet,
        ];
    }

    /**
     * Kasası seçilmiş gider, tedarikçi, nakliye ve avans tutarları.
     *
     * @return array{expenses: float, personnelAdvances: float, supplierPayments: float, shippingPayments: float, ids: array<int, string>}
     */
    private static function recordedOutflowTotals(string $fromDate, string $toDate): array
    {
        $expenses = self::sumRecorded(Expense::class, 'expenseDate', $fromDate, $toDate);
        $advances = self::sumRecorded(PersonnelAdvance::class, 'advanceDate', $fromDate, $toDate);
        $suppliers = self::sumRecorded(SupplierPayment::class, 'paymentDate', $fromDate, $toDate);
        $shipping = self::sumRecorded(ShippingCompanyPayment::class, 'paymentDate', $fromDate, $toDate);

        return [
            'expenses' => $expenses['total'],
            'personnelAdvances' => $advances['total'],
            'supplierPayments' => $suppliers['total'],
            'shippingPayments' => $shipping['total'],
            'ids' => array_values(array_unique(array_merge(
                $expenses['ids'],
                $advances['ids'],
                $suppliers['ids'],
                $shipping['ids'],
            ))),
        ];
    }

    /**
     * @param  class-string  $model
     * @return array{total: float, ids: array<int, string>}
     */
    private static function sumRecorded(string $model, string $dateColumn, string $fromDate, string $toDate): array
    {
        $table = (new $model)->getTable();
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'kasaId')) {
            return ['total' => 0.0, 'ids' => []];
        }

        $rows = $model::query()
            ->whereNotNull('kasaId')
            ->where('kasaId', '!=', '')
            ->whereDate($dateColumn, '>=', $fromDate)
            ->whereDate($dateColumn, '<=', $toDate)
            ->get(['id', 'amount']);

        return [
            'total' => round((float) $rows->sum('amount'), 2),
            'ids' => $rows->map(fn ($row) => (string) $row->id)->all(),
        ];
    }

    /** Kasadan çıkan, gider/ödeme/avans kaydına bağlı olmayan diğer satırlar. */
    private static function otherOutflowTotal(string $fromDate, string $toDate, array $recordedIds): float
    {
        $query = self::cashLedgerQuery($fromDate, $toDate)
            ->where('amount', '<', 0)
            ->where(function ($q) {
                $q->whereNull('refType')
                    ->orWhereNotIn('refType', [
                        'expense',
                        'personnel_advance',
                        'supplier_payment',
                        'shipping_company_payment',
                    ]);
            });

        if ($recordedIds !== []) {
            $query->where(function ($q) use ($recordedIds) {
                $q->whereNull('refId')->orWhereNotIn('refId', $recordedIds);
            });
        }

        return round(abs((float) $query->sum('amount')), 2);
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
            })
            ->where(function ($q) {
                $q->whereNull('type')
                    ->orWhereNotIn('type', ['virman_cikis', 'virman_giris']);
            });
    }

    public static function assertSalesIdentity(float $revenue, float $collected, float $receivable, float $tolerance = 0.02): bool
    {
        return abs(($revenue - $collected) - $receivable) <= $tolerance
            || abs(max(0, $revenue - $collected) - $receivable) <= $tolerance;
    }

    public static function assertCashIdentity(array $cash, float $tolerance = 0.02): bool
    {
        $in = (float) ($cash['cashInflows'] ?? 0);
        $out = (float) ($cash['cashOutflows'] ?? 0);
        $net = (float) ($cash['cashNet'] ?? 0);

        return abs(($in - $out) - $net) <= $tolerance;
    }
}
