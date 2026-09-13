<?php

namespace App\Data;

readonly class CartMergeResult
{
    /** @param array<int> $adjustedVariantIds */
    public function __construct(public array $adjustedVariantIds = []) {}

    public function hasAdjustments(): bool
    {
        return $this->adjustedVariantIds !== [];
    }
}
