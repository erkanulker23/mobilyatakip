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
        'isDefault',
    ];

    protected $casts = [
        'openingBalance' => 'decimal:2',
        'isActive' => 'boolean',
        'isDefault' => 'boolean',
    ];

    /** Aktif kasalar: varsayılan önce, sonra ada göre. */
    public function scopeActiveForSelect($query)
    {
        return $query->where('isActive', true)
            ->orderByDesc('isDefault')
            ->orderBy('name');
    }

    public static function defaultId(): ?string
    {
        return static::query()
            ->where('isActive', true)
            ->where('isDefault', true)
            ->value('id');
    }

    /** Tek varsayılan kalsın diye diğerlerinin bayrağını temizler. */
    public static function clearDefaultExcept(?string $exceptId = null): void
    {
        $q = static::query()->where('isDefault', true);
        if ($exceptId) {
            $q->where('id', '!=', $exceptId);
        }
        $q->update(['isDefault' => false]);
    }

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
