<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\KasaHareket;
use App\Models\PersonnelAdvance;
use App\Models\ShippingCompanyPayment;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\Schema;

/**
 * Kasa defterini kaynak kayıtlara hizalar.
 * Tahsilat kasaya bir kez girer. Gider, tedarikçi, nakliye ve avans bir kez çıkar.
 * Alış faturası kasaya yazılmaz. Tedarikçi ödemesi faturasız da kasadan düşer.
 */
class KasaLedgerSync
{
    /** @return array{created: int, updated: int, removed: int} */
    public function sync(): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'removed' => 0];

        if (Schema::hasTable('customer_payments')) {
            CustomerPayment::query()->with('customer')->orderBy('createdAt')->each(function (CustomerPayment $payment) use (&$stats) {
                $hitsKasa = $payment->kasaId && $payment->paymentType !== 'tedarikciye_ode';
                $this->align(
                    'customer_payment',
                    (string) $payment->id,
                    $hitsKasa ? (string) $payment->kasaId : null,
                    $hitsKasa ? abs((float) $payment->amount) : 0,
                    'giris',
                    $payment->paymentDate?->toDateString(),
                    'Tahsilat - '.($payment->customer?->name ?? 'Müşteri'),
                    $stats,
                );
            });
        }

        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'kasaId')) {
            Expense::query()->orderBy('createdAt')->each(function (Expense $expense) use (&$stats) {
                $this->align(
                    'expense',
                    (string) $expense->id,
                    $expense->kasaId ? (string) $expense->kasaId : null,
                    $expense->kasaId ? -abs((float) $expense->amount) : 0,
                    'cikis',
                    $expense->expenseDate?->toDateString(),
                    'Gider - '.($expense->category ? $expense->category.': ' : '').($expense->description ?? ''),
                    $stats,
                );
            });
        }

        if (Schema::hasTable('supplier_payments') && Schema::hasColumn('supplier_payments', 'kasaId')) {
            SupplierPayment::query()->with('supplier')->orderBy('createdAt')->each(function (SupplierPayment $payment) use (&$stats) {
                $this->align(
                    'supplier_payment',
                    (string) $payment->id,
                    $payment->kasaId ? (string) $payment->kasaId : null,
                    $payment->kasaId ? -abs((float) $payment->amount) : 0,
                    'cikis',
                    $payment->paymentDate?->toDateString(),
                    'Tedarikçi ödemesi - '.($payment->supplier?->name ?? 'Tedarikçi'),
                    $stats,
                );
            });
        }

        if (Schema::hasTable('shipping_company_payments') && Schema::hasColumn('shipping_company_payments', 'kasaId')) {
            ShippingCompanyPayment::query()->with('shippingCompany')->orderBy('createdAt')->each(function (ShippingCompanyPayment $payment) use (&$stats) {
                $this->align(
                    'shipping_company_payment',
                    (string) $payment->id,
                    $payment->kasaId ? (string) $payment->kasaId : null,
                    $payment->kasaId ? -abs((float) $payment->amount) : 0,
                    'cikis',
                    $payment->paymentDate?->toDateString(),
                    'Nakliye ödemesi - '.($payment->shippingCompany?->name ?? 'Nakliye'),
                    $stats,
                );
            });
        }

        if (Schema::hasTable('personnel_advances') && Schema::hasColumn('personnel_advances', 'kasaId')) {
            PersonnelAdvance::query()->with('personnel')->orderBy('createdAt')->each(function (PersonnelAdvance $advance) use (&$stats) {
                $this->align(
                    'personnel_advance',
                    (string) $advance->id,
                    $advance->kasaId ? (string) $advance->kasaId : null,
                    $advance->kasaId ? -abs((float) $advance->amount) : 0,
                    'cikis',
                    $advance->advanceDate?->toDateString(),
                    $advance->cariLabel().($advance->note ? ' · '.$advance->note : ''),
                    $stats,
                );
            });
        }

        return $stats;
    }

    /** @param  array{created: int, updated: int, removed: int}  $stats */
    private function align(
        string $refType,
        string $refId,
        ?string $kasaId,
        float $amount,
        string $type,
        ?string $movementDate,
        string $description,
        array &$stats,
    ): void {
        $rows = KasaHareket::query()
            ->where('refType', $refType)
            ->where('refId', $refId)
            ->orderBy('createdAt')
            ->get();

        if ($kasaId === null || abs($amount) < 0.005) {
            $removed = $rows->count();
            if ($removed > 0) {
                KasaHareket::query()->whereIn('id', $rows->pluck('id'))->delete();
                $stats['removed'] += $removed;
            }

            return;
        }

        $keep = $rows->shift();
        if ($rows->isNotEmpty()) {
            KasaHareket::query()->whereIn('id', $rows->pluck('id'))->delete();
            $stats['removed'] += $rows->count();
        }

        $payload = [
            'kasaId' => $kasaId,
            'type' => $type,
            'amount' => round($amount, 2),
            'movementDate' => $movementDate ?: now()->toDateString(),
            'refType' => $refType,
            'refId' => $refId,
        ];

        if (! $keep) {
            $payload['description'] = $description;
            KasaHareket::create($payload);
            $stats['created']++;

            return;
        }

        $changed = (string) $keep->kasaId !== $kasaId
            || abs((float) $keep->amount - $payload['amount']) > 0.009
            || (string) $keep->type !== $type
            || $keep->movementDate?->toDateString() !== $payload['movementDate'];

        if ($changed) {
            $keep->update($payload);
            $stats['updated']++;
        }
    }
}
