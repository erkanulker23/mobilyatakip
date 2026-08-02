<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Support\UserSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PersonnelAccessService
{
    /**
     * @param  array{canAccessSystem?: bool, systemRole?: string, password?: string|null}  $access
     */
    public function sync(Personnel $personnel, array $access): void
    {
        $canAccess = (bool) ($access['canAccessSystem'] ?? false);
        $role = ($access['systemRole'] ?? 'staff') === 'admin' ? 'admin' : 'staff';
        $password = $access['password'] ?? null;

        if (! $canAccess) {
            $this->disableAccess($personnel);

            return;
        }

        if (empty($personnel->email)) {
            throw ValidationException::withMessages([
                'email' => 'Sistem erişimi için e-posta adresi zorunludur.',
            ]);
        }

        DB::transaction(function () use ($personnel, $role, $password) {
            $user = $this->resolveLinkedUser($personnel);

            if (! $user && empty($password)) {
                throw ValidationException::withMessages([
                    'password' => 'İlk kez sistem erişimi verilirken şifre belirlemeniz gerekir.',
                ]);
            }

            $emailTaken = User::query()
                ->where('email', $personnel->email)
                ->when($user, fn ($q) => $q->where('id', '!=', $user->getKey()))
                ->exists();

            if ($emailTaken) {
                throw ValidationException::withMessages([
                    'email' => 'Bu e-posta adresi başka bir kullanıcı hesabında kayıtlı.',
                ]);
            }

            if (! $user) {
                $user = new User;
            }

            $user->name = $personnel->name;
            $user->email = $personnel->email;
            $user->role = $role;
            $user->isActive = (bool) $personnel->isActive;

            if (! empty($password)) {
                $user->password = $password;
            }

            $user->save();

            if ($personnel->userId !== $user->getKey()) {
                $personnel->userId = $user->getKey();
                $personnel->saveQuietly();
            }
        });
    }

    public function disableAccess(Personnel $personnel): void
    {
        if (! $personnel->userId) {
            return;
        }

        $user = User::find($personnel->userId);
        if ($user) {
            $user->isActive = false;
            $user->save();
        }
    }

    public function syncActiveState(Personnel $personnel): void
    {
        if (! $personnel->userId) {
            return;
        }

        $user = User::find($personnel->userId);
        if (! $user || ! $user->isActive) {
            return;
        }

        $user->isActive = (bool) $personnel->isActive;
        $user->name = $personnel->name;
        if ($personnel->email) {
            $user->email = $personnel->email;
        }
        $user->save();
    }

    private function resolveLinkedUser(Personnel $personnel): ?User
    {
        if ($personnel->userId) {
            $linked = User::find($personnel->userId);
            if ($linked) {
                return $linked;
            }
        }

        if ($personnel->email) {
            return User::where('email', $personnel->email)->first();
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function validationRules(Personnel $personnel, bool $canAccess): array
    {
        $linkedUser = $personnel->user ?? ($personnel->userId ? User::find($personnel->userId) : null);

        $passwordRules = ['nullable', 'string', 'min:8', 'confirmed'];
        if ($canAccess && ! $linkedUser) {
            $passwordRules[0] = 'required';
        }

        return [
            'canAccessSystem' => ['nullable', 'boolean'],
            'systemRole' => ['nullable', Rule::in(['admin', 'staff'])],
            'password' => $passwordRules,
        ];
    }

    public function canManageAccess(?User $actor): bool
    {
        return $actor !== null && $actor->isAdmin();
    }
}
