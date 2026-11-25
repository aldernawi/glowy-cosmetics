<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenStreetMapGeocodingService
{
    /** @return array<int, array{latitude: string, longitude: string, address: string, label: string, secondary_label: string, place_id: string}> */
    public function search(string $query): array
    {
        $response = $this->get('search', [
            'q' => $query,
            'format' => 'jsonv2',
            'limit' => 5,
            'addressdetails' => 1,
        ], ['Accept-Language' => 'ar']);

        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('OpenStreetMap search is temporarily unavailable.');
        }

        return collect($response->json())
            ->filter(fn (mixed $result) => is_array($result) && isset($result['lat'], $result['lon'], $result['display_name'], $result['place_id']))
            ->map(fn (array $result) => $this->searchResult($result))
            ->values()->all();
    }

    /** @return array{latitude: string, longitude: string, address: string, place_id: string} */
    public function reverse(string $latitude, string $longitude): array
    {
        $response = $this->get('reverse', [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'jsonv2',
            'zoom' => 18,
            'addressdetails' => 0,
        ]);

        $result = $response->json();

        if (! $response->successful() || ! is_array($result) || ! isset($result['lat'], $result['lon'], $result['display_name'], $result['place_id'])) {
            throw new RuntimeException('OpenStreetMap reverse lookup is temporarily unavailable.');
        }

        return [
            'latitude' => (string) $result['lat'],
            'longitude' => (string) $result['lon'],
            'address' => (string) $result['display_name'],
            'place_id' => 'osm-'.(string) $result['place_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     */
    private function get(string $endpoint, array $query, array $headers = []): Response
    {
        try {
            return Http::acceptJson()->connectTimeout(5)->timeout(10)->withHeaders([
                'User-Agent' => config('app.name').'/1.0 ('.config('app.url').')',
            ] + $headers)->get("https://nominatim.openstreetmap.org/{$endpoint}", $query);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenStreetMap is unreachable.', previous: $exception);
        }
    }

    /** @param array<string, mixed> $result
     * @return array{latitude: string, longitude: string, address: string, label: string, secondary_label: string, place_id: string}
     */
    private function searchResult(array $result): array
    {
        $address = (string) $result['display_name'];
        $parts = is_array($result['address'] ?? null) ? $result['address'] : [];
        $label = $this->firstAddressPart($parts, ['name', 'road', 'neighbourhood', 'suburb', 'village', 'town', 'city', 'county', 'state']) ?? $address;
        $secondaryLabel = collect(['city', 'town', 'village', 'county', 'state', 'country'])
            ->map(fn (string $key) => $parts[$key] ?? null)
            ->filter(fn (mixed $part) => is_string($part) && $part !== '' && $part !== $label)
            ->unique()
            ->take(2)
            ->implode('، ');

        return [
            'latitude' => (string) $result['lat'],
            'longitude' => (string) $result['lon'],
            'address' => $address,
            'label' => $label,
            'secondary_label' => $secondaryLabel,
            'place_id' => 'osm-'.(string) $result['place_id'],
        ];
    }

    /** @param array<string, mixed> $parts
     * @param  array<int, string>  $keys
     */
    private function firstAddressPart(array $parts, array $keys): ?string
    {
        foreach ($keys as $key) {
            $part = $parts[$key] ?? null;

            if (is_string($part) && $part !== '') {
                return $part;
            }
        }

        return null;
    }
}
