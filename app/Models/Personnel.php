<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personnel extends BaseModel
{
    protected $table = 'personnel';

    protected $fillable = [
        'name',
        'email',
        'userId',
        'phone',
        'category',
        'title',
        'photoUrl',
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

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'personnelId');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(UserTask::class, 'personnelId');
    }

    public function hasSystemAccess(): bool
    {
        if (! $this->userId) {
            return false;
        }

        if ($this->relationLoaded('user')) {
            return (bool) ($this->user?->isActive);
        }

        return User::query()->whereKey($this->userId)->where('isActive', true)->exists();
    }
}
