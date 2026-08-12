<?php

namespace App\Models;

use App\Support\UserSchema;
use App\Support\StorageUrl;
use App\Services\MailConfigService;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class User extends Authenticatable implements CanResetPasswordContract
{
    use Notifiable, CanResetPassword;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'name',
        'password',
        'role',
        'isActive',
        'lastLoginAt',
        'photoUrl',
        'notificationsDismissedAt',
    ];

    protected $hidden = ['password', 'passwordHash', 'remember_token'];

    protected $casts = [
        'isActive' => 'boolean',
        'lastLoginAt' => 'datetime',
        'notificationsDismissedAt' => 'datetime',
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
        return \App\Support\UserRole::isAdmin($this->role);
    }

    /** Personel: dashboard'da önce kendi yapılacak listesi. */
    public function showsPersonalTasksDashboard(): bool
    {
        return ! $this->isAdmin() && ! $this->isWorkshop();
    }

    /** Yapılacaklar sayfasında ekip görevlerini yönetebilir / personele atayabilir. */
    public function canManageTeamTasks(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return (bool) ($this->personnel?->isActive ?? false);
    }

    public function personnelCategory(): ?string
    {
        return $this->personnel?->category;
    }

    public function isWorkshop(): bool
    {
        return \App\Support\WorkshopUser::isWorkshopCategory($this->personnelCategory());
    }

    /** Yönetici paneli: ciro, kasa, teslimat skoru vb. */
    public function canSeeDashboardMetrics(): bool
    {
        return $this->isAdmin();
    }

    /** Atölye personeli: fiyat, tahsilat vb. görmez; müşteri adını operasyon ekranlarında görür. */
    public function hideCommercialData(): bool
    {
        return $this->isWorkshop() && ! $this->isAdmin();
    }

    public function canSeeCustomerNames(): bool
    {
        return ! $this->hideCommercialData() || $this->isWorkshop();
    }

    public function canSeeSalesPersonnel(): bool
    {
        return ! $this->hideCommercialData() || $this->isWorkshop();
    }

    public function isArchitect(): bool
    {
        return $this->personnelCategory() === \App\Support\PersonnelCategory::MIMAR;
    }

    public function lastLoginAtDisplay(): ?\Carbon\CarbonInterface
    {
        return $this->lastLoginAt?->clone()->timezone(config('app.timezone'));
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'userId');
    }

    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class, 'userId');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(UserTask::class, 'userId')->orderBy('sortOrder')->orderBy('dueDate');
    }

    public function photoDisplayUrl(): ?string
    {
        if (! Schema::hasColumn($this->getTable(), 'photoUrl') || ! $this->photoUrl) {
            return null;
        }

        return StorageUrl::url($this->photoUrl);
    }

    /** Kullanıcı veya bağlı personel kartındaki profil fotoğrafı. */
    public function avatarDisplayUrl(): ?string
    {
        if ($url = $this->photoDisplayUrl()) {
            return $url;
        }

        $personnel = $this->relationLoaded('personnel')
            ? $this->personnel
            : $this->personnel()->first(['photoUrl']);

        if ($personnel?->photoUrl) {
            return StorageUrl::url($personnel->photoUrl);
        }

        return null;
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

    public function sendPasswordResetNotification($token): void
    {
        app(MailConfigService::class)->apply();
        $this->notify(new ResetPasswordNotification($token));
    }
}
