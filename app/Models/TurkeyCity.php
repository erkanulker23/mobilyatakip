<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TurkeyCity extends BaseModel
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $table = 'turkey_cities';

    protected $fillable = ['id', 'name'];

    public function districts(): HasMany
    {
        return $this->hasMany(TurkeyDistrict::class, 'cityId');
    }
}
