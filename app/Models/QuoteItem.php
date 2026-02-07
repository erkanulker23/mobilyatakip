<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends BaseModel
{
    protected $table = 'quote_items';

    protected $fillable = [
        'quoteId',
        'productId',
        'unitPrice',
        'quantity',
        'lineDiscountPercent',
        'lineDiscountAmount',
        'kdvRate',
        'lineTotal',
    ];

    protected $casts = [
        'unitPrice' => 'decimal:2',
        'lineTotal' => 'decimal:2',
        'lineDiscountPercent' => 'decimal:2',
        'lineDiscountAmount' => 'decimal:2',
        'kdvRate' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quoteId');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productId');
    }
}
