<?php

namespace App\Models;

use App\Models\Concerns\HasTurkeyAddress;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends BaseModel
{
    use HasTurkeyAddress;

    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'code',
        'address',
        'cityId',
        'districtId',
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
