<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends BaseModel
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'sku',
        'externalId',
        'externalSource',
        'unitPrice',
        'netPurchasePrice',
        'kdvIncluded',
        'kdvRate',
        'images',
        'supplierId',
        'minStockLevel',
        'isActive',
        'description',
    ];

    protected $casts = [
        'unitPrice' => 'decimal:2',
        'netPurchasePrice' => 'decimal:2',
        'kdvRate' => 'decimal:2',
        'kdvIncluded' => 'boolean',
        'isActive' => 'boolean',
        'minStockLevel' => 'integer',
        'images' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'productId');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'productId');
    }
}
