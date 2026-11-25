<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function grantAdministrator(User $actor): bool
    {
        return $actor->can('admin.access');
    }

    /** Staff who only manage users cannot change their own role or touch an administrator. */
    public function changeRole(User $actor, User $target): bool
    {
        if ($actor->can('admin.access')) {
            return true;
        }

        return $actor->can('users.manage') && ! $actor->is($target) && ! $target->can('admin.access');
    }
}
