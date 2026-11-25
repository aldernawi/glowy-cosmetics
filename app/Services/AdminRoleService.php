<?php

namespace App\Services;

use App\Models\User;
use Closure;
use DomainException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class AdminRoleService
{
    public const ADMINISTRATOR = 'administrator';

    public const LABELS = [
        'cashier' => 'كاشير',
        'catalog-manager' => 'إدارة الكتالوج',
        self::ADMINISTRATOR => 'مسؤول كامل',
    ];

    /** A null role revokes every administrative role. */
    public function assign(User $user, ?string $role): void
    {
        DB::transaction(function () use ($user, $role): void {
            if ($role !== self::ADMINISTRATOR) {
                $this->ensureAnotherAdministratorExists($user);
            }

            $user->syncRoles($role === null ? [] : [$role]);
        });
    }

    /**
     * The session must end before the account is deleted: logging out afterwards
     * would save the user again while rotating the remember token.
     */
    public function deleteAccount(User $user, Closure $endSession): void
    {
        DB::transaction(function () use ($user, $endSession): void {
            $this->ensureAnotherAdministratorExists($user);
            $endSession();
            $user->delete();
        });
    }

    private function ensureAnotherAdministratorExists(User $user): void
    {
        // Locked before any other read so two administrators cannot remove each other concurrently.
        Permission::query()->where('name', 'admin.access')->lockForUpdate()->get();

        if (! User::query()->findOrFail($user->getKey())->can('admin.access')) {
            return;
        }

        if (! User::permission('admin.access')->whereKeyNot($user->getKey())->exists()) {
            throw new DomainException('The last administrator cannot be removed.');
        }
    }
}
