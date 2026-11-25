<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><title>{{ $order->number }}</title>
    <style>body{font-family:Tajawal,Arial,sans-serif;padding:30px;color:#2e2528}h1{font-size:22px}dl{display:grid;grid-template-columns:max-content 1fr;gap:6px 16px}dt{font-weight:bold}dd{margin:0}table{width:100%;border-collapse:collapse;margin-top:20px}td,th{border:1px solid #ccc;padding:8px;text-align:right}tfoot td{font-weight:bold}</style>
</head>
<body onload="window.print()">
    <h1>طلب <span dir="ltr">{{ $order->number }}</span></h1>
    <dl>
        <dt>التاريخ</dt><dd>{{ $order->created_at?->format('Y-m-d H:i') }}</dd>
        <dt>العميلة</dt><dd>{{ $order->customer_name }}</dd>
        <dt>الهاتف</dt><dd><span dir="ltr">{{ $order->phone }}</span>@if ($order->secondary_phone) — <span dir="ltr">{{ $order->secondary_phone }}</span>@endif</dd>
        @if ($order->delivery_city_region)<dt>المنطقة</dt><dd>{{ $order->delivery_city_region }}</dd>@endif
        @if ($order->delivery_address)<dt>العنوان</dt><dd>{{ $order->delivery_address }}</dd>@endif
        @if ($order->delivery_landmark)<dt>علامة مميزة</dt><dd>{{ $order->delivery_landmark }}</dd>@endif
        <dt>الإحداثيات</dt><dd dir="ltr" style="text-align:right">{{ $order->delivery_latitude }}, {{ $order->delivery_longitude }}</dd>
        <dt>المسافة</dt><dd>{{ $order->delivery_distance_km }} كم</dd>
        @if ($order->notes)<dt>ملاحظات</dt><dd>{{ $order->notes }}</dd>@endif
        <dt>الدفع</dt><dd>نقدًا عند الاستلام — {{ $order->payment_status === App\Enums\PaymentStatus::Paid ? 'مدفوع' : 'غير مدفوع' }}</dd>
    </dl>
    <table>
        <thead><tr><th>المنتج</th><th>SKU</th><th>السعر</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
        <tbody>@foreach ($order->items as $item)<tr><td>{{ $item->product_name }}@if ($item->variant_name) — {{ $item->variant_name }}@endif</td><td dir="ltr">{{ $item->sku }}</td><td>{{ $item->unit_price }}</td><td>{{ $item->quantity }}</td><td>{{ $item->subtotal }}</td></tr>@endforeach</tbody>
        <tfoot>
            <tr><td colspan="4">المجموع الفرعي</td><td>{{ $order->subtotal }} د.ل</td></tr>
            <tr><td colspan="4">الخصم</td><td>{{ $order->discount }} د.ل</td></tr>
            <tr><td colspan="4">رسوم التوصيل</td><td>{{ $order->delivery_fee }} د.ل</td></tr>
            <tr><td colspan="4">الإجمالي المطلوب</td><td>{{ $order->total }} د.ل</td></tr>
        </tfoot>
    </table>
</body>
</html>
