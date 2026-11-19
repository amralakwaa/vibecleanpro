<?php

namespace Tests\Feature;

use App\Models\ServiceCategory;
use App\Support\PublicPageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Internal links have to reach every service, not only the first few:
 * "related services" follows the Service Entity Map and then rotates
 * through the category, and the footer names each category's services.
 */
class ServiceLinkDistributionTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_related_services_follow_the_entity_map_siblings_first(): void
    {
        $category = ServiceCategory::factory()->create();
        $pages = collect(['glass-facade-cleaning', 'facade-cleaning', 'glass-cleaning', 'cladding-cleaning', 'home-cleaning'])
            ->mapWithKeys(function (string $slug, int $order) use ($category) {
                $page = $this->createCompliantServicePage(slug: $slug);
                $page->pageable->update(['service_category_id' => $category->id, 'sort_order' => $order + 1]);

                return [$slug => $page];
            });

        $html = $this->get('/services/glass-facade-cleaning')->assertOk()->getContent();
        $related = substr($html, strpos($html, 'خدمات ذات صلة'));

        $this->assertStringContainsString('/services/facade-cleaning', $related);
        $this->assertStringContainsString('/services/glass-cleaning', $related);
        $this->assertStringContainsString('/services/cladding-cleaning', $related);
        $this->assertStringNotContainsString('/services/home-cleaning"', substr($related, 0, strpos($related, '</section>')));
    }

    public function test_a_service_outside_the_map_links_to_the_next_services_in_its_category(): void
    {
        $category = ServiceCategory::factory()->create();
        foreach (['svc-a', 'svc-b', 'svc-c', 'svc-d', 'svc-e'] as $order => $slug) {
            $page = $this->createCompliantServicePage(slug: $slug);
            $page->pageable->update(['service_category_id' => $category->id, 'sort_order' => $order + 1]);
        }

        $html = $this->get('/services/svc-d')->assertOk()->getContent();
        $related = substr($html, strpos($html, 'خدمات ذات صلة'));
        $related = substr($related, 0, strpos($related, '</section>'));

        // svc-d is 4th: the ring continues with svc-e, then wraps to svc-a and svc-b.
        foreach (['svc-e', 'svc-a', 'svc-b'] as $slug) {
            $this->assertStringContainsString('/services/'.$slug.'"', $related);
        }
        $this->assertStringNotContainsString('/services/svc-c"', $related);
    }

    public function test_the_footer_lists_services_under_their_categories(): void
    {
        $homes = ServiceCategory::factory()->create(['name' => 'خدمات سكنية']);
        $business = ServiceCategory::factory()->create(['name' => 'خدمات المنشآت']);
        $this->createCompliantServicePage(slug: 'home-cleaning')->pageable->update(['service_category_id' => $homes->id]);
        $this->createCompliantServicePage(slug: 'factory-cleaning')->pageable->update(['service_category_id' => $business->id]);
        Cache::forget(PublicPageCache::LAYOUT_CHROME);

        $footer = $this->get('/contact')->assertOk()->getContent();
        $footer = substr($footer, strpos($footer, '<footer'));

        $this->assertStringContainsString('خدمات سكنية', $footer);
        $this->assertStringContainsString('خدمات المنشآت', $footer);
        $this->assertStringContainsString('/services/factory-cleaning', $footer);
        $this->assertStringContainsString('/services/home-cleaning', $footer);
    }
}
