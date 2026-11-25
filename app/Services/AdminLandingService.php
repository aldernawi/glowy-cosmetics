<?php

namespace App\Services;

use App\Models\User;

class AdminLandingService
{
    /** Ordered by how useful each page is as a starting point. */
    private const PAGES = [
        'admin.dashboard' => ['admin.access', 'admin.dashboard.view'],
        'admin.orders.index' => ['orders.view'],
        'admin.products.index' => ['products.view', 'catalog.manage'],
        'admin.stock.index' => ['inventory.view'],
        'admin.stock.adjustments.index' => ['inventory.adjust'],
        'admin.customers.index' => ['customers.view'],
        'admin.reports.index' => ['reports.view'],
        'admin.content.index' => ['content.manage'],
        'admin.delivery.index' => ['delivery.manage'],
        'admin.brands.index' => ['brands.view'],
        'admin.categories.index' => ['categories.view'],
        'admin.users.index' => ['users.manage'],
        'admin.pos.sales.index' => ['pos.manage'],
        'pos.app' => ['pos.use'],
    ];

    public function routeFor(User $user): ?string
    {
        foreach (self::PAGES as $route => $permissions) {
            if ($user->canAny($permissions)) {
                return $route;
            }
        }

        return null;
    }
}
