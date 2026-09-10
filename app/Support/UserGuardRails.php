<?php

namespace App\Support;

use App\Models\User;

/**
 * Shared "don't lock yourself out of the system" rules, used by both
 * UserPolicy (delete) and the Filament User resource pages (deactivate,
 * role removal). Kept outside Filament so it is plain, testable domain
 * logic.
 */
class UserGuardRails
{
    public static function isSelf(User $actor, User $target): bool
    {
        return $actor->is($target);
    }

    public static function isLastSuperAdmin(User $target): bool
    {
        if (! $target->hasRole('Super Admin')) {
            return false;
        }

        return User::role('Super Admin')->count() <= 1;
    }
}
