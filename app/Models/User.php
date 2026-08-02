<?php

namespace App\Models;

use App\Support\UserSchema;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

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

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! empty($user->getKey()) || ! UserSchema::idIsUuid()) {
                return;
            }

            $user->setAttribute($user->getKeyName(), (string) Str::uuid());
        });
    }

    public function getIncrementing(): bool
    {
        return ! UserSchema::idIsUuid();
    }

    public function getKeyType(): string
    {
        return UserSchema::idIsUuid() ? 'string' : 'int';
    }

    public function getCreatedAtColumn(): ?string
    {
        return UserSchema::createdAtColumn();
    }

    public function getUpdatedAtColumn(): ?string
    {
        return UserSchema::updatedAtColumn();
    }

    public function getAuthPassword(): string
    {
        return $this->passwordHash ?? $this->getAttributeFromArray('password') ?? '';
    }

    public function setPasswordAttribute($value): void
    {
        $hash = bcrypt($value);
        if (Schema::hasColumn($this->getTable(), 'passwordHash')) {
            $this->attributes['passwordHash'] = $hash;
        }
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
