<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Standard CRUD gate for a Filament resource, driven by Spatie permissions
 * named "{ability}_{$permissionPrefix}" (e.g. "update_service"). Every
 * policy using this trait must declare $permissionPrefix. Super Admin
 * bypasses all of this via Gate::before in AppServiceProvider.
 */
trait AuthorizesResource
{
    public function viewAny(User $user): bool
    {
        return $user->can("view_any_{$this->permissionPrefix}");
    }

    public function view(User $user, $model): bool
    {
        return $user->can("view_any_{$this->permissionPrefix}");
    }

    public function create(User $user): bool
    {
        return $user->can("create_{$this->permissionPrefix}");
    }

    public function update(User $user, $model): bool
    {
        return $user->can("update_{$this->permissionPrefix}");
    }

    public function delete(User $user, $model): bool
    {
        return $user->can("delete_{$this->permissionPrefix}");
    }

    public function deleteAny(User $user): bool
    {
        return $user->can("delete_{$this->permissionPrefix}");
    }
}
