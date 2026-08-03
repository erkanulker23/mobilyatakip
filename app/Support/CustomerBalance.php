<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Sale;

class CustomerBalance
{
    public static function saleStatus(Sale $sale): array
    {
        return self::statusFromTotals((float) $sale->grandTotal, (float) ($sale->paidAmount ?? 0));
    }

    public static function statusFromTotals(float $total, float $paid): array
    {
        $remaining = round($total - $paid, 2);

        if ($remaining > 0.005) {
            return [
                'key' => 'borclu',
                'label' => 'Borçlu',
                'amount' => $remaining,
                'description' => 'Kalan: ' . number_format($remaining, 0, ',', '.') . ' ₺',
            ];
        }

        if ($remaining < -0.005) {
            $credit = abs($remaining);

            return [
                'key' => 'alacakli',
                'label' => 'Alacaklı',
                'amount' => $credit,
                'description' => 'Fazla ödeme: ' . number_format($credit, 0, ',', '.') . ' ₺',
            ];
        }

        return [
            'key' => 'odendi',
            'label' => 'Ödendi',
            'amount' => 0,
            'description' => 'Borç yok',
        ];
    }

    public static function saleRemaining(Sale $sale): float
    {
        return round((float) $sale->grandTotal - (float) ($sale->paidAmount ?? 0), 2);
    }

    public static function customerStatus(float $totalSales, float $totalPaid): array
    {
        if ($totalSales <= 0.005 && $totalPaid <= 0.005) {
            return [
                'key' => 'siparis_yok',
                'label' => 'Sipariş yok',
                'amount' => 0,
                'amountLabel' => 'Bakiye',
                'description' => 'Henüz sipariş kaydı yok',
            ];
        }

        $balance = round($totalSales - $totalPaid, 2);

        if ($balance > 0.005) {
            return [
                'key' => 'borclu',
                'label' => 'Borçlu',
                'amount' => $balance,
                'amountLabel' => 'Kalan borç',
                'description' => 'Müşteri borçlu — kalan: ' . number_format($balance, 0, ',', '.') . ' ₺',
            ];
        }

        if ($balance < -0.005) {
            $credit = abs($balance);

            return [
                'key' => 'alacakli',
                'label' => 'Alacaklı',
                'amount' => $credit,
                'amountLabel' => 'Fazla ödeme',
                'description' => 'Fazla tahsilat: ' . number_format($credit, 0, ',', '.') . ' ₺',
            ];
        }

        return [
            'key' => 'borcu_yok',
            'label' => 'Borcu yoktur',
            'amount' => 0,
            'amountLabel' => 'Kalan borç',
            'description' => 'Açık borç bulunmuyor',
        ];
    }

    public static function customerStatusFor(Customer $customer): array
    {
        $totalSales = (float) $customer->sales()->where('isCancelled', false)->sum('grandTotal');
        $totalPaid = (float) $customer->payments()->sum('amount');

        return self::customerStatus($totalSales, $totalPaid);
    }

    public static function badgeClass(string $key): string
    {
        return match ($key) {
            'borclu' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            'alacakli' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            'siparis_yok' => 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400',
            'borcu_yok', 'odendi', 'dengede' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        };
    }
}
