<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends BaseModel
{
    protected $table = 'quotes';

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (Quote $quote) {
            $quote->sales()->update(['quoteId' => null]);
        });
    }

    protected $fillable = [
        'quoteNumber',
        'customerId',
        'kdvIncluded',
        'status',
        'generalDiscountPercent',
        'generalDiscountAmount',
        'revision',
        'subtotal',
        'kdvTotal',
        'grandTotal',
        'validUntil',
        'quoteDate',
        'notes',
        'drawingFiles',
        'isCancelled',
        'convertedSaleId',
        'sourceSaleId',
        'personnelId',
        'branchId',
        'createdBy',
        'customerSource',
    ];

    protected $casts = [
        'kdvIncluded' => 'boolean',
        'subtotal' => 'decimal:2',
        'kdvTotal' => 'decimal:2',
        'grandTotal' => 'decimal:2',
        'generalDiscountPercent' => 'decimal:2',
        'generalDiscountAmount' => 'decimal:2',
        'validUntil' => 'date',
        'quoteDate' => 'date',
        'revision' => 'integer',
        'isCancelled' => 'boolean',
        'drawingFiles' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnelId');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branchId');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createdBy');
    }

    public function items(): HasMany
    {
        $relation = $this->hasMany(QuoteItem::class, 'quoteId');
        // Kolon yokken orderBy 500 verir; migrate öncesi/sonrası güvenli kalsın.
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('quote_items', 'sortOrder')) {
                $relation->orderBy('sortOrder');
            }
        } catch (\Throwable) {
            // şema okunamazsa createdAt sırası yeterli
        }

        return $relation->orderBy('createdAt')->orderBy('id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'quoteId');
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'convertedSaleId');
    }

    public function sourceSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sourceSaleId');
    }

    public function isConverted(): bool
    {
        return $this->convertedSaleId !== null;
    }

    /** Belgede görünen teklif tarihi (yoksa oluşturulma günü). */
    public function issuedAt(): ?\Carbon\CarbonInterface
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'quoteDate') && $this->quoteDate) {
            return $this->quoteDate;
        }

        return $this->createdAt;
    }

    public function generalDiscountApplied(): float
    {
        if ($this->kdvIncluded) {
            $grossBefore = round((float) $this->items->sum('lineTotal'), 2);

            return max(0, round($grossBefore - (float) $this->grandTotal, 2));
        }

        return round(
            (float) $this->subtotal * ((float) ($this->generalDiscountPercent ?? 0) / 100)
            + (float) ($this->generalDiscountAmount ?? 0),
            2
        );
    }
}
