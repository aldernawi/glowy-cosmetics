# Delivery Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Provide administrator-managed store origin and decimal-safe, contiguous delivery distance tiers for future checkout quotes.

**Architecture:** `DeliverySetting` stores the single current origin; `DeliveryTier` stores a fee and upper distance bound. `DeliveryTierService` owns all active-tier integrity checks, so forms and future checkout use one canonical `0 <= first <= max`, then `previous max < distance <= max` rule. Controllers only validate and delegate.

**Tech Stack:** Laravel 13, PHP 8.4, MySQL-compatible migrations, Blade, Tailwind CSS 4, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-13-glowy-foundation-design.md`

## Global Constraints

- ProductVariant remains the canonical sellable item; this phase does not introduce product-level stock paths.
- Persist delivery fees as `DECIMAL(12,3)`; do not use PHP floats for financial calculations.
- Active tiers must begin at zero and remain contiguous, with no overlaps or gaps.
- Canonical tier boundary: first tier `0 <= distance <= max`; later tier `previous_max < distance <= max`.
- Google Routes calls remain outside transactions and row locks; no API call is introduced in this settings phase.
- Admin pages use controller routes and preserve route-cache compatibility.

---

### Task 1: Persist delivery origin and tiers

**Files:**
- Create: `database/migrations/2026_09_13_230100_create_delivery_settings_and_tiers_table.php`, `app/Models/DeliverySetting.php`, `app/Models/DeliveryTier.php`.
- Test: `tests/Feature/Delivery/DeliveryTierServiceTest.php`.

**Interfaces:**
- Produces `DeliverySetting::current(): ?DeliverySetting` and `DeliveryTier` properties `min_distance_km`, `max_distance_km`, `fee`, `is_active`.

- [ ] **Step 1: Write a failing tier boundary test.**

```php
$service->tierForDistance('10.000')->id === $firstTier->id;
$service->tierForDistance('10.001')->id === $secondTier->id;
```

- [ ] **Step 2: Run the focused test to verify it fails.**

Run: `php artisan test tests/Feature/Delivery/DeliveryTierServiceTest.php`

Expected: FAIL because the service and delivery tables do not exist.

- [ ] **Step 3: Add the migration and models.**

Use nullable DECIMAL(10,7) coordinates and `DECIMAL(12,3)` for tier bounds and fee. Make `delivery_settings` a singleton through `DeliverySetting::current()` rather than a hardcoded origin.

- [ ] **Step 4: Run the focused test.**

Run: `php artisan test tests/Feature/Delivery/DeliveryTierServiceTest.php`

Expected: PASS after Task 2 adds the service.

### Task 2: Enforce tier configuration and resolve a tier

**Files:**
- Create: `app/Services/DeliveryTierService.php`, `tests/Feature/Delivery/DeliveryTierServiceTest.php`.

**Interfaces:**
- Consumes: active `DeliveryTier` rows.
- Produces: `validateActiveTiers(): void` and `tierForDistance(string $distanceKm): DeliveryTier` using Brick `BigDecimal` comparisons.

- [ ] **Step 1: Add failing tests for gaps, overlaps, and exact boundaries.**

```php
expect(fn () => $service->validateActiveTiers())
    ->toThrow(DomainException::class);
```

- [ ] **Step 2: Implement one decimal-safe service.**

Sort active tiers by `min_distance_km`; require the first minimum to equal `0.000`, each following minimum to equal the preceding maximum, and every maximum to exceed its minimum. `tierForDistance` returns the first max greater than or equal to the supplied decimal value.

- [ ] **Step 3: Run focused tests.**

Run: `php artisan test tests/Feature/Delivery/DeliveryTierServiceTest.php`

Expected: PASS.

### Task 3: Add administrator settings UI

**Files:**
- Create: `app/Http/Controllers/Admin/DeliverySettingController.php`, `app/Http/Requests/Admin/StoreDeliveryTierRequest.php`, `resources/views/admin/delivery/index.blade.php`, `tests/Feature/Admin/DeliverySettingsTest.php`.
- Modify: `routes/web.php`, `resources/views/layouts/admin.blade.php`.

**Interfaces:**
- Consumes: `DeliveryTierService::validateActiveTiers()` and origin/tier models.
- Produces: `admin.delivery.index`, `admin.delivery.origin.update`, and `admin.delivery.tiers.store` routes protected by `admin.access`.

- [ ] **Step 1: Write failing administrator request tests.**

```php
$this->actingAs($administrator)->post(route('admin.delivery.tiers.store'), $tier)
    ->assertRedirect(route('admin.delivery.index'));
```

- [ ] **Step 2: Implement Form Requests, controller, and Arabic RTL form.**

Store origin coordinates and formatted address server-side. For tier creation, open a transaction, create the tier, validate the active sequence, and roll back with a validation error if the new tier creates a gap or overlap.

- [ ] **Step 3: Run focused test and cache check.**

Run: `php artisan test tests/Feature/Admin/DeliverySettingsTest.php && php artisan route:cache`

Expected: PASS.

- [ ] **Step 4: Run phase quality gates and commit.**

Run: `vendor/bin/pint --test && php artisan optimize:clear && php artisan test && npm run build && php artisan route:cache && php artisan config:cache && php artisan view:cache`

Expected: all commands PASS.
