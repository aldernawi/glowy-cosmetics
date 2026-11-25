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
    /** @return array<int, OrderStatus> */
    public function nextStatuses(Order $order): array
    {
        return [OrderStatus::Pending->value => [OrderStatus::Confirmed, OrderStatus::Cancelled], OrderStatus::Confirmed->value => [OrderStatus::Preparing, OrderStatus::Cancelled], OrderStatus::Preparing->value => [OrderStatus::Ready, OrderStatus::Cancelled], OrderStatus::Ready->value => [OrderStatus::OutForDelivery, OrderStatus::Cancelled], OrderStatus::OutForDelivery->value => [OrderStatus::Delivered], OrderStatus::Delivered->value => [OrderStatus::Returned]][$order->status->value] ?? [];
    }

    public function transition(Order $order, OrderStatus $status, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $status, $actor): Order {
            $order = Order::query()->with('items.productVariant')->lockForUpdate()->findOrFail($order->id);
            if ($order->status === $status) {
                return $order;
            }
            if (! in_array($status, $this->nextStatuses($order), true)) {
                throw new DomainException('Invalid order status transition.');
            }
            if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
                foreach ($order->items as $item) {
                    if ($item->productVariant) {
                        app(StockService::class)->adjust($item->productVariant, $item->quantity, $status === OrderStatus::Cancelled ? StockMovementType::Return : StockMovementType::Return, 'order-restock-'.$status->value.'-'.$order->id.'-'.$item->id, $actor, $order->number);
                    }
                }
            }
            $previousStatus = $order->status;
            $order->update(['status' => $status]);
            $order->statusHistory()->create(['user_id' => $actor?->id, 'from_status' => $previousStatus->value, 'to_status' => $status->value]);

            return $order;
        });
    }
}
