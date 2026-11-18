<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Area;
use App\Models\AreaGroup;
use App\Models\ContentBlock;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * A side-of-Riyadh landing page describes the services its districts
 * offer, so it carries one Service per published, actively-linked service.
 */
class AreaGroupStructuredDataTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private function landing(AreaGroup $group): void
    {
        $page = Page::factory()->create(['type' => PageType::Landing, 'slug' => $group->slug, 'published_at' => now()->subDay()]);
        $page->pageable()->associate($group);
        $page->status = PageStatus::Published;
        $page->saveQuietly();
        ContentBlock::factory()->for($page)->create(['type' => 'rich_text', 'data' => ['content' => 'أحياء.']]);
    }

    public function test_the_landing_page_lists_the_services_of_its_districts(): void
    {
        $group = AreaGroup::factory()->create(['name' => 'شمال الرياض', 'slug' => 'north-riyadh']);
        $area = Area::factory()->create(['area_group_id' => $group->id]);
        $linked = $this->createCompliantServicePage(slug: 'villa-linked')->pageable;
        $inactive = $this->createCompliantServicePage(slug: 'inactive-link')->pageable;
        $area->services()->attach($linked->id, ['is_active' => true]);
        $area->services()->attach($inactive->id, ['is_active' => false]);
        $this->landing($group);

        $response = $this->get('/areas/north-riyadh');

        $response->assertOk();
        $response->assertSee('"@type":"Service","name":"'.$linked->name.'"', false);
        $response->assertSee('"areaServed":{"@type":"Place","name":"شمال الرياض"}', false);
        $response->assertDontSee('"name":"'.$inactive->name.'"', false);
    }
}
