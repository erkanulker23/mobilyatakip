<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'users';

    protected $fillable = [
        'email',
        'name',
        'role',
        'isActive',
    ];

    protected $hidden = ['passwordHash'];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function getAuthPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['passwordHash'] = bcrypt($value);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'userId');
    }
}
