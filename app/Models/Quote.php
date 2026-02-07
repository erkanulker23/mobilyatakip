<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends BaseModel
{
    protected $table = 'quotes';

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
        'notes',
        'isCancelled',
        'convertedSaleId',
        'personnelId',
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
        'revision' => 'integer',
        'isCancelled' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnelId');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'quoteId');
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'convertedSaleId');
    }
}
