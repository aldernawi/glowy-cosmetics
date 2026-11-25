<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PosPaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Services\PosSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class SyncController extends Controller
{
    /** Each sale is handled on its own: one bad record must never hold back the rest. */
    public function __invoke(Request $request, PosSaleService $sales): JsonResponse
    {
        $request->validate(['sales' => ['required', 'array', 'list', 'max:50'], 'sales.*' => ['array']]);
        $device = $request->attributes->get('pos_device');

        return response()->json([
            'results' => array_map(fn (array $sale): array => $this->store($device, $sale, $sales), $request->input('sales')),
        ]);
    }

    /**
     * @param  array<string, mixed>  $sale
     * @return array{uuid: mixed, status: string, message?: string}
     */
    private function store(PosDevice $device, array $sale, PosSaleService $sales): array
    {
        $uuid = $sale['uuid'] ?? null;
        $validator = Validator::make($sale, [
            'uuid' => ['required', 'uuid'],
            'receipt_number' => ['required', 'string', 'max:32'],
            'sequence' => ['required', 'integer', 'min:1', 'max:4000000000'],
            'sold_at' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PosPaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'amount_received' => ['nullable', 'decimal:0,3', 'min:0', 'max:999999999'],
            'items' => ['required', 'array', 'list', 'max:200'],
            'items.*.variant_id' => ['required', 'integer', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.variant' => ['nullable', 'string', 'max:255'],
            'items.*.sku' => ['required', 'string', 'max:255'],
            'items.*.unit_price' => ['required', 'decimal:0,3', 'min:0', 'max:999999'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        if ($validator->fails()) {
            return ['uuid' => $uuid, 'status' => 'rejected', 'message' => $validator->errors()->first()];
        }

        try {
            $sales->record($device, $validator->validated());
        } catch (Throwable $exception) {
            report($exception);

            return ['uuid' => $uuid, 'status' => 'error', 'message' => 'تعذر حفظ البيع في الخادم. ستُعاد المحاولة تلقائيًا.'];
        }

        return ['uuid' => $uuid, 'status' => 'saved'];
    }
}
