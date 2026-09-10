<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesResource;
use App\Support\UserGuardRails;

class UserPolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'user';

    public function delete(User $user, User $model): bool
    {
        if (! $user->can('delete_user')) {
            return false;
        }

        if (UserGuardRails::isSelf($user, $model)) {
            return false;
        }

        return ! UserGuardRails::isLastSuperAdmin($model);
    }
}
