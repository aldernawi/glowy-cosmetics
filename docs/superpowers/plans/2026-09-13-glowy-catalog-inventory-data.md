# Glowy Catalog and Inventory Data Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the typed catalog, canonical product variants, and auditable idempotent inventory foundations required by all commerce flows.

**Architecture:** Product metadata is modeled by dedicated Eloquent models with foreign keys and searchable/indexed columns. Every product gets a default `ProductVariant`, so all sellable references use `product_variant_id`. `StockService` is the exclusive inventory writer and records an idempotent `StockMovement` for every change.

**Tech Stack:** Laravel 13, PHP 8.4, MySQL 8, SQLite test database, Eloquent, PHPUnit, Brick\Math.

**Spec:** `docs/superpowers/specs/2026-09-13-glowy-foundation-design.md`

## Global Constraints

- ProductVariant is the only canonical sellable item; no product-level stock or cart reference exists.
- Money columns are `DECIMAL(12,3)` and future calculations use Brick\Math `BigDecimal`, never PHP floats.
- Stock operations use database transactions, row locking, a unique idempotency key, and append-only movements.
- Products may omit optional detail fields; unavailable frontend sections must be hidden later.
- Migrations use foreign keys and indexes for common lookups, relations, slugs, SKUs, and active flags.

---

### Task 1: Catalog taxonomy schema and Eloquent relationships

**Files:**
- Create: migrations for `brands`, `categories`, `skin_types`, `concerns`, `ingredients`.
- Create: `app/Models/Brand.php`, `Category.php`, `SkinType.php`, `Concern.php`, `Ingredient.php`.
- Test: `tests/Feature/Catalog/CatalogTaxonomyTest.php`.

- [ ] Write tests proving categories can nest and taxonomy records enforce unique slugs.
- [ ] Run `php artisan test tests/Feature/Catalog/CatalogTaxonomyTest.php` and confirm failure because the models and tables do not exist.
- [ ] Implement migrations, casts, relationship methods, unique slugs, sort orders, active/featured flags, and indexes.
- [ ] Run the focused test and `php artisan migrate:fresh --env=testing`; both must pass.
- [ ] Commit: `feat: add catalog taxonomy data model`.

### Task 2: Products, canonical variants, and media schema

**Files:**
- Create: migrations for `products`, `product_variants`, `product_images`, and `product_skin_type`, `concern_product`, `ingredient_product` pivots.
- Create: `app/Models/Product.php`, `ProductVariant.php`, `ProductImage.php`.
- Test: `tests/Feature/Catalog/ProductVariantTest.php`.

- [ ] Write tests proving a product owns variants and the internally created default variant has an active unique SKU and decimal-safe price strings.
- [ ] Run `php artisan test tests/Feature/Catalog/ProductVariantTest.php` and confirm failure because the models and tables do not exist.
- [ ] Implement the schema: product metadata, active/featured/new flags, nullable content fields, image ordering, taxonomy pivots, `DECIMAL(12,3)` prices, active variants, stock quantity on variants only, and SKU/slug indexes.
- [ ] Add `Product::createDefaultVariant()` with an explicit SKU input; do not generate stock at product level.
- [ ] Run focused tests and migration reset; both must pass.
- [ ] Commit: `feat: add products and canonical variants`.

### Task 3: Inventory ledger and idempotent StockService

**Files:**
- Create: migration for `stock_movements` with unique `idempotency_key`.
- Create: `app/Enums/StockMovementType.php`, `app/Services/StockService.php`, `app/Models/StockMovement.php`.
- Test: `tests/Feature/Inventory/StockServiceTest.php`.

- [ ] Write tests for opening stock, failed negative movement, and repeating one idempotency key without a second quantity change or movement.
- [ ] Run `php artisan test tests/Feature/Inventory/StockServiceTest.php` and confirm failure because StockService is absent.
- [ ] Implement a transaction-based `StockService::adjust(ProductVariant $variant, int $delta, StockMovementType $type, string $idempotencyKey, ?User $actor = null, ?string $reference = null, ?string $notes = null): StockMovement`.
- [ ] Lock the variant row, reject negative post-state, create one movement with before/after values, and return the preexisting movement on an idempotent retry.
- [ ] Run focused tests, full tests, Pint, and route/config/view cache checks; all must pass.
- [ ] Commit: `feat: add idempotent inventory ledger`.
