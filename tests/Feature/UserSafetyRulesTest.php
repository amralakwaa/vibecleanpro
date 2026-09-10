<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserSafetyRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        Permission::findOrCreate('delete_user', 'web');
        $role = Role::findOrCreate('Administrator', 'web');
        $role->syncPermissions(['delete_user']);

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->assertFalse($user->can('delete', $user));
    }

    public function test_the_last_super_admin_cannot_be_deleted(): void
    {
        Permission::findOrCreate('delete_user', 'web');
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $adminRole = Role::findOrCreate('Administrator', 'web');
        $adminRole->syncPermissions(['delete_user']);

        $lastSuperAdmin = User::factory()->create();
        $lastSuperAdmin->assignRole($superAdminRole);

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $this->assertFalse($admin->can('delete', $lastSuperAdmin));
    }

    public function test_a_second_super_admin_can_be_deleted(): void
    {
        Permission::findOrCreate('delete_user', 'web');
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $adminRole = Role::findOrCreate('Administrator', 'web');
        $adminRole->syncPermissions(['delete_user']);

        User::factory()->create()->assignRole($superAdminRole);
        $secondSuperAdmin = User::factory()->create();
        $secondSuperAdmin->assignRole($superAdminRole);

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $this->assertTrue($admin->can('delete', $secondSuperAdmin));
    }

    public function test_the_super_admin_role_itself_cannot_be_deleted(): void
    {
        Permission::findOrCreate('delete_role', 'web');
        $role = Role::findOrCreate('Administrator', 'web');
        $role->syncPermissions(['delete_role']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');

        $this->assertFalse($user->can('delete', $superAdminRole));
    }

    public function test_force_delete_requires_its_own_permission_separate_from_delete(): void
    {
        Permission::findOrCreate('delete_service', 'web');
        Permission::findOrCreate('force_delete_service', 'web');

        $role = Role::findOrCreate('Content Manager', 'web');
        $role->syncPermissions(['delete_service']);

        $user = User::factory()->create();
        $user->assignRole('Content Manager');

        $service = Service::factory()->create();

        $this->assertTrue($user->can('delete', $service));
        $this->assertFalse($user->can('forceDelete', $service));
    }
}
