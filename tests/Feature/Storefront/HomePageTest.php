<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_the_storefront_homepage_renders_as_an_arabic_rtl_document(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('Glowy');
    }
}
