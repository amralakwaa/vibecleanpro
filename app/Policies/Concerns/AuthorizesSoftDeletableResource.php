<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Adds restore/force-delete gating for soft-deletable resources. Force
 * delete is deliberately its own permission, never bundled with "delete" -
 * only Super Admin gets it (via Gate::before), no seeded role is granted it.
 */
trait AuthorizesSoftDeletableResource
{
    public function restore(User $user, $model): bool
    {
        return $user->can("restore_{$this->permissionPrefix}");
    }

    public function restoreAny(User $user): bool
    {
        return $user->can("restore_{$this->permissionPrefix}");
    }

    public function forceDelete(User $user, $model): bool
    {
        return $user->can("force_delete_{$this->permissionPrefix}");
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can("force_delete_{$this->permissionPrefix}");
    }
}
