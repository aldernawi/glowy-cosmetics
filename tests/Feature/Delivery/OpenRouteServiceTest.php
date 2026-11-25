<?php

namespace Tests\Feature\Delivery;

use App\Services\OpenRouteService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouteServiceTest extends TestCase
{
    public function test_it_requests_a_server_side_driving_route_at_the_current_heigit_endpoint(): void
    {
        config([
            'services.openrouteservice.key' => 'server-only-key',
            'services.openrouteservice.base_url' => 'https://api.heigit.org/openrouteservice',
        ]);
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(['routes' => [['summary' => ['distance' => 12500, 'duration' => 1234]]]])]);

        $result = app(OpenRouteService::class)->drivingRoute('32.8872000', '13.1913000', '32.9000000', '13.2000000');

        $this->assertSame(12500, $result->distanceMeters);
        $this->assertSame(1234, $result->durationSeconds);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer server-only-key')
            && $request->url() === 'https://api.heigit.org/openrouteservice/v2/directions/driving-car'
            && $request['coordinates'] === [[13.1913, 32.8872], [13.2, 32.9]]);
    }

    public function test_it_fails_closed_when_the_service_key_is_missing(): void
    {
        config(['services.openrouteservice.key' => null]);

        $this->expectExceptionMessage('OpenRouteService is not configured.');
        app(OpenRouteService::class)->drivingRoute('32.8872000', '13.1913000', '32.9000000', '13.2000000');
    }

    public function test_it_accepts_a_full_driving_endpoint_in_legacy_environment_configuration(): void
    {
        config([
            'services.openrouteservice.key' => 'server-only-key',
            'services.openrouteservice.base_url' => 'https://api.heigit.org/openrouteservice/v2/directions/driving-car',
        ]);
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(['routes' => [['summary' => ['distance' => 12500, 'duration' => 1234]]]])]);

        app(OpenRouteService::class)->drivingRoute('32.8872000', '13.1913000', '32.9000000', '13.2000000');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.heigit.org/openrouteservice/v2/directions/driving-car');
    }
}
