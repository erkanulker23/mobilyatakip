<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends BaseModel
{
    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'code',
        'address',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'warehouseId');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouseId');
    }
}
