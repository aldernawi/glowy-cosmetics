<?php

namespace App\Services;

use App\Data\DeliveryQuote;
use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Cart;
use App\Models\DeliverySetting;
use App\Models\DeliveryTier;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(private StockService $stock, private DeliveryTierService $tiers) {}

    public function create(Cart $cart, ?User $user, DeliveryQuote $quote, array $customer): Order
    {
        return DB::transaction(function () use ($cart, $user, $quote, $customer): Order {
            $setting = DeliverySetting::query()->lockForUpdate()->find($quote->settingId);
            $tier = DeliveryTier::query()->lockForUpdate()->find($quote->tierId);
            if ($setting === null || $tier === null || $setting->store_latitude === null || $setting->store_longitude === null || ! $tier->is_active || $tier->fee !== $quote->fee) {
                throw new DomainException('Delivery configuration changed. Request a new quote.');
            }
            $this->tiers->validateActiveTiers();
            if ($this->tiers->tierForDistance($quote->distanceKm)->id !== $tier->id) {
                throw new DomainException('Delivery tier no longer matches the quoted distance.');
            }
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->id);
            $items = $lockedCart->items()->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw new DomainException('Cart is empty.');
            }
            $subtotal = BigDecimal::zero();
            $resolved = [];
            foreach ($items as $item) {
                $variant = ProductVariant::query()->with('product')->lockForUpdate()->findOrFail($item->product_variant_id);
                if (! $variant->is_active || ! $variant->product->is_active || $variant->stock_quantity < $item->quantity) {
                    throw new DomainException('Stock changed.');
                } $line = BigDecimal::of($variant->price)->multipliedBy($item->quantity);
                $subtotal = $subtotal->plus($line);
                $resolved[] = [$item, $variant, $line];
            }
            $fee = BigDecimal::of($quote->fee);
            $total = $subtotal->plus($fee)->toScale(3, RoundingMode::HalfUp);
            $number = 'GLW-'.Str::upper(Str::random(10));
            $order = Order::query()->create(['user_id' => $user?->id, 'number' => $number, 'status' => OrderStatus::Pending, 'customer_name' => $customer['customer_name'], 'phone' => $customer['phone'], 'secondary_phone' => $customer['secondary_phone'] ?? null, 'delivery_city_region' => $customer['city_region'] ?? null, 'delivery_address' => $customer['address'] ?? null, 'delivery_landmark' => $customer['landmark'] ?? null, 'delivery_place_id' => $customer['place_id'] ?? null, 'notes' => $customer['notes'] ?? null, 'delivery_latitude' => $customer['latitude'], 'delivery_longitude' => $customer['longitude'], 'delivery_distance_km' => $quote->distanceKm, 'delivery_duration_minutes' => (int) ceil($quote->durationSeconds / 60), 'delivery_fee' => $quote->fee, 'store_latitude_snapshot' => $setting->store_latitude, 'store_longitude_snapshot' => $setting->store_longitude, 'store_address_snapshot' => $setting->store_formatted_address, 'subtotal' => $subtotal->toScale(3, RoundingMode::HalfUp), 'discount' => '0.000', 'total' => $total]);
            foreach ($resolved as [$item,$variant,$line]) {
                $order->items()->create(['product_variant_id' => $variant->id, 'product_name' => $variant->product->name_ar, 'variant_name' => $variant->name ?? $variant->size, 'sku' => $variant->sku, 'unit_price' => $variant->price, 'quantity' => $item->quantity, 'subtotal' => $line->toScale(3, RoundingMode::HalfUp)]);
                $this->stock->adjust($variant, -$item->quantity, StockMovementType::OrderSale, 'order-sale-'.$order->id.'-'.$variant->id, $user, $order->number);
            }
            $lockedCart->items()->delete();

            return $order;
        });
    }
}
