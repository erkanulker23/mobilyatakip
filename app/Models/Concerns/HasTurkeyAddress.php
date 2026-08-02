<?php

namespace App\Models\Concerns;

use App\Models\TurkeyCity;
use App\Models\TurkeyDistrict;
use App\Support\AddressFormat;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTurkeyAddress
{
    public function city(): BelongsTo
    {
        return $this->belongsTo(TurkeyCity::class, 'cityId');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(TurkeyDistrict::class, 'districtId');
    }

    public function getFullAddressAttribute(): string
    {
        return AddressFormat::format($this);
    }
}
