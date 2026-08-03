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

    public const FINAL_MEASUREMENT = 'final_measurement';

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

    /** Satış listesi teslim filtresi — ölçü bekleyenler dahil. */
    public static function filterOptions(): array
    {
        return [
            self::PENDING => 'Teslim bekliyor',
            self::FINAL_MEASUREMENT => 'Ölçü bekliyor',
            self::IN_DISCUSSION => 'Halen görüşülüyor',
            self::IN_PRODUCTION => 'Üretimde',
            self::DELIVERED => 'Teslim edildi',
            self::SSH => 'SSH var',
        ];
    }

    public static function isFilterValue(?string $value): bool
    {
        return $value !== null && array_key_exists($value, self::filterOptions());
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
        if ($sale->isCancelled ?? false) {
            return false;
        }

        return $sale->deliveredAt !== null
            || ($sale->orderStatus ?? null) === self::DELIVERED;
    }

    /**
     * Satış listesi termin sütunu — teslim edildiyse gecikme uyarısı göstermez.
     *
     * @return array{prefix: ?string, date: ?\Carbon\CarbonInterface, suffix: ?string, class: string, empty: ?string}
     */
    public static function terminListMeta(Sale $sale): array
    {
        if (self::isDelivered($sale)) {
            $deliveredAt = $sale->deliveredAt;
            $suffix = self::deliveredRelativeToTermin($sale);
            $class = 'text-indigo-600 dark:text-indigo-400';
            if ($sale->dueDate && $deliveredAt) {
                $daysFromTermin = (int) $sale->dueDate->copy()->startOfDay()
                    ->diffInDays($deliveredAt->copy()->startOfDay(), false);
                if ($daysFromTermin > 0) {
                    $class = $daysFromTermin <= 3
                        ? 'text-amber-600 dark:text-amber-400'
                        : 'text-red-600 dark:text-red-400';
                } elseif ($daysFromTermin < 0) {
                    $class = 'text-emerald-600 dark:text-emerald-400';
                }
            }

            return [
                'prefix' => 'Teslim',
                'date' => $deliveredAt ?? $sale->dueDate,
                'suffix' => $suffix,
                'class' => $class,
                'empty' => null,
            ];
        }

        if (!$sale->dueDate) {
            return [
                'prefix' => null,
                'date' => null,
                'suffix' => null,
                'class' => 'text-neutral-400 dark:text-neutral-500',
                'empty' => 'Termin yok',
            ];
        }

        $daysLeft = (int) now()->startOfDay()->diffInDays($sale->dueDate, false);

        if ($daysLeft < 0) {
            $class = 'text-red-600 dark:text-red-400';
            $suffix = abs($daysLeft) . ' gün gecikti';
        } elseif ($daysLeft === 0) {
            $class = 'text-amber-600 dark:text-amber-400';
            $suffix = 'Termin bugün';
        } elseif ($daysLeft <= 3) {
            $class = 'text-amber-600 dark:text-amber-400';
            $suffix = $daysLeft . ' gün kaldı';
        } else {
            $class = 'text-neutral-500 dark:text-neutral-400';
            $suffix = $daysLeft . ' gün';
        }

        return [
            'prefix' => 'Termin',
            'date' => $sale->dueDate,
            'suffix' => $suffix,
            'class' => $class,
            'empty' => null,
        ];
    }

    /** Termin tarihine göre teslim farkı: "3 gün önce teslim edildi" vb. */
    public static function deliveredRelativeToTermin(Sale $sale): ?string
    {
        if (! self::isDelivered($sale) || ! $sale->deliveredAt || ! $sale->dueDate) {
            return null;
        }

        $daysFromTermin = (int) $sale->dueDate->copy()->startOfDay()
            ->diffInDays($sale->deliveredAt->copy()->startOfDay(), false);

        if ($daysFromTermin < 0) {
            return abs($daysFromTermin) . ' gün önce teslim edildi';
        }

        if ($daysFromTermin > 0) {
            return $daysFromTermin . ' gün sonra teslim edildi';
        }

        return 'termin gününde teslim edildi';
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

    public static function numberClassFor(Sale $sale): string
    {
        if ($sale->isCancelled ?? false) {
            return 'text-neutral-500 dark:text-neutral-400 line-through';
        }

        if ($sale->needsFinalMeasurement ?? false) {
            return self::numberClass(self::FINAL_MEASUREMENT);
        }

        return self::numberClass(self::currentStatus($sale));
    }

    /** Satış listesinde sipariş numarası rengi — her teslimat durumu farklı. */
    public static function numberClass(?string $status = null): string
    {
        return match ($status) {
            self::FINAL_MEASUREMENT => 'text-amber-600 dark:text-amber-400',
            self::DELIVERED => 'text-indigo-600 dark:text-indigo-400',
            self::SSH => 'text-orange-600 dark:text-orange-400',
            self::IN_PRODUCTION => 'text-violet-600 dark:text-violet-400',
            self::IN_DISCUSSION => 'text-sky-600 dark:text-sky-400',
            self::PENDING => 'text-teal-700 dark:text-teal-400',
            default => 'text-neutral-900 dark:text-neutral-100',
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
