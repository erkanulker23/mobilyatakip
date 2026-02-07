<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends BaseModel
{
    protected $table = 'sale_items';

    protected $fillable = [
        'saleId',
        'productId',
        'productName',
        'unitPrice',
        'quantity',
        'kdvRate',
        'lineDiscountPercent',
        'lineDiscountAmount',
        'lineTotal',
    ];

    protected $casts = [
        'unitPrice' => 'decimal:2',
        'lineTotal' => 'decimal:2',
        'kdvRate' => 'decimal:2',
        'lineDiscountPercent' => 'decimal:2',
        'lineDiscountAmount' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'saleId');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productId');
    }
}
