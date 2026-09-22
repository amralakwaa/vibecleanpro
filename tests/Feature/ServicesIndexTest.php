<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class ServicesIndexTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_only_services_with_a_published_page_are_listed(): void
    {
        $this->createCompliantServicePage(slug: 'listed-service');

        // A Service with no Page at all, and one whose Page is a Draft -
        // neither should ever appear.
        Service::factory()->create(['name' => 'pageless-service']);
        $draftPage = $this->createCompliantServicePage(slug: 'draft-service', status: PageStatus::Draft);

        $html = $this->get('/services')->getContent();

        $this->assertStringContainsString('listed-service', $html);
        $this->assertStringNotContainsString('pageless-service', $html);
        $this->assertStringNotContainsString($draftPage->pageable->name, $html);
    }

    public function test_a_review_status_service_is_not_listed(): void
    {
        $this->createCompliantServicePage(slug: 'review-service', status: PageStatus::Review);

        $this->get('/services')->assertDontSee('review-service');
    }

    public function test_the_index_shows_an_empty_state_when_no_services_are_published(): void
    {
        $response = $this->get('/services');

        $response->assertOk();
        $response->assertSee('خدماتنا في طريقها إلى هذه الصفحة');
        // Customer-facing wording only - never the admin's "published".
        $response->assertDontSee('منشورة');
    }

    public function test_the_index_page_returns_200_and_declares_arabic_rtl(): void
    {
        $this->createCompliantServicePage();

        $response = $this->get('/services');

        $response->assertOk();
        $this->assertMatchesRegularExpression('#<html[^>]*lang="ar"[^>]*dir="rtl"#', $response->getContent());
    }

    public function test_the_index_never_invents_a_price(): void
    {
        $this->createCompliantServicePage();

        $html = $this->get('/services')->getContent();

        $this->assertStringNotContainsString('ريال', $html);
    }

    public function test_services_are_grouped_under_their_real_category_in_category_order(): void
    {
        $later = ServiceCategory::factory()->create(['name' => 'فئة-متأخرة', 'sort_order' => 2]);
        $earlier = ServiceCategory::factory()->create(['name' => 'فئة-مبكرة', 'sort_order' => 1]);
        // The later category's service sorts first so only the category
        // order - not the service order - can put it second.
        $this->publishedService('later-cat-svc', ['service_category_id' => $later->id, 'sort_order' => 0]);
        $this->publishedService('earlier-cat-svc', ['service_category_id' => $earlier->id, 'sort_order' => 5]);
        $this->publishedService('uncategorised-svc', ['sort_order' => 9]);

        $html = $this->get('/services')->assertOk()->getContent();
        // Strip JSON-LD blocks before position checks — the schema may list
        // services in a different order than the visual HTML grouping.
        $body = preg_replace('/<script type="application\/ld\+json">.*?<\/script>/su', '', $html);

        $this->assertMatchesRegularExpression('/<h2[^>]*>فئة-مبكرة<\/h2>/u', $html);
        $this->assertLessThan(mb_strpos($body, 'فئة-متأخرة'), mb_strpos($body, 'فئة-مبكرة'));
        $this->assertLessThan(mb_strpos($body, 'later-cat-svc'), mb_strpos($body, 'earlier-cat-svc'));
        // Uncategorised services close the list under a neutral heading,
        // and a category heading is never a link to a page that does not exist.
        $this->assertLessThan(mb_strpos($body, 'uncategorised-svc'), mb_strpos($body, 'خدمات أخرى'));
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*>\s*فئة-مبكرة\s*</u', $html);
    }

    public function test_no_category_headings_appear_when_no_listed_service_has_a_category(): void
    {
        ServiceCategory::factory()->create(['name' => 'فئة-بلا-خدمات']);
        $this->publishedService('flat-svc');

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringNotContainsString('فئة-بلا-خدمات', $html);
        $this->assertStringNotContainsString('خدمات أخرى', $html);
    }

    public function test_a_featured_service_leads_and_is_labelled_honestly_once(): void
    {
        $this->publishedService('plain-first', ['sort_order' => 0]);
        $this->publishedService('featured-later', ['sort_order' => 9, 'is_featured' => true]);

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertLessThan(mb_strpos($html, 'plain-first'), mb_strpos($html, 'featured-later'));
        $this->assertSame(1, substr_count($html, 'خدمة مميزة'));
        // is_featured is an editor toggle, not demand data - never dress it up as popularity.
        $this->assertStringNotContainsString('الأكثر طلبًا', $html);
    }

    public function test_the_compact_index_links_to_rows_on_the_same_page_only_when_worth_it(): void
    {
        $services = collect(range(1, 4))->map(fn (int $i) => $this->publishedService("indexed-$i"));

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString('فهرس الخدمات', $html);
        foreach ($services as $service) {
            $this->assertStringContainsString('href="#service-'.$service->id.'"', $html);
            $this->assertStringContainsString('id="service-'.$service->id.'"', $html);
        }

        $services->last()->page->update(['status' => PageStatus::Draft]);
        $this->assertStringNotContainsString('فهرس الخدمات', $this->get('/services')->getContent());
    }

    public function test_the_index_triggers_no_lazy_loading(): void
    {
        $category = ServiceCategory::factory()->create();
        foreach (range(1, 3) as $i) {
            $this->publishedService("lazy-$i", ['service_category_id' => $i === 3 ? null : $category->id]);
        }

        $violations = [];
        Model::preventLazyLoading(true);
        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use (&$violations) {
            $violations[] = $model::class.'::'.$relation;
        });

        try {
            $this->get('/services')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame([], $violations);
    }

    private function publishedService(string $slug, array $attributes = []): Service
    {
        $service = Service::factory()->create([...$attributes, 'name' => $slug]);
        $page = Page::factory()->create(['type' => PageType::Service, 'title' => $slug, 'slug' => $slug, 'status' => PageStatus::Draft]);
        $service->page()->save($page);
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => '<p>نص.</p>']]);
        $page->update(['status' => PageStatus::Published]);

        return $service->fresh(['page']);
    }
}
