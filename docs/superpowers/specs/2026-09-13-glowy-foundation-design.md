# Glowy Foundation Design

## Purpose and scope

This first implementation slice establishes a Laravel 13 commerce application that can safely grow into the Glowy V1 scope. It includes application bootstrap, Arabic RTL foundations, authentication and authorization, design primitives, the data model boundaries, and the rules that all later catalog, checkout, delivery, and order work must follow. It does not implement storefront catalog pages, checkout, Google Maps, delivery API integration, or reports yet; those follow the existing phased plan.

## Application architecture

The application is a single Laravel monolith using PHP 8.4+, MySQL 8+, Blade, Livewire 4, Tailwind CSS 4, Vite, and Laravel authentication. The user-facing area is RTL-first with Arabic as its initial locale. The operational dashboard lives under `/admin` and uses an independent practical layout while sharing the same visual tokens.

HTTP controllers, Livewire components, form requests, policies, and middleware handle rendering, validation, and authorization. Business rules live in narrowly scoped services. Models retain relationships, casts, scopes, and query concerns. Repositories are not introduced unless a concrete need emerges.

Initial service boundaries are CartService, CheckoutService, OrderService, PricingService, StockService, DeliveryService, GoogleRoutesService, CouponService, and HomepageService. A service may be introduced when its domain first ships; empty placeholder implementations are avoided.

Spatie Laravel Permission provides roles and permissions. A seeded administrator role controls `/admin`, and policies protect resource-level access as the resource modules are added.

## Experience foundations

The storefront uses the Glowy identity: ivory background, rose accent, dark plum typography, soft blush and champagne supporting surfaces, Alexandria for Arabic headings, Tajawal for Arabic body copy, and Inter for Latin text. It is RTL-first, mobile-first, and uses calm transitions only. Shared Blade components provide the announcement bar, header, navigation, buttons, inputs, cards, feedback states, and footer patterns.

The administration interface prioritizes fast, accessible data work: clear navigation, compact tables, focused forms, status badges, empty states, and restrained Glowy accents. It is not derived from a third-party admin template.

## Core data model

Catalog tables will model brands, nested categories, products, product images, product variants, skin types, concerns, ingredients, and the relevant pivot associations. `ProductVariant` is the canonical sellable item everywhere. A product with no customer-selectable choices still receives one internal default variant. Cart items, inventory, stock movements, and order processing always refer to `product_variant_id`; no product-versus-variant branching is permitted.

All stored monetary values use `DECIMAL(12,3)`. Business calculations use Brick\Math `BigDecimal` (or an equivalently decimal-safe abstraction) and apply one documented rounding rule at each persisted monetary boundary. PHP floats are not used for prices, discounts, fees, subtotals, or totals.

Cart data supports guest and authenticated customers. A cryptographically random guest-cart token is stored in an encrypted and signed cookie with `HttpOnly`, `SameSite=Lax`, and the `Secure` flag in production. The database stores only a hash of the token, never the raw token. Account sign-in uses a deterministic, stock-aware merge by variant: it sums matching quantities, caps them at currently available stock, and returns a clear user-facing notice for each adjusted item. Cart clients never supply trusted price, discount, delivery, distance, or total values.

Inventory keeps current variant quantity and an append-only stock movements ledger. The StockService is the only writer for inventory changes. It rejects negative resulting inventory and records reason, before/after quantities, actor, source, and notes. Every stock-changing operation includes a business reference or idempotency key with a database unique constraint, making sale, cancellation, and return retries safe and preventing duplicate movements.

Orders persist independent snapshots of customer details, delivery location, origin location, route result, fee, discount, total, and product/variant names, SKUs, prices, and quantities. Order status and payment status are separate enums.

Delivery settings persist the store origin and active distance tiers. Active tiers must start at 0, be ordered, and form a contiguous range without overlaps or gaps. The canonical boundary rule is first tier `0 <= distance <= max`, then each later tier `previous_max < distance <= max`; thus 0–10, 10–20, 20–30 is unambiguous. Validation rejects any active-tier set that is not contiguous.

## Critical flows

### Cart

Cart updates resolve products and variants server-side, require active sellable items, and reject quantities above available stock. Cart totals are recalculated on every server request.

### Delivery quote

Checkout sends only an approved destination location. Immediately before order creation, DeliveryService reads the current admin-configured origin, asks GoogleRoutesService for a fresh driving route, selects an active tier, and returns a quote that records the origin and tier version used. The browser only displays the quote. If the route provider fails or the destination is outside every active tier, checkout cannot proceed.

### Order creation

CheckoutService obtains the fresh delivery quote before opening the database transaction and never calls Google Routes while holding database locks. Inside the transaction it reloads the cart, locks variant inventory rows, recalculates product pricing and coupon discount, verifies that the quoted origin and contiguous delivery-tier configuration remain valid, verifies stock, writes the order and its snapshots, decrements stock through StockService with an idempotency key, records movements, and clears the cart only after a successful commit. If delivery settings changed, it aborts cleanly and requires a fresh quote. It does not trust browser-supplied amounts or route data.

### Lifecycle changes

Order status transitions are explicitly validated. Cancellation and return behavior use OrderService and StockService so inventory restoration occurs once and is auditable. Payment method is always cash on delivery in V1; payment status begins unpaid and can be changed independently by authorized staff.

## Reliability, security, and quality

Google browser credentials are limited to Maps JavaScript and Places API with referrer restrictions. Routes credentials remain server-side and are read only from environment-backed configuration. No key is committed. Failing route calculations never become free delivery or a guessed fee.

Foreign keys, indexes for relationships and common filters, eager loading for storefront lists, pagination at 24 products, and cache for low-volatility settings and homepage configuration are the default performance posture. Critical carts, inventory, orders, and delivery quotes are not served from stale cache.

Pest covers authentication, admin authorization, catalog access and filters, cart operations, stock restrictions, tier boundaries and overlaps, checkout revalidation, order totals and snapshots, stock deduction, overselling prevention, cancellation, and return behavior. Each phase must pass its targeted tests before the next begins. Final verification runs optimize clear, route cache, config cache, view cache, and the full test suite.

## Delivery sequence

1. Bootstrap and foundation: Laravel, auth, permissions, RTL layouts, components, tokens, tests.
2. Catalog and inventory data: migrations, models, classifications, stock service and movements.
3. Catalog operations: admin CRUD and storefront discovery.
4. Cart, wishlist, delivery, checkout, and orders.
5. Admin operations, CMS, coupons, reports, SEO, and final QA.

## Out of scope for this slice

No payment gateway, multi-vendor capabilities, driver app, live tracking, multi-country delivery, or full multilingual CMS is introduced. Those remain explicitly outside V1 unless separately approved.
