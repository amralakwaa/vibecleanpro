<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RbacFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie caches roles/permissions; each test creates its own, so
        // start from a clean cache to avoid bleed between test methods.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_a_role_can_be_assigned_to_a_user_and_retrieved(): void
    {
        Role::findOrCreate('Content Manager', 'web');
        $user = User::factory()->create();

        $user->assignRole('Content Manager');

        $this->assertTrue($user->fresh()->hasRole('Content Manager'));
    }

    public function test_super_admin_bypasses_an_ordinary_permission_via_gate_before(): void
    {
        Role::findOrCreate('Super Admin', 'web');
        Permission::findOrCreate('edit articles', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->assertTrue($admin->can('edit articles'));
    }

    public function test_a_user_without_the_permission_does_not_get_the_bypass(): void
    {
        Permission::findOrCreate('edit articles', 'web');
        $user = User::factory()->create();

        $this->assertFalse($user->can('edit articles'));
    }
}
