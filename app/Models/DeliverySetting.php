<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['store_latitude' => 'decimal:7', 'store_longitude' => 'decimal:7'];
    }

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }
}
