<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingCompanyVehicle extends BaseModel
{
    protected $table = 'shipping_company_vehicles';

    protected $fillable = [
        'shippingCompanyId',
        'vehiclePlate',
        'driverName',
        'driverPhone',
        'notes',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shippingCompanyId');
    }

    public function label(): string
    {
        $parts = [$this->vehiclePlate];
        if ($this->driverName) {
            $parts[] = $this->driverName;
        }

        return implode(' — ', $parts);
    }
}
