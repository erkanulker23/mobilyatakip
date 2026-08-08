<?php

namespace App\Support;

use App\Models\KasaHareket;

class KasaMovement
{
    public static function isExpenseReversal(KasaHareket $h): bool
    {
        return str_starts_with((string) ($h->description ?? ''), 'Gider iptal');
    }

    /** @return array{label: string, tone: string, icon: string} */
    public static function typeBadge(KasaHareket $h): array
    {
        if (self::isExpenseReversal($h)) {
            return ['label' => 'Gider iptali', 'tone' => 'slate', 'icon' => '↩'];
        }

        if ($h->refType === 'kasa_transfer' || in_array($h->type, ['virman_cikis', 'virman_giris'], true)) {
            return ['label' => 'Virman', 'tone' => 'indigo', 'icon' => '↔'];
        }

        return match ($h->refType) {
            'customer_payment' => ['label' => 'Tahsilat', 'tone' => 'emerald', 'icon' => '+'],
            'supplier_payment' => ['label' => 'Tedarikçi Ödemesi', 'tone' => 'amber', 'icon' => '−'],
            'expense' => ['label' => 'Gider', 'tone' => 'rose', 'icon' => '−'],
            default => match ($h->type) {
                'giris' => ['label' => 'Giriş', 'tone' => 'emerald', 'icon' => '+'],
                'cikis' => ['label' => 'Çıkış', 'tone' => 'rose', 'icon' => '−'],
                default => ['label' => ucfirst($h->type ?? 'Hareket'), 'tone' => 'slate', 'icon' => '•'],
            },
        };
    }

    public static function toneClasses(string $tone): string
    {
        return match ($tone) {
            'emerald' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
            'rose' => 'bg-rose-50 text-rose-800 border-rose-200',
            'indigo' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            default => 'bg-neutral-50 text-neutral-700 border-neutral-200',
        };
    }

    public static function transferDetail(KasaHareket $h, string $currentKasaId): ?string
    {
        if ($h->refType !== 'kasa_transfer' && ! in_array($h->type, ['virman_cikis', 'virman_giris'], true)) {
            return null;
        }

        if ($h->type === 'virman_cikis' || ($h->amount < 0 && $h->toKasa)) {
            return '→ ' . ($h->toKasa?->name ?? 'Diğer kasa');
        }

        if ($h->type === 'virman_giris' || ($h->amount > 0 && $h->fromKasa)) {
            return '← ' . ($h->fromKasa?->name ?? 'Diğer kasa');
        }

        return null;
    }
}
