<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_form_a_nested_hierarchy(): void
    {
        $parent = Category::query()->create([
            'name_ar' => 'العناية بالبشرة',
            'slug' => 'skincare',
        ]);

        $child = Category::query()->create([
            'name_ar' => 'الغسولات',
            'slug' => 'cleansers',
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->contains($child));
    }
}
