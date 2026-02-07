<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends BaseModel
{
    public $timestamps = false;

    protected $table = 'stocks';

    protected $fillable = [
        'productId',
        'warehouseId',
        'quantity',
        'reservedQuantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reservedQuantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouseId');
    }
}
