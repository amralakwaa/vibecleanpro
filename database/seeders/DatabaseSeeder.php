<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
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
        $this->call(RoleSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $admin->assignRole('Super Admin');

        BusinessProfile::query()->firstOrCreate(['id' => 1], [
            'name' => 'Vibe Clean Pro',
            'city' => 'الرياض',
        ]);
    }
}
