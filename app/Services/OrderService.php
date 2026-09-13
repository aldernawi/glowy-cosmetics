<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function transition(Order $order, OrderStatus $status, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $status, $actor): Order {
            $order = Order::query()->with('items.productVariant')->lockForUpdate()->findOrFail($order->id);
            if ($order->status === $status) {
                return $order;
            }
            $allowed = [OrderStatus::Pending->value => [OrderStatus::Confirmed, OrderStatus::Cancelled], OrderStatus::Confirmed->value => [OrderStatus::Preparing, OrderStatus::Cancelled], OrderStatus::Preparing->value => [OrderStatus::Ready, OrderStatus::Cancelled], OrderStatus::Ready->value => [OrderStatus::OutForDelivery, OrderStatus::Cancelled], OrderStatus::OutForDelivery->value => [OrderStatus::Delivered], OrderStatus::Delivered->value => [OrderStatus::Returned]];
            if (! in_array($status, $allowed[$order->status->value] ?? [], true)) {
                throw new DomainException('Invalid order status transition.');
            }
            if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
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
