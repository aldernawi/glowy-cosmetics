# Glowy V1 Remaining Work — Four Phases

**Scope decision:** Coupons and all coupon-related data, UI, services, and tests are explicitly excluded.

## Phase 1 — Finish the trustworthy order flow

- Complete Checkout location experience: Google Places Autocomplete, browser geolocation as an optional action, explicit location confirmation, map-load and route-failure states.
- Persist delivery address, place ID, optional secondary phone, landmark, and customer notes in order snapshots.
- Show a server-derived delivery quote card before confirmation: route distance, duration, fee, and final total; prevent confirmation out of range.
- Add customer order-success and order-detail pages.
- Complete order operations: payment status update, status transition history, details/filters/printable order view.
- Tests: location required, out-of-range, failed route, server-side quote revalidation, snapshots, lifecycle and stock restoration.

## Phase 2 — Complete the premium storefront and catalog

- Rebuild Homepage around the approved hierarchy: Hero, concerns, categories, new arrivals, best sellers, promotional content, brands, routine, Why Glowy, footer.
- Load Alexandria/Tajawal/Inter correctly and use the supplied logo across responsive headers.
- Add product media gallery, details sections, active variants, related/same-brand/routine recommendations, stock states, and responsive product cards.
- Build search, sorting, and filters for category, brand, price, skin type, concern, availability, sale, and new; add brand and concern pages.
- Finish Cart: quantity update/remove, accurate server totals, Cart Drawer, polished empty/error states.
- Tests: filters/search, cart updates/removal, mobile RTL rendering, product fields only shown when populated.

## Phase 3 — Complete operational administration

- CRUD for categories (nested), brands, skin types, concerns, ingredients, products, product images, and product variants.
- Catalog media validation, ordering, primary image, flags, and stock-aware variant editing.
- Inventory screens: current stock, movement ledger, low/out-of-stock lists, threshold setting, filters.
- Dashboard KPIs: daily/monthly sales, active orders, AOV, customers, low stock, recent orders, best sellers.
- Customer directory, profiles, and order history; admin users and permission management.
- Tests: authorization, validation, media rules, nested categories, low-stock queries, KPIs.

## Phase 4 — Content, discoverability, reporting, and release QA

- Homepage CMS for hero, banners, featured collections, brands, concerns, product blocks, announcement bar, and contact/settings content.
- Reports for sales, orders, products, brands, categories, customers, and stock; CSV exports.
- SEO: metadata, canonicals, breadcrumbs, product structured data, sitemap, robots, and controlled filter canonicalization.
- Performance: image derivatives/WebP or AVIF workflow, eager-loading audit, indexes audit, cache low-volatility content.
- Full QA: 375/430/768/1024/1440+, RTL, browser console, Laravel logs, 404/broken image/empty states, accessibility basics, and full customer-to-admin order flow.
- Final release verification: optimize clear, Pint, full tests, route/config/view cache, and manual delivery flow with restricted real API keys.
