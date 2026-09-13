# Glowy Bootstrap and Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the Laravel 13 foundation for Glowy with secure authentication, admin authorization, RTL layouts, visual tokens, and a verified test and cache baseline.

**Architecture:** A Laravel monolith serves public storefront and `/admin` dashboard shells through controllers and Blade layouts. Authentication uses Laravel's session guard; role and permission checks use Spatie Laravel Permission. Reusable Blade components carry RTL semantics and the Glowy design tokens, while future commercial rules remain in domain services rather than controllers.

**Tech Stack:** PHP 8.4, Laravel 13, MySQL 8, Blade, Livewire 4, Tailwind CSS 4, Vite, Pest, Spatie Laravel Permission.

**Spec:** `docs/superpowers/specs/2026-09-13-glowy-foundation-design.md`

## Global Constraints

- Use Laravel 13, PHP 8.4+, MySQL 8+, Blade, Livewire 4, Tailwind CSS 4, and Vite.
- Use Arabic as the initial UI language and implement RTL-first markup and styles.
- Do not use Filament, Bootstrap, a third-party admin template, floats for money, route closures for application pages, or committed secrets.
- ProductVariant is the canonical sellable item in all future cart, stock, and order work.
- Monetary persistence uses `DECIMAL(12,3)` and business arithmetic uses Brick\Math `BigDecimal` or equivalent decimal-safe arithmetic.
- Google Routes requests are never made inside database transactions or while inventory rows are locked.
- Guest carts use a cryptographically random token, an encrypted/signed HttpOnly SameSite=Lax cookie, a production Secure flag, and a stored token hash only.
- Stock writes are idempotent through a business reference or idempotency key with a database unique constraint.
- Active delivery tiers begin at zero and are contiguous, non-overlapping, and gap-free.
- Each task ends with its stated test command before its commit.

---

## File structure

- `app/Http/Controllers/Storefront/HomeController.php` — renders the public homepage shell.
- `app/Http/Controllers/Admin/DashboardController.php` — renders the protected admin dashboard shell.
- `app/Http/Middleware/EnsureUserHasPermission.php` — maps named authorization middleware to Spatie permissions.
- `app/Providers/AppServiceProvider.php` — configures Arabic locale and production URL policy.
- `app/View/Components/Storefront/*` — shared public presentation primitives.
- `app/View/Components/Admin/*` — shared dashboard presentation primitives.
- `resources/views/layouts/storefront.blade.php` — RTL public document and global chrome.
- `resources/views/layouts/admin.blade.php` — RTL admin document and global chrome.
- `resources/views/storefront/home.blade.php` — foundation homepage composition.
- `resources/views/admin/dashboard.blade.php` — foundation dashboard composition.
- `resources/views/components/*` — accessible reusable buttons, inputs, notices, and empty states.
- `resources/css/app.css` — Tailwind import, Glowy tokens, typography, and RTL-safe utilities.
- `routes/web.php` — cacheable controller routes for home, auth, and admin dashboard.
- `database/seeders/RolesAndPermissionsSeeder.php` — canonical roles and permissions.
- `database/seeders/AdminUserSeeder.php` — local initial administrator guarded by environment variables.
- `tests/Feature/Storefront/HomePageTest.php` — public shell, direction, and Arabic copy tests.
- `tests/Feature/Admin/AdminAccessTest.php` — guest, customer, and administrator access tests.
- `tests/Feature/Auth/AuthenticationTest.php` — registration, login, logout, and redirect behavior tests.

### Task 1: Bootstrap the Laravel application and reproducible local baseline

**Files:**
- Create: Laravel application files generated at the repository root, including `artisan`, `composer.json`, `package.json`, `.env.example`, `bootstrap/app.php`, `config/*`, `routes/*`, `resources/*`, `tests/*`.
- Modify: `.gitignore`, `README.md`, `phpunit.xml`.
- Test: `tests/Feature/ExampleTest.php`.

**Interfaces:**
- Consumes: PHP 8.4, Composer 2.10, Node 24, npm 11.
- Produces: A Laravel 13 application bootable through `php artisan`, with Pest and a configured test environment.

- [ ] **Step 1: Initialize source control before generating application files.**

Run: `git init`

Expected: `.git` exists and `git status --short` lists only the provided documentation as untracked.

- [ ] **Step 2: Create Laravel 13 in a temporary directory and copy its non-conflicting contents into the repository root.**

Run: `composer create-project laravel/laravel:^13.0 <temporary-directory>`

Expected: the generated project reports Laravel 13 with `php artisan --version`; preserve `AGENTS.md`, `SCOPE.md`, `ARCHITECTURE.md`, `UI_UX_GUIDE.md`, `IMPLEMENTATION_PLAN.md`, `CODEX_PROMPT.md`, and `docs/`.

- [ ] **Step 3: Configure test defaults for a new repository.**

Set `APP_ENV=testing`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, and an isolated SQLite test database in `phpunit.xml`. Configure the local `.env` for MySQL without adding credentials to version control.

- [ ] **Step 4: Verify the untouched Laravel test baseline.**

Run: `php artisan test`

Expected: PASS.

- [ ] **Step 5: Commit the baseline.**

Run: `git add . ':!.env' ':!vendor' ':!node_modules' && git commit -m "chore: bootstrap Laravel application"`

Expected: clean working tree except local environment files.

### Task 2: Add frontend tooling, Livewire, authentication, permissions, and decimal arithmetic

**Files:**
- Modify: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `bootstrap/app.php`, `config/auth.php`.
- Create: authentication routes, controllers, requests, and views generated by the selected Laravel starter kit; `database/seeders/RolesAndPermissionsSeeder.php`; `database/seeders/AdminUserSeeder.php`.
- Modify: `database/seeders/DatabaseSeeder.php`.
- Test: `tests/Feature/Auth/AuthenticationTest.php`.

**Interfaces:**
- Consumes: Laravel 13 baseline from Task 1.
- Produces: `auth` middleware, Livewire 4 runtime, `role` and `permission` middleware aliases, `admin.access` permission, and `admin` role.

- [ ] **Step 1: Write authorization and authentication behavior tests.**

```php
it('redirects guests from the admin dashboard to login', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('forbids authenticated users without admin access', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
```

- [ ] **Step 2: Run the focused tests to establish the failure.**

Run: `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Admin/AdminAccessTest.php`

Expected: FAIL because the routes, role data, and permission middleware are absent.

- [ ] **Step 3: Install and configure the required packages.**

Run: `composer require livewire/livewire:^4.0 spatie/laravel-permission brick/math && php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" && php artisan make:migration add_permission_traits_to_users_table`

Implement `HasRoles` on `User`, register Spatie middleware aliases in `bootstrap/app.php`, seed `admin.access`, and create an environment-guarded admin seeder. Install the official Livewire-compatible Laravel authentication starter and retain Blade/Tailwind output.

- [ ] **Step 4: Make the focused tests pass.**

Run: `php artisan migrate:fresh --seed --env=testing && php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Admin/AdminAccessTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the secured application services.**

Run: `git add composer.json composer.lock package.json package-lock.json app bootstrap config database resources routes tests && git commit -m "feat: add authentication and admin permissions"`

### Task 3: Establish application locale, RTL document defaults, and Glowy design tokens

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`, `config/app.php`, `resources/css/app.css`, `resources/js/app.js`, `vite.config.js`.
- Create: `resources/views/layouts/storefront.blade.php`, `resources/views/layouts/admin.blade.php`, `resources/views/components/ui/button.blade.php`, `resources/views/components/ui/input.blade.php`, `resources/views/components/ui/notice.blade.php`, `resources/views/components/ui/empty-state.blade.php`.
- Test: `tests/Feature/Storefront/HomePageTest.php`.

**Interfaces:**
- Consumes: Vite and authenticated Laravel app from Tasks 1–2.
- Produces: `<html lang="ar" dir="rtl">`, `@vite` asset entrypoints, `glowy-*` Tailwind color tokens, and accessible reusable UI components.

- [ ] **Step 1: Write a failing RTL shell test.**

```php
it('renders the public shell as Arabic RTL', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('lang="ar" dir="rtl"', false)
        ->assertSee('Glowy');
});
```

- [ ] **Step 2: Run the shell test to confirm it fails.**

Run: `php artisan test tests/Feature/Storefront/HomePageTest.php`

Expected: FAIL because the home route and layout do not exist.

- [ ] **Step 3: Implement layouts and tokens.**

Create two Blade layouts with Arabic direction, responsive viewport metadata, `@vite`, semantic header/main/footer regions, and skip links. Define named token values for `#B07366`, `#3A2F33`, `#FCFBFA`, `#F8EEEB`, `#E8D7CE`, `#2E2528`, and `#786B6E`; load Alexandria, Tajawal, and Inter through the frontend build. Build input, button, notice, and empty-state components with keyboard focus styles and a 44px minimum interactive size.

- [ ] **Step 4: Run the focused test and compile assets.**

Run: `npm install && npm run build && php artisan test tests/Feature/Storefront/HomePageTest.php`

Expected: asset build succeeds and test PASS.

- [ ] **Step 5: Commit the visual foundation.**

Run: `git add app config resources tests vite.config.js && git commit -m "feat: add RTL Glowy design foundation"`

### Task 4: Add cacheable storefront and administration shells

**Files:**
- Create: `app/Http/Controllers/Storefront/HomeController.php`, `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/storefront/home.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/components/storefront/header.blade.php`, `resources/views/components/storefront/footer.blade.php`, `resources/views/components/admin/sidebar.blade.php`.
- Modify: `routes/web.php`.
- Test: `tests/Feature/Storefront/HomePageTest.php`, `tests/Feature/Admin/AdminAccessTest.php`.

**Interfaces:**
- Consumes: `auth`, `permission:admin.access`, and layouts from prior tasks.
- Produces: named `home` and `admin.dashboard` routes implemented by controller actions, with no page closures.

- [ ] **Step 1: Extend the failing tests for public and admin shell content.**

```php
it('allows an administrator to view the dashboard', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('admin.access');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('لوحة التحكم');
});
```

- [ ] **Step 2: Run feature tests and confirm the missing routes fail.**

Run: `php artisan test tests/Feature/Storefront/HomePageTest.php tests/Feature/Admin/AdminAccessTest.php`

Expected: FAIL until both controller routes and views exist.

- [ ] **Step 3: Implement cacheable controller routes and responsive shells.**

Define `Route::get('/', HomeController::class)->name('home')` and `/admin` with `auth` and `permission:admin.access` middleware. The storefront shell includes announcement, navigation labels, search affordance, a soft hero with the approved Arabic heading, and a footer. The admin shell includes a practical RTL sidebar and dashboard placeholder cards; it must not claim live business metrics.

- [ ] **Step 4: Run targeted feature tests.**

Run: `php artisan test tests/Feature/Storefront/HomePageTest.php tests/Feature/Admin/AdminAccessTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the initial application shells.**

Run: `git add app resources routes tests && git commit -m "feat: add storefront and admin shells"`

### Task 5: Verify phase zero and foundation quality gates

**Files:**
- Modify: `README.md` with exact setup, build, migration, seed, test, and cache commands.
- Test: complete `tests/` suite.

**Interfaces:**
- Consumes: completed bootstrap, authentication, authorization, layouts, and shells.
- Produces: documented local onboarding and a cache-safe baseline.

- [ ] **Step 1: Document required local configuration.**

Document MySQL database creation, `cp .env.example .env`, `php artisan key:generate`, migration/seeding commands, production values for cart-cookie security, and the rule that Google keys are added only in the later delivery phase. Do not document actual secrets.

- [ ] **Step 2: Run code-style and full behavior checks.**

Run: `vendor/bin/pint --test && php artisan test`

Expected: both commands PASS; if Pint reports formatting changes, run `vendor/bin/pint` and rerun its test mode.

- [ ] **Step 3: Run production-cache compatibility checks.**

Run: `php artisan optimize:clear && php artisan route:cache && php artisan config:cache && php artisan view:cache && php artisan optimize:clear`

Expected: each command succeeds with no route closure serialization failures.

- [ ] **Step 4: Perform responsive RTL inspection.**

Run: `npm run build`

Expected: Vite build succeeds. Inspect storefront and admin at 375px, 430px, 768px, 1024px, and 1440px; confirm right-aligned navigation, readable Arabic text, no horizontal overflow, visible keyboard focus, and 44px interactive controls.

- [ ] **Step 5: Check application logs and commit the verified phase.**

Run: `Get-Content storage/logs/laravel.log -Tail 100; git add README.md app bootstrap config database resources routes tests docs && git commit -m "docs: verify Glowy foundation setup"`

Expected: no new application exceptions and a clean `git status --short` except ignored local files.
