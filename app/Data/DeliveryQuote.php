<?php

namespace App\Data;

readonly class DeliveryQuote
{
    public function __construct(public string $distanceKm, public int $durationSeconds, public string $fee, public int $tierId, public int $settingId) {}
}
