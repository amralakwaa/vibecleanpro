<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['view_any_service', 'create_service', 'view_any_page', 'create_page', 'view_any_lead', 'create_lead', 'view_any_user'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_inactive_user_cannot_access_the_panel(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_active_user_with_no_role_can_enter_but_sees_no_protected_resource(): void
    {
        // canAccessPanel() only gates is_active; Policies gate everything else,
        // so a role-less active user reaches an effectively empty shell.
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/admin/services')->assertForbidden();
    }

    public function test_super_admin_can_access_the_panel_and_every_resource(): void
    {
        Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/admin/services/create')->assertOk();
        $this->actingAs($user)->get('/admin/users')->assertOk();
    }

    public function test_content_manager_can_manage_services_but_not_users(): void
    {
        $role = Role::findOrCreate('Content Manager', 'web');
        $role->syncPermissions(['view_any_service', 'create_service', 'view_any_page', 'create_page']);

        $user = User::factory()->create();
        $user->assignRole('Content Manager');

        $this->actingAs($user)->get('/admin/services/create')->assertOk();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_sales_can_only_access_leads(): void
    {
        $role = Role::findOrCreate('Sales', 'web');
        $role->syncPermissions(['view_any_lead', 'create_lead']);

        $user = User::factory()->create();
        $user->assignRole('Sales');

        $this->actingAs($user)->get('/admin/leads')->assertOk();
        $this->actingAs($user)->get('/admin/services')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_seo_manager_can_manage_pages_but_not_create_services(): void
    {
        $role = Role::findOrCreate('SEO Manager', 'web');
        $role->syncPermissions(['view_any_page', 'create_page', 'view_any_service']);

        $user = User::factory()->create();
        $user->assignRole('SEO Manager');

        $this->actingAs($user)->get('/admin/pages/create')->assertOk();
        $this->actingAs($user)->get('/admin/services')->assertOk();
        $this->actingAs($user)->get('/admin/services/create')->assertForbidden();
    }
}
