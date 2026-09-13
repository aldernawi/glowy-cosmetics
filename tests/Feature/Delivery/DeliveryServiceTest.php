<?php

namespace Tests\Feature\Delivery;

use App\Models\DeliverySetting;
use App\Models\DeliveryTier;
use App\Services\DeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_fresh_tiered_quote(): void
    {
        config(['services.google_routes.key' => 'key']);
        DeliverySetting::query()->create(['store_latitude' => '32.8872000', 'store_longitude' => '13.1913000']);
        DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '20.000', 'fee' => '15.000', 'is_active' => true]);
        Http::fake(['routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 12500, 'duration' => '1200s']]])]);
        $quote = app(DeliveryService::class)->quote('32.9000000', '13.2000000');
        $this->assertSame('12.500', $quote->distanceKm);
        $this->assertSame('15.000', $quote->fee);
    }
}
