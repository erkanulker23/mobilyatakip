<?php

namespace App\Support;

use App\Models\KasaHareket;

class KasaMovement
{
    /** @return array{label: string, tone: string, icon: string} */
    public static function typeBadge(KasaHareket $h): array
    {
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

    /**
     * @param  array{
     *     customerPayments?: \Illuminate\Support\Collection|array,
     *     supplierPayments?: \Illuminate\Support\Collection|array,
     *     expenses?: \Illuminate\Support\Collection|array,
     *     shippingCompanyPayments?: \Illuminate\Support\Collection|array,
     * }  $refs
     * @return array{url: string, label: string}|null
     */
    public static function operationDetail(KasaHareket $h, string $currentKasaId, array $refs = []): ?array
    {
        $refId = $h->refId !== null && $h->refId !== '' ? (string) $h->refId : null;

        $customerPayments = collect($refs['customerPayments'] ?? []);
        $supplierPayments = collect($refs['supplierPayments'] ?? []);
        $expenses = collect($refs['expenses'] ?? []);
        $shippingCompanyPayments = collect($refs['shippingCompanyPayments'] ?? []);

        return match ($h->refType) {
            'customer_payment' => ($cp = $customerPayments->get($refId))
                ? ['url' => route('customer-payments.show', $cp), 'label' => 'Tahsilat detayı']
                : null,
            'supplier_payment' => ($sp = $supplierPayments->get($refId))
                ? ['url' => route('supplier-payments.show', $sp), 'label' => 'Tedarikçi ödemesi detayı']
                : null,
            'expense' => ($expense = $expenses->get($refId))
                ? ['url' => route('expenses.show', $expense), 'label' => 'Gider detayı']
                : null,
            'shipping_company_payment' => ($payment = $shippingCompanyPayments->get($refId))
                ? ['url' => route('shipping-company-payments.show', $payment), 'label' => 'Nakliye ödemesi detayı']
                : null,
            'kasa_transfer' => self::transferOperationDetail($h),
            default => null,
        };
    }

    /** @return array{url: string, label: string}|null */
    private static function transferOperationDetail(KasaHareket $h): ?array
    {
        $otherKasa = (float) $h->amount < 0 ? $h->toKasa : $h->fromKasa;
        if (! $otherKasa) {
            return null;
        }

        return [
            'url' => route('kasa.show', $otherKasa),
            'label' => 'Karşı kasa: ' . $otherKasa->name,
        ];
    }
}
