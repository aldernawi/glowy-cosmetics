<?php

namespace App\Services;

use App\Data\RouteResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouteService
{
    public function drivingRoute(string $originLatitude, string $originLongitude, string $destinationLatitude, string $destinationLongitude): RouteResult
    {
        $key = config('services.openrouteservice.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('OpenRouteService is not configured.');
        }

        $baseUrl = rtrim((string) config('services.openrouteservice.base_url'), '/');
        $endpoint = '/v2/directions/driving-car';
        $url = str_ends_with($baseUrl, $endpoint) ? $baseUrl : "{$baseUrl}{$endpoint}";

        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(15)->withToken($key)->post($url, [
                'coordinates' => [
                    [(float) $originLongitude, (float) $originLatitude],
                    [(float) $destinationLongitude, (float) $destinationLatitude],
                ],
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenRouteService is unreachable.', previous: $exception);
        }

        $summary = $response->json('routes.0.summary');

        if (! $response->successful() || ! is_array($summary) || ! isset($summary['distance'], $summary['duration'])) {
            throw new RuntimeException('OpenRouteService could not calculate a delivery route.');
        }

        return new RouteResult((int) round($summary['distance']), (int) round($summary['duration']));
    }
}
