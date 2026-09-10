<?php

namespace Tests\Feature\Seo;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Verifies the actual seeded RBAC (RolePermissionSeeder), not a hand-rolled
 * substitute, so this fails the moment that seeder's SEO-related grants
 * drift from what this project's roles are supposed to be able to do.
 */
class SeoPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_seo_manager_can_view_the_seo_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SEO Manager');

        $this->assertTrue($user->can('view_seo_dashboard'));
    }

    public function test_seo_manager_can_manage_pages_redirects_and_internal_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SEO Manager');

        foreach (['view_any_page', 'create_page', 'update_page', 'delete_page'] as $permission) {
            $this->assertTrue($user->can($permission), "Expected SEO Manager to have {$permission}.");
        }
        foreach (['view_any_redirect', 'create_redirect', 'update_redirect', 'delete_redirect'] as $permission) {
            $this->assertTrue($user->can($permission), "Expected SEO Manager to have {$permission}.");
        }
        foreach (['view_any_internal_link', 'create_internal_link', 'update_internal_link', 'delete_internal_link'] as $permission) {
            $this->assertTrue($user->can($permission), "Expected SEO Manager to have {$permission}.");
        }
    }

    public function test_seo_manager_cannot_manage_business_profile_users_or_site_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SEO Manager');

        $this->assertFalse($user->can('manage_business_profile'));
        $this->assertFalse($user->can('manage_site_settings'));
        $this->assertFalse($user->can('view_any_user'));
        $this->assertFalse($user->can('create_user'));
    }

    public function test_seo_manager_cannot_create_services_areas_or_other_business_content(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SEO Manager');

        $this->assertTrue($user->can('view_any_service'), 'SEO Manager needs read access to relate pages to services.');
        $this->assertFalse($user->can('create_service'));
        $this->assertFalse($user->can('update_service'));
        $this->assertFalse($user->can('delete_service'));
    }

    public function test_content_manager_does_not_get_the_sitewide_seo_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Content Manager');

        $this->assertFalse($user->can('view_seo_dashboard'));
    }

    public function test_administrator_gets_full_seo_access_including_the_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->assertTrue($user->can('view_seo_dashboard'));
        $this->assertTrue($user->can('update_redirect'));
        $this->assertTrue($user->can('update_internal_link'));
        $this->assertTrue($user->can('manage_business_profile'));
    }

    public function test_a_role_less_user_has_no_seo_permissions_at_all(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('view_seo_dashboard'));
        $this->assertFalse($user->can('view_any_page'));
        $this->assertFalse($user->can('update_redirect'));
    }

    public function test_seo_manager_can_reach_the_pages_and_redirects_admin_screens_but_not_service_creation(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create();
        $user->assignRole('SEO Manager');

        $this->actingAs($user)->get('/admin/pages')->assertOk();
        $this->actingAs($user)->get('/admin/redirects')->assertOk();
        $this->actingAs($user)->get('/admin/internal-links')->assertOk();
        $this->actingAs($user)->get('/admin/services/create')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }
}
