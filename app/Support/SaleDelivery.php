<?php

namespace App\Support;

use App\Models\Sale;

class SaleDelivery
{
    public static function isDelivered(Sale $sale): bool
    {
        return !($sale->isCancelled ?? false) && $sale->deliveredAt !== null;
    }

    public static function badgeClass(): string
    {
        return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300';
    }
}
