<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Page;
use App\Seo\ServiceEntityMap;
use App\Support\Content\PublishedLinkFilter;
use Database\Seeders\ArticleContentSeeder;
use Database\Seeders\ProductionContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The entity map is a promise about who owns which query. These tests
 * hold it to that promise, so the map cannot quietly rot as services and
 * articles come and go.
 */
class ServiceEntityMapTest extends TestCase
{
    use RefreshDatabase;

    private ServiceEntityMap $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->map = new ServiceEntityMap;
    }

    public function test_no_two_services_claim_the_same_query(): void
    {
        $collisions = $this->map->collisions();

        $this->assertSame([], $collisions, 'two services chasing one phrase is cannibalisation: '.json_encode(array_keys($collisions), JSON_UNESCAPED_UNICODE));
    }

    public function test_every_service_in_the_catalogue_has_an_entry(): void
    {
        $this->seed(ProductionContentSeeder::class);

        $slugs = Page::query()->where('type', PageType::Service)->pluck('slug');

        foreach ($slugs as $slug) {
            $this->assertNotNull($this->map->for($slug), "{$slug} has no entry in the entity map");
        }
    }

    public function test_every_entry_is_complete_and_uses_a_known_intent(): void
    {
        foreach ($this->map->all() as $slug => $entry) {
            $this->assertNotEmpty($entry['primary'], "{$slug}: no primary keyword");
            $this->assertNotEmpty($entry['secondary'], "{$slug}: no secondary phrases");
            $this->assertContains($entry['intent'], ['transactional', 'commercial', 'informational'], "{$slug}: unknown intent");
            $this->assertNotEmpty($entry['related'], "{$slug}: no related services");
        }
    }

    public function test_every_related_service_and_supporting_article_exists(): void
    {
        $this->seed(ProductionContentSeeder::class);
        $this->seed(ArticleContentSeeder::class);

        $services = Page::query()->where('type', PageType::Service)->pluck('slug')->all();
        $articles = Page::query()->where('type', PageType::Article)->pluck('slug')->all();

        foreach ($this->map->all() as $slug => $entry) {
            foreach ($entry['related'] as $related) {
                $this->assertContains($related, $services, "{$slug}: related service {$related} does not exist");
                $this->assertNotSame($slug, $related, "{$slug}: a service cannot be related to itself");
            }

            foreach ($entry['articles'] as $article) {
                $this->assertContains($article, $articles, "{$slug}: supporting article {$article} does not exist");
            }
        }
    }

    public function test_every_service_the_company_performs_is_published_and_mapped(): void
    {
        // Owner decision, 2026-09-20: every service in the catalogue is one
        // the company can actually perform, so all of them publish. A
        // missing photo or a secondary operational detail never holds a
        // page at Review - pools, pest control and periodic contracts
        // included.
        $manifest = json_decode((string) file_get_contents(database_path('seeders/content/launch-manifest.json')), true);

        $this->seed(ProductionContentSeeder::class);
        $catalogue = Page::query()->where('type', PageType::Service)->pluck('slug');

        foreach ($catalogue as $slug) {
            $this->assertContains($slug, $manifest['publish']['services'], "{$slug} is a service we perform, so the manifest must publish it");
            $this->assertNotNull($this->map->for($slug), "{$slug} is published, so it must own its queries in the entity map");
        }

        $this->assertEmpty(
            array_intersect($catalogue->all(), array_keys($manifest['_excluded_pending_decision'])),
            'no service may sit in the pending-decision block',
        );
    }

    public function test_a_page_that_is_not_published_is_never_linked_from_one_that_is(): void
    {
        $page = Page::factory()->create(['type' => PageType::Service, 'slug' => 'held-service', 'status' => PageStatus::Review]);

        // A bare factory page cannot pass the Publishing Gate (no content,
        // no SEO metadata), and PageObserver would push it straight back to
        // Draft. The filter reads the stored status, so write it quietly:
        // what is under test here is the filter, not the gate.
        $live = Page::factory()->create(['type' => PageType::Service, 'slug' => 'live-service']);
        $live->forceFill(['status' => PageStatus::Published, 'published_at' => now()])->saveQuietly();

        $filter = app(PublishedLinkFilter::class);
        $html = '<p>راجع <a href="/services/held-service">الخدمة المحجوزة</a> و<a href="/services/live-service">الخدمة المنشورة</a>.</p>';

        $filtered = $filter->filter($html);

        $this->assertStringNotContainsString('/services/held-service', $filtered, 'a link to an unpublished page is unwrapped');
        $this->assertStringContainsString('الخدمة المحجوزة', $filtered, 'but its text stays in the sentence');
        $this->assertStringContainsString('href="/services/live-service"', $filtered);
        $this->assertNotNull($page->id.$live->id);
    }

    public function test_the_filter_leaves_external_links_and_action_routes_alone(): void
    {
        $filter = app(PublishedLinkFilter::class);
        $html = '<p><a href="https://wa.me/966534999194">واتساب</a> و<a href="/quote">اطلب عرض سعر</a> و<a href="/warranty">الضمان</a>.</p>';

        $this->assertSame($html, $filter->filter($html));
    }
}
