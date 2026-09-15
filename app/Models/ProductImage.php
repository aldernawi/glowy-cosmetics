<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(function (): string {
            if (Str::startsWith($this->path, ['http://', 'https://', '/'])) {
                return $this->path;
            }

            return Str::startsWith($this->path, 'images/') ? asset($this->path) : Storage::disk('public')->url($this->path);
        });
    }
}
