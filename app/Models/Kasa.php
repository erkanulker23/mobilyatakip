<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Kasa extends BaseModel
{
    protected $table = 'kasa';

    protected $fillable = [
        'name',
        'type',
        'accountNumber',
        'iban',
        'bankName',
        'openingBalance',
        'currency',
        'isActive',
    ];

    protected $casts = [
        'openingBalance' => 'decimal:2',
        'isActive' => 'boolean',
    ];

    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'kasaId');
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'kasaId');
    }

    public function hareketler(): HasMany
    {
        return $this->hasMany(KasaHareket::class, 'kasaId');
    }
}
