<?php

namespace Tests\Feature\Delivery;

use App\Models\DeliveryTier;
use App\Services\DeliveryTierService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTierServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_boundaries_are_unambiguous_and_decimal_safe(): void
    {
        $first = DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '10.000', 'is_active' => true]);
        $second = DeliveryTier::query()->create(['min_distance_km' => '10.000', 'max_distance_km' => '20.000', 'fee' => '15.000', 'is_active' => true]);

        $tiers = app(DeliveryTierService::class);

        $this->assertSame($first->id, $tiers->tierForDistance('10.000')->id);
        $this->assertSame($second->id, $tiers->tierForDistance('10.001')->id);
    }

    public function test_active_tiers_cannot_have_a_gap_or_overlap(): void
    {
        DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '10.000', 'is_active' => true]);
        DeliveryTier::query()->create(['min_distance_km' => '11.000', 'max_distance_km' => '20.000', 'fee' => '15.000', 'is_active' => true]);

        $this->expectException(DomainException::class);

        app(DeliveryTierService::class)->validateActiveTiers();
    }

    public function test_out_of_range_distance_uses_the_highest_active_tier(): void
    {
        DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '10.000', 'is_active' => true]);
        $nearest = DeliveryTier::query()->create(['min_distance_km' => '10.000', 'max_distance_km' => '20.000', 'fee' => '15.000', 'is_active' => true]);

        $this->assertSame($nearest->id, app(DeliveryTierService::class)->tierForDistance('34.500')->id);
    }
}
