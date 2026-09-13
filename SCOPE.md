# SCOPE.md — نطاق مشروع Glowy

## 1. الهدف

إعادة بناء Glowy كمتجر إلكتروني متكامل للعناية والجمال باستخدام Laravel، مع واجهة عميل عربية RTL ولوحة إدارة Custom.

---

# IN SCOPE — V1

## A. Storefront

### الصفحة الرئيسية
- Hero
- Shop by Concern
- Shop by Category
- New Arrivals
- Best Sellers
- Featured Brands
- Skincare Routine
- Why Glowy
- Promotional banners
- Footer

### الكتالوج
- Products listing
- Pagination
- Search
- Sorting
- Filters:
  - category
  - brand
  - price
  - skin type
  - concern
  - product type
  - availability
  - sale
  - new

### Product Details
- Gallery
- Multiple images
- Price
- Compare at price
- Stock state
- Variant support
- Benefits
- Usage
- Warnings
- Skin type
- Concerns
- Ingredients
- Related products
- Same brand
- Complete your routine

### Cart
- Guest cart
- Update quantity
- Remove items
- Coupon
- Totals
- Cart drawer
- Cart page

### Checkout
- Guest checkout
- Customer information
- Phone
- Optional second phone
- Optional textual address
- Notes
- Mandatory Google Maps location
- Automatic delivery price
- Cash on Delivery only

### Customer Account
- Login
- Profile
- Orders
- Order details
- Addresses
- Wishlist

### Wishlist
- Database persistence for logged-in users
- Session/local strategy for guests if appropriate

---

## B. Delivery

### Store origin
Admin selects Glowy origin point on Google Map.

### Customer location
Customer selects delivery point in Checkout.

### Distance
Driving route distance from Glowy to customer.

### Price
Distance tiers managed by Admin.

Example:
- 0-10 km
- 10-20 km
- 20-30 km
- 30-40 km

Actual values are configurable.

### Out of range
If no active tier covers the calculated route:
- prevent checkout completion
- show clear message
- optionally show WhatsApp action

---

## C. Payments

### Included
- Cash on Delivery only
- Payment status: unpaid / paid

### Not included in V1
- Payment gateway
- Moamalat
- Cards
- Bank transfer
- Wallets
- Online payment
- Payment provider abstraction unless needed later by a separate scope

---

## D. Admin Dashboard

### Dashboard
- sales today
- sales month
- orders today
- active orders
- average order value
- customers count
- low stock
- out of stock
- recent orders
- sales charts
- best sellers

### Products
- CRUD
- media
- variants
- prices
- stock
- classifications
- SEO
- active/featured flags

### Categories
- CRUD
- nested category support
- image
- sort order

### Brands
- CRUD
- logo
- description
- featured
- sort order

### Skin Types
- CRUD

### Concerns
- CRUD

### Ingredients
- CRUD

### Inventory
- current stock
- movements
- opening
- manual in/out
- adjustments
- returns
- low stock

### Orders
- list
- filters
- details
- status transitions
- payment status
- delivery map
- open location in Google Maps
- printable order/invoice

### Customers
- list
- detail
- order history

### Coupons
- percentage
- fixed
- minimum order
- maximum discount
- usage limits
- date range
- active state

### Homepage Content
- hero
- banners
- featured categories
- featured concerns
- featured brands
- new arrivals
- best sellers
- promotional blocks

### Delivery Settings
- store location
- distance tiers
- active/inactive tiers
- validation against overlap

### Settings
- logo
- favicon
- phone
- WhatsApp
- email
- address
- working hours
- social links
- currency
- SEO defaults
- announcement bar
- low stock threshold

### Users & Permissions
- admin users
- roles
- permissions

### Reports
- sales
- orders
- products
- brands
- categories
- customers
- stock
- low stock
- movements
- CSV export

---

# OUT OF SCOPE — V1

لا تنفذ هذه الميزات إلا بطلب صريح جديد:

- Mobile app
- Multi-vendor marketplace
- Multiple warehouses
- Multiple store origins
- Driver app
- Live driver tracking
- Route optimization for multiple orders
- Online payment
- Payment gateway integrations
- Moamalat
- POS
- Accounting system
- Supplier purchasing workflow الكامل
- ERP
- Advanced CRM
- Loyalty points
- Subscription boxes
- AI skincare diagnosis
- Doctor/pharmacist consultation
- Multi-currency
- Multi-country shipping
- Full multilingual content management
- Advanced returns portal for customers
- Affiliate system
- Marketplace sellers
- Public reviews moderation system unless added later

---

# Success Criteria

V1 ناجحة عندما يستطيع العميل:

1. تصفح المنتجات بسهولة.
2. الوصول للمنتج حسب احتياج البشرة.
3. إضافة المنتجات للسلة.
4. فتح Checkout.
5. إدخال بياناته.
6. تحديد موقعه بدقة على Google Maps.
7. الحصول على سعر توصيل صحيح حسب الطريق.
8. رؤية الإجمالي النهائي.
9. تأكيد الطلب Cash on Delivery.
10. الحصول على رقم طلب واضح.

ويستطيع Admin:

1. إدارة المنتجات والمخزون.
2. إدارة الطلبات.
3. تحديد موقع Glowy.
4. إدارة شرائح أسعار التوصيل.
5. إدارة الصفحة الرئيسية.
6. متابعة المبيعات والمخزون.
7. فتح موقع العميل من الطلب.
8. تغيير حالات الطلب بشكل منظم.
