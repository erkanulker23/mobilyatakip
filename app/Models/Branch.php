<?php

namespace App\Models;

use App\Models\Concerns\HasTurkeyAddress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends BaseModel
{
    use HasTurkeyAddress;

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'code',
        'phone',
        'address',
        'cityId',
        'districtId',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'branchId');
    }

    public function serviceTickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'branchId');
    }

    /** @return Collection<int, Branch> */
    public static function forSelect(bool $activeOnly = true): Collection
    {
        $q = static::query()->orderBy('name');
        if ($activeOnly) {
            $q->where('isActive', true);
        }

        return $q->get(['id', 'name', 'code', 'isActive']);
    }

    public function displayName(): string
    {
        $code = trim((string) ($this->code ?? ''));

        return $code !== '' ? $this->name.' ('.$code.')' : $this->name;
    }
}
