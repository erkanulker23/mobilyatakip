<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleActivity extends BaseModel
{
    protected $table = 'sale_activities';

    const TYPE_CREATED = 'created';
    const TYPE_SUPPLIER_EMAIL_SENT = 'supplier_email_sent';
    const TYPE_SUPPLIER_EMAIL_READ = 'supplier_email_read';
    const TYPE_SUPPLIER_EMAIL_REPLIED = 'supplier_email_replied';
    const TYPE_CUSTOMER_EMAIL_SENT = 'customer_email_sent';
    const TYPE_STATUS_CHANGED = 'status_changed';

    protected $fillable = [
        'saleId',
        'type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'createdAt' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'saleId');
    }

    public static function logStatusChange(Sale $sale, ?string $fromStatus, string $toStatus, ?\DateTimeInterface $deliveredAt = null): void
    {
        if ($fromStatus === $toStatus && $toStatus !== \App\Support\SaleDelivery::DELIVERED) {
            return;
        }

        $toLabel = \App\Support\SaleDelivery::label($toStatus);
        if ($toStatus === \App\Support\SaleDelivery::DELIVERED) {
            $dateLabel = $deliveredAt
                ? \Carbon\Carbon::parse($deliveredAt)->format('d.m.Y')
                : now()->format('d.m.Y');
            $description = "Sipariş teslim edildi ({$dateLabel})";
        } elseif ($fromStatus && $fromStatus !== $toStatus) {
            $fromLabel = \App\Support\SaleDelivery::label($fromStatus);
            $description = "Sipariş durumu {$fromLabel} → {$toLabel} olarak güncellendi";
        } else {
            $description = "Sipariş durumu: {$toLabel}";
        }

        self::create([
            'saleId' => $sale->id,
            'type' => self::TYPE_STATUS_CHANGED,
            'description' => $description,
            'metadata' => [
                'fromStatus' => $fromStatus,
                'toStatus' => $toStatus,
                'deliveredAt' => $deliveredAt ? \Carbon\Carbon::parse($deliveredAt)->toIso8601String() : null,
            ],
        ]);
    }
}
