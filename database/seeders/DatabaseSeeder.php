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
        $this->call(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Local Admin (dev only)',
            'email' => 'admin@vibecleanpro.test',
        ]);
        $admin->assignRole('Super Admin');

        BusinessProfile::query()->firstOrCreate(['id' => 1], [
            'name' => 'Vibe Clean Pro',
            'city' => 'الرياض',
        ]);

        // Licensed stock illustrations for the hero and the service
        // catalogue - part of the initial content, not test data.
        $this->call(InitialMediaSeeder::class);
    }
}
