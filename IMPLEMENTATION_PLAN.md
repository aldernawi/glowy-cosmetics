# IMPLEMENTATION_PLAN.md — خطة تنفيذ Glowy

> نفذ بالترتيب. لا تبدأ مرحلة جديدة قبل نجاح اختبارات المرحلة السابقة.

## Phase 0 — Audit / Bootstrap
- فحص الريبو
- Git status
- dependencies
- env
- tests
- route/cache compatibility
- تثبيت stack المطلوب إن كان المشروع جديدًا

## Phase 1 — Foundation
- Auth
- Admin auth
- permissions
- RTL shell
- Store layout
- Admin layout
- design tokens
- typography
- reusable components

## Phase 2 — Database Core
- brands
- categories
- products
- images
- variants
- skin types
- concerns
- ingredients
- settings
- proper indexes/FKs

## Phase 3 — Catalog Admin
- products CRUD
- images
- variants
- brands
- categories
- concerns
- skin types
- ingredients
- validation
- permissions

## Phase 4 — Storefront Catalog
- homepage shell
- product listing
- pagination
- filters
- sorting
- search
- product details
- brand pages
- concern pages

## Phase 5 — Cart & Wishlist
- guest cart
- logged-in cart persistence strategy
- cart drawer
- cart page
- quantity validation
- wishlist

## Phase 6 — Inventory
- StockService
- stock movements
- opening
- adjustments
- manual in/out
- low stock
- stock reports basics

## Phase 7 — Delivery System
- delivery distance tiers
- admin delivery settings
- store origin picker
- Google Maps config
- customer location picker
- Places Autocomplete
- Google Routes integration
- DeliveryService
- boundary validation
- out-of-range handling

## Phase 8 — Checkout
- customer form
- map location
- delivery quote
- coupon validation
- COD display
- final totals
- server-side revalidation

## Phase 9 — Orders
- Order + OrderItem snapshots
- order numbering
- status model
- payment status
- stock deduction
- cancellation
- return handling
- success page

## Phase 10 — Orders Admin
- list
- filters
- details
- map
- open Google Maps
- status transitions
- mark COD paid
- print view

## Phase 11 — Homepage CMS
- hero
- banners
- featured categories
- featured concerns
- featured brands
- new arrivals
- best sellers
- promos

## Phase 12 — Coupons
- fixed / percentage
- date limits
- usage limits
- minimum order
- maximum discount

## Phase 13 — Reports
- sales
- orders
- products
- brands
- categories
- customers
- stock
- low stock
- movement
- CSV export

## Phase 14 — SEO / Performance
- metadata
- sitemap
- robots
- breadcrumbs
- structured data
- caching
- image optimization
- N+1 audit
- indexes audit

## Phase 15 — QA / Polish
- 375px
- 430px
- 768px
- 1024px
- 1440px+
- RTL
- browser console
- Laravel logs
- broken image states
- empty states
- 404
- accessibility basics

## Phase 16 — Final Verification

Run:

```bash
php artisan optimize:clear
php artisan test
php artisan route:cache
php artisan config:cache
php artisan view:cache
```

Manual flow:

Homepage
→ Products
→ Filters
→ Product
→ Cart
→ Checkout
→ Map location
→ Delivery quote
→ Create Order
→ Admin Order
→ Status change
→ Stock verification

لا تعتبر المشروع مكتملًا قبل نجاح هذه المرحلة.
