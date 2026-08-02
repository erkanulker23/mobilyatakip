<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurkeyDistrict extends BaseModel
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $table = 'turkey_districts';

    protected $fillable = ['id', 'cityId', 'name'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(TurkeyCity::class, 'cityId');
    }
}
