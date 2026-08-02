<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'createdAt';

    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'email',
        'name',
        'password',
        'role',
        'isActive',
    ];

    protected $hidden = ['password', 'passwordHash', 'remember_token'];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    public function getAuthPassword(): string
    {
        return $this->passwordHash ?? $this->getAttributeFromArray('password') ?? '';
    }

    public function setPasswordAttribute($value): void
    {
        $hash = bcrypt($value);
        $this->attributes['passwordHash'] = $hash;
        if (Schema::hasColumn($this->getTable(), 'password')) {
            $this->attributes['password'] = $hash;
        }
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
