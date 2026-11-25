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

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('catalog.manage', 'web'); // Backward-compatible umbrella permission for pre-existing staff.
        foreach ([
            'admin.dashboard.view',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'inventory.view', 'inventory.adjust',
            'orders.view', 'orders.update',
            'delivery.manage', 'content.manage', 'reports.view', 'users.manage', 'customers.view',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $administrator = Role::findOrCreate('administrator', 'web');
        $catalogManager = Role::findOrCreate('catalog-manager', 'web');

        $administrator->syncPermissions(Permission::query()->pluck('name')->all());
        $catalogManager->syncPermissions([
            'catalog.manage',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        ]);
    }
}
