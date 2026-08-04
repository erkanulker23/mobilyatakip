<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Kasa;
use App\Models\Personnel;
use App\Models\Sale;

class AuditChangeDescriber
{
    /** @var array<string, array<string, string>> */
    private const FIELD_LABELS = [
        'sale' => [
            'notes' => 'açıklama',
            'grandTotal' => 'toplam tutar',
            'customerId' => 'müşteri',
            'personnelId' => 'personel',
            'dueDate' => 'termin tarihi',
            'saleDate' => 'satış tarihi',
            'needsFinalMeasurement' => 'kesin ölçü',
            'saleDiscountPercent' => 'genel iskonto',
            'itemsCount' => 'kalem sayısı',
            'itemsChanged' => 'sipariş kalemleri',
            'orderStatus' => 'teslim durumu',
        ],
        'customer_payment' => [
            'amount' => 'tahsilat tutarı',
            'paymentType' => 'ödeme tipi',
            'saleId' => 'bağlı fatura',
            'kasaId' => 'kasa',
            'paymentDate' => 'tahsilat tarihi',
            'reference' => 'referans',
        ],
        'quote' => [
            'notes' => 'açıklama',
            'grandTotal' => 'toplam tutar',
            'customerId' => 'müşteri',
            'personnelId' => 'personel',
            'validUntil' => 'geçerlilik tarihi',
            'status' => 'durum',
        ],
        'user_task' => [
            'title' => 'başlık',
            'notes' => 'not',
            'dueDate' => 'termin',
            'color' => 'renk',
        ],
        'customer' => [
            'name' => 'ad',
            'phone' => 'telefon',
            'email' => 'e-posta',
        ],
        'service_ticket' => [
            'description' => 'açıklama',
            'status' => 'durum',
            'dueDate' => 'termin',
        ],
        'expense' => [
            'amount' => 'tutar',
            'description' => 'açıklama',
        ],
    ];

    /** @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     * @return list<string>
     */
    public static function describe(string $entity, ?array $old, ?array $new): array
    {
        $old = self::normalize($old);
        $new = self::normalize($new);

        if (! empty($new['changes']) && is_array($new['changes'])) {
            return array_values(array_filter(array_map('strval', $new['changes'])));
        }

        $explicit = [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $skip = ['saleNumber', 'quoteNumber', 'ticketNumber', 'purchaseNumber', 'name', 'title', 'changes', '_actorName', 'statusLabel', 'fromStatusLabel', 'itemsFingerprint'];

        foreach ($keys as $key) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;

            if (self::valuesEqual($oldVal, $newVal)) {
                continue;
            }

            $label = self::FIELD_LABELS[$entity][$key] ?? str_replace('_', ' ', $key);
            $explicit[] = self::phraseChange($entity, $key, $label, $oldVal, $newVal);
        }

        return $explicit;
    }

    /** @param  array<string, mixed>|null  $data
     * @return array<string, mixed>
     */
    private static function normalize(?array $data): array
    {
        if ($data === null) {
            return [];
        }

        return collect($data)->except('_actorName')->all();
    }

    private static function valuesEqual(mixed $old, mixed $new): bool
    {
        if (is_numeric($old) && is_numeric($new)) {
            return abs((float) $old - (float) $new) < 0.005;
        }

        if (is_bool($old) || is_bool($new)) {
            return (bool) $old === (bool) $new;
        }

        return (string) ($old ?? '') === (string) ($new ?? '');
    }

    private static function phraseChange(string $entity, string $key, string $label, mixed $old, mixed $new): string
    {
        if ($key === 'itemsChanged') {
            return $new ? 'sipariş kalemleri güncellendi' : '';
        }

        $oldEmpty = $old === null || $old === '' || $old === false;
        $newEmpty = $new === null || $new === '' || $new === false;

        if ($oldEmpty && ! $newEmpty) {
            return "{$label} eklendi" . self::valueSuffix($entity, $key, $new);
        }

        if (! $oldEmpty && $newEmpty) {
            return "{$label} kaldırıldı";
        }

        $from = self::formatValue($entity, $key, $old);
        $to = self::formatValue($entity, $key, $new);

        if ($from !== '' && $to !== '' && $from !== $to) {
            return "{$label} güncellendi ({$from} → {$to})";
        }

        return "{$label} güncellendi";
    }

    private static function valueSuffix(string $entity, string $key, mixed $value): string
    {
        $formatted = self::formatValue($entity, $key, $value);

        return $formatted !== '' ? " ({$formatted})" : '';
    }

    private static function formatValue(string $entity, string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Evet' : 'Hayır';
        }

        if (in_array($key, ['grandTotal', 'amount', 'saleDiscountPercent', 'lineDiscountPercent'], true) && is_numeric($value)) {
            if (in_array($key, ['saleDiscountPercent', 'lineDiscountPercent'], true)) {
                return '%' . Money::format($value);
            }

            return Money::format($value) . ' ₺';
        }

        if ($key === 'paymentType') {
            $label = PaymentType::label((string) $value);

            return $label !== '—' ? $label : (string) $value;
        }

        if ($key === 'orderStatus') {
            return SaleDelivery::label((string) $value);
        }

        if ($key === 'color') {
            return UserTaskColor::classes(UserTaskColor::normalize((string) $value))['label'] ?? (string) $value;
        }

        if ($key === 'customerId') {
            return Customer::find($value)?->name ?? (string) $value;
        }

        if ($key === 'personnelId') {
            return Personnel::find($value)?->name ?? (string) $value;
        }

        if ($key === 'kasaId') {
            return Kasa::find($value)?->name ?? (string) $value;
        }

        if ($key === 'saleId') {
            return Sale::find($value)?->saleNumber ?? (string) $value;
        }

        return (string) $value;
    }
}
