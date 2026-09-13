<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case Purchase = 'purchase';
    case ManualIn = 'manual_in';
    case OrderSale = 'order_sale';
    case Adjustment = 'adjustment';
    case Return = 'return';
    case Damage = 'damage';
    case ManualOut = 'manual_out';
}
