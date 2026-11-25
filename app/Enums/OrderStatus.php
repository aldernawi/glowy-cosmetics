<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار', self::Confirmed => 'تم التأكيد', self::Preparing => 'قيد التجهيز', self::Ready => 'جاهز', self::OutForDelivery => 'خرج للتوصيل', self::Delivered => 'تم التوصيل', self::Cancelled => 'ملغي', self::Returned => 'مرتجع',
        };
    }
}
