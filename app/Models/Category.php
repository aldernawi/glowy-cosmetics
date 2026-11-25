<?php

namespace App\Models;

use App\Services\SitemapCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_featured' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(SitemapCacheService::class)->forget());
        static::deleted(fn () => app(SitemapCacheService::class)->forget());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * This category's id plus every active category nested below it.
     *
     * @return array<int, int>
     */
    public function activeTreeIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $frontier = self::query()->whereIn('parent_id', $frontier)->whereNotIn('id', $ids)->where('is_active', true)->pluck('id')->all();
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
