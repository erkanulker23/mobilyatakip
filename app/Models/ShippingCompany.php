<?php

namespace App\Models;

use App\Models\Concerns\HasTurkeyAddress;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCompany extends BaseModel
{
    use HasTurkeyAddress;

    protected $table = 'shipping_companies';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'cityId',
        'districtId',
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

    public function vehicles(): HasMany
    {
        return $this->hasMany(ShippingCompanyVehicle::class, 'shippingCompanyId')->orderBy('vehiclePlate');
    }

    public function activeVehicles(): HasMany
    {
        return $this->vehicles()->where('isActive', true);
    }
}
