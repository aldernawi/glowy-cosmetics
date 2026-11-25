<?php

namespace App\Services;

use App\Models\DeliveryTier;
use Brick\Math\BigDecimal;
use DomainException;
use Illuminate\Support\Collection;

class DeliveryTierService
{
    public function validateActiveTiers(): void
    {
        $tiers = $this->activeTiers();

        if ($tiers->isEmpty()) {
            throw new DomainException('No active delivery tiers are configured.');
        }

        $previousMaximum = null;

        foreach ($tiers as $tier) {
            $minimum = BigDecimal::of($tier->min_distance_km);
            $maximum = BigDecimal::of($tier->max_distance_km);

            if (! $maximum->isGreaterThan($minimum)) {
                throw new DomainException('A delivery tier maximum must exceed its minimum.');
            }

            if ($previousMaximum === null && ! $minimum->isEqualTo(BigDecimal::zero())) {
                throw new DomainException('The first active delivery tier must begin at zero.');
            }

            if ($previousMaximum !== null && ! $minimum->isEqualTo($previousMaximum)) {
                throw new DomainException('Active delivery tiers must be contiguous without gaps or overlaps.');
            }

            $previousMaximum = $maximum;
        }
    }

    public function tierForDistance(string $distanceKm): DeliveryTier
    {
        $this->validateActiveTiers();
        $distance = BigDecimal::of($distanceKm);

        if ($distance->isLessThan(BigDecimal::zero())) {
            throw new DomainException('Delivery distance cannot be negative.');
        }

        $tier = $this->activeTiers()->first(fn (DeliveryTier $tier): bool => $distance->isLessThanOrEqualTo(BigDecimal::of($tier->max_distance_km)));

        if ($tier !== null) {
            return $tier;
        }

        $nearestTier = $this->activeTiers()->last();

        if ($nearestTier === null) {
            throw new DomainException('No active delivery tiers are configured.');
        }

        return $nearestTier;
    }

    /** @return Collection<int, DeliveryTier> */
    private function activeTiers(): Collection
    {
        return DeliveryTier::query()->where('is_active', true)->orderBy('min_distance_km')->get();
    }
}
