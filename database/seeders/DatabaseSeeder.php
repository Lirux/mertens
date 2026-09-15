<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Demo Anlagenverwalter',
                'role' => User::ROLE_ASSET_MANAGER,
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'mitarbeiter@example.com'],
            [
                'name' => 'Demo Mitarbeiter',
                'role' => User::ROLE_EMPLOYEE,
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );

        $this->call(AssetSeeder::class);
    }
}
