# Glowy Operational Administration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a complete, RTL-first operational admin for catalog operations, stock visibility, customers, permissions, and meaningful dashboard KPIs.

**Architecture:** Controllers remain thin and use Form Requests, Policies/Spatie middleware, and focused query/action services. `ProductVariant` is the sellable item and all stock mutations use `StockService`; dashboard, inventory, and customer aggregates are read-only query services.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, Tailwind CSS 4, Alpine.js, Spatie Laravel Permission, PHPUnit, MySQL-compatible migrations.

**Spec:** `docs/superpowers/specs/2026-09-15-glowy-operational-admin-design.md`

## Global Constraints

- Arabic RTL-first custom admin; no Filament, Bootstrap, or admin template.
- `ProductVariant` is canonical; never add product-level stock paths.
- Money fields use `DECIMAL(12,3)` and decimal-safe domain code.
- Stock writes only through `StockService` with a unique idempotency key.
- Controllers use Form Requests and policies/middleware where applicable.
- Coupons are excluded.
- Every task starts RED, proceeds GREEN, runs targeted tests, and commits only verified changes.

---

### Task 1: Add operational schema and role seed foundation

**Files:**
- Create: `database/migrations/2026_09_15_000000_add_operational_admin_fields.php`
- Modify: `app/Models/ProductVariant.php`, `app/Models/User.php`, `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Admin/OperationalAdminSchemaTest.php`

**Interfaces:**
- Produces `ProductVariant::$low_stock_threshold` cast as integer.
- Produces roles `admin` and `catalog-manager` with explicit permissions.

- [ ] Write a failing test asserting a variant defaults to threshold 5 and roles exist after seeding.
- [ ] Run `php artisan test tests/Feature/Admin/OperationalAdminSchemaTest.php` and verify RED.
- [ ] Add nullable/non-negative threshold migration and role seeding with permissions.
- [ ] Run targeted test and migration-backed suite to verify GREEN.
- [ ] Commit `feat: add operational admin schema and roles`.

### Task 2: Add taxonomy CRUD and cycle-safe categories

**Files:**
- Create: `app/Http/Controllers/Admin/CategoryController.php`, `SkinTypeController.php`, `ConcernController.php`, `IngredientController.php`
- Create: `app/Http/Requests/Admin/StoreTaxonomyRequest.php`, `UpdateTaxonomyRequest.php`
- Create: `resources/views/admin/taxonomies/index.blade.php`, `edit.blade.php`
- Modify: `routes/web.php`, `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/TaxonomyManagementTest.php`

**Interfaces:**
- Category updates accept `parent_id`, reject itself/descendants, and create unique slugs.
- Flat taxonomies accept Arabic name, optional English name/description, active and sort fields.

- [ ] Write failing authorization, create/update, and category-cycle tests.
- [ ] Run the test file and verify RED.
- [ ] Implement requests, controller actions, routes, and reusable RTL forms.
- [ ] Run targeted tests and verify GREEN.
- [ ] Commit `feat: manage catalog taxonomies`.

### Task 3: Complete product, variant, and image operations

**Files:**
- Create: `app/Http/Requests/Admin/UpdateProductRequest.php`, `StoreVariantRequest.php`, `StoreProductImageRequest.php`
- Create: `app/Http/Controllers/Admin/ProductVariantController.php`, `ProductImageController.php`
- Modify: `app/Http/Controllers/Admin/ProductController.php`, `app/Services/ProductService.php`, `routes/web.php`
- Create: `resources/views/admin/products/edit.blade.php`, `resources/views/admin/products/_form.blade.php`
- Test: `tests/Feature/Admin/ProductOperationsTest.php`

**Interfaces:**
- Variant action never changes `stock_quantity`; stock belongs to Task 4.
- Product image action stores image, assigns ordered primary state, and promotes a replacement after delete.

- [ ] Write failing tests for variant SKU/price validation, image primary invariant, and media authorization.
- [ ] Run the test file and verify RED.
- [ ] Implement CRUD actions and image storage/ordering with transactional primary selection.
- [ ] Run targeted tests and verify GREEN.
- [ ] Commit `feat: complete product variant and media admin`.

### Task 4: Build stock workspace and low-stock queries

**Files:**
- Create: `app/Services/InventoryQueryService.php`, `app/Http/Controllers/Admin/InventoryController.php`
- Create: `app/Http/Requests/Admin/UpdateLowStockThresholdRequest.php`
- Create: `resources/views/admin/inventory/index.blade.php`, `movements.blade.php`, `low-stock.blade.php`
- Modify: `routes/web.php`, `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/InventoryWorkspaceTest.php`

**Interfaces:**
- `InventoryQueryService::stock(array $filters)`, `movements(array $filters)`, and `lowStock(bool $onlyOut = false)` return paginated builders.
- Threshold writes never alter stock and manual adjustments continue calling `StockService::adjust()`.

- [ ] Write failing low/out-of-stock, ledger filter, threshold update, and idempotent adjustment tests.
- [ ] Run the test file and verify RED.
- [ ] Implement query service, controller, threshold request, routes, and responsive inventory pages.
- [ ] Run targeted tests and verify GREEN.
- [ ] Commit `feat: add inventory workspace and low stock controls`.

### Task 5: Add dashboard KPI query service

**Files:**
- Create: `app/Services/AdminDashboardService.php`
- Modify: `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`
- Test: `tests/Feature/Admin/DashboardKpiTest.php`

**Interfaces:**
- `AdminDashboardService::summary(): array` returns `todaySales`, `monthSales`, `todayOrders`, `activeOrders`, `averageOrderValue`, `customers`, `lowStock`, `outOfStock`, `recentOrders`, and `bestSellers`.

- [ ] Write failing test with delivered/cancelled orders and stock states that asserts KPI inclusion rules.
- [ ] Run targeted test and verify RED.
- [ ] Implement decimal-safe aggregate queries and present KPI cards/tables.
- [ ] Run targeted tests and verify GREEN.
- [ ] Commit `feat: add operational dashboard KPIs`.

### Task 6: Add customer directory and profiles

**Files:**
- Create: `app/Services/CustomerQueryService.php`, `app/Http/Controllers/Admin/CustomerController.php`
- Create: `resources/views/admin/customers/index.blade.php`, `show.blade.php`
- Modify: `routes/web.php`, `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/CustomerDirectoryTest.php`

**Interfaces:**
- `CustomerQueryService::paginate(string $term = '')` returns users with customer order count/spend.
- Customer show returns only the requested authenticated user and their orders.

- [ ] Write failing tests for search, aggregate display, customer order history, and authorization.
- [ ] Run targeted test and verify RED.
- [ ] Implement query/controller/views/routes.
- [ ] Run targeted tests and verify GREEN.
- [ ] Commit `feat: add customer administration`.

### Task 7: Add admin-user role management

**Files:**
- Create: `app/Http/Controllers/Admin/AdminUserController.php`, `app/Http/Requests/Admin/StoreAdminUserRequest.php`, `UpdateAdminUserRolesRequest.php`
- Create: `resources/views/admin/users/index.blade.php`, `create.blade.php`, `edit.blade.php`
- Modify: `routes/web.php`, `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/AdminUserManagementTest.php`

**Interfaces:**
- Store action creates a user and synchronizes existing role names.
- Update action synchronizes roles but rejects the actor removing their own `admin` role.

- [ ] Write failing tests for access denial, role assignment, invalid roles, and self-lockout prevention.
- [ ] Run targeted test and verify RED.
- [ ] Implement role management forms/actions/routes.
- [ ] Run targeted tests and verify GREEN.
- [ ] Commit `feat: manage admin users and roles`.

### Task 8: Integrate navigation, quality gates, and release verification

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`, `README.md`
- Test: existing Phase 3 feature tests

- [ ] Add grouped active admin navigation and responsive mobile behavior for every delivered area.
- [ ] Run each Phase 3 feature test plus full suite.
- [ ] Run `vendor/bin/pint --test` and `npm run build`.
- [ ] Run `php artisan optimize:clear`, `php artisan route:cache`, `php artisan config:cache`, and `php artisan view:cache`.
- [ ] Browser-test RTL at 375/430/768/1024/1440, console/logs, empty states, authorization and 404s.
- [ ] Commit `feat: complete operational administration phase`.
