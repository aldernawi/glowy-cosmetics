<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function transition(Order $order, OrderStatus $status, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $status, $actor): Order {
            $order = Order::query()->with('items.productVariant')->lockForUpdate()->findOrFail($order->id);
            if ($order->status === $status) {
                return $order;
            } if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Returned], true) && ! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
                foreach ($order->items as $item) {
                    if ($item->productVariant) {
                        app(StockService::class)->adjust($item->productVariant, $item->quantity, $status === OrderStatus::Cancelled ? StockMovementType::Return : StockMovementType::Return, 'order-restock-'.$status->value.'-'.$order->id.'-'.$item->id, $actor, $order->number);
                    }
                }
            } $order->update(['status' => $status]);

            return $order;
        });
    }
}
