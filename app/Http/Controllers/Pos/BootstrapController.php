<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Services\HomepageService;
use App\Services\PosCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    public function __invoke(Request $request, PosCatalogService $catalog, HomepageService $content): JsonResponse
    {
        $device = $request->attributes->get('pos_device');
        $store = $content->all();

        return response()->json([
            'device' => ['id' => $device->id, 'name' => $device->name, 'cashier' => $device->user->name, 'last_sequence' => (int) $device->sales()->max('sequence')],
            'store' => ['phone' => $store['phone'], 'address' => $store['address']],
            'variants' => $catalog->variants(),
        ]);
    }
}
