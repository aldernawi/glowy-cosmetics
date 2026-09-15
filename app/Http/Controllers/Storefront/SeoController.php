<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Concern;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo.sitemap', 3600, function (): string {
            $urls = collect([
                ['loc' => route('home'), 'updated' => null],
                ['loc' => route('products.index'), 'updated' => null],
            ]);
            Product::query()->sellable()->select(['id', 'slug', 'updated_at'])->latest('updated_at')->each(fn (Product $product) => $urls->push(['loc' => route('products.show', $product), 'updated' => $product->updated_at?->toAtomString()]));
            Brand::query()->where('is_active', true)->select(['id', 'slug', 'updated_at'])->each(fn (Brand $brand) => $urls->push(['loc' => route('brands.show', $brand), 'updated' => $brand->updated_at?->toAtomString()]));
            Concern::query()->where('is_active', true)->select(['id', 'slug', 'updated_at'])->each(fn (Concern $concern) => $urls->push(['loc' => route('concerns.show', $concern), 'updated' => $concern->updated_at?->toAtomString()]));

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /checkout\nDisallow: /cart\nSitemap: ".route('seo.sitemap')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
