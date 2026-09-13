<?php

namespace App\Data;

readonly class RouteResult
{
    public function __construct(public int $distanceMeters, public int $durationSeconds) {}
}
