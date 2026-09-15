<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Resources with Trash/Restore, gated by their own restore_* and
     * force_delete_* permissions (force_delete is granted to no seeded
     * role below - only Super Admin has it, via the Gate::before bypass).
     */
    private const SOFT_DELETE_RESOURCES = [
        'page', 'service', 'area', 'project', 'article', 'offer', 'testimonial', 'lead',
    ];

    /**
     * Resources with plain CRUD only, no Trash.
     */
    private const PLAIN_RESOURCES = [
        'user', 'role', 'service_category', 'area_group', 'article_category', 'faq', 'media', 'redirect', 'internal_link', 'team_member',
    ];

    /**
     * Singleton settings screens: one manage_* permission each, no CRUD shape.
     */
    private const SINGLETON_RESOURCES = ['business_profile', 'site_settings'];

    /**
     * One standalone permission (not CRUD-shaped) for the sitewide SEO
     * Dashboard - distinct from update_page, which already gates the
     * per-page audit panel on the Page edit screen itself.
     */
    private const STANDALONE_PERMISSIONS = ['view_seo_dashboard'];

    /**
     * The editorial content resources (a subset of the two CRUD lists above)
     * that Content Manager, SEO Manager, Project Manager and Viewer reason
     * about as a group.
     */
    private const CONTENT_RESOURCES = [
        'page', 'service', 'service_category', 'area', 'area_group',
        'project', 'article', 'article_category', 'offer', 'faq', 'testimonial', 'media', 'team_member',
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $this->createPermissions();

        // Permissions just created above must be visible before roles are
        // assigned, or Spatie's cached permission map is stale mid-request.
        $registrar->forgetCachedPermissions();

        $this->assignRolePermissions();
    }

    private function createPermissions(): void
    {
        foreach (self::SOFT_DELETE_RESOURCES as $resource) {
            foreach (['view_any', 'create', 'update', 'delete', 'restore', 'force_delete'] as $ability) {
                Permission::findOrCreate("{$ability}_{$resource}", 'web');
            }
        }

        foreach (self::PLAIN_RESOURCES as $resource) {
            foreach (['view_any', 'create', 'update', 'delete'] as $ability) {
                Permission::findOrCreate("{$ability}_{$resource}", 'web');
            }
        }

        foreach (self::SINGLETON_RESOURCES as $resource) {
            Permission::findOrCreate("manage_{$resource}", 'web');
        }

        foreach (self::STANDALONE_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    private function assignRolePermissions(): void
    {
        // Super Admin intentionally gets no explicit permissions: it has
        // full access purely through the Gate::before bypass, so the role
        // stays meaningful even if a permission here is missed or renamed.
        Role::findOrCreate('Super Admin', 'web');

        $administrator = Role::findOrCreate('Administrator', 'web');
        $administrator->syncPermissions([
            ...$this->crud(self::CONTENT_RESOURCES, restore: true),
            ...$this->crud(['lead'], restore: true),
            ...$this->crud(['redirect', 'internal_link', 'user', 'role']),
            'manage_business_profile',
            'manage_site_settings',
            'view_seo_dashboard',
        ]);

        $seoManager = Role::findOrCreate('SEO Manager', 'web');
        $seoManager->syncPermissions([
            ...$this->crud(['page'], restore: true),
            ...$this->crud(['redirect', 'internal_link']),
            'view_any_service', 'view_any_area', 'view_any_project', 'view_any_article', 'view_any_offer', 'view_any_media',
            'view_seo_dashboard',
        ]);

        $contentManager = Role::findOrCreate('Content Manager', 'web');
        $contentManager->syncPermissions([
            ...$this->crud(self::CONTENT_RESOURCES),
            ...$this->crud(['internal_link']),
        ]);

        $projectManager = Role::findOrCreate('Project Manager', 'web');
        $projectManager->syncPermissions([
            ...$this->crud(['project', 'media'], restore: true),
            'view_any_service', 'view_any_area',
        ]);

        $sales = Role::findOrCreate('Sales', 'web');
        $sales->syncPermissions(['view_any_lead', 'create_lead', 'update_lead']);

        $viewer = Role::findOrCreate('Viewer', 'web');
        $viewer->syncPermissions(
            collect([...self::CONTENT_RESOURCES, 'redirect'])
                ->map(fn (string $resource) => "view_any_{$resource}")
                ->all()
        );
    }

    /**
     * $restore only ever applies to resources that actually have Trash
     * (SOFT_DELETE_RESOURCES) - it is silently skipped for any other
     * resource, so a caller can safely pass a mixed list without needing
     * to split it by hand.
     *
     * @return array<int, string>
     */
    private function crud(array $resources, bool $restore = false): array
    {
        $permissions = [];

        foreach ($resources as $resource) {
            foreach (['view_any', 'create', 'update', 'delete'] as $ability) {
                $permissions[] = "{$ability}_{$resource}";
            }

            if ($restore && in_array($resource, self::SOFT_DELETE_RESOURCES, true)) {
                $permissions[] = "restore_{$resource}";
            }
        }

        return $permissions;
    }
}
