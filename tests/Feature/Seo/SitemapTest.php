<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Models\Area;
use App\Seo\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private SitemapGenerator $sitemap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sitemap = app(SitemapGenerator::class);
    }

    public function test_a_published_indexable_page_appears_in_the_sitemap(): void
    {
        $this->createCompliantServicePage(slug: 'deep-cleaning');

        $locs = $this->sitemap->entries()->pluck('loc');

        $this->assertTrue($locs->contains(rtrim(config('app.url'), '/').'/services/deep-cleaning'));
    }

    public function test_a_draft_page_never_appears_in_the_sitemap(): void
    {
        $this->makeBarePage(['status' => PageStatus::Draft, 'slug' => 'draft-page']);

        $this->assertCount(0, $this->sitemap->entries());
    }

    public function test_a_review_page_never_appears_in_the_sitemap(): void
    {
        $this->makeBarePage(['status' => PageStatus::Review, 'slug' => 'review-page']);

        $this->assertCount(0, $this->sitemap->entries());
    }

    public function test_an_archived_page_never_appears_in_the_sitemap(): void
    {
        $this->makeBarePage(['status' => PageStatus::Archived, 'slug' => 'archived-page']);

        $this->assertCount(0, $this->sitemap->entries());
    }

    public function test_a_manually_noindexed_published_page_never_appears_in_the_sitemap(): void
    {
        $page = $this->createCompliantServicePage(slug: 'noindexed-service');
        $page->seoMetadata->update(['robots_index' => false]);

        $locs = $this->sitemap->entries()->pluck('loc');

        $this->assertFalse($locs->contains(fn ($loc) => str_contains($loc, 'noindexed-service')));
    }

    public function test_a_page_scheduled_in_the_future_never_appears_in_the_sitemap(): void
    {
        $this->makeBarePage([
            'status' => PageStatus::Published,
            'published_at' => now()->addWeek(),
            'slug' => 'scheduled-page',
        ]);

        $this->assertCount(0, $this->sitemap->entries());
    }

    public function test_a_soft_deleted_page_never_appears_in_the_sitemap(): void
    {
        $page = $this->createCompliantServicePage(slug: 'soon-to-be-deleted');
        $page->delete();

        $locs = $this->sitemap->entries()->pluck('loc');

        $this->assertFalse($locs->contains(fn ($loc) => str_contains($loc, 'soon-to-be-deleted')));
    }

    public function test_a_page_whose_canonical_points_elsewhere_is_excluded_from_the_sitemap(): void
    {
        $page = $this->createCompliantServicePage(slug: 'duplicate-variant');
        $page->seoMetadata->update([
            'canonical_url' => rtrim(config('app.url'), '/').'/services/deep-cleaning',
        ]);

        $locs = $this->sitemap->entries()->pluck('loc');

        $this->assertFalse($locs->contains(fn ($loc) => str_contains($loc, 'duplicate-variant')));
    }

    public function test_a_self_referencing_canonical_still_appears_in_the_sitemap(): void
    {
        $page = $this->createCompliantServicePage(slug: 'self-canonical');
        $page->seoMetadata->update([
            'canonical_url' => rtrim(config('app.url'), '/').'/services/self-canonical',
        ]);

        $locs = $this->sitemap->entries()->pluck('loc');

        $this->assertTrue($locs->contains(rtrim(config('app.url'), '/').'/services/self-canonical'));
    }

    public function test_each_entry_carries_a_meaningful_lastmod(): void
    {
        $this->createCompliantServicePage(slug: 'lastmod-check');

        $entry = $this->sitemap->entries()->firstWhere('loc', rtrim(config('app.url'), '/').'/services/lastmod-check');

        $this->assertNotNull($entry['lastmod']);
    }

    public function test_the_sitemap_route_returns_valid_xml_with_only_eligible_urls(): void
    {
        $this->createCompliantServicePage(slug: 'in-sitemap');
        $this->makeBarePage(['status' => PageStatus::Draft, 'slug' => 'not-in-sitemap']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('/services/in-sitemap', false);
        $response->assertDontSee('not-in-sitemap', false);
    }

    public function test_an_area_created_alone_with_no_page_produces_no_sitemap_entry(): void
    {
        Area::factory()->create(['slug' => 'orphan-area-no-page']);

        $locs = $this->sitemap->entries()->pluck('loc');

        $this->assertFalse($locs->contains(fn ($loc) => str_contains($loc, 'orphan-area-no-page')));
    }
}
