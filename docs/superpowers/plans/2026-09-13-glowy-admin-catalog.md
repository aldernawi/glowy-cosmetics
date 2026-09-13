# Glowy Admin Catalog Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Provide authorized administrators with practical RTL management for catalog taxonomy, products, variants, and stock adjustments.

**Architecture:** Resource controllers use form requests for validation and policies/permission middleware for access. `ProductService` creates products with their mandatory default variant; inventory adjustments invoke `StockService` exclusively. Blade templates remain small, reusing the existing admin layout and UI components.

**Tech Stack:** Laravel 13, Blade, Livewire 4-ready Blade views, Tailwind CSS 4, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-13-glowy-foundation-design.md`

## Global Constraints

- All admin routes are under `/admin` and require `admin.access`.
- ProductVariant remains the only sellable reference and stock is changed only through StockService.
- Prices are submitted and persisted as decimal strings; browser totals are never authoritative.
- Controllers orchestrate requests and services only; Form Requests own validation.

---

### Task 1: Catalog management permissions, routes, and taxonomy resources

**Files:**
- Create: admin `BrandController`, `CategoryController`, taxonomy controllers and matching form requests/views.
- Modify: `routes/web.php`, `database/seeders/RolesAndPermissionsSeeder.php`, admin sidebar.
- Test: `tests/Feature/Admin/CatalogManagementTest.php`.

- [ ] Write tests for an authorized administrator creating a brand and a forbidden non-administrator request.
- [ ] Run focused tests and confirm the catalog routes fail.
- [ ] Define `catalog.manage` permission, cacheable controller resource routes, and validation rules for Arabic name, slug, state, and sort order.
- [ ] Implement compact RTL list/create/edit forms for brands and categories.
- [ ] Run focused tests and commit `feat: add admin catalog taxonomy management`.

### Task 2: Product creation with mandatory default variant

**Files:**
- Create: `app/Services/ProductService.php`, `Admin/ProductController.php`, `StoreProductRequest.php`, product views.
- Modify: admin sidebar and routes.
- Test: `tests/Feature/Admin/ProductManagementTest.php`.

- [ ] Write a test that posts product and default-variant data and asserts persisted product, canonical variant, and decimal price.
- [ ] Run focused test and confirm failure because the route/service is absent.
- [ ] Implement ProductService transactionally, create the default variant with explicit SKU and decimal string price, and render validation feedback.
- [ ] Run focused tests and commit `feat: add admin product management`.

### Task 3: Stock adjustment administration

**Files:**
- Create: `Admin/StockAdjustmentController.php`, `AdjustStockRequest.php`, stock views.
- Modify: routes and sidebar.
- Test: `tests/Feature/Admin/StockAdjustmentTest.php`.

- [ ] Write a test for an administrator posting a manual adjustment and asserting one stock movement and updated variant quantity.
- [ ] Run focused test and confirm failure because the adjustment route is absent.
- [ ] Invoke StockService with an admin-generated UUID idempotency key; validate movement type, nonzero delta, optional notes, and variant selection.
- [ ] Run focused and full checks, cache checks, then commit `feat: add admin stock adjustments`.
