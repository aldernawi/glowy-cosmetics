<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('admin.access', 'web');
        $catalogPermission = Permission::findOrCreate('catalog.manage', 'web');
        $administrator = Role::findOrCreate('administrator', 'web');
        $catalogManager = Role::findOrCreate('catalog-manager', 'web');

        $administrator->syncPermissions([$permission, $catalogPermission]);
        $catalogManager->syncPermissions([$permission, $catalogPermission]);
    }
}
