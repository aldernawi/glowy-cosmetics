<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function adjust(
        ProductVariant $variant,
        int $delta,
        StockMovementType $type,
        string $idempotencyKey,
        ?User $actor = null,
        ?string $reference = null,
        ?string $notes = null,
    ): StockMovement {
        return DB::transaction(function () use ($variant, $delta, $type, $idempotencyKey, $actor, $reference, $notes): StockMovement {
            $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->getKey());

            $existingMovement = StockMovement::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingMovement !== null) {
                return $existingMovement;
            }

            $quantityBefore = $lockedVariant->stock_quantity;
            $quantityAfter = $quantityBefore + $delta;

            if ($quantityAfter < 0) {
                throw new DomainException('Insufficient stock for this operation.');
            }

            $lockedVariant->update(['stock_quantity' => $quantityAfter]);

            return StockMovement::query()->create([
                'product_variant_id' => $lockedVariant->id,
                'user_id' => $actor?->id,
                'type' => $type,
                'delta' => $delta,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'notes' => $notes,
            ]);
        });
    }
}
