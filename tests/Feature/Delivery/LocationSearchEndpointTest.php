<?php

namespace Tests\Feature\Delivery;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationSearchEndpointTest extends TestCase
{
    public function test_it_returns_a_reverse_lookup_for_a_selected_map_point(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
            'lat' => '32.8872000', 'lon' => '13.1913000', 'display_name' => 'الظهرة، طرابلس، ليبيا', 'place_id' => 456,
        ])]);

        $this->getJson(route('locations.reverse', ['latitude' => '32.8872000', 'longitude' => '13.1913000']))
            ->assertOk()
            ->assertJsonPath('result.place_id', 'osm-456')
            ->assertJsonPath('result.address', 'الظهرة، طرابلس، ليبيا');
    }

    public function test_it_rejects_invalid_reverse_lookup_coordinates(): void
    {
        $this->getJson(route('locations.reverse', ['latitude' => '91', 'longitude' => '13.1913000']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('latitude');
    }

    public function test_it_returns_a_friendly_error_when_the_geocoder_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->getJson(route('locations.search', ['q' => 'طرابلس']))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'تعذر البحث عن الموقع حاليًا. اختاريه مباشرة من الخريطة.');
    }

    public function test_it_returns_a_friendly_error_when_the_reverse_lookup_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->getJson(route('locations.reverse', ['latitude' => '32.8872000', 'longitude' => '13.1913000']))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'تعذر جلب اسم العنوان حاليًا. يمكنك حفظ الإحداثيات كما هي.');
    }
}
