<?php

namespace App\Models;

use App\Support\UserSchema;
use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'photoUrl',
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

    public function usesTimestamps(): bool
    {
        return UserSchema::createdAtColumn() !== null || UserSchema::updatedAtColumn() !== null;
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

    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class, 'userId');
    }

    public function photoDisplayUrl(): ?string
    {
        if (! Schema::hasColumn($this->getTable(), 'photoUrl') || ! $this->photoUrl) {
            return null;
        }

        return StorageUrl::url($this->photoUrl);
    }

    public function initials(): string
    {
        $initials = collect(explode(' ', $this->name ?? 'K'))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->join('');

        return $initials !== '' ? $initials : 'K';
    }
}
