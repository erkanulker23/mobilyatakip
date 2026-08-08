<?php

namespace App\Services;

use App\Models\Kasa;
use App\Models\KasaHareket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KasaService
{
    /** @return array{opening: float, totalIn: float, totalOut: float, netMovements: float, current: float, count: int} */
    public function summary(Kasa $kasa): array
    {
        $opening = (float) ($kasa->openingBalance ?? 0);
        $netMovements = (float) $kasa->hareketler()->sum('amount');
        $totalIn = (float) $kasa->hareketler()
            ->where('amount', '>', 0)
            ->where(function ($q) {
                $q->whereNull('description')
                    ->orWhere('description', 'not like', 'Gider iptal%');
            })
            ->sum('amount');
        $totalOut = abs((float) $kasa->hareketler()->where('amount', '<', 0)->sum('amount'));

        return [
            'opening' => $opening,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'netMovements' => $netMovements,
            'current' => $opening + $netMovements,
            'count' => (int) $kasa->hareketler()->count(),
        ];
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
                'customer_payment' => $this->deleteCustomerPaymentMovement($hareket),
                'supplier_payment' => $this->deleteSupplierPaymentMovement($hareket),
                'shipping_company_payment' => $this->deleteShippingCompanyPaymentMovement($hareket),
                'expense' => $this->deleteExpenseMovement($hareket),
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
        }

        KasaHareket::query()
            ->where('refType', 'customer_payment')
            ->where('refId', $payment->id)
            ->delete();

        $payment->delete();
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
}
