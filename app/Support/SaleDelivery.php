<?php

namespace App\Support;

use App\Models\Sale;

class SaleDelivery
{
    public const PENDING = 'pending';

    public const DELIVERED = 'delivered';

    public const SSH = 'ssh';

    public const IN_PRODUCTION = 'in_production';

    public const IN_DISCUSSION = 'in_discussion';

    /** @return list<string> */
    public static function statuses(): array
    {
        return [
            self::PENDING,
            self::IN_DISCUSSION,
            self::IN_PRODUCTION,
            self::DELIVERED,
            self::SSH,
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::PENDING => 'Teslim bekliyor',
            self::IN_DISCUSSION => 'Halen görüşülüyor',
            self::IN_PRODUCTION => 'Üretimde',
            self::DELIVERED => 'Teslim edildi',
            self::SSH => 'SSH var',
        ];
    }

    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::statuses());
    }

    public static function currentStatus(Sale $sale): string
    {
        if ($sale->isCancelled ?? false) {
            return self::PENDING;
        }

        $status = $sale->orderStatus ?? null;
        if (in_array($status, self::statuses(), true)) {
            return $status;
        }

        return $sale->deliveredAt !== null ? self::DELIVERED : self::PENDING;
    }

    public static function isDelivered(Sale $sale): bool
    {
        return !($sale->isCancelled ?? false) && $sale->deliveredAt !== null;
    }

    public static function isSsh(Sale $sale): bool
    {
        return !($sale->isCancelled ?? false) && self::currentStatus($sale) === self::SSH;
    }

    public static function label(?string $status = null): string
    {
        return self::options()[$status] ?? self::options()[self::PENDING];
    }

    public static function badgeClass(?string $status = null): string
    {
        return match ($status) {
            self::DELIVERED => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
            self::SSH => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            self::IN_PRODUCTION => 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-300',
            self::IN_DISCUSSION => 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
            default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
        };
    }

    public static function syncFromServiceTickets(Sale $sale): void
    {
        if ($sale->isCancelled ?? false) {
            return;
        }

        $hasOpenTickets = $sale->serviceTickets()
            ->whereNotIn('status', ['tamamlandi', 'iptal'])
            ->exists();

        if ($hasOpenTickets) {
            if ($sale->orderStatus !== self::SSH) {
                $sale->update(['orderStatus' => self::SSH]);
            }

            return;
        }

        if ($sale->orderStatus === self::SSH) {
            $sale->update([
                'orderStatus' => $sale->deliveredAt !== null ? self::DELIVERED : self::PENDING,
            ]);
        }
    }
}
