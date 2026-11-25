<?php

namespace App\Services;

use App\Enums\PosPaymentMethod;
use App\Enums\PosSaleStatus;
use App\Enums\StockMovementType;
use App\Models\PosDevice;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosStockShortage;
use App\Models\ProductVariant;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PosSaleService
{
    public const RESOLUTION_DEDUCTED = 'deducted';

    public const RESOLUTION_DISMISSED = 'dismissed';

    public const RESOLUTION_VOIDED = 'voided';

    public function __construct(private StockService $stock) {}

    /**
     * Records a sale that already happened in the shop. It is never refused for lack
     * of stock: whatever the system cannot cover is kept as a shortage to review.
     * The client uuid makes repeated uploads of the same sale harmless.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(PosDevice $device, array $data): PosSale
    {
        try {
            return DB::transaction(fn (): PosSale => $this->existing($data['uuid']) ?? $this->create($device, $data), 3);
        } catch (UniqueConstraintViolationException $exception) {
            return $this->existing($data['uuid']) ?? throw $exception;
        }
    }

    public function void(PosSale $sale, User $actor): PosSale
    {
        return DB::transaction(function () use ($sale, $actor): PosSale {
            $sale = PosSale::query()->with('items.productVariant')->lockForUpdate()->findOrFail($sale->id);

            if ($sale->status === PosSaleStatus::Voided) {
                return $sale;
            }

            foreach ($sale->items->sortBy('product_variant_id') as $item) {
                if ($item->productVariant !== null && $item->stock_deducted > 0) {
                    $this->stock->adjust($item->productVariant, $item->stock_deducted, StockMovementType::Return, 'pos-void-'.$item->id, $actor, $sale->receipt_number);
                }

                $item->shortages()->whereNull('resolved_at')->update(['resolution' => self::RESOLUTION_VOIDED, 'resolved_at' => now(), 'resolved_by' => $actor->id]);
            }

            $sale->update(['status' => PosSaleStatus::Voided, 'voided_at' => now(), 'voided_by' => $actor->id]);

            return $sale;
        });
    }

    /** Takes the missing quantity out of stock once it becomes available, e.g. after cancelling the web order that held it. */
    public function deductShortage(PosStockShortage $shortage, User $actor): void
    {
        $this->resolve($shortage, $actor, self::RESOLUTION_DEDUCTED, function (PosStockShortage $locked) use ($actor): void {
            $item = $locked->item;

            if ($item->productVariant === null) {
                throw new DomainException('The sold variant no longer exists.');
            }

            $this->stock->adjust($item->productVariant, -$locked->quantity, StockMovementType::PosSale, 'pos-shortage-'.$locked->id, $actor, $item->sale->receipt_number);
            $item->increment('stock_deducted', $locked->quantity);
        });
    }

    /** Closes the shortage without touching stock, e.g. when the recorded balance was already wrong. */
    public function dismissShortage(PosStockShortage $shortage, User $actor): void
    {
        $this->resolve($shortage, $actor, self::RESOLUTION_DISMISSED);
    }

    private function resolve(PosStockShortage $shortage, User $actor, string $resolution, ?callable $apply = null): void
    {
        DB::transaction(function () use ($shortage, $actor, $resolution, $apply): void {
            // The sale is locked first, in the same order as void(), so both cannot act on one shortage.
            PosSale::query()->whereKey($shortage->item->pos_sale_id)->lockForUpdate()->firstOrFail();
            $locked = PosStockShortage::query()->with('item.sale', 'item.productVariant')->lockForUpdate()->findOrFail($shortage->id);

            if ($locked->resolved_at !== null) {
                return;
            }

            if ($apply !== null) {
                $apply($locked);
            }

            $locked->update(['resolution' => $resolution, 'resolved_at' => now(), 'resolved_by' => $actor->id]);
        });
    }

    private function existing(string $uuid): ?PosSale
    {
        return PosSale::query()->where('uuid', $uuid)->first();
    }

    /** @param array<string, mixed> $data */
    private function create(PosDevice $device, array $data): PosSale
    {
        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', array_column($data['items'], 'variant_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $total = array_reduce($data['items'], fn (BigDecimal $sum, array $item) => $sum->plus($this->lineTotal($item)), BigDecimal::zero());
        $method = PosPaymentMethod::from($data['payment_method']);
        $received = $method === PosPaymentMethod::Cash && isset($data['amount_received']) ? BigDecimal::of($data['amount_received']) : null;
        $received = $received?->isGreaterThanOrEqualTo($total) ? $received : null;

        $sale = PosSale::query()->create([
            'uuid' => $data['uuid'],
            'pos_device_id' => $device->id,
            'user_id' => $device->user_id,
            'cashier_name' => $device->user->name,
            'receipt_number' => $data['receipt_number'],
            'sequence' => $data['sequence'],
            'status' => PosSaleStatus::Completed,
            'payment_method' => $method,
            'payment_reference' => $method === PosPaymentMethod::Cash ? null : ($data['payment_reference'] ?? null),
            'total' => $this->money($total),
            'amount_received' => $received === null ? null : $this->money($received),
            'change_due' => $received === null ? null : $this->money($received->minus($total)),
            'sold_at' => $this->soldAt($data['sold_at']),
        ]);

        foreach ($data['items'] as $item) {
            $this->addItem($sale, $device, $item, $variants->get($item['variant_id']));
        }

        return $sale;
    }

    /** @param array<string, mixed> $data */
    private function addItem(PosSale $sale, PosDevice $device, array $data, ?ProductVariant $variant): void
    {
        $quantity = (int) $data['quantity'];
        $available = $variant === null ? 0 : (int) ProductVariant::query()->whereKey($variant->id)->value('stock_quantity');
        $deducted = min($available, $quantity);

        /** @var PosSaleItem $item */
        $item = $sale->items()->create([
            'product_variant_id' => $variant?->id,
            'product_name' => $variant?->product->name_ar ?? $data['name'],
            'variant_name' => $variant === null ? ($data['variant'] ?? null) : ($variant->name ?: $variant->size),
            'sku' => $variant?->sku ?? $data['sku'],
            'unit_price' => $data['unit_price'],
            'catalog_price' => $variant?->price,
            'quantity' => $quantity,
            'stock_deducted' => $deducted,
            'subtotal' => $this->money($this->lineTotal($data)),
        ]);

        if ($variant !== null && $deducted > 0) {
            $this->stock->adjust($variant, -$deducted, StockMovementType::PosSale, 'pos-sale-'.$item->id, $device->user, $sale->receipt_number);
        }

        if ($deducted < $quantity) {
            $item->shortages()->create(['quantity' => $quantity - $deducted]);
        }
    }

    /** @param array<string, mixed> $item */
    private function lineTotal(array $item): BigDecimal
    {
        return BigDecimal::of((string) $item['unit_price'])->multipliedBy((int) $item['quantity']);
    }

    private function money(BigDecimal $amount): string
    {
        return (string) $amount->toScale(3, RoundingMode::HalfUp);
    }

    /** A device clock that runs ahead must not place sales in the future. */
    private function soldAt(string $value): Carbon
    {
        return Carbon::parse($value)->setTimezone(config('app.timezone'))->min(now());
    }
}
