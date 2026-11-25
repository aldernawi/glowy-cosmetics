<?php

namespace App\Services;

use App\Models\HomepageSection;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomepageService
{
    public const DEFAULTS = [
        'hero_title' => 'كل بشرة تستحق لحظة Glowy',
        'hero_subtitle' => 'منتجات عناية أصلية ومختارة بعناية، لتبني روتينًا بسيطًا يناسب بشرتك.',
        'hero_cta_label' => 'تسوّقي الآن',
        'hero_cta_url' => '/products',
        'hero_image_path' => 'images/editorial/glowy-hero.webp',
        'announcement' => 'توصيل محسوب بدقة حسب موقعك ✦ الدفع عند الاستلام',
        'promo_title' => 'روتينك يبدأ بخطوة صحيحة',
        'promo_body' => 'ابدئي بغسول لطيف، ثم عالجي احتياج بشرتك، اختمي بالترطيب ولا تنسي الحماية من الشمس صباحًا.',
        'promo_cta_label' => 'كوّني روتينك',
        'promo_cta_url' => '/products',
        'featured_category_ids' => [],
        'featured_brand_ids' => [],
        'featured_concern_ids' => [],
        'new_product_ids' => [],
        'best_seller_ids' => [],
        'phone' => '', 'whatsapp' => '', 'email' => '', 'address' => '', 'working_hours' => '',
        'meta_title' => 'Glowy | عناية تستحقها بشرتك',
        'meta_description' => 'متجر Glowy لمنتجات العناية بالبشرة والجمال المختارة بعناية.',
    ];

    public function all(): array
    {
        return Cache::remember('glowy.content.settings', 3600, function (): array {
            $settings = array_replace(self::DEFAULTS, Setting::query()->pluck('value', 'key')->all());
            foreach (['featured_category_ids', 'featured_brand_ids', 'featured_concern_ids', 'new_product_ids', 'best_seller_ids'] as $key) {
                $settings[$key] = is_array($settings[$key]) ? $settings[$key] : (json_decode((string) $settings[$key], true) ?: []);
            }

            return $settings;
        });
    }

    public function update(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                Setting::query()->updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode(array_values($value)) : $value]);
            }
        });
        Cache::forget('glowy.content.settings');
    }

    /** @return array<string, array{is_visible: bool, sort_order: int}> */
    public function sections(): array
    {
        $defaults = collect(['concerns', 'categories', 'new_arrivals', 'best_sellers', 'promotion', 'brands', 'routine', 'why_glowy', 'banners'])
            ->mapWithKeys(fn (string $key, int $order) => [$key => ['is_visible' => true, 'sort_order' => $order]])
            ->all();

        foreach (HomepageSection::query()->get() as $section) {
            $defaults[$section->key] = ['is_visible' => $section->is_visible, 'sort_order' => $section->sort_order];
        }

        uasort($defaults, fn (array $first, array $second) => $first['sort_order'] <=> $second['sort_order']);

        return $defaults;
    }
}
