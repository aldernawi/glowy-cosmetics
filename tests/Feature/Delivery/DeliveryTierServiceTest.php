<?php

namespace Tests\Feature\Delivery;

use App\Exceptions\OutOfDeliveryRangeException;
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

    public function test_a_distance_beyond_the_highest_active_tier_is_rejected(): void
    {
        DeliveryTier::query()->create(['min_distance_km' => '0.000', 'max_distance_km' => '10.000', 'fee' => '10.000', 'is_active' => true]);
        $highest = DeliveryTier::query()->create(['min_distance_km' => '10.000', 'max_distance_km' => '20.000', 'fee' => '15.000', 'is_active' => true]);

        $tiers = app(DeliveryTierService::class);
        $this->assertSame($highest->id, $tiers->tierForDistance('20.000')->id);

        $this->expectException(OutOfDeliveryRangeException::class);
        $tiers->tierForDistance('20.001');
    }
}
