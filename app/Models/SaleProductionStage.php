<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleProductionStage extends BaseModel
{
    public const UPDATED_AT = null;

    public const TYPE_STAGE = 'asama';

    public const TYPE_DEFICIENCY = 'eksiklik';

    protected $table = 'sale_production_stages';

    protected $fillable = [
        'saleId',
        'userId',
        'type',
        'notes',
        'isCompleted',
        'completedAt',
        'completedByUserId',
        'actionDate',
    ];

    protected $casts = [
        'actionDate' => 'datetime',
        'createdAt' => 'datetime',
        'isCompleted' => 'boolean',
        'completedAt' => 'datetime',
    ];

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_STAGE => 'Aşama',
            self::TYPE_DEFICIENCY => 'Eksiklik',
        ];
    }

    public static function typeLabel(?string $type): string
    {
        return self::typeOptions()[$type] ?? $type ?? '—';
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'saleId');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completedByUserId');
    }
}
