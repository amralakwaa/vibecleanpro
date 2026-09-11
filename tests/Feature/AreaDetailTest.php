<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

class AreaDetailTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    public function test_attaching_a_service_to_an_area_never_auto_creates_a_servicexarea_page(): void
    {
        $countBefore = Page::query()->count();

        $area = Area::factory()->create();
        $service = Service::factory()->create();
        $area->services()->attach($service);

        $this->assertSame($countBefore, Page::query()->count(), 'Linking a Service to an Area must never silently create a Service x Area page.');
    }

    public function test_the_area_hero_carries_a_request_quote_cta_prefilled_with_this_area(): void
    {
        $page = $this->createCompliantAreaPage(slug: 'area-quote-cta');

        $response = $this->get('/areas/area-quote-cta');

        $response->assertSee(route('public.quote').'?area='.$page->pageable->id, false);
    }
}
