<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('glowy.admin.email');
        $password = config('glowy.admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $administrator = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('glowy.admin.name'),
                'password' => Hash::make($password),
            ],
        );

        $administrator->syncRoles(['administrator']);
    }
}
