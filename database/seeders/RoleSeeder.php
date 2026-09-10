<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * The panel roles this project currently plans for. Granular permissions
     * are assigned to these roles later, per resource, as Filament resources
     * are built — not created speculatively here.
     */
    private const ROLES = [
        'Super Admin',
        'Administrator',
        'SEO Manager',
        'Content Manager',
        'Project Manager',
        'Sales',
        'Viewer',
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
