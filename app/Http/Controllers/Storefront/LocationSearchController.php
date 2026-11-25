<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\LocationSearchRequest;
use App\Http\Requests\Storefront\ReverseLocationRequest;
use App\Services\OpenStreetMapGeocodingService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class LocationSearchController extends Controller
{
    public function search(LocationSearchRequest $request, OpenStreetMapGeocodingService $geocoding): JsonResponse
    {
        try {
            return response()->json(['results' => $geocoding->search($request->string('q')->toString())]);
        } catch (RuntimeException) {
            return response()->json(['message' => 'تعذر البحث عن الموقع حاليًا. اختاريه مباشرة من الخريطة.'], 422);
        }
    }

    public function reverse(ReverseLocationRequest $request, OpenStreetMapGeocodingService $geocoding): JsonResponse
    {
        try {
            return response()->json(['result' => $geocoding->reverse(
                $request->string('latitude')->toString(),
                $request->string('longitude')->toString(),
            )]);
        } catch (RuntimeException) {
            return response()->json(['message' => 'تعذر جلب اسم العنوان حاليًا. يمكنك حفظ الإحداثيات كما هي.'], 422);
        }
    }
}
