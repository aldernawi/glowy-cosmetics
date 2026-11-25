# Final Completion & Hardening Implementation Plan

> **For agentic workers:** Execute inline in this workspace. The user explicitly requested one integrated pass, no intermediate approval gates, no commit, and no push.

**Goal:** Close the remaining V1 storefront, delivery, administration, reporting, permission, and release-readiness gaps without regressing completed phases.

**Architecture:** Retain the existing Laravel service boundaries: controllers and form requests remain thin; delivery quotes remain server-originated before order transactions; stock and money behaviour stay in `StockService`, `CheckoutService`, and BigDecimal-backed services. Add small dedicated services only where lifecycle ownership (media cleanup and sitemap invalidation) requires it.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, Tailwind 4, Alpine.js, MySQL 8, Spatie Permission, PHPUnit.

**Spec:** `AGENTS.md`, `SCOPE.md`, `ARCHITECTURE.md`, `UI_UX_GUIDE.md`, `IMPLEMENTATION_PLAN.md`, and `C:\Users\henry\.codex\attachments\fdc10876-c117-42ea-8c69-513afdbf2e62\pasted-text.txt`.

## Global Constraints

- Arabic RTL is primary; all customer and admin copy is Arabic.
- Money is `DECIMAL(12,3)` and calculated server-side with BigDecimal, never PHP float.
- ProductVariant remains the canonical sellable item.
- Google Routes calls occur before transactions; no browser-supplied totals or delivery fees are trusted.
- New database changes use additive migrations only.
- Preserve existing uncommitted login and environment changes; do not commit or push.
- No coupon functionality is added because the user explicitly excluded it.

---

### Task 1: Foundation schema, lifecycle safety, and regression tests

**Files:** migrations for order delivery details, brands, content blocks; models/services for media and sitemap lifecycle; feature tests.

- [ ] Write feature tests for persisted delivery snapshots, safe media deletion, and sitemap invalidation.
- [ ] Run those tests and verify they fail because the fields/services do not exist.
- [ ] Add additive schema fields and narrowly scoped lifecycle services/observers.
- [ ] Re-run the focused tests and keep all existing related tests green.

### Task 2: Delivery configuration and confirmed checkout location

**Files:** delivery controller/requests/routes/view, checkout controller/request/view/JS, order model/service, delivery feature tests.

- [ ] Write failing tests for configured-origin checkout rendering, landmark snapshots, and invalid delivery configuration.
- [ ] Implement map picker state with explicit confirmation, server-provided origin, immutable quote display, and full snapshots.
- [ ] Add manageable delivery tiers (update/delete/active/sort) and a Google Map picker in admin.
- [ ] Re-run delivery and checkout tests.

### Task 3: Catalog administration and inventory surfaces

**Files:** product/brand/category/taxonomy controllers and requests, dedicated create/edit views, product media ordering, inventory routes/views, catalog tests.

- [ ] Write focused failing tests for full product associations, product archive-safe deletion, image ordering, and brand editing.
- [ ] Implement CRUD screens and validations for product data, variants, media, brands, and all taxonomies.
- [ ] Add current, low, and out-of-stock inventory screens with safe adjustment and movement filters.
- [ ] Re-run catalog and inventory tests.

### Task 4: Operations, permissions, customer account, CMS, and reporting

**Files:** dashboard/report services and views, order views/controllers, permission seeder/routes, customer/auth pages, homepage CMS models/controllers/views, storefront navigation, tests.

- [ ] Write failing tests for granular catalog access, filtered report exports, and user-visible status timelines.
- [ ] Implement real dashboard metrics/charts, complete order administration, granular backend middleware and self-lockout protection.
- [ ] Implement Arabic customer account/auth surfaces, navigation access to categories/brands/concerns, homepage banners/section visibility/order, and filtered report exports.
- [ ] Re-run these focused tests.

### Task 5: Integrated audit and release verification

**Files:** all changed files and no generated artifacts.

- [ ] Review the final diff against every spec item and run a requirements checklist.
- [ ] Run Pint, full tests, optimize clear, route/config/view cache commands, Vite build, and route cache recovery check.
- [ ] Manually test core storefront/admin responsive RTL flows and inspect browser console and Laravel logs.
- [ ] Report precise results and remaining limitations without committing or pushing.
