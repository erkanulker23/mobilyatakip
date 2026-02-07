<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Personnel extends BaseModel
{
    protected $table = 'personnel';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'category',
        'title',
        'vehiclePlate',
        'driverInfo',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'personnelId');
    }
}
