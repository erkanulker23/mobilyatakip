<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WardrobeProject;

class WardrobeProjectPolicy
{
    public function view(User $user, WardrobeProject $project): bool
    {
        return $this->owns($user, $project);
    }

    public function update(User $user, WardrobeProject $project): bool
    {
        return $this->owns($user, $project);
    }

    public function delete(User $user, WardrobeProject $project): bool
    {
        return $this->owns($user, $project);
    }

    private function owns(User $user, WardrobeProject $project): bool
    {
        if ($project->userId === null || $project->userId === '') {
            return $user->isAdmin();
        }

        return (string) $project->userId === (string) $user->id;
    }
};
