<?php

namespace App\Models;

use App\Services\SitemapCacheService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_featured' => 'boolean'];
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo_path ? '/storage/'.ltrim($this->logo_path, '/') : null);
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(SitemapCacheService::class)->forget());
        static::deleted(fn () => app(SitemapCacheService::class)->forget());
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
