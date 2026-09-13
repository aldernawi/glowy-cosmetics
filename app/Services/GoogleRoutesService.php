<?php

namespace App\Services;

use App\Data\RouteResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleRoutesService
{
    public function drivingRoute(string $originLatitude, string $originLongitude, string $destinationLatitude, string $destinationLongitude): RouteResult
    {
        $key = config('services.google_routes.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Google Routes is not configured.');
        }

        $response = Http::acceptJson()->withHeaders([
            'X-Goog-Api-Key' => $key,
            'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration',
        ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
            'origin' => ['location' => ['latLng' => ['latitude' => (float) $originLatitude, 'longitude' => (float) $originLongitude]]],
            'destination' => ['location' => ['latLng' => ['latitude' => (float) $destinationLatitude, 'longitude' => (float) $destinationLongitude]]],
            'travelMode' => 'DRIVE', 'routingPreference' => 'TRAFFIC_UNAWARE', 'computeAlternativeRoutes' => false,
        ]);
        if (! $response->successful() || $response->json('routes.0.distanceMeters') === null) {
            throw new RuntimeException('Google Routes could not calculate a delivery route.');
        }
        $route = $response->json('routes.0');

        return new RouteResult((int) $route['distanceMeters'], (int) rtrim((string) $route['duration'], 's'));
    }
}
