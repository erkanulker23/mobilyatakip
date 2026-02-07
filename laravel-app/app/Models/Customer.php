<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'taxNumber',
        'taxOffice',
        'identityNumber',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'customerId');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customerId');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'customerId');
    }

    public function serviceTickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'customerId');
    }
}
