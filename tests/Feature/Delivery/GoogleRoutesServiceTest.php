<?php

namespace Tests\Feature\Delivery;

use App\Services\GoogleRoutesService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleRoutesServiceTest extends TestCase
{
    public function test_it_requests_a_driving_route_with_the_minimal_field_mask(): void
    {
        config(['services.google_routes.key' => 'server-only-key']);
        Http::fake(['routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 12500, 'duration' => '1234s']]])]);
        $result = app(GoogleRoutesService::class)->drivingRoute('32.8872000', '13.1913000', '32.9000000', '13.2000000');
        $this->assertSame(12500, $result->distanceMeters);
        Http::assertSent(fn ($request) => $request->hasHeader('X-Goog-FieldMask', 'routes.distanceMeters,routes.duration') && $request['travelMode'] === 'DRIVE');
    }
}
