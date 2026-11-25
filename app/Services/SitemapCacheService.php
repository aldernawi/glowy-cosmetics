<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SitemapCacheService
{
    public function forget(): void
    {
        Cache::forget('seo.sitemap');
    }
}
