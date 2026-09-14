# Glowy Operational Administration Design

## Goal

Complete the operational administration area without changing the trusted catalog, stock, checkout, or order domain rules established in earlier phases.

## Scope

This phase adds complete back-office workflows for catalog taxonomy and media, stock visibility, operational KPIs, customer lookup, and role-based admin-user management. Coupons, CMS, reports/exports, and SEO remain outside this phase.

## Architecture

The existing `ProductVariant` remains the only sellable item. All stock writes continue through `StockService`; admin pages only request `manual_in`, `manual_out`, or `adjustment` movements using an idempotency key. Controllers validate and authorize requests, services own domain actions, and query services prepare dashboard, inventory, and customer read models without embedding calculation logic in views.

## Catalog Administration

- Add CRUD pages for categories, skin types, concerns, ingredients, products, variants, and product images.
- Categories use nullable `parent_id`; the admin selection excludes the edited category and its descendants to avoid cycles.
- Product edits retain the existing default internal variant rule. A product cannot be made active with no active variant.
- Product image records accept validated image uploads, have a unique ordering per product, and one primary image. Reordering occurs server-side; deleting a primary image promotes the first remaining image.
- Product variant writes validate SKU uniqueness, non-negative stock, and decimal prices with three digits of scale. Direct edits to stock quantity are rejected; the dedicated inventory action creates a `StockMovement` instead.

## Inventory Operations

- Provide current-stock, movement-ledger, low-stock, and out-of-stock views.
- Add nullable `low_stock_threshold` to `ProductVariant`, defaulting to `5`; a variant is low when stock is positive and less than or equal to its threshold.
- Ledger filters are type, variant/SKU search, and date range. It displays before/after quantities and its business reference.
- Stock adjustments use a generated UUID idempotency key per submitted action, retain actor/reference/notes, and surface insufficient-stock errors without partial writes.

## Dashboard and Customers

- `AdminDashboardService` produces today/month sales, today orders, active operational orders, AOV, customer count, low/out-of-stock counts, recent orders, and best sellers from order snapshots and items.
- Sales include only delivered orders; active orders exclude terminal `delivered`, `cancelled`, and `returned` states.
- Customer directory groups users with customer orders, displays aggregate count/spend, supports name/email/phone search, and opens a profile with order history. Guest orders remain operationally visible in orders but are not fabricated into user accounts.

## Users and Permissions

- Keep Spatie Permission as the authorization system. The existing `admin.access` permission remains the route gate.
- Add a users list and create/update role assignment screens. Administrators may only assign existing roles; a non-admin cannot access the area.
- Seed roles `admin` and `catalog-manager`; both roles receive only the permissions required by their screens. User self-demotion/removal is blocked to avoid accidental lockout.

## UI and Accessibility

- Extend the custom RTL admin layout with grouped active navigation and responsive tables/cards. No Filament or template package is introduced.
- All empty states, validation errors, and destructive image actions are clear Arabic UI. Image alt text is optional but sanitized.
- Authorization failures return 403 and inaccessible models return 404 where this is the established project behavior.

## Testing and Verification

- Feature tests cover admin authorization, taxonomy/variant/media validation, cycle prevention, image-primary invariants, stock ledger/threshold queries, KPI calculations, customer visibility, and user role protections.
- Every implementation slice follows red-green testing. Phase completion requires `npm run build`, Pint, `optimize:clear`, route/config/view cache, full tests, browser RTL/responsive/console checks, and Laravel-log inspection.
