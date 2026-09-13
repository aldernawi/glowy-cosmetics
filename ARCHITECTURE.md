# ARCHITECTURE.md — Glowy

## 1. النمط العام

Monolithic Laravel application مع فصل منطقي واضح بين:

- Storefront
- Admin
- Catalog
- Cart
- Checkout
- Orders
- Inventory
- Delivery
- Content
- Reporting

لا حاجة إلى Microservices في V1.

---

## 2. Layers المقترحة

### HTTP Layer
- Controllers
- Livewire Components
- Form Requests
- Middleware

مسؤوليتها:
- استقبال الطلب
- validation
- authorization
- استدعاء Service
- إرجاع response/view

### Domain / Application Services
- CartService
- CheckoutService
- OrderService
- PricingService
- StockService
- DeliveryService
- GoogleRoutesService
- CouponService

مسؤوليتها:
- business rules
- orchestration
- calculations
- state transitions

### Data Layer
- Eloquent Models
- Query scopes
- Repositories فقط إذا ظهرت حاجة حقيقية، لا تنشئ Repository Layer لمجرد الشكل.

---

## 3. Core Models

مقترح:

- User
- Address
- Brand
- Category
- Product
- ProductImage
- ProductVariant
- SkinType
- Concern
- Ingredient
- Cart
- CartItem
- Wishlist
- WishlistItem
- StockMovement
- Coupon
- CouponUsage
- Order
- OrderItem
- DeliveryDistanceTier
- Banner
- HomepageSection
- Setting

---

## 4. Delivery Architecture

### Inputs
- Store origin coordinates
- Customer destination coordinates

### Process

`Checkout UI`
→ `DeliveryService`
→ `GoogleRoutesService`
→ Google Routes API
→ route distance/duration
→ match DeliveryDistanceTier
→ return delivery quote

### Final order creation

Frontend quote ليس مصدر الحقيقة.

عند Create Order:

`CheckoutService`
1. يعيد قراءة cart.
2. يعيد حساب أسعار المنتجات.
3. يعيد تطبيق Coupon.
4. يعيد حساب Driving Distance.
5. يعيد اختيار Delivery Tier.
6. يعيد حساب delivery fee.
7. يحدد total.
8. يتحقق من stock.
9. ينشئ Order + Order Items snapshots.
10. ينفذ stock movements.
11. يثبت transaction.

---

## 5. Distance Tier Rule

استخدم قاعدة حدود موحدة لا تقبل الالتباس:

- أول Tier: `min <= distance <= max`
- كل Tier لاحق: `min < distance <= max`

أو طبّق تمثيلًا داخليًا أكثر دقة مثل:
- min inclusive
- max inclusive
مع Validation يمنع overlap.

المهم أن تكون القاعدة واحدة ومختبرة.

---

## 6. Money

- استخدم DECIMAL وليس Float.
- قيمة مقترحة: `DECIMAL(12,3)` لأن العملة LYD.
- كل العمليات الحسابية المالية Server-side.
- يمكن استخدام Brick\Math إذا احتاج المشروع arithmetic أدق.

---

## 7. Stock

المصدر الوحيد لتغيير المخزون:
`StockService`

أي Confirm Order / Cancel / Return / Adjustment يجب أن يمر عبره.

لا تسمح بتعديل `stock_quantity` من Controller مباشرة.

---

## 8. Order State

Order state transition يجب أن يكون Validated.

أمثلة:
- pending → confirmed
- confirmed → preparing
- preparing → ready
- ready → out_for_delivery
- out_for_delivery → delivered

Cancellation/return لها قواعد منفصلة.

لا تسمح بتغيير عشوائي يكسر المخزون أو التاريخ.

---

## 9. Search

V1:
- MySQL search مناسب
- normalization للعربية بشكل محدود
- indexes جيدة

لا تضف Elasticsearch/Meilisearch في V1 إلا إذا ظهرت حاجة فعلية.

---

## 10. Caching

استخدم Cache عند وجود قيمة:
- settings
- homepage configuration
- featured lists
- brand/category metadata

لا Cache للـ cart/order critical state بشكل يسبب stale data.

---

## 11. Images

- validate mime/type/size
- primary image
- image ordering
- thumbnail/optimized derivatives
- WebP/AVIF إن كان workflow مستقرًا
- fallback Glowy placeholder
- standardized aspect ratio in cards

---

## 12. Google APIs

Browser:
- Maps JavaScript API
- Places API

Server:
- Routes API عند الحاجة لحماية الحساب النهائي

Keys:
- `.env`
- Browser key restricted by referrers
- Server key restricted appropriately
- never commit credentials

---

## 13. Error Handling

### Google route failure
- لا تنشئ order.
- لا تستخدم fee صفر.
- أعط Retry.

### Out of delivery range
- reject checkout gracefully.

### Stock changed أثناء checkout
- أعد فحص stock داخل transaction.
- اعرض المنتجات التي لم تعد متاحة.

### Coupon invalidated
- أعد حساب total وأبلغ العميل.

---

## 14. Route Caching

قبل الإكمال:
- لا page closures تمنع caching.
- نفذ:
  - route:cache
  - config:cache
  - view:cache

---

## 15. Testing Strategy

Unit/Feature tests للـ:
- DeliveryService
- PricingService
- StockService
- OrderService
- tier boundaries
- totals
- overselling
- cancel/return

Browser/UI tests فقط للتدفقات الحرجة إذا كانت البيئة تسمح.
