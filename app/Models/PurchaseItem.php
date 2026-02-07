<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends BaseModel
{
    protected $table = 'purchase_items';

    protected $fillable = [
        'purchaseId',
        'productId',
        'unitPrice',
        'listPrice',
        'quantity',
        'kdvRate',
        'lineTotal',
    ];

    protected $casts = [
        'unitPrice' => 'decimal:2',
        'listPrice' => 'decimal:2',
        'lineTotal' => 'decimal:2',
        'kdvRate' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchaseId');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productId');
    }
}
