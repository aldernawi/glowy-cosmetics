<?php

namespace App\Services;

use App\Data\CartMergeResult;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function add(Cart $cart, ProductVariant $variant, int $quantity): void
    {
        DB::transaction(function () use ($cart, $variant, $quantity): void {
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->id);
            $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            if (! $lockedVariant->is_active) {
                throw new DomainException('This variant is unavailable.');
            }

            $item = $lockedCart->items()->where('product_variant_id', $lockedVariant->id)->lockForUpdate()->first();
            $finalQuantity = ($item?->quantity ?? 0) + $quantity;

            if ($finalQuantity > $lockedVariant->stock_quantity) {
                throw new DomainException('Insufficient stock for this cart quantity.');
            }

            if ($item === null) {
                $lockedCart->items()->create(['product_variant_id' => $lockedVariant->id, 'quantity' => $finalQuantity]);
            } else {
                $item->update(['quantity' => $finalQuantity]);
            }
        });
    }

    public function resolve(Request $request): Cart
    {
        if ($request->user() instanceof User) {
            return Cart::query()->firstOrCreate(['user_id' => $request->user()->id]);
        }

        $cookieName = config('cart.cookie');
        $token = $request->cookie($cookieName);

        if (is_string($token) && $token !== '') {
            $cart = Cart::query()->where('guest_token_hash', hash('sha256', $token))->first();

            if ($cart !== null) {
                return $cart;
            }
        }

        $token = bin2hex(random_bytes(32));
        $cart = Cart::query()->create(['guest_token_hash' => hash('sha256', $token)]);

        Cookie::queue(cookie(
            $cookieName,
            $token,
            config('cart.lifetime'),
            '/',
            null,
            config('cart.secure'),
            true,
            false,
            'lax',
        ));

        return $cart;
    }

    public function mergeGuestCartIntoUserCart(User $user, string $guestToken): CartMergeResult
    {
        return DB::transaction(function () use ($user, $guestToken): CartMergeResult {
            $guestCart = Cart::query()
                ->where('guest_token_hash', hash('sha256', $guestToken))
                ->lockForUpdate()
                ->first();

            if ($guestCart === null) {
                return new CartMergeResult;
            }

            $userCart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
            $userCart = Cart::query()->lockForUpdate()->findOrFail($userCart->id);
            $guestItems = $guestCart->items()->lockForUpdate()->get()->keyBy('product_variant_id');
            $userItems = $userCart->items()->lockForUpdate()->get()->keyBy('product_variant_id');
            $variantIds = $guestItems->keys()->merge($userItems->keys())->unique()->sort()->values();
            $variants = ProductVariant::query()->whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id');
            $adjustedVariantIds = [];

            foreach ($variantIds as $variantId) {
                $guestQuantity = $guestItems->get($variantId)?->quantity ?? 0;
                $userQuantity = $userItems->get($variantId)?->quantity ?? 0;
                $requestedQuantity = $guestQuantity + $userQuantity;
                $availableQuantity = $variants->get($variantId)?->stock_quantity ?? 0;
                $finalQuantity = min($requestedQuantity, $availableQuantity);

                if ($finalQuantity < $requestedQuantity) {
                    $adjustedVariantIds[] = $variantId;
                }

                $item = $userItems->get($variantId);

                if ($finalQuantity === 0) {
                    $item?->delete();

                    continue;
                }

                if ($item === null) {
                    $userCart->items()->create(['product_variant_id' => $variantId, 'quantity' => $finalQuantity]);
                } else {
                    $item->update(['quantity' => $finalQuantity]);
                }
            }

            $guestCart->delete();

            return new CartMergeResult($adjustedVariantIds);
        });
    }
}
