<?php

namespace App\Services;

use App\Data\DeliveryQuote;
use App\Models\DeliverySetting;
use Brick\Math\BigDecimal;
use RuntimeException;

class DeliveryService
{
    public function __construct(private GoogleRoutesService $routes, private DeliveryTierService $tiers) {}

    public function quote(string $latitude, string $longitude): DeliveryQuote
    {
        $setting = DeliverySetting::current();
        if ($setting?->store_latitude === null || $setting->store_longitude === null) {
            throw new RuntimeException('Store delivery origin is not configured.');
        }
        $route = $this->routes->drivingRoute($setting->store_latitude, $setting->store_longitude, $latitude, $longitude);
        $distanceKm = BigDecimal::of($route->distanceMeters)->dividedBy('1000', 3)->__toString();
        $tier = $this->tiers->tierForDistance($distanceKm);

        return new DeliveryQuote($distanceKm, $route->durationSeconds, $tier->fee, $tier->id, $setting->id);
    }
}
