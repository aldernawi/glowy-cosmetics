<?php

namespace Tests\Feature\Delivery;

use App\Services\OpenStreetMapGeocodingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenStreetMapGeocodingServiceTest extends TestCase
{
    public function test_it_returns_normalized_search_results_from_nominatim(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([[
            'lat' => '32.8872000', 'lon' => '13.1913000', 'display_name' => 'طرابلس، ليبيا', 'place_id' => 123,
        ]])]);

        $results = app(OpenStreetMapGeocodingService::class)->search('طرابلس ليبيا');

        $this->assertSame([['latitude' => '32.8872000', 'longitude' => '13.1913000', 'address' => 'طرابلس، ليبيا', 'label' => 'طرابلس، ليبيا', 'secondary_label' => '', 'place_id' => 'osm-123']], $results);
        Http::assertSent(fn ($request) => $request->hasHeader('User-Agent') && $request['q'] === 'طرابلس ليبيا' && $request['limit'] === 5);
    }

    public function test_it_returns_a_normalized_reverse_lookup_from_nominatim(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
            'lat' => '32.8872000', 'lon' => '13.1913000', 'display_name' => 'الظهرة، طرابلس، ليبيا', 'place_id' => 456,
        ])]);

        $result = app(OpenStreetMapGeocodingService::class)->reverse('32.8872000', '13.1913000');

        $this->assertSame([
            'latitude' => '32.8872000',
            'longitude' => '13.1913000',
            'address' => 'الظهرة، طرابلس، ليبيا',
            'place_id' => 'osm-456',
        ], $result);
        Http::assertSent(fn ($request) => $request['lat'] === '32.8872000' && $request['lon'] === '13.1913000' && $request['zoom'] === 18);
    }

    public function test_it_builds_a_short_arabic_label_for_location_search_results(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([[
            'lat' => '32.8880000',
            'lon' => '13.1900000',
            'display_name' => 'السبعة، طرابلس، شعبية طرابلس، ليبيا',
            'place_id' => 789,
            'address' => ['suburb' => 'السبعة', 'city' => 'طرابلس', 'country' => 'ليبيا'],
        ]])]);

        $result = app(OpenStreetMapGeocodingService::class)->search('السبعة')[0];

        $this->assertSame('السبعة', $result['label']);
        $this->assertSame('طرابلس، ليبيا', $result['secondary_label']);
        Http::assertSent(fn ($request) => $request['addressdetails'] === 1 && $request->hasHeader('Accept-Language', 'ar'));
    }
}
