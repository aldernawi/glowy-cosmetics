<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator_only_when_an_email_is_configured(): void
    {
        config()->set('glowy.admin.email', 'admin@glowy.test');
        config()->set('glowy.admin.name', 'Glowy Admin');
        config()->set('glowy.admin.password', 'a-strong-test-password');

        $this->seed(AdminUserSeeder::class);

        $administrator = User::query()->where('email', 'admin@glowy.test')->firstOrFail();

        $this->assertTrue($administrator->can('admin.access'));
    }
}
