<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends BaseModel
{
    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'taxNumber',
        'taxOffice',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplierId');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplierId');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'supplierId');
    }

    public function statements(): HasMany
    {
        return $this->hasMany(SupplierStatement::class, 'supplierId');
    }
}
