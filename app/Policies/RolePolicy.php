<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesResource;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use AuthorizesResource;

    protected string $permissionPrefix = 'role';

    /**
     * The 'Super Admin' role is load-bearing for AppServiceProvider's
     * Gate::before bypass; it must never be deletable from the UI.
     */
    public function delete(User $user, Role $model): bool
    {
        if (! $user->can('delete_role')) {
            return false;
        }

        return $model->name !== 'Super Admin';
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_role');
    }
}
