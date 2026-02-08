<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCompany extends BaseModel
{
    protected $table = 'shipping_companies';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'shippingCompanyId');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ShippingCompanyPayment::class, 'shippingCompanyId');
    }
}
