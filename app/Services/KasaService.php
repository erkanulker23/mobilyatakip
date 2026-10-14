<?php

namespace App\Services;

use App\Models\Kasa;
use App\Models\KasaHareket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KasaService
{
    /**
     * @return array{
     *     opening: float,
     *     totalIn: float,
     *     totalOut: float,
     *     operationalIn: float,
     *     operationalOut: float,
     *     transferIn: float,
     *     transferOut: float,
     *     netMovements: float,
     *     current: float,
     *     count: int,
     * }
     */
    public function summary(Kasa $kasa): array
    {
        $opening = (float) ($kasa->openingBalance ?? 0);
        $rows = $kasa->hareketler()
            ->ledger()
            ->get(['amount', 'refType', 'type']);

        $operationalIn = 0.0;
        $operationalOut = 0.0;
        $transferIn = 0.0;
        $transferOut = 0.0;

        foreach ($rows as $h) {
            $amount = (float) ($h->amount ?? 0);
            $isTransfer = $h->refType === 'kasa_transfer'
                || in_array($h->type, ['virman_cikis', 'virman_giris'], true);

            if ($isTransfer) {
                if ($amount >= 0) {
                    $transferIn += $amount;
                } else {
                    $transferOut += abs($amount);
                }
            } elseif ($amount >= 0) {
                $operationalIn += $amount;
            } else {
                $operationalOut += abs($amount);
            }
        }

        $totalIn = $operationalIn + $transferIn;
        $totalOut = $operationalOut + $transferOut;
        $netMovements = $totalIn - $totalOut;

        return [
            'opening' => round($opening, 2),
            'totalIn' => round($totalIn, 2),
            'totalOut' => round($totalOut, 2),
            'operationalIn' => round($operationalIn, 2),
            'operationalOut' => round($operationalOut, 2),
            'transferIn' => round($transferIn, 2),
            'transferOut' => round($transferOut, 2),
            'netMovements' => round($netMovements, 2),
            'current' => round($opening + $netMovements, 2),
            'count' => $rows->count(),
        ];
    }

    public function deposit(Kasa $kasa, float $amount, string $movementDate, ?string $description, ?string $createdBy): KasaHareket
    {
        if (! ($kasa->isActive ?? true)) {
            throw new \InvalidArgumentException('Pasif kasaya para eklenemez.');
        }

        $amount = abs($amount);
        if ($amount < 0.01) {
            throw new \InvalidArgumentException('Tutar 0,01 ₺ veya üzeri olmalı.');
        }

        return KasaHareket::create([
            'kasaId' => $kasa->id,
            'type' => 'giris',
            'amount' => $amount,
            'movementDate' => $movementDate,
            'description' => self::formatDepositDescription($description),
            'createdBy' => $createdBy,
            'refType' => 'kasa_deposit',
            'refId' => (string) Str::uuid(),
        ]);
    }

    public function updateDeposit(Kasa $kasa, KasaHareket $hareket, float $amount, string $movementDate, ?string $description): KasaHareket
    {
        if ($hareket->kasaId !== $kasa->id) {
            throw new \InvalidArgumentException('Hareket bu kasaya ait değil.');
        }
        if ($hareket->refType !== 'kasa_deposit') {
            throw new \InvalidArgumentException('Yalnızca para ekleme kayıtları düzenlenebilir.');
        }

        $amount = abs($amount);
        if ($amount < 0.01) {
            throw new \InvalidArgumentException('Tutar 0,01 ₺ veya üzeri olmalı.');
        }

        $hareket->update([
            'amount' => $amount,
            'movementDate' => $movementDate,
            'description' => self::formatDepositDescription($description),
        ]);

        return $hareket->fresh();
    }

    public static function formatDepositDescription(?string $note): string
    {
        $desc = 'Dışarıdan para girişi';
        $note = trim((string) $note);

        return $note !== '' ? $desc . ' — ' . $note : $desc;
    }

    public static function depositNote(?string $description): string
    {
        $description = trim((string) $description);
        $prefix = 'Dışarıdan para girişi';

        if ($description === $prefix || $description === '') {
            return '';
        }

        $withSep = $prefix . ' — ';
        if (str_starts_with($description, $withSep)) {
            return trim(substr($description, strlen($withSep)));
        }

        return $description;
    }

    public function transfer(Kasa $from, Kasa $to, float $amount, string $movementDate, ?string $description, ?string $createdBy): string
    {
        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('Kaynak ve hedef kasa aynı olamaz.');
        }

        $transferId = (string) Str::uuid();
        $descOut = 'Virman → ' . $to->name;
        $descIn = 'Virman ← ' . $from->name;
        if ($description) {
            $descOut .= ' — ' . $description;
            $descIn .= ' — ' . $description;
        }

        DB::transaction(function () use ($from, $to, $amount, $movementDate, $descOut, $descIn, $transferId, $createdBy) {
            KasaHareket::create([
                'kasaId' => $from->id,
                'type' => 'virman_cikis',
                'amount' => -abs($amount),
                'movementDate' => $movementDate,
                'description' => $descOut,
                'fromKasaId' => $from->id,
                'toKasaId' => $to->id,
                'createdBy' => $createdBy,
                'refType' => 'kasa_transfer',
                'refId' => $transferId,
            ]);

            KasaHareket::create([
                'kasaId' => $to->id,
                'type' => 'virman_giris',
                'amount' => abs($amount),
                'movementDate' => $movementDate,
                'description' => $descIn,
                'fromKasaId' => $from->id,
                'toKasaId' => $to->id,
                'createdBy' => $createdBy,
                'refType' => 'kasa_transfer',
                'refId' => $transferId,
            ]);
        });

        return $transferId;
    }

    public function deleteMovement(Kasa $kasa, KasaHareket $hareket): void
    {
        if ($hareket->kasaId !== $kasa->id) {
            throw new \InvalidArgumentException('Hareket bu kasaya ait değil.');
        }

        DB::transaction(function () use ($hareket) {
            match ($hareket->refType) {
                'kasa_transfer' => $this->deleteTransferMovement($hareket),
                'kasa_deposit' => $hareket->delete(),
                'customer_payment' => $this->deleteCustomerPaymentMovement($hareket),
                'supplier_payment' => $this->deleteSupplierPaymentMovement($hareket),
                'shipping_company_payment' => $this->deleteShippingCompanyPaymentMovement($hareket),
                'expense' => $this->deleteExpenseMovement($hareket),
                'personnel_advance' => $this->deletePersonnelAdvanceMovement($hareket),
                default => $hareket->delete(),
            };
        });
    }

    private function deleteTransferMovement(KasaHareket $hareket): void
    {
        if ($hareket->refId) {
            KasaHareket::query()
                ->where('refType', 'kasa_transfer')
                ->where('refId', $hareket->refId)
                ->delete();

            return;
        }

        $hareket->delete();
    }

    private function deleteCustomerPaymentMovement(KasaHareket $hareket): void
    {
        $payment = \App\Models\CustomerPayment::find($hareket->refId);
        if (! $payment) {
            $hareket->delete();

            return;
        }

        if ($payment->saleId) {
            \App\Models\Sale::where('id', $payment->saleId)->decrement('paidAmount', (float) $payment->amount);
        } elseif ($payment->paymentType !== 'tedarikciye_ode') {
            $this->reverseUnlinkedCustomerPayment($payment);
        }

        KasaHareket::query()
            ->where('refType', 'customer_payment')
            ->where('refId', $payment->id)
            ->delete();

        $payment->delete();
    }

    private function reverseUnlinkedCustomerPayment(\App\Models\CustomerPayment $payment): void
    {
        $remaining = (float) $payment->amount;
        if ($remaining <= 0.005) {
            return;
        }

        $sales = \App\Models\Sale::where('customerId', $payment->customerId)
            ->where('isCancelled', false)
            ->whereRaw('COALESCE(paidAmount, 0) > 0.005')
            ->orderByDesc('saleDate')
            ->orderByDesc('createdAt')
            ->lockForUpdate()
            ->get();

        foreach ($sales as $sale) {
            if ($remaining <= 0.005) {
                break;
            }
            $paid = (float) ($sale->paidAmount ?? 0);
            if ($paid <= 0.005) {
                continue;
            }
            $take = min($remaining, $paid);
            \App\Models\Sale::where('id', $sale->id)->decrement('paidAmount', $take);
            $remaining -= $take;
        }
    }

    private function deleteSupplierPaymentMovement(KasaHareket $hareket): void
    {
        $payment = \App\Models\SupplierPayment::find($hareket->refId);
        if (! $payment) {
            $hareket->delete();

            return;
        }

        if ($payment->purchaseId) {
            \App\Models\Purchase::where('id', $payment->purchaseId)->decrement('paidAmount', (float) $payment->amount);
        }

        KasaHareket::query()
            ->where('refType', 'supplier_payment')
            ->where('refId', $payment->id)
            ->delete();

        $payment->delete();
    }

    private function deleteShippingCompanyPaymentMovement(KasaHareket $hareket): void
    {
        $payment = \App\Models\ShippingCompanyPayment::find($hareket->refId);
        if (! $payment) {
            $hareket->delete();

            return;
        }

        KasaHareket::query()
            ->where('refType', 'shipping_company_payment')
            ->where('refId', $payment->id)
            ->delete();

        $payment->delete();
    }

    private function deleteExpenseMovement(KasaHareket $hareket): void
    {
        $expense = \App\Models\Expense::find($hareket->refId);
        if (! $expense) {
            $hareket->delete();

            return;
        }

        KasaHareket::query()
            ->where('refType', 'expense')
            ->where('refId', $expense->id)
            ->delete();

        $expense->delete();
    }

    private function deletePersonnelAdvanceMovement(KasaHareket $hareket): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('personnel_advances')) {
            $hareket->delete();

            return;
        }

        $advance = \App\Models\PersonnelAdvance::find($hareket->refId);
        if (! $advance) {
            $hareket->delete();

            return;
        }

        KasaHareket::query()
            ->where('refType', 'personnel_advance')
            ->where('refId', $advance->id)
            ->delete();

        $advance->delete();
    }
}
