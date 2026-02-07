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

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'kasaId');
    }

    /** Açılış + tahsilatlar - tedarikçi ödemeleri - giderler + kasa hareketleri (giris +, cikis -) */
    public function getBalanceAttribute(): float
    {
        $opening = (float) ($this->openingBalance ?? 0);
        $tahsilat = (float) $this->customerPayments()->sum('amount');
        $odeme = (float) $this->supplierPayments()->sum('amount');
        $gider = (float) $this->expenses()->sum('amount');
        $giris = (float) $this->hareketler()->where('type', 'giris')->sum('amount');
        $cikis = (float) $this->hareketler()->where('type', 'cikis')->sum('amount');
        return $opening + $tahsilat - $odeme - $gider + $giris - $cikis;
    }
}
