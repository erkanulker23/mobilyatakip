<?php

namespace App\Support;

use App\Models\CustomerPayment;

class CustomerPaymentSaleCoverage
{
    private const TOLERANCE = 0.005;

    public static function label(CustomerPayment $payment): ?string
    {
        if (! $payment->sale) {
            return null;
        }

        $sale = $payment->sale;
        $amount = (float) $payment->amount;
        $grandTotal = (float) $sale->grandTotal;
        $paidAfter = (float) ($sale->paidAmount ?? 0);
        $remainingBefore = max(0, $grandTotal - ($paidAfter - $amount));

        if ($amount >= $remainingBefore - self::TOLERANCE) {
            return 'Tam tahsilat';
        }

        return 'Kısmi tahsilat';
    }
}
